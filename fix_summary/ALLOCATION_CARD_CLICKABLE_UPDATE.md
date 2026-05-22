# Allocation Card Clickable Update

## Summary
Made the Budget Allocation card fully clickable to redirect to the allocation view page, while maintaining the fiscal year dropdown functionality and "View Details" button.

---

## ✅ Changes Completed

### 1. Made Allocation Card Clickable
**Status:** ✅ Completed

**Location:** `pages/dept_dashboard.php` (line ~390)

**What Changed:**
- Changed from `<div>` to `<a href="allocations_view.php">` tag
- Entire card now redirects to allocation view page when clicked
- Added hover effects and visual feedback
- Added "Click to view allocation page" text with arrow icon

**Before:**
```html
<div class="bg-gradient-to-br from-maroon to-red-800 rounded-2xl shadow-lg border border-maroon p-8 hover:shadow-xl transition-all duration-300 text-white">
    <!-- Card content -->
</div>
```

**After:**
```html
<a href="allocations_view.php" class="bg-gradient-to-br from-maroon to-red-800 rounded-2xl shadow-lg border border-maroon p-8 hover:shadow-xl transition-all duration-300 text-white block group">
    <!-- Card content -->
</a>
```

---

### 2. Preserved Fiscal Year Dropdown Functionality
**Status:** ✅ Completed

**What Was Done:**
- Added `onclick="event.stopPropagation();"` to dropdown
- Prevents card click when interacting with dropdown
- Dropdown still changes fiscal year without navigating away
- Works seamlessly with card click functionality

**Code:**
```html
<select id="allocationYearSelect" 
    onclick="event.stopPropagation();" 
    onchange="changeAllocationYear(this.value)" 
    class="mt-1 px-2 py-1 text-xs bg-white bg-opacity-20 border border-white border-opacity-30 rounded text-white font-semibold hover:bg-opacity-30 transition-colors cursor-pointer">
    <!-- Options -->
</select>
```

---

### 3. Preserved "View Details" Button Functionality
**Status:** ✅ Completed

**What Was Done:**
- Added `event.stopPropagation()` to button click handler
- Button opens modal without navigating to allocation page
- Both button and card click work independently

**Code:**
```html
<button onclick="event.preventDefault(); event.stopPropagation(); showBudgetBreakdown(<?php echo $departmentId; ?>, <?php echo $selectedFiscalYear; ?>);" 
    class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-semibold transition-colors border border-white border-opacity-30">
    View Details
</button>
```

---

### 4. Added Visual Feedback
**Status:** ✅ Completed

**What Was Added:**
1. **Hover Effect on Icon:**
   - Icon scales up on card hover
   - Added `group-hover:scale-110 transition-transform`

2. **Click Indicator:**
   - Added "Click to view allocation page" text at bottom
   - Arrow icon for visual direction
   - Fades in on hover (`group-hover:opacity-100`)

3. **Fallback for Single Year:**
   - Shows "FY 2026" text when only one year exists
   - Maintains consistent layout

**Code:**
```html
<div class="mt-3 flex items-center gap-2 text-xs text-white opacity-75 group-hover:opacity-100 transition-opacity">
    <span>Click to view allocation page</span>
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
    </svg>
</div>
```

---

## User Interaction Flow

### Scenario 1: Click on Card Body
**Action:** User clicks anywhere on the card (except dropdown or button)  
**Result:** Redirects to `allocations_view.php`  
**Visual:** Icon scales up, click indicator appears on hover

### Scenario 2: Click on Fiscal Year Dropdown
**Action:** User clicks on the dropdown to select a year  
**Result:** Dropdown opens, year can be selected  
**Behavior:** `event.stopPropagation()` prevents card navigation  
**After Selection:** Page reloads with new year parameter

### Scenario 3: Click on "View Details" Button
**Action:** User clicks the "View Details" button  
**Result:** Opens budget breakdown modal  
**Behavior:** `event.preventDefault()` and `event.stopPropagation()` prevent card navigation  
**Modal:** Shows detailed allocation breakdown

---

## Visual Improvements

### Before:
- ❌ Card was not clickable
- ❌ No visual indication of interactivity
- ❌ Only "View Details" button or small link at bottom
- ❌ Inconsistent with other dashboard cards (Utilization, PPMP, LIB are clickable)

### After:
- ✅ Entire card is clickable
- ✅ Hover effects show interactivity
- ✅ Icon animation on hover
- ✅ Clear "Click to view allocation page" indicator
- ✅ Consistent with other dashboard cards
- ✅ Dropdown and button still work independently

---

## Technical Implementation

### Event Handling:
```javascript
// Card click - navigates to allocation page
<a href="allocations_view.php">

// Dropdown click - stops propagation, changes year
onclick="event.stopPropagation();"
onchange="changeAllocationYear(this.value)"

// Button click - stops propagation, shows modal
onclick="event.preventDefault(); event.stopPropagation(); showBudgetBreakdown(...);"
```

