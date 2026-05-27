<?php
require_once __DIR__ . '/../includes/admin_auth.php';
$con = require_admin(null, 'Adminlogin.php');

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = mysqli_prepare($con, "UPDATE product_ratings SET deleted_at = NOW() WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        header('Location: product_rating_review.php');
    } else {
        echo "Error deleting rating.";
    }
} else {
    header('Location: product_rating_review.php');
}
?>
