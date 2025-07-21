<?php
session_start();
header('Content-Type: application/json');

require_once "config.php";
require_once "User.php";
$user = new User($conn);

$data = [];
$action = '';

// Determine the input format (JSON or multipart/form-data)
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
        $allUsers = $user->readAll();
        echo json_encode([
            'status' => 'success',
            'message' => 'Users fetched successfully',
            'data' => $allUsers
        ]);
        break;
        case 'get_user':
            $id = $data['id'] ?? null;
        
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
                exit;
            }
        
            $userData = $user->getUserById($id);
        
            if (!$userData) {
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
            } else {
                echo json_encode($userData);  
            }
            break;
        

        case 'create':
            $newId = $user->createuser($data, $_FILES['profile_photo'] ?? null);
            if ($newId['status'] === 'error') {
                echo json_encode($newId);
                exit;
            }
        
            $newUser = $newId['user']; 
            echo json_encode([
                'status' => 'success',
                'message' => 'User created successfully',
                'data' => $newUser
            ]);
            break;
        

        case 'update':
            if (empty($_POST['data'])) {
                echo json_encode(['status' => 'error', 'message' => 'Missing form data']);
                exit;
            }
        
            $postData = json_decode($_POST['data'], true);
            $id = $postData['id'] ?? null;
        
            if (!$id) {
                echo json_encode(['status' => 'error', 'message' => 'Missing user ID']);
                exit;
            }
        
            $existing_photo = $postData['existing_photo'] ?? null;
            unset($postData['id'], $postData['existing_photo'], $postData['action']);
        
            $file = $_FILES['profile_photo'] ?? null;
        
            $result = $user->updateuser($id, $postData, $file, $existing_photo);
            echo json_encode($result);
            break;
        
        
    case 'delete':
        $user->deleteUser($data['id']);
        echo json_encode([
            'status' => 'success',
            'message' => 'User deleted successfully'
        ]);
        break;

    case 'get_user':
        $userData = $user->getUserById($data['id']);
        echo json_encode($userData);
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
            'message' => 'Invalid action'
        ]);
        break;
}