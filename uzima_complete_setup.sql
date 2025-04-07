-- Uzima Enterprise Compensation Management System
-- Complete Database Setup and Sample Data Script
-- This single script creates the database, tables, and loads sample data

-- Drop database if exists to start fresh
DROP DATABASE IF EXISTS uzima_reimbursement;
CREATE DATABASE uzima_reimbursement;
USE uzima_reimbursement;

-- ================================================================
-- TABLE STRUCTURES
-- ================================================================

-- Create Users Table with enhanced fields
CREATE TABLE users (
    userID INT AUTO_INCREMENT PRIMARY KEY,
    employeeID VARCHAR(20) UNIQUE,
    fullName VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    department_id INT,
    position VARCHAR(100),
    role ENUM('Employee', 'Manager', 'FinanceOfficer', 'Admin') NOT NULL DEFAULT 'Employee',
    hire_date DATE,
    contact_number VARCHAR(20),
    profile_image VARCHAR(255) DEFAULT 'assets/images/default_profile.png',
    total_reimbursement DECIMAL(15, 2) DEFAULT 0.00,
    budget_limit DECIMAL(15, 2) DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    last_login DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Department Table
CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) NOT NULL UNIQUE,
    manager_id INT,
    budget_allocation DECIMAL(15, 2) DEFAULT 0.00,
    budget_remaining DECIMAL(15, 2) DEFAULT 0.00,
    fiscal_year_start DATE,
    fiscal_year_end DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (manager_id) REFERENCES users(userID) ON DELETE SET NULL
);

-- Add foreign key to users table
ALTER TABLE users
ADD CONSTRAINT fk_department
FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL;

-- Create Expense Categories Table
CREATE TABLE expense_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    category_code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    max_amount DECIMAL(15, 2) DEFAULT NULL,
    requires_approval_over DECIMAL(15, 2) DEFAULT NULL,
    receipt_required BOOLEAN DEFAULT TRUE,
    is_active BOOLEAN DEFAULT TRUE,
    gl_account VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Policies Table
CREATE TABLE policies (
    policy_id INT AUTO_INCREMENT PRIMARY KEY,
    policy_name VARCHAR(100) NOT NULL,
    policy_description TEXT,
    policy_document VARCHAR(255),
    effective_date DATE,
    expiry_date DATE NULL,
    created_by INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(userID) ON DELETE SET NULL
);

-- Create Enhanced Claims Table
CREATE TABLE claims (
    claimID INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(50) UNIQUE,
    userID INT NOT NULL,
    department_id INT,
    category_id INT NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    description TEXT NOT NULL,
    purpose VARCHAR(255),
    incurred_date DATE NOT NULL,
    receipt_path VARCHAR(255),
    additional_documents TEXT,
    status ENUM('Draft', 'Submitted', 'Under Review', 'Approved', 'Rejected', 'Paid', 'Cancelled') NOT NULL DEFAULT 'Draft',
    submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approval_date DATETIME,
    payment_date DATETIME,
    payment_reference VARCHAR(100),
    approverID INT,
    reviewer_id INT,
    remarks TEXT,
    rejection_reason TEXT,
    policy_id INT,
    tax_amount DECIMAL(15, 2) DEFAULT 0.00,
    billable_to_client BOOLEAN DEFAULT FALSE,
    client_id INT,
    project_id VARCHAR(50),
    is_recurring BOOLEAN DEFAULT FALSE,
    recurrence_pattern VARCHAR(50),
    is_advance BOOLEAN DEFAULT FALSE,
    advance_cleared BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE,
    FOREIGN KEY (approverID) REFERENCES users(userID) ON DELETE SET NULL,
    FOREIGN KEY (reviewer_id) REFERENCES users(userID) ON DELETE SET NULL,
    FOREIGN KEY (category_id) REFERENCES expense_categories(category_id) ON DELETE RESTRICT,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE SET NULL,
    FOREIGN KEY (policy_id) REFERENCES policies(policy_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_submission_date (submission_date),
    INDEX idx_reference (reference_number)
);

-- Create Claim Audit Log Table
CREATE TABLE claim_audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    claimID INT NOT NULL,
    action_type VARCHAR(50) NOT NULL,
    action_details TEXT,
    previous_status VARCHAR(50),
    new_status VARCHAR(50),
    performed_by INT,
    performed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (claimID) REFERENCES claims(claimID) ON DELETE CASCADE,
    FOREIGN KEY (performed_by) REFERENCES users(userID) ON DELETE SET NULL
);

-- Create Budget Tracking Table
CREATE TABLE budget_tracking (
    tracking_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    fiscal_period VARCHAR(20) NOT NULL,
    start_date DATE,
    end_date DATE,
    initial_budget DECIMAL(15, 2) DEFAULT 0.00,
    current_balance DECIMAL(15, 2) DEFAULT 0.00,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(userID) ON DELETE SET NULL
);

-- Create Budget Transactions Table
CREATE TABLE budget_transactions (
    transaction_id INT AUTO_INCREMENT PRIMARY KEY,
    tracking_id INT NOT NULL,
    transaction_type ENUM('Allocation', 'Expense', 'Adjustment', 'Transfer') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    description TEXT,
    related_claim_id INT,
    authorized_by INT,
    FOREIGN KEY (tracking_id) REFERENCES budget_tracking(tracking_id) ON DELETE CASCADE,
    FOREIGN KEY (related_claim_id) REFERENCES claims(claimID) ON DELETE SET NULL,
    FOREIGN KEY (authorized_by) REFERENCES users(userID) ON DELETE SET NULL
);

