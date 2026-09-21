DROP DATABASE IF EXISTS barangay_system;
CREATE DATABASE barangay_system;
USE barangay_system;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('resident', 'staff', 'admin') NOT NULL DEFAULT 'resident',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE residents (
    resident_id     INT AUTO_INCREMENT PRIMARY KEY,
    user_id         INT NULL UNIQUE,
    first_name      VARCHAR(100) NOT NULL,
    last_name       VARCHAR(100) NOT NULL,
    address         VARCHAR(255) NOT NULL,
    contact_number  VARCHAR(20)  NOT NULL,
    status          ENUM('pending', 'verified', 'rejected') NOT NULL DEFAULT 'pending',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_residents_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE document_types (
    document_id       INT AUTO_INCREMENT PRIMARY KEY,
    document_number   VARCHAR(50)  NOT NULL UNIQUE,
    document_name      VARCHAR(100) NOT NULL,  
    file_path         VARCHAR(255) NULL, 
    processing_days   INT NOT NULL DEFAULT 1
);

CREATE TABLE requests (
    request_id    INT AUTO_INCREMENT PRIMARY KEY,
    resident_id   INT NOT NULL,
    document_id   INT NOT NULL,
    processed_by  INT NULL,  
    request_date  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status        VARCHAR(30) NOT NULL DEFAULT 'Pending',
    remarks       TEXT NULL,

    CONSTRAINT fk_requests_resident
        FOREIGN KEY (resident_id) REFERENCES residents(resident_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_requests_document
        FOREIGN KEY (document_id) REFERENCES document_types(document_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    CONSTRAINT fk_requests_user
        FOREIGN KEY (processed_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE SET NULL
);

CREATE TABLE request_history (
    history_id   INT AUTO_INCREMENT PRIMARY KEY,
    request_id   INT NOT NULL,
    status       VARCHAR(30) NOT NULL,
    remarks      TEXT NULL,
    updated_by   INT NOT NULL,   -- FK to users
    updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_history_request
        FOREIGN KEY (request_id) REFERENCES requests(request_id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_history_user
        FOREIGN KEY (updated_by) REFERENCES users(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);

CREATE INDEX idx_requests_status        ON requests(status);
CREATE INDEX idx_requests_resident      ON requests(resident_id);
CREATE INDEX idx_requests_document      ON requests(document_id);
CREATE INDEX idx_history_request        ON request_history(request_id);
CREATE INDEX idx_residents_name         ON residents(last_name, first_name);

-- password_hash = password
INSERT INTO users (name, email, password_hash, role) VALUES
('admin', 'admin@test.com', '$2a$12$AZe01M15Hb2IYJeKMLVC/.rT5ODqB.eQ5BOioc3V.cYmh0.zA5gwy', 'admin'),
('staff01', 'staff01@test.com', '$2a$12$AZe01M15Hb2IYJeKMLVC/.rT5ODqB.eQ5BOioc3V.cYmh0.zA5gwy', 'staff'),
('Juan Dela Cruz', 'resident@test.com', '$2a$12$AZe01M15Hb2IYJeKMLVC/.rT5ODqB.eQ5BOioc3V.cYmh0.zA5gwy', 'resident');

INSERT INTO document_types (document_number, document_name, file_path, processing_days) VALUES
('DOC-001', 'Barangay Clearance',        '/templates/barangay_clearance.docx',        1),
('DOC-002', 'Certificate of Indigency',  '/templates/certificate_of_indigency.docx',  2),
('DOC-003', 'Barangay Business Permit',  '/templates/business_permit.docx',           3),
('DOC-004', 'Certificate of Residency',  '/templates/certificate_of_residency.docx',  1);

INSERT INTO residents (user_id, first_name, last_name, address, contact_number, status) VALUES
(3,    'Juan',  'Dela Cruz', '123 Mabini St., Brgy. San Isidro', '09171234567', 'verified'),
(NULL, 'Maria', 'Santos',    '456 Rizal Ave., Brgy. San Isidro',  '09281234567', 'verified');

INSERT INTO requests (resident_id, document_id, processed_by, status, remarks) VALUES
(1, 1, 2, 'Pending',   NULL),
(2, 2, NULL, 'Pending', NULL);

INSERT INTO request_history (request_id, status, remarks, updated_by) VALUES
(1, 'Pending', 'Request submitted and awaiting review.', 2);
