# Simple Notes App - PHP CRUD Application

A beginner-friendly notes application built with PHP and PostgreSQL demonstrating fundamental web development concepts.

## Live Demo

🔗 **[https://simple-notes-php.onrender.com](https://simple-notes-php.onrender.com)**

## Tech Stack

| Technology | Purpose |
|------------|---------|
| **PHP 8.2** | Backend language |
| **PostgreSQL** | Database (via Supabase) |
| **PDO** | Database abstraction layer |
| **HTML5/CSS3** | Frontend markup & styling |
| **CSS Variables** | Dark/Light theme support |
| **Docker** | Containerization for deployment |
| **Apache** | Web server (in Docker) |

## Features

- **User Authentication**: Register, Login, Logout with session-based auth
- **User-Specific Notes**: Each user sees only their own notes
- Create, Read, Update, Delete (CRUD) notes
- Pin important notes to the top
- Archive notes for later
- Search notes by title or content
- Color-coded notes
- Categorize notes (Personal, Work, Ideas, Shopping, Important)
- Dark/Light theme toggle
- Responsive design
- Secure password hashing with `password_hash()`

## Project Structure

```
notesapp/
├── config/
│   └── database.php      # Database connection & auto-initialization
├── includes/
│   ├── header.php        # HTML header, navigation, session, theme toggle
│   ├── footer.php        # HTML footer
│   ├── functions.php     # Helper functions (CRUD, validation & auth)
│   ├── noteform.php      # Reusable note form partial (shared by create & edit)
│   └── env_loader.php    # .env file loader
├── css/
│   └── style.css         # Styling (with dark mode support)
├── images/
│   ├── login-illustration.svg
│   └── register-illustration.svg
├── index.php             # Home page - List all notes
├── note.php              # Note controller: Create / Edit / Delete
├── search.php            # Search results page
├── archive.php           # View/manage archived notes
├── register.php          # User registration page
├── login.php             # User login page
├── logout.php            # Logout handler
├── test_db.php           # Database connection test script
├── Dockerfile            # Docker configuration for deployment
├── .env                  # Environment variables (DB credentials)
└── README.md             # This file
```

## Requirements

- PHP 8.0 or higher
- PDO with PostgreSQL driver (`php-pgsql`)
- A PostgreSQL database (e.g. [Supabase](https://supabase.com) free tier)
- Web server (Apache, Nginx, or PHP built-in server)
- A `.env` file with your `DATABASE_URL`

## Installation (Local Development)

1. Clone or download this project
2. Navigate to the project directory
3. Start the PHP built-in server:
   ```bash
   php -S localhost:8000
   ```
4. Open `http://localhost:8000` in your browser

The database will be automatically created with sample categories on first run.

## Deployment (Docker/Render.com)

### Using Docker locally:
```bash
docker build -t notesapp .
docker run -p 8000:10000 -e PORT=10000 notesapp
```

### Deploy to Render.com:
1. Push code to GitHub
2. Connect repo to Render.com
3. It will auto-detect the Dockerfile and deploy

## What to Code First (Recommended Order)

If you're building this from scratch, follow this order:

```
1. config/database.php          → Database connection first
2. includes/env_loader.php      → Load .env variables
3. includes/functions.php       → Helper functions (sanitize, redirect, flash, validateNoteInput)
4. includes/header.php          → Common HTML header & navigation
5. includes/footer.php          → Common HTML footer
6. css/style.css                → Basic styling
7. index.php                    → Read notes (the "R" in CRUD)
8. includes/noteform.php        → Reusable form partial
9. note.php                     → Create / Edit / Delete (C, U, D in CRUD) — single controller
10. search.php                  → Search functionality
11. archive.php                 → Archive feature
12. register.php                → User registration
13. login.php                   → User login
14. logout.php                  → Logout handler
```

## Troubleshooting

### Categories not showing in dropdown
**Problem**: Category dropdown is empty when creating/editing notes.

**Solution**: The database needs to be reinitialized. Delete the `data/notes.db` file and refresh the page. Categories will be auto-created.

```bash
rm data/notes.db
```

### Database connection failed
**Problem**: Error "Database connection failed" on page load.

**Solution**:
1. Ensure `data/` directory exists and is writable
2. Check PHP has SQLite extension: `php -m | grep sqlite`
3. On Linux/Mac: `chmod 775 data/`

### Session/Login issues
**Problem**: Can't stay logged in or session errors.

**Solution**:
1. Ensure `session_start()` is called before any output
2. Check PHP session directory is writable
3. Clear browser cookies and try again

### Docker container won't start
**Problem**: Container exits immediately or port issues.

**Solution**:
```bash
# Check logs
docker logs <container_id>

# Ensure port is available
docker run -p 8080:10000 -e PORT=10000 notesapp
```

### Notes not showing after login
**Problem**: User logs in but sees no notes.

**Solution**: Each user only sees their own notes. Notes created before login (with `user_id = NULL`) won't appear. Create new notes after logging in.

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

## Step 6: Read Notes (index.php) - The "R" in CRUD

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
        <a href="note.php?action=create">Create Note</a>
    </div>
<?php else: ?>
    <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
            <div class="note-card" style="background-color: <?= sanitize($note['color']) ?>">
                <h3><?= sanitize($note['title']) ?></h3>
                <p><?= nl2br(sanitize($note['content'])) ?></p>
                <a href="note.php?action=edit&id=<?= $note['id'] ?>">Edit</a>
                <a href="note.php?action=delete&id=<?= $note['id'] ?>">Delete</a>
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

## Step 7: Reusable Form Partial (includes/noteform.php)

### What You'll Learn

- Extracting repeated HTML into a reusable partial
- Passing data to a partial via variables
- The partial naming convention (`noteform.php` lives in `includes/`)

### Concept:

Both the create and edit actions display the **same form** (title, content, category, colour picker). Instead of duplicating the HTML, we write it once in `noteform.php` and `require_once` it wherever needed.

```php
// In the caller (note.php), set variables then include the partial:
$submitLabel = 'Create Note';           // or 'Update Note'
require_once 'includes/noteform.php';  // renders the form
```

### Key Concepts:

1. **DRY Principle**: Don't Repeat Yourself — one place to change the form
2. **Partials**: Template fragments that are not full pages (convention: lives in `includes/`)
3. **Variable scope**: Variables set in the caller are visible inside `require_once`

---

## Step 8: Note Controller (note.php) - C, U & D in CRUD

### What You'll Learn

- The **Front Controller** pattern — one file, multiple actions
- Action-based routing with `?action=`
- Shared validation helper `validateNoteInput()`
- INSERT vs UPDATE in one place

### Routes:

| URL | What happens |
|---|---|
| `note.php?action=create` | Show empty form / handle POST → INSERT |
| `note.php?action=edit&id=X` | Pre-fill form / handle POST → UPDATE |
| `note.php?action=delete&id=X` | DELETE row → redirect (no form) |

### Process Flow:

```
note.php
 │
 ├─ action=delete → DELETE query → redirect('index.php')
 │
 ├─ action=edit   → load note from DB
 │                   ↓
 └─ action=create  → (default values)
                     ↓
                  On POST: validateNoteInput($_POST)
                     ├── errors?  → re-render form with errors
                     └── clean?   → INSERT or UPDATE → redirect
```

### Code Explanation:

```php
<?php
$action = $_GET['action'] ?? 'create';
$id     = (int)($_GET['id'] ?? 0);

// --- DELETE (no form needed) ---
if ($action === 'delete') {
    session_start();
    require_once 'config/database.php';
    require_once 'includes/functions.php';

    $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $_SESSION['user_id']]);

    setFlashMessage('success', 'Note deleted!');
    redirect('index.php');
}

// --- CREATE / EDIT (shared form) ---
$pageTitle = $action === 'edit' ? 'Edit Note' : 'Create Note';
require_once 'includes/header.php';

if ($action === 'edit') {
    $note = getNoteById($pdo, $id);   // ownership check inside
    $title   = $note['title'];
    $content = $note['content'];
    // ...
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = validateNoteInput($_POST);  // ← reusable helper
    $errors = $result['errors'];
    $data   = $result['data'];

    if (empty($errors)) {
        if ($action === 'edit') {
            // UPDATE existing
        } else {
            // INSERT new
        }
        redirect('index.php');
    }
}

$submitLabel = $action === 'edit' ? 'Update Note' : 'Create Note';
require_once 'includes/noteform.php';  // ← shared form partial
```

### Key Concepts:

1. **Front Controller**: One file handles all actions for a resource — common in Laravel, Symfony, etc.
2. **`?action=`**: Simple routing without a framework
3. **`validateNoteInput()`**: Shared validation in `functions.php` — change once, fixed everywhere
4. **`rowCount()`**: Check if DELETE actually removed a row (vs note not found)
5. **Type Casting**: `(int)$_GET['id']` ensures the ID is always a number (prevents injection)

---

## Step 9: Search Functionality (search.php)

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

## Step 10: User Authentication System

### What You'll Learn

- Session-based authentication
- Secure password hashing
- User registration and login
- Protecting user data

### Database Schema (Users Table):

```sql
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Add user_id to notes table
ALTER TABLE notes ADD COLUMN user_id INTEGER NULL REFERENCES users(id) ON DELETE CASCADE;
```

### Authentication Functions (includes/functions.php):

```php
<?php
/**
 * Register a new user
 */
function registerUser($pdo, $username, $email, $password) {
    // ALWAYS hash passwords - never store plain text!
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password)
        VALUES (?, ?, ?)
    ");

    return $stmt->execute([$username, $email, $hashedPassword]);
}

/**
 * Authenticate user login
 */
function loginUser($pdo, $username, $password) {
    // Allow login with username OR email
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    // Verify password against hash
    if ($user && password_verify($password, $user['password'])) {
        // Store user info in session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        return true;
    }

    return false;
}

/**
 * Logout user - destroy session
 */
function logoutUser() {
    unset($_SESSION['user_id']);
    unset($_SESSION['username']);
    unset($_SESSION['email']);
    session_destroy();
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Require login - redirect if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        redirect('login.php');
    }
}
```

### Key Concepts:

1. **`password_hash()`**: Creates secure bcrypt hash (never store plain passwords!)
2. **`password_verify()`**: Safely compare password against hash
3. **Session Variables**: Store user info after successful login
4. **`session_destroy()`**: Clean logout by destroying session

### Registration Flow (register.php):

```php
<?php
// Validate input
if (empty($username)) {
    $errors['username'] = 'Username is required';
} elseif (usernameExists($pdo, $username)) {
    $errors['username'] = 'Username is already taken';
}

// Check password match
if ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Passwords do not match';
}

// Register if no errors
if (empty($errors)) {
    registerUser($pdo, $username, $email, $password);
    loginUser($pdo, $username, $password);  // Auto-login
    redirect('index.php');
}
```

### Filtering Notes by User:

```php
<?php
/**
 * Get all notes for current user only
 */
function getAllNotes($pdo, $archived = false) {
    $userId = $_SESSION['user_id'] ?? null;

    $sql = "SELECT n.*, c.name as category_name, c.color as category_color
            FROM notes n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.is_archived = :archived";

    if ($userId) {
        $sql .= " AND n.user_id = :user_id";
    } else {
        $sql .= " AND n.user_id IS NULL";
    }

    $sql .= " ORDER BY n.is_pinned DESC, n.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $params = ['archived' => $archived ? 1 : 0];
    if ($userId) {
        $params['user_id'] = $userId;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}
```

---

## Security Best Practices Used

1. **Prepared Statements**: Never concatenate user input into SQL
2. **Input Sanitization**: `htmlspecialchars()` prevents XSS
3. **Type Casting**: `(int)$id` ensures numeric IDs
4. **Password Hashing**: Using `password_hash()` with bcrypt
5. **Session-Based Auth**: Secure user identification
6. **CSRF Protection**: (Not implemented - add for production)

---

## Exercises to Practice

1. **Add Categories Page**: Create CRUD for categories
2. ~~**Add User Authentication**: Login/register system~~ ✅ Implemented!
3. **Add Tags**: Many-to-many relationship with notes
4. **Export Notes**: Download notes as JSON or PDF
5. **Add CSRF Tokens**: Protect forms from cross-site attacks
6. **Password Reset**: Add forgot password functionality
7. **User Profile**: Allow users to update their profile info

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
