<?php
session_start();

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $database = "ecommerce";
    public $conn;

    public function __construct() {
        $this->conn = new mysqli($this->host, $this->username, $this->password, $this->database);
        
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserInfo() {
    if (isset($_SESSION['user_id'])) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username']
        ];
    }
    return null;
}
?>