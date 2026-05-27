<?php
session_start();

$_SESSION = [];

session_destroy();

if (isset($_COOKIE['email'])) {

    setcookie("email", "", time() - 3600, "/");
}

header("Location: ../index.php");
exit();
?>
