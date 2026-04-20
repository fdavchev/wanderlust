<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize search variables
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$category_id = isset($_GET['category_id']) ? trim($_GET['category_id']) : '';
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

// Base query
$sql = "SELECT 
            p.post_id,
            p.title, 
            p.content, 
            p.location, 
            p.image_path,
            p.created_at,
            u.username, 
            u.profile_picture,
            c.name AS category_name,
            c.category_id
        FROM posts p
        JOIN users u ON p.user_id = u.user_id
        JOIN categories c ON p.category_id = c.category_id
        WHERE 1=1";

$params = [];
$types = "";

// Add search conditions
if (!empty($location)) {
    $sql .= " AND (p.location LIKE ? OR p.title LIKE ? OR p.content LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
    $params[] = "%$location%";
    $types .= "sss";
}

if (!empty($category_id)) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

if (!empty($username)) {
    $sql .= " AND u.username LIKE ?";
    $params[] = "%$username%";
    $types .= "s";
}

$sql .= " ORDER BY p.created_at DESC";

// Fetch categories for the dropdown
$categories_query = "SELECT * FROM categories ORDER BY name";
$categories_result = $conn->query($categories_query);
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);

// Prepare and execute the search query
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Blog - Search</title>
    <link rel="stylesheet" href="searchh.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

    <div class="content">
        <div class="search-container">
            <h2>Search Posts</h2>
            <form method="GET" action="search.php" class="search-form">
                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" 
                           id="location" 
                           name="location" 
                           placeholder="Enter location..." 
                           value="<?php echo htmlspecialchars($location); ?>">
                </div>

                <div class="form-group">
                    <label for="category_id">Category:</label>
                    <select id="category_id" name="category_id">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['category_id']; ?>"
                                <?php echo $category_id == $category['category_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" 
                           id="username" 
                           name="username" 
                           placeholder="Enter username..." 
                           value="<?php echo htmlspecialchars($username); ?>">
                </div>

                <button type="submit" class="search-button">Search</button>
            </form>
        </div>

        <div class="search-results">
            <h3>Search Results <?php echo $result->num_rows ? "({$result->num_rows})" : ''; ?></h3>
            
            <?php if ($result->num_rows > 0): ?>
                <div class="posts-grid">
    <?php while ($post = $result->fetch_assoc()): ?>
        <div class="post-card" onclick="window.location.href='view_post.php?id=<?php echo $post['post_id']; ?>'" style="cursor: pointer;">
            <div class="post-header">
                <img src="<?php echo htmlspecialchars($post['profile_picture'] ?: 'default-avatar.png'); ?>" 
                     alt="Profile" 
                     class="user-avatar">
                <div class="post-meta">
                    <span class="username"><?php echo htmlspecialchars($post['username']); ?></span>
                    <span class="date"><?php echo date('M d, Y', strtotime($post['created_at'])); ?></span>
                </div>
            </div>

            <h3 class="post-title"><?php echo htmlspecialchars($post['title']); ?></h3>

            <?php if (!empty($post['image_path'])): ?>
                <div class="post-image">
                    <img src="<?php echo htmlspecialchars($post['image_path']); ?>" 
                         alt="Post image"
                         onerror="this.src='default-post-image.png'">
                </div>
            <?php endif; ?>

            <div class="post-content">
                <p><?php echo substr(htmlspecialchars($post['content']), 0, 150) . '...'; ?></p>
            </div>

            <div class="post-footer">
                <div class="meta-info">
                    <span class="location">📍 <?php echo htmlspecialchars($post['location']); ?></span>
                    <span class="category">📁 <?php echo htmlspecialchars($post['category_name']); ?></span>
                </div>
                <span class="view-more">View Post →</span>
            </div>
        </div>
    <?php endwhile; ?>
</div>
            <?php else: ?>
                <div class="no-results">
                    <p>No posts found matching your search criteria.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    // Make posts clickable
    document.querySelectorAll('.post-card').forEach(post => {
        post.addEventListener('click', function(e) {
            // Get the post ID from the onclick attribute
            const url = this.getAttribute('onclick').match(/'([^']+)'/)[1];
            window.location.href = url;
        });
    });

    // Reset button functionality
    const resetButton = document.createElement('button');
    resetButton.type = 'button';
    resetButton.className = 'reset-button';
    resetButton.textContent = 'Reset';
    resetButton.onclick = function() {
        window.location.href = 'search.php';
    };
    
    // Add reset button to form
    const searchButton = document.querySelector('.search-button');
    const buttonContainer = document.createElement('div');
    buttonContainer.className = 'button-group';
    buttonContainer.appendChild(searchButton.cloneNode(true));
    buttonContainer.appendChild(resetButton);
    searchButton.parentNode.replaceChild(buttonContainer, searchButton);
});

    </script>
</body>
</html>