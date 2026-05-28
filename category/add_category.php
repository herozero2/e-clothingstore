<?php
include '../includes/header.php';

require_once __DIR__ . '/../includes/db.php';

$con = db_connect();
if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $name = trim($_POST['name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($name === '') {
        echo "<script>alert('Category name is required.');</script>";
    } else {
        $stmt = mysqli_prepare($con, "INSERT INTO category (name, description) VALUES (?, ?)");
        mysqli_stmt_bind_param($stmt, 'ss', $name, $desc);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo "<script> window.location.href='view_category.php';</script>";
            exit;
        } else {
            echo "<script>alert('Error: " . mysqli_error($con) . "');</script>";
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<section class="add-category-container">
    <form id="addCategoryForm" method="POST" novalidate>
        <button type="button" class="close-btn" onclick="window.location.href='../admin/Admindashboard.php'">&times;</button>

        <h2><i class="fas fa-plus-circle"></i> Add Category</h2>

        <div class="form-group">
            <input type="text" name="name" placeholder=" " required>
            <label><i class="fas fa-tags"></i> Category Name</label>
        </div>

        <div class="form-group">
            <textarea name="description" placeholder=" "></textarea>
            <label><i class="fas fa-align-left"></i> Description</label>
        </div>

        <div class="button-group">
            <button type="submit" name="submit"><i class="fas fa-upload"></i> Add Category</button>
            <button type="reset" class="cancel-btn"><i class="fas fa-eraser"></i> Clear Form</button>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>
