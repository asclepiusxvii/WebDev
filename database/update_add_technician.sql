USE ramtech_db;

-- Run this only if your existing database was created before technician assignment was added.
-- If the columns already exist, the application will detect them automatically and no action is needed.

ALTER TABLE service_requests
ADD COLUMN IF NOT EXISTS technician_name VARCHAR(150) NULL AFTER status;

ALTER TABLE request_updates
ADD COLUMN IF NOT EXISTS technician_name VARCHAR(150) NULL AFTER status;
