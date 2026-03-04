<?php
/**
 * Google OAuth Callback
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Start session
session_start();

// Load configuration
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/google-config.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

// Check if Google OAuth is enabled
if (!GOOGLE_OAUTH_ENABLED) {
    redirect('login.php', 'Google OAuth সক্ষম নয়', 'error');
}

// Verify state parameter
if (!isset($_GET['state']) || !isset($_SESSION['google_oauth_state']) || $_GET['state'] !== $_SESSION['google_oauth_state']) {
    redirect('login.php', 'OAuth স্টেট ভ্যালিডেশন ব্যর্থ', 'error');
}

// Clear state from session
unset($_SESSION['google_oauth_state']);

// Check for error
if (isset($_GET['error'])) {
    redirect('login.php', 'Google OAuth ত্রুটি: ' . $_GET['error'], 'error');
}

// Get authorization code
if (!isset($_GET['code'])) {
    redirect('login.php', 'অনুমোদন কোড পাওয়া যায়নি', 'error');
}

$code = $_GET['code'];

try {
    // Exchange authorization code for access token
    $tokenData = exchangeCodeForToken($code);
    
    if (!isset($tokenData['access_token'])) {
        throw new Exception('অ্যাক্সেস টোকেন পাওয়া যায়নি');
    }
    
    // Get user information
    $userInfo = getUserInfo($tokenData['access_token']);
    
    if (!isset($userInfo['id']) || !isset($userInfo['email'])) {
        throw new Exception('ব্যবহারকারীর তথ্য পাওয়া যায়নি');
    }
    
    $userModel = new User();
    
    // Check if user exists with Google ID
    $user = $userModel->findByGoogleId($userInfo['id']);
    
    if ($user) {
        // User exists, login
        if (!$userModel->isActive($user['id'])) {
            redirect('login.php', 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে', 'error');
        }
        
        // Update user info if needed
        $updateData = [
            'name' => $userInfo['name'],
            'email' => $userInfo['email'],
            'avatar' => $userInfo['picture'] ?? null
        ];
        
        $userModel->update($user['id'], $updateData);
        loginUser($user);
        
    } else {
        // Check if user exists with email
        $existingUser = $userModel->findByEmail($userInfo['email']);
        
        if ($existingUser) {
            // Link Google account to existing user
            $userModel->update($existingUser['id'], [
                'google_id' => $userInfo['id'],
                'avatar' => $userInfo['picture'] ?? null
            ]);
            
            loginUser($existingUser);
            
        } else {
            // Create new user
            if (GOOGLE_AUTO_CREATE_USER) {
                $userData = [
                    'name' => $userInfo['name'],
                    'email' => $userInfo['email'],
                    'google_id' => $userInfo['id'],
                    'avatar' => $userInfo['picture'] ?? null,
                    'role' => GOOGLE_DEFAULT_ROLE,
                    'is_active' => 1
                ];
                
                $userId = $userModel->create($userData);
                
                if ($userId) {
                    // Log activity
                    logActivity($userId, 'create', 'users', $userId, null, $userData);
                    
                    $newUser = $userModel->find($userId);
                    loginUser($newUser);
                    
                    redirect('index.php', 'Google দিয়ে সফলভাবে একাউন্ট তৈরি হয়েছে', 'success');
                } else {
                    throw new Exception('ব্যবহারকারী তৈরি করতে ব্যর্থ');
                }
            } else {
                redirect('login.php', 'Google দিয়ে একাউন্ট তৈরি করা সক্ষম নয়', 'error');
            }
        }
    }
    
    // Redirect to intended page or dashboard
    $redirect = $_SESSION['redirect'] ?? 'index.php';
    unset($_SESSION['redirect']);
    redirect($redirect, 'Google দিয়ে সফলভাবে লগইন হয়েছে', 'success');
    
} catch (Exception $e) {
    error_log("Google OAuth error: " . $e->getMessage());
    redirect('login.php', 'Google OAuth ত্রুটি: ' . $e->getMessage(), 'error');
}

/**
 * Exchange authorization code for access token
 */
function exchangeCodeForToken($code) {
    $postData = [
        'code' => $code,
        'client_id' => GOOGLE_CLIENT_ID,
        'client_secret' => GOOGLE_CLIENT_SECRET,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'grant_type' => 'authorization_code'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, GOOGLE_TOKEN_URL);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('টোকেন এক্সচেঞ্জ ব্যর্থ: HTTP ' . $httpCode);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('টোকেন রেসপন্স পার্স করতে ব্যর্থ');
    }
    
    if (isset($data['error'])) {
        throw new Exception('টোকেন ত্রুটি: ' . $data['error_description'] ?? $data['error']);
    }
    
    return $data;
}

/**
 * Get user information from Google
 */
function getUserInfo($accessToken) {
    $url = GOOGLE_USERINFO_URL . '?access_token=' . urlencode($accessToken);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        throw new Exception('ব্যবহারকারীর তথ্য পেতে ব্যর্থ: HTTP ' . $httpCode);
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('ব্যবহারকারীর তথ্য পার্স করতে ব্যর্থ');
    }
    
    if (isset($data['error'])) {
        throw new Exception('ব্যবহারকারীর তথ্য ত্রুটি: ' . $data['error']['message'] ?? 'Unknown error');
    }
    
    return $data;
}
?>
