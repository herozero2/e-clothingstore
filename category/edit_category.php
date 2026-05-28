<?php
require_once __DIR__ . '/../includes/admin_auth.php';
require_once __DIR__ . '/../includes/db.php';
$con = require_admin(null, '../Admin/Adminlogin.php');

// Get category ID
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: view_category.php");
    exit;
}

// Fetch category details
$stmt = mysqli_prepare($con, "SELECT * FROM category WHERE id = ? AND deleted_at IS NULL LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$query = mysqli_stmt_get_result($stmt);
$category = $query ? mysqli_fetch_assoc($query) : null;
mysqli_stmt_close($stmt);

if (!$category) {
    header("Location: view_category.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["submit"])) {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($name)) {
        $error = "Category name is required.";
    } else {
        $stmt = mysqli_prepare($con, "UPDATE category SET name = ?, description = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $description, $id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: view_category.php");
            exit;
        } else {
            $error = "Error updating category: " . mysqli_error($con);
            mysqli_stmt_close($stmt);
        }
    }
}

include '../includes/header.php';
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
