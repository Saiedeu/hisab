<?php
/**
 * Application Configuration
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// App Settings
define('APP_NAME', 'Hisab Potro');
define('APP_VERSION', '2.0.0');
define('APP_URL', 'https://hisab-potro.free.nf');
define('APP_PATH', __DIR__ . '/..');

// Timezone
date_default_timezone_set('Asia/Dhaka');

// Session Settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 if using HTTPS

// Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Security
define('HASH_ALGO', PASSWORD_DEFAULT);
define('SESSION_LIFETIME', 86400); // 24 hours

// Pagination
define('ITEMS_PER_PAGE', 20);

// Currency
define('CURRENCY', '৳');
define('CURRENCY_CODE', 'BDT');

// Date Format
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'd/m/Y');

// Upload Settings
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('UPLOAD_PATH', APP_PATH . '/uploads');

// Sync Settings
define('SYNC_INTERVAL', 30); // seconds
define('MAX_SESSION_AGE', 3600); // 1 hour

// Email Settings (for receipts)
define('ADMIN_EMAIL', 'exchangebridge.bd@gmail.com');
define('EMAIL_FROM_NAME', 'Hisab Potro');

// Debug Mode
define('DEBUG_MODE', true);

// Maintenance Mode
define('MAINTENANCE_MODE', false);
?>
