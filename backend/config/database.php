<?php
/**
 * Database Configuration
 * MySQL Database
 * Connects to MySQL database
 */

class Database {
    private $host = "localhost";
    private $db_name = "online_learning";
    private $username = "root";
    private $password = "";
    public $conn;

    public function __construct() {
        // Load .env file if env_loader exists
        if (file_exists(__DIR__ . '/env_loader.php')) {
            require_once __DIR__ . '/env_loader.php';
        }
        
        // Override with environment variables if set
        $this->host = getenv('DB_HOST') ?: $this->host;
        $this->db_name = getenv('DB_NAME') ?: $this->db_name;
        $this->username = getenv('DB_USER') ?: $this->username;
        $this->password = getenv('DB_PASSWORD') ?: $this->password;
    }

    public function getConnection() {
        $this->conn = null;

        try {
            // Create MySQL DSN
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // PDO options
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Create PDO connection
            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            
            // Set MySQL connection attributes
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("SET NAMES utf8mb4");
            
        } catch(PDOException $exception) {
            error_log("Database connection error: " . $exception->getMessage());
            error_log("DB: " . $this->db_name . "@" . $this->host);
            error_log("Error Code: " . $exception->getCode());
            $this->conn = null;
        } catch(Exception $exception) {
            error_log("Database connection error (general): " . $exception->getMessage());
            error_log("DB: " . $this->db_name . "@" . $this->host);
            $this->conn = null;
        }

        return $this->conn;
    }

    public function closeConnection() {
        $this->conn = null;
    }
    
    public function getDbName() {
        return $this->db_name;
    }
}
?>
