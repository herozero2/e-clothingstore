<aside class="account-sidebar">
    <a href="myaccount.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'myaccount.php' ? 'active' : '' ?>"><i class="fas fa-columns"></i> Dashboard</a>
    <a href="myorders.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'myorders.php' ? 'active' : '' ?>"><i class="fas fa-box"></i> My Orders</a>
    <a href="wishlist.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'wishlist.php' ? 'active' : '' ?>"><i class="fas fa-heart"></i> My Wishlist</a>
    <a href="profile.php" class="<?= basename($_SERVER['SCRIPT_NAME']) === 'profile.php' ? 'active' : '' ?>"><i class="fas fa-user-edit"></i> Profile</a>
    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
</aside>
