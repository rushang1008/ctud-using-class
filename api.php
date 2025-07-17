<?php
require_once "config.php";
require_once "User.php";

header('Content-Type: application/json');
session_start();

$user = new User($conn);

/** Helper: Send JSON response and stop */
function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

// JSON requests: application/json
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    !empty($_SERVER['CONTENT_TYPE']) &&
    strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? null;

    if (!$action) {
        respond(['status' => 'error', 'message' => 'Missing action']);
    }

    switch ($action) {
        case 'login':
            respond($user->login($input['email'] ?? '', $input['password'] ?? ''));
        case 'logout':
            respond($user->logout());
        case 'read':
            respond($user->getAllUsers());
        case 'delete':
            respond($user->deleteUser($input['id'] ?? null));
        case 'get_user':
            respond($user->getUserById($input['id'] ?? null));
        default:
            respond(['status' => 'error', 'message' => 'Invalid action']);
    }
}

// Multipart/form-data requests: used for file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
    $file = $_FILES['profile_photo'] ?? null;
    $action = $data['action'] ?? null;

    if (!$action) {
        respond(['status' => 'error', 'message' => 'Missing form action']);
    }

    switch ($action) {
        case 'create':
            respond($user->createUser($data, $file));
        case 'update':
            respond($user->updateUser($data, $file));
        default:
            respond(['status' => 'error', 'message' => 'Invalid form action']);
    }
}

// GET request for user by ID
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_user_id') {
    $id = $_GET['id'] ?? null;
    respond($user->getUserById($id));
}