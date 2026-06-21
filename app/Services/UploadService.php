<?php

declare(strict_types=1);

namespace App\Services;

final class UploadService
{
    private const ALLOWED = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    public function store(array $file, string $directory = ''): ?string
    {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('Upload failed.');
        }

        $maxBytes = ((int) config('security.max_upload_mb', 2)) * 1024 * 1024;
        if ((int) $file['size'] > $maxBytes) {
            throw new \RuntimeException('The uploaded file is too large.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!isset(self::ALLOWED[$mime])) {
            throw new \RuntimeException('Unsupported file type.');
        }

        $safeDirectory = trim(preg_replace('/[^a-zA-Z0-9_-]/', '', $directory), '/');
        $targetDirectory = PUBLIC_PATH . '/uploads' . ($safeDirectory ? '/' . $safeDirectory : '');
        if (!is_dir($targetDirectory)) {
            mkdir($targetDirectory, 0755, true);
        }

        $name = bin2hex(random_bytes(18)) . '.' . self::ALLOWED[$mime];
        $target = $targetDirectory . '/' . $name;
        if (!move_uploaded_file($file['tmp_name'], $target)) {
            throw new \RuntimeException('Could not store uploaded file.');
        }

        return ($safeDirectory ? $safeDirectory . '/' : '') . $name;
    }
}
