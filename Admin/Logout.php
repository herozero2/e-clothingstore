<?php
require_once __DIR__ . '/../includes/admin_auth.php';

admin_session_start();
admin_clear_remember_cookie();

$_SESSION = [];
session_destroy();

header("Location: Adminlogin.php");
exit();
?>
