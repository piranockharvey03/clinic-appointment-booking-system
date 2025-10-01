<?php
// Bootstrap database and session
declare(strict_types=1);

// Error reporting for development
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Paths
$dbPath = __DIR__ . DIRECTORY_SEPARATOR . 'database.sqlite';
$schemaPath = __DIR__ . DIRECTORY_SEPARATOR . 'schema.sql';

// Ensure database exists and schema applied
$isNewDb = !file_exists($dbPath);
$pdo = new PDO('sqlite:' . $dbPath, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

if ($isNewDb && file_exists($schemaPath)) {
    $schemaSql = file_get_contents($schemaPath);
    if ($schemaSql !== false) {
        $pdo->exec($schemaSql);
    }
}

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function require_auth(): void {
    if (!current_user_id()) {
        header('Location: login.php');
        exit;
    }
}

function redirect(string $path): void {
    header('Location: ' . $path);
    exit;
}

function h(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

?>


