<?php
// config/database.php - Koneksi Database SIMRS Siloam Peduli

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_NAME', 'simrs_siloam');
define('DB_USER', 'root');
define('DB_PASS', '');

/**
 * Mendapatkan koneksi PDO singleton ke database
 * @return PDO
 */
function get_db(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', DB_HOST, DB_PORT, DB_NAME);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Koneksi Database Gagal: ' . htmlspecialchars($e->getMessage()));
        }
    }

    return $pdo;
}

