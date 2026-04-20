<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$conn = createDatabaseConnection();

// Get user data
$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("User not found");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="css/editprofile.css">

</head>
<body>
    <div class="container">
        <h1>Edit Profile</h1>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php 
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <div>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
            </div>

            <div>
                <label for="bio">Bio:</label>
                <textarea id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
            </div>

            <?php if (!empty($user['profile_picture'])): ?>
                <div>
                    <p>Current Profile Picture:</p>
                    <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile Picture" class="profile-picture">
                </div>
            <?php endif; ?>

            <div>
                <label for="profile_picture">New Profile Picture:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
            </div>

            <button type="submit">Update Profile</button>
        </form>

        <div class="back-to-profile">
    <a href="profile.php">Back to Profile</a></div>
    </div>
</body>
</html>