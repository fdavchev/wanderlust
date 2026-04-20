<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php?error=unauthorized');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');

// Check connection
if ($conn->connect_error) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// Fetch user data
$sql_user = "SELECT * FROM users WHERE user_id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param('i', $user_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();

if ($result_user->num_rows > 0) {
    $user = $result_user->fetch_assoc();
} else {
    echo "<p>Error: User not found.</p>";
    exit();
}

// Fetch user posts with category names
$sql_posts = "SELECT p.*, c.name as category_name 
              FROM posts p 
              LEFT JOIN categories c ON p.category_id = c.category_id 
              WHERE p.user_id = ? 
              ORDER BY p.created_at DESC";
$stmt_posts = $conn->prepare($sql_posts);
$stmt_posts->bind_param('i', $user_id);
$stmt_posts->execute();
$result_posts = $stmt_posts->get_result();

$posts = [];
if ($result_posts->num_rows > 0) {
    while ($row = $result_posts->fetch_assoc()) {
        $posts[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile</title>
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>

<body>
<?php include 'sidebar.php'; ?>
    <div class="container">
        <!-- Display Messages -->
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <!-- Action Buttons at the Top -->
        <div class="profile-actions">
            <a href="edit_profile.php" class="btn">Edit Profile</a>
        </div>

        <!-- Profile Header Section -->
        <div class="profile-header">
            <!-- Profile Picture -->
            <?php if (!empty($user['profile_picture'])): ?>
                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                     alt="Profile Picture" 
                     class="profile-picture"
                     onerror="this.src='default-profile.png'">
            <?php else: ?>
                <img src="default-profile.png" alt="Default Profile" class="profile-picture">
            <?php endif; ?>

            <!-- Profile Info -->
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email'] ?? 'Not provided'); ?></p>
                <?php if (!empty($user['bio'])): ?>
                    <p><strong>Bio:</strong> <?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                <?php else: ?>
                    <p><em>No bio available</em></p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Posts Section -->
        <div class="posts-section">
            <h2>My Posts</h2>
            
            <?php if (empty($posts)): ?>
                <p>You haven't made any posts yet.</p>
            <?php else: ?>
                <?php foreach ($posts as $post): ?>
                    <div class="post">
                        <div class="post-header">
                            <h3><?php echo htmlspecialchars($post['title']); ?></h3>
                            <small>Posted on <?php echo date('F j, Y, g:i a', strtotime($post['created_at'])); ?></small>
                        </div>

                        <?php if (!empty($post['image_path'])): ?>
                            <div class="post-image-container">
                                <?php 
                                $image_paths = explode(',', $post['image_path']);
                                foreach ($image_paths as $path): 
                                    if (!empty(trim($path))):
                                ?>
                                    <img src="<?php echo htmlspecialchars(trim($path)); ?>" 
                                         alt="Post Image" 
                                         class="post-image"
                                         onerror="this.style.display='none'">
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        <?php endif; ?>

                        <div class="post-content">
                            <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                        </div>

                        <div class="stats">
                            <?php if (!empty($post['location'])): ?>
                                <span class="stat-item">📍 <?php echo htmlspecialchars($post['location']); ?></span>
                            <?php endif; ?>
                            
                            <?php if (!empty($post['category_name'])): ?>
                                <span class="stat-item">📁 <?php echo htmlspecialchars($post['category_name']); ?></span>
                            <?php endif; ?>
                            
                            <span class="stat-item">👍 <?php echo (int)$post['likes']; ?> likes</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    // Handle image loading errors
    document.addEventListener('DOMContentLoaded', function() {
        const images = document.querySelectorAll('img');
        images.forEach(img => {
            img.addEventListener('error', function() {
                this.style.display = 'none';
                console.error('Failed to load image:', this.src);
            });
        });
    });
    </script>
</body>
</html>