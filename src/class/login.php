<?php
use Firebase\JWT\JWT;

class Login {
    private $db;
    private $email;
    private $password;

    public function __construct($db, $data) {
        $this->db = $db;
        $this->email = $data['email'];
        $this->password = $data['password'];
    }

    public function loginUser() {
        try {
            // Prepare SQL statement to fetch user details
            $query = 'SELECT user_id, first_name, last_name, password, role_id, is_verified FROM users WHERE email = :email';
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':email', $this->email);
            $stmt->execute();

            // Fetch user data
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Log the attempt
            error_log('Attempting login for email: ' . $this->email);
            if ($user) {
                error_log('User found: ' . json_encode($user)); // Log user details

                // Check if email is verified
                if ($user['is_verified'] == 0) {
                    error_log('Email not verified for user: ' . $this->email);
                    return ['success' => false, 'message' => 'Email not verified.'];
                }

                // Verify password
                if (password_verify($this->password, $user['password'])) {
                    // Generate JWT token
                    $secret_key = $_ENV['JWT_SECRET'];
                    $issuer = "http://127.0.0.1";
                    $issuedAt = time();
                    $expirationTime = $issuedAt + 3600; // Token valid for 1 hour
                    $payload = [
                        'iss' => $issuer,
                        'iat' => $issuedAt,
                        'exp' => $expirationTime,
                        'data' => [
                            'user_id' => $user['user_id'],
                            'first_name' => $user['first_name'],
                            'last_name' => $user['last_name'],
                            'role_id' => $user['role_id'],
                        ]
                    ];

                    // Encode the token
                    $jwt = JWT::encode($payload, $secret_key, 'HS256');

                    // Return user data along with the token
                    error_log('Login successful for user: ' . $this->email);
                    return [
                        'success' => true,
                        'message' => 'Login successful',
                        'token' => $jwt,
                        'name' => trim($user['first_name'] . ' ' . $user['last_name']), // Full name
                    ];
                } else {
                    error_log('Password verification failed for user: ' . $this->email);
                    return ['success' => false, 'message' => 'Invalid Password'];
                }
            } else {
                error_log('User not found for email: ' . $this->email);
                return ['success' => false, 'message' => 'User not verified.'];
            }
        } catch (PDOException $e) {
             error_log('Database error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
}
?>