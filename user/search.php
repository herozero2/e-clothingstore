<?php
include("includes/header.php");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';

$con = db_connect();
$searchQuery = isset($_GET['search_query']) ? trim($_GET['search_query']) : '';
$products = [];

if ($searchQuery !== '') {
    $sql = "SELECT p.*, c.name AS category_name
            FROM product p
            LEFT JOIN category c ON p.category_id = c.id
            WHERE p.deleted_at IS NULL
              AND (p.name LIKE ? OR p.description LIKE ? OR p.sku LIKE ? OR c.name LIKE ?)
            ORDER BY p.name ASC";
    $stmt = mysqli_prepare($con, $sql);
    $term = "%{$searchQuery}%";
    mysqli_stmt_bind_param($stmt, "ssss", $term, $term, $term, $term);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $products[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<main class="container-fluid clothing py-5" style="padding-top: 170px !important;">
    <div class="container py-5">
        <div class="shop-toolbar mb-4">
            <div>
                <p class="text-primary fw-bold mb-1">Product search</p>
                <h1 class="mb-0">Search Results</h1>
            </div>
            <form action="search.php" method="GET" class="input-group">
                <input type="search" name="search_query" class="form-control py-3" value="<?= htmlspecialchars($searchQuery) ?>" placeholder="Search clothes, SKU, category...">
                <button class="btn btn-primary px-4" type="submit"><i class="fas fa-search me-2"></i>Search</button>
            </form>
        </div>

        <?php if ($searchQuery === ''): ?>
            <div class="alert alert-info">Type a product name, category, or SKU to search the catalog.</div>
        <?php else: ?>
            <p class="text-muted mb-4"><?= count($products) ?> result(s) for "<?= htmlspecialchars($searchQuery) ?>"</p>
            <div class="row g-4">
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <div class="col-md-6 col-lg-4 col-xl-2">
                            <div class="rounded position-relative clothing-item">
                                <a href="product_details.php?id=<?= (int) $product['id'] ?>">
                                    <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($product['name']) ?>">
                                </a>
                                <div class="text-white bg-secondary px-3 py-1 rounded position-absolute" style="top: 10px; left: 10px;">
                                    <?= htmlspecialchars($product['category_name']) ?>
                                </div>
                                <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                    <span class="product-meta">SKU <?= htmlspecialchars($product['sku']) ?></span>
                                    <h4><?= htmlspecialchars($product['name']) ?></h4>
                                    <div class="d-flex justify-content-between flex-lg-wrap">
                                        <p class="text-dark fs-5 fw-bold mb-0"><?= money((float) $product['price'], $con) ?></p>
                                        <?php if ((int) $product['quantity'] > 0): ?>
                                            <a href="add_to_cart.php?id=<?= (int) $product['id'] ?>" class="btn border border-secondary rounded-pill px-3 text-primary">
                                                <i class="fa fa-shopping-bag me-2 text-primary"></i> Add to cart
                                            </a>
                                        <?php else: ?>
                                            <button class="btn border border-secondary rounded-pill px-3 text-danger" disabled>Out of Stock</button>
                                        <?php endif; ?>
                                    </div>
                                    <a href="<?= isset($_SESSION['user_id']) ? 'add_to_wishlist.php?id=' . (int) $product['id'] : 'Userlogin.php' ?>"
                                        class="wishlist-card-action" title="Add to wishlist">
                                        <i class="fas fa-heart"></i> Wishlist
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-warning">No products matched your search.</div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include("includes/footer.php"); ?>
