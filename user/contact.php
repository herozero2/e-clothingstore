<?php
use PHPMailer\PHPMailer\PHPMailer;

require 'vendor/autoload.php'; 

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/smtp.php';
$con = db_connect();

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch Store Settings
$storeQuery = mysqli_query($con, "SELECT * FROM store_settings LIMIT 1");
$store = mysqli_fetch_assoc($storeQuery);

$address = $store['store_address'] ?? 'Dhangadhi, Kailali Nepal';
$email = $store['store_email'] ?? 'help@example.com';
$phone = $store['contact_number'] ?? '974212321445';

// Check User Login
$isLoggedIn = isset($_SESSION['user_id']);
$userName = "";
$userEmail = "";

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $userQuery = mysqli_query($con, "SELECT name, email FROM users WHERE id = $userId");
    $user = mysqli_fetch_assoc($userQuery);

    $userName = $user['name'];
    $userEmail = $user['email'];
}

// Handle Form Submission
if (isset($_POST['submit'])) {
    if (!$isLoggedIn) {
        echo "<script>alert('Please login to contact us');</script>";
    } else {
        $message = mysqli_real_escape_string($con, $_POST['message']);
        $insert = mysqli_query($con, "INSERT INTO user_contact (user_id, message) VALUES ($userId, '$message')");

        if ($insert) {
            // Send Email to Admin
            $mail = new PHPMailer(true);

            try {
                $smtpSettings = get_smtp_settings();
                apply_smtp_settings($mail, $smtpSettings);
                $mail->addReplyTo($userEmail, $userName);
                $mail->addAddress($smtpSettings['admin_email'] ?: $email, 'Store Admin');

                // Email Content
                $mail->isHTML(true);
                $mail->Subject = 'New Contact Message from ' . $userName;
                $safeUserName = htmlspecialchars($userName, ENT_QUOTES, 'UTF-8');
                $safeUserEmail = htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8');
                $safeMessage = nl2br(htmlspecialchars($_POST['message'] ?? '', ENT_QUOTES, 'UTF-8'));
                $mail->Body = "
                    <h3>New Contact Message Received</h3>
                    <p><strong>Name:</strong> {$safeUserName}</p>
                    <p><strong>Email:</strong> {$safeUserEmail}</p>
                    <p><strong>Message:</strong><br>{$safeMessage}</p>
                ";

                $mail->send();
                echo "<script>alert('Message Sent Successfully');</script>";
            } catch (\Throwable $e) {
                error_log("Contact mail failed for {$userEmail}: " . $e->getMessage() . " " . $mail->ErrorInfo);
                echo "<script>alert('Message saved but failed to send email.');</script>";
            }
        } else {
            echo "<script>alert('Failed to send message');</script>";
        }
    }
}
?>

<?php include("includes/header.php"); ?>

<!-- Rest of your HTML (unchanged) -->

<div class="container-fluid page-header py-5">
    <h1 class="text-center text-white display-6">Contact</h1>
    <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item"><a href="../index.php">Home</a></li>
        <li class="breadcrumb-item active text-white">Contact</li>
    </ol>
</div>

<div class="container-fluid contact py-5">
    <div class="container py-5">
        <div class="p-5 bg-light rounded">
            <div class="row g-4">
                <div class="col-12">
                    <div class="text-center mx-auto" style="max-width: 700px;">
                        <h1 class="text-primary">Get in touch</h1>
                        <p class="mb-4">Customer are the GOD for us!<br>If you have any issue about the product or anything to tell us, feel free to contact us!<br>Thank you for connecting with us!</p>
                    </div>
                </div>
                <div class="col-lg-12">
                    <div class="h-100 rounded">
                        <iframe class="rounded w-100" style="height: 400px;"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3533.0168924067174!2d80.59858337538288!3d28.701055475642774!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3997b6b13c2e055b%3A0x84b84149b00791e2!2sDhangadhi%2C%20Kailali%2046000!5e0!3m2!1sen!2snp!4v1720072300814!5m2!1sen!2snp"
                            loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>
                <div class="col-lg-7">
                    <form method="POST" action="">
                        <input type="text" class="w-100 form-control border-0 py-3 mb-4" placeholder="Your Name" name="name"
                            value="<?= htmlspecialchars($userName) ?>" <?= $isLoggedIn ? 'readonly' : '' ?>>

                        <input type="email" class="w-100 form-control border-0 py-3 mb-4" placeholder="Enter Your Email" name="email"
                            value="<?= htmlspecialchars($userEmail) ?>" <?= $isLoggedIn ? 'readonly' : '' ?>>

                        <textarea class="w-100 form-control border-0 mb-4" rows="5" cols="10"
                            placeholder="Your Message" name="message" required></textarea>

                        <button class="w-100 btn form-control border-secondary py-3 bg-white text-primary"
                            type="submit" name="submit" <?= $isLoggedIn ? '' : 'disabled' ?>>Submit</button>

                        <?php if (!$isLoggedIn): ?>
                            <p class="text-danger mt-2">* Please login to contact us.</p>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-lg-5">
                    <div class="d-flex p-4 rounded mb-4 bg-white">
                        <i class="fas fa-map-marker-alt fa-2x text-primary me-4"></i>
                        <div>
                            <h4>Address</h4>
                            <p class="mb-2"><?= htmlspecialchars($address) ?></p>
                        </div>
                    </div>
                    <div class="d-flex p-4 rounded mb-4 bg-white">
                        <i class="fas fa-envelope fa-2x text-primary me-4"></i>
                        <div>
                            <h4>Mail Us</h4>
                            <p class="mb-2"><?= htmlspecialchars($email) ?></p>
                        </div>
                    </div>
                    <div class="d-flex p-4 rounded bg-white">
                        <i class="fa fa-phone-alt fa-2x text-primary me-4"></i>
                        <div>
                            <h4>Telephone</h4>
                            <p class="mb-2"><?= htmlspecialchars($phone) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include("includes/footer.php"); ?>
