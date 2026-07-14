<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';
require_once '../includes/RateLimiter.php';

// Start doctor-specific session
startSession('doctor');

try {
    $conn = getDBConnection();

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // --- Rate Limiting Check (5 attempts, 15 min lockout) ---
        $rateLimiter = new RateLimiter($conn, 5, 15);
        if ($rateLimiter->isLockedOut($ip)) {
            header("Location: ../../public/doctor-login.html?error=" . urlencode("Too many failed attempts. Your IP has been locked out for 15 minutes."));
            exit;
        }

        // Validate inputs
        if (empty($email) || empty($password)) {
            header("Location: ../../public/doctor-login.html?error=" . urlencode("Please enter both email and password"));
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            header("Location: ../../public/doctor-login.html?error=" . urlencode("Please enter a valid email address"));
            exit;
        }

        // Get doctor by email
        $stmt = $conn->prepare("SELECT id, full_name, email, specialty, department, password, status FROM doctors WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error. Please try again later.");
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        // If email exists
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($id, $fullName, $emailDb, $specialty, $department, $hashedPassword, $status);
            $stmt->fetch();

            // Check if doctor account is active
            if ($status !== 'active') {
                $stmt->close();
                logActivity($conn, $id, $fullName, 'doctor', 'login_failed', 'Login attempt on inactive account');
                closeDBConnection($conn);
                header("Location: ../../public/doctor-login.html?error=" . urlencode("Your account is inactive. Please contact administration"));
                exit;
            }

            // Verify password
            if ($hashedPassword && password_verify($password, $hashedPassword)) {
                // Clear failed attempts on success
                $rateLimiter->clearAttempts($ip);

                // Save session data
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $fullName;
                $_SESSION['user_email'] = $emailDb;
                $_SESSION['user_role'] = 'doctor';
                $_SESSION['doctor_specialty'] = $specialty;
                $_SESSION['doctor_department'] = $department;
                $_SESSION['login_time'] = time();

                $stmt->close();
                logActivity($conn, $id, $fullName, 'doctor', 'login', 'Doctor logged in successfully');
                closeDBConnection($conn);

                // Redirect to doctor dashboard
                header("Location: doctor-dashboard.php");
                exit;
            } else {
                // Record failed attempt
                $rateLimiter->recordFailedAttempt($ip, $email);
                $stmt->close();
                logActivity($conn, $id, $fullName, 'doctor', 'login_failed', 'Failed login attempt - incorrect password');
                closeDBConnection($conn);
                header("Location: ../../public/doctor-login.html?error=" . urlencode("Incorrect password. Please try again"));
                exit;
            }
        } else {
            // Record failed attempt for unknown email too
            $rateLimiter->recordFailedAttempt($ip, $email);
            $stmt->close();
            logActivity($conn, 0, $email, 'doctor', 'login_failed', 'Failed login attempt - account not found');
            closeDBConnection($conn);
            header("Location: ../../public/doctor-login.html?error=" . urlencode("Doctor account not found. Please contact administration"));
            exit;
        }
    }

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Doctor login error: " . $e->getMessage());
    header("Location: ../../public/doctor-login.html?error=" . urlencode("An error occurred. Please try again later"));
    exit;
}
