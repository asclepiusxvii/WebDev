CREATE DATABASE IF NOT EXISTS ramtech_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ramtech_db;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  email VARCHAR(190) NOT NULL UNIQUE,
  phone VARCHAR(50) DEFAULT NULL,
  password VARCHAR(255) NOT NULL,
  role ENUM('client','admin') NOT NULL DEFAULT 'client',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE service_requests (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  service_type VARCHAR(100) NOT NULL,
  device_type VARCHAR(100) NOT NULL,
  brand VARCHAR(100) DEFAULT NULL,
  model VARCHAR(150) DEFAULT NULL,
  issue_description TEXT NOT NULL,
  service_method ENUM('Drop-off','On-site') NOT NULL,
  status ENUM('Pending','Accepted','Device Received','Diagnosing','In Progress','Ready for Pickup','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  technician_name VARCHAR(150) DEFAULT NULL,
  technician_notes TEXT DEFAULT NULL,
  estimated_completion DATE DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_request_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE request_updates (
  id INT AUTO_INCREMENT PRIMARY KEY,
  request_id INT NOT NULL,
  status VARCHAR(100) NOT NULL,
  technician_name VARCHAR(150) DEFAULT NULL,
  notes TEXT DEFAULT NULL,
  updated_by INT DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_update_request FOREIGN KEY (request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
  CONSTRAINT fk_update_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Default administrator
-- Email: admin@ramtech.local
-- Password: admin123
INSERT INTO users (first_name,last_name,email,phone,password,role)
VALUES ('RamTech','Administrator','admin@ramtech.local','',
'$2y$12$QzUELPuuOeAFCsQuigfPqusqVdWJ5k1IXore7U.Fg4NLtKBqmzssq','admin');