-- Create Approval Workflow Table
CREATE TABLE approval_workflows (
    workflow_id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_name VARCHAR(100) NOT NULL,
    department_id INT,
    category_id INT,
    min_amount DECIMAL(15, 2) DEFAULT 0.00,
    max_amount DECIMAL(15, 2),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(department_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES expense_categories(category_id) ON DELETE CASCADE
);

-- Create Approval Steps Table
CREATE TABLE approval_steps (
    step_id INT AUTO_INCREMENT PRIMARY KEY,
    workflow_id INT NOT NULL,
    step_order INT NOT NULL,
    approver_role ENUM('Manager', 'FinanceOfficer', 'Admin') NOT NULL,
    specific_approver_id INT,
    is_mandatory BOOLEAN DEFAULT TRUE,
    approval_timeout_hours INT DEFAULT 48,
    escalation_after_hours INT DEFAULT 72,
    escalation_to INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES approval_workflows(workflow_id) ON DELETE CASCADE,
    FOREIGN KEY (specific_approver_id) REFERENCES users(userID) ON DELETE SET NULL,
    FOREIGN KEY (escalation_to) REFERENCES users(userID) ON DELETE SET NULL
);

-- Create Comments Table
CREATE TABLE comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    claimID INT NOT NULL,
    userID INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (claimID) REFERENCES claims(claimID) ON DELETE CASCADE,
    FOREIGN KEY (userID) REFERENCES users(userID) ON DELETE CASCADE
);

-- Create Currencies Table
CREATE TABLE currencies (
    currency_id INT AUTO_INCREMENT PRIMARY KEY,
    currency_code VARCHAR(3) NOT NULL UNIQUE,
    currency_name VARCHAR(50) NOT NULL,
    currency_symbol VARCHAR(5) NOT NULL,
    exchange_rate DECIMAL(15, 6) NOT NULL,
    is_base_currency BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Tax Settings Table
CREATE TABLE tax_settings (
    tax_id INT AUTO_INCREMENT PRIMARY KEY,
    tax_name VARCHAR(50) NOT NULL,
    tax_rate DECIMAL(5, 2) NOT NULL,
    tax_code VARCHAR(20) NOT NULL,
    country VARCHAR(50),
    is_default BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Clients Table
CREATE TABLE clients (
    client_id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(100) NOT NULL,
    client_code VARCHAR(20) UNIQUE,
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    billing_address TEXT,
    tax_identification VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create Projects Table
CREATE TABLE projects (
    project_id INT AUTO_INCREMENT PRIMARY KEY,
    project_code VARCHAR(20) UNIQUE,
    project_name VARCHAR(100) NOT NULL,
    client_id INT,
    budget DECIMAL(15, 2),
    start_date DATE,
    end_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE SET NULL
);

-- Create System Settings Table
CREATE TABLE system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Create User Activity Logs
CREATE TABLE user_activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(100) NOT NULL,
    activity_description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(userID) ON DELETE SET NULL
);

-- Create notifications table
CREATE TABLE notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(50) NOT NULL,
    reference_id INT NULL,
    reference_type VARCHAR(50) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_recipient (recipient_id),
    INDEX idx_read_status (is_read),
    INDEX idx_created_at (created_at),
    CONSTRAINT fk_notification_recipient FOREIGN KEY (recipient_id) REFERENCES users (userID) ON DELETE CASCADE
);

-- Create conversations table
CREATE TABLE conversations (
    conversation_id INT AUTO_INCREMENT PRIMARY KEY,
    user1_id INT NOT NULL,
    user2_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user1 (user1_id),
    INDEX idx_user2 (user2_id),
    INDEX idx_last_message (last_message_at),
    CONSTRAINT fk_conversation_user1 FOREIGN KEY (user1_id) REFERENCES users (userID) ON DELETE CASCADE,
    CONSTRAINT fk_conversation_user2 FOREIGN KEY (user2_id) REFERENCES users (userID) ON DELETE CASCADE
);

-- Create messages table
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    message TEXT NOT NULL,
    sent_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_conversation (conversation_id),
    INDEX idx_sender (sender_id),
    INDEX idx_recipient (recipient_id),
    INDEX idx_read_status (is_read),
    INDEX idx_sent_at (sent_at),
    CONSTRAINT fk_message_conversation FOREIGN KEY (conversation_id) REFERENCES conversations (conversation_id) ON DELETE CASCADE,
    CONSTRAINT fk_message_sender FOREIGN KEY (sender_id) REFERENCES users (userID) ON DELETE CASCADE,
    CONSTRAINT fk_message_recipient FOREIGN KEY (recipient_id) REFERENCES users (userID) ON DELETE CASCADE
);

-- Create claim_approvals table
CREATE TABLE claim_approvals (
    approval_id INT AUTO_INCREMENT PRIMARY KEY,
    claimID INT NOT NULL,
    approver_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    notes TEXT NULL,
    approval_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_claim (claimID),
    INDEX idx_approver (approver_id),
    CONSTRAINT fk_approval_claim FOREIGN KEY (claimID) REFERENCES claims (claimID) ON DELETE CASCADE,
    CONSTRAINT fk_approval_approver FOREIGN KEY (approver_id) REFERENCES users (userID) ON DELETE CASCADE
);

-- ================================================================
-- TRIGGERS
-- ================================================================

