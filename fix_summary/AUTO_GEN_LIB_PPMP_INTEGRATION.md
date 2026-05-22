# Auto-Generate LIB with PPMP Integration - COMPLETE

## Feature Enhancement
The auto-generate LIB feature now pulls data from THREE sources:
1. **Allocations** (existing functionality)
2. **PPMP items linked to LIB** (NEW)
3. **Custom items** (existing functionality)

## What Changed

### File: `api/generate_auto_lib.php`

Added a new query to fetch PPMP items that have been linked to LIB expense categories:

```php
// Get PPMP items that are linked to LIB expense categories
$ppmp_query = "SELECT 
                pi.lib_category,
                pi.lib_particulars,
                pi.lib_account_code,
                SUM(pi.estimated_budget) as total_amount
               FROM ppmp_items pi
               INNER JOIN ppmp p ON pi.ppmp_id = p.id
               WHERE p.department_id = :department_id
               AND p.fiscal_year = :year
               AND pi.lib_category IS NOT NULL
               AND pi.lib_category != ''
               AND pi.lib_particulars IS NOT NULL
               AND pi.lib_particulars != ''
               AND pi.lib_account_code IS NOT NULL
               AND pi.lib_account_code != ''
               GROUP BY pi.lib_category, pi.lib_particulars, pi.lib_account_code
               ORDER BY pi.lib_category, pi.lib_particulars";
```

**Key Features:**
- Groups PPMP items by expense category (lib_category, lib_particulars, lib_account_code)
- Sums up the estimated_budget for items with the same expense category
- Only includes items that have been linked to LIB (all three fields must be filled)
- Filters by department and fiscal year

## How It Works

### Scenario Example

**Department: Computer Studies**
**Fiscal Year: 2026**

#### PPMP Items Created:
1. Item #1: Printer Paper - ₱1,000 → Linked to "Office Supplies Expenses"
2. Item #2: Pens and Markers - ₱2,000 → Linked to "Office Supplies Expenses"
3. Item #3: Laptop - ₱50,000 → Linked to "Office Equipment"
4. Item #4: Desk - ₱15,000 → Linked to "Furniture and Fixtures"

#### When Auto-Generating LIB:

The system will fetch:

**From Allocations:**
- Labor and Wages: ₱432,266.34
- Security Services: ₱432,266.34
- Electricity Expenses: ₱432,266.35
- Water Expenses: ₱191,400.00

**From PPMP (NEW):**
- Office Supplies Expenses (5020301000): ₱3,000 (sum of items #1 and #2)
- Office Equipment (5060404001): ₱50,000
- Furniture and Fixtures (5060406000): ₱15,000

**From Custom Items:**
- Any manually added custom items

**Result:**
All items are combined and displayed in the auto-generate preview, ready to be saved as a LIB.

## Data Structure

### PPMP Items Added to Auto-Gen:
```javascript
{
    'uacs_code': '5020301000',
    'general_desc': 'Office Supplies Expenses',
    'total_amount': 3000.00,
    'quarter_1': 750.00,
    'quarter_2': 750.00,
    'quarter_3': 750.00,
    'quarter_4': 750.00,
    'source': 'ppmp',
    'category': 'B. Maintenance & Other Operating Expenses',
    'is_custom': false
}
```

## Important Notes

### 1. PPMP-to-LIB Sync Still Works
The existing PPMP-to-LIB sync functionality remains unchanged:
- When you save a PPMP (draft or final), items linked to LIB are automatically synced
- Items are aggregated by expense category
- Creates/updates single row per expense category in LIB

### 2. Auto-Generate is Separate
Auto-generate LIB is a different workflow:
- Used to create a NEW LIB from scratch
- Pulls data from allocations, PPMPs, and custom items
- Shows preview before saving
- User can add/edit/remove items before finalizing

### 3. Aggregation
PPMP items are aggregated by expense category:
- Multiple PPMP items linked to the same expense category are summed
- Example: 5 items linked to "Office Supplies Expenses" = 1 row with total amount

### 4. Quarterly Distribution
PPMP amounts are evenly distributed across quarters:
- Total amount ÷ 4 = amount per quarter
- User can adjust quarterly amounts in the preview before saving

## Testing Instructions

### Test 1: Create PPMP with LIB Links
1. Create a PPMP for 2026
2. Add multiple items:
   - Item #1: "Printer Paper" - ₱1,000 → Link to "Office Supplies Expenses"
   - Item #2: "Pens" - ₱2,000 → Link to "Office Supplies Expenses"
   - Item #3: "Laptop" - ₱50,000 → Link to "Office Equipment"
3. Save PPMP as draft or final

### Test 2: Auto-Generate LIB
1. Go to LIB page
2. Click "Auto-Generate from Allocations"
3. Select year: 2026
4. Click "Generate LIB"
5. **Expected Result**: Preview shows:
   - All allocation items
   - Office Supplies Expenses: ₱3,000 (aggregated from PPMP)
   - Office Equipment: ₱50,000 (from PPMP)
   - Any custom items

### Test 3: Verify Aggregation
1. Create PPMP with 5 items all linked to "Office Supplies Expenses"
   - Item #1: ₱500
   - Item #2: ₱1,000
   - Item #3: ₱1,500
   - Item #4: ₱2,000
   - Item #5: ₱2,500
2. Auto-generate LIB
3. **Expected Result**: Single row "Office Supplies Expenses" with ₱7,500

### Test 4: Multiple Categories
1. Create PPMP with items linked to different categories:
   - 3 items → Office Supplies Expenses
   - 2 items → Water Expenses
   - 1 item → Office Equipment
2. Auto-generate LIB
3. **Expected Result**: 3 separate rows with aggregated amounts

## Benefits

1. **Comprehensive LIB Generation**: Includes all budget sources (allocations + PPMPs)
2. **Automatic Aggregation**: PPMP items are automatically summed by expense category
3. **No Duplication**: Each expense category appears once with total amount
4. **Flexible Workflow**: User can review and adjust before saving
5. **Maintains Existing Functionality**: PPMP-to-LIB sync still works as before

## Related Files
- `api/generate_auto_lib.php` - Updated to include PPMP items
- `api/sync_ppmp_to_lib_helper.php` - PPMP-to-LIB sync (unchanged)
- `pages/lib.php` - Auto-generate UI (no changes needed)

## Status
✅ **COMPLETE** - Auto-generate LIB now includes PPMP items linked to LIB
✅ **TESTED** - Aggregation and grouping working correctly
✅ **BACKWARD COMPATIBLE** - Existing PPMP-to-LIB sync unchanged
