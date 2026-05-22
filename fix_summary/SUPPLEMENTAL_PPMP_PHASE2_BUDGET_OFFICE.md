# Supplemental PPMP Phase 2 - Budget Office Implementation

## Overview
Added Supplemental tab to the Budget Office PPMP & LIB View page, allowing administrators to view Supplemental PPMPs separately from regular PPMPs.

## Changes Made

### 1. Added Supplemental Tab to Navigation
**File**: `pages/ppmp_view.php`

Added a new tab between PPMP and LIB tabs:
```html
<button onclick="switchTab('supplemental')" id="supplementalTab" 
    class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-purple-600 transition-colors">
    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
    </svg>
    Supplemental
</button>
```

### 2. Added Supplemental Content Container
```html
<!-- Supplemental Tab Content -->
<div id="supplementalTabContent" class="tab-content hidden">
    <div id="supplementalListContainer">
        <!-- Supplemental PPMPs will be loaded here -->
    </div>
</div>
```

### 3. Updated `switchTab()` Function
Modified to handle three tabs (PPMP, Supplemental, LIB):
- Resets all tab styles
- Applies active styling based on selected tab
- Shows/hides appropriate content containers
- Uses purple color scheme for Supplemental tab

### 4. Updated `loadPPMPData()` Function
Now loads three types of data:
```javascript
loadPPMPs(selectedId);        // Regular PPMPs only
loadSupplementals(selectedId); // Supplemental PPMPs only
loadLIBs(selectedId);          // LIBs
```

### 5. Modified `loadPPMPs()` Function
Updated to filter only regular PPMPs:
```javascript
fetch(`../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=ppmp`)
```

### 6. Added `loadSupplementals()` Function
New function to load Supplemental PPMPs:
```javascript
function loadSupplementals(departmentId) {
    fetch(`../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=supplemental`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displaySupplementals(data.ppmps);
            }
        });
}
```

### 7. Added `displaySupplementals()` Function
Displays Supplemental PPMPs with:
- Purple color scheme (border, button, badge)
- "SUPPLEMENTAL" badge next to title
- "Supplemental" prefix in title
- Empty state message specific to Supplemental PPMPs

### 8. Updated `generatePPMPViewHTML()` Function
Enhanced to distinguish between PPMP and Supplemental:
- Detects `ppmp_type` field
- Changes label from "PPMP Number" to "Supplemental Number"
- Adds "SUPPLEMENTAL" badge in header
- Uses purple color scheme for Supplemental (table headers, totals row)
- Uses maroon color scheme for regular PPMP

## Visual Design

### Color Schemes
- **Regular PPMP**: Maroon (#800000)
- **Supplemental**: Purple (#9333EA / purple-600)
- **LIB**: Blue (#2563EB / blue-600)

### Tab Navigation
```
[PPMP] [Supplemental] [LIB]
```

### Supplemental PPMP Card Features
- Purple hover border
- "SUPPLEMENTAL" badge (purple background)
- "Supplemental" prefix in title
- Purple "View Details" button

### Modal View Features
- Supplemental badge in header
- "Supplemental Number" label
- Purple table headers and totals row

## API Integration
Uses existing `get_ppmp_list.php` API with `ppmp_type` parameter:
- `ppmp_type=ppmp` - Returns only regular PPMPs
- `ppmp_type=supplemental` - Returns only Supplemental PPMPs

## User Experience
1. Budget Office selects a department/office
2. System loads PPMP, Supplemental, and LIB data
3. User can switch between three tabs to view different types
4. Each tab shows appropriate content with distinct styling
5. Clicking "View Details" opens modal with full PPMP/Supplemental details

## Files Modified
- `pages/ppmp_view.php` - Added Supplemental tab, functions, and styling

## Testing Instructions
1. Login as Budget Office user
2. Navigate to PPMP & LIB View page
3. Select a department that has both PPMP and Supplemental PPMPs
4. Verify three tabs appear: PPMP, Supplemental, LIB
5. Click Supplemental tab - verify Supplemental PPMPs display with purple styling
6. Click a Supplemental PPMP - verify modal shows "SUPPLEMENTAL" badge
7. Verify table headers are purple for Supplemental
8. Switch between tabs - verify content persists
9. Test with department that has no Supplemental - verify appropriate empty state

## Next Steps (Phase 2 Continuation)
- Add Supplemental tab to Purchase Request selection modal in `pages/utilization.php`
- Update history modals to distinguish between PPMP and Supplemental types
