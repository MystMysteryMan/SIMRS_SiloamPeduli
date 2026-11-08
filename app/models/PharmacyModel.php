<?php
// app/models/PharmacyModel.php - Model Pelayanan Farmasi (Modul 10 - SOP/SIMRS/010/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';
require_once __DIR__ . '/InventoryModel.php';

class PharmacyModel extends Model {
    protected string $table = 'cpoe_orders';

    /**
     * Ambil antrean resep farmasi dengan filter
     */
    public function getPrescriptionQueue(array $filters = [], int $limit = 20, int $offset = 0): array {
        $where = ["o.order_type = 'RESEP_OBAT'"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(p.full_name ILIKE :search OR p.no_rm ILIKE :search OR o.order_number ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['visit_type'])) {
            $where[] = "o.visit_type = :visit_type";
            $params[':visit_type'] = $filters['visit_type'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT o.*, 
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type, p.allergy_notes,
                   d.full_name AS doctor_name, d.specialization,
                   (SELECT COUNT(*) FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS item_count,
                   (SELECT SUM(subtotal) FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS total_prescription_amount
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            JOIN doctors d ON o.doctor_id = d.id
            WHERE {$whereClause}
            ORDER BY 
                CASE WHEN o.priority = 'CITO' THEN 0 ELSE 1 END,
                o.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        return $this->query($sql, array_merge($params, [
            ':limit'  => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Hitung total resep dalam antrean
     */
    public function countPrescriptionQueue(array $filters = []): int {
        $where = ["o.order_type = 'RESEP_OBAT'"];
        $params = [];

        if (!empty($filters['status'])) {
            $where[] = "o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(p.full_name ILIKE :search OR p.no_rm ILIKE :search OR o.order_number ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['visit_type'])) {
            $where[] = "o.visit_type = :visit_type";
            $params[':visit_type'] = $filters['visit_type'];
        }

        $whereClause = implode(' AND ', $where);
        $sql = "
            SELECT COUNT(*) AS total
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            WHERE {$whereClause}
        ";

        $res = $this->query($sql, $params);
        return (int)($res[0]['total'] ?? 0);
    }

    /**
     * Ambil rincian resep lengkap dengan stok real-time untuk setiap item obat
     */
    public function getPrescriptionDetail(int $orderId): ?array {
        $sql = "
            SELECT o.*, 
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type, p.allergy_notes,
                   d.full_name AS doctor_name, d.specialization
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            JOIN doctors d ON o.doctor_id = d.id
            WHERE o.id = :id AND o.order_type = 'RESEP_OBAT'
        ";

        $orders = $this->query($sql, [':id' => $orderId]);
        if (empty($orders)) {
            return null;
        }

        $order = $orders[0];

        // Ambil item obat beserta stok fisik real-time di tabel medicines
        $itemSql = "
            SELECT oi.*, 
                   m.name AS medicine_name, m.medicine_code, m.medicine_code AS code, 
                   m.category AS medicine_form, m.category AS form,
                   m.unit AS medicine_unit, m.stock AS current_stock, m.location AS medicine_location,
                   m.expiry_date AS medicine_expiry
            FROM cpoe_order_items oi
            LEFT JOIN medicines m ON oi.item_id = m.id
            WHERE oi.cpoe_order_id = :order_id
            ORDER BY oi.id ASC
        ";

        $order['items'] = $this->query($itemSql, [':order_id' => $orderId]);
        return $order;
    }

    /**
     * Validasi ketersediaan stok obat real-time
     */
    public function checkStockSufficiency(int $orderId): array {
        $detail = $this->getPrescriptionDetail($orderId);
        if (!$detail) {
            return [
                'is_sufficient' => false,
                'message' => 'Resep tidak ditemukan',
                'insufficient_items' => []
            ];
        }

        $insufficient = [];
        foreach ($detail['items'] as $item) {
            $required = (int)$item['quantity'];
            $available = (int)($item['current_stock'] ?? 0);

            if ($required > $available) {
                $insufficient[] = [
                    'item_id'       => $item['item_id'],
                    'item_name'     => $item['item_name'] ?? $item['medicine_name'],
                    'required'      => $required,
                    'available'     => $available,
                    'deficit'       => $required - $available
                ];
            }
        }

        return [
            'is_sufficient'      => empty($insufficient),
            'message'            => empty($insufficient) ? 'Stok mencukupi' : 'Stok Obat Tidak Mencukupi!',
            'insufficient_items' => $insufficient
        ];
    }

    /**
     * Apoteker memperbarui kuantitas obat pada telaah resep CPOE
     */
    public function updatePrescriptionQuantities(int $orderId, array $itemsData, int $userId): void {
        $this->db->beginTransaction();
        try {
            $order = $this->getPrescriptionDetail($orderId);
            if (!$order) {
                throw new Exception("Resep obat tidak ditemukan.");
            }
            if ($order['status'] === 'COMPLETED') {
                throw new Exception("Resep yang telah selesai/diserahkan tidak dapat diubah kuantitasnya.");
            }

            $newTotal = 0.0;
            foreach ($itemsData as $itemId => $data) {
                $qty = max(1, (int)($data['quantity'] ?? 1));
                $itemSql = "SELECT unit_price FROM cpoe_order_items WHERE id = :id AND cpoe_order_id = :order_id";
                $curr = $this->query($itemSql, [':id' => $itemId, ':order_id' => $orderId])[0] ?? null;
                if ($curr) {
                    $unitPrice = (float)$curr['unit_price'];
                    $subtotal = $qty * $unitPrice;
                    $this->execute(
                        "UPDATE cpoe_order_items SET quantity = :qty, subtotal = :subtotal WHERE id = :id AND cpoe_order_id = :order_id",
                        [':qty' => $qty, ':subtotal' => $subtotal, ':id' => $itemId, ':order_id' => $orderId]
                    );
                    $newTotal += $subtotal;
                }
            }

            // Update total_amount pada cpoe_orders
            $this->execute(
                "UPDATE cpoe_orders SET total_amount = :total, updated_at = CURRENT_TIMESTAMP WHERE id = :id",
                [':total' => $newTotal, ':id' => $orderId]
            );

            // Bila sudah terbit invoice pending di kasir, perbarui rinciannya
            $this->execute(
                "UPDATE invoices SET total_gross = :total, total_net = :total WHERE reference_id = :order_id AND status != 'PAID'",
                [':total' => $newTotal, ':order_id' => $orderId]
            );

            $this->db->commit();

            AuditLogModel::log($userId, 'UPDATE_PRESCRIPTION_QTY', 'Pelayanan Farmasi', "Apoteker memperbarui kuantitas obat pada resep {$order['order_number']}, total baru: Rp " . number_format($newTotal, 0, ',', '.'));
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Selesai & Potong Stok Atomik (Modul 10 - SOP/SIMRS/010/2026)
     * Menjalankan pemotongan stok obat, perubahan status resep ke COMPLETED,
     * pencatatan pharmacy_dispensings, dan pencatatan mutasi KELUAR.
     */
    public function dispenseAndDeductStock(int $orderId, int $pharmacistId, string $notes = ''): array {
        $detail = $this->getPrescriptionDetail($orderId);
        if (!$detail) {
            throw new Exception("Resep obat tidak ditemukan.");
        }

        if ($detail['status'] === 'COMPLETED') {
            throw new Exception("Resep ini telah selesai diproses sebelumnya.");
        }

        // 1. Validasi stok real-time
        $stockCheck = $this->checkStockSufficiency($orderId);
        if (!$stockCheck['is_sufficient']) {
            $names = array_column($stockCheck['insufficient_items'], 'item_name');
            throw new Exception("Stok Obat Tidak Mencukupi! Barang kurang: " . implode(', ', $names));
        }

        // 2. Eksekusi Atomic DB Transaction
        $this->db->beginTransaction();

        try {
            $totalAmount = 0;

            foreach ($detail['items'] as $item) {
                $medId = (int)$item['item_id'];
                $qty = (int)$item['quantity'];
                $subtotal = (float)$item['subtotal'];
                $totalAmount += $subtotal;

                // Kurangi stok di medicines
                $deductSql = "UPDATE medicines SET stock = stock - :qty WHERE id = :id";
                $deductStmt = $this->db->prepare($deductSql);
                $deductStmt->execute([':qty' => $qty, ':id' => $medId]);

                // Update status item CPOE
                $itemUpdateSql = "UPDATE cpoe_order_items SET status = 'DISPENSED' WHERE id = :item_id";
                $this->db->prepare($itemUpdateSql)->execute([':item_id' => $item['id']]);

                // Catat transaksi mutasi KELUAR di inventory_transactions
                $invModel = new InventoryModel();
                $txNumber = $invModel->generateTransactionNumber();
                $txSql = "
                    INSERT INTO inventory_transactions (
                        transaction_number, item_id, transaction_type, document_number, 
                        quantity, from_location, to_location, performed_by, notes, created_at
                    ) VALUES (
                        :tx_num, :item_id, 'KELUAR', :doc_num, 
                        :qty, 'Apotek Farmasi', 'Pasien / Bangsal', :user_id, :notes, NOW()
                    )
                ";
                $this->db->prepare($txSql)->execute([
                    ':tx_num'   => $txNumber,
                    ':item_id'  => $medId,
                    ':doc_num'  => $detail['order_number'],
                    ':qty'      => $qty,
                    ':user_id'  => $pharmacistId,
                    ':notes'    => 'Dispensing Resep Farmasi: ' . $detail['order_number']
                ]);
            }

            // Update status cpoe_orders menjadi COMPLETED
            $orderUpdateSql = "
                UPDATE cpoe_orders 
                SET status = 'COMPLETED', completed_at = NOW() 
                WHERE id = :id
            ";
            $this->db->prepare($orderUpdateSql)->execute([':id' => $orderId]);

            // Catat log penyerahan obat di pharmacy_dispensings
            $dispSql = "
                INSERT INTO pharmacy_dispensings (
                    cpoe_order_id, prescription_number, patient_id, pharmacist_id, 
                    dispensed_at, total_amount, notes, created_at
                ) VALUES (
                    :order_id, :rx_num, :patient_id, :pharmacist_id, 
                    NOW(), :total_amount, :notes, NOW()
                )
            ";
            $this->db->prepare($dispSql)->execute([
                ':order_id'      => $orderId,
                ':rx_num'        => $detail['order_number'],
                ':patient_id'    => $detail['patient_id'],
                ':pharmacist_id' => $pharmacistId,
                ':total_amount'  => $totalAmount,
                ':notes'         => $notes
            ]);

            // Teruskan ke modul Billing jika ada invoice aktif untuk visit ini
            // Jika pasien rawat jalan / IGD punya visit, agregasi biaya obat
            $this->syncPharmacyToBilling($detail, $totalAmount);

            $this->db->commit();

            // Audit Trail
            $auditLog = new AuditLogModel();
            $auditLog->log(
                $pharmacistId,
                'DISPENSE_PRESCRIPTION',
                "Selesai & potong stok resep farmasi {$detail['order_number']} untuk pasien ID {$detail['patient_id']}. Total: Rp " . number_format($totalAmount, 0, ',', '.')
            );

            return [
                'success'      => true,
                'order_number' => $detail['order_number'],
                'total_amount' => $totalAmount
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Sinkronisasi tagihan farmasi ke modul billing kasir jika belum ada item invoice
     */
    private function syncPharmacyToBilling(array $orderDetail, float $totalAmount): void {
        // Cari invoice terbuka untuk pasien ini
        $invSql = "
            SELECT id FROM invoices 
            WHERE patient_id = :patient_id AND status IN ('UNPAID', 'PARTIALLY_PAID')
            ORDER BY id DESC LIMIT 1
        ";
        $invoices = $this->query($invSql, [':patient_id' => $orderDetail['patient_id']]);

        if (!empty($invoices)) {
            $invoiceId = $invoices[0]['id'];
            // Tambahkan item invoice farmasi
            $insItem = "
                INSERT INTO invoice_items (
                    invoice_id, item_type, item_name, quantity, unit_price, subtotal, created_at
                ) VALUES (
                    :invoice_id, 'FARMASI', :item_name, 1, :amount, :amount, NOW()
                )
            ";
            $this->db->prepare($insItem)->execute([
                ':invoice_id' => $invoiceId,
                ':item_name'  => 'Resep Farmasi: ' . $orderDetail['order_number'],
                ':amount'     => $totalAmount
            ]);

            // Update total invoice
            $updInv = "
                UPDATE invoices 
                SET total_gross = total_gross + :amount,
                    patient_amount = patient_amount + :amount,
                    updated_at = NOW()
                WHERE id = :id
            ";
            $this->db->prepare($updInv)->execute([':amount' => $totalAmount, ':id' => $invoiceId]);
        }
    }

    /**
     * Manajemen Stok Obat Farmasi: Ambil daftar obat
     */
    public function getMedicines(array $filters = [], int $limit = 15, int $offset = 0): array {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(name ILIKE :search OR medicine_code ILIKE :search OR category ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $where[] = "stock <= min_stock AND stock > 0";
            } elseif ($filters['stock_status'] === 'empty') {
                $where[] = "stock = 0";
            } elseif ($filters['stock_status'] === 'available') {
                $where[] = "stock > min_stock";
            }
        }

        if (!empty($filters['expiry_status'])) {
            if ($filters['expiry_status'] === 'expired') {
                $where[] = "expiry_date < CURRENT_DATE";
            } elseif ($filters['expiry_status'] === 'warning') {
                $where[] = "expiry_date >= CURRENT_DATE AND expiry_date <= (CURRENT_DATE + INTERVAL '90 days')";
            }
        }

        $whereClause = implode(' AND ', $where);

        $sql = "
            SELECT *, medicine_code AS code, category AS form, unit_price AS sell_price,
                   CASE 
                       WHEN expiry_date < CURRENT_DATE THEN 'EXPIRED'
                       WHEN expiry_date <= (CURRENT_DATE + INTERVAL '90 days') THEN 'WARNING'
                       ELSE 'SAFE'
                   END AS expiry_category
            FROM medicines
            WHERE {$whereClause}
            ORDER BY name ASC
            LIMIT :limit OFFSET :offset
        ";

        return $this->query($sql, array_merge($params, [
            ':limit'  => $limit,
            ':offset' => $offset
        ]));
    }

    /**
     * Hitung total master obat untuk pagination
     */
    public function countMedicines(array $filters = []): int {
        $where = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(name ILIKE :search OR medicine_code ILIKE :search OR category ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'low') {
                $where[] = "stock <= min_stock AND stock > 0";
            } elseif ($filters['stock_status'] === 'empty') {
                $where[] = "stock = 0";
            } elseif ($filters['stock_status'] === 'available') {
                $where[] = "stock > min_stock";
            }
        }

        $whereClause = implode(' AND ', $where);
        $res = $this->query("SELECT COUNT(*) AS total FROM medicines WHERE {$whereClause}", $params);
        return (int)($res[0]['total'] ?? 0);
    }

    /**
     * Tambah stok obat lama
     */
    public function addMedicineStock(int $medicineId, int $additionalQty, int $userId, string $notes = ''): bool {
        if ($additionalQty <= 0) {
            throw new Exception("Jumlah penambahan stok harus lebih dari 0.");
        }

        $this->db->beginTransaction();
        try {
            $medSql = "SELECT name, medicine_code, medicine_code AS code, stock FROM medicines WHERE id = :id";
            $med = $this->query($medSql, [':id' => $medicineId])[0] ?? null;
            if (!$med) {
                throw new Exception("Obat tidak ditemukan.");
            }

            // Update stok
            $upd = "UPDATE medicines SET stock = stock + :qty WHERE id = :id";
            $this->db->prepare($upd)->execute([':qty' => $additionalQty, ':id' => $medicineId]);

            // Catat ke transaksi gudang MASUK
            $invModel = new InventoryModel();
            $txNum = $invModel->generateTransactionNumber();
            $txSql = "
                INSERT INTO inventory_transactions (
                    transaction_number, item_id, transaction_type, document_number,
                    quantity, to_location, performed_by, notes, created_at
                ) VALUES (
                    :tx_num, :item_id, 'MASUK', 'STK-ADD',
                    :qty, 'Gudang / Apotek Farmasi', :user_id, :notes, NOW()
                )
            ";
            $this->db->prepare($txSql)->execute([
                ':tx_num'  => $txNum,
                ':item_id' => $medicineId,
                ':qty'     => $additionalQty,
                ':user_id' => $userId,
                ':notes'   => $notes ?: "Penambahan stok farmasi: {$med['name']}"
            ]);

            $this->db->commit();

            $auditLog = new AuditLogModel();
            $auditLog->log($userId, 'ADD_MEDICINE_STOCK', "Tambah stok obat {$med['name']} sebanyak {$additionalQty}. Sisa stok: " . ($med['stock'] + $additionalQty));

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Tambah obat baru ke katalog
     */
    public function createMedicine(array $data, int $userId): array {
        $code = trim($data['medicine_code'] ?? $data['code'] ?? '');
        $name = trim($data['name'] ?? '');

        if (empty($code) || empty($name)) {
            throw new Exception("Kode obat dan Nama obat wajib diisi.");
        }

        // Cek duplikasi kode
        $chk = $this->query("SELECT id FROM medicines WHERE medicine_code = :code", [':code' => $code]);
        if (!empty($chk)) {
            throw new Exception("Kode obat '{$code}' telah terdaftar.");
        }

        $stock = (int)($data['stock'] ?? 0);
        $minStock = (int)($data['min_stock'] ?? 10);
        $price = (float)($data['unit_price'] ?? $data['sell_price'] ?? 0);
        $category = $data['category'] ?? $data['form'] ?? 'Tablet';
        $location = $data['location'] ?? 'Gudang Utama';
        $expiry = $data['expiry_date'] ?? date('Y-m-d', strtotime('+1 year'));

        $this->db->beginTransaction();
        try {
            $sql = "
                INSERT INTO medicines (
                    medicine_code, name, category, unit, unit_price, stock, min_stock, location, expiry_date, is_active
                ) VALUES (
                    :code, :name, :cat, :unit, :price, :stock, :min_stock, :location, :expiry, TRUE
                )
            ";
            $this->db->prepare($sql)->execute([
                ':code'      => $code,
                ':name'      => $name,
                ':cat'       => $category,
                ':unit'      => $data['unit'] ?? 'Strip',
                ':price'     => $price,
                ':stock'     => $stock,
                ':min_stock' => $minStock,
                ':location'  => $location,
                ':expiry'    => $expiry
            ]);

            $newId = (int)$this->db->lastInsertId();

            if ($stock > 0) {
                $invModel = new InventoryModel();
                $txNum = $invModel->generateTransactionNumber();
                $txSql = "
                    INSERT INTO inventory_transactions (
                        transaction_number, item_id, transaction_type, document_number,
                        quantity, to_location, performed_by, notes, created_at
                    ) VALUES (
                        :tx_num, :item_id, 'MASUK', 'STK-INIT',
                        :qty, :loc, :user_id, 'Stok Awal Pendaftaran Obat Baru', NOW()
                    )
                ";
                $this->db->prepare($txSql)->execute([
                    ':tx_num'  => $txNum,
                    ':item_id' => $newId,
                    ':qty'     => $stock,
                    ':loc'     => $location,
                    ':user_id' => $userId
                ]);
            }

            $this->db->commit();

            $auditLog = new AuditLogModel();
            $auditLog->log($userId, 'CREATE_MEDICINE', "Registrasi master obat baru: {$name} ({$code})");

            return ['id' => $newId, 'name' => $name];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Statistik antrean & stok farmasi (Termasuk 4 Metrik Kartu Ringkasan)
     */
    public function getPharmacyStats(): array {
        $sql = "
            SELECT 
                (SELECT COUNT(*) FROM cpoe_orders WHERE order_type = 'RESEP_OBAT' AND status IN ('PENDING', 'PROCESS')) AS pending_rx,
                (SELECT COUNT(*) FROM pharmacy_dispensings WHERE DATE(dispensed_at) = CURRENT_DATE) AS dispensed_today,
                (SELECT COUNT(*) FROM medicines) AS total_medicines,
                (SELECT COUNT(*) FROM medicines WHERE stock > min_stock) AS safe_stock_count,
                (SELECT COUNT(*) FROM medicines WHERE stock <= min_stock AND stock > 0) AS low_stock_count,
                (SELECT COUNT(*) FROM medicines WHERE stock <= 0) AS empty_stock_count,
                (SELECT COUNT(*) FROM medicines WHERE expiry_date <= (CURRENT_DATE + INTERVAL '90 days')) AS expiry_warning_count
        ";

        $res = $this->query($sql)[0] ?? [];
        return [
            'pending_rx'           => (int)($res['pending_rx'] ?? 0),
            'dispensed_today'      => (int)($res['dispensed_today'] ?? 0),
            'total_medicines'      => (int)($res['total_medicines'] ?? 0),
            'safe_stock_count'     => (int)($res['safe_stock_count'] ?? 0),
            'low_stock_count'      => (int)($res['low_stock_count'] ?? 0),
            'empty_stock_count'    => (int)($res['empty_stock_count'] ?? 0),
            'expiry_warning_count' => (int)($res['expiry_warning_count'] ?? 0)
        ];
    }
}
