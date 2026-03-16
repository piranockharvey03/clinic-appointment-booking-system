<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

header('Content-Type: application/json');

// Require an authenticated patient session
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'patient') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get notification IDs from request
$notificationIds = isset($_POST['notification_ids']) ? $_POST['notification_ids'] : [];

if (empty($notificationIds)) {
    echo json_encode(['success' => false, 'error' => 'No notification IDs provided']);
    exit;
}

// Validate that notification_ids is an array of integers
if (!is_array($notificationIds)) {
    $notificationIds = [$notificationIds];
}
$notificationIds = array_map('intval', $notificationIds);
$notificationIds = array_filter($notificationIds, fn($id) => $id > 0);

if (empty($notificationIds)) {
    echo json_encode(['success' => false, 'error' => 'Invalid notification IDs']);
    exit;
}

try {
    $conn = getDBConnection();

    $patientId = (int)$_SESSION['user_id'];

    // Prepare the statement — only mark notifications belonging to this patient
    $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
    $stmt = $conn->prepare("
        UPDATE patient_notifications
        SET is_read = TRUE, read_at = NOW()
        WHERE id IN ($placeholders) AND patient_id = ? AND is_read = FALSE
    ");

    // Bind: all notification IDs + patient_id ownership check
    $types = str_repeat('i', count($notificationIds)) . 'i';
    $params = array_merge($notificationIds, [$patientId]);
    $stmt->bind_param($types, ...$params);

    $success = $stmt->execute();

    if ($success) {
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        closeDBConnection($conn);

        echo json_encode([
            'success' => true,
            'marked_count' => $affectedRows
        ]);
    } else {
        throw new Exception("Failed to mark notifications as read: " . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark notifications as read']);
}
