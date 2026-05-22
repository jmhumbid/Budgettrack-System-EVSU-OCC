# Deduction Sources Debugging Guide

## Issue
Deduction sources are being saved as `manual_add` with empty entries instead of `purchase_request`/`travels` with actual entry IDs, causing checkboxes to uncheck after page refresh.

## Root Cause Analysis

The system has multiple layers where deduction sources can be set:
1. **User Selection** - When user checks boxes in the modal
2. **Add Selected** - When user clicks "Add Selected" button
3. **Save to LocalStorage** - When `saveUtilizationToLocalStorage()` is called
4. **Save to Database** - When API receives the data
5. **Load from Database** - When page refreshes
6. **Reconstruction** - When system tries to rebuild sources from PR/Travel entries

## Expected Flow

### When Adding Deductions:
1. User clicks "+ Add Deduction" button
2. `showDeductionEntries(entryId, sourceType)` is called
   - Sets `currentDeductionSourceType` = 'purchase_request' or 'travels'
3. User checks boxes
4. User clicks "Add Selected"
5. `addSelectedDeductions()` is called
   - Creates deduction sources with correct `sourceType`
   - Saves to localStorage with key: `deduction_sources_user_X_dept_Y_entry_Z_year_YYYY`
6. `saveUtilizationToLocalStorage()` is called
   - Collects all deduction sources from localStorage
   - Sends to database with `source_type` field
7. Database saves to `budget_utilization_deduction_sources` table

### When Loading After Refresh:
1. `loadUtilizationEntries()` fetches from database
2. Database returns `deduction_sources` array with `sourceType` field
3. Sources stored in `window.pendingDeductionSources`
4. After DOM entries created, sources mapped to new entry IDs using category names
5. Saved to localStorage
6. `deductionSourcesWereLoaded` flag set to `true`
7. Reconstruction is SKIPPED because sources were loaded from DB

## Debug Steps

### Step 1: Check Console Logs When Adding Deductions

Open browser console and add a deduction. Look for:

```
=== ADD SELECTED DEDUCTIONS START ===
Entry ID: 1
Category Name: test
Source Type: purchase_request  <-- MUST be 'purchase_request' or 'travels', NOT 'manual_add'
Checked boxes: 1
```

**If Source Type is undefined or null:**
- Problem: `currentDeductionSourceType` not set correctly
- Check: `showDeductionEntries()` function is being called with correct sourceType parameter

### Step 2: Check What's Being Saved to LocalStorage

Look for:
```
Final deduction sources before saving: [
  {
    categoryEntryId: "1",
    categoryName: "test",
    sourceType: "purchase_request",  <-- MUST be correct type
    amount: 20000,
    entries: [
      {
        sourceEntryId: "123",
        amount: 20000
      }
    ]
  }
]
```

**If sourceType is 'manual_add':**
- Problem: The source type is being set incorrectly in `addSelectedDeductions()`
- Check: Line where `sourceGroups` is created - verify `entry.sourceType` is correct

### Step 3: Check What's Being Sent to Database

Look for:
```
=== SAVING TO DATABASE ===
Deduction sources: [
  {
    entry_id: "1",
    category_name: "test",
    source_type: "purchase_request",  <-- MUST be correct type
    amount: 20000,
    entries: [...]
  }
]
```

**If source_type is 'manual_add':**
- Problem: LocalStorage has wrong data OR collection logic is wrong
- Check: `saveUtilizationToLocalStorage()` line where it reads `source.sourceType`

### Step 4: Check What's Loaded from Database

After refresh, look for:
```
=== LOAD UTILIZATION ENTRIES FROM DATABASE ===
Deduction sources from DB: [
  {
    entry_id: "1",
    categoryName: "test",
    sourceType: "purchase_request",  <-- MUST be correct type
    amount: 20000,
    entries: [...]
  }
]
```

**If sourceType is 'manual_add':**
- Problem: Database has wrong data
- Check: Database table `budget_utilization_deduction_sources` directly
- SQL: `SELECT * FROM budget_utilization_deduction_sources WHERE department_id = X AND fiscal_year = YYYY`

