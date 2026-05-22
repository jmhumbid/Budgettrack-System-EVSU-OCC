# PPMP PDF Download Table Name Fix

## Issue
When clicking the download button for PPMP, the following error occurred:
```
Error generating PDF: SQLSTATE[42S02]: Base table or view not found: 1146 
Table 'budgettrack_db.project_procurement_plans' doesn't exist
```

## Root Cause
The `api/download_ppmp_pdf.php` file was using incorrect table names:
- Used: `project_procurement_plans` (doesn't exist)
- Actual: `ppmp` (correct table name)
- Used: `project_procurement_items` (doesn't exist)
- Actual: `ppmp_items` (correct table name)

## Solution
Updated the SQL queries in `api/download_ppmp_pdf.php` to use the correct table names:

### Before:
```php
SELECT p.*, d.dept_name, d.dept_code
FROM project_procurement_plans p
LEFT JOIN departments d ON p.department_id = d.id
WHERE p.id = ?

SELECT * FROM project_procurement_items 
WHERE ppmp_id = ? 
ORDER BY id
```

### After:
```php
SELECT p.*, d.dept_name, d.dept_code
FROM ppmp p
LEFT JOIN departments d ON p.department_id = d.id
WHERE p.id = ?

SELECT * FROM ppmp_items 
WHERE ppmp_id = ? 
ORDER BY sort_order, id
```

## Database Schema Reference
From `database/ppmp_table.sql`:
- Main table: `ppmp`
- Items table: `ppmp_items`
- History table: `ppmp_history`

The `ppmp_type` column (added via `database/supplemental_ppmp.sql`) correctly distinguishes between regular PPMP and Supplemental PPMP.

## Files Modified
1. `api/download_ppmp_pdf.php` - Fixed table names in SQL queries

## Result
- PPMP PDF download now works correctly
- Supplemental PPMP PDF download also works (uses same table)
- PDFs generate with proper data and formatting
