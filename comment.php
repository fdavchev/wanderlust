<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get and validate inputs
$post_id = filter_input(INPUT_POST, 'post_id', FILTER_VALIDATE_INT);
$comment = trim(filter_input(INPUT_POST, 'comment', FILTER_SANITIZE_STRING));
$user_id = $_SESSION['user_id'];

// Validate inputs
if (!$post_id || empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
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
        // Insert the comment
        $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, content, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("iis", $post_id, $user_id, $comment);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert comment");
        }

        // Get the username of the commenter
        $user_stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
        $user_stmt->bind_param("i", $user_id);
        $user_stmt->execute();
        $username = $user_stmt->get_result()->fetch_assoc()['username'];

        // Commit transaction
        $conn->commit();

        // Return success response with comment data
        echo json_encode([
            'success' => true,
            'message' => 'Comment added successfully',
            'data' => [
                'content' => htmlspecialchars($comment),
                'created_at' => date('M d, Y H:i'),
                'username' => $username
            ]
        ]);

    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($user_stmt)) {
        $user_stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
?>