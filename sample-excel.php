<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Define headers
$headers = ['Name', 'Email', 'Phone', 'Age', 'Salary', 'Address', 'Gender', 'Profile Photo'];

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set headers
$sheet->fromArray($headers, null, 'A1');

// Set response headers
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="sample_users.xlsx"');
header('Cache-Control: max-age=0');

// Output Excel file
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
