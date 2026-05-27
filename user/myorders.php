<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/order_helpers.php';
require_once __DIR__ . '/../includes/product_variants.php';
$con = db_connect();
$user_id = (int) $_SESSION['user_id'];
ensure_order_contact_schema($con);
ensure_orderdetail_variant_schema($con);

$orderQuery = "
    SELECT o.*, s.shipping_address, s.mobile, s.email AS shipping_email, s.shipping_location_details
    FROM orders o
    LEFT JOIN shipping s ON s.order_id = o.id AND s.deleted_at IS NULL
    WHERE o.user_id = $user_id AND o.deleted_at IS NULL
    ORDER BY o.created_at DESC, o.id DESC
";
$orderResult = mysqli_query($con, $orderQuery);
?>

<?php include("includes/header.php"); ?>

<main class="container py-5" style="padding-top: 170px !important;">
    <div class="account-layout">
        <?php include("includes/account_sidebar.php"); ?>
        <section class="account-panel">
            <p class="text-primary fw-bold mb-1">Order history</p>
            <h1>My Orders</h1>

            <?php if ($orderResult && mysqli_num_rows($orderResult) > 0): ?>
                <?php $count = 1; ?>
                <?php while ($order = mysqli_fetch_assoc($orderResult)): ?>
                    <?php
                    $order_id = (int) $order['id'];
                    $detailsQuery = "SELECT od.*, p.name, p.image FROM orderdetail od
                                    JOIN product p ON od.product_id = p.id
                                    WHERE od.order_id = $order_id";
                    $detailsResult = mysqli_query($con, $detailsQuery);
                    $orderDetails = [];
                    $subtotal = 0;

                    while ($detail = mysqli_fetch_assoc($detailsResult)) {
                        $subtotal += $detail['unit_price'] * $detail['quantity'];
                        $orderDetails[] = $detail;
                    }
                    $shipping = (float) $order['shipping_charge'];
                    $grandTotal = $subtotal + $shipping;
                    $statusClass = strtolower($order['order_status']);
                    $trackingText = trim((string) ($order['tracking_id'] ?? '')) ?: 'Not assigned yet';
                    $phoneText = trim((string) ($order['mobile'] ?? '')) ?: 'Not provided';
                    $addressText = trim((string) ($order['shipping_address'] ?? '')) ?: 'Not provided';
                    ?>
                    <article class="filter-panel mb-4">
                        <button class="order-header w-100 text-start border-0 bg-transparent" type="button" data-bs-toggle="collapse" data-bs-target="#orderDetails<?= $count ?>">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div>
                                    <strong>Order #<?= htmlspecialchars($order['id']) ?></strong>
                                    <div class="text-muted"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></div>
                                </div>
                                <div class="d-flex flex-wrap justify-content-end gap-2">
                                    <span class="badge-status <?= $statusClass === 'pending' ? 'pending' : 'completed' ?>"><?= htmlspecialchars($order['order_status']) ?></span>
                                    <span class="badge-status completed">Tracking: <?= htmlspecialchars($trackingText) ?></span>
                                </div>
                            </div>
                        </button>

                        <div class="collapse mt-3" id="orderDetails<?= $count ?>">
                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <div class="bg-light rounded p-3 h-100">
                                        <small class="text-muted d-block">Order Status</small>
                                        <strong><?= htmlspecialchars($order['order_status']) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-light rounded p-3 h-100">
                                        <small class="text-muted d-block">Tracking ID</small>
                                        <strong><?= htmlspecialchars($trackingText) ?></strong>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="bg-light rounded p-3 h-100">
                                        <small class="text-muted d-block">Phone Number</small>
                                        <strong><?= htmlspecialchars($phoneText) ?></strong>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="bg-light rounded p-3">
                                        <small class="text-muted d-block">Delivery Address</small>
                                        <strong><?= htmlspecialchars($addressText) ?></strong>
                                        <?php if (!empty($order['shipping_location_details'])): ?>
                                            <div class="text-muted small mt-2"><?= nl2br(htmlspecialchars(str_replace(' | ', "\n", $order['shipping_location_details']))) ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Name</th>
                                            <th>Unit Price</th>
                                            <th>Qty</th>
                                            <th>Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orderDetails as $detail): ?>
                                            <tr>
                                                <td><img src="../assets/images/<?= htmlspecialchars($detail['image']) ?>" alt="<?= htmlspecialchars($detail['name']) ?>" style="width: 64px; height: 64px; object-fit: cover; border-radius: 8px;"></td>
                                                <td>
                                                    <?= htmlspecialchars($detail['name']) ?>
                                                    <?php if (!empty($detail['variant_label'])): ?>
                                                        <div class="text-muted small"><?= htmlspecialchars($detail['variant_label']) ?></div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($detail['variant_sku'])): ?>
                                                        <div class="text-muted small">SKU: <?= htmlspecialchars($detail['variant_sku']) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= money((float) $detail['unit_price'], $con) ?></td>
                                                <td><?= (int) $detail['quantity'] ?></td>
                                                <td><?= money((float) $detail['unit_price'] * (int) $detail['quantity'], $con) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-end flex-column align-items-end">
                                <div>Shipping: <?= money($shipping, $con) ?></div>
                                <strong>Grand Total: <?= money($grandTotal, $con) ?></strong>
                            </div>
                        </div>
                    </article>
                    <?php $count++; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart mb-3" style="font-size: 54px; color: #777;"></i>
                    <h2>No Orders Yet</h2>
                    <p class="text-muted">Start shopping to place your first order.</p>
                    <a href="our_shop.php" class="btn btn-primary rounded-pill px-4">Shop Now</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include("includes/footer.php"); ?>
