<?php
// Secure Database Connection & Auto-Initialization

$host = '127.0.0.1';
$user = 'root';
$pass = ''; // Default MySQL password for local environment
$dbname = 'travel_agency';
$charset = 'utf8mb4';

$dsnWithoutDb = "mysql:host=$host;charset=$charset";
$dsnWithDb = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // 1. Connect to MySQL without specifying database to check/create it
    $pdo = new PDO($dsnWithoutDb, $user, $pass, $options);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    
    // 2. Reconnect to the specific database
    $pdo = new PDO($dsnWithDb, $user, $pass, $options);
    
    // 3. Auto-initialize tables and seed data if tables do not exist
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($tableCheck->rowCount() == 0) {
        // Read and execute schema.sql
        $schemaPath = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaPath)) {
            $schemaSql = file_get_contents($schemaPath);
            // Remove comments and execute statements
            $pdo->exec($schemaSql);
        }
    }
} catch (\PDOException $e) {
    // In production, log error instead of displaying
    die("Database Connection Failed: " . $e->getMessage());
}
?>
