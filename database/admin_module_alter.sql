
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

    CREATE TABLE `wards` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ward_name` varchar(100) NOT NULL,
  `ward_type` varchar(50) NOT NULL,
  `total_beds` int(11) NOT NULL DEFAULT 0,
  `occupied_beds` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
