-- =====================================================
-- Simple Notes App - Database Schema
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS notes_app;
USE notes_app;



-- =====================================================
-- Notes Table
-- =====================================================
CREATE TABLE IF NOT EXISTS notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
  
    title VARCHAR(255) NOT NULL,
    content TEXT,
    color VARCHAR(7) DEFAULT '#ffffff',
    is_pinned BOOLEAN DEFAULT FALSE,
    is_archived BOOLEAN DEFAULT FALSE,
    category_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_is_archived (is_archived),
    INDEX idx_is_pinned (is_pinned),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================
-- Sample Categories
-- =====================================================
INSERT INTO categories (name, color) VALUES
('Personal', '#e91e63'),
('Work', '#2196f3'),
('Ideas', '#ff9800'),
('Shopping', '#4caf50'),
('Important', '#f44336');

-- =====================================================
-- Sample Notes (for test user with id=1)
-- =====================================================
INSERT INTO notes (user_id, title, content, color, is_pinned, category_id) VALUES
(1, 'Welcome to Notes App!', 'This is your first note. You can:\n- Edit this note\n- Create new notes\n- Pin important notes\n- Archive old notes\n- Search your notes\n\nEnjoy organizing your thoughts!', '#fff9c4', TRUE, NULL),

(1, 'Shopping List', '- Milk\n- Eggs\n- Bread\n- Butter\n- Cheese\n- Fruits\n- Vegetables', '#c8e6c9', FALSE, 4),

(1, 'Project Ideas', '1. Build a todo app with React\n2. Create a blog with PHP\n3. Make a portfolio website\n4. Learn a new programming language\n5. Contribute to open source', '#bbdefb', FALSE, 3),

(1, 'Meeting Notes - Monday', 'Topics discussed:\n- Quarterly goals review\n- Team performance\n- Next sprint planning\n- Budget allocation\n\nAction items:\n- Follow up with marketing\n- Prepare presentation\n- Send meeting summary', '#f8bbd0', FALSE, 2),

(1, 'Personal Goals 2024', '- Learn a new skill\n- Read 12 books\n- Exercise regularly\n- Save more money\n- Travel to a new place\n- Spend more time with family', '#e1bee7', FALSE, 1),

(1, 'Quick Tip', 'Use this app to organize your thoughts and ideas!', '#b2ebf2', TRUE, NULL),

(1, 'Recipe: Pasta', 'Ingredients:\n- 200g pasta\n- 2 cloves garlic\n- Olive oil\n- Parmesan cheese\n- Salt & pepper\n- Fresh basil\n\nSteps:\n1. Boil pasta\n2. Sauté garlic in olive oil\n3. Mix pasta with garlic oil\n4. Add cheese and seasoning\n5. Garnish with basil', '#ffccbc', FALSE, 1);

-- =====================================================
-- Useful Queries
-- =====================================================

-- Get all active notes (not archived)
-- SELECT * FROM notes WHERE is_archived = FALSE ORDER BY is_pinned DESC, updated_at DESC;

-- Get notes by category
-- SELECT n.*, c.name as category_name FROM notes n LEFT JOIN categories c ON n.category_id = c.id;

-- Search notes
-- SELECT * FROM notes WHERE title LIKE '%keyword%' OR content LIKE '%keyword%';

-- Count notes per category
-- SELECT c.name, COUNT(n.id) as note_count FROM categories c LEFT JOIN notes n ON c.id = n.category_id GROUP BY c.id;
