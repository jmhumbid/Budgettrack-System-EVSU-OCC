# COS Mapping Fix for Auto-Sync LIB

## Issue
"COS" allocation items were not being mapped to the correct UACS code "Labor and Wages" (5021601000).

## Solution
Added "COS" to the keyword mapping in the `searchUACSForDescription()` function.

## Changes Made

### File: `pages/lib.php`
Added mapping in the `keywordMap` object:
```javascript
const keywordMap = {
    'part-time': 'honoraria part',
    'overload': 'honoraria overload',
    'cos': 'labor wages',  // ← NEW MAPPING
    'water': 'water expenses',
    // ... rest of mappings
};
```

### File: `test_uacs_search.html`
Updated test cases to include COS testing.

### Documentation Files Updated
- `AUTO_SYNC_LIB_UACS_FIX.md`
- `AUTO_SYNC_LIB_UACS_QUICK_GUIDE.md`

## Result
Now when generating LIB:
- "COS" → Maps to "Labor and Wages" (UACS: 5021601000)
- Category: B. Maintenance & Other Operating Expenses (correct, as it starts with 502)

## Category Confirmation
As per user requirements:
- ✅ Honoraria - Part-time → Category A (PERSONAL SERVICES) - UACS: 5010210001
- ✅ Honoraria - Overload → Category A (PERSONAL SERVICES) - UACS: 5010210001
- ✅ COS/Labor and Wages → Category B (MOOE) - UACS: 5021601000
- ✅ Water → Category B (MOOE) - UACS: 5020401000
- ✅ Electricity → Category B (MOOE) - UACS: 5020402000
- ✅ Security → Category B (MOOE) - UACS: 5021203000
- ✅ All other items → Category B (MOOE) unless they have 501xxx or 506xxx codes

## Testing
To test the fix:
1. Open `test_uacs_search.html` in a browser
2. Verify "COS" maps to "Labor and Wages" with code 5021601000
3. Or generate LIB from allocations with COS items and verify the UACS code appears

## Complete Mapping Table

| Allocation Term | UACS Code | UACS Name | Category |
|----------------|-----------|-----------|----------|
| Part-time | 5010210001 | Honoraria - Part-time | A. PERSONAL SERVICES |
| Overload | 5010210001 | Honoraria - Overload | A. PERSONAL SERVICES |
| COS | 5021601000 | Labor and Wages | B. MOOE |
| Water | 5020401000 | Water Expenses | B. MOOE |
| Electricity | 5020402000 | Electricity Expenses | B. MOOE |
| Security | 5021203000 | Security Services | B. MOOE |
| Internet | 5020503000 | Internet Subscription Expenses | B. MOOE |
| Telephone | 5020502001 | Telephone Expenses - Mobile | B. MOOE |
| Labor | 5021601000 | Labor and Wages | B. MOOE |
| Wages | 5021601000 | Labor and Wages | B. MOOE |
| Supplies | 5020201000 | Office Supplies Expenses | B. MOOE |
| Materials | 5020201000 | Office Supplies Expenses | B. MOOE |
| Repair | Various | Repairs and Maintenance | B. MOOE |
| Maintenance | Various | Repairs and Maintenance | B. MOOE |
