<?php
$host = 'localhost';
$user = 'lsxutvlj_tbd'; // Default XAMPP/WAMP user
$pass = '#Yeshifu241';     // Default XAMPP/WAMP password
$db   = 'lsxutvlj_tbd';

try {
    // Connect to MySQL server without specifying a database first
    $pdo = new PDO("mysql:host=$host", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Auto-create database if it doesn't exist
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");

    // Auto-create the admins table
    $tableQuery = "CREATE TABLE IF NOT EXISTS `admins` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) UNIQUE NOT NULL,
        `password` VARCHAR(255) NOT NULL,
        `remember_token` VARCHAR(64) NULL DEFAULT NULL,
        `remember_expires` DATETIME NULL DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($tableQuery);
    // Add remember columns to existing installs that don't have them yet
    try { $pdo->exec("ALTER TABLE `admins` ADD COLUMN `remember_token` VARCHAR(64) NULL DEFAULT NULL"); } catch(PDOException $e) {}
    try { $pdo->exec("ALTER TABLE `admins` ADD COLUMN `remember_expires` DATETIME NULL DEFAULT NULL"); } catch(PDOException $e) {}

    // Create default admin user if the table is empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM `admins`");
    if ($stmt->fetchColumn() == 0) {
        $defaultUser = 'admin';
        $randomPass  = bin2hex(random_bytes(8)); // 16-char random hex password
        $defaultPass = password_hash($randomPass, PASSWORD_DEFAULT);

        $insertStmt = $pdo->prepare("INSERT INTO `admins` (`username`, `password`) VALUES (?, ?)");
        $insertStmt->execute([$defaultUser, $defaultPass]);

        // Write one-time credential hint — delete this file after first login
        $credFile = dirname(__DIR__) . '/.admin_initial_password';
        file_put_contents($credFile, "username: admin\npassword: $randomPass\n(delete this file after logging in)\n");
        @chmod($credFile, 0600);
    }

} catch(PDOException $e) {
    die("<div style='color:red; padding:20px; font-family:sans-serif;'><strong>Database Connection Failed:</strong> Please ensure your MySQL server (like XAMPP/WAMP) is running. Error details: " . $e->getMessage() . "</div>");
}
?>