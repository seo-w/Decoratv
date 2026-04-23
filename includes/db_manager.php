<?php
/**
 * DecoraTV Database Manager
 * Handles Initialization, Fixes, and Data Migrations.
 */

require_once __DIR__ . '/../config/database.php';

class DBManager {
    private $pdo;
    private $schemaPath;

    public function __construct() {
        $this->pdo = get_db_connection();
        $this->schemaPath = __DIR__ . '/../database/schema.sql';
    }

    /**
     * Get list of tables currently in the database.
     */
    public function get_current_tables() {
        if (DB_DRIVER === 'mysql') {
            $stmt = $this->pdo->query("SHOW TABLES");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $stmt = $this->pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
    }

    /**
     * Get list of required tables from schema.sql.
     */
    public function get_required_tables() {
        return ['materials', 'quotes', 'settings', 'users'];
    }

    /**
     * Check if any required table is missing.
     */
    public function get_missing_tables() {
        $current = $this->get_current_tables();
        $required = $this->get_required_tables();
        return array_diff($required, $current);
    }

    /**
     * Initialize the database.
     * @param bool $clean If true, drops existing tables first.
     */
    public function initialize($clean = false) {
        if ($clean) {
            foreach ($this->get_required_tables() as $table) {
                $this->pdo->exec("DROP TABLE IF EXISTS `$table` ");
            }
        }

        $sql = file_get_contents($this->schemaPath);
        
        // Normalize SQL for MySQL if needed
        if (DB_DRIVER === 'mysql') {
            $sql = str_replace('AUTOINCREMENT', 'AUTO_INCREMENT', $sql);
            $sql = str_replace('INSERT OR IGNORE', 'INSERT IGNORE', $sql);
            $sql = str_replace('PRIMARY KEY AUTO_INCREMENT', 'INT AUTO_INCREMENT PRIMARY KEY', $sql);
            // Ensure proper types for MySQL
            $sql = str_replace('`key` TEXT PRIMARY KEY', '`key` VARCHAR(100) PRIMARY KEY', $sql);
            $sql = str_replace('username TEXT UNIQUE', 'username VARCHAR(100) UNIQUE', $sql);
        }

        // Execute schema parts
        try {
            // Split by semicolon but ignore inside comments/strings if possible
            // Simple split for this specific schema
            $queries = explode(';', $sql);
            foreach ($queries as $q) {
                $q = trim($q);
                if (!empty($q)) {
                    $this->pdo->exec($q);
                }
            }

            // Ensure at least one admin exists
            $this->ensure_admin();
            
            return true;
        } catch (PDOException $e) {
            error_log("DB Init Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Creates a default admin if none exists.
     */
    private function ensure_admin() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        if ($stmt->fetchColumn() == 0) {
            $hash = password_hash('admin', PASSWORD_DEFAULT);
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)");
            $stmt->execute(['admin', $hash, 'Main Admin']);
        }
    }

    /**
     * Migrates data from SQLite file to currently active MySQL connection.
     * Only works if DB_DRIVER is 'mysql'.
     */
    public function migrate_from_sqlite() {
        if (DB_DRIVER !== 'mysql') return ['error' => 'Migration only available in MySQL mode.'];
        if (!file_exists(DB_SQLITE_PATH)) return ['error' => 'SQLite file not found at ' . DB_SQLITE_PATH];

        try {
            $sqlite = new PDO('sqlite:' . DB_SQLITE_PATH);
            $sqlite->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $tables = $this->get_required_tables();
            $results = [];

            // First, ensure tables exist in MySQL
            $this->initialize(false);

            foreach ($tables as $table) {
                $data = $sqlite->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($data) > 0) {
                    // Clear target table before migration to avoid conflicts
                    $this->pdo->exec("DELETE FROM `$table` ");
                    
                    $columns = array_keys($data[0]);
                    $col_list = implode('`, `', $columns);
                    $placeholders = implode(',', array_fill(0, count($columns), '?'));
                    
                    $stmt = $this->pdo->prepare("INSERT INTO `$table` (`$col_list`) VALUES ($placeholders)");
                    foreach ($data as $row) {
                        $stmt->execute(array_values($row));
                    }
                    $results[$table] = count($data);
                } else {
                    $results[$table] = 0;
                }
            }

            // Rename SQLite file as requested
            rename(DB_SQLITE_PATH, DB_SQLITE_PATH . '.migrated_' . date('Ymd_His'));

            return ['success' => true, 'counts' => $results];

        } catch (Exception $e) {
            return ['error' => 'Migration Failed: ' . $e->getMessage()];
        }
    }
}
