<?php
session_start();
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
        <div class="container">
            <a href="index.php" class="logo">Simple Notes</a>
            <div class="nav-links">
                <a href="index.php" class="<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">All Notes</a>
                <a href="create.php" class="<?= basename($_SERVER['PHP_SELF']) === 'create.php' ? 'active' : '' ?>">+ New Note</a>
                <a href="archive.php" class="<?= basename($_SERVER['PHP_SELF']) === 'archive.php' ? 'active' : '' ?>">Archive</a>
            </div>
            <form action="search.php" method="GET" class="search-form">
                <input type="text" name="q" placeholder="Search notes..." value="<?= isset($_GET['q']) ? sanitize($_GET['q']) : '' ?>">
                <button type="submit">Search</button>
            </form>
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
