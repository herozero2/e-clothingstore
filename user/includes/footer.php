<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/pages.php';
require_once __DIR__ . '/../../includes/store.php';

$footerCon = isset($con) && $con instanceof mysqli ? $con : db_connect();
$footerSettings = get_store_settings($footerCon);
$footerPages = get_footer_pages($footerCon);
?>

<footer class="store-footer bg-dark text-white-50 pt-5 mt-5">
    <div class="container py-5">
        <div class="footer-top">
            <div>
                <h2 class="text-primary mb-1"><?= htmlspecialchars($footerSettings['store_name']) ?></h2>
                <p class="text-secondary mb-0">Curated clothing, reliable delivery, Cash on Delivery.</p>
            </div>
            <form class="footer-subscribe" action="contact.php" method="GET">
                <input class="form-control" type="email" placeholder="Your email" aria-label="Subscribe email">
                <button type="submit" class="btn btn-primary text-white">Subscribe</button>
            </form>
        </div>

        <div class="row g-5">
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">About Store</h4>
                    <p class="mb-4"><?= htmlspecialchars($footerSettings['store_information']) ?></p>
                    <a href="page.php?slug=about-us" class="btn border-secondary py-2 px-4 rounded-pill text-primary">Read More</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Shop Info</h4>
                    <?php foreach ($footerPages as $page): ?>
                        <a class="btn-link" href="page.php?slug=<?= urlencode($page['slug']) ?>"><?= htmlspecialchars($page['title']) ?></a>
                    <?php endforeach; ?>
                    <a class="btn-link" href="contact.php">Contact Us</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="d-flex flex-column text-start footer-item">
                    <h4 class="text-light mb-3">Account</h4>
                    <a class="btn-link" href="myaccount.php">My Account</a>
                    <a class="btn-link" href="our_shop.php">Shop</a>
                    <a class="btn-link" href="cart.php">Shopping Cart</a>
                    <a class="btn-link" href="wishlist.php">Wishlist</a>
                    <a class="btn-link" href="myorders.php">Order History</a>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="footer-item">
                    <h4 class="text-light mb-3">Contact</h4>
                    <p>Address: <?= htmlspecialchars($footerSettings['store_address']) ?></p>
                    <p>Email: <?= htmlspecialchars($footerSettings['store_email']) ?></p>
                    <p>Phone: <?= htmlspecialchars($footerSettings['contact_number']) ?></p>
                    <div class="cod-payment-badge"><i class="fas fa-money-bill-wave"></i> COD Only</div>
                </div>
            </div>
        </div>
    </div>
</footer>

<div class="container-fluid copyright bg-dark py-4">
    <div class="container">
        <span class="text-light"><a href="../index.php"><i class="fas fa-copyright text-light me-2"></i><?= htmlspecialchars($footerSettings['store_name']) ?></a>, All rights reserved.</span>
    </div>
</div>

<a href="#" class="btn btn-primary border-3 border-primary rounded-circle back-to-top"><i class="fa fa-arrow-up"></i></a>

<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="../design-assets/lib/easing/easing.min.js"></script>
<script src="../design-assets/lib/waypoints/waypoints.min.js"></script>
<script src="../design-assets/lib/lightbox/js/lightbox.min.js"></script>
<script src="../design-assets/lib/owlcarousel/owl.carousel.min.js"></script>
<script src="../design-assets/js/main.js"></script>
