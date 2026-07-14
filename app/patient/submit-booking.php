<?php
// Booking handler that stores appointments in database
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Start patient-specific session
startSession('patient');

// Check if user is logged in as patient
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    header('Location: ../../public/login.html');
    exit;
}

// Ensure request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../public/patient-book.php');
    exit;
}

// Collect and sanitize inputs
function get_post($key)
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : '';
}

$department = get_post('department');
$doctorId   = get_post('doctorId');
$date       = get_post('date');
$time       = get_post('time');
$reason     = get_post('reason');
$notes      = get_post('notes');
$phone      = get_post('phone');

// Basic validation
if (empty($department)) {
    header('Location: ../../public/patient-book.php?error=' . urlencode('Please select a department'));
    exit;
}

if (empty($doctorId)) {
    header('Location: ../../public/patient-book.php?error=' . urlencode('Please select a doctor'));
    exit;
}

if (empty($date)) {
    header('Location: ../../public/patient-book.php?error=' . urlencode('Please select an appointment date'));
    exit;
}

if (empty($time)) {
    header('Location: ../../public/patient-book.php?error=' . urlencode('Please select an appointment time'));
    exit;
}

// Validate date is not in the past
if (strtotime($date) < strtotime(date('Y-m-d'))) {
    header('Location: ../../public/patient-book.php?error=' . urlencode('Please select a future date for your appointment'));
    exit;
}

// Validate phone if provided
if (empty($phone)) {
    header('Location: ../../public/patient-book.php?error=' . urlencode('Please provide your phone number'));
    exit;
}

// Generate unique appointment ID
$appointmentId = uniqid('appt_', true);

// Get patient information from session (already verified above)
$patientId = $_SESSION['user_id'];
$patientName = $_SESSION['user_name'];

$conn = null;

try {
    $conn = getDBConnection();
    beginDBTransaction($conn);

    $doctorLookupId = (int) $doctorId;
    $docStmt = prepareDBStatement($conn, "SELECT id, full_name, specialty, photo FROM doctors WHERE id = ? AND status = 'active' FOR UPDATE");
    $docStmt->bind_param("i", $doctorLookupId);
    executeDBStatement($docStmt);
    $docResult = $docStmt->get_result();
    $docRow = $docResult ? $docResult->fetch_assoc() : null;
    $docResult?->free();
    $docStmt->close();

    if (!$docRow) {
        throw new RuntimeException('Selected doctor is not available. Please choose another doctor.');
    }

    $doctor = [
        'name' => $docRow['full_name'],
        'specialty' => $docRow['specialty'],
        'photo' => $docRow['photo'] ?? ''
    ];
    $slotKey = buildAppointmentSlotKey($doctorId, $date, $time);

    $stmt = prepareDBStatement($conn, "
        INSERT INTO appointments 
        (appointment_id, patient_id, patient_name, phone, department, doctor_id, 
         doctor_name, doctor_specialty, doctor_photo, appointment_date, appointment_time,
         booking_slot_key,
         reason, notes, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
    ");

    $stmt->bind_param(
        "sissssssssssss",
        $appointmentId,
        $patientId,
        $patientName,
        $phone,
        $department,
        $doctorId,
        $doctor['name'],
        $doctor['specialty'],
        $doctor['photo'],
        $date,
        $time,
        $slotKey,
        $reason,
        $notes
    );
    executeDBStatement($stmt);
    $stmt->close();

    $notificationMessage = "New appointment booked with " . $doctor['name'] . " on " . $date . " at " . $time;
    $notificationStmt = prepareDBStatement($conn, "
        INSERT INTO notifications 
        (type, message, appointment_id) 
        VALUES ('new_appointment', ?, ?)
    ");
    $notificationStmt->bind_param("ss", $notificationMessage, $appointmentId);
    executeDBStatement($notificationStmt);
    $notificationStmt->close();

    commitDBTransaction($conn);
    closeDBConnection($conn);

    header('Location: patient-appointments.php?success=' . urlencode('Appointment booked successfully'));
    exit;
} catch (Exception $e) {
    if ($conn) {
        rollbackDBTransaction($conn);
        closeDBConnection($conn);
    }

    error_log("Appointment booking error: " . $e->getMessage());

    $errorMsg = "Unable to book appointment. Please try again or contact support.";

    if (isDuplicateKeyException($e)) {
        $errorMsg = "This appointment time is already taken. Please select a different time.";
    } elseif ($e->getMessage() === 'Selected doctor is not available. Please choose another doctor.') {
        $errorMsg = $e->getMessage();
    }

    header('Location: ../../public/patient-book.php?error=' . urlencode($errorMsg));
    exit;
}
