<?php
include '../includes/header.php';

$totalProducts = 0;
$totalUsers = 0;
$pendingOrders = 0;
$totalRevenue = 0;
$recentOrders = [];

$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM product WHERE deleted_at IS NULL");
if ($res) {
    $totalProducts = (int) (mysqli_fetch_assoc($res)['total'] ?? 0);
}

$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM users WHERE user_type = 'user' AND deleted_at IS NULL");
if ($res) {
    $totalUsers = (int) (mysqli_fetch_assoc($res)['total'] ?? 0);
}

$res = mysqli_query($con, "SELECT COUNT(*) AS total FROM orders WHERE LOWER(order_status) = 'pending' AND deleted_at IS NULL");
if ($res) {
    $pendingOrders = (int) (mysqli_fetch_assoc($res)['total'] ?? 0);
}

$revenueSql = "
    SELECT COALESCE(SUM(order_total), 0) AS total
    FROM (
        SELECT o.id, COALESCE(SUM(od.quantity * od.unit_price), 0) + COALESCE(o.shipping_charge, 0) AS order_total
        FROM orders o
        LEFT JOIN orderdetail od ON od.order_id = o.id AND od.deleted_at IS NULL
        WHERE o.deleted_at IS NULL AND LOWER(o.order_status) = 'delivered'
        GROUP BY o.id, o.shipping_charge
    ) totals";
$res = mysqli_query($con, $revenueSql);
if ($res) {
    $totalRevenue = (float) (mysqli_fetch_assoc($res)['total'] ?? 0);
}

$recentOrderSql = "
    SELECT id, name, order_status, payment_method, shipping_charge, created_at
    FROM orders
    WHERE deleted_at IS NULL
    ORDER BY created_at DESC, id DESC
    LIMIT 5";
$res = mysqli_query($con, $recentOrderSql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $recentOrders[] = $row;
    }
}
?>

<section class="dashboard-content">
    <header class="page-header dashboard-heading">
        <div>
            <p class="eyebrow">Admin dashboard</p>
            <h1>Store Overview</h1>
        </div>
        <div class="dashboard-actions">
            <a href="../product/add.php" class="quick-action"><i class="fas fa-plus"></i> Add Product</a>
            <a href="../Admin/Adminorders.php" class="quick-action"><i class="fas fa-truck"></i> Manage Orders</a>
        </div>
    </header>

    <div class="stats-container">
        <div class="stat-box">
            <i class="fas fa-box stat-icon"></i>
            <h3>Total Products</h3>
            <p><span class="count" data-target="<?= $totalProducts ?>">0</span></p>
        </div>
        <div class="stat-box">
            <i class="fas fa-users stat-icon"></i>
            <h3>Total Customers</h3>
            <p><span class="count" data-target="<?= $totalUsers ?>">0</span></p>
        </div>
        <div class="stat-box">
            <i class="fas fa-hourglass-half stat-icon"></i>
            <h3>Pending Orders</h3>
            <p><span class="count" data-target="<?= $pendingOrders ?>">0</span></p>
        </div>
        <div class="stat-box">
            <i class="fas fa-coins stat-icon"></i>
            <h3>Total Revenue</h3>
            <p><?= htmlspecialchars(currency_symbol($con)) ?> <span class="count" data-target="<?= (int) $totalRevenue ?>">0</span></p>
        </div>
    </div>

    <section class="dashboard-panel">
        <div class="panel-title">
            <h2>Recent Orders</h2>
            <a href="../Admin/Adminorders.php">View all</a>
        </div>

        <div class="table-container compact-table">
            <table>
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Shipping</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($recentOrders)): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($order['id']) ?></td>
                                <td><?= htmlspecialchars($order['name']) ?></td>
                                <td><span class="status-pill status-<?= strtolower(htmlspecialchars($order['order_status'])) ?>"><?= htmlspecialchars($order['order_status']) ?></span></td>
                                <td><?= htmlspecialchars($order['payment_method']) ?></td>
                                <td><?= money((float) $order['shipping_charge'], $con) ?></td>
                                <td><?= date("Y-m-d h:i A", strtotime($order['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="no-data">No recent orders found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<?php include '../includes/footer.php'; ?>

<script>
    document.querySelectorAll('.count').forEach(counter => {
        const target = Number(counter.dataset.target || 0);
        const step = Math.max(1, Math.ceil(target / 80));
        let value = 0;

        const updateCount = () => {
            value = Math.min(target, value + step);
            counter.innerText = value.toLocaleString('en-IN');

            if (value < target) {
                window.setTimeout(updateCount, 15);
            }
        };

        updateCount();
    });
</script>
