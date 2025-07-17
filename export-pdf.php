<?php
require 'vendor/autoload.php';
require_once "config.php";
require_once "User.php";

use Dompdf\Dompdf;

function resizeImageBase64($path, $width = 50, $height = 50) {
    $src = imagecreatefromstring(file_get_contents($path));
    $dst = imagecreatetruecolor($width, $height);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));
    ob_start();
    imagejpeg($dst, null, 75);
    $data = ob_get_clean();
    imagedestroy($src);
    imagedestroy($dst);
    return 'data:image/jpeg;base64,' . base64_encode($data);
}

$user = new User($conn);
$response = $user->getAllUsers();
$allUsers = $response['data'];

// Build rows for the HTML table
$rowsHtml = '';
foreach ($allUsers as $u) {
    $imagePath = "uploads/" . $u['profile_photo'];
    if (file_exists($imagePath)) {
        $imgTag = '<img src="' . resizeImageBase64($imagePath) . '" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">';
    } else {
        $imgTag = 'N/A';
    }

    $rowsHtml .= <<<HTML
        <tr>
            <td>{$u['id']}</td>
            <td>{$imgTag}</td>
            <td>{$u['name']}</td>
            <td>{$u['email']}</td>
            <td>{$u['phone']}</td>
            <td>{$u['age']}</td>
            <td>{$u['gender']}</td>
            <td>{$u['address']}</td>
            <td>{$u['salary']}</td>
        </tr>
    HTML;
}

// Load and capture the HTML template
ob_start();
include __DIR__ . "/templates/user-list-template.php";
$html = ob_get_clean();

// Generate the PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream("user_list.pdf", ["Attachment" => false]);
exit;
