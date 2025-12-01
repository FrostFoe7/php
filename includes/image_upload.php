<?php

function ensureUploadDirExists(string $dir = IMAGE_UPLOAD_DIR): void
{
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

function getImageExtensionFromMime(string $mime): string
{
    if ($mime === 'image/jpeg') {
        return 'jpg';
    }

    if ($mime === 'image/png') {
        return 'png';
    }

    return 'bin';
}

function storeUploadedImage(array $file): string
{
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('No valid uploaded file found.');
    }

    if ($file['size'] > MAX_IMAGE_UPLOAD_BYTES) {
        throw new RuntimeException('Image must be 100 KB or smaller.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    if (!$imageInfo || !in_array($imageInfo['mime'], ALLOWED_IMAGE_MIME_TYPES, true)) {
        throw new RuntimeException('Only JPEG and PNG images are supported.');
    }

    ensureUploadDirExists(IMAGE_UPLOAD_DIR);

    $extension = getImageExtensionFromMime($imageInfo['mime']);
    $filename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = IMAGE_UPLOAD_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Unable to save the uploaded image.');
    }

    return $filename;
}

function uploadImageFromInput(string $fieldName): ?string
{
    if (!isset($_FILES[$fieldName])) {
        return null;
    }

    $file = $_FILES[$fieldName];
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Image upload failed (error code ' . $file['error'] . ').');
    }

    return storeUploadedImage($file);
}

function deleteUploadedImage(?string $filename): void
{
    if (!$filename) {
        return;
    }

    $sanitized = basename($filename);
    $path = IMAGE_UPLOAD_DIR . '/' . $sanitized;
    if (file_exists($path)) {
        @unlink($path);
    }
}

function manageImageUpload(string $fieldName, ?string $existingFilename = null, bool $removeExisting = false): ?string
{
    $newFilename = uploadImageFromInput($fieldName);
    if ($newFilename !== null) {
        if ($existingFilename) {
            deleteUploadedImage($existingFilename);
        }
        return $newFilename;
    }

    if ($removeExisting && $existingFilename) {
        deleteUploadedImage($existingFilename);
        return null;
    }

    return $existingFilename;
}

function getUploadedImageUrl(?string $filename): ?string
{
    if (!$filename) {
        return null;
    }

    $relativePath = '/' . ltrim(rtrim(IMAGE_UPLOAD_URL, '/') . '/' . ltrim($filename, '/'), '/');
    return buildFullUrl($relativePath);
}

function buildFullUrl(string $path): string
{
    $host = $_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? 'localhost');
    $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $port = $_SERVER['SERVER_PORT'] ?? null;
    $scheme = $https || $port === '443' ? 'https' : 'http';
    return $scheme . '://' . $host . '/' . ltrim($path, '/');
}
