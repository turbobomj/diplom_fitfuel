<?php
header('Content-Type: application/json');
require_once "db.php";

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'ID товара не указан']);
    exit;
}

$id = (int)$_GET['id'];

$query = "SELECT id, name, price, image, description, composition, `usage` FROM products WHERE id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

if ($product) {
    echo json_encode([
        'id' => (int)$product['id'],
        'name' => $product['name'],
        'price' => (float)$product['price'],
        'image' => $product['image'],
        'description' => $product['description'] ?? '',
        'composition' => $product['composition'] ?? '',
        'usage' => $product['usage'] ?? ''
    ]);
} else {
    echo json_encode(['error' => 'Товар не найден']);
}
?>