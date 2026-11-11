<?php
if (getenv('DB_HOST')!=='127.0.0.1' || !str_ends_with((string)getenv('DB_NAME'),'_test')) exit('Local test database required.');
require_once __DIR__.'/../config/config.php';
require_once __DIR__.'/../core/Database.php';
$db=Database::getConnection();
function countOrders(PDO $db): array { return $db->query('SELECT (SELECT COUNT(*) FROM lab_orders) lab,(SELECT COUNT(*) FROM radiology_orders) rad')->fetch(PDO::FETCH_ASSOC); }
function runSetup(array $args): array {
    $cmd=array_merge([getenv('TEST_PHP')?:PHP_BINARY,__DIR__.'/../database/provision_clinical_orders.php','--appointment-id=301','--lab-test-id=401,402','--radiology=Rontgen Thorax PA','--radiology=Rontgen Ekstremitas','--batch=acceptance-orders'],$args);
    $pipes=[];$p=proc_open($cmd,[1=>['pipe','w'],2=>['pipe','w']],$pipes);$out=stream_get_contents($pipes[1]);$error=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($p);
    if($code!==0) throw new RuntimeException($out.$error);
    return [$out,$code];
}
$before=countOrders($db);runSetup([]);
if(countOrders($db)!=$before) throw new RuntimeException('Preview wrote data');
runSetup(['--apply']);$after=countOrders($db);
if((int)$after['lab']!==(int)$before['lab']+1 || (int)$after['rad']!==(int)$before['rad']+2) throw new RuntimeException('Expected 1 lab and 2 radiology orders');
runSetup(['--apply']);if(countOrders($db)!=$after) throw new RuntimeException('Repeated batch duplicated orders');
if($db->query("SELECT COUNT(*) FROM patients WHERE no_rm='SIM-0001'")->fetchColumn()!=1) throw new RuntimeException('Existing patient identity changed');
echo "PASS: Provisioning preview is read-only; apply creates linked orders; repeat preserves counts and patient identity.\n";
