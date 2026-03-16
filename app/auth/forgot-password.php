<?php
// Return JSON for async forgot-password requests.
header('Content-Type: application/json');

require_once '../../config/db-config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
    exit;
}

$email = trim($_POST['email'] ?? '');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.'
    ]);
    exit;
}

try {
    $conn = getDBConnection();

    // Check whether the email exists in any supported account table.
    $queries = [
        'users' => 'SELECT id FROM users WHERE email = ? LIMIT 1',
        'doctors' => 'SELECT id FROM doctors WHERE email = ? LIMIT 1',
        'admin' => 'SELECT id FROM admin WHERE email = ? LIMIT 1'
    ];

    $found = false;

    foreach ($queries as $table => $sql) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            continue;
        }

        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $found = true;
            $stmt->close();
            break;
        }

        $stmt->close();
    }

    // Use a generic response to avoid account enumeration.
    if ($found) {
        logActivity($conn, 0, $email, 'guest', 'forgot_password_request', 'Password reset requested');
    }

    closeDBConnection($conn);

    echo json_encode([
        'success' => true,
        'message' => 'If an account exists for that email, reset instructions have been sent.'
    ]);
} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to process request right now. Please try again later.'
    ]);
}
