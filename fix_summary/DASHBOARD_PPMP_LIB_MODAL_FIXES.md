# Dashboard PPMP and LIB Modal Fixes

## Summary
Fixed multiple issues with PPMP and LIB view details modals in the department dashboard, including empty columns, incorrect field mappings, date formatting, and grand total calculations.

## Issues Fixed

### 1. PPMP Modal Issues

#### Empty Columns Fixed
- **Type Column**: Now correctly displays `project_type` field from database
- **Recommended Mode Column**: Now correctly displays `recommended_mode` field
- **Pre-Proc Column**: Now correctly displays `pre_procurement_conference` field
- **Start Column**: Now correctly displays `start_procurement` date field
- **End Ads Column**: Now correctly displays `end_ads_posting` date field
- **Delivery Column**: Now correctly displays `expected_delivery` date field
- **Remarks Column**: Now correctly displays `remarks` or `deducted_from_categories` field

#### Date Formatting
- Added proper date formatting function that:
  - Handles null/empty dates by showing "-"
  - Handles invalid dates (0000-00-00) by showing "-"
  - Formats valid dates as "MMM DD, YYYY" (e.g., "Jan 15, 2026")

#### Grand Total Calculation
- Fixed grand total to be calculated from items instead of non-existent `ppmp.grand_total` column
- Grand total now displays in three places:
  1. Header section (top right)
  2. Footer row (bottom of table)
  3. Both update dynamically when items are loaded

#### Supplemental PPMP Tab
- Fixed supplemental identification to use `ppmp_type === 'supplemental'` (not `is_supplemental`)
- Tabs now appear when both regular and supplemental PPMPs exist
- Each tab shows its own table with correct data
- Yellow badge for supplemental, maroon badge for regular

### 2. LIB Modal Issues

#### Account Code Display
- Fixed to use correct field name: `account_code` (not `uacs_code`)
- Account codes now display properly in the center column

#### Grand Total Mismatch
- Fixed grand total calculation to sum from items instead of using `lib.grand_total`
- Grand total now matches the sum of all category subtotals
- Displays in header and footer of table

#### Field Simplification
- Removed fallback field names (e.g., `item.description`, `item.uacs_code`)
- Now uses only the correct database field names:
  - `particulars` for item description
  - `account_code` for UACS code
  - `amount` for item amount

## Technical Changes

### File Modified
- `pages/dept_dashboard.php`

### Functions Updated

#### 1. `showPPMPBreakdown()`
```javascript
// Changed from:
const regularPPMP = ppmpData.ppmps.filter(p => p.is_supplemental != 1);
const supplementalPPMP = ppmpData.ppmps.filter(p => p.is_supplemental == 1);

// Changed to:
const regularPPMP = ppmpData.ppmps.filter(p => p.ppmp_type !== 'supplemental');
const supplementalPPMP = ppmpData.ppmps.filter(p => p.ppmp_type === 'supplemental');
```

#### 2. `displayPPMPBreakdown()`
- Now fetches items first to calculate grand total before rendering
- Passes calculated grand total to `generatePPMPTable()`
- Removed redundant item fetching loop

#### 3. `generatePPMPTable()`
```javascript
// Added parameter:
function generatePPMPTable(ppmp, grandTotal = 0)

// Changed from:
const ppmpType = (ppmp.ppmp_type === 'supplemental' || ppmp.is_supplemental == 1) ? 'Supplemental' : 'Regular';
₱${parseFloat(ppmp.grand_total || 0).toLocaleString(...)}

// Changed to:
const ppmpType = (ppmp.ppmp_type === 'supplemental') ? 'Supplemental' : 'Regular';
₱${parseFloat(grandTotal || 0).toLocaleString(...)}
```

#### 4. `populatePPMPItems()`
- Added date formatting function
- Removed fallback field names
- Now uses only correct database field names
- Updates header total in addition to footer totals

```javascript
// Added:
const formatDate = (dateStr) => {
    if (!dateStr || dateStr === '0000-00-00') return '-';
    const date = new Date(dateStr);
    if (isNaN(date.getTime())) return '-';
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
};

// Changed from:
${item.project_type || item.item_type || item.type || 'Goods'}
${item.start_procurement || item.ads_posting_start || item.start_date || '-'}

// Changed to:
${item.project_type || '-'}
${formatDate(item.start_procurement)}
```

#### 5. `populateLIBItemsWithCategories()`
```javascript
// Changed from:
${item.particulars || item.description || '-'}
${item.uacs_code || item.account_code || '-'}

// Changed to:
${item.particulars || '-'}
${item.account_code || '-'}
```

## Database Schema Reference

### PPMP Table Structure
```sql
-- Main PPMP table
ppmp (
    id, department_id, fiscal_year, ppmp_number,
    ppmp_type ENUM('ppmp', 'supplemental'),  -- NOT is_supplemental
    is_indicative, is_final, status, created_by
)

-- PPMP Items table
ppmp_items (
    id, ppmp_id,
    general_description,
    project_type,              -- NOT item_type or type
    quantity, unit,
    recommended_mode,          -- NOT mode_of_procurement
    pre_procurement_conference, -- NOT pre_proc
    start_procurement,         -- NOT ads_posting_start
    end_ads_posting,          -- NOT ads_posting_end
    expected_delivery,        -- NOT delivery_date
    source_of_funds,
    estimated_budget,
    allocated_supporting_funds, -- NOT allocated_budget
    remarks
)
```

### LIB Table Structure
```sql
-- Main LIB table
line_item_budgets (
    id, department_id, fiscal_year, fund_type,
    status, approved_by_budget_office, created_by
)

-- LIB Items table
line_item_budget_items (
    id, lib_id,
    category,
    particulars,    -- NOT description
    account_code,   -- NOT uacs_code
    amount
)
```

## Testing Checklist

- [x] PPMP modal displays all columns correctly
- [x] PPMP dates format properly (or show "-" for empty)
- [x] PPMP grand total matches sum of items
- [x] Supplemental tab appears when both regular and supplemental exist
- [x] Tab switching works between regular and supplemental
- [x] LIB account codes display correctly
- [x] LIB grand total matches sum of items
- [x] LIB categories group items properly
- [x] No JavaScript errors in console

## Notes

1. The PPMP table does NOT have a `grand_total` column - it must be calculated from items
2. The PPMP table does NOT have an `is_supplemental` column - use `ppmp_type` enum instead
3. Date fields in PPMP items are stored as DATE type and may be NULL or '0000-00-00'
4. LIB items use `account_code` field, not `uacs_code`
5. All totals should be calculated from items, not from stored totals in parent tables

## Related Files
- `pages/dept_dashboard.php` - Main file with modal implementations
- `api/get_ppmp_details.php` - Returns PPMP and items data
- `api/get_lib_details.php` - Returns LIB and items data
- `api/get_ppmp_list.php` - Returns list of PPMPs with filters
- `api/get_lib_list.php` - Returns list of LIBs with filters
- `database/ppmp_table.sql` - PPMP schema definition
- `database/supplemental_ppmp.sql` - Supplemental PPMP schema
- `database/lib_table.sql` - LIB schema definition
