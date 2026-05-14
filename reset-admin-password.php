<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/db-config.php';

try {
    $conn = getDBConnection();

    // Generate hash for admin123
    $password = "admin123";
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Update the admin password
    $stmt = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $email = "admin@hospital.com";
    $stmt->bind_param("ss", $hashedPassword, $email);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    $affected = $stmt->affected_rows;
    $stmt->close();

    if ($affected > 0) {
        echo "<h2 style='color: green;'>✓ Password Reset Successfully!</h2>";
        echo "<p><strong>Email:</strong> admin@hospital.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><br><a href='public/admin-login.html'>Go to Admin Login →</a></p>";
    } else {
        echo "<h2 style='color: orange;'>⚠ No admin account found with email: admin@hospital.com</h2>";
        echo "<p>Let me create a new one...</p>";

        // Create new admin account
        $stmt2 = $conn->prepare("INSERT INTO admin (full_name, email, password) VALUES (?, ?, ?)");
        if (!$stmt2) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $fullName = "Admin";
        $stmt2->bind_param("sss", $fullName, $email, $hashedPassword);

        if (!$stmt2->execute()) {
            throw new Exception("Execute failed: " . $stmt2->error);
        }

        $stmt2->close();
        echo "<h2 style='color: green;'>✓ Admin Account Created Successfully!</h2>";
        echo "<p><strong>Email:</strong> admin@hospital.com</p>";
        echo "<p><strong>Password:</strong> admin123</p>";
        echo "<p><br><a href='public/admin-login.html'>Go to Admin Login →</a></p>";
    }

    $conn->close();
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error: " . $e->getMessage() . "</h2>";
}
