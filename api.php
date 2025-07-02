<?php
require_once "config.php";
require_once "User.php";

header('Content-Type: application/json');

$user = new User($conn);

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

    $newPhoto = $user->create($_POST, $_FILES['profile_photo']);
    echo json_encode(['status' => $newPhoto ? 'success' : 'error', 'newPhoto' => $newPhoto]);
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
    echo json_encode(['status' => $newPhoto ? 'success' : 'error', 'newPhoto' => $newPhoto]);
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
