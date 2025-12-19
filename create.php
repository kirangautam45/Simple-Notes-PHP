<?php
$pageTitle = 'Create Note';
require_once 'includes/header.php';

$errors = [];
$title = '';
$content = '';
$color = '#ffffff';
$category_id = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $userId = $_SESSION['user_id'] ?? null;
            $stmt = $pdo->prepare("
                INSERT INTO notes (title, content, color, category_id, user_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $content, $color, $category_id ?: null, $userId]);

            setFlashMessage('success', 'Note created successfully!');
            redirect('index.php');
        } catch (PDOException $e) {
            $errors['database'] = 'Failed to create note. Please try again.';
        }
    }
}

$categories = getAllCategories($pdo);
$colors = getNoteColors();
?>

<div class="page-header">
    <h1>Create New Note</h1>
    <a href="index.php" class="btn btn-secondary">Back to Notes</a>
</div>

<div class="form-container">
    <?php if (isset($errors['database'])): ?>
        <div class="alert alert-error"><?= $errors['database'] ?></div>
    <?php endif; ?>

    <form method="POST" action="" id="noteForm">
        <div class="form-group">
            <label for="title">Title <span style="color: var(--danger-color);">*</span></label>
            <input type="text"
                   id="title"
                   name="title"
                   value="<?= sanitize($title) ?>"
                   placeholder="Enter note title..."
                   maxlength="255"
                   required>
            <?php if (isset($errors['title'])): ?>
                <span class="error"><?= $errors['title'] ?></span>
            <?php endif; ?>
            <div class="char-count"><span id="titleCount"><?= strlen($title) ?></span>/255</div>
        </div>

        <div class="form-group">
            <label for="content">Content</label>
            <textarea id="content"
                      name="content"
                      placeholder="Write your note here..."><?= sanitize($content) ?></textarea>
            <div class="char-count"><span id="contentCount"><?= strlen($content) ?></span> characters</div>
        </div>

        <div class="form-group">
            <label for="category_id">Category</label>
            <select id="category_id" name="category_id">
                <option value="">No Category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"
                            <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                        <?= sanitize($cat['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Note Color</label>
            <div class="color-options">
                <?php foreach ($colors as $hex => $name): ?>
                    <input type="radio"
                           name="color"
                           value="<?= $hex ?>"
                           id="color-<?= str_replace('#', '', $hex) ?>"
                           <?= $color === $hex ? 'checked' : '' ?>
                           style="display: none;">
                    <label for="color-<?= str_replace('#', '', $hex) ?>"
                           class="color-option"
                           style="background-color: <?= $hex ?>;"
                           title="<?= $name ?>">
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-actions">
            <a href="index.php" class="btn btn-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Create Note</button>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
