<?php
$pageTitle = 'All Notes';
require_once 'includes/header.php';

// Handle pin toggle
if (isset($_GET['pin'])) {
    $id = (int)$_GET['pin'];
    if (togglePin($pdo, $id)) {
        setFlashMessage('success', 'Note pin status updated!');
    }
    redirect('index.php');
}

// Get all notes
$notes = getAllNotes($pdo, false);
$categories = getAllCategories($pdo);

// Count stats
$totalNotes = count($notes);
$pinnedNotes = count(array_filter($notes, fn($n) => $n['is_pinned']));
?>

<div class="page-header">
    <h1>My Notes</h1>
    <a href="create.php" class="btn btn-primary">+ New Note</a>
</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <div class="number"><?= $totalNotes ?></div>
        <div class="label">Total Notes</div>
    </div>
    <div class="stat-card">
        <div class="number"><?= $pinnedNotes ?></div>
        <div class="label">Pinned</div>
    </div>
</div>

<?php if (empty($notes)): ?>
    <div class="empty-state">
        <div class="empty-state-icon">📝</div>
        <h2>No Notes Yet</h2>
        <p>Start by creating your first note!</p>
        <a href="create.php" class="btn btn-primary">Create Note</a>
    </div>
<?php else: ?>
    <div class="notes-grid">
        <?php foreach ($notes as $note): ?>
            <div class="note-card <?= $note['is_pinned'] ? 'pinned' : '' ?>"
                 style="background-color: <?= sanitize($note['color']) ?>">

                <?php if ($note['is_pinned']): ?>
                    <span class="pin-badge" title="Pinned">📌</span>
                <?php endif; ?>

                <div class="note-header">
                    <h3><?= sanitize($note['title']) ?></h3>
                    <?php if ($note['category_name']): ?>
                        <span class="note-category"
                              style="background-color: <?= sanitize($note['category_color']) ?>">
                            <?= sanitize($note['category_name']) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="note-content">
                    <?= nl2br(sanitize(truncate($note['content'], 150))) ?>
                </div>

                <div class="note-footer">
                    <span><?= formatDate($note['updated_at']) ?></span>
                    <div class="note-actions">
                        <a href="index.php?pin=<?= $note['id'] ?>"
                           class="pin"
                           title="<?= $note['is_pinned'] ? 'Unpin' : 'Pin' ?>">
                            <?= $note['is_pinned'] ? '📍' : '📌' ?>
                        </a>
                        <a href="edit.php?id=<?= $note['id'] ?>" title="Edit">✏️</a>
                        <a href="archive.php?archive=<?= $note['id'] ?>" title="Archive">📦</a>
                        <a href="delete.php?id=<?= $note['id'] ?>"
                           class="delete"
                           title="Delete"
                           onclick="return confirm('Are you sure you want to delete this note?')">🗑️</a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
