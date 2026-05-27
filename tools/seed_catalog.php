<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/product_variants.php';
require_once __DIR__ . '/../includes/pages.php';
require_once __DIR__ . '/../includes/store.php';

$con = db_connect();
ensure_product_variants_schema($con);
ensure_store_pages_table($con);
ensure_store_settings_schema($con);

$categories = [];
$categoryResult = mysqli_query($con, "SELECT id, name FROM category WHERE deleted_at IS NULL");
while ($row = mysqli_fetch_assoc($categoryResult)) {
    $categories[$row['name']] = (int) $row['id'];
}

$images = [
    'Men' => ['blackTshirt.jpg', 'blackcoat.webp', 'blazersformen.jpeg', 'classic black tshirt.jpg', 'classic white tshirt.jpg', 'lea-ochel-nsRBbE6-YLs-unsplash.jpg', 'pants.jpg', 'ryan-plomp-jvoZ-Aux9aw-unsplash.jpg', 'shoes101.jpg', 'sunglass.jpg', 'bag101.jpg', 'juckets101.jpg', 'traditionalgroomdressnepali.jpeg', 'weddingdressformen.avif'],
    'Women' => ['blackdress.webp', 'blackgaun.webp', 'blackweddingdress.webp', 'casualboot.webp', 'culturaldresswomen.jpeg', 'handbag1.jpg', 'heelsboot.jpg', 'highheelsboots.jpg', 'longblazer.jpg', 'modernblazer.jpg', 'Red-WeddingBridalGown.avif', 'weddingclothes.jpg', 'weddingdress.webp', 'womenblazer.jpg', 'bag2.jpg'],
    'Babies' => ['babies combo set.jpg', 'boykidsdress.jpg', 'boysblazerkids.avif', 'dressforlittlegirl.webp', 'dressset.jpg', 'gownforlittlegirls.avif', 'NepaliGunyoSkirtforKids.webp', 'pasnidressforboy.webp', 'Rainbow princess dress.webp', 'sequin fairy wings dress.avif', 'shreekrishna.webp', 'shreeradhe.webp', 'teddybear.jpeg'],
    'Free Sized' => ['cap.jpeg', 'hankie.webp', 'sunglass1.jpg', 'T-shirtblack.jpg', 'traditionalclothes.jpg', 'whitesboot.jpeg', 'shoes.jpg', 'florencia-simonini-yhk8ZidU-K4-unsplash.jpg'],
];

