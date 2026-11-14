-- install.sql
CREATE DATABASE IF NOT EXISTS kaldisbp_ict;
USE kaldisbp_ict;

-- Settings table
CREATE TABLE IF NOT EXISTS hr_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(50) NOT NULL,
    value VARCHAR(255) NOT NULL,
    chat_id VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Internship Requests
CREATE TABLE IF NOT EXISTS hr_internship_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    to_branch VARCHAR(100) NOT NULL,
    work_branch VARCHAR(100) NOT NULL,
    job VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    pdf_path VARCHAR(500),
    reference_status VARCHAR(50) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Qualification Records
CREATE TABLE IF NOT EXISTS hr_qualifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    branch VARCHAR(100) NOT NULL,
    job VARCHAR(100) NOT NULL,
    probation_start DATE NOT NULL,
    manager_name VARCHAR(100),
    manager_rank VARCHAR(100),
    destination VARCHAR(100),
    pdf_path VARCHAR(500),
    reference_status VARCHAR(50) DEFAULT 'Pending',
    internship_ref_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Recruitment Notifications
CREATE TABLE IF NOT EXISTS hr_recruitments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    gender VARCHAR(20) NOT NULL,
    branch VARCHAR(100) NOT NULL,
    requested_job VARCHAR(100) NOT NULL,
    assigned_job VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    destination VARCHAR(100),
    pdf_path VARCHAR(500),
    qualification_ref_id VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- System config
CREATE TABLE IF NOT EXISTS hr_system_config (
    config_key VARCHAR(100) PRIMARY KEY,
    config_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default data
INSERT INTO hr_settings (type, value) VALUES
('branch', 'Bole'), ('branch', 'Piassa'), ('branch', 'Merkato'), ('branch', 'CMC'),
('branch', 'Kazanchis'), ('branch', 'Saris'), ('branch', 'Gulen'), ('branch', 'Megenagna'),
('branch', '4 Kilo'), ('branch', 'Goro'),
('job', 'Barista'), ('job', 'Cashier'), ('job', 'Waiter'), ('job', 'Cook'),
('job', 'Cleaner'), ('job', 'Supervisor'), ('job', 'Manager'), ('job', 'Driver'),
('job', 'Guard'), ('job', 'Accountant'),
('rank', 'Branch Manager'), ('rank', 'Supervisor'), ('rank', 'Team Leader'),
('rank', 'Senior Staff'), ('rank', 'Junior Staff'),
('logo', 'https://via.placeholder.com/150?text=Logo');

INSERT INTO hr_system_config (config_key, config_value) VALUES
('bot_token', '');

-- Users table to store login credentials
CREATE TABLE IF NOT EXISTS hr_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(50) NOT NULL DEFAULT 'User',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);