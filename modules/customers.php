<?php
/**
 * Customers Management Module
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load configuration and authentication
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/header.php';

// Set page variables
$pageTitle = 'গ্রাহক ব্যবস্থাপনা - হিসাব পত্র';
$pageHeader = 'গ্রাহক ব্যবস্থাপনা';
$pageSubHeader = 'গ্রাহকদের তথ্য এবং হিসাব ব্যবস্থাপনা';

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
    handleAddCustomer();
}

if ($action === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleEditCustomer();
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    handleDeleteCustomer();
}

// Get parameters
$page = (int)($_GET['page'] ?? 1);
$search = sanitize($_GET['search'] ?? '');
$village = sanitize($_GET['village'] ?? '');
$view = $_GET['view'] ?? 'list';

// Get data
try {
    if (!empty($search) || !empty($village)) {
        $filters = [];
        if (!empty($village)) {
            $filters['village'] = $village;
        }
        $customers = $customerModel->searchCustomers($search, $filters);
        $pagination = null;
    } else {
        $offset = ($page - 1) * ITEMS_PER_PAGE;
        $customers = $customerModel->getAllWithBalance(ITEMS_PER_PAGE, $offset);
        $total = $customerModel->count();
        $pagination = paginate($total, ITEMS_PER_PAGE, $page, 'customers.php?page={page}');
    }
    
    $villages = $customerModel->getVillages();
    $stats = $customerModel->getStats();
    
} catch (Exception $e) {
    error_log("Customers module error: " . $e->getMessage());
    $customers = [];
    $villages = [];
    $stats = ['total_customers' => 0];
    $pagination = null;
}

/**
 * Handle add customer
 */
