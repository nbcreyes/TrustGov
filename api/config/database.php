<?php
class Database {
    // Database connection credentials
    private $host     = "localhost";
    private $db_name  = "trustgov_db";
    private $username = "root";
    private $password = "";
    private $conn     = null;
    
    public function getConnection() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password
            );
            // Set PDO to throw exceptions on error
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            // Return associative arrays by default
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Return JSON error response if connection fails
            echo json_encode([
                "status"  => "error",
                "message" => "Database connection failed: " . $e->getMessage(),
                "data"    => null
            ]);
            exit();
        }

        return $this->conn;
    }
}
?>