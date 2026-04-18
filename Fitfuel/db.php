<?php
// Настройки подключения к базе
$host = "localhost";
$user = "ledykrt1_fitfuel";
$pass = "Dan228";
$db   = "ledykrt1_fitfuel";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("Ошибка подключения к базе данных: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");
?>
