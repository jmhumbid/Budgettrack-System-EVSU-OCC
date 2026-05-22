# PPMP Deletion and LIB Synchronization Fix

## Problem Summary

When deleting a PPMP (Project Procurement Management Plan), the linked LIB (Line Item Budget) items were not being removed, leaving orphaned expense category rows visible in the LIB.

### User's Expected Behavior:

1. Create PPMP with 3 items (₱1,000 each) linked to "Office Supplies Expenses"
2. LIB automatically creates "Office Supplies Expenses" row with ₱3,000
3. Delete 1 PPMP item → LIB updates to ₱2,000 ✓ (working)
4. Delete entire PPMP → LIB should completely remove "Office Supplies Expenses" row ✗ (NOT working)

## Root Causes Identified

### Issue 1: Fiscal Year Mismatch
- **PPMP stores**: `2026`
- **LIB stores**: `FY 2026`
- **Problem**: Exact match query `fiscal_year = '2026'` didn't find LIB with `'FY 2026'`
- **Result**: Deletion code couldn't find the LIB, so it skipped cleanup entirely

### Issue 2: Sync Approach Mismatch
There are TWO different sync approaches in the codebase:

1. **`sync_ppmp_to_lib.php`** (OLD approach):
   - Creates individual items WITH PPMP reference
   - Format: `"Office Supplies Expenses (PPMP #1 - Item #1)"`
   - Each PPMP item gets its own LIB row

2. **`sync_ppmp_to_lib_helper.php`** (CURRENT approach):
   - Creates aggregated items WITHOUT PPMP reference
   - Format: `"Office Supplies Expenses"`
   - All PPMP items in same category are summed into ONE LIB row

**Problem**: The deletion code was looking for items with `"(PPMP #X - Item #Y)"` pattern, but the current sync creates items WITHOUT this pattern!

## Solution Implemented

### Fix 1: Flexible Fiscal Year Matching

**File**: `api/delete_ppmp.php`

**Before**:
```php
$stmt = $db->prepare("
    SELECT id FROM line_item_budgets 
    WHERE department_id = ? AND fiscal_year = ? 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$departmentId, $fiscalYear]);
```

**After**:
```php
$stmt = $db->prepare("
    SELECT id FROM line_item_budgets 
    WHERE department_id = ? AND (fiscal_year = ? OR fiscal_year LIKE ?) 
    ORDER BY created_at DESC 
    LIMIT 1
");
$stmt->execute([$departmentId, $fiscalYear, "%{$fiscalYear}%"]);
```

**Result**: Now matches both "2026" and "FY 2026" formats

### Fix 2: Support Both Sync Approaches

**File**: `api/delete_ppmp.php`

**New Logic**:
1. Get all PPMP items with LIB mappings (category, particulars, account_code)
2. For each PPMP item, delete the corresponding LIB item using exact match:
   - Same `category`
   - Same `particulars` (exact match, no PPMP reference)
   - Same `account_code`
3. This handles the AGGREGATED approach (current system)
4. Also check for OLD approach with PPMP references (backwards compatibility)

**Code**:
```php
// Get PPMP items to know which LIB categories to delete
$ppmpItemsStmt = $db->prepare("
    SELECT lib_category, lib_particulars, lib_account_code 
    FROM ppmp_items 
    WHERE ppmp_id = ? 
    AND lib_category IS NOT NULL 
    AND lib_particulars IS NOT NULL
");
$ppmpItemsStmt->execute([$ppmpId]);
$ppmpItems = $ppmpItemsStmt->fetchAll(PDO::FETCH_ASSOC);

// For each PPMP item, delete the corresponding LIB item
foreach ($ppmpItems as $ppmpItem) {
    $deleteStmt = $db->prepare("
        DELETE FROM line_item_budget_items 
        WHERE lib_id = ? 
        AND category = ? 
        AND particulars = ? 
        AND account_code = ?
    ");
    $deleteStmt->execute([
        $libId,
        $ppmpItem['lib_category'],
        $ppmpItem['lib_particulars'],
        $ppmpItem['lib_account_code']
    ]);
}

// ALSO handle OLD approach with PPMP references (backwards compatibility)
$patterns = [
    "(PPMP #{$ppmpNumber} - Item #%",
    "%PPMP #{$ppmpNumber}%",
];

foreach ($patterns as $pattern) {
    $stmt = $db->prepare("
        DELETE FROM line_item_budget_items 
        WHERE lib_id = ? AND particulars LIKE ?
    ");
    $stmt->execute([$libId, $pattern]);
}
```

