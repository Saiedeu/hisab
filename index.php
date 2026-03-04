<?php
/**
 * Main Dashboard
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load configuration and authentication
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/includes/header.php';

// Set page variables
$pageTitle = 'ড্যাশবোর্ড - হিসাব পত্র';
$pageHeader = 'ড্যাশবোর্ড';
$pageSubHeader = 'আপনার ব্যবসার সারসংক্ষেপ';

// Load models
require_once __DIR__ . '/classes/Customer.php';
require_once __DIR__ . '/classes/Debt.php';
require_once __DIR__ . '/classes/Payment.php';

// Initialize models
$customerModel = new Customer();
$debtModel = new Debt();
$paymentModel = new Payment();

// Get dashboard statistics
try {
    // Customer statistics
    $customerStats = $customerModel->getStats();
    $recentCustomers = $customerModel->getRecentCustomers(5);
    $villages = $customerModel->getVillages();
    
    // Debt statistics
    $debtStats = $debtModel->getStats();
    $recentDebts = $debtModel->getRecentDebts(5);
    $overdueDebts = $debtModel->getOverdueDebts();
    $topDebtors = $debtModel->getTopCustomers('given', 5);
    
    // Payment statistics
    $paymentStats = $paymentModel->getStats();
    $todayPayments = $paymentModel->getTodayPayments();
    $todayTotal = $paymentModel->getTodayTotal();
    $recentPayments = $paymentModel->getRecentPayments(5);
    
    // Monthly statistics for charts
    $monthlyDebts = $debtModel->getMonthlyStats();
    $monthlyPayments = $paymentModel->getMonthlyStats();
    
    // Calculate net balance
    $netBalance = ($debtStats['total_given'] ?? 0) - ($debtStats['total_taken'] ?? 0);
    $totalCollected = $paymentStats['total_amount'] ?? 0;
    
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    // Set default values on error
    $customerStats = ['total_customers' => 0, 'today_customers' => 0];
    $debtStats = ['total_debts' => 0, 'total_given' => 0, 'total_taken' => 0, 'pending_amount' => 0];
    $paymentStats = ['total_payments' => 0, 'total_amount' => 0];
    $recentCustomers = [];
    $recentDebts = [];
    $recentPayments = [];
    $overdueDebts = [];
    $topDebtors = [];
    $todayPayments = [];
    $todayTotal = ['count' => 0, 'total' => 0];
    $monthlyDebts = [];
    $monthlyPayments = [];
    $netBalance = 0;
    $totalCollected = 0;
}
?>

<!-- Dashboard Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Customers -->
    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">মোট গ্রাহক</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($customerStats['total_customers'] ?? 0); ?></p>
                <p class="text-xs text-green-600 mt-1">
                    <i class="fas fa-arrow-up"></i>
                    +<?php echo $customerStats['today_customers'] ?? 0; ?> আজ
                </p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Debts -->
    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">মোট বাকি</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo formatCurrency($debtStats['total_given'] ?? 0); ?></p>
                <p class="text-xs text-orange-600 mt-1">
                    <i class="fas fa-clock"></i>
                    <?php echo formatCurrency($debtStats['pending_amount'] ?? 0); ?> বকেয়া
                </p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-hand-holding-usd text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Total Collected -->
    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">সংগৃহীত</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo formatCurrency($totalCollected); ?></p>
                <p class="text-xs text-green-600 mt-1">
                    <i class="fas fa-arrow-up"></i>
                    <?php echo formatCurrency($todayTotal['total'] ?? 0); ?> আজ
                </p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-green-600 text-xl"></i>
            </div>
        </div>
    </div>

    <!-- Net Balance -->
    <div class="bg-white rounded-xl shadow-sm p-6 card-hover">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">নেট ব্যালেন্স</p>
                <p class="text-2xl font-bold <?php echo $netBalance >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-2">
                    <?php echo formatCurrency(abs($netBalance)); ?>
                </p>
                <p class="text-xs <?php echo $netBalance >= 0 ? 'text-green-600' : 'text-red-600'; ?> mt-1">
                    <i class="fas fa-<?php echo $netBalance >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                    <?php echo $netBalance >= 0 ? 'পাওনা' : 'দেনা'; ?>
                </p>
            </div>
            <div class="w-12 h-12 bg-<?php echo $netBalance >= 0 ? 'green' : 'red'; ?>-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-balance-scale text-<?php echo $netBalance >= 0 ? 'green' : 'red'; ?>-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts and Recent Activities -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Monthly Chart -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">মাসিক লেনদেন</h3>
            <select id="chartYear" class="text-sm border border-gray-300 rounded-lg px-3 py-1">
                <?php 
                $currentYear = date('Y');
                for ($year = $currentYear; $year >= $currentYear - 2; $year--) {
                    echo "<option value='{$year}' " . ($year == $currentYear ? 'selected' : '') . ">{$year}</option>";
                }
                ?>
            </select>
        </div>
        <canvas id="monthlyChart" height="120"></canvas>
    </div>

    <!-- Quick Actions -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">দ্রুত কাজ</h3>
        <div class="space-y-3">
            <a href="modules/customers.php?action=add" class="flex items-center p-3 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                <i class="fas fa-user-plus text-blue-600 mr-3"></i>
                <span class="text-sm font-medium text-blue-900">নতুন গ্রাহক</span>
            </a>
            <a href="modules/debts.php?action=add" class="flex items-center p-3 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                <i class="fas fa-plus-circle text-orange-600 mr-3"></i>
                <span class="text-sm font-medium text-orange-900">নতুন বাকি</span>
            </a>
            <a href="modules/payments.php?action=add" class="flex items-center p-3 bg-green-50 rounded-lg hover:bg-green-100 transition">
                <i class="fas fa-money-bill text-green-600 mr-3"></i>
                <span class="text-sm font-medium text-green-900">পেমেন্ট</span>
            </a>
            <a href="modules/reports.php" class="flex items-center p-3 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                <i class="fas fa-chart-bar text-purple-600 mr-3"></i>
                <span class="text-sm font-medium text-purple-900">রিপোর্ট</span>
            </a>
        </div>
    </div>
</div>

<!-- Recent Activities and Top Debtors -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Recent Customers -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">নতুন গ্রাহক</h3>
            <a href="modules/customers.php" class="text-sm text-indigo-600 hover:text-indigo-800">সব দেখুন</a>
        </div>
        <div class="space-y-3">
            <?php if (empty($recentCustomers)): ?>
            <p class="text-gray-500 text-sm text-center py-4">কোনো নতুন গ্রাহক নেই</p>
            <?php else: ?>
            <?php foreach ($recentCustomers as $customer): ?>
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-user text-indigo-600 text-xs"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($customer['phone'] ?? 'N/A'); ?></p>
                    </div>
                </div>
                <span class="text-xs text-gray-400"><?php echo timeAgo($customer['created_at']); ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Debts -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">সাম্প্রতিক বাকি</h3>
            <a href="modules/debts.php" class="text-sm text-indigo-600 hover:text-indigo-800">সব দেখুন</a>
        </div>
        <div class="space-y-3">
            <?php if (empty($recentDebts)): ?>
            <p class="text-gray-500 text-sm text-center py-4">কোনো বাকি নেই</p>
            <?php else: ?>
            <?php foreach ($recentDebts as $debt): ?>
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-<?php echo $debt['type'] == 'given' ? 'orange' : 'blue'; ?>-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-<?php echo $debt['type'] == 'given' ? 'arrow-up' : 'arrow-down'; ?> text-<?php echo $debt['type'] == 'given' ? 'orange' : 'blue'; ?>-600 text-xs"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($debt['customer_name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo formatCurrency($debt['amount']); ?></p>
                    </div>
                </div>
                <span class="text-xs text-gray-400"><?php echo timeAgo($debt['created_at']); ?></span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Debtors -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">শীর্ষ খেলাপী</h3>
            <a href="modules/reports.php?type=debtors" class="text-sm text-indigo-600 hover:text-indigo-800">সব দেখুন</a>
        </div>
        <div class="space-y-3">
            <?php if (empty($topDebtors)): ?>
            <p class="text-gray-500 text-sm text-center py-4">কোনো খেলাপী নেই</p>
            <?php else: ?>
            <?php foreach ($topDebtors as $debtor): ?>
            <div class="flex items-center justify-between p-2 hover:bg-gray-50 rounded-lg">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-exclamation text-red-600 text-xs"></i>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($debtor['name']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo formatCurrency($debtor['net_balance']); ?></p>
                    </div>
                </div>
                <span class="text-xs text-red-600 font-medium"><?php echo $debtor['debt_count']; ?> টি</span>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Overdue Debts Alert -->
<?php if (!empty($overdueDebts)): ?>
<div class="mt-6 bg-red-50 border border-red-200 rounded-xl p-4">
    <div class="flex items-center">
        <div class="flex-shrink-0">
            <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
        </div>
        <div class="ml-3 flex-1">
            <h3 class="text-sm font-medium text-red-800">মেয়াদোত্তীর্ণ বাকি</h3>
            <div class="mt-2 text-sm text-red-700">
                <p><?php echo count($overdueDebts); ?> টি বাকির মেয়াদ উত্তীর্ণ হয়েছে। 
                    <a href="modules/debts.php?status=overdue" class="font-medium underline">বিস্তারিত দেখুন</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
// Monthly Chart
const ctx = document.getElementById('monthlyChart');
if (ctx) {
    const monthlyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['জানু', 'ফেব্রু', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'],
            datasets: [{
                label: 'বাকি দেয়া',
                data: <?php echo json_encode(array_fill(0, 12, 0)); ?>,
                borderColor: 'rgb(251, 146, 60)',
                backgroundColor: 'rgba(251, 146, 60, 0.1)',
                tension: 0.4
            }, {
                label: 'বাকি নেয়',
                data: <?php echo json_encode(array_fill(0, 12, 0)); ?>,
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '৳' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
    
    // Update chart with actual data
    <?php if (!empty($monthlyDebts)): ?>
    const debtData = <?php echo json_encode($monthlyDebts); ?>;
    debtData.forEach(item => {
        const monthIndex = item.month - 1;
        monthlyChart.data.datasets[0].data[monthIndex] = parseFloat(item.given_amount) || 0;
        monthlyChart.data.datasets[1].data[monthIndex] = parseFloat(item.taken_amount) || 0;
    });
    monthlyChart.update();
    <?php endif; ?>
    
    // Handle year change
    document.getElementById('chartYear')?.addEventListener('change', async (e) => {
        const year = e.target.value;
        try {
            const response = await fetch(`api/charts.php?type=monthly&year=${year}`);
            const data = await response.json();
            
            if (data.success) {
                monthlyChart.data.datasets[0].data = new Array(12).fill(0);
                monthlyChart.data.datasets[1].data = new Array(12).fill(0);
                
                data.data.forEach(item => {
                    const monthIndex = item.month - 1;
                    monthlyChart.data.datasets[0].data[monthIndex] = parseFloat(item.given_amount) || 0;
                    monthlyChart.data.datasets[1].data[monthIndex] = parseFloat(item.taken_amount) || 0;
                });
                
                monthlyChart.update();
            }
        } catch (error) {
            console.error('Chart update error:', error);
        }
    });
}

// Auto-refresh dashboard data
let refreshInterval;
function startAutoRefresh() {
    refreshInterval = setInterval(async () => {
        try {
            const response = await fetch('api/dashboard.php');
            const data = await response.json();
            
            if (data.success) {
                // Update statistics cards
                updateStatCards(data.stats);
                
                // Update recent activities
                updateRecentActivities(data.activities);
            }
        } catch (error) {
            console.error('Dashboard refresh error:', error);
        }
    }, 30000); // Refresh every 30 seconds
}

function updateStatCards(stats) {
    // Update customer count
    const customerCard = document.querySelector('[data-stat="customers"]');
    if (customerCard && stats.customers) {
        customerCard.querySelector('.text-2xl').textContent = stats.customers.total.toLocaleString();
    }
    
    // Update other stats similarly
}

function updateRecentActivities(activities) {
    // Update recent activities lists
}

// Start auto-refresh if page is visible
if (!document.hidden) {
    startAutoRefresh();
}

// Pause refresh when page is hidden
document.addEventListener('visibilitychange', () => {
    if (document.hidden) {
        clearInterval(refreshInterval);
    } else {
        startAutoRefresh();
    }
});

// Clean up on page unload
window.addEventListener('beforeunload', () => {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>
