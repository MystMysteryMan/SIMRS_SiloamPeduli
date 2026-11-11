"""HTTP acceptance tests against an isolated, freshly prepared local PostgreSQL database."""
import base64, http.cookiejar, json, os, subprocess, sys, urllib.request, urllib.parse, urllib.error, uuid
from lxml import html
BASE=os.environ.get('TEST_BASE_URL','http://127.0.0.1:8088/')
if not BASE.startswith('http://127.0.0.1:') or not os.environ.get('DB_NAME','').endswith('_test'):
    raise SystemExit('Local test URL and *_test database required.')
PSQL=os.environ.get('TEST_PSQL','psql')
passed=[]
def check(condition,label):
    if not condition: raise AssertionError(label)
    passed.append(label)
def sql(q):
    r=subprocess.run([PSQL,'-h','127.0.0.1','-p',os.environ['DB_PORT'],'-U',os.environ['DB_USER'],'-d',os.environ['DB_NAME'],'-At','-c',q],capture_output=True,text=True,check=True)
    return r.stdout.strip()
class Client:
    def __init__(self,uid=None):
        self.opener=urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
        if uid:
            self.request('index.php?c=auth&a=login',{'username':'test'+str(uid),'password':'local-test-only'})
    def request(self,path,data=None,files=None):
        url=urllib.parse.urljoin(BASE,path)
        if files:
            boundary='TEST'+uuid.uuid4().hex; parts=[]
            for k,v in (data or {}).items(): parts.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{k}"\r\n\r\n{v}\r\n'.encode())
            for key,(filename,content,mime) in files.items():
                parts += [f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"; filename="{filename}"\r\nContent-Type: {mime}\r\n\r\n'.encode(),content,b'\r\n']
            parts.append(f'--{boundary}--\r\n'.encode()); body=b''.join(parts)
            headers={'Content-Type':'multipart/form-data; boundary='+boundary}
        else:
            body=urllib.parse.urlencode(data,doseq=True).encode() if data is not None else None;headers={}
        try:r=self.opener.open(urllib.request.Request(url,data=body,headers=headers),timeout=15)
        except urllib.error.HTTPError as e:r=e
        content=r.read(); text=content.decode('utf-8',errors='replace')
        if r.headers.get('Content-Type','').startswith('text/html'):
            for error in ('Fatal error:','Warning:','Deprecated:','Koneksi Supabase PostgreSQL Gagal'):
                if error in text: raise AssertionError(error+' on '+url+'\n'+text[-3500:])
        return r.status,text,content,r.headers
    def page(self,path):
        status,text,_,_=self.request(path);check(status==200,'HTTP page '+path);return html.fromstring(text)
    def form(self,path,action):
        tree=self.page(path)
        forms=tree.xpath('//form[contains(@action,$action)]',action='a='+action)
        check(len(forms)>=1,'form '+action+' on '+path)
        form=forms[0];data={}
        for x in form.xpath('.//input[@type="hidden"]'):data[x.get('name')]=x.get('value','')
        return form.get('action'),data
    def submit(self,path,action,values,files=None):
        target,data=self.form(path,action);data.update(values);return self.request(target,data,files)
D=Client(101); N=Client(103); L=Client(104); R=Client(105); S=Client(106); O=Client(102); A=Client(107)
for term in ['SIM-0001','9999999999999901','simulasi a']:
    t=D.page('index.php?c=emr&q='+urllib.parse.quote(term));check('Pasien Simulasi A' in t.text_content(),'search '+term)
t=D.page('index.php?c=emr&a=visits&patient_id=201');check('id=301' in html.tostring(t).decode(),'historical visit available');check('Pasien Simulasi B' not in t.text_content(),'patient selection identity')
t=D.page('index.php?c=emr&a=visits&patient_id=203');check('belum memiliki kunjungan' in t.text_content(),'empty visit guidance')
path='index.php?c=emr&a=examine&id=301'
t=D.page(path);check(sql('SELECT status FROM appointments WHERE id=301')=='SCHEDULED','GET does not change visit')
check('name="subjective"' not in html.tostring(N.page(path)).decode(),'nurse cannot edit SOAP UI')
check('name="subjective"' not in html.tostring(O.page(path)).decode(),'other doctor cannot edit visit')
check('name="subjective"' not in html.tostring(D.page('index.php?c=emr&a=examine&id=303')).decode(),'cancelled visit read only')
data={'appointment_id':301,'patient_id':201,'subjective':'Keluhan simulasi','objective':'Pemeriksaan simulasi','assessment':'Penilaian simulasi','plan':'Rencana simulasi','icd10_code':'Z00.0','vital_bp':'120/80','vital_temp':'36.5','vital_pulse':'80','vital_rr':'18','weight_kg':'60','height_cm':'160','new_allergy':'Alergen simulasi','allergy_reaction':'Reaksi simulasi','allergy_severity':'RINGAN','clinical_notes':'Riwayat dan hasil pemeriksaan simulasi','order_lab':'1','lab_tests[]':[401,402],'order_radiology':'1'}
for update,expected in [({'icd10_code':''},'Kode ICD-10 Wajib Diisi!'),({'icd10_code':'INVALID'},'referensi diagnosis'),({'subjective':''},'Subjective wajib diisi'),({'patient_id':202},'Pasien dan kunjungan tidak cocok'),({'vital_pulse':'abc'},'tanda vital tidak valid')]:
    _,text,_,_=D.submit(path,'review',data|update);check(expected in text,'reject '+expected);check(sql('SELECT COUNT(*) FROM medical_records')=='0','invalid EMR no write')
# Role bypass is rejected on the server even if a SOAP field is posted by the nurse.
_,text,_,_=N.submit(path,'review',{'appointment_id':301,'patient_id':201,'subjective':'unauthorized','nurse_notes':'note'});check('hanya dapat diisi dokter' in text,'nurse SOAP POST rejected')
# CSRF, direct save and fake actor.
_,formdata=D.form(path,'review');formdata.update(data);formdata['csrf_token']='bad'
_,text,_,_=D.request('index.php?c=emr&a=review',formdata);check('token keamanan' in text,'invalid CSRF rejected')
_,text,_,_=D.submit(path,'review',data|{'doctor_id':999});tree=html.fromstring(text)
check('Tinjau Rekam Medis' in tree.text_content(),'server review stage')
save=tree.xpath('//form[contains(@action,"a=save")]')[0];payload={x.get('name'):x.get('value','') for x in save.xpath('.//input')}
_,text,_,_=D.request(save.get('action'),payload);check('Rekam medis berhasil disimpan' in text,'EMR save success')
check(sql('SELECT doctor_id FROM medical_records WHERE appointment_id=301')=='101','actor is session user not doctor table ID')
check(sql('SELECT COUNT(*) FROM lab_orders')=='0','EMR never creates lab orders even if submitted')
check(sql('SELECT COUNT(*) FROM radiology_orders')=='0','EMR never creates radiology orders even if submitted')
check('name="order_lab"' not in html.tostring(D.page(path)).decode(),'EMR has no order input UI')
check(bool(D.page(path).xpath('//select[@id="icd10_code"]/option[@value="Z00.0"]')),'doctor ICD select contains database code')
D.request(save.get('action'),payload);check(sql('SELECT COUNT(*) FROM medical_records')=='1','replayed EMR submit does not duplicate record')
# Incoming orders from service units are stored in the shared original schema.
sql("INSERT INTO lab_orders(order_number,patient_id,appointment_id,doctor_id,medical_record_id,status) VALUES('LAB-TEST-0001',201,301,101,(SELECT id FROM medical_records WHERE appointment_id=301),'PENDING')")
sql("INSERT INTO lab_order_items(lab_order_id,lab_test_id,test_name) SELECT (SELECT id FROM lab_orders LIMIT 1),id,test_name FROM lab_tests")
sql("INSERT INTO radiology_orders(order_number,patient_id,appointment_id,doctor_id,medical_record_id,modality_type,clinical_indication,status) VALUES('RAD-TEST-0001',201,301,101,(SELECT id FROM medical_records WHERE appointment_id=301),'Thorax PA','Indikasi uji','PENDING')")
# Nurse can save observations without changing doctor SOAP or completion.
_,text,_,_=N.submit(path,'review',{'nurse_notes':'Catatan perawat tersimpan','vital_bp':'118/78','vital_pulse':'78'})
f=html.fromstring(text).xpath('//form[contains(@action,"a=save")]')[0]
N.request(f.get('action'),{x.get('name'):x.get('value','') for x in f.xpath('.//input')})
check(sql('SELECT nurse_notes FROM medical_records WHERE appointment_id=301')=='Catatan perawat tersimpan','nurse note persists')
check(sql('SELECT subjective FROM medical_records WHERE appointment_id=301')=='Keluhan simulasi','nurse preserves SOAP')
check(sql('SELECT doctor_id FROM medical_records WHERE appointment_id=301')=='101','nurse preserves doctor identity')
# Optimistic locking: second session alters a record between doctor review and commit.
clean=data.copy();clean.pop('order_lab');clean.pop('order_radiology');clean.pop('new_allergy')
_,text,_,_=D.submit(path,'review',clean);f=html.fromstring(text).xpath('//form[contains(@action,"a=save")]')[0];stale={x.get('name'):x.get('value','') for x in f.xpath('.//input')}
sql("UPDATE medical_records SET updated_at=NOW()+INTERVAL '1 second' WHERE appointment_id=301")
_,text,_,_=D.request(f.get('action'),stale);check('telah berubah' in text,'stale EMR review rejected')
D.page(path+'&fresh=1')
# Attachments: real multipart upload and authenticated download.
png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aSpkAAAAASUVORK5CYII=')
_,text,_,_=N.submit(path,'uploadAttachment',{}, {'attachment':('lampiran.png',png,'image/png')});check('Lampiran berhasil disimpan' in text,'attachment upload persists')
fid=sql('SELECT id FROM emr_attachments LIMIT 1');status,_,body,_=D.request('index.php?c=emr&a=attachment&id='+fid);check(status==200 and body==png,'attachment downloads identical bytes')
_,text,_,_=N.submit(path,'uploadAttachment',{}, {'attachment':('invalid.php',b'<?php echo 1;','application/octet-stream')});check('Format berkas tidak didukung' in text,'unsafe file rejected')
# Lab workflow, atomic rejection, review correction, final lock and role guards.
lid=sql('SELECT id FROM lab_orders LIMIT 1');lp='index.php?c=laboratory&a=detail&id='+lid
_,text,_,_=L.submit(lp,'process',{'verified':'1','no_rm':'WRONG'});check('Nomor RM tidak cocok' in text,'lab identity verification rejects mismatch')
sql('UPDATE lab_orders SET patient_id=202 WHERE id='+lid)
_,text,_,_=L.submit(lp,'process',{'verified':'1','no_rm':'SIM-0002'});check('Hubungan pasien dan kunjungan' in text,'lab rejects mismatched order relationships')
sql('UPDATE lab_orders SET patient_id=201 WHERE id='+lid)
L.submit(lp,'process',{'verified':'1','no_rm':'SIM-0001'});check(sql('SELECT status FROM lab_orders WHERE id='+lid)=='PROCESS','lab process transition')
check(D.request('index.php?c=laboratory&a=printResult&id='+lid)[0]==403,'lab draft cannot print')
items=sql('SELECT id FROM lab_order_items ORDER BY id').splitlines();results={}
for i,value in zip(items,['13.1','90']):results.update({f'results[{i}][result_value]':value,f'results[{i}][unit]':'unit-test',f'results[{i}][normal_range]':'ref-test',f'results[{i}][result_flag]':'NORMAL',f'results[{i}][analyst_notes]':'note-test'})
_,text,_,_=L.submit(lp,'saveResult',results|{f'results[{items[-1]}][result_value]':''});check('wajib diisi' in text,'lab incomplete result rejected');check(sql('SELECT COUNT(*) FROM lab_results')=='0','no partial lab write')
_,text,_,_=L.submit(lp,'saveResult',results|{f'results[{items[0]}][result_value]':'abc'});check('harus berupa angka' in text,'numeric lab rejects nonnumber')
_,text,_,_=L.submit(lp,'saveResult',results|{f'results[{items[0]}][result_value]':'-1'});check('harus berupa angka' in text,'numeric lab rejects negative')
sql("ALTER TABLE audit_logs ADD CONSTRAINT reject_test_draft CHECK(action <> 'SAVE_LAB_DRAFT') NOT VALID")
_,text,_,_=L.submit(lp,'saveResult',results);check('Data belum tersimpan' in text,'database write failure does not show success');check(sql('SELECT COUNT(*) FROM lab_results')=='0','failed audit rolls back all lab results')
sql('ALTER TABLE audit_logs DROP CONSTRAINT reject_test_draft')
L.submit(lp,'saveResult',results);check(sql('SELECT status FROM lab_orders WHERE id='+lid)=='REVIEW','lab review stage persists')
check(sql('SELECT COUNT(*) FROM lab_results WHERE validated_at IS NOT NULL')=='0','lab draft not marked valid')
check('unit-test' not in D.page('index.php?c=emr&a=history&patient_id=201').text_content(),'lab draft values hidden in EMR')
L.submit(lp,'validateResult',{'decision':'return','reason':'Koreksi nilai'});check(sql('SELECT status FROM lab_orders WHERE id='+lid)=='PROCESS','lab return to input')
L.submit(lp,'saveResult',results);action,labfinal=L.form(lp,'validateResult');labfinal['decision']='approve';labfinal['confirmed']='1';L.request(action,labfinal)
check(sql('SELECT status FROM lab_orders WHERE id='+lid)=='VALIDATED','lab validation is separate from saving')
check(D.request('index.php?c=laboratory&a=printResult&id='+lid)[0]==403,'validated unsaved result cannot print')
check('ref-test' not in D.page('index.php?c=emr&a=history&patient_id=201').text_content(),'validated unsaved result hidden from EMR')
L.submit(lp,'finalizeResult',{})
check(sql('SELECT status FROM lab_orders WHERE id='+lid)=='VALID','lab final save completed')
check(sql('SELECT saved_by FROM lab_orders WHERE id='+lid)=='104','lab save actor recorded')
L.request(action,labfinal);check(sql('SELECT status FROM lab_orders WHERE id='+lid)=='VALID','lab repeat validation locked')
status,text,_,_=D.request('index.php?c=laboratory&a=printResult&id='+lid);check(status==200 and 'ref-test' in text,'doctor can print valid lab report')
check('ref-test' in D.page('index.php?c=emr&a=history&patient_id=201').text_content(),'lab reference range appears in EMR')
# Radiology workflow and persistence across login sessions.
rid=sql('SELECT id FROM radiology_orders LIMIT 1');rp='index.php?c=radiology&a=detail&id='+rid
check('a=process' not in html.tostring(S.page(rp)).decode(),'radiologist cannot start radiographer procedure UI')
_,valid_rad_token=R.form(rp,'process')
R.submit(rp,'process',{'verified':'1','no_rm':'SIM-0001','scheduled_at':'2026-11-11T08:00'});check(sql('SELECT status FROM radiology_orders WHERE id='+rid)=='PROCESS','radiology process')
_,text,_,_=R.submit(rp,'upload',{'technical_notes':'Catatan teknis simulasi'},{'image':('citra.png',png,'image/png')});check('Citra dan catatan tersimpan' in text,'radiology image upload')
check(sql('SELECT status FROM radiology_orders WHERE id='+rid)=='REVIEW','radiology review stage')
check(D.request('index.php?c=radiology&a=image&id='+rid)[0]==403,'doctor cannot read unvalidated image')
check(D.request('index.php?c=radiology&a=printResult&id='+rid)[0]==403,'draft radiology cannot print')
check(Client(105).request('index.php?c=radiology&a=image&id='+rid)[2]==png,'image persists in new session')
# Cannot validate as radiographer even by manual POST.
_,denied,_,_=R.request('index.php?c=radiology&a=validateResult',{'order_id':rid,'revision':2,'decision':'approve','csrf_token':valid_rad_token['csrf_token']})
check('Akses ditolak' in denied,'radiographer validation denied before processing')
check(sql('SELECT status FROM radiology_orders WHERE id='+rid)=='REVIEW','radiographer cannot validate server endpoint')
S.submit(rp,'validateResult',{'decision':'return','reason':'Perbaiki citra simulasi'});check(sql('SELECT status FROM radiology_orders WHERE id='+rid)=='PROCESS','radiologist returns to radiographer')
R.submit(rp,'upload',{'technical_notes':'Citra perbaikan'},{'image':('citra-baru.png',png,'image/png')})
_,text,_,_=S.submit(rp,'saveExpertise',{'expertise_findings':'Temuan simulasi','expertise_conclusion':'Kesimpulan simulasi'});check('Tinjau Ekspertise Radiologi' in text,'radiology review before final')
check(sql('SELECT COUNT(*) FROM radiology_results WHERE validated_at IS NOT NULL')=='0','radiology draft not final')
review='index.php?c=radiology&a=review&id='+rid;action,radfinal=S.form(review,'validateResult');S.request(action,radfinal)
check(sql('SELECT status FROM radiology_orders WHERE id='+rid)=='VALID','radiology approved')
check(sql('SELECT radiologist_id FROM radiology_orders WHERE id='+rid)=='106','radiologist identity recorded')
status,text,_,_=D.request('index.php?c=radiology&a=printResult&id='+rid);check(status==200 and 'Kesimpulan simulasi' in text,'doctor prints valid radiology')
check(D.request('index.php?c=radiology&a=image&id='+rid)[2]==png,'doctor sees persisted validated image')
check('Kesimpulan simulasi' in D.page('index.php?c=emr&a=history&patient_id=201').text_content(),'radiology returns to EMR')
check('Kesimpulan simulasi' not in D.page('index.php?c=emr&a=history&patient_id=202').text_content(),'results do not leak into another patient record')
check('a=upload' not in html.tostring(R.page(rp)).decode(),'validated radiology upload locked UI')
check('a=saveExpertise' not in html.tostring(S.page(rp)).decode(),'validated expertise locked UI')
# Successful clinical changes must have an audit entry.
check(int(sql("SELECT COUNT(*) FROM audit_logs WHERE module IN ('EMR','LABORATORY','RADIOLOGY')"))>=12,'clinical audit trail persists')
# Unrelated existing routes remain reachable, no edits to their source.
for uid,paths in [(101,['index.php?c=dashboard','index.php?c=cpoe','index.php?c=outpatient']),(108,['index.php?c=patient','index.php?c=appointment']),(109,['index.php?c=billing']),(110,['index.php?c=pharmacy']),(107,['index.php?c=master'])]:
    c=Client(uid)
    for p in paths:c.page(p)
print(json.dumps({'passed':len(passed),'checks':passed},ensure_ascii=False,indent=2))
