<?php
require_once __DIR__ . '/db.php';

function ensure_store_settings_schema(mysqli $con): void
{
    $columns = [];
    $result = mysqli_query($con, "SHOW COLUMNS FROM store_settings");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    if (!in_array('currency_code', $columns, true)) {
        mysqli_query($con, "ALTER TABLE store_settings ADD currency_code varchar(10) NOT NULL DEFAULT 'NPR' AFTER established_date");
    }

    if (!in_array('currency_symbol', $columns, true)) {
        mysqli_query($con, "ALTER TABLE store_settings ADD currency_symbol varchar(20) NOT NULL DEFAULT 'Rs' AFTER currency_code");
    }
}

function get_store_settings(mysqli $con): array
{
    ensure_store_settings_schema($con);
    $result = mysqli_query($con, "SELECT * FROM store_settings ORDER BY id DESC LIMIT 1");
    $settings = $result ? (mysqli_fetch_assoc($result) ?: []) : [];

    return array_merge([
        'store_name' => 'E-Clothing Store',
        'store_logo' => null,
        'contact_number' => '+9779806478012',
        'store_email' => 'help@example.com',
        'store_address' => 'Dhangadhi, Kailali Nepal',
        'store_information' => 'Quality fashion, fast service, and reliable Cash on Delivery across Nepal.',
        'currency_code' => 'NPR',
        'currency_symbol' => 'Rs',
    ], $settings);
}

function currency_symbol(?mysqli $con = null): string
{
    static $symbol = null;

    if ($symbol !== null) {
        return $symbol;
    }

    $localConnection = false;
    if (!$con) {
        $con = db_connect();
        $localConnection = true;
    }

    $settings = get_store_settings($con);
    $symbol = trim($settings['currency_symbol'] ?? 'Rs') ?: 'Rs';

    if ($localConnection) {
        mysqli_close($con);
    }

    return $symbol;
}

function money(float $amount, ?mysqli $con = null): string
{
    return htmlspecialchars(currency_symbol($con)) . ' ' . number_format($amount, 2);
}
