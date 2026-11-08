<?php
// core/PostgresSessionHandler.php - Serverless Session Handler for Supabase PostgreSQL

class PostgresSessionHandler implements SessionHandlerInterface {
    private ?PDO $pdo = null;

    public function __construct(?PDO $pdo = null) {
        $this->pdo = $pdo;
    }

    private function getPdo(): PDO {
        if ($this->pdo === null) {
            require_once __DIR__ . '/Database.php';
            $this->pdo = Database::getConnection();
        }
        return $this->pdo;
    }

    public function open(string $path, string $name): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string|false {
        try {
            $stmt = $this->getPdo()->prepare("SELECT data FROM sessions WHERE id = ? LIMIT 1");
            $stmt->execute([$id]);
            $data = $stmt->fetchColumn();
            return $data !== false ? (string)$data : '';
        } catch (\Throwable $e) {
            error_log('Session read error: ' . $e->getMessage());
            return '';
        }
    }

    public function write(string $id, string $data): bool {
        try {
            $timestamp = time();
            $stmt = $this->getPdo()->prepare("INSERT INTO sessions (id, access_timestamp, data) 
                VALUES (?, ?, ?) 
                ON CONFLICT (id) DO UPDATE 
                SET access_timestamp = EXCLUDED.access_timestamp, data = EXCLUDED.data");
            return $stmt->execute([$id, $timestamp, $data]);
        } catch (\Throwable $e) {
            error_log('Session write error: ' . $e->getMessage());
            return false;
        }
    }

    public function destroy(string $id): bool {
        try {
            $stmt = $this->getPdo()->prepare("DELETE FROM sessions WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (\Throwable $e) {
            error_log('Session destroy error: ' . $e->getMessage());
            return false;
        }
    }

    public function gc(int $max_lifetime): int|false {
        try {
            $old = time() - $max_lifetime;
            $stmt = $this->getPdo()->prepare("DELETE FROM sessions WHERE access_timestamp < ?");
            $stmt->execute([$old]);
            return $stmt->rowCount();
        } catch (\Throwable $e) {
            error_log('Session gc error: ' . $e->getMessage());
            return false;
        }
    }
}

