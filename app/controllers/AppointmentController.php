<?php
// app/controllers/AppointmentController.php - Penjadwalan & Perjanjian Pasien

require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/AppointmentModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/DoctorModel.php';
require_once __DIR__ . '/../models/PoliclinicModel.php';

class AppointmentController extends Controller {

    public function __construct() {
        // Antrean & Penjadwalan Janji Temu Pasien hanya untuk Petugas Registrasi & Admisi dan Admin Sistem
        $this->requireRole(['admin', 'admin sistem', 'petugas registrasi', 'registrasi']);
    }

    public function index(): void {
        $appointmentModel = new AppointmentModel();
        $poliModel = new PoliclinicModel();

        $date = $_GET['date'] ?? date('Y-m-d');
        $policlinicId = !empty($_GET['policlinic_id']) ? (int)$_GET['policlinic_id'] : null;
        $status = $_GET['status'] ?? null;

        $appointments = $appointmentModel->getAppointments($date, $policlinicId, $status);
        $policlinics = $poliModel->getAllPoliclinics(true);

        $this->render('scheduling/index', [
            'pageTitle'     => 'Jadwal & Antrean Poliklinik - SIMRS',
            'appointments'  => $appointments,
            'policlinics'   => $policlinics,
            'selectedDate'  => $date,
            'selectedPoli'  => $policlinicId,
            'selectedStatus'=> $status
        ]);
    }

    public function create(): void {
        $this->requireRole(['admin', 'registrasi']);
        $appointmentModel = new AppointmentModel();
        $patientModel = new PatientModel();
        $poliModel = new PoliclinicModel();
        $doctorModel = new DoctorModel();

        $error = null;
        $selectedPatientId = (int)($_GET['patient_id'] ?? 0);
        $preselectedPatient = $selectedPatientId ? $patientModel->getPatientById($selectedPatientId) : null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $patientId     = (int)($_POST['patient_id'] ?? 0);
            $policlinicId  = (int)($_POST['policlinic_id'] ?? 0);
            $doctorId      = (int)($_POST['doctor_id'] ?? 0);
            $date          = trim($_POST['appointment_date'] ?? date('Y-m-d'));
            $chiefComplaint= trim($_POST['chief_complaint'] ?? '');

            if ($patientId <= 0 || $policlinicId <= 0 || $doctorId <= 0 || empty($date)) {
                $error = 'Pasien, Poliklinik, Dokter, dan Tanggal Perjanjian wajib dipilih.';
            } else {
                // Cek kuota dokter pada tanggal tersebut
                $quotaCheck = $appointmentModel->checkDoctorQuota($doctorId, $date);

                if (!$quotaCheck['has_schedule']) {
                    $error = "Dokter tidak memiliki jadwal praktek pada hari tersebut ({$quotaCheck['day_name']}).";
                } elseif ($quotaCheck['is_full']) {
                    $error = "Maaf, kuota antrean dokter untuk tanggal {$date} telah PENUH ({$quotaCheck['booked']}/{$quotaCheck['quota_per_day']}). Silakan pilih tanggal atau dokter lain.";
                } else {
                    $created = $appointmentModel->createAppointment([
                        'patient_id'       => $patientId,
                        'policlinic_id'    => $policlinicId,
                        'doctor_id'        => $doctorId,
                        'appointment_date' => $date,
                        'chief_complaint'  => $chiefComplaint,
                        'created_by'       => $_SESSION['user_id']
                    ]);

                    if ($created['success']) {
                        $this->logAudit('CREATE_APPOINTMENT', 'Penjadwalan', "Mendaftarkan antrean {$created['queue_number']} untuk Pasien ID {$patientId} di Poli ID {$policlinicId}");
                        $this->setFlash('success', "Perjanjian berhasil dibuat! Nomor Antrean: {$created['queue_number']}");
                        $this->redirect('index.php?c=appointment&a=printTicket&id=' . $created['appointment_id']);
                    } else {
                        $error = $created['message'] ?? 'Gagal membuat perjanjian.';
                    }
                }
            }
        }

        $policlinics = $poliModel->getAllPoliclinics(true);
        $doctors = $doctorModel->getAllDoctors(null, true);
        $patients = $patientModel->searchPatients('', 30);

