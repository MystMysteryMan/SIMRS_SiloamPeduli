<?php
// config/config.php - Konfigurasi Utama SIMRS Siloam Peduli (MVC)

// Load .env jika file lokal tersedia
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

// Konfigurasi Database Supabase PostgreSQL
define('DB_HOST', getenv('DB_HOST') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '6543');
define('DB_NAME', getenv('DB_NAME') ?: 'postgres');
define('DB_USER', getenv('DB_USER') ?: '');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_SSLMODE', getenv('DB_SSLMODE') ?: 'require');

define('SUPABASE_URL', getenv('SUPABASE_URL') ?: '');
define('SUPABASE_KEY', getenv('SUPABASE_KEY') ?: '');
define('SUPABASE_SERVICE_ROLE_KEY', getenv('SUPABASE_SERVICE_ROLE_KEY') ?: '');

// Session handler berbasis Supabase PostgreSQL untuk arsitektur Serverless Vercel
if (session_status() === PHP_SESSION_NONE) {
    require_once dirname(__DIR__) . '/core/PostgresSessionHandler.php';
    $sessionHandler = new PostgresSessionHandler();
    session_set_save_handler($sessionHandler, true);
    session_start();
}

define('APP_NAME', 'SIMRS Siloam Peduli');
define('APP_VERSION', '5.1.0');

/**
 * Base URL dinamis kompatibel dengan Apache Laragon, PHP Built-in Server, dan Vercel/Cloudflare Edge
 */
function base_url(string $path = ''): string {
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }

    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
        || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443);

    $protocol = $isHttps ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    $subDir = '';
    if (!empty($docRoot) && str_starts_with($appDir, $docRoot)) {
        $subDir = substr($appDir, strlen($docRoot));
    }
    $subDir = trim($subDir, '/');
    $base = $protocol . $host . ($subDir ? '/' . $subDir : '');
    
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

function clean($data): string {
    if (is_array($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function format_rupiah($number): string {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

function format_date_id(?string $dateStr): string {
    if (empty($dateStr)) return '-';
    $time = strtotime($dateStr);
    if (!$time) return '-';

    $months = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $m = (int)date('n', $time);
    return date('d', $time) . ' ' . ($months[$m] ?? '') . ' ' . date('Y', $time);
}

function format_datetime_id(?string $dateTimeStr): string {
    if (empty($dateTimeStr)) return '-';
    $time = strtotime($dateTimeStr);
    if (!$time) return '-';

    $months = [
        1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
        'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'
    ];
    $m = (int)date('n', $time);
    return date('d', $time) . ' ' . ($months[$m] ?? '') . ' ' . date('Y, H:i', $time);
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Konversi gambar (JPG/PNG/WEBP) ke format WebP teroptimasi (maksimal 1MB)
 */
function convert_image_to_webp(string $sourcePath, string $destPath, int $quality = 82): bool {
    $info = @getimagesize($sourcePath);
    if (!$info) return false;
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            $image = @imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = @imagecreatefrompng($sourcePath);
            if ($image) {
                imagepalettetotruecolor($image);
                imagealphablending($image, true);
                imagesavealpha($image, true);
            }
            break;
        case 'image/webp':
            $image = @imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    if (!$image) return false;
    
    // Resize down jika dimensi melebihi 1920px untuk menjaga ukuran < 1MB
    $w = imagesx($image);
    $h = imagesy($image);
    $maxDim = 1920;
    if ($w > $maxDim || $h > $maxDim) {
        if ($w > $h) {
            $newW = $maxDim;
            $newH = (int)($h * ($maxDim / $w));
        } else {
            $newH = $maxDim;
            $newW = (int)($w * ($maxDim / $h));
        }
        $resized = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $w, $h);
        imagedestroy($image);
        $image = $resized;
    }
    
    $success = @imagewebp($image, $destPath, $quality);
    imagedestroy($image);
    
    // Pastikan hasil akhir <= 1MB (1048576 bytes)
    if ($success && file_exists($destPath) && filesize($destPath) > 1048576) {
        $img2 = @imagecreatefromwebp($destPath);
        if ($img2) {
            @imagewebp($img2, $destPath, 60);
            imagedestroy($img2);
        }
    }
    
    return $success;
}

