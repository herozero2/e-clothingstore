<?php
require_once __DIR__ . '/admin_auth.php';
require_once __DIR__ . '/store.php';
$con = require_admin(null, '../Admin/Adminlogin.php');
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$adminName = $_SESSION['admin_name'] ?? $_SESSION['admin_email'];
$currentScript = strtolower(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));

if (!function_exists('admin_nav_active')) {
    function admin_nav_active($needles)
    {
        global $currentScript;
        foreach ((array) $needles as $needle) {
            if (str_contains($currentScript, strtolower($needle))) {
                return 'active';
            }
        }
        return '';
    }
}

$storeSettings = get_store_settings($con);

$storeName = $storeSettings['store_name'] ?? "E-Clothing Store";
$storeLogo = $storeSettings['store_logo'] ?? null;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>E-Clothing Store Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/Admindashboard.css" />
    <link rel="stylesheet" href="../assets/css/add_product.css" />
    <link rel="stylesheet" href="../assets/css/view_product.css" />
    <link rel="stylesheet" href="../assets/css/edit_product.css" />
    <link rel="stylesheet" href="../assets/css/customers.css" />
    <link rel="stylesheet" href="../assets/css/admin-modern.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

</head>

<body>
    <header class="topnav admin-topbar">
        <a class="logo admin-brand" href="../Admin/Admindashboard.php" aria-label="Admin dashboard">
            <?php if ($storeLogo): ?>
                <img src="../assets/images/<?= htmlspecialchars($storeLogo) ?>"
                    alt="<?= htmlspecialchars($storeName) ?> Logo"
                    class="admin-brand-logo">
            <?php else: ?>
                <i class="fas fa-tshirt"></i>
            <?php endif; ?>
            <span><?= htmlspecialchars($storeName) ?></span>
        </a>

        <div class="admin-search-shell">
            <i class="fas fa-search" aria-hidden="true"></i>
            <input type="search" id="adminGlobalSearch" placeholder="Search this admin page..." autocomplete="off" aria-label="Search this admin page">
        </div>

        <nav class="topnav-menu admin-actions" aria-label="Admin shortcuts">
            <a href="../index.php" class="nav-link"><i class="fas fa-store"></i> Store</a>
            <a href="../Admin/Admindashboard.php" class="nav-link <?= admin_nav_active('/admin/admindashboard.php') ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
            <a href="../Admin/Logout.php" class="nav-link logout-link"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>

        <div class="welcome-msg">
            <i class="fas fa-user-circle"></i>
            <span>Welcome, <strong><?php echo htmlspecialchars($adminName); ?></strong></span>
        </div>
    </header>

    <aside class="sidebar">
        <ul class="sidebar-menu">
            <li><a href="../Admin/Admindashboard.php" class="sidebar-link <?= admin_nav_active('/admin/admindashboard.php') ?>"><i class="fas fa-chart-line"></i>
                    Dashboard</a></li>

            <li class="dropdown <?= admin_nav_active('/product/') ? 'open' : '' ?>">
                <a href="#" class="sidebar-link dropdown-toggle">
                    <i class="fas fa-box-open"></i> Products <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="../product/add.php" class="sidebar-sublink <?= admin_nav_active('/product/add.php') ?>">Add Product</a></li>
                    <li><a href="../product/view.php" class="sidebar-sublink <?= admin_nav_active('/product/view.php') ?>">View Products</a></li>
                </ul>
            </li>

            <li class="dropdown <?= admin_nav_active('/category/') ? 'open' : '' ?>">
                <a href="#" class="sidebar-link dropdown-toggle">
                    <i class="fas fa-tags"></i> Categories <i class="fas fa-chevron-down"></i>
                </a>
                <ul class="dropdown-menu">
                    <li><a href="../category/add_category.php" class="sidebar-sublink <?= admin_nav_active('/category/add_category.php') ?>">Add Category</a></li>
                    <li><a href="../category/view_category.php" class="sidebar-sublink <?= admin_nav_active('/category/view_category.php') ?>">View Categories</a></li>
                </ul>
            </li>

            <li><a href="../Admin/customers.php" class="sidebar-link <?= admin_nav_active('/admin/customers.php') ?>"><i class="fas fa-users"></i> Customers</a></li>
            <li><a href="../Admin/Adminorders.php" class="sidebar-link <?= admin_nav_active('/admin/adminorders.php') ?>"><i class="fas fa-shopping-cart"></i> Orders</a>
            </li>
            <li><a href="../Admin/product_rating_review.php" class="sidebar-link <?= admin_nav_active('/admin/product_rating_review.php') ?>"><i class="fas fa-comment-dots"></i>
                    Review</a></li>
            <li><a href="../Admin/report.php" class="sidebar-link <?= admin_nav_active('/admin/report.php') ?>"><i class="fas fa-file-alt"></i> Reports</a></li>
            <li><a href="../Admin/sliders.php" class="sidebar-link <?= admin_nav_active('/admin/sliders.php') ?>"><i class="fas fa-images"></i> Sliders</a></li>
            <li><a href="../Admin/pages.php" class="sidebar-link <?= admin_nav_active('/admin/pages.php') ?>"><i class="fas fa-file-signature"></i> Pages</a></li>
            <li><a href="../Admin/smtp_settings.php" class="sidebar-link <?= admin_nav_active('/admin/smtp_settings.php') ?>"><i class="fas fa-envelope-open-text"></i> SMTP Settings</a></li>
            <li><a href="../Admin/setting.php" class="sidebar-link <?= admin_nav_active('/admin/setting.php') ?>"><i class="fas fa-cog"></i> Settings</a></li>

        </ul>
    </aside>

    <main class="main-content">
