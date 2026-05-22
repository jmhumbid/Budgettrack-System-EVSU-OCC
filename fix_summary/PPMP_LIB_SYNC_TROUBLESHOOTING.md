# PPMP to LIB Auto-Sync Troubleshooting Guide

## Issue: Existing LIB Items Disappearing After PPMP Sync

### Problem Description
User reports that when they save a PPMP with LIB mappings, their existing manual LIB items (Water Expenses, Labor and Wages, Security Services, Electricity Expenses) are being removed.

### Root Cause Analysis

The sync function **DOES NOT DELETE** manual items. After code review, the issue is likely caused by:

**MULTIPLE LIBs EXIST** for the same department and fiscal year, causing confusion:
- PPMP sync updates the **MOST RECENT LIB** (by `created_at DESC`)
- User might be viewing an **OLDER LIB** on the LIB page
- Result: User sees manual items in old LIB, but PPMP items are added to new LIB
- User thinks items were deleted, but they're just in different LIBs

### Diagnostic Steps

#### Step 1: Run Diagnostic Script
```bash
# Open in browser:
http://localhost/budgettrack/check_lib_items_source.php
```

This script will show:
- ✅ All LIBs for Computer Studies 2026
- ✅ Which LIB is the newest (where PPMP syncs to)
- ✅ All items in each LIB with source tracking (Manual vs PPMP)
- ✅ Recommendations for fixing the issue

#### Step 2: Check for Multiple LIBs

**If Multiple LIBs Exist:**
```
⚠️ PROBLEM: You have 2+ LIBs for the same department/year
✅ SOLUTION: Delete older LIBs, keep only the newest one
```

**If Only One LIB Exists:**
```
✅ GOOD: You have only one LIB (correct)
⚠️ CHECK: Does it have both manual and PPMP items?
```

### Solutions

#### Solution A: Delete Older LIBs (Recommended)

1. Run diagnostic script: `check_lib_items_source.php`
2. Review all LIBs and their items
3. Select older LIBs to delete
4. Click "Delete Selected LIBs" button
5. Refresh LIB page - you should now see all items in one LIB

#### Solution B: Manually Merge LIBs

If older LIBs have important manual items:

1. Open the newest LIB in edit mode
2. Manually add the missing items from older LIBs
3. Delete the older LIBs
4. Save the merged LIB

#### Solution C: Prevent Multiple LIBs (Future)

To prevent this issue in the future, we can add a check in `api/create_lib.php`:

```php
// Check if LIB already exists for this department/year
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

### How PPMP Sync Works

#### Sync Logic Flow

1. **Get PPMP Details**
   - Fetch PPMP record with department and fiscal year
   - Get all PPMP items with LIB mappings

2. **Find Target LIB**
   ```sql
   SELECT id, status FROM line_item_budgets 
   WHERE department_id = ? AND fiscal_year = ? 
   ORDER BY created_at DESC 
   LIMIT 1
   ```
   - Gets the **MOST RECENT** LIB only
   - If multiple LIBs exist, older ones are ignored

3. **Check LIB Status**
   - If LIB is `approved` (finalized), sync is blocked
   - Only `draft` LIBs can be synced to

4. **Create LIB if Needed**
   - If no LIB exists, creates a new one
   - Status: `draft`
   - Fund Type: `Internally Generated Fund`

5. **Sync Each PPMP Item**
   - Creates unique reference: `"PPMP #1 - Item #1"`
   - Checks if item already exists (by reference)
   - **If exists:** Updates amount if changed
   - **If new:** Inserts new row
   - **NEVER deletes manual items**

#### What Gets Synced

Only PPMP items with complete LIB mappings:
- ✅ `lib_category` is set (e.g., "B. Maintenance & Other Operating Expenses")
- ✅ `lib_particulars` is set (e.g., "Office Supplies Expenses")
- ✅ `lib_account_code` is set (e.g., "5-02-01-010")
- ✅ `estimated_budget` has a value

#### Item Format in LIB

```
Particulars: "Office Supplies Expenses (PPMP #1 - Item #1)"
Category: "B. Maintenance & Other Operating Expenses"
Account Code: "5-02-01-010"
Amount: 50000.00
```

### Verification Steps

After fixing the issue:

1. **Check LIB Count**
   ```sql
   SELECT COUNT(*) FROM line_item_budgets 
   WHERE department_id = ? AND fiscal_year = '2026';
   ```
   Should return: `1`

2. **Check Items in LIB**
   ```sql
   SELECT id, category, particulars, amount 
   FROM line_item_budget_items 
   WHERE lib_id = ?
   ORDER BY category, sort_order;
   ```
   Should show both manual and PPMP items

3. **Test PPMP Sync**
   - Create/edit a PPMP with LIB mappings
   - Save as draft or final
   - Check LIB page - should see new PPMP items
   - Manual items should still be there

### Logging

The sync function now logs to PHP error log:

```
PPMP Sync: Syncing PPMP #1 to LIB #5 (most recent for dept 3, year 2026)
PPMP Sync: Added new item #123 to LIB #5 (ref: PPMP #1 - Item #1, category: B. Maintenance & Other Operating Expenses)
PPMP Sync: Updated existing item #124 in LIB #5 (ref: PPMP #1 - Item #2)
```

Check logs at: `C:\xampp1\apache\logs\error.log` (Windows) or `/var/log/apache2/error.log` (Linux)

### Common Mistakes

❌ **Mistake 1:** Creating multiple LIBs for same department/year
✅ **Fix:** Delete older LIBs, keep only one

❌ **Mistake 2:** Viewing an old LIB and expecting to see new PPMP items
✅ **Fix:** Ensure you're viewing the most recent LIB

❌ **Mistake 3:** Syncing to a finalized (approved) LIB
✅ **Fix:** LIB must be in `draft` status for sync to work

❌ **Mistake 4:** PPMP items missing LIB mappings
✅ **Fix:** Use "Link to LIB" button to map each PPMP item

### Files Modified

1. **api/sync_ppmp_to_lib_helper.php**
   - Added detailed logging
   - Clarified which LIB is being synced to

2. **check_lib_items_source.php** (NEW)
   - Comprehensive diagnostic tool
   - Shows all LIBs and their items
   - Identifies source of each item (Manual vs PPMP)
   - Provides recommendations

3. **delete_old_libs.php** (NEW)
   - Safely deletes selected old LIBs
   - Prevents accidental deletion of newest LIB

### Quick Reference

| Action | Command |
|--------|---------|
| Diagnose issue | Open `check_lib_items_source.php` |
| Delete old LIBs | Use form in diagnostic script |
| Check logs | View `error.log` in Apache logs folder |
| Test sync | Save PPMP with LIB mappings |
| Verify items | Open LIB page, check all items present |

### Support

If issues persist after following this guide:

1. Run diagnostic script and take screenshot
2. Check Apache error logs for sync messages
3. Verify only one LIB exists for department/year
4. Confirm PPMP items have complete LIB mappings
5. Ensure LIB is in `draft` status (not `approved`)

---

**Last Updated:** 2026-04-12
**Status:** Troubleshooting guide complete
