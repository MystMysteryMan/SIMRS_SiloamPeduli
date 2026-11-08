<?php
// app/models/AppointmentModel.php - Model Penjadwalan & Appointment Dokter (Modul 12 - SOP-012)

require_once __DIR__ . '/../../core/Model.php';

class AppointmentModel extends Model {
    protected string $table = 'appointments';

    private function mapAppointment(array $a): array {
        $a['patient_name'] = $a['full_name'] ?? $a['patient_name'] ?? '';
        $a['doctor_name'] = $a['doc_name'] ?? $a['doctor_name'] ?? '';
        $a['policlinic_name'] = $a['poli_name'] ?? $a['policlinic_name'] ?? '';
        $a['chief_complaint'] = $a['notes'] ?? '';

        // Status mapping
        $rawStatus = strtoupper($a['status'] ?? 'SCHEDULED');
        if ($rawStatus === 'SCHEDULED') {
            $a['status_label'] = 'Menunggu';
        } elseif ($rawStatus === 'COMPLETED') {
            $a['status_label'] = 'Selesai';
        } elseif ($rawStatus === 'RESCHEDULED') {
            $a['status_label'] = 'Dijadwal Ulang';
        } elseif ($rawStatus === 'CANCELLED') {
            $a['status_label'] = 'Batal';
        } else {
            $a['status_label'] = $a['status'];
        }
        return $a;
    }

    public function getAppointments(?string $date = null, ?int $policlinicId = null, ?string $status = null): array {
        $sql = "
            SELECT a.*, 
                   p.full_name, p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.phone_number, p.insurance_type,
                   d.full_name AS doc_name, d.full_name AS doctor_name, 
                   pol.name AS poli_name, pol.name AS policlinic_name, pol.queue_prefix
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN policlinics pol ON a.policlinic_id = pol.id
            WHERE 1=1
        ";
        $params = [];

        if (!empty($date)) {
            $sql .= " AND a.appointment_date = :dt";
            $params[':dt'] = $date;
        }

        if (!empty($status)) {
            // Normalisasi status filter (bisa Bahasa Indonesia atau kode DB)
            $st = strtoupper($status);
            if ($st === 'MENUNGGU') $st = 'SCHEDULED';
            if ($st === 'SELESAI') $st = 'COMPLETED';
            if ($st === 'BATAL') $st = 'CANCELLED';
            $sql .= " AND (a.status = :st OR a.status = :st_raw)";
            $params[':st'] = $st;
            $params[':st_raw'] = $status;
        }

        if (!empty($policlinicId) && $policlinicId > 0) {
            $sql .= " AND a.policlinic_id = :pol";
            $params[':pol'] = $policlinicId;
        }

        $sql .= " ORDER BY a.appointment_date ASC, a.queue_number ASC, a.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();
        return array_map([$this, 'mapAppointment'], $rows);
    }

    public function getAppointmentById(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT a.*, 
                   p.full_name, p.full_name AS patient_name, p.no_rm, p.nik, p.gender, p.phone_number, p.insurance_type,
                   d.full_name AS doc_name, d.full_name AS doctor_name, 
                   pol.name AS poli_name, pol.name AS policlinic_name, pol.queue_prefix
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors d ON a.doctor_id = d.id
            JOIN policlinics pol ON a.policlinic_id = pol.id
            WHERE a.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ? $this->mapAppointment($row) : null;
    }

    public function checkDoctorQuota(int $doctorId, string $date): array {
        $dayMap = [
            'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
        ];
        $dayName = $dayMap[date('l', strtotime($date))];

        // Ambil kuota harian dokter pada hari tersebut
        $stmtQ = $this->db->prepare("SELECT quota, start_time, end_time FROM doctor_schedules WHERE doctor_id = :did AND day_name = :dn AND is_active = TRUE LIMIT 1");
        $stmtQ->execute([':did' => $doctorId, ':dn' => $dayName]);
        $schedule = $stmtQ->fetch();

        $maxQuota = $schedule ? (int)$schedule['quota'] : 25;

        // Hitung appointment aktif pada tanggal tersebut
        $stmtBooked = $this->db->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id = :did AND appointment_date = :dt AND status != 'CANCELLED'");
        $stmtBooked->execute([':did' => $doctorId, ':dt' => $date]);
        $bookedCount = (int)$stmtBooked->fetchColumn();

        $remaining = max(0, $maxQuota - $bookedCount);

        return [
            'has_schedule'    => (bool)$schedule,
            'day_name'        => $dayName,
            'max_quota'       => $maxQuota,
            'quota_per_day'   => $maxQuota,
            'booked'          => $bookedCount,
            'remaining'       => $remaining,
            'available_slots' => $remaining,
            'is_full'         => $schedule ? ($bookedCount >= $maxQuota) : false
        ];
    }

