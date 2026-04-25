<?php
require_once __DIR__ . '/../config/database.php';
try {
    $pdo = get_db_connection();
    $pdo->exec("INSERT OR IGNORE INTO settings (key, value) VALUES ('smtp_encryption', 'tls')");
    echo "Successfully added 'smtp_encryption' to settings.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
