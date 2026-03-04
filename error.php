<?php
/**
 * Error Handler Page
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Get error code
$errorCode = (int)($_GET['code'] ?? 404);

// Error messages
$errorMessages = [
    400 => [
        'title' => 'Bad Request',
        'message' => 'আপনার অনুরোধটি সঠিক নয়',
        'description' => 'অনুগ্রহ করে আপনার অনুরোধটি পুনরায় পরীক্ষা করুন এবং আবার চেষ্টা করুন।',
        'icon' => 'fa-exclamation-triangle',
        'color' => 'orange'
    ],
    401 => [
        'title' => 'Unauthorized',
        'message' => 'আপনি অনুমোদিত',
        'description' => 'এই পেজে অ্যাক্সেস করার জন্য আপনাকে লগইন করতে হবে।',
        'icon' => 'fa-lock',
        'color' => 'red'
    ],
    403 => [
        'title' => 'Forbidden',
        'message' => 'অ্যাক্সেস নিষিদ্ধ',
        'description' => 'আপনার এই পেজে অ্যাক্সেস করার অনুমতি নেই।',
        'icon' => 'fa-ban',
        'color' => 'red'
    ],
    404 => [
        'title' => 'Page Not Found',
        'message' => 'পেজ পাওয়া যায়নি',
        'description' => 'আপনি যে পেজটি খুঁজছেন তা বিদ্যমান নয় বা সরানো হয়েছে।',
        'icon' => 'fa-search',
        'color' => 'blue'
    ],
    500 => [
        'title' => 'Internal Server Error',
        'message' => 'সার্ভার ত্রুটি',
        'description' => 'সার্ভারে একটি সমস্যা হয়েছে। অনুগ্রহ করে কিছুক্ষণ পরে আবার চেষ্টা করুন।',
        'icon' => 'fa-server',
        'color' => 'red'
    ],
    503 => [
        'title' => 'Service Unavailable',
        'message' => 'সার্ভিস অনুপলব্ধ',
        'description' => 'সার্ভিসটি সাময়িকভাবে অনুপলব্ধ। অনুগ্রহ করে পরে আবার চেষ্টা করুন।',
        'icon' => 'fa-tools',
        'color' => 'orange'
    ]
];

// Get error info
$errorInfo = $errorMessages[$errorCode] ?? $errorMessages[404];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $errorInfo['title']; ?> - হিসাব পত্র</title>
    
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
        
        .float-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        .pulse-animation {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: .5; }
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

    <!-- Error Container -->
    <div class="relative z-10 w-full max-w-md">
        <!-- Error Icon and Code -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-24 h-24 bg-white rounded-3xl shadow-2xl mb-6">
                <i class="fas <?php echo $errorInfo['icon']; ?> text-4xl text-<?php echo $errorInfo['color']; ?>-600"></i>
            </div>
            <h1 class="text-6xl font-bold text-white mb-2"><?php echo $errorCode; ?></h1>
            <h2 class="text-2xl font-semibold text-white mb-2"><?php echo $errorInfo['title']; ?></h2>
            <p class="text-xl text-white opacity-90"><?php echo $errorInfo['message']; ?></p>
        </div>

        <!-- Error Details -->
        <div class="bg-white rounded-2xl shadow-2xl p-8">
            <div class="text-center mb-6">
                <i class="fas fa-info-circle text-3xl text-gray-400 mb-4"></i>
                <p class="text-gray-600 leading-relaxed">
                    <?php echo $errorInfo['description']; ?>
                </p>
            </div>

            <!-- Action Buttons -->
            <div class="space-y-3">
                <button onclick="goBack()" class="w-full flex items-center justify-center px-6 py-3 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition duration-200">
                    <i class="fas fa-arrow-left mr-3"></i>
                    পিছনে যান
                </button>
                
                <button onclick="goHome()" class="w-full flex items-center justify-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
                    <i class="fas fa-home mr-3"></i>
                    হোম পেজে যান
                </button>
                
                <?php if ($errorCode === 401): ?>
                <button onclick="goLogin()" class="w-full flex items-center justify-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                    <i class="fas fa-sign-in-alt mr-3"></i>
                    লগইন করুন
                </button>
                <?php endif; ?>
                
                <button onclick="refreshPage()" class="w-full flex items-center justify-center px-6 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-200">
                    <i class="fas fa-sync-alt mr-3"></i>
                    পেজ রিফ্রেশ করুন
                </button>
            </div>

            <!-- Additional Info -->
            <div class="mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-center justify-center text-sm text-gray-500">
                    <i class="fas fa-clock mr-2"></i>
                    <span>তারিখ: <?php echo date('d/m/Y H:i:s'); ?></span>
                </div>
                <?php if (isset($_SERVER['REQUEST_URI'])): ?>
                <div class="flex items-center justify-center text-sm text-gray-500 mt-2">
                    <i class="fas fa-link mr-2"></i>
                    <span>URL: <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contact Support -->
        <div class="text-center mt-8">
            <p class="text-white text-sm mb-2">সমস্যা সমাধানে সাহায্য প্রয়োজন?</p>
            <div class="flex items-center justify-center space-x-4 text-white text-sm">
                <a href="mailto:exchangebridge.bd@gmail.com" class="flex items-center hover:opacity-80 transition">
                    <i class="fas fa-envelope mr-2"></i>
                    exchangebridge.bd@gmail.com
                </a>
                <a href="tel:+8801XXXXXXXXX" class="flex items-center hover:opacity-80 transition">
                    <i class="fas fa-phone mr-2"></i>
                    +৮৮০১XXXXXXXXX
                </a>
            </div>
        </div>
    </div>

    <script>
        // Navigation functions
        function goBack() {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                goHome();
            }
        }

        function goHome() {
            window.location.href = 'index.php';
        }

        function goLogin() {
            window.location.href = 'login.php';
        }

        function refreshPage() {
            window.location.reload();
        }

        // Auto retry for server errors
        <?php if (in_array($errorCode, [500, 502, 503, 504])): ?>
        let retryCount = 0;
        const maxRetries = 3;
        const retryDelay = 5000; // 5 seconds

        function autoRetry() {
            if (retryCount < maxRetries) {
                retryCount++;
                
                // Show retry message
                const retryMessage = document.createElement('div');
                retryMessage.className = 'fixed top-4 right-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded-lg z-50';
                retryMessage.innerHTML = `
                    <div class="flex items-center">
                        <i class="fas fa-sync-alt fa-spin mr-2"></i>
                        <span>সার্ভার চেক করা হচ্ছে... (${retryCount}/${maxRetries})</span>
                    </div>
                `;
                document.body.appendChild(retryMessage);
                
                setTimeout(() => {
                    fetch(window.location.href)
                        .then(response => {
                            if (response.ok) {
                                window.location.reload();
                            } else {
                                retryMessage.remove();
                                if (retryCount < maxRetries) {
                                    setTimeout(autoRetry, retryDelay);
                                }
                            }
                        })
                        .catch(() => {
                            retryMessage.remove();
                            if (retryCount < maxRetries) {
                                setTimeout(autoRetry, retryDelay);
                            }
                        });
                }, 2000);
            }
        }

        // Start auto retry after 2 seconds
        setTimeout(autoRetry, 2000);
        <?php endif; ?>

        // Log error for debugging
        console.error('Error occurred:', {
            code: <?php echo $errorCode; ?>,
            message: '<?php echo addslashes($errorInfo['message']); ?>',
            url: window.location.href,
            timestamp: new Date().toISOString(),
            userAgent: navigator.userAgent
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                goBack();
            } else if (e.key === 'Enter' && e.ctrlKey) {
                refreshPage();
            }
        });
    </script>
</body>
</html>
