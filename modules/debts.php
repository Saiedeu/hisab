<?php
/**
 * Debts Management Module
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load configuration and authentication
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';

// Set page variables
$pageTitle = 'বাকি ব্যবস্থাপনা - হিসাব পত্র';
$pageHeader = 'বাকি ব্যবস্থাপনা';
$pageSubHeader = 'বাকি দেওয়া/নেওয়া হিসাব ব্যবস্থাপনা';

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
    handleAddDebt();
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleEditDebt();
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleDeleteDebt();
}

// Get parameters
$page = (int)($_GET['page'] ?? 1);
$search = sanitize($_GET['search'] ?? '');
$type = sanitize($_GET['type'] ?? '');
$status = sanitize($_GET['status'] ?? '');
$dateFrom = sanitize($_GET['date_from'] ?? '');
$dateTo = sanitize($_GET['date_to'] ?? '');
$customerId = (int)($_GET['customer_id'] ?? 0);

// Get data
try {
    $filters = [];
    
    if (!empty($type)) {
        $filters['type'] = $type;
    }
    
    if (!empty($status)) {
        $filters['status'] = $status;
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
    
    $debts = $debtModel->getAllWithDetails($filters);
    $stats = $debtModel->getStats($filters);
    $customers = $customerModel->all();
    $overdueDebts = $debtModel->getOverdueDebts();
    
    // Apply pagination
    $offset = ($page - 1) * ITEMS_PER_PAGE;
    $paginatedDebts = array_slice($debts, $offset, ITEMS_PER_PAGE);
    $pagination = paginate(count($debts), ITEMS_PER_PAGE, $page, 'debts.php?page={page}');
    
} catch (Exception $e) {
    error_log("Debts module error: " . $e->getMessage());
    $debts = [];
    $paginatedDebts = [];
    $stats = ['total_debts' => 0, 'total_given' => 0, 'total_taken' => 0];
    $customers = [];
    $overdueDebts = [];
    $pagination = null;
}

/**
 * Handle add debt
 */
