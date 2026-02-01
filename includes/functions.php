<?php
/**
 * Helper Functions
 * Simple Notes App
 */

/**
 * Sanitize string input
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
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Format date for display
 */
function formatDate($date) {
    return date('M j, Y g:i A', strtotime($date));
}

/**
 * Truncate text to specified length
 */
function truncate($text, $length = 100) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

/**
 * Get all notes for current user
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

/**
 * Get single note by ID (only if owned by current user)
 */
function getNoteById($pdo, $id) {
    $userId = $_SESSION['user_id'] ?? null;

    if ($userId) {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$id, $userId]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id IS NULL");
        $stmt->execute([$id]);
    }
    return $stmt->fetch();
}

/**
 * Get all categories
 */
function getAllCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Search notes for current user
 */
function searchNotes($pdo, $query) {
    $userId = $_SESSION['user_id'] ?? null;

    $sql = "SELECT n.*, c.name as category_name, c.color as category_color
            FROM notes n
            LEFT JOIN categories c ON n.category_id = c.id
            WHERE n.is_archived = 0
            AND (n.title ILIKE :query OR n.content ILIKE :query)";

    if ($userId) {
        $sql .= " AND n.user_id = :user_id";
    } else {
        $sql .= " AND n.user_id IS NULL";
    }

    $sql .= " ORDER BY n.updated_at DESC";

    $stmt = $pdo->prepare($sql);
    $params = ['query' => "%$query%"];
    if ($userId) {
        $params['user_id'] = $userId;
    }
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Toggle pin status
 */
function togglePin($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notes SET is_pinned = NOT is_pinned WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Archive a note
 */
function archiveNote($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notes SET is_archived = 1 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Restore a note from archive
 */
function restoreNote($pdo, $id) {
    $stmt = $pdo->prepare("UPDATE notes SET is_archived = 0 WHERE id = ?");
    return $stmt->execute([$id]);
}

/**
 * Get available note colors
 */
function getNoteColors() {
    return [
        '#ffffff' => 'White',
        '#fff9c4' => 'Yellow',
        '#c8e6c9' => 'Green',
        '#bbdefb' => 'Blue',
        '#f8bbd0' => 'Pink',
        '#ffccbc' => 'Orange',
        '#e1bee7' => 'Purple',
        '#b2ebf2' => 'Cyan'
    ];
}

// ==========================================
// Authentication Functions
// ==========================================

/**
 * Register a new user
 */
function registerUser($pdo, $username, $email, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO users (username, email, password)
        VALUES (?, ?, ?)
    ");

    return $stmt->execute([$username, $email, $hashedPassword]);
}

/**
 * Check if username exists
 */
function usernameExists($pdo, $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    return $stmt->fetch() !== false;
}

/**
 * Check if email exists
 */
function emailExists($pdo, $email) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch() !== false;
}

/**
 * Authenticate user login
 */
function loginUser($pdo, $username, $password) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        return true;
    }

    return false;
}

/**
 * Logout user
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
 * Get current logged in user
 */
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'],
            'email' => $_SESSION['email']
        ];
    }
    return null;
}

/**
 * Require user to be logged in
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Please login to access this page.');
        redirect('login.php');
    }
}
