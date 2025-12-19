# Simple Notes App - PHP CRUD Application

A beginner-friendly notes application built with PHP and SQLite demonstrating fundamental web development concepts.

## Features

- Create, Read, Update, Delete (CRUD) notes
- Pin important notes to the top
- Archive notes for later
- Search notes by title or content
- Color-coded notes
- Categorize notes (Personal, Work, Ideas, Shopping, Important)
- Responsive design

## Project Structure

```
day-45/
├── config/
│   └── database.php      # Database connection & initialization
├── includes/
│   ├── header.php        # HTML header, navigation, session start
│   ├── footer.php        # HTML footer
│   └── functions.php     # Helper functions (CRUD operations)
├── css/
│   └── style.css         # Styling
├── data/
│   └── notes.db          # SQLite database (auto-created)
├── index.php             # Home page - List all notes
├── create.php            # Create new note form
├── edit.php              # Edit existing note
├── delete.php            # Delete a note
├── search.php            # Search results page
├── archive.php           # View/manage archived notes
├── database.sql          # MySQL reference schema (for learning)
└── README.md             # This file
```

## Requirements

- PHP 7.4 or higher
- SQLite3 extension (usually included with PHP)
- Web server (Apache, Nginx, or PHP built-in server)

## Installation

1. Clone or download this project
2. Navigate to the project directory
3. Start the PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
4. Open `http://localhost:8000` in your browser

The database will be automatically created with sample data on first run.

---

# Step-by-Step Guide: Building This App from Scratch

This guide teaches you how to build this notes app step by step. Each step introduces new concepts.

---

## Step 1: Project Setup

### What You'll Learn
- Directory structure for PHP projects
- Separating concerns (config, includes, pages)

### Create the folder structure:

```bash
mkdir -p notes-app/{config,includes,css,data}
cd notes-app
```

### Key Concept: Separation of Concerns
Keep your code organized:
- `config/` - Database and app configuration
- `includes/` - Reusable PHP components
- `css/` - Stylesheets
- `data/` - Database files (for SQLite)

---

## Step 2: Database Connection (config/database.php)

### What You'll Learn
- PDO (PHP Data Objects) for database access
- SQLite database basics
- Try-catch error handling

### Code Explanation:

```php
<?php
// Database file path
$dbPath = __DIR__ . '/../data/notes.db';
$dbDir = dirname($dbPath);

// Create data directory if it doesn't exist
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

try {
    // Create PDO connection to SQLite
    $pdo = new PDO(
        "sqlite:" . $dbPath,
        null,
        null,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Return associative arrays
            PDO::ATTR_EMULATE_PREPARES => false  // Use real prepared statements
        ]
    );

    // Enable foreign keys
    $pdo->exec("PRAGMA foreign_keys = ON");

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
```

### Key Concepts:

1. **PDO**: A database abstraction layer that works with multiple databases
2. **`__DIR__`**: Magic constant for current directory path
3. **Error Modes**: `ERRMODE_EXCEPTION` throws errors instead of silent failures
4. **Prepared Statements**: Prevent SQL injection attacks

---

## Step 3: Create Database Tables

### What You'll Learn
- SQL CREATE TABLE syntax
- Data types (INTEGER, VARCHAR, TEXT, BOOLEAN)
- Primary keys and auto-increment
- Foreign keys for relationships

### The Schema:

```sql
-- Notes table
CREATE TABLE IF NOT EXISTS notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title VARCHAR(255) NOT NULL,
    content TEXT,
    color VARCHAR(7) DEFAULT '#ffffff',
    is_pinned INTEGER DEFAULT 0,
    is_archived INTEGER DEFAULT 0,
    category_id INTEGER NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(100) NOT NULL,
    color VARCHAR(7) DEFAULT '#667eea',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
```

### Key Concepts:

1. **PRIMARY KEY AUTOINCREMENT**: Unique ID, auto-generated
2. **NOT NULL**: Field is required
3. **DEFAULT**: Fallback value if none provided
4. **FOREIGN KEY**: Links to another table
5. **ON DELETE SET NULL**: If category is deleted, set note's category to NULL

---

## Step 4: Helper Functions (includes/functions.php)

### What You'll Learn
- Creating reusable functions
- Prepared statements for secure queries
- Input sanitization

### Essential Functions:

