<?php
/**
 * Login Page
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

// Check if user is already logged in
if (isLoggedIn()) {
    redirect('index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Validate input
    if (empty($email) || empty($password)) {
        redirect('login.php', 'অনুগ্রহ করে সব ঘর পূরণ করুন', 'error');
    }
    
    if (!validateEmail($email)) {
        redirect('login.php', 'সঠিক ইমেল ঠিকানা দিন', 'error');
    }
    
    try {
        $userModel = new User();
        $user = $userModel->findByEmail($email);
        
        if ($user && $userModel->verifyPassword($password, $user['password'])) {
            if (!$userModel->isActive($user['id'])) {
                redirect('login.php', 'আপনার অ্যাকাউন্ট নিষ্ক্রিয় করা হয়েছে', 'error');
            }
            
            // Login user
            loginUser($user);
            
            // Set remember me if checked
            if ($remember) {
                setRememberMe($user['id']);
            }
            
            // Redirect to intended page or dashboard
            $redirect = $_SESSION['redirect'] ?? 'index.php';
            unset($_SESSION['redirect']);
            redirect($redirect, 'সফলভাবে লগইন হয়েছে', 'success');
            
        } else {
            redirect('login.php', 'ভুল ইমেল বা পাসওয়ার্ড', 'error');
        }
        
    } catch (Exception $e) {
        error_log("Login error: " . $e->getMessage());
        redirect('login.php', 'লগইনে সমস্যা হয়েছে, আবার চেষ্টা করুন', 'error');
    }
}

// Check remember me token
checkRememberMe();

// Generate Google OAuth URL
$googleAuthUrl = '';
if (GOOGLE_OAUTH_ENABLED) {
    $params = [
        'client_id' => GOOGLE_CLIENT_ID,
        'redirect_uri' => GOOGLE_REDIRECT_URI,
        'response_type' => GOOGLE_RESPONSE_TYPE,
        'scope' => implode(' ', GOOGLE_SCOPES),
        'access_type' => GOOGLE_ACCESS_TYPE,
        'prompt' => GOOGLE_PROMPT,
        'state' => bin2hex(random_bytes(16))
    ];
    
    $_SESSION['google_oauth_state'] = $params['state'];
    $googleAuthUrl = GOOGLE_AUTH_URL . '?' . http_build_query($params);
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>লগইন - হিসাব পত্র</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Hind Siliguri Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .slide-in {
            animation: slideIn 0.5s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateX(-100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute -top-10 -right-10 w-40 h-40 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 float-animation"></div>
        <div class="absolute -bottom-10 -left-10 w-40 h-40 bg-yellow-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 float-animation" style="animation-delay: 2s;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-40 h-40 bg-pink-300 rounded-full mix-blend-multiply filter blur-xl opacity-70 float-animation" style="animation-delay: 4s;"></div>
    </div>

    <!-- Main container -->
    <div class="relative z-10 w-full max-w-md">
        <!-- Logo and title -->
        <div class="text-center mb-8 slide-in">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-2xl shadow-lg mb-4">
                <i class="fas fa-calculator text-3xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">হিসাব পত্র</h1>
            <p class="text-indigo-100">ফার্মেসি বাকি হিসাব ব্যবস্থাপনা সিস্টেম</p>
        </div>

        <!-- Login form -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8 slide-in">
            <!-- Flash messages -->
            <div id="flashMessages">
                <?php echo displayFlashMessage(); ?>
            </div>

            <form method="POST" action="login.php" class="space-y-6">
                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2"></i>ইমেল ঠিকানা
                    </label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                           placeholder="আপনার ইমেল ঠিকানা লিখুন"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>পাসওয়ার্ড
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                               placeholder="আপনার পাসওয়ার্ড লিখুন">
                        <button type="button" id="togglePassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember me -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" name="remember" class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                        <span class="ml-2 text-sm text-gray-700">মনে রাখুন</span>
                    </label>
                    <a href="forgot-password.php" class="text-sm text-indigo-600 hover:text-indigo-800">
                        পাসওয়ার্ড ভুলে গেছেন?
                    </a>
                </div>

                <!-- Submit button -->
                <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition duration-200 font-medium">
                    <i class="fas fa-sign-in-alt mr-2"></i>লগইন
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-gray-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-white text-gray-500">অথবা</span>
                </div>
            </div>

            <!-- Google Login -->
            <?php if (GOOGLE_OAUTH_ENABLED && !empty($googleAuthUrl)): ?>
            <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" 
               class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google দিয়ে লগইন করুন
            </a>
            <?php endif; ?>

            <!-- Sign up link -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    একাউন্ট নেই? 
                    <a href="signup.php" class="font-medium text-indigo-600 hover:text-indigo-800">
                        সাইন আপ করুন
                    </a>
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white text-sm">
            <p>&copy; 2026 সিদ ম্যান সলিউশন। সর্বস্বত্ব সংরক্ষিত।</p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        
        togglePassword?.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = togglePassword.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Auto-hide flash messages
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transition = 'opacity 0.5s';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Form validation
        const form = document.querySelector('form');
        form?.addEventListener('submit', (e) => {
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                showToast('error', 'সব ঘর পূরণ করুন');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showToast('error', 'সঠিক ইমেল ঠিকানা দিন');
                return;
            }
        });

        // Toast function
        function showToast(type, message) {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg text-white ${
                type === 'error' ? 'bg-red-500' : 'bg-green-500'
            } slide-in`;
            toast.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(toast);
            
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 0.5s';
                setTimeout(() => toast.remove(), 500);
            }, 3000);
        }
    </script>
</body>
</html>
