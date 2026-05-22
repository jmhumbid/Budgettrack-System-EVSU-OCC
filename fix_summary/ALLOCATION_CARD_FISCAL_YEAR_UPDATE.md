# Allocation Card Fiscal Year Update

## Summary
Updated the Budget Allocation card and its "View Details" modal to improve fiscal year visibility and remove unnecessary student count information.

---

## ✅ Changes Completed

### 1. Fiscal Year Selector in Allocation Card
**Status:** ✅ Already Implemented (Previous Session)

**Location:** `pages/dept_dashboard.php` (line ~360)

**What Exists:**
- Dropdown selector appears when multiple fiscal years are available
- Shows all fiscal years with allocation data
- Selected year is highlighted
- Calls `changeAllocationYear(year)` function on change
- Page reloads with selected year's allocation data

**Code:**
```php
<?php if (count($availableFiscalYears) > 1): ?>
<select id="allocationYearSelect" onchange="changeAllocationYear(this.value)" 
    class="mt-1 px-2 py-1 text-xs bg-white bg-opacity-20 border border-white border-opacity-30 rounded text-white font-semibold hover:bg-opacity-30 transition-colors cursor-pointer">
    <?php foreach ($availableFiscalYears as $year): ?>
    <option value="<?php echo $year; ?>" <?php echo $year == $selectedFiscalYear ? 'selected' : ''; ?> class="text-gray-900">
        FY <?php echo $year; ?>
    </option>
    <?php endforeach; ?>
</select>
<?php endif; ?>
```

---

### 2. Removed "Number of Students" from View Details Modal
**Status:** ✅ Completed

**Location:** `pages/dept_dashboard.php` (function `displayBudgetBreakdown`)

**What Was Removed:**
- "Number of Students" field from main allocation details (line ~839-842)
- "Number of Students" field from sub-departments section (line ~996-997)

**Reason:**
- Not relevant for budget allocation display
- Clutters the interface
- User requested removal

