<?php
// core/Storage.php - Supabase Cloud Storage Helper

class Storage {
    /**
     * Upload berkas binary ke Supabase Storage Bucket
     * Mengembalikan Public URL jika sukses, atau null jika gagal
     */
    public static function upload(string $bucket, string $path, string $binaryContent, string $mimeType = 'image/webp'): ?string {
        $supabaseUrl = rtrim(defined('SUPABASE_URL') ? SUPABASE_URL : (getenv('SUPABASE_URL') ?: ''), '/');
        $serviceKey = defined('SUPABASE_SERVICE_ROLE_KEY') ? SUPABASE_SERVICE_ROLE_KEY : (getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');

        if (empty($supabaseUrl) || empty($serviceKey)) {
            return null;
        }

        $url = "{$supabaseUrl}/storage/v1/object/{$bucket}/" . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $binaryContent,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                "Authorization: Bearer {$serviceKey}",
                "apikey: {$serviceKey}",
                "Content-Type: {$mimeType}",
                "x-upsert: true"
            ]
        ]);

        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code >= 200 && $code < 300) {
            return "{$supabaseUrl}/storage/v1/object/public/{$bucket}/" . ltrim($path, '/');
        }

        return null;
    }
}

