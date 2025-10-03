<?php
session_start();

// Database connection
$host = "localhost";
$dbname = "medicare";
$username = "root";
$password = "";

$conn = new mysqli($host, $username, $password, $dbname);

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Get user by email
    $stmt = $conn->prepare("SELECT id, full_name, password FROM admin WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    // If email exists
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $fullName, $hashedPassword);
        $stmt->fetch();

        // Verify password
        if (password_verify($password, $hashedPassword)) {
            // Save session data
            $_SESSION['user_id'] = $id;
            $_SESSION['user_name'] = $fullName;
            $_SESSION['user_email'] = $email;

            // Redirect to PHP dashboard (uses session)
            header("Location: admin-dashboard.php");
            exit;
        } else {
            echo "❌ Invalid password!";
        }
    } else {
        echo "❌ No account found with that email!";
    }

    $stmt->close();
}

$conn->close();
