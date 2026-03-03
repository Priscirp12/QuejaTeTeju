<?php

class Database
{
    private $host = "localhost";
    private $db_name = "quejas_municipales";
    private $username = "root";
    private $password = "prisci";
    private $charset = "utf8mb4";
    public $conn;

    public function getConnection()
    {
        $this->conn = null;

        try {
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=" . $this->charset;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                // Ensure buffered queries are enabled to avoid "packets out of order"
                defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY') ? PDO::MYSQL_ATTR_USE_BUFFERED_QUERY : 0 => true,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            }
        }
        catch (PDOException $exception) {
            // Throw generic exception; caller will handle and return safe JSON
            throw new Exception('Database connection error');
        }

        return $this->conn;
    }
}
?>