<?php
/**
 * Dynamic Database Connection Factory for DecoraTV
 * Supports SQLite and MySQL/MariaDB
 */

require_once __DIR__ . '/config.php';

// Maintain backward compatibility for DB_PATH if needed elsewhere
if (!defined('DB_PATH')) {
    define('DB_PATH', DB_SQLITE_PATH);
}

function get_db_connection() {
    try {
        if (DB_DRIVER === 'mysql') {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $pdo = new PDO($dsn, DB_USER, DB_PASS);
        } else {
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH);
        }

        // Standard PDO Attributes
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error (" . DB_DRIVER . "): " . $e->getMessage());
        // For security, don't show full error to user in production
        die("System error: Unable to connect to database. Please check your config.");
    }
}
