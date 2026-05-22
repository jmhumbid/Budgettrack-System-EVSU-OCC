# LIB Finalization - Conditional Confirmation Flow

## How It Works

The confirmation dialog is **CONDITIONAL** - it only appears if all PPMPs are finalized.

---

## Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│  User clicks "Finalize LIB" button                          │
└────────────────────┬────────────────────────────────────────┘
                     │
                     ▼
┌─────────────────────────────────────────────────────────────┐
│  System checks PPMP status (SILENT VALIDATION)              │
│  API: finalize_lib.php?check_only=1                         │
└────────────────────┬────────────────────────────────────────┘
                     │
              ┌──────┴──────┐
              │             │
              ▼             ▼
        PPMP NOT        PPMP IS
        FINALIZED       FINALIZED
              │             │
              │             │
              ▼             ▼
┌─────────────────────┐   ┌─────────────────────────────────┐
│  ❌ ERROR ALERT     │   │  ✅ CONFIRMATION DIALOG         │
│  (NO CONFIRMATION)  │   │  (CONDITION MET)                │
│                     │   │                                 │
│  ┌───────────────┐  │   │  ┌───────────────────────────┐ │
│  │ localhost says│  │   │  │ localhost says            │ │
│  │               │  │   │  │                           │ │
│  │ Cannot        │  │   │  │ Are you sure you want to  │ │
│  │ finalize LIB: │  │   │  │ finalize this LIB?        │ │
│  │               │  │   │  │                           │ │
│  │ The following │  │   │  │ All linked PPMPs have     │ │
│  │ PPMP(s) must  │  │   │  │ been verified as          │ │
│  │ be finalized  │  │   │  │ finalized.                │ │
│  │ first:        │  │   │  │                           │ │
│  │ PPMP-2026-001 │  │   │  │ Once finalized:           │ │
│  │               │  │   │  │ - Cannot be edited        │ │
│  │         [OK]  │  │   │  │ - Visible to Budget       │ │
│  └───────────────┘  │   │  │ - Cannot be undone        │ │
│                     │   │  │                           │ │
│  User must fix      │   │  │        [OK]  [Cancel]     │ │
│  PPMP first         │   │  └───────────────────────────┘ │
└─────────────────────┘   └──────────────┬────────────────┘
                                         │
                                    ┌────┴────┐
                                    │         │
                                    ▼         ▼
                                  [OK]    [Cancel]
                                    │         │
                                    │         ▼
                                    │    No action
                                    │    (LIB stays draft)
                                    │
                                    ▼
                          ┌──────────────────────────┐
                          │  Finalize LIB            │
                          │  API: finalize_lib.php   │
                          └──────────┬───────────────┘
                                     │
                                     ▼
                          ┌──────────────────────────┐
                          │  ✅ SUCCESS              │
                          │  LIB finalized!          │
                          │  Synced to utilization   │
                          └──────────────────────────┘
```

---

## Key Points

### 1. Confirmation is CONDITIONAL
```
IF all PPMPs finalized:
    THEN show confirmation dialog
ELSE:
    Show error message (no confirmation)
