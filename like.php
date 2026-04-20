<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to like posts']);
    exit;
}

// Get and validate the post_id
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$user_id = $_SESSION['user_id'];

if (!$post_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid post ID']);
    exit;
}

try {
    $conn = new mysqli('localhost', 'root', 'usbw', 'travel_blog');

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Check if the post exists
        $check_post = $conn->prepare("SELECT post_id FROM posts WHERE post_id = ?");
        $check_post->bind_param("i", $post_id);
        $check_post->execute();
        
        if ($check_post->get_result()->num_rows === 0) {
            throw new Exception("Post not found");
        }

        // Check if user already liked the post
        $check_like = $conn->prepare("SELECT like_id FROM likes WHERE post_id = ? AND user_id = ?");
        $check_like->bind_param("ii", $post_id, $user_id);
        $check_like->execute();
        $already_liked = $check_like->get_result()->num_rows > 0;

        if ($already_liked) {
            // Remove like
            $delete_like = $conn->prepare("DELETE FROM likes WHERE post_id = ? AND user_id = ?");
            $delete_like->bind_param("ii", $post_id, $user_id);
            $delete_like->execute();
            $action_taken = 'unliked';
        } else {
            // Add like
            $add_like = $conn->prepare("INSERT INTO likes (post_id, user_id, created_at) VALUES (?, ?, NOW())");
            $add_like->bind_param("ii", $post_id, $user_id);
            $add_like->execute();
            $action_taken = 'liked';
        }

        // Get updated like count
        $get_count = $conn->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = ?");
        $get_count->bind_param("i", $post_id);
        $get_count->execute();
        $likes_count = $get_count->get_result()->fetch_assoc()['count'];

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Post ' . $action_taken . ' successfully',
            'liked' => ($action_taken === 'liked'),
            'likes_count' => $likes_count
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error processing like: ' . $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>