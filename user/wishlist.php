<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/wishlist.php';
require_once __DIR__ . '/../includes/store.php';

$con = db_connect();
$userId = (int) $_SESSION['user_id'];
ensure_wishlist_table($con);

$wishlistSql = "
    SELECT p.*, c.name AS category_name
    FROM wishlist w
    JOIN product p ON p.id = w.product_id
    LEFT JOIN category c ON c.id = p.category_id
    WHERE w.user_id = $userId AND p.deleted_at IS NULL
    ORDER BY w.created_at DESC";
$wishlistResult = mysqli_query($con, $wishlistSql);
?>
<?php include("includes/header.php"); ?>

<main class="container py-5" style="padding-top: 170px !important;">
    <div class="account-layout">
        <?php include("includes/account_sidebar.php"); ?>
        <section class="account-panel">
            <p class="text-primary fw-bold mb-1">Saved products</p>
            <h1>My Wishlist</h1>

            <?php if (!empty($_SESSION['wishlist_flash'])): ?>
                <div class="message-container success-msg"><?= htmlspecialchars($_SESSION['wishlist_flash']) ?></div>
                <?php unset($_SESSION['wishlist_flash']); ?>
            <?php endif; ?>

            <?php if ($wishlistResult && mysqli_num_rows($wishlistResult) > 0): ?>
                <div class="row g-4 mt-2">
                    <?php while ($product = mysqli_fetch_assoc($wishlistResult)): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="clothing-item">
                                <a href="product_details.php?id=<?= (int) $product['id'] ?>">
                                    <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($product['name']) ?>">
                                </a>
                                <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                    <span class="product-meta"><?= htmlspecialchars($product['category_name'] ?? 'Product') ?></span>
                                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                                    <div class="d-flex justify-content-between flex-lg-wrap align-items-center">
                                        <strong class="text-dark"><?= money((float) $product['price'], $con) ?></strong>
                                        <a href="remove_from_wishlist.php?id=<?= (int) $product['id'] ?>" class="btn btn-outline-danger rounded-pill px-3">
                                            <i class="fas fa-heart-broken me-2"></i>Remove
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-heart fa-3x text-primary mb-3"></i>
                    <h2>No Wishlist Items Yet</h2>
                    <p class="text-muted mb-4">Save products you like and they will appear here.</p>
                    <a href="our_shop.php" class="btn btn-primary rounded-pill px-4">Browse Products</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</main>

<?php include("includes/footer.php"); ?>
