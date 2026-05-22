# Fix PPMP Sync Issue - Quick Steps

## Problem
You saved a PPMP with items linked to "Office Supplies Expenses" but they didn't show up in the LIB.

## Likely Causes
1. **PHP Cache** - Server is using old cached code
2. **No LIB exists** - You need to create a LIB first
3. **LIB is finalized** - Can't sync to approved LIBs

## Solution (3 Steps)

### Step 1: Run Diagnostic
Open this in your browser:
```
http://localhost/budgettrack/debug_ppmp_sync.php
```

This will show you:
- ✅ Your most recent PPMP and its items
- ✅ Which LIBs exist for your department/year
- ✅ What's in each LIB
- ✅ Recent error log entries
- ✅ Specific recommendations for your situation

### Step 2: Clear PHP Cache
Open this in your browser:
```
http://localhost/budgettrack/clear_cache.php
```

This will clear the PHP opcache so the server uses the updated code.

### Step 3: Fix Based on Diagnostic Results

**If diagnostic shows "NO LIB FOUND":**
1. Go to LIB page
2. Click "Create New LIB"
3. Fill in Fiscal Year: 2026
4. Add your manual items (Water, Labor, Security, etc.)
5. Save as draft
6. Go back to PPMP page
7. Save your PPMP again
8. Items should now sync!

**If diagnostic shows "LIB IS APPROVED":**
1. The existing LIB is finalized
2. Create a new draft LIB for the next period
3. Save your PPMP again
4. Items will sync to the new draft LIB

**If diagnostic shows "Target LIB found (draft)":**
1. Cache was the issue
2. You already cleared it in Step 2
3. Go back to PPMP page
4. Save your PPMP again
5. Items should now sync!

## Verify It Worked

After saving PPMP:
1. Go to LIB page
2. You should see your PPMP items with format:
   ```
   Office Supplies Expenses (PPMP #1 - Item #1)
   ```
3. Both manual items and PPMP items should be in the same LIB

## Still Not Working?

Run the full diagnostic:
```
http://localhost/budgettrack/check_lib_items_source.php
```

This shows all LIBs and all items in detail.

---

**Quick Links:**
- [Debug PPMP Sync](http://localhost/budgettrack/debug_ppmp_sync.php)
- [Clear Cache](http://localhost/budgettrack/clear_cache.php)
- [Full Diagnostic](http://localhost/budgettrack/check_lib_items_source.php)
- [PPMP Page](http://localhost/budgettrack/pages/ppmp.php)
- [LIB Page](http://localhost/budgettrack/pages/lib.php)
