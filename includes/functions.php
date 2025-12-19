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
 * Get all notes
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
 * Get all categories
 */
function getAllCategories($pdo) {
    $stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
    return $stmt->fetchAll();
}

/**
 * Search notes
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
