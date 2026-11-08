-- =====================================================================
-- SIMRS Siloam Peduli - Standalone Supabase PostgreSQL Schema & Seeder
-- Generated: 2026-11-07 13:53:47
-- Compatibility: PostgreSQL 15, 16, 17 (Supabase Managed Cloud)
-- =====================================================================

-- 1. EXTENSIONS
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
CREATE EXTENSION IF NOT EXISTS "pgcrypto";

-- 2. TABLE DEFINITIONS (DDL)
CREATE TABLE IF NOT EXISTS public."system_configurations" (
"config_key" VARCHAR(50) NOT NULL,
"config_value" TEXT NOT NULL,
"description" VARCHAR(255),
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("config_key")
);

CREATE TABLE IF NOT EXISTS public."users" (
"id" SERIAL,
"username" VARCHAR(50) NOT NULL,
"password_hash" VARCHAR(255) NOT NULL,
"full_name" VARCHAR(100) NOT NULL,
"email" VARCHAR(100),
"role" VARCHAR(50) NOT NULL DEFAULT 'registrasi',
"unit" VARCHAR(100),
"is_active" BOOLEAN DEFAULT true,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."policlinics" (
"id" SERIAL,
"code" VARCHAR(20) NOT NULL,
"name" VARCHAR(100) NOT NULL,
"queue_prefix" VARCHAR(5) NOT NULL DEFAULT 'A',
"description" TEXT,
"is_active" BOOLEAN DEFAULT true,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."tariffs" (
"id" SERIAL,
"code" VARCHAR(20) NOT NULL,
"name" VARCHAR(100) NOT NULL,
"category" VARCHAR(50) NOT NULL,
"amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"description" TEXT,
"is_active" BOOLEAN DEFAULT true,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."icd_diagnoses" (
"id" SERIAL,
"code" VARCHAR(20) NOT NULL,
"description_id" VARCHAR(255) NOT NULL,
"description_en" VARCHAR(255),
"is_active" BOOLEAN DEFAULT true,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."medicines" (
"id" SERIAL,
"medicine_code" VARCHAR(30) NOT NULL,
"name" VARCHAR(100) NOT NULL,
"category" VARCHAR(50) NOT NULL,
"unit" VARCHAR(30) NOT NULL,
"stock" INTEGER NOT NULL DEFAULT 0,
"unit_price" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"expiry_date" DATE NOT NULL,
"is_active" BOOLEAN DEFAULT true,
"location" VARCHAR(100) DEFAULT 'Gudang Utama',
"min_stock" INTEGER DEFAULT 10,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."rooms" (
"id" SERIAL,
"room_code" VARCHAR(20) NOT NULL,
"room_name" VARCHAR(100) NOT NULL,
"class_type" VARCHAR(50) NOT NULL DEFAULT 'KELAS_1',
"daily_rate" NUMERIC(15,2) NOT NULL DEFAULT 350000.00,
"is_active" BOOLEAN DEFAULT true,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."beds" (
"id" SERIAL,
"room_id" INTEGER NOT NULL,
"bed_number" VARCHAR(20) NOT NULL,
"status" VARCHAR(50) NOT NULL DEFAULT 'TERSEDIA',
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."doctors" (
"id" SERIAL,
"user_id" INTEGER,
"policlinic_id" INTEGER NOT NULL,
"sip_number" VARCHAR(50) NOT NULL,
"full_name" VARCHAR(100) NOT NULL,
"specialization" VARCHAR(100) NOT NULL,
"consultation_fee" NUMERIC(15,2) NOT NULL DEFAULT 150000.00,
"is_active" BOOLEAN DEFAULT true,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."doctor_schedules" (
"id" SERIAL,
"doctor_id" INTEGER NOT NULL,
"day_name" VARCHAR(50) NOT NULL,
"start_time" TIME WITHOUT TIME ZONE NOT NULL,
"end_time" TIME WITHOUT TIME ZONE NOT NULL,
"quota" INTEGER NOT NULL DEFAULT 30,
"is_active" BOOLEAN DEFAULT true,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."patients" (
"id" SERIAL,
"no_rm" VARCHAR(20) NOT NULL,
"nik" TEXT NOT NULL,
"full_name" VARCHAR(100) NOT NULL,
"gender" VARCHAR(50) NOT NULL,
"birth_date" DATE NOT NULL,
"blood_type" VARCHAR(50) DEFAULT '-',
"phone_number" VARCHAR(20) NOT NULL,
"email" VARCHAR(100),
"address" TEXT NOT NULL,
"allergy_notes" TEXT,
"insurance_type" VARCHAR(50) NOT NULL DEFAULT 'UMUM',
"insurance_number" VARCHAR(50),
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."patient_allergies" (
"id" SERIAL,
"patient_id" INTEGER NOT NULL,
"allergen_name" VARCHAR(150) NOT NULL,
"severity" VARCHAR(50) NOT NULL DEFAULT 'SEDANG',
"reaction" TEXT,
"recorded_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."appointments" (
"id" SERIAL,
"booking_code" VARCHAR(30) NOT NULL,
"queue_number" VARCHAR(20) NOT NULL,
"patient_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"policlinic_id" INTEGER NOT NULL,
"appointment_date" DATE NOT NULL,
"payment_type" VARCHAR(50) NOT NULL DEFAULT 'UMUM',
"insurance_number" VARCHAR(50),
"referral_number" VARCHAR(50),
"status" VARCHAR(50) NOT NULL DEFAULT 'SCHEDULED',
"notes" TEXT,
"cancel_reason" TEXT,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."visits" (
"id" SERIAL,
"booking_code" VARCHAR(30) NOT NULL,
"queue_number" VARCHAR(20) NOT NULL,
"patient_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"policlinic_id" INTEGER NOT NULL,
"visit_date" DATE NOT NULL,
"payment_type" VARCHAR(50) NOT NULL,
"insurance_card_no" VARCHAR(50),
"referral_no" VARCHAR(50),
"status" VARCHAR(50) NOT NULL DEFAULT 'TERDAFTAR',
"notes" TEXT,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."visit_treatments" (
"id" SERIAL,
"visit_id" INTEGER NOT NULL,
"tariff_id" INTEGER NOT NULL,
"quantity" INTEGER NOT NULL DEFAULT 1,
"amount" NUMERIC(15,2) NOT NULL,
"notes" VARCHAR(255),
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."medical_records" (
"id" SERIAL,
"visit_id" INTEGER,
"patient_id" INTEGER NOT NULL,
"appointment_id" INTEGER,
"doctor_id" INTEGER NOT NULL,
"vital_bp" VARCHAR(20),
"vital_temp" NUMERIC(15,2),
"vital_pulse" INTEGER,
"vital_rr" INTEGER,
"weight_kg" NUMERIC(15,2),
"height_cm" NUMERIC(15,2),
"bmi" NUMERIC(15,2),
"nurse_notes" TEXT,
"nurse_id" INTEGER,
"assessed_at" TIMESTAMP,
"subjective" TEXT,
"objective" TEXT,
"assessment" TEXT,
"plan" TEXT,
"clinical_notes" TEXT,
"icd10_code" VARCHAR(255),
"diagnosis_name" VARCHAR(255),
"icd9_code" VARCHAR(255),
"examined_at" TIMESTAMP,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"status" VARCHAR(50) NOT NULL DEFAULT 'COMPLETED',
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."prescriptions" (
"id" SERIAL,
"prescription_no" VARCHAR(30) NOT NULL,
"medical_record_id" INTEGER NOT NULL,
"visit_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"status" VARCHAR(50) NOT NULL DEFAULT 'MENUNGGU',
"pharmacist_id" INTEGER,
"pharmacist_notes" TEXT,
"dispensed_at" TIMESTAMP,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."prescription_items" (
"id" SERIAL,
"prescription_id" INTEGER NOT NULL,
"medicine_id" INTEGER NOT NULL,
"quantity" INTEGER NOT NULL,
"unit_price" NUMERIC(15,2) NOT NULL,
"subtotal" NUMERIC(15,2) NOT NULL,
"instructions" VARCHAR(255) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."lab_tests" (
"id" SERIAL,
"test_code" VARCHAR(30) NOT NULL,
"test_name" VARCHAR(100) NOT NULL,
"normal_range" VARCHAR(100),
"unit" VARCHAR(30),
"tariff" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"is_active" BOOLEAN DEFAULT true,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."lab_orders" (
"id" SERIAL,
"order_number" VARCHAR(30) NOT NULL,
"patient_id" INTEGER NOT NULL,
"appointment_id" INTEGER,
"medical_record_id" INTEGER,
"doctor_id" INTEGER,
"analyst_id" INTEGER,
"clinical_priority" VARCHAR(50) NOT NULL DEFAULT 'REGULER',
"status" VARCHAR(50) NOT NULL DEFAULT 'PENDING',
"clinical_notes" TEXT,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"completed_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."lab_order_items" (
"id" SERIAL,
"lab_order_id" INTEGER NOT NULL,
"lab_test_id" INTEGER,
"test_name" VARCHAR(100) NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."lab_results" (
"id" SERIAL,
"lab_order_item_id" INTEGER NOT NULL,
"result_value" VARCHAR(100) NOT NULL,
"normal_range" VARCHAR(100),
"unit" VARCHAR(30),
"result_flag" VARCHAR(50) NOT NULL DEFAULT 'NORMAL',
"analyst_notes" TEXT,
"validated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."radiology_orders" (
"id" SERIAL,
"order_number" VARCHAR(30) NOT NULL,
"patient_id" INTEGER NOT NULL,
"appointment_id" INTEGER,
"medical_record_id" INTEGER,
"doctor_id" INTEGER,
"radiographer_id" INTEGER,
"radiologist_id" INTEGER,
"modality_type" VARCHAR(50) NOT NULL DEFAULT 'X-RAY',
"clinical_indication" TEXT NOT NULL,
"clinical_priority" VARCHAR(50) NOT NULL DEFAULT 'REGULER',
"status" VARCHAR(50) NOT NULL DEFAULT 'PENDING',
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"completed_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."radiology_results" (
"id" SERIAL,
"radiology_order_id" INTEGER NOT NULL,
"image_path" VARCHAR(255) NOT NULL,
"image_filename" VARCHAR(150) NOT NULL,
"technical_notes" TEXT,
"expertise_findings" TEXT,
"expertise_conclusion" TEXT,
"uploaded_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"validated_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."cpoe_orders" (
"id" BIGSERIAL,
"order_number" VARCHAR(50) NOT NULL,
"patient_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"visit_type" VARCHAR(50) NOT NULL DEFAULT 'RAWAT_JALAN',
"reference_id" BIGINT,
"order_type" VARCHAR(50) NOT NULL,
"priority" VARCHAR(50) NOT NULL DEFAULT 'REGULER',
"status" VARCHAR(50) NOT NULL DEFAULT 'PENDING',
"clinical_notes" TEXT,
"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
"completed_at" TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."cpoe_order_items" (
"id" BIGSERIAL,
"cpoe_order_id" BIGINT NOT NULL,
"item_type" VARCHAR(50) NOT NULL,
"item_id" INTEGER,
"item_name" VARCHAR(255) NOT NULL,
"dosage" VARCHAR(100),
"frequency" VARCHAR(100),
"route" VARCHAR(50),
"quantity" INTEGER NOT NULL DEFAULT 1,
"unit_price" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"subtotal" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"instructions" TEXT,
"status" VARCHAR(50) NOT NULL DEFAULT 'PENDING',
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."emergency_visits" (
"id" BIGSERIAL,
"visit_number" VARCHAR(50) NOT NULL,
"patient_id" INTEGER,
"anonymous_name" VARCHAR(150),
"arrival_time" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
"transportation" VARCHAR(50) NOT NULL DEFAULT 'KENDARAAN_PRIBADI',
"accompanied_by" VARCHAR(100),
"triage_category" VARCHAR(50),
"status" VARCHAR(50) NOT NULL DEFAULT 'TRIAGE',
"disposition_type" VARCHAR(50),
"disposition_notes" TEXT,
"disposition_time" TIMESTAMP,
"doctor_id" INTEGER,
"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."emergency_triages" (
"id" BIGSERIAL,
"emergency_visit_id" BIGINT NOT NULL,
"officer_id" INTEGER NOT NULL,
"triage_level" VARCHAR(50) NOT NULL,
"esi_level" INTEGER NOT NULL DEFAULT 3,
"chief_complaint" TEXT NOT NULL,
"airway_assessment" VARCHAR(100) NOT NULL DEFAULT 'Paten',
"breathing_assessment" VARCHAR(100) NOT NULL DEFAULT 'Spontan',
"circulation_assessment" VARCHAR(100) NOT NULL DEFAULT 'Nadi Teraba Kuat',
"gcs_eye" INTEGER NOT NULL DEFAULT 4,
"gcs_verbal" INTEGER NOT NULL DEFAULT 5,
"gcs_motor" INTEGER NOT NULL DEFAULT 6,
"gcs_total" INTEGER NOT NULL DEFAULT 15,
"systolic_bp" INTEGER NOT NULL DEFAULT 120,
"diastolic_bp" INTEGER NOT NULL DEFAULT 80,
"pulse_rate" INTEGER NOT NULL DEFAULT 80,
"respiratory_rate" INTEGER NOT NULL DEFAULT 20,
"temperature" NUMERIC(15,2) NOT NULL DEFAULT 36.5,
"spo2" INTEGER NOT NULL DEFAULT 98,
"triage_time" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."emergency_assessments" (
"id" BIGSERIAL,
"emergency_visit_id" BIGINT NOT NULL,
"doctor_id" INTEGER NOT NULL,
"primary_airway" TEXT NOT NULL,
"primary_breathing" TEXT NOT NULL,
"primary_circulation" TEXT NOT NULL,
"primary_disability" TEXT NOT NULL,
"primary_exposure" TEXT NOT NULL,
"secondary_survey" TEXT,
"icd10_code" VARCHAR(50) NOT NULL,
"diagnosis_name" VARCHAR(255) NOT NULL,
"therapy_plan" TEXT NOT NULL,
"created_at" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."emergency_monitorings" (
"id" BIGSERIAL,
"emergency_visit_id" BIGINT NOT NULL,
"recorded_by" INTEGER NOT NULL,
"monitoring_time" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
"systolic_bp" INTEGER NOT NULL,
"diastolic_bp" INTEGER NOT NULL,
"pulse_rate" INTEGER NOT NULL,
"respiratory_rate" INTEGER NOT NULL,
"temperature" NUMERIC(15,2) NOT NULL,
"spo2" INTEGER NOT NULL,
"gcs_total" INTEGER NOT NULL,
"notes" TEXT,
"is_abnormal" BOOLEAN NOT NULL DEFAULT false,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."inpatient_admissions" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"admission_number" VARCHAR(30) NOT NULL,
"patient_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"room_id" INTEGER NOT NULL,
"bed_id" INTEGER NOT NULL,
"admission_date" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"discharge_date" TIMESTAMPTZ,
"care_class" VARCHAR(20) NOT NULL,
"insurance_type" VARCHAR(20) DEFAULT 'UMUM',
"admission_reason" TEXT,
"source_type" VARCHAR(20) DEFAULT 'POLI',
"status" VARCHAR(30) DEFAULT 'DIRAWAT',
"created_by" INTEGER,
"created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."inpatient_transfers" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"admission_id" BIGINT NOT NULL,
"patient_id" INTEGER NOT NULL,
"from_room_id" INTEGER NOT NULL,
"from_bed_id" INTEGER NOT NULL,
"to_room_id" INTEGER NOT NULL,
"to_bed_id" INTEGER NOT NULL,
"transfer_reason" TEXT NOT NULL,
"transferred_by" INTEGER,
"transferred_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"notes" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."inpatient_discharge_summaries" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"admission_id" BIGINT NOT NULL,
"patient_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"admission_diagnosis_icd10" VARCHAR(10),
"discharge_diagnosis_icd10" VARCHAR(10) NOT NULL,
"procedures_icd9cm" VARCHAR(255),
"hospital_course" TEXT NOT NULL,
"condition_at_discharge" VARCHAR(30) DEFAULT 'MEMBAIK',
"discharge_instructions" TEXT,
"take_home_medications" TEXT,
"control_date" DATE,
"finalized_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."nursing_assessments" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"patient_id" INTEGER NOT NULL,
"encounter_type" VARCHAR(20) DEFAULT 'INPATIENT',
"encounter_id" BIGINT NOT NULL,
"nurse_id" INTEGER,
"chief_complaint" TEXT NOT NULL,
"general_condition" TEXT,
"consciousness" VARCHAR(30) DEFAULT 'COMPOS_MENTIS',
"fall_risk_score" INTEGER DEFAULT 0,
"fall_risk_category" VARCHAR(20) DEFAULT 'RENDAH',
"pain_score" INTEGER DEFAULT 0,
"functional_status" VARCHAR(30) DEFAULT 'MANDIRI',
"created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."nursing_vital_signs" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"patient_id" INTEGER NOT NULL,
"encounter_type" VARCHAR(20) DEFAULT 'INPATIENT',
"encounter_id" BIGINT NOT NULL,
"nurse_id" INTEGER,
"systolic_bp" INTEGER NOT NULL,
"diastolic_bp" INTEGER NOT NULL,
"pulse_rate" INTEGER NOT NULL,
"respiratory_rate" INTEGER NOT NULL,
"temperature" NUMERIC(4,1) NOT NULL,
"spo2" INTEGER NOT NULL,
"recorded_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"notes" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."nursing_notes" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"patient_id" INTEGER NOT NULL,
"encounter_type" VARCHAR(20) DEFAULT 'INPATIENT',
"encounter_id" BIGINT NOT NULL,
"nurse_id" INTEGER,
"shift" VARCHAR(10) DEFAULT 'PAGI',
"subjective" TEXT NOT NULL,
"objective" TEXT NOT NULL,
"nursing_diagnosis" TEXT NOT NULL,
"interventions" TEXT NOT NULL,
"evaluation" TEXT NOT NULL,
"recorded_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"is_finalized" BOOLEAN DEFAULT false,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."clinical_escalations" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"patient_id" INTEGER NOT NULL,
"encounter_type" VARCHAR(20) DEFAULT 'INPATIENT',
"encounter_id" BIGINT NOT NULL,
"nurse_id" INTEGER,
"doctor_id" INTEGER,
"escalation_reason" TEXT NOT NULL,
"vital_signs_summary" TEXT NOT NULL,
"doctor_instruction" TEXT,
"escalated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"status" VARCHAR(20) DEFAULT 'REPORTED',
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."medication_administrations" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"patient_id" INTEGER NOT NULL,
"admission_id" BIGINT,
"nurse_id" INTEGER,
"medicine_name" VARCHAR(150) NOT NULL,
"dosage" VARCHAR(50) NOT NULL,
"route" VARCHAR(30) NOT NULL,
"planned_time" TIMESTAMPTZ NOT NULL,
"given_time" TIMESTAMPTZ,
"patient_response" TEXT,
"status" VARCHAR(20) DEFAULT 'GIVEN',
"notes" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."outpatient_controls" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"visit_id" BIGINT,
"patient_id" INTEGER NOT NULL,
"doctor_id" INTEGER NOT NULL,
"control_date" DATE NOT NULL,
"notes" TEXT,
"status" VARCHAR(20) DEFAULT 'SCHEDULED',
"created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."purchase_requests" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"pr_number" VARCHAR(30) NOT NULL,
"item_id" INTEGER,
"item_name" VARCHAR(150) NOT NULL,
"quantity" INTEGER NOT NULL,
"unit" VARCHAR(30) NOT NULL DEFAULT 'Pcs',
"notes" TEXT,
"requested_by" INTEGER,
"status" VARCHAR(20) DEFAULT 'PENDING',
"created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."inventory_transactions" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"transaction_number" VARCHAR(35) NOT NULL,
"item_id" INTEGER,
"transaction_type" VARCHAR(20) NOT NULL,
"document_number" VARCHAR(50),
"quantity" INTEGER NOT NULL,
"from_location" VARCHAR(100),
"to_location" VARCHAR(100),
"expiry_date" DATE,
"notes" TEXT,
"performed_by" INTEGER,
"created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."pharmacy_dispensings" (
"id" BIGINT GENERATED ALWAYS AS IDENTITY NOT NULL,
"cpoe_order_id" BIGINT,
"prescription_number" VARCHAR(35),
"patient_id" INTEGER,
"pharmacist_id" INTEGER,
"dispensed_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
"total_amount" NUMERIC(14,2) DEFAULT 0,
"notes" TEXT,
"created_at" TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."invoices" (
"id" SERIAL,
"invoice_number" VARCHAR(30) NOT NULL,
"visit_id" INTEGER,
"appointment_id" INTEGER,
"emergency_visit_id" BIGINT,
"doctor_id" INTEGER,
"visit_type" VARCHAR(50) NOT NULL DEFAULT 'RAWAT_JALAN',
"patient_type" VARCHAR(50) NOT NULL DEFAULT 'UMUM',
"insurance_number" VARCHAR(100),
"total_gross" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"insurance_coverage" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"patient_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"status" VARCHAR(50) NOT NULL DEFAULT 'UNPAID',
"created_by" INTEGER,
"patient_id" INTEGER NOT NULL,
"total_consultation" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"total_treatment" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"total_medicine" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"total_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"paid_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"change_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"payment_method" VARCHAR(50) NOT NULL DEFAULT 'TUNAI',
"payment_status" VARCHAR(50) NOT NULL DEFAULT 'BELUM_BAYAR',
"cashier_id" INTEGER,
"paid_at" TIMESTAMP,
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
"updated_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."invoice_items" (
"id" SERIAL,
"invoice_id" INTEGER NOT NULL,
"item_type" VARCHAR(50) NOT NULL,
"item_name" VARCHAR(150) NOT NULL,
"quantity" INTEGER NOT NULL DEFAULT 1,
"unit_price" NUMERIC(15,2) NOT NULL,
"subtotal" NUMERIC(15,2) NOT NULL,
"reference_table" VARCHAR(100),
"reference_id" BIGINT,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."payments" (
"id" SERIAL,
"receipt_number" VARCHAR(50) NOT NULL,
"invoice_id" INTEGER NOT NULL,
"amount_paid" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"payment_method" VARCHAR(50) NOT NULL DEFAULT 'TUNAI',
"cashier_id" INTEGER NOT NULL,
"change_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"payment_time" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
"notes" TEXT,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."insurance_claims" (
"id" SERIAL,
"claim_number" VARCHAR(50) NOT NULL,
"invoice_id" INTEGER NOT NULL,
"patient_id" INTEGER NOT NULL,
"insurance_type" VARCHAR(50) NOT NULL DEFAULT 'BPJS',
"policy_number" VARCHAR(100) NOT NULL,
"diagnosis_code" VARCHAR(50) NOT NULL,
"claimed_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"approved_amount" NUMERIC(15,2) NOT NULL DEFAULT 0.00,
"status" VARCHAR(50) NOT NULL DEFAULT 'SUBMITTED',
"submission_date" TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
"adjudication_date" TIMESTAMP,
"notes" TEXT,
"created_by" INTEGER NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."sessions" (
"id" VARCHAR(128) NOT NULL,
"access_timestamp" BIGINT NOT NULL,
"data" TEXT NOT NULL,
    PRIMARY KEY ("id")
);

CREATE TABLE IF NOT EXISTS public."audit_logs" (
"id" BIGSERIAL,
"user_id" INTEGER,
"action" VARCHAR(100) NOT NULL,
"module" VARCHAR(50) NOT NULL,
"details" TEXT,
"ip_address" VARCHAR(45),
"created_at" TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY ("id")
);

-- 3. PERFORMANCE INDEXES
CREATE INDEX IF NOT EXISTS idx_appointments_date ON public.appointments USING btree (appointment_date);
CREATE INDEX IF NOT EXISTS idx_appointments_doctor ON public.appointments USING btree (doctor_id);
CREATE INDEX IF NOT EXISTS idx_appointments_patient ON public.appointments USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_appointments_poli ON public.appointments USING btree (policlinic_id);
CREATE INDEX IF NOT EXISTS idx_appointments_status ON public.appointments USING btree (status);
CREATE INDEX IF NOT EXISTS idx_audit_logs_created ON public.audit_logs USING btree (created_at);
CREATE INDEX IF NOT EXISTS idx_audit_logs_module ON public.audit_logs USING btree (module);
CREATE INDEX IF NOT EXISTS idx_audit_logs_user ON public.audit_logs USING btree (user_id);
CREATE INDEX IF NOT EXISTS idx_beds_room ON public.beds USING btree (room_id);
CREATE INDEX IF NOT EXISTS idx_beds_status ON public.beds USING btree (status);
CREATE INDEX IF NOT EXISTS idx_clinical_escalations_encounter ON public.clinical_escalations USING btree (encounter_type, encounter_id);
CREATE INDEX IF NOT EXISTS idx_cpoe_orders_doctor ON public.cpoe_orders USING btree (doctor_id);
CREATE INDEX IF NOT EXISTS idx_cpoe_orders_patient ON public.cpoe_orders USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_cpoe_orders_priority ON public.cpoe_orders USING btree (priority);
CREATE INDEX IF NOT EXISTS idx_cpoe_orders_status ON public.cpoe_orders USING btree (status);
CREATE INDEX IF NOT EXISTS idx_cpoe_orders_type ON public.cpoe_orders USING btree (order_type);
CREATE UNIQUE INDEX IF NOT EXISTS uq_emergency_assessments_visit ON public.emergency_assessments USING btree (emergency_visit_id);
CREATE UNIQUE INDEX IF NOT EXISTS uq_emergency_triages_visit ON public.emergency_triages USING btree (emergency_visit_id);
CREATE INDEX IF NOT EXISTS idx_emergency_visits_arrival ON public.emergency_visits USING btree (arrival_time);
CREATE INDEX IF NOT EXISTS idx_emergency_visits_doctor ON public.emergency_visits USING btree (doctor_id);
CREATE INDEX IF NOT EXISTS idx_emergency_visits_patient ON public.emergency_visits USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_emergency_visits_status ON public.emergency_visits USING btree (status);
CREATE INDEX IF NOT EXISTS idx_emergency_visits_triage ON public.emergency_visits USING btree (triage_category);
CREATE UNIQUE INDEX IF NOT EXISTS uq_icd_diagnoses_code ON public.icd_diagnoses USING btree (code);
CREATE INDEX IF NOT EXISTS idx_inpatient_admissions_bed ON public.inpatient_admissions USING btree (bed_id);
CREATE INDEX IF NOT EXISTS idx_inpatient_admissions_patient ON public.inpatient_admissions USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_inpatient_admissions_room ON public.inpatient_admissions USING btree (room_id);
CREATE INDEX IF NOT EXISTS idx_inpatient_admissions_status ON public.inpatient_admissions USING btree (status);
CREATE UNIQUE INDEX IF NOT EXISTS inpatient_admissions_admission_number_key ON public.inpatient_admissions USING btree (admission_number);
CREATE UNIQUE INDEX IF NOT EXISTS inpatient_discharge_summaries_admission_id_key ON public.inpatient_discharge_summaries USING btree (admission_id);
CREATE INDEX IF NOT EXISTS idx_insurance_claims_invoice ON public.insurance_claims USING btree (invoice_id);
CREATE INDEX IF NOT EXISTS idx_insurance_claims_status ON public.insurance_claims USING btree (status);
CREATE UNIQUE INDEX IF NOT EXISTS uq_insurance_claims_invoice ON public.insurance_claims USING btree (invoice_id);
CREATE INDEX IF NOT EXISTS idx_inv_tx_date ON public.inventory_transactions USING btree (created_at);
CREATE INDEX IF NOT EXISTS idx_inv_tx_item ON public.inventory_transactions USING btree (item_id);
CREATE INDEX IF NOT EXISTS idx_inv_tx_type ON public.inventory_transactions USING btree (transaction_type);
CREATE UNIQUE INDEX IF NOT EXISTS inventory_transactions_transaction_number_key ON public.inventory_transactions USING btree (transaction_number);
CREATE INDEX IF NOT EXISTS idx_invoices_appt ON public.invoices USING btree (appointment_id);
CREATE INDEX IF NOT EXISTS idx_invoices_emergency ON public.invoices USING btree (emergency_visit_id);
CREATE INDEX IF NOT EXISTS idx_invoices_patient ON public.invoices USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_invoices_status ON public.invoices USING btree (status);
CREATE INDEX IF NOT EXISTS idx_lab_orders_patient ON public.lab_orders USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_lab_orders_priority ON public.lab_orders USING btree (clinical_priority);
CREATE INDEX IF NOT EXISTS idx_lab_orders_status ON public.lab_orders USING btree (status);
CREATE INDEX IF NOT EXISTS idx_med_admin_admission ON public.medication_administrations USING btree (admission_id);
CREATE INDEX IF NOT EXISTS idx_medicines_expiry ON public.medicines USING btree (expiry_date);
CREATE INDEX IF NOT EXISTS idx_medicines_location ON public.medicines USING btree (location);
CREATE INDEX IF NOT EXISTS idx_medicines_name ON public.medicines USING btree (name);
CREATE INDEX IF NOT EXISTS idx_nursing_notes_encounter ON public.nursing_notes USING btree (encounter_type, encounter_id);
CREATE INDEX IF NOT EXISTS idx_nursing_vitals_encounter ON public.nursing_vital_signs USING btree (encounter_type, encounter_id);
CREATE INDEX IF NOT EXISTS idx_patients_name ON public.patients USING btree (full_name);
CREATE INDEX IF NOT EXISTS idx_patients_nik ON public.patients USING btree (nik);
CREATE INDEX IF NOT EXISTS idx_patients_phone ON public.patients USING btree (phone_number);
CREATE INDEX IF NOT EXISTS idx_patients_rm ON public.patients USING btree (no_rm);
CREATE INDEX IF NOT EXISTS idx_payments_invoice ON public.payments USING btree (invoice_id);
CREATE INDEX IF NOT EXISTS idx_payments_time ON public.payments USING btree (payment_time);
CREATE INDEX IF NOT EXISTS idx_dispensings_date ON public.pharmacy_dispensings USING btree (dispensed_at);
CREATE INDEX IF NOT EXISTS idx_dispensings_order ON public.pharmacy_dispensings USING btree (cpoe_order_id);
CREATE INDEX IF NOT EXISTS idx_dispensings_patient ON public.pharmacy_dispensings USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_purchase_requests_date ON public.purchase_requests USING btree (created_at);
CREATE INDEX IF NOT EXISTS idx_purchase_requests_status ON public.purchase_requests USING btree (status);
CREATE UNIQUE INDEX IF NOT EXISTS purchase_requests_pr_number_key ON public.purchase_requests USING btree (pr_number);
CREATE INDEX IF NOT EXISTS idx_radiology_orders_patient ON public.radiology_orders USING btree (patient_id);
CREATE INDEX IF NOT EXISTS idx_radiology_orders_priority ON public.radiology_orders USING btree (clinical_priority);
CREATE INDEX IF NOT EXISTS idx_radiology_orders_status ON public.radiology_orders USING btree (status);
CREATE INDEX IF NOT EXISTS idx_sessions_access ON public.sessions USING btree (access_timestamp);
CREATE INDEX IF NOT EXISTS idx_tariffs_category ON public.tariffs USING btree (category);

-- -------------------------------------------------------------
-- 50. Tabel Unit Pelayanan & Departemen RS (hospital_units)
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS public.hospital_units (
    id SERIAL PRIMARY KEY,
    unit_code VARCHAR(50) UNIQUE NOT NULL,
    unit_name VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'Klinis',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_hospital_units_code ON public.hospital_units USING btree (unit_code);
CREATE INDEX IF NOT EXISTS idx_hospital_units_category ON public.hospital_units USING btree (category);


-- 4. MASTER SEED DATA

-- Data for system_configurations (5 records)
INSERT INTO public."system_configurations" ("config_key", "config_value", "description", "updated_at") VALUES ('hospital_address', 'Jl. Siloam No. 1, Jakarta Barat', 'Alamat rumah sakit', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."system_configurations" ("config_key", "config_value", "description", "updated_at") VALUES ('hospital_name', 'Rumah Sakit Siloam Peduli', 'Nama resmi rumah sakit', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."system_configurations" ("config_key", "config_value", "description", "updated_at") VALUES ('hospital_phone', '(021) 500-911', 'Nomor telepon layanan', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."system_configurations" ("config_key", "config_value", "description", "updated_at") VALUES ('queue_prefix_default', 'A', 'Prefix antrean umum', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."system_configurations" ("config_key", "config_value", "description", "updated_at") VALUES ('rm_prefix', 'RM', 'Prefix Nomor Rekam Medis', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;

-- Data for hospital_units (18 records)
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (1, 'UNIT-REG', 'Registrasi & Admisi Pasien', 'Administrasi', TRUE, '2026-11-08 05:37:07.871578') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (2, 'UNIT-POLI-DALAM', 'Poliklinik Penyakit Dalam', 'Klinis', TRUE, '2026-11-08 05:37:07.896335') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (3, 'UNIT-POLI-ANAK', 'Poliklinik Anak', 'Klinis', TRUE, '2026-11-08 05:37:07.921332') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (4, 'UNIT-POLI-BEDAH', 'Poliklinik Bedah', 'Klinis', TRUE, '2026-11-08 05:37:07.951869') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (5, 'UNIT-POLI-UMUM', 'Poliklinik Umum', 'Klinis', TRUE, '2026-11-08 05:37:07.979375') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (6, 'UNIT-IGD', 'Instalasi Gawat Darurat (IGD)', 'Klinis', TRUE, '2026-11-08 05:37:08.003588') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (7, 'UNIT-RAWAT-JALAN', 'Keperawatan Rawat Jalan', 'Klinis', TRUE, '2026-11-08 05:37:08.034646') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (8, 'UNIT-RANAP-MELATI', 'Rawat Inap - Bangsal Melati', 'Klinis', TRUE, '2026-11-08 05:37:08.059107') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (9, 'UNIT-RANAP-MAWAR', 'Rawat Inap - Bangsal Mawar', 'Klinis', TRUE, '2026-11-08 05:37:08.085347') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (10, 'UNIT-ICU', 'Intensive Care Unit (ICU)', 'Klinis', TRUE, '2026-11-08 05:37:08.111742') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (11, 'UNIT-FARMASI', 'Instalasi Farmasi & Apotek', 'Penunjang', TRUE, '2026-11-08 05:37:08.137381') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (12, 'UNIT-GUDANG', 'Gudang & Logistik Medis', 'Penunjang', TRUE, '2026-11-08 05:37:08.16338') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (13, 'UNIT-KASIR', 'Kasir & Billing Sentral', 'Administrasi', TRUE, '2026-11-08 05:37:08.188396') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (14, 'UNIT-LAB', 'Laboratorium Sentral', 'Penunjang', TRUE, '2026-11-08 05:37:08.213402') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (15, 'UNIT-RAD', 'Instalasi Radiologi', 'Penunjang', TRUE, '2026-11-08 05:37:08.242342') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (16, 'UNIT-RAD-SPESIALIS', 'Spesialis Radiologi', 'Klinis', TRUE, '2026-11-08 05:37:08.266138') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (17, 'UNIT-MANAJEMEN', 'Direksi & Manajemen Rumah Sakit', 'Manajemen', TRUE, '2026-11-08 05:37:08.304536') ON CONFLICT DO NOTHING;
INSERT INTO public."hospital_units" ("id", "unit_code", "unit_name", "category", "is_active", "created_at") VALUES (18, 'UNIT-TI', 'Unit TI & SIMRS', 'Administrasi', TRUE, '2026-11-08 05:37:08.329319') ON CONFLICT DO NOTHING;

-- Data for users (12 records)
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (1, 'admin', '$2y$12$q1zH9bjVWKEgKR/ytEm0XuSQLT1gZh3wXKVR/5kaKz7VXDeJeOZbm', 'Andi Pratama', 'admin@siloampeduli.co.id', 'admin', 'Unit TI & SIMRS', TRUE, '2026-11-07 10:53:11', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (2, 'registrasi', '$2y$12$1gh9Ke1LvMpl/PU08Q0gxOJ/6z2zcEkQhnB.QcPNF0C/0tnrqaC4.', 'Nadia Putri', 'nadia@siloampeduli.co.id', 'registrasi', 'Registrasi & Admisi Pasien', TRUE, '2026-11-07 10:53:11', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (11, 'dokter', '$2y$12$Vhm/p/bIDci/tqy9oI67wuD11n2v2AF/BaxuJB162vhOKVMzlcSRO', 'dr. Ahmad Fauzi, Sp.PD', 'dokter@siloampeduli.co.id', 'dokter', 'Poliklinik Penyakit Dalam', TRUE, '2026-11-07 12:34:33', '2026-11-07 12:35:38') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (12, 'perawat', '$2y$12$LMSjmqT1wFlgGX1BxUlXYe1D0tZRlw7jLcVcpaXgyH/B.FLcg5JXC', 'Ns. Siti Rahmawati, S.Kep', 'perawat@siloampeduli.co.id', 'perawat', 'Keperawatan Rawat Jalan', TRUE, '2026-11-07 12:34:33', '2026-11-07 12:35:38') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (13, 'analis', '$2y$12$nQg/KfvrLMAD40Y5yHyt8.mf/PJZJs1tDHR8Tf0C0Z0fq77P9Ot1u', 'Budi Santoso, A.Md.AK', 'analis@siloampeduli.co.id', 'analis_laboratorium', 'Laboratorium Sentral', TRUE, '2026-11-07 12:34:33', '2026-11-07 12:35:38') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (14, 'radiografer', '$2y$12$xqoU.l8gKZVzE5CX9cSqfOY1oFwVpRUg4b.lMFYbr20Xj7d5s1wF6', 'Doni Prasetyo, A.Md.Rad', 'radiografer@siloampeduli.co.id', 'radiografer', 'Instalasi Radiologi', TRUE, '2026-11-07 12:34:33', '2026-11-07 12:35:38') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (15, 'drad', '$2y$12$9uXm6g7MMhiSTqfNGycxGOWp4N/591eHVvo8PIG3n5a6NvBim5Szi', 'dr. Hendra Wijaya, Sp.Rad', 'drad@siloampeduli.co.id', 'dokter_radiologi', 'Spesialis Radiologi', TRUE, '2026-11-07 12:34:33', '2026-11-07 12:35:38') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (17, 'triase', '$2y$12$5uJXMoct0INs8jKwuuJYguS6UeM.hB8gPUGDAugn9Cr8IW0Kgg4P2', 'Siti Triase, A.Md.Kep', 'triase@siloampeduli.co.id', 'triase', 'Instalasi Gawat Darurat (IGD)', TRUE, '2026-11-07 13:18:44', '2026-11-07 13:21:46') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (18, 'apoteker', '$2y$12$COTx3TzI6WgS.8nWJlBTjOi.jzali95kSofEDBQWU6ZJHkZXjn4Nq', 'apt. Rian Farmasi, S.Farm', 'apoteker@siloampeduli.co.id', 'apoteker', 'Instalasi Farmasi & Apotek', TRUE, '2026-11-07 13:18:44', '2026-11-07 13:21:46') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (19, 'kasir', '$2y$12$UPwMfqELZdfjasRZ4lWYyOd3s36rkjjhSOhpiwmf3nR6mLOMQ7n3y', 'Budi Kasir, S.Ak', 'kasir@siloampeduli.co.id', 'kasir', 'Kasir & Billing Sentral', TRUE, '2026-11-07 13:18:44', '2026-11-07 13:21:46') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (24, 'gudang', '$2y$12$41rOBgdD4OrGtQxkrEqWQ.w73kwO33cu8ee498uRMLMKKz5dxiWM.', 'Budi Santoso, S.Log', 'gudang@siloampeduli.id', 'petugas_gudang', 'Gudang & Logistik Medis', TRUE, '2026-11-07 12:49:05.625938', '2026-11-07 12:49:05.625938') ON CONFLICT DO NOTHING;
INSERT INTO public."users" ("id", "username", "password_hash", "full_name", "email", "role", "unit", "is_active", "created_at", "updated_at") VALUES (25, 'manajemen', '$2y$12$agYys21fkxt.Unh7RHmvPOU5raxnfb1OkhytpbRwnIzp7tiUg8aEW', 'dr. Hendra Pratama, MARS', 'direksi@siloampeduli.id', 'manajemen', 'Direksi & Manajemen Rumah Sakit', TRUE, '2026-11-07 12:49:05.97787', '2026-11-07 12:49:05.97787') ON CONFLICT DO NOTHING;

-- Data for policlinics (6 records)
INSERT INTO public."policlinics" ("id", "code", "name", "queue_prefix", "description", "is_active", "created_at") VALUES (1, 'POL-INT', 'Poli Penyakit Dalam', 'A', 'Spesialis penyakit dalam, metabolik, dan geriatri', TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."policlinics" ("id", "code", "name", "queue_prefix", "description", "is_active", "created_at") VALUES (2, 'POL-UMU', 'Poli Umum', 'B', 'Pelayanan kesehatan umum tingkat pertama', TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."policlinics" ("id", "code", "name", "queue_prefix", "description", "is_active", "created_at") VALUES (3, 'POL-ANA', 'Poli Anak (Pediatri)', 'C', 'Pelayanan kesehatan anak & tumbuh kembang', TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."policlinics" ("id", "code", "name", "queue_prefix", "description", "is_active", "created_at") VALUES (4, 'POL-OBG', 'Poli Kebidanan & Kandungan', 'D', 'Pemeriksaan kehamilan & reproduksi', TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."policlinics" ("id", "code", "name", "queue_prefix", "description", "is_active", "created_at") VALUES (5, 'POL-GIG', 'Poli Gigi & Mulut', 'E', 'Konservasi gigi, bedah mulut, ortodonti', TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."policlinics" ("id", "code", "name", "queue_prefix", "description", "is_active", "created_at") VALUES (6, 'POL-MAT', 'Poli Mata', 'F', 'Pemeriksaan refraksi, katarak, dan retina', TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;

-- Data for tariffs (5 records)
INSERT INTO public."tariffs" ("id", "code", "name", "category", "amount", "description", "is_active") VALUES (1, 'TND-001', 'Administrasi Rawat Jalan', 'ADMINISTRASI', 25000.00, 'Biaya pendaftaran dan kartu periksa', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."tariffs" ("id", "code", "name", "category", "amount", "description", "is_active") VALUES (2, 'TND-002', 'Konsultasi Dokter Spesialis', 'KONSULTASI', 200000.00, 'Pemeriksaan dokter spesialis', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."tariffs" ("id", "code", "name", "category", "amount", "description", "is_active") VALUES (3, 'TND-003', 'Konsultasi Dokter Umum', 'KONSULTASI', 100000.00, 'Pemeriksaan dokter umum', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."tariffs" ("id", "code", "name", "category", "amount", "description", "is_active") VALUES (4, 'TND-004', 'Pemeriksaan EKG 12 Lead', 'TINDAKAN', 120000.00, 'Rekam jantung standar', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."tariffs" ("id", "code", "name", "category", "amount", "description", "is_active") VALUES (5, 'TND-005', 'Nebulisasi / Terapi Uap', 'TINDAKAN', 85000.00, 'Inhalasi bronkodilator', TRUE) ON CONFLICT DO NOTHING;

-- Data for icd_diagnoses (11 records)
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (1, 'I10', 'Hipertensi esensial (primer)', 'Essential (primary) hypertension', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (2, 'E11', 'Diabetes melitus tidak tergantung insulin', 'Non-insulin-dependent diabetes mellitus', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (3, 'J00', 'Nasofaringitis akut (common cold)', 'Acute nasopharyngitis (common cold)', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (4, 'K29.7', 'Gastritis tidak spesifik', 'Gastritis, unspecified', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (5, 'J06.9', 'Infeksi saluran pernapasan atas akut (ISPA)', 'Acute upper respiratory infection, unspecified', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (6, 'A09', 'Diare dan gastroenteritis infeksius', 'Diarrhoea and gastroenteritis', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (7, 'J18.9', 'Pneumonia tidak spesifik', 'Pneumonia, unspecified', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (8, 'M79.1', 'Mialgia / Nyeri Otot', 'Myalgia', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (9, 'R50.9', 'Demam tidak spesifik', 'Fever, unspecified', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (10, 'R51', 'Nyeri kepala / Sefalgia', 'Headache', TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."icd_diagnoses" ("id", "code", "description_id", "description_en", "is_active") VALUES (11, 'Z00.0', 'Pemeriksaan medis umum', 'General medical examination', TRUE) ON CONFLICT DO NOTHING;

-- Data for medicines (18 records)
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (1, 'OBT-001', 'Paracetamol 500mg', 'Tablet Analgesik', 'Tablet', 490, 3500.00, '2028-12-31', TRUE, 'Gudang Utama', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (2, 'OBT-002', 'Amoxicillin 500mg', 'Antibiotik Oral', 'Kapsul', 300, 6000.00, '2027-10-15', TRUE, 'Gudang Utama', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (3, 'OBT-003', 'Amlodipine 10mg', 'Antihipertensi', 'Tablet', 250, 4500.00, '2028-05-20', TRUE, 'Gudang Utama', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (4, 'OBT-004', 'Omeprazole 20mg', 'Antasida / PPI', 'Kapsul', 350, 7500.00, '2027-12-01', TRUE, 'Gudang Utama', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (5, 'OBT-005', 'Cefixime 200mg', 'Kapsul Antibiotik', 'Strip', 120, 18000.00, '2028-06-15', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (6, 'OBT-006', 'Ceftriaxone 1g Injeksi', 'Vial Injeksi', 'Strip', 80, 45000.00, '2027-11-20', TRUE, 'Gudang Farmasi', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (7, 'OBT-007', 'Metformin 500mg', 'Tablet Oral', 'Strip', 350, 2500.00, '2028-09-10', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (8, 'OBT-008', 'Atorvastatin 20mg', 'Tablet Selaput', 'Strip', 200, 8500.00, '2028-04-25', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (9, 'OBT-009', 'Salbutamol 2mg', 'Tablet Oral', 'Strip', 180, 3000.00, '2028-01-30', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (10, 'OBT-010', 'Ketorolac 30mg/mL Injeksi', 'Ampul Injeksi', 'Strip', 95, 22000.00, '2027-12-18', TRUE, 'Depo IGD', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (11, 'OBT-011', 'Ranitidine 150mg', 'Tablet Oral', 'Strip', 260, 4000.00, '2028-03-12', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (12, 'OBT-012', 'Dexamethasone 0.5mg', 'Tablet Oral', 'Strip', 310, 2000.00, '2028-08-05', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (13, 'OBT-013', 'Ciprofloxacin 500mg', 'Kaplet Salut', 'Strip', 140, 9500.00, '2027-10-22', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (14, 'OBT-014', 'Asam Mefenamat 500mg', 'Kapsul Oral', 'Strip', 280, 4500.00, '2028-05-14', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (15, 'OBT-015', 'Azithromycin 500mg', 'Kaplet Salut', 'Strip', 110, 28000.00, '2027-09-08', TRUE, 'Apotek Sentral', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (16, 'OBT-016', 'Ondansetron 4mg Injeksi', 'Ampul Injeksi', 'Strip', 75, 18500.00, '2028-02-28', TRUE, 'Depo IGD', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (17, 'OBT-017', 'Infus Ringer Lactat 500mL', 'Kolf Cairan', 'Strip', 450, 16000.00, '2029-01-15', TRUE, 'Gudang Farmasi', 10) ON CONFLICT DO NOTHING;
INSERT INTO public."medicines" ("id", "medicine_code", "name", "category", "unit", "stock", "unit_price", "expiry_date", "is_active", "location", "min_stock") VALUES (18, 'OBT-018', 'Infus NaCl 0.9% 500mL', 'Kolf Cairan', 'Strip', 500, 15000.00, '2029-02-20', TRUE, 'Gudang Farmasi', 10) ON CONFLICT DO NOTHING;

-- Data for rooms (4 records)
INSERT INTO public."rooms" ("id", "room_code", "room_name", "class_type", "daily_rate", "is_active") VALUES (1, 'R-VIP-01', 'Ruang Mawar 101', 'VIP', 750000.00, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."rooms" ("id", "room_code", "room_name", "class_type", "daily_rate", "is_active") VALUES (2, 'R-K1-01', 'Ruang Melati 201', 'KELAS_1', 450000.00, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."rooms" ("id", "room_code", "room_name", "class_type", "daily_rate", "is_active") VALUES (3, 'R-K2-01', 'Ruang Anggrek 301', 'KELAS_2', 300000.00, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."rooms" ("id", "room_code", "room_name", "class_type", "daily_rate", "is_active") VALUES (4, 'R-ICU-01', 'Ruang ICU Bed 1', 'ICU', 1200000.00, TRUE) ON CONFLICT DO NOTHING;

-- Data for beds (6 records)
INSERT INTO public."beds" ("id", "room_id", "bed_number", "status") VALUES (1, 1, 'BED-VIP-01', 'TERSEDIA') ON CONFLICT DO NOTHING;
INSERT INTO public."beds" ("id", "room_id", "bed_number", "status") VALUES (2, 2, 'BED-M1-A', 'TERISI') ON CONFLICT DO NOTHING;
INSERT INTO public."beds" ("id", "room_id", "bed_number", "status") VALUES (3, 2, 'BED-M1-B', 'TERISI') ON CONFLICT DO NOTHING;
INSERT INTO public."beds" ("id", "room_id", "bed_number", "status") VALUES (4, 3, 'BED-A1-A', 'TERSEDIA') ON CONFLICT DO NOTHING;
INSERT INTO public."beds" ("id", "room_id", "bed_number", "status") VALUES (5, 3, 'BED-A1-B', 'TERSEDIA') ON CONFLICT DO NOTHING;
INSERT INTO public."beds" ("id", "room_id", "bed_number", "status") VALUES (6, 4, 'BED-ICU-01', 'TERSEDIA') ON CONFLICT DO NOTHING;

-- Data for doctors (4 records)
INSERT INTO public."doctors" ("id", "user_id", "policlinic_id", "sip_number", "full_name", "specialization", "consultation_fee", "is_active", "created_at") VALUES (1, 11, 1, 'SIP-446/2023/DKI', 'dr. Ahmad Fauzi, Sp.PD', 'Spesialis Penyakit Dalam', 200000.00, TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."doctors" ("id", "user_id", "policlinic_id", "sip_number", "full_name", "specialization", "consultation_fee", "is_active", "created_at") VALUES (2, NULL, 2, 'SIP-112/2022/DKI', 'dr. Siti Aminah', 'Dokter Umum', 100000.00, TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."doctors" ("id", "user_id", "policlinic_id", "sip_number", "full_name", "specialization", "consultation_fee", "is_active", "created_at") VALUES (3, NULL, 3, 'SIP-305/2024/DKI', 'dr. Hendra Wijaya, Sp.A', 'Spesialis Anak', 220000.00, TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."doctors" ("id", "user_id", "policlinic_id", "sip_number", "full_name", "specialization", "consultation_fee", "is_active", "created_at") VALUES (4, NULL, 4, 'SIP-782/2023/DKI', 'dr. Maya Kartika, Sp.OG', 'Spesialis Kebidanan & Kandungan', 250000.00, TRUE, '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;

-- Data for doctor_schedules (27 records)
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (1, 1, 'Senin', '08:00:00', '13:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (2, 1, 'Rabu', '08:00:00', '13:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (3, 1, 'Jumat', '08:00:00', '13:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (4, 2, 'Senin', '08:00:00', '15:00:00', 50, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (5, 2, 'Selasa', '08:00:00', '15:00:00', 50, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (6, 2, 'Rabu', '08:00:00', '15:00:00', 50, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (7, 3, 'Selasa', '09:00:00', '14:00:00', 25, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (8, 3, 'Kamis', '09:00:00', '14:00:00', 25, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (9, 4, 'Rabu', '10:00:00', '16:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (10, 4, 'Sabtu', '09:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (11, 1, 'Sabtu', '08:00:00', '16:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (12, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (13, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (14, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (15, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (16, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (17, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (18, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (19, 1, 'Minggu', '08:00:00', '14:00:00', 100, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (20, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (21, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (22, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (23, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (24, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (25, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (26, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."doctor_schedules" ("id", "doctor_id", "day_name", "start_time", "end_time", "quota", "is_active") VALUES (27, 1, 'Minggu', '08:00:00', '14:00:00', 20, TRUE) ON CONFLICT DO NOTHING;

-- Data for lab_tests (4 records)
INSERT INTO public."lab_tests" ("id", "test_code", "test_name", "normal_range", "unit", "tariff", "is_active") VALUES (1, 'LAB-001', 'Hemoglobin (Hb)', '13.0 - 17.0 (L), 12.0 - 15.0 (P)', 'g/dL', 45000.00, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."lab_tests" ("id", "test_code", "test_name", "normal_range", "unit", "tariff", "is_active") VALUES (2, 'LAB-002', 'Glukosa Darah Sewaktu (GDS)', '70 - 140', 'mg/dL', 35000.00, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."lab_tests" ("id", "test_code", "test_name", "normal_range", "unit", "tariff", "is_active") VALUES (3, 'LAB-003', 'Darah Lengkap Otomatis (CBC)', 'Lengkap', 'Panel', 120000.00, TRUE) ON CONFLICT DO NOTHING;
INSERT INTO public."lab_tests" ("id", "test_code", "test_name", "normal_range", "unit", "tariff", "is_active") VALUES (4, 'LAB-004', 'Kolesterol Total', '< 200', 'mg/dL', 55000.00, TRUE) ON CONFLICT DO NOTHING;

-- Data for patients (102 records)
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (1, 'RM-202611-0001', '3171010101900001', 'Budi Santoso', 'L', '1990-01-01', 'O', '081234567890', 'budi@gmail.com', 'Jl. Kebon Jeruk No. 12, Jakarta Barat', 'Alergi Penisilin, Seafood', 'UMUM', NULL, '2026-11-07 10:53:11', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (2, 'RM-202611-0002', '3171020202850002', 'Siti Rahmawati', 'P', '1985-05-14', 'A', '081398765432', 'siti.rahma@yahoo.com', 'Jl. Anggrek Merah No. 5, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567891', '2026-11-07 10:53:11', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (3, 'RM-202611-0003', '3171030303780003', 'Joko Susilo', 'L', '1978-11-20', 'B', '081512349876', 'joko_susilo@gmail.com', 'Komplek Permata Indah Blok C2, Tangerang', 'Alergi Aspirin', 'ASURANSI', 'POLIS-PRU-98213', '2026-11-07 10:53:11', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (4, 'RM-202611-0004', '3201015394448635', 'Ratna Sari Dewi', 'L', '1990-05-15', 'O', '', '', 'Jl. Palmerah Barat No. 18, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 11:04:21', '2026-11-07 11:04:21') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (5, 'RM-202611-0005', '3201018053435907', 'Rudi Hermawan', 'L', '1990-05-15', 'O', '081234567890', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 11:07:05', '2026-11-07 11:07:05') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (6, 'RM-202611-0006', '3201017866611896', 'Dewi Anggraini', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 11:07:26', '2026-11-07 11:07:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (7, 'RM-202611-0007', '3201018674280124', 'Bambang Suryono', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 11:07:41', '2026-11-07 11:07:41') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (8, 'RM-202611-0008', '3271011991190413', 'Nurul Hidayati', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 11:11:22', '2026-11-07 11:11:22') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (9, 'RM-202611-0009', '3271016728515610', 'Agus Prasetyo', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 11:12:38', '2026-11-07 11:12:38') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (10, 'RM-202611-0010', '3201013245968695', 'Sri Rahayu', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 11:12:47', '2026-11-07 11:12:47') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (11, 'RM-202611-0011', '3201018178482407', 'Wayan Sudarma', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Margonda Raya No. 102, Depok', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 12:12:44', '2026-11-07 12:12:44') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (12, 'RM-202611-0012', '3271019764852306', 'Kartika Sari', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Boulevard Gading Serpong No. 5, Tangerang', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:13:01', '2026-11-07 12:13:01') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (13, 'RM-202611-0013', '3201017087827749', 'Hendra Gunawan', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 12:13:40', '2026-11-07 12:13:40') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (14, 'RM-202611-0014', '3271017196195969', 'Rina Marlina', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:14:12', '2026-11-07 12:14:12') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (15, 'RM-202611-0015', '3201015576886362', 'Joko Widodo Santoso', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 12:21:06', '2026-11-07 12:21:06') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (16, 'RM-202611-0016', '3271018966243477', 'Fitri Handayani', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:21:11', '2026-11-07 12:21:11') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (17, 'RM-202611-0017', '3201015494852761', 'Dedi Kusnadi', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 12:24:01', '2026-11-07 12:24:01') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (18, 'RM-202611-0018', '3271012213016905', 'Yuliana Lestari', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:24:06', '2026-11-07 12:24:06') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (19, 'RM-202611-0019', '3171010705483146', 'Arief Budiman', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Margonda Raya No. 102, Depok', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 12:48:31', '2026-11-07 12:48:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (20, 'RM-202611-0020', '3171010705485720', 'Siti Fatimah', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Boulevard Gading Serpong No. 5, Tangerang', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 12:48:57', '2026-11-07 12:48:57') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (21, 'RM-202611-0021', '3171010705492618', 'Eko Purnomo', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 12:49:26', '2026-11-07 12:49:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (22, 'RM-202611-0022', '3171010705494588', 'Mega Silvia', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 12:49:45', '2026-11-07 12:49:45') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (23, 'RM-202611-0023', '3172020705512835', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:51:28', '2026-11-07 12:51:28') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (24, 'RM-202611-0024', '3172020705513429', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:51:34', '2026-11-07 12:51:34') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (25, 'RM-202611-0025', '3172020705515487', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:51:54', '2026-11-07 12:51:54') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (26, 'RM-202611-0026', '3172020705524331', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:52:43', '2026-11-07 12:52:43') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (27, 'RM-202611-0027', '3172020705532852', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:53:28', '2026-11-07 12:53:28') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (28, 'RM-202611-0028', '3172020705535917', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:53:59', '2026-11-07 12:53:59') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (29, 'RM-202611-0029', '3172020705544835', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:54:48', '2026-11-07 12:54:48') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (30, 'RM-202611-0030', '3201014595982507', 'Bayu Wicaksono', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 12:54:57', '2026-11-07 12:54:57') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (31, 'RM-202611-0031', '3271018604222567', 'Tia Pratiwi', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:54:58', '2026-11-07 12:54:58') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (32, 'RM-202611-0032', '3171010705562672', 'Ahmad Subagyo', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 12:56:26', '2026-11-07 12:56:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (33, 'RM-202611-0033', '3172020705562657', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:56:26', '2026-11-07 12:56:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (34, 'RM-202611-0034', '3171010706001988', 'Indah Permatasari', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:00:19', '2026-11-07 13:00:19') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (35, 'RM-202611-0035', '3172020706002054', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:00:20', '2026-11-07 13:00:20') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (36, 'RM-202611-0036', '3171010706004326', 'Rizky Ramadhan', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Margonda Raya No. 102, Depok', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:00:43', '2026-11-07 13:00:43') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (37, 'RM-202611-0037', '3172020706004429', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:00:44', '2026-11-07 13:00:44') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (38, 'RM-202611-0038', '3171010706153132', 'Maya Puspita', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Boulevard Gading Serpong No. 5, Tangerang', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:15:31', '2026-11-07 13:15:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (39, 'RM-202611-0039', '3172020706153213', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:15:32', '2026-11-07 13:15:32') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (40, 'RM-202611-0040', '3172020706393765', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:39:37', '2026-11-07 13:39:37') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (41, 'RM-202611-0041', '3172020706414267', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:41:42', '2026-11-07 13:41:42') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (42, 'RM-202611-0042', '3172020706420817', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:42:08', '2026-11-07 13:42:08') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (43, 'RM-202611-0043', '3172020706435630', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:43:56', '2026-11-07 13:43:56') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (44, 'RM-202611-0044', '3172030706435636', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', '', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 13:43:56', '2026-11-07 13:43:56') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (45, 'RM-202611-0045', '3172020706461846', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:46:18', '2026-11-07 13:46:18') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (46, 'RM-202611-0046', '3172030706461850', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 13:46:18', '2026-11-07 13:46:18') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (47, 'RM-202611-0047', '3172020706494479', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:49:44', '2026-11-07 13:49:44') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (48, 'RM-202611-0048', '3172020706550569', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:55:05', '2026-11-07 13:55:05') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (49, 'RM-202611-0049', '3172030706550583', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 13:55:05', '2026-11-07 13:55:05') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (50, 'RM-202611-0050', '3171010706551629', 'Fajar Nugroho', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:55:16', '2026-11-07 13:55:16') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (51, 'RM-202611-0051', '3172020706552246', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:55:22', '2026-11-07 13:55:22') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (52, 'RM-202611-0052', '3201013818884942', 'Lestari Wulandari', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 13:55:31', '2026-11-07 13:55:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (53, 'RM-202611-0053', '3201018748127063', 'Adi Saputra', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 13:55:59', '2026-11-07 13:55:59') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (54, 'RM-202611-0054', '3271016758777444', 'Putri Ayu Wandira', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 13:56:05', '2026-11-07 13:56:05') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (55, 'RM-202611-0055', '3172020708461698', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 15:46:16', '2026-11-07 15:46:16') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (56, 'RM-202611-0056', '3172030708461622', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 15:46:16', '2026-11-07 15:46:16') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (57, 'RM-202611-0057', '3172020708462790', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 15:46:27', '2026-11-07 15:46:27') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (58, 'RM-202611-0058', '3271016916166129', 'Wahyu Setiawan', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 15:46:36', '2026-11-07 15:46:36') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (59, 'RM-202611-0059', '3271015313096785', 'Dian Sastro Wardoyo', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 16:07:23', '2026-11-07 16:07:23') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (60, 'RM-202611-0060', '3172020709134250', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 16:13:42', '2026-11-07 16:13:42') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (61, 'RM-202611-0061', '3271018986728323', 'Bagus Santoso', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Margonda Raya No. 102, Depok', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 16:13:49', '2026-11-07 16:13:49') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (62, 'RM-202611-0062', '3271015508875570', 'Endang Susilowati', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Boulevard Gading Serpong No. 5, Tangerang', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 16:14:23', '2026-11-07 16:14:23') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (63, 'RM-202611-0063', '3201017597211001', 'Aris Munandar', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 16:14:31', '2026-11-07 16:14:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (64, 'RM-202611-0064', '3171010709143115', 'Tri Wahyuni', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 16:14:31', '2026-11-07 16:14:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (65, 'RM-202611-0065', '3172020709143142', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 16:14:31', '2026-11-07 16:14:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (66, 'RM-202611-0066', '3172030709143135', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 16:14:31', '2026-11-07 16:14:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (67, 'RM-202611-0067', '3201011013767529', 'Surya Darmawan', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 12:27:52.305557', '2026-11-07 12:27:52.556558') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (68, 'RM-202611-0068', '3171010712280036', 'Ratna Juwita', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 12:28:00.282942', '2026-11-07 12:28:00.282942') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (69, 'RM-202611-0069', '3172020712281018', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 12:28:10.332174', '2026-11-07 12:28:10.332174') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (70, 'RM-202611-0070', '3172030712281716', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 12:28:17.145022', '2026-11-07 12:28:17.145022') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (71, 'RM-202611-0071', '3172020712295574', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 12:29:55.96678', '2026-11-07 12:29:55.96678') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (72, 'RM-202611-0072', '3172030712300315', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 12:30:03.202185', '2026-11-07 12:30:03.202185') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (73, 'RM-202611-0073', '3201854168503386', 'Gilang Pratama', 'L', '1985-04-12', '-', '081269950989', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Tidak ada', 'UMUM', '', '2026-11-07 12:30:14.054461', '2026-11-07 12:30:14.054461') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (74, 'RM-202611-0074', '3201108373052754', 'Anisa Rahmawati', 'L', '1985-04-12', '-', '081249925862', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Tidak ada', 'UMUM', '', '2026-11-07 12:31:15.303758', '2026-11-07 12:31:15.303758') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (75, 'RM-202611-0075', '3172020712322994', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 12:32:29.741947', '2026-11-07 12:32:29.741947') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (76, 'RM-202611-0076', '3271017299152376', 'Hadi Pranoto', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Margonda Raya No. 102, Depok', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:32:56.372427', '2026-11-07 12:32:56.372427') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (77, 'RM-202611-0077', '3271015933139149', 'Winda Utami', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Boulevard Gading Serpong No. 5, Tangerang', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 12:33:47.20628', '2026-11-07 12:33:47.20628') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (78, 'RM-202611-0078', '3201017130541044', 'Denny Siregar', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 13:10:32.863336', '2026-11-07 13:10:33.102192') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (79, 'RM-202611-0079', '3271018597621303', 'Novita Sari', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 13:10:39.044466', '2026-11-07 13:10:39.044466') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (80, 'RM-202611-0080', '3171010713105649', 'Mulyadi Pratama', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:10:56.862068', '2026-11-07 13:10:56.862068') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (81, 'RM-202611-0081', '3172020713110346', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:11:04.13639', '2026-11-07 13:11:04.13639') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (82, 'RM-202611-0082', '3172020713113137', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:11:31.387064', '2026-11-07 13:11:31.387064') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (83, 'RM-202611-0083', '3172030713113844', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 13:11:38.353946', '2026-11-07 13:11:38.353946') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (84, 'RM-202611-0084', '3201166374208745', 'Irma Suryani', 'L', '1985-04-12', '-', '081273053244', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Tidak ada', 'UMUM', '', '2026-11-07 13:12:15.281017', '2026-11-07 13:12:15.281017') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (85, 'RM-202611-0085', '3201014083788815', 'Gunawan Wibisono', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 13:24:51.906849', '2026-11-07 13:24:52.150657') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (86, 'RM-202611-0086', '3271018356995674', 'Desi Ratnasari', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 13:24:57.946994', '2026-11-07 13:24:57.946994') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (87, 'RM-202611-0087', '3171010713251127', 'Lukman Hakim', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Margonda Raya No. 102, Depok', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:25:11.848443', '2026-11-07 13:25:11.848443') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (88, 'RM-202611-0088', '3172020713251910', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:25:19.759854', '2026-11-07 13:25:19.759854') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (89, 'RM-202611-0089', '3172020713254379', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:25:43.090766', '2026-11-07 13:25:43.090766') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (90, 'RM-202611-0090', '3172030713254946', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 13:25:49.594388', '2026-11-07 13:25:49.594388') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (91, 'RM-202611-0091', '3201105688659819', 'Nur Hasanah', 'L', '1985-04-12', '-', '081254876249', '', 'Jl. Boulevard Gading Serpong No. 5, Tangerang', 'Tidak ada', 'UMUM', '', '2026-11-07 13:26:20.158864', '2026-11-07 13:26:20.158864') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (92, 'RM-202611-0092', '3201014490867321', 'Rian Hidayat', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Anggrek No. 14, Jakarta Barat', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 13:28:01.004698', '2026-11-07 13:28:01.251675') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (93, 'RM-202611-0093', '3271019349519013', 'Susanti Dewi', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Kebon Jeruk No. 8, Jakarta Barat', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 13:28:07.212442', '2026-11-07 13:28:07.212442') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (94, 'RM-202611-0094', '3201816628735129', 'Herman Susilo', 'L', '1985-04-12', '-', '081296319762', '', 'Jl. Merdeka No. 45, Jakarta Pusat', 'Tidak ada', 'UMUM', '', '2026-11-07 13:28:27.487739', '2026-11-07 13:28:27.487739') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (95, 'RM-202611-0095', '3201013332993944', 'Ratih Kumala', 'L', '1990-05-15', 'AB', '081234567899', '', 'Jl. Gatot Subroto No. 12, Jakarta Selatan', 'Tidak ada', 'BPJS', '0001234567890', '2026-11-07 13:29:41.402646', '2026-11-07 13:29:41.616564') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (96, 'RM-202611-0096', '3271016766739533', 'Danang Wijaya', 'L', '1992-08-20', 'B', '081299887766', '', 'Jl. Sudirman No. 88, Jakarta Selatan', 'Tidak ada', 'BPJS', '0009876543210', '2026-11-07 13:29:47.132574', '2026-11-07 13:29:47.132574') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (97, 'RM-202611-0097', '3171010713300093', 'Sri Mulyani', 'L', '1988-06-15', 'O', '081233445566', '', 'Jl. Pemuda No. 21, Rawamangun, Jakarta Timur', 'Alergi Penisilin', 'BPJS', '000123987456', '2026-11-07 13:30:00.181491', '2026-11-07 13:30:00.181491') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (98, 'RM-202611-0098', '3172020713300798', 'Tn. Hendro Wijaksono', 'L', '1990-08-20', 'B', '081298765432', '', 'Jl. Flamboyan Indah No. 8, Jakarta', 'Tidak ada', 'BPJS', '000987123654', '2026-11-07 13:30:07.465371', '2026-11-07 13:30:07.465371') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (99, 'RM-202611-0099', '3172020713302658', 'Tn. Hendra Gawat Darurat', 'L', '1982-04-12', 'B', '081299887766', '', 'Jl. Darurat No. 7', 'Alergi Amoxicillin', 'UMUM', '', '2026-11-07 13:30:26.826223', '2026-11-07 13:30:26.826223') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (100, 'RM-202611-0100', '3172030713303351', 'Ny. Rahmawati BPJS', 'P', '1990-08-20', '-', '', '', 'Jl. Uji BPJS No. 9', 'Tidak ada', 'BPJS', '000778899112', '2026-11-07 13:30:33.690056', '2026-11-07 13:30:33.690056') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (101, 'RM-202611-0101', '3201863601282497', 'Taufik Hidayat', 'L', '1985-04-12', '-', '081231024560', '', 'Jl. Margonda Raya No. 102, Depok', 'Tidak ada', 'UMUM', '', '2026-11-07 13:31:02.654386', '2026-11-07 13:31:02.654386') ON CONFLICT DO NOTHING;
INSERT INTO public."patients" ("id", "no_rm", "nik", "full_name", "gender", "birth_date", "blood_type", "phone_number", "email", "address", "allergy_notes", "insurance_type", "insurance_number", "created_at", "updated_at") VALUES (102, 'RM-202611-0102', '1900000452708660', 'Susi Sulianti', 'P', '1990-12-10', 'AB', '085436776780', '', 'Sukabumi', 'Tidak ada', 'BPJS', '07542356789007', '2026-11-08 02:09:53.304844', '2026-11-08 02:09:53.304844') ON CONFLICT DO NOTHING;

-- Data for patient_allergies (26 records)
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (1, 21, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 12:49:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (2, 22, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 12:49:45') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (3, 28, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 12:54:00') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (4, 29, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 12:54:49') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (5, 32, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 12:56:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (6, 33, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 12:56:26') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (7, 34, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:00:19') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (8, 35, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:00:21') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (9, 36, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:00:43') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (10, 37, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:00:45') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (11, 38, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:15:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (12, 39, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:15:32') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (13, 47, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:49:44') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (14, 50, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:55:16') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (15, 51, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:55:22') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (16, 57, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 15:46:28') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (17, 60, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 16:13:43') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (18, 64, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 16:14:31') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (19, 68, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 12:28:00.878802') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (20, 75, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 12:32:36.97929') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (21, 80, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:10:57.432587') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (22, 81, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:11:13.380633') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (23, 87, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:25:12.411501') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (24, 88, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:25:27.722622') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (25, 97, 'Ceftriaxone', 'BERAT', 'Sesak &amp; Eritema', '2026-11-07 13:30:00.75919') ON CONFLICT DO NOTHING;
INSERT INTO public."patient_allergies" ("id", "patient_id", "allergen_name", "severity", "reaction", "recorded_at") VALUES (26, 98, 'Sulfonamide', 'SEDANG', 'Bercak merah di kulit', '2026-11-07 13:30:14.888519') ON CONFLICT DO NOTHING;

-- Data for appointments (68 records)
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (1, 'BKG-20261107-001', 'A-001', 1, 1, 1, '2026-11-07', 'UMUM', NULL, NULL, 'IN_PROGRESS', 'Konsultasi keluhan tengkuk berat', NULL, '2026-11-07 10:53:11', '2026-11-07 12:52:22') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (2, 'BKG-20261107-002', 'A-002', 2, 1, 1, '2026-11-07', 'BPJS', '0001234567891', 'REF-FKTP-2026-001', 'SCHEDULED', 'Kontrol rutin hipertensi', NULL, '2026-11-07 10:53:11', '2026-11-07 10:53:11') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (3, 'BKG-20261107-433', 'A-001', 5, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 11:07:05', '2026-11-08 05:38:42.498675') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (4, 'BKG-20261107-321', 'A-002', 6, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 11:07:26', '2026-11-08 05:38:42.603905') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (5, 'BKG-20261107-971', 'A-003', 7, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 11:07:41', '2026-11-08 05:38:42.812243') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (6, 'BKG-20261107-324', 'A-004', 8, 1, 1, '2026-11-08', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 11:11:22', '2026-11-08 05:38:42.909036') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (7, 'BKG-20261107-647', 'A-005', 9, 1, 1, '2026-11-08', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 11:12:39', '2026-11-08 05:38:43.023215') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (8, 'BKG-20261107-235', 'A-006', 10, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 11:12:47', '2026-11-08 05:38:43.11027') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (9, 'BKG-20261107-419', 'A-007', 11, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 12:12:44', '2026-11-08 05:38:43.189214') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (10, 'BKG-20261107-411', 'A-008', 12, 1, 1, '2026-11-08', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:13:02', '2026-11-08 05:38:43.321164') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (11, 'BKG-20261107-590', 'A-009', 13, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 12:13:40', '2026-11-08 05:38:43.399838') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (12, 'BKG-20261107-862', 'A-010', 14, 1, 1, '2026-11-08', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:14:12', '2026-11-08 05:38:43.477802') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (13, 'BKG-20261107-757', 'A-011', 15, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 12:21:06', '2026-11-08 05:38:43.550862') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (14, 'BKG-20261107-136', 'A-012', 16, 1, 1, '2026-11-08', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:21:11', '2026-11-08 05:38:43.621706') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (15, 'BKG-20261107-142', 'A-013', 17, 1, 1, '2026-11-08', 'BPJS', '', '', 'SCHEDULED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 12:24:01', '2026-11-08 05:38:43.692528') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (16, 'BKG-20261107-510', 'A-014', 18, 1, 1, '2026-11-08', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:24:06', '2026-11-08 05:38:43.774377') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (17, 'BKG-20261107-298', 'A-003', 21, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 12:49:26', '2026-11-07 12:49:26') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (18, 'BKG-20261107-401', 'A-004', 22, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 12:49:45', '2026-11-07 12:49:45') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (19, 'BKG-20261107-191', 'A-005', 23, 1, 1, '2026-11-07', 'UMUM', '', '', 'SCHEDULED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:51:28', '2026-11-07 12:51:28') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (20, 'BKG-20261107-662', 'A-006', 24, 1, 1, '2026-11-07', 'UMUM', '', '', 'SCHEDULED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:51:34', '2026-11-07 12:51:34') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (21, 'BKG-20261107-348', 'A-007', 25, 1, 1, '2026-11-07', 'UMUM', '', '', 'SCHEDULED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:51:54', '2026-11-07 12:51:54') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (22, 'BKG-20261107-706', 'A-008', 26, 1, 1, '2026-11-07', 'UMUM', '', '', 'IN_PROGRESS', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:52:43', '2026-11-07 12:52:43') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (24, 'BKG-20261107-977', 'A-009', 1, 1, 1, '2026-11-07', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan keluhan batuk berdahak & radang tenggorokan', NULL, '2026-11-07 12:53:35', '2026-11-07 12:53:35') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (25, 'BKG-20261107-146', 'A-010', 27, 1, 1, '2026-11-07', 'UMUM', '', '', 'SCHEDULED', 'Pemeriksaan keluhan batuk berdahak & radang tenggorokan', NULL, '2026-11-07 12:53:49', '2026-11-07 12:53:49') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (26, 'BKG-20261107-823', 'A-011', 28, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:53:59', '2026-11-07 12:54:00') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (27, 'BKG-20261107-431', 'A-012', 29, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:54:48', '2026-11-07 12:54:49') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (28, 'BKG-20261107-505', 'A-015', 30, 1, 1, '2026-11-08', 'BPJS', '', '', 'IN_PROGRESS', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 12:54:57', '2026-11-08 05:38:43.848676') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (29, 'BKG-20261107-744', 'A-016', 31, 1, 1, '2026-11-08', 'UMUM', '', '', 'IN_PROGRESS', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:54:58', '2026-11-08 05:38:43.929853') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (30, 'BKG-20261107-110', 'A-013', 32, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 12:56:26', '2026-11-07 12:56:26') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (31, 'BKG-20261107-733', 'A-014', 33, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:56:26', '2026-11-07 12:56:26') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (32, 'BKG-20261107-575', 'A-015', 34, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:00:19', '2026-11-07 13:00:19') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (33, 'BKG-20261107-723', 'A-016', 35, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:00:20', '2026-11-07 13:00:21') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (34, 'BKG-20261107-729', 'A-017', 36, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:00:43', '2026-11-07 13:00:43') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (35, 'BKG-20261107-658', 'A-018', 37, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:00:44', '2026-11-07 13:00:45') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (36, 'BKG-20261107-124', 'A-019', 38, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:15:31', '2026-11-07 13:15:31') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (37, 'BKG-20261107-773', 'A-020', 39, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:15:32', '2026-11-07 13:15:32') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (38, 'BKG-20261107-371', 'A-021', 47, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:49:44', '2026-11-07 13:49:44') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (39, 'BKG-20261107-811', 'A-022', 50, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:55:16', '2026-11-07 13:55:16') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (40, 'BKG-20261107-568', 'A-023', 51, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:55:22', '2026-11-07 13:55:22') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (42, 'BKG-20261107-4388', 'A-017', 53, 1, 1, '2026-11-08', 'BPJS', '', '', 'IN_PROGRESS', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 13:55:59', '2026-11-08 05:38:44.008759') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (43, 'BKG-20261107-7319', 'A-018', 54, 1, 1, '2026-11-08', 'UMUM', '', '', 'IN_PROGRESS', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 13:56:05', '2026-11-08 05:38:44.087761') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (44, 'BKG-20261107-9946', 'A-024', 57, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 15:46:27', '2026-11-07 15:46:28') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (45, 'BKG-20261107-3778', 'A-019', 58, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 15:46:36', '2026-11-08 05:38:44.1618') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (46, 'BKG-20261107-4757', 'A-020', 59, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 16:07:24', '2026-11-08 05:38:44.242759') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (47, 'BKG-20261107-9805', 'A-025', 60, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 16:13:42', '2026-11-07 16:13:43') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (48, 'BKG-20261107-5974', 'A-021', 61, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 16:13:49', '2026-11-08 05:38:44.317637') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (49, 'BKG-20261107-9532', 'A-022', 62, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 16:14:23', '2026-11-08 05:38:44.392659') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (50, 'BKG-20261107-7096', 'A-023', 63, 1, 1, '2026-11-08', 'BPJS', '', '', 'COMPLETED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 16:14:31', '2026-11-08 05:38:44.465634') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (51, 'BKG-20261107-408', 'A-026', 64, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 16:14:31', '2026-11-07 16:14:31') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (52, 'BKG-20261107-9606', 'A-024', 67, 1, 1, '2026-11-08', 'BPJS', '', '', 'COMPLETED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 12:27:53.46554', '2026-11-08 05:38:44.538718') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (53, 'BKG-20261107-943', 'A-027', 68, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 12:28:00.529481', '2026-11-07 12:28:00.529481') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (54, 'BKG-20261107-7204', 'A-028', 75, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 12:32:31.401921', '2026-11-07 12:32:31.401921') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (55, 'BKG-20261107-2195', 'A-025', 76, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:32:59.223202', '2026-11-08 05:38:44.61964') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (56, 'BKG-20261107-6423', 'A-026', 77, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 12:33:50.020101', '2026-11-08 05:38:44.700669') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (57, 'BKG-20261107-4338', 'A-027', 78, 1, 1, '2026-11-08', 'BPJS', '', '', 'COMPLETED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 13:10:33.9671', '2026-11-08 05:38:44.772385') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (58, 'BKG-20261107-5039', 'A-028', 79, 1, 1, '2026-11-08', 'UMUM', '', '', 'COMPLETED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', NULL, '2026-11-07 13:10:42.363285', '2026-11-08 05:38:44.855768') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (59, 'BKG-20261107-775', 'A-029', 80, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:10:57.102181', '2026-11-07 13:10:57.102181') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (60, 'BKG-20261107-4775', 'A-030', 81, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:11:05.80773', '2026-11-07 13:11:05.80773') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (61, 'BKG-20261107-1469', 'A-029', 85, 1, 1, '2026-11-08', 'BPJS', '', '', 'COMPLETED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', NULL, '2026-11-07 13:24:53.018721', '2026-11-08 05:38:44.931453') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (62, 'BKG-20261107-3306', 'A-030', 86, 1, 1, '2026-11-08', 'UMUM', '', '', 'CANCELLED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', 'Pasien Berhalangan Hadir (Kendala tugas pekerjaan mendadak)', '2026-11-07 13:25:00.953561', '2026-11-08 05:38:45.008529') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (63, 'BKG-20261107-685', 'A-031', 87, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:25:12.081309', '2026-11-07 13:25:12.081309') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (64, 'BKG-20261107-5771', 'A-032', 88, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:25:21.461393', '2026-11-07 13:25:21.461393') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (65, 'BKG-20261107-9717', 'A-031', 92, 1, 1, '2026-11-08', 'BPJS', '', '', 'CANCELLED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', 'Dokter Berhalangan / Operasi Cito Emergensi di Kamar Operasi', '2026-11-07 13:28:02.115639', '2026-11-08 05:38:45.087806') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (66, 'BKG-20261107-1414', 'A-032', 93, 1, 1, '2026-11-08', 'UMUM', '', '', 'CANCELLED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', 'Kegawatan Akut - Pasien dialihkan langsung ke Triase IGD (Zona Merah)', '2026-11-07 13:28:10.307327', '2026-11-08 05:38:45.162967') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (67, 'BKG-20261107-1291', 'A-033', 95, 1, 1, '2026-11-08', 'BPJS', '', '', 'CANCELLED', 'Pemeriksaan rutin kontrol gula darah & hipertensi', 'Salah Input Poliklinik - Dibatalkan & didaftarkan ulang ke Poli Penyakit Dalam', '2026-11-07 13:29:42.384729', '2026-11-08 05:38:45.236593') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (68, 'BKG-20261107-9402', 'A-034', 96, 1, 1, '2026-11-08', 'UMUM', '', '', 'CANCELLED', 'Pemeriksaan tensi darah tinggi & keluhan pusing berputar', 'Permintaan Pasien - Gejala sakit sudah mereda setelah istirahat', '2026-11-07 13:29:49.888927', '2026-11-08 05:38:45.315574') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (69, 'BKG-20261107-262', 'A-033', 97, 1, 1, '2026-11-07', 'BPJS', NULL, NULL, 'COMPLETED', 'Demam tinggi 3 hari, batuk produktif', NULL, '2026-11-07 13:30:00.423055', '2026-11-07 13:30:00.423055') ON CONFLICT DO NOTHING;
INSERT INTO public."appointments" ("id", "booking_code", "queue_number", "patient_id", "doctor_id", "policlinic_id", "appointment_date", "payment_type", "insurance_number", "referral_number", "status", "notes", "cancel_reason", "created_at", "updated_at") VALUES (70, 'BKG-20261107-4210', 'A-034', 98, 1, 1, '2026-11-07', 'UMUM', '', '', 'COMPLETED', 'Batuk berdahak 4 hari, demam tinggi, nyeri dada saat bernapas', NULL, '2026-11-07 13:30:09.155803', '2026-11-07 13:30:09.155803') ON CONFLICT DO NOTHING;

-- 5. RESET SERIAL SEQUENCES
SELECT setval(pg_get_serial_sequence('public."hospital_units"', 'id'), COALESCE(MAX(id), 1)) FROM public."hospital_units";
SELECT setval(pg_get_serial_sequence('public."users"', 'id'), COALESCE(MAX(id), 1)) FROM public."users";
SELECT setval(pg_get_serial_sequence('public."policlinics"', 'id'), COALESCE(MAX(id), 1)) FROM public."policlinics";
SELECT setval(pg_get_serial_sequence('public."tariffs"', 'id'), COALESCE(MAX(id), 1)) FROM public."tariffs";
SELECT setval(pg_get_serial_sequence('public."icd_diagnoses"', 'id'), COALESCE(MAX(id), 1)) FROM public."icd_diagnoses";
SELECT setval(pg_get_serial_sequence('public."medicines"', 'id'), COALESCE(MAX(id), 1)) FROM public."medicines";
SELECT setval(pg_get_serial_sequence('public."rooms"', 'id'), COALESCE(MAX(id), 1)) FROM public."rooms";
SELECT setval(pg_get_serial_sequence('public."beds"', 'id'), COALESCE(MAX(id), 1)) FROM public."beds";
SELECT setval(pg_get_serial_sequence('public."doctors"', 'id'), COALESCE(MAX(id), 1)) FROM public."doctors";
SELECT setval(pg_get_serial_sequence('public."doctor_schedules"', 'id'), COALESCE(MAX(id), 1)) FROM public."doctor_schedules";
SELECT setval(pg_get_serial_sequence('public."lab_tests"', 'id'), COALESCE(MAX(id), 1)) FROM public."lab_tests";
SELECT setval(pg_get_serial_sequence('public."patients"', 'id'), COALESCE(MAX(id), 1)) FROM public."patients";
SELECT setval(pg_get_serial_sequence('public."patient_allergies"', 'id'), COALESCE(MAX(id), 1)) FROM public."patient_allergies";
SELECT setval(pg_get_serial_sequence('public."appointments"', 'id'), COALESCE(MAX(id), 1)) FROM public."appointments";