-- Add trigger to update last_message_at in conversations table
DELIMITER //
DROP TRIGGER IF EXISTS update_conversation_timestamp //
CREATE TRIGGER update_conversation_timestamp
AFTER INSERT ON messages
FOR EACH ROW
BEGIN
    UPDATE conversations 
    SET last_message_at = NEW.sent_at 
    WHERE conversation_id = NEW.conversation_id;
END//
DELIMITER ; 

-- ================================================================
-- INSERT DEFAULT DATA AND SAMPLE DATA
-- ================================================================

-- Default Departments
INSERT INTO departments (department_name, department_code, budget_allocation, fiscal_year_start, fiscal_year_end) VALUES 
('Executive', 'EXEC', 500000.00, '2023-01-01', '2023-12-31'),
('Finance', 'FIN', 250000.00, '2023-01-01', '2023-12-31'),
('Human Resources', 'HR', 150000.00, '2023-01-01', '2023-12-31'),
('Information Technology', 'IT', 350000.00, '2023-01-01', '2023-12-31'),
('Marketing', 'MKT', 300000.00, '2023-01-01', '2023-12-31'),
('Operations', 'OPS', 400000.00, '2023-01-01', '2023-12-31'),
('Sales', 'SALES', 350000.00, '2023-01-01', '2023-12-31'),
('Research & Development', 'R&D', 450000.00, '2023-01-01', '2023-12-31');

