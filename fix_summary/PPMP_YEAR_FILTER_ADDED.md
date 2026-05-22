# PPMP Year Filter Implementation

## Feature Added
Added a fiscal year filter dropdown on the PPMP page so users can view PPMPs from different years.

## Changes Made

### 1. Added Year Filter Dropdown (pages/ppmp.php)
**Location:** Above the action buttons

**HTML:**
```html
<div class="mb-4 flex items-center gap-3 no-print">
    <label class="text-sm font-semibold text-gray-700">Filter by Year:</label>
    <select id="yearFilter" onchange="filterPPMPByYear()" class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon bg-white">
        <option value="">All Years</option>
        <option value="2024">2024</option>
        <option value="2025">2025</option>
        <option value="2026" selected>2026</option>
        <option value="2027">2027</option>
        <option value="2028">2028</option>
        <option value="2029">2029</option>
        <option value="2030">2030</option>
    </select>
</div>
```

**Features:**
- ✅ Dropdown with years 2024-2030
- ✅ Default selection: 2026
- ✅ "All Years" option to show all PPMPs
- ✅ Hidden in print view (no-print class)

### 2. Updated JavaScript Functions (assets/js/ppmp.js)

#### Modified: loadPPMPList()
**Before:**
```javascript
function loadPPMPList() {
    const departmentId = window.DEPARTMENT_ID || '';
    fetch(`../api/get_ppmp_list.php${departmentId ? '?department_id=' + departmentId : ''}`)
    // ...
}
```

**After:**
```javascript
function loadPPMPList(filterYear = null) {
    const departmentId = window.DEPARTMENT_ID || '';
    let url = `../api/get_ppmp_list.php${departmentId ? '?department_id=' + departmentId : ''}`;
    
    // Add year filter if provided
    if (filterYear) {
        url += (departmentId ? '&' : '?') + 'fiscal_year=' + filterYear;
    }
    
    fetch(url)
    // ...
}
```

**Changes:**
- ✅ Added `filterYear` parameter
- ✅ Appends `fiscal_year` to API URL when filter is active
- ✅ Shows empty state with year info when no PPMPs found

#### Modified: loadCurrentPPMP()
**Before:**
```javascript
function loadCurrentPPMP(ppmpType = 'ppmp', forceReload = false) {
    // ...
    fetch(`../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=${ppmpType}`)
}
```

**After:**
```javascript
function loadCurrentPPMP(ppmpType = 'ppmp', forceReload = false, filterYear = null) {
    // ...
    let url = `../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=${ppmpType}`;
    if (filterYear) {
        url += `&fiscal_year=${filterYear}`;
    }
    fetch(url)
}
```

**Changes:**
- ✅ Added `filterYear` parameter
- ✅ Supports filtering for both PPMP and Supplemental tabs

#### Added: filterPPMPByYear()
**New Function:**
```javascript
function filterPPMPByYear() {
    const yearFilter = document.getElementById('yearFilter').value;
    const currentTab = localStorage.getItem('activePPMPTab') || 'ppmp';
    
    // Reload the current tab with year filter
    if (currentTab === 'ppmp') {
        loadCurrentPPMP('ppmp', true, yearFilter || null);
    } else if (currentTab === 'supplemental') {
        loadCurrentPPMP('supplemental', true, yearFilter || null);
    }
}
```

**Features:**
- ✅ Gets selected year from dropdown
- ✅ Detects current active tab (PPMP or Supplemental)
- ✅ Reloads appropriate tab with year filter
- ✅ Forces reload to show filtered results

### 3. Backend API (api/get_ppmp_list.php)
**No changes needed!** The API already supports fiscal_year filtering:

```php
if ($fiscalYear) {
    $sql .= " AND p.fiscal_year = ?";
    $params[] = $fiscalYear;
}
```

## How It Works

### User Flow
1. User opens PPMP page
2. Sees year filter dropdown (default: 2026)
3. Selects different year (e.g., 2025)
4. Page automatically reloads showing only PPMPs from 2025
5. Works for both PPMP and Supplemental tabs

