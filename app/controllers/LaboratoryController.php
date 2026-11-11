<?php
require_once __DIR__.'/../../core/Controller.php';
require_once __DIR__.'/../models/LaboratoryModel.php';
class LaboratoryController extends Controller {
    public function __construct() {
        $this->requireRole(($_GET['a'] ?? 'index')==='printResult' ? ['admin','analis_laboratorium','dokter','perawat','dokter_radiologi'] : ['admin','analis_laboratorium']);
    }
    public function index(): void {
        $m=new LaboratoryModel(); $status=$_GET['status'] ?? null; $priority=$_GET['priority'] ?? null; $search=trim((string)($_GET['search'] ?? ''));
        $p=$m->paginateOrders($status,$priority,$search,max(1,(int)($_GET['page'] ?? 1)),15);
        $this->render('laboratory/index',['pageTitle'=>'Laboratorium','orders'=>$p['data'],'pagination'=>$p,'selectedStatus'=>$status,'selectedPriority'=>$priority,'search'=>$search,'stats'=>$m->getLabStats()]);
    }
    public function detail(): void {
        $m=new LaboratoryModel(); $id=(int)($_GET['id'] ?? 0); $order=$m->getOrderById($id);
        if (!$order) { $this->setFlash('danger','Order tidak ditemukan.'); $this->redirect('index.php?c=laboratory'); }
        $input=$_SESSION['lab_input'][$id] ?? []; unset($_SESSION['lab_input'][$id]);
        $this->render('laboratory/process',['pageTitle'=>'Pemeriksaan Laboratorium','order'=>$order,'items'=>$m->getOrderItemsWithResults($id),'input'=>$input,'canEdit'=>ClinicalWorkflow::role()==='analis_laboratorium']);
    }
    private function post(): void {
        $this->requireRole(['analis_laboratorium']);
        if ($_SERVER['REQUEST_METHOD']!=='POST' || !verify_csrf()) { $this->setFlash('danger','Permintaan/token keamanan tidak valid.'); $this->redirect('index.php?c=laboratory'); }
    }
    private function finish(array $result,int $id): void {
        $this->setFlash($result['success']?'success':'danger',$result['message']); $this->redirect('index.php?c=laboratory&a=detail&id='.$id);
    }
    public function process(): void {
        $this->post(); $id=(int)($_POST['order_id'] ?? 0);
        if (empty($_POST['verified'])) $this->finish(['success'=>false,'message'=>'Konfirmasi kesesuaian pasien dan jenis pemeriksaan terlebih dahulu.'],$id);
        $this->finish((new LaboratoryModel())->processOrder($id,(int)$_SESSION['user_id'],isset($_POST['revision'])?(int)$_POST['revision']:null,trim((string)($_POST['no_rm'] ?? ''))),$id);
    }
    public function saveResult(): void {
        $this->post(); $id=(int)($_POST['order_id'] ?? 0); $input=is_array($_POST['results'] ?? null)?$_POST['results']:[];
        $r=(new LaboratoryModel())->saveResults($id,$input,(int)$_SESSION['user_id'],isset($_POST['revision'])?(int)$_POST['revision']:null);
        if (!$r['success']) $_SESSION['lab_input'][$id]=$input;
        $this->finish($r,$id);
    }
    public function validateResult(): void {
        $this->post(); $id=(int)($_POST['order_id'] ?? 0);
        if (($_POST['decision'] ?? '')==='approve' && empty($_POST['confirmed'])) $this->finish(['success'=>false,'message'=>'Konfirmasi pemeriksaan ulang hasil sebelum validasi.'],$id);
        $this->finish((new LaboratoryModel())->reviewResults($id,(int)$_SESSION['user_id'],(int)($_POST['revision'] ?? -1),($_POST['decision'] ?? '')==='approve',trim((string)($_POST['reason'] ?? ''))),$id);
    }
    public function finalizeResult(): void {
        $this->post(); $id=(int)($_POST['order_id'] ?? 0);
        $this->finish((new LaboratoryModel())->finalizeResults($id,(int)$_SESSION['user_id'],(int)($_POST['revision'] ?? -1)),$id);
    }
    public function printResult(): void {
        $m=new LaboratoryModel(); $id=(int)($_GET['id'] ?? 0); $order=$m->getOrderById($id);
        if (!$order || $order['status']!=='VALID') { http_response_code(403); echo 'Laporan hanya tersedia untuk hasil yang sudah VALID.'; return; }
        $this->render('laboratory/print_result',['pageTitle'=>'Laporan Laboratorium','order'=>$order,'items'=>$m->getOrderItemsWithResults($id)],false);
    }
}
