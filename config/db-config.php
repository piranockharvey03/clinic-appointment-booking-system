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
 * Normalize appointment date for slot key usage.
 * @param string $date
 * @return string
 */
function normalizeAppointmentSlotDate($date)
{
    $raw = trim((string) $date);
    $timestamp = strtotime($raw);

    if ($timestamp === false) {
        return $raw;
    }

    return date('Y-m-d', $timestamp);
}

/**
 * Normalize appointment time for slot key usage.
 * @param string $time
 * @return string
 */
function normalizeAppointmentSlotTime($time)
{
    $raw = trim((string) $time);

    if ($raw === '') {
        return $raw;
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return $raw;
    }

    return date('H:i:s', $timestamp);
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
    $normalizedDate = normalizeAppointmentSlotDate($date);
    $normalizedTime = normalizeAppointmentSlotTime($time);

    return (string) $doctorId . '|' . $normalizedDate . '|' . $normalizedTime;
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
 * Automatically cancel approved appointments that missed check-in beyond a grace period.
 * Frees the slot and notifies patient + doctor once.
 * @param mysqli $conn Open database connection
 * @param int $graceMinutes Minutes allowed after appointment time before no-show cancellation
 * @return int Number of appointments auto-canceled as no-show
 */
function autoMarkNoShowAppointments($conn, $graceMinutes = 30)
{
    $grace = max(0, (int) $graceMinutes);
    $cutoff = date('Y-m-d H:i:s', time() - ($grace * 60));
    $processed = 0;

    $selectStmt = prepareDBStatement(
        $conn,
        "SELECT appointment_id, patient_id, patient_name, doctor_id, doctor_name, appointment_date, appointment_time
         FROM appointments
         WHERE status = 'approved'
           AND checked_in_at IS NULL
           AND TIMESTAMP(appointment_date, appointment_time) <= ?"
    );
    $selectStmt->bind_param('s', $cutoff);
    executeDBStatement($selectStmt);
    $result = $selectStmt->get_result();

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $apptId = $row['appointment_id'];

            $updateStmt = prepareDBStatement(
                $conn,
                "UPDATE appointments
                 SET status = 'canceled', cancel_reason = 'Patient no-show (auto)', booking_slot_key = NULL
                 WHERE appointment_id = ?
                   AND status = 'approved'
                   AND checked_in_at IS NULL
                   AND TIMESTAMP(appointment_date, appointment_time) <= ?"
            );
            $updateStmt->bind_param('ss', $apptId, $cutoff);
            executeDBStatement($updateStmt);

            $affected = $updateStmt->affected_rows;
            $updateStmt->close();

            if ($affected <= 0) {
                continue;
            }

            $processed++;

            if (!empty($row['patient_id'])) {
                $patientMessage = "Your appointment with Dr. " . $row['doctor_name'] . " on "
                    . $row['appointment_date'] . " at " . $row['appointment_time']
                    . " was automatically canceled because check-in was not completed in time.";
                $patientNotifStmt = prepareDBStatement(
                    $conn,
                    "INSERT INTO patient_notifications (patient_id, appointment_id, patient_name, notification_type, message)
                     VALUES (?, ?, ?, 'canceled', ?)"
                );
                $patientNotifStmt->bind_param('isss', $row['patient_id'], $apptId, $row['patient_name'], $patientMessage);
                executeDBStatement($patientNotifStmt);
                $patientNotifStmt->close();
            }

            $doctorId = (int) ($row['doctor_id'] ?? 0);
            if ($doctorId > 0) {
                $doctorMessage = "Auto no-show cancellation: " . $row['patient_name'] . " missed the check-in window for "
                    . $row['appointment_date'] . " at " . $row['appointment_time'] . ".";
                $doctorNotifStmt = prepareDBStatement(
                    $conn,
                    "INSERT INTO doctor_notifications (doctor_id, type, message, appointment_id)
                     VALUES (?, 'canceled', ?, ?)"
                );
                $doctorNotifStmt->bind_param('iss', $doctorId, $doctorMessage, $apptId);
                executeDBStatement($doctorNotifStmt);
                $doctorNotifStmt->close();
            }

            logActivity(
                $conn,
                0,
                'System',
                'system',
                'auto_no_show_cancel',
                "Auto-canceled no-show appointment {$apptId}"
            );
        }
        $result->free();
    }

    $selectStmt->close();
    return $processed;
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
