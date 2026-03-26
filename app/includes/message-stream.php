<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Check if session is valid
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit;
}

// Validate required params
if (!isset($_GET['conversation_id'])) {
    http_response_code(400);
    exit;
}

try {
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $conversationId = intval($_GET['conversation_id']);

    $conn = getDBConnection();

    // Verify user belongs to this conversation
    if ($userRole === 'patient') {
        $stmt = $conn->prepare("
            SELECT conversation_id FROM conversations
            WHERE conversation_id = ? AND patient_id = ?
        ");
        $stmt->bind_param("ii", $conversationId, $userId);
    } else {
        $stmt = $conn->prepare("
            SELECT conversation_id FROM conversations
            WHERE conversation_id = ? AND doctor_id = ?
        ");
        $stmt->bind_param("ii", $conversationId, $userId);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        closeDBConnection($conn);
        exit;
    }

    $stmt->close();

    // Set SSE headers
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('Access-Control-Allow-Origin: *');

    // Get last message timestamp (for polling updates)
    $lastTimestamp = isset($_GET['since']) ? $_GET['since'] : date('Y-m-d H:i:s', time() - 60);

    // Function to send SSE message
    function sendSSE($data)
    {
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
    }

    // Send initial keepalive
    echo ": keepalive\n\n";
    ob_flush();
    flush();

    // Poll for messages every 4 seconds for 120 seconds (2 minutes polling max)
    $startTime = time();
    $pollInterval = 4; // increased from 2 to 4 seconds
    $maxDuration = 120; // increased from 60 to 120 seconds for longer connections

    while ((time() - $startTime) < $maxDuration) {
        // Check for new messages - optimized query using indexes
        $stmt = $conn->prepare("
            SELECT 
                m.message_id,
                m.sender_role,
                m.sender_id,
                m.message_text,
                m.delivery_status,
                m.is_read,
                m.created_at,
                CASE 
                    WHEN m.sender_role = 'doctor' THEN d.full_name
                    ELSE u.full_name
                END as sender_name
            FROM messages m
            LEFT JOIN doctors d ON m.sender_role = 'doctor' AND m.sender_id = d.id
            LEFT JOIN users u ON m.sender_role = 'patient' AND m.sender_id = u.id
            WHERE m.conversation_id = ? AND m.created_at > ?
            ORDER BY m.created_at ASC
        ");
        $stmt->bind_param("is", $conversationId, $lastTimestamp);
        $stmt->execute();
        $result = $stmt->get_result();

        $messages = [];
        $hasNewMessages = false;
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
            $lastTimestamp = $row['created_at'];
            $hasNewMessages = true;
        }

        $stmt->close();
        $result->free();

        // Check for typing status updates
        $stmt = $conn->prepare("
            SELECT user_role, user_id, is_typing
            FROM typing_status
            WHERE conversation_id = ?
              AND expires_at > NOW()
              AND (is_typing = 1 OR updated_at > DATE_SUB(NOW(), INTERVAL 5 SECOND))
              AND NOT (user_role = ? AND user_id = ?)
            ORDER BY updated_at DESC
        ");
        $stmt->bind_param("isi", $conversationId, $userRole, $userId);
        $stmt->execute();
        $typingResult = $stmt->get_result();

        $typingUsers = [];
        while ($row = $typingResult->fetch_assoc()) {
            if ($row['is_typing']) {
                $typingUsers[] = [
                    'user_role' => $row['user_role'],
                    'user_id' => $row['user_id']
                ];
            }
        }

        $typingResult->free();
        $stmt->close();

        // Send messages to client
        if ($hasNewMessages) {
            foreach ($messages as $msg) {
                sendSSE([
                    'type' => 'message',
                    'data' => $msg
                ]);
            }

            // Mark messages as read if receiver (only do this if there are new messages)
            $stmt = $conn->prepare("
                UPDATE messages
                SET is_read = 1, read_at = NOW(), delivery_status = 'read'
                WHERE conversation_id = ? AND receiver_id = ? AND is_read = 0
                LIMIT 100
            ");
            $stmt->bind_param("ii", $conversationId, $userId);
            $stmt->execute();
            $stmt->close();
        }

        // Send typing status updates
        if (count($typingUsers) > 0) {
            sendSSE([
                'type' => 'typing',
                'data' => $typingUsers
            ]);
        }

        // Send keepalive
        echo ": keepalive\n\n";
        ob_flush();
        flush();

        // Sleep before next poll
        sleep($pollInterval);
    }

    // Send end event
    sendSSE(['type' => 'reconnect']);

    closeDBConnection($conn);
} catch (Exception $e) {
    error_log("SSE error: " . $e->getMessage());
    echo "data: " . json_encode(['type' => 'error', 'message' => 'Connection error']) . "\n\n";
    ob_flush();
    flush();
}
