<?php
require_once __DIR__ . '/db.php';

function ensure_store_pages_table(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS store_pages (
            id INT NOT NULL AUTO_INCREMENT,
            slug VARCHAR(120) NOT NULL,
            title VARCHAR(180) NOT NULL,
            content MEDIUMTEXT NOT NULL,
            footer_group VARCHAR(50) NOT NULL DEFAULT 'shop',
            sort_order INT NOT NULL DEFAULT 0,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_store_page_slug (slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $columns = [];
    $result = mysqli_query($con, "SHOW COLUMNS FROM store_pages");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    $missing = [
        'slug' => "ADD slug varchar(120) NOT NULL DEFAULT '' AFTER id",
        'title' => "ADD title varchar(180) NOT NULL DEFAULT '' AFTER slug",
        'content' => "ADD content mediumtext AFTER title",
        'footer_group' => "ADD footer_group varchar(50) NOT NULL DEFAULT 'shop' AFTER content",
        'sort_order' => "ADD sort_order int NOT NULL DEFAULT 0 AFTER footer_group",
        'is_active' => "ADD is_active tinyint(1) NOT NULL DEFAULT 1 AFTER sort_order",
        'updated_at' => "ADD updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER is_active",
    ];

    foreach ($missing as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            mysqli_query($con, "ALTER TABLE store_pages $definition");
        }
    }

    $countResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM store_pages");
    $count = (int) (mysqli_fetch_assoc($countResult)['total'] ?? 0);
    if ($count > 0) {
        return;
    }

    $defaults = [
        ['about-us', 'About Us', 'E-Clothing Store brings quality fashion, reliable service, and practical everyday style to customers across Nepal.', 'shop', 1],
        ['privacy-policy', 'Privacy Policy', 'We collect only the information needed to process orders, support customers, and improve the shopping experience.', 'shop', 2],
        ['terms-condition', 'Terms & Condition', 'By using this store, customers agree to provide accurate order details and follow our purchase, delivery, and return policies.', 'shop', 3],
        ['return-policy', 'Return Policy', 'Eligible products can be requested for return within 30 days when unused, undamaged, and returned with original packaging.', 'shop', 4],
        ['faqs-help', 'FAQs & Help', 'For order, delivery, or product questions, contact help@example.com or call our support number during business hours.', 'shop', 5],
    ];

    $stmt = mysqli_prepare($con, "INSERT INTO store_pages (slug, title, content, footer_group, sort_order) VALUES (?, ?, ?, ?, ?)");
    foreach ($defaults as $page) {
        mysqli_stmt_bind_param($stmt, 'ssssi', $page[0], $page[1], $page[2], $page[3], $page[4]);
        mysqli_stmt_execute($stmt);
    }
    mysqli_stmt_close($stmt);
}

function get_footer_pages(mysqli $con): array
{
    ensure_store_pages_table($con);
    $pages = [];
    $result = mysqli_query($con, "SELECT slug, title FROM store_pages WHERE is_active = 1 ORDER BY sort_order ASC, title ASC");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $pages[] = $row;
    }
    return $pages;
}

function get_store_page(mysqli $con, string $slug): ?array
{
    ensure_store_pages_table($con);
    $stmt = mysqli_prepare($con, "SELECT * FROM store_pages WHERE slug = ? AND is_active = 1 LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $slug);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $page = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $page ?: null;
}
