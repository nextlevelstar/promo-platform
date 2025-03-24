<?php 
include('header.php');

// Generate CSRF token if not already set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = ""; // To store error messages dynamically

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("❌ Invalid CSRF token.");
    }

    // Sanitize & validate input
    $username_or_email = htmlspecialchars(trim($_POST['username_or_email']));
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $error_message = "❌ Both fields are required.";
    } else {
        // Check if the input is an email or username
        if (filter_var($username_or_email, FILTER_VALIDATE_EMAIL)) {
            // It's an email, so check the email in the database
            $sql = "SELECT * FROM users WHERE email = ?";
        } else {
            // It's a username, so check the username in the database
            $sql = "SELECT * FROM users WHERE username = ?";
        }

        // Prepare and execute the query
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username_or_email); // Bind the input parameter (email or username)
            $stmt->execute();
            $result = $stmt->get_result(); // Get the result of the query
            $user = $result->fetch_assoc(); // Fetch user data as an associative array

            // If the user exists
            if ($user) {
                // Check if the password matches
                if (password_verify($password, $user['password'])) {
                    // If password matches, proceed with login
                    $_SESSION['user_id'] = $user['id']; // Store user ID in session
                    $_SESSION['username'] = $user['username']; // Optionally, store username in session
                    header("Location: index.php"); // Redirect to home page
                    exit;
                } else {
                    // If the password does not match
                    $error_message = "❌ Incorrect password.";
                }
            } else {
                // If the username/email doesn't exist
                $error_message = "❌ Username or Email not found.";
            }

            // Close the statement
            $stmt->close();
        } else {
            // Error handling for database query failure
            $error_message = "❌ Something went wrong, please try again later.";
        }
    }
}

// Close the MySQLi connection
$conn->close();
?>

<h2>Login To Your Account</h2>

<!-- Login Form -->
<form action="login.php" method="post">
    <!-- CSRF Token -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <input type="text" name="username_or_email" placeholder="Username or Email" required>
    <input type="password" name="password" placeholder="Password" required>
    
    <?php if (!empty($error_message)): ?>
        <div class='error-message'><?= $error_message ?></div>
    <?php endif; ?>

    <button type="submit">Login</button>
</form>

<?php include('footer.php'); ?>
