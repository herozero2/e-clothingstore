<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php?redirect=checkout.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/mail_queue.php';
require_once __DIR__ . '/../includes/smtp.php';
require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/product_variants.php';

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$con = db_connect();
$cart = $_SESSION['cart'];
ensure_order_contact_schema($con);
ensure_product_variants_schema($con);
ensure_orderdetail_variant_schema($con);

$firstName = trim($_POST['first_name'] ?? '');
$lastName = trim($_POST['last_name'] ?? '');
$shippingAddress = trim($_POST['shipping_address'] ?? '');
$country = 'Nepal';
$mobile = trim($_POST['mobile'] ?? '');
$email = trim($_POST['email'] ?? '');
$shippingLatRaw = trim($_POST['shipping_lat'] ?? '');
$shippingLngRaw = trim($_POST['shipping_lng'] ?? '');
$shippingLat = $shippingLatRaw !== '' ? (float) $shippingLatRaw : null;
$shippingLng = $shippingLngRaw !== '' ? (float) $shippingLngRaw : null;
$shippingLocationDetails = trim($_POST['shipping_location_details'] ?? '');
$shippingCharge = isset($_POST['shipping_charge']) && in_array((float) $_POST['shipping_charge'], [300.0, 500.0], true)
    ? (float) $_POST['shipping_charge']
    : 300.0;
$paymentMethod = 'Cash on Delivery';
$userId = (int) $_SESSION['user_id'];
$customerName = trim($firstName . ' ' . $lastName) ?: 'Customer';

if ($shippingAddress === '' || $mobile === '' || $email === '') {
    $_SESSION['checkout_error'] = 'Please complete the shipping details before placing the order.';
    header("Location: checkout.php");
    exit;
}

