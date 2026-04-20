<?php
session_start();
require_once 'config.php';

// Initialize variables
$username = $email = $password = $confirm_password = '';
$errors = [];

try {
    // Get database connection
    $conn = createDatabaseConnection();

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize inputs
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $confirm_password = trim($_POST['confirm_password']);

        // Validate inputs
        if (empty($username)) {
            $errors[] = "Username is required";
        }

        if (empty($email)) {
            $errors[] = "Email is required";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (empty($password)) {
            $errors[] = "Password is required";
        } elseif (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters long";
        }

        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // Check if username or email already exists using prepared statement
        if (empty($errors)) {
            $check_stmt = $conn->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $check_stmt->bind_param("ss", $username, $email);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = "Username or email already exists";
            }
            $check_stmt->close();
        }

        // If no errors, proceed with registration
        if (empty($errors)) {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Prepare INSERT statement
            $insert_stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert_stmt->bind_param("sss", $username, $email, $hashed_password);

            // Execute the statement
            if ($insert_stmt->execute()) {
                // Set session variables
                $_SESSION['user_id'] = $insert_stmt->insert_id;
                $_SESSION['username'] = $username;
                
                // Redirect to home page
                header("Location: home.php");
                exit();
            } else {
                $errors[] = "Registration failed: " . $insert_stmt->error;
            }
            $insert_stmt->close();
        }
    }
} catch (Exception $e) {
    $errors[] = "An error occurred: " . $e->getMessage();
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/register.css">
</head>
<body>
    <div class="register-container">
        <h2>Create an Account</h2>
        
        <?php
        // Display errors
        if (!empty($errors)) {
            echo "<div class='error-box'>";
            foreach ($errors as $error) {
                echo "<p>• " . htmlspecialchars($error) . "</p>";
            }
            echo "</div>";
        }
        ?>
        
        <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <input type="text" name="username" placeholder="Username" required 
                       value="<?php echo htmlspecialchars($username); ?>">
            </div>
            
            <div class="form-group">
                <input type="email" name="email" placeholder="Email" required
                       value="<?php echo htmlspecialchars($email); ?>">
            </div>
            
            <div class="form-group">
                <input type="password" name="password" placeholder="Password" required>
            </div>
            
            <div class="form-group">
                <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            </div>
            
            <button type="submit" class="submit-btn">Register</button>
        </form>
        
        <div class="login-link">
            Already have an account? <a href="index.php">Login here</a>
        </div>
    </div>
</body>
</html>