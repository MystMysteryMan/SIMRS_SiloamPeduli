<?php
/** CLI only. Reuse a real registered patient/appointment; never overwrite identity/results. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
foreach (['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'] as $key) {
    if (getenv($key) === false) { fwrite(STDERR,"Set all database environment variables explicitly. No embedded fallback credentials are used.\n"); exit(1); }
}
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../core/Database.php';
require_once __DIR__.'/../app/models/LaboratoryModel.php';
require_once __DIR__.'/../app/models/RadiologyModel.php';
$options=getopt('', ['appointment-id:','lab-test-id:','radiology:','batch:','apply']);
$appointmentId=(int)($options['appointment-id'] ?? 0);
$batch=(string)($options['batch'] ?? '');
if (!$appointmentId || !preg_match('/^[a-zA-Z0-9_-]{1,80}$/D',$batch)) exit("Usage: php database/provision_clinical_orders.php --appointment-id=ID --lab-test-id=ID,ID --radiology='Rontgen Thorax PA' --batch=unique-key [--apply]\n");
$db=Database::getConnection();
$q=$db->prepare('SELECT a.*,p.full_name AS patient_name,p.no_rm,d.user_id AS doctor_user_id,d.full_name AS doctor_name FROM appointments a JOIN patients p ON p.id=a.patient_id JOIN doctors d ON d.id=a.doctor_id JOIN users u ON u.id=d.user_id WHERE a.id=? AND d.is_active=TRUE AND u.is_active=TRUE');
$q->execute([$appointmentId]); $visit=$q->fetch(PDO::FETCH_ASSOC);
if (!$visit || $visit['status']==='CANCELLED') exit("Appointment not found, cancelled, or doctor account not active.\n");
$tests=array_values(array_unique(array_filter(array_map('intval',explode(',',(string)($options['lab-test-id'] ?? '')))))); sort($tests);
$modalities=array_values(array_unique((array)($options['radiology'] ?? []))); sort($modalities);
if (!$tests && !$modalities) exit("Choose at least one laboratory test or radiology examination.\n");
$names=[];
foreach ($tests as $id) {
    $q=$db->prepare('SELECT test_name FROM lab_tests WHERE id=? AND is_active=TRUE');$q->execute([$id]);$name=$q->fetchColumn();
    if (!$name) exit("Laboratory test not found or inactive.\n");$names[]=$name;
}
foreach ($modalities as $m) if (trim($m)==='' || mb_strlen($m)>50) exit("Radiology examination is empty or exceeds 50 characters.\n");
$request=['appointment_id'=>$appointmentId,'tests'=>$tests,'modalities'=>$modalities];
echo json_encode(['patient'=>$visit['patient_name'],'no_rm'=>$visit['no_rm'],'appointment'=>$appointmentId,'date'=>$visit['appointment_date'],'doctor'=>$visit['doctor_name'],'lab_tests'=>$names,'radiology'=>$modalities,'apply'=>isset($options['apply'])],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
if (!isset($options['apply'])) exit("Preview only. Rerun the same command with --apply to create these orders.\n");
$_SESSION=['user_id'=>(int)$visit['doctor_user_id'],'role'=>'dokter'];
$result=ClinicalWorkflow::transaction($db,function() use($db,$visit,$request,$batch,$tests,$modalities,$appointmentId) {
    $q=$db->prepare('SELECT pg_advisory_xact_lock(hashtext(?))');$q->execute(['clinical-preparation-'.$batch]);
    $q=$db->prepare('SELECT request_json,result_json FROM clinical_order_batches WHERE batch_key=?');$q->execute([$batch]);$old=$q->fetch(PDO::FETCH_ASSOC);
    if ($old) {
        if (json_decode($old['request_json'],true)!=$request) throw new DomainException('Batch key already belongs to a different request.');
        return ['success'=>true,'reused'=>true,'orders'=>json_decode($old['result_json'],true)];
    }
    $q=$db->prepare("SELECT id FROM appointments WHERE id=? AND status<>'CANCELLED' FOR UPDATE");$q->execute([$appointmentId]);
    if (!$q->fetchColumn()) throw new DomainException('Appointment is no longer available.');
    $q=$db->prepare('SELECT id FROM medical_records WHERE appointment_id=? AND patient_id=? ORDER BY id DESC LIMIT 1');$q->execute([$appointmentId,$visit['patient_id']]);$recordId=$q->fetchColumn() ?: null;
    $common=['patient_id'=>(int)$visit['patient_id'],'appointment_id'=>$appointmentId,'medical_record_id'=>$recordId,'doctor_id'=>(int)$visit['doctor_user_id'],'clinical_priority'=>'REGULER'];
    $orders=[];
    if ($tests) {
        $r=(new LaboratoryModel())->createLabOrder($common+['tests'=>$tests,'clinical_notes'=>'Pemeriksaan sesuai permintaan dokter']);
        if (!$r['success']) throw new DomainException($r['message']);$orders[]=['kind'=>'LAB','id'=>$r['order_id'],'number'=>$r['order_number']];
    }
    foreach ($modalities as $modality) {
        $r=(new RadiologyModel())->createRadiologyOrder($common+['modality_type'=>$modality,'clinical_indication'=>'Pemeriksaan sesuai permintaan dokter']);
        if (!$r['success']) throw new DomainException($r['message']);$orders[]=['kind'=>'RAD','id'=>$r['order_id'],'number'=>$r['order_number']];
    }
    $q=$db->prepare('INSERT INTO clinical_order_batches(batch_key,appointment_id,request_json,result_json) VALUES(?,?,?::jsonb,?::jsonb)');
    $q->execute([$batch,$appointmentId,json_encode($request),json_encode($orders)]);
    ClinicalWorkflow::audit($db,'PREPARE_CLINICAL_ORDERS','EMR','Persiapan data presentasi; batch '.$batch.'; kunjungan #'.$appointmentId);
    return ['success'=>true,'reused'=>false,'orders'=>$orders];
});
echo json_encode($result,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
exit($result['success']?0:1);
