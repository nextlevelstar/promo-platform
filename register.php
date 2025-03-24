<?php
include('header.php');

$error_message = ""; // To store error messages dynamically

// Generate CSRF token for the form
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("❌ Invalid CSRF token.");
    }

    // Database connection
    if (!isset($conn)) {
        die("❌ Could not connect to the database. Please try again later.");
    }

    // Sanitize & validate input
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if any field is empty
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "❌ All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "❌ Invalid email format.";
    } elseif ($password !== $confirm_password) {
        $error_message = "❌ Passwords do not match.";
    } else {
        // Check password strength
        $password_pattern = "/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$%^&*])[A-Za-z\d@#$%^&*]{8,}$/";
        if (!preg_match($password_pattern, $password)) {
            $error_message = "❌ Password must be at least 8 characters long, include at least one uppercase letter, 
                              one lowercase letter, one number, and one special character (@#$%^&*).";
        } else {
            // Check if email is already registered
            $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $check_email->store_result();
            if ($check_email->num_rows > 0) {
                $error_message = "❌ Email is already registered.";
            } else {
                // Check if username is already taken
                $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
                $check_username->bind_param("s", $username);
                $check_username->execute();
                $check_username->store_result();
                if ($check_username->num_rows > 0) {
                    $error_message = "❌ Username is already taken.";
                } else {
                    // Hash password securely
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // Insert into database
                    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("sss", $username, $email, $hashed_password);

                    if ($stmt->execute()) {
                        echo "<div class='success-message'>✅ Registration successful! Redirecting...</div>";

                        // Redirect to login page after 3 seconds
                        echo "<script>
                                setTimeout(function() {
                                    window.location.href = 'login.php';
                                }, 3000);
                              </script>";

                        // Fallback PHP redirect
                        header("Location: login.php");
                        exit();
                    } else {
                        error_log("Database error: " . $stmt->error);  // Log detailed error
                        $error_message = "❌ Something went wrong, please try again.";
                    }

                    // Close the statement
                    $stmt->close();
                }
                $check_username->close(); // Close the username check statement
            }
            $check_email->close(); // Close the email check statement
        }
    }
}

// Close the MySQLi connection
$conn->close();
?>

<h2>Register A New Account</h2>

<!-- Registration Form -->
<form action="register.php" method="post" onsubmit="return validateForm()">
    <input type="text" name="username" placeholder="Username" required>
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" id="password" name="password" placeholder="Password" required onkeyup="checkPassword()">
    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
    <div id="password-hint" class="password-hint"></div>
    
    <!-- CSRF token hidden input -->
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

    <?php if (!empty($error_message)): ?>
        <div class='error-message'><?= $error_message ?></div>
    <?php endif; ?>
    
    <button type="submit">Register</button>
</form>

<!-- JavaScript for Password Validation -->
<script>
function checkPassword() {
    let password = document.getElementById("password").value;
    let hint = document.getElementById("password-hint");

    let pattern = /^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@#$%^&*])[A-Za-z\d@#$%^&*]{8,}$/;
    
    if (password.length < 8) {
        hint.innerHTML = "❌ Password must be at least 8 characters long.";
        hint.style.color = "red";
    } else if (!/[A-Z]/.test(password)) {
        hint.innerHTML = "❌ Include at least one uppercase letter.";
        hint.style.color = "red";
    } else if (!/[a-z]/.test(password)) {
        hint.innerHTML = "❌ Include at least one lowercase letter.";
        hint.style.color = "red";
    } else if (!/\d/.test(password)) {
        hint.innerHTML = "❌ Include at least one number.";
        hint.style.color = "red";
    } else if (!/[@#$%^&*]/.test(password)) {
        hint.innerHTML = "❌ Include at least one special character (@#$%^&*).";
        hint.style.color = "red";
    } else {
        hint.innerHTML = "✅ Strong password!";
        hint.style.color = "green";
    }
}

function validateForm() {
    let password = document.getElementById("password").value;
    let confirm_password = document.getElementById("confirm_password").value;

    if (password !== confirm_password) {
        alert("❌ Passwords do not match!");
        return false;
    }

    return true;
}
</script>

<?php include('footer.php'); ?>
