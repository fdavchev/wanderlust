<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: edit_profile.php");
    exit();
}

try {
    $conn = createDatabaseConnection();
    
    $user_id = $_SESSION['user_id'];
    $username = trim($_POST['username']);
    $bio = trim($_POST['bio']);
    
    // Validate username
    if (empty($username)) {
        throw new Exception("Username cannot be empty");
    }

    // Check if username is taken by another user
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
    $stmt->bind_param("si", $username, $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        throw new Exception("Username already taken");
    }

    // Handle profile picture upload
    $profile_picture_path = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        
        // Create directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_info = pathinfo($_FILES['profile_picture']['name']);
        $file_extension = strtolower($file_info['extension']);
        
        // Validate file type
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception("Invalid file type. Allowed types: " . implode(', ', $allowed_types));
        }

        // Generate unique filename
        $new_filename = uniqid('profile_') . '.' . $file_extension;
        $upload_path = $upload_dir . $new_filename;

        // Move uploaded file
        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
            throw new Exception("Failed to upload profile picture");
        }

        $profile_picture_path = $upload_path;
    }

    // Update user profile
    if ($profile_picture_path) {
        $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ?, profile_picture = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $username, $bio, $profile_picture_path, $user_id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET username = ?, bio = ? WHERE user_id = ?");
        $stmt->bind_param("ssi", $username, $bio, $user_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update profile");
    }

    $_SESSION['success'] = "Profile updated successfully!";
    $_SESSION['username'] = $username; // Update session username
    header("Location: profile.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    header("Location: edit_profile.php");
    exit();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>