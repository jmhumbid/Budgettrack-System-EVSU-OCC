# LIB PPMP Fiscal Year Rule - Summary

## The Rule (Simple)

**To finalize a LIB, there must be at least ONE finalized PPMP in the same fiscal year.**

## Examples

### ✅ CAN Finalize
```
LIB: FY 2026
PPMP: FY 2026 (FINALIZED) ✅

Result: ✅ Can finalize LIB
```

### ❌ CANNOT Finalize
```
LIB: FY 2026
PPMP: FY 2026 (DRAFT) ❌

Result: ❌ Cannot finalize LIB
Error: "No finalized PPMP found for this fiscal year"
```

### ❌ CANNOT Finalize (Wrong Year)
```
LIB: FY 2026
PPMP: FY 2025 (FINALIZED) ✅ ← Wrong year!

Result: ❌ Cannot finalize LIB
Error: "No finalized PPMP found for this fiscal year"
```

## What Happens

### When You Click "Finalize LIB"

**Step 1: System checks**
```
Does this department have at least 1 finalized PPMP 
in the same fiscal year as this LIB?
```

**Step 2: Result**
```
YES → Show confirmation dialog → Finalize
NO  → Show error message → Cannot finalize
```

## Error Message

```
Cannot finalize LIB for FY 2026: No finalized PPMP found for 
this fiscal year.

Please finalize at least one PPMP for FY 2026 before finalizing 
the LIB.
```

## Confirmation Message

```
Are you sure you want to finalize this LIB?

At least one PPMP for this fiscal year has been verified as 
finalized.

Once finalized:
- The LIB cannot be edited
- It will be visible to Budget Office for utilization
- This action cannot be undone
```

## How to Fix Error

1. Go to PPMP page
2. Find a PPMP for the same fiscal year (e.g., FY 2026)
3. Finalize that PPMP (mark as final)
4. Return to LIB page
5. Click "Finalize LIB" again
6. ✅ Should work now

## Key Points

✅ **Fiscal year must match** - LIB 2026 needs PPMP 2026  
✅ **At least one** - Don't need all PPMPs finalized, just one  
✅ **Same department** - PPMP must be from same department  
✅ **Must be final** - PPMP must have is_final=1 and status='approved'  

## Status

✅ **ACTIVE** - This rule is now enforced

## Implementation Date
April 14, 2026