function handleAddDebt() {
    global $debtModel, $customerModel, $currentUser;
    
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $type = sanitize($_POST['type'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date = sanitize($_POST['date'] ?? getCurrentDate());
    $dueDate = sanitize($_POST['due_date'] ?? '');
    
    // Validation
    if ($customerId <= 0) {
        redirect('modules/debts.php', 'গ্রাহক নির্বাচন করুন', 'error');
    }
    
    if ($amount <= 0) {
        redirect('modules/debts.php', 'পরিমাণ 0 এর বেশি হতে হবে', 'error');
    }
    
    if (!in_array($type, ['given', 'taken'])) {
        redirect('modules/debts.php', 'বাকির ধরন সঠিক নয়', 'error');
    }
    
    if (empty($date)) {
        redirect('modules/debts.php', 'তারিখ প্রয়োজন', 'error');
    }
    
    // Check if customer exists
    $customer = $customerModel->find($customerId);
    if (!$customer) {
        redirect('modules/debts.php', 'গ্রাহক পাওয়া যায়নি', 'error');
    }
    
    // Create debt
    $debtData = [
        'customer_id' => $customerId,
        'amount' => $amount,
        'type' => $type,
        'description' => $description,
        'date' => $date,
        'due_date' => $dueDate ?: null,
        'status' => 'pending',
        'created_by' => $currentUser['id']
    ];
    
    $debtId = $debtModel->create($debtData);
    
    if ($debtId) {
        logActivity($currentUser['id'], 'create', 'debts', $debtId, null, $debtData);
        redirect('modules/debts.php', 'বাকি সফলভাবে যোগ করা হয়েছে', 'success');
    } else {
        redirect('modules/debts.php', 'বাকি যোগ করতে ব্যর্থ হয়েছে', 'error');
    }
}

/**
 * Handle edit debt
 */
function handleEditDebt() {
    global $debtModel, $customerModel, $currentUser;
    
    $debtId = (int)($_POST['id'] ?? 0);
    $customerId = (int)($_POST['customer_id'] ?? 0);
    $amount = (float)($_POST['amount'] ?? 0);
    $type = sanitize($_POST['type'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $date = sanitize($_POST['date'] ?? '');
    $dueDate = sanitize($_POST['due_date'] ?? '');
    $status = sanitize($_POST['status'] ?? '');
    
    if ($debtId <= 0) {
        redirect('modules/debts.php', 'অবৈধ বাকি ID', 'error');
    }
    
    // Get existing debt
    $existingDebt = $debtModel->find($debtId);
    if (!$existingDebt) {
        redirect('modules/debts.php', 'বাকি পাওয়া যায়নি', 'error');
    }
    
    // Check permissions
    if (!canAccessResource($existingDebt['created_by'])) {
        redirect('modules/debts.php', 'আপনার এই বাকি সম্পাদনা করার অনুমতি নেই', 'error');
    }
    
    // Update debt
    $updateData = [
        'customer_id' => $customerId,
        'amount' => $amount,
        'type' => $type,
        'description' => $description,
        'date' => $date,
        'due_date' => $dueDate ?: null,
        'status' => $status
    ];
    
    if ($debtModel->update($debtId, $updateData)) {
        logActivity($currentUser['id'], 'update', 'debts', $debtId, $existingDebt, $updateData);
        redirect('modules/debts.php', 'বাকি সফলভাবে আপডেট করা হয়েছে', 'success');
    } else {
        redirect('modules/debts.php', 'বাকি আপডেট করতে ব্যর্থ হয়েছে', 'error');
    }
}

/**
 * Handle delete debt
 */
function handleDeleteDebt() {
    global $debtModel, $currentUser;
    
    $debtId = (int)($_POST['id'] ?? 0);
    
    if ($debtId <= 0) {
        redirect('modules/debts.php', 'অবৈধ বাকি ID', 'error');
    }
    
    // Get debt
    $debt = $debtModel->find($debtId);
    if (!$debt) {
        redirect('modules/debts.php', 'বাকি পাওয়া যায়নি', 'error');
    }
    
    // Check permissions
    if (!canAccessResource($debt['created_by'])) {
        redirect('modules/debts.php', 'আপনার এই বাকি ডিলিট করার অনুমতি নেই', 'error');
    }
    
    // Delete debt with payments
    if ($debtModel->deleteWithPayments($debtId)) {
        logActivity($currentUser['id'], 'delete', 'debts', $debtId, $debt, null);
        redirect('modules/debts.php', 'বাকি সফলভাবে ডিলিট করা হয়েছে', 'success');
    } else {
        redirect('modules/debts.php', 'বাকি ডিলিট করতে ব্যর্থ হয়েছে', 'error');
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
                           placeholder="গ্রাহকের নাম, ফোন বা গ্রাম দিয়ে খুঁজুন..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div class="sm:w-32">
                    <select name="type" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">সব ধরন</option>
                        <option value="given" <?php echo $type === 'given' ? 'selected' : ''; ?>>বাকি দেয়া</option>
                        <option value="taken" <?php echo $type === 'taken' ? 'selected' : ''; ?>>বাকি নেয়া</option>
                    </select>
                </div>
                <div class="sm:w-32">
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">সব স্ট্যাটাস</option>
                        <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>বকেয়া</option>
                        <option value="partial" <?php echo $status === 'partial' ? 'selected' : ''; ?>>আংশিক</option>
                        <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>পরিশোধিত</option>
                        <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>মেয়াদোত্তীর্ণ</option>
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
                <?php if (!empty($search) || !empty($type) || !empty($status) || !empty($dateFrom) || !empty($dateTo)): ?>
                <a href="modules/debts.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                    <i class="fas fa-times mr-2"></i>ক্লিয়ার
                </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="flex gap-3">
            <button onclick="showAddDebtModal()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                <i class="fas fa-plus mr-2"></i>নতুন বাকি
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">মোট বাকি</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_debts'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-hand-holding-usd text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">বাকি দেয়া</p>
                <p class="text-2xl font-bold text-orange-600 mt-2"><?php echo formatCurrency($stats['total_given'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-arrow-up text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">বাকি নেয়া</p>
                <p class="text-2xl font-bold text-blue-600 mt-2"><?php echo formatCurrency($stats['total_taken'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-arrow-down text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">বকেয়া</p>
                <p class="text-2xl font-bold text-red-600 mt-2"><?php echo formatCurrency($stats['pending_amount'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-clock text-red-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Overdue Debts Alert -->
<?php if (!empty($overdueDebts)): ?>
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
    <div class="flex items-center justify-between">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-exclamation-triangle text-red-400 text-xl"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">মেয়াদোত্তীর্ণ বাকি</h3>
                <div class="mt-1 text-sm text-red-700">
                    <p><?php echo count($overdueDebts); ?> টি বাকির মেয়াদ উত্তীর্ণ হয়েছে। 
                        <a href="modules/debts.php?status=overdue" class="font-medium underline">বিস্তারিত দেখুন</a>
                    </p>
                </div>
            </div>
        </div>
        <div class="text-red-600 font-bold">
            <?php 
            $totalOverdue = array_sum(array_column($overdueDebts, 'amount'));
            echo formatCurrency($totalOverdue); 
            ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Debts Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            বাকির তালিকা 
            <?php if (!empty($search) || !empty($type) || !empty($status)): ?>
            <span class="text-sm font-normal text-gray-500">
                (ফিল্টার: <?php 
                $filters = [];
                if ($search) $filters[] = "খুঁজুন: $search";
                if ($type) $filters[] = $type === 'given' ? 'বাকি দেয়া' : 'বাকি নেয়া';
                if ($status) $filters[] = "স্ট্যাটাস: $status";
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ধরন</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">পরিমাণ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">পরিশোধিত</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">অবশিষ্ট</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">তারিখ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">স্ট্যাটাস</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">কার্যক্রম</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($paginatedDebts)): ?>
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-hand-holding-usd text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg">কোনো বাকি পাওয়া যায়নি</p>
                        <p class="text-sm mt-2">নতুন বাকি যোগ করতে "নতুন বাকি" বাটনে ক্লিক করুন</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($paginatedDebts as $debt): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($debt['customer_name']); ?></div>
                                <div class="text-xs text-gray-500">
                                    <?php if (!empty($debt['customer_phone'])): ?>
                                    <?php echo formatPhone($debt['customer_phone']); ?>
                                    <?php endif; ?>
                                    <?php if (!empty($debt['customer_village'])): ?>
                                    • <?php echo htmlspecialchars($debt['customer_village']); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php echo $debt['type'] === 'given' ? 'bg-orange-100 text-orange-800' : 'bg-blue-100 text-blue-800'; ?>">
                            <i class="fas fa-<?php echo $debt['type'] === 'given' ? 'arrow-up' : 'arrow-down'; ?> mr-1"></i>
                            <?php echo $debt['type'] === 'given' ? 'বাকি দেয়া' : 'বাকি নেয়া'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">
                            <?php echo formatCurrency($debt['amount']); ?>
                        </div>
                        <?php if (!empty($debt['description'])): ?>
                        <div class="text-xs text-gray-500 mt-1" title="<?php echo htmlspecialchars($debt['description']); ?>">
                            <?php echo truncateText($debt['description'], 30); ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-green-600 font-medium">
                            <?php echo formatCurrency($debt['total_paid'] ?? 0); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium <?php echo ($debt['remaining_amount'] ?? 0) > 0 ? 'text-red-600' : 'text-green-600'; ?>">
                            <?php echo formatCurrency($debt['remaining_amount'] ?? $debt['amount']); ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php echo formatDate($debt['date']); ?>
                        </div>
                        <?php if (!empty($debt['due_date'])): ?>
                        <div class="text-xs <?php 
                            $dueDate = new DateTime($debt['due_date']);
                            $today = new DateTime();
                            $isOverdue = $dueDate < $today && ($debt['remaining_amount'] ?? $debt['amount']) > 0;
                            echo $isOverdue ? 'text-red-600 font-medium' : 'text-gray-500'; 
                        ?>">
                            মেয়াদ: <?php echo formatDate($debt['due_date']); ?>
                            <?php if ($isOverdue): ?>
                            <i class="fas fa-exclamation-triangle ml-1"></i>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                            <?php 
                            switch ($debt['status']) {
                                case 'paid':
                                    echo 'bg-green-100 text-green-800';
                                    break;
                                case 'partial':
                                    echo 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'overdue':
                                    echo 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    echo 'bg-gray-100 text-gray-800';
                            }
                            ?>">
                            <?php 
                            switch ($debt['status']) {
                                case 'paid':
                                    echo 'পরিশোধিত';
                                    break;
                                case 'partial':
                                    echo 'আংশিক';
                                    break;
                                case 'overdue':
                                    echo 'মেয়াদোত্তীর্ণ';
                                    break;
                                default:
                                    echo 'বকেয়া';
                            }
                            ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <a href="modules/debt_details.php?id=<?php echo $debt['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900" title="বিস্তারিত">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (($debt['remaining_amount'] ?? $debt['amount']) > 0): ?>
                            <button onclick="addPayment(<?php echo $debt['id']; ?>, <?php echo $debt['amount']; ?>, '<?php echo htmlspecialchars($debt['customer_name']); ?>')" 
                                    class="text-green-600 hover:text-green-900" title="পেমেন্ট">
                                <i class="fas fa-money-bill"></i>
                            </button>
                            <?php endif; ?>
                            <?php if (canAccessResource($debt['created_by'])): ?>
                            <button onclick="editDebt(<?php echo $debt['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-900" title="সম্পাদনা">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="deleteDebt(<?php echo $debt['id']; ?>, '<?php echo htmlspecialchars($debt['customer_name']); ?>')" 
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

<!-- Add Debt Modal -->
<div id="addDebtModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">নতুন বাকি যোগ করুন</h3>
        </div>
        <form method="POST" action="modules/debts.php?action=add" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">গ্রাহক *</label>
                    <select name="customer_id" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">গ্রাহক নির্বাচন করুন</option>
                        <?php foreach ($customers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>"
                                <?php echo (isset($_GET['customer_id']) && $_GET['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($customer['name']); ?>
                            <?php if (!empty($customer['phone'])): ?>
                            (<?php echo formatPhone($customer['phone']); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">বাকির ধরন *</label>
                    <select name="type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">ধরন নির্বাচন করুন</option>
                        <option value="given">বাকি দেয়া (গ্রাহক আমাদের কাছে পাবে)</option>
                        <option value="taken">বাকি নেয়া (আমরা গ্রাহকের কাছ থেকে পাব)</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">পরিমাণ *</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="০.০০">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">তারিখ *</label>
                    <input type="date" name="date" value="<?php echo getCurrentDate(); ?>" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">মেয়াদ তারিখ</label>
                    <input type="date" name="due_date"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">বিবরণ</label>
                    <textarea name="description" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="বাকির বিবরণ..."></textarea>
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideAddDebtModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    বাতিল
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    বাকি যোগ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Modal functions
function showAddDebtModal() {
    document.getElementById('addDebtModal').classList.remove('hidden');
}

function hideAddDebtModal() {
    document.getElementById('addDebtModal').classList.add('hidden');
}

function editDebt(id) {
    window.location.href = `modules/debts.php?action=edit&id=${id}`;
}

function deleteDebt(id, customerName) {
    showConfirm(
        'বাকি ডিলিট করুন',
        `আপনি কি "${customerName}" এর বাকি ডিলিট করতে চান? এই কাজটি অপরিবর্তনীয়।`,
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'modules/debts.php?action=delete';
            
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

function addPayment(debtId, totalAmount, customerName) {
    window.location.href = `modules/payments.php?action=add&debt_id=${debtId}&customer_name=${encodeURIComponent(customerName)}&max_amount=${totalAmount}`;
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hideAddDebtModal();
    }
});

// Close modal on background click
document.getElementById('addDebtModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'addDebtModal') {
        hideAddDebtModal();
    }
});

// Form validation
document.querySelector('form[action*="action=add"]')?.addEventListener('submit', (e) => {
    const customerId = e.target.querySelector('select[name="customer_id"]').value;
    const amount = parseFloat(e.target.querySelector('input[name="amount"]').value);
    const type = e.target.querySelector('select[name="type"]').value;
    const date = e.target.querySelector('input[name="date"]').value;
    
    if (!customerId) {
        e.preventDefault();
        showToast('error', 'গ্রাহক নির্বাচন করুন');
        return;
    }
    
    if (!type) {
        e.preventDefault();
        showToast('error', 'বাকির ধরন নির্বাচন করুন');
        return;
    }
    
    if (amount <= 0) {
        e.preventDefault();
        showToast('error', 'পরিমাণ 0 এর বেশি হতে হবে');
        return;
    }
    
    if (!date) {
        e.preventDefault();
        showToast('error', 'তারিখ নির্বাচন করুন');
        return;
    }
    
    const dueDate = e.target.querySelector('input[name="due_date"]').value;
    if (dueDate && new Date(dueDate) < new Date(date)) {
        e.preventDefault();
        showToast('error', 'মেয়াদ তারিখ বাকি তারিখের পরে হতে হবে');
        return;
    }
});

// Auto-select customer if provided in URL
document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const customerId = urlParams.get('customer_id');
    const customerName = urlParams.get('customer_name');
    
    if (customerId && customerName) {
        showAddDebtModal();
        const customerSelect = document.querySelector('select[name="customer_id"]');
        if (customerSelect) {
            customerSelect.value = customerId;
        }
    }
});
</script>