    public function generateQueueNumber(int $policlinicId, string $date): string {
        $stmtPref = $this->db->prepare("SELECT queue_prefix FROM policlinics WHERE id = :id");
        $stmtPref->execute([':id' => $policlinicId]);
        $prefix = $stmtPref->fetchColumn() ?: 'A';

        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM appointments WHERE policlinic_id = :pol AND appointment_date = :dt");
        $stmtCount->execute([':pol' => $policlinicId, ':dt' => $date]);
        $count = (int)$stmtCount->fetchColumn();
        $nextNo = str_pad((string)($count + 1), 3, '0', STR_PAD_LEFT);

        return sprintf('%s-%s', $prefix, $nextNo);
    }

    public function createAppointment(array $data): array {
        $quota = $this->checkDoctorQuota((int)$data['doctor_id'], $data['appointment_date']);
        if (!$quota['has_schedule']) {
            return ['success' => false, 'message' => "Dokter tidak memiliki jadwal praktek pada hari {$quota['day_name']}."];
        }
        if ($quota['is_full']) {
            return ['success' => false, 'message' => "Kuota dokter pada tanggal {$data['appointment_date']} telah PENUH."];
        }

        $policlinicId = (int)$data['policlinic_id'];
        $queueNo = $this->generateQueueNumber($policlinicId, $data['appointment_date']);
        do {
            $bookingCode = 'BKG-' . date('Ymd', strtotime($data['appointment_date'])) . '-' . str_pad((string)mt_rand(1000, 9999), 4, '0', STR_PAD_LEFT);
            $exists = $this->query("SELECT id FROM appointments WHERE booking_code = :bc LIMIT 1", [':bc' => $bookingCode]);
        } while (!empty($exists));

        $payType = strtoupper(trim($data['payment_type'] ?? 'UMUM'));
        if (str_contains($payType, 'BPJS')) $payType = 'BPJS';
        elseif (str_contains($payType, 'ASURANSI')) $payType = 'ASURANSI';
        else $payType = 'UMUM';

        $stmt = $this->db->prepare("
            INSERT INTO appointments (booking_code, queue_number, patient_id, doctor_id, policlinic_id, appointment_date, payment_type, insurance_number, referral_number, status, notes)
            VALUES (:bkg, :qno, :pid, :did, :pol, :ad, :pt, :in, :ref, 'SCHEDULED', :notes)
        ");
        $stmt->execute([
            ':bkg'   => $bookingCode,
            ':qno'   => $queueNo,
            ':pid'   => (int)$data['patient_id'],
            ':did'   => (int)$data['doctor_id'],
            ':pol'   => $policlinicId,
            ':ad'    => $data['appointment_date'],
            ':pt'    => $payType,
            ':in'    => clean($data['insurance_number'] ?? ''),
            ':ref'   => clean($data['referral_number'] ?? ''),
            ':notes' => clean($data['chief_complaint'] ?? $data['notes'] ?? '')
        ]);

        $appId = (int)$this->lastInsertId();
        return [
            'success'        => true,
            'appointment_id' => $appId,
            'queue_number'   => $queueNo,
            'booking_code'   => $bookingCode
        ];
    }

    public function reschedule(int $id, string $newDate, int $newDoctorId, ?string $reason = null): array {
        $appt = $this->getAppointmentById($id);
        if (!$appt) {
            return ['success' => false, 'message' => 'Data perjanjian tidak ditemukan.'];
        }

        $quota = $this->checkDoctorQuota($newDoctorId, $newDate);
        if (!$quota['has_schedule']) {
            return ['success' => false, 'message' => "Dokter tidak memiliki jadwal praktek pada hari {$quota['day_name']}."];
        }
        if ($quota['is_full']) {
            return ['success' => false, 'message' => "Kuota dokter pada tanggal {$newDate} telah PENUH."];
        }

        $newQueue = $this->generateQueueNumber((int)$appt['policlinic_id'], $newDate);

        $stmt = $this->db->prepare("
            UPDATE appointments
            SET appointment_date = :nd, doctor_id = :did, queue_number = :qno, status = 'RESCHEDULED', notes = COALESCE(notes, '') || ' [Rescheduled: ' || :reason || ']', updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':nd'     => $newDate,
            ':did'    => $newDoctorId,
            ':qno'    => $newQueue,
            ':reason' => clean($reason ?? 'Permintaan pasien'),
            ':id'     => $id
        ]);

        return [
            'success'          => true,
            'new_queue_number' => $newQueue
        ];
    }

    public function cancelAppointment(int $id, string $reason): bool {
        $stmt = $this->db->prepare("UPDATE appointments SET status = 'CANCELLED', cancel_reason = :cr, updated_at = NOW() WHERE id = :id");
        return $stmt->execute([':cr' => clean($reason), ':id' => $id]);
    }
}
