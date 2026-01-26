<?php
/**
 * Database Configuration
 * Bossify Academy Backend System
 */

// Database credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'bossify_academy');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('SITE_NAME', 'Bossify Academy');
define('SITE_EMAIL', 'info@bossifyacademy.com');
define('ADMIN_EMAIL', 'mwiganivalence@gmail.com'); // Main recipient email for all form submissions

// Email settings (SMTP)
// Configure these with your Gmail credentials
// For Gmail: Enable 2FA and create an App Password at https://myaccount.google.com/apppasswords
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'mwiganivalence@gmail.com'); // Your Gmail address
define('SMTP_PASS', 'cwrg wxki urrn lgkn'); // Gmail App Password (not regular password)
define('SMTP_FROM_EMAIL', 'noreply@bossifyacademy.com'); // Sender email
define('SMTP_FROM_NAME', 'Bossify Academy'); // Sender name

// Security
define('SECRET_KEY', 'your-secret-key-change-this-in-production');
define('ENCRYPTION_METHOD', 'AES-256-CBC');

// Timezone
date_default_timezone_set('Africa/Dar_es_Salaam');

/**
 * Database Connection Class
 */
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch(PDOException $e) {
            error_log("Database Connection Error: " . $e->getMessage());
            die("Database connection failed. Please check your configuration.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
