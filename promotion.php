<?php 
// Include the header
include('header.php'); 

// Check if the promotion ID is provided and is numeric
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $promotion_id = $_GET['id'];

    // Fetch the promotion and category from the database using the promotion ID (MySQLi)
    $sql = "SELECT promotions.*, categories.name AS category_name 
            FROM promotions 
            JOIN categories ON promotions.category_id = categories.id 
            WHERE promotions.id = ?";
    $stmt = $conn->prepare($sql); // Prepare the SQL query

    // Bind the parameter (promotion ID)
    $stmt->bind_param("i", $promotion_id); // "i" denotes an integer parameter

    // Execute the query
    $stmt->execute();
    
    // Get the result
    $result = $stmt->get_result();

    // Check if a promotion was found
    if ($result->num_rows > 0) {
        $promotion = $result->fetch_assoc(); // Fetch the promotion data as an associative array
    } else {
        echo "Promotion not found!";
        exit;
    }

    // Close the statement
    $stmt->close();
} else {
    echo "Invalid promotion ID!";
    exit;
}
?>

<main>
    <div class="user-submitted-promotions-container">
        <div class="user-submitted-promotions">
            <div class="promotion-title">
                <h1><?php echo htmlspecialchars($promotion['title'], ENT_QUOTES, 'UTF-8'); ?></h1>
            </div>
            <div class="promotion-category">
                <h1><?php echo htmlspecialchars($promotion['category_name'], ENT_QUOTES, 'UTF-8'); ?></h1> <!-- Displaying the category name -->
            </div>
            <div class="submitted-promotions-time">
                Created on: <?php echo date("F j, Y, g:i a", strtotime($promotion['created_at'])); ?>
            </div>
            <div class="submitted-promotions-description">
                <p><?php echo htmlspecialchars($promotion['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
            <div class="submitted-promotions-url">
                <p><strong>Website:</strong> 
                    <?php 
                    // Validate URL before displaying it
                    if (filter_var($promotion['website_url'], FILTER_VALIDATE_URL)) {
                        echo '<a href="' . htmlspecialchars($promotion['website_url'], ENT_QUOTES, 'UTF-8') . '" target="_blank">';
                        echo htmlspecialchars($promotion['website_url'], ENT_QUOTES, 'UTF-8');
                        echo '</a>';
                    } else {
                        echo "Invalid URL";
                    }
                    ?>
                </p>
            </div>
        </div>
    </div>
</main>

<?php 
// Include the footer
include('footer.php'); 
?>
