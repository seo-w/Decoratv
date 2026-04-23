<?php
/**
 * Global Configuration for DecoraTV
 */

// Database Switch: 'sqlite' or 'mysql'
define('DB_DRIVER', 'sqlite'); 

// MySQL / MariaDB Settings (Only used if DB_DRIVER is 'mysql')
define('DB_HOST', 'localhost');
define('DB_NAME', 'decoratv');
define('DB_USER', 'root');
define('DB_PASS', '');

// SQLite Settings (Only used if DB_DRIVER is 'sqlite')
define('DB_SQLITE_PATH', __DIR__ . '/../database/decoratv.sqlite');
