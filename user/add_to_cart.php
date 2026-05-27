<?php
session_start();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid product ID.");
}

$product_id = (int) $_GET['id'];
$variant_id = isset($_GET['variant_id']) && is_numeric($_GET['variant_id']) ? (int) $_GET['variant_id'] : 0;
$buyNow = isset($_GET['buy_now']) && $_GET['buy_now'] === '1';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product_variants.php';
$con = db_connect();

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$stmt = mysqli_prepare($con, "SELECT id, name, price, image, quantity FROM product WHERE id = ? AND deleted_at IS NULL");
mysqli_stmt_bind_param($stmt, "i", $product_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) === 0) {
    die("Product not found.");
}

$product = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

$allVariants = get_product_variants($con, $product_id);
$variant = null;

if ($variant_id > 0) {
    $variant = get_product_variant($con, $product_id, $variant_id);
    if (!$variant) {
        die("Selected product option is not available.");
    }
} elseif (!empty($allVariants)) {
    $variant = first_available_product_variant($con, $product_id);
}

if (!empty($allVariants) && !$variant) {
    die("This product option is out of stock.");
}

$variantId = $variant ? (int) $variant['id'] : null;
$cartKey = $variantId ? $product_id . ':v' . $variantId : (string) $product_id;
$variantLabel = format_product_variant_label($variant);
$variantSku = $variant ? trim((string) ($variant['variant_sku'] ?? '')) : '';
$unitPrice = (float) $product['price'] + ($variant ? (float) $variant['price_adjustment'] : 0.0);
$stock = $variant ? (int) $variant['quantity'] : (int) $product['quantity'];

if ($stock < 1) {
    die("This product is out of stock.");
}

if (isset($_SESSION['cart'][$cartKey])) {
    if ((int) $_SESSION['cart'][$cartKey]['quantity'] < $stock) {
        $_SESSION['cart'][$cartKey]['quantity'] += 1;
    }
} else {
    $_SESSION['cart'][$cartKey] = [
        'cart_key' => $cartKey,
        'id' => (int) $product['id'],
        'product_id' => (int) $product['id'],
        'variant_id' => $variantId,
        'variant_label' => $variantLabel,
        'variant_sku' => $variantSku,
        'name' => $product['name'],
        'price' => $unitPrice,
        'base_price' => (float) $product['price'],
        'image' => $product['image'],
        'quantity' => 1,
        'stock' => $stock,
    ];
}

$_SESSION['cart_flash'] = [
    'name' => $_SESSION['cart'][$cartKey]['name'] ?? 'Product',
    'variant' => $_SESSION['cart'][$cartKey]['variant_label'] ?? '',
    'image' => $_SESSION['cart'][$cartKey]['image'] ?? '',
    'quantity' => $_SESSION['cart'][$cartKey]['quantity'] ?? 1,
];

mysqli_close($con);

if ($buyNow) {
    $target = isset($_SESSION['user_id']) ? 'checkout.php' : 'Userlogin.php?redirect=checkout.php';
    header("Location: $target");
    exit;
}

$redirect = $_SERVER['HTTP_REFERER'] ?? '../index.php';
if (!str_contains($redirect, $_SERVER['HTTP_HOST'] ?? '')) {
    $redirect = '../index.php';
}

header("Location: $redirect");
exit;
