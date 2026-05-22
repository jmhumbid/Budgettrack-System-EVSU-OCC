# LIB Fiscal Year Format Fix

## Problem Found

The fiscal year formats between LIB and PPMP tables were inconsistent:

- **LIB table:** Uses `'FY 2026'` (with "FY " prefix)
- **PPMP table:** Uses `'2026'` (without "FY " prefix)

The validation query was looking for an exact match, so it couldn't find finalized PPMPs even when they existed.

## Diagnosis Results

```
LIB Fiscal Year Formats:
  'FY 2026' (1 LIBs)
  '2026' (2 LIBs)

PPMP Fiscal Year Formats:
  '2026' (1 PPMPs)
  '2027' (1 PPMPs)

Finalized PPMPs:
  '2026': 1 finalized (CS-2026-001)
  '2027': 1 finalized (CS-2027-001)

Mismatch:
  ⚠️  LIB uses: 'FY 2026'
  ⚠️  PPMP uses: '2026'
```

## Solution

Updated the validation query to handle multiple fiscal year formats:

### Old Query (Exact Match Only)
```sql
WHERE fiscal_year = ?
```

### New Query (Flexible Matching)
```sql
WHERE (fiscal_year = ? OR fiscal_year = ? OR fiscal_year LIKE ?)
```

### Matching Logic
```php
// Extract year number from LIB fiscal year
preg_match('/\d{4}/', $libFiscalYear, $matches);
$yearNumber = $matches[0]; // e.g., "2026"

// Check for multiple formats:
// 1. "2026" (just the year)
// 2. "FY 2026" (with FY prefix)
// 3. "%2026%" (contains the year anywhere)
```

## Examples

### Example 1: LIB with "FY 2026"
```
LIB fiscal_year: "FY 2026"
Extract year: "2026"

Query checks:
  fiscal_year = "2026" ✓ (matches PPMP)
  fiscal_year = "FY 2026" ✓ (matches other LIBs)
  fiscal_year LIKE "%2026%" ✓ (catches variations)

Result: ✅ Finds PPMP with fiscal_year = "2026"
```

### Example 2: LIB with "2026"
```
LIB fiscal_year: "2026"
Extract year: "2026"

Query checks:
  fiscal_year = "2026" ✓
  fiscal_year = "FY 2026" ✓
  fiscal_year LIKE "%2026%" ✓

Result: ✅ Finds PPMP with fiscal_year = "2026"
```

### Example 3: LIB with "Fiscal Year 2026"
```
LIB fiscal_year: "Fiscal Year 2026"
Extract year: "2026"

Query checks:
  fiscal_year = "2026" ✓
  fiscal_year = "FY 2026" ✓
  fiscal_year LIKE "%2026%" ✓

Result: ✅ Finds PPMP with fiscal_year = "2026"
```

## Code Changes

### File Modified
`api/finalize_lib.php`

### Before
```php
$ppmpCheckStmt = $db->prepare("
    SELECT COUNT(*) as finalized_count
    FROM ppmp
    WHERE department_id = ?
    AND fiscal_year = ?  // ❌ Exact match only
    AND is_final = 1
    AND status = 'approved'
");

$ppmpCheckStmt->execute([
    $lib['department_id'],
    $lib['fiscal_year']  // ❌ "FY 2026" won't match "2026"
]);
```

### After
```php
// Extract year number
preg_match('/\d{4}/', $libFiscalYear, $matches);
$yearNumber = $matches[0] ?? $libFiscalYear;

$ppmpCheckStmt = $db->prepare("
    SELECT COUNT(*) as finalized_count
    FROM ppmp
    WHERE department_id = ?
    AND (fiscal_year = ? OR fiscal_year = ? OR fiscal_year LIKE ?)  // ✅ Flexible matching
    AND is_final = 1
    AND status = 'approved'
");

$ppmpCheckStmt->execute([
    $lib['department_id'],
    $yearNumber,           // ✅ "2026"
    'FY ' . $yearNumber,   // ✅ "FY 2026"
    '%' . $yearNumber . '%' // ✅ "%2026%"
]);
```

## Testing

### Test Case 1: LIB "FY 2026" + PPMP "2026"
- **Before:** ❌ Error "No finalized PPMP found"
- **After:** ✅ Success (finds PPMP)

### Test Case 2: LIB "2026" + PPMP "2026"
- **Before:** ✅ Success
- **After:** ✅ Success (still works)

### Test Case 3: LIB "FY 2026" + PPMP "FY 2026"
- **Before:** ✅ Success
- **After:** ✅ Success (still works)

## Diagnostic Scripts Created

1. **test_lib_ppmp_fiscal_year.php** - Test specific LIB/PPMP validation
2. **diagnose_fiscal_year_mismatch.php** - Diagnose format mismatches

## Status

✅ **FIXED** - Fiscal year format mismatch resolved

## Implementation Date
April 14, 2026
