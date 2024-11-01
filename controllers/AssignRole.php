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

try {
    // Establish a connection to the database
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);  // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get the JWT token from the Authorization header
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);  // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authorization token not found']);
    exit();
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);

try {
    // Decode the JWT
    $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
    $userRoleId = $decoded->data->role_id; // Get the user's role from the token
} catch (Exception $e) {
    http_response_code(401);  // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Invalid token: ' . $e->getMessage()]);
    exit();
}

// Check if the user is an admin (role_id 3)
if ($userRoleId !== 3) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Access denied: Admin privileges required.']);
    exit();
}

// Get the input data
$request = json_decode(file_get_contents('php://input'), true);

// Validate input data
$email = $request['email'] ?? null;
$role_id = $request['role_id'] ?? null;

if (!$email || !$role_id) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Email and role ID are required']);
    exit();
}

// Check if the user exists in the database by email
$stmt = $db->prepare("SELECT user_id, role_id, is_verified FROM users WHERE email = :email");
$stmt->bindParam(':email', $email);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    http_response_code(404); // Not Found
    echo json_encode(['status' => 'error', 'message' => 'Email not found']);
    exit();
}

// Get the user ID, current role, and verification status from the result
$userId = $user['user_id'];
$currentRoleId = $user['role_id'];
$is_verified = $user['is_verified'];

// Check if the user is verified
if ($is_verified != 1) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Cannot assign a role to an unverified account']);
    exit();
}

// Check if the current role is admin (role_id = 3)
if ($currentRoleId == 3) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Cannot change the role of an admin account']);
    exit();
}

// Proceed to assign the new role
$userClass = new User($db);
$result = $userClass->assignRole($email, $role_id); // Pass email instead of user ID

if ($result['success']) {
    http_response_code(200);
    echo json_encode(['status' => 'success', 'message' => $result['message']]);
} else {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => $result['message']]);
}
?>