```

### 2. Two Possible Outcomes

**Outcome A: PPMPs Not Finalized**
- ❌ Error alert appears
- ❌ No confirmation dialog
- ❌ LIB not finalized
- User must finalize PPMPs first

**Outcome B: PPMPs Finalized**
- ✅ Confirmation dialog appears
- ✅ Message confirms PPMPs verified
- ✅ User can proceed or cancel
- ✅ LIB finalizes if user clicks OK

---

## Message Comparison

### Error Message (When PPMPs Not Finalized)
```
┌─────────────────────────────────────────────────────────┐
│ localhost says                                          │
│                                                         │
│ Cannot finalize LIB: The following PPMP(s) linked to   │
│ this LIB must be finalized first: PPMP-2026-001.       │
│                                                         │
│ Please finalize all linked PPMPs before finalizing     │
│ the LIB.                                                │
│                                                         │
│                                                   [OK]  │
└─────────────────────────────────────────────────────────┘
```

### Confirmation Message (When PPMPs Finalized)
```
┌─────────────────────────────────────────────────────────┐
│ localhost says                                          │
│                                                         │
│ Are you sure you want to finalize this LIB?            │
│                                                         │
│ All linked PPMPs have been verified as finalized.      │
│                                                         │
│ Once finalized:                                         │
│ - The LIB cannot be edited                             │
│ - It will be visible to Budget Office for utilization  │
│ - This action cannot be undone                         │
│                                                         │
│                                        [OK]  [Cancel]   │
└─────────────────────────────────────────────────────────┘
```

---

## Validation Logic

### Step 1: Pre-Check (Silent)
```javascript
// Check PPMP status without user knowing
fetch('finalize_lib.php', {
    body: { lib_id: 123, check_only: 1 }
})
```

**Backend checks:**
1. Find all PPMP-linked items in LIB
2. For each item, find source PPMP
3. Check if PPMP is finalized (is_final=1 AND status='approved')
4. Return success/failure

### Step 2: Show Appropriate Message
```javascript
.then(data => {
    if (!data.success) {
        // PPMPs not finalized
        alert(data.message); // Error message
        return; // STOP HERE
    }
    
    // PPMPs finalized - show confirmation
    if (confirm('Are you sure...')) {
        // Proceed with finalization
    }
});
```

---

## User Experience

### Scenario 1: PPMP Not Finalized

```
1. User clicks "Finalize LIB"
2. System checks (silent)
3. ❌ PPMP-2026-001 is draft
4. Error alert: "PPMP must be finalized first"
5. User clicks OK on error
6. User goes to PPMP page
7. User finalizes PPMP-2026-001
8. User returns to LIB page
9. User clicks "Finalize LIB" again
10. System checks (silent)
11. ✅ All PPMPs finalized
12. Confirmation: "All linked PPMPs verified..."
13. User clicks OK
14. ✅ LIB finalized successfully
```

### Scenario 2: PPMP Already Finalized

```
1. User clicks "Finalize LIB"
2. System checks (silent)
3. ✅ All PPMPs finalized
4. Confirmation: "All linked PPMPs verified..."
5. User clicks OK
6. ✅ LIB finalized successfully
```

### Scenario 3: User Cancels

```
1. User clicks "Finalize LIB"
2. System checks (silent)
3. ✅ All PPMPs finalized
4. Confirmation: "All linked PPMPs verified..."
5. User clicks Cancel
6. Nothing happens (LIB stays draft)
```

---

## Technical Implementation

### Frontend (pages/lib.php)
```javascript
function finalizeLIB(libId) {
    // STEP 1: Check PPMP status
    fetch('finalize_lib.php', { check_only: 1 })
        .then(data => {
            if (!data.success) {
                // CONDITION NOT MET - Show error
                alert(data.message);
                return;
            }
            
            // CONDITION MET - Show confirmation
            if (confirm('Are you sure...All PPMPs verified...')) {
                // STEP 2: Finalize
                fetch('finalize_lib.php', { ... })
            }
        });
}
```

### Backend (api/finalize_lib.php)
```php
// Check PPMP status
if (!empty($unfinalizedPPMPs)) {
    // CONDITION NOT MET
    echo json_encode([
        'success' => false,
        'message' => 'PPMP must be finalized first...'
    ]);
    exit;
}

// CONDITION MET
if ($checkOnly) {
    echo json_encode(['success' => true]);
    exit;
}

// Proceed with finalization...
```

---

## Summary

✅ **Confirmation is CONDITIONAL**
- Only shows if all PPMPs are finalized
- This is enforced by code, not just a message

✅ **Error shows if condition not met**
- Clear error message
- Lists which PPMPs need finalization
- No confirmation dialog shown

✅ **Message confirms validation passed**
- "All linked PPMPs have been verified as finalized"
- User knows it's safe to proceed

---

## Implementation Date
April 14, 2026
