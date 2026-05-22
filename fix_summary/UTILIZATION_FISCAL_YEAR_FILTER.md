# Utilization View Fiscal Year Filter

## Overview

Added a fiscal year filter to the Utilization View page, allowing users to view their utilization data for different years instead of seeing all years mixed together.

## Features

### 1. **Fiscal Year Dropdown**
- Located at the top of the page, below the tab navigation
- Shows years from 2024 to 2030
- Default selection: 2026 (current year)
- "All Years" option to view all data

### 2. **Real-Time Filtering**
- Filters utilization summaries by selected year
- Filters history modal by selected year
- Updates immediately when year is changed

### 3. **Visual Feedback**
- Shows currently selected year in the filter bar
- Updates modal headers to show filtered year
- Clear indication of what data is being viewed

## User Interface

### Filter Bar Design:
```
┌─────────────────────────────────────────────────────────────┐
│ 📅 Filter by Fiscal Year: [2026 ▼]    Viewing: 2026        │
└─────────────────────────────────────────────────────────────┘
```

### Features:
- **Calendar icon**: Visual indicator for date filtering
- **Dropdown**: Easy year selection
- **Current year display**: Shows what's being viewed
- **Clean design**: Matches existing UI style

## How It Works

### For Users:

1. **View Current Year** (Default):
   - Page loads showing 2026 data by default
   - See only current year's utilization summaries

2. **View Different Year**:
   - Click the fiscal year dropdown
   - Select desired year (e.g., 2025, 2024)
   - Page automatically reloads with that year's data

3. **View All Years**:
   - Select "All Years" from dropdown
   - See utilization data from all fiscal years
   - Useful for historical comparison

4. **History Modal**:
   - When viewing history, it shows only the selected year's history
   - Modal header indicates the filtered year
   - Example: "History for: Computer Studies (2026)"

## Technical Implementation

### Files Modified:

1. **`pages/utilization__view.php`**:
   - Added fiscal year filter UI
   - Added `currentFiscalYear` variable
   - Added `filterByFiscalYear()` function
   - Updated `loadSavedSummaries()` to use fiscal year filter
   - Updated `showHistory()` to use fiscal year filter

### Key Changes:

#### 1. Filter UI (HTML)
```html
<div class="mb-6 flex items-center gap-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
    <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
        <svg class="w-5 h-5 text-maroon">...</svg>
        Filter by Fiscal Year:
    </label>
    <select id="fiscalYearFilter" onchange="filterByFiscalYear()">
        <option value="">All Years</option>
        <option value="2024">2024</option>
        <option value="2025">2025</option>
        <option value="2026" selected>2026</option>
        ...
    </select>
    <div id="fiscalYearInfo">
        <span>Viewing:</span> 
        <span id="currentFiscalYearText">2026</span>
    </div>
</div>
```

#### 2. JavaScript Functions
```javascript
let currentFiscalYear = '2026'; // Default to current year

function filterByFiscalYear() {
    const fiscalYearFilter = document.getElementById('fiscalYearFilter');
    currentFiscalYear = fiscalYearFilter.value;
    
    // Update display text
    document.getElementById('currentFiscalYearText').textContent = 
        currentFiscalYear || 'All Years';
    
    // Reload summaries with the selected fiscal year
    loadSavedSummaries();
    
    // Reload history if modal is open
    if (historyModal is open) {
        showHistory();
    }
}
```

#### 3. API Calls with Fiscal Year
```javascript
// Load summaries
let apiUrl = `../api/load_utilization_summaries.php?department_id=${departmentId}`;
if (currentFiscalYear) {
    apiUrl += `&fiscal_year=${currentFiscalYear}`;
}

// Load history
let historyUrl = `../api/get_utilization_history.php?department_id=${departmentId}`;
if (currentFiscalYear) {
    historyUrl += `&fiscal_year=${currentFiscalYear}`;
}
```

### APIs Used:

Both APIs already supported fiscal year filtering:

1. **`api/load_utilization_summaries.php`**:
   - Accepts `fiscal_year` parameter
   - Filters summaries by year if provided
   - Returns all years if not provided

2. **`api/get_utilization_history.php`**:
   - Accepts `fiscal_year` parameter
   - Filters history entries by year if provided
   - Returns all years if not provided

## Benefits

### 1. **Better Organization**
- View one year at a time
- Less clutter on the page
- Easier to focus on specific year's data

### 2. **Historical Access**
- View previous years' utilization
- Compare different years
- Access archived data easily

### 3. **Performance**
- Loads less data when filtering by year
- Faster page load times
- More responsive interface

### 4. **User-Friendly**
- Simple dropdown interface
- Clear indication of what's being viewed
- Intuitive year selection

## Use Cases

### Scenario 1: Review Current Year
```
User: Opens utilization view
System: Shows 2026 data by default
User: Reviews current year's utilization
```

### Scenario 2: Compare with Previous Year
```
User: Selects "2025" from dropdown
System: Loads 2025 utilization data
User: Compares with current year
User: Selects "2026" to go back
```

### Scenario 3: View All Historical Data
```
User: Selects "All Years" from dropdown
System: Loads all utilization data
User: Sees complete history across all years
```

### Scenario 4: Check Specific Year's History
```
User: Selects "2024" from dropdown
User: Clicks "History" button
System: Shows only 2024 history entries
Modal Header: "History for: Department (2024)"
```

## Edge Cases Handled

### 1. **No Data for Selected Year**
- Shows "No summaries found" message
- User can select different year
- Clear feedback

### 2. **All Years Selected**
- Shows data from all fiscal years
- Sorted by most recent first
- No filtering applied

### 3. **History Modal Open**
- When year is changed, history reloads automatically
- Modal header updates to show new year
- Seamless experience

### 4. **Default Year**
- Page loads with current year (2026) selected
- Most common use case
- Can be changed immediately

## Future Enhancements (Optional)

1. **Year Range**: Allow selecting a range of years (e.g., 2024-2026)
2. **Quick Filters**: Add buttons for "Current Year", "Last Year", "All Years"
3. **Comparison View**: Side-by-side comparison of two years
4. **Export by Year**: Export data for specific year only
5. **Year Statistics**: Show summary stats for each year in dropdown

## Testing Checklist

- [x] Fiscal year dropdown appears on page
- [x] Default year is 2026
- [x] Changing year reloads summaries
- [x] "All Years" option shows all data
- [x] History modal respects fiscal year filter
- [x] Modal header shows filtered year
- [x] Current year display updates correctly
- [x] API calls include fiscal_year parameter
- [x] No data message shows when year has no data
- [x] Filter persists when switching tabs (if applicable)

## Status

✅ **IMPLEMENTED** - Fiscal year filter is fully functional!

## User Guide

**To filter by fiscal year:**
1. Look for the "Filter by Fiscal Year" dropdown at the top of the page
2. Click the dropdown and select desired year
3. Page automatically updates to show that year's data
4. Select "All Years" to see all data again

**Current year display:**
- Shows "Viewing: [Year]" on the right side of the filter
- Updates when you change the year
- Clear indication of what you're looking at

---

**Questions?** The fiscal year filter makes it easy to focus on specific years' utilization data!
