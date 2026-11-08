-- ==========================================================
-- SISTEM INFORMASI MANAJEMEN RUMAH SAKIT (SIMRS)
-- SILOAM PEDULI - DATABASE SCHEMA (4 MODUL TERPILIH)
-- Modul 1: Authentication & User Management
-- Modul 2: Patient Registration
-- Modul 12: Scheduling & Appointment
-- Modul 16: Master Data & Configuration
-- ==========================================================

CREATE DATABASE IF NOT EXISTS `simrs_siloam` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `simrs_siloam`;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. PENGATURAN SISTEM (MODUL 16)
DROP TABLE IF EXISTS `system_configurations`;
CREATE TABLE `system_configurations` (
    `config_key` VARCHAR(50) NOT NULL PRIMARY KEY,
    `config_value` TEXT NOT NULL,
    `description` VARCHAR(255) NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. PENGGUNA SISTEM & RBAC (MODUL 1 & EKSPANSI FASE 2)
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NULL,
    `role` ENUM('admin', 'registrasi', 'dokter', 'perawat', 'analis_laboratorium', 'radiografer', 'dokter_radiologi', 'triase', 'apoteker', 'kasir') NOT NULL DEFAULT 'registrasi',
    `unit` VARCHAR(100) NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. AUDIT LOG AKTIVITAS (MODUL 1 - SOP-001)
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(100) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `details` TEXT NULL,
    `ip_address` VARCHAR(45) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_audit_module` (`module`),
    INDEX `idx_audit_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. MASTER POLIKLINIK (MODUL 16)
DROP TABLE IF EXISTS `policlinics`;
CREATE TABLE `policlinics` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `queue_prefix` VARCHAR(5) NOT NULL DEFAULT 'A',
    `description` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. MASTER DOKTER (MODUL 16)
DROP TABLE IF EXISTS `doctors`;
CREATE TABLE `doctors` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `policlinic_id` INT NOT NULL,
    `sip_number` VARCHAR(50) NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `specialization` VARCHAR(100) NOT NULL,
    `consultation_fee` DECIMAL(12,2) NOT NULL DEFAULT 150000.00,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`policlinic_id`) REFERENCES `policlinics`(`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. JADWAL PRAKTIK & KUOTA DOKTER (MODUL 12 & 16)
DROP TABLE IF EXISTS `doctor_schedules`;
CREATE TABLE `doctor_schedules` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `doctor_id` INT NOT NULL,
    `day_name` ENUM('Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu') NOT NULL,
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `quota` INT NOT NULL DEFAULT 30,
    `is_active` TINYINT(1) DEFAULT 1,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. MASTER RUANGAN & TEMPAT TIDUR / BED (MODUL 16)
DROP TABLE IF EXISTS `rooms`;
CREATE TABLE `rooms` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_code` VARCHAR(20) NOT NULL UNIQUE,
    `room_name` VARCHAR(100) NOT NULL,
    `class_type` ENUM('VVIP', 'VIP', 'KELAS_1', 'KELAS_2', 'KELAS_3', 'ICU', 'ISOLASI') NOT NULL DEFAULT 'KELAS_1',
    `daily_rate` DECIMAL(12,2) NOT NULL DEFAULT 350000.00,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `beds`;
CREATE TABLE `beds` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `room_id` INT NOT NULL,
    `bed_number` VARCHAR(20) NOT NULL,
    `status` ENUM('TERSEDIA', 'TERISI', 'PERBAIKAN') NOT NULL DEFAULT 'TERSEDIA',
    FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. MASTER TARIF TINDAKAN (MODUL 16)
DROP TABLE IF EXISTS `tariffs`;
CREATE TABLE `tariffs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `category` ENUM('KONSULTASI', 'TINDAKAN', 'LABORATORIUM', 'RADIOLOGI', 'ADMINISTRASI') NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `description` TEXT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. MASTER KATALOG ICD-10 (MODUL 16)
DROP TABLE IF EXISTS `icd_diagnoses`;
CREATE TABLE `icd_diagnoses` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `code` VARCHAR(20) NOT NULL UNIQUE,
    `description_id` VARCHAR(255) NOT NULL,
    `description_en` VARCHAR(255) NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. MASTER OBAT & ALKES (MODUL 16)
DROP TABLE IF EXISTS `medicines`;
CREATE TABLE `medicines` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `medicine_code` VARCHAR(30) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `category` VARCHAR(50) NOT NULL,
    `unit` VARCHAR(30) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `expiry_date` DATE NOT NULL,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. MASTER PEMERIKSAAN LABORATORIUM (MODUL 16)
DROP TABLE IF EXISTS `lab_tests`;
CREATE TABLE `lab_tests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `test_code` VARCHAR(30) NOT NULL UNIQUE,
    `test_name` VARCHAR(100) NOT NULL,
    `normal_range` VARCHAR(100) NULL,
    `unit` VARCHAR(30) NULL,
    `tariff` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. TABEL DATA PASIEN (MODUL 2 - PATIENT REGISTRATION)
DROP TABLE IF EXISTS `patients`;
CREATE TABLE `patients` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `no_rm` VARCHAR(20) NOT NULL UNIQUE,
    `nik` CHAR(16) NOT NULL UNIQUE,
    `full_name` VARCHAR(100) NOT NULL,
    `gender` ENUM('L', 'P') NOT NULL,
    `birth_date` DATE NOT NULL,
    `blood_type` ENUM('A', 'B', 'AB', 'O', '-') DEFAULT '-',
    `phone_number` VARCHAR(20) NOT NULL,
    `email` VARCHAR(100) NULL,
    `address` TEXT NOT NULL,
    `allergy_notes` TEXT NULL,
    `insurance_type` ENUM('UMUM', 'BPJS', 'ASURANSI') NOT NULL DEFAULT 'UMUM',
    `insurance_number` VARCHAR(50) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_patient_rm` (`no_rm`),
    INDEX `idx_patient_nik` (`nik`),
    INDEX `idx_patient_name` (`full_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. TABEL APPOINTMENT & PENJADWALAN KUNJUNGAN (MODUL 12)
DROP TABLE IF EXISTS `appointments`;
CREATE TABLE `appointments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_code` VARCHAR(30) NOT NULL UNIQUE,
    `queue_number` VARCHAR(20) NOT NULL,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `policlinic_id` INT NOT NULL,
    `appointment_date` DATE NOT NULL,
    `payment_type` ENUM('UMUM', 'BPJS', 'ASURANSI') NOT NULL DEFAULT 'UMUM',
    `insurance_number` VARCHAR(50) NULL,
    `referral_number` VARCHAR(50) NULL,
    `status` ENUM('SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'RESCHEDULED', 'CANCELLED') NOT NULL DEFAULT 'SCHEDULED',
    `notes` TEXT NULL,
    `cancel_reason` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`policlinic_id`) REFERENCES `policlinics`(`id`) ON DELETE RESTRICT,
    INDEX `idx_appt_date_status` (`appointment_date`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. TABEL REKAM MEDIS ELEKTRONIK (MODUL 3 - EMR)
DROP TABLE IF EXISTS `medical_records`;
CREATE TABLE `medical_records` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `appointment_id` INT NULL,
    `doctor_id` INT NULL,
    `vital_bp` VARCHAR(20) NULL,
    `vital_temp` DECIMAL(4,1) NULL,
    `vital_pulse` INT NULL,
    `vital_rr` INT NULL,
    `weight_kg` DECIMAL(5,2) NULL,
    `height_cm` DECIMAL(5,2) NULL,
    `bmi` DECIMAL(4,1) NULL,
    `nurse_notes` TEXT NULL,
    `nurse_id` INT NULL,
    `assessed_at` DATETIME NULL,
    `subjective` TEXT NOT NULL,
    `objective` TEXT NOT NULL,
    `assessment` TEXT NOT NULL,
    `plan` TEXT NOT NULL,
    `icd10_code` VARCHAR(255) NOT NULL,
    `diagnosis_name` VARCHAR(255) NULL,
    `icd9_code` VARCHAR(255) NULL,
    `clinical_notes` TEXT NULL,
    `status` ENUM('IN_PROGRESS', 'COMPLETED') NOT NULL DEFAULT 'COMPLETED',
    `examined_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`nurse_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_mr_patient` (`patient_id`),
    INDEX `idx_mr_appointment` (`appointment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. TABEL RIWAYAT ALERGI PASIEN (MODUL 3 - EMR)
DROP TABLE IF EXISTS `patient_allergies`;
CREATE TABLE `patient_allergies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `patient_id` INT NOT NULL,
    `allergen_name` VARCHAR(150) NOT NULL,
    `severity` ENUM('RINGAN', 'SEDANG', 'BERAT') NOT NULL DEFAULT 'SEDANG',
    `reaction` TEXT NULL,
    `recorded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. TABEL ORDER LABORATORIUM (MODUL 8)
DROP TABLE IF EXISTS `lab_orders`;
CREATE TABLE `lab_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(30) NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `appointment_id` INT NULL,
    `medical_record_id` INT NULL,
    `doctor_id` INT NULL,
    `analyst_id` INT NULL,
    `clinical_priority` ENUM('CITO', 'REGULER') NOT NULL DEFAULT 'REGULER',
    `status` ENUM('PENDING', 'PROCESS', 'VALID', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    `clinical_notes` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`analyst_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_lab_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. TABEL ITEM ORDER LABORATORIUM (MODUL 8)
DROP TABLE IF EXISTS `lab_order_items`;
CREATE TABLE `lab_order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lab_order_id` INT NOT NULL,
    `lab_test_id` INT NULL,
    `test_name` VARCHAR(100) NOT NULL,
    FOREIGN KEY (`lab_order_id`) REFERENCES `lab_orders`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`lab_test_id`) REFERENCES `lab_tests`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. TABEL HASIL UJI LABORATORIUM (MODUL 8)
DROP TABLE IF EXISTS `lab_results`;
CREATE TABLE `lab_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `lab_order_item_id` INT NOT NULL UNIQUE,
    `result_value` VARCHAR(100) NOT NULL,
    `normal_range` VARCHAR(100) NULL,
    `unit` VARCHAR(30) NULL,
    `result_flag` ENUM('NORMAL', 'HIGH', 'LOW', 'CRITICAL') NOT NULL DEFAULT 'NORMAL',
    `analyst_notes` TEXT NULL,
    `validated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`lab_order_item_id`) REFERENCES `lab_order_items`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 19. TABEL ORDER RADIOLOGI (MODUL 9)
