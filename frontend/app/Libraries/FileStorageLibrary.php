<?php

namespace App\Libraries;

/**
 * File Storage Library
 * Supports local disk storage (default) with MinIO/S3 hooks ready.
 */
class FileStorageLibrary
{
    private string $driver;
    private string $basePath;
    private string $baseUrl;

    // MinIO/S3 config (set via .env)
    private string $minioEndpoint;
    private string $minioKey;
    private string $minioSecret;
    private string $minioBucket;

    public function __construct()
    {
        $this->driver        = env('STORAGE_DRIVER', 'local'); // 'local' | 'minio'
        $this->basePath      = WRITEPATH . 'uploads/';
        $this->baseUrl       = env('APP_URL', 'http://localhost') . '/uploads/';
        $this->minioEndpoint = env('MINIO_ENDPOINT', '');
        $this->minioKey      = env('MINIO_ACCESS_KEY', '');
        $this->minioSecret   = env('MINIO_SECRET_KEY', '');
        $this->minioBucket   = env('MINIO_BUCKET', 'securecomm');
    }

    /**
     * Store an uploaded file.
     * @param \CodeIgniter\HTTP\Files\UploadedFile $file
     * @param string $directory  e.g. 'images', 'videos', 'audio', 'documents'
     * @return array{path: string, url: string, filename: string, size: int, mime: string}
     */
    public function store($file, string $directory = 'files'): array
    {
        $filename = $this->generateFilename($file->getExtension());
        $dir      = rtrim($directory, '/') . '/';

        if ($this->driver === 'minio') {
            return $this->storeMinIO($file, $dir . $filename);
        }

        return $this->storeLocal($file, $dir, $filename);
    }

    public function delete(string $path): bool
    {
        if ($this->driver === 'minio') {
            return $this->deleteMinIO($path);
        }

        $fullPath = $this->basePath . ltrim($path, '/');
        if (file_exists($fullPath)) {
            return unlink($fullPath);
        }
        return false;
    }

    public function getUrl(string $path): string
    {
        if ($this->driver === 'minio') {
            return $this->minioEndpoint . '/' . $this->minioBucket . '/' . ltrim($path, '/');
        }
        return $this->baseUrl . ltrim($path, '/');
    }

    public function storeEncrypted($file, string $directory, EncryptionLibrary $enc): array
    {
        // Store temp, encrypt, then persist encrypted version
        $tmpPath  = sys_get_temp_dir() . '/' . uniqid('enc_', true);
        $file->move(dirname($tmpPath), basename($tmpPath));

        $filename    = $this->generateFilename('enc');
        $dir         = $this->basePath . 'encrypted/' . $directory . '/';
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $destPath = $dir . $filename;

        $metadata = $enc->encryptFile($tmpPath, $destPath);
        @unlink($tmpPath);

        return [
            'path'     => 'encrypted/' . $directory . '/' . $filename,
            'url'      => null, // encrypted files are never directly served
            'filename' => $filename,
            'size'     => filesize($destPath),
            'mime'     => $file->getMimeType(),
            'meta'     => $metadata,
        ];
    }

    // ─────────────────────────────────────────────
    // Local storage
    // ─────────────────────────────────────────────

    private function storeLocal($file, string $dir, string $filename): array
    {
        $fullDir = $this->basePath . $dir;
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $file->move($fullDir, $filename);

        return [
            'path'     => $dir . $filename,
            'url'      => $this->baseUrl . $dir . $filename,
            'filename' => $filename,
            'size'     => filesize($fullDir . $filename),
            'mime'     => $file->getMimeType(),
        ];
    }

    // ─────────────────────────────────────────────
    // MinIO (S3-compatible) via cURL
    // ─────────────────────────────────────────────

    private function storeMinIO($file, string $objectKey): array
    {
        // Build S3 PUT request manually (no SDK needed)
        $content   = file_get_contents($file->getTempName());
        $mime      = $file->getMimeType();
        $date      = gmdate('D, d M Y H:i:s T');
        $md5       = base64_encode(md5($content, true));
        $stringToSign = "PUT\n{$md5}\n{$mime}\n{$date}\n/{$this->minioBucket}/{$objectKey}";
        $sig          = base64_encode(hash_hmac('sha1', $stringToSign, $this->minioSecret, true));
        $auth         = "AWS {$this->minioKey}:{$sig}";
        $url          = "{$this->minioEndpoint}/{$this->minioBucket}/{$objectKey}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $content,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "Authorization: {$auth}",
                "Content-Type: {$mime}",
                "Content-MD5: {$md5}",
                "Date: {$date}",
                "Content-Length: " . strlen($content),
            ],
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 && $httpCode !== 204) {
            throw new \RuntimeException("MinIO upload failed: HTTP {$httpCode} — {$response}");
        }

        return [
            'path'     => $objectKey,
            'url'      => $url,
            'filename' => basename($objectKey),
            'size'     => strlen($content),
            'mime'     => $mime,
        ];
    }

    private function deleteMinIO(string $path): bool
    {
        $date         = gmdate('D, d M Y H:i:s T');
        $stringToSign = "DELETE\n\n\n{$date}\n/{$this->minioBucket}/{$path}";
        $sig          = base64_encode(hash_hmac('sha1', $stringToSign, $this->minioSecret, true));
        $auth         = "AWS {$this->minioKey}:{$sig}";
        $url          = "{$this->minioEndpoint}/{$this->minioBucket}/{$path}";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'DELETE',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ["Authorization: {$auth}", "Date: {$date}"],
        ]);
        curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $code === 204 || $code === 200;
    }

    private function generateFilename(string $ext): string
    {
        return bin2hex(random_bytes(16)) . ($ext ? ".{$ext}" : '');
    }
}
