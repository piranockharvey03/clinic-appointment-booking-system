<?php
session_start();

// Check if session is valid
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo 'unauthorized';
    exit;
}

// Additional security: check if session has expired (optional - implement based on your needs)
// if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 3600) { // 1 hour timeout
//     session_destroy();
//     http_response_code(401);
//     echo 'unauthorized';
//     exit;
// }

// Session is valid
echo 'authenticated';
?>
