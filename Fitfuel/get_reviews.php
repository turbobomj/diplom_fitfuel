<?php
header('Content-Type: application/json; charset=utf-8');

require_once "db.php";

$product_id = (int)($_GET['product_id'] ?? 0);

if ($product_id < 1) {
    http_response_code(400);
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        r.rating, r.comment, r.created_at,
        u.login AS author
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.product_id = ?
    ORDER BY r.created_at DESC
    LIMIT 20
");

$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();

$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = [
        'author' => $row['author'],
        'date'   => date('d.m.Y', strtotime($row['created_at'])),
        'rating' => (int)$row['rating'],
        'text'   => htmlspecialchars($row['comment']),
    ];
}

echo json_encode($reviews);

$stmt->close();