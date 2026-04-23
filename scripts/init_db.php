<?php
/**
 * Database Initializer for DecoraTV
 * Run this script to create the SQLite file and default admin user.
 */

$dbPath = __DIR__ . '/../database/decoratv.sqlite';
$schemaPath = __DIR__ . '/../database/schema.sql';

try {
    // Create (or open) the database
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Initializing database at $dbPath...\n";

    // Read and execute schema
    $sql = file_get_contents($schemaPath);
    $pdo->exec($sql);

    // Create default admin user if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $password = 'admin'; // DEFAULT PASSWORD
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, full_name) VALUES (?, ?, ?)");
        $stmt->execute(['admin', $hash, 'Main Admin']);
        echo "Default admin user created: admin / admin\n";
    }

    echo "Database setup completed successfully!\n";

    // Security: Create .htaccess in database folder
    $htaccessContent = "Require all denied";
    file_put_contents(__DIR__ . '/../database/.htaccess', $htaccessContent);
    echo ".htaccess security file created in /database folder.\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
