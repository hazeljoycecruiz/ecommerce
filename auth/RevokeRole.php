<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/db_conn.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header('Content-Type: application/json');

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

try {
    // Establish a connection to the database
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);  // Internal Server Error
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get the JWT token from the Authorization header
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);  // Unauthorized
    echo json_encode(['message' => 'Authorization token not found']);
    exit;
}

$jwt = str_replace('Bearer ', '', $headers['Authorization']);

try {
    // Decode the JWT
    $decoded = JWT::decode($jwt, new Key($_ENV['JWT_SECRET'], 'HS256'));
    $userRoleId = $decoded->data->role_id; // Get the user's role from the token
} catch (Exception $e) {
    http_response_code(401);  // Unauthorized
    echo json_encode(['message' => 'Invalid token: ' . $e->getMessage()]);
    exit;
}

// Check if the user is an admin (role_id 3)
if ($userRoleId !== 3) {
    http_response_code(403); // Forbidden
    echo json_encode(['message' => 'Access denied: Admin privileges required.']);
    exit;
}

// Get the input data
$request = json_decode(file_get_contents('php://input'), true);
$endpoint = $_GET['endpoint'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($endpoint) {
        case 'revokeRole':
            $userEmail = $request['email'] ?? null; // Get email from the request
            $requestedRole = $request['role'] ?? null; // Get role from the request
            
            if ($userEmail && $requestedRole) {
                // Check if user exists and get their details
                $query = "SELECT user_id, role_id, is_verified FROM users WHERE email = :email";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':email', $userEmail);
                $stmt->execute();

                if ($stmt->rowCount() > 0) {
                    $userData = $stmt->fetch(PDO::FETCH_ASSOC);
                    $userId = $userData['user_id'];
                    $currentRole = $userData['role_id'];
                    $isVerified = $userData['is_verified'];

                    // Check if user is verified
                    if (!$isVerified) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['success' => false, 'message' => 'The user you are trying to revoke is not verified.']);
                        exit;
                    }

                    // Prevent revocation of admin role (role_id 3)
                    if ($currentRole === 3) {
                        http_response_code(403); // Forbidden
                        echo json_encode(['success' => false, 'message' => 'Cannot revoke admin role.']);
                        exit;
                    }

                    // Check if the requested role matches the current role
                    if ($currentRole != $requestedRole) {
                        http_response_code(400); // Bad Request
                        echo json_encode(['success' => false, 'message' => 'The requested role does not match the current role.']);
                        exit;
                    }

                    // Proceed to revoke role only if not already set to buyer
                    if ($currentRole != 2) { // Assuming 2 is the default role for buyer
                        $defaultRole = 2; // Default role for buyer
                        $query = "UPDATE users SET role_id = :role_id WHERE user_id = :user_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':role_id', $defaultRole);
                        $stmt->bindParam(':user_id', $userId);

                        if ($stmt->execute() && $stmt->rowCount() > 0) {
                            http_response_code(200); // OK
                            echo json_encode(['success' => true, 'message' => 'Role revoked and set to default (buyer).']);
                        } else {
                            http_response_code(400); // Bad Request
                            echo json_encode(['success' => false, 'message' => 'User not found or role already set to buyer.']);
                        }
                    } else {
                        http_response_code(400); // Bad Request
                        echo json_encode(['success' => false, 'message' => 'User role is already set to default (buyer).']);
                    }
                } else {
                    http_response_code(404); // Not Found
                    echo json_encode(['success' => false, 'message' => 'Email not found.']);
                }
            } else {
                http_response_code(400); // Bad Request
                echo json_encode(['success' => false, 'message' => 'Email and role are required.']);
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

// Disable error display for production
ini_set('display_errors', '0');
error_reporting(0);
?>