DROP TABLE IF EXISTS `radiology_orders`;
CREATE TABLE `radiology_orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(30) NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `appointment_id` INT NULL,
    `medical_record_id` INT NULL,
    `doctor_id` INT NULL,
    `radiographer_id` INT NULL,
    `radiologist_id` INT NULL,
    `modality_type` VARCHAR(50) NOT NULL DEFAULT 'X-RAY',
    `clinical_indication` TEXT NOT NULL,
    `clinical_priority` ENUM('CITO', 'REGULER') NOT NULL DEFAULT 'REGULER',
    `status` ENUM('PENDING', 'PROCESS', 'VALID', 'CANCELLED') NOT NULL DEFAULT 'PENDING',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE RESTRICT,
    FOREIGN KEY (`appointment_id`) REFERENCES `appointments`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`medical_record_id`) REFERENCES `medical_records`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`radiographer_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`radiologist_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_rad_orders_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 20. TABEL HASIL & CITRA RADIOLOGI (MODUL 9)
DROP TABLE IF EXISTS `radiology_results`;
CREATE TABLE `radiology_results` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `radiology_order_id` INT NOT NULL UNIQUE,
    `image_path` VARCHAR(255) NOT NULL,
    `image_filename` VARCHAR(150) NOT NULL,
    `technical_notes` TEXT NULL,
    `expertise_findings` TEXT NULL,
    `expertise_conclusion` TEXT NULL,
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `validated_at` TIMESTAMP NULL,
    FOREIGN KEY (`radiology_order_id`) REFERENCES `radiology_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================================