```php
<?php
/**
 * Sanitize string input - ALWAYS use this for user input!
 */
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect to a URL
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get all notes from database
 */
function getAllNotes($pdo, $archived = false) {
    $sql = "SELECT n.*, c.name as category_name, c.color as category_color
            FROM notes n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.is_archived = :archived
            ORDER BY n.is_pinned DESC, n.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['archived' => $archived ? 1 : 0]);
    return $stmt->fetchAll();
}

/**
 * Get single note by ID
 */
function getNoteById($pdo, $id) {
    $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Search notes by keyword
 */
function searchNotes($pdo, $query) {
    $sql = "SELECT n.*, c.name as category_name, c.color as category_color
            FROM notes n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.is_archived = 0
            AND (n.title LIKE :query OR n.content LIKE :query)
            ORDER BY n.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => "%$query%"]);
    return $stmt->fetchAll();
}
```

### Key Concepts:

1. **`htmlspecialchars()`**: Prevents XSS attacks by escaping HTML
2. **Prepared Statements**: Use `?` or `:named` placeholders, NEVER concatenate user input
3. **LEFT JOIN**: Get notes with their category info (even if no category)
4. **LIKE**: SQL pattern matching with `%` wildcards

---

## Step 5: Header & Footer Templates (includes/header.php, footer.php)

### What You'll Learn
- Session management
- Reusable templates with includes
- Flash messages for user feedback

### header.php:

```php
<?php
session_start();  // Start session for flash messages
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?>Simple Notes App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <!-- Navigation links -->
    </nav>

    <main class="container">
        <?php
        // Display flash message if exists
        $flash = getFlashMessage();
        if ($flash):
        ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= sanitize($flash['message']) ?>
        </div>
        <?php endif; ?>
```

### Key Concepts:

1. **`session_start()`**: Must be called before any output
2. **`require_once`**: Include file only once (prevents duplicate definitions)
3. **Short echo**: `<?= $var ?>` is shorthand for `<?php echo $var; ?>`
4. **Flash Messages**: One-time messages stored in session, cleared after display

---

## Step 6: Create Note (create.php) - The "C" in CRUD

### What You'll Learn
- HTML forms with POST method
- Form validation
- Inserting data into database

### Process Flow:

```
1. User visits create.php (GET request)
   └── Display empty form

2. User submits form (POST request)
   ├── Validate input
   │   ├── If errors → Show form with errors
   │   └── If valid → Insert into DB → Redirect to index
```

### Code Explanation:

```php
<?php
$pageTitle = 'Create Note';
require_once 'includes/header.php';

$errors = [];
$title = '';
$content = '';

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $color = $_POST['color'] ?? '#ffffff';
    $category_id = $_POST['category_id'] ?? null;

    // Validation
    if (empty($title)) {
        $errors['title'] = 'Title is required';
    } elseif (strlen($title) > 255) {
        $errors['title'] = 'Title must be less than 255 characters';
    }

    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO notes (title, content, color, category_id)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$title, $content, $color, $category_id ?: null]);

            setFlashMessage('success', 'Note created successfully!');
            redirect('index.php');
        } catch (PDOException $e) {
            $errors['database'] = 'Failed to create note. Please try again.';
        }
    }
}
?>

<!-- HTML Form -->
<form method="POST" action="">
    <input type="text" name="title" value="<?= sanitize($title) ?>" required>
    <textarea name="content"><?= sanitize($content) ?></textarea>
    <button type="submit">Create Note</button>
</form>
```

### Key Concepts:

1. **`$_SERVER['REQUEST_METHOD']`**: Check if GET or POST request
2. **`$_POST`**: Array containing submitted form data
3. **Null Coalescing**: `$_POST['title'] ?? ''` returns empty string if not set
4. **INSERT Query**: Add new record to database
5. **Form Repopulation**: Show entered values if validation fails

---

## Step 7: Read Notes (index.php) - The "R" in CRUD

### What You'll Learn
- Fetching data from database
- Looping through results in HTML
- Conditional rendering

### Code:

```php
<?php
$pageTitle = 'All Notes';
require_once 'includes/header.php';

// Get all notes (not archived)
$notes = getAllNotes($pdo, false);
?>

<?php if (empty($notes)): ?>
    <div class="empty-state">
        <h2>No Notes Yet</h2>
        <a href="create.php">Create Note</a>
    </div>
<?php else: ?>
    <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
            <div class="note-card" style="background-color: <?= sanitize($note['color']) ?>">
                <h3><?= sanitize($note['title']) ?></h3>
                <p><?= nl2br(sanitize($note['content'])) ?></p>
                <a href="edit.php?id=<?= $note['id'] ?>">Edit</a>
                <a href="delete.php?id=<?= $note['id'] ?>">Delete</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
```

