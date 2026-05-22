# LIB Undefined Fields Fix

## Issue
When viewing LIB details in `ppmp_view.php`, several fields were showing as "undefined":
- LIB Number: showing "undefined"
- Particular column: showing "undefined"

## Root Cause

### 1. Missing `lib_number` Column
The `line_item_budgets` table does not have a `lib_number` column in the database schema. The code was trying to access `lib.lib_number` which doesn't exist.

### 2. Wrong Column Name for Items
The code was accessing `item.particular` but the database column is named `particulars` (plural).

## Solution

### 1. Generate LIB Number Dynamically
Instead of storing a lib_number in the database, we now generate it dynamically using:
- Department code (from `dept_code` column)
- Fiscal year
- LIB ID (padded to 3 digits)

Format: `{DEPT_CODE}-LIB-{YEAR}-{ID}`
Example: `CS-LIB-2026-001`

**Updated Functions:**
- `displayLIBs()` - Generates lib_number for list display
- `generateLIBViewHTML()` - Generates lib_number for detail view

```javascript
// Generate LIB number using dept_code if available
let deptCode = lib.dept_code || '';
if (!deptCode && lib.dept_name) {
    deptCode = lib.dept_name.split(' ').map(word => word.charAt(0).toUpperCase()).join('').substring(0, 4);
}
const libNumber = `${deptCode}-LIB-${lib.fiscal_year}-${String(lib.id).padStart(3, '0')}`;
```

### 2. Fixed Column Name
Changed `item.particular` to `item.particulars` to match the database schema.

```javascript
// Before
<td>${item.particular}</td>

// After
<td>${item.particulars || ''}</td>
```

### 3. Added dept_code to API Response
Updated `api/get_lib_list.php` to include `dept_code` in the SELECT query:

```php
$sql = "SELECT l.*, d.dept_name, d.dept_code, CONCAT(u.first_name, ' ', u.last_name) as created_by_name,
        (SELECT SUM(amount) FROM line_item_budget_items WHERE lib_id = l.id) as total_amount
        FROM line_item_budgets l
        LEFT JOIN departments d ON l.department_id = d.id
        LEFT JOIN users u ON l.created_by = u.id
        WHERE l.department_id = ?";
```

## Files Modified
1. `pages/ppmp_view.php`
   - Updated `displayLIBs()` function to generate lib_number
   - Updated `generateLIBViewHTML()` function to generate lib_number
   - Fixed `item.particular` to `item.particulars`
   - Added null coalescing for safety (`|| ''`)

2. `api/get_lib_list.php`
   - Added `d.dept_code` to SELECT query

## Result
- LIB numbers now display correctly (e.g., "CS-LIB-2026-001")
- Particular column shows the correct values
- No more "undefined" text in the LIB view

## Database Schema Reference
```sql
-- line_item_budgets table does NOT have lib_number column
-- line_item_budget_items has 'particulars' (plural) not 'particular'
CREATE TABLE line_item_budget_items (
  id int(11) NOT NULL AUTO_INCREMENT,
  lib_id int(11) NOT NULL,
  category varchar(100) NOT NULL,
  particulars varchar(255) NOT NULL,  -- Note: plural
  account_code varchar(50) NOT NULL,
  amount decimal(15,2) NOT NULL DEFAULT 0.00,
  ...
);
```
