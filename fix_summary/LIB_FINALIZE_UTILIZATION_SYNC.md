# LIB Finalize with Utilization Sync

## Overview
When a LIB is finalized, it now:
1. ✅ Syncs all items to the utilization table for Budget Office
2. ✅ Hides "Add Item" buttons (no longer editable)
3. ✅ Makes all items read-only

## Changes Made

### 1. Updated `api/finalize_lib.php`
Added utilization sync functionality that:
- Deletes existing auto-filled entries for the department/fiscal year
- Inserts all LIB items into `budget_utilization_entries` table
- Marks entries as `is_auto_filled = 1` and links to `lib_id`
- Uses transaction to ensure data consistency

### 2. Updated `pages/lib.php`
Changed "Add Item" button visibility:
- **Before**: Always visible
- **After**: Only visible when `isDraft && showActions`

## How It Works

### Finalize Flow
```
1. User clicks "Finalize LIB" button
   ↓
2. Confirmation dialog appears
   ↓
3. User confirms
   ↓
4. API starts transaction
   ↓
5. Update LIB status: draft → approved
   ↓
6. Delete old utilization entries (if any)
   ↓
7. Insert all LIB items into utilization table
   ↓
8. Commit transaction
   ↓
9. Return success
   ↓
10. Frontend refreshes display
   ↓
11. LIB now shows:
    - Status: FINAL (green badge)
    - NO "Add Item" buttons
    - NO edit/delete buttons
    - Only "Print" button
   ↓
12. Budget Office can now see items in utilization.php ✓
```

## Utilization Table Structure

Each LIB item is inserted as:
```sql
INSERT INTO budget_utilization_entries (
    department_id,           -- From LIB
    expense_category,        -- Item particulars
    account_code,           -- Item account code
    allocated_budget,       -- Item amount
    deductions,            -- 0 (no deductions yet)
    total_balance,         -- Same as allocated_budget
    fiscal_year,           -- Extracted from LIB fiscal_year
    created_by,            -- User who finalized
    deducted_from_entry_id, -- Auto-incremented ID
    is_auto_filled,        -- 1 (marks as from LIB)
    lib_id                 -- Links back to LIB
)
```

## Visual Changes

### Draft LIB (Before Finalize)
```
┌─────────────────────────────────────────────────────┐
│ Status: [DRAFT]                                      │
│                                                       │
│ A. PERSONAL SERVICES                                 │
│ [+ Add Item] ← Button visible                       │
│                                                       │
│ • Honoraria - Part-time  5010210001  ₱50,000        │
│ • Custom Item           5010210002   ₱10,000 [✏️][🗑️]│
│                                                       │
│ Sub-Total: ₱60,000                                   │
│                                                       │
│ [✓ Finalize LIB] ← Button at bottom                 │
└─────────────────────────────────────────────────────┘
```

### Final LIB (After Finalize)
```
┌─────────────────────────────────────────────────────┐
│ Status: [FINAL]                                      │
│                                                       │
│ A. PERSONAL SERVICES                                 │
│ (No Add Item button) ← Hidden                       │
│                                                       │
│ • Honoraria - Part-time  5010210001  ₱50,000        │
│ • Custom Item           5010210002   ₱10,000        │
│   (No edit/delete buttons) ← All read-only          │
│                                                       │
│ Sub-Total: ₱60,000                                   │
│                                                       │
│ [🖨 Print] ← Only print button available            │
└─────────────────────────────────────────────────────┘
```

### Budget Office Utilization View
```
Budget Office logs in → Opens utilization.php
  ↓
Sees all finalized LIB items:
┌──────────────────────────────────────────────────────┐
│ Department: Computer Studies                          │
│ Fiscal Year: 2026                                     │
│                                                        │
│ Expense Category          | Allocated | Balance       │
│ Honoraria - Part-time     | ₱50,000  | ₱50,000       │
│ Custom Item               | ₱10,000  | ₱10,000       │
│ Water Expenses            | ₱10,000  | ₱10,000       │
│ Labor and Wages           | ₱100,000 | ₱100,000      │
│ Security Services         | ₱50,000  | ₱50,000       │
│ Electricity Expenses      | ₱20,000  | ₱20,000       │
└──────────────────────────────────────────────────────┘
```

## Testing Steps

### 1. Create and Finalize LIB
1. Generate a LIB from allocations
2. Add a manual item
3. Verify "Add Item" buttons are visible
4. Click "Finalize LIB"
5. Confirm finalization
6. ✅ "Add Item" buttons should disappear
7. ✅ Edit/delete buttons should disappear
8. ✅ Status should show "FINAL"

### 2. Check Utilization Sync
1. Log in as Budget Office user (budget role)
2. Go to utilization.php
3. Select the department and fiscal year
4. ✅ Should see all LIB items listed
5. ✅ Each item should have:
   - Expense category (particulars)
   - Account code
   - Allocated budget (amount)
   - Balance (same as allocated)

### 3. Verify Read-Only State
1. Try to click where "Add Item" button was
2. ✅ Nothing happens (button is gone)
3. Try to edit an item
4. ✅ No edit button available
5. Try to delete an item
6. ✅ No delete button available

## Database Verification

Check if items were synced:
```sql
SELECT 
    e.expense_category,
    e.account_code,
    e.allocated_budget,
    e.is_auto_filled,
    e.lib_id
FROM budget_utilization_entries e
WHERE e.lib_id = [YOUR_LIB_ID]
ORDER BY e.id;
```

Should show all LIB items with:
- `is_auto_filled = 1`
- `lib_id = [your LIB ID]`

## Files Modified

1. ✅ `api/finalize_lib.php` - Added utilization sync logic
2. ✅ `pages/lib.php` - Made "Add Item" buttons conditional (draft only)

## Benefits

✅ **Automatic Sync** - No manual data entry needed for Budget Office
✅ **Data Consistency** - LIB and utilization always match
✅ **Clear Workflow** - Draft → Edit → Finalize → Sync
✅ **Read-Only Final** - Finalized LIBs cannot be modified
✅ **Budget Office Ready** - Items immediately available for utilization tracking

## Status: ✅ COMPLETE

- Finalize now syncs to utilization
- "Add Item" buttons hidden when finalized
- Budget Office can see finalized LIB items
- All items become read-only after finalization
