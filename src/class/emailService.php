<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

class EmailService {
    private $mail;
    private $db; // Database connection

    public function __construct($dbConnection) {
        $this->mail = new PHPMailer(true);
        $this->setupMailServer();
        $this->db = $dbConnection; // Assign the database connection
    }

    private function setupMailServer() {
        $this->mail->isSMTP();
        $this->mail->Host       = 'smtp.gmail.com';
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $_ENV['SMTP_EMAIL'];
        $this->mail->Password   = $_ENV['SMTP_PASSWORD'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = 587;
    }

    // Function to generate a random verification code
    private function generateVerificationCode($length = 6) {
        return substr(
            str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length / 36))),
            1,
            $length
        );
    }

    // Function to check if user ID exists
    private function userExists($userId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn() > 0; // Returns true if user exists
    }

    // Function to store verification code in the database
    private function storeVerificationCode($userId, $verificationCode) {
        if (!$this->userExists($userId)) {
            return false; // User does not exist
        }

        $verificationId = bin2hex(random_bytes(16)); // Generate a unique verification ID
        $createdAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes')); // Set expiration time

        $stmt = $this->db->prepare("INSERT INTO user_verifications (verification_id, user_id, verification_code, created_at, expires_at) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$verificationId, $userId, $verificationCode, $createdAt, $expiresAt]);
    }

    public function sendVerificationEmail($toEmail, $userId) {
        // Generate the verification code
        $verificationCode = $this->generateVerificationCode();

        // Setup email details
        $this->mail->setFrom('no-reply@example.com', 'IT113 E-Commerce');
        $this->mail->addAddress($toEmail);

        $this->mail->isHTML(true);
        $this->mail->Subject = 'Confirm your Email Address';
        $this->mail->Body    = "
            <p>Here is your email verification code: <strong>$verificationCode</strong>.</p>
            <p>It is valid for 30 minutes.</p>
            <p>Please enter this code in the application to verify your email address. If you did not request this verification, you can safely ignore this email.</p>
        ";

        try {
            $this->mail->send();
            // Store the verification code in the database
            if ($this->storeVerificationCode($userId, $verificationCode)) {
                return [
                    'success' => true,
                    'verification_code' => $verificationCode
                ];
            } else {
                return ['success' => false, 'message' => 'Failed to store verification code. User may not exist.'];
            }
        } catch (Exception $e) {
            error_log("Email sending failed: " . $this->mail->ErrorInfo);
            return ['success' => false, 'message' => 'Failed to send email.'];
        }
    }
}
?>
