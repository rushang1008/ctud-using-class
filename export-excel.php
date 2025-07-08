<?php
require_once "config.php";
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Header Row
$sheet->fromArray(
    [ 'Name', 'Email', 'Phone', 'Age', 'Salary', 'Address', 'Gender','Profile Photo'],
    NULL, 'A1'
);

// Fetch Data
$sql = "SELECT  name, email, phone, age, salary, address, gender,profile_photo FROM users";
$result = $conn->query($sql);
$rowNum = 2;

while ($user = $result->fetch_assoc()) {
    $sheet->fromArray(array_values($user), NULL, "A$rowNum");
    $rowNum++;
}

// Output Excel
header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=users.xlsx");
header("Cache-Control: max-age=0");

$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;
?>
    