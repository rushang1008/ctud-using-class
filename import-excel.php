<?php
session_start();
require_once "config.php";
require_once "User.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');

$user = new User($conn);

// Check if file is uploaded
if ( empty($_FILES['excel_file']['tmp_name'])) {
    sendError("Please upload a valid Excel file.");
}

$file = $_FILES['excel_file'];
$uploadDir = __DIR__ . '/uploads/excels/';
$fileName = time() . '_' . basename($file['name']);
$savePath = $uploadDir . $fileName;

if (!move_uploaded_file($file['tmp_name'], $savePath)) {
    sendError("Failed to upload Excel file.");
}

$uploadedBy = $_SESSION['name'] ?? 'Unknown';
$now = date('Y-m-d H:i:s');
$conn->query("INSERT INTO excel_uploads (filename, uploaded_by, uploaded_at) VALUES ('$fileName', '$uploadedBy', '$now')");

try {
    // Load Excel file
    $spreadsheet = IOFactory::load($savePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();

    // Check headers
    $expectedHeaders = ['Name', 'Email', 'Phone', 'Age', 'Salary', 'Address', 'Gender', 'Profile Photo'];
    $headers = array_map('strtolower', array_map('trim', $rows[0]));
    if ($headers !== array_map('strtolower', $expectedHeaders)) {
        sendError("Excel headers must be: " . implode(', ', $expectedHeaders));
    }

    // Remove header row
    array_shift($rows);

    $inserted = 0;
    $updated = 0;

    foreach ($rows as $index => $row) {
        $rowNumber = $index + 2; // Excel row number
        $row = array_pad($row, 8, '');

        list($name, $email, $phone, $age, $salary, $address, $gender, $photo) = array_map('trim', $row);

        // Validate basic fields
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) sendError("Row $rowNumber: Invalid email.");
        if (!is_numeric($phone) || strlen($phone) < 8) sendError("Row $rowNumber: Invalid phone.");
        if (!is_numeric($age)) sendError("Row $rowNumber: Age must be a number.");
        if (!is_numeric($salary)) sendError("Row $rowNumber: Salary must be a number.");
        if (!in_array(strtolower($gender), ['male', 'female', 'other'])) sendError("Row $rowNumber: Invalid gender.");

        // Check if user exists
        $existing = $user->getUserByEmail($email) ?? $user->getByPhone($phone);

        // Prepare data
        $data = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'age' => (int)$age,
            'salary' => (float)$salary,
            'address' => $address,
            'gender' => ucfirst(strtolower($gender)),
            'profile_photo' => $photo
        ];

        // Insert or Update
        if ($existing) {
            $success = $user->update($existing['id'], $data, $photo ?: $existing['profile_photo']);
            if ($success) $updated++;
        } else {
            $user->create($data, null);
            $inserted++;
        }
    }

    // Send success response
    echo json_encode([
        'status' => 'success',
        'inserted' => $inserted,
        'updated' => $updated,
        'file' => $fileName,
        'uploaded_by' => $uploadedBy
    ]);

} catch (Exception $e) {
    sendError("Could not read Excel file: " . $e->getMessage());
}

// Helper to return error in JSON format and stop
function sendError($message)
{
    echo json_encode(['status' => 'error', 'message' => $message]);
    exit;
}