### Key Concepts:

1. **`foreach`**: Loop through each note in the array
2. **Alternative Syntax**: `if(): ... endif;` is cleaner in templates
3. **`nl2br()`**: Convert newlines to `<br>` tags for display
4. **Query Parameters**: Pass note ID via URL (`?id=123`)

---

## Step 8: Update Note (edit.php) - The "U" in CRUD

### What You'll Learn
- Fetching single record by ID
- Pre-filling form with existing data
- UPDATE query

### Code:

```php
<?php
$pageTitle = 'Edit Note';
require_once 'includes/header.php';

// Get note ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch existing note
$note = getNoteById($pdo, $id);

if (!$note) {
    setFlashMessage('error', 'Note not found!');
    redirect('index.php');
}

// Pre-fill form with existing values
$title = $note['title'];
$content = $note['content'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');

    // Validation...

    if (empty($errors)) {
        $stmt = $pdo->prepare("
            UPDATE notes
            SET title = ?, content = ?, color = ?, category_id = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $content, $color, $category_id, $id]);

        setFlashMessage('success', 'Note updated!');
        redirect('index.php');
    }
}
```

### Key Concepts:

1. **Type Casting**: `(int)$_GET['id']` ensures ID is integer (security)
2. **UPDATE Query**: Modify existing record WHERE id matches
3. **Pre-filling**: Show current values in form fields

---

## Step 9: Delete Note (delete.php) - The "D" in CRUD

### What You'll Learn
- DELETE query
- Confirmation before destructive actions

### Code:

```php
<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Check if note exists first
    $note = getNoteById($pdo, $id);

    if ($note) {
        $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
        $stmt->execute([$id]);
        setFlashMessage('success', 'Note deleted!');
    } else {
        setFlashMessage('error', 'Note not found!');
    }
}

redirect('index.php');
```

### Key Concepts:

1. **DELETE Query**: Remove record from database
2. **Validate Existence**: Check if note exists before deleting
3. **No Template**: This page only processes, then redirects

### Important Security Note:
In production, use POST requests for deletions, not GET. GET requests can be triggered by bots or prefetching.

---

## Step 10: Search Functionality (search.php)

### What You'll Learn
- GET parameters for search queries
- SQL LIKE for pattern matching

### Code:

```php
<?php
$pageTitle = 'Search Results';
require_once 'includes/header.php';

$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$notes = [];

if (!empty($query)) {
    $notes = searchNotes($pdo, $query);
}
?>

<p>Found <?= count($notes) ?> results for "<?= sanitize($query) ?>"</p>

<!-- Display results same as index.php -->
```

### Key Concepts:

1. **Search Form**: `<form method="GET">` puts data in URL
2. **`$_GET['q']`**: Access query string parameters
3. **SQL LIKE**: `WHERE title LIKE '%keyword%'` for partial matching

---

## Security Best Practices Used

1. **Prepared Statements**: Never concatenate user input into SQL
2. **Input Sanitization**: `htmlspecialchars()` prevents XSS
3. **Type Casting**: `(int)$id` ensures numeric IDs
4. **CSRF Protection**: (Not implemented - add for production)

---

## Exercises to Practice

1. **Add Categories Page**: Create CRUD for categories
2. **Add User Authentication**: Login/register system
3. **Add Tags**: Many-to-many relationship with notes
4. **Export Notes**: Download notes as JSON or PDF
5. **Add CSRF Tokens**: Protect forms from cross-site attacks

---

## Useful SQL Queries

```sql
-- Get all notes with categories
SELECT n.*, c.name as category_name
FROM notes n
LEFT JOIN categories c ON n.category_id = c.id;

-- Count notes per category
SELECT c.name, COUNT(n.id) as count
FROM categories c
LEFT JOIN notes n ON c.id = n.category_id
GROUP BY c.id;

-- Get recently updated notes
SELECT * FROM notes
ORDER BY updated_at DESC
LIMIT 5;
```

---

## Resources for Further Learning

- [PHP Documentation](https://www.php.net/docs.php)
- [PDO Tutorial](https://www.php.net/manual/en/book.pdo.php)
- [SQL Tutorial](https://www.w3schools.com/sql/)
- [OWASP Security Guidelines](https://owasp.org/www-project-web-security-testing-guide/)

---

Happy Coding!
