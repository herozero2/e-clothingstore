<?php
function ensure_product_variants_schema(mysqli $con): void
{
    mysqli_query($con, "
        CREATE TABLE IF NOT EXISTS productdetail (
            id INT NOT NULL AUTO_INCREMENT,
            product_id INT DEFAULT NULL,
            variation_key VARCHAR(100) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            deleted_at DATETIME DEFAULT NULL,
            PRIMARY KEY (id),
            KEY product_id (product_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $columns = [];
    $result = mysqli_query($con, "SHOW COLUMNS FROM productdetail");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    $missing = [
        'variation_value' => "ADD variation_value varchar(100) DEFAULT NULL AFTER variation_key",
        'variant_sku' => "ADD variant_sku varchar(120) DEFAULT NULL AFTER variation_value",
        'price_adjustment' => "ADD price_adjustment decimal(10,2) NOT NULL DEFAULT 0.00 AFTER variant_sku",
        'quantity' => "ADD quantity int NOT NULL DEFAULT 0 AFTER price_adjustment",
    ];

    foreach ($missing as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            mysqli_query($con, "ALTER TABLE productdetail $definition");
        }
    }
}

function save_product_variants(mysqli $con, int $productId, array $post): void
{
    ensure_product_variants_schema($con);
    mysqli_query($con, "UPDATE productdetail SET deleted_at = NOW() WHERE product_id = $productId");

    $keys = $post['variant_key'] ?? [];
    $values = $post['variant_value'] ?? [];
    $skus = $post['variant_sku'] ?? [];
    $priceAdjustments = $post['variant_price_adjustment'] ?? [];
    $quantities = $post['variant_quantity'] ?? [];

    $stmt = mysqli_prepare($con, "
        INSERT INTO productdetail (product_id, variation_key, variation_value, variant_sku, price_adjustment, quantity)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $saved = 0;
    foreach ($keys as $index => $key) {
        $key = trim((string) $key);
        $value = trim((string) ($values[$index] ?? ''));
        if ($key === '' || $value === '') {
            continue;
        }

        $sku = trim((string) ($skus[$index] ?? ''));
        $adjustment = (float) ($priceAdjustments[$index] ?? 0);
        $quantity = max(0, (int) ($quantities[$index] ?? 0));
        mysqli_stmt_bind_param($stmt, 'isssdi', $productId, $key, $value, $sku, $adjustment, $quantity);
        mysqli_stmt_execute($stmt);
        $saved++;
    }

    if ($saved === 0) {
        $productResult = mysqli_query($con, "SELECT sku, quantity FROM product WHERE id = $productId LIMIT 1");
        $product = $productResult ? mysqli_fetch_assoc($productResult) : [];
        $key = 'Fit';
        $value = 'Free Size';
        $sku = trim((string) ($product['sku'] ?? '')) ?: 'PR-' . str_pad((string) $productId, 4, '0', STR_PAD_LEFT);
        $variantSku = $sku . '-FS';
        $adjustment = 0.0;
        $quantity = max(0, (int) ($product['quantity'] ?? 0));
        mysqli_stmt_bind_param($stmt, 'isssdi', $productId, $key, $value, $variantSku, $adjustment, $quantity);
        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
}

function get_product_variants(mysqli $con, int $productId): array
{
    ensure_product_variants_schema($con);
    $variants = [];
    $result = mysqli_query($con, "SELECT * FROM productdetail WHERE product_id = $productId AND deleted_at IS NULL ORDER BY variation_key ASC, variation_value ASC");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $variants[] = $row;
    }
    return $variants;
}

function get_product_variant(mysqli $con, int $productId, int $variantId): ?array
{
    ensure_product_variants_schema($con);
    $stmt = mysqli_prepare($con, "
        SELECT *
        FROM productdetail
        WHERE id = ? AND product_id = ? AND deleted_at IS NULL
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'ii', $variantId, $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $variant = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $variant ?: null;
}

function first_available_product_variant(mysqli $con, int $productId): ?array
{
    ensure_product_variants_schema($con);
    $stmt = mysqli_prepare($con, "
        SELECT *
        FROM productdetail
        WHERE product_id = ? AND deleted_at IS NULL AND quantity > 0
        ORDER BY variation_key ASC, variation_value ASC, id ASC
        LIMIT 1
    ");
    mysqli_stmt_bind_param($stmt, 'i', $productId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $variant = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $variant ?: null;
}

function format_product_variant_label(?array $variant): string
{
    if (!$variant) {
        return '';
    }

    $key = trim((string) ($variant['variation_key'] ?? 'Option'));
    $value = trim((string) ($variant['variation_value'] ?? ''));

    return trim($key . ': ' . $value);
}

function ensure_orderdetail_variant_schema(mysqli $con): void
{
    $columns = [];
    $result = mysqli_query($con, "SHOW COLUMNS FROM orderdetail");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
    }

    $missing = [
        'variant_id' => "ADD variant_id INT DEFAULT NULL AFTER product_id",
        'variant_label' => "ADD variant_label VARCHAR(255) DEFAULT NULL AFTER variant_id",
        'variant_sku' => "ADD variant_sku VARCHAR(120) DEFAULT NULL AFTER variant_label",
    ];

    foreach ($missing as $column => $definition) {
        if (!in_array($column, $columns, true)) {
            mysqli_query($con, "ALTER TABLE orderdetail $definition");
        }
    }
}
