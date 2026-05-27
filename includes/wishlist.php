<?php
function ensure_wishlist_table(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS wishlist (
            id INT NOT NULL AUTO_INCREMENT,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_wishlist_item (user_id, product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
}

function get_user_wishlist_ids(mysqli $con, int $userId): array
{
    ensure_wishlist_table($con);
    $ids = [];
    $result = mysqli_query($con, "SELECT product_id FROM wishlist WHERE user_id = $userId");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $ids[] = (int) $row['product_id'];
    }

    return $ids;
}
