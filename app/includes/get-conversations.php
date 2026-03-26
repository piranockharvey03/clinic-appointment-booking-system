<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Check if session is valid
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// Cache response for 2 seconds to reduce database load
header('Cache-Control: private, max-age=2');
header('Content-Type: application/json');

try {
    $requestStart = microtime(true);
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];

    $conversations = [];

    if ($userRole === 'patient') {
        // Fetch base conversations first, then unread counts via correlated subquery.
        // This avoids scanning/joining the full messages table for every poll.
        $stmt = $conn->prepare("
            SELECT 
                c.conversation_id,
                c.patient_id,
                c.doctor_id,
                d.full_name as doctor_name,
                d.specialty as doctor_specialty,
                d.photo as doctor_photo,
                c.last_message_at,
                c.created_at,
                (
                    SELECT COUNT(*)
                    FROM messages m
                    WHERE m.conversation_id = c.conversation_id
                      AND m.sender_role = 'doctor'
                      AND m.receiver_id = ?
                      AND m.is_read = 0
                ) as unread_count
            FROM conversations c
            JOIN doctors d ON c.doctor_id = d.id
            WHERE c.patient_id = ?
            ORDER BY (c.last_message_at IS NULL), c.last_message_at DESC, c.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ii", $userId, $userId);
    } else {
        // Fetch base conversations first, then unread counts via correlated subquery.
        // This avoids scanning/joining the full messages table for every poll.
        $stmt = $conn->prepare("
            SELECT 
                c.conversation_id,
                c.patient_id,
                c.doctor_id,
                u.full_name as patient_name,
                u.email as patient_email,
                u.phone as patient_phone,
                c.last_message_at,
                c.created_at,
                (
                    SELECT COUNT(*)
                    FROM messages m
                    WHERE m.conversation_id = c.conversation_id
                      AND m.sender_role = 'patient'
                      AND m.receiver_id = ?
                      AND m.is_read = 0
                ) as unread_count
            FROM conversations c
            JOIN users u ON c.patient_id = u.id
            WHERE c.doctor_id = ?
            ORDER BY (c.last_message_at IS NULL), c.last_message_at DESC, c.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("ii", $userId, $userId);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $row['unread_count'] = intval($row['unread_count'] ?? 0);
        $conversations[] = $row;
    }

    $result->free();
    $stmt->close();
    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'conversations' => $conversations,
        'count' => count($conversations)
    ]);

    $elapsedMs = round((microtime(true) - $requestStart) * 1000, 2);
    error_log("[MESSAGING_PERF] get-conversations user_id={$userId} role={$userRole} count=" . count($conversations) . " duration_ms={$elapsedMs}");
} catch (Exception $e) {
    error_log("Error fetching conversations: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch conversations']);
}
