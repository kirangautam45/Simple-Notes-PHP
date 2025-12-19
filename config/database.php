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

  
    // Insert sample categories
    $pdo->exec("
        INSERT INTO categories (name, color) VALUES
        ('Personal', '#e91e63'),
        ('Work', '#2196f3'),
        ('Ideas', '#ff9800'),
        ('Shopping', '#4caf50'),
        ('Important', '#f44336')
    ");

    // Insert sample notes
    $pdo->exec("
        INSERT INTO notes (title, content, color, is_pinned, category_id) VALUES
        ('Welcome to Notes App!', 'This is your first note. You can:
- Edit this note
- Create new notes
- Pin important notes
- Archive old notes
- Search your notes

Enjoy organizing your thoughts!', '#fff9c4', 1, NULL),

        ('Shopping List', '- Milk
- Eggs
- Bread
- Butter
- Cheese
- Fruits
- Vegetables', '#c8e6c9', 0, 4),

        ('Project Ideas', '1. Build a todo app with React
2. Create a blog with PHP
3. Make a portfolio website
4. Learn a new programming language
5. Contribute to open source', '#bbdefb', 0, 3),

        ('Meeting Notes - Monday', 'Topics discussed:
- Quarterly goals review
- Team performance
- Next sprint planning
- Budget allocation

Action items:
- Follow up with marketing
- Prepare presentation
- Send meeting summary', '#f8bbd0', 0, 2),

        ('Personal Goals 2024', '- Learn a new skill
- Read 12 books
- Exercise regularly
- Save more money
- Travel to a new place
- Spend more time with family', '#e1bee7', 0, 1),

        ('Quick Tip', 'Use this app to organize your thoughts and ideas!', '#b2ebf2', 1, NULL),

        ('Recipe: Pasta', 'Ingredients:
- 200g pasta
- 2 cloves garlic
- Olive oil
- Parmesan cheese
- Salt & pepper
- Fresh basil

Steps:
1. Boil pasta
2. Sauté garlic in olive oil
3. Mix pasta with garlic oil
4. Add cheese and seasoning
5. Garnish with basil', '#ffccbc', 0, 1)
    ");
}
