# PPMP to LIB Sync - Testing Guide

## Current Status

✅ **Sync is working!** The test script confirms:
- PPMP items ARE being saved with LIB mappings
- Sync function IS running successfully  
- Items ARE being added to the LIB database

## The Issue

The LIB page is showing an **old/different LIB**, not the one that was just updated.

## Solution Applied

I've updated the code to:
1. Return the LIB ID when PPMP is saved
2. Show a notification that items were synced to LIB
3. Tell you to check the LIB page

## How to Test

### Step 1: Clear Browser Cache
**CRITICAL**: Clear your browser cache first!
- Press `Ctrl + Shift + R` (hard refresh)
- Or clear all cache in browser settings

### Step 2: Create a New PPMP with LIB Link

1. Go to PPMP page
2. Click "Create New PPMP"
3. Fill in:
   - Fiscal Year: **2026**
   - PPMP Number: **TEST_1**

4. Click "Add Item"
5. Fill in item details:
   - Description: "Test office supplies"
   - Quantity: 10
   - Unit: boxes
   - Estimated Budget: **50000**

6. **IMPORTANT**: Click "Link to LIB" button
7. Select "Office Supplies Expenses" from the modal
8. Verify the hidden fields are filled (check browser console if needed)

9. Click "Save Draft"

### Step 3: Check the Success Message

You should see:
```
PPMP created successfully

✅ Items have been automatically added to the Line Item Budget (LIB).
Go to LIB page to view them.
```

### Step 4: Go to LIB Page

1. Navigate to the LIB page
2. **Hard refresh** the page (Ctrl + Shift + R)
3. Look for "B. Maintenance & Other Operating Expenses"
4. You should see:
   ```
   Office Supplies Expenses (PPMP #TEST_1 - Item #1)    5020301000    ₱50,000.00
   ```

## If Items Still Don't Appear

### Check 1: Verify Sync Happened
Run the test script again:
```
http://localhost/budgettrack/test_ppmp_lib_sync.php
```

Look for:
- ✅ "Sync completed successfully"
- Items synced: 1 (or more)
- LIB Items After Sync shows your item

### Check 2: Check Which LIB is Displayed

The LIB page shows the **most recent LIB** for your department and fiscal year.

**Problem**: If you have multiple LIBs, it might show the wrong one.

**Solution**: 
1. Go to LIB page
2. Click "Drafts" button
3. Check if there are multiple draft LIBs
4. Click "View" on each one to find the one with your PPMP items

### Check 3: Verify LIB is Not Finalized

If the LIB status is "FINAL" (approved), the sync will fail.

**Check**:
1. Run test script
2. Look at "LIB Status" section
3. If status is "approved", you need to:
   - Delete the finalized LIB (if it's a test)
   - Or create a new PPMP for a different fiscal year

### Check 4: Database Check

Run this SQL query in phpMyAdmin:

```sql
-- Check PPMP items with LIB mappings
SELECT 
    pi.id,
    pi.general_description,
    pi.estimated_budget,
    pi.lib_category,
    pi.lib_particulars,
    pi.lib_account_code,
    p.ppmp_number,
    p.fiscal_year
FROM ppmp_items pi
JOIN ppmp p ON pi.ppmp_id = p.id
WHERE pi.lib_category IS NOT NULL
ORDER BY pi.id DESC
LIMIT 5;

-- Check LIB items
SELECT 
    li.id,
    li.category,
    li.particulars,
    li.account_code,
    li.amount,
    li.source,
    l.fiscal_year,
    l.status
FROM line_item_budget_items li
JOIN line_item_budgets l ON li.lib_id = l.id
WHERE li.particulars LIKE '%PPMP%'
ORDER BY li.id DESC
LIMIT 10;
```

## Expected Results

### In PPMP Items Table
| lib_category | lib_particulars | lib_account_code |
|--------------|-----------------|------------------|
| B. Maintenance & Other Operating Expenses | Office Supplies Expenses | 5020301000 |

### In LIB Items Table
| category | particulars | account_code | amount |
|----------|-------------|--------------|---------|
| B. Maintenance & Other Operating Expenses | Office Supplies Expenses (PPMP #TEST_1 - Item #1) | 5020301000 | 50000.00 |

## Common Issues

### Issue 1: "Link to LIB" button doesn't work
**Solution**: Clear browser cache and hard refresh

### Issue 2: Hidden fields not filled
**Check**: Open browser console (F12) and look for JavaScript errors

### Issue 3: Items synced: 0
**Cause**: LIB mapping fields are empty
**Solution**: Make sure you clicked "Link to LIB" and selected a category

### Issue 4: LIB is finalized
**Cause**: Cannot sync to approved LIB
**Solution**: Create PPMP for a different year or delete the finalized LIB

### Issue 5: Wrong LIB displayed
**Cause**: Multiple LIBs exist for same department/year
**Solution**: 
- Check "Drafts" modal
- View each LIB to find the correct one
- Delete old test LIBs

## Quick Fix: Force Refresh LIB

If items are in database but not showing:

1. Go to LIB page
2. Press `Ctrl + Shift + R` (hard refresh)
3. Check browser console for errors
4. Try clicking "Drafts" then "View" on the most recent LIB

## Verification Checklist

- [ ] Browser cache cleared
- [ ] PPMP created with LIB link
- [ ] "Link to LIB" button clicked
- [ ] Category selected from modal
- [ ] PPMP saved successfully
- [ ] Success message shows "Items synced to LIB"
- [ ] LIB page hard refreshed
- [ ] Test script shows sync successful
- [ ] Database shows LIB items with PPMP reference

## Next Steps

Once you confirm it's working:
1. Delete test PPMPs and LIBs
2. Create real PPMPs with proper data
3. Verify all items sync correctly
4. Test with multiple items
5. Test with different expense categories

---

**Status**: Sync is working in backend, just need to verify frontend display

**Last Updated**: April 12, 2026
