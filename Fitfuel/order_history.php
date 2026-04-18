<?php
session_start();
require_once "db.php"; // должен предоставлять $conn (mysqli)

function isLoggedIn() { return isset($_SESSION['user_id']); }
if (!isLoggedIn()) {
    header('Location: logreg.php');
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Получаем заказы пользователя
$orders = [];
$stmt = $conn->prepare("SELECT id, fio, phone, city, street, house, flat, comment, status, total_amount, created_at FROM `orders` WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

// Для каждого заказа подгружаем товары
foreach ($orders as &$order) {
    $order_id = (int)$order['id'];
    $items = [];
    $qi = $conn->prepare("
    SELECT 
        oi.qty, 
        oi.price AS item_price, 
        p.id AS product_id, 
        p.name, 
        p.image
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
    $qi->bind_param("i", $order_id);
    $qi->execute();
    $ri = $qi->get_result();
    while ($r = $ri->fetch_assoc()) {
        $items[] = $r;
    }
    $qi->close();
    $order['items'] = $items;
}

// РУССКИЕ статусы заказов
$status_labels = [
    'new' => ['label' => '🆕 Новый', 'color' => '#0E9AA7', 'icon' => 'fa-clock'],
    'processing' => ['label' => '⚙️ В обработке', 'color' => '#F59E0B', 'icon' => 'fa-cogs'],
    'shipped' => ['label' => '🚚 Отправлен', 'color' => '#3B82F6', 'icon' => 'fa-truck'],
    'delivered' => ['label' => '✅ Доставлен', 'color' => '#10B981', 'icon' => 'fa-check-circle'],
    'completed' => ['label' => '✅ Завершён', 'color' => '#10B981', 'icon' => 'fa-check-circle'],
    'cancelled' => ['label' => '❌ Отменён', 'color' => '#EF4444', 'icon' => 'fa-times-circle'],
    'pending' => ['label' => '⏳ Ожидает', 'color' => '#6B7280', 'icon' => 'fa-hourglass-half'],
];
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>FitFuel — История заказов</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0E9AA7;
            --primary-dark: #0A7A85;
            --primary-light: #E6F7F9;
            --accent-color: #FF6B6B;
            --text-color: #1E3A5F;
            --text-light: #4A5568;
            --bg-white: #FFFFFF;
            --shadow: 0 10px 25px rgba(14, 154, 167, .12);
            --card-shadow: 0 8px 20px rgba(30,58,95,0.06);
            --radius: 16px;
            --transition: .35s cubic-bezier(.2,.8,.2,1);
        }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Montserrat, sans-serif; background: #f6f9fb; color: var(--text-color); -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 20px; }

        .topbar { display:flex; justify-content:space-between; align-items:center; gap:16px; margin-bottom:20px; }
        .topbar h1 { margin:0; font-size:1.6rem; font-weight:800; display: flex; align-items: center; gap: 10px; }
        .topbar h1 i { color: var(--primary-color); font-size: 1.8rem; }
        .topbar-actions { display:flex; gap:12px; align-items:center; flex-wrap: wrap; }
        .user-chip { display:flex; align-items:center; gap:10px; background:linear-gradient(135deg,var(--primary-color),var(--primary-dark)); color:#fff; padding:10px 16px; border-radius:999px; box-shadow: var(--card-shadow); font-weight:700; text-decoration:none; }
        .user-chip a { color:#fff; text-decoration:none; margin-left:8px; font-weight:600; opacity:.95; transition: opacity 0.2s; }
        .user-chip a:hover { opacity: 1; }

        .btn { padding:10px 14px; border-radius:12px; border:none; cursor:pointer; font-weight:700; font-size:.95rem; transition: .2s; display:inline-flex; gap:8px; align-items:center; text-decoration:none; }
        .btn-ghost { background:var(--primary-light); color:var(--text-color); }
        .btn-ghost:hover { background:var(--primary-color); color:#fff; transform: translateY(-2px); }
        .btn-primary { background:var(--primary-color); color:#fff; }
        .btn-primary:hover { background:var(--primary-dark); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(14,154,167,0.3); }
        .btn-small { padding:8px 12px; font-size:.9rem; }
        .btn-back { background:var(--primary-light); color:var(--text-color); text-decoration:none; }
        .btn-back:hover { background:var(--primary-color); color:#fff; transform: translateX(-3px); }

        .orders-list { display:flex; flex-direction:column; gap:18px; }

        .order-card { background:var(--bg-white); border-radius:var(--radius); box-shadow:var(--shadow); padding:18px; transition:var(--transition); overflow:hidden; border:1px solid rgba(14,154,167,0.04); }
        .order-card:hover { transform: translateY(-2px); box-shadow: 0 12px 28px rgba(14,154,167,0.15); }
        .order-header { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap: wrap; }
        .order-left { display:flex; gap:14px; align-items:center; }
        .order-meta { display:flex; flex-direction:column; gap:6px; }
        .order-id { font-weight:800; font-size:1.05rem; }
        .order-date { color:var(--text-light); font-size:.9rem; }
        .order-status { padding:8px 12px; border-radius:12px; font-weight:700; font-size:.85rem; color:white; display:inline-flex; align-items: center; gap: 6px; }
        .order-right { display:flex; gap:10px; align-items:center; flex-wrap: wrap; }
        .order-total { font-weight:900; font-size:1.1rem; color:var(--primary-color); }
        .order-actions { display:flex; gap:8px; flex-wrap: wrap; }

        .order-body { margin-top:14px; display:none; gap:14px; border-top:1px dashed rgba(0,0,0,0.06); padding-top:14px; }
        .order-body.active { display:flex; flex-direction:column; }

        .items-grid { display:flex; flex-direction:column; gap:12px; }
        .item { display:flex; gap:12px; align-items:center; background:#fbfeff; padding:12px; border-radius:12px; border:1px solid rgba(10,120,130,0.03); transition: all 0.2s; }
        .item:hover { background: #fff; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .item img { width:72px; height:72px; object-fit:cover; border-radius:10px; }
        .item-info { flex:1; display:flex; justify-content:space-between; gap:12px; align-items:center; flex-wrap: wrap; }
        .item-name { font-weight:700; font-size: 1rem; }
        .item-meta { color:var(--text-light); font-size:.95rem; }
        .item-subtotal { font-weight:800; color:var(--text-color); background: var(--primary-light); padding: 4px 12px; border-radius: 20px; }

        .order-address { background:#ffffff; padding:12px; border-radius:10px; border:1px solid rgba(0,0,0,0.04); color:var(--text-light); font-size:.95rem; }
        .order-footer { display:flex; justify-content:space-between; gap:12px; align-items:center; margin-top:12px; flex-wrap: wrap; }

        .no-orders { text-align:center; padding:60px; background:linear-gradient(180deg,#fff,#fbfeff); border-radius:16px; box-shadow:var(--card-shadow); color:var(--text-light); }
        .no-orders h3 { font-size: 1.5rem; margin-bottom: 15px; color: var(--text-color); }
        .no-orders p { margin-bottom: 20px; }

        /* responsive */
        @media (max-width:720px) {
            .container { margin: 20px auto; }
            .topbar { flex-direction: column; align-items: flex-start; gap: 12px; }
            .topbar-actions { width: 100%; justify-content: space-between; flex-wrap: wrap; }
            .order-header { flex-direction:column; align-items:flex-start; gap:12px; }
            .order-right { width:100%; justify-content:space-between; }
            .item { flex-direction: column; text-align: center; }
            .item img { width: 100px; height: 100px; }
            .item-info { text-align: center; justify-content: center; }
            .order-actions { width: 100%; justify-content: space-between; }
        }

        @keyframes slideIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .order-card {
            animation: slideIn 0.3s ease-out;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <h1>
            <i class="fas fa-history"></i> 
            История заказов
        </h1>
        <div class="topbar-actions">
            <a href="index.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> На главную
            </a>
            <div class="user-chip">
                <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['user_login'] ?? 'Пользователь') ?>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </div>
        </div>
    </div>

    <?php if (empty($orders)): ?>
        <div class="no-orders">
            <i class="fas fa-shopping-bag" style="font-size: 4rem; color: var(--primary-color); opacity: 0.5; margin-bottom: 20px; display: inline-block;"></i>
            <h3>У вас пока нет заказов</h3>
            <p>Когда вы сделаете первый заказ, он появится здесь. А пока — загляните в каталог.</p>
            <p><a href="index.php#products" class="btn btn-primary"><i class="fas fa-arrow-right"></i> Перейти в каталог</a></p>
        </div>
    <?php else: ?>
        <div class="orders-list">
            <?php foreach ($orders as $ord): 
                $sid = htmlspecialchars($ord['status'] ?: 'pending');
                $slabel = isset($status_labels[$sid]) ? $status_labels[$sid]['label'] : '📦 ' . htmlspecialchars($ord['status']);
                $scolor = isset($status_labels[$sid]) ? $status_labels[$sid]['color'] : '#6B7280';
                $sicon = isset($status_labels[$sid]) ? $status_labels[$sid]['icon'] : 'fa-box';
                $created = htmlspecialchars(date("d.m.Y H:i", strtotime($ord['created_at'] ?? '')));
                $total = htmlspecialchars(number_format((float)$ord['total_amount'], 0, '.', ' ')) . " ₽";
                ?>
                <div class="order-card" data-order-id="<?= (int)$ord['id'] ?>">
                    <div class="order-header">
                        <div class="order-left">
                            <div class="order-meta">
                                <div class="order-id">
                                    <i class="fas fa-receipt"></i> Заказ №<?= (int)$ord['id'] ?>
                                    <span style="color:var(--text-light);font-weight:600;margin-left:8px;font-size:.95rem">
                                        <i class="far fa-calendar-alt"></i> <?= $created ?>
                                    </span>
                                </div>
                                <div class="order-date">
                                    <i class="fas fa-user"></i> <?= htmlspecialchars($ord['fio']) ?> · 
                                    <i class="fas fa-phone"></i> <?= htmlspecialchars($ord['phone']) ?>
                                </div>
                            </div>
                        </div>
                        <div class="order-right">
                            <div class="order-total">
                                <i class="fas fa-ruble-sign"></i> <?= $total ?>
                            </div>
                            <div class="order-actions">
                                <div class="order-status" style="background: <?= $scolor ?>">
                                    <i class="fas <?= $sicon ?>"></i> <?= $slabel ?>
                                </div>
                                <button class="btn btn-ghost btn-small toggle-details">
                                    <i class="fas fa-chevron-down"></i> Подробнее
                                </button>
                                <button class="btn btn-primary btn-small repeat-order">
                                    <i class="fas fa-redo-alt"></i> Повторить
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="order-body">
                        <div class="items-grid">
                            <?php if (!empty($ord['items'])): 
                                foreach ($ord['items'] as $it):
                                    $img = htmlspecialchars($it['image'] ?: 'images/no-image.png');
                                    $iname = htmlspecialchars($it['name']);
                                    $iprice = (float)$it['item_price'];
                                    $iqty = (int)$it['qty'];
                                    $subtotal = number_format($iprice * $iqty, 0, '.', ' ') . " ₽";
                                ?>
                                    <div class="item" 
                                         data-product-id="<?= (int)$it['product_id'] ?>" 
                                         data-price="<?= $iprice ?>" 
                                         data-qty="<?= $iqty ?>"
                                         data-name="<?= $iname ?>"
                                         data-image="<?= $img ?>">
                                        <img src="<?= $img ?>" alt="<?= $iname ?>">
                                        <div class="item-info">
                                            <div style="flex:1">
                                                <div class="item-name"><?= $iname ?></div>
                                                <div class="item-meta">
                                                    <i class="fas fa-tag"></i> <?= number_format($iprice, 0, '.', ' ') ?> ₽ × <?= $iqty ?> шт.
                                                </div>
                                            </div>
                                            <div class="item-subtotal">
                                                <i class="fas fa-calculator"></i> <?= $subtotal ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach;
                            else: ?>
                                <div style="color:var(--text-light);padding:8px 12px; text-align: center;">
                                    <i class="fas fa-box-open"></i> Товары для этого заказа отсутствуют.
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="display:flex;flex-direction:column;gap:10px;margin-top:8px">
                            <div class="order-address">
                                <i class="fas fa-map-marker-alt" style="color: var(--primary-color); margin-right: 8px;"></i>
                                <strong>Адрес доставки:</strong>
                                <?= htmlspecialchars($ord['city']) ?>, ул. <?= htmlspecialchars($ord['street']) ?>, д. <?= htmlspecialchars($ord['house']) ?>
                                <?= $ord['flat'] ? ', кв/офис ' . htmlspecialchars($ord['flat']) : '' ?>
                                <?php if (trim($ord['comment'])): ?>
                                    <div style="margin-top:6px">
                                        <i class="fas fa-comment"></i> <strong>Комментарий:</strong> <?= htmlspecialchars($ord['comment']) ?>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="order-footer">
                                <div style="display:flex;gap:8px; flex-wrap: wrap;">
                                    <button class="btn btn-primary repeat-order">
                                        <i class="fas fa-cart-plus"></i> Повторить заказ
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
// UI: открытие / закрытие деталей
document.querySelectorAll('.toggle-details').forEach(btn => {
    btn.addEventListener('click', (e) => {
        const card = btn.closest('.order-card');
        const body = card.querySelector('.order-body');
        const icon = btn.querySelector('i');
        body.classList.toggle('active');
        if (body.classList.contains('active')) { 
            icon.classList.remove('fa-chevron-down'); 
            icon.classList.add('fa-chevron-up'); 
            btn.innerHTML = '<i class="fas fa-chevron-up"></i> Скрыть';
        } else { 
            icon.classList.remove('fa-chevron-up'); 
            icon.classList.add('fa-chevron-down');
            btn.innerHTML = '<i class="fas fa-chevron-down"></i> Подробнее';
        }
    });
});

// Получение корзины из localStorage
function getCart() { 
    try { 
        return JSON.parse(localStorage.getItem('cart') || '[]'); 
    } catch(e) { 
        return []; 
    }
}

function saveCart(c) { 
    localStorage.setItem('cart', JSON.stringify(c)); 
}

// Показ уведомления
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10B981' : '#EF4444'};
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        z-index: 10000;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease-out;
    `;
    notification.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> ${message}`;
    document.body.appendChild(notification);
    setTimeout(() => {
        notification.style.opacity = '0';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Повторить заказ
async function repeatOrderFromCard(card) {
    const items = Array.from(card.querySelectorAll('.item')).map(it => {
        const productId = parseInt(it.dataset.productId, 10);
        const price = parseFloat(it.dataset.price) || 0;
        const qty = parseInt(it.dataset.qty, 10) || 1;
        const name = it.dataset.name || it.querySelector('.item-name')?.innerText.trim() || 'Товар';
        const image = it.dataset.image || it.querySelector('img')?.src || '';

        return {
            id: productId,
            name: name,
            price: price,
            qty: qty,
            image: image
        };
    }).filter(item => item.id > 0); // Убираем товары без ID

    if (!items.length) { 
        showNotification('Нет товаров для добавления в корзину', 'error');
        return; 
    }

    // Получаем текущую корзину
    let cart = getCart();
    
    // Добавляем товары
    items.forEach(newItem => {
        const existingItem = cart.find(item => item.id === newItem.id);
        if (existingItem) {
            existingItem.qty += newItem.qty;
        } else {
            cart.push(newItem);
        }
    });

    saveCart(cart);
    
    const totalItems = cart.reduce((sum, item) => sum + item.qty, 0);
    showNotification(`✅ Добавлено ${items.length} товаров в корзину. Всего в корзине: ${totalItems} шт.`);
    
    // Через 1.5 секунды переходим на главную
    setTimeout(() => {
        window.location.href = 'index.php#products';
    }, 1500);
}

// Привязка кнопок "Повторить"
document.querySelectorAll('.repeat-order').forEach(btn => {
    btn.addEventListener('click', (e) => {
        e.preventDefault();
        const card = btn.closest('.order-card');
        if (card) {
            repeatOrderFromCard(card);
        }
    });
});

// Добавляем анимацию для уведомлений
const style = document.createElement('style');
style.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);
</script>
</body>
</html>