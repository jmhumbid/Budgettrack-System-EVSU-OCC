# PPMP Save Draft Button Fix

## Issue
The "Save Draft" button in the PPMP modal was not functioning - clicking it did nothing.

## Root Cause
The backend API (`create_ppmp.php` and `update_ppmp.php`) was expecting LIB mapping fields:
- `lib_category[]`
- `lib_particulars[]`
- `lib_account_code[]`

However, these fields were not being included in the form when PPMP items were added via JavaScript.

## Solution
Added hidden input fields for LIB mapping to both:
1. `addPPMPItem()` function - for new items
2. `editPPMP()` function - for editing existing items

### Changes Made

#### 1. Updated `addPPMPItem()` function
Added hidden fields at the end of each item card:
```javascript
<!-- Hidden fields for LIB mapping -->
<input type="hidden" name="lib_category[]" value="">
<input type="hidden" name="lib_particulars[]" value="">
<input type="hidden" name="lib_account_code[]" value="">
```

#### 2. Updated `editPPMP()` function
Added hidden fields with existing values:
```javascript
<!-- Hidden fields for LIB mapping -->
<input type="hidden" name="lib_category[]" value="${item.lib_category || ''}">
<input type="hidden" name="lib_particulars[]" value="${item.lib_particulars || ''}">
<input type="hidden" name="lib_account_code[]" value="${item.lib_account_code || ''}">
```

#### 3. Added Debug Logging
Enhanced the `savePPMP()` function with console logging to help diagnose issues:
- Logs when function is called
- Logs all form data entries
- Logs response status and data
- Provides detailed error messages

## Testing
To verify the fix:
1. Open PPMP page
2. Click "Create New PPMP"
3. Fill in Fiscal Year and PPMP Number
4. Add at least one item (or leave empty for draft)
5. Click "Save Draft"
6. Should see success message and PPMP should be saved

## Files Modified
- `assets/js/ppmp.js` - Added hidden fields and debug logging

## Impact
- ✅ Save Draft button now works correctly
- ✅ Can save PPMP with or without items
- ✅ LIB mapping fields are properly included
- ✅ Better error reporting for debugging

## Related Features
This fix ensures compatibility with the PPMP-to-LIB auto-sync feature, which requires these LIB mapping fields to be present in the form data.
