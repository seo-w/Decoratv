<?php
/**
 * DB Patch v1: Add is_active support to users table.
 */
require_once __DIR__ . '/../config/database.php';

$pdo = get_db_connection();

try {
    // Check if column exists
    $exists = false;
    if (DB_DRIVER === 'mysql') {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_active'");
        $exists = (bool)$stmt->fetch();
    } else {
        $stmt = $pdo->query("PRAGMA table_info(users)");
        $cols = $stmt->fetchAll(PDO::FETCH_COLUMN, 1);
        $exists = in_array('is_active', $cols);
    }

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active INTEGER DEFAULT 1");
        echo "Column 'is_active' added successfully.\n";
    } else {
        echo "Column 'is_active' already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error applying patch: " . $e->getMessage() . "\n";
}
