<?php
session_start();

// Полностью уничтожаем сессию
session_unset();
session_destroy();

// Удаляем куки сессии если они есть
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Проверяем, был ли запрос через AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
    // AJAX запрос - возвращаем JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Вы вышли из системы']);
    exit;
}

// Обычный запрос - выводим страницу с очисткой корзины
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="0; url=index.php">
    <title>Выход из системы</title>
    <script>
        // Очищаем корзину в localStorage
        localStorage.removeItem('cart');
        
        // Перенаправляем на главную страницу
        window.location.href = 'index.php';
    </script>
</head>
<body>
    <p>Выход из системы...</p>
</body>
</html>
<?php
exit();
?>