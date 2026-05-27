<?php
require_once __DIR__ . '/db.php';

function ensure_smtp_settings_table(mysqli $con): void
{
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS smtp_settings (
        id INT NOT NULL AUTO_INCREMENT,
        host VARCHAR(255) NOT NULL DEFAULT '',
        port INT NOT NULL DEFAULT 587,
        username VARCHAR(255) DEFAULT '',
        password VARCHAR(255) DEFAULT '',
        encryption VARCHAR(20) DEFAULT 'tls',
        from_email VARCHAR(255) NOT NULL DEFAULT 'help@example.com',
        from_name VARCHAR(255) NOT NULL DEFAULT 'E-Clothing Store',
        admin_email VARCHAR(255) NOT NULL DEFAULT 'help@example.com',
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function get_smtp_settings(): array
{
    $defaults = [
        'host' => 'sandbox.smtp.mailtrap.io',
        'port' => 2525,
        'username' => '16419e58453544',
        'password' => '37e11ef18c6f9f',
        'encryption' => '',
        'from_email' => 'help@example.com',
        'from_name' => 'E-Clothing Store',
        'admin_email' => 'help@example.com',
        'is_active' => 1,
    ];

    $con = db_connect();
    ensure_smtp_settings_table($con);

    $result = mysqli_query($con, "SELECT * FROM smtp_settings WHERE is_active = 1 ORDER BY id DESC LIMIT 1");
    if ($result && mysqli_num_rows($result) > 0) {
        return array_merge($defaults, mysqli_fetch_assoc($result));
    }

    return $defaults;
}

function apply_smtp_settings($mail, array $settings): void
{
    $mail->SMTPDebug = 0;
    $mail->isSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Timeout = 10;
    $mail->SMTPKeepAlive = false;
    $mail->Host = $settings['host'];
    $mail->SMTPAuth = !empty($settings['username']);
    $mail->Port = (int) $settings['port'];
    $mail->Username = $settings['username'];
    $mail->Password = $settings['password'];

    if (!empty($settings['encryption'])) {
        $mail->SMTPSecure = $settings['encryption'];
    }

    $mail->setFrom($settings['from_email'], $settings['from_name']);
    $mail->addReplyTo($settings['from_email'], $settings['from_name']);
}
