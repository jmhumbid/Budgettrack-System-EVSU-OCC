# LIB Draft-to-Final Workflow - Visual Guide

## 🔄 Complete Workflow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  STEP 1: AUTO-GENERATE LIB                                  │
├─────────────────────────────────────────────────────────────┤
│  1. Click "Auto-Generate from Allocations"                  │
│  2. Select Fiscal Year (2024-2028)                          │
│  3. Click "Generate LIB"                                     │
│  4. Preview items with UACS codes                           │
│  5. Click "Save LIB"                                         │
│                                                              │
│  ✅ Result: LIB saved as DRAFT (status = 'draft')          │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  STEP 2: EDIT DRAFT LIB (Inline Add Items)                 │
├─────────────────────────────────────────────────────────────┤
│  Draft LIB Display:                                          │
│  ┌────────────────────────────────────────────────────┐    │
│  │ A. PERSONAL SERVICES                                │    │
│  │ [+ Add Item] ← Click to add items                  │    │
│  │                                                      │    │
│  │ • Honoraria - Part-time    5010210001    ₱50,000   │    │
│  │ • Honoraria - Overload     5010210001    ₱30,000   │    │
│  │                                                      │    │
│  │ Sub-Total: ₱80,000                                  │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  When you click [+ Add Item]:                               │
│  ┌────────────────────────────────────────────────────┐    │
│  │ Particulars: [Type to search UACS...        ]      │    │
│  │              ↓ Autocomplete dropdown appears       │    │
│  │ Account Code: [5010210001] (auto-filled)          │    │
│  │ Amount: [10000.00]                                 │    │
│  │ [Save] [Cancel]                                    │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ✅ Can add multiple items to each category                │
│  ✅ UACS codes auto-populate from search                   │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  STEP 3: FINALIZE LIB                                       │
├─────────────────────────────────────────────────────────────┤
│  After adding all items:                                     │
│                                                              │
│  Grand Total: ₱500,000.00                                   │
│                                                              │
│  [✓ Finalize LIB] ← Green button at bottom                 │
│                                                              │
│  Confirmation Dialog:                                        │
│  ┌────────────────────────────────────────────────────┐    │
│  │ Are you sure you want to finalize this LIB?        │    │
│  │                                                      │    │
│  │ Once finalized:                                     │    │
│  │ • The LIB cannot be edited                         │    │
│  │ • It will be visible to Budget Office              │    │
│  │ • This action cannot be undone                     │    │
│  │                                                      │    │
│  │ [Cancel] [OK]                                       │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  ✅ Result: Status changes to 'approved' (FINAL)           │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│  STEP 4: FINAL LIB (Read-Only)                             │
├─────────────────────────────────────────────────────────────┤
│  Final LIB Display:                                          │
│  ┌────────────────────────────────────────────────────┐    │
│  │ Status: [FINAL] ← Green badge                      │    │
│  │                                                      │    │
│  │ A. PERSONAL SERVICES                                │    │
│  │ (No Add Item button - read-only)                   │    │
│  │                                                      │    │
│  │ • Honoraria - Part-time    5010210001    ₱50,000   │    │
│  │ • Honoraria - Overload     5010210001    ₱30,000   │    │
│  │                                                      │    │
│  │ Sub-Total: ₱80,000                                  │    │
│  └────────────────────────────────────────────────────┘    │
│                                                              │
│  Only action available: [🖨 Print]                          │
│                                                              │
│  ✅ Cannot be edited                                        │
│  ✅ Visible to Budget Office for utilization tracking      │
└─────────────────────────────────────────────────────────────┘
```

## 📊 Status Flow

```
DRAFT → APPROVED (FINAL)
  ↓         ↓
Editable  Read-Only
  ↓         ↓
Hidden    Visible to
from      Budget Office
Budget    for Utilization
Office
```

## 🎯 Key Points

### ✅ DRAFT Status
- **Badge Color**: Gray
- **Badge Text**: "DRAFT"
- **Editable**: YES
- **Add Item Buttons**: Visible
- **Finalize Button**: Visible
- **Visible to Budget Office**: NO

### ✅ APPROVED Status (FINAL)
- **Badge Color**: Green
- **Badge Text**: "FINAL"
- **Editable**: NO
- **Add Item Buttons**: Hidden
- **Finalize Button**: Hidden
- **Visible to Budget Office**: YES

## 🔒 Security Features

1. **Draft-Only Editing**: Backend API checks status before allowing edits
2. **Department Access Control**: Users can only edit their own department's LIBs
3. **Irreversible Finalization**: Once finalized, cannot be reverted to draft
4. **Session Validation**: All API calls require valid user session

## 🎨 UI Elements

### Add Item Button
```
[+ Add Item] ← Blue button, appears below each category header
```

### Inline Add Form
```
┌──────────────────────────────────────────────────────┐
│ Particulars: [Search UACS...] ← Autocomplete        │
│ Account Code: [Auto-filled] ← Read-only             │
│ Amount: [0.00] ← Number input                        │
│ [Save] [Cancel] ← Action buttons                     │
└──────────────────────────────────────────────────────┘
```

### Finalize Button
```
[✓ Finalize LIB] ← Green, large, at bottom of page
```

## 📝 Error Messages

### Trying to Add Item to Final LIB
```
❌ Error: Cannot add items to a finalized LIB. 
   Only draft LIBs can be edited.
```

### Missing Required Fields
```
❌ Please enter particulars
❌ Please select a UACS code
❌ Please enter a valid amount
```

### Already Finalized
```
❌ LIB is already finalized
```

## 🧪 Testing Scenarios

### Scenario 1: Happy Path
1. ✅ Auto-generate LIB → Saved as DRAFT
2. ✅ Add 3 items using inline form → All saved
3. ✅ Click Finalize → Status changes to APPROVED
4. ✅ Try to add item → Error message appears
5. ✅ Verify Budget Office can see the LIB

### Scenario 2: Multiple Categories
1. ✅ Add items to Category A
2. ✅ Add items to Category B
3. ✅ Add items to Category C
4. ✅ Verify all categories show correct sub-totals
5. ✅ Verify grand total is correct

### Scenario 3: UACS Autocomplete
1. ✅ Type "part" → Shows "Honoraria - Part-time"
2. ✅ Type "cos" → Shows "Labor and Wages"
3. ✅ Type "water" → Shows "Water Expenses"
4. ✅ Select item → Account code auto-fills
5. ✅ Save → Item appears in table

## 🚀 Ready to Test!

The implementation is complete. Follow the workflow above to test the system.
