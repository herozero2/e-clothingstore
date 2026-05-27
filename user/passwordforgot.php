<?php
require_once __DIR__ . '/../includes/db.php';
$con = db_connect();

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

    require_once __DIR__ . '/Mail/mailer.php';
    require_once __DIR__ . '/../settings.php';

    $message = '';
    $error = '';

    if(isset($_POST['submit'])){
        $email = trim($_POST['email'] ?? '');
        $message = "If an account exists for that email, we sent a reset link.";

        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $stmt = mysqli_prepare($con, "SELECT id, name, email FROM users WHERE email = ? AND deleted_at IS NULL LIMIT 1");
            mysqli_stmt_bind_param($stmt, "s", $email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $row = $res ? mysqli_fetch_assoc($res) : null;

            if ($row) {
                $link = BASE_URL . "user/passwordreset.php?id=" . (int) $row['id'];
                $safeName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
                $safeLink = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
                $msg = "Hello {$safeName},<br>";
                $msg .= "Please click this link to reset your password: <a href='{$safeLink}'>Reset Password</a>";
                mailer($row['email'], $row['name'], "Password Reset - E-Clothing Store", $msg);
            }

            mysqli_stmt_close($stmt);
        }

    }
?>
<?php include("includes/header.php"); ?>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<main class="auth-shell">
    <form class="auth-card" action="" method="POST">
        <p class="text-primary fw-bold mb-2">Password help</p>
        <h1 class="mb-3">Forgot Password</h1>
        <p class="text-muted">Enter your email address and we will send you a reset link.</p>

        <?php if ($message): ?><div class="message-container success-msg"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <?php if ($error): ?><div class="message-container error-msg"><?= htmlspecialchars($error) ?></div><?php endif; ?>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" placeholder="you@example.com" name="email" required>
        </div>

        <div class="button-group">
            <button type="submit" name="submit">Send Reset Link</button>
            <a href="Userlogin.php" class="cancel-btn">Back to Login</a>
        </div>
    </form>
</main>

<?php include("includes/footer.php"); ?>
