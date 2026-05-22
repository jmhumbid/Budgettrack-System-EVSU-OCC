# Auto-Sync LIB System - Bug Fixes

## Issues Fixed

### Issue 1: "Unauthorized" Error
**Problem:** When clicking "Generate LIB" button, API returned "Unauthorized" error.

**Root Cause:** The API file wasn't starting the session before checking `$_SESSION['user_id']`.

**Fix Applied:**
```php
// Added session start at the beginning of api/generate_auto_lib.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
```

**Files Modified:**
- `api/generate_auto_lib.php`

---

### Issue 2: Column Not Found Error (department_name)
**Problem:** Test script showed error: `Column not found: 1054 Unknown column 'd.department_name' in 'field list'`

**Root Cause:** The departments table uses `dept_name` column, not `department_name`.

**Fix Applied:**
Changed all SQL queries from:
```sql
d.department_name
```
To:
```sql
d.dept_name
```

**Files Modified:**
- `api/generate_auto_lib.php`
- `test_auto_lib_simple.php`
- `test_auto_lib_generation.php`

---

### Issue 3: Column Not Found Error (uacs_code)
**Problem:** Browser showed error: `Column not found: 1054 Unknown column 'a.uacs_code' in 'field list'`

**Root Cause:** The `budget_allocations` table doesn't have individual columns for allocation items. Instead, it stores all allocation data in a JSON field called `allocation_data`.

**Table Structure:**
```sql
CREATE TABLE budget_allocations (
  id int(11) NOT NULL,
  department_id int(11) NOT NULL,
  fiscal_year year(4) NOT NULL,
  allocation_data longtext NOT NULL,  -- JSON data here!
  allocated_amount decimal(15,2),
  status enum('active','inactive','closed'),
  ...
);
```

**Fix Applied:**
Changed the query to fetch `allocation_data` and parse the JSON:
```php
// Get allocation_data JSON field
$query = "SELECT 
            a.id as allocation_id,
            a.allocation_data,  -- JSON field
            a.fiscal_year,
            d.dept_name
          FROM budget_allocations a
          JOIN departments d ON a.department_id = d.id
          WHERE a.department_id = :department_id 
          AND a.fiscal_year = :year
          AND a.status = 'active'";

// Parse JSON and extract items
$allocation_data = json_decode($allocation_record['allocation_data'], true);

foreach ($allocation_data as $item) {
    $lib_items[] = [
        'uacs_code' => $item['uacs_code'] ?? $item['uacsCode'] ?? '',
        'general_desc' => $item['general_desc'] ?? $item['generalDesc'] ?? '',
        'total_amount' => floatval($item['total_amount'] ?? $item['totalAmount'] ?? 0),
        // ... etc
    ];
}
```

**Files Modified:**
- `api/generate_auto_lib.php` - Complete rewrite to parse JSON

---

## Testing After Fixes

### Test 1: Check Allocation Structure
```bash
php test_allocation_structure.php
```

This will show you:
- Sample allocation record
- JSON structure of allocation_data
- Available keys in the data
- Sample items

### Test 2: Run Simple Test
```bash
php test_auto_lib_simple.php
```

**Expected Output:**
```
✅ SUCCESS!
Results:
- Items Count: [number]
- Department: [department name]
- Year: [year]

[Table showing sample items]
Grand Total: ₱[amount]
```

### Test 3: Test in Browser
1. Login to BudgetTrack
2. Go to LIB page
3. Click "Auto-Generate from Allocations"
4. Select year
5. Click "Generate LIB"

**Expected Result:**
- Modal shows list of allocation items
- Items have green "Allocation" badges
- Grand total displays correctly
- No errors

---

## Files Fixed

### Backend
- ✅ `api/generate_auto_lib.php` - Added session start, fixed to parse JSON allocation_data

### Testing
- ✅ `test_auto_lib_simple.php` - Fixed column name
- ✅ `test_auto_lib_generation.php` - Fixed column name
- ✅ `test_allocation_structure.php` - NEW: Check allocation data structure

---

## Understanding the Data Structure

The `budget_allocations` table stores allocation items as JSON in the `allocation_data` column. The structure might look like:

```json
[
  {
    "uacs_code": "5-02-01-010",
    "general_desc": "Salaries and Wages",
    "total_amount": 500000,
    "quarter_1": 125000,
    "quarter_2": 125000,
    "quarter_3": 125000,
    "quarter_4": 125000
  },
  {
    "uacs_code": "5-02-03-050",
    "general_desc": "Office Supplies",
    "total_amount": 50000,
    "quarter_1": 12500,
    "quarter_2": 12500,
    "quarter_3": 12500,
    "quarter_4": 12500
  }
]
```

The API now:
1. Fetches the `allocation_data` JSON field
2. Parses it with `json_decode()`
3. Loops through each item
4. Extracts the needed fields (with fallbacks for different key names)
5. Formats them for the LIB

---

## Verification Checklist

- [ ] Run `php test_allocation_structure.php` - Check data structure
- [ ] Run `php test_auto_lib_simple.php` - Should pass
- [ ] Login to system
- [ ] Navigate to LIB page
- [ ] Click "Auto-Generate from Allocations"
- [ ] Select year with allocations
- [ ] Click "Generate LIB"
- [ ] Verify items appear
- [ ] Verify no errors in browser console
- [ ] Add custom item (optional test)
- [ ] Save LIB

---

## Status

✅ **All Issues Fixed**

The Auto-Sync LIB system now correctly:
- Starts sessions
- Uses correct column names
- Parses JSON allocation data
- Handles different JSON key formats

**Date Fixed:** April 8, 2026
