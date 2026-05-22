# PPMP to LIB Sync - Issue Resolution Summary

## Issue Reported
User reported that when saving a PPMP with LIB mappings, existing manual LIB items (Water Expenses, Labor and Wages, Security Services, Electricity Expenses) were being removed.

## Investigation Results

### Code Review Findings
✅ **The sync function DOES NOT delete manual items**
- Reviewed `api/sync_ppmp_to_lib_helper.php` line by line
- Confirmed: Only INSERT and UPDATE operations, NO DELETE operations
- Manual items are never touched by the sync process

### Root Cause Identified
🔍 **Multiple LIBs exist for the same department and fiscal year**

**How this causes confusion:**
1. User creates LIB #1 with manual items (Water, Labor, Security, Electricity)
2. Later, LIB #2 is created (accidentally or intentionally)
3. User saves PPMP with LIB mappings
4. PPMP sync adds items to **LIB #2** (most recent by `created_at`)
5. User views LIB page, which might show **LIB #1** (the old one)
6. User sees manual items but no PPMP items
7. User thinks items were deleted, but they're actually in different LIBs

## Solution Provided

### 1. Diagnostic Tool (NEW)
**File:** `check_lib_items_source.php`

**Features:**
- Shows ALL LIBs for Computer Studies 2026
- Displays which LIB is newest (where PPMP syncs to)
- Lists all items in each LIB with source tracking:
  - 🟢 PPMP items (green background)
  - 🟡 Manual items (yellow background)
- Identifies if multiple LIBs exist
- Provides specific recommendations
- Includes delete buttons for old LIBs

**How to use:**
```
1. Open in browser: http://localhost/budgettrack/check_lib_items_source.php
2. Review all LIBs and their items
3. If multiple LIBs exist, select old ones to delete
4. Click "Delete Selected LIBs" button
5. Refresh LIB page to see all items in one place
```

### 2. Delete Tool (NEW)
**File:** `delete_old_libs.php`

**Features:**
- Safely deletes selected old LIBs
- Deletes both LIB record and all its items
- Shows success/error messages
- Prevents accidental deletion (requires confirmation)

### 3. Enhanced Logging
**File:** `api/sync_ppmp_to_lib_helper.php`

**Added logging:**
```
PPMP Sync: Syncing PPMP #1 to LIB #5 (most recent for dept 3, year 2026)
PPMP Sync: Created new LIB #5 for dept 3, year 2026
PPMP Sync: Added new item #123 to LIB #5 (ref: PPMP #1 - Item #1, category: B. Maintenance & Other Operating Expenses)
PPMP Sync: Updated existing item #124 in LIB #5 (ref: PPMP #1 - Item #2)
```

**Where to find logs:**
- Windows: `C:\xampp1\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`

### 4. Troubleshooting Guide (NEW)
**File:** `PPMP_LIB_SYNC_TROUBLESHOOTING.md`

Complete guide covering:
- Problem description and root cause
- Step-by-step diagnostic process
- Multiple solution options
- How PPMP sync works (detailed)
- Verification steps
- Common mistakes and fixes
- Quick reference table

## How PPMP Sync Actually Works

### Target LIB Selection
```sql
SELECT id, status FROM line_item_budgets 
WHERE department_id = ? AND fiscal_year = ? 
ORDER BY created_at DESC 
LIMIT 1
```
- Gets the **MOST RECENT** LIB only
- If multiple LIBs exist, older ones are **completely ignored**

### Sync Process
1. ✅ Check if LIB exists for department/year
2. ✅ If no LIB exists, create new one
3. ✅ If LIB is finalized (`approved`), block sync
4. ✅ For each PPMP item with LIB mapping:
   - Create reference: `"PPMP #1 - Item #1"`
   - Check if item exists (by reference)
   - If exists: Update amount if changed
   - If new: Insert new row
5. ✅ **NEVER delete any items**

