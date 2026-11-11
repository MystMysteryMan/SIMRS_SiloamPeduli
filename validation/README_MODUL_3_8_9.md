# Revisi EMR, Laboratory, Radiology untuk repository kelompok

Status: implementasi PHP pada branch `codex/clinical-approved-port` untuk ditinjau
sebelum penerapan ke production. Akun `13ebungam-tech` telah mendapat akses
collaborator. Migrasi Supabase, data kelompok, dan deployment Vercel masih harus
diverifikasi; hasil pengujian di bawah berasal dari database lokal terisolasi.

- Repository asal: https://github.com/MystMysteryMan/SIMRS_SiloamPeduli
- Dasar: c929e571634947bdf6c60f2e01ef5095b1b9d956 (upstream diperiksa 11 November 2026)
- Target: https://simrssiloampeduli.vercel.app/
- Acuan: https://simrs-siloam-peduli-klinis.readycove1024.chatgpt.site/

## Perubahan

EMR menampilkan antrean lintas tanggal, filter poli/dokter/tanggal/nama/RM/NIK,
dan daftar semua pasien. Pengguna memilih pasien dan kunjungan dari PostgreSQL
kelompok. Header, logo, sidebar, akun, dan tombol Keluar memakai layout asli
kelompok. Identitas Syella Max harus menggunakan record registrasi asli; nomor
RM-004 dari website acuan tidak ditimpa ke database kelompok.

Dokter penanggung jawab mengisi SOAP lengkap dan memilih ICD-10 dari master
database, meninjau data, mengonfirmasi, lalu menyimpan. ICD kosong menghasilkan
“Kode ICD-10 Wajib Diisi!”. Perawat mengisi vital sign, catatan perawat, alergi dan
lampiran tanpa mengubah SOAP/ICD dokter. Form EMR tidak membuat order penunjang,
termasuk bila field order dikirim manual ke endpoint. Hasil yang sudah tersimpan
masih dapat dibaca di riwayat dan dicetak.

Laboratorium mengikuti PENDING → PROCESS → REVIEW → VALIDATED → VALID.
Verifikasi mencocokkan RM, hubungan pasien/kunjungan/order, dan item pemeriksaan.
Nilai wajib diisi; unit kuantitatif menerima angka tidak negatif. Hasil dapat
dikembalikan ke input dengan alasan, tanpa menghapus draf sebelumnya. Validasi
memerlukan konfirmasi, lalu tombol Simpan hasil pemeriksaan menerbitkan hasil
ke EMR. Status VALIDATED belum dapat dicetak atau dibaca sebagai hasil final.
Simpan/validasi tercatat sebagai dua aktivitas berbeda. Hasil VALID dikunci.

Radiografer memverifikasi, memulai pemeriksaan, mengunggah citra JPG/PNG/WebP
maksimum 3 MB beserta teknik pemeriksaan. Dokter radiologi meninjau ekspertise,
mengembalikan untuk diperbaiki, atau memvalidasi. Hasil final terhubung pasien
serta order dan dapat dicetak dari EMR. Lampiran/citra disimpan pada tabel
PostgreSQL melalui endpoint PHP berautentikasi, bukan folder sementara Vercel.

Semua aksi klinis memeriksa peran dan CSRF serta memakai transaksi, audit,
version/revision check. Data atau hasil yang selesai tidak direset ketika dimuat ulang.

## Batas integrasi

File aplikasi yang berubah hanya controller/model/view tiga modul serta helper
khusus. Login, router, konfigurasi, layout bersama, registrasi, rawat jalan,
rawat inap, IGD, CPOE, farmasi, billing, pelaporan dan master data tidak diubah.
`EmrModel::saveMedicalRecord`, yang dipakai Rawat Jalan, dipertahankan persis.

Ini adaptasi ke PHP/Supabase, bukan salinan React/D1 dari website acuan. Struktur
kartu/antrean dan langkah utama disesuaikan, tetapi belum dapat dinyatakan identik
secara visual pada deployment kelompok. Form hasil mengikuti master pemeriksaan
kelompok; paket CBC kelompok masih berupa item Panel, bukan tiga parameter
terpisah seperti katalog website acuan. Konfigurasi parameter panel perlu
disepakati sebelum menyatakan kesamaan penuh.

Kode kelompok mempunyai dua jalur kunjungan: `appointments` (EMR/Billing) dan
`visits` (Rawat Jalan). Revisi ini menggunakan `appointments` sesuai jalur EMR
asal. Tidak mengubah atau menyatukan dua tabel tersebut secara diam-diam.
Pasien yang baru didaftarkan perlu appointment sebelum dapat dicatat di EMR.
Order CPOE lama tetap diterima; order tanpa appointment dapat dibaca melalui
riwayat pasien, tetapi tidak diasumsikan sebagai kunjungan tertentu.

