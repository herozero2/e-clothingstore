<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product_variants.php';

$con = db_connect();
if (!$con) {
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed']);
    exit;
}

$key = trim((string) ($_POST['key'] ?? ''));
if ($key === '' && isset($_POST['id'])) {
    $key = (string) (int) $_POST['id'];
}
$action = $_POST['action'] ?? '';
$cart = $_SESSION['cart'] ?? [];

if ($key === '' || !isset($cart[$key])) {
    echo json_encode(['status' => 'error', 'message' => 'Product not in cart']);
    exit;
}

$item = $cart[$key];
$id = (int) ($item['product_id'] ?? $item['id'] ?? $key);

// Get current stock from DB
$res = mysqli_query($con, "SELECT price, quantity FROM product WHERE id = $id AND deleted_at IS NULL");
if (!$res || mysqli_num_rows($res) === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Product not found']);
    exit;
}

$row = mysqli_fetch_assoc($res);
$stock = (int) $row['quantity'];
$price = (float) $row['price'];
$variantId = (int) ($item['variant_id'] ?? 0);

if ($variantId > 0) {
    $variant = get_product_variant($con, $id, $variantId);
    if (!$variant) {
        echo json_encode(['status' => 'error', 'message' => 'Selected product option is no longer available']);
        exit;
    }
    $price += (float) $variant['price_adjustment'];
    $stock = min($stock, (int) $variant['quantity']);
    $cart[$key]['variant_label'] = format_product_variant_label($variant);
    $cart[$key]['variant_sku'] = trim((string) ($variant['variant_sku'] ?? ''));
}

if ($stock < 1) {
    echo json_encode(['status' => 'error', 'message' => 'This product is out of stock']);
    exit;
}

$cart[$key]['price'] = $price;
$cart[$key]['stock'] = $stock;
$currentQty = (int) $cart[$key]['quantity'];

if ($action === 'increase' && $currentQty < $stock) {
    $cart[$key]['quantity']++;
} elseif ($action === 'decrease' && $currentQty > 1) {
    $cart[$key]['quantity']--;
}

$_SESSION['cart'] = $cart;

// Calculate item total and grand total
$itemTotal = number_format($cart[$key]['quantity'] * $price, 2);

$grandTotal = 0;
foreach ($cart as $cartItem) {
    $grandTotal += (int) ($cartItem['quantity'] ?? 1) * (float) ($cartItem['price'] ?? 0);
}

echo json_encode([
    'status' => 'success',
    'quantity' => $cart[$key]['quantity'],
    'item_total' => $itemTotal,
    'grand_total' => number_format($grandTotal, 2)
]);
?>
