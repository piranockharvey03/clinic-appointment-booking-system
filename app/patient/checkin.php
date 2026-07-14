<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Start patient-specific session
startSession('patient');

// Must be a logged-in patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['appt_id'])) {
    header('Location: patient-appointments.php?tab=approved');
    exit;
}

$apptId    = trim($_POST['appt_id']);
$patientId = (int) $_SESSION['user_id'];
$conn      = null;

try {
    $conn = getDBConnection();
    beginDBTransaction($conn);

    // Keep no-show cleanup up to date before evaluating this check-in request.
    autoMarkNoShowAppointments($conn, 30);

    // Lock the row; verify ownership, status, and that it is today's appointment
    $stmt = prepareDBStatement(
        $conn,
        "SELECT id, appointment_date, status, cancel_reason, checked_in_at, patient_name, doctor_id, doctor_name
         FROM appointments
         WHERE appointment_id = ? AND patient_id = ?
         FOR UPDATE"
    );
    $stmt->bind_param('si', $apptId, $patientId);
    executeDBStatement($stmt);
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        throw new RuntimeException('Appointment not found.');
    }
    if ($row['status'] !== 'approved') {
        if ($row['status'] === 'canceled' && stripos((string)($row['cancel_reason'] ?? ''), 'no-show') !== false) {
            throw new RuntimeException('Check-in window has closed and this appointment was marked as no-show.');
        }
        throw new RuntimeException('Only approved appointments can be checked in.');
    }
    if ($row['checked_in_at'] !== null) {
        throw new RuntimeException('Already checked in.');
    }

    // Allow check-in only on the appointment date
    if ($row['appointment_date'] !== date('Y-m-d')) {
        throw new RuntimeException('Check-in is only available on the day of the appointment.');
    }

    // Generate a cryptographically secure 4-digit verification code
    $token = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

    $upd = prepareDBStatement(
        $conn,
        "UPDATE appointments
         SET checked_in_at = NOW(), checkin_token = ?, checked_in_by = 'patient'
         WHERE appointment_id = ? AND patient_id = ?"
    );
    $upd->bind_param('ssi', $token, $apptId, $patientId);
    executeDBStatement($upd);
    $upd->close();

    logActivity(
        $conn,
        $_SESSION['user_id'],
        $_SESSION['user_name'],
        'patient',
        'checkin_appointment',
        "Patient checked in for appointment {$apptId} with Dr. " . $row['doctor_name']
    );

    // Notify the doctor that the patient has arrived
    $doctorId = (int) $row['doctor_id'];
    $notifMsg = $row['patient_name'] . " has checked in for their appointment today. "
        . "Show them this verification code to confirm: " . $token . ".";
    $notifStmt = prepareDBStatement(
        $conn,
        "INSERT INTO doctor_notifications (doctor_id, type, message, appointment_id)
         VALUES (?, 'checked_in', ?, ?)"
    );
    $notifStmt->bind_param('iss', $doctorId, $notifMsg, $apptId);
    executeDBStatement($notifStmt);
    $notifStmt->close();

    commitDBTransaction($conn);
    closeDBConnection($conn);
} catch (Exception $e) {
    if ($conn) {
        rollbackDBTransaction($conn);
        closeDBConnection($conn);
    }
    error_log('Appointment check-in error: ' . $e->getMessage());
}

header('Location: patient-appointments.php?tab=approved');
exit;
