<?php
require_once __DIR__ . '/../config/database.php';

try {
    $pdo = get_db_connection();
    
    // Check if column exists (to avoid errors on multiple runs)
    $stmt = $pdo->query("PRAGMA table_info(quotes)");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $columnExists = false;
    foreach ($columns as $col) {
        if ($col['name'] === 'mail_sent') {
            $columnExists = true;
            break;
        }
    }

    if (!$columnExists) {
        $pdo->exec("ALTER TABLE quotes ADD COLUMN mail_sent INTEGER DEFAULT 0");
        echo "Successfully added 'mail_sent' column to 'quotes' table.\n";
    } else {
        echo "Column 'mail_sent' already exists.\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
