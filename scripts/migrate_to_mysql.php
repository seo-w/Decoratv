<?php
/**
 * DecoraTV - SQLite to MySQL Migration Tool
 * This script migrates your existing data from SQLite to a MySQL/MariaDB database.
 */

require_once __DIR__ . '/../config/config.php';

echo "=== DecoraTV Migration Tool (SQLite -> MySQL) ===\n";

// 1. Establish Connections
try {
    echo "[1/4] Connecting to databases...\n";
    $sqlite = new PDO('sqlite:' . DB_SQLITE_PATH);
    $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $mysql_dsn = "mysql:host=" . DB_HOST . ";charset=utf8mb4";
    $mysql_init = new PDO($mysql_dsn, DB_USER, DB_PASS);
    $mysql_init->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create database if not exists
    $mysql_init->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "`");
    $mysql_init->exec("USE `" . DB_NAME . "`");
    echo "      Connected to MySQL and Database '" . DB_NAME . "' is ready.\n";
} catch (PDOException $e) {
    die("ERROR: Could not connect to databases. " . $e->getMessage() . "\n");
}

// 2. Define MySQL Schema
$tables = [
    'materials' => "CREATE TABLE materials (
        id INT AUTO_INCREMENT PRIMARY KEY,
        type VARCHAR(50) NOT NULL,
        name VARCHAR(255) NOT NULL,
        image_path TEXT NOT NULL,
        artist VARCHAR(255) DEFAULT NULL,
        group_name VARCHAR(100) DEFAULT 'General',
        internal_id VARCHAR(100) UNIQUE NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    'quotes' => "CREATE TABLE quotes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_name VARCHAR(255) NOT NULL,
        customer_last_name VARCHAR(255),
        customer_email VARCHAR(255) NOT NULL,
        customer_phone VARCHAR(50),
        selection_json TEXT NOT NULL,
        status VARCHAR(50) DEFAULT 'pending',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    'settings' => "CREATE TABLE settings (
        `key` VARCHAR(100) PRIMARY KEY,
        `value` TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

    'users' => "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
];

// 3. Recreate Tables and Migrate Data
echo "[2/4] Recreating tables in MySQL...\n";
foreach ($tables as $name => $sql) {
    $mysql_init->exec("DROP TABLE IF EXISTS `$name` ");
    $mysql_init->exec($sql);
    echo "      Table '$name' created.\n";
}

echo "[3/4] Migrating Data...\n";
$target_tables = ['materials', 'quotes', 'settings', 'users'];

foreach ($target_tables as $table) {
    echo "      Migrating '$table'...";
    $data = $sqlite->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($data) > 0) {
        $columns = array_keys($data[0]);
        $col_list = implode('`, `', $columns);
        $placeholders = implode(',', array_fill(0, count($columns), '?'));
        
        $insert_sql = "INSERT INTO `$table` (`$col_list`) VALUES ($placeholders)";
        $stmt = $mysql_init->prepare($insert_sql);
        
        foreach ($data as $row) {
            $stmt->execute(array_values($row));
        }
        echo " OK (" . count($data) . " rows)\n";
    } else {
        echo " Skipped (Empty)\n";
    }
}

echo "[4/4] Finalizing...\n";
echo "\nSUCCESS: Migration completed!\n";
echo "IMPORTANT: Now update your 'config/config.php' to define DB_DRIVER as 'mysql'.\n";
?>
