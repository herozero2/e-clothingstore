<?php
// File: cart.php
session_start();
include("includes/header.php");


$cart = $_SESSION['cart'] ?? [];

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/product_variants.php';

$con = db_connect();
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$products = [];
if ($cart) {
    $productIds = [];
    foreach ($cart as $cartKey => $item) {
        $productId = (int) ($item['product_id'] ?? $item['id'] ?? $cartKey);
        if ($productId > 0) {
            $productIds[] = $productId;
        }
    }
    $ids = implode(',', array_unique(array_map('intval', $productIds)));
    if ($ids !== '') {
        $sql = "SELECT id, name, price, image, quantity AS stock FROM product WHERE id IN ($ids) AND deleted_at IS NULL";
        $res = mysqli_query($con, $sql);
        while ($row = mysqli_fetch_assoc($res)) {
            $products[$row['id']] = $row;
        }
    }
}
?>
<br><br><br><br><br>
<div class="container mt-5">
    <h2 class="mb-4"><i class="fas fa-shopping-cart"></i> Your Shopping Cart</h2>
    <?php if (empty($cart)): ?>
        <p class="text-muted">Your cart is empty. Redirecting to homepage...</p>
        <script>
            setTimeout(() => window.location.href = '../index.php', 1000);
        </script>
    <?php else: ?>
        <table class="table table-bordered text-center align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Product</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Remove</th>
                </tr>
            </thead>
            <tbody>
                <?php $grandTotal = 0; ?>
                <?php foreach ($cart as $cartKey => $item): ?>
                    <?php
                    $productId = (int) ($item['product_id'] ?? $item['id'] ?? $cartKey);
                    if (!isset($products[$productId])) continue;
                    $product = $products[$productId];
                    $price = (float) $product['price'];
                    $variantId = (int) ($item['variant_id'] ?? 0);
                    $variantLabel = trim((string) ($item['variant_label'] ?? ''));
                    $variantSku = trim((string) ($item['variant_sku'] ?? ''));
                    $stock = (int) $product['stock'];

                    if ($variantId > 0) {
                        $variant = get_product_variant($con, $productId, $variantId);
                        if (!$variant) {
                            unset($_SESSION['cart'][$cartKey]);
                            continue;
                        }
                        $price += (float) $variant['price_adjustment'];
                        $variantLabel = format_product_variant_label($variant);
                        $variantSku = trim((string) ($variant['variant_sku'] ?? ''));
                        $stock = min($stock, (int) $variant['quantity']);
                    }

                    if ($stock < 1) {
                        unset($_SESSION['cart'][$cartKey]);
                        continue;
                    }

                    $quantity = (int) $item['quantity'];

                    if ($quantity > $stock) {
                        $_SESSION['cart'][$cartKey]['quantity'] = $stock;
                        $quantity = $stock;
                    }
                    $_SESSION['cart'][$cartKey]['product_id'] = $productId;
                    $_SESSION['cart'][$cartKey]['id'] = $productId;
                    $_SESSION['cart'][$cartKey]['price'] = $price;
                    $_SESSION['cart'][$cartKey]['variant_id'] = $variantId ?: null;
                    $_SESSION['cart'][$cartKey]['variant_label'] = $variantLabel;
                    $_SESSION['cart'][$cartKey]['variant_sku'] = $variantSku;
                    $_SESSION['cart'][$cartKey]['stock'] = $stock;

                    $total = $price * $quantity;
                    $grandTotal += $total;
                    $rowKey = md5((string) $cartKey);
                    ?>
                    <tr>
                        <td><img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" class="img-fluid" style="max-width: 100px;"></td>
                        <td>
                            <?php echo htmlspecialchars($product['name']); ?>
                            <?php if ($variantLabel !== ''): ?>
                                <div class="text-muted small"><?= htmlspecialchars($variantLabel) ?></div>
                            <?php endif; ?>
                            <?php if ($variantSku !== ''): ?>
                                <div class="text-muted small">SKU: <?= htmlspecialchars($variantSku) ?></div>
                            <?php endif; ?>
                        </td>
                        <td><?php echo money($price, $con); ?></td>
                        <td>
                            <div class="d-flex justify-content-center align-items-center">
                                <button class="btn btn-sm btn-outline-secondary me-2 btn-decrease" data-key="<?php echo htmlspecialchars((string) $cartKey); ?>" data-row="<?php echo $rowKey; ?>">-</button>
                                <span id="qty-<?php echo $rowKey; ?>"><?php echo $quantity; ?></span>
                                <button class="btn btn-sm btn-outline-secondary ms-2 btn-increase" data-key="<?php echo htmlspecialchars((string) $cartKey); ?>" data-row="<?php echo $rowKey; ?>">+</button>
                            </div>
                            <div class="text-muted small mt-1">(Stock: <?php echo $stock; ?>)</div>
                        </td>
                        <td id="total-<?php echo $rowKey; ?>"><?php echo money($total, $con); ?></td>
                        <td><a href="remove_from_cart.php?key=<?php echo urlencode((string) $cartKey); ?>" class="btn btn-danger btn-sm">X</a></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="4" class="text-end fw-bold">Grand Total</td>
                    <td colspan="2" class="fw-bold text-success" id="grand-total"><?php echo money($grandTotal, $con); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="d-flex justify-content-between">
            <a href="../index.php" class="btn btn-primary"><i class="fas fa-arrow-left"></i> Continue Shopping</a>
            <a href="<?php echo isset($_SESSION['user_id']) ? 'checkout.php' : 'Userlogin.php?redirect=checkout.php'; ?>" class="btn btn-success">Proceed to Checkout <i class="fas fa-arrow-right"></i></a>
        </div>
    <?php endif; ?>
</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(function () {
        function updateQuantity(cartKey, rowKey, action) {
            $.ajax({
                url: 'update_quantity.php',
                type: 'POST',
                data: { key: cartKey, action: action },
                dataType: 'json',
                success: function (response) {
                    if (response.status === 'success') {
                        $('#qty-' + rowKey).text(response.quantity);
                        $('#total-' + rowKey).text("<?= htmlspecialchars(currency_symbol($con)) ?> " + response.item_total);
                        $('#grand-total').text("<?= htmlspecialchars(currency_symbol($con)) ?> " + response.grand_total);
                    } else {
                        alert(response.message);
                    }
                },
                error: function () {
                    alert("Failed to update quantity");
                }
            });
        }

        $('.btn-increase').click(function () {
            updateQuantity($(this).data('key'), $(this).data('row'), 'increase');
        });

        $('.btn-decrease').click(function () {
            updateQuantity($(this).data('key'), $(this).data('row'), 'decrease');
        });
    });
</script>
<?php include("includes/footer.php"); ?>