-- SEED INITIAL DATA
-- ==========================================================

-- Konfigurasi Format Sistem
INSERT INTO `system_configurations` (`config_key`, `config_value`, `description`) VALUES
('hospital_name', 'Rumah Sakit Siloam Peduli', 'Nama resmi rumah sakit'),
('hospital_address', 'Jl. Siloam No. 1, Jakarta Barat', 'Alamat rumah sakit'),
('hospital_phone', '(021) 500-911', 'Nomor telepon layanan'),
('rm_prefix', 'RM', 'Prefix Nomor Rekam Medis'),
('queue_prefix_default', 'A', 'Prefix antrean umum');

-- Akun Pengguna Standar 7 Modul
INSERT INTO `users` (`id`, `username`, `password_hash`, `full_name`, `email`, `role`, `unit`, `is_active`) VALUES
(1, 'admin', '$2y$10$w81.7T2qTqH5i2kKqD43AODxO73Mv3.G7xT03Hq6gO/O6oGg1v4e.', 'Andi Pratama', 'admin@siloampeduli.co.id', 'admin', 'Unit TI & SIMRS', 1),
(2, 'registrasi', '$2y$10$A1B2C3D4E5F6G7H8I9J0K1L2M3N4O5P6Q7R8S9T0U1V2W3X4Y5Z6a', 'Nadia Putri', 'nadia@siloampeduli.co.id', 'registrasi', 'Registrasi & Admisi Pasien', 1),
(3, 'dokter', '$2y$12$SFEwPAE74KQs4NbXZJCpNO8tGvFRpRZeUQaUkp4Du59L9seanfALe', 'dr. Ahmad Fauzi, Sp.PD', 'dokter@siloampeduli.co.id', 'dokter', 'Poliklinik Penyakit Dalam', 1),
(4, 'perawat', '$2y$12$Fxyzyts6wXnLyiXULIOf9erf.9wqVMqqbXqTTLM8ZjJnxSAtQ/xo2', 'Ns. Siti Rahmawati, S.Kep', 'perawat@siloampeduli.co.id', 'perawat', 'Keperawatan Rawat Jalan', 1),
(5, 'analis', '$2y$12$Z07yGKkdbJQboeUMK6q08.f8QFWdwjf53BpMzYwG8kpOlkD3uekLO', 'Budi Santoso, A.Md.AK', 'analis@siloampeduli.co.id', 'analis_laboratorium', 'Laboratorium Sentral', 1),
(6, 'radiografer', '$2y$12$GzF2zedmT9MzSSPnNw1fEO0Audy43H9humFb.0DPwXbRnn1TKEVie', 'Doni Prasetyo, A.Md.Rad', 'radiografer@siloampeduli.co.id', 'radiografer', 'Instalasi Radiologi', 1),
(7, 'drad', '$2y$12$Y45MuGRATTbrNiuQGqCcq.MUF1GysnVAd7H85x3CKYcnGIWqqP7zS', 'dr. Hendra Wijaya, Sp.Rad', 'drad@siloampeduli.co.id', 'dokter_radiologi', 'Spesialis Radiologi', 1);