$catalog = [
    ['Men', 'Everyday Cotton Crew T-Shirt', 'Soft breathable cotton crew neck t-shirt for daily layering and weekend wear.', 1290],
    ['Men', 'Slim Fit Oxford Shirt', 'Crisp button-down shirt with a clean slim cut for office and smart casual styling.', 2190],
    ['Men', 'Stretch Denim Jeans', 'Mid-rise stretch denim jeans with a versatile tapered fit and durable stitching.', 2990],
    ['Men', 'Classic Bomber Jacket', 'Lightweight bomber jacket with ribbed trims and practical pockets for cool evenings.', 4590],
    ['Men', 'Wool Blend Overcoat', 'Structured wool blend coat designed for polished winter outfits.', 6990],
    ['Men', 'Leather Biker Jacket', 'Faux leather biker jacket with zip details and a modern fitted profile.', 5990],
    ['Men', 'Formal Black Blazer', 'Sharp black blazer tailored for meetings, ceremonies, and evening events.', 5490],
    ['Men', 'Nepali Groom Daura Set', 'Traditional groom-ready daura suruwal set with heritage inspired styling.', 9990],
    ['Men', 'Athletic Training Shoes', 'Cushioned lace-up training shoes for daily movement and casual wear.', 3490],
    ['Men', 'Air Cushion Sneakers', 'Street-ready sneakers with a cushioned sole and breathable upper.', 4990],
    ['Men', 'Canvas Weekend Backpack', 'Durable backpack with organized compartments for work, travel, and college.', 2590],
    ['Men', 'Polarized Sport Sunglasses', 'Lightweight wraparound sunglasses with dark lenses for outdoor comfort.', 1890],
    ['Men', 'Stand Collar Winter Coat', 'Warm stand collar coat with a clean profile and easy layering fit.', 5290],
    ['Men', 'Textured Party Blazer', 'Statement blazer with a textured finish for receptions and festive nights.', 6490],
    ['Men', 'Relaxed Linen Shirt', 'Breathable linen blend shirt made for warm days and relaxed styling.', 2490],
    ['Men', 'Cargo Utility Pants', 'Utility pants with roomy pockets, sturdy fabric, and a comfortable straight fit.', 3290],
    ['Men', 'Minimal White T-Shirt', 'Clean white t-shirt with a classic neckline and soft everyday fabric.', 1190],
    ['Men', 'Graphic Black T-Shirt', 'Bold black graphic t-shirt for casual streetwear outfits.', 1490],
    ['Men', 'Wedding Waistcoat Set', 'Elegant waistcoat set made for wedding, formal, and cultural functions.', 7490],
    ['Men', 'Smart Casual Loafers', 'Easy slip-on loafers with a polished finish for work and evening wear.', 3990],
    ['Women', 'Black Evening Dress', 'Elegant black dress with a flattering silhouette for dinner and party styling.', 4990],
    ['Women', 'Sequin Ball Gown', 'Sparkling gown with a graceful fall for receptions and formal celebrations.', 8990],
    ['Women', 'Bridal Tulle Gown', 'Romantic bridal gown with tulle volume and refined detailing.', 12990],
    ['Women', 'Rayon Embroidered Kurti Set', 'Comfortable embroidered kurti set with a festive yet wearable finish.', 3990],
    ['Women', 'Crimson Kurta Sharara Set', 'Vibrant kurta and sharara set designed for family events and festivals.', 5490],
    ['Women', 'Modern White Blazer', 'Clean white blazer with a confident cut for work and occasion wear.', 5290],
    ['Women', 'Longline Black Blazer', 'Longline blazer with sharp lapels and easy layering structure.', 5990],
    ['Women', 'Knee High Fashion Boots', 'Statement knee-high boots with a sturdy heel and sleek finish.', 4290],
    ['Women', 'Retro Ankle Boots', 'Warm retro ankle boots with zipper entry and everyday heel height.', 3790],
    ['Women', 'Red Bridal Gown', 'Rich red bridal gown with a dramatic shape for wedding and engagement events.', 11990],
    ['Women', 'Traditional Tamang Dress', 'Cultural outfit inspired by Tamang heritage with elegant color detail.', 4890],
    ['Women', 'Gunyo Choli Set', 'Traditional coming-of-age outfit with a comfortable festive fit.', 2990],
    ['Women', 'Structured Red Handbag', 'Elegant red handbag with top handle styling and a polished clasp.', 1990],
    ['Women', 'Teal Shoulder Bag', 'Compact teal shoulder bag with a smooth finish for everyday outfits.', 2490],
    ['Women', 'Party Heel Boots', 'High heel boots with party-ready styling and a confident silhouette.', 3990],
    ['Women', 'Printed Wedding Kurta Set', 'Printed kurta set with refined details for ceremonies and family gatherings.', 6590],
    ['Women', 'Soft Knit Cardigan', 'Comfortable layering cardigan with a soft handfeel and relaxed shape.', 3290],
    ['Women', 'A-Line Occasion Dress', 'Graceful A-line dress for office events, dinners, and semi-formal gatherings.', 4490],
    ['Women', 'Everyday Tote Bag', 'Roomy tote bag with a structured body for work essentials and shopping.', 2290],
    ['Women', 'Fashion Sunglasses', 'Modern sunglasses with a flattering frame and UV protective lenses.', 1690],
    ['Babies', 'Little Feminist Combo Set', 'Comfortable baby combo set with playful styling and soft fabrics.', 2490],
    ['Babies', 'Boys Festive Kurta Set', 'Festive kurta set for boys with a charming jacket and comfortable pants.', 3490],
    ['Babies', 'Girls Floral Party Dress', 'Sweet floral party dress for birthdays, photos, and family gatherings.', 2890],
    ['Babies', 'Baby Shirt And Shorts Set', 'Soft shirt and shorts set for warm days and easy movement.', 1690],
    ['Babies', 'Tulle Princess Dress', 'Princess-style tulle dress with bow detail for special occasions.', 3990],
    ['Babies', 'Rainbow Dance Dress', 'Colorful toddler dress designed for comfort and happy movement.', 3190],
    ['Babies', 'Pasni Ceremony Outfit', 'Traditional baby outfit made for pasni and family ceremonies.', 4590],
    ['Babies', 'Kids Blazer Set', 'Smart blazer set for boys with a neat occasion-ready look.', 3790],
    ['Babies', 'Sequin Fairy Dress', 'Sparkly fairy dress with soft layers for celebrations and photos.', 3290],
    ['Babies', 'Radhe Krishna Kids Set', 'Traditional kids set inspired by Radha Krishna festive styling.', 2990],
    ['Babies', 'Soft Teddy Bear Hoodie', 'Cozy teddy hoodie for cool days and playful everyday wear.', 1890],
    ['Babies', 'Girls Birthday Gown', 'Elegant birthday gown with a comfortable fit for long celebrations.', 3490],
    ['Babies', 'Kids Traditional Dress', 'Traditional clothing set for cultural programs and family functions.', 3190],
    ['Babies', 'Baby Winter Boots', 'Soft baby boots with warm lining and easy slip-on comfort.', 1490],
    ['Babies', 'Toddler Cotton T-Shirt', 'Breathable cotton tee for active toddlers and daily play.', 990],
    ['Babies', 'Baby Denim Pants', 'Soft denim pants with a flexible waist and gentle fabric.', 1390],
    ['Babies', 'Kids Party Shoes', 'Comfortable kids party shoes with sturdy soles and neat styling.', 1790],
    ['Babies', 'Baby Festival Combo', 'Festival-ready combo set with soft pieces for easy dressing.', 2690],
    ['Babies', 'Little Princess Hair Set', 'Cute accessory set with matching hair styling pieces for kids.', 790],
    ['Babies', 'Kids Casual Hoodie', 'Warm casual hoodie for school, outings, and daily comfort.', 1990],
    ['Free Sized', 'Classic Baseball Cap', 'Adjustable cap with a clean profile and comfortable daily fit.', 990],
    ['Free Sized', 'Printed Cotton Scarf', 'Soft cotton scarf with versatile styling for casual outfits.', 890],
    ['Free Sized', 'Black Finger Heart T-Shirt', 'Free-sized graphic tee with a relaxed fit and soft cotton feel.', 1490],
    ['Free Sized', 'Traditional Shawl', 'Elegant shawl for cultural outfits, ceremonies, and cool evenings.', 1890],
    ['Free Sized', 'Round Metal Sunglasses', 'Classic round sunglasses with a light frame and tinted lenses.', 1590],
    ['Free Sized', 'Travel Duffel Bag', 'Spacious travel bag with sturdy handles and practical compartments.', 2790],
    ['Free Sized', 'Minimal Canvas Shoes', 'Everyday canvas shoes with a clean profile and flexible sole.', 1990],
    ['Free Sized', 'White Winter Boots', 'Warm white boots with a plush lining and sturdy sole.', 3290],
    ['Free Sized', 'Soft Handkerchief Pack', 'Reusable soft handkerchief pack for daily carry.', 490],
    ['Free Sized', 'Festival Topi And Shawl Set', 'Traditional accessory set suitable for ceremonies and cultural events.', 1690],
    ['Free Sized', 'Everyday Crossbody Bag', 'Compact crossbody bag with adjustable strap and secure pockets.', 2190],
    ['Free Sized', 'Sport Shield Sunglasses', 'Full coverage sport sunglasses with a secure lightweight fit.', 1790],
];

