<?php
/**
 * Payment Model Class
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load required classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class Payment extends Model {
    protected $table = 'payments';
    protected $primaryKey = 'id';
    protected $fillable = [
        'debt_id', 'customer_id', 'amount', 'payment_method', 'payment_date', 'notes', 'receipt_no', 'created_by'
    ];
    protected $casts = [
        'amount' => 'float',
        'payment_date' => 'date'
    ];
    
    public function getByDebt($debtId) {
        $sql = "SELECT p.*, u.name as created_by_name
                FROM {$this->table} p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.debt_id = :debt_id
                ORDER BY p.payment_date DESC";
        
        return $this->db->query($sql)->bind(':debt_id', $debtId)->fetchAll();
    }
    
    public function getByCustomer($customerId, $limit = null) {
        $sql = "SELECT p.*, d.type as debt_type, u.name as created_by_name
                FROM {$this->table} p
                INNER JOIN debts d ON p.debt_id = d.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.customer_id = :customer_id
                ORDER BY p.payment_date DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit";
            return $this->db->query($sql)
                ->bind(':customer_id', $customerId)
                ->bind(':limit', $limit, PDO::PARAM_INT)
                ->fetchAll();
        }
        
        return $this->db->query($sql)->bind(':customer_id', $customerId)->fetchAll();
    }
    
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT p.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       c.village as customer_village,
                       d.type as debt_type,
                       d.description as debt_description,
                       u.name as created_by_name
                FROM {$this->table} p
                INNER JOIN customers c ON p.customer_id = c.id
                INNER JOIN debts d ON p.debt_id = d.id
                LEFT JOIN users u ON p.created_by = u.id";
        
        $params = [];
        $whereClauses = [];
        
        if (!empty($filters['payment_method'])) {
            $whereClauses[] = "p.payment_method = :payment_method";
            $params[':payment_method'] = $filters['payment_method'];
        }
        
        if (!empty($filters['customer_id'])) {
            $whereClauses[] = "p.customer_id = :customer_id";
            $params[':customer_id'] = $filters['customer_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "p.payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "p.payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClauses[] = "(c.name LIKE :search OR c.phone LIKE :search OR c.village LIKE :search OR p.receipt_no LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $sql .= " ORDER BY p.payment_date DESC";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->fetchAll();
    }
    
    public function generateReceiptNo() {
        $prefix = 'HP' . date('Ym');
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE receipt_no LIKE :prefix";
        $result = $this->db->query($sql)->bind(':prefix', "{$prefix}%")->fetch();
        
        $count = ($result['count'] ?? 0) + 1;
        return $prefix . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
    
    public function findByReceiptNo($receiptNo) {
        $sql = "SELECT p.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       c.village as customer_village,
                       d.type as debt_type,
                       d.description as debt_description,
                       u.name as created_by_name
                FROM {$this->table} p
                INNER JOIN customers c ON p.customer_id = c.id
                INNER JOIN debts d ON p.debt_id = d.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.receipt_no = :receipt_no
                LIMIT 1";
        
        return $this->db->query($sql)->bind(':receipt_no', $receiptNo)->fetch();
    }
    
    public function getWithDetails($paymentId) {
        $sql = "SELECT p.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       c.village as customer_village,
                       d.type as debt_type,
                       d.description as debt_description,
                       u.name as created_by_name
                FROM {$this->table} p
                INNER JOIN customers c ON p.customer_id = c.id
                INNER JOIN debts d ON p.debt_id = d.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = :payment_id
                LIMIT 1";
        
        return $this->db->query($sql)->bind(':payment_id', $paymentId)->fetch();
    }
    
    public function createPayment($data) {
        $this->db->beginTransaction();
        
        try {
            // Generate receipt number if not provided
            if (empty($data['receipt_no'])) {
                $data['receipt_no'] = $this->generateReceiptNo();
            }
            
            // Create payment
            $paymentId = $this->create($data);
            
            if (!$paymentId) {
                throw new Exception("Failed to create payment");
            }
            
            // Update debt status
            $debt = new Debt();
            $debt->updateStatus($data['debt_id']);
            
            $this->db->commit();
            return $paymentId;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getStats($filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount,
                    MAX(amount) as max_amount,
                    MIN(amount) as min_amount,
                    COUNT(CASE WHEN payment_method = 'cash' THEN 1 END) as cash_payments,
                    COUNT(CASE WHEN payment_method = 'bank' THEN 1 END) as bank_payments,
                    COUNT(CASE WHEN payment_method = 'mobile_banking' THEN 1 END) as mobile_payments,
                    COUNT(CASE WHEN payment_method = 'check' THEN 1 END) as check_payments
                FROM {$this->table}";
        
        $params = [];
        $whereClauses = [];
        
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->fetch();
    }
    
    public function getMonthlyStats($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "SELECT 
                    MONTH(payment_date) as month,
                    SUM(amount) as total_amount,
                    COUNT(*) as payment_count,
                    AVG(amount) as avg_amount
                FROM {$this->table}
                WHERE YEAR(payment_date) = :year
                GROUP BY MONTH(payment_date)
                ORDER BY month";
        
        return $this->db->query($sql)->bind(':year', $year)->fetchAll();
    }
    
    public function getPaymentMethodStats($filters = []) {
        $sql = "SELECT 
                    payment_method,
                    COUNT(*) as count,
                    SUM(amount) as total_amount,
                    AVG(amount) as avg_amount
                FROM {$this->table}";
        
        $params = [];
        $whereClauses = [];
        
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $sql .= " GROUP BY payment_method ORDER BY total_amount DESC";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->fetchAll();
    }
    
    public function getRecentPayments($limit = 5) {
        $sql = "SELECT p.*, c.name as customer_name, c.phone as customer_phone
                FROM {$this->table} p
                INNER JOIN customers c ON p.customer_id = c.id
                ORDER BY p.created_at DESC
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
    
    public function getTodayPayments() {
        $sql = "SELECT p.*, c.name as customer_name, c.phone as customer_phone
                FROM {$this->table} p
                INNER JOIN customers c ON p.customer_id = c.id
                WHERE DATE(p.payment_date) = CURDATE()
                ORDER BY p.payment_date DESC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getTodayTotal() {
        $sql = "SELECT COUNT(*) as count, SUM(amount) as total
                FROM {$this->table}
                WHERE DATE(payment_date) = CURDATE()";
        
        return $this->db->query($sql)->fetch();
    }
    
    public function receiptExists($receiptNo, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE receipt_no = :receipt_no";
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
        }
        
        $this->db->query($sql)->bind(':receipt_no', $receiptNo);
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        
        $result = $this->db->fetch();
        return $result['count'] > 0;
    }
    
    public function deletePayment($paymentId) {
        $this->db->beginTransaction();
        
        try {
            // Get payment info before deletion
            $payment = $this->find($paymentId);
            if (!$payment) {
                throw new Exception("Payment not found");
            }
            
            // Delete payment
            $result = $this->delete($paymentId);
            
            if (!$result) {
                throw new Exception("Failed to delete payment");
            }
            
            // Update debt status
            $debt = new Debt();
            $debt->updateStatus($payment['debt_id']);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getCustomerPaymentHistory($customerId, $limit = 20) {
        $sql = "SELECT p.*, d.type as debt_type, d.description as debt_description
                FROM {$this->table} p
                INNER JOIN debts d ON p.debt_id = d.id
                WHERE p.customer_id = :customer_id
                ORDER BY p.payment_date DESC
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':customer_id', $customerId)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
}
?>
