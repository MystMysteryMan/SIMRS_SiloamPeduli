<?php
// app/models/BillingModel.php - Model Billing & Insurance (Modul 11 - SOP/SIMRS/011/2026)

require_once __DIR__ . '/../../core/Model.php';
require_once __DIR__ . '/AuditLogModel.php';

class BillingModel extends Model {
    protected string $table = 'invoices';

    /**
     * Generate Nomor Faktur / Invoice: INV-YYYYMMDD-XXXX
     */
    public function generateInvoiceNumber(): string {
        $prefix = 'INV-' . date('Ymd') . '-';
        $sql = "SELECT invoice_number FROM invoices WHERE invoice_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $last = $row[0]['invoice_number'];
            $seq = (int)substr($last, -4);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Nomor Klaim Asuransi: CLM-YYYYMMDD-XXXX
     */
    public function generateClaimNumber(): string {
        $prefix = 'CLM-' . date('Ymd') . '-';
        $sql = "SELECT claim_number FROM insurance_claims WHERE claim_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $last = $row[0]['claim_number'];
            $seq = (int)substr($last, -4);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Nomor Kuitansi Pembayaran: KW-YYYYMMDD-XXXX
     */
    public function generateReceiptNumber(): string {
        $prefix = 'KW-' . date('Ymd') . '-';
        $sql = "SELECT receipt_number FROM payments WHERE receipt_number LIKE :prefix ORDER BY id DESC LIMIT 1";
        $row = $this->query($sql, [':prefix' => $prefix . '%']);

        if (!empty($row)) {
            $last = $row[0]['receipt_number'];
            $seq = (int)substr($last, -4);
            $nextSeq = $seq + 1;
        } else {
            $nextSeq = 1;
        }

        return $prefix . str_pad((string)$nextSeq, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Ambil daftar kunjungan yang belum memiliki tagihan / belum lunas
     */
    public function getUnbilledVisits(): array {
        // 1. Rawat Jalan (Appointments)
        $sqlAppt = "
            SELECT 'RAWAT_JALAN' AS visit_type,
                   a.id AS reference_id,
                   a.booking_code AS visit_number,
                   a.appointment_date AS visit_date,
                   p.id AS patient_id,
                   p.full_name AS patient_name,
                   p.no_rm,
                   p.insurance_type,
                   p.insurance_number,
                   d.id AS doctor_id,
                   d.full_name AS doctor_name,
                   pol.name AS unit_name,
                   inv.id AS invoice_id,
                   inv.invoice_number,
                   inv.status AS invoice_status,
                   inv.patient_amount,
                   inv.total_gross
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN policlinics pol ON a.policlinic_id = pol.id
            LEFT JOIN invoices inv ON inv.appointment_id = a.id
            WHERE a.status IN ('COMPLETED', 'IN_PROGRESS')
              AND (inv.id IS NULL OR inv.status = 'UNPAID')
            ORDER BY a.appointment_date DESC, a.id DESC
            LIMIT 50
        ";
        $appts = $this->query($sqlAppt);

        // 2. IGD (Emergency Visits)
        $sqlIgd = "
            SELECT 'IGD' AS visit_type,
                   ev.id AS reference_id,
                   ev.visit_number,
                   DATE(ev.arrival_time) AS visit_date,
                   COALESCE(p.id, 0) AS patient_id,
                   COALESCE(p.full_name, ev.anonymous_name) AS patient_name,
                   COALESCE(p.no_rm, 'EMERGENCY') AS no_rm,
                   COALESCE(p.insurance_type, 'UMUM') AS insurance_type,
                   COALESCE(p.insurance_number, '-') AS insurance_number,
                   d.id AS doctor_id,
                   COALESCE(d.full_name, 'Dokter Jaga IGD') AS doctor_name,
                   'Instalasi Gawat Darurat' AS unit_name,
                   inv.id AS invoice_id,
                   inv.invoice_number,
                   inv.status AS invoice_status,
                   inv.patient_amount,
                   inv.total_gross
            FROM emergency_visits ev
            LEFT JOIN patients p ON ev.patient_id = p.id
            LEFT JOIN doctors d ON ev.doctor_id = d.id
            LEFT JOIN invoices inv ON inv.emergency_visit_id = ev.id
            WHERE ev.status IN ('EXAMINATION', 'OBSERVATION', 'DISPOSED')
              AND (inv.id IS NULL OR inv.status = 'UNPAID')
            ORDER BY ev.arrival_time DESC, ev.id DESC
            LIMIT 50
        ";
        $igds = $this->query($sqlIgd);

        return array_merge($appts, $igds);
    }

    /**
     * Agregasi Biaya Otomatis dari Pelayanan Pasien (Konsultasi, Tindakan, Lab, Rad, Obat, Admin)
     */
    public function aggregateVisitCosts(string $visitType, int $referenceId): array {
        $items = [];
        $patientId = 0;
        $doctorId = null;
        $patientType = 'UMUM';
        $insuranceNumber = null;
        $patientName = '';
        $noRm = '';
        $doctorName = '';
        $icdCode = 'Z00.0';

        if ($visitType === 'RAWAT_JALAN') {
            $appt = $this->query("
                SELECT a.*, p.id AS pid, p.full_name AS pname, p.no_rm, p.insurance_type, p.insurance_number,
                       d.id AS docid, d.full_name AS docname, d.consultation_fee,
                       mr.icd10_code
                FROM appointments a
                JOIN patients p ON a.patient_id = p.id
                JOIN doctors d ON a.doctor_id = d.id
                LEFT JOIN medical_records mr ON a.id = mr.appointment_id
                WHERE a.id = :id
            ", [':id' => $referenceId])[0] ?? null;

            if (!$appt) return ['success' => false, 'message' => 'Janji temu rawat jalan tidak ditemukan'];

            $patientId = (int)$appt['pid'];
            $doctorId = (int)$appt['docid'];
            $patientType = $appt['payment_type'] ?? 'UMUM';
            $insuranceNumber = $appt['insurance_number'];
            $patientName = $appt['pname'];
            $noRm = $appt['no_rm'];
            $doctorName = $appt['docname'];
            $icdCode = $appt['icd10_code'] ?? 'Z00.0';

            // 1. Jasa Konsultasi Dokter
            $consFee = (float)($appt['consultation_fee'] > 0 ? $appt['consultation_fee'] : 200000.00);
            $items[] = [
                'item_type'       => 'KONSULTASI',
                'item_name'       => 'Konsultasi Dokter Poliklinik (' . $doctorName . ')',
                'quantity'        => 1,
                'unit_price'      => $consFee,
                'subtotal'        => $consFee,
                'reference_table' => 'doctors',
                'reference_id'    => $doctorId
            ];

            // 2. Tindakan Medis / Nursing Orders dari CPOE
            $cpoeProcs = $this->query("
                SELECT oi.* FROM cpoe_order_items oi
                JOIN cpoe_orders o ON oi.cpoe_order_id = o.id
                WHERE o.visit_type = 'RAWAT_JALAN' AND o.reference_id = :id AND oi.item_type = 'PROCEDURE'
            ", [':id' => $referenceId]);
            foreach ($cpoeProcs as $cp) {
                $items[] = [
                    'item_type'       => 'TINDAKAN',
                    'item_name'       => $cp['item_name'],
                    'quantity'        => (int)$cp['quantity'],
                    'unit_price'      => (float)$cp['unit_price'],
                    'subtotal'        => (float)$cp['subtotal'],
                    'reference_table' => 'cpoe_order_items',
                    'reference_id'    => $cp['id']
                ];
            }

            // 3. Obat-obatan dari E-Resep CPOE
            $cpoeMeds = $this->query("
                SELECT oi.* FROM cpoe_order_items oi
                JOIN cpoe_orders o ON oi.cpoe_order_id = o.id
                WHERE o.visit_type = 'RAWAT_JALAN' AND o.reference_id = :id AND oi.item_type = 'MEDICINE'
            ", [':id' => $referenceId]);
            foreach ($cpoeMeds as $cm) {
                $items[] = [
                    'item_type'       => 'OBAT',
                    'item_name'       => $cm['item_name'] . ' (' . ($cm['dosage'] ?? '') . ')',
                    'quantity'        => (int)$cm['quantity'],
                    'unit_price'      => (float)$cm['unit_price'],
                    'subtotal'        => (float)$cm['subtotal'],
                    'reference_table' => 'cpoe_order_items',
                    'reference_id'    => $cm['id']
                ];
            }

            // 4. Pemeriksaan Laboratorium Valid
            $labOrders = $this->query("
                SELECT lo.id, lo.order_number, lt.test_name, lt.tariff
                FROM lab_orders lo
                JOIN lab_order_items loi ON lo.id = loi.lab_order_id
                LEFT JOIN lab_tests lt ON loi.lab_test_id = lt.id
                WHERE lo.medical_record_id = (SELECT id FROM medical_records WHERE appointment_id = :id LIMIT 1)
            ", [':id' => $referenceId]);
            foreach ($labOrders as $lo) {
                $tariff = (float)($lo['tariff'] ?? 45000.00);
                $items[] = [
                    'item_type'       => 'LABORATORIUM',
                    'item_name'       => 'Uji Lab: ' . ($lo['test_name'] ?? 'Laboratorium'),
                    'quantity'        => 1,
                    'unit_price'      => $tariff,
                    'subtotal'        => $tariff,
                    'reference_table' => 'lab_orders',
                    'reference_id'    => $lo['id']
                ];
            }

            // 5. Pemeriksaan Radiologi Valid
            $radOrders = $this->query("
                SELECT ro.id, ro.order_number, ro.modality_type
                FROM radiology_orders ro
                WHERE ro.medical_record_id = (SELECT id FROM medical_records WHERE appointment_id = :id LIMIT 1)
            ", [':id' => $referenceId]);
            foreach ($radOrders as $ro) {
                $radTariff = 180000.00; // Standar rontgen thorax
                $items[] = [
                    'item_type'       => 'RADIOLOGI',
                    'item_name'       => 'Radiologi: ' . $ro['modality_type'],
                    'quantity'        => 1,
                    'unit_price'      => $radTariff,
                    'subtotal'        => $radTariff,
                    'reference_table' => 'radiology_orders',
                    'reference_id'    => $ro['id']
                ];
            }

        } else {
            // IGD (Emergency)
            $ev = $this->query("
                SELECT ev.*, p.id AS pid, p.full_name AS pname, p.no_rm, p.insurance_type, p.insurance_number,
                       d.id AS docid, d.full_name AS docname,
                       ea.icd10_code
                FROM emergency_visits ev
                LEFT JOIN patients p ON ev.patient_id = p.id
                LEFT JOIN doctors d ON ev.doctor_id = d.id
                LEFT JOIN emergency_assessments ea ON ev.id = ea.emergency_visit_id
                WHERE ev.id = :id
            ", [':id' => $referenceId])[0] ?? null;

            if (!$ev) return ['success' => false, 'message' => 'Kunjungan IGD tidak ditemukan'];

            $patientId = (int)($ev['pid'] ?? 0);
            $doctorId = !empty($ev['docid']) ? (int)$ev['docid'] : null;
            $patientType = $ev['insurance_type'] ?? 'UMUM';
            $insuranceNumber = $ev['insurance_number'];
            $patientName = $ev['pname'] ?? $ev['anonymous_name'];
            $noRm = $ev['no_rm'] ?? 'EMERGENCY';
            $doctorName = $ev['docname'] ?? 'Dokter Jaga IGD';
            $icdCode = $ev['icd10_code'] ?? 'R68.89';

            // Jasa Medis Dokter IGD
            $items[] = [
                'item_type'       => 'KONSULTASI',
                'item_name'       => 'Pemeriksaan Dokter Jaga IGD (' . $doctorName . ')',
                'quantity'        => 1,
                'unit_price'      => 150000.00,
                'subtotal'        => 150000.00,
                'reference_table' => 'doctors',
                'reference_id'    => $doctorId
            ];

            // Jasa Triase & Ruang Gawat Darurat
            $items[] = [
                'item_type'       => 'TINDAKAN',
                'item_name'       => 'Triase & Pelayanan Gawat Darurat (' . ($ev['triage_category'] ?? 'IGD') . ')',
                'quantity'        => 1,
                'unit_price'      => 120000.00,
                'subtotal'        => 120000.00,
                'reference_table' => 'emergency_visits',
                'reference_id'    => $referenceId
            ];

            // CPOE Orders di IGD
            $cpoeItems = $this->query("
                SELECT oi.*, o.order_type FROM cpoe_order_items oi
                JOIN cpoe_orders o ON oi.cpoe_order_id = o.id
                WHERE o.visit_type = 'IGD' AND o.reference_id = :id
            ", [':id' => $referenceId]);

            foreach ($cpoeItems as $ci) {
                $type = $ci['item_type'] === 'MEDICINE' ? 'OBAT' : 'TINDAKAN';
                $items[] = [
                    'item_type'       => $type,
                    'item_name'       => $ci['item_name'],
                    'quantity'        => (int)$ci['quantity'],
                    'unit_price'      => (float)$ci['unit_price'],
                    'subtotal'        => (float)$ci['subtotal'],
                    'reference_table' => 'cpoe_order_items',
                    'reference_id'    => $ci['id']
                ];
            }
        }

        // Biaya Administrasi Rumah Sakit
        $adminFee = 25000.00;
        $items[] = [
            'item_type'       => 'ADMINISTRASI',
            'item_name'       => 'Biaya Administrasi & Rekam Medis Elektronik',
            'quantity'        => 1,
            'unit_price'      => $adminFee,
            'subtotal'        => $adminFee,
            'reference_table' => null,
            'reference_id'    => null
        ];

        // Total Gross
        $totalGross = 0.00;
        foreach ($items as $it) {
            $totalGross += (float)$it['subtotal'];
        }

        // Hitung Pertanggungan Asuransi & Sisa Tagihan Pasien
        $insuranceCoverage = 0.00;
        $patientAmount = $totalGross;

        if ($patientType === 'BPJS') {
            // BPJS Kesehatan JKN meng-cover 100% biaya standar yang eligible
            $insuranceCoverage = $totalGross;
            $patientAmount = 0.00;
        } elseif ($patientType === 'ASURANSI') {
            // Asuransi Swasta: Cover 80% biaya
            $insuranceCoverage = round($totalGross * 0.80, 2);
            $patientAmount = round($totalGross - $insuranceCoverage, 2);
        }

        return [
            'success'            => true,
            'visit_type'         => $visitType,
            'reference_id'       => $referenceId,
            'patient_id'         => $patientId,
            'doctor_id'          => $doctorId,
            'patient_name'       => $patientName,
            'no_rm'              => $noRm,
            'doctor_name'        => $doctorName,
            'patient_type'       => $patientType,
            'insurance_number'   => $insuranceNumber,
            'icd10_code'         => $icdCode,
            'items'              => $items,
            'total_gross'        => $totalGross,
            'insurance_coverage' => $insuranceCoverage,
            'patient_amount'     => $patientAmount
        ];
    }

    /**
     * Terbitkan Invoice Resmi Tagihan Pasien
     */
    public function generateInvoice(array $data, int $userId): array {
        $invoiceNumber = $this->generateInvoiceNumber();
        $visitType = $data['visit_type'] ?? 'RAWAT_JALAN';
        $referenceId = (int)$data['reference_id'];
        $patientId = (int)$data['patient_id'];
        $doctorId = !empty($data['doctor_id']) ? (int)$data['doctor_id'] : null;
        $patientType = $data['patient_type'] ?? 'UMUM';
        $insuranceNumber = $data['insurance_number'] ?? null;
        $totalGross = (float)$data['total_gross'];
        $insuranceCoverage = (float)($data['insurance_coverage'] ?? 0.00);
        $patientAmount = (float)($data['patient_amount'] ?? $totalGross);
        $items = $data['items'] ?? [];

        $appointmentId = ($visitType === 'RAWAT_JALAN') ? $referenceId : null;
        $emergencyId = ($visitType === 'IGD') ? $referenceId : null;

        $this->db->beginTransaction();
        try {
            // Cek apakah sudah ada invoice aktif untuk visit ini
            $existing = null;
            if ($appointmentId) {
                $existing = $this->query("SELECT id FROM invoices WHERE appointment_id = :aid LIMIT 1", [':aid' => $appointmentId]);
            } elseif ($emergencyId) {
                $existing = $this->query("SELECT id FROM invoices WHERE emergency_visit_id = :eid LIMIT 1", [':eid' => $emergencyId]);
            }

            if (!empty($existing)) {
                $invoiceId = (int)$existing[0]['id'];
                // Update header invoice
                $this->execute("
                    UPDATE invoices SET 
                        total_gross = :tg,
                        insurance_coverage = :ic,
                        patient_amount = :pa,
                        patient_type = :pt,
                        insurance_number = :in,
                        updated_at = NOW()
                    WHERE id = :id
                ", [
                    ':tg' => $totalGross,
                    ':ic' => $insuranceCoverage,
                    ':pa' => $patientAmount,
                    ':pt' => $patientType,
                    ':in' => $insuranceNumber,
                    ':id' => $invoiceId
                ]);

                // Hapus item lama dan re-insert
                $this->execute("DELETE FROM invoice_items WHERE invoice_id = :id", [':id' => $invoiceId]);
            } else {
                // Insert baru
                $stmt = $this->db->prepare("
                    INSERT INTO invoices (
                        invoice_number, patient_id, appointment_id, emergency_visit_id, doctor_id,
                        visit_type, patient_type, insurance_number, total_gross, insurance_coverage,
                        patient_amount, status, created_by, created_at
                    ) VALUES (
                        :inv_num, :pid, :aid, :eid, :docid,
                        :vtype, :ptype, :ins_num, :tg, :ic,
                        :pa, 'UNPAID', :uid, NOW()
                    )
                ");

                $stmt->execute([
                    ':inv_num' => $invoiceNumber,
                    ':pid'     => $patientId,
                    ':aid'     => $appointmentId,
                    ':eid'     => $emergencyId,
                    ':docid'   => $doctorId,
                    ':vtype'   => $visitType,
                    ':ptype'   => $patientType,
                    ':ins_num' => $insuranceNumber,
                    ':tg'      => $totalGross,
                    ':ic'      => $insuranceCoverage,
                    ':pa'      => $patientAmount,
                    ':uid'     => $userId
                ]);

                $invoiceId = (int)$this->db->lastInsertId();
            }

            // Insert Items
            $itemStmt = $this->db->prepare("
                INSERT INTO invoice_items (
                    invoice_id, item_type, item_name, quantity, unit_price, subtotal, reference_table, reference_id
                ) VALUES (
                    :invid, :itype, :iname, :qty, :uprice, :subtotal, :reftbl, :refid
                )
            ");

            foreach ($items as $it) {
                $itemStmt->execute([
                    ':invid'    => $invoiceId,
                    ':itype'    => $it['item_type'],
                    ':iname'    => $it['item_name'],
                    ':qty'      => (int)$it['quantity'],
                    ':uprice'   => (float)$it['unit_price'],
                    ':subtotal' => (float)$it['subtotal'],
                    ':reftbl'   => $it['reference_table'] ?? null,
                    ':refid'    => $it['reference_id'] ?? null
                ]);
            }

            // Jika ada tanggungan asuransi/BPJS, buat atau update draf klaim
            if ($insuranceCoverage > 0 && ($patientType === 'BPJS' || $patientType === 'ASURANSI')) {
                $claimNumber = $this->generateClaimNumber();
                $icdCode = $data['icd10_code'] ?? 'Z00.0';
                $insType = ($patientType === 'BPJS') ? 'BPJS' : 'ASURANSI_SWASTA';

                $claimStmt = $this->db->prepare("
                    INSERT INTO insurance_claims (
                        claim_number, invoice_id, patient_id, insurance_type, policy_number,
                        diagnosis_code, claimed_amount, approved_amount, status, submission_date, created_by
                    ) VALUES (
                        :clm_num, :invid, :pid, :instype, :policynum,
                        :icd, :claimed, :approved, 'SUBMITTED', NOW(), :uid
                    )
                    ON CONFLICT (invoice_id) DO UPDATE SET
                        claimed_amount = EXCLUDED.claimed_amount,
                        approved_amount = EXCLUDED.approved_amount,
                        submission_date = NOW()
                ");

                $claimStmt->execute([
                    ':clm_num'   => $claimNumber,
                    ':invid'     => $invoiceId,
                    ':pid'       => $patientId,
                    ':instype'   => $insType,
                    ':policynum' => $insuranceNumber ?? 'JKN-DEFAULT',
                    ':icd'       => $icdCode,
                    ':claimed'   => $insuranceCoverage,
                    ':approved'  => $insuranceCoverage,
                    ':uid'       => $userId
                ]);
            }

            $this->db->commit();

            AuditLogModel::log(
                'GENERATE_INVOICE',
                'BILLING',
                "Invoice {$invoiceNumber} diterbitkan untuk pasien ID {$patientId} sebesar Rp " . number_format($totalGross, 0, ',', '.'),
                $userId
            );

            return ['success' => true, 'invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Proses Penerimaan Pembayaran Kasir & Penutupan Tagihan (PAID)
     */
    public function processPayment(int $invoiceId, array $data, int $cashierId): array {
        $inv = $this->query("SELECT * FROM invoices WHERE id = :id", [':id' => $invoiceId])[0] ?? null;
        if (!$inv) {
            return ['success' => false, 'message' => 'Invoice tidak ditemukan'];
        }

        if ($inv['status'] === 'PAID') {
            return ['success' => false, 'message' => 'Invoice sudah lunas sebelumnya'];
        }

        $patientAmount = (float)$inv['patient_amount'];
        $paymentMethod = $data['payment_method'] ?? 'TUNAI';
        $amountPaid = isset($data['amount_paid']) ? (float)$data['amount_paid'] : 0.00;

        // Pasien BPJS dengan patient_amount 0
        if ($inv['patient_type'] === 'BPJS' && $patientAmount <= 0) {
            $paymentMethod = 'ASURANSI_FULL';
            $amountPaid = 0.00;
            $changeAmount = 0.00;
        } else {
            // Validasi underpayment: tolak pembayaran jika uang kurang!
            if ($amountPaid < $patientAmount) {
                return [
                    'success' => false,
                    'message' => 'Jumlah pembayaran kurang! Tagihan pasien: Rp ' . number_format($patientAmount, 0, ',', '.') . ', diterima: Rp ' . number_format($amountPaid, 0, ',', '.')
                ];
            }
            $changeAmount = $amountPaid - $patientAmount;
        }

        $receiptNumber = $this->generateReceiptNumber();

        $this->db->beginTransaction();
        try {
            // Insert Payment Record
            $stmt = $this->db->prepare("
                INSERT INTO payments (
                    receipt_number, invoice_id, amount_paid, payment_method,
                    cashier_id, change_amount, payment_time, notes
                ) VALUES (
                    :rcpt_num, :invid, :amt, :method,
                    :cid, :change, NOW(), :notes
                )
            ");

            $stmt->execute([
                ':rcpt_num' => $receiptNumber,
                ':invid'    => $invoiceId,
                ':amt'      => $amountPaid,
                ':method'   => $paymentMethod,
                ':cid'      => $cashierId,
                ':change'   => $changeAmount,
                ':notes'    => $data['notes'] ?? 'Pelunasan tagihan kasir'
            ]);

            $paymentId = (int)$this->db->lastInsertId();

            // Update status invoice menjadi PAID
            $this->execute("
                UPDATE invoices 
                SET status = 'PAID', paid_at = NOW(), cashier_id = :cid, paid_amount = :amt, change_amount = :change
                WHERE id = :id
            ", [
                ':cid'    => $cashierId,
                ':amt'    => $amountPaid,
                ':change' => $changeAmount,
                ':id'     => $invoiceId
            ]);

            $this->db->commit();

            AuditLogModel::log(
                'PROCESS_PAYMENT',
                'BILLING',
                "Pembayaran kuitansi {$receiptNumber} berhasil diproses untuk invoice ID {$invoiceId}",
                $cashierId
            );

            return [
                'success'        => true,
                'receipt_number' => $receiptNumber,
                'payment_id'     => $paymentId,
                'amount_paid'    => $amountPaid,
                'change_amount'  => $changeAmount,
                'patient_amount' => $patientAmount
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Ambil Detail Tagihan Lengkap untuk Pratinjau & Cetak Kuitansi
     */
    public function getInvoiceDetail(int $invoiceId): ?array {
        $sql = "
            SELECT inv.*,
                   p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.birth_date, p.address,
                   d.full_name AS doctor_name, d.specialization,
                   u.full_name AS cashier_name
            FROM invoices inv
            JOIN patients p ON inv.patient_id = p.id
            LEFT JOIN doctors d ON inv.doctor_id = d.id
            LEFT JOIN users u ON inv.cashier_id = u.id
            WHERE inv.id = :id
        ";
        $res = $this->query($sql, [':id' => $invoiceId]);
        if (empty($res)) return null;

        $invoice = $res[0];
        $invoice['items'] = $this->query("SELECT * FROM invoice_items WHERE invoice_id = :id ORDER BY id ASC", [':id' => $invoiceId]);
        $invoice['payment'] = $this->query("SELECT * FROM payments WHERE invoice_id = :id ORDER BY id DESC LIMIT 1", [':id' => $invoiceId])[0] ?? null;
        $invoice['claim'] = $this->query("SELECT * FROM insurance_claims WHERE invoice_id = :id LIMIT 1", [':id' => $invoiceId])[0] ?? null;

        return $invoice;
    }

    /**
     * Ambil Semua Klaim Asuransi/BPJS
     */
    public function getInsuranceClaims(array $filters = []): array {
        $sql = "
            SELECT c.*,
                   inv.invoice_number, inv.total_gross, inv.patient_amount,
                   p.full_name AS patient_name, p.no_rm, p.insurance_type AS patient_insurance,
                   d.description_id AS diagnosis_name,
                   u.full_name AS creator_name
            FROM insurance_claims c
            JOIN invoices inv ON c.invoice_id = inv.id
            JOIN patients p ON c.patient_id = p.id
            LEFT JOIN icd_diagnoses d ON c.diagnosis_code = d.code
            LEFT JOIN users u ON c.created_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['insurance_type'])) {
            $sql .= " AND c.insurance_type = :type";
            $params[':type'] = $filters['insurance_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.claim_number LIKE :s OR inv.invoice_number LIKE :s OR p.full_name LIKE :s OR p.no_rm LIKE :s)";
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY c.id DESC";

        return $this->query($sql, $params);
    }

    /**
     * Ambil Klaim Asuransi dengan Pagination
     */
    public function paginateInsuranceClaims(array $filters = [], int $page = 1, int $perPage = 15): array {
        $sql = "
            SELECT c.*,
                   inv.invoice_number, inv.total_gross, inv.patient_amount,
                   p.full_name AS patient_name, p.no_rm, p.insurance_type AS patient_insurance,
                   d.description_id AS diagnosis_name,
                   u.full_name AS creator_name
            FROM insurance_claims c
            JOIN invoices inv ON c.invoice_id = inv.id
            JOIN patients p ON c.patient_id = p.id
            LEFT JOIN icd_diagnoses d ON c.diagnosis_code = d.code
            LEFT JOIN users u ON c.created_by = u.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND c.status = :status";
            $params[':status'] = $filters['status'];
        }

        if (!empty($filters['insurance_type'])) {
            $sql .= " AND c.insurance_type = :type";
            $params[':type'] = $filters['insurance_type'];
        }

        if (!empty($filters['search'])) {
            $sql .= " AND (c.claim_number ILIKE :s OR inv.invoice_number ILIKE :s OR p.full_name ILIKE :s OR p.no_rm ILIKE :s)";
            $params[':s'] = '%' . $filters['search'] . '%';
        }

        $sql .= " ORDER BY c.id DESC";

        return $this->paginate($sql, $params, $page, $perPage);
    }

    /**
     * Update Status Klaim Asuransi (APPROVED / REJECTED / PENDING)
     */
    public function updateClaimStatus(int $claimId, string $status, ?float $approvedAmount, ?string $notes, int $userId): bool {
        $this->db->beginTransaction();
        try {
            $claim = $this->query("SELECT * FROM insurance_claims WHERE id = :id", [':id' => $claimId])[0] ?? null;
            if (!$claim) throw new Exception("Klaim tidak ditemukan");

            $appr = $approvedAmount ?? $claim['claimed_amount'];
            if ($status === 'REJECTED') {
                $appr = 0.00;
            }

            $this->execute("
                UPDATE insurance_claims 
                SET status = :status, approved_amount = :appr, notes = :notes, adjudication_date = NOW()
                WHERE id = :id
            ", [
                ':status' => $status,
                ':appr'   => $appr,
                ':notes'  => $notes,
                ':id'     => $claimId
            ]);

            // Jika klaim ditolak (REJECTED), pindahkan porsi asuransi kembali ke kewajiban pasien (patient_amount)
            if ($status === 'REJECTED') {
                $this->execute("
                    UPDATE invoices 
                    SET insurance_coverage = 0.00, patient_amount = total_gross 
                    WHERE id = :invid
                ", [':invid' => $claim['invoice_id']]);
            }

            $this->db->commit();

            AuditLogModel::log(
                'UPDATE_CLAIM_STATUS',
                'BILLING',
                "Status klaim ID {$claimId} diperbarui menjadi {$status} (Disetujui: Rp " . number_format($appr, 0, ',', '.') . ")",
                $userId
            );

            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Statistik Finansial & Billing
     */
    public function getBillingStats(): array {
        $sql = "
            SELECT 
                (SELECT COALESCE(SUM(amount_paid), 0) FROM payments WHERE DATE(payment_time) = CURRENT_DATE) AS revenue_today,
                (SELECT COUNT(*) FROM invoices WHERE status = 'UNPAID') AS unpaid_count,
                (SELECT COUNT(*) FROM insurance_claims WHERE status IN ('SUBMITTED', 'PENDING')) AS claims_pending,
                (SELECT COUNT(*) FROM invoices WHERE status = 'PAID' AND DATE(paid_at) = CURRENT_DATE) AS paid_today
        ";
        $row = $this->query($sql)[0] ?? [];
        return [
            'revenue_today'  => (float)($row['revenue_today'] ?? 0.00),
            'unpaid_count'   => (int)($row['unpaid_count'] ?? 0),
            'claims_pending' => (int)($row['claims_pending'] ?? 0),
            'paid_today'     => (int)($row['paid_today'] ?? 0),
        ];
    }
}
