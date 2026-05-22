-- Add supplemental support to PPMP tables

-- Add ppmp_type column to distinguish between regular PPMP and Supplemental
ALTER TABLE ppmp 
ADD COLUMN ppmp_type ENUM('ppmp', 'supplemental') DEFAULT 'ppmp' AFTER ppmp_number;

-- Add index for better query performance
ALTER TABLE ppmp 
ADD INDEX idx_ppmp_type (ppmp_type);

-- Add index for department and type combination
ALTER TABLE ppmp 
ADD INDEX idx_dept_type (department_id, ppmp_type, fiscal_year);

-- Update existing records to be 'ppmp' type (if column doesn't exist yet)
UPDATE ppmp SET ppmp_type = 'ppmp' WHERE ppmp_type IS NULL;