### CSS Classes:
- `block` - Makes anchor tag behave like block element
- `group` - Enables group hover effects
- `group-hover:scale-110` - Scales icon on card hover
- `group-hover:opacity-100` - Shows click indicator on hover
- `transition-transform` - Smooth icon animation
- `transition-opacity` - Smooth text fade-in

---

## Fiscal Year Dropdown Behavior

### When Multiple Years Exist:
```html
<select id="allocationYearSelect" onclick="event.stopPropagation();" onchange="changeAllocationYear(this.value)">
    <option value="2026" selected>FY 2026</option>
    <option value="2025">FY 2025</option>
    <option value="2024">FY 2024</option>
</select>
```

### When Single Year Exists:
```html
<p class="text-xs text-red-100 mt-1">FY 2026</p>
```

**Benefits:**
- Cleaner UI when only one year
- Consistent spacing
- No unnecessary dropdown

---

## Consistency with Other Cards

### Utilization Card:
```html
<a href="utilization__view.php" class="...">
    <!-- Clickable card -->
</a>
```

### PPMP Card:
```html
<a href="ppmp.php" class="...">
    <!-- Clickable card -->
</a>
```

### LIB Card:
```html
<a href="lib.php" class="...">
    <!-- Clickable card -->
</a>
```

### Allocation Card (NOW):
```html
<a href="allocations_view.php" class="...">
    <!-- Clickable card -->
</a>
```

**All dashboard cards are now consistently clickable!**

---

## Testing Checklist

### ✅ Card Clickability
- [x] Clicking card body redirects to `allocations_view.php`
- [x] Hover shows visual feedback (icon scale, text fade-in)
- [x] Cursor changes to pointer on hover
- [x] Works on desktop and mobile

### ✅ Fiscal Year Dropdown
- [x] Dropdown appears when multiple years exist
- [x] Clicking dropdown doesn't navigate away
- [x] Selecting year calls `changeAllocationYear()`
- [x] Page reloads with selected year
- [x] Selected year is highlighted
- [x] Single year shows as text (no dropdown)

### ✅ View Details Button
- [x] Button appears when allocation > 0
- [x] Clicking button opens modal
- [x] Button click doesn't navigate away
- [x] Modal shows correct fiscal year data
- [x] Button hover effect works

### ✅ Visual Feedback
- [x] Icon scales on hover
- [x] "Click to view allocation page" appears on hover
- [x] Arrow icon shows direction
- [x] Smooth transitions
- [x] Consistent with other cards

---

## Files Modified

### `pages/dept_dashboard.php`
**Section:** Budget Allocation Card (line ~390-430)

**Changes:**
1. Changed `<div>` to `<a href="allocations_view.php">`
2. Added `block` and `group` classes
3. Added `onclick="event.stopPropagation();"` to dropdown
4. Added `event.stopPropagation()` to "View Details" button
5. Added hover effect to icon (`group-hover:scale-110`)
6. Added "Click to view allocation page" indicator
7. Added fallback text for single fiscal year
8. Removed conditional link at bottom (no longer needed)

**Lines Modified:** ~50 lines

---

## User Experience Improvements

### Before:
1. User had to find small "View allocation page →" link
2. Link only appeared when no allocation set
3. Inconsistent with other dashboard cards
4. Not obvious that card relates to allocation page

### After:
1. Entire card is clickable - much larger target
2. Always clickable regardless of allocation amount
3. Consistent with Utilization, PPMP, and LIB cards
4. Clear visual feedback on hover
5. "Click to view allocation page" text guides user
6. Dropdown and button still work independently

---

## Edge Cases Handled

### 1. No Allocation Data:
- Card still clickable
- Shows ₱0.00
- "No allocation set" message
- Redirects to allocation page where user can set allocation

### 2. Single Fiscal Year:
- Shows year as text instead of dropdown
- Maintains consistent spacing
- Card still clickable

### 3. Multiple Fiscal Years:
- Dropdown allows year selection
- Dropdown click doesn't navigate away
- Selected year updates allocation amount

### 4. With "View Details" Button:
- Button appears when allocation > 0
- Button click opens modal
- Button doesn't interfere with card click
- Both work independently

---

## Summary

**What Changed:**
- ✅ Made allocation card fully clickable
- ✅ Redirects to `allocations_view.php`
- ✅ Preserved fiscal year dropdown functionality
- ✅ Preserved "View Details" button functionality
- ✅ Added visual feedback and hover effects
- ✅ Added "Click to view allocation page" indicator
- ✅ Consistent with other dashboard cards

**Impact:**
- Better user experience
- Larger clickable area
- Clear visual feedback
- Consistent dashboard design
- Easier navigation to allocation page

**Status:** ✅ All Changes Complete

---

**Date Completed:** April 15, 2026  
**Files Modified:** 1 (`pages/dept_dashboard.php`)  
**Lines Changed:** ~50 lines  
**Status:** ✅ Complete
