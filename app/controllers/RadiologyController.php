<?php
require_once __DIR__.'/../../core/Controller.php';
require_once __DIR__.'/../models/RadiologyModel.php';
class RadiologyController extends Controller {
    public function __construct() {
        $this->requireRole(in_array($_GET['a'] ?? '',['printResult','image'],true) ? ['admin','radiografer','dokter_radiologi','dokter','perawat'] : ['admin','radiografer','dokter_radiologi']);
    }
    public function index(): void {
        $m=new RadiologyModel(); $status=$_GET['status'] ?? null; $priority=$_GET['priority'] ?? null; $search=trim((string)($_GET['search'] ?? ''));
        $p=$m->paginateOrders($status,$priority,$search,max(1,(int)($_GET['page'] ?? 1)),15);
        $this->render('radiology/index',['pageTitle'=>'Radiologi','orders'=>$p['data'],'pagination'=>$p,'selectedStatus'=>$status,'selectedPriority'=>$priority,'search'=>$search,'stats'=>$m->getRadiologyStats()]);
    }
    private function order(int $id): array {
        $order=(new RadiologyModel())->getOrderById($id);
        if (!$order) { $this->setFlash('danger','Order radiologi tidak ditemukan.'); $this->redirect('index.php?c=radiology'); }
        return $order;
    }
    public function detail(): void {
        $id=(int)($_GET['id'] ?? 0); $order=$this->order($id);
        $input=$_SESSION['rad_input'][$id] ?? []; unset($_SESSION['rad_input'][$id]);
        $this->render('radiology/process',['pageTitle'=>'Pemeriksaan Radiologi','order'=>$order,'input'=>$input,
            'canUpload'=>ClinicalWorkflow::role()==='radiografer','canExpertise'=>ClinicalWorkflow::role()==='dokter_radiologi']);
    }
    private function post(array $roles): void {
        $this->requireRole($roles);
        if ($_SERVER['REQUEST_METHOD']!=='POST' || !verify_csrf()) { $this->setFlash('danger','Permintaan/token keamanan tidak valid.'); $this->redirect('index.php?c=radiology'); }
    }
    private function finish(array $result,int $id,string $action='detail'): void {
        $this->setFlash($result['success']?'success':'danger',$result['message']); $this->redirect('index.php?c=radiology&a='.$action.'&id='.$id);
    }
    public function process(): void {
        $this->post(['radiografer']); $id=(int)($_POST['order_id'] ?? 0);
        if (empty($_POST['verified'])) $this->finish(['success'=>false,'message'=>'Konfirmasi kesesuaian identitas pasien dan pemeriksaan.'],$id);
        $this->finish((new RadiologyModel())->processOrder($id,(int)$_SESSION['user_id'],isset($_POST['revision'])?(int)$_POST['revision']:null,(string)($_POST['no_rm'] ?? ''),(string)($_POST['scheduled_at'] ?? '')),$id);
    }
    public function upload(): void {
        $this->post(['radiografer']); $id=(int)($_POST['order_id'] ?? 0);
        $this->finish((new RadiologyModel())->uploadImage($id,$_FILES['image'] ?? [],(string)($_POST['technical_notes'] ?? ''),(int)$_SESSION['user_id'],isset($_POST['revision'])?(int)$_POST['revision']:null),$id);
    }
    public function saveExpertise(): void {
        $this->post(['dokter_radiologi']); $id=(int)($_POST['order_id'] ?? 0);
        $r=(new RadiologyModel())->saveExpertise($id,(string)($_POST['expertise_findings'] ?? ''),(string)($_POST['expertise_conclusion'] ?? ''),(int)$_SESSION['user_id'],isset($_POST['revision'])?(int)$_POST['revision']:null);
        if (!$r['success']) $_SESSION['rad_input'][$id]=['expertise_findings'=>(string)($_POST['expertise_findings'] ?? ''),'expertise_conclusion'=>(string)($_POST['expertise_conclusion'] ?? '')];
        $this->finish($r,$id,$r['success']?'review':'detail');
    }
    public function review(): void {
        $this->requireRole(['dokter_radiologi']); $order=$this->order((int)($_GET['id'] ?? 0));
        if ($order['status']!=='REVIEW' || empty($order['expertise_findings'])) $this->redirect('index.php?c=radiology&a=detail&id='.$order['id']);
        $this->render('radiology/review',['pageTitle'=>'Tinjau Validasi Radiologi','order'=>$order]);
    }
    public function validateResult(): void {
        $this->post(['dokter_radiologi']); $id=(int)($_POST['order_id'] ?? 0);
        $this->finish((new RadiologyModel())->validateExpertise($id,(int)$_SESSION['user_id'],(int)($_POST['revision'] ?? -1),($_POST['decision'] ?? '')==='approve',(string)($_POST['reason'] ?? '')),$id);
    }
    public function printResult(): void {
        $order=$this->order((int)($_GET['id'] ?? 0));
        if ($order['status']!=='VALID') { http_response_code(403); echo 'Laporan hanya tersedia untuk hasil yang sudah VALID.'; return; }
        $this->render('radiology/print_result',['pageTitle'=>'Laporan Radiologi','order'=>$order],false);
    }
    public function image(): void {
        $id=(int)($_GET['id'] ?? 0); $order=$this->order($id);
        if ($order['status']!=='VALID' && !in_array(ClinicalWorkflow::role(),['radiografer','dokter_radiologi','admin'],true)) { http_response_code(403); echo 'Citra belum divalidasi.'; return; }
        $file=(new RadiologyModel())->query('SELECT * FROM radiology_image_files WHERE radiology_order_id=?',[$id])[0] ?? null;
        if (!$file) { http_response_code(404); echo 'Citra tidak ditemukan.'; return; }
        ClinicalWorkflow::sendFile($file);
    }
}
