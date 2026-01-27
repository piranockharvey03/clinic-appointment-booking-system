<?php
// Test script to check database connection and password change functionality
session_start();
require_once 'db-config.php';

header('Content-Type: application/json');

try {
    echo json_encode([
        'database_connection' => 'Testing database connection...',
        'tables_exist' => 'Checking if users and admin tables exist...',
        'session_info' => [
            'user_id' => $_SESSION['user_id'] ?? 'Not set',
            'user_role' => $_SESSION['user_role'] ?? 'Not set',
            'user_name' => $_SESSION['user_name'] ?? 'Not set'
        ]
    ]);

    $conn = getDBConnection();

    // Check if users table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    $users_exists = $result->num_rows > 0;

    // Check if admin table exists
    $result = $conn->query("SHOW TABLES LIKE 'admin'");
    $admin_exists = $result->num_rows > 0;

    echo json_encode([
        'users_table_exists' => $users_exists,
        'admin_table_exists' => $admin_exists,
        'next_steps' => 'If tables exist, try logging in and then test password change'
    ]);

    closeDBConnection($conn);

} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'suggestion' => 'Make sure to run the database schema first'
    ]);
}
?>
