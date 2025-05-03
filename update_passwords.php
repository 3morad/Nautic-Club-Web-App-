<?php

// Simple script to update user passwords

$password = password_hash('123456', PASSWORD_BCRYPT);
echo "Generated password hash: " . $password . "\n";

// Update password in the database using the correct connection details from .env
$dbHost = '127.0.0.1';
$dbPort = '3307';
$dbName = 'event_manager';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update admin user
    $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE email = 'admin@example.com'");
    $stmt->execute(['password' => $password]);
    echo "Updated admin@example.com password\n";
    
    // Update regular user
    $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE email = 'user@example.com'");
    $stmt->execute(['password' => $password]);
    echo "Updated user@example.com password\n";
    
    // Also update Ahmed's account password if needed
    $stmt = $pdo->prepare("UPDATE user SET password = :password WHERE email = 'ahmed@gmail.com'");
    $stmt->execute(['password' => $password]);
    echo "Updated ahmed@gmail.com password\n";
    
    echo "All passwords have been updated to '123456'\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 