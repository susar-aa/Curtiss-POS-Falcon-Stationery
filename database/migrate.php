<?php
/**
 * Migration Script for Curtiss POS Database Setup (XAMPP Localhost)
 * Executes database/schema.sql
 */

$host = 'localhost';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    echo "Connected to MySQL server on $host...\n";
    
    $sqlPath = __DIR__ . '/schema.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("schema.sql not found at $sqlPath");
    }
    
    $sql = file_get_contents($sqlPath);
    
    // Execute multi-query script
    $pdo->exec($sql);
    
    echo "SUCCESS: Curtiss POS database (curtiss_pos) created and seeded successfully!\n";
    
} catch (Exception $e) {
    echo "ERROR during migration: " . $e->getMessage() . "\n";
    exit(1);
}
