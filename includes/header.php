<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// Check if flash message exists for meta refresh
$hasFlash = isset($_SESSION['flash']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if ($hasFlash): ?>
    <meta http-equiv="refresh" content="5">
    <?php endif; ?>
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?>Simple Notes App</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">Simple Notes</a>
            <!-- $_SERVER['PHP_SELF'] - Returns the full path of the current script (e.g., /notesapp/login.php) -->
            <?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
            <?php if ($currentPage !== 'login.php' && $currentPage !== 'register.php'): ?>
            <div class="nav-links">
                <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>">All Notes</a>
                <a href="create.php" class="<?= $currentPage === 'create.php' ? 'active' : '' ?>">+ New Note</a>
                <a href="archive.php" class="<?= $currentPage === 'archive.php' ? 'active' : '' ?>">Archive</a>
            </div>
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Search notes..." value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
                <button type="submit">Search</button>
            </form>
            <?php endif; ?>
            <div class="auth-links">
                <?php if (isLoggedIn()): ?>
                    <span class="user-greeting">Hello, <?= sanitize($_SESSION['username']) ?></span>
                    <a href="logout.php" class="btn btn-sm btn-logout">Logout</a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-sm">Login</a>
                    <a href="register.php" class="btn btn-sm btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php
        $flash = getFlashMessage();
        if ($flash):
        ?>
        <div class="alert alert-<?= $flash['type'] ?>">
            <?= sanitize($flash['message']) ?>
        </div>
        <?php endif; ?>
