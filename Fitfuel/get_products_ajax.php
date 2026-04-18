<?php
session_start();
require_once "db.php";

function getProductRating($conn, $product_id) {
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as reviews_count FROM reviews WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return ['avg' => round($data['avg_rating'] ?? 0, 1), 'count' => $data['reviews_count'] ?? 0];
}

function getProductsByCategory($conn, $category_id = null) {
    if ($category_id && $category_id !== 'all') {
        $stmt = mysqli_prepare($conn, "SELECT * FROM products WHERE category_id = ? ORDER BY id DESC");
        mysqli_stmt_bind_param($stmt, "i", $category_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
    } else {
        $result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
    }
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tags = [];
        if (isset($row['is_hit']) && $row['is_hit']) $tags[] = 'hit';
        if (isset($row['is_sale']) && $row['is_sale']) $tags[] = 'sale';
        if (isset($row['is_new']) && $row['is_new']) $tags[] = 'new';
        $row['tags'] = $tags;
        $products[] = $row;
    }
    if (isset($stmt)) mysqli_stmt_close($stmt);
    return $products;
}

$category_id = isset($_GET['category']) ? $_GET['category'] : 'all';
$products = getProductsByCategory($conn, $category_id !== 'all' ? $category_id : null);

$category_name = 'Все товары';
if ($category_id !== 'all') {
    $stmt = mysqli_prepare($conn, "SELECT name FROM categories WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $category_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $cat = mysqli_fetch_assoc($result);
    if ($cat) $category_name = $cat['name'];
    mysqli_stmt_close($stmt);
}

ob_start();
if (empty($products)): ?>
    <div class="no-products"><i class="fas fa-box-open"></i><h3>Товаров не найдено</h3><a href="#" onclick="loadCategory('all'); return false;">Все товары</a></div>
<?php else: ?>
    <?php foreach ($products as $p):
        $rating = getProductRating($conn, $p['id']);
        $fullStars = floor($rating['avg']);
        $halfStar = ($rating['avg'] - $fullStars) >= 0.5;
        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
        $salePrice = $p['price'];
        $oldPrice = null;
        if (in_array('sale', $p['tags'])) {
            $oldPrice = $p['price'];
            $salePrice = round($p['price'] * 0.85);
        }
    ?>
        <div class="product-card"> 
            <div style="position: relative;">
                <img src="<?= htmlspecialchars($p['image']) ?>">
                <?php if (in_array('hit', $p['tags'])): ?>
                    <div style="position: absolute; top: 10px; left: 10px; background: linear-gradient(135deg, #FF6B6B, #FF4757); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;"><i class="fas fa-fire"></i> ХИТ</div>
                <?php endif; ?>
                <?php if (in_array('sale', $p['tags'])): ?>
                    <div style="position: absolute; top: 10px; right: 10px; background: linear-gradient(135deg, #FFA502, #FF7F00); color: #fff; padding: 4px 12px; border-radius: 20px; font-size: 0.75rem;">-15%</div>
                <?php endif; ?>
            </div>
            <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
            <div class="product-rating">
                <div class="stars">
                    <?php for($i = 0; $i < $fullStars; $i++): ?><i class="fas fa-star"></i><?php endfor; ?>
                    <?php if($halfStar): ?><i class="fas fa-star-half-alt"></i><?php endif; ?>
                    <?php for($i = 0; $i < $emptyStars; $i++): ?><i class="far fa-star"></i><?php endfor; ?>
                </div>
                <span class="rating-value"><?= number_format($rating['avg'], 1, '.', '') ?></span>
                <span class="reviews-count">(<?= $rating['count'] ?> отзывов)</span>
            </div>
            <div class="product-price">
                <?= number_format($salePrice, 0, '.', ' ') ?> ₽
                <?php if ($oldPrice): ?><span style="text-decoration: line-through; margin-left: 8px;"><?= number_format($oldPrice, 0, '.', ' ') ?> ₽</span><?php endif; ?>
            </div>
            <div class="product-actions">
                <button class="btn btn-details" data-id="<?= (int)$p['id'] ?>"><i class="fas fa-info-circle"></i> Подробнее</button>
                <button class="btn btn-order" data-id="<?= (int)$p['id'] ?>"><i class="fas fa-shopping-cart"></i> Заказать</button>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif;
$products_html = ob_get_clean();

header('Content-Type: application/json');
echo json_encode(['html' => $products_html, 'count' => count($products), 'category_name' => $category_name]);
?>