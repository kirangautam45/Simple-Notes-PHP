<?php
require_once __DIR__ . '/config/database.php';

try {
    echo "Testing connection...\n";
    $stmt = $pdo->query("SELECT version()");
    $version = $stmt->fetchColumn();
    echo "Connected successfully to: " . $version . "\n";
    
    // Check tables
    $tables = ['users', 'notes', 'categories'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $count = $stmt->fetchColumn();
        echo "Table '$table' exists with $count rows.\n";
    }
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage() . "\n";
}
