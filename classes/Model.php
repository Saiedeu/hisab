<?php
/**
 * Base Model Class
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

abstract class Model {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];
    protected $casts = [];
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->query($sql)->bind(':id', $id)->fetch();
    }
    
    public function all($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
            return $this->db->query($sql)
                ->bind(':limit', $limit, PDO::PARAM_INT)
                ->bind(':offset', $offset, PDO::PARAM_INT)
                ->fetchAll();
        }
        return $this->db->query($sql)->fetchAll();
    }
    
    public function where($column, $operator, $value = null) {
        if ($value === null) {
            $value = $operator;
            $operator = '=';
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE {$column} {$operator} :value";
        return $this->db->query($sql)->bind(':value', $value)->fetchAll();
    }
    
    public function create($data) {
        $data = $this->filterFillable($data);
        $data = $this->addTimestamps($data);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        
        $this->db->query($sql);
        foreach ($data as $key => $value) {
            $this->db->bind(":{$key}", $value);
        }
        
        if ($this->db->execute()) {
            return $this->db->lastInsertId();
        }
        
        return false;
    }
    
    public function update($id, $data) {
        $data = $this->filterFillable($data);
        $data = $this->addTimestamps($data, true);
        
        $setClause = [];
        foreach ($data as $key => $value) {
            $setClause[] = "{$key} = :{$key}";
        }
        
        $setClause = implode(', ', $setClause);
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
        
        $this->db->query($sql);
        foreach ($data as $key => $value) {
            $this->db->bind(":{$key}", $value);
        }
        $this->db->bind(':id', $id);
        
        return $this->db->execute();
    }
    
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        return $this->db->query($sql)->bind(':id', $id)->execute();
    }
    
    public function count() {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $result = $this->db->query($sql)->fetch();
        return $result['count'] ?? 0;
    }
    
    public function exists($id) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $result = $this->db->query($sql)->bind(':id', $id)->fetch();
        return $result['count'] > 0;
    }
    
    public function search($term, $columns = []) {
        if (empty($columns)) {
            $columns = $this->fillable;
        }
        
        $conditions = [];
        foreach ($columns as $column) {
            $conditions[] = "{$column} LIKE :{$column}";
        }
        
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $conditions);
        
        $this->db->query($sql);
        foreach ($columns as $column) {
            $this->db->bind(":{$column}", "%{$term}%");
        }
        
        return $this->db->fetchAll();
    }
    
    protected function filterFillable($data) {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    protected function addTimestamps($data, $update = false) {
        $now = date('Y-m-d H:i:s');
        
        if (!$update) {
            $data['created_at'] = $now;
        }
        $data['updated_at'] = $now;
        
        return $data;
    }
    
    protected function castAttributes($data) {
        foreach ($this->casts as $key => $type) {
            if (isset($data[$key])) {
                switch ($type) {
                    case 'int':
                    case 'integer':
                        $data[$key] = (int) $data[$key];
                        break;
                    case 'float':
                    case 'double':
                        $data[$key] = (float) $data[$key];
                        break;
                    case 'bool':
                    case 'boolean':
                        $data[$key] = (bool) $data[$key];
                        break;
                    case 'array':
                    case 'json':
                        $data[$key] = json_decode($data[$key], true);
                        break;
                }
            }
        }
        
        return $data;
    }
    
    public function hideAttributes($data) {
        foreach ($this->hidden as $attribute) {
            unset($data[$attribute]);
        }
        return $data;
    }
    
    public function paginate($page = 1, $perPage = ITEMS_PER_PAGE) {
        $offset = ($page - 1) * $perPage;
        $total = $this->count();
        $items = $this->all($perPage, $offset);
        
        return [
            'data' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage),
            'from' => $offset + 1,
            'to' => $offset + count($items)
        ];
    }
}
?>
