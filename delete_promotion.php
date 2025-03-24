<?php
session_start();
include('include/config.php'); // Database connection file

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please log in first!";
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// CSRF token validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch!'); // Terminate if token doesn't match
    }

    $promotion_id = $_POST['promotion_id'];

    // Ensure the promotion_id is valid
    if (!filter_var($promotion_id, FILTER_VALIDATE_INT)) {
        echo "Invalid promotion ID!";
        exit();
    }

    // Verify that the logged-in user owns this promotion
    $check_query = "SELECT * FROM promotions WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("ii", $promotion_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "You are not authorized to delete this promotion!";
        exit();
    }

    // Proceed with deletion
    $delete_query = "DELETE FROM promotions WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $promotion_id);

    if ($stmt->execute()) {
        header("Location: dashboard.php"); // Redirect back to the promotions page
        exit();
    } else {
        echo "Error deleting promotion: " . htmlspecialchars($conn->error);
    }

    $stmt->close();
} else {
    echo "Invalid request!";
}
?>
