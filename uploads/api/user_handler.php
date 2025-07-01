<?php
require_once "../config.php";
require_once "../models/User.php";

header('Content-Type: application/json');

$user = new User($conn);

// Handle AJAX Create
if (isset($_POST['create'])) {
    $newPhoto = $user->create($_POST, $_FILES['profile_photo']);
    echo json_encode(['status' => $newPhoto ? 'success' : 'error', 'newPhoto' => $newPhoto]);
    exit;
}

// Handle AJAX Update
if (isset($_POST['update'])) {
    $newPhoto = $user->update($_POST['id'], $_POST, $_FILES['profile_photo']);
    echo json_encode(['status' => $newPhoto !== false ? 'success' : 'error', 'newPhoto' => $newPhoto]);
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

// Default response
echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
