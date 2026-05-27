<?php
session_start();

$key = trim((string) ($_GET['key'] ?? ''));
if ($key === '' && isset($_GET['id'])) {
    $key = (string) (int) $_GET['id'];
}

if ($key !== '' && isset($_SESSION['cart'][$key])) {
    unset($_SESSION['cart'][$key]);
}

// Redirect back to the cart page
header("Location: cart.php");
exit;
