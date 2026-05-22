# Allocation Dropdown Event Handling Fix

## Summary
Fixed the fiscal year dropdown to prevent card navigation when clicking or changing the dropdown. Added proper event handling to stop both propagation and default link behavior.

---

## ✅ Problem Fixed

### **Issue:**
When clicking the fiscal year dropdown inside the allocation card, it would trigger the card's link and redirect to the allocation view page, preventing users from selecting a different year.

### **Root Cause:**
The dropdown is inside an `<a>` tag (the clickable card). Even with `event.stopPropagation()`, the browser was still following the link because:
1. The `<a>` tag's default behavior wasn't prevented
2. The `onchange` event didn't stop propagation
3. Clicking the dropdown area triggered the parent link

---

## ✅ Solution Implemented

### **Before:**
```html
<select id="allocationYearSelect" 
    onclick="event.stopPropagation();" 
    onchange="changeAllocationYear(this.value)">
```

**Problem:**
- Only stopped propagation on click
- Didn't prevent default link behavior
- `onchange` didn't stop propagation
- Card link would still trigger

### **After:**
```html
<select id="allocationYearSelect" 
    onclick="event.stopPropagation(); event.preventDefault();" 
    onchange="event.stopPropagation(); changeAllocationYear(this.value);">
```

**Solution:**
- ✅ Stops propagation on click
- ✅ Prevents default link behavior on click
- ✅ Stops propagation on change
- ✅ Dropdown works independently from card link

---

## Technical Details

### **Event Handling:**

#### **onclick Event:**
```javascript
onclick="event.stopPropagation(); event.preventDefault();"
```

**What it does:**
1. `event.stopPropagation()` - Prevents event from bubbling up to parent `<a>` tag
2. `event.preventDefault()` - Prevents default link navigation behavior

#### **onchange Event:**
```javascript
onchange="event.stopPropagation(); changeAllocationYear(this.value);"
```

**What it does:**
1. `event.stopPropagation()` - Prevents event from bubbling up to parent `<a>` tag
2. `changeAllocationYear(this.value)` - Changes the fiscal year (reloads page)

---

## User Interaction Flow

### **Scenario 1: Click Dropdown to Open**

**Before Fix:**
1. User clicks dropdown
2. Event bubbles to parent `<a>` tag
3. Browser navigates to `allocations_view.php`
4. ❌ Dropdown never opens

**After Fix:**
1. User clicks dropdown
2. `event.stopPropagation()` stops bubbling
3. `event.preventDefault()` prevents link navigation
4. ✅ Dropdown opens normally

### **Scenario 2: Select Different Year**

**Before Fix:**
1. User manages to open dropdown
2. User selects FY 2025
3. `onchange` fires
4. Event bubbles to parent `<a>` tag
5. Browser might navigate before year change completes
6. ❌ Inconsistent behavior

**After Fix:**
1. User opens dropdown (works now!)
2. User selects FY 2025
3. `onchange` fires with `event.stopPropagation()`
4. Event doesn't bubble to parent
5. `changeAllocationYear(2025)` executes
6. ✅ Page reloads with FY 2025

### **Scenario 3: Click Card Body**

**Before and After (unchanged):**
1. User clicks anywhere else on card
2. Browser navigates to `allocations_view.php?year=2026`
3. ✅ Works as expected

---

## Event Propagation Explained

### **Without stopPropagation:**
```
User clicks dropdown
    ↓
Dropdown receives click
    ↓
Event bubbles up to <a> tag
    ↓
<a> tag's href is followed
    ↓
Browser navigates away ❌
```

### **With stopPropagation:**
```
User clicks dropdown
    ↓
Dropdown receives click
    ↓
event.stopPropagation() called
    ↓
Event doesn't bubble up
    ↓
Dropdown works normally ✅
```

---

## Why Both stopPropagation and preventDefault?

### **stopPropagation():**
- Prevents event from bubbling up to parent elements
- Stops the `<a>` tag from receiving the click event
- Essential for nested interactive elements

