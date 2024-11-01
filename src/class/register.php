<?php
class Register {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function registerUser($firstName, $lastName, $email, $password, $phone_num, $address, $lang_profile = 'en', $role_id = 2, $created_at, $updated_at) {
        try {
            // Generate a unique user ID and token
            $userId = bin2hex(random_bytes(16)); // Unique user ID
            $token = bin2hex(random_bytes(16)); // Session token

            // Check if role_id exists in roles table
            $roleCheckQuery = 'SELECT COUNT(*) FROM roles WHERE role_id = :role_id';
            $roleCheckStmt = $this->db->prepare($roleCheckQuery);
            $roleCheckStmt->bindParam(':role_id', $role_id);
            $roleCheckStmt->execute();
            $roleExists = $roleCheckStmt->fetchColumn();

            if (!$roleExists) {
                return ['success' => false, 'message' => 'Role ID does not exist.'];
            }

            // Insert user into the users table 
            $query = 'INSERT INTO users (user_id, first_name, last_name, email, password, phone_num, address, lang_profile, role_id, created_at, updated_at) 
                      VALUES (:user_id, :first_name, :last_name, :email, :password, :phone_num, :address, :lang_profile, :role_id, :created_at, :updated_at)';
            $stmt = $this->db->prepare($query);
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Bind parameters
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':first_name', $firstName);
            $stmt->bindParam(':last_name', $lastName);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password', $hashedPassword);
            $stmt->bindParam(':phone_num', $phone_num);
            $stmt->bindParam(':address', $address);
            $stmt->bindParam(':lang_profile', $lang_profile);
            $stmt->bindParam(':role_id', $role_id);
            $stmt->bindParam(':created_at', $created_at);
            $stmt->bindParam(':updated_at', $updated_at);
            
            if ($stmt->execute()) {
                // Insert session token into session_tokens table
                $sessionQuery = 'INSERT INTO session_tokens (token, expiration, created_at, user_id) 
                                 VALUES (:token, DATE_ADD(NOW(), INTERVAL 1 HOUR), NOW(), :user_id)';
                $sessionStmt = $this->db->prepare($sessionQuery);
                $sessionStmt->bindParam(':token', $token);
                $sessionStmt->bindParam(':user_id', $userId);
                
                if ($sessionStmt->execute()) {
                    return [
                        'success' => true,
                        'token' => $token,
                        'user_id' => $userId
                    ];
                } else {
                    return ['success' => false, 'message' => 'Failed to insert session token.'];
                }
            } else {
                return ['success' => false, 'message' => 'User registration failed.'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];   
        }
    }
}

?>
