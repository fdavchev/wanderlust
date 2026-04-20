<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit();
}

// Database connection
$conn = createDatabaseConnection();

// Fetch posts with user information
$query = "SELECT p.*, u.username, u.profile_picture, c.name AS category_name,
          (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) as likes,
          EXISTS(SELECT 1 FROM likes WHERE post_id = p.post_id AND user_id = ?) as user_liked
          FROM posts p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN categories c ON p.category_id = c.category_id
          ORDER BY p.created_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

// Fetch categories for dropdown
$categories_query = "SELECT * FROM categories";
$categories_result = $conn->query($categories_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Travel Blog</title>
    <link rel="stylesheet" href="css/home.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <link rel="stylesheet" href="css/sidebar.css">
</head>
<body>
<?php include 'sidebar.php'; ?>

<!-- Map Container in Sidebar -->
<div id="map-container" style="display:none; margin-top: 20px;">
    <button onclick="closeMap()" style="position: absolute; top: 10px; right: 10px; background-color: #ff4d4d; color: white; border: none; padding: 10px 20px; font-size: 16px; border-radius: 5px; cursor: pointer; transition: background-color 0.3s ease, transform 0.3s ease;">
        Close Map
    </button>
    <div id="map" style="width:100%; height:300px;"></div>
</div>

<div class="main-content">

    <div id="post-section">
        <div class="post-box">
            <form id="create-post-form" method="POST" enctype="multipart/form-data">
                <input type="text" name="title" placeholder="Post Title" required>
                <textarea name="content" placeholder="Describe your travel experience..." required></textarea>

                <label for="category_id">Category/Interest:</label>
                <select name="category_id" id="category_id" required>
                    <?php while ($category = $categories_result->fetch_assoc()): ?>
                        <option value="<?php echo $category['category_id']; ?>">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>

                <input type="hidden" id="location" name="location">

                <div class="actions">
                    <div>
                        <label for="image-upload" style="cursor: pointer;">📷</label>
                        <input type="file" id="image-upload" name="image[]" style="display: none;" accept="image/*" multiple>

                        <button type="button" onclick="showLocationPicker()">📍</button>
                    </div>
                    <button type="submit">Post</button>
                </div>
            </form>
        </div>

        <!-- Location Picker Map -->
        <div id="location-picker-map"></div>

       <!-- Posts Container -->
    <div class="posts">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="post" id="post-<?php echo $row['post_id']; ?>">
                <!-- Post Header -->
                <div class="post-header">
                    <div class="author-info">
                        <img src="<?php echo htmlspecialchars($row['profile_picture'] ?: 'default-avatar.png'); ?>" 
                             alt="Profile Picture" 
                             class="author-avatar">
                        <div class="author-details">
                            <span class="author-name"><?php echo htmlspecialchars($row['username']); ?></span>
                            <span class="post-date"><?php echo date('M d, Y H:i', strtotime($row['created_at'])); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Post Content -->
                <div class="post-content">
                    <h3 class="post-title"><?php echo htmlspecialchars($row['title']); ?></h3>
                    <?php if (!empty($row['image_path'])): ?>
                        <img src="<?php echo htmlspecialchars($row['image_path']); ?>" 
                             alt="Post Image" 
                             class="post-image">
                    <?php endif; ?>
                    <p class="post-text"><?php echo htmlspecialchars($row['content']); ?></p>
                    <div class="post-meta">
                        <span class="category">Category: <?php echo htmlspecialchars($row['category_name']); ?></span>
                        <?php if (!empty($row['location'])): ?>
                            <span class="location">📍 <?php echo htmlspecialchars($row['location']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Post Actions -->
                <div class="post-actions">
    <button onclick="likePost(<?php echo $row['post_id']; ?>)" class="action-button <?php echo $row['user_liked'] ? 'liked' : ''; ?>">
        <?php echo $row['user_liked'] ? '❤️' : '👍'; ?> 
        <span class="like-count"><?php echo (int)$row['likes']; ?></span>
    </button>
    <button onclick="toggleCommentForm(<?php echo $row['post_id']; ?>)" class="action-button comment-button">
        💬 <span class="comment-count">0</span>
    </button>
    <button onclick="bookmarkPost(<?php echo $row['post_id']; ?>)" class="action-button">
        🔖 Bookmark
    </button>
</div>

                <!-- Comments Section -->
                <div class="comments-section" id="comments-section-<?php echo $row['post_id']; ?>" style="display: none;">
                    <form class="comment-form" data-post-id="<?php echo $row['post_id']; ?>">
                        <textarea name="comment" placeholder="Add a comment..." required></textarea>
                        <button type="submit" class="submit-comment">Post Comment</button>
                    </form>
                    <div class="comments-list" id="comments-list-<?php echo $row['post_id']; ?>">
                        <!-- Comments will be loaded here -->
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Make posts clickable
    document.querySelectorAll('.post').forEach(post => {
        post.addEventListener('click', function(e) {
            // Don't trigger if clicking on a button or link
            if (!e.target.closest('button') && !e.target.closest('a')) {
                const postId = this.id.split('-')[1];
                window.location.href = `view_post.php?id=${postId}`;
            }
        });
    });
});
    // Image Upload Preview
    document.getElementById('image-upload').addEventListener('change', function(event) {
        const existingPreviews = document.querySelectorAll('.image-preview');
        existingPreviews.forEach(preview => preview.remove());

        Array.from(event.target.files).forEach(file => {
            const reader = new FileReader();

            reader.onload = function(e) {
                const previewContainer = document.createElement('div');
                previewContainer.className = 'image-preview';
                previewContainer.innerHTML = `<img src="${e.target.result}" style="max-width:200px; max-height:200px; margin:10px; display:inline-block;">`;

                const actionsDiv = document.querySelector('.actions');
                actionsDiv.parentNode.insertBefore(previewContainer, actionsDiv);
            }

            reader.readAsDataURL(file);
        });
    });

    // Location Picker
    function showLocationPicker() {
        const mapContainer = document.getElementById('location-picker-map');
        mapContainer.style.display = 'block';
        mapContainer.style.height = '400px';
        mapContainer.style.width = '100%';

        setTimeout(initLocationPicker, 100);
    }

    function initLocationPicker() {
        const map = L.map('location-picker-map').setView([41.9973, 21.4280], 8);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(map);

        const marker = L.marker([41.9973, 21.4280], {
            draggable: true
        }).addTo(map);

        const confirmButton = document.createElement('button');
        confirmButton.textContent = 'Confirm Location';
        confirmButton.style.backgroundColor = 'green';
        confirmButton.style.color = 'white';
        confirmButton.style.padding = '10px';
        confirmButton.style.border = 'none';
        confirmButton.style.borderRadius = '5px';
        confirmButton.style.cursor = 'pointer';

        confirmButton.onclick = function() {
            const currentLocation = marker.getLatLng();

            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${currentLocation.lat}&lon=${currentLocation.lng}`)
                .then(response => response.json())
                .then(data => {
                    const locationName = data.display_name || `${currentLocation.lat.toFixed(4)}, ${currentLocation.lng.toFixed(4)}`;

                    const existingLocationPreview = document.querySelector('.location-preview');
                    if (existingLocationPreview) {
                        existingLocationPreview.remove();
                    }

                    const locationPreview = document.createElement('div');
                    locationPreview.className = 'location-preview';
                    locationPreview.innerHTML = `<strong>Confirmed Location:</strong> ${locationName}`;
                    locationPreview.style.margin = '10px 0';

                    const actionsDiv = document.querySelector('.actions');
                    actionsDiv.parentNode.insertBefore(locationPreview, actionsDiv);

                    document.getElementById('location').value = locationName;
                    document.getElementById('location-picker-map').style.display = 'none';
                });
        };

        const buttonContainer = document.createElement('div');
        buttonContainer.style.position = 'absolute';
        buttonContainer.style.bottom = '10px';
        buttonContainer.style.left = '50%';
        buttonContainer.style.transform = 'translateX(-50%)';
        buttonContainer.style.zIndex = '1000';

        buttonContainer.appendChild(confirmButton);
        document.getElementById('location-picker-map').appendChild(buttonContainer);

        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
        });
    }

    // Map Functions
    function toggleMap() {
        const mapContainer = document.getElementById('map-container');
        if (mapContainer.style.display === 'none') {
            mapContainer.style.display = 'block';
            initMap(); // Initialize the map when it's shown
        } else {
            mapContainer.style.display = 'none';
            destroyMap(); // Destroy map when it's hidden to prevent it from running in the background
        }
    }

    function closeMap() {
        document.getElementById('map-container').style.display = 'none';
        destroyMap(); // Optionally destroy the map when closed to release resources
    }

    // Global variable to hold the map instance
    let map;

    function initMap() {
        if (!map) {
            const location = { lat: 41.9981, lng: 21.4254 };
            map = L.map('map').setView([location.lat, location.lng], 8);

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; OpenStreetMap contributors'
            }).addTo(map);

            L.marker([location.lat, location.lng]).addTo(map);
        }
    }

    // Function to destroy the map instance when it is closed
    function destroyMap() {
        if (map) {
            map.remove(); // This removes the map instance and all its layers
            map = null; // Set the map variable to null to ensure it's re-initialized next time
        }
    }

    function logout() {
        window.location.href = 'logout.php';
    }

    // Post Submission
    document.getElementById('create-post-form').addEventListener('submit', async (e) => { 
        e.preventDefault();
        const form = e.target;
        const formData = new FormData(form);

        try {
            const response = await fetch('post.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                const postSection = document.querySelector('.posts');
                const newPost = document.createElement('div');
                newPost.classList.add('post');
                
                // Create post HTML with image handling
                let postHTML = `
                    <div>
                        <div class="author">You</div>
                        ${result.data.image_path ? `<img src="${result.data.image_path}" alt="Post Image" class="post-image">` : ''}
                        <div class="title"><strong>${result.data.title}</strong></div>
                        <div class="content">${result.data.content}</div>
                        <div class="category">Category: ${result.data.category}</div>
                        <div class="location">Location: ${result.data.location}</div>
                    </div>
                    <div class="post-actions">
                        <button onclick="likePost(${result.data.post_id})">👍 Like</button>
                        <button onclick="commentPost(${result.data.post_id})">💬 Comment</button>
                        <button onclick="bookmarkPost(${result.data.post_id})">🔖 Bookmark</button>
                    </div>
                `;

                newPost.innerHTML = postHTML;
                postSection.prepend(newPost);
                form.reset();
                
                // Clear image previews
                const previews = document.querySelectorAll('.image-preview, .location-preview');
                previews.forEach(preview => preview.remove());
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while creating the post.');
        }
    });

    // Placeholder functions for post interactions
    function likePost(postId) {
    const formData = new FormData();
    formData.append('post_id', postId);

    fetch('like.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update the like count display
            const likeCountElement = document.querySelector(`#post-${postId} .like-count`);
            if (likeCountElement) {
                likeCountElement.textContent = data.likes_count;
            }

            // Update the like button appearance
            const likeButton = document.querySelector(`#post-${postId} .action-button`);
            if (likeButton) {
                if (data.liked) {
                    likeButton.innerHTML = `❤️ <span class="like-count">${data.likes_count}</span>`;
                    likeButton.classList.add('liked');
                } else {
                    likeButton.innerHTML = `👍 <span class="like-count">${data.likes_count}</span>`;
                    likeButton.classList.remove('liked');
                }
            }
        } else {
            console.error('Like failed:', data.message);
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to process like');
    });
}
    document.addEventListener("DOMContentLoaded", function () {
    // Handle comment form submissions
    document.body.addEventListener("submit", function (event) {
        if (event.target.classList.contains("comment-form")) {
            event.preventDefault();

            const form = event.target;
            const postId = form.getAttribute("data-post-id");
            const textarea = form.querySelector("textarea");
            const commentText = textarea.value.trim();

            if (!commentText) {
                alert("Comment cannot be empty!");
                return;
            }

            fetch("comment.php", {
                method: "POST",
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    post_id: postId,
                    comment: commentText
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Create new comment element
                    const commentsList = document.getElementById(`comments-list-${postId}`);
                    const newComment = document.createElement("div");
                    newComment.classList.add("comment");
                    newComment.innerHTML = `
                        <div class="comment-content">
                            <div>
                                <strong>${data.data.username}:</strong> 
                                ${data.data.content}
                            </div>
                            <small class="comment-date">${data.data.created_at}</small>
                        </div>
                    `;
                    
                    // Add the new comment to the top of the list
                    commentsList.insertBefore(newComment, commentsList.firstChild);
                    
                    // Clear the textarea
                    textarea.value = "";
                    
                    // Update comment count
                    const countElement = document.querySelector(`#post-${postId} .comment-count`);
                    if (countElement) {
                        const currentCount = parseInt(countElement.textContent) || 0;
                        countElement.textContent = currentCount + 1;
                    }
                } else {
                    alert(data.message || "Error posting comment");
                }
            })
            .catch(error => {
                console.error("Error:", error);
                alert("Failed to post comment");
            });
        }
    });
});

// Function to toggle comment section visibility
function toggleCommentForm(postId) {
    const commentSection = document.getElementById(`comments-section-${postId}`);
    if (commentSection) {
        const isHidden = commentSection.style.display === 'none';
        commentSection.style.display = isHidden ? 'block' : 'none';
    }
}
    function bookmarkPost(postId) {
        console.log('Attempting to bookmark post:', postId);

        fetch('bookmark.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ postId: postId }), // Use postId directly
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                alert('Post bookmarked successfully!');
            } else {
                alert(data.message || 'Failed to bookmark the post');
            }
        })
        .catch(error => {
            console.error('There was a problem with the fetch operation:', error);
        });
    }
</script>
</body>
</html>

<?php
// Close database connection
$conn->close();
?>