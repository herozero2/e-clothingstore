<?php

function ensure_order_contact_schema(mysqli $con): void
{
    $columns = [];
    $types = [];
    $result = mysqli_query($con, "SHOW COLUMNS FROM shipping");
    while ($result && $row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
        $types[$row['Field']] = strtolower($row['Type']);
    }

    if (isset($types['billing_address']) && !str_contains($types['billing_address'], 'text')) {
        mysqli_query($con, "ALTER TABLE shipping MODIFY billing_address TEXT NOT NULL");
    }
    if (isset($types['shipping_address']) && !str_contains($types['shipping_address'], 'text')) {
        mysqli_query($con, "ALTER TABLE shipping MODIFY shipping_address TEXT NOT NULL");
    }
    if (!in_array('mobile', $columns, true)) {
        mysqli_query($con, "ALTER TABLE shipping ADD mobile VARCHAR(30) DEFAULT NULL AFTER shipping_address");
    }
    if (!in_array('email', $columns, true)) {
        mysqli_query($con, "ALTER TABLE shipping ADD email VARCHAR(255) DEFAULT NULL AFTER mobile");
    }
    if (!in_array('shipping_lat', $columns, true)) {
        mysqli_query($con, "ALTER TABLE shipping ADD shipping_lat DECIMAL(10,6) DEFAULT NULL AFTER email");
    }
    if (!in_array('shipping_lng', $columns, true)) {
        mysqli_query($con, "ALTER TABLE shipping ADD shipping_lng DECIMAL(10,6) DEFAULT NULL AFTER shipping_lat");
    }
    if (!in_array('shipping_location_details', $columns, true)) {
        mysqli_query($con, "ALTER TABLE shipping ADD shipping_location_details TEXT DEFAULT NULL AFTER shipping_lng");
    }

    mysqli_query($con, "
        UPDATE shipping s
        JOIN orders o ON o.id = s.order_id
        JOIN users u ON u.id = o.user_id
        SET s.email = u.email
        WHERE (s.email IS NULL OR s.email = '')
          AND u.email IS NOT NULL
          AND u.email <> ''
    ");

    backfill_shipping_contacts_from_mail_queue($con);
}

function backfill_shipping_contacts_from_mail_queue(mysqli $con): void
{
    $tableCheck = mysqli_query($con, "SHOW TABLES LIKE 'mail_queue'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        return;
    }

    $result = mysqli_query($con, "
        SELECT s.id, mq.body
        FROM shipping s
        JOIN mail_queue mq ON mq.subject LIKE CONCAT('%#', s.order_id)
        WHERE (s.mobile IS NULL OR s.mobile = '' OR s.email IS NULL OR s.email = '')
          AND (mq.body LIKE '%<strong>Mobile:</strong>%' OR mq.body LIKE '%<strong>Email:</strong>%')
        ORDER BY mq.id ASC
    ");

    if (!$result) {
        return;
    }

    $stmt = mysqli_prepare($con, "
        UPDATE shipping
        SET mobile = IF((mobile IS NULL OR mobile = ''), ?, mobile),
            email = IF((email IS NULL OR email = ''), ?, email)
        WHERE id = ?
    ");

    while ($row = mysqli_fetch_assoc($result)) {
        $mobile = '';
        $email = '';
        $body = (string) ($row['body'] ?? '');

        if (preg_match('/<strong>Mobile:<\/strong>\s*([^<\r\n]+)/i', $body, $matches)) {
            $mobile = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8'));
        }
        if (preg_match('/<strong>Email:<\/strong>\s*([^<\r\n]+)/i', $body, $matches)) {
            $email = trim(html_entity_decode(strip_tags($matches[1]), ENT_QUOTES, 'UTF-8'));
        }

        if ($mobile === '' && $email === '') {
            continue;
        }

        $shippingId = (int) $row['id'];
        mysqli_stmt_bind_param($stmt, 'ssi', $mobile, $email, $shippingId);
        mysqli_stmt_execute($stmt);
    }

    mysqli_stmt_close($stmt);
}

function compact_order_address(?string $address, int $limit = 90): string
{
    $address = trim((string) $address);
    if ($address === '') {
        return 'Not provided';
    }

    if (strlen($address) <= $limit) {
        return $address;
    }

    return substr($address, 0, $limit - 3) . '...';
}
