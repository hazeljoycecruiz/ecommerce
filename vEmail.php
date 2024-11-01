<?php
require_once 'src/database/db_conn.php';

header('Content-Type: application/json');

class EmailVerification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function verifyCode($request) {
        // Check if verification code is provided in the request
        if (!isset($request['verification_code'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Verification code is required.']);
            return;
        }

        $verificationCode = $request['verification_code'];

        try {
            // Check if the verification code exists and is not expired
            $stmt = $this->db->prepare('
                SELECT user_id, expires_at 
                FROM user_verifications 
                WHERE verification_code = :verification_code
            ');
            $stmt->execute(['verification_code' => $verificationCode]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);

            // Validate the code and expiration date
            if (!$verification || strtotime($verification['expires_at']) < time()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Invalid or expired verification code.']);
                return;
            }

            $userId = $verification['user_id'];

            // Check if user exists in the users table
            $checkUserStmt = $this->db->prepare('SELECT COUNT(*) FROM users WHERE user_id = :user_id');
            $checkUserStmt->execute(['user_id' => $userId]);
            if ($checkUserStmt->fetchColumn() == 0) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'User not found.']);
                return;
            }

            // Mark the user as verified and update `expires_at` to 1 in `user_verifications`
            $updateStmt = $this->db->prepare('UPDATE user_verifications SET expires_at = 1 WHERE verification_code = :verification_code');
            $updateStmt->execute(['verification_code' => $verificationCode]);

            // Update the user's verified status in the `users` table
            $updateUserStmt = $this->db->prepare('UPDATE users SET is_verified = 1 WHERE user_id = :user_id');
            $updateUserStmt->execute(['user_id' => $userId]);

            echo json_encode(['status' => 'success', 'message' => 'Email verified successfully.']);
        } catch (PDOException $e) {
            http_response_code(500);
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
        }
    }
}

// Decode the incoming JSON request
$request = json_decode(file_get_contents('php://input'), true) ?? [];
$endpoint = $_GET['endpoint'] ?? '';

// Create a new Database instance and connect
$database = new Database();
$db = $database->connect();
$verificationHandler = new EmailVerification($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($endpoint) {
        case 'verifyEmail':
            $verificationHandler->verifyCode($request);
            break;
        default:
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}
?>
