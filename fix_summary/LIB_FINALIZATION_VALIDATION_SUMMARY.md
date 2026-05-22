# LIB Finalization Validation - Implementation Summary

## Overview
Added validation to prevent LIB finalization if any linked PPMP items are not finalized. This ensures proper workflow order and data integrity.

---

## Business Rule

**A Line Item Budget (LIB) cannot be finalized if it contains items linked to PPMPs that are not yet finalized.**

---

## Implementation

### File Modified
- `api/finalize_lib.php` - Added PPMP finalization validation

### Validation Steps

1. **Identify PPMP-Linked Items**
   - Query all items in the LIB where `source = 'ppmp'`
   - If no PPMP items found, skip validation

2. **Find Source PPMPs**
   - For each PPMP-linked item, find the source PPMP by matching:
     - Department ID
     - Fiscal Year
     - LIB Category
     - LIB Particulars
     - LIB Account Code

3. **Check PPMP Status**
   - Verify PPMP is finalized: `is_final = 1` AND `status = 'approved'`
   - Collect all unfinalized PPMPs

4. **Block or Allow**
   - If unfinalized PPMPs found: Block with error message listing PPMP numbers
   - If all PPMPs finalized: Allow LIB finalization to proceed

---

## Error Message Format

### Single Unfinalized PPMP
```
Cannot finalize LIB: The following PPMP(s) linked to this LIB are not 
finalized: PPMP-2026-001. Please finalize all linked PPMPs before 
finalizing the LIB.
```

### Multiple Unfinalized PPMPs
```
Cannot finalize LIB: The following PPMP(s) linked to this LIB are not 
finalized: PPMP-2026-001, PPMP-2026-003, PPMP-2026-005. Please finalize 
all linked PPMPs before finalizing the LIB.
```

---

## Workflow Diagram

```
┌─────────────────┐
│  Create PPMP    │
│    (Draft)      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Link PPMP      │
│  Items to LIB   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Items Sync     │
│  to LIB         │
│  (source=ppmp)  │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Finalize PPMP  │ ◄── MUST DO THIS FIRST
│  (is_final=1)   │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Finalize LIB   │ ◄── NOW ALLOWED
│  (status=       │
│   approved)     │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Sync to        │
│  Utilization    │
└─────────────────┘
```

---

## Code Changes

### Before (No Validation)
```php
// Get LIB items
$stmt = $db->prepare("SELECT * FROM line_item_budget_items WHERE lib_id = ?");
$stmt->execute([$libId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Proceed with finalization...
```

### After (With Validation)
```php
// Check if there are any PPMP-linked items that are not finalized
$stmt = $db->prepare("
    SELECT DISTINCT lib_category, lib_particulars, lib_account_code
    FROM line_item_budget_items
    WHERE lib_id = ? AND source = 'ppmp'
");
$stmt->execute([$libId]);
$ppmpLinkedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($ppmpLinkedItems)) {
    $unfinalizedPPMPs = [];
    
    foreach ($ppmpLinkedItems as $linkedItem) {
        // Find unfinalized PPMPs
        $ppmpCheckStmt = $db->prepare("
            SELECT DISTINCT p.id, p.ppmp_number, p.status, p.is_final
            FROM ppmp p
            INNER JOIN ppmp_items pi ON p.id = pi.ppmp_id
            WHERE p.department_id = ?
            AND p.fiscal_year = ?
            AND pi.lib_category = ?
            AND pi.lib_particulars = ?
            AND pi.lib_account_code = ?
            AND (p.is_final = 0 OR p.status != 'approved')
        ");
        
        $ppmpCheckStmt->execute([...]);
        
        if ($unfinalizedPPMP = $ppmpCheckStmt->fetch(PDO::FETCH_ASSOC)) {
            $unfinalizedPPMPs[] = $unfinalizedPPMP['ppmp_number'];
        }
    }
    
    // Block if unfinalized PPMPs found
    if (!empty($unfinalizedPPMPs)) {
        echo json_encode([
            'success' => false,
            'message' => "Cannot finalize LIB: The following PPMP(s)..."
        ]);
        exit;
    }
}

// Proceed with finalization...
```

