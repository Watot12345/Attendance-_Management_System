<?php
/**
 * Supabase Storage Service — includes/core/SupabaseStorage.php
 * Handles direct file uploads to Supabase Storage REST API.
 */

require_once __DIR__ . '/Database.php';

class SupabaseStorage {
    private static ?string $supabaseUrl = null;
    private static ?string $supabaseKey = null;
    private static ?string $supabaseBucket = null;

    /**
     * Initialize environment configuration
     */
    private static function init(): void {
        if (self::$supabaseUrl === null) {
            // Ensure Database::getConnection() has loaded .env
            Database::getConnection();

            self::$supabaseUrl = rtrim(getenv('SUPABASE_URL') ?: '', '/');
            self::$supabaseKey = getenv('SUPABASE_KEY') ?: getenv('SUPABASE_ANNON_KEY') ?: '';
            self::$supabaseBucket = getenv('SUPABASE_BUCKET') ?: 'documents';
        }
    }

    /**
     * Upload a local or temp file to Supabase Storage
     *
     * @param string $tmpFilePath Local path of the uploaded file
     * @param string $originalFilename Original name of the uploaded file
     * @param string|null $mimeType MIME type (e.g. image/png, image/jpeg, application/pdf)
     * @param string $folder Subfolder inside the bucket (e.g. 'excuses')
     * @return array Result containing success status, public URL, or error details
     */
    public static function upload(string $tmpFilePath, string $originalFilename, ?string $mimeType = null, string $folder = 'excuses'): array {
        self::init();

        if (empty(self::$supabaseUrl) || empty(self::$supabaseKey)) {
            return [
                'success' => false,
                'error'   => 'Supabase URL or API Key is not configured in .env',
            ];
        }

        if (!file_exists($tmpFilePath) || !is_readable($tmpFilePath)) {
            return [
                'success' => false,
                'error'   => 'Temporary upload file not found or unreadable.',
            ];
        }

        // Determine MIME type if not provided
        if (empty($mimeType) || $mimeType === 'application/octet-stream') {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_file($finfo, $tmpFilePath);
            finfo_close($finfo);
            $mimeType = $detectedMime ?: 'image/jpeg';
        }

        // Generate sanitized unique remote filename
        $ext = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        if (empty($ext)) {
            $ext = match ($mimeType) {
                'image/png'  => 'png',
                'image/jpeg' => 'jpg',
                'image/webp' => 'webp',
                'application/pdf' => 'pdf',
                default => 'jpg',
            };
        }

        $cleanBase = preg_replace('/[^a-zA-Z0-9_-]/', '_', pathinfo($originalFilename, PATHINFO_FILENAME));
        $cleanBase = substr($cleanBase, 0, 30);
        $random = bin2hex(random_bytes(6));
        $remoteFilename = time() . "_{$cleanBase}_{$random}.{$ext}";
        $folder = trim($folder, '/');
        $remotePath = $folder !== '' ? "{$folder}/{$remoteFilename}" : $remoteFilename;

        $fileData = file_get_contents($tmpFilePath);
        if ($fileData === false) {
            return [
                'success' => false,
                'error'   => 'Failed to read uploaded file contents.',
            ];
        }

        $endpoint = self::$supabaseUrl . "/storage/v1/object/" . self::$supabaseBucket . "/{$remotePath}";

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fileData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: " . self::$supabaseKey,
            "Authorization: Bearer " . self::$supabaseKey,
            "Content-Type: {$mimeType}",
            "x-upsert: true"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'error'   => "cURL error during upload: {$curlError}",
            ];
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            $publicUrl = self::$supabaseUrl . "/storage/v1/object/public/" . self::$supabaseBucket . "/{$remotePath}";
            return [
                'success'    => true,
                'url'        => $publicUrl,
                'path'       => $remotePath,
                'bucket'     => self::$supabaseBucket,
                'mime'       => $mimeType,
                'size'       => strlen($fileData),
            ];
        }

        $decoded = json_decode($response, true);
        $errMsg = $decoded['message'] ?? $decoded['error'] ?? "Upload failed with HTTP {$httpCode}: {$response}";

        return [
            'success' => false,
            'error'   => $errMsg,
            'code'    => $httpCode,
            'raw'     => $response,
        ];
    }

    /**
     * Get the public URL for a given storage path
     */
    public static function getPublicUrl(string $remotePath): string {
        self::init();
        $remotePath = ltrim($remotePath, '/');
        return self::$supabaseUrl . "/storage/v1/object/public/" . self::$supabaseBucket . "/{$remotePath}";
    }

    /**
     * Delete an object from Supabase Storage by path or public URL
     */
    public static function delete(string $pathOrUrl): array {
        self::init();

        if (empty(self::$supabaseUrl) || empty(self::$supabaseKey)) {
            return ['success' => false, 'error' => 'Supabase URL or Key not configured.'];
        }

        // Extract relative storage path if full URL was provided
        $remotePath = $pathOrUrl;
        $bucketPrefix = "/storage/v1/object/public/" . self::$supabaseBucket . "/";
        if (str_contains($remotePath, $bucketPrefix)) {
            $parts = explode($bucketPrefix, $remotePath, 2);
            $remotePath = $parts[1] ?? $remotePath;
        }

        $remotePath = ltrim($remotePath, '/');
        $endpoint = self::$supabaseUrl . "/storage/v1/object/" . self::$supabaseBucket;

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['prefixes' => [$remotePath]]));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "apikey: " . self::$supabaseKey,
            "Authorization: Bearer " . self::$supabaseKey,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [
            'success' => ($httpCode >= 200 && $httpCode < 300),
            'status'  => $httpCode,
            'raw'     => $response
        ];
    }
}

