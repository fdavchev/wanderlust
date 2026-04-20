<?php
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

// Get POST data
$_POST = json_decode(file_get_contents('php://input'), true);

if (!isset($_POST['post_id'])) {
    echo json_encode(['success' => false, 'message' => 'No post ID provided']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];

// Delete the bookmark
$stmt = $conn->prepare("DELETE FROM bookmarks WHERE user_id = ? AND post_id = ?");
$stmt->bind_param("ii", $user_id, $post_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to remove bookmark']);
}

$stmt->close();
$conn->close();
?>