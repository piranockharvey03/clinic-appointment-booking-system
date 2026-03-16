<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Capture session data before destroying the session
$logUserId   = $_SESSION['user_id']   ?? 0;
$logUserName = $_SESSION['user_name'] ?? 'Unknown';

// Log the logout action
try {
    $conn = getDBConnection();
    logActivity($conn, $logUserId, $logUserName, 'doctor', 'logout', 'Doctor logged out');
    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("Logout logging error: " . $e->getMessage());
}

// Destroy all session data
$_SESSION = array();

// Delete the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

// Clear all cookies for this domain
if (isset($_SERVER['HTTP_COOKIE'])) {
    $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
    foreach ($cookies as $cookie) {
        $parts = explode('=', $cookie);
        $name = trim($parts[0]);
        setcookie($name, '', time() - 1000);
        setcookie($name, '', time() - 1000, '/');
    }
}

// Destroy the session
session_destroy();

// Clear browser cache headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Redirect to doctor login page with logout success parameter
header("Location: ../../public/doctor-login.html?logout=success");
exit;
