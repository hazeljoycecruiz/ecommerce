<?php

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database/db_conn.php';
require_once __DIR__ . '/../src/class/userRole.php'; 

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Dotenv\Dotenv;

header("Content-Type: application/json");

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

// Check for Authorization header
$headers = apache_request_headers();
if (!isset($headers['Authorization'])) {
    http_response_code(401);
    echo json_encode(['message' => 'Authorization header missing']);
    exit();
}

$authHeader = $headers['Authorization'];
$token = str_replace('Bearer ', '', $authHeader);

try {
    // Decode JWT to get user data
    $decodedToken = JWT::decode($token, new Key('your_secret_key', 'HS256'));
    $userData = (array) $decodedToken->data;

    // Check if the requester is either Admin (role_id = 3) or the user themselves
    if ($userData['role_id'] !== 3 && $userData['id'] !== $userId) {
        $this->respond(["message" => "Access denied."], 403); // Forbidden
        return;
    }

    // Retrieve the user profile from the database
    $query = "SELECT first_name, last_name FROM user WHERE id = :id";
    $stmt = $this->database->connect()->prepare($query);
    $stmt->bindParam(':id', $userId, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Return the user profile (first name, last name)
        $this->respond([
            "id" => $userId,
            "first_name" => $user['first_name'],
            "last_name" => $user['last_name']
        ], 200); // OK
    } else {
        $this->respond(["message" => "User not found."], 404); // Not Found
    }
} catch (\Exception $e) {
    $this->respond(["message" => "Invalid or expired token.", "error" => $e->getMessage()], 401);
}


private function respond(array $data, int $statusCode = 200)
{
http_response_code($statusCode);
header('Content-Type: application/json');
echo json_encode($data);
}
