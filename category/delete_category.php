<?php
require_once __DIR__ . '/../includes/admin_auth.php';
$con = require_admin(null, '../Admin/Adminlogin.php');

// Get category ID from query param
$id = (int) ($_GET['id'] ?? 0);

if ($id <= 0) {
    // Redirect if no ID provided
    header("Location: view_category.php");
    exit;
}

$stmt = mysqli_prepare($con, "UPDATE category SET deleted_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);

if (mysqli_stmt_execute($stmt)) {
    mysqli_stmt_close($stmt);
    // Redirect back to category list after deletion
    header("Location: view_category.php");
    exit;
} else {
    echo "Error deleting category: " . mysqli_error($con);
}
?>
