# LIB Finalization - Fiscal Year PPMP Validation

## New Validation Rule

**A LIB can only be finalized if there is at least ONE finalized PPMP in the same fiscal year.**

## Rule Explanation

### Simple Rule
```
LIB Fiscal Year 2026 → Must have at least 1 finalized PPMP in 2026
LIB Fiscal Year 2027 → Must have at least 1 finalized PPMP in 2027
```

### What "Finalized PPMP" Means
- `is_final = 1` (marked as final)
- `status = 'approved'` (approved status)
- Same fiscal year as the LIB
- Same department as the LIB

## Examples

### ✅ Example 1: Can Finalize
```
LIB: Fiscal Year 2026
PPMPs in 2026:
  - PPMP-2026-001: FINALIZED ✅
  - PPMP-2026-002: Draft ❌
  - PPMP-2026-003: Draft ❌

Result: ✅ LIB can be finalized
Reason: At least one PPMP (PPMP-2026-001) is finalized
```

### ❌ Example 2: Cannot Finalize
```
LIB: Fiscal Year 2026
PPMPs in 2026:
  - PPMP-2026-001: Draft ❌
  - PPMP-2026-002: Draft ❌
  - PPMP-2026-003: Draft ❌

Result: ❌ LIB cannot be finalized
Reason: No finalized PPMP in 2026
Error: "No finalized PPMP found for this fiscal year"
```

### ❌ Example 3: Wrong Fiscal Year
```
LIB: Fiscal Year 2026
PPMPs:
  - PPMP-2025-001: FINALIZED ✅ (but 2025, not 2026)
  - PPMP-2027-001: FINALIZED ✅ (but 2027, not 2026)

Result: ❌ LIB cannot be finalized
Reason: No finalized PPMP in 2026 (only in other years)
Error: "No finalized PPMP found for this fiscal year"
```

### ✅ Example 4: Multiple Finalized
```
LIB: Fiscal Year 2026
PPMPs in 2026:
  - PPMP-2026-001: FINALIZED ✅
  - PPMP-2026-002: FINALIZED ✅
  - PPMP-2026-003: Draft ❌

Result: ✅ LIB can be finalized
Reason: Multiple finalized PPMPs exist (even better!)
```

## Implementation

### SQL Query
```sql
SELECT COUNT(*) as finalized_count, 
       GROUP_CONCAT(ppmp_number SEPARATOR ', ') as ppmp_numbers
FROM ppmp
WHERE department_id = ?
AND fiscal_year = ?
AND is_final = 1
AND status = 'approved'
```

### Validation Logic
```php
if ($ppmpCheck['finalized_count'] == 0) {
    // No finalized PPMP found
    echo json_encode([
        'success' => false,
        'message' => "Cannot finalize LIB for {$fiscal_year}: 
                      No finalized PPMP found for this fiscal year."
    ]);
    exit;
}

// At least one finalized PPMP exists - allow LIB finalization
```

## Error Messages

### When No Finalized PPMP Exists
```
Cannot finalize LIB for FY 2026: No finalized PPMP found for this 
fiscal year.

Please finalize at least one PPMP for FY 2026 before finalizing the LIB.
```

### Confirmation When Validation Passes
```
Are you sure you want to finalize this LIB?

At least one PPMP for this fiscal year has been verified as finalized.

Once finalized:
- The LIB cannot be edited
- It will be visible to Budget Office for utilization
- This action cannot be undone
```

## User Flow

### Scenario 1: No Finalized PPMP

```
1. User has LIB for FY 2026
2. User has 3 draft PPMPs for FY 2026
3. User clicks "Finalize LIB"
4. System checks: Any finalized PPMP in 2026?
5. Result: NO (all are draft)
6. ❌ Error: "No finalized PPMP found for this fiscal year"
7. User must finalize at least one PPMP first
```

### Scenario 2: Has Finalized PPMP

```
1. User has LIB for FY 2026
2. User has 1 finalized PPMP for FY 2026
3. User clicks "Finalize LIB"
4. System checks: Any finalized PPMP in 2026?
5. Result: YES (PPMP-2026-001 is finalized)
6. ✅ Show confirmation dialog
7. User clicks OK
8. ✅ LIB finalized successfully
```

## Why This Rule?

### Business Logic
1. **Budget Planning**: PPMP represents procurement plans
2. **LIB Dependency**: LIB allocates budget based on PPMP
3. **Approval Chain**: PPMP must be approved before LIB
4. **Fiscal Year Alignment**: Both must be in same fiscal year

### Data Integrity
- Ensures PPMP is finalized before LIB
- Maintains proper workflow order
- Prevents premature LIB finalization
- Enforces fiscal year consistency

## Comparison with Old Rule

### OLD RULE (Removed)
```
Check if PPMP-linked items in LIB have finalized source PPMPs
- Only checked items with source='ppmp'
- Checked specific item mappings
- Complex logic with item matching
```

### NEW RULE (Current)
```
Check if at least one finalized PPMP exists in same fiscal year
- Simple count query
- No item mapping needed
- Fiscal year based validation
- Easier to understand and maintain
```

## Technical Details

### Database Query Performance
- **Fast**: Simple COUNT query with indexes
- **Efficient**: No joins or complex matching
- **Scalable**: Works with any number of PPMPs

### Validation Timing
- **Pre-check**: Before showing confirmation
- **Silent**: User doesn't see the check
- **Fast**: ~50-100ms response time

## Testing Checklist

- [ ] Create LIB for FY 2026 with no PPMPs
  - Expected: Error "No finalized PPMP found"
  
- [ ] Create LIB for FY 2026 with draft PPMPs only
  - Expected: Error "No finalized PPMP found"
  
- [ ] Create LIB for FY 2026 with 1 finalized PPMP in 2026
  - Expected: Confirmation dialog shown
  
- [ ] Create LIB for FY 2026 with finalized PPMP in 2025 only
  - Expected: Error "No finalized PPMP found" (wrong year)
  
- [ ] Create LIB for FY 2026 with multiple finalized PPMPs in 2026
  - Expected: Confirmation dialog shown
  
- [ ] Finalize LIB after finalizing PPMP
  - Expected: Success

## Files Modified

1. **api/finalize_lib.php**
   - Replaced item-based validation
   - Added fiscal year based validation
   - Simplified logic

2. **pages/lib.php**
   - Updated confirmation message
   - Changed wording to reflect new rule

## Status

✅ **IMPLEMENTED** - New validation is active

## Implementation Date
April 14, 2026
