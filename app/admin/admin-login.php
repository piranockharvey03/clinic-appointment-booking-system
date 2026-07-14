<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';
require_once '../includes/RateLimiter.php';

// Start admin-specific session
startSession('admin');

try {
    $conn = getDBConnection();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // --- Rate Limiting Check (stricter: 5 attempts, 30 min lockout for admin) ---
        $rateLimiter = new RateLimiter($conn, 5, 30);
        if ($rateLimiter->isLockedOut($ip)) {
            header("Location: ../../public/admin-login.html?error=" . urlencode("Too many failed attempts. Your IP has been locked out for 30 minutes."));
            exit;
        }

        // Validate inputs
        if (empty($email) || empty($password)) {
            header("Location: ../../public/admin-login.html?error=" . urlencode("Please enter both email and password"));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../../public/admin-login.html?error=" . urlencode("Please enter a valid email address"));
            exit;
        }

        // Get admin by email
        $stmt = $conn->prepare("SELECT id, full_name, password FROM admin WHERE email = ?");
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
                // Clear failed attempts on success
                $rateLimiter->clearAttempts($ip);

                // Save session data
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $email;
                $_SESSION['user_role'] = 'admin';
                $_SESSION['login_time'] = time();

                $stmt->close();
                // Redirect to admin dashboard
                header("Location: new-admin-dashboard.php");
                exit;
            } else {
                // Record failed attempt
                $rateLimiter->recordFailedAttempt($ip, $email);
                $stmt->close();
                header("Location: ../../public/admin-login.html?error=" . urlencode("Incorrect password. Please try again"));
                exit;
            }
        } else {
            // Record failed attempt for unknown email too
            $rateLimiter->recordFailedAttempt($ip, $email);
            $stmt->close();
            header("Location: ../../public/admin-login.html?error=" . urlencode("Administrator account not found. Please contact support"));
            exit;
        }
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Admin login error: " . $e->getMessage());
    header("Location: ../../public/admin-login.html?error=" . urlencode("An error occurred. Please try again later"));
    exit;
}
