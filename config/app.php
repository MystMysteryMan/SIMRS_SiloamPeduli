<?php
// config/app.php - Konfigurasi Umum dan Helper SIMRS Siloam Peduli

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

define('APP_NAME', 'SIMRS Siloam Peduli');
define('APP_VERSION', '2.0.0');

/**
 * Menghasilkan Base URL aplikasi secara dinamis
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
    
    // Script path detection
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT'] ?? '');
    $appDir = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    
    // Hitung subfolder relatif terhadap DOCUMENT_ROOT
    $subDir = '';
    if (!empty($docRoot) && str_starts_with($appDir, $docRoot)) {
        $subDir = substr($appDir, strlen($docRoot));
    }
    $subDir = trim($subDir, '/');
    $base = $protocol . $host . ($subDir ? '/' . $subDir : '');
    
    return rtrim($base, '/') . '/' . ltrim($path, '/');
}

/**
 * Sanitasi string input untuk mencegah XSS
 */
function clean_input($data): string {
    if (is_array($data)) return '';
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Format angka ke mata uang Rupiah (e.g. Rp 150.000)
 */
function format_rupiah($number): string {
    return 'Rp ' . number_format((float)$number, 0, ',', '.');
}

/**
 * Format tanggal standar Indonesia (e.g. 07 November 2026)
 */
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

/**
 * Format tanggal & jam Indonesia (e.g. 07 Nov 2026, 14:30)
 */
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

/**
 * Mengambil nilai konfigurasi dari tabel system_configurations
 */
function get_config(string $key, string $default = ''): string {
    try {
        $db = get_db();
        $stmt = $db->prepare("SELECT config_value FROM system_configurations WHERE config_key = :k LIMIT 1");
        $stmt->execute([':k' => $key]);
        $val = $stmt->fetchColumn();
        return $val !== false ? (string)$val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Flash message helper (success, danger, warning, info)
 */
function set_flash(string $type, string $message): void {
    $_SESSION['flash_msg'] = [
        'type' => $type,
        'text' => $message
    ];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash_msg'])) {
        $msg = $_SESSION['flash_msg'];
        unset($_SESSION['flash_msg']);
        return $msg;
    }
    return null;
}

/**
 * CSRF Protection
 */
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
 * Auto-generate Nomor Rekam Medis (No. RM) berikutnya
 * Format: RM-YYYYMM-XXXX (contoh: RM-202611-0005)
 */
function generate_next_rm(): string {
    $db = get_db();
    $prefix = get_config('rm_prefix', 'RM');
    $ym = date('Ym');

    $stmt = $db->query("SELECT MAX(id) FROM patients");
    $maxId = (int)$stmt->fetchColumn();
    $nextNumber = str_pad((string)($maxId + 1), 4, '0', STR_PAD_LEFT);

    return sprintf('%s-%s-%s', $prefix, $ym, $nextNumber);
}

/**
 * Auto-generate Nomor Antrean per Poliklinik hari ini
 * Contoh: A-001, B-004
 */
function generate_queue_number(int $policlinicId): string {
    $db = get_db();
    
    // Ambil queue prefix dari poliklinik
    $stmtPoli = $db->prepare("SELECT queue_prefix FROM policlinics WHERE id = :id");
    $stmtPoli->execute([':id' => $policlinicId]);
    $prefix = $stmtPoli->fetchColumn() ?: 'A';

    // Hitung antrean poli hari ini
    $today = date('Y-m-d');
    $stmtCount = $db->prepare("SELECT COUNT(*) FROM visits WHERE policlinic_id = :poli_id AND visit_date = :vdate");
    $stmtCount->execute([
        ':poli_id' => $policlinicId,
        ':vdate'   => $today
    ]);
    $count = (int)$stmtCount->fetchColumn();
    $nextNo = str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

    return sprintf('%s-%s', $prefix, $nextNo);
}

