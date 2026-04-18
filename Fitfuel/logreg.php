<?php
session_start();
require_once "db.php";

$error = '';
$success = '';

// Регистрация
if (isset($_POST['register'])) {
    $login = trim($_POST['login']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Проверки
    if (empty($login) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Все поля обязательны для заполнения';
    } elseif ($password !== $confirm_password) {
        $error = 'Пароли не совпадают';
    } elseif (strlen($password) < 6) {
        $error = 'Пароль должен быть не менее 6 символов';
    } else {
        // Проверяем, нет ли уже такого логина или email
        $check_query = "SELECT id FROM users WHERE login = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "ss", $login, $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = 'Пользователь с таким логином или email уже существует';
        } else {
            // Регистрируем пользователя (по умолчанию не админ)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_query = "INSERT INTO users (login, email, password, is_admin, created_at) VALUES (?, ?, ?, 0, NOW())";
            $stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($stmt, "sss", $login, $email, $hashed_password);
            
            if (mysqli_stmt_execute($stmt)) {
                $success = 'Регистрация успешна! Теперь вы можете войти.';
                // Очищаем поля после успешной регистрации
                $_POST = array();
            } else {
                $error = 'Ошибка регистрации: ' . mysqli_error($conn);
            }
        }
        mysqli_stmt_close($stmt);
    }
}

