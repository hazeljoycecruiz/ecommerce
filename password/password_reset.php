<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../src/database/db_conn.php';

class PasswordReset
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->connect();
    }

    public function resetPassword($request)
    {
        // Extract values from the request
        $reset_code = $request['reset_code'] ?? '';
        $password = $request['password'] ?? '';
        $password_confirmation = $request['password_confirmation'] ?? '';

        // Check if passwords match
        if ($password !== $password_confirmation) {
            echo json_encode(['status' => 'error', 'message' => 'Passwords do not match']);
            return;
        }

        // Validate password strength
        if (
            empty($password) || strlen($password) < 8 ||
            !preg_match('/[A-Za-z]/', $password) ||
            !preg_match('/[0-9]/', $password) ||
            !preg_match('/[@$!%*?&]/', $password)
        ) {
            echo json_encode(['status' => 'error', 'message' => 'Password must be at least 8 characters, contain at least one letter, one number, and one special character (@$!%*?&)']);
            return;
        }

        // Hash the reset code
        $token_hash = hash('sha256', $reset_code);

        // Prepare and execute the statement to find the user
        $stmt = $this->db->prepare("SELECT user_id, is_verified, reset_token_expires_at FROM users WHERE reset_token_hash = ?");
        $stmt->execute([$token_hash]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if user exists, is verified, and if the token is valid and not expired
        if (!$user) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid reset code']);
            return;
        }

        if (!$user['is_verified']) {
            echo json_encode(['status' => 'error', 'message' => 'User is not verified.']);
            return;
        }

        if (strtotime($user['reset_token_expires_at']) < time()) {
            echo json_encode(['status' => 'error', 'message' => 'Reset code has expired']);
            return;
        }

        // Hash the new password
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        // Update the user's password and reset token fields
        $stmt = $this->db->prepare("UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE user_id = ?");
        $stmt->execute([$password_hash, $user['user_id']]);

        echo json_encode(['status' => 'success', 'message' => 'Password reset successful.']);
    }
}

// Handle API request with endpoint logic
$request = json_decode(file_get_contents('php://input'), true) ?? [];
$endpoint = $_GET['endpoint'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($endpoint) {
        case 'resetPassword':
            $reset = new PasswordReset();
            $reset->resetPassword($request); // Pass the entire request
            break;

        default:
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Endpoint not found']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
}

?>
