<?php

class Database {

    private $host = "";
    private $db_name = "";
    private $username = "";
    private $password = "";
    public $conn;

    public function connect() {

        $this->conn = null;

        try {

            $this->conn = new mysqli(
                $this->host,
                $this->username,
                $this->password,
                $this->db_name
            );

            // UTF-8
            $this->conn->set_charset("utf8mb4");

        } catch (Exception $e) {

            die("Error de conexión: " . $e->getMessage());

        }

        return $this->conn;
    }
}
?>