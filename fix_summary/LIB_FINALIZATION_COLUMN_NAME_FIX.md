# LIB Finalization Column Name Fix

## Error Encountered
```
Database error: SQLSTATE[42S22]: Column not found: 1054 Unknown column 
'lib.lib_category' in 'field list'
```

## Problem
The SQL query in `api/finalize_lib.php` was using incorrect column names that don't exist in the `line_item_budget_items` table.

### Incorrect Column Names (Used in Query)
- `lib.lib_category`
- `lib.lib_particulars`
- `lib.lib_account_code`

### Actual Column Names (In Database)
- `category`
- `particulars`
- `account_code`

## Root Cause
The query was written assuming the columns had a `lib_` prefix, but the actual database schema uses simple column names without the prefix.

## Solution

### Fixed Query

**Before (Incorrect):**
```sql
SELECT DISTINCT lib.lib_category, lib.lib_particulars, lib.lib_account_code
FROM line_item_budget_items lib
WHERE lib.lib_id = ? AND lib.source = 'ppmp'
```

**After (Correct):**
```sql
SELECT DISTINCT category, particulars, account_code
FROM line_item_budget_items
WHERE lib_id = ? AND source = 'ppmp'
```

### Fixed Variable References

**Before (Incorrect):**
```php
$linkedItem['lib_category']
$linkedItem['lib_particulars']
$linkedItem['lib_account_code']
```

**After (Correct):**
```php
$linkedItem['category']
$linkedItem['particulars']
$linkedItem['account_code']
```

## Database Schema Reference

### line_item_budget_items Table
```sql
CREATE TABLE IF NOT EXISTS `line_item_budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lib_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,           -- ✓ Correct name
  `particulars` varchar(255) NOT NULL,        -- ✓ Correct name
  `account_code` varchar(50) NOT NULL,        -- ✓ Correct name
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `source` ENUM('manual', 'ppmp') NOT NULL DEFAULT 'manual',
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lib_id` (`lib_id`),
  CONSTRAINT `lib_items_lib_fk` FOREIGN KEY (`lib_id`) REFERENCES `line_item_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Files Modified
- `api/finalize_lib.php` - Fixed column names in SQL query

## Testing
After this fix:
1. Click "Finalize LIB" with unfinalized PPMP
2. Should see error message (not database error)
3. Finalize PPMP
4. Click "Finalize LIB" again
5. Should see confirmation dialog
6. Click OK
7. Should finalize successfully

## Status
✅ **FIXED** - Column names corrected

## Implementation Date
April 14, 2026
