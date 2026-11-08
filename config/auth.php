<?php
// config/auth.php - Otentikasi, Otorisasi RBAC, dan Audit Trail

require_once __DIR__ . '/app.php';

/**
 * Mengecek apakah pengguna sudah login
 */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
}

/**
 * Mengambil data pengguna yang sedang login
 */
function current_user(): ?array {
    if (!is_logged_in()) {
        return null;
    }
    return [
        'id'        => $_SESSION['user_id'],
        'username'  => $_SESSION['username'],
        'full_name' => $_SESSION['full_name'],
        'role'      => $_SESSION['role'],
        'unit'      => $_SESSION['unit'] ?? '',
        'email'     => $_SESSION['email'] ?? ''
    ];
}

/**
 * Mewajibkan status login (redirect ke login jika belum login)
 */
function require_login(): void {
    if (!is_logged_in()) {
        set_flash('warning', 'Silakan masuk terlebih dahulu untuk mengakses sistem.');
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

/**
 * Memeriksa apakah user memiliki peran yang diizinkan
 * @param string|array $roles
 */
function has_role($roles): bool {
    if (!is_logged_in()) {
        return false;
    }
    $userRole = $_SESSION['role'] ?? '';
    if (is_array($roles)) {
        return in_array($userRole, $roles, true);
    }
    return $userRole === $roles;
}

/**
 * Membatasi akses modul berdasarkan peran (RBAC Guard)
 * @param string|array $roles
 */
function require_role($roles): void {
    require_login();
    if (!has_role($roles)) {
        http_response_code(403);
        set_flash('danger', 'Akses ditolak: Anda tidak memiliki wewenang untuk modul ini.');
        header('Location: ' . base_url('modules/dashboard/index.php'));
        exit;
    }
}

/**
 * Mencatat riwayat ke tabel audit_logs (SOP-001)
 */
function log_audit(string $action, string $module, ?string $details = null): void {
    try {
        $db = get_db();
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

        $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (:uid, :act, :mod, :det, :ip)");
        $stmt->execute([
            ':uid' => $userId,
            ':act' => $action,
            ':mod' => $module,
            ':det' => $details,
            ':ip'  => $ip
        ]);
    } catch (Exception $e) {
        // Jangan hentikan aplikasi jika logging gagal
        error_log("Audit log failed: " . $e->getMessage());
    }
}

/**
 * Memproses login pengguna
 */
function do_login(string $username, string $password): array {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM users WHERE username = :u AND is_active = 1 LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Username atau password tidak sesuai.'];
    }

    // Buat sesi login
    $_SESSION['user_id']   = (int)$user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['unit']      = $user['unit'];
    $_SESSION['email']     = $user['email'];

    log_audit('LOGIN', 'AUTH', "Pengguna {$user['username']} ({$user['role']}) berhasil login.");

    return ['success' => true, 'user' => $user];
}

/**
 * Memproses logout pengguna
 */
function do_logout(): void {
    if (is_logged_in()) {
        log_audit('LOGOUT', 'AUTH', "Pengguna {$_SESSION['username']} keluar dari sistem.");
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

