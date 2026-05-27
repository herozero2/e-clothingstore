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
    mysqli_query($con, "DELETE FROM wishlist WHERE user_id = $userId AND product_id = $productId");
    $_SESSION['wishlist_flash'] = "Product removed from your wishlist.";
}

header("Location: wishlist.php");
exit;
