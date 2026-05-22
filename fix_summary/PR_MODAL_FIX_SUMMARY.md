# Purchase Request Modal Issues - Fix Summary

## Issues Identified

1. **Excessive Database Saves**: The system saves to database on every small change without debouncing
2. **Missing Dropdown Elements**: Warning about `prDeductFrom_1` not found when reopening modal
3. **Items Disappearing**: When adding multiple PPMP items and reopening, some items disappear
4. **Duplicate Items**: Sometimes items appear duplicated

## Root Causes

### 1. No Debouncing on Save Operations
- Every change triggers immediate database save
- Logs show 10+ identical save operations in quick succession
- Located in `saveUtilizationData()` function around line 3835

### 2. Timing Issues with Dropdown Population
- `populateDeductFromDropdown()` is called before DOM elements are created
- Happens when modal reopens and tries to restore state
- Warning at line 7006 is symptom, not cause

### 3. Race Condition in PR Loading
- "Processing 0 PR entries" suggests data not loaded when modal opens
- Then "Processing 1 PR entry" after delay
- Indicates async loading issue in `loadPurchaseRequestEntries()`

## Recommended Fixes

### Fix 1: Add Debouncing to Save Function
```javascript
// Add at top of script section
let saveDebounceTimer = null;
const SAVE_DEBOUNCE_DELAY = 500; // 500ms delay

// Modify saveUtilizationData to use debouncing
function saveUtilizationDataDebounced() {
    if (saveDebounceTimer) {
        clearTimeout(saveDebounceTimer);
    }
    
    saveDebounceTimer = setTimeout(() => {
        saveUtilizationData();
    }, SAVE_DEBOUNCE_DELAY);
}
```

### Fix 2: Ensure DOM Ready Before Populating Dropdowns
```javascript
// In populateDeductFromDropdown, add retry logic
function populateDeductFromDropdown(rowId, selectId, selectedValue = null) {
    return new Promise((resolve) => {
        const select = document.getElementById(selectId);
        if (!select) {
            // Retry after short delay if element not found
            setTimeout(() => {
                populateDeductFromDropdown(rowId, selectId, selectedValue).then(resolve);
            }, 100);
            return;
        }
        // ... rest of function
    });
}
```

### Fix 3: Wait for PR Data Before Opening Modal
```javascript
// In handlePurchaseRequest, ensure data loaded before showing modal
function handlePurchaseRequest() {
    const modal = document.getElementById('purchaseRequestModal');
    
    // Load data FIRST
    loadPurchaseRequestEntries(departmentId).then(() => {
        // THEN show modal
        modal.classList.remove('hidden');
        console.log('✓ Purchase request modal opened - data loaded');
    });
}
```

## Implementation Priority

1. **HIGH**: Add debouncing to save operations (prevents excessive saves)
2. **MEDIUM**: Fix dropdown population timing (eliminates warnings)
3. **MEDIUM**: Ensure PR data loaded before modal opens (prevents disappearing items)

## Testing Steps

1. Add multiple PPMP items to purchase request
2. Close modal
3. Reopen modal
4. Verify all items still present
5. Check console - should see only 1-2 save operations, not 10+
6. No warnings about missing dropdown elements

## Files to Modify

- `pages/utilization.php` (lines 3750-3900, 7000-7010, 7200-7250, 9450-9500)
