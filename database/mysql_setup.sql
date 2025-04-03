-- MySQL Database Setup for Uzima Expense Management System

-- Create users table
CREATE TABLE IF NOT EXISTS users (
    userID INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    fullName VARCHAR(100) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'Employee',
    employeeID VARCHAR(50),
    department_id INT,
    contact VARCHAR(20),
    profile_picture VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    last_login DATETIME,
    status VARCHAR(20) DEFAULT 'Active'
);

-- Create departments table
CREATE TABLE IF NOT EXISTS departments (
    department_id INT PRIMARY KEY AUTO_INCREMENT,
    department_name VARCHAR(100) NOT NULL,
    department_code VARCHAR(20) NOT NULL,
    manager_id INT,
    budget DECIMAL(10,2),
    FOREIGN KEY (manager_id) REFERENCES users(userID)
);

-- Create expense categories
CREATE TABLE IF NOT EXISTS expense_categories (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(100) NOT NULL,
    description TEXT,
    budget_limit DECIMAL(10,2),
    approval_threshold DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Active'
);

-- Create claims table
CREATE TABLE IF NOT EXISTS claims (
    claimID INT PRIMARY KEY AUTO_INCREMENT,
    reference_number VARCHAR(20) NOT NULL UNIQUE,
    userID INT NOT NULL,
    department_id INT,
    category_id INT,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'KES',
    description TEXT NOT NULL,
    purpose VARCHAR(255) NOT NULL,
    incurred_date DATE NOT NULL,
    submission_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    receipt_path VARCHAR(255),
    status VARCHAR(20) DEFAULT 'Draft',
    project_id INT,
    mpesa_code VARCHAR(50),
    billable_to_client TINYINT DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES users(userID),
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(category_id)
);

-- Create claim_approvals table
CREATE TABLE IF NOT EXISTS claim_approvals (
    approval_id INT PRIMARY KEY AUTO_INCREMENT,
    claim_id INT NOT NULL,
    approver_id INT NOT NULL,
    step_id INT,
    status VARCHAR(20) DEFAULT 'Pending',
    comments TEXT,
    approved_at DATETIME,
    FOREIGN KEY (claim_id) REFERENCES claims(claimID),
    FOREIGN KEY (approver_id) REFERENCES users(userID)
);

-- Create approval_workflows table
CREATE TABLE IF NOT EXISTS approval_workflows (
    workflow_id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_name VARCHAR(100) NOT NULL,
    description TEXT,
    department_id INT,
    category_id INT,
    amount_threshold DECIMAL(10,2),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(category_id)
);

-- Create approval_steps table
CREATE TABLE IF NOT EXISTS approval_steps (
    step_id INT PRIMARY KEY AUTO_INCREMENT,
    workflow_id INT NOT NULL,
    step_order INT NOT NULL,
    approver_role VARCHAR(50),
    approver_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (workflow_id) REFERENCES approval_workflows(workflow_id),
    FOREIGN KEY (approver_id) REFERENCES users(userID)
);

-- Create projects table
CREATE TABLE IF NOT EXISTS projects (
    project_id INT PRIMARY KEY AUTO_INCREMENT,
    project_name VARCHAR(100) NOT NULL,
    project_code VARCHAR(20) NOT NULL,
    client_id INT,
    start_date DATE,
    end_date DATE,
    budget DECIMAL(10,2),
    status VARCHAR(20) DEFAULT 'Active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Create clients table
CREATE TABLE IF NOT EXISTS clients (
    client_id INT PRIMARY KEY AUTO_INCREMENT,
    client_name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    status VARCHAR(20) DEFAULT 'Active'
);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    recipient_id INT NOT NULL,
    sender_id INT,
    title VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    notification_type VARCHAR(50),
    related_id INT,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (recipient_id) REFERENCES users(userID),
    FOREIGN KEY (sender_id) REFERENCES users(userID)
);

-- Create policies table
CREATE TABLE IF NOT EXISTS policies (
    policy_id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    department_id INT,
    category_id INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME,
    status VARCHAR(20) DEFAULT 'Active',
    FOREIGN KEY (department_id) REFERENCES departments(department_id),
    FOREIGN KEY (category_id) REFERENCES expense_categories(category_id)
);

