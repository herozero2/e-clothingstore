<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: Userlogin.php");
    exit;
}

require_once __DIR__ . '/../includes/db.php';
$con = db_connect();
$user_id = (int) $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $imageSql = '';

    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid name and email.";
    } else {
        if (!empty($_FILES['image']['name'])) {
            $image = basename($_FILES['image']['name']);
            $target = __DIR__ . '/../assets/images/' . $image;
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg', 'image/avif'];
            if (!in_array($_FILES['image']['type'], $allowed)) {
                $error = "Please upload a valid image file.";
            } elseif (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
                $imageSql = ", image='" . mysqli_real_escape_string($con, $image) . "'";
                $_SESSION['user_image'] = $image;
            }
        }

        if ($error === '') {
            $safeName = mysqli_real_escape_string($con, $name);
            $safeEmail = mysqli_real_escape_string($con, $email);
            mysqli_query($con, "UPDATE users SET name='$safeName', email='$safeEmail' $imageSql WHERE id=$user_id");
            $_SESSION['user_name'] = $name;
            $_SESSION['email'] = $email;
            $success = "Profile updated successfully.";
        }
    }
}

$user = mysqli_fetch_assoc(mysqli_query($con, "SELECT * FROM users WHERE id = $user_id"));
?>
<?php include("includes/header.php"); ?>

<main class="container py-5" style="padding-top: 170px !important;">
    <div class="account-layout">
        <?php include("includes/account_sidebar.php"); ?>
        <section class="account-panel">
            <p class="text-primary fw-bold mb-1">Profile</p>
            <h1>Manage Profile</h1>
            <?php if ($success): ?><div class="message-container success-msg"><?= htmlspecialchars($success) ?></div><?php endif; ?>
            <?php if ($error): ?><div class="message-container error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <form method="POST" enctype="multipart/form-data" class="mt-4">
                <div class="row g-4">
                    <div class="col-12">
                        <?php
                        $profileImage = !empty($user['image']) ? '../assets/images/' . $user['image'] : '../assets/images/avatar.jpg';
                        if (!empty($user['image']) && str_starts_with($user['image'], 'http')) {
                            $profileImage = $user['image'];
                        }
                        ?>
                        <div class="d-flex align-items-center gap-3 filter-panel">
                            <img src="<?= htmlspecialchars($profileImage) ?>" alt="Profile image" style="width: 86px; height: 86px; object-fit: cover; border-radius: 50%;">
                            <div>
                                <strong><?= htmlspecialchars($user['name'] ?? 'Customer') ?></strong>
                                <div class="text-muted"><?= htmlspecialchars($user['email'] ?? '') ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Name</label>
                        <input class="form-control py-3" type="text" name="name" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Email</label>
                        <input class="form-control py-3" type="email" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label fw-bold">Profile Image</label>
                        <input class="form-control py-3" type="file" name="image" accept="image/*">
                    </div>
                </div>
                <button class="btn btn-primary rounded-pill px-4 mt-4" type="submit">Save Profile</button>
            </form>
        </section>
    </div>
</main>

<?php include("includes/footer.php"); ?>
