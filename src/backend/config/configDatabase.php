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
                // Use buffered queries to ensure result sets are fully buffered on client
                // which prevents "Commands out of sync" / packets out of order issues
                // when calling stored procedures that return multiple result sets.
                defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY') ? PDO::MYSQL_ATTR_USE_BUFFERED_QUERY : 0 => true,
            ];

            $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            // If available, enable buffered queries on the connection to ensure
            // the client fully buffers result sets from stored procedures
            // which prevents "Packets out of order" when issuing subsequent queries.
            if (defined('PDO::MYSQL_ATTR_USE_BUFFERED_QUERY')) {
                $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
            }
        }
        catch (PDOException $exception) {
            // Do not echo errors directly; throw to let caller handle and return
            // a proper JSON response. Avoid exposing credentials/paths in output.
            throw new Exception('Database connection error');
        }

        return $this->conn;
    }
}
?>