$imageFixes = [
    'weddingdressformen.jpg' => 'weddingdressformen.avif',
    'gownforlittlegirls.jpg' => 'gownforlittlegirls.avif',
];

$imageFixStmt = mysqli_prepare($con, "UPDATE product SET image = ? WHERE image = ?");
foreach ($imageFixes as $oldImage => $newImage) {
    mysqli_stmt_bind_param($imageFixStmt, 'ss', $newImage, $oldImage);
    mysqli_stmt_execute($imageFixStmt);
}
mysqli_stmt_close($imageFixStmt);

$skuFixRows = mysqli_query($con, "
    SELECT p.id, c.name AS category_name
    FROM product p
    LEFT JOIN category c ON c.id = p.category_id
    WHERE p.deleted_at IS NULL AND (p.sku IS NULL OR p.sku = '' OR p.sku = '0')
");
$skuFixStmt = mysqli_prepare($con, "UPDATE product SET sku = ? WHERE id = ?");
$variantSkuFixStmt = mysqli_prepare($con, "
    UPDATE productdetail
    SET variant_sku = CONCAT(?, '-', UPPER(REPLACE(COALESCE(variation_value, variation_key, 'VAR'), ' ', '')))
    WHERE product_id = ? AND (variant_sku IS NULL OR variant_sku = '' OR variant_sku LIKE '0-%')
");
while ($product = mysqli_fetch_assoc($skuFixRows)) {
    $categoryName = $product['category_name'] ?: 'Product';
    $prefix = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $categoryName), 0, 2));
    $prefix = $prefix !== '' ? $prefix : 'PR';
    $productId = (int) $product['id'];
    $sku = $prefix . '-' . str_pad((string) (2000 + $productId), 4, '0', STR_PAD_LEFT);
    mysqli_stmt_bind_param($skuFixStmt, 'si', $sku, $productId);
    mysqli_stmt_execute($skuFixStmt);
    mysqli_stmt_bind_param($variantSkuFixStmt, 'si', $sku, $productId);
    mysqli_stmt_execute($variantSkuFixStmt);
}
mysqli_stmt_close($skuFixStmt);
mysqli_stmt_close($variantSkuFixStmt);