-- Master Poliklinik
INSERT INTO `policlinics` (`id`, `code`, `name`, `queue_prefix`, `description`, `is_active`) VALUES
(1, 'POL-INT', 'Poli Penyakit Dalam', 'A', 'Spesialis penyakit dalam, metabolik, dan geriatri', 1),
(2, 'POL-UMU', 'Poli Umum', 'B', 'Pelayanan kesehatan umum tingkat pertama', 1),
(3, 'POL-ANA', 'Poli Anak (Pediatri)', 'C', 'Pelayanan kesehatan anak & tumbuh kembang', 1),
(4, 'POL-OBG', 'Poli Kebidanan & Kandungan', 'D', 'Pemeriksaan kehamilan & reproduksi', 1),
(5, 'POL-GIG', 'Poli Gigi & Mulut', 'E', 'Konservasi gigi, bedah mulut, ortodonti', 1),
(6, 'POL-MAT', 'Poli Mata', 'F', 'Pemeriksaan refraksi, katarak, dan retina', 1);

-- Master Dokter
INSERT INTO `doctors` (`id`, `user_id`, `policlinic_id`, `sip_number`, `full_name`, `specialization`, `consultation_fee`, `is_active`) VALUES
(1, NULL, 1, 'SIP-446/2023/DKI', 'dr. Ahmad Fauzi, Sp.PD', 'Spesialis Penyakit Dalam', 200000.00, 1),
(2, NULL, 2, 'SIP-112/2022/DKI', 'dr. Siti Aminah', 'Dokter Umum', 100000.00, 1),
(3, NULL, 3, 'SIP-305/2024/DKI', 'dr. Hendra Wijaya, Sp.A', 'Spesialis Anak', 220000.00, 1),
(4, NULL, 4, 'SIP-782/2023/DKI', 'dr. Maya Kartika, Sp.OG', 'Spesialis Kebidanan & Kandungan', 250000.00, 1);

