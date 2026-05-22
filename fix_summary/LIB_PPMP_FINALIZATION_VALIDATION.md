# LIB PPMP Finalization Validation

## Overview
Added validation to prevent LIB finalization if any linked PPMP items are not finalized. This ensures data integrity and proper workflow order.

## Business Rule
**A LIB cannot be finalized if it contains items linked to PPMPs that are not yet finalized.**

## Implementation Details

### File Modified
- `api/finalize_lib.php`

### Validation Logic

1. **Check for PPMP-Linked Items**: Query all items in the LIB where `source = 'ppmp'`
2. **Find Source PPMPs**: For each PPMP-linked item, find the source PPMP by matching:
   - Department ID
   - Fiscal Year
   - LIB Category
   - LIB Particulars
   - LIB Account Code
3. **Verify PPMP Status**: Check if the PPMP is finalized:
   - `is_final = 1` AND
   - `status = 'approved'`
4. **Block if Unfinalized**: If any linked PPMP is not finalized, prevent LIB finalization with a clear error message

### SQL Query
```sql
SELECT DISTINCT p.id, p.ppmp_number, p.status, p.is_final
FROM ppmp p
INNER JOIN ppmp_items pi ON p.id = pi.ppmp_id
WHERE p.department_id = ?
AND p.fiscal_year = ?
AND pi.lib_category = ?
AND pi.lib_particulars = ?
AND pi.lib_account_code = ?
AND (p.is_final = 0 OR p.status != 'approved')
```

### Error Message
```
Cannot finalize LIB: The following PPMP(s) linked to this LIB are not finalized: 
PPMP-2026-001, PPMP-2026-003. Please finalize all linked PPMPs before finalizing the LIB.
```

## User Experience

### Before
1. User creates PPMP (draft)
2. PPMP items sync to LIB
3. User could finalize LIB even though PPMP is still draft
4. Data inconsistency: LIB is final but source PPMP is not

### After
1. User creates PPMP (draft)
2. PPMP items sync to LIB
3. User tries to finalize LIB
4. **System blocks finalization** with error message listing unfinalized PPMPs
5. User finalizes PPMP first
6. User can now finalize LIB successfully

## Workflow Order

```
1. Create PPMP (Draft)
   ↓
2. Link PPMP items to LIB categories
   ↓
3. PPMP items sync to LIB (source='ppmp')
   ↓
4. Finalize PPMP (is_final=1, status='approved')
   ↓
5. Finalize LIB (now allowed)
   ↓
6. LIB syncs to Utilization
```

## Benefits

1. **Data Integrity**: Ensures LIB is only finalized when source data is final
2. **Proper Workflow**: Enforces correct order of operations
3. **Clear Feedback**: Users know exactly which PPMPs need to be finalized
4. **Prevents Errors**: Avoids inconsistent states in the system
5. **Audit Trail**: Maintains proper approval chain

## Edge Cases Handled

### Case 1: LIB with No PPMP Items
- **Scenario**: LIB contains only manual items (source='manual')
- **Behavior**: Finalization proceeds normally (no PPMP check needed)

### Case 2: LIB with Mixed Items
- **Scenario**: LIB has both manual and PPMP-linked items
- **Behavior**: Only checks PPMP-linked items; manual items don't affect validation

### Case 3: Multiple PPMPs Linked
- **Scenario**: LIB items come from multiple PPMPs
- **Behavior**: All linked PPMPs must be finalized; error lists all unfinalized ones

### Case 4: PPMP Deleted After Sync
- **Scenario**: PPMP is deleted but items remain in LIB
- **Behavior**: No PPMP found, so no blocking (items are now orphaned manual items)

## Testing Checklist

- [ ] Create draft PPMP with LIB links
- [ ] Verify PPMP items sync to LIB with source='ppmp'
- [ ] Try to finalize LIB while PPMP is draft
- [ ] Verify error message appears with PPMP number
- [ ] Finalize PPMP (mark as final)
- [ ] Try to finalize LIB again
- [ ] Verify LIB finalizes successfully
- [ ] Test with multiple PPMPs linked to same LIB
- [ ] Test with LIB containing only manual items
- [ ] Test with LIB containing mixed manual and PPMP items

## Error Handling

### Validation Errors
- **Unfinalized PPMP**: Clear message listing PPMP numbers
- **Multiple Unfinalized**: Lists all unfinalized PPMPs (comma-separated)
- **No PPMP Number**: Falls back to "PPMP #[ID]"

### Database Errors
- Wrapped in try-catch
- Logged to error log
- Returns generic error message to user

## Related Features

- **PPMP-LIB Sync**: Items sync from PPMP to LIB with source='ppmp'
- **Read-Only LIB Items**: PPMP-linked items cannot be edited in LIB
- **PPMP Finalization**: PPMP must be marked as final before LIB can be finalized

## Database Schema

### Required Fields

**line_item_budget_items table:**
- `source` - 'manual' or 'ppmp'
- `lib_category` - Category name
- `lib_particulars` - Particulars/description
- `lib_account_code` - UACS code

**ppmp table:**
- `is_final` - 0 (draft) or 1 (final)
- `status` - 'draft', 'pending', 'approved'
- `ppmp_number` - Display number (e.g., "PPMP-2026-001")

**ppmp_items table:**
- `lib_category` - Linked LIB category
- `lib_particulars` - Linked LIB particulars
- `lib_account_code` - Linked LIB account code

## Code Example

```php
// Check if there are any PPMP-linked items that are not finalized
$stmt = $db->prepare("
    SELECT DISTINCT lib.lib_category, lib.lib_particulars, lib.lib_account_code
    FROM line_item_budget_items lib
    WHERE lib.lib_id = ? AND lib.source = 'ppmp'
");
$stmt->execute([$libId]);
$ppmpLinkedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($ppmpLinkedItems)) {
    $unfinalizedPPMPs = [];
    
    foreach ($ppmpLinkedItems as $linkedItem) {
        // Find unfinalized PPMPs linked to this item
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
        
        $ppmpCheckStmt->execute([
            $lib['department_id'],
            $lib['fiscal_year'],
            $linkedItem['lib_category'],
            $linkedItem['lib_particulars'],
            $linkedItem['lib_account_code']
        ]);
        
        $unfinalizedPPMP = $ppmpCheckStmt->fetch(PDO::FETCH_ASSOC);
        if ($unfinalizedPPMP) {
            $unfinalizedPPMPs[] = $unfinalizedPPMP['ppmp_number'] ?? 'PPMP #' . $unfinalizedPPMP['id'];
        }
    }
    
    // Block finalization if unfinalized PPMPs found
    if (!empty($unfinalizedPPMPs)) {
        $ppmpList = implode(', ', array_unique($unfinalizedPPMPs));
        echo json_encode([
            'success' => false,
            'message' => "Cannot finalize LIB: The following PPMP(s) linked to this LIB are not finalized: {$ppmpList}. Please finalize all linked PPMPs before finalizing the LIB."
        ]);
        exit;
    }
}
```

## Status

✅ **IMPLEMENTED** - Validation is active and enforced

## Last Updated
April 14, 2026
