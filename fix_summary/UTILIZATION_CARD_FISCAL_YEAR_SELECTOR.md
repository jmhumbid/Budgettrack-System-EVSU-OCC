# Utilization Card Fiscal Year Selector Implementation

## Overview
Added fiscal year selector functionality to the Utilization card on the Department Dashboard, matching the behavior of the Allocation card. Users can now select different fiscal years to view utilization data without page refresh.

## Changes Made

### 1. Fixed Variable Name Issue
**File:** `pages/dept_dashboard.php`
- **Issue:** Variable `$displayFiscalYear` was used but not defined
- **Fix:** Changed all references to use `$selectedUtilizationYear` (which was already properly defined in the PHP logic)

### 2. Updated Utilization Card HTML
**File:** `pages/dept_dashboard.php` (lines ~460-480)

**Added:**
- `id="utilizationCardLink"` to the card's `<a>` tag
- `id="utilizationAmount"` to the amount display element
- `id="utilizationYearDisplay"` to the fiscal year text
- `id="utilizationCount"` to the entry count display
- Fiscal year dropdown selector (similar to allocation card)
- Year parameter in card link: `href="utilization__view.php?year=<?php echo $selectedUtilizationYear; ?>"`
- "Click to view utilization page" text with arrow icon
- Hover effects and transitions

**Dropdown Behavior:**
- Shows dropdown when multiple fiscal years are available
- Calls `updateUtilizationCardLink(year)` on change
- Prevents event propagation to avoid triggering card navigation
- Styled to match card color scheme (green/red based on balance)

### 3. Created JavaScript Function
**File:** `pages/dept_dashboard.php` (after `changeAllocationYear` function)

**Function:** `updateUtilizationCardLink(year)`
- Updates card link href with selected year parameter
- Updates displayed fiscal year text
- Fetches utilization data via AJAX from API endpoint
- Shows "Loading..." state during fetch
- Updates amount display with proper formatting
- Updates entry count display
- Handles color classes based on balance (negative = red, positive = green, zero = gray)
- Handles errors gracefully (shows ₱0.00 on error)

### 4. Created API Endpoint
**File:** `api/get_utilization_amount.php` (NEW)

**Purpose:** Fetch utilization data for a specific department and fiscal year

**Parameters:**
- `department_id` (required): Department ID
- `year` (required): Fiscal year

**Logic:**
1. Checks authentication
2. Validates parameters
3. First tries to get data from `utilization_summaries` table
4. If no summary found, checks `budget_utilization_entries` table
5. Returns JSON with:
   - `success`: boolean
   - `amount`: total balance (float)
   - `count`: number of entries (int)
   - `fiscal_year`: selected year

**Response Format:**
```json
{
    "success": true,
    "amount": 150000.50,
    "count": 5,
    "fiscal_year": 2025
}
```

## User Experience

### Before
- Utilization card showed data for most recent fiscal year only
- No way to view other fiscal years without navigating away
- Variable name error caused undefined fiscal year display
- Card was not fully clickable

### After
- Dropdown selector shows all available fiscal years
- Select any year to see its utilization data instantly (no page refresh)
- Amount and count update dynamically via AJAX
- Card link includes year parameter for navigation
- Clicking card navigates to utilization view with selected year
- Dropdown works independently without triggering navigation
- Proper color coding (green for positive, red for negative, gray for zero)

## Technical Details

### Event Handling
- `onclick="event.stopPropagation(); event.preventDefault();"` on dropdown prevents card click
- `onchange="event.stopPropagation(); updateUtilizationCardLink(this.value);"` updates data without refresh

### Dynamic Styling
The JavaScript function dynamically updates CSS classes based on the balance:
- Zero balance: `text-gray-400` (gray)
- Positive balance: `text-green-700` (green)
- Negative balance: `text-red-700` (red)

### Data Sources
The system checks two tables in order:
1. `utilization_summaries` - Pre-calculated summary data (preferred)
2. `budget_utilization_entries` - Individual entries (fallback)

## Files Modified
1. `pages/dept_dashboard.php` - Updated utilization card HTML and added JavaScript function
2. `api/get_utilization_amount.php` - NEW API endpoint for fetching utilization data

## Testing Checklist
- [x] Fiscal year dropdown appears when multiple years exist
- [x] Selecting a year updates the amount without page refresh
- [x] Selecting a year updates the entry count
- [x] Selecting a year updates the displayed fiscal year
- [x] Card link includes selected year parameter
- [x] Clicking card navigates to utilization view with correct year
- [x] Dropdown doesn't trigger card navigation
- [x] Loading state shows during AJAX fetch
- [x] Error handling works (shows ₱0.00 on error)
- [x] Color coding updates based on balance
- [x] Works with both utilization_summaries and budget_utilization_entries tables

## Consistency with Allocation Card
The utilization card now has feature parity with the allocation card:
- ✅ Fiscal year dropdown selector
- ✅ Dynamic amount fetching via AJAX
- ✅ No page refresh on year selection
- ✅ Year parameter in card link
- ✅ Clickable card with hover effects
- ✅ Independent dropdown operation
- ✅ Loading states and error handling

## Notes
- The implementation follows the exact same pattern as the allocation card for consistency
- The API endpoint handles both data sources (summaries and entries) for flexibility
- The JavaScript function updates both the amount and count for complete data refresh
- Color classes are dynamically applied based on the fetched balance value
