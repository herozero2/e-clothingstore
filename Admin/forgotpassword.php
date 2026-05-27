<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="../assets/css/passwordreset.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <div class="forgot-password-wrapper">
        <form action="" method="POST">
            
            <h1>Forgot Password</h1>
            <p>Enter your email address and we’ll send you a link to reset your password.</p>
            
            <div class="input-box">
                <i class='bx bxs-envelope'></i>
                <input type="email" placeholder="Email Address" name="email" required>
            </div>
            
            <button type="submit" class="btn">Send Reset Link</button>
            
            <div class="login-link">
                <p>Remember your password? <a href="Adminlogin.php">Login</a></p>
            </div>
        </form>
    </div>
</body>
</html>
