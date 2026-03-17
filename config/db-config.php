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
 * Start a database transaction.
 * @param mysqli $conn Database connection object
 * @throws RuntimeException if the transaction cannot be started
 */
function beginDBTransaction($conn)
{
    if (!$conn->begin_transaction()) {
        throw new RuntimeException("Failed to start database transaction: " . $conn->error, (int) $conn->errno);
    }
}

/**
 * Commit a database transaction.
 * @param mysqli $conn Database connection object
 * @throws RuntimeException if the transaction cannot be committed
 */
function commitDBTransaction($conn)
{
    if (!$conn->commit()) {
        throw new RuntimeException("Failed to commit database transaction: " . $conn->error, (int) $conn->errno);
    }
}

/**
 * Roll back a database transaction.
 * @param mysqli $conn Database connection object
 */
function rollbackDBTransaction($conn)
{
    if ($conn) {
        $conn->rollback();
    }
}

/**
 * Prepare a database statement or throw with the SQL error.
 * @param mysqli $conn Database connection object
 * @param string $query SQL query to prepare
 * @return mysqli_stmt
 * @throws RuntimeException if preparation fails
 */
function prepareDBStatement($conn, $query)
{
    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new RuntimeException("Database statement preparation failed: " . $conn->error, (int) $conn->errno);
    }

    return $stmt;
}

/**
 * Execute a prepared statement or throw with the SQL error.
 * @param mysqli_stmt $stmt Prepared statement
 * @return mysqli_stmt
 * @throws RuntimeException if execution fails
 */
function executeDBStatement($stmt)
{
    if (!$stmt->execute()) {
        throw new RuntimeException("Database statement execution failed: " . $stmt->error, (int) $stmt->errno);
    }

    return $stmt;
}

/**
 * Build a unique key representing an active appointment slot.
 * @param int|string $doctorId Doctor identifier
 * @param string $date Appointment date in YYYY-MM-DD format
 * @param string $time Appointment time in HH:MM[:SS] format
 * @return string
 */
function buildAppointmentSlotKey($doctorId, $date, $time)
{
    return (string) $doctorId . '|' . $date . '|' . $time;
}

/**
 * Determine whether an exception came from a duplicate-key violation.
 * @param Throwable $error
 * @return bool
 */
function isDuplicateKeyException($error)
{
    $message = $error->getMessage();

    return (int) $error->getCode() === 1062
        || stripos($message, 'Duplicate entry') !== false
        || stripos($message, 'duplicate') !== false;
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
    $stmt = prepareDBStatement(
        $conn,
        "INSERT INTO activity_logs (user_id, user_name, user_role, action, description, ip_address) VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssss", $userId, $userName, $userRole, $action, $description, $ip);
    executeDBStatement($stmt);
    $stmt->close();
}
