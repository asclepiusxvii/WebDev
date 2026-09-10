USE ramtech_db;

-- Safe migration for projects created before technician assignment was added.
ALTER TABLE service_requests
    ADD COLUMN IF NOT EXISTS technician_name VARCHAR(150) NULL AFTER status;

ALTER TABLE request_updates
    ADD COLUMN IF NOT EXISTS technician_name VARCHAR(150) NULL AFTER status;


-- Add staff/employee account role
ALTER TABLE users
  MODIFY role ENUM('client','staff','admin') NOT NULL DEFAULT 'client';
