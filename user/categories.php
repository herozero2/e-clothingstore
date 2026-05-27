<?php
include("includes/header.php");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';

$con = db_connect();
$categories = [];
$categorySql = "SELECT id, name, description FROM category WHERE deleted_at IS NULL ORDER BY name ASC";
$categoryResult = mysqli_query($con, $categorySql);
while ($row = mysqli_fetch_assoc($categoryResult)) {
    $row['products'] = [];
    $categories[(int) $row['id']] = $row;
}

$productSql = "SELECT p.*, c.name AS category_name
               FROM product p
               JOIN category c ON c.id = p.category_id
               WHERE p.deleted_at IS NULL AND c.deleted_at IS NULL
               ORDER BY c.name ASC, p.created_at DESC";
$productResult = mysqli_query($con, $productSql);
while ($product = mysqli_fetch_assoc($productResult)) {
    $categoryId = (int) $product['category_id'];
    if (isset($categories[$categoryId])) {
        $categories[$categoryId]['products'][] = $product;
    }
}
?>

<main class="container-fluid py-5" style="padding-top: 170px !important;">
    <div class="container py-5">
        <div class="shop-toolbar mb-5">
            <div>
                <p class="text-primary fw-bold mb-1">Browse by category</p>
                <h1 class="mb-0">Categories</h1>
            </div>
            <a href="our_shop.php" class="btn btn-primary rounded-pill px-4">View All Products</a>
        </div>

        <div class="row g-4 mb-5">
            <?php foreach ($categories as $category): ?>
                <div class="col-md-6 col-lg-4 col-xl-3">
                    <a href="#category-<?= (int) $category['id'] ?>" class="category-card">
                        <span class="category-icon"><i class="fas fa-tags"></i></span>
                        <h3><?= htmlspecialchars($category['name']) ?></h3>
                        <p><?= htmlspecialchars($category['description'] ?? 'Explore selected products in this category.') ?></p>
                        <strong><?= count($category['products']) ?> products</strong>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <?php foreach ($categories as $category): ?>
            <section id="category-<?= (int) $category['id'] ?>" class="category-product-section">
                <div class="shop-toolbar mb-4">
                    <div>
                        <p class="text-primary fw-bold mb-1"><?= count($category['products']) ?> products</p>
                        <h2 class="mb-0"><?= htmlspecialchars($category['name']) ?></h2>
                    </div>
                    <a href="our_shop.php?categories[]=<?= (int) $category['id'] ?>" class="btn btn-outline-primary rounded-pill px-4">Shop <?= htmlspecialchars($category['name']) ?></a>
                </div>

                <?php if (!empty($category['products'])): ?>
                    <div class="row g-4">
                        <?php foreach ($category['products'] as $product): ?>
                            <div class="col-md-6 col-lg-4 col-xl-2">
                                <div class="rounded position-relative clothing-item">
                                    <a href="product_details.php?id=<?= (int) $product['id'] ?>">
                                        <img src="../assets/images/<?= htmlspecialchars($product['image']) ?>" class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($product['name']) ?>">
                                    </a>
                                    <div class="text-white bg-secondary px-3 py-1 rounded position-absolute product-badge" style="top: 10px; left: 10px;">
                                        <?= htmlspecialchars($category['name']) ?>
                                    </div>
                                    <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                        <span class="product-meta">SKU <?= htmlspecialchars($product['sku']) ?></span>
                                        <h4><?= htmlspecialchars($product['name']) ?></h4>
                                        <div class="d-flex justify-content-between flex-lg-wrap">
                                            <p class="text-dark fs-5 fw-bold mb-0"><?= money((float) $product['price'], $con) ?></p>
                                            <?php if ((int) $product['quantity'] > 0): ?>
                                                <a href="add_to_cart.php?id=<?= (int) $product['id'] ?>" class="btn border border-secondary rounded-pill px-3 text-primary">
                                                    <i class="fa fa-shopping-bag me-2 text-primary"></i> Add
                                                </a>
                                            <?php else: ?>
                                                <button class="btn border border-secondary rounded-pill px-3 text-danger" disabled>Out</button>
                                            <?php endif; ?>
                                        </div>
                                        <a href="<?= isset($_SESSION['user_id']) ? 'add_to_wishlist.php?id=' . (int) $product['id'] : 'Userlogin.php' ?>" class="wishlist-card-action">
                                            <i class="fas fa-heart"></i> Wishlist
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">No products in this category yet.</div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<?php include("includes/footer.php"); ?>