### Technical Flow
```
User selects year
    ↓
filterPPMPByYear() called
    ↓
Gets selected year value
    ↓
Detects current tab (PPMP or Supplemental)
    ↓
Calls loadCurrentPPMP(type, true, year)
    ↓
Builds API URL with fiscal_year parameter
    ↓
Fetches filtered PPMPs from backend
    ↓
Displays results or empty state
```

### Empty State
When no PPMPs found for selected year:
```
No PPMP Found for 2025
Create your first PPMP to get started
```

## Benefits

### 1. Better Organization
- ✅ View PPMPs by specific year
- ✅ Separate current year from historical data
- ✅ Easy to find old PPMPs

### 2. Improved Performance
- ✅ Loads only PPMPs for selected year
- ✅ Faster page load with filtered data
- ✅ Less clutter on screen

### 3. Better UX
- ✅ Simple dropdown interface
- ✅ Instant filtering (no page reload)
- ✅ Works with both PPMP and Supplemental tabs
- ✅ Remembers current tab when filtering

### 4. Consistent with LIB Page
- ✅ Same year filter as LIB page
- ✅ Consistent user experience
- ✅ Same year options (2024-2030)

## Testing Steps

### Test 1: Filter by Year
1. Go to PPMP page
2. Select year: 2025
3. **Expected:** Only PPMPs from 2025 shown
4. Select "All Years"
5. **Expected:** All PPMPs shown

### Test 2: Empty State
1. Select a year with no PPMPs (e.g., 2024)
2. **Expected:** "No PPMP Found for 2024" message

### Test 3: Tab Switching
1. Filter by 2026 on PPMP tab
2. Switch to Supplemental tab
3. **Expected:** Supplemental PPMPs from 2026 shown
4. Filter still active

### Test 4: Create New PPMP
1. Filter by 2026
2. Create new PPMP for 2026
3. **Expected:** New PPMP appears in filtered view

### Test 5: Default Year
1. Refresh page
2. **Expected:** Year filter defaults to 2026
3. **Expected:** Shows PPMPs from 2026

## Files Modified

### 1. pages/ppmp.php
**Lines:** ~407 (added year filter dropdown)
**Changes:** Added HTML for year filter dropdown

### 2. assets/js/ppmp.js
**Lines:** ~580-640 (loadPPMPList function)
**Lines:** ~1630-1660 (loadCurrentPPMP function)
**Lines:** ~640-650 (new filterPPMPByYear function)
**Changes:** 
- Added year filtering support to load functions
- Created new filter function
- Added empty state handling

### 3. api/get_ppmp_list.php
**No changes needed** - Already supports fiscal_year parameter

## Usage

### For Users
1. Open PPMP page
2. Use year dropdown to filter PPMPs
3. Select "All Years" to see everything
4. Filter works on both PPMP and Supplemental tabs

### For Developers
```javascript
// Load PPMPs for specific year
loadPPMPList('2026');

// Load current PPMP with year filter
loadCurrentPPMP('ppmp', true, '2026');

// Filter by year (called by dropdown)
filterPPMPByYear();
```

## Future Enhancements

### Possible Improvements
1. Add year range filter (e.g., 2024-2026)
2. Add month filter within year
3. Add status filter (draft, approved)
4. Combine filters (year + status)
5. Save filter preference in localStorage
6. Add "Clear Filters" button

### Integration Ideas
1. Sync year filter with LIB page
2. Add year filter to dashboard
3. Add year filter to reports
4. Export filtered PPMPs to PDF/Excel

## Summary

### Before
- ❌ All PPMPs shown together
- ❌ Hard to find specific year
- ❌ Cluttered view
- ❌ No way to filter

### After
- ✅ Filter by fiscal year
- ✅ Easy to find PPMPs
- ✅ Clean, organized view
- ✅ Works on both tabs
- ✅ Consistent with LIB page

---

**Status:** ✅ COMPLETE - Year filter added to PPMP page
**Date:** 2026-04-12
**Impact:** MEDIUM - Improves usability and organization
**Testing:** Ready for user testing
