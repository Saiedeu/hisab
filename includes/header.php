<?php
/**
 * Header Component
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    exit('Direct access not allowed');
}

// Load authentication
require_once __DIR__ . '/auth.php';

// Check if user is logged in for protected pages
$protectedPage = true; // Set to false for login/signup pages
if ($protectedPage) {
    requireAuth();
}

// Get current user
$currentUser = getCurrentUser();
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Hind Siliguri Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Custom Styles -->
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
        }
        
        .bangla-font {
            font-family: 'Hind Siliguri', sans-serif;
        }
        
        .sidebar-transition {
            transition: all 0.3s ease;
        }
        
        .card-hover {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .sidebar-collapsed {
            width: 80px !important;
        }
        
        .sidebar-collapsed .sidebar-text {
            display: none;
        }
        
        .sidebar-collapsed .sidebar-icon {
            margin: 0 auto;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
        }
        
        .loading-spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #667eea;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .slide-in {
            animation: slideIn 0.3s ease-out;
        }
        
        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .pulse {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ef4444;
            color: white;
            border-radius: 50%;
            width: 18px;
            height: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
        }
        
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #555;
        }
    </style>
</head>
<body class="bg-gray-50 bangla-font">
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="loading-spinner"></div>
    </div>

    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200 fixed top-0 left-0 right-0 z-40">
        <div class="px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Left side -->
                <div class="flex items-center">
                    <!-- Menu Toggle -->
                    <button id="sidebarToggle" class="p-2 rounded-md text-gray-500 hover:text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 lg:hidden">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                    
                    <!-- Logo -->
                    <div class="ml-4 flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-calculator text-white text-sm"></i>
                            </div>
                        </div>
                        <div class="ml-3">
                            <h1 class="text-xl font-bold text-gray-900">হিসাব পত্র</h1>
                        </div>
                    </div>
                </div>

                <!-- Right side -->
                <div class="flex items-center space-x-4">
                    <!-- Search -->
                    <div class="hidden md:block">
                        <div class="relative">
                            <input type="text" id="globalSearch" placeholder="খুঁজুন..." 
                                   class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                            <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <div class="relative">
                        <button id="notificationBtn" class="p-2 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 relative">
                            <i class="fas fa-bell text-xl"></i>
                            <span id="notificationCount" class="notification-badge hidden">0</span>
                        </button>
                    </div>

                    <!-- Sync Status -->
                    <div class="flex items-center">
                        <div id="syncStatus" class="flex items-center text-sm">
                            <i id="syncIcon" class="fas fa-sync text-green-500 mr-2"></i>
                            <span id="syncText" class="text-gray-600">সিঙ্ক</span>
                        </div>
                    </div>

                    <!-- User Menu -->
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100">
                            <img src="<?php echo $currentUser['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=667eea&color=fff'; ?>" 
                                 alt="User" class="w-8 h-8 rounded-full">
                            <div class="hidden md:block text-left">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($currentUser['role']); ?></div>
                            </div>
                            <i class="fas fa-chevron-down text-gray-400"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <!-- Sidebar -->
    <aside id="sidebar" class="sidebar fixed left-0 top-16 bottom-0 w-64 bg-white shadow-lg border-r border-gray-200 z-30 sidebar-transition lg:translate-x-0">
        <nav class="h-full overflow-y-auto custom-scrollbar">
            <div class="p-4">
                <ul class="space-y-2">
                    <!-- Dashboard -->
                    <li>
                        <a href="index.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                            <i class="fas fa-home sidebar-icon w-5"></i>
                            <span class="sidebar-text">ড্যাশবোর্ড</span>
                        </a>
                    </li>

                    <!-- Customers -->
                    <li>
                        <a href="modules/customers.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                            <i class="fas fa-users sidebar-icon w-5"></i>
                            <span class="sidebar-text">গ্রাহক</span>
                        </a>
                    </li>

                    <!-- Debts -->
                    <li>
                        <a href="modules/debts.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 <?php echo basename($_SERVER['PHP_SELF']) == 'debts.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                            <i class="fas fa-hand-holding-usd sidebar-icon w-5"></i>
                            <span class="sidebar-text">বাকি</span>
                        </a>
                    </li>

                    <!-- Payments -->
                    <li>
                        <a href="modules/payments.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 <?php echo basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                            <i class="fas fa-money-bill-wave sidebar-icon w-5"></i>
                            <span class="sidebar-text">পেমেন্ট</span>
                        </a>
                    </li>

                    <!-- Reports -->
                    <li>
                        <a href="modules/reports.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                            <i class="fas fa-chart-bar sidebar-icon w-5"></i>
                            <span class="sidebar-text">রিপোর্ট</span>
                        </a>
                    </li>

                    <!-- Settings -->
                    <li>
                        <a href="modules/settings.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600 <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'bg-indigo-50 text-indigo-600' : ''; ?>">
                            <i class="fas fa-cog sidebar-icon w-5"></i>
                            <span class="sidebar-text">সেটিংস</span>
                        </a>
                    </li>

                    <!-- Divider -->
                    <li class="border-t border-gray-200 pt-2 mt-2">
                        <div class="px-3 py-2 text-xs font-semibold text-gray-500 uppercase sidebar-text">অন্যান্য</div>
                    </li>

                    <!-- Help -->
                    <li>
                        <a href="#" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-indigo-50 text-gray-700 hover:text-indigo-600">
                            <i class="fas fa-question-circle sidebar-icon w-5"></i>
                            <span class="sidebar-text">সাহায্য</span>
                        </a>
                    </li>

                    <!-- Logout -->
                    <li>
                        <a href="logout.php" class="nav-item flex items-center space-x-3 p-3 rounded-lg hover:bg-red-50 text-gray-700 hover:text-red-600">
                            <i class="fas fa-sign-out-alt sidebar-icon w-5"></i>
                            <span class="sidebar-text">লগআউট</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="lg:ml-64 pt-16 min-h-screen">
        <div class="p-4 sm:p-6 lg:p-8">
            <!-- Flash Messages -->
            <div id="flashMessages">
                <?php echo displayFlashMessage(); ?>
            </div>

            <!-- Page Header -->
            <?php if (isset($pageHeader)): ?>
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($pageHeader); ?></h2>
                <?php if (isset($pageSubHeader)): ?>
                <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($pageSubHeader); ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

<!-- Content starts here -->
