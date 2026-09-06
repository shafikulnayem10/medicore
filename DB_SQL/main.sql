CREATE DATABASE IF NOT EXISTS medicore;
USE medicore;

-- Drop tables if they exist 
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS billing, lab_test_result, lab_test_request, prescription, appointment, wards, work_bed, admin, patient, receptionist, doctor, authentication, user;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Base User Table
CREATE TABLE user (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    phone       VARCHAR(20) NULL,
    password    VARCHAR(255) NOT NULL,
    user_type   ENUM('Doctor', 'Receptionist', 'Patient', 'Admin') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. Authentication Log Table
CREATE TABLE authentication (
    auth_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    login_time  DATETIME,
    logout_time DATETIME,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 3. Doctor Table
CREATE TABLE doctor (
    doctor_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    specialization VARCHAR(100),
    qualification  VARCHAR(100),
    gender         VARCHAR(20) NULL,
    experience     INT,
    status         VARCHAR(20) NOT NULL DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 4. Receptionist Table
CREATE TABLE receptionist (
    receptionist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    employee_code   VARCHAR(50),
    gender          VARCHAR(20) NULL,
    shift           VARCHAR(50) NULL,
    status          VARCHAR(20) NOT NULL DEFAULT 'Active',
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 5. Patient Table
CREATE TABLE patient (
    patient_id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id           INT NOT NULL UNIQUE,
    dob               DATE,
    gender            VARCHAR(20) NULL,
    medical_condition VARCHAR(150) NULL,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 6. Admin Table
CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 7. Work Bed Table
CREATE TABLE work_bed (
    bed_id   INT AUTO_INCREMENT PRIMARY KEY,
    bed_info VARCHAR(100)
);

-- 8. Wards Table
CREATE TABLE wards (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    ward_name     VARCHAR(100) NOT NULL,
    ward_type     VARCHAR(50) NOT NULL,
    total_beds    INT NOT NULL DEFAULT 0,
    occupied_beds INT NOT NULL DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 9. Appointment Table
CREATE TABLE appointment (
    appointment_id   INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT NOT NULL,
    doctor_id        INT NOT NULL,
    reason           VARCHAR(150) NULL,
    receptionist_id  INT,
    bed_id           INT,
    appointment_date DATETIME NOT NULL,
    status           ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (patient_id)       REFERENCES patient(patient_id)           ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)        REFERENCES doctor(doctor_id)            ON DELETE CASCADE,
    FOREIGN KEY (receptionist_id) REFERENCES receptionist(receptionist_id) ON DELETE SET NULL,
    FOREIGN KEY (bed_id)          REFERENCES work_bed(bed_id)               ON DELETE SET NULL
);

-- 10. Prescription Table
CREATE TABLE prescription (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT NOT NULL,
    patient_id       INT NOT NULL,
    doctor_id        INT NOT NULL,
    medication       TEXT,
    instructions     TEXT,
    created_at       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id)     REFERENCES patient(patient_id)     ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)      REFERENCES doctor(doctor_id)      ON DELETE CASCADE
);

-- 11. Lab Test Request Table
CREATE TABLE lab_test_request (
    lab_request_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id     INT NOT NULL,
    test_type      VARCHAR(100),
    notes          TEXT,
    requested_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id)     REFERENCES patient(patient_id)     ON DELETE CASCADE
);

-- 12. Lab Test Result Table
CREATE TABLE lab_test_result (
    lab_result_id  INT AUTO_INCREMENT PRIMARY KEY,
    lab_request_id INT NOT NULL UNIQUE,
    result_data    TEXT,
    result_file    VARCHAR(255),
    result_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_request_id) REFERENCES lab_test_request(lab_request_id) ON DELETE CASCADE
);

-- 13. Billing Table
CREATE TABLE billing (
    billing_id     INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT NOT NULL,
    appointment_id INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    services       TEXT NULL,
    payment_status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
    generated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)     REFERENCES patient(patient_id)     ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE
);


-- DUMMY DATA INSERTIONS 


-- 1. INSERT USERS (Password: password123)
INSERT INTO user (user_id, full_name, email, phone, password, user_type) VALUES
-- Admin Users
(1, 'System Admin 1', 'admin1@medicore.com', '01700000001', 'password123', 'Admin'),
(2, 'System Admin 2', 'admin2@medicore.com', '01700000002', 'password123', 'Admin'),
(3, 'System Admin 3', 'admin3@medicore.com', '01700000003', 'password123', 'Admin'),

-- Doctor Users
(4, 'Dr. Anika Rahman', 'doctor1@medicore.com', '01800000001', 'password123', 'Doctor'),
(5, 'Dr. Mahmud Hassan', 'doctor2@medicore.com', '01800000002', 'password123', 'Doctor'),
(6, 'Dr. Nusrat Jahan', 'doctor3@medicore.com', '01800000003', 'password123', 'Doctor'),
(7, 'Dr. Tanvir Hossain', 'doctor4@medicore.com', '01800000004', 'password123', 'Doctor'),

