USE ramtech_db;

ALTER TABLE users
  MODIFY role ENUM('client','staff','admin') NOT NULL DEFAULT 'client';
