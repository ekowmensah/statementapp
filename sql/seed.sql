-- Daily Statement App - Seed Data
USE daily_statement_app;

-- Insert roles
INSERT INTO roles (name) VALUES ('admin'), ('accountant'), ('viewer');

-- Insert admin user (password: admin123 - change in production!)
INSERT INTO users (name, email, password_hash) VALUES
('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- Assign admin role to admin user
INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r 
WHERE u.email='admin@example.com' AND r.name='admin';

-- Insert sample accountant user
INSERT INTO users (name, email, password_hash) VALUES
('John Accountant', 'accountant@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r 
WHERE u.email='accountant@example.com' AND r.name='accountant';

-- Insert sample viewer user
INSERT INTO users (name, email, password_hash) VALUES
('Jane Viewer', 'viewer@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id FROM users u, roles r 
WHERE u.email='viewer@example.com' AND r.name='viewer';

-- Insert initial rates (21% AG1, 4% AG2 effective from 2025-10-01)
INSERT INTO rates (effective_on, rate_ag1, rate_ag2, note, created_by)
VALUES ('2025-10-01', 0.2100, 0.0400, 'Initial rates', 1);

-- Insert sample daily transactions
INSERT INTO daily_txn (txn_date, ca, ga, je, note, created_by) VALUES
('2025-10-01', 3000.00, 1000.00, 840.00, 'JE applied', 1),
('2025-10-02', 2000.00, 1000.00,   0.00, NULL, 1),
('2025-10-03', 1500.00, 1000.00,   0.00, NULL, 1),
('2025-10-06',    0.00, 1000.00,   0.00, 'Payout-only day', 1),
('2025-10-07',    0.00, 1000.00,   0.00, 'Payout-only day', 1),
('2025-10-08', 2500.00, 1000.00, 200.00, 'Small JE adjustment', 1),
('2025-10-09', 1800.00, 1000.00,   0.00, NULL, 1),
('2025-10-10', 2200.00, 1000.00,   0.00, NULL, 1);