// Вход
if (isset($_POST['do_login'])) {
    $login_input = trim($_POST['login_input']);
    $password = trim($_POST['password']);
    
    if (empty($login_input) || empty($password)) {
        $error = 'Введите логин и пароль';
    } else {
        $query = "SELECT id, login, password, is_admin FROM users WHERE login = ? OR email = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "ss", $login_input, $login_input);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if ($user = mysqli_fetch_assoc($result)) {
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_login'] = $user['login'];
                $_SESSION['is_admin'] = $user['is_admin']; // КЛЮЧЕВАЯ СТРОКА!
                
                // Перенаправление в зависимости от роли
                if ($user['is_admin'] == 1) {
                    header('Location: admin.php');
                } else {
                    header('Location: index.php');
                }
                exit();
            } else {
                $error = 'Неверный пароль';
            }
        } else {
            $error = 'Пользователь не найден';
        }
        mysqli_stmt_close($stmt);
    }
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <title>FitFuel — Вход / Регистрация</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap');
        :root {
            --primary-color: #0E9AA7;
            --primary-dark: #0A7A85;
            --primary-light: #E6F7F9;
            --accent: #FF6B6B;
            --bg: #f5f7fa;
            --card: #ffffff;
            --text: #1E3A5F;
            --error: #FF6B6B;
            --success: #0E9AA7;
        }
        
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0
        }
        
        html,
        body {
            height: 100%
        }
        
        body {
            font-family: Montserrat, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #e6f7f9 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text);
            -webkit-font-smoothing: antialiased
        }
        
        #container {
            width: 900px;
            max-width: 95%;
            height: 520px;
            position: relative;
            perspective: 1500px;
            transform-style: preserve-3d
        }
        
        #container>div {
            position: absolute;
            width: 50%;
            min-width: 350px;
            height: 100%;
            top: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center
        }
        
        .content {
            width: 100%;
            padding: 2.2em 3.2em;
            text-align: center
        }
        
        h1 {
            font-weight: 700;
            font-size: 2.6em;
            margin-bottom: 0.6em
        }
        
        form input {
            background: var(--primary-light);
            border: none;
            padding: 12px 15px;
            margin: 8px 0;
            width: 100%;
            font-size: 1.1rem;
            border-radius: 10px;
            border: 1px solid rgba(14, 154, 167, 0.12);
            transition: all 0.3s ease;
        }
        
        form input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(14, 154, 167, 0.1);
        }
        
        .remember {
            float: left;
            color: var(--text);
            font-size: 0.95rem
        }
        
        .forget {
            float: right;
            color: var(--text);
            font-size: 0.95rem
        }
        
        .clearfix {
            clear: both;
            display: table
        }
        
        button {
            display: block;
            margin: 1em auto;
            border-radius: 40px;
            border: 1px solid var(--primary-color);
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: #fff;
            font-size: 1.05rem;
            font-weight: 700;
            padding: 0.8em 2em;
            letter-spacing: 1px;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(14, 154, 167, 0.3)
        }
        
        button:active {
            transform: scale(0.98)
        }
        
        .login {
            left: 0;
            background: var(--card);
            border-radius: 20px 0 0 20px;
            box-shadow: 0 14px 28px rgba(14, 154, 167, 0.08)
        }
        
        .register {
            right: 0;
            background: var(--card);
            border-radius: 0 20px 20px 0;
            z-index: 1;
            box-shadow: 0 14px 28px rgba(14, 154, 167, 0.06)
        }
        
        .page {
            right: 0;
            color: #fff;
            border-radius: 0 20px 20px 0;
            transform-origin: left center;
            transition: animation 1s linear
        }
        
        .front {
            background: linear-gradient(-45deg, var(--primary-light) 0%, var(--primary-color) 100%);
            z-index: 3
        }
        
        .back {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            z-index: 2
        }
        
        .front .content h1,
        .back .content h1 {
            color: #fff
        }
        
        .front button,
        .back button {
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.85);
            color: #fff;
            border-radius: 30px;
            padding: 12px 28px;
            width: auto
        }
        
        /* animations */
        .active .front {
            animation: rot-front 0.6s ease-in-out normal forwards
        }
        
        .active .back {
            animation: rot-back 0.6s ease-in-out normal forwards
        }
        
        .close .front {
            animation: close-rot-front 0.6s ease-in-out normal forwards
        }
        
        .close .back {
            animation: close-rot-back 0.6s ease-in-out normal forwards
        }
        
        @keyframes rot-front {
            from {
                transform: translateZ(2px) rotateY(0deg)
            }
            to {
                transform: translateZ(1px) rotateY(-180deg)
            }
        }
        
        @keyframes rot-back {
            from {
                transform: translateZ(1px) rotateY(0deg)
            }
            to {
                transform: translateZ(2px) rotateY(-180deg)
            }
        }
        
        @keyframes close-rot-front {
            from {
                transform: translateZ(1px) rotateY(-180deg)
            }
            to {
                transform: translateZ(2px) rotateY(0deg)
            }
        }
        
        @keyframes close-rot-back {
            from {
                transform: translateZ(2px) rotateY(-180deg)
            }
            to {
                transform: translateZ(1px) rotateY(0deg)
            }
        }
        
        /* show/hide content animations */
        .active .register .content {
            animation: show 0.7s ease-in-out forwards
        }
        
        .active .login .content {
            animation: hide 0.7s ease-in-out forwards
        }
        
        .close .register .content {
            animation: hide 0.7s ease-in-out forwards
        }
        
        .close .login .content {
            animation: show 0.7s ease-in-out forwards
        }
        
        @keyframes show {
            from {
                opacity: 0;
                transform: scale(0.8)
            }
            to {
                opacity: 0.99;
                transform: scale(0.99)
            }
        }
        
        @keyframes hide {
            from {
                opacity: 0.99;
                transform: scale(0.99)
            }
            to {
                opacity: 0;
                transform: scale(0.8)
            }
        }
        
        /* Сообщения об ошибках/успехе */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 15px;
            font-weight: 600;
            font-size: 0.95rem;
            text-align: center;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-error {
            background: rgba(255, 107, 107, 0.1);
            color: var(--error);
            border: 1px solid rgba(255, 107, 107, 0.3);
        }
        
        .alert-success {
            background: rgba(14, 154, 167, 0.1);
            color: var(--success);
            border: 1px solid rgba(14, 154, 167, 0.3);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 15px;
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: color 0.3s ease;
        }
        
        .back-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }
        
        .copy {
            margin-top: 18px;
            color: rgba(30, 58, 95, 0.6);
            font-size: 0.95rem;
            display: block
        }
        
        .admin-badge {
            background: rgba(255, 107, 107, 0.1);
            border: 1px solid var(--accent);
            color: var(--accent);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 15px;
        }
        
        /* responsive */
        @media (max-width:900px) {
            #container>div {
                min-width: 300px
            }
            #container {
                width: 720px
            }
        }
        
        @media (max-width:600px) {
            #container {
                width: 360px;
                height: 560px
            }
            #container>div {
                width: 100%;
                left: 0;
                right: 0;
                border-radius: 18px
            }
            .front,
            .back {
                display: none
            }
            .login,
            .register {
                position: relative;
                transform: none;
                border-radius: 18px;
            }
            .active .login .content,
            .close .register .content {
                opacity: 1;
                transform: scale(1);
            }
            .active .register .content,
            .close .login .content {
                display: none;
            }
        }
        
        /* FIX: возвращаем текст на задней панели */
        .back .content {
            transform: rotateY(180deg);
        }
    </style>
