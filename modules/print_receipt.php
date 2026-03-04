<?php
/**
 * Print Receipt Module
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load configuration and authentication
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

// Require authentication
requireAuth();

// Load models
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/Debt.php';
require_once __DIR__ . '/../classes/Customer.php';

// Initialize models
$paymentModel = new Payment();
$debtModel = new Debt();
$customerModel = new Customer();

// Get receipt number
$receiptNo = sanitize($_GET['receipt_no'] ?? '');
$paymentId = (int)($_GET['id'] ?? 0);

if (empty($receiptNo) && $paymentId <= 0) {
    redirect('modules/payments.php', 'রশিদ নম্বর বা পেমেন্ট ID প্রয়োজন', 'error');
}

try {
    // Get payment details
    if (!empty($receiptNo)) {
        $payment = $paymentModel->findByReceiptNo($receiptNo);
    } else {
        $payment = $paymentModel->getWithDetails($paymentId);
    }
    
    if (!$payment) {
        redirect('modules/payments.php', 'পেমেন্ট পাওয়া যায়নি', 'error');
    }
    
    // Get debt details
    $debt = $debtModel->getWithPayments($payment['debt_id']);
    
    // Get customer details
    $customer = $customerModel->find($payment['customer_id']);
    
    // Get shop settings (you can create a ShopSettings model for this)
    $shopSettings = [
        'name' => 'হিসাব পত্র',
        'address' => 'ঢাকা, বাংলাদেশ',
        'phone' => '০১XXXXXXXXX',
        'email' => 'info@hisabpotro.com'
    ];
    
} catch (Exception $e) {
    error_log("Print receipt error: " . $e->getMessage());
    redirect('modules/payments.php', 'রশিদ তৈরিতে সমস্যা হয়েছে', 'error');
}

// Calculate totals
$totalDebt = $debt['amount'];
$totalPaid = $debt['total_paid'] ?? 0;
$remainingAmount = $totalDebt - $totalPaid;
$paymentAmount = $payment['amount'];
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>রশিদ - <?php echo htmlspecialchars($payment['receipt_no']); ?></title>
    
    <!-- Hind Siliguri Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Hind Siliguri', sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        
        .receipt-container {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .receipt-header {
            text-align: center;
            border-bottom: 2px dashed #ccc;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }
        
        .shop-name {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
            color: #333;
        }
        
        .shop-address {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        
        .receipt-title {
            font-size: 18px;
            font-weight: bold;
            margin: 15px 0 10px 0;
            color: #333;
        }
        
        .receipt-no {
            font-size: 14px;
            color: #666;
            margin: 5px 0;
        }
        
        .receipt-body {
            margin-bottom: 20px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }
        
        .info-label {
            color: #666;
        }
        
        .info-value {
            font-weight: 500;
            text-align: right;
        }
        
        .payment-details {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        
        .payment-amount {
            font-size: 20px;
            font-weight: bold;
            color: #27ae60;
            text-align: center;
            margin: 10px 0;
        }
        
        .payment-method {
            text-align: center;
            font-size: 14px;
            color: #666;
        }
        
        .account-summary {
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
            margin: 15px 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 14px;
        }
        
        .remaining-amount {
            font-size: 16px;
            font-weight: bold;
            color: <?php echo $remainingAmount > 0 ? '#e74c3c' : '#27ae60'; ?>;
        }
        
        .receipt-footer {
            text-align: center;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px dashed #ccc;
        }
        
        .thank-you {
            font-size: 16px;
            font-weight: bold;
            margin: 10px 0;
            color: #333;
        }
        
        .footer-note {
            font-size: 12px;
            color: #666;
            margin: 5px 0;
        }
        
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .signature-box {
            width: 45%;
            text-align: center;
        }
        
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 30px;
            margin-bottom: 5px;
        }
        
        .signature-label {
            font-size: 12px;
            color: #666;
        }
        
        .print-button {
            display: block;
            width: 100%;
            padding: 12px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
        }
        
        .print-button:hover {
            background: #2980b9;
        }
        
        @media print {
            body {
                background: white;
                padding: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                border-radius: 0;
                max-width: 100%;
            }
            
            .print-button {
                display: none;
            }
        }
        
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 72px;
            opacity: 0.1;
            color: #333;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="receipt-container">
        <div class="watermark">PAID</div>
        
        <!-- Receipt Header -->
        <div class="receipt-header">
            <h1 class="shop-name"><?php echo htmlspecialchars($shopSettings['name']); ?></h1>
            <p class="shop-address"><?php echo htmlspecialchars($shopSettings['address']); ?></p>
            <p class="shop-address">ফোন: <?php echo htmlspecialchars($shopSettings['phone']); ?></p>
            <p class="shop-address">ইমেল: <?php echo htmlspecialchars($shopSettings['email']); ?></p>
            
            <h2 class="receipt-title">পেমেন্ট রশিদ</h2>
            <p class="receipt-no">রশিদ নম্বর: <?php echo htmlspecialchars($payment['receipt_no']); ?></p>
            <p class="receipt-no">তারিখ: <?php echo formatDate($payment['payment_date']); ?></p>
            <p class="receipt-no">সময়: <?php echo formatTime($payment['payment_date']); ?></p>
        </div>
        
        <!-- Customer Information -->
        <div class="receipt-body">
            <div class="info-row">
                <span class="info-label">গ্রাহকের নাম:</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['name']); ?></span>
            </div>
            
            <?php if (!empty($customer['phone'])): ?>
            <div class="info-row">
                <span class="info-label">ফোন নম্বর:</span>
                <span class="info-value"><?php echo formatPhone($customer['phone']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($customer['village'])): ?>
            <div class="info-row">
                <span class="info-label">গ্রাম:</span>
                <span class="info-value"><?php echo htmlspecialchars($customer['village']); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($debt['description'])): ?>
            <div class="info-row">
                <span class="info-label">বাকির বিবরণ:</span>
                <span class="info-value"><?php echo htmlspecialchars($debt['description']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Payment Details -->
        <div class="payment-details">
            <div class="payment-amount">
                <?php echo formatCurrency($payment['amount']); ?>
            </div>
            <div class="payment-method">
                পেমেন্ট মেথড: <?php 
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
                        echo htmlspecialchars($payment['payment_method']);
                }
                ?>
            </div>
        </div>
        
        <!-- Account Summary -->
        <div class="account-summary">
            <div class="summary-row">
                <span>মোট বাকি:</span>
                <span><?php echo formatCurrency($totalDebt); ?></span>
            </div>
            <div class="summary-row">
                <span>আগের পেমেন্ট:</span>
                <span><?php echo formatCurrency($totalPaid - $payment['amount']); ?></span>
            </div>
            <div class="summary-row">
                <span>বর্তমান পেমেন্ট:</span>
                <span><?php echo formatCurrency($payment['amount']); ?></span>
            </div>
            <div class="summary-row">
                <span>সর্বমোট পেমেন্ট:</span>
                <span><?php echo formatCurrency($totalPaid); ?></span>
            </div>
            <div class="summary-row remaining-amount">
                <span>অবশিষ্ট বাকি:</span>
                <span><?php echo formatCurrency($remainingAmount); ?></span>
            </div>
        </div>
        
        <?php if (!empty($payment['notes'])): ?>
        <div class="receipt-body">
            <div class="info-row">
                <span class="info-label">নোট:</span>
                <span class="info-value"><?php echo htmlspecialchars($payment['notes']); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Receipt Footer -->
        <div class="receipt-footer">
            <p class="thank-you">ধন্যবাদ!</p>
            <p class="footer-note">আপনার পেমেন্ট সফলভাবে গ্রহণ করা হয়েছে</p>
            <p class="footer-note">এই রশিদটি সংরক্ষণ করুন</p>
            
            <!-- Signature Section -->
            <div class="signature-section">
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">গ্রাহকের স্বাক্ষর</div>
                </div>
                <div class="signature-box">
                    <div class="signature-line"></div>
                    <div class="signature-label">প্রতিষ্ঠানের স্বাক্ষর</div>
                </div>
            </div>
        </div>
        
        <!-- Print Button -->
        <button class="print-button" onclick="window.print()">
            <i class="fas fa-print"></i> রশিদ প্রিন্ট করুন
        </button>
    </div>
    
    <script>
        // Auto print when page loads (optional)
        // window.onload = function() {
        //     setTimeout(() => {
        //         window.print();
        //     }, 500);
        // };
        
        // Close window after printing
        window.onafterprint = function() {
            setTimeout(() => {
                window.close();
            }, 1000);
        };
    </script>
</body>
</html>