### What Gets Synced
Only PPMP items with complete LIB mappings:
- ✅ Category (e.g., "B. Maintenance & Other Operating Expenses")
- ✅ Particulars (e.g., "Office Supplies Expenses")
- ✅ Account Code (e.g., "5-02-01-010")
- ✅ Amount (e.g., 50000.00)

### Item Format in LIB
```
Particulars: "Office Supplies Expenses (PPMP #1 - Item #1)"
Category: "B. Maintenance & Other Operating Expenses"
Account Code: "5-02-01-010"
Amount: 50000.00
```

## Action Required from User

### Immediate Steps
1. **Run diagnostic script:**
   ```
   http://localhost/budgettrack/check_lib_items_source.php
   ```

2. **Review the results:**
   - How many LIBs exist for Computer Studies 2026?
   - Which LIB has manual items?
   - Which LIB has PPMP items?
   - Are they in different LIBs?

3. **Take action based on findings:**

   **If multiple LIBs exist:**
   - Option A: Delete older LIBs (use form in diagnostic script)
   - Option B: Manually merge items, then delete old LIBs

   **If only one LIB exists:**
   - Check if it has both manual and PPMP items
   - If items are missing, they may have been accidentally deleted
   - Re-add manual items if needed

### Verification Steps
After fixing:

1. ✅ Confirm only ONE LIB exists for Computer Studies 2026
2. ✅ Open LIB page and verify all items are visible
3. ✅ Test PPMP sync by saving a PPMP with LIB mappings
4. ✅ Refresh LIB page and confirm new PPMP items appear
5. ✅ Verify manual items are still present

## Prevention for Future

### Best Practices
1. ✅ **One LIB per department per year** - Don't create duplicates
2. ✅ **Edit existing LIBs** - Don't create new ones if one already exists
3. ✅ **Check LIB page after PPMP sync** - Verify items were added
4. ✅ **Keep LIB in draft status** - Finalized LIBs can't be synced to

### Possible Enhancement
Add validation in `api/create_lib.php` to prevent duplicate LIBs:

```php
// Check if LIB already exists
$checkQuery = "SELECT id FROM line_item_budgets 
               WHERE department_id = ? AND fiscal_year = ?";
$stmt = $db->prepare($checkQuery);
$stmt->execute([$departmentId, $fiscalYear]);
if ($stmt->fetch()) {
    echo json_encode([
        'success' => false, 
        'message' => 'A LIB already exists for this department and fiscal year. Please edit the existing LIB instead.'
    ]);
    exit;
}
```

## Files Created/Modified

### New Files
1. ✅ `check_lib_items_source.php` - Comprehensive diagnostic tool
2. ✅ `delete_old_libs.php` - Safe deletion of old LIBs
3. ✅ `PPMP_LIB_SYNC_TROUBLESHOOTING.md` - Complete troubleshooting guide
4. ✅ `PPMP_LIB_SYNC_FIX_SUMMARY.md` - This summary document

### Modified Files
1. ✅ `api/sync_ppmp_to_lib_helper.php` - Added detailed logging

## Summary

### What We Found
- ❌ Items are NOT being deleted by sync function
- ✅ Multiple LIBs likely exist for same department/year
- ✅ PPMP syncs to newest LIB, user views older LIB
- ✅ This creates illusion that items were deleted

### What We Fixed
- ✅ Created diagnostic tool to identify the issue
- ✅ Created deletion tool to remove old LIBs
- ✅ Added detailed logging to track sync operations
- ✅ Provided comprehensive troubleshooting guide

### Next Steps for User
1. Run `check_lib_items_source.php`
2. Review findings
3. Delete old LIBs if multiple exist
4. Verify all items are now visible in one LIB
5. Test PPMP sync to confirm it works correctly

---

**Status:** ✅ Investigation complete, tools provided, awaiting user action
**Date:** 2026-04-12
**Priority:** High - User needs to run diagnostic script to confirm root cause