</head>

<body>

    <div id="container" class="<?php echo (isset($_POST['register']) && $error) ? 'active' : 'close'; ?>">
        <!-- LOGIN (left) -->
        <div class="login">
            <div class="content">
                <h1>Вход</h1>

                <?php if ($error && isset($_POST['do_login'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="login_input" placeholder="Логин или e-mail" required value="<?php echo isset($_POST['login_input']) ? htmlspecialchars($_POST['login_input']) : ''; ?>">
                    <input type="password" name="password" placeholder="Пароль" required>
                    <span class="remember">
                        <input type="checkbox" id="remember" style="width: auto; margin-right: 5px;">
                        <label for="remember" style="display: inline;">Запомнить меня</label>
                    </span>
                    <span class="forget"><a href="#" style="color: var(--text); text-decoration: none;">Забыли пароль?</a></span>
                    <span class="clearfix"></span>
                    <button type="submit" name="do_login">
                        <i class="fas fa-sign-in-alt"></i> Войти
                    </button>
                </form>

                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Вернуться в магазин
                </a>
                <span class="copy">© FitFuel 2024</span>
            </div>
        </div>

        <!-- FRONT page -> shows Register CTA -->
        <div class="page front">
            <div class="content">
                <h1>Привет!</h1>
                <p style="color:rgba(255,255,255,0.9);margin-bottom:18px;">Создай аккаунт FitFuel и начни улучшать результаты сегодня.</p>
                <button id="register">
                    <i class="fas fa-user-plus"></i> Регистрация
                </button>
            </div>
        </div>

        <!-- BACK page -> shows Login CTA -->
        <div class="page back">
            <div class="content">
                <h1>С возвращением!</h1>
                <p style="color:rgba(255,255,255,0.9);margin-bottom:18px;">Уже с нами? Войдите в аккаунт.</p>
                <button id="login">
                    <i class="fas fa-sign-in-alt"></i> Вход
                </button>
            </div>
        </div>

        <!-- REGISTER (right) -->
        <div class="register">
            <div class="content">
                <h1>Регистрация</h1>

                <?php if ($error && isset($_POST['register'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="text" name="login" placeholder="Логин" required value="<?php echo isset($_POST['login']) ? htmlspecialchars($_POST['login']) : ''; ?>">
                    <input type="email" name="email" placeholder="E-mail" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                    <input type="password" name="password" placeholder="Пароль (мин. 6 символов)" required>
                    <input type="password" name="confirm_password" placeholder="Повтор пароля" required>
                    <span class="clearfix"></span>
                    <button type="submit" name="register">
                        <i class="fas fa-user-plus"></i> Создать аккаунт
                    </button>
                </form>

                <a href="index.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Вернуться в магазин
                </a>
            </div>
        </div>

    </div>

    <script>
        const registerButton = document.getElementById('register')
        const loginButton = document.getElementById('login')
        const container = document.getElementById('container')

        registerButton.onclick = function() {
            container.className = 'active'
        }
        loginButton.onclick = function() {
            container.className = 'close'
        }

        // Автоматически показываем форму регистрации если есть ошибка регистрации
        <?php if (isset($_POST['register']) && $error): ?>
            container.className = 'active';
        <?php endif; ?>
        
        // Автоматически показываем форму входа если есть ошибка входа
        <?php if (isset($_POST['do_login']) && $error): ?>
            container.className = 'close';
        <?php endif; ?>
        
        // Если есть успешная регистрация, показываем форму входа через 2 секунды
        <?php if ($success): ?>
        setTimeout(function() {
            container.className = 'close';
        }, 2000);
        <?php endif; ?>
    </script>

</body>

</html>