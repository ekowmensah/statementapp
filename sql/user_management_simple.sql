-- User Management Setup - Simplified Version
-- This script sets up the user management system without foreign key constraints initially

-- =====================================================================
-- Create users table (if not exists)
-- =====================================================================
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_active (is_active)
);

-- =====================================================================
-- Create roles table (if not exists)
-- =====================================================================
CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
);

-- =====================================================================
-- Create permissions table (if not exists)
-- =====================================================================
CREATE TABLE IF NOT EXISTS permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    category VARCHAR(50) DEFAULT 'general',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    INDEX idx_category (category)
);

-- =====================================================================
-- Create user_roles junction table (if not exists)
-- =====================================================================
CREATE TABLE IF NOT EXISTS user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_role (user_id, role_id),
    INDEX idx_user_id (user_id),
    INDEX idx_role_id (role_id)
);

-- =====================================================================
-- Create role_permissions junction table (if not exists)
-- =====================================================================
CREATE TABLE IF NOT EXISTS role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_permission (role_id, permission_id),
    INDEX idx_role_id (role_id),
    INDEX idx_permission_id (permission_id)
);

-- =====================================================================
-- Insert default permissions
-- =====================================================================
INSERT IGNORE INTO permissions (name, description, category) VALUES
-- User Management
('manage_users', 'Full user management access', 'users'),
('create_users', 'Create new users', 'users'),
('edit_users', 'Edit existing users', 'users'),
('delete_users', 'Delete users', 'users'),
('view_users', 'View user list', 'users'),

-- Daily Transactions
('view_daily', 'View daily transactions', 'transactions'),
('create_daily', 'Create daily transactions', 'transactions'),
('edit_daily', 'Edit daily transactions', 'transactions'),
('delete_daily', 'Delete daily transactions', 'transactions'),
('manage_daily', 'Full daily transaction management', 'transactions'),

-- Reports
('view_reports', 'View reports', 'reports'),
('create_reports', 'Create reports', 'reports'),
('export_reports', 'Export reports', 'reports'),
('manage_reports', 'Full report management', 'reports'),

-- Statements
('view_statements', 'View statements', 'statements'),
('create_statements', 'Create statements', 'statements'),
('export_statements', 'Export statements', 'statements'),
('manage_statements', 'Full statement management', 'statements'),

-- Rates Management
('view_rates', 'View rates', 'rates'),
('edit_rates', 'Edit rates', 'rates'),
('manage_rates', 'Full rate management', 'rates'),

-- Month Locks
('view_locks', 'View month locks', 'locks'),
('manage_locks', 'Manage month locks', 'locks'),

-- System Administration
('system_admin', 'Full system administration', 'system'),
('view_logs', 'View system logs', 'system'),
('manage_settings', 'Manage system settings', 'system');

-- =====================================================================
-- Insert default roles (without description first, then update)
-- =====================================================================
INSERT IGNORE INTO roles (name) VALUES
('Super Admin'),
('Admin'),
('Manager'),
('User'),
('Viewer');

-- Add description column if it doesn't exist (ignore error if it exists)
-- ALTER TABLE roles ADD COLUMN description TEXT AFTER name;

-- Update roles with descriptions (run this after adding the column if needed)
-- UPDATE roles SET description = 'Full system access with all permissions' WHERE name = 'Super Admin';
-- UPDATE roles SET description = 'Administrative access with most permissions' WHERE name = 'Admin';
-- UPDATE roles SET description = 'Management level access for daily operations' WHERE name = 'Manager';
-- UPDATE roles SET description = 'Standard user access for basic operations' WHERE name = 'User';
-- UPDATE roles SET description = 'Read-only access to view data' WHERE name = 'Viewer';

-- =====================================================================
-- Assign permissions to roles
-- =====================================================================

-- Super Admin gets all permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Super Admin';

-- Admin gets most permissions (excluding some system admin functions)
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Admin'
AND p.name IN (
    'manage_users', 'create_users', 'edit_users', 'view_users',
    'manage_daily', 'view_daily', 'create_daily', 'edit_daily', 'delete_daily',
    'manage_reports', 'view_reports', 'create_reports', 'export_reports',
    'manage_statements', 'view_statements', 'create_statements', 'export_statements',
    'manage_rates', 'view_rates', 'edit_rates',
    'manage_locks', 'view_locks',
    'view_logs'
);

-- Manager gets operational permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Manager'
AND p.name IN (
    'view_users',
    'manage_daily', 'view_daily', 'create_daily', 'edit_daily',
    'view_reports', 'create_reports', 'export_reports',
    'view_statements', 'create_statements', 'export_statements',
    'view_rates', 'edit_rates',
    'view_locks'
);

-- User gets basic permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'User'
AND p.name IN (
    'view_daily', 'create_daily', 'edit_daily',
    'view_reports', 'export_reports',
    'view_statements', 'create_statements',
    'view_rates'
);

-- Viewer gets read-only permissions
INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r
CROSS JOIN permissions p
WHERE r.name = 'Viewer'
AND p.name IN (
    'view_daily',
    'view_reports',
    'view_statements',
    'view_rates',
    'view_locks'
);

-- =====================================================================
-- Create default admin user (if no users exist)
-- =====================================================================
INSERT INTO users (name, email, password_hash, is_active)
SELECT 'System Administrator', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1
WHERE NOT EXISTS (SELECT 1 FROM users LIMIT 1);

-- Assign Super Admin role to the default admin user
INSERT IGNORE INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
CROSS JOIN roles r
WHERE u.email = 'admin@example.com'
AND r.name = 'Super Admin';

-- =====================================================================
-- Show setup summary
-- =====================================================================
SELECT 'User Management Setup Complete' as status;
SELECT COUNT(*) as total_permissions FROM permissions;
SELECT COUNT(*) as total_roles FROM roles;
SELECT COUNT(*) as total_users FROM users;
SELECT 
    r.name as role_name,
    COUNT(rp.permission_id) as permission_count
FROM roles r
LEFT JOIN role_permissions rp ON r.id = rp.role_id
GROUP BY r.id, r.name
ORDER BY permission_count DESC;
