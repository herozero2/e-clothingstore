<?php
include '../includes/header.php';

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product_variants.php';

$con = db_connect();
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}
ensure_product_variants_schema($con);

// Fetch active categories (where deleted_at IS NULL)
$categoryResult = mysqli_query($con, "SELECT id, name FROM category WHERE deleted_at IS NULL");
$categories = [];
if ($categoryResult) {
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categories[] = $row;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
    $upload_dir = "../assets/images/";
    $image = "";
    $error_message = "";

    if (!empty($_FILES['userfile']['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/avif'];

        if (!in_array($_FILES['userfile']['type'], $allowedTypes, true)) {
            $error_message = "Only JPG, PNG, WEBP, and AVIF images are allowed.";
        } elseif ($_FILES['userfile']['error'] !== UPLOAD_ERR_OK) {
            $error_message = "Product image upload failed.";
        } else {
            $image = basename($_FILES['userfile']['name']);
            $upload_file = $upload_dir . $image;
            if (!move_uploaded_file($_FILES['userfile']['tmp_name'], $upload_file)) {
                $error_message = "Could not save the uploaded product image.";
            }
        }
    }

    $name = trim($_POST["name"] ?? '');
    $desc = trim($_POST["description"] ?? '');
    $price = max(0, (float) ($_POST["price"] ?? 0));
    $qty = max(0, (int) ($_POST["quantity"] ?? 0));
    $sku = trim($_POST["sku"] ?? '');
    $c_id = (int) ($_POST["category_id"] ?? 0);

    if ($error_message !== '') {
        echo "<script>alert(" . json_encode($error_message) . ");</script>";
    } elseif ($name === '' || $desc === '' || $sku === '' || $c_id <= 0 || $image === '') {
        echo "<script>alert('Product name, description, SKU, category, and image are required.');</script>";
    } else {
        $stmt = mysqli_prepare($con, "
            INSERT INTO product (name, description, price, sku, quantity, category_id, image)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, 'ssdsiis', $name, $desc, $price, $sku, $qty, $c_id, $image);

        if (mysqli_stmt_execute($stmt)) {
            $productId = mysqli_insert_id($con);
            mysqli_stmt_close($stmt);
            save_product_variants($con, $productId, $_POST);
            echo "<script>window.location.href='view.php';</script>";
        } else {
            $error_message = "Could not add product: " . mysqli_error($con);
            mysqli_stmt_close($stmt);
            echo "<script>alert(" . json_encode($error_message) . ");</script>";
        }
    }
}
?>

<section class="add-product-container">
    <form id="addProductForm" method="POST" enctype="multipart/form-data">
        <div class="product-form-header">
            <div>
                <p class="eyebrow">Catalog management</p>
                <h2><i class="fas fa-plus-circle"></i> Add Product</h2>
                <p>Create a polished product listing with stock, pricing, category, and image details.</p>
            </div>
            <button type="button" class="close-btn" onclick="window.location.href='../Admin/Admindashboard.php'" aria-label="Back to dashboard">&times;</button>
        </div>

        <div class="inline-group">
            <div class="form-group">
                <label><i class="fas fa-tag"></i> Product Name</label>
                <input type="text" name="name" placeholder="e.g. Classic Cotton Shirt" required>
            </div>
            <div class="form-group">
                <label><span class="rs-symbol"><?= htmlspecialchars(currency_symbol($con)) ?></span> Price</label>
                <input type="number" id="price" name="price" step="0.01" min="0" placeholder="2500" required>
            </div>
            <div class="form-group">
                <label><i class="fas fa-boxes"></i> Quantity</label>
                <input type="number" name="quantity" min="0" placeholder="10" required>
            </div>
        </div>

        <div class="inline-group form-section">
            <div class="form-group">
                <label><i class="fas fa-barcode"></i> SKU</label>
                <input type="text" name="sku" placeholder="SKU-001" required>
            </div>

            <div class="form-group">
                <label><i class="fas fa-list"></i> Category</label>
                <select name="category_id" required>
                    <option value="" disabled selected>Select category</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo htmlspecialchars($category['id']); ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group form-section">
            <label><i class="fas fa-align-left"></i> Description</label>
            <textarea name="description" placeholder="Write a clear product description for the product detail page." required></textarea>
        </div>

        <div class="form-group form-section">
            <label><i class="fas fa-image"></i> Upload Image</label>
            <input type="file" name="userfile" accept="image/*" required>
        </div>

        <div class="form-section variant-section">
            <div class="product-form-header compact-header">
                <div>
                    <h3><i class="fas fa-layer-group"></i> Product Variants</h3>
                    <p>Add size, color, or fit options with optional stock and price adjustments.</p>
                </div>
                <button type="button" class="quick-action" id="addVariantRow"><i class="fas fa-plus"></i> Add Variant</button>
            </div>
            <div id="variantRows" class="variant-rows">
                <div class="variant-row">
                    <input type="text" name="variant_key[]" placeholder="Type, e.g. Size">
                    <input type="text" name="variant_value[]" placeholder="Value, e.g. M">
                    <input type="text" name="variant_sku[]" placeholder="Variant SKU">
                    <input type="number" name="variant_price_adjustment[]" step="0.01" placeholder="Price +/-">
                    <input type="number" name="variant_quantity[]" min="0" placeholder="Stock">
                    <button type="button" class="remove-variant" aria-label="Remove variant">&times;</button>
                </div>
            </div>
        </div>

        <div class="button-group">
            <button type="submit" name="submit"><i class="fas fa-upload"></i> Add Product</button>
            <button type="reset" class="cancel-btn"><i class="fas fa-eraser"></i> Clear Form</button>
        </div>
    </form>
</section>

<script>
    document.getElementById('addProductForm').addEventListener('submit', function (e) {
        const priceInput = document.getElementById('price');
        if (parseFloat(priceInput.value) < 0) {
            alert("Price cannot be negative!");
            priceInput.focus();
            e.preventDefault();
        }
    });

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
