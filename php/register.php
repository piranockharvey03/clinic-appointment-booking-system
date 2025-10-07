<?php
// Database connection settings
$host = "localhost";       // Change if needed
$dbname = "medicare";      // Your database name
$username = "root";        // Your DB username
$password = "";            // Your DB password

// Connect to DB
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input
    $fullName = trim($_POST['full-name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm-password'];

    // Check password match
    if ($password !== $confirmPassword) {
        die("❌ Passwords do not match!");
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Prepare & insert
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullName, $email, $phone, $hashedPassword);

    if ($stmt->execute()) {
        header('Location: ../html/login.html');
    } else {
        echo "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
