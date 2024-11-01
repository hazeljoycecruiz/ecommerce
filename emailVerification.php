<?php

header('Content-Type: application/json');

require_once __DIR__ . "/src/database/Db_conn.php";
require_once __DIR__ . "/mailer.php";

class EmailVerification {
    private $db;
    private $mailer;

    public function __construct() {
        $database = new Database();
        $this->db = $database->connect();

        $mailer = new Mailer();
        $this->mailer = $mailer->getMailer();
    }

    public function sendVerificationEmail($request) {
        $email = $request['email'] ?? '';

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email address']);
            return;
        }

        // Generate a shorter verification code (e.g., 6 alphanumeric characters)
        $verificationCode = $this->generateVerificationCode(6);
        $expiry = date('Y-m-d H:i:s', time() + 1800); // 30 minutes expiry

        // Get user ID based on the email
        $stmt = $this->db->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            // Insert a new verification record into user_verifications
            $verificationId = $this->generateUuid(); // Generate a UUID for verification ID
            $stmt = $this->db->prepare("INSERT INTO user_verifications (verification_id, user_id, verification_code, created_at, expires_at) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->execute([$verificationId, $user['user_id'], $verificationCode, $expiry]);

            $this->sendVerificationEmailToUser($email, $verificationCode);
            echo json_encode(['status' => 'success', 'message' => 'Verification email sent.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'No user found with this email.']);
        }
    }

    private function sendVerificationEmailToUser($email, $verificationCode) {
        $this->mailer->addAddress($email);
        $this->mailer->Subject = "Email Verification";
        $this->mailer->Body = "Your verification code is: <strong>$verificationCode</strong>. Please use this code to verify your email. It is valid for 30 minutes.";

        try {
            $this->mailer->send();
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => "Mailer Error: {$this->mailer->ErrorInfo}"]);
        }
    }

    // Function to generate a random verification code
    private function generateVerificationCode($length) {
        return substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / 36))), 1, $length);
    }

    // Function to generate a UUID
    private function generateUuid() {
        return sprintf('%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );
    }
}

// Handle API request
$request = json_decode(file_get_contents('php://input'), true) ?? [];
$endpoint = $_GET['endpoint'] ?? '';

$emailVerification = new EmailVerification();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($endpoint) {
        case 'sendVerificationEmail':
            $emailVerification->sendVerificationEmail($request);
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
