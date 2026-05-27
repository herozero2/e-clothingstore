<?php
session_start();
 include("includes/header.php");
?>

<br><br>
    <!-- Success Message Start -->
    <div class="success-container-wrapper">
        <div class="success-container">
            <i class="fas fa-check-circle mb-3 animate__animated animate__bounceIn"></i>
            <h1 class="animate__animated animate__fadeInDown">Thank you! 🎉</h1>
            <p class="animate__animated animate__fadeInUp">Your order has been placed successfully.</p>
            <div class="mt-4">
                <a href="../index.php" class="animate__animated animate__fadeInLeft">Continue Shopping</a>
                <a href="myorders.php" class="animate__animated animate__fadeInRight">View My Orders</a>
            </div>
        </div>
    </div>
    <!-- Success Message End -->
    <?php include("includes/footer.php");?>
