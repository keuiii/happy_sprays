<?php
session_start();
echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>

