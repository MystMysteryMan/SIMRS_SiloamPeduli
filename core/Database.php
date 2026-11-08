<?php
// core/Database.php - Supabase PostgreSQL Database Singleton Handler

class Database {
    private static ?PDO $instance = null;

    private function __construct() {}

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            // Ensure .env is loaded if standalone
            if (!getenv('DB_HOST') && file_exists(dirname(__DIR__) . '/.env')) {
                $lines = file(dirname(__DIR__) . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || str_starts_with($line, '#')) continue;
                    if (str_contains($line, '=')) {
                        [$k, $v] = explode('=', $line, 2);
                        putenv(trim($k) . "=" . trim($v, " \t\n\r\0\x0B\"'"));
                    }
                }
            }

            $host = defined('DB_HOST') ? DB_HOST : (getenv('DB_HOST') ?: 'aws-0-ap-southeast-1.pooler.supabase.com');
            $port = defined('DB_PORT') ? DB_PORT : (getenv('DB_PORT') ?: '6543');
            $dbname = defined('DB_NAME') ? DB_NAME : (getenv('DB_NAME') ?: 'postgres');
            $user = defined('DB_USER') ? DB_USER : (getenv('DB_USER') ?: 'postgres.dvntrjohxewczvgppeio');
            $pass = defined('DB_PASS') ? DB_PASS : (getenv('DB_PASS') ?: 'NjiNNFOMfE2bK8vb');
            $sslmode = defined('DB_SSLMODE') ? DB_SSLMODE : (getenv('DB_SSLMODE') ?: 'require');

            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_TIMEOUT            => 10,
            ];

            try {
                self::$instance = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                die('Koneksi Supabase PostgreSQL Gagal: ' . htmlspecialchars($e->getMessage()));
            }
        }

        return self::$instance;
    }
}
