-- Add departments/offices to the database
-- Ensure fiduciary_type column exists
ALTER TABLE departments 
ADD COLUMN IF NOT EXISTS fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code;

-- Insert FIDUCIARY departments
INSERT INTO departments (dept_name, dept_code, fiduciary_type) VALUES
('SSG', 'SSG', 'Fiduciary'),
('Guidance Office', 'GUID', 'Fiduciary'),
('Culture and Arts', 'C&A', 'Fiduciary'),
('IGP Production Office', 'IGP', 'Fiduciary'),
('Library', 'LIB', 'Fiduciary')
ON DUPLICATE KEY UPDATE dept_name = VALUES(dept_name), fiduciary_type = VALUES(fiduciary_type);

-- Insert NON-FIDUCIARY departments
INSERT INTO departments (dept_name, dept_code, fiduciary_type) VALUES
('Research', 'RES', 'Non-Fiduciary'),
('Admin', 'ADMIN', 'Non-Fiduciary'),
('Extension Services', 'EXT', 'Non-Fiduciary')
ON DUPLICATE KEY UPDATE dept_name = VALUES(dept_name), fiduciary_type = VALUES(fiduciary_type);

