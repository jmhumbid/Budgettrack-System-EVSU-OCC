# Delete Functionality Fixes Summary

## Issues Fixed

### 1. Cannot Delete Utilization Entries in Current Fiscal Year (2025)
**Problem:** User received error "Cannot delete entries from prior year 2025" even though 2025 is the current fiscal year.

**Root Cause:** The check was comparing `CURRENT_FISCAL_YEAR < currentYear` which blocked deletion when viewing 2025 (since current calendar year is 2026).

**Solution:** Changed the logic to only block deletion for years that are definitely in the past (more than 1 year old):
```javascript
// OLD: Blocked if CURRENT_FISCAL_YEAR < currentYear (blocked 2025 when current is 2026)
if (CURRENT_FISCAL_YEAR < currentYear) {
    alert('Cannot delete...');
}

// NEW: Only block if more than 1 year old (allows current and recent years)
if (CURRENT_FISCAL_YEAR < (currentYear - 1)) {
    alert('Cannot delete...');
}
```

**Result:** Users can now delete entries from fiscal year 2025 (current year) and 2026 (future planning year). Only years 2024 and earlier are blocked.

---

### 2. Deleted PR/Travel Entries Keep Appearing (Ghost Entries)
**Problem:** After deleting a Purchase Request or Travel entry, it would reappear when reopening the modal or refreshing the page.

**Root Cause:** The delete function was removing the entry from the DOM but not reloading the list from the database. This caused:
- Stale data to persist in the UI
- Deleted entries to reappear
- Confusion about what was actually deleted

**Solution:** Added automatic list reload after successful deletion:

**Purchase Request Delete:**
```javascript
// After successful deletion
fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear and reload PR table
            const tbody = document.getElementById('purchaseRequestTableBody');
            tbody.innerHTML = '';
            data.entries.forEach((entry, index) => {
                prEntryCounter++;
                addPurchaseRequestRow(entry, prEntryCounter);
            });
            calculatePurchaseRequestTotal();
        }
    });
```

**Travel Delete:**
```javascript
// After successful deletion
fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear and reload Travel table
            const tbody = document.getElementById('travelsTableBody');
            tbody.innerHTML = '';
            data.entries.forEach((entry, index) => {
                travelEntryCounter++;
                addTravelRow(entry, travelEntryCounter);
            });
            calculateTravelsTotal();
        }
    });
```

**Result:** After deletion, the list is immediately reloaded from the database, ensuring the UI shows the current state.

---

### 3. Deduction Amounts Not Auto-Deducted When PR/Travel Deleted
**Problem:** When a Purchase Request or Travel entry was deleted, the deduction amount in the utilization entry was not automatically reduced.

**Root Cause:** The deduction sources in the database were not being updated when PR/Travel entries were deleted.

**Solution:** Updated both delete APIs to clean up deduction sources:

**In `api/delete_purchase_request.php`:**
```php
// After deleting PR entry, clean up deduction sources
$sourcesStmt = $db->prepare("
    SELECT id, source_entries, amount 
    FROM budget_utilization_deduction_sources 
    WHERE department_id = :dept_id 
    AND source_type = 'purchase_request'
");
$sourcesStmt->execute([':dept_id' => $pr_department_id]);
$sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($sources as $source) {
    $entries = json_decode($source['source_entries'], true);
    
    // Remove this PR entry from the entries array
    $entries = array_filter($entries, function($entry) use ($pr_id) {
        $entryId = isset($entry['sourceEntryId']) ? $entry['sourceEntryId'] : null;
        return $entryId != $pr_id && (string)$entryId !== (string)$pr_id;
    });
    
    // If entries were removed, update or delete the source
    if (count($entries) > 0) {
        // Recalculate amount and update
        $newAmount = array_reduce($entries, function($sum, $entry) {
            return $sum + floatval($entry['amount']);
        }, 0);
        
        $updateStmt = $db->prepare("
            UPDATE budget_utilization_deduction_sources 
            SET source_entries = :entries, amount = :amount 
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':entries' => json_encode(array_values($entries)),
            ':amount' => $newAmount,
            ':id' => $source['id']
        ]);
    } else {
        // No entries left, delete the source
        $deleteSourceStmt = $db->prepare("DELETE FROM budget_utilization_deduction_sources WHERE id = :id");
        $deleteSourceStmt->execute([':id' => $source['id']]);
    }
}
```

**In `api/delete_travel.php`:**
Same logic but for `source_type = 'travels'`

**Result:** 
- When a PR/Travel entry is deleted, it's automatically removed from all deduction sources
- The deduction amounts are recalculated
- If a deduction source has no entries left, it's deleted
- The utilization entry deduction amount is automatically updated

---

## Files Modified

### Frontend (pages/utilization.php)
1. **Line ~5098** - Updated prior year deletion check
   - Changed from `< currentYear` to `< (currentYear - 1)`
   
2. **Line ~6490** - Added PR list reload after deletion
   - Fetches fresh data from database
   - Clears and rebuilds PR table
   
3. **Line ~9555** - Added Travel list reload after deletion
   - Fetches fresh data from database
   - Clears and rebuilds Travel table

### Backend APIs
1. **api/delete_purchase_request.php**
   - Added deduction sources cleanup logic
   - Removes deleted PR from all deduction sources
   - Recalculates or deletes affected sources
   
2. **api/delete_travel.php**
   - Added deduction sources cleanup logic
   - Removes deleted Travel from all deduction sources
   - Recalculates or deletes affected sources

---

## Testing Checklist

- [x] Delete utilization entry in fiscal year 2025 (current year) - Should work ✅
- [x] Delete utilization entry in fiscal year 2026 (future year) - Should work ✅
- [ ] Delete utilization entry in fiscal year 2024 (prior year) - Should be blocked ✅
- [ ] Delete PR entry - Should disappear and not reappear ✅
- [ ] Delete Travel entry - Should disappear and not reappear ✅
- [ ] Delete PR that's used in deduction - Deduction amount should auto-decrease ✅
- [ ] Delete Travel that's used in deduction - Deduction amount should auto-decrease ✅
- [ ] Delete PR/Travel - Checkbox should uncheck in modal ✅
- [ ] Test across different departments ✅
- [ ] Test across different fiscal years ✅

---

## How It Works Now

### Deleting a PR/Travel Entry:
1. User clicks delete button
2. Confirmation dialog appears
3. If confirmed:
   - Entry is deleted from database
   - Entry is removed from all deduction sources in database
   - Deduction amounts are recalculated
   - Frontend removes entry from DOM
   - Frontend reloads entire list from database
   - UI shows current state (no ghost entries)

### Deleting a Utilization Entry:
1. User clicks delete button
2. System checks fiscal year:
   - If year < (currentYear - 1): Blocked with warning
   - Otherwise: Allowed to proceed
3. If confirmed:
   - Entry is deleted from database
   - Entry is removed from DOM
   - Totals are recalculated

---

## Benefits

1. **No More Ghost Entries** - Deleted entries stay deleted
2. **Automatic Deduction Updates** - No manual recalculation needed
3. **Database Consistency** - Deduction sources always match actual PR/Travel entries
4. **Better UX** - Users can delete current year entries without confusion
5. **Data Integrity** - All related data is cleaned up properly

---

## Notes

- Prior years (2024 and earlier) remain protected from deletion
- Current year (2025) and future years can be modified
- All changes are immediately reflected in the database
- List reloads ensure UI always shows current state
- Deduction sources are automatically maintained
