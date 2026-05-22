# PPMP to LIB Workflow Diagram

## Complete Workflow with Validation

```
┌─────────────────────────────────────────────────────────────────┐
│                    PPMP CREATION PHASE                          │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────────┐
    │  Create PPMP     │
    │  (Draft Status)  │
    │  is_final = 0    │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │  Add PPMP Items  │
    │  - Description   │
    │  - Budget        │
    │  - Dates         │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │  Link Items to   │
    │  LIB Categories  │
    │  - Category      │
    │  - Particulars   │
    │  - UACS Code     │
    └────────┬─────────┘
             │
             ▼

┌─────────────────────────────────────────────────────────────────┐
│                    AUTO-SYNC TO LIB                             │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────────┐
    │  Items Sync to   │
    │  LIB             │
    │  source = 'ppmp' │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │  LIB Items       │
    │  Created/Updated │
    │  - Read-Only     │
    │  - PPMP Badge    │
    │  - Locked Badge  │
    └────────┬─────────┘
             │
             ▼

┌─────────────────────────────────────────────────────────────────┐
│                    FINALIZATION PHASE                           │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────────┐
    │  Finalize PPMP   │ ◄─── MUST DO THIS FIRST
    │  is_final = 1    │
    │  status =        │
    │  'approved'      │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │  Try to Finalize │
    │  LIB             │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │  VALIDATION      │
    │  Check PPMP      │
    │  Status          │
    └────────┬─────────┘
             │
        ┌────┴────┐
        │         │
        ▼         ▼
   ┌─────┐   ┌─────┐
   │ YES │   │ NO  │
   └──┬──┘   └──┬──┘
      │         │
      │         ▼
      │    ┌──────────────────┐
      │    │  ❌ BLOCKED      │
      │    │  Error Message:  │
      │    │  "Cannot         │
      │    │  finalize LIB:   │
      │    │  PPMP-2026-001   │
      │    │  not finalized"  │
      │    └──────────────────┘
      │
      ▼
┌──────────────────┐
│  ✅ ALLOWED      │
│  LIB Finalized   │
│  status =        │
│  'approved'      │
└────────┬─────────┘
         │
         ▼

┌─────────────────────────────────────────────────────────────────┐
│                    UTILIZATION SYNC                             │
└─────────────────────────────────────────────────────────────────┘

    ┌──────────────────┐
    │  Sync to Budget  │
    │  Utilization     │
    │  - Allocated     │
    │  - Deductions    │
    │  - Balance       │
    └────────┬─────────┘
             │
             ▼
    ┌──────────────────┐
    │  Ready for       │
    │  Utilization     │
    │  Tracking        │
    └──────────────────┘
```

---

## Key Validation Points

### 1. PPMP Item Creation
- ✅ Items can be added/edited
- ✅ Items can be linked to LIB categories
- ✅ Auto-scroll to new items
- ✅ Search bar for 5+ items

### 2. LIB Sync
- ✅ Items sync automatically
- ✅ Items marked with source='ppmp'
- ✅ Items are read-only in LIB
- ✅ Visual badges show PPMP link

### 3. PPMP Finalization
- ✅ User marks PPMP as final
- ✅ is_final = 1
- ✅ status = 'approved'
- ✅ Items locked in PPMP

### 4. LIB Finalization (NEW VALIDATION)
- ✅ Check all PPMP-linked items
- ✅ Verify source PPMPs are finalized
- ❌ Block if any PPMP not finalized
- ✅ Show error with PPMP numbers
- ✅ Allow if all PPMPs finalized

### 5. Utilization Sync
- ✅ LIB data syncs to utilization
- ✅ Budget tracking enabled
- ✅ Deductions can be made

---

## Error Scenarios

### Scenario 1: Try to Finalize LIB with Draft PPMP

```
User Action: Click "Finalize LIB"
             ↓
System Check: Find PPMP-linked items
             ↓
System Check: Find source PPMP
             ↓
System Check: PPMP is_final = 0 ❌
             ↓
System Response: 
┌─────────────────────────────────────────────┐
│ ❌ Error                                    │
│                                             │
│ Cannot finalize LIB: The following PPMP(s)  │
│ linked to this LIB are not finalized:       │
│ PPMP-2026-001.                              │
│                                             │
│ Please finalize all linked PPMPs before     │
│ finalizing the LIB.                         │
└─────────────────────────────────────────────┘
             ↓
User Action: Go to PPMP page
             ↓
User Action: Finalize PPMP-2026-001
             ↓
User Action: Return to LIB page
             ↓
User Action: Click "Finalize LIB" again
             ↓
System Check: PPMP is_final = 1 ✅
             ↓
System Response: ✅ LIB Finalized Successfully
```

### Scenario 2: LIB with Multiple PPMPs

```
LIB Items:
- Item 1: From PPMP-2026-001 (Draft) ❌
- Item 2: From PPMP-2026-002 (Final) ✅
- Item 3: From PPMP-2026-003 (Draft) ❌
- Item 4: Manual item ✅

User Action: Click "Finalize LIB"
             ↓
System Check: Find 3 PPMP-linked items
             ↓
System Check: PPMP-2026-001 not finalized ❌
System Check: PPMP-2026-002 finalized ✅
System Check: PPMP-2026-003 not finalized ❌
             ↓
System Response:
┌─────────────────────────────────────────────┐
│ ❌ Error                                    │
│                                             │
│ Cannot finalize LIB: The following PPMP(s)  │
│ linked to this LIB are not finalized:       │
│ PPMP-2026-001, PPMP-2026-003.              │
│                                             │
│ Please finalize all linked PPMPs before     │
│ finalizing the LIB.                         │
└─────────────────────────────────────────────┘
```

### Scenario 3: LIB with Only Manual Items

```
LIB Items:
- Item 1: Manual (source='manual') ✅
- Item 2: Manual (source='manual') ✅
- Item 3: Manual (source='manual') ✅

User Action: Click "Finalize LIB"
             ↓
System Check: No PPMP-linked items found
             ↓
System Response: ✅ LIB Finalized Successfully
             ↓
Note: Validation skipped (no PPMP items)
```

---

## Data Flow

```
┌──────────┐     ┌──────────┐     ┌──────────┐
│   PPMP   │────▶│   LIB    │────▶│Utilization│
│  Items   │     │  Items   │     │  Entries  │
└──────────┘     └──────────┘     └──────────┘
     │                │                 │
     │                │                 │
  Draft           Read-Only         Tracking
  Editable        Locked            Active
     │                │                 │
     ▼                ▼                 ▼
  Finalize        Finalize          Deduct
  (is_final=1)    (status=          (track
                   approved)         spending)
```

---

## Status Transitions

### PPMP Status
```
Draft ──────▶ Finalized
(is_final=0)  (is_final=1)
              (status='approved')
```

### LIB Status
```
Draft ──────▶ Approved
(editable)    (final)
              (synced to utilization)
```

### Validation Gate
```
PPMP: Draft ──────▶ LIB: Cannot Finalize ❌
PPMP: Final ──────▶ LIB: Can Finalize ✅
```

---

## Implementation Date
April 14, 2026
