<?php
session_start();
echo json_encode([
    'logged_in' => isset($_SESSION['logged_in']),
    'user_name' => $_SESSION['name'] ?? null
]);

