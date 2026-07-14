<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Set JSON response header
header('Content-Type: application/json');

try {
    $conn = getDBConnection();

    // Only process POST requests
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Collect and sanitize form inputs
        $name      = htmlspecialchars(trim($_POST['name'] ?? ''));
        $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone     = !empty($_POST['phone']) ? htmlspecialchars(trim($_POST['phone'])) : NULL;
        $service   = htmlspecialchars(trim($_POST['service'] ?? ''));
        $rating    = intval($_POST['rating'] ?? 0);
        $feedback  = htmlspecialchars(trim($_POST['feedback'] ?? ''));
        $newsletter = isset($_POST['newsletter']) ? 1 : 0;
        $privacy   = isset($_POST['privacy']) ? 1 : 0;

        // Validate required fields
        if (empty($name)) {
            throw new Exception("Please enter your name");
        }
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address");
        }
        if (empty($service)) {
            throw new Exception("Please select a service");
        }
        if ($rating < 1 || $rating > 5) {
            throw new Exception("Please provide a rating between 1 and 5");
        }
        if (empty($feedback)) {
            throw new Exception("Please provide your feedback");
        }
        if (!$privacy) {
            throw new Exception("You must agree to the privacy policy to submit feedback");
        }

        // Prepare SQL statement (avoids SQL injection)
        $stmt = $conn->prepare("
            INSERT INTO feedback (name, email, phone, service, rating, feedback, newsletter, privacy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt === false) {
            throw new Exception("Failed to prepare statement");
        }

        $stmt->bind_param("ssssissi", $name, $email, $phone, $service, $rating, $feedback, $newsletter, $privacy);

        if ($stmt->execute()) {
            $stmt->close();
            closeDBConnection($conn);
            // Return JSON success response
            echo json_encode(['success' => true, 'message' => 'Thank you for your feedback!']);
            exit();
        } else {
            throw new Exception("Failed to submit feedback. Please try again.");
        }
    } else {
        throw new Exception("Invalid request method");
    }
} catch (Exception $e) {
    error_log("Feedback submission error: " . $e->getMessage());

    // Close connections if they exist
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) closeDBConnection($conn);

    // Return JSON error response
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit();
}
