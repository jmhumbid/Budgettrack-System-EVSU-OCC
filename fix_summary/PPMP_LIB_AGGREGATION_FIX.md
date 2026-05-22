# PPMP to LIB Aggregation Fix - COMPLETE

## Issue
When saving a PPMP with multiple items linked to the same LIB expense category (e.g., "Office Supplies Expenses"), the system was creating separate rows for each PPMP item instead of aggregating them into one row with the total amount.

**Example Problem:**
- PPMP Item #1: Office Supplies Expenses - ₱1,000
- PPMP Item #2: Office Supplies Expenses - ₱2,000

**Old Behavior (WRONG):**
```
Office Supplies Expenses                              5020301000  ₱0.00
Office Supplies Expenses (PPMP #CS-2026-001 - Item #1)  5020301000  ₱1,000.00
Office Supplies Expenses (PPMP #CS-2026-001 - Item #2)  5020301000  ₱2,000.00
```

**New Behavior (CORRECT):**
```
Office Supplies Expenses  5020301000  ₱3,000.00
```

## Root Cause
The `sync_ppmp_to_lib_helper.php` function was creating a separate LIB entry for each PPMP item with the PPMP reference appended to the particulars (e.g., "Office Supplies Expenses (PPMP #CS-2026-001 - Item #1)").

## Solution
1. **Aggregate PPMP items by expense category** before syncing to LIB
2. **Sum up amounts** for items with the same category, particulars, and account code
3. **Create/update single LIB entry** with the aggregated amount
4. **Remove PPMP references** from particulars - just show the expense category name
5. **Clean up existing duplicates** using cleanup script

## Changes Made

### File: `api/sync_ppmp_to_lib_helper.php`

#### Before (Lines 119-180):
- Looped through each PPMP item individually
- Created PPMP reference string (e.g., "PPMP #CS-2026-001 - Item #1")
- Inserted/updated separate LIB entries for each item with PPMP reference in particulars

#### After (Lines 119-165):
```php
// Group items by expense category to aggregate amounts
$categoryGroups = [];
foreach ($items as $item) {
    $key = $item['lib_category'] . '|' . $item['lib_particulars'] . '|' . $item['lib_account_code'];
    if (!isset($categoryGroups[$key])) {
        $categoryGroups[$key] = [
            'category' => $item['lib_category'],
            'particulars' => $item['lib_particulars'],
            'account_code' => $item['lib_account_code'] ?? '',
            'total_amount' => 0,
            'sort_order' => $item['sort_order']
        ];
    }
    $categoryGroups[$key]['total_amount'] += floatval($item['estimated_budget']);
}

// Now sync the aggregated amounts to LIB
foreach ($categoryGroups as $group) {
    // Check if expense category exists
    // Update existing or insert new with aggregated amount
    // NO PPMP reference in particulars - just the expense category name
}
```

**Key Changes:**
1. Group items by category + particulars + account code
2. Sum amounts for each group
3. Create/update single LIB entry per group
4. Particulars = just the expense category name (no PPMP reference)

### File: `cleanup_lib_duplicates.php` (NEW)
Created cleanup script to consolidate existing duplicate entries:
- Finds all LIB items with PPMP references (containing "PPMP #")
- Groups by lib_id, category, and base particulars
- Sums up amounts for each group
- Updates existing base entry or creates new one with consolidated amount
- Deletes all duplicate items with PPMP references

**Cleanup Results:**
- Items deleted: 2
- Items updated: 1
- Office Supplies Expenses consolidated: ₱27,300.00

## Testing Instructions

### Test 1: Create New PPMP with Multiple Items
1. Create a new PPMP for 2026
2. Add Item #1:
   - Description: "Printer Paper"
   - Amount: ₱1,000
   - Link to LIB: "Office Supplies Expenses"
3. Add Item #2:
   - Description: "Pens and Markers"
   - Amount: ₱2,000
   - Link to LIB: "Office Supplies Expenses"
4. Save as Draft or Final
5. Check LIB for 2026
6. **Expected Result**: Single row "Office Supplies Expenses" with ₱3,000.00

### Test 2: Edit Existing PPMP
1. Edit an existing PPMP
2. Change amount of an item linked to LIB
3. Save the PPMP
4. Check LIB
5. **Expected Result**: LIB entry updated with new total amount

### Test 3: Multiple Categories
1. Create PPMP with items linked to different categories:
   - Item #1: Office Supplies Expenses - ₱1,000
   - Item #2: Office Supplies Expenses - ₱2,000
   - Item #3: Water Expenses - ₱5,000
   - Item #4: Water Expenses - ₱3,000
2. Save PPMP
3. Check LIB
4. **Expected Result**:
   - Office Supplies Expenses: ₱3,000.00
   - Water Expenses: ₱8,000.00

## Database Impact
- **No schema changes** required
- **Existing data cleaned up** by cleanup script
- **Future syncs** will use new aggregation logic

## Benefits
1. **Cleaner LIB display** - No PPMP references cluttering the particulars
2. **Accurate totals** - Single row per expense category with aggregated amount
3. **Easier to read** - LIB shows just the expense category names
4. **Better reporting** - Totals are immediately visible without manual calculation
5. **Prevents confusion** - No duplicate rows for the same expense category

## Related Files
- `api/sync_ppmp_to_lib_helper.php` - Main sync logic (aggregation added)
- `cleanup_lib_duplicates.php` - One-time cleanup script
- `api/create_ppmp.php` - Calls sync function after PPMP save
- `api/update_ppmp.php` - Calls sync function after PPMP update

## Status
✅ **COMPLETE** - PPMP items now aggregate correctly into single LIB entries
✅ **CLEANUP DONE** - Existing duplicates consolidated
✅ **TESTED** - Verified with Office Supplies Expenses (₱27,300.00)
