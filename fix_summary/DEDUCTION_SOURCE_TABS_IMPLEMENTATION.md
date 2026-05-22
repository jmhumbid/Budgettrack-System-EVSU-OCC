# Deduction Source Selection Modal - Tab Implementation

## Overview
Added separate tabs for PPMP and Supplemental items in the deduction source selection modal when selecting Purchase Request entries. This allows users to clearly identify and select items from either PPMP or Supplemental sources separately.

## Changes Made

### 1. Modal HTML Structure (`pages/utilization.php` - Lines 1730-1750)
- Added tab navigation container with two tabs: PPMP and Supplemental
- Tabs are hidden by default and only shown for `purchase_request` source type
- Tab IDs: `deductionSourceTab-ppmp` and `deductionSourceTab-supplemental`
- Container ID: `deductionSourceTabs`

### 2. JavaScript Variables (Lines 14159-14163)
Added new variables to track tab state and store entries:
```javascript
let currentDeductionEntryId = null;
let currentDeductionSourceType = null;
let currentDeductionSourceTab = 'ppmp'; // Track current tab (ppmp or supplemental)
let allDeductionEntries = []; // Store all entries before filtering by tab
```

### 3. Tab Switching Function (`switchDeductionSourceTab()`)
- Switches between PPMP and Supplemental tabs
- Updates tab styling based on active tab:
  - PPMP: Maroon border and background
  - Supplemental: Purple border and background
- Calls `displayDeductionEntriesByTab()` to filter and display entries

### 4. Display Function (`displayDeductionEntriesByTab()`)
- Filters `allDeductionEntries` by `ppmp_type` matching current tab
- Displays only entries matching the current tab (ppmp or supplemental)
- Shows appropriate badges:
  - PPMP: Maroon badge "From PPMP"
  - Supplemental: Purple badge "From Supplemental"
- Handles empty states with tab-specific messages
- Preserves checkbox selection state and "used by other category" logic

### 5. Updated `showDeductionEntries()` Function
- Shows/hides tabs based on source type:
  - `purchase_request`: Shows tabs, resets to PPMP tab
  - `travels` and `honoraria`: Hides tabs
- For purchase_request:
  - Stores all entries in `allDeductionEntries`
  - Calls `displayDeductionEntriesByTab()` to show filtered entries
- For other source types:
  - Displays entries normally without tab filtering

### 6. API Update (`api/load_purchase_requests.php`)
Updated all three query branches to include `ppmp_type`:
```sql
SELECT pr.id, pr.purchase_request, pr.particulars, pr.pr_number, pr.po_number, 
       pr.date, pr.amount, pr.created_by,
       pr.ppmp_item_id, pr.ppmp_id, pr.ppmp_description,
       COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
FROM utilization_purchase_requests pr
LEFT JOIN ppmp p ON pr.ppmp_id = p.id
WHERE ...
```

## User Experience

### Before
- All purchase request entries (PPMP and Supplemental) were mixed together
- No way to identify which items came from PPMP vs Supplemental
- Difficult to manage selections when both types were present

### After
- Two separate tabs: PPMP | Supplemental
- Each tab shows only items of that type
- Clear visual identification with colored badges
- "Select All" checkbox works per tab (not across both tabs)
- Selected count shows count for current tab only
- Tabs only appear for Purchase Request source type

## Tab Behavior

1. **Initial Load**: Opens to PPMP tab by default
2. **Tab Switching**: Click tab to switch, entries are filtered instantly
3. **Selection State**: Checkbox selections are preserved when switching tabs
4. **Select All**: Only selects visible entries in current tab
5. **Count Display**: Shows count of selected entries across all tabs

## Visual Design

### PPMP Tab (Active)
- Border: Maroon (`border-maroon`)
- Background: Light maroon (`bg-maroon bg-opacity-5`)
- Text: Maroon (`text-maroon`)

### Supplemental Tab (Active)
- Border: Purple (`border-purple-600`)
- Background: Light purple (`bg-purple-50`)
- Text: Purple (`text-purple-600`)

### Inactive Tabs
- Border: Transparent (`border-transparent`)
- Text: Gray with purple hover (`text-gray-500 hover:text-purple-600`)

## Badge Styling

### PPMP Badge
```html
<span class="text-xs bg-maroon bg-opacity-10 text-maroon px-2 py-1 rounded ml-2">
    From PPMP
</span>
```

### Supplemental Badge
```html
<span class="text-xs bg-purple-100 text-purple-700 px-2 py-1 rounded ml-2">
    From Supplemental
</span>
```

## Technical Notes

1. **Data Filtering**: Uses `ppmp_type` field from database (defaults to 'ppmp' if NULL)
2. **Tab Visibility**: Tabs only shown for `purchase_request` source type
3. **Selection Persistence**: Checkbox states are maintained in localStorage per category
4. **Cross-Category Check**: Still validates if entry is used by other expense categories
5. **Empty States**: Shows tab-specific "No PPMP entries found" or "No Supplemental entries found"

## Files Modified

1. `pages/utilization.php`
   - Added tab HTML structure
   - Added JavaScript variables and functions
   - Updated `showDeductionEntries()` function
   - Added `switchDeductionSourceTab()` function
   - Added `displayDeductionEntriesByTab()` function

2. `api/load_purchase_requests.php`
   - Updated all SQL queries to join with ppmp table
   - Added `ppmp_type` to SELECT statements
   - Uses `COALESCE(p.ppmp_type, 'ppmp')` to handle NULL values

## Testing Checklist

- [x] Tabs appear only for Purchase Request source type
- [x] Tabs do not appear for Travels or Honoraria
- [x] PPMP tab shows only PPMP entries
- [x] Supplemental tab shows only Supplemental entries
- [x] Tab switching works smoothly
- [x] Checkbox selections persist when switching tabs
- [x] "Select All" works per tab
- [x] Selected count updates correctly
- [x] Badges display correctly (PPMP vs Supplemental)
- [x] Empty states show appropriate messages
- [x] "Used by other category" logic still works
- [x] No syntax errors in PHP or JavaScript

## Related Features

This implementation completes the Supplemental PPMP Phase 2 feature set:
1. ✅ Separate tabs in PPMP page (PPMP | Supplemental)
2. ✅ Separate tabs in Budget Office view (PPMP | Supplemental | LIB)
3. ✅ Separate tabs in PPMP Selection Modal
4. ✅ Separate tabs in Deduction Source Selection Modal (this implementation)
5. ✅ Visual identification with badges throughout
6. ✅ Proper filtering by ppmp_type in all APIs

## Summary

The deduction source selection modal now provides a clean, organized interface for selecting purchase request entries. Users can easily distinguish between PPMP and Supplemental items, making budget tracking and deduction management more intuitive and error-free.
