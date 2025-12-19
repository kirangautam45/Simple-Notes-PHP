<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // Check if note exists
    $note = getNoteById($pdo, $id);

    if ($note) {
        try {
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ?");
            $stmt->execute([$id]);
            setFlashMessage('success', 'Note deleted successfully!');
        } catch (PDOException $e) {
            setFlashMessage('error', 'Failed to delete note. Please try again.');
        }
    } else {
        setFlashMessage('error', 'Note not found!');
    }
} else {
    setFlashMessage('error', 'Invalid note ID!');
}

redirect('index.php');
