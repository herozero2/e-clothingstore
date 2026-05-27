<?php
session_start();

// Check admin login
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: Adminlogin.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';

$con = db_connect();
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['order_status'])) {
    $order_id = (int)$_POST['order_id'];
    $order_status = mysqli_real_escape_string($con, $_POST['order_status']);
    $update_sql = "UPDATE orders SET order_status='$order_status' WHERE id=$order_id";
    mysqli_query($con, $update_sql);
    // Redirect to avoid form resubmission
    header("Location: Adminorders.php");
    exit();
}

// Fetch orders with user and product details
$sql = "
    SELECT 
        o.id AS order_id, o.name AS order_name, o.order_status, o.payment_method, o.created_at,
        u.name AS user_name, u.email AS user_email,
        GROUP_CONCAT(p.name SEPARATOR ', ') AS product_names,
        SUM(od.quantity) AS total_quantity,
        SUM(od.unit_price * od.quantity) AS total_price
    FROM orders o
    LEFT JOIN users u ON o.user_id = u.id
    LEFT JOIN orderdetail od ON od.order_id = o.id
    LEFT JOIN product p ON od.product_id = p.id
    WHERE o.deleted_at IS NULL
    GROUP BY o.id
    ORDER BY o.created_at ASC
";

$res = mysqli_query($con, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin - Orders Management</title>
    <link href="../design-assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding: 20px; font-family: Arial, sans-serif; }
        table { margin-top: 20px; }
        th, td { vertical-align: middle !important; }
        .status-select { width: 150px; }
    </style>
</head>
<body>
    <h1>Orders Management</h1>
    <table class="table table-bordered table-hover">
        <thead class="table-success">
            <tr>
                <th>#Order ID</th>
                <th>Customer Name</th>
                <th>Email</th>
                <th>Order Date</th>
                <th>Payment Method</th>
                <th>Product Names</th>
                <th>Total Qty</th>
                <th>Total Price (Rs)</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($res && mysqli_num_rows($res) > 0): ?>
                <?php while ($order = mysqli_fetch_assoc($res)): ?>
                <tr>
                    <td><?= $order['order_id'] ?></td>
                    <td><?= htmlspecialchars($order['order_name']) ?: htmlspecialchars($order['user_name']) ?></td>
                    <td><?= htmlspecialchars($order['user_email']) ?></td>
                    <td><?= date('Y-m-d h:i A', strtotime($order['created_at'])) ?></td>
                    <td><?= htmlspecialchars($order['payment_method']) ?></td>
                    <td><?= htmlspecialchars($order['product_names']) ?></td>
                    <td><?= (int)$order['total_quantity'] ?></td>
                    <td><?= number_format($order['total_price'], 2) ?></td>
                    <td>
                        <form method="POST" action="Adminorders.php" class="d-flex align-items-center gap-2">
                            <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                            <select name="order_status" class="form-select status-select" onchange="this.form.submit()">
                                <?php 
                                $statuses = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];
                                foreach ($statuses as $status):
                                ?>
                                <option value="<?= $status ?>" <?= ($order['order_status'] === $status) ? 'selected' : '' ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                            <noscript><button type="submit" class="btn btn-primary btn-sm">Update</button></noscript>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="9" class="text-center text-muted">No orders found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script src="../design-assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>