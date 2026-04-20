<?php
header('Content-Type: application/json');

// Поврзување со базата
$conn = new mysqli('localhost', 'root', '', 'travel_blog');
if ($conn->connect_error) {
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit();
}

// Читање на податоците од AJAX
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? null;
$post_id = $data['post_id'] ?? null;

if ($action && $post_id) {
    if ($action === 'like') {
        // Лајк акција
        $stmt = $conn->prepare("UPDATE posts SET likes = likes + 1 WHERE id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        echo json_encode(['success' => $stmt->affected_rows > 0]);
    } elseif ($action === 'comment') {
        // Коментар акција
        $comment = $data['comment'] ?? '';
        $stmt = $conn->prepare("INSERT INTO comments (post_id, content) VALUES (?, ?)");
        $stmt->bind_param("is", $post_id, $comment);
        $stmt->execute();
        echo json_encode(['success' => $stmt->affected_rows > 0]);
    } elseif ($action === 'bookmark') {
        // Буукмарк акција
        $stmt = $conn->prepare("INSERT INTO bookmarks (post_id) VALUES (?)");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        echo json_encode(['success' => $stmt->affected_rows > 0]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
}

$conn ->close();