<?php
include("includes/header.php");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/product_variants.php';

$con = db_connect();
ensure_product_variants_schema($con);

$categoryResult = mysqli_query($con, "SELECT id, name FROM category WHERE deleted_at IS NULL ORDER BY name ASC");
$categories = [];
while ($category = mysqli_fetch_assoc($categoryResult)) {
    $categories[] = $category;
}

$rangeResult = mysqli_query($con, "SELECT MIN(price) AS min_price, MAX(price) AS max_price FROM product WHERE deleted_at IS NULL");
$priceRange = mysqli_fetch_assoc($rangeResult) ?: ['min_price' => 0, 'max_price' => 0];

$sizes = [];
$sizeResult = mysqli_query($con, "SELECT DISTINCT variation_value FROM productdetail WHERE LOWER(variation_key) = 'size' AND deleted_at IS NULL ORDER BY variation_value ASC");
while ($sizeResult && $row = mysqli_fetch_assoc($sizeResult)) {
    if ($row['variation_value'] !== '') {
        $sizes[] = $row['variation_value'];
    }
}

$colors = [];
$colorResult = mysqli_query($con, "SELECT DISTINCT variation_value FROM productdetail WHERE LOWER(variation_key) = 'color' AND deleted_at IS NULL ORDER BY variation_value ASC");
while ($colorResult && $row = mysqli_fetch_assoc($colorResult)) {
    if ($row['variation_value'] !== '') {
        $colors[] = $row['variation_value'];
    }
}

$search = trim($_GET['q'] ?? '');
$selectedCategories = isset($_GET['categories']) ? array_map('intval', (array) $_GET['categories']) : [];
$selectedSizes = isset($_GET['sizes']) ? array_map('strval', (array) $_GET['sizes']) : [];
$selectedColors = isset($_GET['colors']) ? array_map('strval', (array) $_GET['colors']) : [];
$minPrice = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float) $_GET['min_price'] : null;
$maxPrice = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float) $_GET['max_price'] : null;
$stock = $_GET['stock'] ?? 'all';
$sort = $_GET['sort'] ?? 'newest';

$conditions = ["p.deleted_at IS NULL"];
if ($search !== '') {
    $safeSearch = mysqli_real_escape_string($con, $search);
    $conditions[] = "(p.name LIKE '%$safeSearch%' OR p.description LIKE '%$safeSearch%' OR p.sku LIKE '%$safeSearch%' OR c.name LIKE '%$safeSearch%')";
}

if (!empty($selectedCategories)) {
    $ids = implode(',', $selectedCategories);
    $conditions[] = "p.category_id IN ($ids)";
}

if ($minPrice !== null) {
    $conditions[] = "p.price >= " . $minPrice;
}

if ($maxPrice !== null) {
    $conditions[] = "p.price <= " . $maxPrice;
}

if ($stock === 'in') {
    $conditions[] = "p.quantity > 0";
} elseif ($stock === 'out') {
    $conditions[] = "p.quantity <= 0";
}

foreach ([['size', $selectedSizes], ['color', $selectedColors]] as $variantFilter) {
    [$key, $values] = $variantFilter;
    if (!empty($values)) {
        $safeValues = array_map(fn($value) => "'" . mysqli_real_escape_string($con, $value) . "'", $values);
        $valueList = implode(',', $safeValues);
        $conditions[] = "EXISTS (
            SELECT 1 FROM productdetail pd
            WHERE pd.product_id = p.id
              AND pd.deleted_at IS NULL
              AND LOWER(pd.variation_key) = '$key'
              AND pd.variation_value IN ($valueList)
        )";
    }
}

$orderMap = [
    'newest' => 'p.created_at DESC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
    'stock' => 'p.quantity DESC',
];
$orderBy = $orderMap[$sort] ?? $orderMap['newest'];
$where = implode(' AND ', $conditions);

$sql = "SELECT p.*, c.name AS category_name 
        FROM product p 
        LEFT JOIN category c ON p.category_id = c.id 
        WHERE $where
        ORDER BY $orderBy";
$result = mysqli_query($con, $sql);
?>

