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

// Function to get the Authorization token
function getAuthToken()
{
    $headers = apache_request_headers();
    return $headers['Authorization'] ?? null;
}

// Function to check if the user is an admin
function isAdmin($token, $jwt_secret_key)
{
    try {
        // Decode the JWT token
        $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));

        // Check if the user has admin role_id = 3
        return isset($decoded->data->role_id) && $decoded->data->role_id == 3;
    } catch (Exception $e) {
        error_log("Error verifying admin token: " . $e->getMessage());
        return false;
    }
}

// Get the token
$authHeader = getAuthToken();
if (!$authHeader) {
    http_response_code(401); // Unauthorized
    echo json_encode(['status' => 'error', 'message' => 'Authorization header missing']);
    exit();
}

$token = str_replace('Bearer ', '', $authHeader);

// Check if the user is an admin
if (!isAdmin($token, $jwt_secret_key)) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Access denied. Admin access only.']);
    exit();
}

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);
$role_id = $data['role_id'] ?? null;
$email = $data['email'] ?? null; // Changed to email

// Validate required fields
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
$hashed_user_id = $user['user_id'];
$current_role_id = $user['role_id'];
$is_verified = $user['is_verified'];

// Check if the current role is admin (role_id = 3)
if ($current_role_id == 3) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Cannot change the role of an admin account']);
    exit();
}

// Check if the user is verified
if ($is_verified != 1) {
    http_response_code(403); // Forbidden
    echo json_encode(['status' => 'error', 'message' => 'Cannot assign a role to an unverified account']);
    exit();
}

// Assign the role using the User class
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
