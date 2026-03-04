<?php
/**
 * Logout Page
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Log activity if user is logged in
if (isLoggedIn()) {
    $userId = getCurrentUserId();
    logActivity($userId, 'logout', 'users', $userId);
}

// Clear remember me token
clearRememberMe();

// Logout user
logoutUser();

// Redirect to login page
redirect('login.php', 'সফলভাবে লগআউট হয়েছে', 'success');
?>