## Penerapan

1. Pemilik repository dapat menerapkan paket, atau memberi `13ebungam-tech`
   akses collaborator Write lalu menerima undangannya dan mengizinkan repository
   tersebut pada koneksi GitHub yang dipakai ChatGPT. Tidak perlu membagikan password.
2. Buat branch dari `main` terbaru, lalu dari root repo jalankan:

   ```bash
   git switch -c revisi/modul-3-8-9
   git apply --check /lokasi-paket/modules-3-8-9.patch
   git apply /lokasi-paket/modules-3-8-9.patch
   ```

   Folder `files/` adalah alternatif berkas pengganti sesuai struktur root repo.
   Jangan menaruh folder `files` sebagai subfolder aplikasi.
3. Pemilik Supabase menjalankan dua migrasi secara berurutan pada SQL Editor:
   - `database/migrations/20261110_modules_3_8_9.sql`
   - `database/migrations/20261111_clinical_approved.sql`

   Keduanya aditif dan diuji dapat dijalankan ulang. Jangan menjalankan ulang
   seluruh seed/schema atau `database/setup.php` untuk pembaruan.
4. Push branch, periksa preview Vercel beserta data kelompok, lalu merge ke
   production branch proyek Vercel asal. Periksa status Ready dan URL asli.
   Pemilik proyek harus memastikan integrasi GitHub–Vercel memang menunjuk branch itu.

## Menyiapkan order Syella Max / menambah antrean

Gunakan pasien hasil registrasi yang sudah ada, bukan membuat pasien kedua.
Cari appointment pasien tersebut melalui Antrean & Janji Temu. Jika belum ada,
petugas registrasi membuat appointment dengan dokter yang sesuai.

Untuk persiapan presentasi, tersedia script CLI yang menerima **ID appointment
nyata** dan **ID pemeriksaan dari master**. Script ini tidak dapat dipanggil via
HTTP, tidak membuat/mengganti pasien, tidak menyentuh hasil lama, dan tidak
membuat order dua kali bila kunci batch sama. Semua DB environment variables
harus diisi secara eksplisit oleh pemilik proyek; fallback credential tidak dipakai.

```bash
php database/provision_clinical_orders.php \
  --appointment-id=ID_KUNJUNGAN_SYELLA \
  --lab-test-id=ID_PEMERIKSAAN_LAB \
  --radiology='Rontgen Thorax PA' \
  --radiology='Rontgen Ekstremitas' \
  --batch=syella-presentasi-20261111
```

Perintah awal hanya menampilkan rencana. Setelah memastikan nama, RM, dokter,
dan pemeriksaan benar, ulangi dengan `--apply`. Gunakan batch lain untuk
permintaan baru yang memang disengaja. Pengisian hasil tidak dilakukan script.
Daftar identitas dan pilihan pemeriksaan harus diperiksa pengelola sebelum apply.

## Verifikasi lokal

- 170 assertion HTTP untuk alur EMR/Lab/Radiologi, bukan 170 skenario independen.
- 9 pemeriksaan kompatibilitas Rawat Jalan, CPOE, format nomor order dan Billing.
- 4 pemeriksaan persiapan order: preview tanpa tulis, satu lab + dua radiologi,
  pengulangan tanpa duplikasi, dan identitas pasien tetap sama.
- Migrasi dijalankan dua kali di PostgreSQL 17 lokal.
- Pemeriksaan sintaks PHP seluruh berkas aplikasi/tes lulus.
- Hasil rinci: `validation/results-20261111.json`.

Belum terverifikasi: skema/data Supabase aktif, layout dan seluruh klik di Vercel,
semua transaksi modul lain, dan kesamaan visual persis dengan website acuan.

Untuk tes ulang, gunakan PHP 8.2+ (pdo_pgsql, mbstring, fileinfo), PostgreSQL,
Python 3+lxml. Fixture hanya menerima database lokal kosong dengan nama `_test`.

```bash
export DB_HOST=127.0.0.1 DB_PORT=5432 DB_NAME=simrs_workflow_test
export DB_USER=USER_LOKAL DB_PASS=PASSWORD_LOKAL DB_SSLMODE=disable
php validation/prepare_database.php
php -d upload_max_filesize=4M -d post_max_size=5M -S 127.0.0.1:8088 -t .
# Terminal lain, environment sama:
python3 validation/run_http.py
php validation/compatibility.php
php validation/provision_orders.php
```
