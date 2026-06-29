-- Run this SQL in phpMyAdmin to set up the database (Task 1 + Task 2)

-- Create database
CREATE DATABASE IF NOT EXISTS apex_intern;
USE apex_intern;

-- Create users table with extended fields (Task 2: Create Operation)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- ── If you already created the table in Task 1, run this instead ──
-- ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL AFTER password;
-- ALTER TABLE users ADD COLUMN address VARCHAR(255) DEFAULT NULL AFTER phone;
