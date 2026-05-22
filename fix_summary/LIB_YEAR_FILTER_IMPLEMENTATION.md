# LIB Year Filter Implementation

## Overview
Added fiscal year filtering functionality to the LIB page, allowing users to view Line Item Budgets from different years (past and future). Also expanded the year range in the auto-generate modal to include 2024-2028.

## Changes Made

### 1. Frontend Changes - `pages/lib.php`

#### A. Added Year Filter Dropdown
Added a year filter dropdown above the action buttons:
```html
<!-- Year Filter -->
<div class="mb-4 flex items-center gap-3 no-print">
    <label class="text-sm font-semibold text-gray-700">Filter by Year:</label>
    <select id="yearFilter" onchange="filterLIBByYear()" class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon bg-white">
        <option value="">All Years</option>
        <option value="2024">2024</option>
        <option value="2025">2025</option>
        <option value="2026" selected>2026</option>
        <option value="2027">2027</option>
        <option value="2028">2028</option>
    </select>
</div>
```

**Features:**
- Default selection: 2026 (current year)
- "All Years" option to show all LIBs
- Years 2024-2028 available
- Styled to match the page design

#### B. Updated `loadLIBList()` Function
Modified to accept an optional year parameter:
```javascript
function loadLIBList(filterYear = null) {
    const departmentId = window.DEPARTMENT_ID || '';
    let url = `../api/get_lib_list.php${departmentId ? '?department_id=' + departmentId : ''}`;
    
    // Add year filter if provided
    if (filterYear) {
        url += (departmentId ? '&' : '?') + 'year=' + filterYear;
    }
    
    // ... rest of the function
}
```

**Changes:**
- Added `filterYear` parameter (defaults to null)
- Constructs URL with year parameter when provided
- Updated empty state message to show filtered year

#### C. Added `filterLIBByYear()` Function
New function to handle year filter changes:
```javascript
function filterLIBByYear() {
    const yearFilter = document.getElementById('yearFilter').value;
    loadLIBList(yearFilter || null);
}
```

**Behavior:**
- Reads selected year from dropdown
- Calls `loadLIBList()` with the year parameter
- Passes null if "All Years" is selected

#### D. Updated Auto-Generate Year Dropdown
Expanded the year range in the auto-generate modal:
```html
<select id="autoGenYear" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600">
    <option value="2024">2024</option>
    <option value="2025">2025</option>
    <option value="2026" selected>2026</option>
    <option value="2027">2027</option>
    <option value="2028">2028</option>
</select>
```

**Changes:**
- Added 2027 and 2028 options
- Reordered chronologically (2024-2028)
- Set 2026 as default selection

### 2. Backend Changes - `api/get_lib_list.php`

#### Added Year Filtering Logic
```php
// Add year filter if provided
$yearFilter = $_GET['year'] ?? null;
if ($yearFilter) {
    $sql .= " AND l.fiscal_year LIKE ?";
    $params[] = "%$yearFilter%";
}
```

**Features:**
- Accepts `year` parameter from query string
- Uses LIKE operator to match fiscal year (e.g., "FY 2026" matches "2026")
- Maintains existing department and status filtering

## How It Works

### User Flow
1. User opens LIB page
2. Sees year filter dropdown (default: 2026)
3. Can select different years or "All Years"
4. Page automatically reloads LIB list for selected year
5. If no LIBs found for that year, shows appropriate message

### Auto-Generate Flow
1. User clicks "Auto-Generate from Allocations"
2. Modal opens with year selector (2024-2028)
3. User selects year and clicks "Generate LIB"
4. System fetches allocations for that year
5. Generates LIB items with UACS codes

### Technical Flow
```
User selects year
    ↓
filterLIBByYear() called
    ↓
loadLIBList(year) called
    ↓
API request: get_lib_list.php?department_id=X&year=2026
    ↓
Backend filters by fiscal_year LIKE '%2026%'
    ↓
Returns filtered LIB list
    ↓
Frontend displays most recent LIB for that year
```

## Benefits

1. **Historical View**: Users can view past years' LIBs (2024, 2025)
2. **Future Planning**: Users can create LIBs for future years (2027, 2028)
3. **Better Organization**: Separate LIBs by fiscal year
4. **Flexible Filtering**: "All Years" option for complete overview
5. **Consistent UX**: Same filtering applies to main view, drafts, and history

## Testing

### Test Cases
1. **Default View**: Page loads with 2026 selected, shows 2026 LIBs
2. **Year Change**: Select 2025, page shows 2025 LIBs
3. **All Years**: Select "All Years", shows all LIBs
4. **No Data**: Select year with no LIBs, shows "No LIB found for [year]"
5. **Auto-Generate**: Select 2027 in modal, generates LIB for 2027

### Expected Behavior
- Year filter persists during session
- Drafts and history modals respect year filter
- Auto-generate works for all years (2024-2028)
- Empty state shows appropriate message with year

## Files Modified

1. **pages/lib.php**
   - Added year filter dropdown UI
   - Updated `loadLIBList()` function
   - Added `filterLIBByYear()` function
   - Updated auto-generate year dropdown

2. **api/get_lib_list.php**
   - Added year filtering logic
   - Maintains backward compatibility (works without year parameter)

## Database Schema
No database changes required. Uses existing `fiscal_year` column in `line_item_budgets` table.

## Notes

- Year filter uses LIKE operator to match fiscal year format (e.g., "FY 2026")
- Filter is applied on backend for security and performance
- Frontend dropdown can be easily extended to add more years
- Compatible with existing department and status filtering

## Future Enhancements

1. **Dynamic Year Range**: Auto-populate years based on available data
2. **Year Range Filter**: Allow filtering by year range (e.g., 2024-2026)
3. **Fiscal Year Selector**: Add fiscal year start/end month configuration
4. **Year Comparison**: Side-by-side comparison of different years
5. **Year Statistics**: Show summary statistics per year

## Troubleshooting

### Issue: Year filter not working
- Check browser console for errors
- Verify `get_lib_list.php` receives year parameter
- Check database fiscal_year format matches filter

### Issue: No LIBs showing for a year
- Verify LIBs exist for that year in database
- Check fiscal_year column format (should be "FY YYYY")
- Ensure department_id matches user's department

### Issue: Auto-generate not showing 2027
- Clear browser cache
- Verify dropdown HTML was updated
- Check if allocations exist for 2027
