<?php
// process_order.php
session_start();
require_once "db.php"; // должен давать $conn (mysqli)

// авторизация
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo "Ошибка: пользователь не авторизован";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo "Неверный метод";
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// читаем поля формы
$fio    = isset($_POST['fio']) ? trim($_POST['fio']) : '';
$phone  = isset($_POST['phone']) ? trim($_POST['phone']) : '';
$city   = isset($_POST['city']) ? trim($_POST['city']) : '';
$street = isset($_POST['street']) ? trim($_POST['street']) : '';
$house  = isset($_POST['house']) ? trim($_POST['house']) : '';
$flat   = isset($_POST['flat']) ? trim($_POST['flat']) : '';
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Получаем корзину: сначала cart_json или cart_data, иначе fallback на product_id/quantity
$raw = '';
if (!empty($_POST['cart_json'])) $raw = $_POST['cart_json'];
elseif (!empty($_POST['cart_data'])) $raw = $_POST['cart_data'];

// Если пришло не в JSON — поддержим старый hidden input с JSON-строкой
$cart_map = []; // ключ product_id => qty

if ($raw !== '') {
    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        foreach ($decoded as $it) {
            $id = isset($it['id']) ? (int)$it['id'] : (isset($it['product_id']) ? (int)$it['product_id'] : 0);
            $qty = isset($it['qty']) ? (int)$it['qty'] : (isset($it['quantity']) ? (int)$it['quantity'] : 1);
            if ($id > 0 && $qty > 0) $cart_map[$id] = ($cart_map[$id] ?? 0) + $qty;
        }
    }
} else {
    // fallback: product_id[] & quantity[]
    if (isset($_POST['product_id'])) {
        $p = $_POST['product_id'];
        $q = isset($_POST['quantity']) ? $_POST['quantity'] : [];
        if (is_array($p)) {
            foreach ($p as $i => $pid) {
                $id = (int)$pid;
                $qty = isset($q[$i]) ? (int)$q[$i] : 1;
                if ($id > 0 && $qty > 0) $cart_map[$id] = ($cart_map[$id] ?? 0) + $qty;
            }
        } else {
            $id = (int)$p;
            $qty = is_array($q) ? 1 : (int)$q;
            if ($id > 0 && $qty > 0) $cart_map[$id] = ($cart_map[$id] ?? 0) + $qty;
        }
    }
}

if (empty($cart_map)) {
    echo "Корзина пуста";
    exit;
}

// Получаем актуальные цены и данные товаров из БД (защита от подмены цен)
$ids = array_keys($cart_map);
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$types = str_repeat('i', count($ids));

$sql = "SELECT id, price, name, image FROM products WHERE id IN ($placeholders)";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    http_response_code(500);
    echo "DB prepare error: " . htmlspecialchars($conn->error);
    exit;
}
$stmt->bind_param($types, ...$ids);
$stmt->execute();
$res = $stmt->get_result();

$products = [];
while ($row = $res->fetch_assoc()) {
    $products[(int)$row['id']] = [
        'price' => (float)$row['price'],
        'name'  => $row['name'],
        'image' => $row['image'],
    ];
}
$stmt->close();

// Формируем финальный список айтемов (используем цену из БД)
$final_items = [];
$total_amount = 0.0;

foreach ($cart_map as $pid => $qty) {
    if (!isset($products[$pid])) continue; // товар не найден — пропустить
    $unit = (float)$products[$pid]['price'];
    $subtotal = $unit * $qty;
    $total_amount += $subtotal;
    $final_items[] = [
        'id' => $pid,
        'qty' => $qty,
        'price' => $unit
    ];
}

if (empty($final_items)) {
    echo "Нет корректных товаров";
    exit;
}

// Вставляем заказ и айтемы в транзакции
mysqli_begin_transaction($conn);

try {
    $sql_order = "INSERT INTO orders (user_id, fio, phone, city, street, house, flat, comment, total_amount, status, created_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'new', NOW())";
    $s = $conn->prepare($sql_order);
    if ($s === false) throw new Exception($conn->error);
    $s->bind_param("isssssssd",
        $user_id, $fio, $phone, $city, $street, $house, $flat, $comment, $total_amount
    );
    if (!$s->execute()) throw new Exception($s->error);
    $order_id = $conn->insert_id;
    $s->close();

    $sql_item = "INSERT INTO order_items (order_id, product_id, qty, price) VALUES (?, ?, ?, ?)";
    $si = $conn->prepare($sql_item);
    if ($si === false) throw new Exception($conn->error);

    foreach ($final_items as $it) {
        $pid = (int)$it['id'];
        $qty = (int)$it['qty'];
        $price = (float)$it['price'];
        $si->bind_param("iiid", $order_id, $pid, $qty, $price);
        if (!$si->execute()) throw new Exception($si->error);
    }
    $si->close();

    mysqli_commit($conn);

    // Ответ — скрипт для очистки корзины у клиента и редирект (как у тебя раньше)
    echo "<script>
            try { localStorage.removeItem('cart'); } catch(e){}
            window.location.href = 'order_history.php';
          </script>";
    exit;

} catch (Exception $ex) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo "Ошибка при оформлении: " . htmlspecialchars($ex->getMessage());
    exit;
}
