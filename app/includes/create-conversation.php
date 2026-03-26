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
if (!isset($_POST['doctor_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'doctor_id is required']);
    exit;
}

try {
    $conn = getDBConnection();
    $userId = intval($_SESSION['user_id']);
    $userRole = $_SESSION['user_role'];
    $doctorId = intval($_POST['doctor_id']);

    if ($userRole !== 'patient') {
        http_response_code(403);
        echo json_encode(['error' => 'Only patients can create conversations']);
        exit;
    }

    // Verify appointment relationship exists
    $stmt = $conn->prepare("
        SELECT appointment_id FROM appointments
        WHERE patient_id = ? AND doctor_id = ? AND status IN ('pending', 'approved', 'rescheduled', 'completed')
        LIMIT 1
    ");
    $stmt->bind_param("ii", $userId, $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(403);
        echo json_encode(['error' => 'No appointment relationship found']);
        exit;
    }

    $stmt->close();

    // Check if conversation already exists
    $stmt = $conn->prepare("
        SELECT conversation_id FROM conversations
        WHERE patient_id = ? AND doctor_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $userId, $doctorId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $conversationId = $row['conversation_id'];
    } else {
        // Create new conversation
        $stmt = $conn->prepare("
            INSERT INTO conversations (patient_id, doctor_id, created_at)
            VALUES (?, ?, NOW())
        ");
        $stmt->bind_param("ii", $userId, $doctorId);
        $stmt->execute();
        $conversationId = $stmt->insert_id;
    }
    $stmt->close();

    closeDBConnection($conn);

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'conversation_id' => $conversationId
    ]);
} catch (Exception $e) {
    error_log("Error creating conversation: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to create conversation']);
}
