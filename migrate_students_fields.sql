-- Migration: Add applicant fields to students table
-- Run each statement individually; ignore errors for columns that already exist

ALTER TABLE students ADD COLUMN lrn VARCHAR(12) NULL AFTER student_number;
ALTER TABLE students ADD COLUMN place_of_birth VARCHAR(200) NULL;
ALTER TABLE students ADD COLUMN civil_status VARCHAR(20) NULL;
ALTER TABLE students ADD COLUMN religion VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN nationality VARCHAR(50) NULL;
ALTER TABLE students ADD COLUMN disability VARCHAR(200) NULL;
ALTER TABLE students ADD COLUMN married_name VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN perm_region VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN perm_province VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN perm_municipality VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN perm_barangay VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN perm_address TEXT NULL;
ALTER TABLE students ADD COLUMN perm_zipcode VARCHAR(10) NULL;
ALTER TABLE students ADD COLUMN mail_region VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN mail_province VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN mail_municipality VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN mail_barangay VARCHAR(100) NULL;
ALTER TABLE students ADD COLUMN mail_address TEXT NULL;
ALTER TABLE students ADD COLUMN mail_zipcode VARCHAR(10) NULL;
ALTER TABLE students ADD COLUMN doc_form138 VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN doc_birth_cert VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN doc_good_moral VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN doc_our_au001 VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN doc_our_au002 VARCHAR(255) NULL;
ALTER TABLE students ADD COLUMN applicant_id INT NULL;
