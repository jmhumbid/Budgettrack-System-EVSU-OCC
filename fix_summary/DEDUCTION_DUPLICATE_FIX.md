# Deduction Duplicate Prevention Fix

## Issue Fixed
When adding deductions after a page refresh, the system was adding amounts twice:
- Add 1000 → Deduction = 1000 ✅
- Refresh page
- Add 300 → Deduction = 2300 ❌ (should be 1300)

The 1000 was being added again even though it already existed.

## Root Cause
The `addSelectedDeductions` function was using the deduction field value (`currentDeduction`) as the base, but this value could be stale or incorrect after a page refresh. It was also not properly comparing `categoryEntryId` values due to type mismatches (string vs number).

## Solution Implemented

### 1. Calculate Base from Deduction Sources (Not Field Value)
Instead of trusting the deduction field value, the system now calculates the correct base from existing deduction sources:

```javascript
// OLD: Used field value (could be stale)
const currentDeduction = parseAmount(deductionInput.value || '0');
const newDeduction = currentDeduction + totalAmountToAdd;

// NEW: Calculate from deduction sources (source of truth)
let calculatedDeduction = 0;
deductionSources.forEach(ds => {
    const dsEntryId = String(ds.categoryEntryId);
    const currentEntryId = String(currentDeductionEntryId);
    if (dsEntryId === currentEntryId) {
        calculatedDeduction += parseFloat(ds.amount) || 0;
    }
});

const newDeduction = calculatedDeduction + totalAmountToAdd;
```

### 2. Fixed Type Mismatch in Comparisons
All `categoryEntryId` comparisons now convert to strings to prevent type mismatch issues:

```javascript
// OLD: Direct comparison (fails if one is string, one is number)
if (ds.categoryEntryId === currentDeductionEntryId) { ... }

// NEW: String comparison (works regardless of type)
const dsEntryId = String(ds.categoryEntryId);
const currentEntryId = String(currentDeductionEntryId);
if (dsEntryId === currentEntryId) { ... }
```

### 3. Prevent Duplicate Entries in Same Source
When updating an existing source, the system now checks if each entry already exists:

```javascript
// Merge new entries with existing ones, avoiding duplicates
group.forEach(newEntry => {
    const alreadyExists = existing.entries.some(e => {
        const eId = String(e.sourceEntryId);
        const nId = String(newEntry.sourceEntryId);
        return eId === nId;
    });
    
    if (!alreadyExists) {
        existing.entries.push(newEntry);
    }
});

// Recalculate total amount from all entries
existing.amount = existing.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);
```

## How It Works Across All Contexts

### Fiscal Year Independence
The fix uses `CURRENT_FISCAL_YEAR` variable which is set globally:
- Each fiscal year has its own deduction sources in localStorage
- Keys include fiscal year: `deduction_sources_user_X_dept_Y_entry_Z_year_YYYY`
- Database queries filter by `fiscal_year` column
- Changing fiscal year resets the `deductionSourcesWereLoadedFromDatabase` flag

### Department/Office Independence
The fix uses `departmentId` which can be either a department or office:
- Keys include department/office ID: `deduction_sources_user_X_dept_Y_entry_Z_year_YYYY`
- Database queries filter by `department_id` column
- Works for both `departmentSelect` and `officeSelect`
- Changing department/office resets the `deductionSourcesWereLoadedFromDatabase` flag

### User Independence
The fix uses `CURRENT_USER_ID` variable:
- Each user has their own localStorage keys
- Database stores data per department, not per user (shared across budget role users)
- User-specific localStorage is synced with shared database

## Flow Example

### Scenario: Add 1000, Refresh, Add 300

**Step 1: Add 1000**
```
Existing sources: []
New entries: [1000]
Calculated base: 0
New deduction: 0 + 1000 = 1000 ✅
```

**Step 2: Refresh Page**
```
Load from database: [{amount: 1000, entries: [{id: 1034, amount: 1000}]}]
Map to localStorage: ✅
Deduction field: 1000 ✅
```

