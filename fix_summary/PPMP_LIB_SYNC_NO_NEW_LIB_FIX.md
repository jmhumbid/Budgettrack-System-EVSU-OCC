# PPMP to LIB Sync - Prevent Creating New LIBs

## Issue Identified
When saving a PPMP (draft or final) with LIB mappings, the sync function was **creating a NEW draft LIB** instead of adding items to the **existing draft LIB**.

### User's Expectation
✅ PPMP items should be added to the existing draft LIB

### Previous Behavior
❌ Sync function created a new draft LIB every time, causing:
- Multiple LIBs for the same department/year
- Manual items in old LIB, PPMP items in new LIB
- Confusion about where items are located

## Root Cause

The sync function had this logic:
```php
// Check if LIB exists
$lib = findLIB();

if (!$lib) {
    // CREATE NEW LIB ❌ This was the problem!
    $libId = createNewLIB();
} else {
    $libId = $lib['id'];
}
```

**Problem:** If no LIB was found (or query didn't match), it automatically created a new one.

## Solution Implemented

### Change 1: Prioritize Draft LIBs
Modified the query to prioritize draft LIBs:

```php
SELECT id, status FROM line_item_budgets 
WHERE department_id = ? AND fiscal_year = ? 
ORDER BY 
    CASE WHEN status = 'draft' THEN 0 ELSE 1 END,  -- Draft LIBs first
    created_at DESC                                 -- Then most recent
LIMIT 1
```

**Benefits:**
- ✅ Always finds draft LIBs first
- ✅ Falls back to most recent if no draft exists
- ✅ Prevents creating duplicates

### Change 2: Don't Auto-Create LIBs
Removed the auto-create logic:

```php
if (!$lib) {
    // Don't create - return error instead ✅
    return [
        'success' => false, 
        'message' => 'No LIB found. Please create a LIB first, then sync PPMP items.'
    ];
}
```

**Benefits:**
- ✅ User must create LIB manually first
- ✅ Prevents accidental duplicate LIBs
- ✅ Clear error message guides user

### Change 3: Better Error for Approved LIBs
Improved error message when LIB is finalized:

```php
if ($lib && $lib['status'] === 'approved') {
    return [
        'success' => false, 
        'message' => 'Cannot sync to LIB: LIB is already finalized/approved. Please create a new draft LIB or edit the existing one.'
    ];
}
```

## New Workflow

### Correct Process
1. ✅ **Create a LIB first** (manually via LIB page)
2. ✅ Add manual items to LIB (Water, Labor, Security, etc.)
3. ✅ Create/edit PPMP with LIB mappings
4. ✅ Save PPMP (draft or final)
5. ✅ PPMP items are added to **existing draft LIB**
6. ✅ Both manual and PPMP items are in the same LIB

### What Happens Now

**Scenario A: Draft LIB exists**
```
User saves PPMP with LIB mappings
↓
Sync finds existing draft LIB
↓
Adds PPMP items to that LIB ✅
↓
Success! All items in one LIB
```

**Scenario B: No LIB exists**
```
User saves PPMP with LIB mappings
↓
Sync finds no LIB
↓
Returns error: "Please create a LIB first" ❌
↓
User creates LIB manually
↓
User saves PPMP again
↓
Success! Items added to LIB ✅
```

**Scenario C: Only approved LIB exists**
```
User saves PPMP with LIB mappings
↓
Sync finds only approved LIB
↓
Returns error: "LIB is finalized" ❌
↓
User creates new draft LIB for next period
↓
User saves PPMP again
↓
Success! Items added to new draft LIB ✅
```

## Benefits of This Fix

### 1. No More Duplicate LIBs
- ✅ Sync never creates new LIBs automatically
- ✅ Always uses existing draft LIB
- ✅ One LIB per department/year (as intended)

### 2. All Items in One Place
- ✅ Manual items stay in the LIB
- ✅ PPMP items are added to same LIB
- ✅ No confusion about where items are

### 3. Clear User Guidance
- ✅ Error messages tell user what to do
- ✅ User knows they need to create LIB first
- ✅ User knows when LIB is finalized

### 4. Predictable Behavior
- ✅ Sync always targets draft LIBs
- ✅ No surprise LIB creation
- ✅ User has full control

## Testing Steps

### Test 1: Normal Flow (Draft LIB exists)
1. Create a draft LIB for Computer Studies 2026
2. Add manual items (Water, Labor, etc.)
3. Create PPMP with LIB mappings
4. Save PPMP (draft or final)
5. **Expected:** PPMP items added to existing draft LIB
6. **Verify:** Open LIB page, see both manual and PPMP items

### Test 2: No LIB Exists
1. Delete all LIBs for Computer Studies 2026
2. Create PPMP with LIB mappings
3. Save PPMP
4. **Expected:** Error message "No LIB found. Please create a LIB first"
5. Create a draft LIB
6. Save PPMP again
7. **Expected:** Success! Items added to LIB

### Test 3: Only Approved LIB Exists
1. Create and finalize a LIB (status = approved)
2. Create PPMP with LIB mappings
3. Save PPMP
4. **Expected:** Error message "LIB is already finalized"
5. Create new draft LIB for next period
6. Save PPMP again
7. **Expected:** Success! Items added to new draft LIB

### Test 4: Multiple Draft LIBs (Edge Case)
1. Create 2 draft LIBs for same department/year
2. Create PPMP with LIB mappings
3. Save PPMP
4. **Expected:** Items added to the draft LIB (prioritized)
5. **Note:** User should still delete duplicate LIBs

## Migration Notes

### For Existing Users
If you already have multiple LIBs:

1. **Run diagnostic script:**
   ```
   http://localhost/budgettrack/check_lib_items_source.php
   ```

2. **Delete old LIBs:**
   - Keep the one with all your manual items
   - Delete the empty or duplicate ones

3. **Test PPMP sync:**
   - Save a PPMP with LIB mappings
   - Verify items are added to existing LIB
   - No new LIB should be created

### For New Users
Just follow the normal workflow:
1. Create LIB first
2. Add manual items
3. Save PPMP with LIB mappings
4. Items automatically sync to LIB

## Code Changes

### File Modified
`api/sync_ppmp_to_lib_helper.php`

### Changes Made
1. ✅ Modified LIB query to prioritize draft LIBs
2. ✅ Removed auto-create LIB logic
3. ✅ Added error return when no LIB found
4. ✅ Improved error messages
5. ✅ Enhanced logging

### Lines Changed
- Lines 45-75: LIB lookup and validation logic
- Added draft prioritization in ORDER BY
- Removed INSERT INTO line_item_budgets
- Added error returns instead of auto-create

## Logging

The sync function now logs:

```
PPMP Sync: Found existing LIB #5 (status: draft) for dept 3, year 2026
PPMP Sync: Syncing PPMP #1 to existing LIB #5
PPMP Sync: Added new item #123 to LIB #5 (ref: PPMP #1 - Item #1)
```

Check logs at: `C:\xampp1\apache\logs\error.log`

## Summary

### Before Fix
- ❌ Sync created new LIBs automatically
- ❌ Multiple LIBs for same department/year
- ❌ Manual items in old LIB, PPMP items in new LIB
- ❌ User confusion

### After Fix
- ✅ Sync uses existing draft LIB
- ✅ One LIB per department/year
- ✅ All items in same LIB
- ✅ Clear error messages guide user
- ✅ No more confusion!

---

**Status:** ✅ Fixed - Sync now adds to existing draft LIB instead of creating new ones
**Date:** 2026-04-12
**Impact:** High - Resolves the root cause of missing items issue
