<?php

// Database connection settings
$host     = "localhost";   // or your server name
$user     = "root";        // your DB username
$password = "";            // your DB password
$dbname   = "medicare";    // your DB name

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Only process POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Collect and sanitize form inputs
    $name      = htmlspecialchars(trim($_POST['name']));
    $email     = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone     = !empty($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : NULL;
    $service   = htmlspecialchars(trim($_POST['service']));
    $rating    = intval($_POST['rating']);
    $feedback  = htmlspecialchars(trim($_POST['feedback']));
    $newsletter = isset($_POST['newsletter']) ? 1 : 0;
    $privacy   = isset($_POST['privacy']) ? 1 : 0;

    // Validate required fields
    if (empty($name) || empty($email) || empty($service) || empty($rating) || empty($feedback) || !$privacy) {
        die("Please fill in all required fields and agree to the privacy policy.");
    }

    // Prepare SQL statement (avoids SQL injection)
    $stmt = $conn->prepare("
        INSERT INTO feedback (name, email, phone, service, rating, feedback, newsletter, privacy)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssissi", $name, $email, $phone, $service, $rating, $feedback, $newsletter, $privacy);

    if ($stmt->execute()) {
        header("Location: ../html/index.html");
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
