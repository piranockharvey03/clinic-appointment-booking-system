<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$doctorId = trim((string)($_GET['doctor_id'] ?? ''));
$date = trim((string)($_GET['date'] ?? ''));
$time = trim((string)($_GET['time'] ?? ''));

if ($doctorId === '' || $date === '' || $time === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

$normalizedDate = normalizeAppointmentSlotDate($date);
$normalizedTime = normalizeAppointmentSlotTime($time);

if ($normalizedDate === '' || $normalizedTime === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid date or time']);
    exit;
}

$slotKey = buildAppointmentSlotKey($doctorId, $normalizedDate, $normalizedTime);

try {
    $conn = getDBConnection();

    // Primary check is by canonical slot key. The secondary clause protects legacy rows.
    $stmt = $conn->prepare("\n        SELECT appointment_id
        FROM appointments
        WHERE status IN ('pending', 'approved', 'rescheduled')
          AND (
              booking_slot_key = ?
              OR (
                  doctor_id = ?
                  AND appointment_date = ?
                  AND TIME_FORMAT(appointment_time, '%H:%i:%s') = ?
              )
          )
        LIMIT 1
    ");
    $stmt->bind_param('ssss', $slotKey, $doctorId, $normalizedDate, $normalizedTime);
    $stmt->execute();
    $result = $stmt->get_result();

    $isAvailable = !$result || $result->num_rows === 0;

    if ($result) {
        $result->free();
    }

    $stmt->close();
    closeDBConnection($conn);

    echo json_encode([
        'success' => true,
        'available' => $isAvailable,
        'message' => $isAvailable
            ? 'Slot is available.'
            : 'This doctor is already booked for this date and time.'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to check slot availability']);
}
