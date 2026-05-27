<?php
include '../includes/header.php';
require_once __DIR__ . '/../includes/db.php';
$con = db_connect();
if (!$con) die("Connection failed: " . mysqli_connect_error());

// Get category ID
$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: view_category.php");
    exit;
}

// Fetch category details
$query = mysqli_query($con, "SELECT * FROM category WHERE id = '$id' AND deleted_at IS NULL");
$category = mysqli_fetch_assoc($query);

if (!$category) {
    echo "Category not found.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
    $name = mysqli_real_escape_string($con, $_POST['name']);
    $description = mysqli_real_escape_string($con, $_POST['description']);

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $update_sql = "UPDATE category SET name = '$name', description = '$description' WHERE id = '$id'";
        if (mysqli_query($con, $update_sql)) {
            header("Location: view_category.php");
            exit;
        } else {
            $error = "Error updating category: " . mysqli_error($con);
        }
    }
}
?>

<section class="edit-category-container">
    <form id="editCategoryForm" method="POST">
        <!-- Close button -->
        <button type="button" class="close-btn" onclick="window.location.href='view_category.php'">&times;</button>

        <h2><i class="fas fa-edit"></i> Edit Category</h2>

        <?php if (!empty($error)) : ?>
            <p style="color: red; text-align: center;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <div class="form-group" style="margin-top: 15px;">
            <input type="text" name="name" placeholder=" " value="<?= htmlspecialchars($category['name']) ?>" required>
            <label><i class="fas fa-tag"></i> Category Name</label>
        </div>

        <div class="form-group" style="margin-top: 15px;">
            <textarea name="description" placeholder=" " rows="5"><?= htmlspecialchars($category['description']) ?></textarea>
            <label><i class="fas fa-align-left"></i> Description</label>
        </div>

        <div class="button-group" style="margin-top: 25px;">
            <button type="submit" name="submit"><i class="fas fa-save"></i> Update Category</button>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>
