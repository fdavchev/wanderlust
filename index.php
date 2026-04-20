<?php
session_start();

// Пренасочи ако корисникот е веќе најавен
//if (isset($_SESSION['user_id'])) {
  //  header("Location: home.php");
    //exit();
//}

// Поврзување со базата
$con = new mysqli("localhost", "root", "usbw", "travel_blog");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
}

// Иницијализација
$username = $password = '';
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = mysqli_real_escape_string($con, trim($_POST['username']));
    $password = trim($_POST['password']);

    if (empty($username)) {
        $errors[] = "Username is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        $stmt = $con->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];

                header("Location: home.php");
                exit();
            } else {
                $errors[] = "Invalid username or password";
            }
        } else {
            $errors[] = "Invalid username or password";
        }
        $stmt->close();
    }
}
$con->close();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/login.css">
</head>
<body>
    <div class="login-container">
        <h2>Welcome Back</h2>
        
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
                <input type="password" name="password" placeholder="Password" required>
            </div>

            <button type="submit" class="submit-btn">Login</button>
        </form>
        
        <div class="register-link">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </div>
</body>
</html>