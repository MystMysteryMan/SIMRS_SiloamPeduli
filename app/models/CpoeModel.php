<?php
// app/models/CpoeModel.php - Model CPOE / Order Management (Modul 4 - SOP/SIMRS/004/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';

class CpoeModel extends Model {
    protected string $table = 'cpoe_orders';

    /**
     * Generate nomor order unik: ORD-YYYYMMDD-XXXX
     */
    public function generateOrderNumber(): string {
        $prefix = 'ORD-' . date('Ymd') . '-';
        $sql = "SELECT order_number FROM cpoe_orders WHERE order_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $lastNumber = $row[0]['order_number'];
            $seq = (int)substr($lastNumber, -4);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Buat clinical order CPOE baru (E-Resep, Tindakan Medis, Lab, Radiologi)
     */
    public function createOrder(array $orderData, array $items, int $userId): array {
        $orderNumber = $this->generateOrderNumber();
        $this->db->beginTransaction();

        try {
            $sql = "
                INSERT INTO cpoe_orders (
                    order_number, patient_id, doctor_id, visit_type, reference_id, 
                    order_type, priority, status, clinical_notes, created_at
                ) VALUES (
                    :order_number, :patient_id, :doctor_id, :visit_type, :reference_id, 
                    :order_type, :priority, 'PENDING', :clinical_notes, NOW()
                )
            ";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':order_number'   => $orderNumber,
                ':patient_id'     => $orderData['patient_id'],
                ':doctor_id'      => $orderData['doctor_id'],
                ':visit_type'     => $orderData['visit_type'] ?? 'RAWAT_JALAN',
                ':reference_id'   => $orderData['reference_id'] ?? null,
                ':order_type'     => $orderData['order_type'],
                ':priority'       => $orderData['priority'] ?? 'REGULER',
                ':clinical_notes' => $orderData['clinical_notes'] ?? null
            ]);

            $orderId = (int)$this->db->lastInsertId();

            // Insert item-item order
            $itemStmt = $this->db->prepare("
                INSERT INTO cpoe_order_items (
                    cpoe_order_id, item_type, item_id, item_name, dosage, 
                    frequency, route, quantity, unit_price, subtotal, instructions, status
                ) VALUES (
                    :cpoe_order_id, :item_type, :item_id, :item_name, :dosage, 
                    :frequency, :route, :quantity, :unit_price, :subtotal, :instructions, 'PENDING'
                )
            ");

            foreach ($items as $item) {
                $qty = isset($item['quantity']) ? (int)$item['quantity'] : 1;
                $unitPrice = 0.00;

                // Lookup harga dari master obat atau master tarif
                if (($item['item_type'] ?? '') === 'MEDICINE' && !empty($item['item_id'])) {
                    $med = $this->query("SELECT unit_price, name FROM medicines WHERE id = :id", [':id' => $item['item_id']]);
                    if (!empty($med)) {
                        $unitPrice = (float)$med[0]['unit_price'];
                        if (empty($item['item_name'])) $item['item_name'] = $med[0]['name'];
                    }
                } elseif (($item['item_type'] ?? '') === 'PROCEDURE' && !empty($item['item_id'])) {
                    $tar = $this->query("SELECT amount, name FROM tariffs WHERE id = :id", [':id' => $item['item_id']]);
                    if (!empty($tar)) {
                        $unitPrice = (float)$tar[0]['amount'];
                        if (empty($item['item_name'])) $item['item_name'] = $tar[0]['name'];
                    }
                } else {
                    $unitPrice = isset($item['unit_price']) ? (float)$item['unit_price'] : 0.00;
                }

                $subtotal = $qty * $unitPrice;

                $itemStmt->execute([
                    ':cpoe_order_id' => $orderId,
                    ':item_type'     => $item['item_type'] ?? 'MEDICINE',
                    ':item_id'       => $item['item_id'] ?? null,
                    ':item_name'     => $item['item_name'] ?? 'Item Pelayanan',
                    ':dosage'        => $item['dosage'] ?? null,
                    ':frequency'     => $item['frequency'] ?? null,
                    ':route'         => $item['route'] ?? null,
                    ':quantity'      => $qty,
                    ':unit_price'    => $unitPrice,
                    ':subtotal'      => $subtotal,
                    ':instructions'  => $item['instructions'] ?? null
                ]);
            }

            // Integrasi langsung dengan modul penunjang jika order Lab atau Radiologi
            if ($orderData['order_type'] === 'LABORATORIUM' && !empty($items)) {
                $this->syncToLabOrder($orderId, $orderData, $items);
            } elseif ($orderData['order_type'] === 'RADIOLOGI' && !empty($items)) {
                $this->syncToRadiologyOrder($orderId, $orderData, $items);
            }

            $this->db->commit();

            AuditLogModel::log(
                'CREATE_ORDER',
                'CPOE',
                "Order {$orderData['order_type']} diterbitkan dengan nomor {$orderNumber} (ID: {$orderId})",
                $userId
            );

            return ['success' => true, 'order_id' => $orderId, 'order_number' => $orderNumber];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Integrasikan order Lab dari CPOE ke tabel lab_orders (Modul 8)
     */
    private function syncToLabOrder(int $cpoeOrderId, array $orderData, array $items): void {
        $prefix = 'LAB-' . date('Ymd') . '-';
        $lastLab = $this->query("SELECT order_number FROM lab_orders WHERE order_number LIKE :p ORDER BY id DESC LIMIT 1", [':p' => $prefix . '%']);
        $seq = !empty($lastLab) ? ((int)substr($lastLab[0]['order_number'], -4) + 1) : 1;
        $labNumber = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $mrid = (($orderData['visit_type'] ?? '') === 'RAWAT_JALAN' && !empty($orderData['reference_id'])) ? (int)$orderData['reference_id'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO lab_orders (
                order_number, patient_id, medical_record_id, doctor_id, clinical_priority, status, clinical_notes, created_at
            ) VALUES (
                :order_num, :pid, :mrid, :docid, :priority, 'PENDING', :notes, NOW()
            )
        ");
        $stmt->execute([
            ':order_num' => $labNumber,
            ':pid'       => $orderData['patient_id'],
            ':mrid'      => $mrid,
            ':docid'     => $orderData['doctor_id'],
            ':priority'  => $orderData['priority'] ?? 'REGULER',
            ':notes'     => $orderData['clinical_notes'] ?? 'Order via CPOE'
        ]);
        $labId = (int)$this->db->lastInsertId();

        $itemStmt = $this->db->prepare("INSERT INTO lab_order_items (lab_order_id, lab_test_id, test_name) VALUES (?, ?, ?)");
        foreach ($items as $it) {
            $itemStmt->execute([$labId, $it['item_id'] ?? null, $it['item_name']]);
        }
    }

    /**
     * Integrasikan order Radiologi dari CPOE ke tabel radiology_orders (Modul 9)
     */
    private function syncToRadiologyOrder(int $cpoeOrderId, array $orderData, array $items): void {
        $prefix = 'RAD-' . date('Ymd') . '-';
        $lastRad = $this->query("SELECT order_number FROM radiology_orders WHERE order_number LIKE :p ORDER BY id DESC LIMIT 1", [':p' => $prefix . '%']);
        $seq = !empty($lastRad) ? ((int)substr($lastRad[0]['order_number'], -4) + 1) : 1;
        $radNumber = $prefix . str_pad((string)$seq, 4, '0', STR_PAD_LEFT);

        $modality = $items[0]['item_name'] ?? 'X-RAY THORAX';
        $mrid = (($orderData['visit_type'] ?? '') === 'RAWAT_JALAN' && !empty($orderData['reference_id'])) ? (int)$orderData['reference_id'] : null;

        $stmt = $this->db->prepare("
            INSERT INTO radiology_orders (
                order_number, patient_id, medical_record_id, doctor_id, modality_type, 
                clinical_indication, clinical_priority, status, created_at
            ) VALUES (
                :order_num, :pid, :mrid, :docid, :modality, :indication, :priority, 'PENDING', NOW()
            )
        ");
        $stmt->execute([
            ':order_num'   => $radNumber,
            ':pid'         => $orderData['patient_id'],
            ':mrid'        => $mrid,
            ':docid'       => $orderData['doctor_id'],
            ':modality'    => $modality,
            ':indication'  => $orderData['clinical_notes'] ?? 'Order via CPOE',
            ':priority'    => $orderData['priority'] ?? 'REGULER'
        ]);
    }

    /**
     * Ambil seluruh order CPOE dengan filter
     */
    public function getAllOrders(array $filters = []): array {
        $sql = "
            SELECT o.*, 
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type,
                   d.full_name AS doctor_name, d.specialization,
                   (SELECT COUNT(*) FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS item_count,
                   (SELECT STRING_AGG(item_name, ', ') FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS item_names_preview
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            JOIN doctors d ON o.doctor_id = d.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['order_type'])) {
            $sql .= " AND o.order_type = :order_type";
            $params[':order_type'] = $filters['order_type'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND o.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['patient_id'])) {
            $sql .= " AND o.patient_id = :patient_id";
            $params[':patient_id'] = $filters['patient_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number LIKE :search OR p.full_name LIKE :search OR p.no_rm LIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY o.id DESC";

        if (isset($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        return $this->query($sql, $params);
    }

    /**
     * Ambil seluruh order CPOE dengan Pagination
     */
    public function paginateOrders(array $filters = [], int $page = 1, int $perPage = 15): array {
        $sql = "
            SELECT o.*, 
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type,
                   d.full_name AS doctor_name, d.specialization,
                   (SELECT COUNT(*) FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS item_count,
                   (SELECT STRING_AGG(item_name, ', ') FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS item_names_preview
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            JOIN doctors d ON o.doctor_id = d.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND o.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['order_type'])) {
            $sql .= " AND o.order_type = :order_type";
            $params[':order_type'] = $filters['order_type'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND o.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }

        if (!empty($filters['patient_id'])) {
            $sql .= " AND o.patient_id = :patient_id";
            $params[':patient_id'] = $filters['patient_id'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (o.order_number ILIKE :search OR p.full_name ILIKE :search OR p.no_rm ILIKE :search)";
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY o.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Ambil detail order CPOE beserta item rinciannya
     */
    public function getOrderDetail(int $orderId): ?array {
        $sql = "
            SELECT o.*, 
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type, p.allergy_notes,
                   d.full_name AS doctor_name, d.specialization
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            JOIN doctors d ON o.doctor_id = d.id
            WHERE o.id = :id
        ";
        $res = $this->query($sql, [':id' => $orderId]);
        if (empty($res)) return null;

        $order = $res[0];
        $order['items'] = $this->query("SELECT * FROM cpoe_order_items WHERE cpoe_order_id = :id", [':id' => $orderId]);
        return $order;
    }

    /**
     * Antrean Farmasi (E-Resep yang belum diserahkan)
     */
    public function getPharmacyQueue(): array {
        $sql = "
            SELECT o.*, 
                   p.full_name AS patient_name, p.no_rm, p.gender, p.birth_date, p.insurance_type, p.allergy_notes,
                   d.full_name AS doctor_name, d.specialization,
                   (SELECT COUNT(*) FROM cpoe_order_items WHERE cpoe_order_id = o.id) AS item_count
            FROM cpoe_orders o
            JOIN patients p ON o.patient_id = p.id
            JOIN doctors d ON o.doctor_id = d.id
            WHERE o.order_type = 'RESEP_OBAT'
              AND o.status IN ('PENDING', 'PROCESS')
            ORDER BY o.priority = 'CITO' DESC, o.id ASC
        ";
        $orders = $this->query($sql);
        if (!empty($orders)) {
            $orderIds = array_column($orders, 'id');
            $placeholders = implode(',', array_map('intval', $orderIds));
            $allItems = $this->query("SELECT * FROM cpoe_order_items WHERE cpoe_order_id IN ({$placeholders}) ORDER BY id ASC");
            $itemsByOrder = [];
            foreach ($allItems as $it) {
                $itemsByOrder[$it['cpoe_order_id']][] = $it;
            }
            foreach ($orders as &$ord) {
                $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
            }
        }
        return $orders;
    }

    /**
     * Apoteker menandai resep sudah disiapkan dan diserahkan (Dispensed / Completed)
     */
    public function dispensePrescription(int $orderId, int $userId): bool {
        $this->db->beginTransaction();
        try {
            $this->execute(
                "UPDATE cpoe_orders SET status = 'COMPLETED', completed_at = NOW() WHERE id = :id",
                [':id' => $orderId]
            );
            $this->execute(
                "UPDATE cpoe_order_items SET status = 'DISPENSED' WHERE cpoe_order_id = :id",
                [':id' => $orderId]
            );
            $this->db->commit();

            AuditLogModel::log(
                'DISPENSE_PRESCRIPTION',
                'CPOE',
                "Resep order ID {$orderId} selesai disiapkan dan diserahkan ke pasien",
                $userId
            );
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Perawat mengeksekusi instruksi keperawatan (Nursing order completed)
     */
    public function executeNursingOrder(int $orderId, int $userId): bool {
        $this->db->beginTransaction();
        try {
            $this->execute(
                "UPDATE cpoe_orders SET status = 'COMPLETED', completed_at = NOW() WHERE id = :id",
                [':id' => $orderId]
            );
            $this->execute(
                "UPDATE cpoe_order_items SET status = 'EXECUTED' WHERE cpoe_order_id = :id",
                [':id' => $orderId]
            );
            $this->db->commit();

            AuditLogModel::log(
                'EXECUTE_NURSING_ORDER',
                'CPOE',
                "Instruksi tindakan medis / keperawatan order ID {$orderId} selesai dilaksanakan",
                $userId
            );
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Batalkan order CPOE
     */
    public function cancelOrder(int $orderId, int $userId, string $reason): bool {
        $this->db->beginTransaction();
        try {
            $this->execute(
                "UPDATE cpoe_orders SET status = 'CANCELLED', clinical_notes = COALESCE(clinical_notes, '') || ' [DIBATALKAN: ' || :reason || ']' WHERE id = :id",
                [':id' => $orderId, ':reason' => $reason]
            );
            $this->execute(
                "UPDATE cpoe_order_items SET status = 'CANCELLED' WHERE cpoe_order_id = :id",
                [':id' => $orderId]
            );
            $this->db->commit();

            AuditLogModel::log(
                'CANCEL_ORDER',
                'CPOE',
                "Order CPOE ID {$orderId} dibatalkan dengan alasan: {$reason}",
                $userId
            );
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Statistik Modul CPOE
     */
    public function getCpoeStats(): array {
        $sql = "
            SELECT 
                COUNT(*) FILTER (WHERE DATE(created_at) = CURRENT_DATE) AS total_today,
                COUNT(*) FILTER (WHERE order_type = 'RESEP_OBAT' AND status IN ('PENDING', 'PROCESS')) AS pending_pharmacy,
                COUNT(*) FILTER (WHERE order_type = 'TINDAKAN_MEDIS' AND status IN ('PENDING', 'PROCESS')) AS pending_nursing,
                COUNT(*) FILTER (WHERE status = 'COMPLETED' AND DATE(completed_at) = CURRENT_DATE) AS completed_today
            FROM cpoe_orders
        ";
        $row = $this->query($sql)[0] ?? [];
        return [
            'total_today'      => (int)($row['total_today'] ?? 0),
            'pending_pharmacy' => (int)($row['pending_pharmacy'] ?? 0),
            'pending_nursing'  => (int)($row['pending_nursing'] ?? 0),
            'completed_today'  => (int)($row['completed_today'] ?? 0),
        ];
    }
}
