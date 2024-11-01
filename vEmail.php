<?php
require_once 'src/database/db_conn.php';

header('Content-Type: application/json');

class EmailVerification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function verifyCode($request) {
        // Ensure the verification code is provided in the request
        if (!isset($request['verification_code'])) {
            http_response_code(400);
            echo json_encode(['status' => 'error', 'message' => 'Verification code is required.']);
            return;
        }

        $verificationCode = $request['verification_code'];

        try {
            // Look for the verification code in the database
            $stmt = $this->db->prepare('
                SELECT user_id, expires_at 
                FROM user_verifications 
                WHERE verification_code = :verification_code
            ');
            $stmt->execute(['verification_code' => $verificationCode]);
            $verification = $stmt->fetch(PDO::FETCH_ASSOC);

            // Check if the code is valid and not expired
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

            // Mark the user as verified
            $updateStmt = $this->db->prepare('UPDATE users SET is_verified = 1 WHERE user_id = :user_id');
            $updateStmt->execute(['user_id' => $userId]);

            // Optional: delete the verification record after successful verification
            $deleteStmt = $this->db->prepare('DELETE FROM user_verifications WHERE verification_code = :verification_code');
            $deleteStmt->execute(['verification_code' => $verificationCode]);

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

// Handle the request
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