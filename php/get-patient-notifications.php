<?php
// Get patient notifications by appointment IDs
require_once 'db-config.php';

header('Content-Type: application/json');

// Check if patient is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $conn = getDBConnection();
    $patientId = $_SESSION['user_id'];

    // Get unread patient notifications for this patient's appointments only
    $stmt = $conn->prepare("
        SELECT pn.id, pn.notification_type, pn.message, pn.created_at
        FROM patient_notifications pn
        INNER JOIN appointments a ON pn.appointment_id = a.appointment_id
        WHERE a.patient_id = ? AND pn.is_read = FALSE
        ORDER BY pn.created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['notification_type'],
                'message' => $row['message'],
                'time' => date('M j, Y g:i A', strtotime($row['created_at']))
            ];
        }
        $result->free();
    }

    $stmt->close();
    closeDBConnection($conn);

    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch notifications']);
}
?>
