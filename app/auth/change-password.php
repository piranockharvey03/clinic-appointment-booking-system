<?php
// Start output buffering to prevent any accidental output
ob_start();

require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

// Start patient-specific session
startSession('patient');

// Enable error logging (never display errors in JSON responses)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../../password-change-errors.log');

// Clear any output that might have been generated
ob_clean();

// Set JSON header
header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login again']);
    ob_end_flush();
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword     = $_POST['new_password']     ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

// Validate all fields are present
if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'All fields are required']);
    ob_end_flush();
    exit;
}

if ($newPassword !== $confirmPassword) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'New passwords do not match']);
    ob_end_flush();
    exit;
}

if (strlen($newPassword) < 8) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters long']);
    ob_end_flush();
    exit;
}

// Enforce at least one number and one letter
if (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Password must contain at least one letter and one number']);
    ob_end_flush();
    exit;
}

// Prevent same-as-current password
if ($currentPassword === $newPassword) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'New password must be different from your current password']);
    ob_end_flush();
    exit;
}

try {
    $conn    = getDBConnection();
    $userId  = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];

    // Determine which table to use based on user role
    if ($userRole === 'admin') {
        $tableName = 'admin';
    } elseif ($userRole === 'patient') {
        $tableName = 'users';
    } elseif ($userRole === 'doctor') {
        $tableName = 'doctors';
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Invalid user role']);
        ob_end_flush();
        exit;
    }

    // --- Step 1: Verify the current password ---
    $fetchStmt = $conn->prepare("SELECT password FROM `{$tableName}` WHERE id = ?");
    if (!$fetchStmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $fetchStmt->bind_param("i", $userId);
    $fetchStmt->execute();
    $fetchResult = $fetchStmt->get_result();
    $user = $fetchResult->fetch_assoc();
    $fetchStmt->close();

    if (!$user) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'User not found. Please login again.']);
        ob_end_flush();
        exit;
    }

    if (!password_verify($currentPassword, $user['password'])) {
        error_log("Failed password change attempt for {$userRole} ID: {$userId} - incorrect current password");
        ob_clean();
        echo json_encode(['success' => false, 'error' => 'Your current password is incorrect']);
        ob_end_flush();
        exit;
    }

    // --- Step 2: Hash and update new password ---
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    $updateStmt = $conn->prepare("UPDATE `{$tableName}` SET password = ? WHERE id = ?");
    if (!$updateStmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }
    $updateStmt->bind_param("si", $hashedPassword, $userId);

    if ($updateStmt->execute()) {
        $affectedRows = $updateStmt->affected_rows;
        $updateStmt->close();

        error_log("Password changed successfully for {$userRole} ID: {$userId}, affected_rows: {$affectedRows}");

        if ($affectedRows > 0) {
            logActivity($conn, $userId, $_SESSION['user_name'] ?? 'Unknown', $userRole, 'change_password', 'Password changed successfully');
            closeDBConnection($conn);
            ob_clean();
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully! Please use your new password next time you log in.'
            ]);
            ob_end_flush();
        } else {
            closeDBConnection($conn);
            ob_clean();
            echo json_encode([
                'success' => false,
                'error'   => 'Password could not be updated. Please try again.'
            ]);
            ob_end_flush();
        }
    } else {
        $error = $updateStmt->error;
        $updateStmt->close();
        closeDBConnection($conn);
        throw new Exception("Failed to update password: " . $error);
    }
} catch (Exception $e) {
    error_log("Password change error: " . $e->getMessage());
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Failed to change password. Please try again.']);
}

ob_end_flush();
