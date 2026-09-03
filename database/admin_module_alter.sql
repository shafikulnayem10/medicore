-- =====================================================================
--  MediCore — Admin Module: small additive migration
--  Run this ONCE in phpMyAdmin (SQL tab) on top of your existing DB.
--  This ONLY adds nullable/defaulted columns — it does not drop or
--  rename anything, and does not touch any existing rows/data.
--  Safe to run even if login/register/other modules are already live.
-- =====================================================================

USE medicore;

ALTER TABLE `user`
    ADD COLUMN `phone` VARCHAR(20) NULL AFTER `email`;

ALTER TABLE `doctor`
    ADD COLUMN `gender` VARCHAR(20) NULL AFTER `qualification`,
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'Active' AFTER `experience`;

ALTER TABLE `receptionist`
    ADD COLUMN `gender` VARCHAR(20) NULL AFTER `employee_code`,
    ADD COLUMN `shift`  VARCHAR(50) NULL AFTER `gender`,
    ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'Active' AFTER `shift`;