        $this->render('scheduling/create', [
            'pageTitle'          => 'Pendaftaran Antrean & Janji Temu - SIMRS',
            'policlinics'        => $policlinics,
            'doctors'            => $doctors,
            'patients'           => $patients,
            'preselectedPatient' => $preselectedPatient,
            'error'              => $error
        ]);
    }

    public function reschedule(): void {
        $this->requireRole(['admin', 'registrasi']);
        $id = (int)($_GET['id'] ?? 0);
        $appointmentModel = new AppointmentModel();
        $doctorModel = new DoctorModel();

        $appt = $appointmentModel->getAppointmentById($id);
        if (!$appt) {
            $this->setFlash('danger', 'Data perjanjian tidak ditemukan.');
            $this->redirect('index.php?c=appointment&a=index');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $newDate     = trim($_POST['new_date'] ?? '');
            $newDoctorId = (int)($_POST['new_doctor_id'] ?? $appt['doctor_id']);
            $reason      = trim($_POST['reason'] ?? '');

            if (empty($newDate) || $newDoctorId <= 0) {
                $error = 'Tanggal baru dan Dokter wajib ditentukan.';
            } else {
                $res = $appointmentModel->reschedule($id, $newDate, $newDoctorId, $reason);
                if ($res['success']) {
                    $this->logAudit('RESCHEDULE_APPOINTMENT', 'Penjadwalan', "Menjadwal ulang antrean ID {$id} ke tanggal {$newDate} (Antrean Baru: {$res['new_queue_number']})");
                    $this->setFlash('success', "Janji temu berhasil dijadwalkan ulang ke tanggal {$newDate}. Nomor antrean baru: {$res['new_queue_number']}");
                    $this->redirect('index.php?c=appointment&a=index&date=' . $newDate);
                } else {
                    $error = $res['message'];
                }
            }
        }

        $doctorsInPoli = $doctorModel->getAllDoctors($appt['policlinic_id'], true);

        $this->render('scheduling/reschedule', [
            'pageTitle'     => 'Reschedule Janji Temu - ' . $appt['queue_number'],
            'appointment'   => $appt,
            'doctorsInPoli' => $doctorsInPoli,
            'error'         => $error
        ]);
    }

    public function cancel(): void {
        $this->requireRole(['admin', 'registrasi']);
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Dibatalkan oleh pasien/petugas');

        if ($id > 0) {
            $appointmentModel = new AppointmentModel();
            $appt = $appointmentModel->getAppointmentById($id);
            if ($appt) {
                $appointmentModel->cancelAppointment($id, $reason);
                $this->logAudit('CANCEL_APPOINTMENT', 'Penjadwalan', "Membatalkan antrean {$appt['queue_number']} (Alasan: {$reason})");
                $this->setFlash('success', "Janji temu {$appt['queue_number']} berhasil dibatalkan.");
            }
        }

        $this->redirect('index.php?c=appointment&a=index');
    }

    public function printTicket(): void {
        $this->requireRole(['admin', 'registrasi']);
        $id = (int)($_GET['id'] ?? 0);
        $appointmentModel = new AppointmentModel();
        $appt = $appointmentModel->getAppointmentById($id);

        if (!$appt) {
            $this->setFlash('danger', 'Data perjanjian tidak ditemukan.');
            $this->redirect('index.php?c=appointment&a=index');
        }

        $this->render('scheduling/print_ticket', [
            'pageTitle'   => 'Cetak Tiket Antrean - ' . $appt['queue_number'],
            'appointment' => $appt
        ], false); // No main layout, clean printable ticket format
    }

    public function apiCheckQuota(): void {
        $doctorId = (int)($_GET['doctor_id'] ?? 0);
        $date = trim($_GET['date'] ?? date('Y-m-d'));

        $appointmentModel = new AppointmentModel();
        $quota = $appointmentModel->checkDoctorQuota($doctorId, $date);

        $this->json(['status' => 'success', 'data' => $quota]);
    }

    public function apiGetDoctors(): void {
        $policlinicId = (int)($_GET['policlinic_id'] ?? 0);
        $doctorModel = new DoctorModel();
        $doctors = $doctorModel->getAllDoctors($policlinicId > 0 ? $policlinicId : null, true);
        $this->json(['status' => 'success', 'data' => $doctors]);
    }
}

