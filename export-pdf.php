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

// Fetch data
$user = new User($conn);
$response = $user->getAllUsers();
$allUsers = $response['data'];

// Make $allUsers available to the template
ob_start();
include __DIR__ . "/templates/user-list-template.php";
$html = ob_get_clean();

// Create PDF
$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
$dompdf->stream("user_list.pdf", ["Attachment" => false]);
exit;
