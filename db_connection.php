<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'movie-poll-db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Error Reporting (for development)
error_reporting(E_ALL);
ini_set('display_errors', '1');

class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // Create PDO connection
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => true // For better performance
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Set timezone if needed
            $this->connection->exec("SET time_zone = '+00:00'");
            
        } catch (PDOException $e) {
            // Log error securely
            error_log("Database Connection Error: " . $e->getMessage());
            
            // Display user-friendly message
            die("We're experiencing technical difficulties. Please try again later.");
        }
    }
    
    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning and serialization
    private function __clone() {}
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Initialize database connection
try {
    $db = Database::getInstance();
    $conn = $db->getConnection();
    
    // Test connection (remove in production)
    // $conn->query("SELECT 1");
    
} catch (Exception $e) {
    error_log("Database Initialization Error: " . $e->getMessage());
    die("Database initialization failed. Contact administrator.");
}

// Helper functions
function prepareQuery($sql) {
    global $conn;
    return $conn->prepare($sql);
}

function executeQuery($sql, $params = []) {
    global $conn;
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function getLastInsertId() {
    global $conn;
    return $conn->lastInsertId();
}

// Close connection automatically at script end
register_shutdown_function(function() {
    global $conn;
    $conn = null;
});
?>