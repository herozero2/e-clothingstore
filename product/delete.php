<?php
require_once __DIR__ . '/../includes/admin_auth.php';
$con = require_admin(null, '../Admin/Adminlogin.php');

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    header("Location: view.php");
    exit;
}

$stmt = mysqli_prepare($con, "UPDATE product SET deleted_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

$variantStmt = mysqli_prepare($con, "UPDATE productdetail SET deleted_at = NOW() WHERE product_id = ?");
mysqli_stmt_bind_param($variantStmt, 'i', $id);
mysqli_stmt_execute($variantStmt);
mysqli_stmt_close($variantStmt);

header("Location: view.php");
exit;
?>
