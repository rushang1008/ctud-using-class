<?php
require_once "config.php";
require_once "User.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');
$user = new User($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file']['tmp_name'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    try {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        if (empty($rows) || !is_array($rows[0])) {
            responseError("Excel file is empty or not formatted properly");
        }

        $expectedHeaders = ['Name', 'Email', 'Phone', 'Age', 'Salary', 'Address', 'Gender', 'Profile Photo'];
        $receivedHeaders = array_map(fn($h) => strtolower(trim((string)$h)), $rows[0]);
        $expectedLower = array_map('strtolower', $expectedHeaders);

        if ($receivedHeaders !== $expectedLower) {
            responseError("Invalid headers. Expected: " . implode(', ', $expectedHeaders));
        }

        array_shift($rows); // Remove header row
        $inserted = 0;
        $updated = 0;

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // Excel row number for error reporting
            $row = array_map(fn($val) => is_null($val) ? '' : trim((string)$val), $row);
            $row = array_pad($row, count($expectedHeaders), '');

            if (count(array_filter($row)) === 0) continue;

            [$name, $email, $phone, $age, $salary, $address, $gender, $profile_photo] = $row;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responseError("Row $rowNumber: Invalid email format ($email)");
            if (!is_numeric($phone) || strlen($phone) < 8) responseError("Row $rowNumber: Invalid phone ($phone)");
            if (!is_numeric($age)) responseError("Row $rowNumber: Age must be a number");
            if (!is_numeric($salary)) responseError("Row $rowNumber: Salary must be a number");
            if (!in_array(strtolower($gender), ['male', 'female', 'other'])) responseError("Row $rowNumber: Invalid gender");

            $existing = $user->getUserByEmail($email);

            if ($existing) {
                $updatedSuccess = $user->update($existing['id'], [
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'age' => (int)$age,
                    'salary' => (float)$salary,
                    'address' => $address,
                    'gender' => ucfirst(strtolower($gender)),
                    'existing_photo' => $existing['profile_photo'] ?? '',
                    'profile_photo' => $profile_photo
                ], null);

                if ($updatedSuccess !== false) {
                    $updated++;
                } else {
                    responseError("Row $rowNumber: Failed to update user with email $email");
                }
            } else {
                $createdId = $user->create([
                    'name' => $name,
                    'email' => $email,
                    'phone' => $phone,
                    'age' => (int)$age,
                    'salary' => (float)$salary,
                    'address' => $address,
                    'gender' => ucfirst(strtolower($gender)),
                    'profile_photo' => $profile_photo
                ], null);

                if ($createdId !== false) {
                    $inserted++;
                } else {
                    responseError("Row $rowNumber: Failed to insert user ($email)");
                }
            }
        }

        echo json_encode([
            'status' => 'success',
            'inserted' => $inserted,
            'updated' => $updated
        ]);
    } catch (Exception $e) {
        responseError("Invalid Excel file: " . $e->getMessage());
    }
} else {
    responseError("No file uploaded");
}

function responseError($msg) {
    echo json_encode(['status' => 'error', 'field' => 'excel_file', 'message' => $msg]);
    exit;
}
