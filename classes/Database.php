<?php
/**
 * Database Singleton Class
 * @Project: Hisab Potro
 * @author: Saieed Rahman
 * @copyright: SidMan Solution 2026
 */

class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    private $queryCount = 0;
    
    private function __construct() {
        $this->connect();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function connect() {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => true,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
            PDO::ATTR_TIMEOUT => DB_TIMEOUT
        ];
        
        $retries = 0;
        while ($retries < DB_MAX_RETRIES) {
            try {
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
                return;
            } catch (PDOException $e) {
                $retries++;
                if ($retries >= DB_MAX_RETRIES) {
                    throw new Exception("Database connection failed after " . DB_MAX_RETRIES . " attempts: " . $e->getMessage());
                }
                sleep(DB_RETRY_DELAY);
            }
        }
    }
    
    public function query($sql) {
        $this->statement = $this->connection->prepare($sql);
        return $this;
    }
    
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        
        $this->statement->bindValue($param, $value, $type);
        return $this;
    }
    
    public function execute() {
        $start = microtime(true);
        $result = $this->statement->execute();
        $executionTime = microtime(true) - $start;
        
        $this->queryCount++;
        
        if (DB_LOG_QUERIES && $executionTime > DB_SLOW_QUERY_THRESHOLD) {
            error_log("Slow Query ({$executionTime}s): " . $this->statement->queryString);
        }
        
        return $result;
    }
    
    public function fetchAll() {
        $this->execute();
        return $this->statement->fetchAll();
    }
    
    public function fetch() {
        $this->execute();
        return $this->statement->fetch();
    }
    
    public function fetchColumn() {
        $this->execute();
        return $this->statement->fetchColumn();
    }
    
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    public function rowCount() {
        return $this->statement->rowCount();
    }
    
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    public function commit() {
        return $this->connection->commit();
    }
    
    public function rollBack() {
        return $this->connection->rollBack();
    }
    
    public function getQueryCount() {
        return $this->queryCount;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function tableExists($table) {
        $sql = "SHOW TABLES LIKE :table";
        $this->query($sql)->bind(':table', $table);
        return $this->fetch() !== false;
    }
    
    public function getLastError() {
        $errorInfo = $this->statement->errorInfo();
        return $errorInfo[2] ?? 'Unknown error';
    }
    
    // Prevent cloning of singleton
    private function __clone() {}
    
    // Prevent unserialization of singleton
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
?>
