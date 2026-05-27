<?php
require_once __DIR__ . '/../settings.php';

mysqli_report(MYSQLI_REPORT_OFF);

function db_connect(): mysqli
{
    $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

    if (!$connection) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    mysqli_set_charset($connection, "utf8mb4");

    return $connection;
}
