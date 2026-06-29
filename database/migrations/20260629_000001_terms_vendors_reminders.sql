CREATE TABLE IF NOT EXISTS vendors (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  contact_name VARCHAR(190) NULL,
  email VARCHAR(190) NULL,
  phone VARCHAR(80) NULL,
  website VARCHAR(190) NULL,
  billing_address TEXT NULL,
  tax_number VARCHAR(120) NULL,
  notes TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL,
  deleted_at TIMESTAMP NULL DEFAULT NULL,
  INDEX idx_vendors_name (name),
  INDEX idx_vendors_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO vendors (name)
SELECT DISTINCT TRIM(vendor)
FROM expenses
WHERE TRIM(vendor) <> ''
  AND NOT EXISTS (
    SELECT 1 FROM vendors WHERE LOWER(vendors.name) = LOWER(TRIM(expenses.vendor)) AND vendors.deleted_at IS NULL
  );

ALTER TABLE expenses
  ADD COLUMN vendor_id BIGINT UNSIGNED NULL AFTER id,
  ADD INDEX idx_expenses_vendor (vendor_id),
  ADD CONSTRAINT fk_expenses_vendor FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE SET NULL;

UPDATE expenses
INNER JOIN vendors ON LOWER(vendors.name) = LOWER(TRIM(expenses.vendor)) AND vendors.deleted_at IS NULL
SET expenses.vendor_id = vendors.id
WHERE expenses.vendor_id IS NULL;

ALTER TABLE invoices
  ADD COLUMN last_reminder_sent_at TIMESTAMP NULL DEFAULT NULL AFTER paid_at;

INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
('default_invoice_terms', 'Payment is due by the due date shown on this invoice.');
