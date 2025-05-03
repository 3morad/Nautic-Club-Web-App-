<?php

// Script to update user roles to add ROLE_ADMIN

// Update password in the database using the correct connection details from .env
$dbHost = '127.0.0.1';
$dbPort = '3307';
$dbName = 'event_manager';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Update admin's role
    $stmt = $pdo->prepare("UPDATE user SET roles = '[\"ROLE_ADMIN\"]' WHERE email = 'admin@example.com'");
    $stmt->execute();
    echo "Updated admin@example.com to ROLE_ADMIN\n";
    
    // Update Ahmed's role to include admin
    $stmt = $pdo->prepare("UPDATE user SET roles = '[\"ROLE_USER\", \"ROLE_ADMIN\"]' WHERE email = 'ahmed@gmail.com'");
    $stmt->execute();
    echo "Updated ahmed@gmail.com to include ROLE_ADMIN\n";
    
    echo "All roles have been updated\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 