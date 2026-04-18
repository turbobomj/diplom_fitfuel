<?php
require_once "db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $product_id = intval($_POST["product_id"]);
    $fio = mysqli_real_escape_string($conn, $_POST["fio"]);
    $phone = mysqli_real_escape_string($conn, $_POST["phone"]);
    $city = mysqli_real_escape_string($conn, $_POST["city"]);
    $street = mysqli_real_escape_string($conn, $_POST["street"]);
    $house = mysqli_real_escape_string($conn, $_POST["house"]);
    $flat = mysqli_real_escape_string($conn, $_POST["flat"]);
    $comment = mysqli_real_escape_string($conn, $_POST["comment"]);

    // создаём заказ
    $sql = "INSERT INTO orders (fio, phone, city, street, house, flat, comment)
            VALUES ('$fio', '$phone', '$city', '$street', '$house', '$flat', '$comment')";
    mysqli_query($conn, $sql);

    $order_id = mysqli_insert_id($conn);

    // добавляем товар в заказ
    $sql2 = "INSERT INTO order_items (order_id, product_id)
             VALUES ($order_id, $product_id)";
    mysqli_query($conn, $sql2);

    echo "OK";
}
