<?php
/**
 * Customer Model Class
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load required classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class Customer extends Model {
    protected $table = 'customers';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'phone', 'village', 'address', 'email', 'created_by'
    ];
    
    public function searchByName($name) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE name LIKE :name 
                ORDER BY name 
                LIMIT 20";
        
        return $this->db->query($sql)->bind(':name', "%{$name}%")->fetchAll();
    }
    
    public function searchByPhone($phone) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE phone LIKE :phone 
                ORDER BY name 
                LIMIT 20";
        
        return $this->db->query($sql)->bind(':phone', "%{$phone}%")->fetchAll();
    }
    
    public function searchByVillage($village) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE village LIKE :village 
                ORDER BY village, name 
                LIMIT 50";
        
        return $this->db->query($sql)->bind(':village', "%{$village}%")->fetchAll();
    }
    
    public function getWithDebts($customerId) {
        $sql = "SELECT c.*, 
                       COUNT(d.id) as total_debts,
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE 0 END) as total_given,
                       SUM(CASE WHEN d.type = 'taken' THEN d.amount ELSE 0 END) as total_taken,
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE -d.amount END) as net_balance
                FROM {$this->table} c
                LEFT JOIN debts d ON c.id = d.customer_id
                WHERE c.id = :id
                GROUP BY c.id";
        
        return $this->db->query($sql)->bind(':id', $customerId)->fetch();
    }
    
    public function getAllWithBalance($limit = null, $offset = 0) {
        $sql = "SELECT c.*, 
                       COUNT(d.id) as total_debts,
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE 0 END) as total_given,
                       SUM(CASE WHEN d.type = 'taken' THEN d.amount ELSE 0 END) as total_taken,
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE -d.amount END) as net_balance
                FROM {$this->table} c
                LEFT JOIN debts d ON c.id = d.customer_id
                GROUP BY c.id
                ORDER BY c.name";
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return $this->db->query($sql)
                ->bind(':limit', $limit, PDO::PARAM_INT)
                ->bind(':offset', $offset, PDO::PARAM_INT)
                ->fetchAll();
        }
        
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getDebtors($type = 'given') {
        $sql = "SELECT c.*, 
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE 0 END) as total_given,
                       SUM(CASE WHEN d.type = 'taken' THEN d.amount ELSE 0 END) as total_taken,
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE -d.amount END) as net_balance
                FROM {$this->table} c
                INNER JOIN debts d ON c.id = d.customer_id
                GROUP BY c.id
                HAVING ";
        
        if ($type === 'given') {
            $sql .= "total_given > total_taken";
        } else {
            $sql .= "total_taken > total_given";
        }
        
        $sql .= " ORDER BY net_balance DESC";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getTopDebtors($limit = 10) {
        $sql = "SELECT c.*, 
                       SUM(CASE WHEN d.type = 'given' THEN d.amount ELSE -d.amount END) as net_balance
                FROM {$this->table} c
                INNER JOIN debts d ON c.id = d.customer_id
                GROUP BY c.id
                HAVING net_balance > 0
                ORDER BY net_balance DESC
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
    
    public function getVillages() {
        $sql = "SELECT DISTINCT village, COUNT(*) as customer_count 
                FROM {$this->table} 
                WHERE village IS NOT NULL AND village != ''
                GROUP BY village 
                ORDER BY village";
        
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN phone IS NOT NULL AND phone != '' THEN 1 END) as customers_with_phone,
                    COUNT(CASE WHEN village IS NOT NULL AND village != '' THEN 1 END) as customers_with_village,
                    COUNT(CASE WHEN email IS NOT NULL AND email != '' THEN 1 END) as customers_with_email,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_customers
                FROM {$this->table}";
        
        return $this->db->query($sql)->fetch();
    }
    
    public function getRecentCustomers($limit = 5) {
        $sql = "SELECT * FROM {$this->table} 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
    
    public function phoneExists($phone, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE phone = :phone";
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
        }
        
        $this->db->query($sql)->bind(':phone', $phone);
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        
        $result = $this->db->fetch();
        return $result['count'] > 0;
    }
    
    public function searchCustomers($term, $filters = []) {
        $sql = "SELECT * FROM {$this->table} WHERE (name LIKE :term OR phone LIKE :term OR village LIKE :term)";
        $params = [':term' => "%{$term}%"];
        
        if (!empty($filters['village'])) {
            $sql .= " AND village = :village";
            $params[':village'] = $filters['village'];
        }
        
        if (!empty($filters['phone'])) {
            $sql .= " AND phone LIKE :phone_filter";
            $params[':phone_filter'] = "%{$filters['phone']}%";
        }
        
        $sql .= " ORDER BY name LIMIT 50";
        
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        
        return $this->db->fetchAll();
    }
    
    public function getCustomersByCreator($userId) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE created_by = :user_id 
                ORDER BY created_at DESC";
        
        return $this->db->query($sql)->bind(':user_id', $userId)->fetchAll();
    }
}
?>
