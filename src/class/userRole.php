<?php
class userRole {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getUserRole($user_id) {
        $query = "SELECT role FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $roles = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $roles[] = $row['role'];
        }
        return $roles;
    }

    public function getUserProfile($user_id) {
        $query = "SELECT first_name, last_name, email, role FROM users WHERE user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
}
