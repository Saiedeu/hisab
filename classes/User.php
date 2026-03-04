<?php
/**
 * User Model Class
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

// Load required classes
require_once __DIR__ . '/Database.php';
require_once __DIR__ . '/Model.php';

class User extends Model {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = [
        'name', 'email', 'password', 'google_id', 'avatar', 
        'role', 'phone', 'village', 'is_active'
    ];
    protected $hidden = ['password'];
    
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->db->query($sql)->bind(':email', $email)->fetch();
    }
    
    public function findByGoogleId($googleId) {
        $sql = "SELECT * FROM {$this->table} WHERE google_id = :google_id LIMIT 1";
        return $this->db->query($sql)->bind(':google_id', $googleId)->fetch();
    }
    
    public function createWithGoogle($data) {
        $userData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'google_id' => $data['google_id'],
            'avatar' => $data['avatar'] ?? null,
            'role' => GOOGLE_DEFAULT_ROLE,
            'is_active' => 1
        ];
        
        return $this->create($userData);
    }
    
    public function updateLastLogin($id) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
    
    public function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public function hashPassword($password) {
        return password_hash($password, HASH_ALGO);
    }
    
    public function isActive($id) {
        $sql = "SELECT is_active FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $result = $this->db->query($sql)->bind(':id', $id)->fetch();
        return $result && $result['is_active'];
    }
    
    public function getAllActive() {
        $sql = "SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY created_at DESC";
        return $this->db->query($sql)->fetchAll();
    }
    
    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_users,
                    COUNT(CASE WHEN is_active = 1 THEN 1 END) as active_users,
                    COUNT(CASE WHEN role = 'admin' THEN 1 END) as admin_users,
                    COUNT(CASE WHEN google_id IS NOT NULL THEN 1 END) as google_users,
                    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_users
                FROM {$this->table}";
        
        return $this->db->query($sql)->fetch();
    }
    
    public function searchUsers($term) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (name LIKE :term OR email LIKE :term OR phone LIKE :term OR village LIKE :term)
                AND is_active = 1
                ORDER BY name";
        
        return $this->db->query($sql)->bind(':term', "%{$term}%")->fetchAll();
    }
    
    public function updateProfile($id, $data) {
        $allowedFields = ['name', 'phone', 'village'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return false;
        }
        
        return $this->update($id, $updateData);
    }
    
    public function updatePassword($id, $newPassword) {
        $hashedPassword = $this->hashPassword($newPassword);
        $sql = "UPDATE {$this->table} SET password = :password, updated_at = NOW() WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql)
            ->bind(':password', $hashedPassword)
            ->bind(':id', $id)
            ->execute();
    }
    
    public function deactivate($id) {
        $sql = "UPDATE {$this->table} SET is_active = 0, updated_at = NOW() WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
    
    public function activate($id) {
        $sql = "UPDATE {$this->table} SET is_active = 1, updated_at = NOW() WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
    
    public function getRecentUsers($limit = 5) {
        $sql = "SELECT id, name, email, role, created_at FROM {$this->table} 
                WHERE is_active = 1 
                ORDER BY created_at DESC 
                LIMIT :limit";
        
        return $this->db->query($sql)
            ->bind(':limit', $limit, PDO::PARAM_INT)
            ->fetchAll();
    }
    
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE email = :email";
        if ($excludeId) {
            $sql .= " AND {$this->primaryKey} != :exclude_id";
        }
        
        $this->db->query($sql)->bind(':email', $email);
        if ($excludeId) {
            $this->db->bind(':exclude_id', $excludeId);
        }
        
        $result = $this->db->fetch();
        return $result['count'] > 0;
    }
}
?>
