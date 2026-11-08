<?php
// app/models/AuditLogModel.php - Model Jejak Aktivitas Pengguna (Modul 1 - SOP-001 E)

require_once __DIR__ . '/../../core/Model.php';

class AuditLogModel extends Model {
    protected string $table = 'audit_logs';

    public function getLogs(?string $module = null, ?string $date = null, int $limit = 100): array {
        $sql = "
            SELECT a.*, u.username, u.full_name, u.role, u.full_name AS user_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($module)) {
            $sql .= " AND a.module = :mod";
            $params[':mod'] = $module;
        }

        if (!empty($date)) {
            $sql .= " AND DATE(a.created_at) = :dt";
            $params[':dt'] = $date;
        }

        $sql .= " ORDER BY a.id DESC LIMIT " . (int)$limit;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function paginateLogs(?string $module = null, ?string $date = null, int $page = 1, int $perPage = 20): array {
        $sql = "
            SELECT a.*, u.username, u.full_name, u.role, u.full_name AS user_name
            FROM audit_logs a
            LEFT JOIN users u ON a.user_id = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($module)) {
            $sql .= " AND a.module = :mod";
            $params[':mod'] = $module;
        }

        if (!empty($date)) {
            $sql .= " AND DATE(a.created_at) = :dt";
            $params[':dt'] = $date;
        }

        $sql .= " ORDER BY a.id DESC";
        return $this->paginate($sql, $params, $page, $perPage);
    }

    public static function log($arg1, $arg2, ?string $arg3 = null, $arg4 = null): void {
        try {
            $db = Database::getConnection();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            // Support both signatures:
            // 1) log(string $action, string $module, ?string $details = null, ?int $userId = null)
            // 2) log(?int $userId, string $action, string $module, ?string $details = null)
            if (is_int($arg1) || ($arg1 === null && is_string($arg2) && is_string($arg3) && is_string($arg4))) {
                $uid = $arg1 !== null ? (int)$arg1 : ($_SESSION['user_id'] ?? null);
                $action = (string)$arg2;
                $module = (string)$arg3;
                $details = (string)$arg4;
            } else {
                $action = (string)$arg1;
                $module = (string)$arg2;
                $details = $arg3 !== null ? (string)$arg3 : null;
                $uid = $arg4 !== null ? (int)$arg4 : ($_SESSION['user_id'] ?? null);
            }

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (:uid, :act, :mod, :det, :ip)");
            $stmt->execute([
                ':uid' => $uid,
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
