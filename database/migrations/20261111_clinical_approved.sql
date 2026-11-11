-- Additive follow-up to 20261110_modules_3_8_9.sql; preserve all existing records.
BEGIN;
ALTER TABLE lab_orders ADD COLUMN IF NOT EXISTS validated_at TIMESTAMP;
ALTER TABLE lab_orders ADD COLUMN IF NOT EXISTS validated_by INTEGER REFERENCES users(id);
ALTER TABLE lab_orders ADD COLUMN IF NOT EXISTS saved_by INTEGER REFERENCES users(id);
ALTER TABLE lab_orders ADD COLUMN IF NOT EXISTS review_notes TEXT;
CREATE TABLE IF NOT EXISTS clinical_order_batches (
    batch_key VARCHAR(80) PRIMARY KEY,
    appointment_id INTEGER NOT NULL REFERENCES appointments(id),
    request_json JSONB NOT NULL,
    result_json JSONB NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
ALTER TABLE clinical_order_batches ENABLE ROW LEVEL SECURITY;
COMMIT;
