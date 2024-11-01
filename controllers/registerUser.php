<?php
require_once 'src/database/db_conn.php';
require_once 'src/class/register.php';
require_once 'src/class/emailService.php';
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Rakit\Validation\Validator;

header('Content-Type: application/json');

$dotenv = Dotenv::createImmutable(__DIR__ . '/../'); 
$dotenv->load();

try {
    $database = new Database();
    $db = $database->connect();
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Check if the required keys are present in the request
$requiredKeys = ['first_name', 'last_name', 'email', 'password', 'confirm_password', 'phone_num', 'address'];

foreach ($requiredKeys as $key) {
    if (!isset($data[$key])) {
        http_response_code(400);
        echo json_encode(['message' => 'Missing required field: ' . $key]);
        exit;
    }
}

// Initialize Rakit Validator
$validator = new Validator;

// Validation rules
$validation = $validator->make($data, [
    'first_name'            => 'required|regex:/^[\p{L} ]+$/|max:50',
    'last_name'             => 'required|regex:/^[\p{L} ]+$/|max:50',
    'email'                 => 'required|email|max:100',
    'password'              => 'required|min:8|regex:/[A-Za-z]/|regex:/[0-9]/|regex:/[@$!%*?&]/', 
    'confirm_password'      => 'required|min:8|same:password',
    'phone_num'             => 'required|regex:/^09\d{9}$/',  // Must be 11 digits and start with 09
    'address'               => 'required|regex:/^[^,]+,[^,]+,[^,]+$/' // street, city, province format
]);

try {
    // Run validation
    $validation->validate();

    // Check if validation failed
    if ($validation->fails()) {
        // Handling validation errors
        $errors = $validation->errors();
        http_response_code(400);
        echo json_encode(['message' => 'Validation failed', 'errors' => $errors->firstOfAll()]);
        exit;
    }

    // Check for existing email
    $emailExistsQuery = $db->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
    $emailExistsQuery->execute(['email' => $data['email']]);
    $emailExistsCount = $emailExistsQuery->fetchColumn();

    if ($emailExistsCount > 0) {
        http_response_code(400);
        echo json_encode(['message' => 'Email is already registered.']);
        exit;
    }

    // Prepare timestamps for created_at and updated_at
    $createdAt = date('Y-m-d H:i:s');
    $updatedAt = date('Y-m-d H:i:s');

    // Check if an admin user already exists
    $adminCheckQuery = $db->prepare("SELECT COUNT(*) FROM users WHERE role_id = 3");
    $adminCheckQuery->execute();
    $adminCount = $adminCheckQuery->fetchColumn();

    // Set role_id based on whether an admin exists
    $role_id = ($adminCount == 0) ? 3 : 2; // Assign role_id = 3 (admin) if no admin exists, otherwise 2 (buyer)

    // Attempt to register the user
    $user = new Register($db);
    $result = $user->registerUser(
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['password'],
        $data['phone_num'],
        $data['address'],
        $data['lang_profile'] ?? 'en', // Default to 'en' if not provided
        $role_id,                      // Use the determined role_id
        $createdAt,
        $updatedAt
    );

    if ($result['success']) {
        $token = $result['token'];
        $userId = $result['user_id']; // Assuming 'registerUser' function returns user_id
        $fullName = $data['first_name'] . ' ' . $data['last_name'];

        $emailService = new EmailService($db);
        if ($emailService->sendVerificationEmail($data['email'], $userId)) {
            http_response_code(201);
            echo json_encode([
                'message' => 'User registered successfully. Verification email sent.',
                'user' => [
                    'name' => $fullName,
                    'email' => $data['email']
                ]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['message' => 'Verification email could not be sent.']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['message' => $result['message']]);
    }
    
} catch (Exception $e) {
    // General error handling
    http_response_code(500);
    echo json_encode(['message' => 'An error occurred: ' . $e->getMessage()]);
}

ini_set('display_errors', '1');
error_reporting(E_ALL);
?>