-- Create user_tokens table for password reset
CREATE TABLE IF NOT EXISTS user_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    userID INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    token_type VARCHAR(20) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME NOT NULL,
    is_used TINYINT DEFAULT 0,
    FOREIGN KEY (userID) REFERENCES users(userID)
);

-- Create messages table for direct messaging
CREATE TABLE IF NOT EXISTS messages (
    message_id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message_text TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(userID),
    FOREIGN KEY (recipient_id) REFERENCES users(userID)
);

-- Create indexes for performance
CREATE INDEX idx_claims_user ON claims(userID);
CREATE INDEX idx_claims_department ON claims(department_id);
CREATE INDEX idx_claims_category ON claims(category_id);
CREATE INDEX idx_claims_status ON claims(status);
CREATE INDEX idx_approvals_claim ON claim_approvals(claim_id);
CREATE INDEX idx_approvals_approver ON claim_approvals(approver_id);
CREATE INDEX idx_notifications_recipient ON notifications(recipient_id);
CREATE INDEX idx_messages_sender ON messages(sender_id);
CREATE INDEX idx_messages_recipient ON messages(recipient_id);

-- Create message_replies table for threaded conversations
CREATE TABLE IF NOT EXISTS message_replies (
    reply_id INT PRIMARY KEY AUTO_INCREMENT,
    message_id INT NOT NULL,
    sender_id INT NOT NULL,
    reply_text TEXT NOT NULL,
    is_read TINYINT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (message_id) REFERENCES messages(message_id),
    FOREIGN KEY (sender_id) REFERENCES users(userID)
);

-- Create index for message replies
CREATE INDEX idx_replies_message ON message_replies(message_id);

-- Insert default admin user
INSERT INTO users (username, password, email, fullName, role) 
VALUES ('admin', '$2y$10$8zUm0JTxzXakXCTJZgj8XuZdGl8Zw8T3TsAv6Z4vZtMzg3LAaUY1e', 'admin@uzima.com', 'Admin User', 'Admin');

-- Insert default departments
INSERT INTO departments (department_name, department_code) VALUES
('Finance', 'FIN'),
('Human Resources', 'HR'),
('Information Technology', 'IT'),
('Operations', 'OPS'),
('Marketing', 'MKT'),
('Sales', 'SALES'),
('Research and Development', 'R&D'),
('Customer Service', 'CS'),
('Legal', 'LGL'),
('Administration', 'ADMIN');

-- Insert default expense categories
INSERT INTO expense_categories (category_name, description) VALUES
('Transport', 'Transportation expenses including taxi, bus, train'),
('Accommodation', 'Hotel and lodging expenses during business trips'),
('Meals', 'Food and dining expenses during business activities'),
('Office Supplies', 'Stationery and other office consumables'),
('Communication', 'Phone, internet and other communication expenses'),
('Training', 'Course fees, seminar registration and training materials'),
('Entertainment', 'Client entertainment and business meetings'),
('Equipment', 'Small equipment and tools under capitalization threshold'),
('Software', 'Software purchases and subscriptions'),
('Other', 'Miscellaneous expenses not fitting other categories');

-- Insert sample projects
INSERT INTO projects (project_name, project_code) VALUES
('Website Redesign', 'PRJ001'),
('Annual Audit', 'PRJ002'),
('Market Research', 'PRJ003'),
('Product Launch', 'PRJ004'),
('Staff Training', 'PRJ005');

-- Insert sample clients
INSERT INTO clients (client_name, contact_person, email, phone) VALUES
('ABC Corporation', 'John Smith', 'jsmith@abccorp.com', '+1-555-123-4567'),
('XYZ Industries', 'Jane Doe', 'jane.doe@xyzind.com', '+1-555-987-6543'),
('Acme Enterprises', 'Robert Johnson', 'rjohnson@acme.com', '+1-555-456-7890'),
('Global Solutions', 'Sarah Williams', 'swilliams@globalsol.com', '+1-555-789-0123'),
('Tech Innovations', 'Michael Brown', 'mbrown@techinno.com', '+1-555-234-5678');

-- Insert default approval workflow
INSERT INTO approval_workflows (workflow_name, description) VALUES
('Standard Approval', 'Default workflow for expense approvals');

-- Insert approval steps for the default workflow
INSERT INTO approval_steps (workflow_id, step_order, approver_role)
VALUES (1, 1, 'Manager'), (1, 2, 'FinanceOfficer'); 