function handleAddCustomer() {
    global $customerModel, $currentUser;
    
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $village = sanitize($_POST['village'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    // Validation
    if (empty($name)) {
        redirect('modules/customers.php', 'গ্রাহকের নাম প্রয়োজন', 'error');
    }
    
    if (strlen($name) < 3) {
        redirect('modules/customers.php', 'নাম কমপক্ষে ৩ অক্ষরের হতে হবে', 'error');
    }
    
    if (!empty($email) && !validateEmail($email)) {
        redirect('modules/customers.php', 'সঠিক ইমেল ঠিকানা দিন', 'error');
    }
    
    if (!empty($phone) && !validatePhone($phone)) {
        redirect('modules/customers.php', 'সঠিক ফোন নম্বর দিন', 'error');
    }
    
    // Check if phone already exists
    if (!empty($phone) && $customerModel->phoneExists($phone)) {
        redirect('modules/customers.php', 'এই ফোন নম্বরে ইতিমধ্যে গ্রাহক আছে', 'error');
    }
    
    // Create customer
    $customerData = [
        'name' => $name,
        'phone' => $phone,
        'village' => $village,
        'address' => $address,
        'email' => $email,
        'created_by' => $currentUser['id']
    ];
    
    $customerId = $customerModel->create($customerData);
    
    if ($customerId) {
        logActivity($currentUser['id'], 'create', 'customers', $customerId, null, $customerData);
        redirect('modules/customers.php', 'গ্রাহক সফলভাবে যোগ করা হয়েছে', 'success');
    } else {
        redirect('modules/customers.php', 'গ্রাহক যোগ করতে ব্যর্থ হয়েছে', 'error');
    }
}

/**
 * Handle edit customer
 */
function handleEditCustomer() {
    global $customerModel, $currentUser;
    
    $customerId = (int)($_POST['id'] ?? 0);
    $name = sanitize($_POST['name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $village = sanitize($_POST['village'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    
    if ($customerId <= 0) {
        redirect('modules/customers.php', 'অবৈধ গ্রাহক ID', 'error');
    }
    
    // Get existing customer
    $existingCustomer = $customerModel->find($customerId);
    if (!$existingCustomer) {
        redirect('modules/customers.php', 'গ্রাহক পাওয়া যায়নি', 'error');
    }
    
    // Check permissions
    if (!canAccessResource($existingCustomer['created_by'])) {
        redirect('modules/customers.php', 'আপনার এই গ্রাহক সম্পাদনা করার অনুমতি নেই', 'error');
    }
    
    // Validation
    if (empty($name)) {
        redirect('modules/customers.php', 'গ্রাহকের নাম প্রয়োজন', 'error');
    }
    
    // Update customer
    $updateData = [
        'name' => $name,
        'phone' => $phone,
        'village' => $village,
        'address' => $address,
        'email' => $email
    ];
    
    if ($customerModel->update($customerId, $updateData)) {
        logActivity($currentUser['id'], 'update', 'customers', $customerId, $existingCustomer, $updateData);
        redirect('modules/customers.php', 'গ্রাহক সফলভাবে আপডেট করা হয়েছে', 'success');
    } else {
        redirect('modules/customers.php', 'গ্রাহক আপডেট করতে ব্যর্থ হয়েছে', 'error');
    }
}

/**
 * Handle delete customer
 */
function handleDeleteCustomer() {
    global $customerModel, $currentUser;
    
    $customerId = (int)($_POST['id'] ?? 0);
    
    if ($customerId <= 0) {
        redirect('modules/customers.php', 'অবৈধ গ্রাহক ID', 'error');
    }
    
    // Get customer
    $customer = $customerModel->find($customerId);
    if (!$customer) {
        redirect('modules/customers.php', 'গ্রাহক পাওয়া যায়নি', 'error');
    }
    
    // Check permissions
    if (!canAccessResource($customer['created_by'])) {
        redirect('modules/customers.php', 'আপনার এই গ্রাহক ডিলিট করার অনুমতি নেই', 'error');
    }
    
    // Check if customer has debts
    $debts = $customerModel->getByCustomer($customerId);
    if (!empty($debts)) {
        redirect('modules/customers.php', 'গ্রাহকের বাকি আছে, ডিলিট করা যাবে না', 'error');
    }
    
    // Delete customer
    if ($customerModel->delete($customerId)) {
        logActivity($currentUser['id'], 'delete', 'customers', $customerId, $customer, null);
        redirect('modules/customers.php', 'গ্রাহক সফলভাবে ডিলিট করা হয়েছে', 'success');
    } else {
        redirect('modules/customers.php', 'গ্রাহক ডিলিট করতে ব্যর্থ হয়েছে', 'error');
    }
}
?>

<!-- Search and Filter Section -->
<div class="bg-white rounded-xl shadow-sm p-6 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div class="flex-1">
            <form method="GET" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1">
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                           placeholder="গ্রাহকের নাম, ফোন বা গ্রাম দিয়ে খুঁজুন..." 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                </div>
                <div class="sm:w-48">
                    <select name="village" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        <option value="">সব গ্রাম</option>
                        <?php foreach ($villages as $villageItem): ?>
                        <option value="<?php echo htmlspecialchars($villageItem['village']); ?>" 
                                <?php echo $village === $villageItem['village'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($villageItem['village']); ?> (<?php echo $villageItem['customer_count']; ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition duration-200">
                    <i class="fas fa-search mr-2"></i>খুঁজুন
                </button>
                <?php if (!empty($search) || !empty($village)): ?>
                <a href="modules/customers.php" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                    <i class="fas fa-times mr-2"></i>ক্লিয়ার
                </a>
                <?php endif; ?>
            </form>
        </div>
        <div class="flex gap-3">
            <button onclick="showAddCustomerModal()" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition duration-200">
                <i class="fas fa-plus mr-2"></i>নতুন গ্রাহক
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">মোট গ্রাহক</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['total_customers'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-users text-blue-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">ফোন আছে</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['customers_with_phone'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-phone text-green-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">গ্রাম আছে</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['customers_with_village'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-map-marker-alt text-purple-600 text-xl"></i>
            </div>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-gray-600">আজকের গ্রাহক</p>
                <p class="text-2xl font-bold text-gray-900 mt-2"><?php echo number_format($stats['today_customers'] ?? 0); ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                <i class="fas fa-calendar-day text-orange-600 text-xl"></i>
            </div>
        </div>
    </div>
</div>

<!-- Customers Table -->
<div class="bg-white rounded-xl shadow-sm overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">
            গ্রাহকের তালিকা 
            <?php if (!empty($search) || !empty($village)): ?>
            <span class="text-sm font-normal text-gray-500">(অনুসন্ধান: <?php echo htmlspecialchars($search ?: $village); ?>)</span>
            <?php endif; ?>
        </h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">গ্রাহক</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">যোগাযোগ</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">গ্রাম</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">বাকি হিসাব</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">নেট ব্যালেন্স</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">কার্যক্রম</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($customers)): ?>
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-users text-4xl mb-4 text-gray-300"></i>
                        <p class="text-lg">কোনো গ্রাহক পাওয়া যায়নি</p>
                        <p class="text-sm mt-2">নতুন গ্রাহক যোগ করতে "নতুন গ্রাহক" বাটনে ক্লিক করুন</p>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($customers as $customer): ?>
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                                <i class="fas fa-user text-indigo-600"></i>
                            </div>
                            <div>
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($customer['name']); ?></div>
                                <div class="text-xs text-gray-500">ID: #<?php echo $customer['id']; ?></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php if (!empty($customer['phone'])): ?>
                            <div class="flex items-center">
                                <i class="fas fa-phone text-gray-400 mr-2"></i>
                                <?php echo formatPhone($customer['phone']); ?>
                            </div>
                            <?php endif; ?>
                            <?php if (!empty($customer['email'])): ?>
                            <div class="flex items-center mt-1">
                                <i class="fas fa-envelope text-gray-400 mr-2"></i>
                                <span class="text-xs"><?php echo htmlspecialchars($customer['email']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm text-gray-900">
                            <?php if (!empty($customer['village'])): ?>
                            <div class="flex items-center">
                                <i class="fas fa-map-marker-alt text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($customer['village']); ?>
                            </div>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm">
                            <div class="text-green-600">
                                <i class="fas fa-arrow-up mr-1"></i>
                                <?php echo formatCurrency($customer['total_given'] ?? 0); ?>
                            </div>
                            <div class="text-blue-600">
                                <i class="fas fa-arrow-down mr-1"></i>
                                <?php echo formatCurrency($customer['total_taken'] ?? 0); ?>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium <?php echo ($customer['net_balance'] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo formatCurrency(abs($customer['net_balance'] ?? 0)); ?>
                            <span class="text-xs text-gray-500 ml-1">
                                <?php echo ($customer['net_balance'] ?? 0) >= 0 ? 'পাওনা' : 'দেনা'; ?>
                            </span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <a href="modules/customer_details.php?id=<?php echo $customer['id']; ?>" 
                               class="text-indigo-600 hover:text-indigo-900" title="বিস্তারিত">
                                <i class="fas fa-eye"></i>
                            </a>
                            <button onclick="editCustomer(<?php echo $customer['id']; ?>)" 
                                    class="text-blue-600 hover:text-blue-900" title="সম্পাদনা">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="addDebt(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['name']); ?>')" 
                                    class="text-orange-600 hover:text-orange-900" title="বাকি যোগ করুন">
                                <i class="fas fa-plus-circle"></i>
                            </button>
                            <button onclick="addPayment(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['name']); ?>')" 
                                    class="text-green-600 hover:text-green-900" title="পেমেন্ট">
                                <i class="fas fa-money-bill"></i>
                            </button>
                            <?php if (canAccessResource($customer['created_by'])): ?>
                            <button onclick="deleteCustomer(<?php echo $customer['id']; ?>, '<?php echo htmlspecialchars($customer['name']); ?>')" 
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

<!-- Add Customer Modal -->
<div id="addCustomerModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md">
        <div class="p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">নতুন গ্রাহক যোগ করুন</h3>
        </div>
        <form method="POST" action="modules/customers.php?action=add" class="p-6">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">গ্রাহকের নাম *</label>
                    <input type="text" name="name" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="গ্রাহকের পূর্ণ নাম">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ফোন নম্বর</label>
                    <input type="tel" name="phone"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="০১XXXXXXXXX">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">গ্রাম/এলাকা</label>
                    <input type="text" name="village"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="গ্রামের নাম">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ঠিকানা</label>
                    <textarea name="address" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                              placeholder="সম্পূর্ণ ঠিকানা"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">ইমেল</label>
                    <input type="email" name="email"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                           placeholder="ইমেল ঠিকানা">
                </div>
            </div>
            
            <div class="flex justify-end space-x-3 mt-6">
                <button type="button" onclick="hideAddCustomerModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200">
                    বাতিল
                </button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                    গ্রাহক যোগ করুন
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<script>
// Modal functions
function showAddCustomerModal() {
    document.getElementById('addCustomerModal').classList.remove('hidden');
}

function hideAddCustomerModal() {
    document.getElementById('addCustomerModal').classList.add('hidden');
}

function editCustomer(id) {
    // Load customer data and show edit modal
    window.location.href = `modules/customers.php?action=edit&id=${id}`;
}

function deleteCustomer(id, name) {
    showConfirm(
        'গ্রাহক ডিলিট করুন',
        `আপনি কি "${name}" কে ডিলিট করতে চান? এই কাজটি অপরিবর্তনীয়।`,
        () => {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'modules/customers.php?action=delete';
            
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

function addDebt(customerId, customerName) {
    window.location.href = `modules/debts.php?action=add&customer_id=${customerId}&customer_name=${encodeURIComponent(customerName)}`;
}

function addPayment(customerId, customerName) {
    window.location.href = `modules/payments.php?action=add&customer_id=${customerId}&customer_name=${encodeURIComponent(customerName)}`;
}

// Close modal on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        hideAddCustomerModal();
    }
});

// Close modal on background click
document.getElementById('addCustomerModal')?.addEventListener('click', (e) => {
    if (e.target.id === 'addCustomerModal') {
        hideAddCustomerModal();
    }
});

// Form validation
document.querySelector('form[action*="action=add"]')?.addEventListener('submit', (e) => {
    const name = e.target.querySelector('input[name="name"]').value;
    const phone = e.target.querySelector('input[name="phone"]').value;
    const email = e.target.querySelector('input[name="email"]').value;
    
    if (name.length < 3) {
        e.preventDefault();
        showToast('error', 'নাম কমপক্ষে ৩ অক্ষরের হতে হবে');
        return;
    }
    
    if (phone && !/^01[3-9]\d{8}$/.test(phone)) {
        e.preventDefault();
        showToast('error', 'সঠিক ফোন নম্বর দিন (১১ ডিজিট)');
        return;
    }
    
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        e.preventDefault();
        showToast('error', 'সঠিক ইমেল ঠিকানা দিন');
        return;
    }
});

// Phone number formatting
document.querySelector('input[name="phone"]')?.addEventListener('input', (e) => {
    let value = e.target.value.replace(/\D/g, '');
    if (value.length > 11) {
        value = value.slice(0, 11);
    }
    e.target.value = value;
});
</script>
