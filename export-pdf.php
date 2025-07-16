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

$html = '
<h2 style="text-align:center;">User List</h2>
<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Address</th>
            <th>Salary</th>
        </tr>
    </thead>
    <tbody>';

foreach ($allUsers as $u) {
    $imagePath = "uploads/" . $u['profile_photo'];

    if (file_exists($imagePath)) {
        $imageData = base64_encode(file_get_contents($imagePath));
        $imageMime = mime_content_type($imagePath);
        $imgTag = '<img src="' . resizeImageBase64($imagePath) . '" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">';
    } else {
        $imgTag = 'N/A';
    }

    $html .= "<tr>
        <td>{$u['id']}</td>
        <td>{$imgTag}</td>
        <td>{$u['name']}</td>
        <td>{$u['email']}</td>
        <td>{$u['phone']}</td>
        <td>{$u['age']}</td>
        <td>{$u['gender']}</td>
        <td>{$u['address']}</td>
        <td>{$u['salary']}</td>
    </tr>";
}

$html .= '</tbody></table>';

$dompdf = new Dompdf();
$dompdf->loadHtml($html);
$dompdf->setPaper('A4');
$dompdf->render();
file_put_contents("debug.html", $html);
$dompdf->stream("user_list.pdf", ["Attachment" => false]);
exit;
