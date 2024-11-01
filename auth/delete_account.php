<?php
require_once __DIR__ . "/../src/database/Db_conn.php";

// Set headers to allow DELETE requests and JSON responses
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// Create a database connection
$database = new Database();
$db = $database->connect(); // Reuse the existing connection logic

// Get the JSON input from the request body
$data = json_decode(file_get_contents("php://input"), true);

// Validate that 'email' and 'password' are provided
if (!isset($data['email']) || empty($data['email']) || !isset($data['password']) || empty($data['password'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
    exit;
}

// Extract email and password from the request
$email = $data['email'];
$password = $data['password'];

try {
    // Prepare a SELECT query to find the user by email
    $query = 'SELECT user_id, password, is_verified, role_id FROM users WHERE email = :email'; // Include role_id in the select
    $stmt = $db->prepare($query);
    $stmt->bindParam(':email', $email);
    $stmt->execute();

    // Fetch the user record
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the user exists and check the password
    if ($user) {
        if (!password_verify($password, $user['password'])) {
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
            exit;
        }

        // Check if the account is verified
        if ($user['is_verified'] != 1) { // Assuming '1' indicates verified
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Account not verified.']);
            exit;
        }

        // Check if the user is an admin (role_id 3)
        if ($user['role_id'] === 3) { // Assuming 3 is the role_id for admin
            http_response_code(403); // Forbidden
            echo json_encode(['status' => 'error', 'message' => 'Admin cannot be deleted.']);
            exit;
        }

        // Prepare the DELETE query to delete the user account
        $deleteQuery = 'DELETE FROM users WHERE user_id = :user_id';
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':user_id', $user['user_id'], PDO::PARAM_INT);

        // Execute the delete statement
        if ($deleteStmt->execute() && $deleteStmt->rowCount() > 0) {
            http_response_code(200); // OK
            echo json_encode(['status' => 'success', 'message' => 'Account deleted successfully.']);
        } else {
            http_response_code(404); // Not Found
            echo json_encode(['status' => 'error', 'message' => 'Account not found or already deleted.']);
        }
    } else {
        http_response_code(403); // Forbidden
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password.']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