-- Default admin user (Password: Admin@123)
INSERT INTO users (employeeID, fullName, email, password, department_id, position, role, hire_date) VALUES 
('EMP001', 'System Administrator', 'admin@uzima.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'System Administrator', 'Admin', '2023-01-01');

-- Default Finance Officer (Password: Finance@123)
INSERT INTO users (employeeID, fullName, email, password, department_id, position, role, hire_date) VALUES 
('EMP002', 'Finance Officer', 'finance@uzima.com', '$2y$10$okU9VF6H.C/OTBWK8fimCOGKEA4wBKn1eP9Nb0tGnpx76jlT0VH5.', 2, 'Finance Manager', 'FinanceOfficer', '2023-01-15');

-- Default Manager (Password: Manager@123)
INSERT INTO users (employeeID, fullName, email, password, department_id, position, role, hire_date) VALUES 
('EMP003', 'Department Manager', 'manager@uzima.com', '$2y$10$wfDs7xZRHFV/Ju0p7qlfVe.4sF3zDxtAZlM5YuFuCV4VD6R4MYN2.', 4, 'IT Manager', 'Manager', '2023-01-20');

-- Default Employee (Password: Employee@123)
INSERT INTO users (employeeID, fullName, email, password, department_id, position, role, hire_date) VALUES 
('EMP004', 'Regular Employee', 'employee@uzima.com', '$2y$10$uVtqU2ay.X/O57soNyIy8uGGnNrjA0y8r4YXHSXr2t7AdXbQ2Kj82', 4, 'Software Developer', 'Employee', '2023-02-01');

-- Update Department Managers
UPDATE departments SET manager_id = 3 WHERE department_id = 4;  -- IT Department
UPDATE departments SET manager_id = 1 WHERE department_id = 2;  -- Finance Department

-- Default Expense Categories
INSERT INTO expense_categories (category_name, category_code, description, max_amount, requires_approval_over, receipt_required, gl_account) VALUES
('Travel - Airfare', 'T-AIR', 'Flight tickets and related fees', 5000.00, 1000.00, TRUE, '6100-01'),
('Travel - Lodging', 'T-LODGE', 'Hotel and accommodation expenses', 3000.00, 500.00, TRUE, '6100-02'),
('Travel - Meals', 'T-MEAL', 'Food and beverages during business trips', 1000.00, 200.00, TRUE, '6100-03'),
('Travel - Ground Transportation', 'T-TRANS', 'Taxis, trains, rental cars, etc.', 1000.00, 200.00, TRUE, '6100-04'),
('Office Supplies', 'OFF-SUP', 'General office supplies and stationery', 500.00, 100.00, TRUE, '6200-01'),
('IT Equipment', 'IT-EQUIP', 'Computers, peripherals, and accessories', 3000.00, 500.00, TRUE, '6300-01'),
('Software & Subscriptions', 'IT-SOFT', 'Software licenses and online services', 2000.00, 300.00, TRUE, '6300-02'),
('Training & Development', 'TRAIN', 'Courses, certifications, and educational materials', 2500.00, 500.00, TRUE, '6400-01'),
('Conferences & Events', 'CONF', 'Registration fees and related expenses', 3000.00, 1000.00, TRUE, '6400-02'),
('Entertainment', 'ENTERTAIN', 'Client entertainment and business meals', 1000.00, 200.00, TRUE, '6500-01'),
('Marketing & Advertising', 'MKTG', 'Promotional materials and advertising expenses', 5000.00, 1000.00, TRUE, '6600-01'),
('Professional Services', 'PROF-SVC', 'Consulting, legal, and other professional fees', 10000.00, 2000.00, TRUE, '6700-01'),
('Telecommunications', 'TELECOM', 'Mobile phone and internet expenses', 500.00, 100.00, TRUE, '6800-01'),
('Health & Wellness', 'HEALTH', 'Medical expenses and wellness programs', 2000.00, 500.00, TRUE, '6900-01'),
('Miscellaneous', 'MISC', 'Other business-related expenses', 1000.00, 200.00, TRUE, '7000-01');

-- Default Policies
INSERT INTO policies (policy_name, policy_description, effective_date, created_by, is_active) VALUES
('General Expense Policy', 'Guidelines for all business expenses', '2023-01-01', 1, TRUE),
('Travel Policy', 'Rules and procedures for business travel', '2023-01-01', 1, TRUE),
('Per Diem Policy', 'Fixed allowances for travel expenses', '2023-01-01', 1, TRUE),
('Equipment Purchase Policy', 'Guidelines for purchasing business equipment', '2023-01-01', 1, TRUE);

-- Create Default Approval Workflows
INSERT INTO approval_workflows (workflow_name, department_id, category_id, min_amount, max_amount, is_active) VALUES
('Standard Expense Approval', NULL, NULL, 0.00, 1000.00, TRUE),
('High-Value Expense Approval', NULL, NULL, 1000.01, 5000.00, TRUE),
('Executive Approval', NULL, NULL, 5000.01, NULL, TRUE),
('IT Equipment Approval', 4, 6, 0.00, NULL, TRUE);

-- Create Default Approval Steps
INSERT INTO approval_steps (workflow_id, step_order, approver_role, specific_approver_id, is_mandatory) VALUES
(1, 1, 'Manager', NULL, TRUE),
(1, 2, 'FinanceOfficer', 2, TRUE),
(2, 1, 'Manager', NULL, TRUE),
(2, 2, 'FinanceOfficer', 2, TRUE),
(2, 3, 'Admin', 1, TRUE),
(3, 1, 'Manager', NULL, TRUE),
(3, 2, 'FinanceOfficer', 2, TRUE),
(3, 3, 'Admin', 1, TRUE),
(4, 1, 'Manager', 3, TRUE),
(4, 2, 'FinanceOfficer', 2, FALSE);

-- Default Currencies
INSERT INTO currencies (currency_code, currency_name, currency_symbol, exchange_rate, is_base_currency, is_active) VALUES
('USD', 'US Dollar', '$', 1.000000, TRUE, TRUE),
('EUR', 'Euro', '€', 1.120000, FALSE, TRUE),
('GBP', 'British Pound', '£', 1.310000, FALSE, TRUE),
('JPY', 'Japanese Yen', '¥', 0.009120, FALSE, TRUE),
('CAD', 'Canadian Dollar', 'C$', 0.780000, FALSE, TRUE),
('AUD', 'Australian Dollar', 'A$', 0.750000, FALSE, TRUE);

-- Default Tax Settings
INSERT INTO tax_settings (tax_name, tax_rate, tax_code, country, is_default, is_active) VALUES
('No Tax', 0.00, 'NOTAX', 'Global', TRUE, TRUE),
('Standard VAT', 20.00, 'VAT20', 'United Kingdom', FALSE, TRUE),
('Reduced VAT', 5.00, 'VAT5', 'United Kingdom', FALSE, TRUE),
('Sales Tax', 7.00, 'STAX7', 'United States', FALSE, TRUE);

-- Default System Settings
INSERT INTO system_settings (setting_key, setting_value, setting_description, is_public) VALUES
('company_name', 'Uzima Corporation', 'Company name for reports and interfaces', TRUE),
('company_logo', 'assets/images/uzima_logo.png', 'Company logo path', TRUE),
('fiscal_year_start', '01-01', 'Start of fiscal year (MM-DD)', TRUE),
('fiscal_year_end', '12-31', 'End of fiscal year (MM-DD)', TRUE),
('default_currency', 'USD', 'Default system currency', TRUE),
('expense_attachment_size_limit', '10', 'Maximum file size for expense attachments (MB)', TRUE),
('expense_attachment_types', 'jpg,jpeg,png,pdf,doc,docx,xls,xlsx', 'Allowed file types for expense attachments', TRUE),
('approval_reminder_days', '2', 'Days after which to send approval reminders', FALSE),
('session_timeout', '30', 'Session timeout in minutes', FALSE),
('enable_2fa', 'false', 'Enable two-factor authentication', FALSE),
('smtp_host', '', 'SMTP server for email notifications', FALSE),
('smtp_port', '587', 'SMTP port', FALSE),
('smtp_user', '', 'SMTP username', FALSE),
('smtp_password', '', 'SMTP password (encrypted)', FALSE),
('smtp_from_email', 'noreply@uzima.com', 'From email for system notifications', FALSE),
('enable_audit_logs', 'true', 'Enable detailed audit logs', FALSE);

-- Create some sample clients
INSERT INTO clients (client_name, client_code, contact_person, contact_email, is_active) VALUES
('Acme Corporation', 'ACME-001', 'John Smith', 'john.smith@acme.com', TRUE),
('TechNova Solutions', 'TNOVA-001', 'Sarah Johnson', 'sarah.j@technova.com', TRUE),
('Global Industries', 'GLOBAL-001', 'Michael Wong', 'michael.w@globalind.com', TRUE);

-- Create sample projects
INSERT INTO projects (project_code, project_name, client_id, budget, start_date, end_date, is_active) VALUES
('PRJ-2023-001', 'Website Redesign', 1, 75000.00, '2023-03-01', '2023-08-31', TRUE),
('PRJ-2023-002', 'ERP Implementation', 2, 150000.00, '2023-02-15', '2023-12-31', TRUE),
('PRJ-2023-003', 'Mobile App Development', 3, 120000.00, '2023-04-01', '2023-10-31', TRUE);

-- ================================================================
-- SAMPLE CLAIM DATA FOR TESTING
-- ================================================================

-- Sample claims for testing
INSERT INTO claims (reference_number, userID, department_id, category_id, amount, currency, description, purpose, incurred_date, status, submission_date, policy_id)
VALUES
('CLM-2023-00001', 4, 4, 1, 850.75, 'USD', 'Flight to New York conference', 'Attending DevCon 2023', '2023-03-15', 'Approved', '2023-03-20 10:15:00', 2),
('CLM-2023-00002', 4, 4, 2, 625.50, 'USD', 'Hotel stay for 3 nights', 'Attending DevCon 2023', '2023-03-15', 'Approved', '2023-03-20 10:20:00', 2),
('CLM-2023-00003', 4, 4, 6, 1299.99, 'USD', 'New laptop for development', 'Replacement for damaged equipment', '2023-04-05', 'Under Review', '2023-04-07 14:30:00', 4),
('CLM-2023-00004', 3, 4, 9, 1500.00, 'USD', 'Conference registration fee', 'Annual Tech Leadership Summit', '2023-05-10', 'Submitted', '2023-05-12 09:45:00', 1);

-- Additional sample claims with more varied statuses and details
INSERT INTO claims (reference_number, userID, department_id, category_id, amount, currency, description, purpose, incurred_date, status, submission_date, policy_id, approverID)
VALUES
-- Employee claims (userID 4)
('CLM-2023-00005', 4, 4, 3, 120.50, 'USD', 'Team lunch with clients', 'Client relationship building', '2023-04-10', 'Paid', '2023-04-11 13:15:00', 1, 3),
('CLM-2023-00006', 4, 4, 5, 85.75, 'USD', 'Office supplies for project', 'Project materials', '2023-04-20', 'Rejected', '2023-04-21 10:30:00', 1, 3),
('CLM-2023-00007', 4, 4, 7, 199.99, 'USD', 'Software license renewal', 'Annual software subscription', '2023-05-05', 'Approved', '2023-05-06 09:15:00', 4, 3),
('CLM-2023-00008', 4, 4, 13, 75.00, 'USD', 'Internet expenses for remote work', 'Work from home expenses', '2023-05-15', 'Paid', '2023-05-16 11:20:00', 1, 3),
('CLM-2023-00009', 4, 4, 4, 65.50, 'USD', 'Taxi to client meeting', 'Client meeting transportation', '2023-06-02', 'Paid', '2023-06-03 14:45:00', 2, 3),
('CLM-2023-00010', 4, 4, 10, 150.25, 'USD', 'Dinner with potential clients', 'Client acquisition meeting', '2023-06-15', 'Under Review', '2023-06-16 09:30:00', 1, NULL),

-- Manager claims (userID 3)
('CLM-2023-00011', 3, 4, 8, 750.00, 'USD', 'Leadership training course', 'Professional development', '2023-04-15', 'Paid', '2023-04-16 10:00:00', 1, 1),
('CLM-2023-00012', 3, 4, 9, 2500.00, 'USD', 'Tech conference registration and travel', 'Industry conference', '2023-05-01', 'Approved', '2023-05-02 13:45:00', 2, 2),
('CLM-2023-00013', 3, 4, 12, 1200.00, 'USD', 'Consulting services for project', 'External expertise for Project X', '2023-05-20', 'Rejected', '2023-05-21 15:30:00', 1, 1),
('CLM-2023-00014', 3, 4, 7, 399.99, 'USD', 'Premium software subscription', 'Team productivity tools', '2023-06-10', 'Submitted', '2023-06-11 11:15:00', 4, NULL),

-- Finance officer claims (userID 2)
('CLM-2023-00015', 2, 2, 8, 500.00, 'USD', 'Financial certification course', 'Professional development', '2023-04-05', 'Paid', '2023-04-06 09:15:00', 1, 1),
('CLM-2023-00016', 2, 2, 12, 850.00, 'USD', 'Tax advisory services', 'Year-end tax planning', '2023-06-01', 'Under Review', '2023-06-02 14:30:00', 1, NULL),

-- Admin claims (userID 1)
('CLM-2023-00017', 1, 2, 9, 3500.00, 'USD', 'Executive leadership summit', 'Strategic planning conference', '2023-05-10', 'Paid', '2023-05-11 10:00:00', 2, 2),
('CLM-2023-00018', 1, 2, 11, 2000.00, 'USD', 'Company branding materials', 'Corporate identity refresh', '2023-06-05', 'Approved', '2023-06-06 13:45:00', 1, 2);

-- ================================================================
-- SAMPLE CLAIM AUDIT LOGS
-- ================================================================

-- Add claim audit logs for the existing and new claims
INSERT INTO claim_audit_logs (claimID, action_type, action_details, previous_status, new_status, performed_by, performed_at, ip_address)
VALUES
-- For existing claims
(1, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-03-20 10:15:00', '192.168.1.101'),
(1, 'Review', 'Claim reviewed by manager', 'Submitted', 'Under Review', 3, '2023-03-21 09:30:00', '192.168.1.102'),
(1, 'Approval', 'Claim approved by manager', 'Under Review', 'Approved', 3, '2023-03-22 11:45:00', '192.168.1.102'),
(1, 'Final Approval', 'Claim approved by finance', 'Approved', 'Approved', 2, '2023-03-23 14:30:00', '192.168.1.103'),

(2, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-03-20 10:20:00', '192.168.1.101'),
(2, 'Review', 'Claim reviewed by manager', 'Submitted', 'Under Review', 3, '2023-03-21 09:35:00', '192.168.1.102'),
(2, 'Approval', 'Claim approved by manager', 'Under Review', 'Approved', 3, '2023-03-22 11:50:00', '192.168.1.102'),
(2, 'Final Approval', 'Claim approved by finance', 'Approved', 'Approved', 2, '2023-03-23 14:35:00', '192.168.1.103'),

(3, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-04-07 14:30:00', '192.168.1.101'),
(3, 'Review', 'Claim under review by IT manager', 'Submitted', 'Under Review', 3, '2023-04-08 10:15:00', '192.168.1.102'),

(4, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 3, '2023-05-12 09:45:00', '192.168.1.102'),

-- For new claims
(5, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-04-11 13:15:00', '192.168.1.101'),
(5, 'Review', 'Claim reviewed by manager', 'Submitted', 'Under Review', 3, '2023-04-12 10:30:00', '192.168.1.102'),
(5, 'Approval', 'Claim approved by manager', 'Under Review', 'Approved', 3, '2023-04-13 11:45:00', '192.168.1.102'),
(5, 'Final Approval', 'Claim approved by finance', 'Approved', 'Approved', 2, '2023-04-14 14:30:00', '192.168.1.103'),
(5, 'Payment', 'Payment processed', 'Approved', 'Paid', 2, '2023-04-15 10:00:00', '192.168.1.103'),

(6, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-04-21 10:30:00', '192.168.1.101'),
(6, 'Review', 'Claim reviewed by manager', 'Submitted', 'Under Review', 3, '2023-04-22 09:15:00', '192.168.1.102'),
(6, 'Rejection', 'Claim rejected - insufficient documentation', 'Under Review', 'Rejected', 3, '2023-04-23 11:30:00', '192.168.1.102'),

(7, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-05-06 09:15:00', '192.168.1.101'),
(7, 'Review', 'Claim reviewed by manager', 'Submitted', 'Under Review', 3, '2023-05-07 10:45:00', '192.168.1.102'),
(7, 'Approval', 'Claim approved by manager', 'Under Review', 'Approved', 3, '2023-05-08 13:30:00', '192.168.1.102'),

(8, 'Submission', 'Claim submitted for approval', 'Draft', 'Submitted', 4, '2023-05-16 11:20:00', '192.168.1.101'),
(8, 'Review', 'Claim reviewed by manager', 'Submitted', 'Under Review', 3, '2023-05-17 09:30:00', '192.168.1.102'),
(8, 'Approval', 'Claim approved by manager', 'Under Review', 'Approved', 3, '2023-05-18 14:15:00', '192.168.1.102'),
(8, 'Final Approval', 'Claim approved by finance', 'Approved', 'Approved', 2, '2023-05-19 10:45:00', '192.168.1.103'),
(8, 'Payment', 'Payment processed', 'Approved', 'Paid', 2, '2023-05-20 11:30:00', '192.168.1.103');

-- ================================================================
-- SAMPLE CLAIM APPROVALS
-- ================================================================

-- Add claim approvals for all claims that have gone through approval process
INSERT INTO claim_approvals (claimID, approver_id, status, notes, approval_date)
VALUES
-- Approved claims with manager approval
(1, 3, 'Approved', 'Approved for reimbursement as per travel policy', '2023-03-22 11:45:00'),
(2, 3, 'Approved', 'Approved for reimbursement as per travel policy', '2023-03-22 11:50:00'),
(5, 3, 'Approved', 'Approved - valid business expense', '2023-04-13 11:45:00'),
(7, 3, 'Approved', 'Approved - necessary software for job function', '2023-05-08 13:30:00'),
(8, 3, 'Approved', 'Approved - justified WFH expense', '2023-05-18 14:15:00'),
(9, 3, 'Approved', 'Approved for reimbursement as per travel policy', '2023-06-04 10:30:00'),
(11, 1, 'Approved', 'Approved - aligns with professional development policy', '2023-04-17 11:15:00'),
(12, 2, 'Approved', 'Approved - important industry event', '2023-05-03 14:30:00'),
(17, 2, 'Approved', 'Approved - strategic importance for organization', '2023-05-12 11:30:00'),
(18, 2, 'Approved', 'Approved - within marketing budget allocation', '2023-06-07 15:00:00'),

-- Finance officer approvals (second level)
(1, 2, 'Approved', 'Verified receipts and approved', '2023-03-23 14:30:00'),
(2, 2, 'Approved', 'Verified receipts and approved', '2023-03-23 14:35:00'),
(5, 2, 'Approved', 'Verified and approved for payment', '2023-04-14 14:30:00'),
(8, 2, 'Approved', 'Verified and approved for payment', '2023-05-19 10:45:00'),
(9, 2, 'Approved', 'Verified and approved for payment', '2023-06-05 09:30:00'),
(11, 2, 'Approved', 'Verified and approved for payment', '2023-04-18 10:30:00'),
(17, 1, 'Approved', 'Reviewed and authorized for payment', '2023-05-13 13:15:00'),

-- Rejected claims
(6, 3, 'Rejected', 'Rejected - Missing itemized receipts for some items', '2023-04-23 11:30:00'),
(13, 1, 'Rejected', 'Rejected - Exceeds approved budget for consulting services', '2023-05-22 14:15:00');

-- ================================================================
-- SAMPLE NOTIFICATIONS
-- ================================================================

-- Add notifications for various claim events
INSERT INTO notifications (recipient_id, title, message, notification_type, reference_id, reference_type, is_read, created_at)
VALUES
-- For employee (userID 4)
(4, 'Claim Approved', 'Your claim CLM-2023-00001 has been approved by your manager', 'Approval', 1, 'claim', 1, '2023-03-22 11:45:00'),
(4, 'Claim Approved', 'Your claim CLM-2023-00001 has been fully approved and processed', 'Approval', 1, 'claim', 1, '2023-03-23 14:30:00'),
(4, 'Claim Approved', 'Your claim CLM-2023-00002 has been approved by your manager', 'Approval', 2, 'claim', 1, '2023-03-22 11:50:00'),
(4, 'Claim Approved', 'Your claim CLM-2023-00002 has been fully approved and processed', 'Approval', 2, 'claim', 1, '2023-03-23 14:35:00'),
(4, 'Claim Under Review', 'Your claim CLM-2023-00003 is under review by your manager', 'Review', 3, 'claim', 1, '2023-04-08 10:15:00'),
(4, 'Claim Rejected', 'Your claim CLM-2023-00006 has been rejected - see comments for details', 'Rejection', 6, 'claim', 1, '2023-04-23 11:30:00'),
(4, 'Claim Approved', 'Your claim CLM-2023-00007 has been approved by your manager', 'Approval', 7, 'claim', 1, '2023-05-08 13:30:00'),
(4, 'Claim Payment', 'Your claim CLM-2023-00005 has been paid', 'Payment', 5, 'claim', 0, '2023-04-15 10:00:00'),
(4, 'Claim Payment', 'Your claim CLM-2023-00008 has been paid', 'Payment', 8, 'claim', 0, '2023-05-20 11:30:00'),
(4, 'Claim Payment', 'Your claim CLM-2023-00009 has been paid', 'Payment', 9, 'claim', 0, '2023-06-06 10:15:00'),

-- For manager (userID 3)
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00003 for your review', 'Submission', 3, 'claim', 1, '2023-04-07 14:30:00'),
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00005 for your review', 'Submission', 5, 'claim', 1, '2023-04-11 13:15:00'),
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00006 for your review', 'Submission', 6, 'claim', 1, '2023-04-21 10:30:00'),
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00007 for your review', 'Submission', 7, 'claim', 1, '2023-05-06 09:15:00'),
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00008 for your review', 'Submission', 8, 'claim', 1, '2023-05-16 11:20:00'),
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00009 for your review', 'Submission', 9, 'claim', 1, '2023-06-03 14:45:00'),
(3, 'New Claim Submitted', 'Employee Regular Employee has submitted claim CLM-2023-00010 for your review', 'Submission', 10, 'claim', 0, '2023-06-16 09:30:00'),
(3, 'Claim Approved', 'Your claim CLM-2023-00011 has been approved and processed for payment', 'Approval', 11, 'claim', 1, '2023-04-18 10:30:00'),
(3, 'Claim Approved', 'Your claim CLM-2023-00012 has been approved by finance', 'Approval', 12, 'claim', 1, '2023-05-03 14:30:00'),
(3, 'Claim Rejected', 'Your claim CLM-2023-00013 has been rejected - see comments for details', 'Rejection', 13, 'claim', 1, '2023-05-22 14:15:00'),

-- For finance officer (userID 2)
(2, 'Claim Pending Finance Approval', 'Claim CLM-2023-00001 has been approved by manager and requires your review', 'Pending Approval', 1, 'claim', 1, '2023-03-22 11:45:00'),
(2, 'Claim Pending Finance Approval', 'Claim CLM-2023-00002 has been approved by manager and requires your review', 'Pending Approval', 2, 'claim', 1, '2023-03-22 11:50:00'),
(2, 'Claim Pending Finance Approval', 'Claim CLM-2023-00005 has been approved by manager and requires your review', 'Pending Approval', 5, 'claim', 1, '2023-04-13 11:45:00'),
(2, 'Claim Pending Finance Approval', 'Claim CLM-2023-00008 has been approved by manager and requires your review', 'Pending Approval', 8, 'claim', 1, '2023-05-18 14:15:00'),
(2, 'Claim Pending Finance Approval', 'Claim CLM-2023-00009 has been approved by manager and requires your review', 'Pending Approval', 9, 'claim', 1, '2023-06-04 10:30:00'),
(2, 'Claim Approved', 'Your claim CLM-2023-00015 has been approved and processed for payment', 'Approval', 15, 'claim', 1, '2023-04-07 11:30:00'),

-- For admin (userID 1)
(1, 'Claim Pending Executive Approval', 'High-value claim CLM-2023-00013 requires your review', 'Pending Approval', 13, 'claim', 1, '2023-05-21 16:00:00'),
(1, 'Claim Pending Executive Approval', 'High-value claim CLM-2023-00017 requires your review', 'Pending Approval', 17, 'claim', 1, '2023-05-12 10:15:00'),
(1, 'Claim Approved', 'Your claim CLM-2023-00017 has been approved and processed for payment', 'Approval', 17, 'claim', 1, '2023-05-13 13:15:00'),
(1, 'Claim Pending Finance Approval', 'Your claim CLM-2023-00018 requires finance review', 'Pending Approval', 18, 'claim', 0, '2023-06-06 13:45:00');

-- ================================================================
-- SAMPLE CONVERSATIONS AND MESSAGES
-- ================================================================

-- Add some sample conversations between users
INSERT INTO conversations (conversation_id, user1_id, user2_id, created_at, last_message_at)
VALUES
(1, 4, 3, '2023-04-08 11:30:00', '2023-04-08 14:45:00'),
(2, 4, 2, '2023-04-15 09:15:00', '2023-04-15 16:20:00'),
(3, 3, 2, '2023-05-02 10:30:00', '2023-05-03 11:15:00'),
(4, 3, 1, '2023-05-22 13:45:00', '2023-05-22 15:30:00');

-- Add sample messages within those conversations
INSERT INTO messages (conversation_id, sender_id, recipient_id, message, sent_at, is_read)
VALUES
-- Conversation between employee and manager about laptop claim
(1, 4, 3, 'Hello, I submitted a claim for a new laptop. Could you please review it when you have time?', '2023-04-08 11:30:00', 1),
(1, 3, 4, 'I see your claim. Can you provide more details about why the current laptop needs replacement?', '2023-04-08 13:15:00', 1),
(1, 4, 3, 'My current laptop has a cracked screen and the battery only lasts about 30 minutes. It\'s over 4 years old.', '2023-04-08 13:45:00', 1),
(1, 3, 4, 'Thank you for the details. I\'ll review your claim today.', '2023-04-08 14:45:00', 1),

-- Conversation between employee and finance about paid claim
(2, 4, 2, 'Hi, I noticed my claim for the team lunch was approved but I haven\'t received the reimbursement yet. Any update?', '2023-04-15 09:15:00', 1),
(2, 2, 4, 'Let me check the status for you.', '2023-04-15 10:30:00', 1),
(2, 2, 4, 'I\'ve processed the payment. It should appear in your next paycheck on the 30th.', '2023-04-15 11:45:00', 1),
(2, 4, 2, 'Great, thank you for the update!', '2023-04-15 16:20:00', 1),

-- Conversation between manager and finance about budget
(3, 3, 2, 'Hello, I need to check the remaining IT department budget for equipment purchases.', '2023-05-02 10:30:00', 1),
(3, 2, 3, 'The IT department has $125,000 remaining in the equipment budget for this fiscal year.', '2023-05-02 14:15:00', 1),
(3, 3, 2, 'Thanks! I need to approve a high-value claim for a new development server.', '2023-05-03 09:30:00', 1),
(3, 2, 3, 'If it exceeds $5,000, please remember it will need administrative approval as well.', '2023-05-03 11:15:00', 1),

-- Conversation between manager and admin about rejected claim
(4, 3, 1, 'I have a team member who needs consulting services that exceeded our budget. Can we make an exception?', '2023-05-22 13:45:00', 1),
(4, 1, 3, 'Can you provide details on why this consulting service is necessary?', '2023-05-22 14:30:00', 1),
(4, 3, 1, 'We need specialized security expertise for the new client project that we don\'t have in-house.', '2023-05-22 15:00:00', 1),
(4, 1, 3, 'I see. Please have them resubmit with a detailed business case and I\'ll reconsider.', '2023-05-22 15:30:00', 1);

-- ================================================================
-- SAMPLE USER ACTIVITY LOGS
-- ================================================================

-- Add user activity logs to show system usage
INSERT INTO user_activity_logs (user_id, activity_type, activity_description, ip_address, created_at)
VALUES
-- User logins
(1, 'Login', 'User logged into the system', '192.168.1.100', '2023-03-15 08:30:00'),
(2, 'Login', 'User logged into the system', '192.168.1.103', '2023-03-15 09:15:00'),
(3, 'Login', 'User logged into the system', '192.168.1.102', '2023-03-15 09:30:00'),
(4, 'Login', 'User logged into the system', '192.168.1.101', '2023-03-15 10:00:00'),

-- Claim submissions
(4, 'Claim Submission', 'User submitted claim CLM-2023-00001', '192.168.1.101', '2023-03-20 10:15:00'),
(4, 'Claim Submission', 'User submitted claim CLM-2023-00002', '192.168.1.101', '2023-03-20 10:20:00'),
(4, 'Claim Submission', 'User submitted claim CLM-2023-00003', '192.168.1.101', '2023-04-07 14:30:00'),
(3, 'Claim Submission', 'User submitted claim CLM-2023-00004', '192.168.1.102', '2023-05-12 09:45:00'),

-- Claim reviews and approvals
(3, 'Claim Review', 'User reviewed claim CLM-2023-00001', '192.168.1.102', '2023-03-21 09:30:00'),
(3, 'Claim Approval', 'User approved claim CLM-2023-00001', '192.168.1.102', '2023-03-22 11:45:00'),
(2, 'Claim Approval', 'User approved claim CLM-2023-00001', '192.168.1.103', '2023-03-23 14:30:00'),
(2, 'Payment Processing', 'User processed payment for claim CLM-2023-00001', '192.168.1.103', '2023-03-25 10:15:00'),

-- Report generation
(2, 'Report Generation', 'User generated monthly expense report', '192.168.1.103', '2023-04-01 14:30:00'),
(1, 'Report Generation', 'User generated department budget report', '192.168.1.100', '2023-04-05 11:15:00'),
(3, 'Report Generation', 'User generated team expense report', '192.168.1.102', '2023-04-10 15:45:00'),

-- Settings changes
(1, 'System Settings', 'User updated expense attachment size limit', '192.168.1.100', '2023-04-18 13:20:00'),
(1, 'User Management', 'User added new department', '192.168.1.100', '2023-05-01 10:45:00'),
(2, 'Budget Management', 'User updated department budget allocations', '192.168.1.103', '2023-05-05 14:30:00');

-- End of script
-- If you're seeing this message, the script completed successfully! 