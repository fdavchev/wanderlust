<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get post ID from URL
$post_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$post_id) {
    header('Location: search.php');
    exit();
}

// Determine the referring page
$referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
$return_page = 'search.php';
$return_text = 'Back to Search';

if (strpos($referrer, 'bookmark_post.php') !== false) {
    $return_page = 'bookmark_post.php';
    $return_text = 'Back to Bookmarks';
}

// Updated query to check if post is bookmarked
$query = "
    SELECT 
        p.*, 
        u.username, 
        u.profile_picture,
        c.name AS category_name,
        CASE WHEN EXISTS (
            SELECT 1 FROM bookmarks b 
            WHERE b.post_id = p.post_id 
            AND b.user_id = ?
        ) THEN 1 ELSE 0 END as is_bookmarked
    FROM posts p
    JOIN users u ON p.user_id = u.user_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE p.post_id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $_SESSION['user_id'], $post_id);
$stmt->execute();
$result = $stmt->get_result();
$post = $result->fetch_assoc();

if (!$post) {
    header('Location: ' . $return_page);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($post['title']); ?> - Wanderlust</title>
    <link rel="stylesheet" href="css/viewposts.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="post-container">
            <div class="post-header">
                <div class="user-info">
                    <img src="<?php echo htmlspecialchars($post['profile_picture'] ?? 'default-avatar.png'); ?>" 
                         alt="Profile" 
                         class="profile-pic">
                    <div class="user-meta">
                        <h2><?php echo htmlspecialchars($post['username']); ?></h2>
                        <span class="post-date">Posted on <?php echo date('F j, Y', strtotime($post['created_at'])); ?></span>
                    </div>
                </div>
                <div class="header-actions">
                    <?php if ($_SESSION['user_id'] !== $post['user_id']): // Only show bookmark button if not the post author ?>
                        <button id="bookmarkBtn" class="bookmark-button <?php echo $post['is_bookmarked'] ? 'bookmarked' : ''; ?>"
                                onclick="toggleBookmark(<?php echo $post_id; ?>)">
                            <?php echo $post['is_bookmarked'] ? '🔖 Bookmarked' : '🔖 Bookmark'; ?>
                        </button>
                    <?php endif; ?>
                    <a href="<?php echo $return_page; ?>" class="back-button">← <?php echo $return_text; ?></a>
                </div>
            </div>

            <h1 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h1>

            <?php if (!empty($post['image_path'])): ?>
                <div class="post-image">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                         alt="Post image"
                         onerror="this.src='default-post-image.png'">
                </div>
            <?php endif; ?>

            <div class="post-content">
                <?php echo nl2br(htmlspecialchars($post['content'])); ?>
            </div>

            <div class="post-footer">
                <div class="post-meta">
                    <?php if (!empty($post['location'])): ?>
                        <span class="location">📍 <?php echo htmlspecialchars($post['location']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($post['category_name'])): ?>
                        <span class="category">📁 <?php echo htmlspecialchars($post['category_name']); ?></span>
                    <?php endif; ?>
                </div>
                <div class="post-info">
                    <span class="timestamp">Last updated: <?php echo date('M d, Y H:i', strtotime($post['updated_at'] ?? $post['created_at'])); ?></span>
                </div>
            </div>
        </div>
    </div>

    <script>
    function toggleBookmark(postId) {
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('toggle_bookmark.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const btn = document.getElementById('bookmarkBtn');
            if (data.bookmarked) {
                btn.classList.add('bookmarked');
                btn.textContent = '🔖 Bookmarked';
            } else {
                btn.classList.remove('bookmarked');
                btn.textContent = '🔖 Bookmark';
            }
        } else {
            alert(data.message || 'Failed to toggle bookmark');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating bookmark');
    });
}
    </script>
</body>
</html>

<?php
$stmt->close();
$conn->close();
?>