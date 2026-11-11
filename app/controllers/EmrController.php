<?php
require_once __DIR__ . '/../../core/Controller.php';
require_once __DIR__ . '/../models/EmrModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/LaboratoryModel.php';
require_once __DIR__ . '/../models/RadiologyModel.php';

class EmrController extends Controller {
    public function __construct() {
        $this->requireRole(['admin','dokter','dokter_radiologi','perawat','triase']);
    }
    public function index(): void {
        $model=new EmrModel(); $search=trim((string)($_GET['q'] ?? ''));
        $filters=['q'=>$search,'policlinic_id'=>(int)($_GET['policlinic_id'] ?? 0),'doctor_id'=>(int)($_GET['doctor_id'] ?? 0),
            'status'=>in_array($_GET['status'] ?? '',['SCHEDULED','IN_PROGRESS','COMPLETED','CANCELLED'],true)?$_GET['status']:'','date'=>''];
        $date=(string)($_GET['date'] ?? '');
        if ($date!=='' && preg_match('/^\d{4}-\d{2}-\d{2}$/D',$date)) {
            [$y,$m,$d]=array_map('intval',explode('-',$date)); if (checkdate($m,$d,$y)) $filters['date']=$date;
        }
        $patientMode=($_GET['mode'] ?? '')==='patients'; $page=max(1,(int)($_GET['page'] ?? 1));
        $pagination=$patientMode?$model->searchPatients($search,$page):$model->clinicalQueue($filters,$page);
        $this->render('emr/index',['pageTitle'=>'Antrean Rawat Jalan & EMR - SIMRS','search'=>$search,'filters'=>$filters,
            'pagination'=>$pagination,'rows'=>$pagination['data'],'patientMode'=>$patientMode,'stats'=>$model->clinicalStats(),
            'policlinics'=>$model->query('SELECT id,name FROM policlinics WHERE is_active=TRUE ORDER BY name'),
            'doctors'=>$model->query('SELECT id,full_name FROM doctors WHERE is_active=TRUE ORDER BY full_name')]);
    }
    public function visits(): void {
        $patient = (new PatientModel())->getPatientById((int)($_GET['patient_id'] ?? 0));
        if (!$patient) { $this->setFlash('danger','Pasien tidak ditemukan.'); $this->redirect('index.php?c=emr'); }
        $this->render('emr/visits', ['pageTitle'=>'Pilih Kunjungan EMR','patient'=>$patient,'visits'=>(new EmrModel())->getPatientVisits((int)$patient['id'])]);
    }
    private function workspace(int $id, array $input = []): void {
        $model = new EmrModel(); $appointment = $model->getAppointmentDetail($id);
        if (!$appointment) { $this->setFlash('danger','Kunjungan tidak ditemukan.'); $this->redirect('index.php?c=emr'); }
        $record = $model->getRecordByAppointment($id);
        $this->render('emr/workspace', [
            'pageTitle'=>'EMR — '.$appointment['patient_name'], 'appointment'=>$appointment,
            'record'=>$record, 'input'=>$input ?: ($record ?? []), 'canDocument'=>$model->canDocument($appointment),
            'isNurse'=>ClinicalWorkflow::nurse(), 'allergies'=>$model->getPatientAllergies((int)$appointment['patient_id']),
            'attachments'=>$model->getAttachments((int)$appointment['patient_id'],$id),
            'icdList'=>$model->query('SELECT code,description_id FROM icd_diagnoses WHERE is_active=TRUE ORDER BY code'),
            'labHistory'=>(new LaboratoryModel())->getPatientLabHistory((int)$appointment['patient_id']),
            'radHistory'=>(new RadiologyModel())->getPatientRadiologyHistory((int)$appointment['patient_id'])
        ]);
    }
    public function examine(): void {
        $id=(int)($_GET['id'] ?? $_GET['appointment_id'] ?? 0);
        if (!empty($_GET['fresh'])) unset($_SESSION['emr_review'][$id]);
        $draft = $_SESSION['emr_review'][$id]['input'] ?? [];
        $this->workspace($id,$draft);
    }
    private function post(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf()) {
            $this->setFlash('danger','Permintaan atau token keamanan tidak valid.'); $this->redirect('index.php?c=emr');
        }
    }
    public function review(): void {
        $this->post(); $id=(int)($_POST['appointment_id'] ?? 0); $model=new EmrModel();
        try { $input=$model->clinicalInput($_POST); }
        catch (DomainException $e) { $this->setFlash('danger',$e->getMessage()); $this->workspace($id,$_POST); return; }
        $token=bin2hex(random_bytes(24));
        $_SESSION['emr_review'][$id]=['token'=>$token,'input'=>$input];
        $this->render('emr/review', ['pageTitle'=>'Tinjau dan Simpan EMR','input'=>$input,'token'=>$token,
            'appointment'=>$model->getAppointmentDetail($id),'isNurse'=>ClinicalWorkflow::nurse()]);
    }
    public function save(): void {
        $this->post(); $id=(int)($_POST['appointment_id'] ?? 0); $review=$_SESSION['emr_review'][$id] ?? null;
        if (!$review || !hash_equals($review['token'],(string)($_POST['review_token'] ?? ''))) {
            $this->setFlash('warning','Buka formulir dan tinjau data sebelum menyimpan. Permintaan yang sudah tersimpan tidak akan diulang.');
            $this->redirect('index.php?c=emr&a=examine&id='.$id);
        }
        if (empty($_POST['confirmed'])) { $this->setFlash('warning','Konfirmasi bahwa data telah diperiksa sebelum menyimpan.'); $this->redirect('index.php?c=emr&a=examine&id='.$id); }
        $result=(new EmrModel())->saveClinicalRecord($review['input']);
        $this->setFlash($result['success']?'success':'danger',$result['message']);
        if ($result['success']) { unset($_SESSION['emr_review'][$id]); }
        $this->redirect('index.php?c=emr&a=examine&id='.$id);
    }
    public function uploadAttachment(): void {
        $this->post(); $id=(int)($_POST['appointment_id'] ?? 0);
        $r=(new EmrModel())->saveAttachment($id,(int)($_POST['patient_id'] ?? 0),$_FILES['attachment'] ?? []);
        $this->setFlash($r['success']?'success':'danger',$r['message']);
        $this->redirect('index.php?c=emr&a=examine&id='.$id.'#lampiran');
    }
    public function attachment(): void {
        $rows=(new EmrModel())->query('SELECT * FROM emr_attachments WHERE id=?',[(int)($_GET['id'] ?? 0)]);
        if (!$rows) { http_response_code(404); echo 'Lampiran tidak ditemukan.'; return; }
        ClinicalWorkflow::sendFile($rows[0]);
    }
    public function history(): void {
        $patientId=(int)($_GET['patient_id'] ?? 0); $patient=(new PatientModel())->getPatientById($patientId);
        if (!$patient) { $this->setFlash('danger','Pasien tidak ditemukan.'); $this->redirect('index.php?c=emr'); }
        $model=new EmrModel();
        $this->render('emr/history',['pageTitle'=>'Riwayat EMR — '.$patient['full_name'],'patient'=>$patient,
            'records'=>$model->getPatientEmrHistory($patientId),'allergies'=>$model->getPatientAllergies($patientId),
            'labHistory'=>(new LaboratoryModel())->getPatientLabHistory($patientId),
            'radHistory'=>(new RadiologyModel())->getPatientRadiologyHistory($patientId),
            'attachments'=>$model->getAttachments($patientId)]);
    }
    public function icd10Search(): void {
        $q=trim((string)($_GET['q'] ?? ''));
        $this->json($q === '' ? [] : (new EmrModel())->query('SELECT code,description_id,description_en FROM icd_diagnoses WHERE is_active=TRUE AND (code ILIKE ? OR description_id ILIKE ? OR description_en ILIKE ?) ORDER BY code LIMIT 20',["%$q%","%$q%","%$q%"]));
    }
}
