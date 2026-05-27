<?php
require_once __DIR__ . '/../includes/admin_auth.php';
$con = require_admin(null, 'Adminlogin.php');

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header("Location: Adminorders.php");
    exit;
}

$stmt = mysqli_prepare($con, "UPDATE orders SET deleted_at = NOW() WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $id);
if (!mysqli_stmt_execute($stmt)) {
    die("Error deleting record: " . mysqli_error($con));
}
mysqli_stmt_close($stmt);

header("Location: Adminorders.php");
exit;
?>
