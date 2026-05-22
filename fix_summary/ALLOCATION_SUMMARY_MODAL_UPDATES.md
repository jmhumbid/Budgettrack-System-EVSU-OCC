# Budget Allocation Summary Modal and View Updates

## Overview
Updated the Budget Allocation Summary modal in `allocations.php` and the Budget Allocation Details section in `allocations_view.php` to properly display Total Amount, Additional Amount, and Overall Total with conditional logic.

## Changes Made

### 1. Budget Allocation Summary Modal (`pages/allocations.php`)

#### HTML Structure Updates:
- **Hidden Number of Students**: Added `style="display: none;"` to `summaryStudentsDiv` to hide it from the summary modal
- **Updated Total Display Section**: Replaced single "Overall Total" with three-row structure:
  - `summaryTotalAmountRow` - Shows "Total Amount" (gray text)
  - `summaryAdditionalAmountRow` - Shows "Additional Amount" (amber text)
  - `summaryOverallTotalLabel` - Dynamic label that shows "Overall Total"

#### JavaScript Logic Updates (`saveAndDisplaySummary` function):
- **Separated Total Calculation**:
  - `totalAmountBeforeAdditional` = Non-Fiduciary Total + Fiduciary Total (for departments) or Budget Allocated (for offices)
  - `overallTotal` = `totalAmountBeforeAdditional` + `additionalAmount`

- **Conditional Display Logic**:
  - **With Additional Amount** (additionalAmount > 0):
    - Show Total Amount row with gray text
    - Show Additional Amount row with amber text
    - Show Overall Total with maroon text
    - Label remains "Overall Total"
  
  - **Without Additional Amount** (additionalAmount = 0):
    - Hide Total Amount row
    - Hide Additional Amount row
    - Show only Overall Total with maroon text
    - Label remains "Overall Total"

- **Removed**: Old `additionalAmountSummarySection` that showed additional amount in a separate amber box

### 2. Budget Allocation Details View (`pages/allocations_view.php`)

#### Updated Budget Allocation Details Section:
- **Hidden Number of Students**: Added `style="display: none;"` to hide from view
- **Moved Additional Amount**: Integrated into Budget Allocation Details section instead of separate section below
- **Added Total Display Logic**:
  ```php
  $additionalAmount = floatval($allocationData['additional_amount'] ?? 0);
  $overallTotal = floatval($allocationData['overall_total'] ?? 0);
  $totalAmountBeforeAdditional = $overallTotal - $additionalAmount;
  ```

#### Conditional Display:
- **With Additional Amount** (additionalAmount > 0):
  - 3-column grid showing:
    - Total Amount (gray text)
    - Additional Amount (amber text) with description
    - Overall Total (maroon text, larger font)

- **Without Additional Amount** (additionalAmount = 0):
  - 3-column grid with Overall Total centered
  - Empty columns on left and right

- **Removed**: Separate "Additional Amount Section" that was displayed below Budget Allocation Details

### 3. PDF Generation (`api/generate_allocation_pdf.php`)

#### Updated Summary Information Section:
- **For Departments**:
  - Shows Number of Students (kept for record purposes)
  - Shows Total Tuition Fee
  - Shows 50% Instructional
  - **NEW**: Shows Total Amount (when additional amount exists)
  - **NEW**: Shows Additional Amount with description (when exists)
  - Shows Overall Total

- **For Offices**:
  - Shows Budget Allocated
  - **NEW**: Shows Total Amount (when additional amount exists)
  - **NEW**: Shows Additional Amount with description (when exists)
  - Shows Overall Total

## Display Logic Summary

### Without Additional Amount:
```
Overall Total: ₱4,000,000
```

### With Additional Amount:
```
Total Amount: ₱4,000,000 (gray)
Additional Amount: ₱1,000,000 (amber)
Overall Total: ₱5,000,000 (maroon, bold)
```

## Files Modified
1. `pages/allocations.php` - Summary modal HTML and JavaScript
2. `pages/allocations_view.php` - Budget Allocation Details section
3. `api/generate_allocation_pdf.php` - PDF summary section

## Testing Checklist
- [ ] Summary modal shows only "Overall Total" when no additional amount
- [ ] Summary modal shows "Total Amount" → "Additional Amount" → "Overall Total" when additional amount exists
- [ ] Number of Students is hidden from summary modal
- [ ] allocations_view.php shows proper total breakdown
- [ ] Additional Amount with description displays correctly in view page
- [ ] PDF includes Total Amount and Additional Amount breakdown
- [ ] All calculations are correct (Overall Total = Total Amount + Additional Amount)

## Notes
- Number of Students is hidden from UI but still stored in database for record purposes
- The label "Overall Total" is used consistently (not "Total Amount" when no additional)
- Additional Amount description is shown inline with the amount in view page
- PDF maintains all information including Number of Students for archival purposes
