<?php
session_start();
require_once "config.php";
require_once "User.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json');
$user = new User($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['excel_file']['tmp_name'])) {
    $fileTmpPath = $_FILES['excel_file']['tmp_name'];
    $originalName = $_FILES['excel_file']['name'];

    // Save uploaded Excel file
    $uploadDir = __DIR__ . '/uploads/excels/';
    $savedFileName = time() . '_' . basename($originalName);
    $savedFilePath = $uploadDir . $savedFileName;

    if (!move_uploaded_file($fileTmpPath, $savedFilePath)) {
        responseError("Failed to save uploaded Excel file.");
    }

    // ✅ Log upload in DB
    $uploadedBy = $_SESSION['name'] ?? 'Unknown';
    $uploadedAt = date('Y-m-d H:i:s');

    $stmt = $conn->prepare("INSERT INTO excel_uploads (filename, uploaded_by, uploaded_at) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $savedFileName, $uploadedBy, $uploadedAt);
    $stmt->execute();

    // ✅ Parse and process Excel
    try {
        $spreadsheet = IOFactory::load($savedFilePath);
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

        // ✅ Remove duplicates based on email or phone
        $seen = [];
        $uniqueRows = [];

        foreach (array_reverse($rows) as $row) {
            $row = array_pad(array_map(fn($val) => is_null($val) ? '' : trim((string)$val), $row), count($expectedHeaders), '');
            [$name, $email, $phone] = $row;

            $key = strtolower($email) . '|' . $phone;

            if (!isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueRows[] = $row;
            }
        }

        $rows = array_reverse($uniqueRows); // Keep original order
        $duplicatesRemoved = count($sheet->toArray()) - 1 - count($rows); // subtract header

        $inserted = 0;
        $updated = 0;

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2;
            $row = array_pad($row, count($expectedHeaders), '');
            [$name, $email, $phone, $age, $salary, $address, $gender, $profile_photo] = $row;

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responseError("Row $rowNumber: Invalid email format ($email)");
            if (!is_numeric($phone) || strlen($phone) < 8) responseError("Row $rowNumber: Invalid phone ($phone)");
            if (!is_numeric($age)) responseError("Row $rowNumber: Age must be a number");
            if (!is_numeric($salary)) responseError("Row $rowNumber: Salary must be a number");
            if (!in_array(strtolower($gender), ['male', 'female', 'other'])) responseError("Row $rowNumber: Invalid gender");

            $userByEmail = $user->getUserByEmail($email);
            $userByPhone = $user->getByPhone($phone);

            if ($userByEmail && $userByPhone && $userByEmail['id'] === $userByPhone['id']) {
                $existing = $userByEmail;
            } elseif ($userByEmail) {
                $existing = $userByEmail;
            } elseif ($userByPhone) {
                $existing = $userByPhone;
            } elseif ($userByEmail && $userByPhone && $userByEmail['id'] !== $userByPhone['id']) {
                responseError("Row $rowNumber: Email ($email) and Phone ($phone) belong to different users. Conflict found.");
            } else {
                $existing = null;
            }

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
                    responseError("Row $rowNumber: Failed to update user");
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
            'updated' => $updated,
            'duplicates_removed' => $duplicatesRemoved,
            'file' => $savedFileName,
            'uploaded_by' => $uploadedBy
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
