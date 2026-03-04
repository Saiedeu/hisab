<?php
/**
 * Signup Page
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
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $phone = sanitize($_POST['phone'] ?? '');
    $village = sanitize($_POST['village'] ?? '');
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirmPassword)) {
        redirect('signup.php', 'অনুগ্রহ করে সব ঘর পূরণ করুন', 'error');
    }
    
    if (!validateEmail($email)) {
        redirect('signup.php', 'সঠিক ইমেল ঠিকানা দিন', 'error');
    }
    
    if (strlen($password) < 6) {
        redirect('signup.php', 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে', 'error');
    }
    
    if ($password !== $confirmPassword) {
        redirect('signup.php', 'পাসওয়ার্ড মিলছে না', 'error');
    }
    
    if (!empty($phone) && !validatePhone($phone)) {
        redirect('signup.php', 'সঠিক ফোন নম্বর দিন (১১ ডিজিট)', 'error');
    }
    
    try {
        $userModel = new User();
        
        // Check if email already exists
        if ($userModel->emailExists($email)) {
            redirect('signup.php', 'এই ইমেল ঠিকানায় ইতিমধ্যে একাউন্ট আছে', 'error');
        }
        
        // Create user
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $userModel->hashPassword($password),
            'phone' => $phone,
            'village' => $village,
            'role' => 'user',
            'is_active' => 1
        ];
        
        $userId = $userModel->create($userData);
        
        if ($userId) {
            // Log activity
            logActivity($userId, 'create', 'users', $userId, null, $userData);
            
            // Auto login after signup
            $newUser = $userModel->find($userId);
            loginUser($newUser);
            
            redirect('index.php', 'একাউন্ট সফলভাবে তৈরি হয়েছে', 'success');
        } else {
            redirect('signup.php', 'একাউন্ট তৈরিতে সমস্যা হয়েছে', 'error');
        }
        
    } catch (Exception $e) {
        error_log("Signup error: " . $e->getMessage());
        redirect('signup.php', 'নিবন্ধনে সমস্যা হয়েছে, আবার চেষ্টা করুন', 'error');
    }
}

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
    <title>সাইন আপ - হিসাব পত্র</title>
    
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
        
        .password-strength {
            height: 4px;
            border-radius: 2px;
            transition: all 0.3s ease;
        }
        
        .strength-weak { background-color: #ef4444; width: 33%; }
        .strength-medium { background-color: #f59e0b; width: 66%; }
        .strength-strong { background-color: #10b981; width: 100%; }
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
                <i class="fas fa-user-plus text-3xl text-indigo-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-white mb-2">হিসাব পত্র</h1>
            <p class="text-indigo-100">নতুন একাউন্ট তৈরি করুন</p>
        </div>

        <!-- Signup form -->
        <div class="glass-effect rounded-2xl shadow-2xl p-8 slide-in">
            <!-- Flash messages -->
            <div id="flashMessages">
                <?php echo displayFlashMessage(); ?>
            </div>

            <form method="POST" action="signup.php" class="space-y-4">
                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-user mr-2"></i>পূর্ণ নাম *
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                           placeholder="আপনার পূর্ণ নাম লিখুন"
                           value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-envelope mr-2"></i>ইমেল ঠিকানা *
                    </label>
                    <input type="email" id="email" name="email" required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                           placeholder="আপনার ইমেল ঠিকানা লিখুন"
                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>

                <!-- Phone -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-phone mr-2"></i>ফোন নম্বর
                    </label>
                    <input type="tel" id="phone" name="phone"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                           placeholder="০১XXXXXXXXX"
                           value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                </div>

                <!-- Village -->
                <div>
                    <label for="village" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-map-marker-alt mr-2"></i>গ্রাম/এলাকা
                    </label>
                    <input type="text" id="village" name="village"
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                           placeholder="আপনার গ্রাম বা এলাকার নাম"
                           value="<?php echo htmlspecialchars($_POST['village'] ?? ''); ?>">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>পাসওয়ার্ড *
                    </label>
                    <div class="relative">
                        <input type="password" id="password" name="password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                               placeholder="কমপক্ষে ৬ অক্ষর">
                        <button type="button" id="togglePassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="mt-2">
                        <div id="passwordStrength" class="password-strength"></div>
                        <p id="strengthText" class="text-xs text-gray-500 mt-1"></p>
                    </div>
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-lock mr-2"></i>পাসওয়ার্ড নিশ্চিত করুন *
                    </label>
                    <div class="relative">
                        <input type="password" id="confirm_password" name="confirm_password" required
                               class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition duration-200"
                               placeholder="পাসওয়ার্ড আবার লিখুন">
                        <button type="button" id="toggleConfirmPassword" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-gray-700">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <!-- Terms and conditions -->
                <div class="flex items-start">
                    <input type="checkbox" id="terms" name="terms" required
                           class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500 mt-1">
                    <label for="terms" class="ml-2 text-sm text-gray-700">
                        আমি <a href="#" class="text-indigo-600 hover:text-indigo-800">শর্তাবলী</a> এবং 
                        <a href="#" class="text-indigo-600 hover:text-indigo-800">গোপনীয়তা নীতি</a> মেনে নিচ্ছি
                    </label>
                </div>

                <!-- Submit button -->
                <button type="submit" class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-200 transition duration-200 font-medium">
                    <i class="fas fa-user-plus mr-2"></i>একাউন্ট তৈরি করুন
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

            <!-- Google Signup -->
            <?php if (GOOGLE_OAUTH_ENABLED && !empty($googleAuthUrl)): ?>
            <a href="<?php echo htmlspecialchars($googleAuthUrl); ?>" 
               class="w-full flex items-center justify-center px-4 py-3 border border-gray-300 rounded-lg shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition duration-200">
                <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                    <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                </svg>
                Google দিয়ে সাইন আপ করুন
            </a>
            <?php endif; ?>

            <!-- Login link -->
            <div class="text-center mt-6">
                <p class="text-sm text-gray-600">
                    ইতিমধ্যে একাউন্ট আছে? 
                    <a href="login.php" class="font-medium text-indigo-600 hover:text-indigo-800">
                        লগইন করুন
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
        const toggleConfirmPassword = document.getElementById('toggleConfirmPassword');
        const confirmPasswordInput = document.getElementById('confirm_password');
        
        togglePassword?.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            const icon = togglePassword.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });
        
        toggleConfirmPassword?.addEventListener('click', () => {
            const type = confirmPasswordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            confirmPasswordInput.setAttribute('type', type);
            
            const icon = toggleConfirmPassword.querySelector('i');
            icon.classList.toggle('fa-eye');
            icon.classList.toggle('fa-eye-slash');
        });

        // Password strength checker
        const passwordStrength = document.getElementById('passwordStrength');
        const strengthText = document.getElementById('strengthText');
        
        passwordInput?.addEventListener('input', (e) => {
            const password = e.target.value;
            let strength = 0;
            
            if (password.length >= 6) strength++;
            if (password.length >= 10) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;
            
            passwordStrength.className = 'password-strength';
            
            if (password.length === 0) {
                strengthText.textContent = '';
            } else if (strength <= 2) {
                passwordStrength.classList.add('strength-weak');
                strengthText.textContent = 'দুর্বল পাসওয়ার্ড';
                strengthText.className = 'text-xs text-red-500 mt-1';
            } else if (strength <= 3) {
                passwordStrength.classList.add('strength-medium');
                strengthText.textContent = 'মাঝারি পাসওয়ার্ড';
                strengthText.className = 'text-xs text-yellow-500 mt-1';
            } else {
                passwordStrength.classList.add('strength-strong');
                strengthText.textContent = 'শক্তিশালী পাসওয়ার্ড';
                strengthText.className = 'text-xs text-green-500 mt-1';
            }
        });

        // Password confirmation check
        confirmPasswordInput?.addEventListener('input', (e) => {
            const confirmPassword = e.target.value;
            const password = passwordInput.value;
            
            if (confirmPassword && password !== confirmPassword) {
                confirmPasswordInput.setCustomValidity('পাসওয়ার্ড মিলছে না');
            } else {
                confirmPasswordInput.setCustomValidity('');
            }
        });

        // Phone number formatting
        const phoneInput = document.getElementById('phone');
        phoneInput?.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.slice(0, 11);
            }
            e.target.value = value;
        });

        // Form validation
        const form = document.querySelector('form');
        form?.addEventListener('submit', (e) => {
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const phone = document.getElementById('phone').value;
            const terms = document.getElementById('terms').checked;
            
            if (!name || !email || !password || !confirmPassword) {
                e.preventDefault();
                showToast('error', 'সব ঘর পূরণ করুন');
                return;
            }
            
            if (name.length < 3) {
                e.preventDefault();
                showToast('error', 'নাম কমপক্ষে ৩ অক্ষরের হতে হবে');
                return;
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                showToast('error', 'সঠিক ইমেল ঠিকানা দিন');
                return;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showToast('error', 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে');
                return;
            }
            
            if (password !== confirmPassword) {
                e.preventDefault();
                showToast('error', 'পাসওয়ার্ড মিলছে না');
                return;
            }
            
            if (phone && !/^01[3-9]\d{8}$/.test(phone)) {
                e.preventDefault();
                showToast('error', 'সঠিক ফোন নম্বর দিন (১১ ডিজিট)');
                return;
            }
            
            if (!terms) {
                e.preventDefault();
                showToast('error', 'শর্তাবলী মেনে নিতে হবে');
                return;
            }
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
