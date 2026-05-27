<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/wishlist.php';

$con = db_connect();
ensure_wishlist_table($con);

$productId = (int) ($_GET['id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($productId > 0) {
    mysqli_query($con, "INSERT IGNORE INTO wishlist (user_id, product_id) VALUES ($userId, $productId)");
    $_SESSION['wishlist_flash'] = "Product saved to your wishlist.";
}

$redirect = $_SERVER['HTTP_REFERER'] ?? 'wishlist.php';
$host = $_SERVER['HTTP_HOST'] ?? '';
$redirectHost = parse_url($redirect, PHP_URL_HOST);
if ($redirectHost && $redirectHost !== $host) {
    $redirect = 'wishlist.php';
}

header("Location: $redirect");
exit;
