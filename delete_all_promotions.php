<?php
// Include the database connection file
include('header.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please log in first!";
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// CSRF protection: Verify the CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token mismatch!');
}

// Process the deletion of all promotions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Prepare the SQL query to delete all promotions by the user
    $query = "DELETE FROM promotions WHERE user_id = ?";
    
    // Prepare the statement
    if ($stmt = $conn->prepare($query)) {
        // Bind the parameters to prevent SQL injection
        $stmt->bind_param("i", $user_id); // Bind user_id as an integer

        // Execute the query
        if ($stmt->execute()) {
            echo "<div class='success-message'>All promotions deleted successfully!</div>";
        } else {
            echo "<div class='error-message'>Error: " . htmlspecialchars($stmt->error) . "</div>";
        }

        // Close the statement
        $stmt->close();
    } else {
        echo "<div class='error-message'>Error preparing statement: " . htmlspecialchars($conn->error) . "</div>";
    }
}

// Redirect back to the promotions page or another appropriate page
header("Location: dashboard.php");
exit();
?>