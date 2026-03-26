<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Check if session is valid
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

// Validate required params
if (!isset($_POST['conversation_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'conversation_id is required']);
    exit;
}

// Add caching for performance
header('Cache-Control: private, max-age=1');
header('Content-Type: application/json');

try {
    $requestStart = microtime(true);
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $conversationId = intval($_POST['conversation_id']);
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $pageSize = 30;
    $offset = ($page - 1) * $pageSize;

    // Get messages with authorization check combined - optimized query
    // Load messages with pagination
    $stmt = $conn->prepare("
        SELECT 
            m.message_id,
            m.conversation_id,
            m.sender_role,
            m.sender_id,
            m.message_text,
            m.delivery_status,
            m.is_read,
            m.read_at,
            m.delivered_at,
            m.created_at,
            CASE 
                WHEN m.sender_role = 'doctor' THEN d.full_name
                ELSE u.full_name
            END as sender_name
        FROM messages m
        INNER JOIN conversations c ON m.conversation_id = c.conversation_id
        LEFT JOIN doctors d ON m.sender_role = 'doctor' AND m.sender_id = d.id
        LEFT JOIN users u ON m.sender_role = 'patient' AND m.sender_id = u.id
        WHERE m.conversation_id = ?
            AND ((c.patient_id = ? AND ? = 'patient') OR (c.doctor_id = ? AND ? = 'doctor'))
        ORDER BY m.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("isisiiii", $conversationId, $userId, $userRole, $userId, $userRole, $pageSize, $offset);
    $stmt->execute();
    $result = $stmt->get_result();

    $messages = [];
    while ($row = $result->fetch_assoc()) {
        $messages[] = $row;
    }

    // Reverse to show oldest to newest (we fetched newest first for pagination)
    $messages = array_reverse($messages);

    $result->free();
    $stmt->close();
    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'count' => count($messages),
        'page' => $page,
        'page_size' => $pageSize
    ]);

    $elapsedMs = round((microtime(true) - $requestStart) * 1000, 2);
    error_log("[MESSAGING_PERF] get-messages conversation_id={$conversationId} user_id={$userId} page={$page} count=" . count($messages) . " duration_ms={$elapsedMs}");
} catch (Exception $e) {
    error_log("Error fetching messages: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch messages']);
}
