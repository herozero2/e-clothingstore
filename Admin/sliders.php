<?php
include '../includes/header.php';
require_once __DIR__ . '/../includes/slider.php';

ensure_home_sliders_table($con);

$upload_dir = '../assets/images/';
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'delete' && isset($_POST['id'])) {
        $id = (int) $_POST['id'];
        $stmt = mysqli_prepare($con, "UPDATE home_sliders SET is_active = 0 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $success_message = "Slider removed successfully.";
    } else {
        $link_url = trim($_POST['link_url'] ?? 'user/our_shop.php');
        $sort_order = (int) ($_POST['sort_order'] ?? 0);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $image = '';

        if (!empty($_FILES['image']['name'])) {
            $file_name = basename($_FILES['image']['name']);
            $target = $upload_dir . $file_name;
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/avif'];

            if (!in_array($_FILES['image']['type'], $allowed)) {
                $error_message = "Only JPG, PNG, WEBP, and AVIF images are allowed.";
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $error_message = "Could not upload slider image.";
            } else {
                $image = $file_name;
            }
        }

        if ($error_message === '') {
            if ($action === 'update' && isset($_POST['id'])) {
                $id = (int) $_POST['id'];
                if ($image !== '') {
                    $stmt = mysqli_prepare($con, "UPDATE home_sliders SET image=?, link_url=?, sort_order=?, is_active=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "ssiii", $image, $link_url, $sort_order, $is_active, $id);
                } else {
                    $stmt = mysqli_prepare($con, "UPDATE home_sliders SET link_url=?, sort_order=?, is_active=? WHERE id=?");
                    mysqli_stmt_bind_param($stmt, "siii", $link_url, $sort_order, $is_active, $id);
                }
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success_message = "Slider updated successfully.";
            } elseif ($image !== '') {
                $stmt = mysqli_prepare($con, "INSERT INTO home_sliders (image, link_url, sort_order, is_active) VALUES (?, ?, ?, ?)");
                mysqli_stmt_bind_param($stmt, "ssii", $image, $link_url, $sort_order, $is_active);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
                $success_message = "Slider added successfully.";
            } else {
                $error_message = "Please choose an image for the new slider.";
            }
        }
    }
}

$slides = get_home_sliders($con);
?>

<section class="dashboard-content">
    <header class="page-header dashboard-heading">
        <div>
            <p class="eyebrow">Homepage media</p>
            <h1><i class="fas fa-images"></i> Slider Manager</h1>
        </div>
    </header>

    <?php if ($success_message): ?><div class="message-container success-msg"><?= htmlspecialchars($success_message) ?></div><?php endif; ?>
    <?php if ($error_message): ?><div class="message-container error-msg"><?= htmlspecialchars($error_message) ?></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="admin-form-card">
        <input type="hidden" name="action" value="create">
        <div class="admin-form-grid">
            <label>
                <span>Slider Image</span>
                <input type="file" name="image" accept="image/*" required>
            </label>
            <label>
                <span>Clickable Link</span>
                <input type="text" name="link_url" value="user/our_shop.php" required>
            </label>
            <label>
                <span>Sort Order</span>
                <input type="number" name="sort_order" value="1" min="0">
            </label>
            <label class="checkbox-label">
                <span>Active</span>
                <input type="checkbox" name="is_active" checked>
            </label>
        </div>
        <div class="admin-form-actions">
            <button type="submit"><i class="fas fa-plus"></i> Add Slider</button>
        </div>
    </form>

    <div class="table-container" style="margin-top: 22px;">
        <table>
            <thead>
                <tr>
                    <th>Preview</th>
                    <th>Link</th>
                    <th>Order</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $slide): ?>
                    <?php
                    $slideLink = $slide['link_url'];
                    $slideHref = preg_match('/^https?:\/\//', $slideLink) ? $slideLink : '../' . ltrim($slideLink, '/');
                    ?>
                    <tr>
                        <td><img src="../assets/images/<?= htmlspecialchars($slide['image']) ?>" alt="" style="width: 140px; height: 70px; object-fit: cover; border-radius: 8px;"></td>
                        <td><a href="<?= htmlspecialchars($slideHref) ?>" target="_blank" rel="noopener"><?= htmlspecialchars($slide['link_url']) ?></a></td>
                        <td><?= (int) $slide['sort_order'] ?></td>
                        <td><?= $slide['is_active'] ? 'Active' : 'Hidden' ?></td>
                        <td>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Remove this slider?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $slide['id'] ?>">
                                <button class="btn delete-btn" type="submit"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include '../includes/footer.php'; ?>