-- Jadwal Praktik Dokter & Kuota
INSERT INTO `doctor_schedules` (`doctor_id`, `day_name`, `start_time`, `end_time`, `quota`, `is_active`) VALUES
(1, 'Senin', '08:00:00', '13:00:00', 30, 1),
(1, 'Rabu', '08:00:00', '13:00:00', 30, 1),
(1, 'Jumat', '08:00:00', '13:00:00', 25, 1),
(2, 'Senin', '08:00:00', '15:00:00', 50, 1),
(2, 'Selasa', '08:00:00', '15:00:00', 50, 1),
(2, 'Rabu', '08:00:00', '15:00:00', 50, 1),
(3, 'Selasa', '09:00:00', '14:00:00', 25, 1),
(3, 'Kamis', '09:00:00', '14:00:00', 25, 1),
(4, 'Rabu', '10:00:00', '16:00:00', 20, 1),
(4, 'Sabtu', '09:00:00', '14:00:00', 20, 1);

-- Master Ruangan & Bed
INSERT INTO `rooms` (`id`, `room_code`, `room_name`, `class_type`, `daily_rate`, `is_active`) VALUES
(1, 'R-VIP-01', 'Ruang Mawar 101', 'VIP', 750000.00, 1),
(2, 'R-K1-01', 'Ruang Melati 201', 'KELAS_1', 450000.00, 1),
(3, 'R-K2-01', 'Ruang Anggrek 301', 'KELAS_2', 300000.00, 1),
(4, 'R-ICU-01', 'Ruang ICU Bed 1', 'ICU', 1200000.00, 1);

INSERT INTO `beds` (`room_id`, `bed_number`, `status`) VALUES
(1, 'BED-VIP-01', 'TERSEDIA'),
(2, 'BED-M1-A', 'TERSEDIA'),
(2, 'BED-M1-B', 'TERISI'),
(3, 'BED-A1-A', 'TERSEDIA'),
(3, 'BED-A1-B', 'TERSEDIA'),
(4, 'BED-ICU-01', 'TERSEDIA');

