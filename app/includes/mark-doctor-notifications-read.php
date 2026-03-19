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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$notificationIds = isset($_POST['notification_ids']) ? $_POST['notification_ids'] : [];

if (empty($notificationIds)) {
    echo json_encode(['success' => false, 'error' => 'No notification IDs provided']);
    exit;
}

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
    $doctorId = (int) $_SESSION['user_id'];

    $placeholders = str_repeat('?,', count($notificationIds) - 1) . '?';
    $stmt = $conn->prepare("
        UPDATE doctor_notifications
        SET is_read = TRUE, read_at = NOW()
        WHERE id IN ($placeholders) AND doctor_id = ? AND is_read = FALSE
    ");

    $types = str_repeat('i', count($notificationIds)) . 'i';
    $params = array_merge($notificationIds, [$doctorId]);
    $stmt->bind_param($types, ...$params);

    $success = $stmt->execute();

    if ($success) {
        $affectedRows = $stmt->affected_rows;
        $stmt->close();

        $countStmt = $conn->prepare("SELECT COUNT(*) AS unread_count FROM doctor_notifications WHERE doctor_id = ? AND is_read = FALSE");
        $countStmt->bind_param('i', $doctorId);
        $countStmt->execute();
        $countResult = $countStmt->get_result();

        $unreadCount = 0;
        if ($countResult) {
            $countRow = $countResult->fetch_assoc();
            $unreadCount = (int) ($countRow['unread_count'] ?? 0);
            $countResult->free();
        }
        $countStmt->close();

        closeDBConnection($conn);

        echo json_encode([
            'success' => true,
            'marked_count' => $affectedRows,
            'unread_count' => $unreadCount
        ]);
    } else {
        throw new Exception('Failed to mark doctor notifications as read: ' . $stmt->error);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to mark doctor notifications as read']);
}
