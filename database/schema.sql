

CREATE DATABASE IF NOT EXISTS medicore;
USE medicore;


CREATE TABLE user (
    user_id     INT AUTO_INCREMENT PRIMARY KEY,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(100) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- store hashed password (password_hash())
    user_type   ENUM('Doctor', 'Receptionist', 'Patient', 'Admin') NOT NULL,
    created_at  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);


CREATE TABLE authentication (
    auth_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT NOT NULL,
    login_time  DATETIME,
    logout_time DATETIME,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


CREATE TABLE doctor (
    doctor_id      INT AUTO_INCREMENT PRIMARY KEY,
    user_id        INT NOT NULL UNIQUE,
    specialization VARCHAR(100),
    qualification  VARCHAR(100),
    experience     INT,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


CREATE TABLE receptionist (
    receptionist_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NOT NULL UNIQUE,
    employee_code   VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


CREATE TABLE patient (
    patient_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL UNIQUE,
    dob        DATE,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


CREATE TABLE admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id  INT NOT NULL UNIQUE,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


CREATE TABLE work_bed (
    bed_id   INT AUTO_INCREMENT PRIMARY KEY,
    bed_info VARCHAR(100)
);


CREATE TABLE appointment (
    appointment_id   INT AUTO_INCREMENT PRIMARY KEY,
    patient_id       INT NOT NULL,
    doctor_id        INT NOT NULL,
    receptionist_id  INT,                       
    bed_id           INT,                        
    appointment_date DATETIME NOT NULL,
    status           ENUM('Pending', 'Confirmed', 'Completed', 'Cancelled') DEFAULT 'Pending',
    FOREIGN KEY (patient_id)      REFERENCES patient(patient_id)           ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)       REFERENCES doctor(doctor_id)             ON DELETE CASCADE,
    FOREIGN KEY (receptionist_id) REFERENCES receptionist(receptionist_id) ON DELETE SET NULL,
    FOREIGN KEY (bed_id)          REFERENCES work_bed(bed_id)              ON DELETE SET NULL
);


CREATE TABLE prescription (
    prescription_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id  INT NOT NULL,
    patient_id      INT NOT NULL,
    doctor_id       INT NOT NULL,
    medication      TEXT,
    instructions    TEXT,
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id)     REFERENCES patient(patient_id)         ON DELETE CASCADE,
    FOREIGN KEY (doctor_id)      REFERENCES doctor(doctor_id)           ON DELETE CASCADE
);


CREATE TABLE lab_test_request (
    lab_request_id INT AUTO_INCREMENT PRIMARY KEY,
    appointment_id INT NOT NULL,
    patient_id     INT NOT NULL,
    test_type      VARCHAR(100),
    notes          TEXT,
    requested_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id)     REFERENCES patient(patient_id)         ON DELETE CASCADE
);


CREATE TABLE lab_test_result (
    lab_result_id  INT AUTO_INCREMENT PRIMARY KEY,
    lab_request_id INT NOT NULL UNIQUE,
    result_data    TEXT,
    result_file    VARCHAR(255),
    result_date    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lab_request_id) REFERENCES lab_test_request(lab_request_id) ON DELETE CASCADE
);


CREATE TABLE billing (
    billing_id     INT AUTO_INCREMENT PRIMARY KEY,
    patient_id     INT NOT NULL,
    appointment_id INT NOT NULL,
    amount         DECIMAL(10,2) NOT NULL,
    payment_status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
    generated_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (patient_id)     REFERENCES patient(patient_id)         ON DELETE CASCADE,
    FOREIGN KEY (appointment_id) REFERENCES appointment(appointment_id) ON DELETE CASCADE
);




INSERT INTO user (full_name, email, password, user_type) VALUES
('Dr. Anika Rahman', 'anika.doctor@medicore.com', '$2y$10$examplehashvalueusepasswordhash', 'Doctor'),
('Sadia Islam', 'sadia.reception@medicore.com', '$2y$10$examplehashvalueusepasswordhash', 'Receptionist'),
('Karim Uddin', 'karim.patient@medicore.com', '$2y$10$examplehashvalueusepasswordhash', 'Patient'),
('Rafiq Ahmed', 'rafiq.admin@medicore.com', '$2y$10$examplehashvalueusepasswordhash', 'Admin');

INSERT INTO doctor (user_id, specialization, qualification, experience) VALUES (1, 'Cardiology', 'MBBS, FCPS', 8);
INSERT INTO receptionist (user_id, employee_code) VALUES (2, 'REC-001');
INSERT INTO patient (user_id, dob) VALUES (3, '1998-05-14');
INSERT INTO admin (user_id) VALUES (4);

INSERT INTO work_bed (bed_info) VALUES ('Ward A - Bed 1'), ('Ward A - Bed 2'), ('Ward B - Bed 1');