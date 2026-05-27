<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$con = db_connect();
$user_id = (int) $_SESSION['user_id'];

$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id = $user_id"));
$orderCount = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM orders WHERE user_id = $user_id AND deleted_at IS NULL"))['total'] ?? 0);
$pendingCount = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM orders WHERE user_id = $user_id AND order_status = 'Pending' AND deleted_at IS NULL"))['total'] ?? 0);
?>
<?php include("includes/header.php"); ?>

<main class="container py-5" style="padding-top: 170px !important;">
    <div class="account-layout">
        <?php include("includes/account_sidebar.php"); ?>
        <section class="account-panel">
            <p class="text-primary fw-bold mb-1">Account dashboard</p>
            <h1>Welcome, <?= htmlspecialchars($user['name'] ?? 'Customer') ?></h1>
            <p class="text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></p>

            <div class="row g-4 mt-2">
                <div class="col-md-6">
                    <div class="filter-panel h-100">
                        <h3><?= $orderCount ?></h3>
                        <p class="mb-0">Total Orders</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="filter-panel h-100">
                        <h3><?= $pendingCount ?></h3>
                        <p class="mb-0">Pending Orders</p>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <a href="our_shop.php" class="btn btn-primary rounded-pill px-4">Continue Shopping</a>
                <a href="profile.php" class="btn btn-outline-secondary rounded-pill px-4">Update Profile</a>
            </div>
        </section>
    </div>
</main>

<?php include("includes/footer.php"); ?>
