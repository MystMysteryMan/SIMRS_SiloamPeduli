<?php
// app/models/UserModel.php - Model Pengguna & Hak Akses (Modul 1 - SOP-001)

require_once __DIR__ . '/../../core/Model.php';

class UserModel extends Model {
    protected string $table = 'users';

    public function authenticate(string $username, string $password): ?array {
        $user = $this->findBy('username', $username);
        if (!$user) {
            return null;
        }

        if (empty($user['is_active'])) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        // Normalisasi role display
        $user['role_label'] = ($user['role'] === 'admin') ? 'Admin Sistem' : 'Petugas Registrasi';
        return $user;
    }

    public function getAllUsers(): array {
        $stmt = $this->db->query("SELECT * FROM users ORDER BY id ASC");
        $users = $stmt->fetchAll();
        foreach ($users as &$u) {
            $u['role_label'] = ($u['role'] === 'admin') ? 'Admin Sistem' : 'Petugas Registrasi';
        }
        return $users;
    }

    public function findById(int $id): ?array {
        return $this->findBy('id', $id);
    }

    public function findByUsername(string $username): ?array {
        return $this->findBy('username', strtolower(trim($username)));
    }

    public function createUser(array $data): int {
        $role = 'registrasi';
        $inputRole = strtolower($data['role'] ?? '');
        if (str_contains($inputRole, 'admin')) {
            $role = 'admin';
        }

        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("
            INSERT INTO users (username, password_hash, full_name, email, role, unit, is_active)
            VALUES (:u, :p, :fn, :em, :r, :un, :act)
        ");
        $stmt->execute([
            ':u'   => strtolower(trim($data['username'])),
            ':p'   => $hash,
            ':fn'  => clean($data['full_name']),
            ':em'  => !empty($data['email']) ? clean($data['email']) : null,
            ':r'   => $role,
            ':un'  => !empty($data['unit']) ? clean($data['unit']) : 'Admisi',
            ':act' => $data['is_active'] ?? 1
        ]);
        return $this->lastInsertId();
    }

    public function resetPassword(int $userId, string $newPassword): bool {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password_hash = :p, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':p' => $hash, ':id' => $userId]);
    }

    public function toggleActive(int $userId): bool {
        $stmt = $this->db->prepare("UPDATE users SET is_active = NOT is_active, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':id' => $userId]);
    }
}
