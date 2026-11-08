<?php
// database/setup.php - 1-Click Automated Supabase Database Setup & Seeder for SIMRS Siloam Peduli

echo "====================================================================\n";
echo "   SIMRS SILOAM PEDULI - SUPABASE DATABASE AUTOMATED SETUP          \n";
echo "====================================================================\n\n";

// 1. Load .env
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            putenv("{$k}={$v}");
            $_ENV[$k] = $v;
            $_SERVER[$k] = $v;
        }
    }
}

$host = getenv('DB_HOST') ?: 'aws-0-ap-southeast-1.pooler.supabase.com';
$port = getenv('DB_PORT') ?: '6543';
$dbname = getenv('DB_NAME') ?: 'postgres';
$user = getenv('DB_USER') ?: 'postgres.dvntrjohxewczvgppeio';
$pass = getenv('DB_PASS') ?: 'NjiNNFOMfE2bK8vb';
$sslmode = getenv('DB_SSLMODE') ?: 'require';

echo "Menghubungkan ke PostgreSQL:\n";
echo " - Host: {$host}\n";
echo " - Port: {$port}\n";
echo " - Database: {$dbname}\n";
echo " - User: {$user}\n\n";

try {
    $dsn = "pgsql:host={$host};port={$port};dbname={$dbname};sslmode={$sslmode}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 20
    ]);

    $t0 = microtime(true);
    $pdo->query("SELECT 1")->fetch();
    $pingMs = round((microtime(true) - $t0) * 1000, 1);
    echo "✓ Koneksi Berhasil! Ping latency: {$pingMs} ms\n\n";

    $sqlFile = __DIR__ . '/supabase_schema_complete.sql';
    if (!file_exists($sqlFile)) {
        die("ERROR: File skema {$sqlFile} tidak ditemukan!\n");
    }

    echo "Membaca skema master dari database/supabase_schema_complete.sql...\n";
    $sqlContent = file_get_contents($sqlFile);

    echo "Mengeksekusi skema DDL & Seeder ke Supabase PostgreSQL...\n";
    $pdo->exec($sqlContent);

    $tblCount = (int)$pdo->query("SELECT count(1) FROM pg_tables WHERE schemaname = 'public'")->fetchColumn();
    $idxCount = (int)$pdo->query("SELECT count(1) FROM pg_indexes WHERE schemaname = 'public'")->fetchColumn();
    $userCount = (int)$pdo->query("SELECT count(1) FROM users")->fetchColumn();

    echo "\n====================================================================\n";
    echo "✓ SETUP DATABASE SELESAI DENGAN SUKSES!\n";
    echo " - Total Tabel Terbuat : {$tblCount} Tabel\n";
    echo " - Total Indexes Aktif  : {$idxCount} Indexes\n";
    echo " - Total Akun Pengguna  : {$userCount} Akun Demo Bawaan\n";
    echo " - Status Server        : Siap Digunakan (Production Ready)\n";
    echo "====================================================================\n";

} catch (Exception $e) {
    echo "\n[ERROR]: " . $e->getMessage() . "\n";
    exit(1);
}
