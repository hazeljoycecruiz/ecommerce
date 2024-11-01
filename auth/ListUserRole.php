<?php
require_once __DIR__ . '/../src/database/db_conn.php';
require_once __DIR__ . '/../src/class/User.php';

class ListUserRole
{
    private $pdo;

    public function __construct()
    {
        $db = new Database();
        $this->pdo = $db->connect();
    }

    public function listUserRoles()
    {
        try {
            // Prepare the query to fetch all users and their roles
            $query = "SELECT u.user_id, u.first_name, u.last_name, r.role_name 
                      FROM users u 
                      LEFT JOIN roles r ON u.role_id = r.role_id";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute();

            // Fetch all user-role pairs
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if ($users) {
                // Respond with success message and data
                $this->respond(['success' => true, 'data' => $users]);
            } else {
                // If no users found
                $this->respond(['success' => false, 'message' => 'No users or roles found.']);
            }
        } catch (PDOException $e) {
            // Log detailed error message and respond with the error for debugging
            error_log("Error fetching all user roles: " . $e->getMessage());
            $this->respond(['success' => false, 'message' => "An error occurred while fetching user roles.", 'error' => $e->getMessage()]);
        }
    }


    private function respond(array $data, int $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}

// API Usage
$listUserRole = new ListUserRole();
$endpoint = $_GET['endpoint'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($endpoint) {
        case 'user_role':
            $listUserRole->listUserRoles();
            break;
        default:
            http_response_code(404);
            echo json_encode(['message' => 'Endpoint not found']);
            break;
    }
}