-- Master Tarif
INSERT INTO `tariffs` (`id`, `code`, `name`, `category`, `amount`, `description`, `is_active`) VALUES
(1, 'TND-001', 'Administrasi Rawat Jalan', 'ADMINISTRASI', 25000.00, 'Biaya pendaftaran dan kartu periksa', 1),
(2, 'TND-002', 'Konsultasi Dokter Spesialis', 'KONSULTASI', 200000.00, 'Pemeriksaan dokter spesialis', 1),
(3, 'TND-003', 'Konsultasi Dokter Umum', 'KONSULTASI', 100000.00, 'Pemeriksaan dokter umum', 1),
(4, 'TND-004', 'Pemeriksaan EKG 12 Lead', 'TINDAKAN', 120000.00, 'Rekam jantung standar', 1),
(5, 'TND-005', 'Nebulisasi / Terapi Uap', 'TINDAKAN', 85000.00, 'Inhalasi bronkodilator', 1);

-- Master Katalog ICD-10
INSERT INTO `icd_diagnoses` (`id`, `code`, `description_id`, `description_en`, `is_active`) VALUES
(1, 'I10', 'Hipertensi esensial (primer)', 'Essential (primary) hypertension', 1),
(2, 'E11', 'Diabetes melitus tidak tergantung insulin', 'Non-insulin-dependent diabetes mellitus', 1),
(3, 'J00', 'Nasofaringitis akut (common cold)', 'Acute nasopharyngitis (common cold)', 1),
(4, 'K29.7', 'Gastritis tidak spesifik', 'Gastritis, unspecified', 1),
(5, 'J06.9', 'Infeksi saluran pernapasan atas akut (ISPA)', 'Acute upper respiratory infection, unspecified', 1);

-- Master Obat
INSERT INTO `medicines` (`id`, `medicine_code`, `name`, `category`, `unit`, `stock`, `unit_price`, `expiry_date`, `is_active`) VALUES
(1, 'OBT-001', 'Paracetamol 500mg', 'Tablet Analgesik', 'Tablet', 500, 3500.00, '2028-12-31', 1),
(2, 'OBT-002', 'Amoxicillin 500mg', 'Antibiotik Oral', 'Kapsul', 300, 6000.00, '2027-10-15', 1),
(3, 'OBT-003', 'Amlodipine 10mg', 'Antihipertensi', 'Tablet', 250, 4500.00, '2028-05-20', 1),
(4, 'OBT-004', 'Omeprazole 20mg', 'Antasida / PPI', 'Kapsul', 350, 7500.00, '2027-12-01', 1);

-- Master Pemeriksaan Laboratorium
INSERT INTO `lab_tests` (`id`, `test_code`, `test_name`, `normal_range`, `unit`, `tariff`, `is_active`) VALUES
(1, 'LAB-001', 'Hemoglobin (Hb)', '13.0 - 17.0 (L), 12.0 - 15.0 (P)', 'g/dL', 45000.00, 1),
(2, 'LAB-002', 'Glukosa Darah Sewaktu (GDS)', '70 - 140', 'mg/dL', 35000.00, 1),
(3, 'LAB-003', 'Darah Lengkap Otomatis (CBC)', 'Lengkap', 'Panel', 120000.00, 1),
(4, 'LAB-004', 'Kolesterol Total', '< 200', 'mg/dL', 55000.00, 1);

-- Data Pasien Awal
INSERT INTO `patients` (`id`, `no_rm`, `nik`, `full_name`, `gender`, `birth_date`, `blood_type`, `phone_number`, `email`, `address`, `allergy_notes`, `insurance_type`, `insurance_number`) VALUES
(1, 'RM-202611-0001', '3171010101900001', 'Budi Santoso', 'L', '1990-01-01', 'O', '081234567890', 'budi@gmail.com', 'Jl. Kebon Jeruk No. 12, Jakarta Barat', 'Alergi Penisilin, Seafood', 'UMUM', NULL),
(2, 'RM-202611-0002', '3171020202850002', 'Siti Rahmawati', 'P', '1985-05-14', 'A', '081398765432', 'siti.rahma@yahoo.com', 'Jl. Anggrek Merah No. 5, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567891'),
(3, 'RM-202611-0003', '3171030303780003', 'Joko Susilo', 'L', '1978-11-20', 'B', '081512349876', 'joko_susilo@gmail.com', 'Komplek Permata Indah Blok C2, Tangerang', 'Alergi Aspirin', 'ASURANSI', 'POLIS-PRU-98213');

