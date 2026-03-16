<?php
require_once '../../config/session-config.php';
require_once '../../config/db-config.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Check authentication and admin role
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

try {
    // Get database credentials from config
    $conn = getDBConnection();

    // Get database name from the connection
    $result = $conn->query("SELECT DATABASE()");
    $row = $result->fetch_row();
    $database = $row[0];

    // Close the connection
    closeDBConnection($conn);

    // Create backup filename with timestamp
    $backupFile = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
    $backupPath = '../../backups/';

    // Create backups directory if it doesn't exist
    if (!file_exists($backupPath)) {
        mkdir($backupPath, 0755, true);
    }

    $fullBackupPath = $backupPath . $backupFile;

    // Use database credentials from db-config.php
    $dbHost = DB_HOST;
    $dbUser = DB_USER;
    $dbPass = DB_PASS;
    $dbName = DB_NAME;

    // Use mysqldump to create backup
    // Note: This requires mysqldump to be available in the system PATH
    $command = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($dbHost),
        escapeshellarg($dbUser),
        escapeshellarg($dbPass),
        escapeshellarg($dbName),
        escapeshellarg($fullBackupPath)
    );

    // Execute the command
    exec($command, $output, $returnVar);

    // Check if backup was successful
    if ($returnVar === 0 && file_exists($fullBackupPath) && filesize($fullBackupPath) > 0) {
        // Log successful backup
        error_log("Database backup created successfully: " . $backupFile);

        echo json_encode([
            'success' => true,
            'message' => 'Database backup completed successfully',
            'filename' => $backupFile,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } else {
        // Backup failed - try alternative method
        throw new Exception("mysqldump failed, attempting PHP backup method");
    }
} catch (Exception $e) {
    // Fallback: Create a simple SQL dump using PHP
    try {
        error_log("Attempting PHP-based backup: " . $e->getMessage());

        $conn = getDBConnection();

        // Get database name
        $result = $conn->query("SELECT DATABASE()");
        $row = $result->fetch_row();
        $database = $row[0];

        // Create backup file
        $backupFile = 'backup_' . $database . '_' . date('Y-m-d_H-i-s') . '.sql';
        $backupPath = '../../backups/';

        if (!file_exists($backupPath)) {
            mkdir($backupPath, 0755, true);
        }

        $fullBackupPath = $backupPath . $backupFile;
        $sqlDump = "-- MediCare Clinic Database Backup\n";
        $sqlDump .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        $sqlDump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        $sqlDump .= "SET time_zone = \"+00:00\";\n\n";

        // Get all tables
        $tables = array();
        $result = $conn->query("SHOW TABLES");
        while ($row = $result->fetch_row()) {
            $tables[] = $row[0];
        }

        // Dump each table
        foreach ($tables as $table) {
            $sqlDump .= "\n-- Table structure for table `{$table}`\n";
            $sqlDump .= "DROP TABLE IF EXISTS `{$table}`;\n";

            // Get CREATE TABLE statement
            $result = $conn->query("SHOW CREATE TABLE `{$table}`");
            $row = $result->fetch_row();
            $sqlDump .= $row[1] . ";\n\n";

            // Get table data
            $result = $conn->query("SELECT * FROM `{$table}`");
            if ($result->num_rows > 0) {
                $sqlDump .= "-- Dumping data for table `{$table}`\n";

                while ($row = $result->fetch_assoc()) {
                    $sqlDump .= "INSERT INTO `{$table}` VALUES (";
                    $values = array();
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $sqlDump .= implode(", ", $values) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }

        closeDBConnection($conn);

        // Write to file
        if (file_put_contents($fullBackupPath, $sqlDump)) {
            error_log("PHP-based database backup created successfully: " . $backupFile);

            echo json_encode([
                'success' => true,
                'message' => 'Database backup completed successfully',
                'filename' => $backupFile,
                'timestamp' => date('Y-m-d H:i:s'),
                'method' => 'php'
            ]);
        } else {
            throw new Exception("Failed to write backup file");
        }
    } catch (Exception $innerException) {
        error_log("Database backup error: " . $innerException->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Failed to create database backup. Please check server permissions.'
        ]);
    }
}
