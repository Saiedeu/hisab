<?php
/**
 * Footer Component
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Prevent direct access
if (!defined('APP_NAME')) {
    exit('Direct access not allowed');
}
?>

<!-- Content ends here -->

        </div>
    </main>

    <!-- User Menu Dropdown -->
    <div id="userMenu" class="hidden fixed top-16 right-4 w-64 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center space-x-3">
                <img src="<?php echo $currentUser['avatar'] ?? 'https://ui-avatars.com/api/?name=' . urlencode($currentUser['name']) . '&background=667eea&color=fff'; ?>" 
                     alt="User" class="w-10 h-10 rounded-full">
                <div>
                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($currentUser['name']); ?></div>
                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($currentUser['email']); ?></div>
                </div>
            </div>
        </div>
        <div class="py-2">
            <a href="modules/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-user mr-2"></i> প্রোফাইল
            </a>
            <a href="modules/settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-cog mr-2"></i> সেটিংস
            </a>
            <a href="modules/help.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                <i class="fas fa-question-circle mr-2"></i> সাহায্য
            </a>
            <div class="border-t border-gray-200 mt-2 pt-2">
                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt mr-2"></i> লগআউট
                </a>
            </div>
        </div>
    </div>

    <!-- Notification Dropdown -->
    <div id="notificationDropdown" class="hidden fixed top-16 right-20 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
        <div class="p-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="font-medium text-gray-900">নোটিফিকেশন</h3>
                <button id="markAllRead" class="text-sm text-indigo-600 hover:text-indigo-800">সব পড়ুন</button>
            </div>
        </div>
        <div id="notificationList" class="max-h-96 overflow-y-auto">
            <!-- Notifications will be loaded here -->
            <div class="p-4 text-center text-gray-500">
                <i class="fas fa-bell-slash text-2xl mb-2"></i>
                <p>কোনো নোটিফিকেশন নেই</p>
            </div>
        </div>
    </div>

    <!-- Search Results Modal -->
    <div id="searchModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-start justify-center pt-20">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl mx-4">
            <div class="p-4 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="font-medium text-gray-900">অনুসন্ধান ফলাফল</h3>
                    <button id="closeSearchModal" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            <div id="searchResults" class="max-h-96 overflow-y-auto p-4">
                <!-- Search results will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-md mx-4">
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mr-4">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                    <div>
                        <h3 id="confirmTitle" class="text-lg font-medium text-gray-900">নিশ্চিত করুন</h3>
                        <p id="confirmMessage" class="text-gray-600">আপনি কি নিশ্চিত?</p>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button id="confirmCancel" class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                        বাতিল
                    </button>
                    <button id="confirmOk" class="px-4 py-2 text-red-600 text-white rounded-lg hover:bg-red-700">
                        নিশ্চিত
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Success/Error Toast -->
    <div id="toast" class="hidden fixed bottom-4 right-4 z-50">
        <div class="bg-white rounded-lg shadow-lg border border-gray-200 p-4 flex items-center space-x-3 min-w-[300px]">
            <div id="toastIcon"></div>
            <div>
                <div id="toastTitle" class="font-medium text-gray-900"></div>
                <div id="toastMessage" class="text-sm text-gray-600"></div>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Global variables
        const APP_URL = '<?php echo APP_URL; ?>';
        const USER_ID = <?php echo getCurrentUserId() ?? 'null'; ?>;
        const SYNC_INTERVAL = <?php echo SYNC_INTERVAL * 1000; ?>;

        // DOM elements
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenu = document.getElementById('userMenu');
        const notificationBtn = document.getElementById('notificationBtn');
        const notificationDropdown = document.getElementById('notificationDropdown');
        const globalSearch = document.getElementById('globalSearch');
        const searchModal = document.getElementById('searchModal');
        const searchResults = document.getElementById('searchResults');
        const closeSearchModal = document.getElementById('closeSearchModal');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Toggle sidebar
        sidebarToggle?.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });

        // Toggle user menu
        userMenuBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            userMenu.classList.toggle('hidden');
            notificationDropdown.classList.add('hidden');
        });

        // Toggle notification dropdown
        notificationBtn?.addEventListener('click', (e) => {
            e.stopPropagation();
            notificationDropdown.classList.toggle('hidden');
            userMenu.classList.add('hidden');
            
            if (!notificationDropdown.classList.contains('hidden')) {
                loadNotifications();
            }
        });

        // Close dropdowns when clicking outside
        document.addEventListener('click', () => {
            userMenu.classList.add('hidden');
            notificationDropdown.classList.add('hidden');
        });

        // Global search
        let searchTimeout;
        globalSearch?.addEventListener('input', (e) => {
            clearTimeout(searchTimeout);
            const query = e.target.value.trim();
            
            if (query.length < 2) {
                searchModal.classList.add('hidden');
                return;
            }
            
            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 500);
        });

        // Close search modal
        closeSearchModal?.addEventListener('click', () => {
            searchModal.classList.add('hidden');
            globalSearch.value = '';
        });

        // Search function
        async function performSearch(query) {
            try {
                showLoading();
                const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
                const data = await response.json();
                
                displaySearchResults(data);
                searchModal.classList.remove('hidden');
            } catch (error) {
                console.error('Search error:', error);
                showToast('error', 'অনুসন্ধানে সমস্যা হয়েছে');
            } finally {
                hideLoading();
            }
        }

        // Display search results
        function displaySearchResults(data) {
            if (!data.success || data.data.length === 0) {
                searchResults.innerHTML = `
                    <div class="text-center text-gray-500 py-8">
                        <i class="fas fa-search text-2xl mb-2"></i>
                        <p>কোনো ফলাফল পাওয়া যায়নি</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            data.data.forEach(item => {
                html += `
                    <div class="p-3 hover:bg-gray-50 cursor-pointer rounded-lg" onclick="window.location.href='${item.url}'">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                <i class="fas ${item.icon} text-indigo-600"></i>
                            </div>
                            <div>
                                <div class="font-medium text-gray-900">${item.title}</div>
                                <div class="text-sm text-gray-500">${item.description}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            searchResults.innerHTML = html;
        }

        // Load notifications
        async function loadNotifications() {
            try {
                const response = await fetch('api/notifications.php');
                const data = await response.json();
                
                if (data.success) {
                    displayNotifications(data.data);
                    updateNotificationCount(data.unread_count);
                }
            } catch (error) {
                console.error('Load notifications error:', error);
            }
        }

        // Display notifications
        function displayNotifications(notifications) {
            const notificationList = document.getElementById('notificationList');
            
            if (notifications.length === 0) {
                notificationList.innerHTML = `
                    <div class="p-4 text-center text-gray-500">
                        <i class="fas fa-bell-slash text-2xl mb-2"></i>
                        <p>কোনো নোটিফিকেশন নেই</p>
                    </div>
                `;
                return;
            }
            
            let html = '';
            notifications.forEach(notification => {
                html += `
                    <div class="p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 ${notification.is_read ? 'opacity-60' : ''}" 
                         onclick="markNotificationRead(${notification.id})">
                        <div class="flex items-start space-x-3">
                            <div class="w-8 h-8 bg-${notification.type === 'success' ? 'green' : notification.type === 'error' ? 'red' : 'blue'}-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-${notification.type === 'success' ? 'check' : notification.type === 'error' ? 'exclamation' : 'info'} text-${notification.type === 'success' ? 'green' : notification.type === 'error' ? 'red' : 'blue'}-600 text-xs"></i>
                            </div>
                            <div class="flex-1">
                                <div class="text-sm font-medium text-gray-900">${notification.title}</div>
                                <div class="text-sm text-gray-600">${notification.message}</div>
                                <div class="text-xs text-gray-400 mt-1">${timeAgo(notification.created_at)}</div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            notificationList.innerHTML = html;
        }

        // Update notification count
        function updateNotificationCount(count) {
            const badge = document.getElementById('notificationCount');
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
            } else {
                badge.classList.add('hidden');
            }
        }

        // Mark notification as read
        async function markNotificationRead(notificationId) {
            try {
                await fetch(`api/notifications.php?action=read&id=${notificationId}`, {
                    method: 'POST'
                });
                loadNotifications();
            } catch (error) {
                console.error('Mark notification read error:', error);
            }
        }

        // Mark all notifications as read
        document.getElementById('markAllRead')?.addEventListener('click', async () => {
            try {
                await fetch('api/notifications.php?action=read_all', {
                    method: 'POST'
                });
                loadNotifications();
            } catch (error) {
                console.error('Mark all read error:', error);
            }
        });

        // Show/hide loading
        function showLoading() {
            loadingOverlay.classList.remove('hidden');
        }

        function hideLoading() {
            loadingOverlay.classList.add('hidden');
        }

        // Show toast notification
        function showToast(type, title, message = '') {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastMessage = document.getElementById('toastMessage');
            
            const icons = {
                success: '<i class="fas fa-check-circle text-green-500 text-xl"></i>',
                error: '<i class="fas fa-exclamation-circle text-red-500 text-xl"></i>',
                warning: '<i class="fas fa-exclamation-triangle text-yellow-500 text-xl"></i>',
                info: '<i class="fas fa-info-circle text-blue-500 text-xl"></i>'
            };
            
            toastIcon.innerHTML = icons[type] || icons.info;
            toastTitle.textContent = title;
            toastMessage.textContent = message;
            
            toast.classList.remove('hidden');
            toast.classList.add('slide-in');
            
            setTimeout(() => {
                toast.classList.add('hidden');
                toast.classList.remove('slide-in');
            }, 3000);
        }

        // Time ago function
        function timeAgo(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diff = Math.floor((now - date) / 1000);
            
            if (diff < 60) return 'এইমাত্র';
            if (diff < 3600) return Math.floor(diff / 60) + ' মিনিট আগে';
            if (diff < 86400) return Math.floor(diff / 3600) + ' ঘন্টা আগে';
            if (diff < 2592000) return Math.floor(diff / 86400) + ' দিন আগে';
            return Math.floor(diff / 2592000) + ' মাস আগে';
        }

        // Confirmation modal
        function showConfirm(title, message, onConfirm) {
            const modal = document.getElementById('confirmModal');
            const confirmTitle = document.getElementById('confirmTitle');
            const confirmMessage = document.getElementById('confirmMessage');
            const confirmOk = document.getElementById('confirmOk');
            const confirmCancel = document.getElementById('confirmCancel');
            
            confirmTitle.textContent = title;
            confirmMessage.textContent = message;
            
            modal.classList.remove('hidden');
            
            const handleConfirm = () => {
                modal.classList.add('hidden');
                onConfirm();
                cleanup();
            };
            
            const handleCancel = () => {
                modal.classList.add('hidden');
                cleanup();
            };
            
            const cleanup = () => {
                confirmOk.removeEventListener('click', handleConfirm);
                confirmCancel.removeEventListener('click', handleCancel);
            };
            
            confirmOk.addEventListener('click', handleConfirm);
            confirmCancel.addEventListener('click', handleCancel);
        }

        // Sync functionality
        let syncInterval;
        
        function startSync() {
            syncData();
            syncInterval = setInterval(syncData, SYNC_INTERVAL);
        }
        
        async function syncData() {
            const syncIcon = document.getElementById('syncIcon');
            const syncText = document.getElementById('syncText');
            
            try {
                syncIcon.classList.add('fa-spin');
                syncText.textContent = 'সিঙ্ক হচ্ছে...';
                
                const response = await fetch('api/sync.php');
                const data = await response.json();
                
                if (data.success) {
                    syncIcon.classList.remove('fa-spin');
                    syncIcon.classList.remove('text-green-500');
                    syncIcon.classList.add('text-blue-500');
                    syncText.textContent = 'সিঙ্ক হয়েছে';
                    
                    setTimeout(() => {
                        syncIcon.classList.remove('text-blue-500');
                        syncIcon.classList.add('text-green-500');
                        syncText.textContent = 'সিঙ্ক';
                    }, 2000);
                    
                    // Update UI if needed
                    if (data.updates && data.updates.length > 0) {
                        processUpdates(data.updates);
                    }
                }
            } catch (error) {
                console.error('Sync error:', error);
                syncIcon.classList.remove('fa-spin');
                syncIcon.classList.remove('text-green-500');
                syncIcon.classList.add('text-red-500');
                syncText.textContent = 'সিঙ্ক ব্যর্থ';
            }
        }
        
        function processUpdates(updates) {
            // Process real-time updates
            updates.forEach(update => {
                switch (update.type) {
                    case 'new_customer':
                        // Update customer list if on customer page
                        break;
                    case 'new_debt':
                        // Update debt list if on debt page
                        break;
                    case 'new_payment':
                        // Update payment list if on payment page
                        break;
                }
            });
        }
        
        // Initialize sync
        if (USER_ID) {
            startSync();
        }
        
        // Cleanup on page unload
        window.addEventListener('beforeunload', () => {
            if (syncInterval) {
                clearInterval(syncInterval);
            }
        });
    </script>
</body>
</html>
