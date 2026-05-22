# PPMP to LIB Auto-Sync Feature Guide

## Overview
This feature automatically syncs PPMP items to the Line Item Budget (LIB) when a PPMP is saved (draft or final). Items that are linked to LIB expense categories will be automatically added to the existing draft LIB for the same fiscal year.

## How It Works

### 1. Installation
First, run the installation script to add the necessary database fields:

```bash
php install_ppmp_lib_mapping.php
```

This adds three fields to the `ppmp_items` table:
- `lib_category` - The LIB expense category (e.g., "B. Maintenance & Other Operating Expenses")
- `lib_particulars` - The specific expense (e.g., "Office Supplies Expenses")
- `lib_account_code` - The UACS account code (e.g., "5020301000")

### 2. Creating a PPMP with LIB Links

1. **Create a PPMP** for fiscal year 2026
2. **Add items** to the PPMP
3. **Link each item to a LIB expense category**:
   - Click the "Link to LIB" button on each item
   - Select the appropriate expense category (e.g., "Office Supplies Expenses")
   - The link will be saved with the item
4. **Save the PPMP** (as draft or final)

### 3. Automatic Sync Process

When you save the PPMP, the system automatically:

1. **Finds the existing draft LIB** for the same department and fiscal year
   - Prioritizes draft LIBs over finalized ones
   - If no LIB exists, returns an error (you must create a LIB first)
   - If LIB is finalized/approved, returns an error (cannot modify approved LIBs)

2. **Adds items to the LIB**:
   - Each PPMP item with a LIB link is added as a new row in the LIB
   - The item is added under the correct category (e.g., "B. Maintenance & Other Operating Expenses")
   - The particulars include a reference: "Office Supplies Expenses (PPMP #CS-2026-001 - Item #1)"
   - The account code and amount are copied from the PPMP item

3. **Prevents duplicates**:
   - If the same PPMP item is already synced, it updates the amount instead of creating a duplicate
   - Uses the PPMP reference to identify existing items

### 4. Example Workflow

**Scenario**: Create a PPMP for Computer Studies department, fiscal year 2026

1. **Create LIB first** (if not exists):
   - Go to LIB page
   - Create a draft LIB for fiscal year 2026
   - Add expense categories (e.g., "B. Maintenance & Other Operating Expenses")
   - Add expense items (e.g., "Office Supplies Expenses" with code "5020301000")
   - Save as draft

2. **Create PPMP**:
   - Go to PPMP page
   - Select fiscal year 2026 from the filter
   - Click "Create New PPMP"
   - Add items:
     - Item 1: "Bond papers, pens, folders" - ₱15,000
     - Item 2: "Printer ink cartridges" - ₱8,000
     - Item 3: "Whiteboard markers" - ₱2,500

3. **Link items to LIB**:
   - Item 1: Link to "Office Supplies Expenses" (5020301000)
   - Item 2: Link to "Office Supplies Expenses" (5020301000)
   - Item 3: Link to "Office Supplies Expenses" (5020301000)

4. **Save PPMP**:
   - Click "Save Draft" or check "Mark as Final" and save
   - System automatically syncs to LIB

5. **Result in LIB**:
   The draft LIB for 2026 now contains:
   ```
   B. Maintenance & Other Operating Expenses
   ├─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #1) - ₱15,000
   ├─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #2) - ₱8,000
   └─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #3) - ₱2,500
   ```

### 5. Important Notes

- **LIB must exist first**: You cannot sync to a LIB that doesn't exist. Create the LIB before creating the PPMP.
- **LIB must be draft**: You cannot sync to a finalized/approved LIB. The LIB must be in draft status.
- **Existing items are preserved**: Manual LIB items are NOT deleted when PPMP items are synced.
- **Updates are supported**: If you edit a PPMP and change the budget amount, the sync will update the corresponding LIB item.
- **Multiple PPMPs can sync to same LIB**: Different PPMPs can add items to the same draft LIB.

### 6. Sync Behavior

| PPMP Status | LIB Status | Sync Behavior |
|-------------|------------|---------------|
| Draft | Draft | ✅ Syncs successfully |
| Final | Draft | ✅ Syncs successfully |
| Draft | Approved | ❌ Error: Cannot modify approved LIB |
| Final | Approved | ❌ Error: Cannot modify approved LIB |
| Any | Not exists | ❌ Error: Create LIB first |

### 7. Troubleshooting

**Error: "No LIB found for this department and fiscal year"**
- Solution: Create a draft LIB first, then save the PPMP

**Error: "Cannot sync to LIB: LIB is already finalized/approved"**
- Solution: Either:
  - Create a new draft LIB for the same fiscal year, OR
  - Edit the existing LIB to change status back to draft (if allowed)

**Items not syncing**
- Check that items have LIB links (click "Link to LIB" button)
- Verify the LIB expense category exists in the LIB
- Check browser console for JavaScript errors

**Duplicate items in LIB**
- This shouldn't happen - the sync checks for existing items by PPMP reference
- If it does happen, manually delete duplicates from the LIB page

### 8. Technical Details

**Files involved**:
- `api/sync_ppmp_to_lib_helper.php` - Main sync function
- `api/create_ppmp.php` - Calls sync after creating PPMP
- `api/update_ppmp.php` - Calls sync after updating PPMP
- `api/get_lib_expense_categories.php` - Returns available expense categories
- `assets/js/ppmp.js` - Frontend JavaScript for LIB linking UI

**Database tables**:
- `ppmp` - Main PPMP records
- `ppmp_items` - PPMP items with LIB mapping fields
- `line_item_budgets` - Main LIB records
- `line_item_budget_items` - LIB items (where PPMP items are synced to)

**Sync logic**:
1. Get PPMP details and items with LIB mappings
2. Find existing draft LIB for same department and fiscal year
3. For each PPMP item with LIB mapping:
   - Check if already synced (by PPMP reference in particulars)
   - If exists: Update amount if changed
   - If not exists: Insert new LIB item with PPMP reference
4. Return sync results (items synced, items updated)

## Summary

The PPMP-to-LIB auto-sync feature streamlines budget planning by automatically transferring PPMP items to the LIB. This ensures consistency between procurement plans and budget allocations, reduces manual data entry, and prevents errors.
