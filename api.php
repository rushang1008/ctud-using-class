<?php
require_once "config.php";
require_once "User.php";

header('Content-Type: application/json');

$user = new User($conn);
session_start();
if (isset($_POST['logout'])) {
    session_destroy();
    echo json_encode(['status' => 'logged_out']);
    exit;
}

if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $loggedInUser = $user->getByEmail($email);

    if ($loggedInUser && password_verify($password, $loggedInUser['password'])) {
        $_SESSION['logged_in'] = true;
        $_SESSION['name'] = $loggedInUser['name']; // save name
        echo json_encode(['status' => 'success', 'name' => $loggedInUser['name']]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email or password']);
    }
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['read'])) {
    $users = $user->readAll();
    echo json_encode(['data' => $users]);
    exit;
}
// Handle AJAX Create
if (isset($_POST['create'])) {
    if ($user->emailExists($_POST['email'])) {
        echo json_encode(['status' => 'error', 'field' => 'email', 'message' => 'Email already exists']);
        exit;
    }
    if ($user->phoneExists($_POST['phone'])) {
        echo json_encode(['status' => 'error', 'field' => 'phone', 'message' => 'Phone already exists']);
        exit;
    }

    $newId = $user->create($_POST, $_FILES['profile_photo']);
    if ($newId) {
        $createdUser = $user->get($newId);
        echo json_encode([
            'status' => 'success',
            'user' => $createdUser
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Handle AJAX Update
if (isset($_POST['update'])) {
    $id = $_POST['id'];

    if ($user->emailExists($_POST['email'], $id)) {
        echo json_encode(['status' => 'error', 'field' => 'email', 'message' => 'Email already exists']);
        exit;
    }
    if ($user->phoneExists($_POST['phone'], $id)) {
        echo json_encode(['status' => 'error', 'field' => 'phone', 'message' => 'Phone already exists']);
        exit;
    }

    $newPhoto = $user->update($id, $_POST, $_FILES['profile_photo'] ?? null);
    if ($newPhoto !== false) {
        $updatedUser = $user->get($id);
        echo json_encode([
            'status' => 'success',
            'user' => $updatedUser,
            'newPhoto' => $newPhoto
        ]);
    } else {
        echo json_encode(['status' => 'error']);
    }
    exit;
}

// Handle AJAX Delete
if (isset($_POST['delete'])) {
    $result = $user->delete($_POST['id']);
    echo json_encode(['status' => $result ? 'success' : 'error']);
    exit;
}

// Get user data for editing
if (isset($_GET['get_user_id'])) {
    echo json_encode($user->get($_GET['get_user_id']));
    exit;
}
