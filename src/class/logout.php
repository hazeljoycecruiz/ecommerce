<?php

class Logout
{
    private $db;
    private $token;

    public function __construct($db, $token)
    {
        $this->db = $db;
        $this->token = $token;
    }

    public function isTokenInvalidated($userId)
    {
        // Adjusted query to check if the token is already in session_tokens
        $query = "SELECT * FROM session_tokens WHERE token = :token AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':token', $this->token);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();

        return $stmt->rowCount() > 0; // Return true if token is found
    }

    public function invalidateToken($userId)
    {
        // Insert the token into the session_tokens table
        $query = "INSERT INTO session_tokens (user_id, token, created_at, expiration) VALUES (:user_id, :token, :created_at, :expiration)";
        $stmt = $this->db->prepare($query);

        // Set the created_at and expiration timestamps
        $createdAt = date('Y-m-d H:i:s');
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour')); // Adjust expiration time as needed

        // Bind parameters
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':token', $this->token);
        $stmt->bindParam(':created_at', $createdAt);
        $stmt->bindParam(':expiration', $expiration);

        return $stmt->execute(); // Return true if insertion is successful
    }

    public function processLogout($userId)
    {
        if ($this->isTokenInvalidated($userId)) {
            return ['success' => false, 'message' => 'Token already invalidated.'];
        }

        if ($this->invalidateToken($userId)) {
            return ['success' => true, 'message' => 'Logged out successfully. Token invalidated.'];
        } else {
            return ['success' => false, 'message' => 'Failed to invalidate token.'];
        }
    }
}
?>