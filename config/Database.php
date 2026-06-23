<?php
/**
 * Database Configuration
 * Update these values based on your XAMPP setup
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'school_enrollment';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function connect() {
        $this->conn = null;

        try {
            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name
            );

            if ($this->conn->connect_error) {
                throw new Exception('Connection failed: ' . $this->conn->connect_error);
            }

            $this->conn->set_charset('utf8mb4');
        } catch (Exception $e) {
            die('Database Connection Error: ' . $e->getMessage());
        }

        return $this->conn;
    }

    public function closeConnection() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
}
