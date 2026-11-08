<?php
// app/controllers/AuthController.php - Autentikasi & Sesi Pengguna

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';

class AuthController extends Controller {

    public function login(): void {
        if ($this->isLoggedIn()) {
            $this->redirect('index.php?c=dashboard');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');

            if (empty($username) || empty($password)) {
                $error = 'Username dan kata sandi wajib diisi.';
            } else {
                $userModel = new UserModel();
                $user = $userModel->authenticate($username, $password);

                if ($user) {
                    // Simpan sesi
                    $_SESSION['user_id']   = $user['id'];
                    $_SESSION['username']  = $user['username'];
                    $_SESSION['full_name'] = $user['full_name'];
                    $_SESSION['role']      = $user['role'];
                    $_SESSION['unit']      = $user['unit'];

                    $this->logAudit('LOGIN', 'Autentikasi', "User {$user['username']} ({$user['role']}) berhasil login");

                    $this->setFlash('success', "Selamat datang, {$user['full_name']}! Anda masuk sebagai {$user['role']}.");
                    $this->redirect('index.php?c=dashboard');
                } else {
                    $this->logAudit('LOGIN_FAILED', 'Autentikasi', "Percobaan login gagal untuk username: {$username}");
                    $error = 'Username atau kata sandi tidak valid, atau akun Anda dinonaktifkan.';
                }
            }
        }

        $this->render('auth/login', [
            'pageTitle' => 'Masuk Sistem - SIMRS Siloam Peduli',
            'error'     => $error
        ], false); // No header/sidebar layout for login page
    }

    public function logout(): void {
        if ($this->isLoggedIn()) {
            $username = $_SESSION['username'] ?? 'Unknown';
            $this->logAudit('LOGOUT', 'Autentikasi', "User {$username} keluar dari sistem");
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

        header('Location: ' . base_url('index.php?c=auth&a=login'));
        exit;
    }
}

