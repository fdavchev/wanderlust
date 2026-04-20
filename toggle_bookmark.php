<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

// Get the post_id from POST data
$post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
$user_id = $_SESSION['user_id'];

// Validate post_id
if ($post_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit();
}

// First verify that the post exists
$check_post = $conn->prepare("SELECT post_id FROM posts WHERE post_id = ?");
$check_post->bind_param("i", $post_id);
$check_post->execute();
$post_result = $check_post->get_result();

if ($post_result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Post not found']);
    $check_post->close();
    $conn->close();
    exit();
}
$check_post->close();

// Check if already bookmarked
$check_bookmark = $conn->prepare("SELECT bookmark_id FROM bookmarks WHERE user_id = ? AND post_id = ?");
$check_bookmark->bind_param("ii", $user_id, $post_id);
$check_bookmark->execute();
$bookmark_result = $check_bookmark->get_result();

if ($bookmark_result->num_rows > 0) {
    // Remove bookmark
    $delete = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $delete->bind_param("ii", $user_id, $post_id);
    
    if ($delete->execute()) {
        echo json_encode([
            'success' => true,
            'bookmarked' => false,
            'message' => 'Bookmark removed'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to remove bookmark'
        ]);
    }
    $delete->close();
} else {
    // Add bookmark
    $insert = $conn->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)");
    $insert->bind_param("ii", $user_id, $post_id);
    
    if ($insert->execute()) {
        echo json_encode([
            'success' => true,
            'bookmarked' => true,
            'message' => 'Post bookmarked'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to bookmark post'
        ]);
    }
    $insert->close();
}

$check_bookmark->close();
$conn->close();
?>