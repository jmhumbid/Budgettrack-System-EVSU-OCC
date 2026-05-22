<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = getDB();

    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'password_reset_tokens'");
    if ($stmt->rowCount() > 0) {
        echo "Table 'password_reset_tokens' already exists. Skipping creation.\n";
    } else {
        $sql = "
        CREATE TABLE password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(255) NOT NULL UNIQUE,
            expires_at TIMESTAMP NOT NULL,
            used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_token (token),
            INDEX idx_user_id (user_id),
            INDEX idx_expires_at (expires_at)
        );";
        $pdo->exec($sql);
        echo "Table 'password_reset_tokens' created successfully!\n";
    }

    // Add password_change_required column to users table if it doesn't exist
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'password_change_required'");
    if ($stmt->rowCount() == 0) {
        $sql = "ALTER TABLE users ADD COLUMN password_change_required BOOLEAN DEFAULT FALSE";
        $pdo->exec($sql);
        echo "Column 'password_change_required' added to users table!\n";
    } else {
        echo "Column 'password_change_required' already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
