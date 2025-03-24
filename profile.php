<?php
include('header.php');

// Check if the 'user' parameter is set in the URL (for another user's profile)
if (isset($_GET['user'])) {
    $username = $_GET['user']; // Fetch the username from the URL parameter
} else {
    // If no 'user' parameter is passed, redirect to the logged-in user's profile
    $username = $_SESSION['username'];
}

// Fetch the user info from the database using MySQLi
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql); // Prepare the SQL query
$stmt->bind_param("s", $username); // Bind the username parameter as a string
$stmt->execute(); // Execute the query
$result = $stmt->get_result(); // Get the result

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc(); // Fetch user data
} else {
    echo "User not found!";
    exit();
}

// Fetch the user's promotions using MySQLi
$sql = "SELECT * FROM promotions WHERE user_id = ?";
$stmt = $conn->prepare($sql); // Prepare the SQL query
$stmt->bind_param("i", $user['id']); // Bind the user_id parameter as an integer
$stmt->execute(); // Execute the query
$result = $stmt->get_result(); // Get the result

$promotions = [];
if ($result->num_rows > 0) {
    while ($promo = $result->fetch_assoc()) {
        $promotions[] = $promo; // Store each promotion in an array
    }
} else {
    echo "No promotions found!";
    exit();
}

// Check if the logged-in user is following this user
$following = false;
if (isset($_SESSION['user_id'])) {
    $logged_in_user_id = $_SESSION['user_id']; // Get the logged-in user's ID
    $sql = "SELECT * FROM follows WHERE follower_id = ? AND followed_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $logged_in_user_id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $following = true;
    }
}

// Handle follow/unfollow action
if (isset($_POST['follow'])) {
    if ($following) {
        // Unfollow the user
        $sql = "DELETE FROM follows WHERE follower_id = ? AND followed_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $logged_in_user_id, $user['id']);
        $stmt->execute();
        $following = false;
    } else {
        // Follow the user
        $sql = "INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $logged_in_user_id, $user['id']);
        $stmt->execute();
        $following = true;
    }
}

// Get the number of followers and following
$sql = "SELECT COUNT(*) AS followers_count FROM follows WHERE followed_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$followers_count = $result->fetch_assoc()['followers_count'];

$sql = "SELECT COUNT(*) AS following_count FROM follows WHERE follower_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user['id']);
$stmt->execute();
$result = $stmt->get_result();
$following_count = $result->fetch_assoc()['following_count'];

?>

<h2><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>'s Promotions:</h2>

<!-- Display Followers and Following Count -->
<div>
    <p><strong><?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?></strong> has:</p>
    <p><?php echo $followers_count; ?> Followers</p>
    <p><?php echo $following_count; ?> Following</p>
</div>

<div class="promotions">
<?php foreach ($promotions as $promo): ?>
    <div class="promotion">
        <div class="promotion-title">
            <a href="<?php echo htmlspecialchars($promo['website_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
                <h4><?php echo htmlspecialchars($promo['title'], ENT_QUOTES, 'UTF-8'); ?></h4>
            </a>
        </div>
        <div class="submitted-promotions-time">
            Created on: <?php echo date("F j, Y, g:i a", strtotime($promo['created_at'])); ?>
        </div>
        <div class="submitted-promotions-description">
            <p><?php echo htmlspecialchars($promo['description'], ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </div>
<?php endforeach; ?>
</div>

<!-- Follow/Unfollow Button -->
<?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $user['id']): ?>
    <form action="" method="POST">
        <button type="submit" name="follow">
            <?php echo $following ? 'Unfollow' : 'Follow'; ?>
        </button>
    </form>
<?php endif; ?>

<?php include('footer.php'); ?>
