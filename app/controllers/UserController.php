<?php
// app/controllers/UserController.php - Manajemen Pengguna & Hak Akses

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/UserModel.php';
require_once __DIR__ . '/../models/AuditLogModel.php';
require_once __DIR__ . '/../models/MasterDataModel.php';

class UserController extends Controller {

    public function index(): void {
        $this->requireRole('Admin Sistem');

        $userModel = new UserModel();
        $users = $userModel->getAllUsers();

        $this->render('user_management/index', [
            'pageTitle' => 'Manajemen Pengguna - SIMRS',
            'users'     => $users
        ]);
    }

    public function create(): void {
        $this->requireRole('Admin Sistem');
        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = trim($_POST['username'] ?? '');
            $password = trim($_POST['password'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $role     = trim($_POST['role'] ?? '');
            $unit     = trim($_POST['unit'] ?? '');

            if (empty($username) || empty($password) || empty($fullName) || empty($role)) {
                $error = 'Semua field wajib diisi.';
            } else {
                $userModel = new UserModel();
                if ($userModel->findByUsername($username)) {
                    $error = "Username '{$username}' sudah terdaftar. Silakan gunakan username lain.";
                } else {
                    $newId = $userModel->createUser([
                        'username'  => $username,
                        'password'  => $password,
                        'full_name' => $fullName,
                        'role'      => $role,
                        'unit'      => $unit,
                        'is_active' => 1
                    ]);

                    $this->logAudit('CREATE_USER', 'Manajemen Pengguna', "Membuat user baru: {$username} ({$role})");
                    $this->setFlash('success', "Pengguna {$username} berhasil ditambahkan!");
                    $this->redirect('index.php?c=user&a=index');
                }
            }
        }

        $masterModel = new MasterDataModel();
        $hospitalUnits = $masterModel->getAllHospitalUnits(true);

        $this->render('user_management/create', [
            'pageTitle'     => 'Tambah Pengguna Baru - SIMRS',
            'error'         => $error,
            'hospitalUnits' => $hospitalUnits
        ]);
    }

    public function resetPassword(): void {
        $this->requireRole('Admin Sistem');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = trim($_POST['new_password'] ?? '');

            if ($userId > 0 && !empty($newPassword)) {
                $userModel = new UserModel();
                $targetUser = $userModel->findById($userId);

                if ($targetUser) {
                    $userModel->resetPassword($userId, $newPassword);
                    $this->logAudit('RESET_PASSWORD', 'Manajemen Pengguna', "Mereset kata sandi user ID {$userId} ({$targetUser['username']})");
                    $this->setFlash('success', "Kata sandi untuk {$targetUser['username']} berhasil direset.");
                } else {
                    $this->setFlash('danger', 'Pengguna tidak ditemukan.');
                }
            } else {
                $this->setFlash('danger', 'Kata sandi baru tidak boleh kosong.');
            }
        }

        $this->redirect('index.php?c=user&a=index');
    }

    public function toggleStatus(): void {
        $this->requireRole('Admin Sistem');

        $userId = (int)($_GET['id'] ?? 0);
        if ($userId > 0) {
            // Hindari menonaktifkan akun sendiri
            if ($userId === (int)$_SESSION['user_id']) {
                $this->setFlash('danger', 'Anda tidak dapat menonaktifkan akun Anda sendiri yang sedang aktif.');
            } else {
                $userModel = new UserModel();
                $userModel->toggleActive($userId);
                $this->logAudit('TOGGLE_USER_STATUS', 'Manajemen Pengguna', "Mengubah status aktif user ID {$userId}");
                $this->setFlash('success', 'Status akun berhasil diperbarui.');
            }
        }

        $this->redirect('index.php?c=user&a=index');
    }

    public function auditLog(): void {
        $this->requireRole('Admin Sistem');

        $auditModel = new AuditLogModel();
        $module = $_GET['module'] ?? null;
        $date = $_GET['date'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $pagination = $auditModel->paginateLogs($module, $date, $page, 20);

        $this->render('user_management/audit_log', [
            'pageTitle'    => 'Audit Trail & Log Aktivitas - SIMRS',
            'logs'         => $pagination['data'],
            'pagination'   => $pagination,
            'filterModule' => $module,
            'filterDate'   => $date
        ]);
    }
}

