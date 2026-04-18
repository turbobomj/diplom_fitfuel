<?php
require_once "db.php";

$result = mysqli_query($conn, "SHOW COLUMNS FROM products");
echo "<pre>";
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
echo "</pre>";
?>