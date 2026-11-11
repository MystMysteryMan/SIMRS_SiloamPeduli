<?php
/** Helpers used only by EMR, Laboratory and Radiology. */
final class ClinicalWorkflow {
    public static function role(): string { return strtolower($_SESSION['role'] ?? ''); }
    public static function doctor(): bool { return in_array(self::role(), ['dokter', 'dokter spesialis', 'dpjp'], true); }
    public static function nurse(): bool { return in_array(self::role(), ['perawat', 'perawat poli', 'kepala_ruang'], true); }
    public static function requireRole(array $roles): void {
        if (empty($_SESSION['user_id']) || !in_array(self::role(), $roles, true)) {
            throw new DomainException('Peran Anda tidak berwenang melakukan tindakan ini.');
        }
    }
    public static function transaction(PDO $db, callable $work): array {
        $own = !$db->inTransaction();
        if ($own) $db->beginTransaction();
        try {
            $result = $work();
            if ($own) $db->commit();
            return $result;
        } catch (Throwable $e) {
            if ($own && $db->inTransaction()) $db->rollBack();
            if (!$own) throw $e;
            if ($e instanceof DomainException) return ['success' => false, 'message' => $e->getMessage()];
            error_log('Clinical workflow failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Data belum tersimpan. Periksa koneksi dan migrasi modul 3, 8, 9, lalu coba kembali.'];
        }
    }
    public static function audit(PDO $db, string $action, string $module, string $details): void {
        $q = $db->prepare('INSERT INTO audit_logs (user_id, action, module, details, ip_address) VALUES (?, ?, ?, ?, ?)');
        $q->execute([(int)$_SESSION['user_id'], $action, $module, $details, $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1']);
    }
    public static function text($value, int $max = 10000): string {
        if (!is_scalar($value) && $value !== null) throw new DomainException('Format input tidak valid.');
        $value = trim((string)$value);
        if (mb_strlen($value) > $max) throw new DomainException('Teks melebihi batas panjang yang diizinkan.');
        return $value;
    }
    public static function vitals(array $data): array {
        $out = ['vital_bp' => self::text($data['vital_bp'] ?? '', 20)];
        if ($out['vital_bp'] !== '' && !preg_match('/^[0-9]{1,3}\/[0-9]{1,3}$/D', $out['vital_bp'])) {
            throw new DomainException('Format tekanan darah harus sistolik/diastolik, misalnya 120/80.');
        }
        foreach (['vital_temp' => [0, 50, false], 'vital_pulse' => [0, 350, true], 'vital_rr' => [0, 150, true], 'weight_kg' => [0.1, 700, false], 'height_cm' => [1, 300, false]] as $key => [$min, $max, $integer]) {
            $v = self::text($data[$key] ?? '', 20);
            if ($v !== '' && (!is_numeric($v) || !is_finite((float)$v) || (float)$v < $min || (float)$v > $max || ($integer && (float)$v !== (float)(int)$v))) {
                throw new DomainException('Format atau rentang tanda vital tidak valid: ' . $key . '.');
            }
            $out[$key] = $v === '' ? null : ($integer ? (int)$v : (float)$v);
        }
        $out['bmi'] = $out['weight_kg'] && $out['height_cm'] ? round($out['weight_kg'] / (($out['height_cm'] / 100) ** 2), 1) : null;
        return $out;
    }
    public static function file(array $file, bool $pdf = false): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new DomainException('Berkas gagal diunggah. Pilih berkas kembali.');
        }
        $size = filesize($file['tmp_name']);
        if (!$size || $size > 3145728) throw new DomainException('Ukuran berkas maksimal 3 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg', 'image/png', 'image/webp'];
        if ($pdf) $allowed[] = 'application/pdf';
        if (!in_array($mime, $allowed, true)) throw new DomainException('Format berkas tidak didukung. Gunakan JPG, PNG, WebP' . ($pdf ? ', atau PDF.' : '.'));
        if (str_starts_with($mime, 'image/')) {
            $info = @getimagesize($file['tmp_name']);
            if (!$info || $info[0] * $info[1] > 40000000) throw new DomainException('Berkas gambar rusak atau resolusinya terlalu besar.');
        }
        $name = mb_substr(preg_replace('/[^\pL\pN._ -]/u', '_', basename($file['name'] ?? 'berkas')), 0, 140);
        return ['filename' => $name, 'mime_type' => $mime, 'byte_size' => $size, 'content_base64' => base64_encode(file_get_contents($file['tmp_name']))];
    }
    public static function revision(array $order, ?int $expected): void {
        if ($expected === null || (int)$order['revision'] !== $expected) throw new DomainException('Data berubah di sesi lain. Muat ulang halaman sebelum melanjutkan.');
    }
    public static function orderLinks(PDO $db, array $order): void {
        if (!empty($order['appointment_id'])) {
            $q=$db->prepare('SELECT patient_id FROM appointments WHERE id=?');
            $q->execute([$order['appointment_id']]);
            if ((int)$q->fetchColumn() !== (int)$order['patient_id']) throw new DomainException('Hubungan pasien dan kunjungan pada order tidak sesuai.');
        }
        if (!empty($order['medical_record_id'])) {
            $q=$db->prepare('SELECT patient_id,appointment_id FROM medical_records WHERE id=?');
            $q->execute([$order['medical_record_id']]); $record=$q->fetch(PDO::FETCH_ASSOC);
            if (!$record || (int)$record['patient_id'] !== (int)$order['patient_id'] || (!empty($order['appointment_id']) && (int)$record['appointment_id'] !== (int)$order['appointment_id'])) throw new DomainException('Hubungan rekam medis pada order tidak sesuai.');
        }
    }
    public static function sendFile(array $file): never {
        header('Content-Type: ' . $file['mime_type']);
        header('Content-Disposition: inline; filename="' . rawurlencode($file['filename']) . '"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        echo base64_decode($file['content_base64'], true);
        exit;
    }
}
