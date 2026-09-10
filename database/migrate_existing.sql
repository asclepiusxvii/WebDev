USE ramtech_db;

ALTER TABLE service_requests
    ADD COLUMN IF NOT EXISTS technician_name VARCHAR(150) NULL AFTER status;

ALTER TABLE request_updates
    ADD COLUMN IF NOT EXISTS technician_name VARCHAR(150) NULL AFTER status;

ALTER TABLE users
  MODIFY role ENUM('client','staff','admin') NOT NULL DEFAULT 'client';
