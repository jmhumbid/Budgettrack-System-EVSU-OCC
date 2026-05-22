# Supplemental PPMP Phase 2 - Complete Implementation

## Overview
Phase 2 implementation adds Supplemental PPMP support to Budget Office view and Purchase Request selection modal, with proper filtering and UI updates.

## Part 1: Budget Office PPMP & LIB View

### Changes Made
**File**: `pages/ppmp_view.php`

1. **Added Supplemental Tab**
   - New tab between PPMP and LIB tabs
   - Purple color scheme for Supplemental
   - Separate content container

2. **Updated Tab Switching**
   - `switchTab()` function now handles 3 tabs (PPMP, Supplemental, LIB)
   - Each tab has distinct styling

3. **Added Data Loading Functions**
   - `loadPPMPs()` - Loads only regular PPMPs (`ppmp_type=ppmp`)
   - `loadSupplementals()` - Loads only Supplemental PPMPs (`ppmp_type=supplemental`)
   - `loadLIBs()` - Loads LIBs (unchanged)

4. **Added Display Functions**
   - `displayPPMPs()` - Displays regular PPMPs with maroon styling
   - `displaySupplementals()` - Displays Supplemental PPMPs with purple styling and badges
   - `displayLIBs()` - Displays LIBs (unchanged)

5. **Updated View Modal**
   - `generatePPMPViewHTML()` detects `ppmp_type`
   - Shows "Supplemental Number" label for supplemental
   - Adds "SUPPLEMENTAL" badge
   - Uses purple color scheme for supplemental table headers

## Part 2: Purchase Request Selection Modal

### Changes Made
**File**: `pages/utilization.php`

1. **Added Tab Navigation to Modal**
   ```html
   <div class="flex border-b border-gray-200 px-8 pt-4">
       <button onclick="switchPPMPSelectionTab('ppmp')" id="ppmpSelectionTab-ppmp">PPMP</button>
       <button onclick="switchPPMPSelectionTab('supplemental')" id="ppmpSelectionTab-supplemental">Supplemental</button>
   </div>
   ```

2. **Separate Content Containers**
   - `ppmpSelectionContent-ppmp` - For PPMP items
   - `ppmpSelectionContent-supplemental` - For Supplemental items
   - Each has its own loading/empty states

3. **Updated JavaScript Functions**
   - Added `currentPPMPSelectionTab` variable
   - Added `supplementalItemsCache` array
   - `switchPPMPSelectionTab()` - Handles tab switching
   - `openPPMPSelectionModal()` - Loads both PPMP and Supplemental items
   - `loadPPMPItems(departmentId, ppmpType)` - Now accepts ppmpType parameter
   - `displayPPMPItems(items, ppmpType)` - Displays items in correct container with correct badges

4. **Dynamic Badges**
   - PPMP items: Maroon badge "PPMP #X"
   - Supplemental items: Purple badge "Supplemental #X"

### API Updates
**File**: `api/get_ppmp_items_for_pr.php`

1. **Added ppmp_type Filtering**
   - Accepts `ppmp_type` parameter (defaults to 'ppmp')
   - Filters query: `WHERE p.ppmp_type = :ppmp_type OR (p.ppmp_type IS NULL AND :ppmp_type = 'ppmp')`
   - Returns `ppmp_type` in response

2. **Backward Compatibility**
   - Handles NULL ppmp_type (treats as 'ppmp')
   - Existing code continues to work

## Part 3: PPMP Creation/Editing Fixes

### Changes Made
**File**: `assets/js/ppmp.js`

1. **Fixed Button Text for Supplemental**
   - `handleMarkAsFinal()` now checks `currentPPMPType`
   - When "Mark as Final" is checked:
     - Regular PPMP: "Save PPMP"
     - Supplemental: "Save Supplemental"
   - When unchecked: "Save Draft" (both types)

2. **Fixed Edit Modal Title**
   - `editPPMP()` now sets `currentPPMPType` from data
   - Modal title changes based on type:
     - Regular: "Edit PPMP"
     - Supplemental: "Edit Supplemental PPMP"
   - Button text updates correctly based on type and status

3. **Added ppmpType to Edit Form**
   - Sets `document.getElementById('ppmpType').value = currentPPMPType`
   - Ensures type is preserved when editing

## Visual Design

### Color Schemes
- **Regular PPMP**: Maroon (#800000)
- **Supplemental**: Purple (#9333EA / purple-600)
- **LIB**: Blue (#2563EB / blue-600)

### Budget Office View
- Three tabs: PPMP | Supplemental | LIB
- Supplemental cards have purple hover border
- "SUPPLEMENTAL" badge on cards and in modal
- Purple table headers for Supplemental

### Purchase Request Modal
- Two tabs: PPMP | Supplemental
- Each tab shows only its type of items
- Dynamic badges based on type
- Separate loading/empty states

## Data Flow

### Budget Office View
1. User selects department/office
2. System calls:
   - `loadPPMPs(deptId)` → API with `ppmp_type=ppmp`
   - `loadSupplementals(deptId)` → API with `ppmp_type=supplemental`
   - `loadLIBs(deptId)` → API for LIBs
3. Each tab displays its filtered data

### Purchase Request Selection
1. User clicks "Select PPMP Items"
2. Modal opens with PPMP tab active
3. System loads:
   - PPMP items → `ppmp_type=ppmp`
   - Supplemental items → `ppmp_type=supplemental`
4. User switches tabs to view different types
5. Items display with appropriate badges

### PPMP Creation/Editing
1. User creates/edits PPMP or Supplemental
2. Modal title reflects type
3. Button text changes based on:
   - Type (PPMP vs Supplemental)
   - Status (Draft vs Final)
4. Form submits with correct `ppmp_type`

## Files Modified

1. `pages/ppmp_view.php` - Budget Office view with Supplemental tab
2. `pages/utilization.php` - Purchase Request modal with tabs
3. `api/get_ppmp_items_for_pr.php` - Added ppmp_type filtering
4. `assets/js/ppmp.js` - Fixed button text and modal titles

## Testing Checklist

### Budget Office View
- [ ] Select department with both PPMP and Supplemental
- [ ] Verify three tabs appear
- [ ] Click Supplemental tab - verify only Supplemental items show
- [ ] Click PPMP tab - verify only PPMP items show
- [ ] View Supplemental details - verify purple styling and badge
- [ ] View PPMP details - verify maroon styling

### Purchase Request Modal
- [ ] Open "Select PPMP Items" modal
- [ ] Verify two tabs: PPMP and Supplemental
- [ ] PPMP tab shows only PPMP items with maroon badges
- [ ] Supplemental tab shows only Supplemental items with purple badges
- [ ] Switch between tabs - content persists
- [ ] Select items from both tabs
- [ ] Add to purchase request - verify items added correctly

### PPMP Creation/Editing
- [ ] Create new PPMP - button shows "Save Draft"
- [ ] Check "Mark as Final" - button shows "Save PPMP"
- [ ] Create new Supplemental - button shows "Save Draft"
- [ ] Check "Mark as Final" - button shows "Save Supplemental"
- [ ] Edit PPMP draft - title shows "Edit PPMP"
- [ ] Edit Supplemental draft - title shows "Edit Supplemental PPMP"
- [ ] Edit with "Mark as Final" checked - button text correct for type

## Browser Cache
Remember to clear browser cache (Ctrl+Shift+R) to see JavaScript changes.

## Next Steps (Future Enhancements)
- Add Supplemental filtering to history modals
- Add Supplemental reports/exports
- Add Supplemental-specific notifications
