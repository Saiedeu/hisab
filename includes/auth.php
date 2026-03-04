<?php
/**
 * Authentication Helper
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Start session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/functions.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user data
function getCurrentUser() {
    static $currentUser = null;
    
    if ($currentUser === null && isLoggedIn()) {
        $userModel = new User();
        $currentUser = $userModel->find(getCurrentUserId());
    }
    
    return $currentUser;
}

// Login user
function loginUser($userData) {
    $_SESSION['user_id'] = $userData['id'];
    $_SESSION['user_name'] = $userData['name'];
    $_SESSION['user_email'] = $userData['email'];
    $_SESSION['user_role'] = $userData['role'];
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
    
    // Update last login in database
    $userModel = new User();
    $userModel->updateLastLogin($userData['id']);
    
    // Log user session
    logUserSession($userData['id']);
}

// Logout user
function logoutUser() {
    // Clear session
    $_SESSION = [];
    
    // Destroy session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

// Check if session is expired
function isSessionExpired() {
    if (!isset($_SESSION['last_activity'])) {
        return true;
    }
    
    $inactive = time() - $_SESSION['last_activity'];
    return $inactive > SESSION_LIFETIME;
}

// Update last activity
function updateLastActivity() {
    $_SESSION['last_activity'] = time();
}

// Require authentication
function requireAuth() {
    if (!isLoggedIn()) {
        redirect('login.php', 'অনুগ্রহ করে লগইন করুন', 'warning');
    }
    
    if (isSessionExpired()) {
        logoutUser();
        redirect('login.php', 'সেশন শেষ হয়েছে, আবার লগইন করুন', 'warning');
    }
    
    updateLastActivity();
}

// Check user role
function hasRole($role) {
    $user = getCurrentUser();
    return $user && $user['role'] === $role;
}

// Require specific role
function requireRole($role) {
    requireAuth();
    
    if (!hasRole($role)) {
        redirect('index.php', 'আপনার এই পেজে অ্যাক্সেস নেই', 'error');
    }
}

// Require admin role
function requireAdmin() {
    requireRole('admin');
}

// Check if current user can access resource
function canAccessResource($createdBy) {
    $user = getCurrentUser();
    
    // Admin can access everything
    if ($user && $user['role'] === 'admin') {
        return true;
    }
    
    // Users can only access their own resources
    return $user && $user['id'] == $createdBy;
}

// Log user session for sync
function logUserSession($userId) {
    try {
        $db = Database::getInstance();
        
        $sessionId = session_id();
        $ipAddress = getClientIP();
        $userAgent = getUserAgent();
        
        // Remove old sessions for this user
        $deleteSql = "DELETE FROM user_sessions WHERE user_id = :user_id";
        $db->query($deleteSql)->bind(':user_id', $userId)->execute();
        
        // Insert new session
        $insertSql = "INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent) 
                      VALUES (:user_id, :session_id, :ip_address, :user_agent)";
        
        $db->query($insertSql)
            ->bind(':user_id', $userId)
            ->bind(':session_id', $sessionId)
            ->bind(':ip_address', $ipAddress)
            ->bind(':user_agent', $userAgent)
            ->execute();
            
    } catch (Exception $e) {
        error_log("Session log failed: " . $e->getMessage());
    }
}

// Get active users for sync
function getActiveUsers() {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT DISTINCT u.id, u.name, u.email, us.last_activity
                FROM users u
                INNER JOIN user_sessions us ON u.id = us.user_id
                WHERE us.is_active = 1 
                AND us.last_activity > DATE_SUB(NOW(), INTERVAL " . SYNC_INTERVAL . " SECOND)
                AND u.id != :current_user_id";
        
        return $db->query($sql)
            ->bind(':current_user_id', getCurrentUserId())
            ->fetchAll();
            
    } catch (Exception $e) {
        error_log("Get active users failed: " . $e->getMessage());
        return [];
    }
}

// Clean up old sessions
function cleanupOldSessions() {
    try {
        $db = Database::getInstance();
        
        // Mark old sessions as inactive
        $updateSql = "UPDATE user_sessions 
                      SET is_active = 0 
                      WHERE last_activity < DATE_SUB(NOW(), INTERVAL " . MAX_SESSION_AGE . " SECOND)";
        
        $db->query($updateSql)->execute();
        
        // Delete very old sessions
        $deleteSql = "DELETE FROM user_sessions 
                      WHERE last_activity < DATE_SUB(NOW(), INTERVAL 7 DAY)";
        
        $db->query($deleteSql)->execute();
        
    } catch (Exception $e) {
        error_log("Session cleanup failed: " . $e->getMessage());
    }
}

// Check if user should sync data
function shouldSyncData() {
    if (!isLoggedIn()) {
        return false;
    }
    
    $lastSync = $_SESSION['last_sync'] ?? 0;
    $timeSinceSync = time() - $lastSync;
    
    return $timeSinceSync >= SYNC_INTERVAL;
}

// Mark data as synced
function markDataSynced() {
    $_SESSION['last_sync'] = time();
}

// Get sync data for other users
function getSyncData() {
    try {
        $db = Database::getInstance();
        $currentUserId = getCurrentUserId();
        $lastSync = $_SESSION['last_sync'] ?? 0;
        
        $syncTime = date('Y-m-d H:i:s', $lastSync);
        
        $data = [
            'customers' => [],
            'debts' => [],
            'payments' => [],
            'users' => []
        ];
        
        // Get new/updated customers
        $customerSql = "SELECT * FROM customers 
                        WHERE created_at > :sync_time 
                        OR updated_at > :sync_time";
        
        $data['customers'] = $db->query($customerSql)
            ->bind(':sync_time', $syncTime)
            ->fetchAll();
        
        // Get new/updated debts
        $debtSql = "SELECT d.*, c.name as customer_name, c.phone as customer_phone
                    FROM debts d
                    INNER JOIN customers c ON d.customer_id = c.id
                    WHERE d.created_at > :sync_time 
                    OR d.updated_at > :sync_time";
        
        $data['debts'] = $db->query($debtSql)
            ->bind(':sync_time', $syncTime)
            ->fetchAll();
        
        // Get new/updated payments
        $paymentSql = "SELECT p.*, c.name as customer_name, c.phone as customer_phone
                       FROM payments p
                       INNER JOIN customers c ON p.customer_id = c.id
                       WHERE p.created_at > :sync_time";
        
        $data['payments'] = $db->query($paymentSql)
            ->bind(':sync_time', $syncTime)
            ->fetchAll();
        
        // Get active users
        $data['users'] = getActiveUsers();
        
        return $data;
        
    } catch (Exception $e) {
        error_log("Get sync data failed: " . $e->getMessage());
        return [];
    }
}

// Validate CSRF token
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Check request rate limiting
function checkRateLimit($action, $limit = 10, $window = 60) {
    $key = $action . '_' . getClientIP();
    $current = time();
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Remove old entries
    $_SESSION['rate_limit'][$key] = array_filter(
        $_SESSION['rate_limit'][$key],
        function($timestamp) use ($current, $window) {
            return $current - $timestamp < $window;
        }
    );
    
    // Check limit
    if (count($_SESSION['rate_limit'][$key]) >= $limit) {
        return false;
    }
    
    // Add current request
    $_SESSION['rate_limit'][$key][] = $current;
    
    return true;
}

// Remember me functionality
function setRememberMe($userId) {
    $token = generateRandomString(32);
    $selector = generateRandomString(12);
    $expires = time() + (30 * 24 * 60 * 60); // 30 days
    
    try {
        $db = Database::getInstance();
        
        // Delete old tokens for this user
        $deleteSql = "DELETE FROM user_tokens WHERE user_id = :user_id";
        $db->query($deleteSql)->bind(':user_id', $userId)->execute();
        
        // Insert new token
        $insertSql = "INSERT INTO user_tokens (user_id, selector, token, expires) 
                      VALUES (:user_id, :selector, :token, :expires)";
        
        $db->query($insertSql)
            ->bind(':user_id', $userId)
            ->bind(':selector', $selector)
            ->bind(':token', hash('sha256', $token))
            ->bind(':expires', date('Y-m-d H:i:s', $expires))
            ->execute();
        
        // Set cookie
        $cookieValue = $selector . ':' . $token;
        setcookie('remember_me', $cookieValue, $expires, '/', '', false, true);
        
    } catch (Exception $e) {
        error_log("Remember me failed: " . $e->getMessage());
    }
}

// Check remember me token
function checkRememberMe() {
    if (!isset($_COOKIE['remember_me'])) {
        return false;
    }
    
    list($selector, $token) = explode(':', $_COOKIE['remember_me'], 2);
    
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT t.*, u.* FROM user_tokens t
                INNER JOIN users u ON t.user_id = u.id
                WHERE t.selector = :selector AND t.expires > NOW() AND u.is_active = 1";
        
        $result = $db->query($sql)->bind(':selector', $selector)->fetch();
        
        if ($result && hash_equals($result['token'], hash('sha256', $token))) {
            loginUser($result);
            return true;
        }
        
        // Invalid token, delete it
        $deleteSql = "DELETE FROM user_tokens WHERE selector = :selector";
        $db->query($deleteSql)->bind(':selector', $selector)->execute();
        
        // Clear cookie
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
        
    } catch (Exception $e) {
        error_log("Check remember me failed: " . $e->getMessage());
    }
    
    return false;
}

// Clear remember me
function clearRememberMe() {
    if (isset($_COOKIE['remember_me'])) {
        setcookie('remember_me', '', time() - 3600, '/', '', false, true);
        unset($_COOKIE['remember_me']);
    }
    
    if (isLoggedIn()) {
        try {
            $db = Database::getInstance();
            $deleteSql = "DELETE FROM user_tokens WHERE user_id = :user_id";
            $db->query($deleteSql)->bind(':user_id', getCurrentUserId())->execute();
        } catch (Exception $e) {
            error_log("Clear remember me failed: " . $e->getMessage());
        }
    }
}
?>