-- Receptionist Users
(8, 'Sadia Islam', 'receptionist1@medicore.com', '01900000001', 'password123', 'Receptionist'),
(9, 'Naimur Rahman', 'receptionist2@medicore.com', '01900000002', 'password123', 'Receptionist'),
(10, 'Farhana Chowdhury', 'receptionist3@medicore.com', '01900000003', 'password123', 'Receptionist'),

-- Patient Users
(11, 'Karim Uddin', 'patient1@medicore.com', '01500000001', 'password123', 'Patient'),
(12, 'Rahim Mia', 'patient2@medicore.com', '01500000002', 'password123', 'Patient'),
(13, 'Sultana Begum', 'patient3@medicore.com', '01500000003', 'password123', 'Patient'),
(14, 'Tariqul Islam', 'patient4@medicore.com', '01500000004', 'password123', 'Patient');

-- 2. ROLE SPECIFIC PROFILES
INSERT INTO admin (user_id) VALUES (1), (2), (3);

INSERT INTO doctor (user_id, specialization, qualification, gender, experience, status) VALUES
(4, 'Cardiology', 'MBBS, FCPS (Cardiology)', 'Female', 8, 'Active'),
(5, 'Neurology', 'MBBS, MD (Neurology)', 'Male', 12, 'Active'),
(6, 'Pediatrics', 'MBBS, DCH', 'Female', 5, 'Active'),
(7, 'Orthopedics', 'MBBS, MS (Orthopedics)', 'Male', 10, 'Active');

INSERT INTO receptionist (user_id, employee_code, gender, shift, status) VALUES
(8, 'REC-001', 'Female', 'Morning', 'Active'),
(9, 'REC-002', 'Male', 'Evening', 'Active'),
(10, 'REC-003', 'Female', 'Night', 'Active');

INSERT INTO patient (user_id, dob, gender, medical_condition) VALUES
(11, '1998-05-14', 'Male', 'Hypertension'),
(12, '1985-08-22', 'Male', 'Diabetes Type 2'),
(13, '1992-12-05', 'Female', 'Asthma'),
(14, '2001-03-30', 'Male', 'Seasonal Allergy');

-- 3. WARDS & BEDS
INSERT INTO wards (ward_name, ward_type, total_beds, occupied_beds) VALUES
('General Ward A', 'General', 10, 2),
('ICU Ward 1', 'ICU', 5, 1),
('Cabin Block B', 'Private Cabin', 8, 1);

INSERT INTO work_bed (bed_info) VALUES
('Ward A - Bed 1'),
('Ward A - Bed 2'),
('ICU - Bed 1'),
('Cabin B - Room 101');

-- 4. APPOINTMENTS
INSERT INTO appointment (patient_id, doctor_id, reason, receptionist_id, bed_id, appointment_date, status) VALUES
(1, 1, 'Regular Heart Checkup', 1, 1, '2026-09-10 10:00:00', 'Confirmed'),
(2, 2, 'Severe Migraine Headache', 2, NULL, '2026-09-11 11:30:00', 'Pending'),
(3, 3, 'Child Vaccination & Checkup', 1, NULL, '2026-09-08 09:00:00', 'Completed'),
(4, 4, 'Knee Joint Pain', 3, 4, '2026-09-12 16:00:00', 'Confirmed');

-- 5. PRESCRIPTIONS
INSERT INTO prescription (appointment_id, patient_id, doctor_id, medication, instructions) VALUES
(3, 3, 3, 'Paracetamol Syrup 120mg/5ml', '5ml 3 times a day after meals for 3 days');

-- 6. LAB REQUESTS & RESULTS
INSERT INTO lab_test_request (appointment_id, patient_id, test_type, notes) VALUES
(1, 1, 'ECG & Lipid Profile', 'Check cholesterol levels and heart rhythm'),
(2, 2, 'Brain MRI', 'Check for persistent migraine root cause');

INSERT INTO lab_test_result (lab_request_id, result_data, result_file) VALUES
(1, 'ECG normal sinus rhythm. Cholesterol slightly elevated.', 'reports/ecg_patient1.pdf');

-- 7. BILLING
INSERT INTO billing (patient_id, appointment_id, amount, services, payment_status) VALUES
(1, 1, 1500.00, 'Doctor Consultation + ECG', 'Paid'),
(2, 2, 800.00, 'Doctor Consultation', 'Unpaid'),
(3, 3, 1200.00, 'Consultation + Vaccine', 'Paid'),
(4, 4, 2500.00, 'Consultation + Cabin Allocation', 'Unpaid');