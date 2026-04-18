<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Требуется авторизация']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не разрешён']);
    exit;
}

require_once "db.php";

$product_id = (int)($_POST['product_id'] ?? 0);
$rating     = (int)($_POST['rating']     ?? 0);
$comment    = trim($_POST['comment']    ?? '');

if ($product_id < 1 || $rating < 1 || $rating > 5 || mb_strlen($comment) < 5) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Некорректные данные']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];

$stmt = $conn->prepare("
    INSERT INTO reviews (product_id, user_id, rating, comment)
    VALUES (?, ?, ?, ?)
");
$stmt->bind_param("iiis", $product_id, $user_id, $rating, $comment);

$success = $stmt->execute();

if ($success) {
    // можно сразу вернуть новый отзыв для мгновенного отображения
    $new_id = $conn->insert_id;
    
    $user_stmt = $conn->prepare("SELECT login FROM users WHERE id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_res = $user_stmt->get_result();
    $user = $user_res->fetch_assoc();
    $username = $user['login'] ?? 'Пользователь';

    echo json_encode([
        'success' => true,
        'review' => [
            'author'   => $username,
            'date'     => date('d.m.Y'),
            'rating'   => $rating,
            'text'     => htmlspecialchars($comment),
        ]
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка базы данных']);
}

$stmt->close();