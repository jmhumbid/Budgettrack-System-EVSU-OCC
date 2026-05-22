# Deduction Sources Persistence Fix

## Problem
When adding deductions from Purchase Requests and Travels in the Utilization page:
- The deduction amount (e.g., 40,000) persists after page refresh ✓
- BUT the checkboxes become unchecked after refresh ✗
- Users have to remember which entries they selected

## Root Cause
The system was only saving the total deduction amount to the database, but NOT which specific Purchase Request or Travel entries contributed to that amount. This information was only stored in browser localStorage, which doesn't persist across page refreshes reliably.

## Solution Implemented

### 1. Created New Database Table
**Table:** `budget_utilization_deduction_sources`

Stores the relationship between utilization entries and their deduction sources:
- `department_id`: Which department
- `fiscal_year`: Which fiscal year
- `entry_id`: DOM entry ID (temporary, used for mapping)
- `category_name`: Expense category name (stable identifier)
- `source_type`: 'purchase_request', 'travels', or 'honoraria'
- `amount`: Total amount from this source type
- `source_entries`: JSON array of specific entry IDs and amounts

### 2. Updated Save Process
**File:** `api/save_utilization_entry.php`
- Now saves deduction sources to the database
- Uses category name as the stable identifier
- Stores complete information about which entries were selected

**File:** `pages/utilization.php` - `saveUtilizationToLocalStorage()`
- Collects all deduction sources from localStorage
- Includes them in the API request
- Uses current category name from DOM for accuracy

### 3. Updated Load Process
**File:** `api/load_utilization_entries.php`
- Changed from reading `utilization_summaries` table to `budget_utilization_entries` table
- Now loads deduction sources from `budget_utilization_deduction_sources` table
- Returns both entries and deduction sources in the response

**File:** `pages/utilization.php` - `loadUtilizationEntries()`
- Receives deduction sources from API
- Maps them to new DOM entry IDs using category names
- Restores them to localStorage with correct entry IDs

### 4. Enhanced Checkbox Logic
**File:** `pages/utilization.php` - `showDeductionEntries()`
- Checks both `deduction_selections` and `deduction_sources` in localStorage
- Uses string comparison for reliable matching
- Added comprehensive logging for debugging

## How It Works Now

### When Saving:
1. User selects Purchase Request/Travel entries
2. Clicks "Add Selected"
3. System saves to localStorage with DOM entry ID
4. When page saves, collects all deduction sources
5. Sends to API with category name as identifier
6. API saves to `budget_utilization_deduction_sources` table

### When Loading (After Refresh):
1. API loads entries from `budget_utilization_entries`
2. API loads deduction sources from `budget_utilization_deduction_sources`
3. Frontend creates DOM entries (new entry IDs)
4. Maps deduction sources to new DOM IDs using category names
5. Restores to localStorage with new entry IDs
6. When modal opens, checkboxes are checked based on localStorage data

## Testing Steps

1. **Setup:**
   - Navigate to Utilization page
   - Select a department
   - Add expense category "test" with budget 100,000

2. **Add Deductions:**
   - Add Purchase Request entry with amount 20,000
   - Add Travel entry with amount 20,000
   - Click "+" button next to deduction field
   - Select Purchase Request, check the 20,000 entry
   - Click "Add Selected"
   - Click "+" button again
   - Select Travels, check the 20,000 entry
   - Click "Add Selected"
   - Deduction should show 40,000

3. **Verify Persistence:**
   - Refresh the page (F5)
   - Deduction should still show 40,000 ✓
   - Click "+" button, select Purchase Request
   - The 20,000 entry should be CHECKED ✓
   - Click "+" button, select Travels
   - The 20,000 entry should be CHECKED ✓

4. **Verify Database:**
   - Run: `php test_deduction_sources.php`
   - Should show table exists and contains data

## Debugging

If checkboxes are still unchecked after refresh:

1. **Open Browser Console (F12)**
2. **Look for these logs:**
   - "Mapping X deduction sources to new DOM entry IDs"
   - "Category 'test' -> DOM entry ID Y"
   - "Saved to localStorage key: ..."
   - "Source has X entries: [...]"

3. **When opening modal:**
   - "Checking deduction sources for entry X"
   - "✓ Found entry Y in deduction sources"

4. **Check localStorage:**
   ```javascript
   // In browser console
   Object.keys(localStorage).filter(k => k.includes('deduction_sources'))
   ```

5. **Check database:**
   ```sql
   SELECT * FROM budget_utilization_deduction_sources;
   ```

## Files Modified

1. `pages/utilization.php`
   - `saveUtilizationToLocalStorage()` - Collect and send deduction sources
   - `loadUtilizationEntries()` - Receive and map deduction sources
   - `showDeductionEntries()` - Enhanced checkbox checking logic

2. `api/save_utilization_entry.php`
   - Create `budget_utilization_deduction_sources` table
   - Save deduction sources to database

3. `api/load_utilization_entries.php`
   - Changed from `utilization_summaries` to `budget_utilization_entries`
   - Load deduction sources from database

## Key Improvements

1. **Database Persistence:** Deduction sources now saved to database, not just localStorage
2. **Category Name Mapping:** Uses stable category names instead of volatile entry IDs
3. **Comprehensive Logging:** Detailed console logs for debugging
4. **String Comparison:** Consistent string comparison for reliability
5. **Proper Table Usage:** Reads from same table that writes to

## Notes

- Category names must match exactly (case-sensitive, whitespace-sensitive)
- Entry IDs change on each page load, but category names stay the same
- The system now works like a proper database-backed application
- All budget role users will see the same deduction selections
