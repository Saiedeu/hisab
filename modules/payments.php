<?php
/**
 * Payments Management Module
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load configuration and authentication
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';

// Set page variables
$pageTitle = 'পেমেন্ট ব্যবস্থাপনা - হিসাব পত্র';
$pageHeader = 'পেমেন্ট ব্যবস্থাপনা';
$pageSubHeader = 'পেমেন্ট গ্রহণ এবং হিসাব ব্যবস্থাপনা';

// Load models
require_once __DIR__ . '/../classes/Customer.php';
require_once __DIR__ . '/../classes/Debt.php';
require_once __DIR__ . '/../classes/Payment.php';

// Initialize models
$customerModel = new Customer();
$debtModel = new Debt();
$paymentModel = new Payment();
$currentUser = getCurrentUser();

// Handle form submissions
$action = $_GET['action'] ?? '';

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleAddPayment();
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleDeletePayment();
}

// Get parameters
$page = (int)($_GET['page'] ?? 1);
$search = sanitize($_GET['search'] ?? '');
$paymentMethod = sanitize($_GET['payment_method'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');
$customerId = (int)($_GET['customer_id'] ?? 0);
$debtId = (int)($_GET['debt_id'] ?? 0);

// Get data
try {
    $filters = [];
    
    if (!empty($paymentMethod)) {
        $filters['payment_method'] = $paymentMethod;
    }
    
    if (!empty($search)) {
        $filters['search'] = $search;
    }
    
    if (!empty($dateFrom)) {
        $filters['date_from'] = $dateFrom;
    }
    
    if (!empty($dateTo)) {
        $filters['date_to'] = $dateTo;
    }
    
    if ($customerId > 0) {
        $filters['customer_id'] = $customerId;
    }
    
    $payments = $paymentModel->getAllWithDetails($filters);
    $stats = $paymentModel->getStats($filters);
    $todayPayments = $paymentModel->getTodayPayments();
    $todayTotal = $paymentModel->getTodayTotal();
    
    // Apply pagination
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $paginatedPayments = array_slice($payments, $offset, ITEMS_PER_PAGE);
    $pagination = paginate(count($payments), ITEMS_PER_PAGE, $page, 'payments.php?page={page}');
    
} catch (Exception $e) {
    error_log("Payments module error: " . $e->getMessage());
    $payments = [];
    $paginatedPayments = [];
    $stats = ['total_payments' => 0, 'total_amount' => 0];
    $todayPayments = [];
    $todayTotal = ['count' => 0, 'total' => 0];
    $pagination = null;
}

/**
 * Handle add payment
 */
