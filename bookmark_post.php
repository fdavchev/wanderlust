<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user's bookmarked posts with all necessary information
$user_id = $_SESSION['user_id'];
$query = "
    SELECT 
        p.*, 
        u.username, 
        u.profile_picture,
        b.created_at AS bookmarked_at,  -- Changed from b.bookmarked_at to b.created_at
        c.name AS category_name 
    FROM bookmarks b
    JOIN posts p ON b.post_id = p.post_id
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE b.user_id = ?
    ORDER BY b.created_at DESC  -- Changed from b.bookmarked_at to b.created_at
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookmarks</title>
    <link rel="stylesheet" href="css/bookmark.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="main-content">
        <h1>My Bookmarks</h1>

        <?php if ($result->num_rows > 0): ?>
            <div class="bookmarks-grid">
                <?php while($bookmark = $result->fetch_assoc()): ?>
                    <div class="bookmark-card">
                        <div class="bookmark-header">
                            <div class="user-info">
                                <img src="<?php echo htmlspecialchars($bookmark['profile_picture'] ?? 'default-avatar.png'); ?>" 
                                     alt="Profile" 
                                     class="profile-pic">
                                <div>
                                    <span class="username"><?php echo htmlspecialchars($bookmark['username']); ?></span>
                                    <small class="date">Bookmarked: <?php echo date('M d, Y', strtotime($bookmark['bookmarked_at'])); ?></small>
                                </div>
                            </div>
                        </div>

                        <h3 class="post-title"><?php echo htmlspecialchars($bookmark['title']); ?></h3>

                        <?php if (!empty($bookmark['image_path'])): ?>
                            <div class="post-image">
                                <img src="<?php echo htmlspecialchars($bookmark['image_path']); ?>" 
                                     alt="Post image"
                                     onerror="this.onerror=null; this.src='default-post-image.png';">
                            </div>
                        <?php endif; ?>

                        <div class="post-content">
                            <p><?php echo htmlspecialchars(substr($bookmark['content'], 0, 150)) . '...'; ?></p>
                        </div>

                        <div class="post-meta">
                            <?php if (!empty($bookmark['location'])): ?>
                                <span class="location">📍 <?php echo htmlspecialchars($bookmark['location']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($bookmark['category_name'])): ?>
                                <span class="category">📁 <?php echo htmlspecialchars($bookmark['category_name']); ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="bookmark-actions">
                            <a href="view_post.php?id=<?php echo $bookmark['post_id']; ?>" class="btn btn-view">View Post</a>
                            <button onclick="removeBookmark(<?php echo $bookmark['post_id']; ?>)" class="btn btn-remove">
                                Remove Bookmark
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="no-bookmarks">
                <p>You haven't bookmarked any posts yet.</p>
                <a href="home.php" class="btn btn-primary">Explore Posts</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function removeBookmark(postId) {
        if (confirm('Are you sure you want to remove this bookmark?')) {
            fetch('remove_bookmark.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_id: postId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Simple page reload after successful removal
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to remove bookmark');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the bookmark');
            });
        }
    }
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>