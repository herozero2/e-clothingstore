<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$cartCount = isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0;
?>
<?php $headerSearchQuery = isset($_GET['search_query']) ? trim($_GET['search_query']) : ''; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>E-Clothing Store</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&family=Raleway:wght@600;800&display=swap" rel="stylesheet"> 

    <!-- Icon Font Stylesheet -->
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.15.4/css/all.css"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../design-assets/lib/lightbox/css/lightbox.min.css" rel="stylesheet">
    <link href="../design-assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="../design-assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../design-assets/css/product_details.css" rel="stylesheet">
    <link href="../design-assets/css/order_success.css" rel="stylesheet">
    <link href="../design-assets/css/myorder.css" rel="stylesheet">
    <link href="../design-assets/css/style.css" rel="stylesheet"> 
    <link href="../design-assets/css/storefront-modern.css" rel="stylesheet">
</head>

<body>

<!-- Spinner Start -->
<div id="spinner" class="show w-100 vh-100 bg-white position-fixed translate-middle top-50 start-50  d-flex align-items-center justify-content-center">
    <div class="spinner-grow text-primary" role="status"></div>
</div>
<!-- Spinner End -->

<!-- Navbar start -->
<div class="container-fluid fixed-top">
    <div class="container topbar bg-primary d-none d-lg-block">
        <div class="d-flex justify-content-between">
            <div class="top-info ps-2">
                <small class="me-3"><i class="fas fa-map-marker-alt me-2 text-secondary"></i> <a href="#" class="text-white">Dhangadhi ,Kailali</a></small>
                <small class="me-3"><i class="fas fa-envelope me-2 text-secondary"></i><a href="mailto:help@example.com" class="text-white">help@example.com</a></small>
            </div>
            <div class="top-link pe-2">
                <a href="tel:+9779806478012" class="text-white"><small class="text-white mx-2"><i class="fas fa-phone-alt me-2 text-secondary"></i>+977 9806478012</small></a>
            </div>
        </div>
    </div><br>
    <div class="container px-0">
        <nav class="navbar navbar-light bg-white navbar-expand-lg">
            <a href="./../index.php" class="navbar-brand">
                <h2 class="text-primary display-6">E Clothing Store</h2>
            </a>
            <button class="navbar-toggler py-2 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
                <span class="fa fa-bars text-primary"></span>
            </button>
            <div class="collapse navbar-collapse bg-white" id="navbarCollapse">
                <div class="navbar-nav mx-auto">
                    <a href="./../index.php" class="nav-item nav-link active">Home</a> 
                    <a href="./categories.php" class="nav-item nav-link">Categories</a>
                    <a href="./our_shop.php" class="nav-item nav-link">Shop</a>
                    <a href="./contact.php" class="nav-item nav-link">Contact</a>
                </div>
                        <form action="./search.php" method="GET" class="header-search-form mx-xl-3 my-3 my-xl-0" role="search">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <input type="search" name="search_query" value="<?= htmlspecialchars($headerSearchQuery) ?>" placeholder="Search clothes, categories, styles..." aria-label="Search products">
                            <button type="submit">Search</button>
                        </form>
                    <div class="d-flex m-3 me-0 storefront-actions header-actions">
                        <a href="./cart.php" class="position-relative me-4 my-auto">
                        <i class="fa fa-shopping-bag fa-2x"></i>
                        <span class="position-absolute bg-secondary rounded-circle d-flex align-items-center justify-content-center text-dark px-1" style="top: -5px; left: 15px; height: 20px; min-width: 20px;"> <?php echo isset($cartCount) ? $cartCount : 0; ?></span>
                    </a>
                    <div class="dropdown account-dropdown my-auto">
                        <button class="account-button" type="button" id="accountMenu" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                            <i class="fas fa-user"></i>
                            <span><?= isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Account' ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="accountMenu">
                            <?php if (!isset($_SESSION['user_id'])): ?>
                                <li><a class="dropdown-item" href="./Userlogin.php"><i class="fas fa-sign-in-alt"></i> Login</a></li>
                                <li><a class="dropdown-item" href="./signup.php"><i class="fas fa-user-plus"></i> Register</a></li>
                            <?php else: ?>
                                <li><a class="dropdown-item" href="./myaccount.php"><i class="fas fa-columns"></i> Dashboard</a></li>
                                <li><a class="dropdown-item" href="./myorders.php"><i class="fas fa-box"></i> My Orders</a></li>
                                <li><a class="dropdown-item" href="./wishlist.php"><i class="fas fa-heart"></i> My Wishlist</a></li>
                                <li><a class="dropdown-item" href="./profile.php"><i class="fas fa-user-edit"></i> Profile</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="./logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
    </div>
</div>
<!-- Navbar End -->

<?php if (!empty($_SESSION['cart_flash'])):
    $cartFlash = $_SESSION['cart_flash'];
    unset($_SESSION['cart_flash']);
?>
    <div class="cart-toast" role="status" aria-live="polite">
        <div class="cart-toast-inner">
            <?php if (!empty($cartFlash['image'])): ?>
                <img src="../assets/images/<?= htmlspecialchars($cartFlash['image']) ?>" alt="">
            <?php endif; ?>
            <div>
                <h6>Added to cart</h6>
                <div><?= htmlspecialchars($cartFlash['name']) ?> is now in your cart.</div>
                <?php if (!empty($cartFlash['variant'])): ?>
                    <small class="text-muted"><?= htmlspecialchars($cartFlash['variant']) ?></small>
                <?php endif; ?>
                <div class="cart-toast-actions">
                    <a href="./cart.php" class="btn btn-sm btn-primary">View Cart</a>
                    <a href="./checkout.php" class="btn btn-sm btn-outline-success">Checkout</a>
                </div>
            </div>
        </div>
    </div>
    <script>
        window.setTimeout(function() {
            const toast = document.querySelector('.cart-toast');
            if (toast) toast.remove();
        }, 5000);
    </script>
<?php endif; ?>

<?php if (!empty($_SESSION['wishlist_flash'])): ?>
    <div class="cart-toast" role="status" aria-live="polite">
        <div class="cart-toast-inner">
            <i class="fas fa-heart text-primary fa-2x"></i>
            <div>
                <h6>Wishlist updated</h6>
                <div><?= htmlspecialchars($_SESSION['wishlist_flash']) ?></div>
                <div class="cart-toast-actions">
                    <a href="./wishlist.php" class="btn btn-sm btn-primary">View Wishlist</a>
                </div>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['wishlist_flash']); ?>
    <script>
        window.setTimeout(function() {
            const toast = document.querySelector('.cart-toast');
            if (toast) toast.remove();
        }, 5000);
    </script>
<?php endif; ?>