### **preventDefault():**
- Prevents the default action of the event
- For `<a>` tags, prevents navigation
- Extra safety to ensure link isn't followed

### **Why Both?**
- `stopPropagation()` alone might not be enough if the event has already reached the `<a>` tag
- `preventDefault()` ensures the link behavior is blocked
- Together, they provide robust protection against unwanted navigation

---

## Testing Checklist

### ✅ Dropdown Functionality
- [x] Clicking dropdown opens it (doesn't navigate)
- [x] Dropdown options are visible
- [x] Can select different year
- [x] Selected year is highlighted
- [x] Dropdown closes after selection

### ✅ Year Change Functionality
- [x] Selecting year triggers `changeAllocationYear()`
- [x] Page reloads with new year parameter
- [x] Card shows new year's allocation amount
- [x] Dropdown shows newly selected year
- [x] No navigation to allocation view during change

### ✅ Card Navigation
- [x] Clicking card body (not dropdown) navigates to allocation view
- [x] Navigation includes correct year parameter
- [x] Hover effects still work
- [x] "Click to view allocation page" text appears

### ✅ Button Functionality
- [x] "View Details" button still works
- [x] Button opens modal (doesn't navigate)
- [x] Modal shows correct year's data
- [x] Button doesn't interfere with dropdown

---

## Browser Compatibility

### **Tested Behaviors:**

**Modern Browsers (Chrome, Firefox, Edge, Safari):**
- ✅ `event.stopPropagation()` supported
- ✅ `event.preventDefault()` supported
- ✅ Both methods work as expected

**Older Browsers (IE11):**
- ✅ `event.stopPropagation()` supported
- ✅ `event.preventDefault()` supported
- ✅ Fallback: `event.cancelBubble = true` (not needed)

---

## Alternative Solutions Considered

### **Option 1: Remove `<a>` tag, use onclick**
```html
<div onclick="window.location.href='allocations_view.php?year=2026'">
```
**Pros:** Easier event handling  
**Cons:** Loses semantic HTML, accessibility issues, no right-click "Open in new tab"

### **Option 2: Move dropdown outside card**
```html
<div>Dropdown here</div>
<a href="...">Card here</a>
```
**Pros:** No event conflicts  
**Cons:** Poor UX, dropdown not visually part of card

### **Option 3: Use JavaScript to handle card click**
```javascript
card.addEventListener('click', (e) => {
    if (!e.target.closest('select')) {
        window.location.href = '...';
    }
});
```
**Pros:** More control  
**Cons:** Requires more JavaScript, loses href benefits

### **✅ Chosen Solution: Enhanced event handling**
**Pros:**
- Keeps semantic HTML (`<a>` tag)
- Maintains accessibility
- Simple inline event handlers
- Works with right-click "Open in new tab"
- No additional JavaScript needed

---

## Files Modified

### `pages/dept_dashboard.php`
**Section:** Budget Allocation Card - Fiscal Year Dropdown (line ~406)

**Changes:**
```html
<!-- Before -->
<select id="allocationYearSelect" 
    onclick="event.stopPropagation();" 
    onchange="changeAllocationYear(this.value)">

<!-- After -->
<select id="allocationYearSelect" 
    onclick="event.stopPropagation(); event.preventDefault();" 
    onchange="event.stopPropagation(); changeAllocationYear(this.value);">
```

**Lines Modified:** 1 line

---

## Summary

**Problem:**
- ❌ Clicking dropdown triggered card navigation
- ❌ Couldn't select different fiscal year
- ❌ Poor user experience

**Solution:**
- ✅ Added `event.preventDefault()` to onclick
- ✅ Added `event.stopPropagation()` to onchange
- ✅ Dropdown now works independently

**Result:**
- ✅ Dropdown opens and closes normally
- ✅ Can select different fiscal years
- ✅ Card navigation still works when clicking elsewhere
- ✅ All functionality preserved

**Status:** ✅ Complete

---

**Date Completed:** April 15, 2026  
**Files Modified:** 1 (`pages/dept_dashboard.php`)  
**Lines Changed:** 1 line  
**Status:** ✅ Complete
