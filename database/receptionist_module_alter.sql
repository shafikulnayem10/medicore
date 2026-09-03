-- =====================================================================
--  MediCore — Receptionist Module: small additive migration
--  Run this ONCE in phpMyAdmin (SQL tab), same rules as before:
--  only ADDS nullable columns, drops/renames/deletes nothing, and does
--  not touch any existing rows. Safe even if the app is already live.
-- =====================================================================

USE medicore;

ALTER TABLE `appointment`
    ADD COLUMN `reason` VARCHAR(150) NULL AFTER `doctor_id`;

ALTER TABLE `patient`
    ADD COLUMN `gender` VARCHAR(20) NULL AFTER `dob`,
    ADD COLUMN `medical_condition` VARCHAR(150) NULL AFTER `gender`;

ALTER TABLE `billing`
    ADD COLUMN `services` TEXT NULL AFTER `amount`;
