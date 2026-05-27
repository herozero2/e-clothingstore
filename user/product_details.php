<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/store.php';
require_once __DIR__ . '/../includes/product_variants.php';
$con = db_connect();

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$product = null;
$avg_rating = 0;
$total_reviews = 0;
$reviews_result = false;
$variants = [];

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $sql = "SELECT p.*, c.name AS category_name 
            FROM product p
            LEFT JOIN category c ON p.category_id = c.id
            WHERE p.id = $id";

    $result = mysqli_query($con, $sql);
    if ($row = mysqli_fetch_assoc($result)) {
        $product = $row;

        // Get average rating and total reviews
        $rating_sql = "SELECT AVG(rating) AS avg_rating, COUNT(*) AS total_reviews 
                       FROM product_ratings 
                       WHERE product_id = $id";
        $rating_result = mysqli_query($con, $rating_sql);
        $rating_data = mysqli_fetch_assoc($rating_result);
        $avg_rating = round((float) ($rating_data['avg_rating'] ?? 0), 1);
        $total_reviews = (int) ($rating_data['total_reviews'] ?? 0);

        // Fetch all reviews
        $review_sql = "SELECT pr.rating, pr.review, u.name, pr.created_at 
                       FROM product_ratings pr 
                       JOIN users u ON pr.user_id = u.id 
                       WHERE pr.product_id = $id 
                       ORDER BY pr.created_at DESC";
        $reviews_result = mysqli_query($con, $review_sql);
        $variants = get_product_variants($con, $id);
    }
}

$firstAvailableVariantId = 0;
foreach ($variants as $variant) {
    if ((int) ($variant['quantity'] ?? 0) > 0) {
        $firstAvailableVariantId = (int) $variant['id'];
        break;
    }
}
?>

<?php include("includes/header.php"); ?> 

<style>
    .star-rating {
        direction: rtl;
        unicode-bidi: bidi-override;
        display: inline-flex;
    }
    .star-rating input {
        display: none;
    }
    .star-rating label {
        font-size: 1.5rem;
        color: #ccc;
        cursor: pointer;
    }
    .star-rating input:checked ~ label,
    .star-rating label:hover,
    .star-rating label:hover ~ label {
        color: gold;
    }

    .product-option-form {
        display: grid;
        gap: 1rem;
    }

    .variant-option-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: .75rem;
    }

    .variant-option-card {
        display: grid;
        gap: .25rem;
        min-height: 86px;
        padding: .9rem;
        border: 1px solid #dde5ef;
        border-radius: 8px;
        background: #fff;
        cursor: pointer;
        transition: border-color .2s ease, box-shadow .2s ease, transform .2s ease;
    }

    .variant-radio {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }

    .variant-option-card small {
        color: #6c757d;
    }

    .variant-radio:checked + .variant-option-card {
        border-color: #0f766e;
        box-shadow: 0 12px 26px rgba(15, 118, 110, .16);
        transform: translateY(-1px);
    }

    .variant-radio:disabled + .variant-option-card {
        opacity: .52;
        cursor: not-allowed;
        background: #f8f9fa;
    }

    .product-stock-line {
        color: #0f766e;
        font-weight: 700;
    }
</style>

