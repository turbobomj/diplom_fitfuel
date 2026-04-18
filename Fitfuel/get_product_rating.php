<?php
require_once "db.php";
header('Content-Type: application/json');

if (!isset($_GET['product_id'])) {
    echo json_encode(['avg' => 0, 'count' => 0]);
    exit;
}

$product_id = (int)$_GET['product_id'];

$query = "SELECT AVG(rating) as avg_rating, COUNT(*) as reviews_count FROM reviews WHERE product_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

echo json_encode([
    'avg' => round($data['avg_rating'] ?? 0, 1),
    'count' => $data['reviews_count'] ?? 0
]);
?>