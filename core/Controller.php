<?php
// core/Controller.php - Base Controller untuk SIMRS Native MVC

abstract class Controller {

    protected function render(string $view, array $data = [], bool $useLayout = true): void {
        extract($data);
        $flash = $this->getFlash();
        $u = $this->currentUser();
        $pageTitle = $data['pageTitle'] ?? 'SIMRS Siloam Peduli';

        $viewFile = __DIR__ . '/../app/views/' . $view . '.php';
        if (!file_exists($viewFile)) {
            die("View file tidak ditemukan: app/views/{$view}.php");
        }

        if ($useLayout) {
            require __DIR__ . '/../app/views/layouts/header.php';
            require $viewFile;
            require __DIR__ . '/../app/views/layouts/footer.php';
        } else {
            require $viewFile;
        }
    }

    protected function view(string $view, array $data = [], bool $useLayout = true): void {
        $this->render($view, $data, $useLayout);
    }

    protected function redirect(string $path): void {
        header('Location: ' . base_url($path));
        exit;
    }

    protected function json(array $data, int $status = 200): void {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    protected function setFlash(string $type, string $message): void {
        $_SESSION['flash_msg'] = [
            'type' => $type,
            'text' => $message
        ];
    }

    protected function getFlash(): ?array {
        if (isset($_SESSION['flash_msg'])) {
            $msg = $_SESSION['flash_msg'];
            unset($_SESSION['flash_msg']);
            return $msg;
        }
        return null;
    }

    protected function isLoggedIn(): bool {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['role']);
    }

    protected function currentUser(): ?array {
        if (!$this->isLoggedIn()) return null;
        return [
            'id'        => $_SESSION['user_id'],
            'username'  => $_SESSION['username'],
            'full_name' => $_SESSION['full_name'],
            'role'      => $_SESSION['role'],
            'unit'      => $_SESSION['unit'] ?? ''
        ];
    }

    protected function requireLogin(): void {
        if (!$this->isLoggedIn()) {
            $this->setFlash('warning', 'Silakan masuk terlebih dahulu untuk mengakses sistem.');
            $this->redirect('index.php?c=auth&a=login');
        }
    }

    protected function requireRole($roles): void {
        $this->requireLogin();
        $userRole = strtolower($_SESSION['role'] ?? '');
        $allowedRoles = is_array($roles) ? $roles : [$roles];

        $allowed = false;
        foreach ($allowedRoles as $r) {
            $rLower = strtolower($r);
            if ($rLower === $userRole) {
                $allowed = true;
                break;
            }
            if (str_contains($rLower, 'admin') && str_contains($userRole, 'admin')) {
                $allowed = true;
                break;
            }
            if (str_contains($rLower, 'registrasi') && str_contains($userRole, 'registrasi')) {
                $allowed = true;
                break;
            }
            // Dokter klinis poliklinik / rawat inap / IGD (EXCLUDES dokter_radiologi)
            if ($rLower === 'dokter' || $rLower === 'dokter umum' || $rLower === 'dokter spesialis' || $rLower === 'dpjp') {
                if ($userRole === 'dokter' || $userRole === 'dokter spesialis' || $userRole === 'dpjp') {
                    $allowed = true;
                    break;
                }
            }
            // Dokter Spesialis Radiologi (spesifik)
            if (($rLower === 'dokter_radiologi' || $rLower === 'spesialis_radiologi') && $userRole === 'dokter_radiologi') {
                $allowed = true;
                break;
            }
            // Radiografer pelaksana imaging (spesifik)
            if ($rLower === 'radiografer' && $userRole === 'radiografer') {
                $allowed = true;
                break;
            }
            // Perawat (mencakup perawat, perawat poli, kepala_ruang)
            if (str_contains($rLower, 'perawat') && (str_contains($userRole, 'perawat') || str_contains($userRole, 'kepala_ruang'))) {
                $allowed = true;
                break;
            }
            // Farmasi & Apoteker
            if ((str_contains($rLower, 'apoteker') || str_contains($rLower, 'farmasi')) && (str_contains($userRole, 'apoteker') || str_contains($userRole, 'farmasi'))) {
                $allowed = true;
                break;
            }
            // Laboratorium & Analis
            if ((str_contains($rLower, 'analis') || str_contains($rLower, 'lab')) && (str_contains($userRole, 'analis') || str_contains($userRole, 'lab'))) {
                $allowed = true;
                break;
            }
            // Kasir & Billing
            if ((str_contains($rLower, 'kasir') || str_contains($rLower, 'billing')) && (str_contains($userRole, 'kasir') || str_contains($userRole, 'billing'))) {
                $allowed = true;
                break;
            }
            // Triase IGD
            if (str_contains($rLower, 'triase') && str_contains($userRole, 'triase')) {
                $allowed = true;
                break;
            }
            // Gudang & Logistik
            if ((str_contains($rLower, 'gudang') || str_contains($rLower, 'logistik')) && (str_contains($userRole, 'gudang') || str_contains($userRole, 'logistik'))) {
                $allowed = true;
                break;
            }
            // Manajemen & Direksi
            if ((str_contains($rLower, 'manajemen') || str_contains($rLower, 'direksi')) && (str_contains($userRole, 'manajemen') || str_contains($userRole, 'direksi'))) {
                $allowed = true;
                break;
            }
        }

        if (!$allowed) {
            http_response_code(403);
            $this->setFlash('danger', 'Akses ditolak: Anda tidak memiliki wewenang untuk mengakses halaman layanan ini.');
            $this->redirect('index.php?c=dashboard');
        }
    }

    protected function validateCsrf(): void {
        if (function_exists('verify_csrf') && !verify_csrf()) {
            $this->setFlash('danger', 'Token keamanan CSRF tidak valid. Silakan coba kembali.');
            $ref = $_SERVER['HTTP_REFERER'] ?? base_url('index.php');
            header("Location: {$ref}");
            exit;
        }
    }

    protected function logAudit(string $action, string $module, ?string $details = null): void {
        try {
            $db = Database::getConnection();
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
            error_log("Audit log failed: " . $e->getMessage());
        }
    }
}
