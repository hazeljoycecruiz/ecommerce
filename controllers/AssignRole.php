<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/db_conn.php';
require_once __DIR__ . '/../src/class/User.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header("Content-Type: application/json");

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$jwt_secret_key = $_ENV['JWT_SECRET'];

try {
    // Establish a connection to the database
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);  // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Check for Authorization header
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Authorization header missing']);
    exit();
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {
    // Decode JWT token to get the hashed user ID
    $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));
    $hashed_user_id = $decoded->data->user_id; // Retrieve the hashed user ID

    // Get the input data
    $data = json_decode(file_get_contents("php://input"), true);
    $role_id = $data['role_id'] ?? null;

    // Validate required fields
    if (!$hashed_user_id || !$role_id) {
        http_response_code(400); // Bad Request
        echo json_encode(['status' => 'error', 'message' => 'User ID and role ID are required']);
        exit();
    }

    // Assign the role using the User class
    $user = new User($db);
    $result = $user->assignRole($hashed_user_id, $role_id);

    if ($result['success']) {
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => $result['message']]);
    } else {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => $result['message']]);
    }
} catch (Exception $e) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Access denied', 'error' => $e->getMessage()]);
}
?>
