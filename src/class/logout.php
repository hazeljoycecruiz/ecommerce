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

    public function isTokenInvalidated($userId) // Accept userId as a parameter
    {
        // Adjusted query to include user_id
        $query = "SELECT * FROM user_tokens WHERE token_id = :token_id AND user_id = :user_id";
        $stmt = $this->db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(':token_id', $this->token);
        $stmt->bindParam(':user_id', $userId); // Bind user ID
        $stmt->execute();

        return $stmt->rowCount() > 0; // Return true if token is found
    }

    public function invalidateToken($userId) // Accept userId as a parameter
    {
        // Inserting the token into the user_tokens table
        $query = "INSERT INTO user_tokens (user_id, token_id, issued_at, expired_at) VALUES (:user_id, :token_id, :issued_at, :expired_at)";
        $stmt = $this->db->prepare($query);
        
        // Set the issue and expiration timestamps
        $issueAt = date('Y-m-d H:i:s');
        $expiredAt = date('Y-m-d H:i:s', strtotime('+1 hour')); // Adjust expiration time as needed
        
        // Bind parameters
        $stmt->bindParam(':user_id', $userId); // Bind user ID
        $stmt->bindParam(':token_id', $this->token); // Bind token ID
        $stmt->bindParam(':issued_at', $issueAt); // Bind issue time
        $stmt->bindParam(':expired_at', $expiredAt); // Bind expiration time
        
        return $stmt->execute(); // Return true if insertion is successful
    }

    public function processLogout($userId) // Accept userId as a parameter
    {
        if ($this->isTokenInvalidated($userId)) { // Pass userId to isTokenInvalidated
            return ['success' => false, 'message' => 'Token already invalidated.'];
        }

        if ($this->invalidateToken($userId)) { // Pass userId to invalidateToken
            return ['success' => true, 'message' => 'Logged out successfully. Token invalidated.'];
        } else {
            return ['success' => false, 'message' => 'Failed to invalidate token.'];
        }
    }
}
