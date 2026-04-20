<?php
define('host', 'localhost');
define('user', 'root');
define('password', 'usbw');
define('dbname', 'travel_blog');

// Create database connection
function createDatabaseConnection() {
    $conn = new mysqli(host, user, password, dbname);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function to sanitize input
function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

// Function to redirect
function redirect($location) {
    header("Location: $location");
    exit();
}
