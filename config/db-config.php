<?php

/**
 * Database Configuration File
 * Centralized database connection for the MediCare Clinic system
 */

// Database connection settings
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'medicare');

/**
 * Get database connection
 * @return mysqli Database connection object
 * @throws Exception if connection fails
 */
function getDBConnection()
{
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    if ($conn->connect_error) {
        error_log("Database connection failed: " . $conn->connect_error);
        throw new Exception("Database connection failed. Please try again later.");
    }

    $conn->set_charset("utf8mb4");
    return $conn;
}

/**
 * Close database connection
 * @param mysqli $conn Database connection object
 */
function closeDBConnection($conn)
{
    if ($conn) {
        $conn->close();
    }
}

/**
 * Log a doctor (or other user) action to the activity_logs table.
 * @param mysqli $conn   Open database connection
 * @param int    $userId  User's ID (0 for unknown/unauthenticated)
 * @param string $userName User's display name or email
 * @param string $userRole Role (e.g. 'doctor', 'admin', 'patient')
 * @param string $action  Short action key (e.g. 'login', 'approve_appointment')
 * @param string|null $description Human-readable detail
 */
function logActivity($conn, $userId, $userName, $userRole, $action, $description = null)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (user_id, user_name, user_role, action, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)"
    );
    if ($stmt) {
        $stmt->bind_param("isssss", $userId, $userName, $userRole, $action, $description, $ip);
        $stmt->execute();
        $stmt->close();
    }
}
