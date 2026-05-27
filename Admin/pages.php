<?php
include '../includes/header.php';
require_once __DIR__ . '/../includes/pages.php';

ensure_store_pages_table($con);
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $slug = strtolower(trim($_POST['slug'] ?? ''));
    $content = trim($_POST['content'] ?? '');
    $sortOrder = (int) ($_POST['sort_order'] ?? 0);
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    $slug = preg_replace('/[^a-z0-9-]+/', '-', $slug);
    $slug = trim($slug, '-');

    if ($title === '' || $slug === '' || $content === '') {
        $error = 'Title, slug, and content are required.';
    } else {
        if ($id > 0) {
            $stmt = mysqli_prepare($con, "UPDATE store_pages SET title=?, slug=?, content=?, sort_order=?, is_active=? WHERE id=?");
            mysqli_stmt_bind_param($stmt, 'sssiii', $title, $slug, $content, $sortOrder, $isActive, $id);
        } else {
            $footerGroup = 'shop';
            $stmt = mysqli_prepare($con, "INSERT INTO store_pages (title, slug, content, footer_group, sort_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($stmt, 'ssssii', $title, $slug, $content, $footerGroup, $sortOrder, $isActive);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success = 'Page saved successfully.';
        } else {
            $error = 'Could not save page: ' . mysqli_error($con);
        }
        mysqli_stmt_close($stmt);
    }
}

$editId = (int) ($_GET['edit'] ?? 0);
$editPage = null;
if ($editId > 0) {
    $editResult = mysqli_query($con, "SELECT * FROM store_pages WHERE id = $editId");
    $editPage = $editResult ? mysqli_fetch_assoc($editResult) : null;
}

$pages = [];
$result = mysqli_query($con, "SELECT * FROM store_pages ORDER BY sort_order ASC, title ASC");
while ($result && $row = mysqli_fetch_assoc($result)) {
    $pages[] = $row;
}
?>

<section class="dashboard-content">
    <header class="page-header dashboard-heading">
        <div>
            <p class="eyebrow">Footer content</p>
            <h1><i class="fas fa-file-signature"></i> Pages</h1>
        </div>
        <a href="../user/page.php?slug=about-us" class="quick-action" target="_blank" rel="noopener"><i class="fas fa-eye"></i> Preview</a>
    </header>

    <?php if ($success): ?><div class="message-container success-msg"><?= htmlspecialchars($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message-container error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="admin-two-column">
        <div>
            <form class="admin-form-card" method="POST">
                <input type="hidden" name="id" value="<?= (int) ($editPage['id'] ?? 0) ?>">
                <div class="admin-form-grid">
                    <label>
                        <span>Page Title</span>
                        <input type="text" name="title" value="<?= htmlspecialchars($editPage['title'] ?? '') ?>" placeholder="About Us" required>
                    </label>
                    <label>
                        <span>Slug</span>
                        <input type="text" name="slug" value="<?= htmlspecialchars($editPage['slug'] ?? '') ?>" placeholder="about-us" required>
                    </label>
                    <label>
                        <span>Sort Order</span>
                        <input type="number" name="sort_order" value="<?= htmlspecialchars((string) ($editPage['sort_order'] ?? 1)) ?>" min="0">
                    </label>
                    <label class="checkbox-label">
                        <span>Show In Footer</span>
                        <input type="checkbox" name="is_active" <?= !isset($editPage) || (int) ($editPage['is_active'] ?? 1) === 1 ? 'checked' : '' ?>>
                    </label>
                </div>
                <div class="admin-form-grid one-column mt-3">
                    <label>
                        <span>Page Content</span>
                        <textarea name="content" rows="10" required><?= htmlspecialchars($editPage['content'] ?? '') ?></textarea>
                    </label>
                </div>
                <div class="admin-form-actions">
                    <button type="submit"><i class="fas fa-save"></i> Save Page</button>
                    <a href="pages.php">New Page</a>
                </div>
            </form>
        </div>

        <div>
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>Page</th>
                            <th>Slug</th>
                            <th>Status</th>
                            <th>Edit</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pages as $page): ?>
                            <tr>
                                <td><?= htmlspecialchars($page['title']) ?></td>
                                <td><?= htmlspecialchars($page['slug']) ?></td>
                                <td><?= (int) $page['is_active'] === 1 ? 'Visible' : 'Hidden' ?></td>
                                <td><a class="btn edit-btn" href="pages.php?edit=<?= (int) $page['id'] ?>"><i class="fas fa-edit"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
