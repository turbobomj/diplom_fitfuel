<?php
session_start();
require_once "db.php";

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['can_review' => false, 'error' => 'not_logged_in']);
    exit;
}

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
if (!$product_id) {
    echo json_encode(['can_review' => false, 'error' => 'invalid_product']);
    exit;
}

$query = "SELECT COUNT(*) as count FROM orders o 
          JOIN order_items oi ON o.id = oi.order_id 
          WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "ii", $_SESSION['user_id'], $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$data = mysqli_fetch_assoc($result);

$can_review = ($data['count'] ?? 0) > 0;

echo json_encode(['can_review' => $can_review]);