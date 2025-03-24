<?php 
// Include the database connection file
include('header.php');

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "Please log in first!";
    exit();
}

$user_id = $_SESSION['user_id']; // Get the logged-in user's ID

// CSRF protection: Generate a CSRF token for the session
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Create a secure CSRF token
}

// Process single promotion deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_promotion'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch!');
    }

    $promotion_id = (int)$_POST['promotion_id']; // Cast to integer for security
    $delete_query = "DELETE FROM promotions WHERE id = ? AND user_id = ?";

    if ($stmt = $conn->prepare($delete_query)) {
        $stmt->bind_param("ii", $promotion_id, $user_id);

        if ($stmt->execute()) {
            echo "<div class='success-message'>Promotion deleted successfully!</div>";
        } else {
            error_log("Error deleting promotion with ID $promotion_id for user $user_id"); // Log errors for further inspection
            echo "<div class='error-message'>Error deleting promotion.</div>";
        }
        $stmt->close();
    }
}

// Process promotion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_promotion'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch!');
    }

    // Get the form data
    $title = trim($_POST['title']);
    $category_id = (int)$_POST['category'];
    $description = trim($_POST['description']);
    $website_url = trim($_POST['website_url']);

    // Validate the data before inserting it
    if (empty($title) || empty($website_url)) {
        echo "<div class='error-message'>Please fill in all required fields.</div>";
    } else {
        // Prepare and insert the promotion into the database
        $insert_query = "INSERT INTO promotions (user_id, title, category_id, description, website_url) VALUES (?, ?, ?, ?, ?)";

        if ($stmt = $conn->prepare($insert_query)) {
            $stmt->bind_param("issss", $user_id, $title, $category_id, $description, $website_url);

            if ($stmt->execute()) {
                echo "<div class='success-message'>Promotion submitted successfully!</div>";
            } else {
                error_log("Error inserting promotion for user $user_id"); // Log errors for further inspection
                echo "<div class='error-message'>Error submitting promotion.</div>";
            }
            $stmt->close();
        }
    }
}

?>

<h2>Your Promotions</h2>

<!-- Promotions Table -->
<div class="table-container">
    <table>
        <thead>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Description</th>
                <th>Website</th>
                <th>Likes</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Query to fetch promotions and like counts
            $query = "SELECT promotions.*, categories.name AS category_name, 
                         (SELECT COUNT(*) FROM promotion_likes WHERE promotion_id = promotions.id) AS likes_count 
                      FROM promotions 
                      JOIN categories ON promotions.category_id = categories.id 
                      WHERE promotions.user_id = ?";

            if ($stmt = $conn->prepare($query)) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                while ($promotion = $result->fetch_assoc()) {
                    // Safely display the data
                    echo "<tr>
                            <td data-label='Title'>" . htmlspecialchars($promotion['title'], ENT_QUOTES, 'UTF-8') . "</td>
                            <td data-label='Category'>" . htmlspecialchars($promotion['category_name'], ENT_QUOTES, 'UTF-8') . "</td>
                            <td data-label='Description'>" . htmlspecialchars($promotion['description'], ENT_QUOTES, 'UTF-8') . "</td>
                            <td data-label='Website'><a href='" . htmlspecialchars($promotion['website_url'], ENT_QUOTES, 'UTF-8') . "' target='_blank' rel='noopener noreferrer'>Visit</a></td>
                            <td data-label='Likes'>" . $promotion['likes_count'] . " ❤️</td>
                            <td data-label='Actions'>
                                <form method='POST'>
                                    <input type='hidden' name='csrf_token' value='" . $_SESSION['csrf_token'] . "'>
                                    <input type='hidden' name='promotion_id' value='" . $promotion['id'] . "'>
                                    <button type='submit' name='delete_promotion' onclick='return confirm(\"Are you sure you want to delete this promotion?\")'>Delete</button>
                                </form>
                            </td>
                        </tr>";
                }
                $stmt->close();
            } else {
                // Log any database query issues
                error_log("Error fetching promotions for user $user_id");
            }
            ?>
        </tbody>
    </table>
</div>

<?php

// Process delete all promotions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_all_promotions'])) {
    // CSRF Token Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token mismatch!');
    }

    $delete_all_query = "DELETE FROM promotions WHERE user_id = ?";

    if ($stmt = $conn->prepare($delete_all_query)) {
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            echo "<div class='success-message'>All promotions deleted successfully!</div>";
        } else {
            error_log("Error deleting all promotions for user $user_id"); // Log errors for further inspection
            echo "<div class='error-message'>Error deleting promotions.</div>";
        }
        $stmt->close();
    }
}

?>

<!-- Delete All Promotions Form -->
<h2>Your Promotions</h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <button type="submit" name="delete_all_promotions" onclick="return confirm('Are you sure you want to delete all your promotions? This action cannot be undone.')">
        🗑️ Delete All Promotions
    </button>
</form>

<!-- Submit Promotion Form -->
<h2>Submit Your Promotion</h2>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <label for="title">Title:</label>
    <input type="text" name="title" id="title" required><br>
    <label for="category">Category:</label>
    <select name="category" id="category" required>
        <?php
        // Ensure category selection is valid
        $categoryQuery = "SELECT * FROM categories ORDER BY name";
        $categoryResult = $conn->query($categoryQuery);
        if ($categoryResult->num_rows > 0) {
            while ($category = $categoryResult->fetch_assoc()) {
                echo "<option value='" . (int)$category['id'] . "'>" . htmlspecialchars($category['name'], ENT_QUOTES, 'UTF-8') . "</option>";
            }
        }
        ?>
    </select><br>
    <label for="description">Description:</label>
    <textarea name="description" id="description" required></textarea><br>
    <label for="website_url">Website URL:</label>
    <input type="url" name="website_url" id="website_url" required><br>
    <button type="submit" name="submit_promotion">Submit Promotion</button>
</form>

<?php include('footer.php'); ?>
