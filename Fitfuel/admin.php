<?php
session_start();
require_once "db.php";

// Проверка авторизации и прав администратора
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    header('Location: index.php');
    exit();
}

// Обработка действий
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

// Добавление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = (float)$_POST['price'];
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $composition = mysqli_real_escape_string($conn, $_POST['composition']);
    $usage = mysqli_real_escape_string($conn, $_POST['usage']);
    
    $query = "INSERT INTO products (name, price, image, description, composition, usage) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sdssss", $name, $price, $image, $description, $composition, $usage);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Товар успешно добавлен!";
    } else {
        $error = "Ошибка при добавлении товара: " . mysqli_error($conn);
    }
    mysqli_stmt_close($stmt);
}

// Редактирование товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $id = (int)$_POST['product_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $price = (float)$_POST['price'];
    $image = mysqli_real_escape_string($conn, $_POST['image']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $composition = mysqli_real_escape_string($conn, $_POST['composition']);
    $usage = mysqli_real_escape_string($conn, $_POST['usage']);
    
    $query = "UPDATE products SET name=?, price=?, image=?, description=?, composition=?, usage=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "sdssssi", $name, $price, $image, $description, $composition, $usage, $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Товар успешно обновлен!";
    } else {
        $error = "Ошибка при обновлении товара";
    }
    mysqli_stmt_close($stmt);
}

// Удаление товара
if (isset($_GET['delete_product'])) {
    $id = (int)$_GET['delete_product'];
    $query = "DELETE FROM products WHERE id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Товар успешно удален!";
    } else {
        $error = "Ошибка при удалении товара";
    }
    mysqli_stmt_close($stmt);
}

// Удаление отзыва
if (isset($_GET['delete_review'])) {
    $id = (int)$_GET['delete_review'];
    $query = "DELETE FROM reviews WHERE id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Отзыв успешно удален!";
    } else {
        $error = "Ошибка при удалении отзыва";
    }
    mysqli_stmt_close($stmt);
}

// Обновление статуса заказа
if (isset($_POST['update_order_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    
    $query = "UPDATE orders SET status=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $message = "Статус заказа обновлен!";
    } else {
        $error = "Ошибка при обновлении статуса";
    }
    mysqli_stmt_close($stmt);
}

// Получение данных для статистики
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products"))['count'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders"))['count'];
$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'];
$total_reviews = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM reviews"))['count'];
$total_income = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total) as total FROM orders WHERE status='completed'"))['total'] ?? 0;

// Получение последних заказов
$recent_orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");

// Получение всех товаров
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");

