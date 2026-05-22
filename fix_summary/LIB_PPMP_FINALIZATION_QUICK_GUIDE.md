# LIB PPMP Finalization Quick Guide

## Rule
**You cannot finalize a LIB if it contains items from unfinalized PPMPs.**

## Correct Workflow

```
1. Create PPMP → 2. Link to LIB → 3. Finalize PPMP → 4. Finalize LIB
```

## What Happens

### ❌ If PPMP is Not Finalized
```
User clicks "Finalize LIB"
↓
System checks linked PPMPs
↓
Error: "Cannot finalize LIB: The following PPMP(s) linked to this LIB 
are not finalized: PPMP-2026-001. Please finalize all linked PPMPs 
before finalizing the LIB."
```

### ✅ If PPMP is Finalized
```
User clicks "Finalize LIB"
↓
System checks linked PPMPs
↓
All PPMPs are finalized
↓
LIB finalized successfully
↓
Data synced to Utilization
```

## How to Fix

1. **Read the Error Message**: It lists which PPMPs need to be finalized
2. **Open PPMP Page**: Navigate to the PPMP management page
3. **Find the PPMP**: Look for the PPMP number mentioned in the error
4. **Finalize PPMP**: Mark the PPMP as final and save
5. **Return to LIB**: Go back to the LIB page
6. **Try Again**: Click "Finalize LIB" again

## Example Error Messages

### Single PPMP
```
Cannot finalize LIB: The following PPMP(s) linked to this LIB are not 
finalized: PPMP-2026-001. Please finalize all linked PPMPs before 
finalizing the LIB.
```

### Multiple PPMPs
```
Cannot finalize LIB: The following PPMP(s) linked to this LIB are not 
finalized: PPMP-2026-001, PPMP-2026-003, PPMP-2026-005. Please finalize 
all linked PPMPs before finalizing the LIB.
```

## Special Cases

### LIB with Only Manual Items
- **No PPMP items**: Finalization works normally
- **No validation needed**: System skips PPMP check

### LIB with Mixed Items
- **Manual + PPMP items**: Only PPMP items are checked
- **Manual items**: Don't affect finalization
- **PPMP items**: Must have finalized source PPMPs

## Why This Rule Exists

1. **Data Integrity**: Ensures final LIB is based on final PPMP data
2. **Approval Chain**: Maintains proper workflow order
3. **Audit Trail**: Clear record of what was approved when
4. **Prevents Errors**: Avoids inconsistent states

## Quick Checklist

Before finalizing LIB:
- [ ] All linked PPMPs are marked as final
- [ ] All linked PPMPs have status = 'approved'
- [ ] No draft PPMPs are linked to this LIB
- [ ] Error message (if any) has been addressed

## Status Indicators

### PPMP Status
- **Draft**: ❌ Cannot finalize LIB
- **Pending**: ❌ Cannot finalize LIB
- **Approved + Final**: ✅ Can finalize LIB

### LIB Status
- **Draft**: Can be edited, not yet final
- **Approved**: Final, synced to utilization

## Need Help?

If you see the error:
1. Note the PPMP numbers listed
2. Finalize those PPMPs first
3. Return and try again

If error persists:
- Check PPMP status is "Approved"
- Check PPMP is marked as "Final"
- Verify fiscal years match
- Contact system administrator

## Implementation Date
April 14, 2026
