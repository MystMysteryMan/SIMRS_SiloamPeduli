<?php
// core/Model.php - Base Model untuk SIMRS Native MVC (Supabase PostgreSQL Compatible)

abstract class Model {
    protected PDO $db;
    protected string $table = '';

    public function __construct() {
        $this->db = Database::getConnection();
    }

    public function all($arg1 = 'id DESC', ?string $arg2 = null): array {
        if ($arg2 !== null) {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$arg1);
            $orderBy = $arg2;
            $stmt = $this->db->query("SELECT * FROM {$table} ORDER BY {$orderBy}");
            return $stmt->fetchAll();
        }
        $table = $this->table;
        $orderBy = (string)$arg1;
        $stmt = $this->db->query("SELECT * FROM {$table} ORDER BY {$orderBy}");
        return $stmt->fetchAll();
    }

    public function find($arg1, ?int $arg2 = null): ?array {
        if (is_string($arg1) && $arg2 !== null) {
            $table = preg_replace('/[^a-zA-Z0-9_]/', '', $arg1);
            $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $arg2]);
            $row = $stmt->fetch();
            return $row ?: null;
        }
        $id = (int)$arg1;
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function findById(int $id): ?array {
        return $this->find($id);
    }

    public function findAll(array $criteria = []): array {
        $where = ['1=1'];
        $params = [];
        $limit = '';
        foreach ($criteria as $col => $val) {
            if ($col === 'limit') {
                $limit = " LIMIT " . (int)$val;
            } else {
                $where[] = "{$col} = :{$col}";
                $params[":{$col}"] = $val;
            }
        }
        $sql = "SELECT * FROM {$this->table} WHERE " . implode(' AND ', $where) . " ORDER BY id DESC" . $limit;
        return $this->query($sql, $params);
    }

    public function findBy(string $column, $value): ?array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$column} = :val LIMIT 1");
        $stmt->execute([':val' => $value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function count(string $where = '1=1', array $params = []): int {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE {$where}");
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function lastInsertId(): int {
        return (int)$this->db->lastInsertId();
    }

    public function query(string $sql, array $params = []): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function execute(string $sql, array $params = []): bool {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    public function paginate(string $baseSql, array $params = [], int $page = 1, int $perPage = 15): array {
        $page = max(1, $page);
        $perPage = max(1, min(100, $perPage));

        $countSql = "SELECT COUNT(*) FROM ({$baseSql}) AS _sub_count";
        $stmtCount = $this->db->prepare($countSql);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $lastPage = max(1, (int)ceil($total / $perPage));
        if ($page > $lastPage && $total > 0) {
            $page = $lastPage;
        }
        $offset = ($page - 1) * $perPage;

        $dataSql = "{$baseSql} LIMIT {$perPage} OFFSET {$offset}";
        $stmtData = $this->db->prepare($dataSql);
        $stmtData->execute($params);
        $items = $stmtData->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data'         => $items,
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => $lastPage,
            'has_prev'     => $page > 1,
            'has_next'     => $page < $lastPage,
            'prev_page'    => $page > 1 ? $page - 1 : null,
            'next_page'    => $page < $lastPage ? $page + 1 : null,
            'from'         => $total > 0 ? $offset + 1 : 0,
            'to'           => min($offset + $perPage, $total),
        ];
    }

    public function insert(string $sql, array $params = []): int {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }
}