## How It Works Now

### Scenario: Delete Entire PPMP

**Before deletion**:
- PPMP #56 has 1 item: "Office Supplies Expenses" (₱1,000)
- LIB #67 has item #666: "Office Supplies Expenses" (₱1,000)

**When user clicks Delete PPMP**:
1. System finds PPMP #56 (ppmp_number: CS-2026-001, fiscal_year: 2026)
2. System finds LIB #67 using flexible matching (fiscal_year: FY 2026) ✓
3. System gets PPMP items with LIB mappings:
   - lib_category: "B. Maintenance & Other Operating Expenses"
   - lib_particulars: "Office Supplies Expenses"
   - lib_account_code: "5020301000"
4. System deletes LIB item #666 that matches these exact values ✓
5. System deletes PPMP #56 and all its items ✓

**After deletion**:
- PPMP #56: DELETED ✓
- LIB item #666: DELETED ✓
- "Office Supplies Expenses" row: GONE from LIB display ✓

### Scenario: Delete Individual PPMP Item (via Edit)

**Handled by**: `api/update_ppmp.php`

**Logic**:
1. When PPMP is updated, old LIB items are deleted first
2. Then new items are synced based on current PPMP items
3. If PPMP has 3 items → LIB shows ₱3,000
4. If PPMP has 2 items → LIB shows ₱2,000
5. If PPMP has 0 items → LIB item is removed

## Testing

### Test Scripts Created:

1. **`test_ppmp_deletion_fix.php`**
   - Tests fiscal year matching (OLD vs NEW approach)
   - Shows current LIB items

2. **`test_ppmp_deletion_complete.php`**
   - Complete workflow test
   - Shows LIB state, PPMP state, and deletion logic explanation

3. **`check_lib_fiscal_year.php`**
   - Shows actual fiscal year formats in database

### Manual Testing Steps:

1. Create a new PPMP with 3 items linked to "Office Supplies Expenses"
2. Verify LIB shows "Office Supplies Expenses" with total amount
3. Delete 1 item from PPMP (via Edit)
4. Verify LIB amount decreases
5. Delete entire PPMP
6. Verify "Office Supplies Expenses" row is completely removed from LIB

## Files Modified

1. **`api/delete_ppmp.php`**
   - Added flexible fiscal year matching
   - Added support for aggregated sync approach
   - Kept backwards compatibility with old approach

## Files Created

1. **`test_ppmp_deletion_fix.php`** - Fiscal year matching test
2. **`test_ppmp_deletion_complete.php`** - Complete workflow test
3. **`PPMP_DELETION_LIB_SYNC_FIX.md`** - This documentation

## Important Notes

### Current Sync Approach
The system uses **`sync_ppmp_to_lib_helper.php`** which creates AGGREGATED items:
- One LIB item per expense category
- No PPMP reference in particulars
- Amount is sum of all PPMP items in that category

### Backwards Compatibility
The deletion code still checks for OLD approach items with PPMP references, ensuring it works with both sync methods.

### Real-Time Updates
When PPMP is deleted, the LIB items are removed immediately in the database. The user needs to refresh the LIB page to see the changes.

## Status

✅ **FIXED** - PPMP deletion now correctly removes linked LIB items using:
1. Flexible fiscal year matching (handles "2026" and "FY 2026")
2. Aggregated item deletion (matches category/particulars/account_code)
3. Backwards compatibility (still checks for PPMP reference patterns)