// Получение всех отзывов
$reviews = mysqli_query($conn, "SELECT r.*, u.login, p.name as product_name FROM reviews r 
                                 LEFT JOIN users u ON r.user_id = u.id 
                                 LEFT JOIN products p ON r.product_id = p.id 
                                 ORDER BY r.created_at DESC");

// Получение всех заказов
$orders = mysqli_query($conn, "SELECT * FROM orders ORDER BY created_at DESC");

// Получение всех пользователей
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Админ-панель FitFuel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #0E9AA7;
            --primary-dark: #0A7A85;
            --primary-light: #E6F7F9;
            --accent-color: #FF6B6B;
            --text-color: #1E3A5F;
            --text-light: #4A5568;
            --bg-white: #FFFFFF;
            --shadow: 0 10px 25px rgba(14, 154, 167, .15);
            --radius: 16px;
            --transition: .4s cubic-bezier(.175, .885, .32, 1.275);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e6f7f9 100%);
            color: var(--text-color);
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background: linear-gradient(135deg, #1E3A5F 0%, #0F2B44 100%);
            color: #fff;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 30px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-header h2 {
            font-size: 1.5rem;
            font-weight: 800;
        }

        .sidebar-header p {
            font-size: 0.85rem;
            opacity: 0.7;
            margin-top: 5px;
        }

        .sidebar-menu {
            padding: 20px 0;
        }

        .menu-item {
            padding: 12px 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }

        .menu-item:hover {
            background: rgba(255,255,255,0.1);
            color: #fff;
            border-left-color: var(--primary-color);
        }

        .menu-item.active {
            background: rgba(255,255,255,0.1);
            border-left-color: var(--primary-color);
            color: #fff;
        }

        .menu-item i {
            width: 24px;
            font-size: 1.2rem;
        }

        /* Main Content */
        .main-content {
            margin-left: 280px;
            flex: 1;
            padding: 30px;
        }

        /* Header */
        .admin-header {
            background: #fff;
            padding: 20px 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .admin-header h1 {
            font-size: 1.8rem;
            font-weight: 700;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .admin-user span {
            font-weight: 600;
        }

        .logout-btn {
            padding: 8px 20px;
            background: var(--accent-color);
            color: #fff;
            border-radius: 25px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: #ff5252;
            transform: translateY(-2px);
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card i {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }

        .stat-card h3 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 5px;
        }

        .stat-card p {
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Tables */
        .admin-table {
            background: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow-x: auto;
            margin-top: 20px;
        }

        .admin-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .admin-table th,
        .admin-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-table th {
            background: var(--primary-light);
            font-weight: 700;
            color: var(--text-color);
        }

        .admin-table tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .btn-action {
            padding: 5px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            margin: 0 3px;
            transition: all 0.3s ease;
        }

        .btn-edit {
            background: var(--primary-light);
            color: var(--primary-color);
        }

        .btn-delete {
            background: #fee2e2;
            color: var(--accent-color);
        }

        .btn-edit:hover,
        .btn-delete:hover {
            transform: translateY(-2px);
        }

        /* Forms */
        .admin-form {
            background: #fff;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            margin-top: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: var(--text-color);
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14,154,167,0.1);
        }

        .btn-submit {
            padding: 12px 30px;
            background: var(--primary-color);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-submit:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .alert {
            padding: 15px 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: #fff;
            padding: 30px;
            border-radius: var(--radius);
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-close {
            cursor: pointer;
            font-size: 1.5rem;
            color: var(--text-light);
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                z-index: 100;
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2><i class="fas fa-dumbbell"></i> FitFuel</h2>
                <p>Административная панель</p>
            </div>
            <div class="sidebar-menu">
                <a href="?action=dashboard" class="menu-item <?= $action == 'dashboard' ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i> <span>Дашборд</span>
                </a>
                <a href="?action=products" class="menu-item <?= $action == 'products' ? 'active' : '' ?>">
                    <i class="fas fa-box"></i> <span>Товары</span>
                </a>
                <a href="?action=add_product" class="menu-item <?= $action == 'add_product' ? 'active' : '' ?>">
                    <i class="fas fa-plus-circle"></i> <span>Добавить товар</span>
                </a>
                <a href="?action=orders" class="menu-item <?= $action == 'orders' ? 'active' : '' ?>">
                    <i class="fas fa-shopping-cart"></i> <span>Заказы</span>
                </a>
                <a href="?action=reviews" class="menu-item <?= $action == 'reviews' ? 'active' : '' ?>">
                    <i class="fas fa-star"></i> <span>Отзывы</span>
                </a>
                <a href="?action=users" class="menu-item <?= $action == 'users' ? 'active' : '' ?>">
                    <i class="fas fa-users"></i> <span>Пользователи</span>
                </a>
                <a href="index.php" class="menu-item">
                    <i class="fas fa-home"></i> <span>На сайт</span>
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
            <div class="admin-header">
                <h1><i class="fas fa-crown"></i> Админ-панель</h1>
                <div class="admin-user">
                    <span><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_login']) ?></span>
                    <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Выйти</a>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= $message ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($action == 'dashboard'): ?>
                <!-- Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <i class="fas fa-box"></i>
                        <h3><?= $total_products ?></h3>
                        <p>Товаров в каталоге</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-shopping-cart"></i>
                        <h3><?= $total_orders ?></h3>
                        <p>Всего заказов</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <h3><?= $total_users ?></h3>
                        <p>Пользователей</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-star"></i>
                        <h3><?= $total_reviews ?></h3>
                        <p>Отзывов</p>
                    </div>
                    <div class="stat-card">
                        <i class="fas fa-ruble-sign"></i>
                        <h3><?= number_format($total_income, 0, '.', ' ') ?> ₽</h3>
                        <p>Выручка</p>
                    </div>
                </div>

                <div class="admin-table">
                    <h3 style="padding: 20px 20px 0;">Последние заказы</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['fio']) ?></td>
                                    <td><?= number_format($order['total'], 0, '.', ' ') ?> ₽</td>
                                    <td>
                                        <span class="status-badge status-<?= $order['status'] ?>">
                                            <?php
                                            $statuses = ['pending' => 'Ожидает', 'processing' => 'В обработке', 'completed' => 'Выполнен', 'cancelled' => 'Отменен'];
                                            echo $statuses[$order['status']] ?? $order['status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editOrder(<?= $order['id'] ?>, '<?= $order['status'] ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action == 'products'): ?>
                <!-- Products List -->
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Изображение</th>
                                <th>Название</th>
                                <th>Цена</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($product = mysqli_fetch_assoc($products)): ?>
                                <tr>
                                    <td><?= $product['id'] ?></td>
                                    <td><img src="<?= htmlspecialchars($product['image']) ?>" width="50" height="50" style="object-fit:cover; border-radius:8px;"></td>
                                    <td><?= htmlspecialchars($product['name']) ?></td>
                                    <td><?= number_format($product['price'], 0, '.', ' ') ?> ₽</td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="editProduct(<?= $product['id'] ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="?action=products&delete_product=<?= $product['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Удалить товар?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action == 'add_product'): ?>
                <!-- Add Product Form -->
                <div class="admin-form">
                    <h2 style="margin-bottom: 20px;">Добавить новый товар</h2>
                    <form method="POST">
                        <div class="form-group">
                            <label>Название товара *</label>
                            <input type="text" name="name" required>
                        </div>
                        <div class="form-group">
                            <label>Цена (₽) *</label>
                            <input type="number" name="price" step="1" required>
                        </div>
                        <div class="form-group">
                            <label>URL изображения *</label>
                            <input type="text" name="image" placeholder="images/product.jpg" required>
                        </div>
                        <div class="form-group">
                            <label>Описание</label>
                            <textarea name="description" rows="4"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Состав</label>
                            <textarea name="composition" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>Способ применения</label>
                            <textarea name="usage" rows="3"></textarea>
                        </div>
                        <button type="submit" name="add_product" class="btn-submit">
                            <i class="fas fa-save"></i> Добавить товар
                        </button>
                    </form>
                </div>

            <?php elseif ($action == 'orders'): ?>
                <!-- Orders List -->
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Клиент</th>
                                <th>Телефон</th>
                                <th>Адрес</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = mysqli_fetch_assoc($orders)): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['fio']) ?></td>
                                    <td><?= htmlspecialchars($order['phone']) ?></td>
                                    <td><?= htmlspecialchars($order['city'] . ', ' . $order['street'] . ' ' . $order['house']) ?></td>
                                    <td><?= number_format($order['total'], 0, '.', ' ') ?> ₽</td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <select name="status" onchange="this.form.submit()">
                                                <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Ожидает</option>
                                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Выполнен</option>
                                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                                            </select>
                                            <input type="hidden" name="update_order_status" value="1">
                                        </form>
                                    </td>
                                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                    <td>
                                        <button class="btn-action btn-edit" onclick="viewOrder(<?= $order['id'] ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action == 'reviews'): ?>
                <!-- Reviews List -->
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Товар</th>
                                <th>Пользователь</th>
                                <th>Оценка</th>
                                <th>Отзыв</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($review = mysqli_fetch_assoc($reviews)): ?>
                                <tr>
                                    <td><?= $review['id'] ?></td>
                                    <td><?= htmlspecialchars($review['product_name'] ?? 'Товар удален') ?></td>
                                    <td><?= htmlspecialchars($review['login'] ?? 'Гость') ?></td>
                                    <td>
                                        <?php for($i = 1; $i <= 5; $i++): ?>
                                            <?php if($i <= $review['rating']): ?>
                                                <i class="fas fa-star" style="color:#FFB800;"></i>
                                            <?php else: ?>
                                                <i class="far fa-star" style="color:#FFB800;"></i>
                                            <?php endif; ?>
                                        <?php endfor; ?>
                                    </td>
                                    <td style="max-width: 300px;"><?= htmlspecialchars(mb_substr($review['comment'], 0, 50)) ?>...</td>
                                    <td><?= date('d.m.Y', strtotime($review['created_at'])) ?></td>
                                    <td>
                                        <a href="?action=reviews&delete_review=<?= $review['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Удалить отзыв?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

            <?php elseif ($action == 'users'): ?>
                <!-- Users List -->
                <div class="admin-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Логин</th>
                                <th>Email</th>
                                <th>Телефон</th>
                                <th>Роль</th>
                                <th>Дата регистрации</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?= $user['id'] ?></td>
                                    <td><?= htmlspecialchars($user['login']) ?></td>
                                    <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                                    <td>
                                        <?php if ($user['is_admin'] == 1): ?>
                                            <span class="status-badge" style="background: var(--primary-light); color: var(--primary-color);">Администратор</span>
                                        <?php else: ?>
                                            <span class="status-badge" style="background: #e2e8f0; color: #4a5568;">Пользователь</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d.m.Y', strtotime($user['created_at'])) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal для редактирования товара -->
    <div id="editProductModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Редактировать товар</h2>
                <span class="modal-close" onclick="closeModal('editProductModal')">&times;</span>
            </div>
            <form id="editProductForm" method="POST">
                <input type="hidden" name="product_id" id="edit_product_id">
                <div class="form-group">
                    <label>Название</label>
                    <input type="text" name="name" id="edit_name" required>
                </div>
                <div class="form-group">
                    <label>Цена (₽)</label>
                    <input type="number" name="price" id="edit_price" step="1" required>
                </div>
                <div class="form-group">
                    <label>URL изображения</label>
                    <input type="text" name="image" id="edit_image" required>
                </div>
                <div class="form-group">
                    <label>Описание</label>
                    <textarea name="description" id="edit_description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label>Состав</label>
                    <textarea name="composition" id="edit_composition" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Способ применения</label>
                    <textarea name="usage" id="edit_usage" rows="3"></textarea>
                </div>
                <button type="submit" name="edit_product" class="btn-submit">Сохранить изменения</button>
            </form>
        </div>
    </div>

    <!-- Modal для просмотра заказа -->
    <div id="viewOrderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Детали заказа #<span id="order_id_display"></span></h2>
                <span class="modal-close" onclick="closeModal('viewOrderModal')">&times;</span>
            </div>
            <div id="order_details"></div>
        </div>
    </div>

    <script>
        // Редактирование товара
        function editProduct(id) {
            fetch(`get_product.php?id=${id}`)
                .then(r => r.json())
                .then(p => {
                    document.getElementById('edit_product_id').value = p.id;
                    document.getElementById('edit_name').value = p.name;
                    document.getElementById('edit_price').value = p.price;
                    document.getElementById('edit_image').value = p.image;
                    document.getElementById('edit_description').value = p.description || '';
                    document.getElementById('edit_composition').value = p.composition || '';
                    document.getElementById('edit_usage').value = p.usage || '';
                    document.getElementById('editProductModal').classList.add('show');
                });
        }

        // Просмотр заказа
        function viewOrder(id) {
            fetch(`get_order.php?id=${id}`)
                .then(r => r.json())
                .then(order => {
                    document.getElementById('order_id_display').innerText = order.id;
                    let itemsHtml = '';
                    if (order.items) {
                        const items = JSON.parse(order.items);
                        itemsHtml = '<h3>Товары:</h3><ul>';
                        items.forEach(item => {
                            itemsHtml += `<li>${item.name} x ${item.qty} шт. = ${item.price * item.qty} ₽</li>`;
                        });
                        itemsHtml += '</ul>';
                    }
                    document.getElementById('order_details').innerHTML = `
                        <p><strong>Клиент:</strong> ${order.fio}</p>
                        <p><strong>Телефон:</strong> ${order.phone}</p>
                        <p><strong>Адрес:</strong> ${order.city}, ${order.street} ${order.house}</p>
                        <p><strong>Сумма:</strong> ${order.total} ₽</p>
                        <p><strong>Статус:</strong> ${order.status}</p>
                        <p><strong>Дата:</strong> ${order.created_at}</p>
                        <p><strong>Комментарий:</strong> ${order.comment || '-'}</p>
                        ${itemsHtml}
                    `;
                    document.getElementById('viewOrderModal').classList.add('show');
                });
        }

        // Редактирование статуса заказа
        function editOrder(id, currentStatus) {
            const newStatus = prompt('Изменить статус заказа (pending/processing/completed/cancelled):', currentStatus);
            if (newStatus) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="order_id" value="${id}">
                    <input type="hidden" name="status" value="${newStatus}">
                    <input type="hidden" name="update_order_status" value="1">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
        }

        // Закрытие модалки при клике вне окна
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
            }
        }
    </script>
</body>
</html>