<?php
// app/models/InventoryModel.php - Model Pengelolaan Persediaan & Gudang (Modul 14 - SOP/SIMRS/014/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';

class InventoryModel extends Model {
    protected string $table = 'medicines';

    /**
     * Ambil data stok barang gudang logistik beserta status kedaluwarsa
     */
    public function getStockList(array $filters = [], int $limit = 15, int $offset = 0): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(medicine_code ILIKE :search OR name ILIKE :search OR location ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['location'])) {
            $where[] = "location = :location";
            $params[':location'] = $filters['location'];
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $where[] = "stock <= min_stock AND stock > 0";
            } elseif ($filters['stock_status'] === 'empty') {
                $where[] = "stock = 0";
            } elseif ($filters['stock_status'] === 'safe') {
                $where[] = "stock > min_stock";
            }
        }

        if (!empty($filters['expiry_status'])) {
            if ($filters['expiry_status'] === 'expired') {
                $where[] = "expiry_date < CURRENT_DATE";
            } elseif ($filters['expiry_status'] === 'warning') {
                $where[] = "expiry_date >= CURRENT_DATE AND expiry_date <= (CURRENT_DATE + INTERVAL '90 days')";
            } elseif ($filters['expiry_status'] === 'safe') {
                $where[] = "expiry_date > (CURRENT_DATE + INTERVAL '90 days')";
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT id, medicine_code AS code, medicine_code, name, category AS form, category, 
                   unit, unit_price AS sell_price, unit_price, stock, min_stock, location, expiry_date,
                   CASE 
                       WHEN expiry_date < CURRENT_DATE THEN 'EXPIRED'
                       WHEN expiry_date <= (CURRENT_DATE + INTERVAL '90 days') THEN 'WARNING'
                       ELSE 'SAFE'
                   END AS expiry_status,
                   CASE 
                       WHEN stock = 0 THEN 'EMPTY'
                       WHEN stock <= min_stock THEN 'LOW'
                       ELSE 'SAFE'
                   END AS stock_level,
                   (expiry_date - CURRENT_DATE) AS days_until_expiry
            FROM medicines
            WHERE {$whereClause}
            ORDER BY 
                CASE 
                    WHEN expiry_date < CURRENT_DATE THEN 0
                    WHEN expiry_date <= (CURRENT_DATE + INTERVAL '90 days') THEN 1
                    WHEN stock <= min_stock THEN 2
                    ELSE 3
                END,
                name ASC
            LIMIT :limit OFFSET :offset
        ";

        return $this->query($sql, array_merge($params, [
            ':limit'  => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Hitung total barang gudang untuk pagination
     */
    public function countStockList(array $filters = []): int {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(medicine_code ILIKE :search OR name ILIKE :search OR location ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['location'])) {
            $where[] = "location = :location";
            $params[':location'] = $filters['location'];
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $where[] = "stock <= min_stock AND stock > 0";
            } elseif ($filters['stock_status'] === 'empty') {
                $where[] = "stock = 0";
            } elseif ($filters['stock_status'] === 'safe') {
                $where[] = "stock > min_stock";
            }
        }

        if (!empty($filters['expiry_status'])) {
            if ($filters['expiry_status'] === 'expired') {
                $where[] = "expiry_date < CURRENT_DATE";
            } elseif ($filters['expiry_status'] === 'warning') {
                $where[] = "expiry_date >= CURRENT_DATE AND expiry_date <= (CURRENT_DATE + INTERVAL '90 days')";
            } elseif ($filters['expiry_status'] === 'safe') {
                $where[] = "expiry_date > (CURRENT_DATE + INTERVAL '90 days')";
            }
        }

        $whereClause = implode(' AND ', $where);
        $res = $this->query("SELECT COUNT(*) AS total FROM medicines WHERE {$whereClause}", $params);
        return (int)($res[0]['total'] ?? 0);
    }

    /**
     * Statistik ringkasan gudang
     */
    public function getInventoryStats(): array {
        $sql = "
            SELECT 
                COUNT(*) AS total_items,
                COUNT(*) FILTER (WHERE stock <= min_stock) AS low_stock_items,
                COUNT(*) FILTER (WHERE expiry_date <= (CURRENT_DATE + INTERVAL '90 days') AND expiry_date >= CURRENT_DATE) AS warning_expiry_items,
                COUNT(*) FILTER (WHERE expiry_date < CURRENT_DATE) AS expired_items,
                (SELECT COUNT(*) FROM purchase_requests WHERE status = 'PENDING') AS pending_pr_count,
                (SELECT COUNT(*) FROM inventory_transactions WHERE DATE(created_at) = CURRENT_DATE) AS transactions_today
            FROM medicines
        ";

        $res = $this->query($sql)[0] ?? [];
        return [
            'total_items'          => (int)($res['total_items'] ?? 0),
            'low_stock_items'      => (int)($res['low_stock_items'] ?? 0),
            'warning_expiry_items' => (int)($res['warning_expiry_items'] ?? 0),
            'expired_items'        => (int)($res['expired_items'] ?? 0),
            'pending_pr_count'     => (int)($res['pending_pr_count'] ?? 0),
            'transactions_today'   => (int)($res['transactions_today'] ?? 0)
        ];
    }

    /**
     * Generate nomor Purchase Request unik: PR-YYYYMMDD-XXXX
     */
    public function generatePRNumber(): string {
        $prefix = 'PR-' . date('Ymd') . '-';
        $sql = "SELECT pr_number FROM purchase_requests WHERE pr_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $lastNumber = $row[0]['pr_number'];
            $parts = explode('-', $lastNumber);
            $seq = (int)end($parts);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        do {
            $candidate = $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            $chk = $this->query("SELECT id FROM purchase_requests WHERE pr_number = :c", [':c' => $candidate]);
            if (empty($chk)) {
                return $candidate;
            }
            $nextSeq++;
        } while (true);
    }

    /**
     * Buat Pengajuan Pembelian / Purchase Request (PR) baru
     */
    public function createPurchaseRequest(array $data, int $userId): array {
        if (empty($data['item_name']) || empty($data['quantity']) || (int)$data['quantity'] <= 0) {
            throw new Exception("Nama barang dan jumlah kebutuhan (> 0) wajib diisi.");
        }

        $prNumber = $this->generatePRNumber();

        $sql = "
            INSERT INTO purchase_requests (
                pr_number, item_id, item_name, quantity, unit, notes, requested_by, status, created_at, updated_at
            ) VALUES (
                :pr_num, :item_id, :item_name, :qty, :unit, :notes, :user_id, 'PENDING', NOW(), NOW()
            )
        ";

        $this->db->prepare($sql)->execute([
            ':pr_num'    => $prNumber,
            ':item_id'   => !empty($data['item_id']) ? (int)$data['item_id'] : null,
            ':item_name' => trim($data['item_name']),
            ':qty'       => (int)$data['quantity'],
            ':unit'      => $data['unit'] ?? 'Pcs',
            ':notes'     => $data['notes'] ?? '',
            ':user_id'   => $userId
        ]);

        $prId = (int)$this->db->lastInsertId();

        $auditLog = new AuditLogModel();
        $auditLog->log($userId, 'CREATE_PURCHASE_REQUEST', "Mengajukan PR {$prNumber} untuk item {$data['item_name']} sebanyak {$data['quantity']}");

        return ['id' => $prId, 'pr_number' => $prNumber];
    }

    /**
     * Ambil daftar Purchase Request
     */
    public function getPurchaseRequests(array $filters = [], int $limit = 15, int $offset = 0): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "pr.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(pr.pr_number ILIKE :search OR pr.item_name ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT pr.*, u.full_name AS requester_name, u.role AS requester_role
            FROM purchase_requests pr
            LEFT JOIN users u ON pr.requested_by = u.id
            WHERE {$whereClause}
            ORDER BY pr.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        return $this->query($sql, array_merge($params, [
            ':limit'  => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Hitung total Purchase Request
     */
    public function countPurchaseRequests(array $filters = []): int {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "pr.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(pr.pr_number ILIKE :search OR pr.item_name ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $res = $this->query("SELECT COUNT(*) AS total FROM purchase_requests pr WHERE {$whereClause}", $params);
        return (int)($res[0]['total'] ?? 0);
    }

    /**
     * Update status Purchase Request (APPROVED, REJECTED, ORDERED, RECEIVED)
     */
    public function updatePRStatus(int $prId, string $status, int $userId): bool {
        $validStatuses = ['PENDING', 'APPROVED', 'REJECTED', 'ORDERED', 'RECEIVED'];
        if (!in_array($status, $validStatuses)) {
            throw new Exception("Status PR tidak valid: {$status}");
        }

        $sql = "UPDATE purchase_requests SET status = :status, updated_at = NOW() WHERE id = :id";
        $this->db->prepare($sql)->execute([':status' => $status, ':id' => $prId]);

        $auditLog = new AuditLogModel();
        $auditLog->log($userId, 'UPDATE_PR_STATUS', "Update status PR ID {$prId} menjadi {$status}");

        return true;
    }

    /**
     * Generate nomor transaksi mutasi unik: TRX-YYYYMMDD-XXXX
     */
    public function generateTransactionNumber(): string {
        $prefix = 'TRX-' . date('Ymd') . '-';
        $sql = "SELECT transaction_number FROM inventory_transactions WHERE transaction_number ~ '^TRX-[0-9]{8}-[0-9]+$' ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql);

        if (!empty($row)) {
            $lastNumber = $row[0]['transaction_number'];
            $parts = explode('-', $lastNumber);
            $seq = (int)end($parts);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        do {
            $candidate = $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
            $chk = $this->query("SELECT id FROM inventory_transactions WHERE transaction_number = :c", [':c' => $candidate]);
            if (empty($chk)) {
                return $candidate;
            }
            $nextSeq++;
        } while (true);
    }

    /**
     * Penerimaan Barang Masuk (Goods In) & Expiry Date
     */
    public function receiveGoods(array $data, int $userId): array {
        if (empty($data['item_id']) || empty($data['quantity']) || (int)$data['quantity'] <= 0) {
            throw new Exception("Pilih barang dan jumlah penerimaan (> 0) wajib diisi.");
        }

        $itemId = (int)$data['item_id'];
        $qty = (int)$data['quantity'];
        $docNum = trim($data['document_number'] ?? 'SJ-' . date('Ymd'));
        $expiryDate = !empty($data['expiry_date']) ? $data['expiry_date'] : null;
        $toLocation = $data['to_location'] ?? 'Gudang Utama';
        $notes = $data['notes'] ?? 'Penerimaan barang dari supplier';

        $txNumber = $this->generateTransactionNumber();

        $this->db->beginTransaction();
        try {
            // Ambil info barang
            $med = $this->query("SELECT name, stock FROM medicines WHERE id = :id", [':id' => $itemId])[0] ?? null;
            if (!$med) {
                throw new Exception("Barang tidak ditemukan.");
            }

            // Update stok dan tanggal kedaluwarsa jika diberikan
            if ($expiryDate) {
                $updMed = "UPDATE medicines SET stock = stock + :qty, expiry_date = :expiry, location = :loc WHERE id = :id";
                $this->db->prepare($updMed)->execute([
                    ':qty'    => $qty,
                    ':expiry' => $expiryDate,
                    ':loc'    => $toLocation,
                    ':id'     => $itemId
                ]);
            } else {
                $updMed = "UPDATE medicines SET stock = stock + :qty, location = :loc WHERE id = :id";
                $this->db->prepare($updMed)->execute([
                    ':qty' => $qty,
                    ':loc' => $toLocation,
                    ':id'  => $itemId
                ]);
            }

            // Catat transaksi MASUK
            $insTx = "
                INSERT INTO inventory_transactions (
                    transaction_number, item_id, transaction_type, document_number,
                    quantity, to_location, expiry_date, performed_by, notes, created_at
                ) VALUES (
                    :tx_num, :item_id, 'MASUK', :doc_num,
                    :qty, :to_loc, :expiry, :user_id, :notes, NOW()
                )
            ";
            $this->db->prepare($insTx)->execute([
                ':tx_num'  => $txNumber,
                ':item_id' => $itemId,
                ':doc_num' => $docNum,
                ':qty'     => $qty,
                ':to_loc'  => $toLocation,
                ':expiry'  => $expiryDate,
                ':user_id' => $userId,
                ':notes'   => $notes
            ]);

            $this->db->commit();

            $auditLog = new AuditLogModel();
            $auditLog->log($userId, 'GOODS_IN', "Penerimaan barang {$med['name']} sebanyak {$qty} (Dok: {$docNum}). Sisa stok: " . ($med['stock'] + $qty));

            return ['transaction_number' => $txNumber, 'new_stock' => $med['stock'] + $qty];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Pengeluaran Barang ke Unit (Goods Out) dengan validasi ketat stok
     */
    public function issueGoods(array $data, int $userId): array {
        if (empty($data['item_id']) || empty($data['quantity']) || (int)$data['quantity'] <= 0) {
            throw new Exception("Pilih barang dan jumlah pengeluaran (> 0) wajib diisi.");
        }

        $itemId = (int)$data['item_id'];
        $qty = (int)$data['quantity'];
        $docNum = trim($data['document_number'] ?? 'REQ-' . date('Ymd'));
        $unitDestination = trim($data['unit_destination'] ?? 'Poliklinik');
        $notes = $data['notes'] ?? 'Pengeluaran permintaan unit';

        // Validasi stok
        $med = $this->query("SELECT name, stock, location FROM medicines WHERE id = :id", [':id' => $itemId])[0] ?? null;
        if (!$med) {
            throw new Exception("Barang tidak ditemukan.");
        }

        if ((int)$med['stock'] < $qty) {
            throw new Exception("Stok Tidak Mencukupi! Sisa stok barang '{$med['name']}' adalah {$med['stock']} (diminta: {$qty}).");
        }

        $txNumber = $this->generateTransactionNumber();

        $this->db->beginTransaction();
        try {
            // Kurangi stok
            $updMed = "UPDATE medicines SET stock = stock - :qty WHERE id = :id";
            $this->db->prepare($updMed)->execute([':qty' => $qty, ':id' => $itemId]);

            // Catat transaksi KELUAR
            $insTx = "
                INSERT INTO inventory_transactions (
                    transaction_number, item_id, transaction_type, document_number,
                    quantity, from_location, to_location, performed_by, notes, created_at
                ) VALUES (
                    :tx_num, :item_id, 'KELUAR', :doc_num,
                    :qty, :from_loc, :to_loc, :user_id, :notes, NOW()
                )
            ";
            $this->db->prepare($insTx)->execute([
                ':tx_num'   => $txNumber,
                ':item_id'  => $itemId,
                ':doc_num'  => $docNum,
                ':qty'      => $qty,
                ':from_loc' => $med['location'] ?: 'Gudang Utama',
                ':to_loc'   => $unitDestination,
                ':user_id'  => $userId,
                ':notes'    => $notes
            ]);

            $this->db->commit();

            $auditLog = new AuditLogModel();
            $auditLog->log($userId, 'GOODS_OUT', "Pengeluaran barang {$med['name']} sebanyak {$qty} ke unit {$unitDestination}. Sisa stok: " . ($med['stock'] - $qty));

            return ['transaction_number' => $txNumber, 'new_stock' => $med['stock'] - $qty];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Mutasi Stok Antar-Lokasi Gudang / Depo
     */
    public function mutateStock(array $data, int $userId): array {
        if (empty($data['item_id']) || empty($data['quantity']) || (int)$data['quantity'] <= 0) {
            throw new Exception("Pilih barang dan kuantitas mutasi (> 0) wajib diisi.");
        }

        $fromLoc = trim($data['from_location'] ?? '');
        $toLoc = trim($data['to_location'] ?? '');

        if (empty($fromLoc) || empty($toLoc)) {
            throw new Exception("Lokasi asal dan lokasi tujuan mutasi wajib dipilih.");
        }

        if ($fromLoc === $toLoc) {
            throw new Exception("Lokasi tujuan tidak boleh sama dengan lokasi asal.");
        }

        $itemId = (int)$data['item_id'];
        $qty = (int)$data['quantity'];
        $notes = $data['notes'] ?? "Mutasi persediaan dari {$fromLoc} ke {$toLoc}";

        // Validasi stok
        $med = $this->query("SELECT name, stock FROM medicines WHERE id = :id", [':id' => $itemId])[0] ?? null;
        if (!$med) {
            throw new Exception("Barang tidak ditemukan.");
        }

        if ((int)$med['stock'] < $qty) {
            throw new Exception("Stok Asal Tidak Mencukupi! Sisa stok '{$med['name']}' adalah {$med['stock']} (hendak dimutasi: {$qty}).");
        }

        $txNumber = $this->generateTransactionNumber();

        $this->db->beginTransaction();
        try {
            // Catat transaksi mutasi
            $insTx = "
                INSERT INTO inventory_transactions (
                    transaction_number, item_id, transaction_type, document_number,
                    quantity, from_location, to_location, performed_by, notes, created_at
                ) VALUES (
                    :tx_num, :item_id, 'MUTASI', :doc_num,
                    :qty, :from_loc, :to_loc, :user_id, :notes, NOW()
                )
            ";
            $this->db->prepare($insTx)->execute([
                ':tx_num'   => $txNumber,
                ':item_id'  => $itemId,
                ':doc_num'  => 'MUT-' . date('Ymd'),
                ':qty'      => $qty,
                ':from_loc' => $fromLoc,
                ':to_loc'   => $toLoc,
                ':user_id'  => $userId,
                ':notes'    => $notes
            ]);

            $this->db->commit();

            $auditLog = new AuditLogModel();
            $auditLog->log($userId, 'STOCK_MUTATION', "Mutasi barang {$med['name']} sebanyak {$qty} dari {$fromLoc} ke {$toLoc}");

            return ['transaction_number' => $txNumber, 'item_name' => $med['name']];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Ambil buku mutasi / riwayat transaksi persediaan
     */
    public function getTransactions(array $filters = [], int $limit = 20, int $offset = 0): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['transaction_type'])) {
            $where[] = "t.transaction_type = :type";
            $params[':type'] = $filters['transaction_type'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(t.transaction_number ILIKE :search OR m.name ILIKE :search OR m.medicine_code ILIKE :search OR t.document_number ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT t.*, m.name AS item_name, m.medicine_code AS item_code, m.medicine_code AS code, m.unit AS item_unit,
                   u.full_name AS officer_name
            FROM inventory_transactions t
            JOIN medicines m ON t.item_id = m.id
            LEFT JOIN users u ON t.performed_by = u.id
            WHERE {$whereClause}
            ORDER BY t.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        return $this->query($sql, array_merge($params, [
            ':limit'  => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Hitung total transaksi persediaan
     */
    public function countTransactions(array $filters = []): int {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['transaction_type'])) {
            $where[] = "t.transaction_type = :type";
            $params[':type'] = $filters['transaction_type'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(t.transaction_number ILIKE :search OR m.name ILIKE :search OR m.medicine_code ILIKE :search OR t.document_number ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $whereClause = implode(' AND ', $where);
        $res = $this->query("SELECT COUNT(*) AS total FROM inventory_transactions t JOIN medicines m ON t.item_id = m.id WHERE {$whereClause}", $params);
        return (int)($res[0]['total'] ?? 0);
    }
}
