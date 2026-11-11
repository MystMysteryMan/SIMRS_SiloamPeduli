<?php
// LOCAL TEST FIXTURE ONLY. Never run against the group's Supabase database.
$host=getenv('DB_HOST'); $name=getenv('DB_NAME');
if ($host!=='127.0.0.1' || !str_ends_with((string)$name,'_test')) { fwrite(STDERR,"Use a local database ending in _test.\n"); exit(1); }
$db=new PDO('pgsql:host='.$host.';port='.getenv('DB_PORT').';dbname='.$name,getenv('DB_USER'),getenv('DB_PASS'),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if ((int)$db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='public'")->fetchColumn()>0) { fwrite(STDERR,"Use a new empty test database.\n"); exit(1); }
$schema=explode('-- 4. MASTER SEED DATA',file_get_contents(__DIR__.'/../database/supabase_schema_complete.sql'))[0];
$schema=preg_replace('/CREATE EXTENSION[^;]+;/i','',$schema); $db->exec($schema);
$db->exec(file_get_contents(__DIR__.'/../database/migrations/20261110_modules_3_8_9.sql'));
$db->exec(file_get_contents(__DIR__.'/../database/migrations/20261111_clinical_approved.sql'));
$db->exec(file_get_contents(__DIR__.'/../database/migrations/20261111_clinical_approved.sql'));
// Applying twice must be safe.
$db->exec(file_get_contents(__DIR__.'/../database/migrations/20261110_modules_3_8_9.sql'));
$roles=[101=>'dokter',102=>'dokter',103=>'perawat',104=>'analis_laboratorium',105=>'radiografer',106=>'dokter_radiologi',107=>'admin',108=>'registrasi',109=>'kasir',110=>'apoteker'];
foreach ($roles as $id=>$role) { $q=$db->prepare('INSERT INTO users(id,username,password_hash,full_name,role,unit) VALUES(?,?,?,?,?,?)'); $q->execute([$id,'test'.$id,password_hash('local-test-only',PASSWORD_DEFAULT),'Pengguna Uji '.$id,$role,'Unit Uji']); }
$db->exec("INSERT INTO policlinics(id,code,name,queue_prefix) VALUES(1,'UJI','Poliklinik Uji','U')");
$db->exec("INSERT INTO doctors(id,user_id,policlinic_id,sip_number,full_name,specialization) VALUES(501,101,1,'TEST-501','Dokter Uji 101','Umum'),(502,102,1,'TEST-502','Dokter Uji 102','Umum')");
$db->exec("INSERT INTO patients(id,no_rm,nik,full_name,gender,birth_date,phone_number,address) VALUES(201,'SIM-0001','9999999999999901','Pasien Simulasi A','P','2000-01-01','000','Alamat Simulasi'),(202,'SIM-0002','9999999999999902','Pasien Simulasi B','L','2001-01-01','000','Alamat Simulasi'),(203,'SIM-0003','9999999999999903','Pasien Tanpa Kunjungan','P','2002-01-01','000','Alamat Simulasi')");
$db->exec("INSERT INTO appointments(id,booking_code,queue_number,patient_id,doctor_id,policlinic_id,appointment_date,status) VALUES(301,'BKG-TEST1','U-001',201,501,1,CURRENT_DATE-10,'SCHEDULED'),(302,'BKG-TEST2','U-002',202,502,1,CURRENT_DATE,'SCHEDULED'),(303,'BKG-TEST3','U-003',201,501,1,CURRENT_DATE,'CANCELLED')");
$db->exec("INSERT INTO icd_diagnoses(code,description_id,description_en) VALUES('Z00.0','Pemeriksaan umum','General examination')");
$db->exec("INSERT INTO lab_tests(id,test_code,test_name,normal_range,unit,tariff) VALUES(401,'HB-TEST','Hemoglobin simulasi','12-16','g/dL',100),(402,'GLU-TEST','Glukosa simulasi','70-100','mg/dL',100)");
echo "Local fixture ready; additive migration applied twice.\n";
