<?php

class Database {
    // Local XAMPP configuration (default)
    private $host = 'localhost';
    private $db_name = 'budgettrack_db';
    private $username = 'root';
    private $password = '';
    private $conn;


    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );
        } catch(PDOException $exception) {
            // Log error but don't expose sensitive information in production
            error_log("Database connection error: " . $exception->getMessage());
            
            // For development, show error message
            if (defined('DEBUG') && DEBUG) {
                echo "Connection error: " . $exception->getMessage();
            } else {
                echo "Database connection failed. Please contact the administrator.";
            }
            
            // Return null to indicate connection failure
            return null;
        }

        return $this->conn;
    }
}

function getDB() {
    static $db = null;
    if ($db === null) {
        $database = new Database();
        $db = $database->getConnection();
    }
    return $db;
}
?>
