<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Start patient-specific session
startSession('patient');

try {
    $conn = getDBConnection();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate inputs
        if (empty($email) || empty($password)) {
            header("Location: ../../public/login.html?error=" . urlencode("Please enter both email and password"));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../../public/login.html?error=" . urlencode("Please enter a valid email address"));
            exit;
        }

        // Get user by email
        $stmt = $conn->prepare("SELECT id, full_name, password FROM users WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error. Please try again later.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // If email exists
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullName, $hashedPassword);
            $stmt->fetch();

            // Verify password
            if ($hashedPassword && password_verify($password, $hashedPassword)) {
                // Save session data
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'patient';
                $_SESSION['login_time'] = time();

                // Redirect to PHP dashboard (uses session)
                header("Location: ../patient/patient-dashboard.php");
                exit;
            } else {
                header("Location: ../../public/login.html?error=" . urlencode("Incorrect password. Please try again or use 'Forgot Password'"));
                exit;
            }
        } else {
            header("Location: ../../public/login.html?error=" . urlencode("No account found with this email. Please register first"));
            exit;
        }

        $stmt->close();
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    header("Location: ../../public/login.html?error=" . urlencode("An error occurred. Please try again later"));
    exit;
}
