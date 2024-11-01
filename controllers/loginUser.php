<?php

require_once 'src/database/db_conn.php';
require_once 'src/class/login.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Rakit\Validation\Validator;

header('Content-Type: application/json');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
$dotenv->load();

try {
    // Establish a connection to the database
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Get the input data
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['message' => 'No input data provided.']);
    exit;
}

// Initialize Rakit Validator
$validator = new Validator;

// Define validation rules
$validation = $validator->make($data, [
    'email'    => 'required|email',
    'password' => 'required|min:8|regex:/[A-Za-z]/|regex:/[0-9]/|regex:/[@$!%*?&]/'
]);

// Run validation
$validation->validate();

if ($validation->fails()) {
    $errors = $validation->errors();
    http_response_code(400);
    echo json_encode(['message' => 'Validation failed', 'errors' => $errors->firstOfAll()]);
    exit;
}

// Instantiate the Login class and attempt to login the user
$login = new Login($db, ['email' => $data['email'], 'password' => $data['password']]);
$result = $login->loginUser();

// Handle login result
if ($result['success']) {
    $token = $result['token'];
    $fullName = $result['name'] ?? '';    
    
    // Return response with JWT token and user info
    http_response_code(200);
    echo json_encode([
        'message' => 'Login successful',
        'token' => $token,
        'user' => [
            'name' => $fullName,
            'email' => $data['email']
        ]
    ]);
    exit;
} else {
    http_response_code(401);
    echo json_encode(['message' => $result['message']]);
    exit;
}

?>