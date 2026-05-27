<?php
include '../includes/header.php';
require_once __DIR__ . '/../includes/smtp.php';

ensure_smtp_settings_table($con);

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['host'] ?? '');
    $port = (int) ($_POST['port'] ?? 587);
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $encryption = trim($_POST['encryption'] ?? '');
    $from_email = trim($_POST['from_email'] ?? '');
    $from_name = trim($_POST['from_name'] ?? '');
    $admin_email = trim($_POST['admin_email'] ?? '');

    if ($host === '') {
        $error_message = "SMTP host is required.";
    } elseif ($port <= 0) {
        $error_message = "SMTP port must be valid.";
    } elseif (!filter_var($from_email, FILTER_VALIDATE_EMAIL) || !filter_var($admin_email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "From email and admin email must be valid.";
    } else {
        mysqli_query($con, "UPDATE smtp_settings SET is_active = 0");
        $stmt = mysqli_prepare($con, "INSERT INTO smtp_settings
            (host, port, username, password, encryption, from_email, from_name, admin_email, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, "sissssss", $host, $port, $username, $password, $encryption, $from_email, $from_name, $admin_email);

        if (mysqli_stmt_execute($stmt)) {
            $success_message = "SMTP settings saved successfully.";
        } else {
            $error_message = "Could not save SMTP settings: " . mysqli_error($con);
        }
        mysqli_stmt_close($stmt);
    }
}

$settings = get_smtp_settings();
?>

<section class="dashboard-content">
    <header class="page-header dashboard-heading">
        <div>
            <p class="eyebrow">Email delivery</p>
            <h1><i class="fas fa-envelope-open-text"></i> SMTP Settings</h1>
        </div>
    </header>

    <form method="POST" class="admin-form-card">
        <?php if ($success_message): ?>
            <div class="message-container success-msg"><?= htmlspecialchars($success_message) ?></div>
        <?php endif; ?>
        <?php if ($error_message): ?>
            <div class="message-container error-msg"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="admin-form-grid">
            <label>
                <span>SMTP Host</span>
                <input type="text" name="host" value="<?= htmlspecialchars($settings['host']) ?>" required>
            </label>
            <label>
                <span>Port</span>
                <input type="number" name="port" value="<?= htmlspecialchars($settings['port']) ?>" min="1" required>
            </label>
            <label>
                <span>Username</span>
                <input type="text" name="username" value="<?= htmlspecialchars($settings['username']) ?>">
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" value="<?= htmlspecialchars($settings['password']) ?>">
            </label>
            <label>
                <span>Encryption</span>
                <select name="encryption">
                    <option value="" <?= $settings['encryption'] === '' ? 'selected' : '' ?>>None</option>
                    <option value="tls" <?= $settings['encryption'] === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= $settings['encryption'] === 'ssl' ? 'selected' : '' ?>>SSL</option>
                </select>
            </label>
            <label>
                <span>From Email</span>
                <input type="email" name="from_email" value="<?= htmlspecialchars($settings['from_email']) ?>" required>
            </label>
            <label>
                <span>From Name</span>
                <input type="text" name="from_name" value="<?= htmlspecialchars($settings['from_name']) ?>" required>
            </label>
            <label>
                <span>Admin Notification Email</span>
                <input type="email" name="admin_email" value="<?= htmlspecialchars($settings['admin_email']) ?>" required>
            </label>
        </div>

        <div class="admin-form-actions">
            <button type="submit"><i class="fas fa-save"></i> Save SMTP Settings</button>
            <a href="Admindashboard.php">Cancel</a>
        </div>
    </form>
</section>

<?php include '../includes/footer.php'; ?>
