# Supplemental PPMP Tab Switching Fix

## Issue
When switching between PPMP and Supplemental tabs, the content would disappear because `loadCurrentPPMP()` was being called every time, replacing existing content with fresh API calls.

## Root Cause
1. The `switchPPMPTab()` function was calling `loadCurrentPPMP()` every time a tab was clicked
2. `loadCurrentPPMP()` would always fetch and replace content, even if content already existed
3. This caused the displayed PPMP to disappear and be replaced with the default "No PPMP Created" message or reload from API

## Solution

### 1. Updated `loadCurrentPPMP()` Function
**File**: `assets/js/ppmp.js`

Added a `forceReload` parameter and content existence check:
```javascript
function loadCurrentPPMP(ppmpType = 'ppmp', forceReload = false) {
    // Check if content already exists (not the default empty state)
    const hasContent = container.querySelector('.screen-only-header') !== null;
    
    // If content exists and we're not forcing a reload, don't reload
    if (hasContent && !forceReload) {
        return;
    }
    
    // ... rest of loading logic
}
```

This prevents unnecessary reloads when switching tabs if content already exists.

### 2. Updated `switchPPMPTab()` Function
Removed the call to `loadCurrentPPMP()` - now it only:
- Updates tab button styles
- Shows/hides content panels
- Saves tab preference to localStorage

The content is preserved because we're just showing/hiding the containers, not reloading them.

### 3. Updated Page Initialization
**File**: `assets/js/ppmp.js`

Changed the DOMContentLoaded event to:
1. Load PPMP content initially with `forceReload = true`
2. Check for supplemental PPMPs and load them if they exist
3. Restore the active tab from localStorage (just switch UI, don't reload)

```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Load PPMP content initially
    loadCurrentPPMP('ppmp', true);
    
    // Check and load supplemental if exists
    // ... supplemental check logic
    
    // Restore active tab (just UI switch)
    const savedTab = localStorage.getItem('activePPMPTab');
    if (savedTab) {
        switchPPMPTab(savedTab);
    }
});
```

### 4. Updated Save and Delete Functions
Force reload when content changes:
- `savePPMP()`: Uses `forceReload = true` when creating new PPMP
- `deletePPMP()`: Uses `forceReload = true` after deletion

### 5. Content Detection Logic
The function checks for `.screen-only-header` element to determine if actual PPMP content exists (vs. the default empty state message).

## HTML Structure
The page has two separate content containers that are shown/hidden:
```html
<!-- PPMP Tab Content -->
<div id="ppmpTabContent" class="ppmp-content-panel">
    <div id="currentPPMPContainer">
        <!-- PPMP content here -->
    </div>
</div>

<!-- Supplemental Tab Content -->
<div id="supplementalTabContent" class="ppmp-content-panel hidden">
    <div id="currentSupplementalContainer">
        <!-- Supplemental content here -->
    </div>
</div>
```

## Result
- PPMP and Supplemental tabs now maintain their content when switching
- Content is only loaded once on page load (or when forced)
- Switching tabs is instant - just shows/hides containers
- Content is preserved until explicitly changed (save, delete, view draft)
- No unnecessary API calls when switching tabs

## Files Modified
- `assets/js/ppmp.js` - Updated tab switching, loading, and initialization logic

## Testing Instructions
1. Create a regular PPMP (draft or final)
2. Create a Supplemental PPMP (draft or final)
3. Switch between PPMP and Supplemental tabs multiple times
4. Verify both tabs maintain their content without reloading
5. Edit a PPMP and verify it updates correctly
6. Delete a PPMP and verify the tab reloads properly
7. View drafts of both types - verify they appear in correct tab
8. Clear browser cache (Ctrl+Shift+R) to see changes

