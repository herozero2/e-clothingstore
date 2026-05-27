<?php

function ensure_mail_queue_table(mysqli $con): void
{
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS mail_queue (
        id INT NOT NULL AUTO_INCREMENT,
        to_email VARCHAR(255) NOT NULL,
        to_name VARCHAR(255) NOT NULL DEFAULT '',
        subject VARCHAR(255) NOT NULL,
        body LONGTEXT NOT NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        attempts INT NOT NULL DEFAULT 0,
        last_error TEXT DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        sent_at DATETIME DEFAULT NULL,
        PRIMARY KEY (id),
        KEY status_created_at (status, created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

function queue_mail(mysqli $con, string $toEmail, string $toName, string $subject, string $body): void
{
    ensure_mail_queue_table($con);

    $stmt = mysqli_prepare($con, "
        INSERT INTO mail_queue (to_email, to_name, subject, body)
        VALUES (?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, 'ssss', $toEmail, $toName, $subject, $body);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}
