# PPMP to LIB Auto-Sync Feature

## Overview
This feature automatically syncs PPMP items that are linked to LIB expense categories to the Line Item Budget (LIB) when a PPMP is saved (either as draft or final).

## How It Works

### 1. PPMP Item Linking
When creating or editing a PPMP, users can link each item to a LIB expense category by:
- Selecting a category (e.g., "Office Supplies")
- Specifying particulars (e.g., "Bond Paper")
- Providing an account code (UACS code)

### 2. Automatic Sync to LIB
When a PPMP is saved (draft or final), the system:
1. Checks if any PPMP items have LIB mappings
2. Verifies that the LIB is not finalized/approved
3. Creates a LIB if one doesn't exist for the department and fiscal year
4. Adds or updates LIB items based on PPMP items

### 3. LIB Item Format
PPMP items are added to the LIB with the following format:
- **Category**: From the PPMP item's `lib_category`
- **Particulars**: `{lib_particulars} (PPMP #{ppmp_number} - Item #{item_number})`
- **Account Code**: From the PPMP item's `lib_account_code`
- **Amount**: From the PPMP item's `estimated_budget`

Example:
```
Category: Office Supplies
Particulars: Bond Paper (PPMP #2025-001 - Item #1)
Account Code: 5-02-01-010
Amount: 15,000.00
```

## Key Features

### ✅ Automatic Creation
- If no LIB exists for the department and fiscal year, one is automatically created
- LIB is created in "draft" status

### ✅ Update Existing Items
- If a PPMP item is already synced to LIB, the amount is updated if changed
- Prevents duplicate entries

### ✅ Safety Checks
- **Cannot sync to finalized LIB**: If the LIB status is "approved", sync is blocked
- **Error handling**: Sync failures are logged but don't prevent PPMP creation/update

### ✅ Works for Both Draft and Final
- Syncs when saving as draft
- Syncs when saving as final
- Allows departments to build their LIB incrementally

## Database Structure

### PPMP Items Table
```sql
ALTER TABLE ppmp_items 
ADD COLUMN lib_category VARCHAR(255) NULL,
ADD COLUMN lib_particulars VARCHAR(255) NULL,
ADD COLUMN lib_account_code VARCHAR(50) NULL;
```

### LIB Items Table
```sql
line_item_budget_items:
- id
- lib_id (FK to line_item_budgets)
- category
- particulars (includes PPMP reference)
- account_code
- amount
- sort_order
```

## API Endpoint

### `api/sync_ppmp_to_lib.php`
**Method**: POST  
**Input**: `{ "ppmp_id": 123 }`  
**Output**:
```json
{
  "success": true,
  "message": "Successfully synced PPMP items to LIB",
  "items_synced": 5,
  "items_updated": 2,
  "lib_id": 45
}
```

## Usage Flow

### For Department Users:
1. Create/Edit PPMP
2. Add items and link them to LIB categories
3. Save as draft or final
4. System automatically syncs to LIB
5. View LIB to see PPMP items included

### For Budget Office:
1. Department saves PPMP with LIB mappings
2. LIB is automatically updated
3. Budget office can review LIB with PPMP items
4. Once LIB is approved/finalized, no more PPMP items can be synced

## Error Handling

### Sync Failures
- Logged to error log
- PPMP creation/update still succeeds
- User is not notified (silent failure)

### LIB Finalized
- Sync is blocked
- Error message: "Cannot sync to LIB: LIB is already finalized/approved"
- PPMP can still be saved, but items won't sync

## Benefits

1. **Efficiency**: No manual entry of PPMP items into LIB
2. **Accuracy**: Direct mapping ensures consistency
3. **Traceability**: LIB items reference their source PPMP
4. **Flexibility**: Works with both draft and final PPMPs
5. **Safety**: Cannot overwrite finalized LIBs

## Implementation Files

- `api/sync_ppmp_to_lib.php` - Main sync logic
- `api/create_ppmp.php` - Triggers sync on create
- `api/update_ppmp.php` - Triggers sync on update
- `database/ppmp_utilization_integration.sql` - Database schema

## Testing Checklist

- [ ] Create PPMP with LIB mappings → Check LIB updated
- [ ] Update PPMP item amount → Check LIB amount updated
- [ ] Save PPMP as draft → Check LIB updated
- [ ] Save PPMP as final → Check LIB updated
- [ ] Try to sync to finalized LIB → Check blocked
- [ ] Create PPMP without LIB mappings → Check no sync
- [ ] Multiple PPMP items to same category → Check all added

## Future Enhancements

1. User notification when sync completes
2. Sync status indicator in PPMP view
3. Ability to manually trigger sync
4. Bulk sync for multiple PPMPs
5. Sync history/audit trail
