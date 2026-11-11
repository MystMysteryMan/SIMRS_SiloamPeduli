<?php
if (getenv('DB_HOST')!=='127.0.0.1' || !str_ends_with((string)getenv('DB_NAME'),'_test')) exit('Local test database required.');
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../core/Database.php';
require_once __DIR__.'/../app/models/EmrModel.php';
require_once __DIR__.'/../app/models/LaboratoryModel.php';
require_once __DIR__.'/../app/models/RadiologyModel.php';
require_once __DIR__.'/../app/models/CpoeModel.php';
require_once __DIR__.'/../app/models/BillingModel.php';
$_SESSION=['user_id'=>101,'role'=>'dokter'];
function verify($ok,$message) { if (!$ok) throw new RuntimeException($message); echo "PASS: $message\n"; }
$db=Database::getConnection();
// Preserve the legacy entry point still called by OutpatientController.
$r=(new EmrModel())->saveMedicalRecord(['patient_id'=>202,'doctor_id'=>102,'icd10_code'=>'Z00.0','subjective'=>'Legacy rawat jalan','objective'=>'Legacy','assessment'=>'Legacy','plan'=>'Legacy']);
verify($r['success'],'Legacy Outpatient EMR API remains usable');
$billing=(new BillingModel())->aggregateVisitCosts('RAWAT_JALAN',301);
verify($billing['success'] && (int)$billing['patient_id']===201,'Billing reads same patient and appointment');
$types=array_column($billing['items'],'item_type');
verify(in_array('LABORATORIUM',$types) && in_array('RADIOLOGI',$types),'Existing Billing aggregates lab and radiology items');
// The CPOE legacy order format omits appointment_id; it may link the medical record.
$mrid=(int)$db->query('SELECT id FROM medical_records WHERE appointment_id=301')->fetchColumn();
foreach (['LABORATORIUM','RADIOLOGI'] as $type) {
    $r=(new CpoeModel())->createOrder(['patient_id'=>201,'doctor_id'=>101,'visit_type'=>'RAWAT_JALAN','reference_id'=>$mrid,'order_type'=>$type,'clinical_notes'=>'Compatibility test'],[['item_type'=>$type,'item_id'=>$type==='LABORATORIUM'?401:null,'item_name'=>'Pemeriksaan kompatibilitas','quantity'=>1,'unit_price'=>100]],101);
    verify($r['success'],"Existing CPOE creates $type order against revised clinical models");
    $table=$type==='LABORATORIUM'?'lab_orders':'radiology_orders';
    $order=$db->query("SELECT * FROM $table ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    verify((bool)preg_match('/^(LAB|RAD)-[0-9]{8}-[0-9]{4}$/D',$order['order_number']),'Legacy numeric order numbering preserved');
    $_SESSION=['user_id'=>$type==='LABORATORIUM'?104:105,'role'=>$type==='LABORATORIUM'?'analis_laboratorium':'radiografer'];
    $model=$type==='LABORATORIUM'?new LaboratoryModel():new RadiologyModel();
    $processed=$model->processOrder((int)$order['id'],(int)$_SESSION['user_id'],0,'SIM-0001');
    verify($processed['success'],"Unit accepts existing CPOE $type order without appointment_id");
    $_SESSION=['user_id'=>101,'role'=>'dokter'];
}
