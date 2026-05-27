<?php
include '../includes/header.php';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product_variants.php';

$con = db_connect();
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}
ensure_product_variants_schema($con);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: view.php");
    exit;
}

$query = "SELECT * FROM product WHERE id = $id AND deleted_at IS NULL";
$result = mysqli_query($con, $query);
$product = $result ? mysqli_fetch_assoc($result) : null;

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

$categories = [];
$catResult = mysqli_query($con, "SELECT * FROM category WHERE deleted_at IS NULL");
while ($cat = mysqli_fetch_assoc($catResult)) {
    $categories[] = $cat;
}

$upload_dir = "../assets/images/";
$image = $product['image']; // current image filename
$variants = get_product_variants($con, $id);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = $_POST['name'];
    $desc = str_replace("'", "", $_POST['description']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $sku = $_POST['sku'];
    $category_id = $_POST['category_id'];

    if (!empty($_FILES['userfile']['name'])) {
        $image = basename($_FILES['userfile']['name']);
        $upload_file = $upload_dir . $image;
        move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_file);
    }

    // Update query
    $sql_update = "UPDATE product SET 
        name = '$name', 
        description = '$desc', 
        image = '$image', 
        price = $price, 
        quantity = $quantity, 
        sku = '$sku', 
        category_id = $category_id
        WHERE id = $id";

    $res_update = mysqli_query($con, $sql_update);

    if ($res_update) {
        save_product_variants($con, $id, $_POST);
        header("Location: view.php");
        exit;
    } else {
        echo "<p style='color:red;'>Error updating product: " . mysqli_error($con) . "</p>";
    }
}
?>

<section class="add-product-container">
    <form id="addProductForm" method="POST" enctype="multipart/form-data" novalidate>
        <div class="product-form-header">
            <div>
                <p class="eyebrow">Catalog management</p>
                <h2><i class="fas fa-edit"></i> Edit Product</h2>
                <p>Update stock, pricing, category, image, and detail information.</p>
            </div>
            <button type="button" class="close-btn" onclick="window.location.href='view.php'" aria-label="Back to products">&times;</button>
        </div>

        <div class="inline-group">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Product Name</label>
                <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>
            <div class="form-group">
                <label><span class="rs-symbol"><?= htmlspecialchars(currency_symbol($con)) ?></span> Price</label>
                <input type="number" name="price" step="0.01" min="0" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-boxes"></i> Quantity</label>
                <input type="number" name="quantity" min="0" value="<?= htmlspecialchars($product['quantity']) ?>" required>
            </div>
        </div>

        <div class="inline-group form-section">
            <div class="form-group">
                <label><i class="fas fa-barcode"></i> SKU</label>
                <input type="text" name="sku" value="<?= htmlspecialchars($product['sku']) ?>" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-list"></i> Category</label>
                <select name="category_id" required>
                    <option value="" disabled>Select category</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $product['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group form-section">
            <label><i class="fas fa-align-left"></i> Description</label>
            <textarea name="description" required><?= htmlspecialchars($product['description']) ?></textarea>
        </div>

        <div class="form-group form-section">
            <label for="userfile"><i class="fas fa-upload"></i> Product Image</label>
            <input type="file" name="userfile" id="userfile" accept="image/*">
            <?php if (!empty($image) && file_exists($upload_dir . $image)) : ?>
                <img src="<?= $upload_dir . htmlspecialchars($image) ?>" alt="Product Image" style="max-width:180px; margin-top:10px;">
            <?php endif; ?>
        </div>

        <div class="form-section variant-section">
            <div class="product-form-header compact-header">
                <div>
                    <h3><i class="fas fa-layer-group"></i> Product Variants</h3>
                    <p>Keep size, color, fit, stock, and price adjustments updated.</p>
                </div>
                <button type="button" class="quick-action" id="addVariantRow"><i class="fas fa-plus"></i> Add Variant</button>
            </div>
            <div id="variantRows" class="variant-rows">
                <?php $variantRows = !empty($variants) ? $variants : [['variation_key' => '', 'variation_value' => '', 'variant_sku' => '', 'price_adjustment' => '', 'quantity' => '']]; ?>
                <?php foreach ($variantRows as $variant): ?>
                    <div class="variant-row">
                        <input type="text" name="variant_key[]" placeholder="Type, e.g. Size" value="<?= htmlspecialchars($variant['variation_key'] ?? '') ?>">
                        <input type="text" name="variant_value[]" placeholder="Value, e.g. M" value="<?= htmlspecialchars($variant['variation_value'] ?? '') ?>">
                        <input type="text" name="variant_sku[]" placeholder="Variant SKU" value="<?= htmlspecialchars($variant['variant_sku'] ?? '') ?>">
                        <input type="number" name="variant_price_adjustment[]" step="0.01" placeholder="Price +/-" value="<?= htmlspecialchars((string) ($variant['price_adjustment'] ?? '')) ?>">
                        <input type="number" name="variant_quantity[]" min="0" placeholder="Stock" value="<?= htmlspecialchars((string) ($variant['quantity'] ?? '')) ?>">
                        <button type="button" class="remove-variant" aria-label="Remove variant">&times;</button>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" name="submit"><i class="fas fa-save"></i> Update Product</button>
            <button type="button" class="cancel-btn" onclick="window.location.href='view.php'"><i class="fas fa-times"></i> Cancel</button>
        </div>
    </form>
</section>

<script>
    document.getElementById('addVariantRow').addEventListener('click', function () {
        const rows = document.getElementById('variantRows');
        const row = rows.querySelector('.variant-row').cloneNode(true);
        row.querySelectorAll('input').forEach(input => input.value = '');
        rows.appendChild(row);
    });

    document.getElementById('variantRows').addEventListener('click', function (event) {
        if (!event.target.classList.contains('remove-variant')) return;
        const rows = document.querySelectorAll('.variant-row');
        if (rows.length === 1) {
            event.target.closest('.variant-row').querySelectorAll('input').forEach(input => input.value = '');
            return;
        }
        event.target.closest('.variant-row').remove();
    });
</script>

<?php include '../includes/footer.php'; ?>
