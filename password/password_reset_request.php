<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../src/database/db_conn.php';
require_once __DIR__ . '/../password/mailer.php';

class PasswordResetRequest {
    private $db;
    private $mailer;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();

        $mailer = new Mailer();
        $this->mailer = $mailer->getMailer();
    }

    public function requestReset($request) {
        $email = $request['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            return;
        }

        // Generate a 6-character alphanumeric reset code with uppercase letters and numbers
        $reset_code = substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 6)), 0, 6);
        $token_hash = hash('sha256', $reset_code);
        $expiry = date('Y-m-d H:i:s', time() + 1800); // 30 mins expiry

        $stmt = $this->db->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires_at = ? WHERE email = ?");
        $stmt->execute([$token_hash, $expiry, $email]);

        if ($stmt->rowCount()) {
            $this->sendResetEmail($email, $reset_code);
            echo json_encode(['status' => 'success', 'message' => 'Reset code sent to your email.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No user found with this email.']);
        }
    }

    private function sendResetEmail($email, $reset_code) {
        $this->mailer->addAddress($email);
        $this->mailer->Subject = "Password Reset Verification";
        $this->mailer->Body = "Here is your password reset code: <strong>$reset_code</strong>. It is valid for 30 minutes. Use this code in the password reset form to proceed.";

        try {
            $this->mailer->send();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => "Mailer Error: {$this->mailer->ErrorInfo}"]);
        }
    }
}

// Handle API request
$request = json_decode(file_get_contents('php://input'), true) ?? [];
$endpoint = $_GET['endpoint'] ?? '';

$passwordResetRequest = new PasswordResetRequest();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($endpoint) {
        case 'requestPasswordReset':
            $passwordResetRequest->requestReset($request);
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
