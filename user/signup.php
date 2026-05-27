<?php
session_start();

require_once __DIR__ . '/../includes/db.php';

$con = db_connect();
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$errors = [];
$success = '';

if (isset($_POST['signup'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password_raw = trim($_POST['password']);
    $confirm_password_raw = trim($_POST['confirm_password']);

    $image = "avatar.jpg";

    // Server-side validation
    if (empty($name)) {
        $errors['name'] = "Full name is required.";
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $errors['name'] = "Name should contain letters and spaces only.";
    }

    if (empty($email)) {
        $errors['email'] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format.";
    }

    if (empty($password_raw)) {
        $errors['password'] = "Password is required.";
    } elseif (strlen($password_raw) < 6) {
        $errors['password'] = "Password must be at least 6 characters.";
    }

    if (empty($confirm_password_raw)) {
        $errors['confirm_password'] = "Confirm your password.";
    } elseif ($password_raw !== $confirm_password_raw) {
        $errors['confirm_password'] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $password = md5($password_raw); // Use password_hash() for production

        $checkQuery = "SELECT id FROM users WHERE email = ? AND deleted_at IS NULL";
        $checkStmt = mysqli_prepare($con, $checkQuery);
        mysqli_stmt_bind_param($checkStmt, "s", $email);
        mysqli_stmt_execute($checkStmt);
        $checkResult = mysqli_stmt_get_result($checkStmt);

        if ($checkResult && mysqli_num_rows($checkResult) > 0) {
            $errors['email'] = "Email is already registered.";
        } else {
            $insertQuery = "INSERT INTO users (name, email, password, image) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($con, $insertQuery);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $image);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['user_id'] = mysqli_insert_id($con);
                $_SESSION['user_name'] = $name;
                $_SESSION['email'] = $email;
                $_SESSION['user_type'] = 'user';
                $_SESSION['user_image'] = $image;
                header("Location: ../index.php");
                exit;
            } else {
                $errors['form'] = "Registration failed: " . mysqli_error($con);
            }
            mysqli_stmt_close($stmt);
        }
        mysqli_stmt_close($checkStmt);
    }
}
?>
<?php include("includes/header.php"); ?>
<link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet" />

<main class="auth-shell">
    <form class="auth-card authForm" action="" method="post">
        <p class="text-primary fw-bold mb-2">Create account</p>
        <h1 class="mb-4">Create Account</h1>

        <?php if (!empty($errors['form'])): ?>
            <div class="message-container error-msg"><?php echo $errors['form']; ?></div>
        <?php elseif ($success): ?>
            <div class="message-container success-msg"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" name="name" id="name" placeholder=" " required
                value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" />
            <?php if (!empty($errors['name'])): ?>
                <small style="color: red;"><?php echo $errors['name']; ?></small>
            <?php endif; ?>
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" name="email" id="email" placeholder=" " required
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" />
            <?php if (!empty($errors['email'])): ?>
                <small style="color: red;"><?php echo $errors['email']; ?></small>
            <?php endif; ?>
        </div>

        <div class="form-group" style="position: relative;">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" placeholder=" " required
                minlength="6"
                value="<?php echo (!isset($errors['password']) && isset($_POST['password'])) ? htmlspecialchars($_POST['password']) : ''; ?>" />
            <i class="bx bx-show toggle-icon" id="togglePassword" aria-label="Toggle password visibility" role="button"
                tabindex="0"></i>
            <?php if (!empty($errors['password'])): ?>
                <small style="color: red;"><?php echo $errors['password']; ?></small>
            <?php endif; ?>
        </div>

        <div class="form-group" style="position: relative;">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder=" " required
                minlength="6"
                value="<?php echo (!isset($errors['confirm_password']) && isset($_POST['confirm_password'])) ? htmlspecialchars($_POST['confirm_password']) : ''; ?>" />

            <i class="bx bx-show toggle-icon" id="toggleConfirmPassword" aria-label="Toggle password visibility"
                role="button" tabindex="0"></i>
            <?php if (!empty($errors['confirm_password'])): ?>
                <small style="color: red;"><?php echo $errors['confirm_password']; ?></small>
            <?php endif; ?>
        </div>

        <div class="button-group">
            <button type="submit" name="signup">Create Account</button>
            <a href="Userlogin.php" id="backToLogin" class="cancel-btn">Back to Login</a>
        </div>
    </form>
</main>

    <script>
        function toggleVisibility(toggleId, inputId) {
            const toggleIcon = document.getElementById(toggleId);
            const inputField = document.getElementById(inputId);

            toggleIcon.addEventListener("click", () => {
                const type = inputField.type === "password" ? "text" : "password";
                inputField.type = type;
                toggleIcon.classList.toggle("bx-show");
                toggleIcon.classList.toggle("bx-hide");
            });

            toggleIcon.addEventListener("keydown", (e) => {
                if (e.key === "Enter" || e.key === " ") {
                    e.preventDefault();
                    toggleIcon.click();
                }
            });
        }
        toggleVisibility("togglePassword", "password");
        toggleVisibility("toggleConfirmPassword", "confirm_password");

        document.getElementById("backToLogin").addEventListener("click", function (e) {
            e.preventDefault();
            const emailValue = document.getElementById("email").value.trim();
            let loginUrl = "Userlogin.php";
            if (emailValue) {
                loginUrl += "?email=" + encodeURIComponent(emailValue);
            }
            window.location.href = loginUrl;
        });

    </script>
<?php include("includes/footer.php"); ?>
