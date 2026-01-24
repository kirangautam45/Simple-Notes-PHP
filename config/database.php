<?php
/**
 * Database Configuration
 * Simple Notes App - SQLite Version
 */

// Database file path
$dbPath = __DIR__ . '/../data/notes.db';
$dbDir = dirname($dbPath);

// Create data directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

try {
    $pdo = new PDO(
        "sqlite:" . $dbPath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );

    // Enable foreign keys for SQLite
    $pdo->exec("PRAGMA foreign_keys = ON");

    // Debug: Show PDO object info
    var_dump($pdo);

    // Initialize database if tables don't exist
    initializeDatabase($pdo);

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

/**
 * Initialize SQLite database with tables and sample data
 */
function initializeDatabase($pdo) {
    // Create Users Table if not exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Check if tables exist
    $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='notes'");
    if ($result->fetch()) {
        // Add user_id column if it doesn't exist
        $columns = $pdo->query("PRAGMA table_info(notes)")->fetchAll();
        $hasUserId = false;
        foreach ($columns as $col) {
            if ($col['name'] === 'user_id') {
                $hasUserId = true;
                break;
            }
        }
        if (!$hasUserId) {
            $pdo->exec("ALTER TABLE notes ADD COLUMN user_id INTEGER NULL REFERENCES users(id) ON DELETE CASCADE");
            $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON notes(user_id)");
        }
        return; // Tables already exist
    }


    // Create Categories Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            color VARCHAR(7) DEFAULT '#667eea',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Create Notes Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS notes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT,
            color VARCHAR(7) DEFAULT '#ffffff',
            is_pinned INTEGER DEFAULT 0,
            is_archived INTEGER DEFAULT 0,
            category_id INTEGER NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
        )
    ");

    // Create indexes
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_is_archived ON notes(is_archived)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_is_pinned ON notes(is_pinned)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_updated_at ON notes(updated_at)");
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_user_id ON notes(user_id)");

  
}
