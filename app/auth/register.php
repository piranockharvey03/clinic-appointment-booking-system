<?php
// Include database configuration
require_once 'db-config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Initialize response array
$response = ['success' => false, 'message' => ''];

try {
    // Get database connection
    $conn = getDBConnection();
    
    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitize and validate input
        $fullName = trim(filter_input(INPUT_POST, 'full-name', FILTER_SANITIZE_STRING));
        $email = strtolower(trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL)));
        $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING));
        $password = $_POST['password'];
        $confirmPassword = $_POST['confirm-password'];

        // Validate input
        if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
            throw new Exception("All fields are required");
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Invalid email format");
        }

        if (strlen($password) < 8) {
            throw new Exception("Password must be at least 8 characters long");
        }

        if ($password !== $confirmPassword) {
            throw new Exception("Passwords do not match");
        }

        // Check for existing email
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Email already registered. Please use a different email or try logging in.");
        }
        $stmt->close();

        // Check for existing phone number
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Phone number already registered. Please use a different number or contact support.");
        }
        $stmt->close();

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Prepare & insert
        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $fullName, $email, $phone, $hashedPassword);

        if ($stmt->execute()) {
            $response = [
                'success' => true,
                'message' => 'Registration successful! Redirecting to login...',
                'redirect' => '../html/login.html'
            ];
        } else {
            // Check for duplicate entry error
            if ($conn->errno == 1062) {
                // Extract the duplicate field from the error message
                if (strpos($conn->error, 'email') !== false) {
                    throw new Exception("Email already registered. Please use a different email or try logging in.");
                } elseif (strpos($conn->error, 'phone') !== false) {
                    throw new Exception("Phone number already registered. Please use a different number or contact support.");
                }
            }
            throw new Exception("Registration failed. Please try again later.");
        }
    } else {
        throw new Exception("Invalid request method");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    http_response_code(400); // Bad Request
} finally {
    // Close connections
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) closeDBConnection($conn);
    
    // Return JSON response
    echo json_encode($response);
    exit();
}
