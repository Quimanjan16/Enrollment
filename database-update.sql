-- Run in phpMyAdmin if you already imported an older schema
USE school_enrollment;

ALTER TABLE payments
    ADD COLUMN verification_status ENUM('pending', 'verified') DEFAULT 'pending' AFTER notes;

ALTER TABLE payments
    ADD COLUMN verified_by INT NULL AFTER verification_status;

ALTER TABLE payments
    ADD COLUMN verified_at TIMESTAMP NULL AFTER verified_by;

ALTER TABLE payments
    ADD CONSTRAINT fk_payments_verified_by FOREIGN KEY (verified_by) REFERENCES users(id);