<main class="container py-5" style="padding-top: 170px !important;">
<div class="product-container product-detail-panel p-4">
    <?php if ($product): ?>
        <div class="row g-5 align-items-start">
            <div class="col-md-6 text-center">
                <img src="../assets/images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="img-fluid product-image rounded">
            </div>
            <div class="col-md-6">
                <span class="category-badge"><?php echo htmlspecialchars($product['category_name']); ?></span>
                <h2 class="mt-3"><?php echo htmlspecialchars($product['name']); ?></h2>

                <!-- Rating Display -->
                <p class="mt-2">
                    Average Rating:
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= round($avg_rating) ? 'text-warning' : 'text-secondary'; ?>"></i>
                    <?php endfor; ?>
                    (<?= $avg_rating; ?>/5 from <?= $total_reviews; ?> reviews)
                </p>

                <p class="text-muted"><?php echo htmlspecialchars($product['description']); ?></p>
                <p><strong>SKU:</strong> <?php echo htmlspecialchars($product['sku']); ?></p>
                <p class="product-stock-line" id="stockText">
                    Quantity Available:
                    <?php if (!empty($variants) && $firstAvailableVariantId > 0): ?>
                        <?php
                        $selectedVariant = array_values(array_filter($variants, fn($variant) => (int) $variant['id'] === $firstAvailableVariantId))[0] ?? null;
                        echo htmlspecialchars((string) ((int) ($selectedVariant['quantity'] ?? 0)));
                        ?>
                    <?php else: ?>
                        <?php echo htmlspecialchars($product['quantity']); ?>
                    <?php endif; ?>
                </p>
                <p class="price-tag" id="productPrice"><?php echo money((float) $product['price'], $con); ?></p>
                <?php if (isset($_GET['rated'])): ?>
                    <div class="message-container success-msg">Thank you. Your review has been saved.</div>
                <?php endif; ?>
                <?php $canPurchase = (int) $product['quantity'] > 0 && (empty($variants) || $firstAvailableVariantId > 0); ?>
                <form class="product-option-form" id="productOptionForm" action="add_to_cart.php" method="GET">
                    <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                    <?php if (!empty($variants)): ?>
                        <div class="filter-panel mb-1">
                            <strong>Choose Option</strong>
                            <div class="variant-option-grid mt-3">
                                <?php foreach ($variants as $variant): ?>
                                    <?php
                                    $variantId = (int) $variant['id'];
                                    $variantStock = (int) ($variant['quantity'] ?? 0);
                                    $variantPrice = (float) $product['price'] + (float) $variant['price_adjustment'];
                                    $variantLabel = format_product_variant_label($variant);
                                    $isChecked = $variantId === $firstAvailableVariantId;
                                    $isDisabled = $variantStock <= 0;
                                    ?>
                                    <div>
                                        <input
                                            class="variant-radio"
                                            type="radio"
                                            name="variant_id"
                                            id="variant-<?= $variantId ?>"
                                            value="<?= $variantId ?>"
                                            data-price="<?= htmlspecialchars(money($variantPrice, $con), ENT_QUOTES, 'UTF-8') ?>"
                                            data-stock="<?= $variantStock ?>"
                                            <?= $isChecked ? 'checked' : '' ?>
                                            <?= $isDisabled ? 'disabled' : '' ?>
                                            required>
                                        <label class="variant-option-card" for="variant-<?= $variantId ?>">
                                            <strong><?= htmlspecialchars($variantLabel) ?></strong>
                                            <small><?= htmlspecialchars(trim((string) ($variant['variant_sku'] ?? '')) ?: 'SKU varies by option') ?></small>
                                            <small><?= $isDisabled ? 'Out of stock' : 'Stock: ' . $variantStock ?></small>
                                            <?php if ((float) $variant['price_adjustment'] !== 0.0): ?>
                                                <small><?= htmlspecialchars(money($variantPrice, $con)) ?></small>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="text-danger small mt-2" id="variantMessage" hidden>Please choose an available option.</div>
                        </div>
                    <?php endif; ?>
                    <div class="buy-actions">
                        <?php if ($canPurchase): ?>
                            <button type="submit" class="btn btn-success btn-style">
                                <i class="fa fa-shopping-cart me-2"></i>Add to Cart
                            </button>
                            <button type="submit" name="buy_now" value="1" class="btn btn-primary btn-style">
                                <i class="fa fa-bolt me-2"></i>Buy Now
                            </button>
                        <?php else: ?>
                            <button class="btn btn-outline-danger btn-style" disabled>Out of Stock</button>
                        <?php endif; ?>
                    </div>
                </form>
                <div class="buy-actions mt-2">
                    <a href="<?= isset($_SESSION['user_id']) ? 'add_to_wishlist.php?id=' . (int) $product['id'] : 'Userlogin.php' ?>" class="btn btn-outline-danger btn-style">
                        <i class="fas fa-heart me-2"></i>Add to Wishlist
                    </a>
                    <a href="our_shop.php" class="btn btn-outline-secondary btn-style">Back to Products</a>
                </div>

                <!-- Rating Submission -->
                <?php if (isset($_SESSION['user_id'])): ?>
                    <hr>
                    <h5>Rate this product</h5>
                    <form action="submit_rating.php" method="POST">
                        <input type="hidden" name="product_id" value="<?= $product['id']; ?>">
                        <div class="star-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?= $i; ?>" value="<?= $i; ?>" required>
                                <label for="star<?= $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="review" rows="3" class="form-control mt-2" placeholder="Write your review (optional)"></textarea>
                        <button type="submit" class="btn btn-primary mt-2">Submit Rating</button>
                    </form>
                <?php else: ?>
                    <p class="mt-3 text-muted"><a href="Userlogin.php">Login</a> to rate this product.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Show Reviews -->
        <?php if ($reviews_result && mysqli_num_rows($reviews_result) > 0): ?>
            <hr>
            <h5>User Reviews</h5>
            <?php while ($review = mysqli_fetch_assoc($reviews_result)): ?>
                <div class="border p-2 rounded mb-2">
                    <strong><?= htmlspecialchars($review['name']); ?></strong>
                    <small class="text-muted"><?= $review['created_at']; ?></small><br>
                    <?= str_repeat("⭐", $review['rating']); ?><br>
                    <em><?= nl2br(htmlspecialchars($review['review'])); ?></em>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    <?php else: ?>
        <div class="text-center">
            <h3>Product not found</h3>
            <a href="../index.php" class="btn btn-primary mt-3">Back to Home</a>
        </div>
    <?php endif; ?>
</div>
</main>

<script>
    (function setupProductVariants() {
        const form = document.getElementById('productOptionForm');
        const radios = Array.from(document.querySelectorAll('.variant-radio'));
        const priceEl = document.getElementById('productPrice');
        const stockEl = document.getElementById('stockText');
        const messageEl = document.getElementById('variantMessage');

        if (!form || radios.length === 0) {
            return;
        }

        function syncVariant() {
            const selected = radios.find(radio => radio.checked);
            if (!selected) {
                return;
            }
            priceEl.textContent = selected.dataset.price || priceEl.textContent;
            stockEl.textContent = 'Quantity Available: ' + (selected.dataset.stock || '0');
            if (messageEl) {
                messageEl.hidden = true;
            }
        }

        radios.forEach(radio => radio.addEventListener('change', syncVariant));
        form.addEventListener('submit', function (event) {
            if (!radios.some(radio => radio.checked && !radio.disabled)) {
                event.preventDefault();
                if (messageEl) {
                    messageEl.hidden = false;
                }
            }
        });
        syncVariant();
    })();
</script>

<?php include("includes/footer.php"); ?>
