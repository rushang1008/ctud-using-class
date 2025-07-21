<?php
session_start();
header('Content-Type: application/json');

require_once "config.php";
require_once "User.php";
$user = new User($conn);

$data = [];
$action = '';

// Detect JSON or multipart/form-data input
if (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $json = json_decode(file_get_contents("php://input"), true);
    $data = $json ?? [];
    $action = $data['action'] ?? '';
} elseif (!empty($_POST['data'])) {
    $data = json_decode($_POST['data'], true);
    $action = $data['action'] ?? '';
    if (!empty($_FILES['profile_photo']['name'])) {
        $data['profile_photo'] = $_FILES['profile_photo'];
    }
}

switch ($action) {
    case 'read':
        $search = $data['search'] ?? '';
        $sortBy = $data['sortBy'] ?? 'id';
        $sortDir = $data['sortDir'] ?? 'ASC';
        $page = isset($data['page']) ? (int)$data['page'] : 1;
        $limit = isset($data['limit']) ? (int)$data['limit'] : 10;
    
        $start = ($page - 1) * $limit;
    
        $result = $user->readAll($search, $sortBy, $sortDir, $start, $limit);
    
        $totalPages = ceil($result['recordsFiltered'] / $limit);
        //  echo '<pre>';
        // print_r($totalPages);die;
    
        echo json_encode([
            'status' => 'success',
            'message' => 'Users fetched successfully',
            'recordsTotal' => $result['recordsTotal'],
            'recordsFiltered' => $result['recordsFiltered'],
            'data' => $result['data'],
            'page' => $page,
            'totalPages' => $totalPages
        ]);
        break;
    
    

    case 'get_user':
        $id = $data['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
            exit;
        }

        $userData = $user->getUserById($id);
        echo json_encode($userData ?: ['status' => 'error', 'message' => 'User not found']);
        break;

    case 'create':
        $newId = $user->createuser($data, $_FILES['profile_photo'] ?? null);
        if ($newId['status'] === 'error') {
            echo json_encode($newId);
            exit;
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'User created successfully',
            'data' => $newId['user']
        ]);
        break;

    case 'update':
        $id = $data['id'] ?? null;

        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
            exit;
        }

        $existing_photo = $data['existing_photo'] ?? null;
        unset($data['id'], $data['existing_photo'], $data['action']);

        $file = $_FILES['profile_photo'] ?? null;

        $result = $user->updateuser($id, $data, $file, $existing_photo);
        echo json_encode($result);
        break;

    case 'delete':
        $user->deleteUser($data['id']);
        echo json_encode([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
        break;

    case 'login':
        $email = $data['email'] ?? '';
        $password = $data['password'] ?? '';
        if (!$email || !$password) {
            echo json_encode(['status' => 'error', 'message' => 'Email and password are required.']);
            exit;
        }

        $result = $user->login($email, $password);
        if ($result['status'] === 'success') {
            echo json_encode([
                'status' => 'success',
                'message' => 'Login successful',
                'name' => $result['name']
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
        }
        break;

    case 'logout':
        session_destroy();
        echo json_encode([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
        break;

    default:
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid action: ' . htmlspecialchars($action)
        ]);
        break;
}