-- Data Appointment Awal
INSERT INTO `appointments` (`id`, `booking_code`, `queue_number`, `patient_id`, `doctor_id`, `policlinic_id`, `appointment_date`, `payment_type`, `insurance_number`, `referral_number`, `status`, `notes`) VALUES
(1, 'BKG-20261107-001', 'A-001', 1, 1, 1, CURRENT_DATE(), 'UMUM', NULL, NULL, 'SCHEDULED', 'Konsultasi keluhan tengkuk berat'),
(2, 'BKG-20261107-002', 'A-002', 2, 1, 1, CURRENT_DATE(), 'BPJS', '0001234567891', 'REF-FKTP-2026-001', 'SCHEDULED', 'Kontrol rutin hipertensi');

-- ==========================================================
-- FASE 3: MODUL 4 (CPOE), MODUL 7 (IGD), MODUL 11 (BILLING & INSURANCE)
-- ==========================================================

-- Modul 4: CPOE Orders & Items
CREATE TABLE IF NOT EXISTS `cpoe_orders` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `order_number` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `doctor_id` INT NOT NULL,
    `visit_type` ENUM('RAWAT_JALAN', 'IGD', 'RAWAT_INAP') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'RAWAT_JALAN',
    `reference_id` BIGINT NULL,
    `order_type` ENUM('RESEP_OBAT', 'TINDAKAN_MEDIS', 'LABORATORIUM', 'RADIOLOGI') COLLATE utf8mb4_unicode_ci NOT NULL,
    `priority` ENUM('REGULER', 'CITO') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'REGULER',
    `status` ENUM('PENDING', 'PROCESS', 'COMPLETED', 'CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
    `clinical_notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` TIMESTAMP NULL,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cpoe_order_items` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `cpoe_order_id` BIGINT NOT NULL,
    `item_type` ENUM('MEDICINE', 'PROCEDURE', 'LAB_TEST', 'RADIOLOGY') COLLATE utf8mb4_unicode_ci NOT NULL,
    `item_id` INT NULL,
    `item_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `dosage` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
    `frequency` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
    `route` VARCHAR(50) COLLATE utf8mb4_unicode_ci NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `unit_price` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `instructions` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `status` ENUM('PENDING', 'DISPENSED', 'EXECUTED', 'CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'PENDING',
    FOREIGN KEY (`cpoe_order_id`) REFERENCES `cpoe_orders`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modul 7: IGD Emergency Department
CREATE TABLE IF NOT EXISTS `emergency_visits` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `visit_number` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `patient_id` INT NULL,
    `anonymous_name` VARCHAR(150) COLLATE utf8mb4_unicode_ci NULL,
    `arrival_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `transportation` ENUM('AMBULANS', 'KENDARAAN_PRIBADI', 'JALAN_KAKI', 'LAINNYA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'KENDARAAN_PRIBADI',
    `accompanied_by` VARCHAR(100) COLLATE utf8mb4_unicode_ci NULL,
    `triage_category` ENUM('MERAH', 'KUNING', 'HIJAU', 'HITAM') COLLATE utf8mb4_unicode_ci NULL,
    `status` ENUM('TRIAGE', 'EXAMINATION', 'OBSERVATION', 'DISPOSED', 'CANCELLED') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TRIAGE',
    `disposition_type` ENUM('RAWAT_INAP', 'ICU', 'RUJUK', 'PULANG', 'MENINGGAL') COLLATE utf8mb4_unicode_ci NULL,
    `disposition_notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `disposition_time` TIMESTAMP NULL,
    `doctor_id` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `emergency_triages` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `emergency_visit_id` BIGINT NOT NULL UNIQUE,
    `officer_id` INT NOT NULL,
    `triage_level` ENUM('MERAH', 'KUNING', 'HIJAU', 'HITAM') COLLATE utf8mb4_unicode_ci NOT NULL,
    `esi_level` INT NOT NULL DEFAULT 3,
    `chief_complaint` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `airway_assessment` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Paten',
    `breathing_assessment` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Spontan',
    `circulation_assessment` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Nadi Teraba Kuat',
    `gcs_eye` INT NOT NULL DEFAULT 4,
    `gcs_verbal` INT NOT NULL DEFAULT 5,
    `gcs_motor` INT NOT NULL DEFAULT 6,
    `gcs_total` INT NOT NULL DEFAULT 15,
    `systolic_bp` INT NOT NULL DEFAULT 120,
    `diastolic_bp` INT NOT NULL DEFAULT 80,
    `pulse_rate` INT NOT NULL DEFAULT 80,
    `respiratory_rate` INT NOT NULL DEFAULT 20,
    `temperature` DECIMAL(4,1) NOT NULL DEFAULT 36.5,
    `spo2` INT NOT NULL DEFAULT 98,
    `triage_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`emergency_visit_id`) REFERENCES `emergency_visits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`officer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `emergency_assessments` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `emergency_visit_id` BIGINT NOT NULL UNIQUE,
    `doctor_id` INT NOT NULL,
    `primary_airway` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `primary_breathing` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `primary_circulation` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `primary_disability` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `primary_exposure` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `secondary_survey` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `icd10_code` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `diagnosis_name` VARCHAR(255) COLLATE utf8mb4_unicode_ci NOT NULL,
    `therapy_plan` TEXT COLLATE utf8mb4_unicode_ci NOT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY `idx_icd10` (`icd10_code`),
    FOREIGN KEY (`emergency_visit_id`) REFERENCES `emergency_visits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`doctor_id`) REFERENCES `doctors`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `emergency_monitorings` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `emergency_visit_id` BIGINT NOT NULL,
    `recorded_by` INT NOT NULL,
    `monitoring_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `systolic_bp` INT NOT NULL,
    `diastolic_bp` INT NOT NULL,
    `pulse_rate` INT NOT NULL,
    `respiratory_rate` INT NOT NULL,
    `temperature` DECIMAL(4,1) NOT NULL,
    `spo2` INT NOT NULL,
    `gcs_total` INT NOT NULL,
    `notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `is_abnormal` TINYINT(1) NOT NULL DEFAULT 0,
    FOREIGN KEY (`emergency_visit_id`) REFERENCES `emergency_visits`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Modul 11: Billing & Insurance
CREATE TABLE IF NOT EXISTS `insurance_claims` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `claim_number` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `invoice_id` INT NOT NULL UNIQUE,
    `patient_id` INT NOT NULL,
    `insurance_type` ENUM('BPJS', 'ASURANSI_SWASTA') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BPJS',
    `policy_number` VARCHAR(100) COLLATE utf8mb4_unicode_ci NOT NULL,
    `diagnosis_code` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL,
    `claimed_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `approved_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('SUBMITTED', 'APPROVED', 'REJECTED', 'PENDING') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'SUBMITTED',
    `submission_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `adjudication_date` TIMESTAMP NULL,
    `notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    `created_by` INT NOT NULL,
    KEY `idx_claim_diag` (`diagnosis_code`),
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`patient_id`) REFERENCES `patients`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `receipt_number` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
    `invoice_id` INT NOT NULL,
    `amount_paid` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_method` ENUM('TUNAI', 'DEBIT', 'KREDIT', 'QRIS', 'TRANSFER', 'ASURANSI_FULL') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'TUNAI',
    `cashier_id` INT NOT NULL,
    `change_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `payment_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `notes` TEXT COLLATE utf8mb4_unicode_ci NULL,
    FOREIGN KEY (`invoice_id`) REFERENCES `invoices`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`cashier_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
