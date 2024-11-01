<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

$request_method = $_SERVER['REQUEST_METHOD'];
$request_uri = $_SERVER['REQUEST_URI'];

// Base path for the API routes
$base_path = '/E-commerce/api.php';


// Register Route
if ($request_uri === $base_path . '/register' && $request_method === 'POST') {
    require __DIR__ . '/controllers/registerUser.php'; 
    exit();
}

// Login Route
if ($request_uri === $base_path . '/login' && $request_method === 'POST') {
    require __DIR__ . '/controllers/loginUser.php'; 
    exit();
}

// Logout Route
if ($request_uri === $base_path . '/logout' && $request_method === 'POST') {
    require __DIR__ . '/controllers/logoutUser.php';
    exit();
}


// Profile Update Route
if ($request_uri === $base_path . '/update/user' && $request_method === 'POST') {
    require __DIR__ . '/controllers/updateUser.php';
    exit();
}

// Verify Email
if ($request_uri === $base_path. '/verify/email' && $request_method === 'POST') {
    require __DIR__. '/verification/verifyEmail.php';
    exit();
}

// Get User Profile Route
if ($request_uri === $base_path . '/user/profile' && $request_method === 'GET') {
    require __DIR__ . '/controllers/getUserProfile.php';
    exit();
}


// If no route matches, return 404S
http_response_code(404);
echo json_encode(['message' => 'Endpoint not found']);