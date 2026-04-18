<?php
session_start();
require_once "db.php";

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Функция для получения средней оценки товара
function getProductRating($conn, $product_id) {
    $query = "SELECT AVG(rating) as avg_rating, COUNT(*) as reviews_count FROM reviews WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $data = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return [
        'avg' => round($data['avg_rating'] ?? 0, 1),
        'count' => $data['reviews_count'] ?? 0
    ];
}

// Функция для получения товаров с тегами (хит, скидка)
function getProductsWithTags($conn) {
    $query = "SELECT * FROM products ORDER BY id DESC";
    $result = mysqli_query($conn, $query);
    $products = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $tags = [];
        // Убираем генерацию случайных тегов, пока нет реальных данных
        // if ($row['id'] % 3 == 1) $tags[] = 'hit';
        // if ($row['id'] % 4 == 2) $tags[] = 'sale';
        // if ($row['id'] % 5 == 3) $tags[] = 'new';
        
        // Добавляем теги только если есть соответствующие поля в БД
        // Если хотите оставить демо-теги, раскомментируйте строки выше
        
        $row['tags'] = $tags;
        $products[] = $row;
    }
    return $products;
}

// Обработка формы сотрудничества
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cooperation_submit'])) {
    $name = mysqli_real_escape_string($conn, $_POST['coop_name'] ?? '');
    $email = mysqli_real_escape_string($conn, $_POST['coop_email'] ?? '');
    $phone = mysqli_real_escape_string($conn, $_POST['coop_phone'] ?? '');
    $message = mysqli_real_escape_string($conn, $_POST['coop_message'] ?? '');
    
    $query = "INSERT INTO cooperation_requests (name, email, phone, message, created_at) VALUES (?, ?, ?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $phone, $message);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    $coop_success = true;
}
?>
<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <title>FitFuel — Магазин спортивного питания</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />
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

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            margin: 0;
            font-family: 'Montserrat', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e6f7f9 100%);
            color: var(--text-color);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            position: relative;
            overflow-x: hidden;
        }

        /* Геометрический фон с анимацией */
        .background-animation {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: -1; overflow: hidden;
        }
        .background-shapes, .waves, .particles { position: absolute; width: 100%; height: 100%; }

        .shape {
            position: absolute; opacity: 0.1; border-radius: 50%; background: var(--primary-color);
            animation: float 20s infinite linear;
        }
        .shape:nth-child(1) { width: 300px; height: 300px; top: -150px; left: -150px; background: linear-gradient(45deg, var(--primary-color), var(--accent-color)); animation-delay: 0s; animation-duration: 25s; }
        .shape:nth-child(2) { width: 200px; height: 200px; top: 20%; right: -100px; background: linear-gradient(45deg, var(--accent-color), var(--primary-dark)); animation-delay: -5s; animation-duration: 20s; }
        .shape:nth-child(3) { width: 150px; height: 150px; bottom: 10%; left: 10%; background: linear-gradient(45deg, var(--primary-dark), var(--primary-color)); animation-delay: -10s; animation-duration: 30s; }
        .shape:nth-child(4) { width: 100px; height: 100px; top: 60%; right: 20%; background: var(--accent-color); animation-delay: -15s; animation-duration: 15s; }

        .waves { bottom: 0; height: 100px; opacity: 0.3; }
        .wave {
            position: absolute; bottom: 0; left: 0; width: 200%; height: 100%;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M321.39,56.44c58-10.79,114.16-30.13,172-41.86,82.39-16.72,168.19-17.73,250.45-.39C823.78,31,906.67,72,985.66,92.83c70.05,18.48,146.53,26.09,214.34,3V0H0V27.35A600.21,600.21,0,0,0,321.39,56.44Z' fill='%230E9AA7'%3E%3C/path%3E%3C/svg%3E");
            background-size: 1200px 100%; animation: wave 15s infinite linear;
        }
        .wave:nth-child(1) { opacity: 0.5; animation-delay: 0s; }
        .wave:nth-child(2) { opacity: 0.3; animation-delay: -5s; animation-duration: 20s; }
        .wave:nth-child(3) { opacity: 0.2; animation-delay: -2s; animation-duration: 25s; }

        .particle {
            position: absolute; width: 6px; height: 6px; background: var(--primary-color);
            border-radius: 50%; opacity: 0.3; animation: particle-float 15s infinite linear;
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; animation-delay: -2s; }
        .particle:nth-child(3) { left: 30%; animation-delay: -4s; }
        .particle:nth-child(4) { left: 40%; animation-delay: -6s; }
        .particle:nth-child(5) { left: 50%; animation-delay: -8s; }
        .particle:nth-child(6) { left: 60%; animation-delay: -10s; }
        .particle:nth-child(7) { left: 70%; animation-delay: -12s; }
        .particle:nth-child(8) { left: 80%; animation-delay: -14s; }
        .particle:nth-child(9) { left: 90%; animation-delay: -16s; }

        @keyframes float { 0%,100% { transform: translate(0,0) rotate(0deg); } 25% { transform: translate(50px,50px) rotate(90deg); } 50% { transform: translate(100px,0) rotate(180deg); } 75% { transform: translate(50px,-50px) rotate(270deg); } }
        @keyframes wave { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
        @keyframes particle-float { 0%,100% { transform: translateY(100vh) scale(0.5); opacity:0; } 10%,90% { opacity:0.3; } 50% { transform: translateY(0) scale(1); opacity:0.5; } }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; position: relative; z-index: 1; }

        /* ШАПКА */
        .main-header {
            position: sticky;
            top: 0;
            z-index: 1100;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 15px 0;
        }
        .header-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 48px;
            flex-wrap: wrap;
        }
        .nav-link {
            text-decoration: none;
            color: var(--text-color);
            font-weight: 600;
            font-size: 1.05rem;
            padding: 8px 0;
            position: relative;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: var(--primary-color);
        }
        .nav-link::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primary-color);
            transition: width 0.3s ease;
        }
        .nav-link:hover::after {
            width: 100%;
        }
        .nav-link i {
            margin-right: 8px;
        }

        .hero { padding: 80px 0; position: relative; overflow: hidden; }
        .hero-wrap { display: flex; align-items: center; gap: 60px; }
        .hero-video { width: 100%; max-height: 400px; object-fit: cover; border-radius: var(--radius); box-shadow: var(--shadow); }
        .hero-content h1 { font-size: 2.8rem; font-weight: 800; margin-bottom: 20px; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .hero-content p { font-size: 1.1rem; line-height: 1.6; margin-bottom: 30px; color: var(--text-light); }
        .hero-btn { display: inline-block; background: linear-gradient(135deg, var(--primary-color), var(--primary-dark)); color: #fff; padding: 16px 32px; border-radius: 50px; text-decoration: none; transition: all 0.3s ease; font-weight: 600; box-shadow: var(--shadow); }
        .hero-btn:hover { transform: translateY(-2px); box-shadow: 0 15px 30px rgba(14,154,167,0.3); }

        /* Стили для карусели */
        .carousel-section {
            padding: 60px 0;
            background: linear-gradient(135deg, rgba(14,154,167,0.03) 0%, rgba(255,107,107,0.03) 100%);
        }
        .carousel-title {
            text-align: center;
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 15px;
            color: var(--text-color);
        }
        .carousel-subtitle {
            text-align: center;
            font-size: 1rem;
            color: var(--text-light);
            margin-bottom: 40px;
        }
        .swiper {
            padding: 20px 10px 40px;
        }
        .swiper-slide {
            height: auto;
        }
        .carousel-card {
            background: #fff;
            border-radius: var(--radius);
            overflow: hidden;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            position: relative;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .carousel-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(14,154,167,0.2);
        }
        .carousel-img {
            width: 100%;
            height: 220px;
            object-fit: cover;
        }
        .carousel-badge {
            position: absolute;
            top: 15px;
            left: 15px;
            z-index: 10;
            display: flex;
            gap: 8px;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #fff;
        }
        .badge-hit {
            background: linear-gradient(135deg, #FF6B6B, #FF4757);
            box-shadow: 0 2px 8px rgba(255,71,87,0.3);
        }
        .badge-sale {
            background: linear-gradient(135deg, #FFA502, #FF7F00);
            box-shadow: 0 2px 8px rgba(255,127,0,0.3);
        }
        .badge-new {
            background: linear-gradient(135deg, #0E9AA7, #0A7A85);
            box-shadow: 0 2px 8px rgba(14,154,167,0.3);
        }
        .carousel-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        .carousel-name {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-color);
        }
        .carousel-rating {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 10px;
            font-size: 0.85rem;
        }
        .carousel-rating .stars {
            color: #FFB800;
            font-size: 0.85rem;
        }
        .carousel-price {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--primary-color);
            margin: 10px 0;
        }
        .old-price {
            font-size: 0.9rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 8px;
            font-weight: 400;
        }
        .carousel-btn {
            margin-top: auto;
            padding: 10px;
            background: var(--primary-light);
            color: var(--primary-color);
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .carousel-btn:hover {
            background: var(--primary-color);
            color: #fff;
        }
        .swiper-button-next,
        .swiper-button-prev {
            color: var(--primary-color);
            background: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 18px;
        }
        .swiper-pagination-bullet-active {
            background: var(--primary-color);
        }

        .products-section { padding: 80px 0; }
        .products-title { text-align: center; font-size: 2.5rem; margin-bottom: 40px; font-weight: 800; position: relative; display: inline-block; width: 100%; }
        .products-title:after { content: ''; display: block; width: 80px; height: 4px; background: var(--primary-color); margin: 15px auto 0; border-radius: 4px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 30px; }

        .product-card {
            background: #fff; border-radius: var(--radius); padding: 25px; box-shadow: var(--shadow);
            display: flex; flex-direction: column; gap: 12px; transition: all 0.4s cubic-bezier(0.175,0.885,0.32,1.275);
            position: relative; overflow: hidden; animation: cardAppear 0.6s ease-out forwards; opacity: 0; transform: translateY(30px);
        }
        .product-card::before {
            content:''; position:absolute; top:0; left:-100%; width:100%; height:100%;
            background: linear-gradient(90deg, transparent, rgba(14,154,167,0.1), transparent); transition: left 0.6s;
        }
        .product-card:hover::before { left: 100%; }
        .product-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: 0 20px 40px rgba(14,154,167,0.25); }

        .product-card:nth-child(1) { animation-delay: 0.1s; }
        .product-card:nth-child(2) { animation-delay: 0.2s; }
        .product-card:nth-child(3) { animation-delay: 0.3s; }
        .product-card:nth-child(4) { animation-delay: 0.4s; }
        .product-card:nth-child(5) { animation-delay: 0.5s; }
        .product-card:nth-child(6) { animation-delay: 0.6s; }
        @keyframes cardAppear { to { opacity:1; transform:translateY(0); } }

        .product-card img { width:100%; height:180px; object-fit:cover; border-radius:12px; }
        .product-name { font-size:1.3rem; font-weight:700; margin-bottom:5px; }
        .product-price { font-size:1.35rem; font-weight:800; color:var(--primary-color); margin:6px 0 10px; }
        
        .product-rating {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 8px 0;
            font-size: 0.9rem;
        }
        .stars { color: #FFB800; letter-spacing: 2px; }
        .stars i { margin-right: 2px; }
        .rating-value { font-weight: 600; color: var(--text-color); }
        .reviews-count { color: var(--text-light); font-size: 0.85rem; }
        
        .product-actions { display:flex; gap:12px; margin-top:auto; }
        .btn {
            padding:12px 14px; border-radius:12px; font-weight:600; border:none; cursor:pointer;
            display:inline-flex; align-items:center; gap:8px; transition:all 0.3s ease;
        }
        .btn-details { background:var(--primary-light); color:var(--text-color); flex: 1; justify-content: center; }
        .btn-details:hover { background:var(--primary-color); color:white; }
        .btn-order { background:var(--primary-color); color:#fff; flex: 1; justify-content: center; }
        .btn-order:hover { background:var(--primary-dark); }

        /* БЛОК СОТРУДНИЧЕСТВА */
        .cooperation-section {
            background: linear-gradient(135deg, #fff 0%, var(--primary-light) 100%);
            padding: 80px 0;
            margin: 40px 0;
            border-radius: var(--radius);
        }
        .cooperation-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: center;
        }
        .cooperation-content h2 {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: var(--text-color);
        }
        .cooperation-content p {
            font-size: 1.05rem;
            line-height: 1.6;
            color: var(--text-light);
            margin-bottom: 25px;
        }
        .cooperation-features {
            list-style: none;
            padding: 0;
        }
        .cooperation-features li {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .cooperation-features li i {
            color: var(--primary-color);
            font-size: 1.2rem;
        }
        .cooperation-form {
            background: #fff;
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }
        .cooperation-form h3 {
            font-size: 1.5rem;
            margin-bottom: 20px;
            color: var(--text-color);
        }
        .coop-success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            text-align: center;
        }

        /* ФУТЕР */
        .footer {
            background: linear-gradient(135deg, #1E3A5F 0%, #0F2B44 100%);
            color: #fff;
            padding: 60px 0 30px;
            margin-top: 60px;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        .footer-col h3 {
            font-size: 1.3rem;
            margin-bottom: 20px;
            position: relative;
            display: inline-block;
        }
        .footer-col h3:after {
            content: '';
            position: absolute;
            bottom: -8px;
            left: 0;
            width: 40px;
            height: 3px;
            background: var(--primary-color);
            border-radius: 3px;
        }
        .footer-col p {
            line-height: 1.6;
            opacity: 0.9;
            margin-bottom: 15px;
        }
        .footer-links {
            list-style: none;
            padding: 0;
        }
        .footer-links li {
            margin-bottom: 12px;
        }
        .footer-links a {
            color: #fff;
            text-decoration: none;
            opacity: 0.8;
            transition: opacity 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .footer-links a:hover {
            opacity: 1;
            color: var(--primary-color);
        }
        .social-links {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        .social-links a {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            color: #fff;
            transition: all 0.3s ease;
        }
        .social-links a:hover {
            background: var(--primary-color);
            transform: translateY(-3px);
        }
        .footer-bottom {
            text-align: center;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
            opacity: 0.7;
        }

        /* Остальные стили (модалки, корзина и т.д.) */
        .modal, .order-modal { position:fixed; inset:0; background:rgba(0,0,0,.6); display:none; align-items:center; justify-content:center; z-index:1200; padding:20px; }
        .modal.show, .order-modal.active { display:flex; }
        .modal-window, .order-modal-content { background:#fff; border-radius:var(--radius); padding:24px; max-width:600px; width:100%; max-height:90vh; overflow-y:auto; position:relative; box-shadow:0 12px 40px rgba(0,0,0,0.12); }
        .modal-close, .order-close { position:absolute; top:12px; right:14px; font-size:20px; cursor:pointer; color:var(--text-light); background:none; border:none; z-index:1; }
        .modal-close:hover, .order-close:hover { color:var(--accent-color); }
        .form-group { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; }
        .form-group label { font-weight:600; font-size:.92rem; color:var(--text-color); }
        .form-group input, .form-group textarea, .form-group select { padding:10px; border:1px solid #e2e8f0; border-radius:10px; font-family:inherit; transition: border-color 0.3s ease; }
        .form-group input:focus, .form-group textarea:focus, .form-group select:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(14,154,167,0.1); }
        .form-row { display:flex; gap:12px; }
        .order-submit-btn { width:100%; padding:12px; background:var(--primary-color); color:#fff; border:none; border-radius:12px; font-size:1rem; font-weight:700; cursor:pointer; transition:background 0.3s ease; }
        .order-submit-btn:hover { background:var(--primary-dark); }
        .success-popup { position:fixed; bottom:30px; right:30px; background:#fff; padding:14px 20px; border-radius:14px; box-shadow:0 20px 45px rgba(0,0,0,.15); opacity:0; transform:translateY(20px); pointer-events:none; transition:.35s; z-index:2000; border-left:4px solid #10b981; display: flex; align-items: center; gap: 12px; }
        .success-popup.show { opacity:1; transform:translateY(0); }
        .popup-icon { color: #10b981; font-size: 1.5rem; }
        .modal-product-image { width:100%; max-height:200px; object-fit:cover; border-radius:12px; margin-bottom:15px; }
        .modal-section { margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #e2e8f0; }
        .modal-section:last-child { border-bottom:none; margin-bottom:0; }
        .modal-section-title { font-weight:700; color:var(--primary-color); margin-bottom:8px; font-size:1.1rem; display: flex; align-items: center; gap: 8px; }
        .modal-section-content { color:var(--text-light); line-height:1.5; }
        .cart-btn { position:fixed; top:25px; right:25px; z-index:1400; background:var(--primary-color); color:#fff; padding:12px 18px; border-radius:50px; border:none; cursor:pointer; font-weight:700; box-shadow:0 8px 24px rgba(14,154,167,0.18); display:flex; align-items:center; gap:10px; transition:all 0.3s ease; }
        .cart-btn:hover { background:var(--primary-dark); transform:translateY(-2px); }
        .cart-btn span { background:rgba(255,255,255,0.12); padding:4px 8px; border-radius:999px; font-weight:800; font-size:.95rem; }
        .cart-overlay { position:fixed; inset:0; background:rgba(0,0,0,0.45); z-index:1350; display:none; transition:opacity .2s; }
        .cart-overlay.active { display:block; opacity:1; }
        .cart-panel { position:fixed; right:0; top:0; height:100vh; width:360px; max-width:calc(100% - 36px); background:#fff; box-shadow:-14px 0 40px rgba(0,0,0,0.12); z-index:1400; padding:18px; display:flex; flex-direction:column; transform:translateX(110%); transition:transform var(--transition); border-left:1px solid rgba(10,10,10,0.03); border-radius:12px 0 0 12px; }
        .cart-panel.active { transform:translateX(0); }
        .cart-header { display:flex; align-items:center; justify-content:space-between; gap:8px; padding-bottom:6px; border-bottom:1px solid #f0f3f5; margin-bottom:12px; }
        .cart-header h2 { margin:0; font-size:1.1rem; font-weight:800; display:flex; align-items:center; gap:10px; }
        .cart-close { background:transparent; border:none; font-size:18px; cursor:pointer; color:var(--text-light); padding:6px; border-radius:8px; }
        .cart-close:hover { background:var(--primary-light); color:var(--text-color); }
        .cart-items { flex:1; overflow-y:auto; padding-right:6px; }
        .cart-item { display:grid; grid-template-columns:72px 1fr; gap:12px; align-items:center; padding:12px 0; border-bottom:1px solid #f3f5f6; }
        .cart-item img { width:72px; height:72px; object-fit:cover; border-radius:10px; }
        .cart-item-info { display:flex; flex-direction:column; gap:6px; }
        .cart-item-name { font-weight:700; font-size:.95rem; color:var(--text-color); line-height:1.2; }
        .cart-item-price { font-weight:800; color:var(--primary-color); font-size:1rem; }
        .cart-qty { display:flex; align-items:center; gap:8px; margin-top:6px; }
        .cart-qty button { width:30px; height:30px; border-radius:8px; border:none; background:var(--primary-light); cursor:pointer; font-weight:800; font-size:16px; display:inline-flex; align-items:center; justify-content:center; }
        .cart-qty button:hover { background:var(--primary-color); color:#fff; }
        .cart-remove { background:transparent; border:none; color:var(--accent-color); cursor:pointer; font-size:.9rem; padding-top:6px; display:inline-flex; align-items:center; gap:6px; }
        .cart-footer { padding-top:12px; border-top:1px solid #f0f3f5; margin-top:8px; }
        .cart-total { font-size:1.15rem; font-weight:900; text-align:center; margin-bottom:10px; color:var(--text-color); }
        .cart-checkout { width:100%; padding:12px; border-radius:12px; border:none; background:var(--primary-color); color:#fff; font-weight:800; cursor:pointer; font-size:1rem; transition:background 0.3s ease; }
        .cart-checkout:hover { background:var(--primary-dark); }
        .cart-empty { text-align:center; color:var(--text-light); padding:28px 6px; font-weight:600; }
        .reviews-section { margin-top:24px; padding-top:20px; border-top:1px solid #e2e8f0; }
        .reviews-toggle { display:flex; align-items:center; justify-content:space-between; width:100%; padding:12px 16px; background:var(--primary-light); border:none; border-radius:12px; font-weight:700; color:var(--primary-color); cursor:pointer; transition:all 0.25s ease; }
        .reviews-toggle:hover { background:var(--primary-color); color:white; }
        .reviews-toggle i { transition:transform 0.3s ease; }
        .reviews-toggle.active i { transform:rotate(180deg); }
        .reviews-list { max-height:0; overflow:hidden; transition:max-height 0.4s ease, opacity 0.3s ease; opacity:0; }
        .reviews-list.active { max-height:1200px; opacity:1; margin-top:16px; }
        .review-item { background:#f8fcff; border-radius:12px; padding:16px; margin-bottom:12px; border:1px solid rgba(14,154,167,0.08); box-shadow:0 2px 8px rgba(0,0,0,0.04); }
        .review-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:8px; }
        .review-author { font-weight:700; color:var(--text-color); }
        .review-date { font-size:0.85rem; color:var(--text-light); }
        .review-rating { color:#f59e0b; font-size:1.1rem; margin-bottom: 8px; }
        .review-text { line-height:1.6; color:var(--text-light); }
        .no-reviews { text-align:center; color:var(--text-light); padding:20px; font-style:italic; }
        .review-form-section { margin-top:24px; padding-top:20px; border-top:1px solid #e2e8f0; }
        .review-form-section h3 { margin:0 0 16px; color:var(--primary-color); font-size:1.25rem; }
        .review-form-section form { display:flex; flex-direction:column; gap:14px; }
        .review-form-section button { padding:12px; background:var(--primary-color); color:white; border:none; border-radius:10px; font-weight:600; cursor:pointer; transition:background 0.2s; }
        .review-form-section button:hover { background:var(--primary-dark); }
        .loader { display: inline-block; width: 20px; height: 20px; border: 2px solid #f3f3f3; border-top: 2px solid var(--primary-color); border-radius: 50%; animation: spin 1s linear infinite; margin-left: 10px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        
        @media (max-width: 768px) {
            .hero-wrap { flex-direction: column; gap: 30px; text-align: center; }
            .hero-content h1 { font-size: 2rem; }
            .products-title { font-size: 2rem; }
            .cooperation-grid { grid-template-columns: 1fr; }
            .header-container { gap: 25px; }
        }
        @media (max-width: 480px) {
            .cart-panel { width:100%; max-width:100%; border-radius:0; }
            .products-grid { grid-template-columns:1fr; }
            .product-actions { flex-direction:column; }
            .form-row { flex-direction: column; }
            .auth-btn { top: auto; bottom: 20px; left: 20px; right: auto; z-index: 1300; }
            .cart-btn { top: auto; bottom: 20px; right: 20px; }
            .header-container { gap: 15px; }
            .nav-link { font-size: 0.9rem; }
            .carousel-title { font-size: 1.5rem; }
        }
    </style>
</head>
<body>

    <!-- Анимированный фон -->
    <div class="background-animation">
        <div class="background-shapes">
            <div class="shape"></div><div class="shape"></div><div class="shape"></div><div class="shape"></div>
        </div>
        <div class="waves">
            <div class="wave"></div><div class="wave"></div><div class="wave"></div>
        </div>
        <div class="particles">
            <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
            <div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div>
        </div>
    </div>

    <!-- ШАПКА -->
    <header class="main-header">
        <div class="container header-container">
            <a href="#products" class="nav-link"><i class="fas fa-dumbbell"></i> Товары</a>
            <a href="#about" class="nav-link"><i class="fas fa-info-circle"></i> О нас</a>
            <a href="#cooperation" class="nav-link"><i class="fas fa-handshake"></i> Сотрудничество</a>
            <a href="#contacts" class="nav-link"><i class="fas fa-envelope"></i> Контакты</a>
        </div>
    </header>

    <!-- HERO / О НАС -->
    <section class="hero" id="about">
        <div class="container hero-wrap">
            <div class="hero-media">
                <video src="images/logo.mp4" class="hero-video" autoplay muted loop playsinline></video>
            </div>
            <div class="hero-content">
                <h1>FitFuel — Магазин спортивного питания</h1>
                <p>Мы помогаем спортсменам любого уровня достигать максимальных результатов: от набора мышечной массы до повышения выносливости. Только проверенные бренды, сертифицированная продукция и честные рекомендации.</p>
                <a href="#products" class="hero-btn">К товарам</a>
            </div>
        </div>
    </section>

    <!-- WHY CHOOSE US -->
    <section class="hero">
        <div class="container hero-wrap">
            <div class="hero-content">
                <h2>Почему спортсмены выбирают FitFuel?</h2>
                <p><strong>Наука и качество в каждой банке!</strong> Мы не просто продаём добавки — мы создаём инструменты для вашего прогресса. Наша продукция проходит 5-ступенчатый контроль качества и разрабатывается совместно с ведущими спортивными диетологами.</p>
                <div style="margin:20px 0;">
                    <div style="display:flex; align-items:center; gap:10px; margin:12px 0;">
                        <i class="fas fa-check-circle" style="color:var(--primary-color);"></i><span>100% соответствие заявленному составу</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; margin:12px 0;">
                        <i class="fas fa-check-circle" style="color:var(--primary-color);"></i><span>Быстрая доставка по всей России</span>
                    </div>
                    <div style="display:flex; align-items:center; gap:10px; margin:12px 0;">
                        <i class="fas fa-check-circle" style="color:var(--primary-color);"></i><span>Персональные консультации от экспертов</span>
                    </div>
                </div>
                <p>Каждый продукт в нашем ассортименте — это результат многолетних исследований и тестирований. Мы знаем, что доверие нужно заслужить, поэтому гарантируем прозрачность и качество на каждом этапе.</p>
            </div>
            <div class="hero-media">
                <video src="images/logo2.mp4" class="hero-video" autoplay muted loop playsinline></video>
            </div>
        </div>
    </section>

    <!-- КАРУСЕЛЬ ТОВАРОВ -->
    <section class="carousel-section">
        <div class="container">
            <h2 class="carousel-title"> Популярные товары</h2>
            <p class="carousel-subtitle">Выбор наших покупателей — лучшее, что есть в мире спортивного питания</p>
            
            <div class="swiper productSwiper">
                <div class="swiper-wrapper">
                    <?php
                    $products = getProductsWithTags($conn);
                    foreach ($products as $p):
                        $rating = getProductRating($conn, $p['id']);
                        $fullStars = floor($rating['avg']);
                        $halfStar = ($rating['avg'] - $fullStars) >= 0.5;
                        $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                        $salePrice = $p['price'];
                        $oldPrice = null;
                        // Для товаров с тегом sale делаем скидку 15%
                        if (in_array('sale', $p['tags'])) {
                            $oldPrice = $p['price'];
                            $salePrice = round($p['price'] * 0.85);
                        }
                    ?>
                        <div class="swiper-slide">
                            <div class="carousel-card">
                                <div class="carousel-badge">
                                    <?php if (in_array('hit', $p['tags'])): ?>
                                        <span class="badge badge-hit"><i class="fas fa-fire"></i> ХИТ</span>
                                    <?php endif; ?>
                                    <?php if (in_array('sale', $p['tags'])): ?>
                                        <span class="badge badge-sale"><i class="fas fa-tag"></i> -15%</span>
                                    <?php endif; ?>
                                    <?php if (in_array('new', $p['tags'])): ?>
                                        <span class="badge badge-new"><i class="fas fa-star"></i> NEW</span>
                                    <?php endif; ?>
                                </div>
                                <img src="<?= htmlspecialchars($p['image']) ?>" class="carousel-img">
                                <div class="carousel-info">
                                    <div class="carousel-name"><?= htmlspecialchars($p['name']) ?></div>
                                    <div class="carousel-rating">
                                        <div class="stars">
                                            <?php for($i = 0; $i < $fullStars; $i++): ?>
                                                <i class="fas fa-star"></i>
                                            <?php endfor; ?>
                                            <?php if($halfStar): ?>
                                                <i class="fas fa-star-half-alt"></i>
                                            <?php endif; ?>
                                            <?php for($i = 0; $i < $emptyStars; $i++): ?>
                                                <i class="far fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                        <span class="rating-value"><?= number_format($rating['avg'], 1, '.', '') ?></span>
                                        <span class="reviews-count">(<?= $rating['count'] ?>)</span>
                                    </div>
                                    <div class="carousel-price">
                                        <?= number_format($salePrice, 0, '.', ' ') ?> ₽
                                        <?php if ($oldPrice): ?>
                                            <span class="old-price"><?= number_format($oldPrice, 0, '.', ' ') ?> ₽</span>
                                        <?php endif; ?>
                                    </div>
                                    <button class="carousel-btn btn-order" data-id="<?= (int)$p['id'] ?>">
                                        <i class="fas fa-shopping-cart"></i> В корзину
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- PRODUCTS (основной каталог) -->
    <section class="products-section" id="products">
        <div class="container">
            <h2 class="products-title">Весь каталог</h2>
            <div class="products-grid">
                <?php
                $result = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
                while ($p = mysqli_fetch_assoc($result)):
                    $rating = getProductRating($conn, $p['id']);
                    $fullStars = floor($rating['avg']);
                    $halfStar = ($rating['avg'] - $fullStars) >= 0.5;
                    $emptyStars = 5 - $fullStars - ($halfStar ? 1 : 0);
                ?>
                    <div class="product-card"> 
                        <img src="<?= htmlspecialchars($p['image']) ?>">
                        <div class="product-name"><?= htmlspecialchars($p['name']) ?></div>
                        <div class="product-rating">
                            <div class="stars">
                                <?php for($i = 0; $i < $fullStars; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                                <?php if($halfStar): ?>
                                    <i class="fas fa-star-half-alt"></i>
                                <?php endif; ?>
                                <?php for($i = 0; $i < $emptyStars; $i++): ?>
                                    <i class="far fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-value"><?= number_format($rating['avg'], 1, '.', '') ?></span>
                            <span class="reviews-count">(<?= $rating['count'] ?> отзывов)</span>
                        </div>
                        <div class="product-price"><?= number_format($p['price'], 0, '.', ' ') ?> ₽</div>
                        <div class="product-actions">
                            <button class="btn btn-details" data-id="<?= (int)$p['id'] ?>">
                                <i class="fas fa-info-circle"></i> Подробнее
                            </button>
                            <button class="btn btn-order" data-id="<?= (int)$p['id'] ?>">
                                <i class="fas fa-shopping-cart"></i> Заказать
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- БЛОК СОТРУДНИЧЕСТВА -->
    <section class="cooperation-section" id="cooperation">
        <div class="container cooperation-grid">
            <div class="cooperation-content">
                <h2><i class="fas fa-handshake"></i> Станьте нашим партнёром</h2>
                <p>Мы открыты к сотрудничеству с фитнес-клубами, спортивными магазинами и дистрибьюторами. Предлагаем выгодные условия, индивидуальный подход и поддержку на всех этапах.</p>
                <ul class="cooperation-features">
                    <li><i class="fas fa-percent"></i> <strong>Специальные цены</strong> для партнёров</li>
                    <li><i class="fas fa-truck"></i> <strong>Бесплатная доставка</strong> от определённой суммы</li>
                    <li><i class="fas fa-chart-line"></i> <strong>Маркетинговая поддержка</strong> и рекламные материалы</li>
                    <li><i class="fas fa-headset"></i> <strong>Персональный менеджер</strong> 24/7</li>
                </ul>
            </div>
            <div class="cooperation-form">
                <h3><i class="fas fa-paper-plane"></i> Оставить заявку</h3>
                <?php if (isset($coop_success) && $coop_success): ?>
                    <div class="coop-success">
                        <i class="fas fa-check-circle"></i> Спасибо! Ваша заявка отправлена. Мы свяжемся с вами в ближайшее время.
                    </div>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> Ваше имя</label>
                        <input type="text" name="coop_name" required placeholder="Иван Иванов">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-envelope"></i> Email</label>
                        <input type="email" name="coop_email" required placeholder="ivan@example.com">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Телефон</label>
                        <input type="text" name="coop_phone" required placeholder="+7 (___) ___-__-__" id="coop_phone">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-comment"></i> Сообщение</label>
                        <textarea name="coop_message" rows="3" placeholder="Расскажите о вашем бизнесе..."></textarea>
                    </div>
                    <button type="submit" name="cooperation_submit" class="order-submit-btn">
                        <i class="fas fa-paper-plane"></i> Отправить заявку
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- ФУТЕР -->
    <footer class="footer" id="contacts">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-col">
                    <h3>FitFuel</h3>
                    <p>Ваш надёжный партнёр в мире спортивного питания. Качество, проверенное временем и тысячами спортсменов.</p>
                </div>
                <div class="footer-col">
                    <h3>Контакты</h3>
                    <p><i class="fas fa-phone"></i> +7 (800) 123-45-67</p>
                    <p><i class="fas fa-envelope"></i> info@fitfuel.ru</p>
                    <p><i class="fas fa-map-marker-alt"></i> г. Москва, ул. Спортивная, д. 15</p>
                    <p><i class="fas fa-clock"></i> Пн-Пт: 9:00 - 21:00, Сб-Вс: 10:00 - 18:00</p>
                </div>
                <div class="footer-col">
                    <h3>Информация</h3>
                    <ul class="footer-links">
                        <li><a href="#products"><i class="fas fa-dumbbell"></i> Каталог товаров</a></li>
                        <li><a href="#about"><i class="fas fa-info-circle"></i> О компании</a></li>
                        <li><a href="#cooperation"><i class="fas fa-handshake"></i> Сотрудничество</a></li>
                        <li><a href="#">Доставка и оплата</a></li>
                        <li><a href="#">Политика конфиденциальности</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Мы в соцсетях</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-vk"></i></a>
                        <a href="#"><i class="fab fa-telegram"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-youtube"></i></a>
                    </div>

                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 FitFuel. Все права защищены. Спортивное питание для достижения ваших целей.</p>
            </div>
        </div>
    </footer>

    <!-- PRODUCT MODAL -->
    <div id="modal-product" class="modal">
        <div class="modal-window">
            <button class="modal-close"><i class="fas fa-times"></i></button>
            <img id="m-image" class="modal-product-image" src="" alt="">
            <h2 id="m-name"></h2>
            <div id="m-rating" class="product-rating" style="margin: 10px 0;"></div>
            <div id="m-price" class="product-price"></div>

            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-align-left"></i> Описание</div>
                <div id="m-description" class="modal-section-content"></div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-flask"></i> Состав</div>
                <div id="m-composition" class="modal-section-content"></div>
            </div>

            <div class="modal-section">
                <div class="modal-section-title"><i class="fas fa-clock"></i> Способ применения</div>
                <div id="m-usage" class="modal-section-content"></div>
            </div>

            <div class="reviews-section">
                <button class="reviews-toggle" id="reviewsToggle">
                    <span><i class="fas fa-comments"></i> Отзывы покупателей</span> <i class="fas fa-chevron-down"></i>
                </button>
                <div class="reviews-list" id="reviewsList"></div>
            </div>

            <div class="review-form-section">
                <h3><i class="fas fa-pen"></i> Оставить отзыв</h3>
                <?php if (isLoggedIn()): ?>
                    <form id="reviewForm">
                        <input type="hidden" name="product_id" id="reviewProductId">
                        <div class="form-group">
                            <label><i class="fas fa-star" style="color:#FFB800;"></i> Оценка</label>
                            <select name="rating" required>
                                <option value="5">5 ★★★★★ - Отлично</option>
                                <option value="4">4 ★★★★☆ - Хорошо</option>
                                <option value="3">3 ★★★☆☆ - Средне</option>
                                <option value="2">2 ★★☆☆☆ - Плохо</option>
                                <option value="1">1 ★☆☆☆☆ - Ужасно</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-comment"></i> Ваш отзыв</label>
                            <textarea name="comment" required rows="4" placeholder="Что вам понравилось / не понравилось..."></textarea>
                        </div>
                        <button type="submit"><i class="fas fa-paper-plane"></i> Отправить отзыв</button>
                    </form>
                <?php else: ?>
                    <p style="text-align:center; color:var(--text-light);">
                        <a href="logreg.php" style="color:var(--primary-color); font-weight:600;"><i class="fas fa-sign-in-alt"></i> Войдите</a>, чтобы оставить отзыв
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ORDER MODAL -->
    <div id="orderModal" class="order-modal">
        <div class="order-modal-content">
            <div class="order-header">
                <h2><i class="fas fa-shopping-bag"></i> Оформление заказа</h2>
                <span class="order-close"><i class="fas fa-times"></i></span>
            </div>
            <form id="orderForm" action="process_order.php" method="POST">
                <input type="hidden" name="cart_data" id="cartDataHidden">
                <div class="order-fields">
                    <div class="form-group">
                        <label><i class="fas fa-user"></i> ФИО полностью</label>
                        <input type="text" name="fio" required placeholder="Иванов Иван Иванович">
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-phone"></i> Телефон</label>
                        <input type="text" name="phone" id="phone" required placeholder="+7 (___) ___-__-__">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-city"></i> Город</label>
                            <input type="text" name="city" required placeholder="Москва">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-road"></i> Улица</label>
                            <input type="text" name="street" required placeholder="Ленина">
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label><i class="fas fa-home"></i> Дом</label>
                            <input type="text" name="house" required placeholder="12">
                        </div>
                        <div class="form-group">
                            <label><i class="fas fa-door-open"></i> Квартира / офис</label>
                            <input type="text" name="flat" placeholder="45">
                        </div>
                    </div>
                    <div class="form-group">
                        <label><i class="fas fa-note-sticky"></i> Комментарий</label>
                        <textarea name="comment" placeholder="Ваши пожелания"></textarea>
                    </div>
                    <button type="submit" class="order-submit-btn">
                        <i class="fas fa-paper-plane"></i> Отправить заказ
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUCCESS POPUP -->
    <div id="successPopup" class="success-popup">
        <div class="popup-icon"><i class="fas fa-check-circle"></i></div>
        <div class="popup-text">Заказ успешно оформлен!</div>
    </div>

    <!-- CART -->
    <button id="cartButton" class="cart-btn">
        <i class="fas fa-shopping-cart"></i>
        <span id="cartCount">0</span>
    </button>
    <div id="cartOverlay" class="cart-overlay"></div>
    <div id="cartPanel" class="cart-panel">
        <div class="cart-header">
            <h2><i class="fas fa-shopping-cart"></i> Корзина</h2>
            <button id="cartClose" class="cart-close"><i class="fas fa-times"></i></button>
        </div>
        <div id="cartItems" class="cart-items"></div>
        <div class="cart-footer">
            <div id="cartEmpty" class="cart-empty" style="display:none;">Корзина пуста</div>
            <div class="cart-total">Итого: <strong id="cartTotal">0 ₽</strong></div>
            <button id="cartCheckout" class="cart-checkout">
                <i class="fas fa-credit-card"></i> Оформить заказ
            </button>
        </div>
    </div>

    <!-- AUTH -->
    <div class="auth-btn" style="position:fixed;top:25px;left:25px;z-index:1400">
        <?php if (isLoggedIn()): ?>
            <span style="background:linear-gradient(135deg,var(--primary-color),var(--primary-dark));color:#fff;padding:12px 20px;border-radius:50px;font-weight:600;display:flex;align-items:center;gap:8px;box-shadow:var(--shadow)">
                <i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['user_login'] ?? 'Пользователь') ?>
                <a href="order_history.php" style="color:#fff;margin-left:10px;text-decoration:none;"><i class="fas fa-history"></i> История</a>
                <a href="logout.php" style="color:#fff;margin-left:10px;text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Выйти</a>
            </span>
        <?php else: ?>
            <a href="logreg.php" style="background:linear-gradient(135deg,var(--primary-color),var(--primary-dark));color:#fff;padding:12px 20px;border-radius:50px;font-weight:600;text-decoration:none;display:flex;align-items:center;gap:8px;box-shadow:var(--shadow)">
                <i class="fas fa-sign-in-alt"></i> Войти / Регистрация
            </a>
        <?php endif; ?>
    </div>

    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.9/jquery.inputmask.min.js"></script>
    <script>
        // Инициализация Swiper карусели
        const swiper = new Swiper('.productSwiper', {
            slidesPerView: 1,
            spaceBetween: 20,
            loop: true,
            autoplay: {
                delay: 4000,
                disableOnInteraction: false,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                    spaceBetween: 20,
                },
                992: {
                    slidesPerView: 3,
                    spaceBetween: 25,
                },
                1200: {
                    slidesPerView: 4,
                    spaceBetween: 25,
                },
            },
        });

        let cart = JSON.parse(localStorage.getItem("cart") || "[]").map(i => ({ ...i, id: +i.id, price: +i.price, qty: +i.qty || 1 }));

        function formatMoney(n) {
            try { return Number(n).toLocaleString('ru-RU', { maximumFractionDigits: 0 }); }
            catch (e) { return n; }
        }

        function escapeHtml(text) {
            if (!text && text !== 0) return '';
            return String(text)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }
        
        function renderStars(rating) {
            rating = parseFloat(rating) || 0;
            const fullStars = Math.floor(rating);
            const halfStar = (rating - fullStars) >= 0.5;
            const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);
            let starsHtml = '';
            for(let i = 0; i < fullStars; i++) starsHtml += '<i class="fas fa-star"></i>';
            if(halfStar) starsHtml += '<i class="fas fa-star-half-alt"></i>';
            for(let i = 0; i < emptyStars; i++) starsHtml += '<i class="far fa-star"></i>';
            return starsHtml;
        }

        function updateCartUI() {
            const itemsEl = document.getElementById("cartItems");
            const countEl = document.getElementById("cartCount");
            const totalEl = document.getElementById("cartTotal");
            const emptyEl = document.getElementById("cartEmpty");

            itemsEl.innerHTML = "";
            if (!cart.length) {
                emptyEl.style.display = 'block';
                totalEl.innerText = "0 ₽";
                countEl.innerText = "0";
                localStorage.setItem("cart", JSON.stringify(cart));
                return;
            }
            emptyEl.style.display = 'none';

            let total = 0;
            cart.forEach(item => {
                const subtotal = item.price * item.qty;
                total += subtotal;
                itemsEl.insertAdjacentHTML('beforeend', `
                    <div class="cart-item" data-id="${item.id}">
                        <img src="${item.image || 'images/no-image.png'}" alt="${escapeHtml(item.name)}">
                        <div class="cart-item-info">
                            <div>
                                <div class="cart-item-name">${escapeHtml(item.name)}</div>
                                <div class="cart-item-price">${formatMoney(item.price)} ₽</div>
                            </div>
                            <div style="display:flex;align-items:center;justify-content:space-between;">
                                <div class="cart-qty">
                                    <button class="qty-decr" data-id="${item.id}">−</button>
                                    <span class="qty-value">${item.qty}</span>
                                    <button class="qty-incr" data-id="${item.id}">+</button>
                                </div>
                                <div style="text-align:right">
                                    <div style="font-weight:800">${formatMoney(subtotal)} ₽</div>
                                    <button class="cart-remove" data-id="${item.id}"><i class="fas fa-trash"></i> Удалить</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `);
            });

            countEl.innerText = cart.reduce((s, p) => s + p.qty, 0);
            totalEl.innerText = formatMoney(total) + " ₽";
            localStorage.setItem("cart", JSON.stringify(cart));
        }

        function addToCart(item) {
            const id = +item.id;
            let found = cart.find(x => x.id === id);
            if (found) found.qty += 1;
            else cart.push({ id, name: item.name || 'Товар', price: +item.price || 0, image: item.image || '', qty: 1 });
            updateCartUI();
            openCart();
            const popup = document.createElement('div');
            popup.className = 'success-popup';
            popup.style.cssText = 'position:fixed;bottom:100px;right:30px;background:#fff;padding:12px 20px;border-radius:12px;border-left:4px solid #0E9AA7;z-index:2100;';
            popup.innerHTML = '<i class="fas fa-check-circle" style="color:#0E9AA7;"></i> Товар добавлен в корзину!';
            document.body.appendChild(popup);
            setTimeout(() => popup.remove(), 2000);
        }

        function removeFromCart(id) {
            cart = cart.filter(x => x.id !== +id);
            updateCartUI();
        }

        function changeQty(id, delta) {
            const it = cart.find(x => x.id === +id);
            if (!it) return;
            it.qty = Math.max(1, it.qty + delta);
            if (it.qty <= 0) removeFromCart(id);
            else updateCartUI();
        }

        document.getElementById('cartButton').addEventListener('click', () => { updateCartUI(); openCart(); });
        document.getElementById('cartClose').addEventListener('click', closeCart);
        document.getElementById('cartOverlay').addEventListener('click', closeCart);

        function openCart() { 
            document.getElementById('cartPanel').classList.add('active'); 
            document.getElementById('cartOverlay').classList.add('active'); 
        }
        function closeCart() { 
            document.getElementById('cartPanel').classList.remove('active'); 
            document.getElementById('cartOverlay').classList.remove('active'); 
        }

        document.addEventListener('click', e => {
            const t = e.target;
            if (t.closest('.qty-incr')) changeQty(t.closest('.qty-incr').dataset.id, 1);
            if (t.closest('.qty-decr')) changeQty(t.closest('.qty-decr').dataset.id, -1);
            if (t.closest('.cart-remove')) removeFromCart(t.closest('.cart-remove').dataset.id);
        });

        // Обработка кнопок заказа (в карусели и в основном каталоге)
        document.querySelectorAll('.btn-order, .carousel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                <?php if (!isLoggedIn()) { echo "location.href = 'logreg.php'; return;"; } ?>
                const id = btn.dataset.id;
                fetch(`product_info.php?id=${encodeURIComponent(id)}`)
                    .then(r => r.ok ? r.json() : Promise.reject())
                    .then(p => {
                        const item = { id: +p.id, name: p.name || 'Товар', price: +p.price || 0, image: p.image || '' };
                        addToCart(item);
                        document.getElementById('cartDataHidden').value = JSON.stringify(cart);
                        closeCart();
                        document.getElementById("orderModal").classList.add("active");
                        setTimeout(() => document.querySelector('#orderModal input[name="fio"]').focus(), 100);
                    })
                    .catch(() => alert('Не удалось загрузить товар'));
            });
        });

        document.getElementById('cartCheckout').addEventListener('click', () => {
            <?php if (!isLoggedIn()) { echo "location.href = 'logreg.php'; return;"; } ?>
            if (!cart.length) return alert('Корзина пуста!');
            document.getElementById('cartDataHidden').value = JSON.stringify(cart);
            closeCart();
            document.getElementById("orderModal").classList.add("active");
            setTimeout(() => document.querySelector('#orderModal input[name="fio"]').focus(), 100);
        });

        $("#phone, #coop_phone").inputmask("+7 (999) 999-99-99", { showMaskOnHover: false });

        document.getElementById("orderForm").addEventListener("submit", function(e) {
            <?php if (!isLoggedIn()) { ?>
                e.preventDefault(); alert("Необходимо авторизоваться"); location.href = 'logreg.php'; return;
            <?php } ?>
            e.preventDefault();

            const req = this.querySelectorAll('[required]');
            let valid = true;
            req.forEach(f => {
                if (!f.value.trim()) { valid = false; f.style.borderColor = 'var(--accent-color)'; }
                else f.style.borderColor = '';
            });
            if (!valid) return alert('Заполните все обязательные поля');
            
            const submitBtn = this.querySelector('.order-submit-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            submitBtn.disabled = true;

            fetch('process_order.php', { method: 'POST', body: new FormData(this) })
                .then(r => { if (!r.ok) throw new Error(); return r.text(); })
                .then(() => {
                    document.getElementById("orderModal").classList.remove("active");
                    document.getElementById("successPopup").classList.add("show");
                    localStorage.removeItem("cart"); cart = []; updateCartUI(); this.reset();
                    setTimeout(() => {
                        document.getElementById("successPopup").classList.remove("show");
                        location.href = 'order_history.php';
                    }, 1800);
                })
                .catch(() => {
                    alert('Ошибка при отправке заказа');
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
        });

        document.addEventListener('click', async e => {
            const btn = e.target.closest('.btn-details');
            if (!btn) return;

            const productId = btn.dataset.id;
            const modal = document.getElementById("modal-product");
            modal.classList.add("show");
            document.getElementById("m-name").innerHTML = '<i class="fas fa-spinner fa-spin"></i> Загрузка...';
            
            try {
                const prodRes = await fetch(`product_info.php?id=${encodeURIComponent(productId)}`);
                if (!prodRes.ok) throw new Error();
                const p = await prodRes.json();

                document.getElementById("m-image").src = p.image || 'images/no-image.png';
                document.getElementById("m-name").innerText = p.name || '';
                document.getElementById("m-price").innerText = p.price ? formatMoney(p.price) + ' ₽' : '';
                document.getElementById("m-description").innerText = p.description || 'Информация отсутствует';
                document.getElementById("m-composition").innerText = p.composition || 'Информация отсутствует';
                document.getElementById("m-usage").innerText = p.usage || 'Информация отсутствует';
                
                const ratingRes = await fetch(`get_product_rating.php?product_id=${encodeURIComponent(productId)}`);
                const ratingData = await ratingRes.json();
                document.getElementById("m-rating").innerHTML = `
                    <div class="stars">${renderStars(ratingData.avg)}</div>
                    <span class="rating-value">${ratingData.avg}</span>
                    <span class="reviews-count">(${ratingData.count} отзывов)</span>
                `;

                document.getElementById("reviewProductId").value = productId;

                const revRes = await fetch(`get_reviews.php?product_id=${encodeURIComponent(productId)}`);
                const reviews = await revRes.json();

                const list = document.getElementById('reviewsList');
                list.innerHTML = '';

                if (reviews.length === 0) {
                    list.innerHTML = '<div class="no-reviews"><i class="fas fa-comment-slash"></i> Пока нет отзывов. Будьте первым!</div>';
                } else {
                    reviews.forEach(r => {
                        const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);
                        list.innerHTML += `
                            <div class="review-item">
                                <div class="review-header">
                                    <span class="review-author"><i class="fas fa-user-circle"></i> ${escapeHtml(r.author)}</span>
                                    <span class="review-date"><i class="far fa-calendar-alt"></i> ${r.date}</span>
                                </div>
                                <div class="review-rating">${stars}</div>
                                <div class="review-text">${escapeHtml(r.text)}</div>
                            </div>
                        `;
                    });
                }

                document.getElementById('reviewsToggle').classList.remove('active');
                list.classList.remove('active');
            } catch (err) {
                console.error(err);
                alert('Ошибка загрузки товара');
                modal.classList.remove("show");
            }
        });

        document.getElementById('reviewForm')?.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Отправка...';
            submitBtn.disabled = true;

            try {
                const response = await fetch('add_review.php', {
                    method: 'POST',
                    body: new FormData(this)
                });

                const data = await response.json();

                if (data.success) {
                    const r = data.review;
                    const stars = '★'.repeat(r.rating) + '☆'.repeat(5 - r.rating);

                    const list = document.getElementById('reviewsList');
                    if (list.querySelector('.no-reviews')) list.innerHTML = '';

                    list.insertAdjacentHTML('afterbegin', `
                        <div class="review-item" style="border-left:3px solid var(--primary-color); background:#f0f9ff;">
                            <div class="review-header">
                                <span class="review-author"><i class="fas fa-user-circle"></i> ${escapeHtml(r.author)} <small>(только что)</small></span>
                                <span class="review-date"><i class="far fa-calendar-alt"></i> ${r.date}</span>
                            </div>
                            <div class="review-rating">${stars}</div>
                            <div class="review-text">${escapeHtml(r.text)}</div>
                        </div>
                    `);
                    
                    const productId = document.getElementById("reviewProductId").value;
                    const ratingRes = await fetch(`get_product_rating.php?product_id=${encodeURIComponent(productId)}`);
                    const ratingData = await ratingRes.json();
                    document.getElementById("m-rating").innerHTML = `
                        <div class="stars">${renderStars(ratingData.avg)}</div>
                        <span class="rating-value">${ratingData.avg}</span>
                        <span class="reviews-count">(${ratingData.count} отзывов)</span>
                    `;
                    
                    const productCards = document.querySelectorAll('.product-card');
                    for(let card of productCards) {
                        const btn = card.querySelector('.btn-details');
                        if(btn && btn.dataset.id == productId) {
                            const ratingDiv = card.querySelector('.product-rating');
                            if(ratingDiv) {
                                ratingDiv.innerHTML = `
                                    <div class="stars">${renderStars(ratingData.avg)}</div>
                                    <span class="rating-value">${ratingData.avg}</span>
                                    <span class="reviews-count">(${ratingData.count} отзывов)</span>
                                `;
                            }
                            break;
                        }
                    }

                    this.reset();
                    const popup = document.createElement('div');
                    popup.className = 'success-popup';
                    popup.style.cssText = 'position:fixed;bottom:100px;right:30px;background:#fff;padding:12px 20px;border-radius:12px;border-left:4px solid #10b981;z-index:2100;';
                    popup.innerHTML = '<i class="fas fa-check-circle" style="color:#10b981;"></i> Спасибо за ваш отзыв!';
                    document.body.appendChild(popup);
                    setTimeout(() => popup.remove(), 3000);
                } else {
                    alert(data.error || 'Не удалось отправить отзыв');
                }
            } catch (err) {
                alert('Ошибка связи с сервером');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });

        document.querySelectorAll('.modal-close').forEach(b => b.onclick = () => document.getElementById('modal-product').classList.remove('show'));
        document.querySelectorAll('.order-close').forEach(b => b.onclick = () => document.getElementById('orderModal').classList.remove('active'));

        document.getElementById('modal-product').onclick = e => { if (e.target.id === 'modal-product') e.target.classList.remove('show'); };
        document.getElementById('orderModal').onclick = e => { if (e.target.id === 'orderModal') e.target.classList.remove('active'); };

        document.getElementById('reviewsToggle')?.addEventListener('click', () => {
            const toggle = document.getElementById('reviewsToggle');
            const list = document.getElementById('reviewsList');
            toggle.classList.toggle('active');
            list.classList.toggle('active');
        });

        updateCartUI();
        
        // Плавная прокрутка для навигации
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                const targetId = this.getAttribute('href');
                if (targetId && targetId !== '#') {
                    e.preventDefault();
                    const target = document.querySelector(targetId);
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }
                }
            });
        });
        // Замените блок загрузки товара на:
fetch(`product_info.php?id=${encodeURIComponent(productId)}`)
    .then(r => {
        console.log('Response status:', r.status);
        if (!r.ok) throw new Error(`HTTP ${r.status}`);
        return r.json();
    })
    .then(p => {
        console.log('Product data:', p);
        if (p.error) throw new Error(p.error);
        // ... остальной код
    })
    .catch(err => {
        console.error('Error details:', err);
        alert('Ошибка загрузки товара: ' + err.message);
        modal.classList.remove("show");
    });
    </script>
</body>
</html>