**Before:**
```javascript
html += `
    <div>
        <p class="text-sm text-gray-600">Number of Students</p>
        <p class="text-lg font-semibold">${data.num_students || 0}</p>
    </div>
    <div>
        <p class="text-sm text-gray-600">Total Tuition Fee</p>
        <p class="text-lg font-semibold">₱${parseFloat(data.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
    </div>
`;
```

**After:**
```javascript
html += `
    <div>
        <p class="text-sm text-gray-600">Total Tuition Fee</p>
        <p class="text-lg font-semibold">₱${parseFloat(data.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
    </div>
`;
```

---

### 3. Added "Fiscal Year" to View Details Modal
**Status:** ✅ Completed

**Location:** `pages/dept_dashboard.php` (function `displayBudgetBreakdown`)

**What Was Added:**
1. **Main Allocation Section (line ~833):**
   - Added "Fiscal Year" field at the top
   - Displays in maroon color for emphasis
   - Shows for both departments and offices

2. **Sub-Departments Section (line ~995):**
   - Added "Fiscal Year" field for each sub-department
   - Displays in maroon color
   - Shows before other allocation details

**Code Added:**
```javascript
// Main allocation section
html += `
    <div>
        <p class="text-sm text-gray-600">Fiscal Year</p>
        <p class="text-lg font-semibold text-maroon">${data.fiscal_year || fiscalYear}</p>
    </div>
`;

// Sub-departments section
html += `<div><p class="text-sm text-gray-600">Fiscal Year</p><p class="text-lg font-semibold text-maroon">${childAlloc.fiscal_year || fiscalYear}</p></div>`;
```

**Benefits:**
- Clear identification of which fiscal year the allocation belongs to
- Consistent with user's selection in the allocation card
- Prominent display in maroon color
- Visible for both main department and sub-departments

---

## Modal Layout Changes

### Before:
```
Allocation Details
├── Number of Students: 150
├── Total Tuition Fee: ₱500,000.00
├── 50% Instructional: ₱250,000.00
└── Overall Total: ₱300,000.00
```

### After (Departments):
```
Allocation Details
├── Fiscal Year: 2026
├── Total Tuition Fee: ₱500,000.00
├── 50% Instructional: ₱250,000.00
└── Overall Total: ₱300,000.00
```

### After (Offices):
```
Allocation Details
├── Fiscal Year: 2026
└── Overall Total: ₱300,000.00
```

---

## Display Logic

### For Departments (Non-Fiduciary):
1. **Fiscal Year** (NEW - always shown)
2. Total Tuition Fee (kept)
3. 50% Instructional (kept)
4. Overall Total (kept)

### For Offices (Fiduciary):
1. **Fiscal Year** (NEW - always shown)
2. Overall Total (kept)

**Note:** Offices don't have tuition fees or instructional amounts, so those fields are not shown.

---

## Testing Checklist

### ✅ Allocation Card
- [x] Fiscal year dropdown appears when multiple years exist
- [x] Selected year is highlighted in dropdown
- [x] Changing year reloads page with correct data
- [x] Allocation amount updates based on selected year
- [x] "No allocation set" message shows when amount is 0

### ✅ View Details Modal - Main Allocation
- [x] "Fiscal Year" field appears at the top
- [x] Fiscal year displays in maroon color
- [x] "Number of Students" field is removed
- [x] Total Tuition Fee still displays (for departments)
- [x] 50% Instructional still displays (for departments)
- [x] Overall Total still displays
- [x] Works correctly for offices (no tuition/instructional fields)

### ✅ View Details Modal - Sub-Departments
- [x] "Fiscal Year" field appears for each sub-department
- [x] Fiscal year displays in maroon color
- [x] "Number of Students" field is removed
- [x] Other fields display correctly
- [x] Works for both department and office sub-departments

---

## Files Modified

### `pages/dept_dashboard.php`
**Functions Updated:**
1. `displayBudgetBreakdown()` - Main allocation section
2. `displayBudgetBreakdown()` - Sub-departments section

**Lines Modified:**
- Line ~833-860: Main allocation details section
- Line ~995-1000: Sub-departments details section

**Changes:**
- ❌ Removed: "Number of Students" field (2 locations)
- ✅ Added: "Fiscal Year" field (2 locations)
- ✅ Reordered: Fiscal Year now appears first

---

## User Experience Improvements

### Before:
- ❌ No clear indication of fiscal year in modal
- ❌ "Number of Students" cluttered the display
- ❌ Had to remember which year was selected

### After:
- ✅ Fiscal year prominently displayed in maroon
- ✅ Cleaner, more focused allocation details
- ✅ Clear identification of allocation period
- ✅ Consistent with fiscal year selector in card
- ✅ Works for both departments and offices

---

## Technical Details

### Fiscal Year Display Priority:
1. Uses `data.fiscal_year` from database if available
2. Falls back to `fiscalYear` parameter passed to function
3. Ensures fiscal year is always displayed

### Color Coding:
- **Fiscal Year:** Maroon (`text-maroon`) - matches overall total
- **Overall Total:** Maroon (`text-maroon`) - primary emphasis
- **Other Fields:** Default gray text

### Responsive Design:
- Grid layout: 2 columns on desktop
- Automatically adjusts for mobile
- Fiscal year takes one grid cell
- Overall total takes one grid cell

---

## Integration with Existing Features

### Works With:
- ✅ Fiscal year selector in allocation card
- ✅ `changeAllocationYear()` function
- ✅ Budget breakdown API
- ✅ Sub-departments display
- ✅ Office vs Department logic
- ✅ Fiduciary vs Non-Fiduciary funds

### Maintains:
- ✅ All existing allocation calculations
- ✅ Deduction tracking
- ✅ Category breakdowns
- ✅ Sub-department hierarchies
- ✅ Tab switching functionality

---

## Future Enhancements (Optional)

1. **Add Fiscal Year to Modal Title:**
   ```javascript
   <h2 class="text-2xl font-bold">Budget Allocation Breakdown - FY ${fiscalYear}</h2>
   ```

2. **Add Year Comparison:**
   - Show previous year's allocation
   - Display percentage change
   - Highlight increases/decreases

3. **Add Year Filter in Modal:**
   - Allow viewing different years without closing modal
   - AJAX-based year switching
   - No page reload required

4. **Add Export with Fiscal Year:**
   - Include fiscal year in PDF exports
   - Add to filename: `allocation_FY2026.pdf`

---

## Summary

**What Changed:**
- ❌ Removed "Number of Students" from modal (2 locations)
- ✅ Added "Fiscal Year" to modal (2 locations)
- ✅ Fiscal year selector already exists in allocation card

**Impact:**
- Cleaner, more focused allocation display
- Clear fiscal year identification
- Better user experience
- Consistent with user's selection

**Status:** ✅ All Changes Complete

---

**Date Completed:** April 15, 2026  
**Files Modified:** 1 (`pages/dept_dashboard.php`)  
**Functions Updated:** 1 (`displayBudgetBreakdown`)  
**Lines Changed:** ~20 lines  
**Status:** ✅ Complete