try {
    mysqli_begin_transaction($con);

    $stockStmt = mysqli_prepare($con, "
        SELECT id, name, price, image, quantity
        FROM product
        WHERE id = ? AND deleted_at IS NULL
        FOR UPDATE
    ");
    $variantStockStmt = mysqli_prepare($con, "
        SELECT *
        FROM productdetail
        WHERE id = ? AND product_id = ? AND deleted_at IS NULL
        FOR UPDATE
    ");
    $orderItems = [];
    $subtotal = 0.0;

    foreach ($cart as $item) {
        $productId = (int) ($item['product_id'] ?? $item['id'] ?? 0);
        $variantId = (int) ($item['variant_id'] ?? 0);
        $quantity = max(1, (int) ($item['quantity'] ?? 1));
        if ($productId <= 0) {
            continue;
        }

        mysqli_stmt_bind_param($stockStmt, 'i', $productId);
        mysqli_stmt_execute($stockStmt);
        $result = mysqli_stmt_get_result($stockStmt);
        $product = $result ? mysqli_fetch_assoc($result) : null;

        if (!$product) {
            throw new RuntimeException('One of the products in your cart is no longer available.');
        }

        if ((int) $product['quantity'] < $quantity) {
            throw new RuntimeException('Not enough stock for ' . $product['name'] . '.');
        }

        $unitPrice = (float) $product['price'];
        $variantLabel = '';
        $variantSku = '';

        if ($variantId > 0) {
            mysqli_stmt_bind_param($variantStockStmt, 'ii', $variantId, $productId);
            mysqli_stmt_execute($variantStockStmt);
            $variantResult = mysqli_stmt_get_result($variantStockStmt);
            $variant = $variantResult ? mysqli_fetch_assoc($variantResult) : null;

            if (!$variant) {
                throw new RuntimeException('One selected option for ' . $product['name'] . ' is no longer available.');
            }

            if ((int) $variant['quantity'] < $quantity) {
                throw new RuntimeException('Not enough stock for ' . $product['name'] . ' (' . format_product_variant_label($variant) . ').');
            }

            $unitPrice += (float) $variant['price_adjustment'];
            $variantLabel = format_product_variant_label($variant);
            $variantSku = trim((string) ($variant['variant_sku'] ?? ''));
        }

        $lineTotal = $unitPrice * $quantity;
        $subtotal += $lineTotal;

        $orderItems[] = [
            'product_id' => $productId,
            'variant_id' => $variantId ?: null,
            'variant_label' => $variantLabel,
            'variant_sku' => $variantSku,
            'name' => $product['name'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total' => $lineTotal,
            'image' => $product['image'],
        ];
    }
    mysqli_stmt_close($stockStmt);
    mysqli_stmt_close($variantStockStmt);

    if (!$orderItems) {
        throw new RuntimeException('Your cart is empty.');
    }

    $insertOrder = mysqli_prepare($con, "
        INSERT INTO orders (user_id, name, payment_method, shipping_charge)
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($insertOrder, 'issd', $userId, $customerName, $paymentMethod, $shippingCharge);
    mysqli_stmt_execute($insertOrder);
    $orderId = mysqli_insert_id($con);
    mysqli_stmt_close($insertOrder);

    $insertShipping = mysqli_prepare($con, "
        INSERT INTO shipping (order_id, billing_address, shipping_address, mobile, email, shipping_lat, shipping_lng, shipping_location_details)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($insertShipping, 'issssdds', $orderId, $shippingAddress, $shippingAddress, $mobile, $email, $shippingLat, $shippingLng, $shippingLocationDetails);
    mysqli_stmt_execute($insertShipping);
    mysqli_stmt_close($insertShipping);

    $insertDetail = mysqli_prepare($con, "
        INSERT INTO orderdetail (order_id, product_id, variant_id, variant_label, variant_sku, quantity, unit_price, total)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $updateStock = mysqli_prepare($con, "UPDATE product SET quantity = quantity - ? WHERE id = ?");
    $updateVariantStock = mysqli_prepare($con, "UPDATE productdetail SET quantity = quantity - ? WHERE id = ? AND product_id = ?");

    foreach ($orderItems as $item) {
        $detailProductId = (int) $item['product_id'];
        $detailVariantId = $item['variant_id'] !== null ? (int) $item['variant_id'] : null;
        $detailVariantLabel = (string) $item['variant_label'];
        $detailVariantSku = (string) $item['variant_sku'];
        $detailQuantity = (int) $item['quantity'];
        $detailUnitPrice = (float) $item['unit_price'];
        $detailTotal = (float) $item['total'];

        mysqli_stmt_bind_param(
            $insertDetail,
            'iiissidd',
            $orderId,
            $detailProductId,
            $detailVariantId,
            $detailVariantLabel,
            $detailVariantSku,
            $detailQuantity,
            $detailUnitPrice,
            $detailTotal
        );
        mysqli_stmt_execute($insertDetail);

        mysqli_stmt_bind_param($updateStock, 'ii', $detailQuantity, $detailProductId);
        mysqli_stmt_execute($updateStock);

        if (!empty($detailVariantId)) {
            mysqli_stmt_bind_param($updateVariantStock, 'iii', $detailQuantity, $detailVariantId, $detailProductId);
            mysqli_stmt_execute($updateVariantStock);
        }
    }

    mysqli_stmt_close($insertDetail);
    mysqli_stmt_close($updateStock);
    mysqli_stmt_close($updateVariantStock);
    mysqli_commit($con);
} catch (Throwable $e) {
    mysqli_rollback($con);
    $_SESSION['checkout_error'] = $e->getMessage();
    header("Location: checkout.php");
    exit;
}

$grandTotal = $subtotal + $shippingCharge;
$table = "<h2>Thank you for your order, " . htmlspecialchars($customerName) . "!</h2>";
$table .= "<p><strong>Order ID:</strong> #$orderId<br>";
$table .= "<strong>Payment:</strong> $paymentMethod<br>";
$table .= "<strong>Shipping Charge:</strong> " . money($shippingCharge, $con) . "<br>";
$table .= "<strong>Shipping Address:</strong> " . htmlspecialchars($shippingAddress) . "<br>";
$table .= "<strong>Country:</strong> $country<br>";
$table .= "<strong>Mobile:</strong> " . htmlspecialchars($mobile) . "<br>";
$table .= "<strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
if ($shippingLocationDetails !== '') {
    $table .= "<p><strong>Map Location Details:</strong><br>" . nl2br(htmlspecialchars(str_replace(' | ', "\n", $shippingLocationDetails))) . "</p>";
}
$table .= "<table border='1' cellpadding='8' cellspacing='0'>";
$table .= "<tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Total</th></tr>";

foreach ($orderItems as $item) {
    $table .= "<tr>";
    $displayName = $item['name'] . (!empty($item['variant_label']) ? ' - ' . $item['variant_label'] : '');
    $table .= "<td>" . htmlspecialchars($displayName) . "</td>";
    $table .= "<td>" . (int) $item['quantity'] . "</td>";
    $table .= "<td>" . money((float) $item['unit_price'], $con) . "</td>";
    $table .= "<td>" . money((float) $item['total'], $con) . "</td>";
    $table .= "</tr>";
}

$table .= "</table><p><strong>Grand Total: " . money($grandTotal, $con) . "</strong></p>";

$smtpSettings = get_smtp_settings();
queue_mail($con, $email, $customerName, "Order Placed - Order #$orderId", $table);
queue_mail($con, $smtpSettings['admin_email'], 'E-Clothing Admin', "New Order Received - Order #$orderId", $table);

unset($_SESSION['cart']);

header("Location: order_success.php?order_id=$orderId");
exit;
