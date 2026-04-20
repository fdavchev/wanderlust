<?php
session_start();
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'User not logged in'
    ]);
    exit();
}

// Database connection
$conn = createDatabaseConnection();

try {
    // Prepare data
    $user_id = $_SESSION['user_id'];
    $title = $conn->real_escape_string($_POST['title']);
    $content = $conn->real_escape_string($_POST['content']);
    $category_id = $conn->real_escape_string($_POST['category_id']);
    $location = $conn->real_escape_string($_POST['location'] ?? '');

    // Handle image upload
    $image_path = '';
    if (!empty($_FILES['image']['name'][0])) {
        $upload_dir = 'uploads/';
        
        // Create uploads directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate unique filename
        $filename = uniqid() . '_' . $_FILES['image']['name'][0];
        $target_path = $upload_dir . $filename;

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'][0], $target_path)) {
            $image_path = $target_path;
        }
    }

    // Prepare SQL statement
    $sql = "INSERT INTO posts (user_id, title, content, category_id, location, image_path, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssss', $user_id, $title, $content, $category_id, $location, $image_path);
    
    // Execute the statement
    if ($stmt->execute()) {
        // Get the ID of the newly inserted post
        $post_id = $stmt->insert_id;

        // Fetch category name
        $category_query = $conn->prepare("SELECT name FROM categories WHERE category_id = ?");
        $category_query->bind_param('s', $category_id);
        $category_query->execute();
        $category_result = $category_query->get_result();
        $category_name = $category_result->fetch_assoc()['name'];

        // Prepare response
        echo json_encode([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => [
                'post_id' => $post_id,
                'title' => $title,
                'content' => $content,
                'category' => $category_name,
                'location' => $location,
                'image_path' => $image_path
            ]
        ]);
    } else {
        throw new Exception("Failed to insert post: " . $stmt->error);
    }
} catch (Exception $e) {
    // Log the error (you might want to log to a file in a production environment)
    error_log($e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
} finally {
    // Close database connection
    $conn->close();
}
exit();
?>