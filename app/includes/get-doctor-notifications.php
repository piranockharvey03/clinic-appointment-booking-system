<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

header('Content-Type: application/json');

// Only allow doctor access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    $conn = getDBConnection();
    $doctorId = (int) $_SESSION['user_id'];

    $stmt = $conn->prepare("
        SELECT id, type, message, appointment_id, created_at
        FROM doctor_notifications
        WHERE doctor_id = ? AND is_read = FALSE
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param('i', $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = [
                'id' => $row['id'],
                'type' => $row['type'],
                'message' => $row['message'],
                'appointment_id' => $row['appointment_id'],
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
    echo json_encode(['success' => false, 'error' => 'Failed to fetch doctor notifications']);
}
