<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/db_conn.php';
require_once __DIR__ . '/../src/class/User.php';
require_once __DIR__ . '/../src/class/login.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
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
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit();
}

// Get the Authorization header from the request
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Authorization header missing']);
    exit();
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {
    // Validate and decode JWT
    $decoded = JWT::decode($token, new Key($jwt_secret_key, 'HS256'));

    // Check if user_id exists in decoded token data
    if (!isset($decoded->data->user_id)) {
        //throw new Exception('Invalid token structure');
        echo json_encode(['error' => 'Invalid token structure', 'decoded' => $decoded]);
        exit();
    }

    $user_id = $decoded->data->user_id;

    // Initialize User class and get profile
    $user = new User($db);
    $profile = $user->getUserProfile($user_id);

    if ($profile) {
        echo json_encode($profile);
    } else {
        http_response_code(404);
        echo json_encode(['message' => 'User not found']);
    }
} catch (ExpiredException $e) {
    http_response_code(401);
    echo json_encode(['message' => 'Access denied', 'error' => 'Token expired.']);
} catch (SignatureInvalidException $e) {
    http_response_code(401);
    echo json_encode(['message' => 'Access denied', 'error' => 'Signature verification error.']);
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['message' => 'Access denied', 'error' => $e->getMessage()]);
}
