<?php
include("includes/header.php");
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/pages.php';

$con = db_connect();
$slug = $_GET['slug'] ?? 'about-us';
$page = get_store_page($con, $slug);
?>

<main class="container py-5" style="padding-top: 170px !important;">
    <section class="account-panel content-page">
        <?php if ($page): ?>
            <p class="text-primary fw-bold mb-1">E-Clothing Store</p>
            <h1><?= htmlspecialchars($page['title']) ?></h1>
            <div class="content-page-body">
                <?= nl2br(htmlspecialchars($page['content'])) ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <h1>Page Not Found</h1>
                <p class="text-muted">The page you are looking for is not available.</p>
                <a href="../index.php" class="btn btn-primary rounded-pill px-4">Back to Home</a>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php include("includes/footer.php"); ?>
