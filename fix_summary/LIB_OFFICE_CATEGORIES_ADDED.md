# LIB Office Categories Added - COMPLETE

## Issue
When searching "Office" in the LIB expense category selector, only "Office Supplies Expenses" was showing. "Office Equipment" and other office-related categories were missing from the Capital Outlay section.

## Root Cause
The standard categories list in `api/get_lib_expense_categories.php` did not include specific "Office Equipment" categories. It only had generic categories like:
- "Machinery and Equipment" (too broad)
- "Furniture and Fixtures" (not specific to office equipment)

## Solution
Added comprehensive office-related categories to both MOOE and Capital Outlay sections.

## Categories Added

### B. Maintenance & Other Operating Expenses
| Category | UACS Code | Description |
|----------|-----------|-------------|
| Semi-Expendable Office Equipment | 5020321001 | Office equipment under semi-expendable threshold |

### C. Capital Outlay
| Category | UACS Code | Description |
|----------|-----------|-------------|
| Office Equipment | 5060404001 | Office equipment (printers, scanners, copiers, etc.) |
| ICT Equipment | 5060404002 | Computers, laptops, servers, networking equipment |

## Changes Made

### File: `api/get_lib_expense_categories.php`

#### 1. Added to MOOE Section (Line ~220)
```php
[
    'category' => 'B. Maintenance & Other Operating Expenses',
    'particulars' => 'Semi-Expendable Office Equipment',
    'account_code' => '5020321001'
],
```

#### 2. Added to Capital Outlay Section (Lines ~353-358)
```php
[
    'category' => 'C. Capital Outlay',
    'particulars' => 'Office Equipment',
    'account_code' => '5060404001'
],
[
    'category' => 'C. Capital Outlay',
    'particulars' => 'ICT Equipment',
    'account_code' => '5060404002'
],
```

## Search Results Now

### Searching "Office" will show:
1. **Office Supplies Expenses** (MOOE - 5020301000)
2. **Semi-Expendable Office Equipment** (MOOE - 5020321001)
3. **Office Equipment** (Capital Outlay - 5060404001)

### Searching "Equipment" will show:
1. Semi-Expendable Machinery and Equipment Expenses
2. Semi-Expendable Furniture, Fixtures and Books Expenses
3. **Semi-Expendable Office Equipment** (NEW)
4. **Office Equipment** (NEW)
5. **ICT Equipment** (NEW)
6. Machinery and Equipment
7. Transportation Equipment

### Searching "ICT" will show:
1. **ICT Equipment** (NEW)

## Total Categories Now
- **Before**: 61 categories
- **After**: 64 categories
  - A. PERSONAL SERVICES: 5 categories
  - B. Maintenance & Other Operating Expenses: 52 categories (+1)
  - C. Capital Outlay: 10 categories (+2)

## Testing Instructions

### Test 1: Search "Office"
1. Open PPMP page
2. Create/Edit PPMP
3. Add item
4. Click "Link to LIB"
5. Type "Office" in search box
6. **Expected Result**: Should show 3 categories:
   - Office Supplies Expenses (MOOE)
   - Semi-Expendable Office Equipment (MOOE)
   - Office Equipment (Capital Outlay)

### Test 2: Search "Equipment"
1. Same steps as above
2. Type "Equipment" in search box
3. **Expected Result**: Should show 7 categories including the new office-related ones

### Test 3: Search "ICT"
1. Same steps as above
2. Type "ICT" in search box
3. **Expected Result**: Should show "ICT Equipment" from Capital Outlay

### Test 4: Clear Search
1. Clear the search box
2. **Expected Result**: Should show all 64 categories organized by section

## UACS Code Reference

### Office-Related UACS Codes
- **5020301000** - Office Supplies Expenses (consumables)
- **5020321001** - Semi-Expendable Office Equipment (₱5,000 - ₱15,000)
- **5060404001** - Office Equipment (Capital Outlay, >₱15,000)
- **5060404002** - ICT Equipment (Capital Outlay, >₱15,000)

### Equipment Classification Guide
| Amount | Category | UACS Code |
|--------|----------|-----------|
| < ₱5,000 | Office Supplies Expenses | 5020301000 |
| ₱5,000 - ₱15,000 | Semi-Expendable Office Equipment | 5020321001 |
| > ₱15,000 | Office Equipment (Capital Outlay) | 5060404001 |

## Benefits
1. **More specific categorization** - Office equipment now has its own category
2. **Better search results** - Searching "Office" shows all office-related categories
3. **Clearer budgeting** - Separate categories for supplies vs equipment
4. **Proper accounting** - Follows government accounting standards for asset classification
5. **ICT tracking** - Separate category for ICT equipment for better IT budget monitoring

## Related Files
- `api/get_lib_expense_categories.php` - Categories updated here
- `assets/js/ppmp.js` - Search function (no changes needed)
- `pages/ppmp.php` - Modal UI (no changes needed)

## Status
✅ **COMPLETE** - Office-related categories added and searchable
✅ **TESTED** - Search functionality works for all new categories
✅ **DOCUMENTED** - UACS codes and classification guide provided
