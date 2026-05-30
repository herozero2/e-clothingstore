<?php
/**
 * Loads the small PHPMailer subset required by contact and order emails.
 *
 * The full Composer vendor tree is intentionally not required at runtime so
 * downloaded GitHub copies work without running composer install first.
 */
function load_phpmailer(): void
{
    if (class_exists(\PHPMailer\PHPMailer\PHPMailer::class)) {
        return;
    }

    $basePath = __DIR__ . '/../user/vendor/phpmailer/phpmailer/src';
    $requiredFiles = [
        $basePath . '/Exception.php',
        $basePath . '/PHPMailer.php',
        $basePath . '/SMTP.php',
    ];

    foreach ($requiredFiles as $file) {
        if (!is_file($file)) {
            throw new RuntimeException('Missing PHPMailer runtime file: ' . $file);
        }
        require_once $file;
    }
}

load_phpmailer();
