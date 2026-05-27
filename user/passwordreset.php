<?php
require_once __DIR__ . '/../includes/db.php';
$con = db_connect();

if (!$con) {
    die("Database connection failed: " . mysqli_connect_error());
}

$user_id = (int) ($_GET['id'] ?? 0);
$error = '';

if ($user_id <= 0) {
    $error = "Invalid reset link.";
}

if (isset($_POST['submit']) && $user_id > 0) {
    $pass = $_POST['pass'];
    $cpass = $_POST['cpass'];

    if ($pass !== $cpass) {
        $error = "Passwords do not match.";
    } elseif (strlen($pass) < 6) {
        $error = "Password must be at least 6 characters.";
    } else {
        $safePassword = md5($pass);
        $stmt = mysqli_prepare($con, "UPDATE users SET password = ? WHERE id = ? AND deleted_at IS NULL");
        mysqli_stmt_bind_param($stmt, "si", $safePassword, $user_id);
        if (mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            header("Location: Userlogin.php");
            exit();
        }
        mysqli_stmt_close($stmt);
        $error = "Something went wrong while resetting the password.";
    }
}
?>
<?php include("includes/header.php"); ?>
<link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>

<main class="auth-shell">
    <form class="auth-card" action="" method="post" onsubmit="return validatePassword()">
        <p class="text-primary fw-bold mb-2">Password help</p>
        <h1 class="mb-4">Reset Password</h1>

        <?php if (!empty($error)): ?>
            <div class="message-container error-msg"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="password">New Password</label>
            <input type="password" name="pass" id="password" placeholder=" " minlength="6" required>
            <i class="bx bx-show toggle-icon" id="togglePassword"></i>
        </div>

        <div class="form-group">
            <label for="cpassword">Confirm Password</label>
            <input type="password" name="cpass" id="cpassword" placeholder=" " minlength="6" required>
            <i class="bx bx-show toggle-icon" id="toggleConfirmPassword"></i>
        </div>

        <div class="button-group">
            <button type="submit" name="submit">Change Password</button>
            <a href="Userlogin.php" class="cancel-btn">Back to Login</a>
        </div>
    </form>
</main>

<script>
    function togglePasswordField(toggleId, inputId) {
        const toggle = document.getElementById(toggleId);
        const input = document.getElementById(inputId);
        toggle.addEventListener("click", () => {
            input.type = input.type === "password" ? "text" : "password";
            toggle.classList.toggle('bx-show');
            toggle.classList.toggle('bx-hide');
        });
    }

    togglePasswordField("togglePassword", "password");
    togglePasswordField("toggleConfirmPassword", "cpassword");

    function validatePassword() {
        const password = document.getElementById("password").value;
        const confirm = document.getElementById("cpassword").value;

        if (password !== confirm) {
            alert("Passwords do not match.");
            return false;
        }

        if (password.length < 6) {
            alert("Password must be at least 6 characters.");
            return false;
        }

        return true;
    }
</script>

<?php include("includes/footer.php"); ?>
