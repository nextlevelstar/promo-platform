<?php  
include('header.php'); 
?>

<main>
    <div class="container">
        <?php

        // 🔥 Fetch Top 5 Most Liked Promotions
        $featured_query = "
            SELECT promotions.*, users.username, 
                   (SELECT COUNT(*) FROM promotion_likes WHERE promotion_id = promotions.id) AS like_count
            FROM promotions 
            JOIN users ON promotions.user_id = users.id 
            ORDER BY like_count DESC, promotions.created_at DESC 
            LIMIT 5
        ";
        $featured_result = mysqli_query($conn, $featured_query);

        if (mysqli_num_rows($featured_result) > 0) {
            echo '<h2>🔥 Featured Promotions (Most Liked)</h2>';
            echo '<div class="featured-promotions">';
            while ($row = mysqli_fetch_assoc($featured_result)) {
                $promotion_id = $row['id']; 
                $username = htmlspecialchars($row['username']);
                $title = htmlspecialchars($row['title']);
                $description = htmlspecialchars($row['description']);
                $like_count = $row['like_count'];

                echo '<div class="promotion featured">
                        <h3 class="promotion-title">
                            <a href="promotion.php?id=' . $promotion_id . '">' . $title . '</a>
                        </h3>
                        <p class="submitted-by">
                            <a href="profile.php?user=' . urlencode($username) . '">' . $username . '</a> - ' . format_date($row['created_at']) . '
                        </p>
                        <p class="promotion-description">' . $description . '</p>
                        <span class="like-count">❤️ ' . $like_count . '</span>
                    </div>';
            }
            echo '</div>'; // Close featured-promotions
        }

        // 🎯 Fetch and Display Promotions by Category
        $category_query = "SELECT * FROM categories ORDER BY id ASC";
        $category_result = mysqli_query($conn, $category_query);

        while ($category = mysqli_fetch_assoc($category_result)) {
            $category_id = $category['id'];
            $category_name = htmlspecialchars($category['name']);

            echo "<h2>$category_name</h2>"; // Category Title

            // Fetch promotions for this category using prepared statements
            $promotion_query = "
                SELECT promotions.*, users.username, 
                       (SELECT COUNT(*) FROM promotion_likes WHERE promotion_id = promotions.id) AS like_count,
                       (SELECT COUNT(*) FROM promotion_likes WHERE promotion_id = promotions.id AND user_id = ?) AS user_liked
                FROM promotions 
                JOIN users ON promotions.user_id = users.id 
                WHERE promotions.category_id = ? 
                ORDER BY promotions.created_at DESC
            ";
            if ($stmt = $conn->prepare($promotion_query)) {
                $stmt->bind_param("ii", $current_user_id, $category_id);
                $stmt->execute();
                $promotion_result = $stmt->get_result();

                if (mysqli_num_rows($promotion_result) > 0) {
                    echo '<div class="promotions">';
                    while ($row = mysqli_fetch_assoc($promotion_result)) {
                        $promotion_id = $row['id']; 
                        $username = htmlspecialchars($row['username']);
                        $title = htmlspecialchars($row['title']);
                        $description = htmlspecialchars($row['description']);
                        $like_count = $row['like_count'];
                        $user_liked = $row['user_liked'] > 0 ? 'liked' : ''; // Check if user liked

                        echo '<div class="promotion">
                                <h3 class="promotion-title">
                                    <a href="promotion.php?id=' . $promotion_id . '">' . $title . '</a>
                                </h3>
                                <p class="submitted-by">
                                    <a href="profile.php?user=' . urlencode($username) . '">' . $username . '</a> - ' . format_date($row['created_at']) . '
                                </p>
                                <p class="promotion-description">' . $description . '</p>
                                <button class="like-btn ' . $user_liked . '" data-id="' . $promotion_id . '">
                                    ❤️ <span class="like-count">' . $like_count . '</span>
                                </button>
                            </div>';
                    }
                    echo '</div>'; // Close category-promotions
                } else {
                    echo "<div class='no-promotions-message'><p>No promotions available in this category.</p></div>";
                }
                $stmt->close();
            }
        }
        ?>
    </div>
</main>

<?php include('footer.php'); ?>

<script>
// CSRF protection is not included here since we're assuming the like button triggers an AJAX request 
document.addEventListener("DOMContentLoaded", function() {
    document.querySelectorAll('.like-btn').forEach(button => {
        button.addEventListener('click', function() {
            let promotionId = this.getAttribute('data-id');
            let likeBtn = this;
            let likeCount = likeBtn.querySelector('.like-count');

            fetch('like.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'promotion_id=' + promotionId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    likeBtn.classList.toggle('liked');
                    likeCount.textContent = data.likes;
                }
            });
        });
    });
});
</script>

<style>
.featured-promotions {
    border: 1px solid;
    margin: 30px auto;
    width: 80%;
    box-shadow: 1px 1px 5px red;
}
.promotion.featured {
    border: 2px solid #ff9800;
    padding: 10px;
    margin-bottom: 10px;
    background: #fff3cd;
}
.like-btn {
    background: none;
    border: none;
    font-size: 16px;
    cursor: pointer;
}
.liked {
    color: red;
}
</style>

<?php
function format_date($datetime) {
    return date("F j, Y \a\\t g:i A", strtotime($datetime)); 
}
?>