function handleAddPayment() {
    global $paymentModel, $debtModel, $currentUser;
    
    $debtId = (int)($_POST['debt_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $paymentMethod = sanitize($_POST['payment_method'] ?? 'cash');
    $paymentDate = sanitize($_POST['payment_date'] ?? getCurrentDate());
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Validation
    if ($debtId <= 0) {
        redirect('modules/payments.php', 'বাকি নির্বাচন করুন', 'error');
    }
    
    if ($amount <= 0) {
        redirect('modules/payments.php', 'পরিমাণ 0 এর বেশি হতে হবে', 'error');
    }
    
    if (!in_array($paymentMethod, ['cash', 'bank', 'mobile_banking', 'check'])) {
        redirect('modules/payments.php', 'পেমেন্ট মেথড সঠিক নয়', 'error');
    }
    
    if (empty($paymentDate)) {
        redirect('modules/payments.php', 'পেমেন্ট তারিখ প্রয়োজন', 'error');
    }
    
    // Get debt details
    $debt = $debtModel->getWithPayments($debtId);
    if (!$debt) {
        redirect('modules/payments.php', 'বাকি পাওয়া যায়নি', 'error');
    }
    
    // Check if payment amount exceeds remaining amount
    $remainingAmount = $debt['amount'] - ($debt['total_paid'] ?? 0);
    if ($amount > $remainingAmount) {
        redirect('modules/payments.php', "পেমেন্ট পরিমাণ অবশিষ্ট পরিমাণের বেশি (অবশিষ্ট: " . formatCurrency($remainingAmount) . ")", 'error');
    }
    
    // Create payment
    $paymentData = [
        'debt_id' => $debtId,
        'customer_id' => $debt['customer_id'],
        'amount' => $amount,
        'payment_method' => $paymentMethod,
        'payment_date' => $paymentDate,
        'notes' => $notes,
        'created_by' => $currentUser['id']
    ];
    
    $paymentId = $paymentModel->createPayment($paymentData);
    
    if ($paymentId) {
        logActivity($currentUser['id'], 'create', 'payments', $paymentId, null, $paymentData);
        
        // Check if debt is fully paid
        if (($debt['total_paid'] ?? 0) + $amount >= $debt['amount']) {
            logActivity($currentUser['id'], 'update', 'debts', $debtId, ['status' => $debt['status']], ['status' => 'paid']);
        }
        
        redirect('modules/payments.php', 'পেমেন্ট সফলভাবে যোগ করা হয়েছে', 'success');
    } else {
        redirect('modules/payments.php', 'পেমেন্ট যোগ করতে ব্যর্থ হয়েছে', 'error');
    }
}

/**
 * Handle delete payment
 */
function handleDeletePayment() {
    global $paymentModel, $currentUser;
    
    $paymentId = (int)($_POST['id'] ?? 0);
    
    if ($paymentId <= 0) {
        redirect('modules/payments.php', 'অবৈধ পেমেন্ট ID', 'error');
    }
    
    // Get payment
    $payment = $paymentModel->find($paymentId);
    if (!$payment) {
        redirect('modules/payments.php', 'পেমেন্ট পাওয়া যায়নি', 'error');
    }
    
    // Check permissions
    if (!canAccessResource($payment['created_by'])) {
        redirect('modules/payments.php', 'আপনার এই পেমেন্ট ডিলিট করার অনুমতি নেই', 'error');
    }
    
    // Delete payment
    if ($paymentModel->deletePayment($paymentId)) {
        logActivity($currentUser['id'], 'delete', 'payments', $paymentId, $payment, null);
        redirect('modules/payments.php', 'পেমেন্ট সফলভাবে ডিলিট করা হয়েছে', 'success');
    } else {
        redirect('modules/payments.php', 'পেমেন্ট ডিলিট করতে ব্যর্থ হয়েছে', 'error');
    }
}
?>

<!-- Search and Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex-1">
            <form method="GET" class="flex flex-col xl:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="গ্রাহকের নাম, ফোন, গ্রাম বা রশিদ নম্বর দিয়ে খুঁজুন..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div class="sm:w-40">
                    <select name="payment_method" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">সব মেথড</option>
                        <option value="cash" <?php echo $paymentMethod === 'cash' ? 'selected' : ''; ?>>ক্যাশ</option>
                        <option value="bank" <?php echo $paymentMethod === 'bank' ? 'selected' : ''; ?>>ব্যাংক</option>
                        <option value="mobile_banking" <?php echo $paymentMethod === 'mobile_banking' ? 'selected' : ''; ?>>মোবাইল ব্যাংকিং</option>
                        <option value="check" <?php echo $paymentMethod === 'check' ? 'selected' : ''; ?>>চেক</option>
                    </select>
                </div>
                <div class="sm:w-40">
                    <input type="date" name="date_from" value="<?php echo htmlspecialchars($dateFrom); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div class="sm:w-40">
                    <input type="date" name="date_to" value="<?php echo htmlspecialchars($dateTo); ?>" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
                    <i class="fas fa-search mr-2"></i>খুঁজুন
                </button>
                <?php if (!empty($search) || !empty($paymentMethod) || !empty($dateFrom) || !empty($dateTo)): ?>
                <a href="modules/payments.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                    <i class="fas fa-times mr-2"></i>ক্লিয়ার
                </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="flex gap-3">
            <button onclick="showAddPaymentModal()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                <i class="fas fa-plus mr-2"></i>নতুন পেমেন্ট
            </button>
        </div>
    </div>
</div>

<!-- Today's Summary -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">আজকের পেমেন্ট</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($todayTotal['count'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-day text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">আজকের আয়</p>
                <p class="text-2xl font-bold text-green-600 mt-2"><?php echo formatCurrency($todayTotal['total'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">মোট পেমেন্ট</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_payments'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-receipt text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">মোট আয়</p>
                <p class="text-2xl font-bold text-green-600 mt-2"><?php echo formatCurrency($stats['total_amount'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-chart-line text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Recent Payments -->
<?php if (!empty($todayPayments)): ?>
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">আজকের পেমেন্ট</h3>
        <span class="text-sm text-gray-500"><?php echo count($todayPayments); ?> টি পেমেন্ট</span>
    </div>
    <div class="space-y-3">
        <?php foreach (array_slice($todayPayments, 0, 5) as $payment): ?>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                    <i class="fas fa-money-bill text-green-600"></i>
                </div>
                <div>
                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                    <div class="text-xs text-gray-500">
                        <?php echo formatTime($payment['payment_date']); ?> • 
                        <?php echo $payment['payment_method'] === 'cash' ? 'ক্যাশ' : 
                                ($payment['payment_method'] === 'bank' ? 'ব্যাংক' : 
                                ($payment['payment_method'] === 'mobile_banking' ? 'মোবাইল ব্যাংকিং' : 'চেক')); ?>
                    </div>
                </div>
            </div>
            <div class="text-right">
                <div class="text-sm font-bold text-green-600"><?php echo formatCurrency($payment['amount']); ?></div>
                <div class="text-xs text-gray-500">রশিদ: <?php echo htmlspecialchars($payment['receipt_no']); ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- Payments Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            পেমেন্টের তালিকা 
            <?php if (!empty($search) || !empty($paymentMethod)): ?>
            <span class="text-sm font-normal text-gray-500">
                (ফিল্টার: <?php 
                $filters = [];
                if ($search) $filters[] = "খুঁজুন: $search";
                if ($paymentMethod) $filters[] = $paymentMethod === 'cash' ? 'ক্যাশ' : 
                                ($paymentMethod === 'bank' ? 'ব্যাংক' : 
                                ($paymentMethod === 'mobile_banking' ? 'মোবাইল ব্যাংকিং' : 'চেক');
                echo implode(', ', $filters); 
                ?>)
            </span>
            <?php endif; ?>
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">গ্রাহক</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">পরিমাণ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">মেথড</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">তারিখ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">রশিদ নম্বর</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">কর্মকর্তা</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">কার্যক্রম</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($paginatedPayments)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-money-bill-wave text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg">কোনো পেমেন্ট পাওয়া যায়নি</p>
                        <p class="text-sm mt-2">নতুন পেমেন্ট যোগ করতে "নতুন পেমেন্ট" বাটনে ক্লিক করুন</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($paginatedPayments as $payment): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['customer_name']); ?></div>
                                <div class="text-xs text-gray-500">
                                    <?php if (!empty($payment['customer_phone'])): ?>
                                    <?php echo formatPhone($payment['customer_phone']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($payment['customer_village'])): ?>
                                    • <?php echo htmlspecialchars($payment['customer_village']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-green-600">
                            <?php echo formatCurrency($payment['amount']); ?>
                        </div>
                        <?php if (!empty($payment['debt_description'])): ?>
                        <div class="text-xs text-gray-500 mt-1" title="<?php echo htmlspecialchars($payment['debt_description']); ?>">
                            <?php echo truncateText($payment['debt_description'], 25); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php 
                            switch ($payment['payment_method']) {
                                case 'cash':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'bank':
                                    echo 'bg-blue-100 text-blue-800';
                                    break;
                                case 'mobile_banking':
                                    echo 'bg-purple-100 text-purple-800';
                                    break;
                                case 'check':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php 
                            switch ($payment['payment_method']) {
                                case 'cash':
                                    echo 'ক্যাশ';
                                    break;
                                case 'bank':
                                    echo 'ব্যাংক';
                                    break;
                                case 'mobile_banking':
                                    echo 'মোবাইল ব্যাংকিং';
                                    break;
                                case 'check':
                                    echo 'চেক';
                                    break;
                                default:
                                    echo $payment['payment_method'];
                            }
                            ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php echo formatDate($payment['payment_date']); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo formatTime($payment['payment_date']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($payment['receipt_no']); ?>
                        </div>
                        <button onclick="printReceipt('<?php echo $payment['receipt_no']; ?>')" 
                                class="text-xs text-indigo-600 hover:text-indigo-900 mt-1">
                            <i class="fas fa-print mr-1"></i>প্রিন্ট
                        </button>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php echo htmlspecialchars($payment['created_by_name'] ?? 'Unknown'); ?>
                        </div>
                        <div class="text-xs text-gray-500">
                            <?php echo timeAgo($payment['created_at']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <a href="modules/payment_details.php?id=<?php echo $payment['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900" title="বিস্তারিত">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="printReceipt('<?php echo $payment['receipt_no']; ?>')" 
                                    class="text-blue-600 hover:text-blue-900" title="রশিদ প্রিন্ট">
                                <i class="fas fa-print"></i>
                            </button>
                            <?php if (canAccessResource($payment['created_by'])): ?>
                            <button onclick="deletePayment(<?php echo $payment['id']; ?>, '<?php echo htmlspecialchars($payment['customer_name']); ?>')" 
                                    class="text-red-600 hover:text-red-900" title="ডিলিট">
                                <i class="fas fa-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if ($pagination && $pagination['last_page'] > 1): ?>
    <div class="px-6 py-4 border-t border-gray-200">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700">
                দেখাচ্ছে <span class="font-medium"><?php echo $pagination['from']; ?></span> 
                থেকে <span class="font-medium"><?php echo $pagination['to']; ?></span> 
                এর মধ্যে <span class="font-medium"><?php echo $pagination['total']; ?></span> টি
            </div>
            <div class="flex items-center space-x-2">
                <?php if ($pagination['has_previous']): ?>
                <a href="<?php echo str_replace('{page}', $pagination['previous_page'], $pagination['links'][0]['url']); ?>" 
                   class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    «
                </a>
                <?php endif; ?>
                
                <?php foreach ($pagination['links'] as $link): ?>
                <?php if ($link['active']): ?>
                <span class="px-3 py-1 text-sm bg-indigo-600 text-white rounded-md">
                    <?php echo $link['label']; ?>
                </span>
                <?php elseif (!in_array($link['label'], ['«', '»'])): ?>
                <a href="<?php echo $link['url']; ?>" 
                   class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <?php echo $link['label']; ?>
                </a>
                <?php endif; ?>
                <?php endforeach; ?>
                
                <?php if ($pagination['has_next']): ?>
                <a href="<?php echo str_replace('{page}', $pagination['next_page'], end($pagination['links'])['url']); ?>" 
                   class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    »
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Add Payment Modal -->
<div id="addPaymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">নতুন পেমেন্ট যোগ করুন</h3>
        </div>
        <form method="POST" action="modules/payments.php?action=add" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">বাকি নির্বাচন করুন *</label>
                    <select name="debt_id" id="debtSelect" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">বাকি নির্বাচন করুন</option>
                    </select>
                    <div id="debtInfo" class="mt-2 text-sm text-gray-600 hidden">
                        <div>মোট বাকি: <span id="totalAmount" class="font-bold"></span></div>
                        <div>পরিশোধিত: <span id="paidAmount" class="font-bold"></span></div>
                        <div>অবশিষ্ট: <span id="remainingAmount" class="font-bold text-red-600"></span></div>
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">পরিমাণ *</label>
                    <input type="number" name="amount" id="paymentAmount" step="0.01" min="0.01" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="০.০০">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">পেমেন্ট মেথড *</label>
                    <select name="payment_method" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="cash">ক্যাশ</option>
                        <option value="bank">ব্যাংক</option>
                        <option value="mobile_banking">মোবাইল ব্যাংকিং</option>
                        <option value="check">চেক</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">পেমেন্ট তারিখ *</label>
                    <input type="date" name="payment_date" value="<?php echo getCurrentDate(); ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">নোট</label>
                    <textarea name="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="পেমেন্টের নোট..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideAddPaymentModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    বাতিল
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    পেমেন্ট যোগ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Load customer debts
let customerDebts = [];

async function loadCustomerDebts(customerId = null) {
    try {
        let url = 'api/debts.php?action=list';
        if (customerId) {
            url += `&customer_id=${customerId}`;
        }
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success) {
            customerDebts = data.data.filter(debt => {
                const remaining = debt.amount - (debt.total_paid || 0);
                return remaining > 0;
            });
            
            populateDebtSelect();
        }
    } catch (error) {
        console.error('Load debts error:', error);
    }
}

function populateDebtSelect() {
    const select = document.getElementById('debtSelect');
    select.innerHTML = '<option value="">বাকি নির্বাচন করুন</option>';
    
    customerDebts.forEach(debt => {
        const remaining = debt.amount - (debt.total_paid || 0);
        const option = document.createElement('option');
        option.value = debt.id;
        option.textContent = `${debt.customer_name} - ${formatCurrency(remaining)} (${debt.type === 'given' ? 'বাকি দেয়া' : 'বাকি নেয়া'})`;
        option.dataset.amount = debt.amount;
        option.dataset.paid = debt.total_paid || 0;
        option.dataset.customer = debt.customer_name;
        select.appendChild(option);
    });
}

// Modal functions
function showAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.remove('hidden');
    loadCustomerDebts();
}

function hideAddPaymentModal() {
    document.getElementById('addPaymentModal').classList.add('hidden');
}

function deletePayment(id, customerName) {
    showConfirm(
        'পেমেন্ট ডিলিট করুন',
        `আপনি কি "${customerName}" এর পেমেন্ট ডিলিট করতে চান? এই কাজটি অপরিবর্তনীয়।`,
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'modules/payments.php?action=delete';
            
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'id';
            input.value = id;
            
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
        }
    );
}

function printReceipt(receiptNo) {
    window.open(`modules/print_receipt.php?receipt_no=${receiptNo}`, '_blank', 'width=800,height=600');
}

// Debt selection handler
document.getElementById('debtSelect')?.addEventListener('change', (e) => {
    const selectedOption = e.target.options[e.target.selectedIndex];
    const debtInfo = document.getElementById('debtInfo');
    
    if (e.target.value) {
        const totalAmount = parseFloat(selectedOption.dataset.amount);
        const paidAmount = parseFloat(selectedOption.dataset.paid);
        const remainingAmount = totalAmount - paidAmount;
        
        document.getElementById('totalAmount').textContent = formatCurrency(totalAmount);
        document.getElementById('paidAmount').textContent = formatCurrency(paidAmount);
        document.getElementById('remainingAmount').textContent = formatCurrency(remainingAmount);
        
        // Set max amount for payment
        document.getElementById('paymentAmount').max = remainingAmount;
        document.getElementById('paymentAmount').placeholder = `সর্বোচ্চ: ${formatCurrency(remainingAmount)}`;
        
        debtInfo.classList.remove('hidden');
    } else {
        debtInfo.classList.add('hidden');
        document.getElementById('paymentAmount').max = '';
        document.getElementById('paymentAmount').placeholder = '০.০০';
    }
});

// Payment amount validation
document.getElementById('paymentAmount')?.addEventListener('input', (e) => {
    const maxAmount = parseFloat(e.target.max);
    const amount = parseFloat(e.target.value);
    
    if (maxAmount && amount > maxAmount) {
        e.target.value = maxAmount;
        showToast('error', `পেমেন্ট পরিমাণ সর্বোচ্চ ${formatCurrency(maxAmount)} হতে পারে`);
    }
});

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hideAddPaymentModal();
    }
});

// Close modal on background click
document.getElementById('addPaymentModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'addPaymentModal') {
        hideAddPaymentModal();
    }
});

// Form validation
document.querySelector('form[action*="action=add"]')?.addEventListener('submit', (e) => {
    const debtId = e.target.querySelector('#debtSelect').value;
    const amount = parseFloat(e.target.querySelector('#paymentAmount').value);
    const paymentMethod = e.target.querySelector('select[name="payment_method"]').value;
    const paymentDate = e.target.querySelector('input[name="payment_date"]').value;
    
    if (!debtId) {
        e.preventDefault();
        showToast('error', 'বাকি নির্বাচন করুন');
        return;
    }
    
    if (amount <= 0) {
        e.preventDefault();
        showToast('error', 'পরিমাণ 0 এর বেশি হতে হবে');
        return;
    }
    
    if (!paymentMethod) {
        e.preventDefault();
        showToast('error', 'পেমেন্ট মেথড নির্বাচন করুন');
        return;
    }
    
    if (!paymentDate) {
        e.preventDefault();
        showToast('error', 'পেমেন্ট তারিখ নির্বাচন করুন');
        return;
    }
});

// Auto-select debt if provided in URL
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const debtId = urlParams.get('debt_id');
    const customerName = urlParams.get('customer_name');
    const maxAmount = urlParams.get('max_amount');
    
    if (debtId && customerName) {
        showAddPaymentModal();
        // Load specific debt
        loadCustomerDebts().then(() => {
            const select = document.getElementById('debtSelect');
            select.value = debtId;
            select.dispatchEvent(new Event('change'));
            
            if (maxAmount) {
                document.getElementById('paymentAmount').value = maxAmount;
            }
        });
    }
});

// Format currency helper
function formatCurrency(amount) {
    return '৳' + parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

// Format time helper
function formatTime(datetime) {
    const date = new Date(datetime);
    return date.toLocaleTimeString('bn-BD', { hour: '2-digit', minute: '2-digit' });
}
</script>