---

## Benefits

1. ✅ **Data Integrity**: Ensures LIB is only finalized when source data is final
2. ✅ **Proper Workflow**: Enforces correct order of operations
3. ✅ **Clear Feedback**: Users know exactly which PPMPs need to be finalized
4. ✅ **Prevents Errors**: Avoids inconsistent states in the system
5. ✅ **Audit Trail**: Maintains proper approval chain

---

## Edge Cases

### Case 1: LIB with No PPMP Items
- **Items**: All manual (source='manual')
- **Behavior**: Validation skipped, finalization proceeds
- **Result**: ✅ Success

### Case 2: LIB with Mixed Items
- **Items**: Manual + PPMP-linked
- **Behavior**: Only PPMP items checked
- **Result**: ✅ Success if all PPMPs finalized

### Case 3: Multiple PPMPs
- **Items**: From PPMP-001, PPMP-002, PPMP-003
- **Behavior**: All PPMPs must be finalized
- **Result**: ❌ Blocked if any PPMP not finalized

### Case 4: Orphaned Items
- **Items**: PPMP deleted but items remain
- **Behavior**: No source PPMP found, treated as manual
- **Result**: ✅ Success (no blocking)

---

## Testing

### Test Script
Run `test_lib_finalization_validation.php` to test the validation logic.

### Manual Testing Steps

1. **Setup**
   - Create a draft PPMP
   - Link PPMP items to LIB categories
   - Verify items sync to LIB with source='ppmp'

2. **Test Blocking**
   - Try to finalize LIB while PPMP is draft
   - Verify error message appears
   - Verify PPMP number is listed in error

3. **Test Success**
   - Finalize the PPMP (mark as final)
   - Try to finalize LIB again
   - Verify LIB finalizes successfully

4. **Test Multiple PPMPs**
   - Create 2-3 draft PPMPs linked to same LIB
   - Try to finalize LIB
   - Verify all PPMP numbers listed in error
   - Finalize all PPMPs
   - Verify LIB can now be finalized

---

## Related Features

- **PPMP-LIB Sync**: Items sync from PPMP to LIB with source='ppmp'
- **Read-Only LIB Items**: PPMP-linked items cannot be edited in LIB
- **PPMP Finalization**: PPMP must be marked as final (is_final=1, status='approved')
- **LIB to Utilization Sync**: Finalized LIB syncs to budget utilization

---

## Documentation Files

1. `LIB_PPMP_FINALIZATION_VALIDATION.md` - Detailed technical documentation
2. `LIB_PPMP_FINALIZATION_QUICK_GUIDE.md` - Quick reference for users
3. `test_lib_finalization_validation.php` - Test script

---

## Database Requirements

### Tables Used
- `line_item_budgets` - LIB header
- `line_item_budget_items` - LIB items (requires `source` column)
- `ppmp` - PPMP header (requires `is_final`, `status`, `ppmp_number`)
- `ppmp_items` - PPMP items (requires LIB mapping fields)

### Required Columns
- `line_item_budget_items.source` - 'manual' or 'ppmp'
- `ppmp.is_final` - 0 or 1
- `ppmp.status` - 'draft', 'pending', 'approved'
- `ppmp.ppmp_number` - Display number

---

## User Impact

### Before Implementation
- Users could finalize LIB with draft PPMPs
- Data inconsistency possible
- No workflow enforcement

### After Implementation
- Users must finalize PPMPs first
- Clear error messages guide users
- Proper workflow enforced
- Data integrity maintained

---

## Status

✅ **COMPLETED** - Feature is fully implemented and tested

---

## Implementation Date
April 14, 2026

## Last Updated
April 14, 2026
