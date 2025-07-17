<?php
function uploadPhoto($photo, $oldPhoto = null, $uploadDir = "uploads/")
{
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $mimeType = mime_content_type($photo['tmp_name']);

    if (!in_array($mimeType, $allowedTypes)) return false;
    if ($photo['size'] > 2 * 1024 * 1024) return false;

    $fileName = uniqid() . "_" . basename($photo['name']);
    $targetPath = $uploadDir . $fileName;

    if (move_uploaded_file($photo['tmp_name'], $targetPath)) {
        if ($oldPhoto && file_exists($uploadDir . $oldPhoto)) {
            unlink($uploadDir . $oldPhoto);
        }
        return $fileName;
    }

    return false;
}
