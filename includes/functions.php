<?php
/**
 * Helper Functions
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load configuration
require_once __DIR__ . '/../config/app.php';

// Format currency
function formatCurrency($amount) {
    return CURRENCY . number_format($amount, 2);
}

// Format date
function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (!$date) return '';
    return date($format, strtotime($date));
}

// Format datetime
function formatDateTime($datetime, $format = DISPLAY_DATE_FORMAT . ' H:i') {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

// Get current date in database format
function getCurrentDate() {
    return date(DATE_FORMAT);
}

// Get current datetime in database format
function getCurrentDateTime() {
    return date(DATETIME_FORMAT);
}

// Sanitize input
function sanitize($input) {
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Validate phone number (Bangladesh format)
function validatePhone($phone) {
    // Remove spaces, dashes, parentheses
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);
    
    // Check if it starts with 01 and has 11 digits
    return preg_match('/^01[3-9]\d{8}$/', $phone);
}

// Format phone number
function formatPhone($phone) {
    if (!$phone) return '';
    
    // Remove all non-digit characters
    $phone = preg_replace('/\D/', '', $phone);
    
    // Format: 01XXX-XXXXXX
    if (strlen($phone) === 11) {
        return substr($phone, 0, 5) . '-' . substr($phone, 5);
    }
    
    return $phone;
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    
    return $randomString;
}

// Generate unique ID
function generateUniqueId($prefix = '') {
    return $prefix . time() . '_' . generateRandomString(5);
}

// Calculate age from date of birth
function calculateAge($dob) {
    if (!$dob) return '';
    
    $dobDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dobDate);
    
    return $age->y;
}

// Get time ago
function timeAgo($datetime) {
    if (!$datetime) return '';
    
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'এইমাত্র';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' মিনিট আগে';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' ঘন্টা আগে';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . ' দিন আগে';
    } elseif ($diff < 31536000) {
        return floor($diff / 2592000) . ' মাস আগে';
    } else {
        return floor($diff / 31536000) . ' বছর আগে';
    }
}

// Bangla numbers
function banglaNumber($number) {
    $bangla = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english, $bangla, $number);
}

// Convert English number to Bangla
function englishToBangla($str) {
    $bangla = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];
    $english = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    
    return str_replace($english, $bangla, $str);
}

// Get client IP address
function getClientIP() {
    $ipaddress = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    
    return $ipaddress;
}

// Get user agent
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
}

// Check if request is AJAX
function isAjaxRequest() {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

// Send JSON response
function sendJsonResponse($data, $statusCode = 200) {
    header_remove();
    header('Content-Type: application/json');
    http_response_code($statusCode);
    echo json_encode($data);
    exit;
}

// Send success response
function sendSuccessResponse($message, $data = null) {
    sendJsonResponse([
        'success' => true,
        'message' => $message,
        'data' => $data
    ]);
}

// Send error response
function sendErrorResponse($message, $statusCode = 400, $data = null) {
    sendJsonResponse([
        'success' => false,
        'message' => $message,
        'data' => $data
    ], $statusCode);
}

// Redirect with message
function redirect($url, $message = '', $type = 'info') {
    if ($message) {
        $_SESSION['flash_message'] = [
            'message' => $message,
            'type' => $type
        ];
    }
    
    header("Location: $url");
    exit;
}

// Get flash message
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Display flash message
function displayFlashMessage() {
    $message = getFlashMessage();
    if (!$message) return '';
    
    $alertClass = [
        'success' => 'alert-success',
        'error' => 'alert-danger',
        'warning' => 'alert-warning',
        'info' => 'alert-info'
    ];
    
    $class = $alertClass[$message['type']] ?? 'alert-info';
    
    return "<div class='alert $class alert-dismissible fade show' role='alert'>
                {$message['message']}
                <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
            </div>";
}

// Pagination helper
function paginate($totalItems, $itemsPerPage, $currentPage, $urlPattern) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    
    $pagination = [
        'current_page' => $currentPage,
        'last_page' => $totalPages,  // Add this for compatibility
        'total_pages' => $totalPages,
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => $currentPage - 1,
        'next_page' => $currentPage + 1,
        'from' => ($currentPage - 1) * $itemsPerPage + 1,
        'to' => min($currentPage * $itemsPerPage, $totalItems),
        'total' => $totalItems
    ];
    
    // Generate pagination links
    $links = [];
    
    // Previous link
    if ($pagination['has_previous']) {
        $links[] = [
            'url' => str_replace('{page}', $pagination['previous_page'], $urlPattern),
            'label' => '«',
            'active' => false
        ];
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $links[] = [
            'url' => str_replace('{page}', $i, $urlPattern),
            'label' => $i,
            'active' => $i == $currentPage
        ];
    }
    
    // Next link
    if ($pagination['has_next']) {
        $links[] = [
            'url' => str_replace('{page}', $pagination['next_page'], $urlPattern),
            'label' => '»',
            'active' => false
        ];
    }
    
    $pagination['links'] = $links;
    
    return $pagination;
}

// Debug function
function debug($var, $die = false) {
    echo '<pre>';
    print_r($var);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

// Log activity
function logActivity($userId, $action, $tableName, $recordId, $oldData = null, $newData = null) {
    try {
        $db = Database::getInstance();
        
        $sql = "INSERT INTO activity_log (user_id, action, table_name, record_id, old_data, new_data) 
                VALUES (:user_id, :action, :table_name, :record_id, :old_data, :new_data)";
        
        $db->query($sql)
            ->bind(':user_id', $userId)
            ->bind(':action', $action)
            ->bind(':table_name', $tableName)
            ->bind(':record_id', $recordId)
            ->bind(':old_data', $oldData ? json_encode($oldData) : null)
            ->bind(':new_data', $newData ? json_encode($newData) : null)
            ->execute();
            
    } catch (Exception $e) {
        error_log("Activity log failed: " . $e->getMessage());
    }
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php', 'অনুগ্রহ করে লগইন করুন', 'warning');
    }
}

// Get Bangla month name
function getBanglaMonth($month) {
    $months = [
        1 => 'জানুয়ারি',
        2 => 'ফেব্রুয়ারি',
        3 => 'মার্চ',
        4 => 'এপ্রিল',
        5 => 'মে',
        6 => 'জুন',
        7 => 'জুলাই',
        8 => 'আগস্ট',
        9 => 'সেপ্টেম্বর',
        10 => 'অক্টোবর',
        11 => 'নভেম্বর',
        12 => 'ডিসেম্বর'
    ];
    
    return $months[$month] ?? '';
}

// Export to CSV
function exportToCSV($data, $filename, $headers = []) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fwrite($output, "\xEF\xBB\xBF");
    
    // Write headers
    if (!empty($headers)) {
        fputcsv($output, $headers);
    }
    
    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

// Clean URL
function cleanUrl($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Truncate text
function truncateText($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    return substr($text, 0, $length) . $suffix;
}
?>