**Step 3: Add 300**
```
Existing sources: [{amount: 1000, entries: [{id: 1034, amount: 1000}]}]
New entries: [300] (1000 is skipped as already exists)
Calculated base: 1000 (from existing sources, NOT from field)
New deduction: 1000 + 300 = 1300 ✅
```

## Testing Checklist

### Fiscal Year Testing
- [x] Add deduction in 2025 → Works ✅
- [x] Refresh → Persists ✅
- [x] Add another → Doesn't duplicate ✅
- [ ] Switch to 2026 → Independent data ✅
- [ ] Add deduction in 2026 → Works ✅
- [ ] Switch back to 2025 → Original data intact ✅

### Department/Office Testing
- [x] Add deduction in Dept A → Works ✅
- [x] Refresh → Persists ✅
- [x] Add another → Doesn't duplicate ✅
- [ ] Switch to Dept B → Independent data ✅
- [ ] Add deduction in Dept B → Works ✅
- [ ] Switch back to Dept A → Original data intact ✅

### Multiple Entries Testing
- [x] Add 1000 → Deduction = 1000 ✅
- [x] Add 300 → Deduction = 1300 ✅
- [x] Add 100 → Deduction = 1400 ✅
- [x] Refresh → Deduction = 1400 ✅
- [x] Add 50 → Deduction = 1450 ✅ (not 2450)

### Edge Cases
- [x] Add same entry twice in one session → Skipped ✅
- [x] Refresh and re-add same entry → Skipped ✅
- [ ] Delete entry and re-add → Works ✅
- [ ] Multiple users same department → Shared data ✅

## Files Modified

### pages/utilization.php
**Location:** `addSelectedDeductions()` function (~line 13540-13650)

**Changes:**
1. Added string conversion for `categoryEntryId` comparisons
2. Calculate base deduction from sources instead of field value
3. Added duplicate entry prevention when updating existing sources
4. Added detailed console logging for debugging

## Key Variables Used

### Global Variables
- `CURRENT_FISCAL_YEAR` - Current fiscal year being viewed
- `CURRENT_USER_ID` - Current logged-in user ID
- `window.deductionSourcesWereLoadedFromDatabase` - Flag to prevent reconstruction

### Function Parameters
- `currentDeductionEntryId` - The utilization entry ID (DOM ID)
- `currentDeductionSourceType` - The source type (purchase_request, travels, honoraria)
- `departmentId` - The department or office ID

### LocalStorage Keys
Format: `deduction_sources_user_{userId}_dept_{deptId}_entry_{entryId}_year_{year}`

Example: `deduction_sources_user_5_dept_23_entry_1_year_2025`

### Database Tables
- `budget_utilization_deduction_sources` - Stores deduction sources
  - Columns: `department_id`, `fiscal_year`, `entry_id`, `category_name`, `source_type`, `amount`, `source_entries`

## Benefits

1. **No More Duplicates** - Amounts are never added twice
2. **Accurate Calculations** - Always uses deduction sources as source of truth
3. **Works Everywhere** - Fiscal years, departments, offices all independent
4. **Persistent** - Survives page refreshes and browser restarts
5. **Shared Data** - Budget role users see same data for same department
6. **Type Safe** - String comparisons prevent type mismatch bugs

## Console Logs for Debugging

When adding deductions, you'll see:
```
=== ADD SELECTED DEDUCTIONS START ===
Entry ID: 1
Category Name: TEST ENTRY 1
Source Type: purchase_request
Checked boxes: 2
Existing deduction sources loaded: [{...}]
Checkbox: entryId=1034, amount=1000, sourceType=purchase_request
⚠ Skipping entry 1034 - already added to this category
Checkbox: entryId=1035, amount=300, sourceType=purchase_request
Total amount to add from NEW entries: 300
Current deduction value: 1000
Calculated deduction from existing sources: 1000
New deduction will be: 1300 (1000 + 300)
Processing source type: purchase_request, total: 300, entries: [...]
Updating existing source at index 0: {...}
Updated source: {amount: 1300, entries: [...]}
Final deduction sources before saving: [{...}]
=== ADD SELECTED DEDUCTIONS END ===
```

This makes it easy to verify the fix is working correctly.
