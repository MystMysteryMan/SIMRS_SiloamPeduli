-- Additive migration: modules 3 / 8 / 9 only. Do not rerun the full seed script.
BEGIN;
CREATE TABLE IF NOT EXISTS emr_attachments (
    id BIGSERIAL PRIMARY KEY,
    patient_id INTEGER NOT NULL REFERENCES patients(id),
    appointment_id INTEGER NOT NULL REFERENCES appointments(id),
    uploaded_by INTEGER NOT NULL REFERENCES users(id),
    filename VARCHAR(150) NOT NULL,
    mime_type VARCHAR(80) NOT NULL,
    content_base64 TEXT NOT NULL,
    byte_size INTEGER NOT NULL CHECK (byte_size BETWEEN 1 AND 3145728),
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_emr_attachments_visit ON emr_attachments(appointment_id);
CREATE TABLE IF NOT EXISTS radiology_image_files (
    radiology_order_id INTEGER PRIMARY KEY REFERENCES radiology_orders(id),
    mime_type VARCHAR(80) NOT NULL,
    filename VARCHAR(150) NOT NULL,
    content_base64 TEXT NOT NULL,
    byte_size INTEGER NOT NULL CHECK (byte_size BETWEEN 1 AND 3145728),
    uploaded_by INTEGER NOT NULL REFERENCES users(id),
    uploaded_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE lab_orders ADD COLUMN IF NOT EXISTS revision INTEGER NOT NULL DEFAULT 0;
ALTER TABLE radiology_orders ADD COLUMN IF NOT EXISTS revision INTEGER NOT NULL DEFAULT 0;
ALTER TABLE radiology_orders ADD COLUMN IF NOT EXISTS review_notes TEXT;
ALTER TABLE radiology_orders ADD COLUMN IF NOT EXISTS scheduled_at TIMESTAMP;
-- These tables are accessed only by the existing authenticated PHP backend.
ALTER TABLE emr_attachments ENABLE ROW LEVEL SECURITY;
ALTER TABLE radiology_image_files ENABLE ROW LEVEL SECURITY;
COMMIT;
