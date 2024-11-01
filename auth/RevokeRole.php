<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../src/database/db_conn.php';

class User
{
    private $pdo;

    // Initialize database connection
    public function __construct($db)
    {
        $this->pdo = $db;
    }

    // Revoke a role from a user and reset to buyer role (2)
    public function revokeRole($user_id)
    {
        $defaultRole = 2; // Default role for buyer
        try {
            $query = "UPDATE users SET role_id = :role_id WHERE user_id = :user_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->bindParam(':role_id', $defaultRole);
            $stmt->bindParam(':user_id', $user_id);

            if ($stmt->execute() && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Role revoked and set to default (buyer).'];
            } else {
                return ['success' => false, 'message' => 'User not found or role already set to buyer.'];
            }
        } catch (PDOException $e) {
            error_log("Error revoking role: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred while revoking the role.'];
        }
    }
}

// API Usage
$db = new Database();
$pdo = $db->connect();
$user = new User($pdo);

$request = json_decode(file_get_contents('php://input'), true) ?? [];
$endpoint = $_GET['endpoint'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($endpoint) {
        case 'revokeRole':
            $userId = $request['user_id'] ?? null;
            if ($userId) {
                $revokeResult = $user->revokeRole($userId);
                http_response_code($revokeResult['success'] ? 200 : 400);
                echo json_encode($revokeResult);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'User ID is required.']);
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