<div class="container-fluid clothing py-5" style="padding-top: 170px !important;">
    <div class="container py-5">
        <div class="shop-toolbar mb-5">
            <div>
                <p class="text-primary fw-bold mb-1">Shop catalog</p>
                <h1 class="mb-0">All Products</h1>
            </div>
            <form method="GET" class="shop-search">
                <i class="fas fa-search"></i>
                <input type="search" name="q" id="searchBox" value="<?= htmlspecialchars($search) ?>" placeholder="Search product, SKU, category, style...">
                <button class="btn btn-primary" type="submit">Search</button>
            </form>
        </div>

        <form method="GET" class="filter-panel advanced-filter-panel mb-4">
            <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
            <div class="filter-header">
                <div>
                    <strong>Filters</strong>
                    <p class="text-muted mb-0">Category, price, availability, variants, and sorting.</p>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Apply Filters</button>
                    <a href="our_shop.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3">Clear</a>
                </div>
            </div>

            <div class="filter-grid">
                <label>
                    <span>Min Price</span>
                    <input type="number" name="min_price" min="0" step="1" value="<?= htmlspecialchars((string) ($_GET['min_price'] ?? '')) ?>" placeholder="<?= (int) $priceRange['min_price'] ?>">
                </label>
                <label>
                    <span>Max Price</span>
                    <input type="number" name="max_price" min="0" step="1" value="<?= htmlspecialchars((string) ($_GET['max_price'] ?? '')) ?>" placeholder="<?= (int) $priceRange['max_price'] ?>">
                </label>
                <label>
                    <span>Availability</span>
                    <select name="stock">
                        <option value="all" <?= $stock === 'all' ? 'selected' : '' ?>>All products</option>
                        <option value="in" <?= $stock === 'in' ? 'selected' : '' ?>>In stock</option>
                        <option value="out" <?= $stock === 'out' ? 'selected' : '' ?>>Out of stock</option>
                    </select>
                </label>
                <label>
                    <span>Sort By</span>
                    <select name="sort">
                        <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest first</option>
                        <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Price: low to high</option>
                        <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Price: high to low</option>
                        <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                        <option value="stock" <?= $sort === 'stock' ? 'selected' : '' ?>>Most stock</option>
                    </select>
                </label>
            </div>

            <div class="filter-block">
                <strong>Categories</strong>
                <div class="filter-categories">
                    <?php foreach ($categories as $category): ?>
                        <label>
                            <input type="checkbox" name="categories[]" value="<?= (int) $category['id'] ?>" <?= in_array((int) $category['id'], $selectedCategories, true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($sizes)): ?>
                <div class="filter-block">
                    <strong>Sizes</strong>
                    <div class="filter-categories">
                        <?php foreach ($sizes as $size): ?>
                            <label><input type="checkbox" name="sizes[]" value="<?= htmlspecialchars($size) ?>" <?= in_array($size, $selectedSizes, true) ? 'checked' : '' ?>><?= htmlspecialchars($size) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($colors)): ?>
                <div class="filter-block">
                    <strong>Colors</strong>
                    <div class="filter-categories">
                        <?php foreach ($colors as $color): ?>
                            <label><input type="checkbox" name="colors[]" value="<?= htmlspecialchars($color) ?>" <?= in_array($color, $selectedColors, true) ? 'checked' : '' ?>><?= htmlspecialchars($color) ?></label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </form>

        <div class="row g-4" id="productList">
            <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <?php while ($product = mysqli_fetch_assoc($result)): ?>
                    <div class="col-md-6 col-lg-4 col-xl-2 product-card" data-id="<?= (int) $product['id']; ?>"
                        data-name="<?= htmlspecialchars(strtolower($product['name']), ENT_QUOTES); ?>" data-sku="<?= htmlspecialchars(strtolower($product['sku']), ENT_QUOTES); ?>"
                        data-description="<?= htmlspecialchars(strtolower($product['description']), ENT_QUOTES); ?>" data-price="<?= (float) $product['price']; ?>"
                        data-category="<?= htmlspecialchars(strtolower($product['category_name']), ENT_QUOTES); ?>">
                        <div class="rounded position-relative clothing-item">
                            <a href="product_details.php?id=<?= (int) $product['id']; ?>">
                                <img src="../assets/images/<?= htmlspecialchars($product['image']); ?>"
                                    class="img-fluid w-100 rounded-top" alt="<?= htmlspecialchars($product['name']); ?>">
                            </a>
                            <div class="text-white bg-secondary px-3 py-1 rounded position-absolute product-badge" style="top: 10px; left: 10px;">
                                <?= htmlspecialchars($product['category_name']); ?>
                            </div>
                            <div class="p-4 border border-secondary border-top-0 rounded-bottom">
                                <span class="product-meta">SKU <?= htmlspecialchars($product['sku']); ?></span>
                                <h5><?= htmlspecialchars($product['name']); ?></h5>
                                <div class="d-flex justify-content-between flex-lg-wrap">
                                    <span class="text-dark fs-5 fw-bold"><?= money((float) $product['price'], $con); ?></span>
                                    <?php if ((int) $product['quantity'] > 0): ?>
                                        <a href="add_to_cart.php?id=<?= (int) $product['id']; ?>&quantity=1" class="btn border border-secondary rounded-pill px-3 text-primary">
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
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning">No products match the selected filters.</div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
<script>
    document.getElementById('searchBox').addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        document.querySelectorAll('.product-card').forEach(card => {
            const text = `${card.dataset.id} ${card.dataset.name} ${card.dataset.sku} ${card.dataset.description} ${card.dataset.category} ${card.dataset.price}`;
            card.style.display = text.includes(query) ? '' : 'none';
        });
    });
</script>