mysqli_query($con, "
    INSERT INTO productdetail (product_id, variation_key, variation_value, variant_sku, price_adjustment, quantity)
    SELECT p.id, 'Fit', 'Free Size', CONCAT(p.sku, '-FS'), 0.00, GREATEST(p.quantity, 0)
    FROM product p
    WHERE p.deleted_at IS NULL
      AND NOT EXISTS (
          SELECT 1
          FROM productdetail pd
          WHERE pd.product_id = p.id
            AND pd.deleted_at IS NULL
      )
");

$currentCount = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM product WHERE deleted_at IS NULL"))['total'] ?? 0);
$targetCount = 110;
$needed = max(0, $targetCount - $currentCount);
$inserted = 0;
$baseDate = new DateTime('2026-05-25 09:20:00');

$productStmt = mysqli_prepare($con, "
    INSERT INTO product (name, description, price, quantity, sku, category_id, image, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");
$variantStmt = mysqli_prepare($con, "
    INSERT INTO productdetail (product_id, variation_key, variation_value, variant_sku, price_adjustment, quantity)
    VALUES (?, ?, ?, ?, ?, ?)
");

for ($i = 0; $inserted < $needed; $i++) {
    $item = $catalog[$i % count($catalog)];
    [$categoryName, $nameBase, $description, $price] = $item;
    $series = intdiv($i, count($catalog)) + 1;
    $name = $series > 1 ? $nameBase . ' ' . $series : $nameBase;

    $existsStmt = mysqli_prepare($con, "SELECT id FROM product WHERE name = ? AND deleted_at IS NULL LIMIT 1");
    mysqli_stmt_bind_param($existsStmt, 's', $name);
    mysqli_stmt_execute($existsStmt);
    $exists = mysqli_stmt_get_result($existsStmt);
    mysqli_stmt_close($existsStmt);
    if ($exists && mysqli_num_rows($exists) > 0) {
        continue;
    }

    $categoryId = $categories[$categoryName] ?? reset($categories);
    $imagePool = $images[$categoryName] ?? $images['Free Sized'];
    $image = $imagePool[$i % count($imagePool)];
    $sku = strtoupper(substr($categoryName, 0, 2)) . '-' . str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT);
    $quantity = 18 + ($i % 33);
    $created = clone $baseDate;
    $created->modify('+' . (($currentCount + $i) * 20) . ' minutes');
    $createdAt = $created->format('Y-m-d H:i:s');

    mysqli_stmt_bind_param($productStmt, 'ssdiisss', $name, $description, $price, $quantity, $sku, $categoryId, $image, $createdAt);
    mysqli_stmt_execute($productStmt);
    $productId = mysqli_insert_id($con);
    if ($productId <= 0) {
        continue;
    }

    $variantSets = $categoryName === 'Free Sized'
        ? [['Fit', 'Free Size', 'FS', 0, $quantity]]
        : [['Size', 'S', 'S', 0, 6], ['Size', 'M', 'M', 0, 8], ['Size', 'L', 'L', 150, 7], ['Color', 'Black', 'BLK', 0, 5], ['Color', 'Navy', 'NVY', 0, 5], ['Color', 'Maroon', 'MRN', 120, 4]];

    foreach ($variantSets as $variant) {
        [$key, $value, $skuSuffix, $adjustment, $variantQty] = $variant;
        $variantSku = $sku . '-' . $skuSuffix;
        mysqli_stmt_bind_param($variantStmt, 'isssdi', $productId, $key, $value, $variantSku, $adjustment, $variantQty);
        mysqli_stmt_execute($variantStmt);
    }

    $inserted++;
}

mysqli_stmt_close($productStmt);
mysqli_stmt_close($variantStmt);

$testOrderExists = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM orders WHERE user_id = 5 AND created_at = '2026-05-28 10:40:00'"))['total'] ?? 0);
if ($testOrderExists === 0) {
    $orderName = 'Roshan Negi';
    mysqli_query($con, "INSERT INTO orders (user_id, name, order_status, payment_method, shipping_charge, created_at) VALUES (5, '$orderName', 'Pending', 'Cash on Delivery', 300.00, '2026-05-28 10:40:00')");
    $orderId = mysqli_insert_id($con);
    if ($orderId > 0) {
        mysqli_query($con, "INSERT INTO shipping (order_id, billing_address, shipping_address, created_at) VALUES ($orderId, 'Dhangadhi, Nepal', 'Dhangadhi, Kailali, Nepal', '2026-05-28 10:40:00')");
        $items = mysqli_query($con, "SELECT id, price FROM product WHERE deleted_at IS NULL AND quantity > 2 ORDER BY id DESC LIMIT 2");
        while ($product = mysqli_fetch_assoc($items)) {
            $pid = (int) $product['id'];
            $price = (float) $product['price'];
            $qty = 1;
            $total = $price * $qty;
            mysqli_query($con, "INSERT INTO orderdetail (order_id, product_id, quantity, unit_price, total, created_at) VALUES ($orderId, $pid, $qty, $price, $total, '2026-05-28 10:40:00')");
            mysqli_query($con, "UPDATE product SET quantity = GREATEST(quantity - $qty, 0) WHERE id = $pid");
        }
    }
}

$finalCount = (int) (mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) AS total FROM product WHERE deleted_at IS NULL"))['total'] ?? 0);
echo "Seed complete. Products: $finalCount. Inserted: $inserted." . PHP_EOL;
