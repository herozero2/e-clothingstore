<?php
require_once __DIR__ . '/db.php';

function ensure_home_sliders_table(mysqli $con): void
{
    mysqli_query($con, "CREATE TABLE IF NOT EXISTS home_sliders (
        id INT NOT NULL AUTO_INCREMENT,
        image VARCHAR(255) NOT NULL,
        link_url VARCHAR(255) NOT NULL DEFAULT 'user/our_shop.php',
        sort_order INT NOT NULL DEFAULT 0,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $countResult = mysqli_query($con, "SELECT COUNT(*) AS total FROM home_sliders");
    $total = $countResult ? (int) (mysqli_fetch_assoc($countResult)['total'] ?? 0) : 0;

    if ($total === 0) {
        $defaults = [
            ['blackcoat.webp', 'user/our_shop.php', 1],
            ['Red-WeddingBridalGown.avif', 'user/our_shop.php', 2],
            ['boykidsdress.jpg', 'user/our_shop.php', 3],
        ];

        $stmt = mysqli_prepare($con, "INSERT INTO home_sliders (image, link_url, sort_order, is_active) VALUES (?, ?, ?, 1)");
        foreach ($defaults as $slide) {
            mysqli_stmt_bind_param($stmt, "ssi", $slide[0], $slide[1], $slide[2]);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }
}

function get_home_sliders(mysqli $con): array
{
    ensure_home_sliders_table($con);
    $slides = [];
    $result = mysqli_query($con, "SELECT * FROM home_sliders WHERE is_active = 1 ORDER BY sort_order ASC, id ASC");

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $slides[] = $row;
        }
    }

    return $slides;
}
