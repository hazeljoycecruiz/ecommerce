<?php

require_once __DIR__ . "/src/database/Db_conn.php";

class EmailVerificationHandler {
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function verifyCode($request) {
        $code = $request['verification_code'] ?? '';

        // Fetch the verification record based on the code
        $stmt = $this->db->prepare("SELECT * FROM user_verifications WHERE verification_code = ? AND expires_at > NOW()");
        $stmt->execute([$code]);
        $verification = $stmt->fetch();

        if ($verification) {
            // Mark the email as verified
            $stmt = $this->db->prepare("UPDATE users SET is_verified = 1 WHERE user_id = ?");
            $stmt->execute([$verification['user_id']]);

            // Optionally, delete the verification record after use
            $stmt = $this->db->prepare("DELETE FROM user_verifications WHERE verification_id = ?");
            $stmt->execute([$verification['verification_id']]);

            echo json_encode(['status' => 'success', 'message' => 'Email verified successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid or expired verification code.']);
        }
    }
}

// Handle API request
$request = json_decode(file_get_contents('php://input'), true) ?? [];
$emailVerificationHandler = new EmailVerificationHandler();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emailVerificationHandler->verifyCode($request);
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
}

?>
