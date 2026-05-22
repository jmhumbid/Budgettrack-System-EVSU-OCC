# PPMP Create Button Fix

## Problem
The "Create New PPMP" button was not working/functioning.

## Root Cause
The `assets/js/ppmp.js` file contained PHP code:
```javascript
const departmentId = <?php echo $departmentId ?? 'null'; ?>;
```

**Issue**: `.js` files are served as static files and are NOT processed by PHP. The browser was receiving literal PHP code instead of the department ID value, causing JavaScript errors.

## Solution

### 1. Removed PHP from JavaScript File
Changed `ppmp.js` to use a global variable instead:
```javascript
const departmentId = window.DEPARTMENT_ID || null;
```

### 2. Set Global Variable in HTML
Added a script block in `pages/ppmp.php` before including `ppmp.js`:
```html
<script>
// Set global variables for JavaScript
window.DEPARTMENT_ID = <?php echo $departmentId ?? 'null'; ?>;
</script>
<script src="../assets/js/ppmp.js"></script>
```

## Files Modified
1. `assets/js/ppmp.js` - Removed PHP code, use global variable
2. `pages/ppmp.php` - Added global variable declaration

## Why This Works
- PHP processes the HTML file and outputs the department ID
- JavaScript file remains clean and portable
- Global variable is available to all JavaScript functions
- No syntax errors in JavaScript

## Testing
1. Clear browser cache (Ctrl + Shift + R)
2. Click "Create New PPMP" button
3. Dropdown should appear with options:
   - Regular PPMP
   - Supplemental PPMP
4. Click either option to open the create modal

## Additional Notes
- This is a common pattern for passing server-side data to client-side JavaScript
- Keeps JavaScript files clean and reusable
- Avoids mixing PHP and JavaScript in the same file
