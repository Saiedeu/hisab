<?php
/**
 * Debt Model Class
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load required classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class Debt extends Model {
    protected $table = 'debts';
    protected $primaryKey = 'id';
    protected $fillable = [
        'customer_id', 'amount', 'type', 'description', 'date', 'due_date', 'status', 'created_by'
    ];
    protected $casts = [
        'amount' => 'float',
        'date' => 'date',
        'due_date' => 'date'
    ];
    
    public function getByCustomer($customerId, $type = null) {
        $sql = "SELECT d.*, c.name as customer_name, c.phone as customer_phone 
                FROM {$this->table} d
                INNER JOIN customers c ON d.customer_id = c.id
                WHERE d.customer_id = :customer_id";
        
        if ($type) {
            $sql .= " AND d.type = :type";
        }
        
        $sql .= " ORDER BY d.date DESC";
        
        $this->db->query($sql)->bind(':customer_id', $customerId);
        if ($type) {
            $this->db->bind(':type', $type);
        }
        
        return $this->db->fetchAll();
    }
    
    public function getWithPayments($debtId) {
        $sql = "SELECT d.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       SUM(p.amount) as total_paid,
                       (d.amount - COALESCE(SUM(p.amount), 0)) as remaining_amount
                FROM {$this->table} d
                INNER JOIN customers c ON d.customer_id = c.id
                LEFT JOIN payments p ON d.id = p.debt_id
                WHERE d.id = :id
                GROUP BY d.id";
        
        return $this->db->query($sql)->bind(':id', $debtId)->fetch();
    }
    
    public function getAllWithDetails($filters = []) {
        $sql = "SELECT d.*, 
                       c.name as customer_name, 
                       c.phone as customer_phone,
                       c.village as customer_village,
                       SUM(p.amount) as total_paid,
                       (d.amount - COALESCE(SUM(p.amount), 0)) as remaining_amount
                FROM {$this->table} d
                INNER JOIN customers c ON d.customer_id = c.id
                LEFT JOIN payments p ON d.id = p.debt_id";
        
        $params = [];
        $whereClauses = [];
        
        if (!empty($filters['type'])) {
            $whereClauses[] = "d.type = :type";
            $params[':type'] = $filters['type'];
        }
        
        if (!empty($filters['status'])) {
            $whereClauses[] = "d.status = :status";
            $params[':status'] = $filters['status'];
        }
        
        if (!empty($filters['customer_id'])) {
            $whereClauses[] = "d.customer_id = :customer_id";
            $params[':customer_id'] = $filters['customer_id'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "d.date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "d.date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClauses[] = "(c.name LIKE :search OR c.phone LIKE :search OR c.village LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        if (!empty($whereClauses)) {
            $sql .= " WHERE " . implode(' AND ', $whereClauses);
        }
        
        $sql .= " GROUP BY d.id ORDER BY d.date DESC";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->fetchAll();
    }
    
    public function getOverdueDebts() {
        $sql = "SELECT d.*, c.name as customer_name, c.phone as customer_phone
                FROM {$this->table} d
                INNER JOIN customers c ON d.customer_id = c.id
                WHERE d.due_date < CURDATE() 
                AND d.status != 'paid'
                AND d.type = 'given'
                ORDER BY d.due_date ASC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    public function updateStatus($debtId) {
        $sql = "SELECT d.amount, SUM(p.amount) as paid_amount 
                FROM {$this->table} d
                LEFT JOIN payments p ON d.id = p.debt_id
                WHERE d.id = :id
                GROUP BY d.id";
        
        $result = $this->db->query($sql)->bind(':id', $debtId)->fetch();
        
        if (!$result) {
            return false;
        }
        
        $paidAmount = $result['paid_amount'] ?? 0;
        $status = 'pending';
        
        if ($paidAmount >= $result['amount']) {
            $status = 'paid';
        } elseif ($paidAmount > 0) {
            $status = 'partial';
        }
        
        $updateSql = "UPDATE {$this->table} SET status = :status WHERE id = :id";
        return $this->db->query($updateSql)
            ->bind(':status', $status)
            ->bind(':id', $debtId)
            ->execute();
    }
    
    public function getStats($filters = []) {
        $sql = "SELECT 
                    COUNT(*) as total_debts,
                    SUM(CASE WHEN type = 'given' THEN amount ELSE 0 END) as total_given,
                    SUM(CASE WHEN type = 'taken' THEN amount ELSE 0 END) as total_taken,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN status = 'partial' THEN amount ELSE 0 END) as partial_amount,
                    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                    COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
                FROM {$this->table}";
        
        $params = [];
        $whereClauses = [];
        
        if (!empty($filters['date_from'])) {
            $whereClauses[] = "date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClauses[] = "date <= :date_to";
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
                    MONTH(date) as month,
                    SUM(CASE WHEN type = 'given' THEN amount ELSE 0 END) as given_amount,
                    SUM(CASE WHEN type = 'taken' THEN amount ELSE 0 END) as taken_amount,
                    COUNT(*) as total_debts
                FROM {$this->table}
                WHERE YEAR(date) = :year
                GROUP BY MONTH(date)
                ORDER BY month";
        
        return $this->db->query($sql)->bind(':year', $year)->fetchAll();
    }
    
    public function getTopCustomers($type = 'given', $limit = 10) {
        $sql = "SELECT c.*, 
                       SUM(d.amount) as total_amount,
                       COUNT(d.id) as debt_count
                FROM customers c
                INNER JOIN {$this->table} d ON c.id = d.customer_id
                WHERE d.type = :type
                GROUP BY c.id
                ORDER BY total_amount DESC
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':type', $type)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
    
    public function getRecentDebts($limit = 5) {
        $sql = "SELECT d.*, c.name as customer_name, c.phone as customer_phone
                FROM {$this->table} d
                INNER JOIN customers c ON d.customer_id = c.id
                ORDER BY d.created_at DESC
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
    
    public function deleteWithPayments($debtId) {
        $this->db->beginTransaction();
        
        try {
            // Delete payments first
            $deletePaymentsSql = "DELETE FROM payments WHERE debt_id = :debt_id";
            $this->db->query($deletePaymentsSql)->bind(':debt_id', $debtId)->execute();
            
            // Delete debt
            $deleteDebtSql = "DELETE FROM {$this->table} WHERE id = :id";
            $this->db->query($deleteDebtSql)->bind(':id', $debtId)->execute();
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getCustomerBalance($customerId) {
        $sql = "SELECT 
                    SUM(CASE WHEN type = 'given' THEN amount ELSE 0 END) as total_given,
                    SUM(CASE WHEN type = 'taken' THEN amount ELSE 0 END) as total_taken,
                    (SUM(CASE WHEN type = 'given' THEN amount ELSE 0 END) - 
                     SUM(CASE WHEN type = 'taken' THEN amount ELSE 0 END)) as net_balance
                FROM {$this->table}
                WHERE customer_id = :customer_id";
        
        return $this->db->query($sql)->bind(':customer_id', $customerId)->fetch();
    }
}
?>
