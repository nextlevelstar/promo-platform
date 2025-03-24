<?php
session_start();
include('include/config.php'); // Your database connection

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$promotion_id = $_POST['promotion_id'] ?? 0;

// Check if the user already liked the promotion
$check_query = "SELECT * FROM promotion_likes WHERE user_id = $user_id AND promotion_id = $promotion_id";
$check_result = mysqli_query($conn, $check_query);

if (mysqli_num_rows($check_result) > 0) {
    // Unlike: Remove the like
    $delete_query = "DELETE FROM promotion_likes WHERE user_id = $user_id AND promotion_id = $promotion_id";
    mysqli_query($conn, $delete_query);
} else {
    // Like: Add new like
    $insert_query = "INSERT INTO promotion_likes (user_id, promotion_id) VALUES ($user_id, $promotion_id)";
    mysqli_query($conn, $insert_query);
}

// Get the updated like count
$count_query = "SELECT COUNT(*) AS total FROM promotion_likes WHERE promotion_id = $promotion_id";
$count_result = mysqli_query($conn, $count_query);
$count_row = mysqli_fetch_assoc($count_result);
$total_likes = $count_row['total'];

echo json_encode(['success' => true, 'likes' => $total_likes]);
?>
