<?php
/**
 * Database Configuration
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Database Credentials
define('DB_HOST', 'sql211.infinityfree.com');
define('DB_NAME', 'if0_38847546_hisab');
define('DB_USER', 'if0_38847546');
define('DB_PASS', 'SubtitleSync');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATION', 'utf8mb4_unicode_ci');

// PDO Options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

// Connection Timeout
define('DB_TIMEOUT', 30);

// Retry Settings
define('DB_MAX_RETRIES', 3);
define('DB_RETRY_DELAY', 1); // seconds

// Database Table Prefix
define('DB_PREFIX', '');

// Enable Query Log (Debug Mode Only)
define('DB_LOG_QUERIES', DEBUG_MODE);

// Slow Query Threshold (seconds)
define('DB_SLOW_QUERY_THRESHOLD', 2.0);
?>