### Step 5: Check Mapping After Load

Look for:
```
=== MAPPING DEDUCTION SOURCES FROM DATABASE ===
Mapping 1 deduction sources to new DOM entry IDs
Pending deduction sources: [
  {
    sourceType: "purchase_request",  <-- MUST be correct type
    ...
  }
]
```

### Step 6: Verify Reconstruction is Skipped

Look for:
```
Deduction sources already loaded from database, skipping reconstruction to preserve them
```

**If you see "No deduction sources loaded from database, attempting reconstruction":**
- Problem: `window.pendingDeductionSources` is empty
- Check: Step 4 - database is not returning deduction sources

## Common Issues

### Issue 1: sourceType is undefined in addSelectedDeductions
**Cause:** `currentDeductionSourceType` not set
**Fix:** Verify `showDeductionEntries()` is called before modal opens

### Issue 2: Reconstruction overwrites correct sources
**Cause:** `deductionSourcesWereLoaded` flag not working
**Fix:** Verify `window.pendingDeductionSources` has data before mapping

### Issue 3: Database has manual_add instead of purchase_request
**Cause:** Data was saved before fix was applied
**Fix:** Clear database and re-add deductions:
```sql
DELETE FROM budget_utilization_deduction_sources WHERE department_id = X AND fiscal_year = YYYY;
```

### Issue 4: Checkboxes show as unchecked but deduction amount persists
**Cause:** Deduction sources have wrong sourceType or empty entries array
**Fix:** Check localStorage key: `deduction_sources_user_X_dept_Y_entry_Z_year_YYYY`
- Verify sourceType is correct
- Verify entries array is not empty

## Testing Checklist

- [ ] Add Purchase Request deduction - check console logs
- [ ] Verify localStorage has correct sourceType
- [ ] Verify database save shows correct source_type
- [ ] Refresh page
- [ ] Verify database load shows correct sourceType
- [ ] Verify mapping completes successfully
- [ ] Verify reconstruction is skipped
- [ ] Open modal - checkboxes should be checked
- [ ] Test with multiple fiscal years
- [ ] Test with multiple categories
- [ ] Test with both Purchase Request and Travels

## SQL Queries for Debugging

### Check deduction sources in database:
```sql
SELECT * FROM budget_utilization_deduction_sources 
WHERE department_id = ? AND fiscal_year = ?;
```

### Check utilization entries:
```sql
SELECT * FROM budget_utilization_entries 
WHERE department_id = ? AND fiscal_year = ?;
```

### Clear deduction sources for testing:
```sql
DELETE FROM budget_utilization_deduction_sources 
WHERE department_id = ? AND fiscal_year = ?;
```

## Expected Console Output (Success Case)

```
=== ADD SELECTED DEDUCTIONS START ===
Entry ID: 1
Category Name: test
Source Type: purchase_request
Checked boxes: 1
Existing deduction sources loaded: []
Processing source type: purchase_request, total: 20000, entries: [...]
Adding new source: {...}
Final deduction sources before saving: [...]
Saved to localStorage key: deduction_sources_user_1_dept_2_entry_1_year_2025
=== ADD SELECTED DEDUCTIONS END ===

=== SAVING TO DATABASE ===
Department ID: 2
Fiscal Year: 2025
Deduction sources count: 1
Deduction sources: [
  {
    entry_id: "1",
    category_name: "test",
    source_type: "purchase_request",
    amount: 20000,
    entries: [...]
  }
]

[After Refresh]

=== LOAD UTILIZATION ENTRIES FROM DATABASE ===
Department ID: 2
Fiscal Year: 2025
Deduction sources from DB: [
  {
    sourceType: "purchase_request",
    categoryName: "test",
    amount: 20000,
    entries: [...]
  }
]

=== MAPPING DEDUCTION SOURCES FROM DATABASE ===
Mapping 1 deduction sources to new DOM entry IDs
✓ Mapped deduction source for "test" to DOM entry ID 1

Deduction sources already loaded from database, skipping reconstruction to preserve them
```
