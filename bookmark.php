<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Database connection
$conn = createDatabaseConnection();

// Get the post ID from the request body
$data = json_decode(file_get_contents('php://input'), true);
$post_id = $data['postId'] ?? null;
$user_id = $_SESSION['user_id'];

if ($post_id) {
    // Check if the bookmark already exists
    $stmt = $conn->prepare("SELECT * FROM bookmarks WHERE user_id = ? AND post_id = ?");
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        // Insert new bookmark
        $stmt = $conn->prepare("INSERT INTO bookmarks (user_id, post_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $post_id);
        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to bookmark']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Post already bookmarked']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
}

$stmt->close();
$conn->close();
?>