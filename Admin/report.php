<?php
include '../includes/header.php';

$limit = 8;
$page = max(1, (int) ($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$safeSearch = mysqli_real_escape_string($con, $search);
$where = "WHERE LOWER(o.order_status) = 'delivered' AND o.deleted_at IS NULL";

$dateRangePattern = '/^(\d{4}-\d{2}-\d{2})\s*-\s*(\d{4}-\d{2}-\d{2})$/';
if (preg_match($dateRangePattern, $search, $matches)) {
    $startDate = $matches[1] . " 00:00:00";
    $endDate = $matches[2] . " 23:59:59";
    $where .= " AND o.created_at BETWEEN '$startDate' AND '$endDate'";
} elseif ($search !== '') {
    $where .= " AND (p.name LIKE '%$safeSearch%' OR p.id LIKE '%$safeSearch%' OR u.name LIKE '%$safeSearch%' OR u.email LIKE '%$safeSearch%')";
}

$reportSql = "
    SELECT 
        p.id AS product_id,
        p.name AS product_name,
        DATE(o.created_at) AS ordered_date,
        COUNT(DISTINCT o.id) AS order_count,
        SUM(od.quantity) AS total_quantity,
        SUM(od.quantity * od.unit_price) AS product_revenue
    FROM orderdetail od
    JOIN product p ON od.product_id = p.id
    JOIN orders o ON od.order_id = o.id
    JOIN users u ON o.user_id = u.id
    $where
    GROUP BY p.id, p.name, DATE(o.created_at)
    ORDER BY ordered_date DESC, total_quantity DESC
    LIMIT $offset, $limit";
$result = mysqli_query($con, $reportSql);

$countSql = "
    SELECT COUNT(*) AS total
    FROM (
        SELECT p.id, DATE(o.created_at) AS ordered_date
        FROM orderdetail od
        JOIN product p ON od.product_id = p.id
        JOIN orders o ON od.order_id = o.id
        JOIN users u ON o.user_id = u.id
        $where
        GROUP BY p.id, DATE(o.created_at)
    ) grouped_report";
$countResult = mysqli_query($con, $countSql);
$totalRecords = (int) (mysqli_fetch_assoc($countResult)['total'] ?? 0);
$totalPages = max(1, (int) ceil($totalRecords / $limit));

$summarySql = "
    SELECT 
        COUNT(DISTINCT o.id) AS delivered_orders,
        COALESCE(SUM(od.quantity), 0) AS products_sold,
        COALESCE(SUM(od.quantity * od.unit_price), 0) AS product_revenue
    FROM orders o
    LEFT JOIN orderdetail od ON od.order_id = o.id AND od.deleted_at IS NULL
    LEFT JOIN product p ON od.product_id = p.id
    LEFT JOIN users u ON o.user_id = u.id
    $where";
$summary = mysqli_fetch_assoc(mysqli_query($con, $summarySql)) ?: [];

$revenueSql = "
    SELECT COALESCE(SUM(order_total), 0) AS total_revenue
    FROM (
        SELECT o.id, COALESCE(SUM(od.quantity * od.unit_price), 0) + COALESCE(o.shipping_charge, 0) AS order_total
        FROM orders o
        LEFT JOIN orderdetail od ON od.order_id = o.id AND od.deleted_at IS NULL
        LEFT JOIN product p ON od.product_id = p.id
        LEFT JOIN users u ON o.user_id = u.id
        $where
        GROUP BY o.id, o.shipping_charge
    ) order_totals";
$totalRevenue = (float) (mysqli_fetch_assoc(mysqli_query($con, $revenueSql))['total_revenue'] ?? 0);

$freqUserSql = "
    SELECT 
        u.id,
        u.name,
        u.email,
        u.created_at,
        COUNT(order_totals.order_id) AS total_orders,
        SUM(order_totals.order_total) AS total_price
    FROM users u
    JOIN (
        SELECT o.id AS order_id, o.user_id, COALESCE(SUM(od.unit_price * od.quantity), 0) + COALESCE(o.shipping_charge, 0) AS order_total
        FROM orders o
        LEFT JOIN orderdetail od ON od.order_id = o.id AND od.deleted_at IS NULL
        WHERE LOWER(o.order_status) = 'delivered' AND o.deleted_at IS NULL
        GROUP BY o.id, o.user_id, o.shipping_charge
    ) order_totals ON order_totals.user_id = u.id
    WHERE u.deleted_at IS NULL
    GROUP BY u.id, u.name, u.email, u.created_at
    HAVING total_orders >= 2
    ORDER BY total_orders DESC, total_price DESC";
$freqUserResult = mysqli_query($con, $freqUserSql);
$frequentUsers = [];
while ($freqUserResult && $user = mysqli_fetch_assoc($freqUserResult)) {
    $frequentUsers[] = $user;
}
?>

<section class="dashboard-content">
    <header class="page-header dashboard-heading">
        <div>
            <p class="eyebrow">Sales intelligence</p>
            <h1><i class="fas fa-file-alt"></i> Reports</h1>
        </div>
        <a href="Admindashboard.php" class="quick-action"><i class="fas fa-arrow-left"></i> Dashboard</a>
    </header>

    <form method="GET" class="search-wrapper report-search">
        <input type="search" name="search" id="searchInput"
            placeholder="Search product, user, email, or date range: YYYY-MM-DD - YYYY-MM-DD"
            value="<?= htmlspecialchars($search) ?>" autocomplete="off">
        <button type="submit" class="quick-action"><i class="fas fa-search"></i> Search</button>
        <?php if ($search !== ''): ?>
            <a href="report.php" class="admin-muted-link">Clear</a>
        <?php endif; ?>
    </form>

    <div class="stats-container report-stats">
        <div class="stat-box">
            <i class="fas fa-truck stat-icon"></i>
            <h3>Delivered Orders</h3>
            <p><?= number_format((int) ($summary['delivered_orders'] ?? 0)) ?></p>
        </div>
        <div class="stat-box">
            <i class="fas fa-boxes stat-icon"></i>
            <h3>Products Sold</h3>
            <p><?= number_format((int) ($summary['products_sold'] ?? 0)) ?></p>
        </div>
        <div class="stat-box">
            <i class="fas fa-coins stat-icon"></i>
            <h3>Total Revenue</h3>
            <p><?= money($totalRevenue, $con) ?></p>
        </div>
        <div class="stat-box">
            <i class="fas fa-user-check stat-icon"></i>
            <h3>Repeat Customers</h3>
            <p><?= number_format(count($frequentUsers)) ?></p>
        </div>
    </div>

    <section class="dashboard-panel">
        <div class="panel-title">
            <h2>Product Sales</h2>
            <span class="text-muted"><?= number_format($totalRecords) ?> report rows</span>
        </div>
        <div class="table-container" role="region">
            <table id="productReportTable" class="report-table report-filterable">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Orders</th>
                        <th>Quantity Sold</th>
                        <th>Product Revenue</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && mysqli_num_rows($result) > 0): ?>
                        <?php $sn = $offset + 1; ?>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?= $sn++ ?></td>
                                <td>#<?= (int) $row['product_id'] ?></td>
                                <td><?= htmlspecialchars($row['product_name']) ?></td>
                                <td><?= (int) $row['order_count'] ?></td>
                                <td><?= (int) $row['total_quantity'] ?></td>
                                <td><?= money((float) $row['product_revenue'], $con) ?></td>
                                <td><?= date('Y-m-d', strtotime($row['ordered_date'])) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No report records found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <nav class="pagination admin-pagination" aria-label="Report pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>" class="<?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </nav>
    </section>

    <section class="dashboard-panel">
        <div class="panel-title">
            <h2>Frequently Ordering Users</h2>
            <span class="text-muted">Customers with two or more delivered orders</span>
        </div>
        <div class="table-container" role="region">
            <table id="frequentUsersTable" class="report-table report-filterable">
                <thead>
                    <tr>
                        <th>S.N</th>
                        <th>User ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Orders</th>
                        <th>Total Spend</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($frequentUsers)): ?>
                        <?php foreach ($frequentUsers as $index => $user): ?>
                            <tr>
                                <td><?= $index + 1 ?></td>
                                <td>#<?= (int) $user['id'] ?></td>
                                <td><?= htmlspecialchars($user['name']) ?></td>
                                <td><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= (int) $user['total_orders'] ?></td>
                                <td><?= money((float) $user['total_price'], $con) ?></td>
                                <td><?= date('Y-m-d h:i A', strtotime($user['created_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center text-muted">No frequent users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</section>

<script>
    document.getElementById('searchInput').addEventListener('input', function () {
        const filter = this.value.toLowerCase();
        document.querySelectorAll('.report-filterable tbody tr').forEach(row => {
            row.style.display = row.innerText.toLowerCase().includes(filter) ? '' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
