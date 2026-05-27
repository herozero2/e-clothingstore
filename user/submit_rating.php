<?php
session_start();
require_once __DIR__ . '/../includes/db.php';
$conn = db_connect();

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['rating'], $_POST['product_id']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = intval($_POST['product_id']);
    $rating = intval($_POST['rating']);
    $review = mysqli_real_escape_string($conn, $_POST['review']);

    // Prevent duplicate rating by same user on same product
    $check = mysqli_query($conn, "SELECT * FROM product_ratings WHERE user_id = $user_id AND product_id = $product_id");
    if (mysqli_num_rows($check) > 0) {
        // update rating
        $query = "UPDATE product_ratings SET rating = $rating, review = '$review', created_at = NOW() 
                  WHERE user_id = $user_id AND product_id = $product_id";
    } else {
        // insert new rating
        $query = "INSERT INTO product_ratings (product_id, user_id, rating, review) 
                  VALUES ($product_id, $user_id, $rating, '$review')";
    }

    if (mysqli_query($conn, $query)) {
        header("Location: product_details.php?id=$product_id&rated=1");
        exit;
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} else {
    echo "Invalid access.";
}
?>
