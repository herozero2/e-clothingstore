<?php
require_once __DIR__ . '/../includes/admin_auth.php';

admin_session_start();
$con = db_connect();
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$error = '';

if (admin_try_remember_login($con)) {
    header("Location: Admindashboard.php");
    exit();
}

if (isset($_POST['adminlogin'])) {
    $email = trim($_POST['email']);
    $password = md5(trim($_POST['password'])); 

    $stmt = mysqli_prepare($con, "SELECT * FROM users WHERE email = ? AND password = ? AND deleted_at IS NULL AND user_type = 'admin' LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'ss', $email, $password);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($result && mysqli_num_rows($result) === 1) {
        $admin = mysqli_fetch_assoc($result);

        admin_hydrate_session($admin);

        if (!empty($_POST['remember'])) {
            admin_set_remember_cookie($admin);
        } else {
            admin_clear_remember_cookie();
        }

        header("Location: Admindashboard.php");
        exit();
    } else {
        $error = "Invalid admin credentials!";
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Login - E-Clothing Store</title>
    <link rel="stylesheet" href="../assets/css/style.css" />
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />
</head>
<body>
    <form class="authForm" action="" method="post" novalidate>
        <h2>E-Clothing Store</h2>

        <?php if ($error): ?>
            <p style="color: red; font-weight: 600;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <div class="form-group">
            <input type="email" name="email" id="email" placeholder=" " required
                value="<?php echo isset($_COOKIE['admin_email']) ? htmlspecialchars($_COOKIE['admin_email']) : ''; ?>" />
            <label for="email">Email</label>
        </div>

        <div class="form-group" style="position: relative;">
        <i class="bx bx-show toggle-icon" id="togglePassword"></i>
            <input type="password" name="password" id="password" placeholder=" " required />
            <label for="password">Password</label>
        </div>

        <div class="form-options">
            <label>
                <input type="checkbox" name="remember" <?php if (isset($_COOKIE['admin_email'])) echo 'checked'; ?> />
                Remember me
            </label>
            <!-- <a href="forgotpassword.php">Forgot Password?</a> -->
        </div>

        <div class="button-group">
            <button type="submit" name="adminlogin">Login</button>
        </div>

    </form>

    <script>
        const togglePassword = document.getElementById("togglePassword");
        const passwordInput = document.getElementById("password");

        togglePassword.addEventListener("click", () => {
            const type = passwordInput.type === "password" ? "text" : "password";
            passwordInput.type = type;
            togglePassword.classList.toggle('bx-show');
            togglePassword.classList.toggle('bx-hide');
        });

        togglePassword.addEventListener("keydown", e => {
            if (e.key === "Enter" || e.key === " ") {
                e.preventDefault();
                togglePassword.click();
            }
        });

    </script>
</body>
</html>
