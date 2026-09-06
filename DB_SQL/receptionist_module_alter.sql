
USE medicore;

ALTER TABLE `appointment`
    ADD COLUMN `reason` VARCHAR(150) NULL AFTER `doctor_id`;

ALTER TABLE `patient`
    ADD COLUMN `gender` VARCHAR(20) NULL AFTER `dob`,
    ADD COLUMN `medical_condition` VARCHAR(150) NULL AFTER `gender`;

ALTER TABLE `billing`
    ADD COLUMN `services` TEXT NULL AFTER `amount`;
