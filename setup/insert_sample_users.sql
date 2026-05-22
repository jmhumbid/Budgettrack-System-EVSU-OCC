-- Sample Users SQL Insert Statements
-- Password for all users: @Skypian#000
-- Generated password hash: $2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe

-- First, get the role IDs (run this query first to see your role IDs)
-- SELECT id, role_name FROM roles;

-- BUDGET OFFICE ACCOUNTS
-- Insert first budget user (created_by = NULL)
INSERT INTO users (email, password_hash, first_name, last_name, employee_id, role_id, is_active, created_by) VALUES
('budget@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Budget', 'Office', 'BUDGET001', (SELECT id FROM roles WHERE role_name = 'budget' LIMIT 1), 1, NULL);

-- Insert second budget user (created_by references the first budget user)
SET @budget_user_id = (SELECT id FROM users WHERE email = 'budget@test.edu' LIMIT 1);
INSERT INTO users (email, password_hash, first_name, last_name, employee_id, role_id, is_active, created_by) VALUES
('budget1@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Budget', 'Office 1', 'BUDGET002', (SELECT id FROM roles WHERE role_name = 'budget' LIMIT 1), 1, @budget_user_id);

-- PROCUREMENT ACCOUNTS
INSERT INTO users (email, password_hash, first_name, last_name, employee_id, role_id, is_active, created_by) VALUES
('bac@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Procurement', 'Office', 'BAC001', (SELECT id FROM roles WHERE role_name = 'procurement' LIMIT 1), 1, @budget_user_id),
('bac1@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Procurement', 'Office 1', 'BAC002', (SELECT id FROM roles WHERE role_name = 'procurement' LIMIT 1), 1, @budget_user_id);

-- SUPPLY OFFICE ACCOUNTS
INSERT INTO users (email, password_hash, first_name, last_name, employee_id, role_id, is_active, created_by) VALUES
('supply@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Supply', 'Office', 'SUPPLY001', (SELECT id FROM roles WHERE role_name = 'supply_office' LIMIT 1), 1, @budget_user_id),
('supply1@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Supply', 'Office 1', 'SUPPLY002', (SELECT id FROM roles WHERE role_name = 'supply_office' LIMIT 1), 1, @budget_user_id);

-- DEPARTMENT ACCOUNTS
-- Note: You may need to assign department_id to these users later
INSERT INTO users (email, password_hash, first_name, last_name, employee_id, role_id, is_active, created_by) VALUES
('dept1@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'One', 'DEPT001', (SELECT id FROM roles WHERE role_name = 'offices' LIMIT 1), 1, @budget_user_id),
('dept2@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Two', 'DEPT002', (SELECT id FROM roles WHERE role_name = 'offices' LIMIT 1), 1, @budget_user_id),
('dept3@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Three', 'DEPT003', (SELECT id FROM roles WHERE role_name = 'offices' LIMIT 1), 1, @budget_user_id),
('dept4@test.edu', '$2y$10$c.gpR7rfH9V3LUo5cDCHneELg5ylrJi55qb0f2z4dEoi0qeytdKwe', 'Department', 'Four', 'DEPT004', (SELECT id FROM roles WHERE role_name = 'offices' LIMIT 1), 1, @budget_user_id);

