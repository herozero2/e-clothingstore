<?php
require_once __DIR__ . '/db.php';

function ensure_store_settings_schema(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS store_settings (
            id INT NOT NULL AUTO_INCREMENT,
            store_name VARCHAR(255) NOT NULL DEFAULT 'E-Clothing Store',
            store_logo VARCHAR(255) DEFAULT NULL,
            contact_number VARCHAR(30) NOT NULL DEFAULT '+9779806478012',
            store_email VARCHAR(255) NOT NULL DEFAULT 'help@example.com',
            store_address TEXT,
            store_information TEXT,
            established_date DATE DEFAULT NULL,
            currency_code VARCHAR(10) NOT NULL DEFAULT 'NPR',
            currency_symbol VARCHAR(20) NOT NULL DEFAULT 'Rs',
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $columns = [];
    $result = mysqli_query($con, "SHOW COLUMNS FROM store_settings");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    $missing = [
        'store_name' => "ADD store_name varchar(255) NOT NULL DEFAULT 'E-Clothing Store' AFTER id",
        'store_logo' => "ADD store_logo varchar(255) DEFAULT NULL AFTER store_name",
        'contact_number' => "ADD contact_number varchar(30) NOT NULL DEFAULT '+9779806478012' AFTER store_logo",
        'store_email' => "ADD store_email varchar(255) NOT NULL DEFAULT 'help@example.com' AFTER contact_number",
        'store_address' => "ADD store_address text AFTER store_email",
        'store_information' => "ADD store_information text AFTER store_address",
        'established_date' => "ADD established_date date DEFAULT NULL AFTER store_information",
        'currency_code' => "ADD currency_code varchar(10) NOT NULL DEFAULT 'NPR' AFTER established_date",
        'currency_symbol' => "ADD currency_symbol varchar(20) NOT NULL DEFAULT 'Rs' AFTER currency_code",
    ];

    foreach ($missing as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            mysqli_query($con, "ALTER TABLE store_settings $definition");
        }
    }

    $countResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM store_settings");
    $count = $countResult ? (int) (mysqli_fetch_assoc($countResult)['total'] ?? 0) : 0;
    if ($count === 0) {
        mysqli_query($con, "
            INSERT INTO store_settings
                (store_name, store_logo, contact_number, store_email, store_address, store_information, established_date, currency_code, currency_symbol)
            VALUES
                ('E-Clothing Store', 'groupdiscuss.png', '+9779806478012', 'help@example.com', 'Dhangadhi, Kailali Nepal',
                'Quality fashion, fast service, and reliable Cash on Delivery across Nepal.', '2026-07-01', 'NPR', 'Rs')
        ");
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
