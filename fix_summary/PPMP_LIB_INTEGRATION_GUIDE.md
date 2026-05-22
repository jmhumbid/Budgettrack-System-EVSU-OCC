# PPMP-LIB Integration Implementation Guide

## Overview

This implementation modernizes the PPMP (Project Procurement Management Plan) creation flow and integrates it seamlessly with the LIB (Line Item Budget) system. When users create PPMP items, they can now link each item to specific LIB expense categories, and when the PPMP is finalized, the budget allocations automatically sync to the LIB.

## Key Features

### 1. **Modern PPMP Creation Flow**
- Card-based, intuitive UI instead of paper-based forms
- Streamlined item entry with clear visual feedback
- Real-time validation and error handling

### 2. **LIB Expense Linking**
- Each PPMP item can be linked to a specific LIB expense category
- Searchable dropdown with all UACS expense categories
- Visual confirmation of linked expenses
- Ability to clear and re-link expenses

### 3. **Automatic LIB Synchronization**
- When PPMP is marked as FINAL, items automatically sync to LIB
- Creates new LIB if none exists for the department/fiscal year
- Updates existing LIB items or creates new ones
- Maintains mapping between PPMP items and LIB items

## Database Changes

### New Columns in `ppmp_items` Table
```sql
- lib_category VARCHAR(255) - LIB category (A/B/C)
- lib_particulars VARCHAR(500) - LIB expense description
- lib_account_code VARCHAR(50) - UACS account code
- lib_synced TINYINT(1) - Whether synced to LIB
```

### New Table: `ppmp_lib_mappings`
Tracks the relationship between PPMP items and LIB items:
```sql
- ppmp_id INT - Reference to PPMP
- ppmp_item_id INT - Reference to PPMP item
- lib_id INT - Reference to LIB
- lib_item_id INT - Reference to LIB item
- fiscal_year VARCHAR(20)
- department_id INT
```

## Installation

Run the installation script:
```bash
php install_ppmp_lib_integration.php
```

This will:
1. Add new columns to ppmp_items table
2. Create necessary indexes
3. Create ppmp_lib_mappings table

## User Workflow

### Creating a PPMP with LIB Integration

1. **Start Creating PPMP**
   - Click "Create New PPMP"
   - Fill in Fiscal Year and PPMP Number
   - Optionally check "Mark as Final" (unchecked = draft)

2. **Add Procurement Items**
   - Click "Add Item" to add a new row
   - Fill in all procurement details (description, type, quantity, etc.)
   - Enter the estimated budget

3. **Link to LIB Expense**
   - Click the "Link to LIB" button in the LIB Expense column
   - A modal opens showing all available LIB expense categories
   - Categories are organized by:
     - **A. PERSONAL SERVICES** (blue)
     - **B. Maintenance & Other Operating Expenses** (green)
     - **C. Capital Outlay** (purple)
   - Use the search box to quickly find expenses
   - Click on an expense to link it
   - The selected expense appears in the table with its UACS code

4. **Save PPMP**
   - If "Mark as Final" is checked:
     - PPMP is saved as FINAL
     - Items automatically sync to LIB
     - LIB is created/updated with budget allocations
     - Notifications sent to Budget Office
   - If unchecked:
     - PPMP is saved as DRAFT
     - Can be edited later
     - No LIB sync until finalized

### Viewing LIB After PPMP Sync

1. Navigate to LIB page
2. The LIB will show:
   - All synced PPMP items under their respective categories
   - UACS account codes
   - Total amounts from PPMP budgets
   - Source marked as "ppmp"

## API Endpoints

### 1. `api/get_lib_expense_categories.php`
**Purpose:** Retrieve all available LIB expense categories with UACS codes

**Parameters:**
- `department_id` (required)
- `fiscal_year` (required)

**Response:**
```json
{
  "success": true,
  "categories": {
    "A. PERSONAL SERVICES": [
      {"code": "5010101000", "name": "Salaries and Wages - Regular"},
      ...
    ],
    "B. Maintenance & Other Operating Expenses": [...],
    "C. Capital Outlay": [...]
  }
}
```

### 2. `api/sync_ppmp_to_lib.php`
**Purpose:** Sync PPMP items to LIB

**Parameters:**
```json
{
  "ppmp_id": 123
}
```

**Response:**
```json
{
  "success": true,
  "message": "Successfully synced 5 items to LIB",
  "lib_id": 45,
  "synced_count": 5,
  "errors": []
}
```

**Process:**
1. Validates PPMP is FINAL
2. Gets all unsynced items with LIB mappings
3. Creates or finds draft LIB for department/fiscal year
4. For each item:
   - Checks if LIB item already exists
   - Updates existing or creates new LIB item
   - Marks PPMP item as synced
   - Creates mapping record
5. Updates LIB total amount

### 3. Updated `api/create_ppmp.php`
Now accepts additional fields:
- `lib_category[]` - Array of LIB categories
- `lib_particulars[]` - Array of LIB expense descriptions
- `lib_account_code[]` - Array of UACS codes

When PPMP is marked as FINAL, automatically calls sync_ppmp_to_lib.php

## JavaScript Functions

### Core Functions

#### `loadLibExpenseCategories()`
Loads and caches LIB expense categories from API

#### `showLibExpenseSelector(itemIndex)`
Opens modal to select LIB expense for a PPMP item

#### `renderLibExpenseCategories(categories)`
Renders expense categories in the selector modal with color coding

#### `selectLibExpense(category, particulars, accountCode)`
Links selected expense to PPMP item and updates UI

#### `clearLibMapping(itemIndex)`
Removes LIB mapping from a PPMP item

#### `searchLibExpenses()`
Filters expense categories based on search input

#### `syncPPMPToLIB(ppmpId)`
Manually triggers sync of PPMP to LIB (called automatically on finalize)

#### `showToast(message, type)`
Shows temporary notification messages

## UI Components

### LIB Expense Selector Modal
- **Header:** Shows item name and budget
- **Search Bar:** Quick filter for expenses
- **Category Sections:** Color-coded by category type
- **Expense Buttons:** Click to select, shows name and UACS code

### PPMP Table Updates
- New column: "LIB Expense"
- Shows "Link to LIB" button for unmapped items
- Shows linked expense details for mapped items
- Includes "Clear" button to remove mapping

## Benefits

### For Users
1. **Streamlined Workflow:** Link PPMP items to LIB in one step
2. **No Manual Entry:** Budget allocations automatically appear in LIB
3. **Accuracy:** Eliminates manual transcription errors
4. **Transparency:** Clear visibility of budget allocations
5. **Flexibility:** Can link multiple PPMP items to same LIB expense

### For System
1. **Data Integrity:** Maintains referential integrity between PPMP and LIB
2. **Audit Trail:** Complete mapping history in ppmp_lib_mappings table
3. **Automation:** Reduces manual data entry and processing time
4. **Scalability:** Handles multiple PPMPs syncing to same LIB

## Error Handling

### Common Scenarios

1. **PPMP Not Final**
   - Sync fails with message: "Only final PPMPs can be synced to LIB"
   - Solution: Mark PPMP as final before syncing

2. **No LIB Mappings**
   - Sync fails with message: "No items to sync"
   - Solution: Link at least one PPMP item to a LIB expense

3. **Duplicate Sync**
   - Items already synced are skipped
   - Only unsynced items are processed

4. **LIB Creation Failure**
   - Transaction rolls back
   - Error logged and returned to user
   - PPMP remains in database

## Testing Checklist

- [ ] Install database changes successfully
- [ ] Create new PPMP with LIB mappings
- [ ] Link PPMP items to different LIB categories
- [ ] Search for LIB expenses
- [ ] Clear LIB mappings
- [ ] Save PPMP as draft (no sync)
- [ ] Mark PPMP as final (triggers sync)
- [ ] Verify LIB items created correctly
- [ ] Check ppmp_lib_mappings table
- [ ] Create second PPMP, verify LIB updates
- [ ] Test with existing LIB
- [ ] Test with no existing LIB
- [ ] Verify notifications sent
- [ ] Test error scenarios

## Troubleshooting

### LIB Items Not Appearing

**Check:**
1. PPMP is marked as FINAL
2. PPMP items have LIB mappings (lib_category, lib_particulars, lib_account_code not null)
3. lib_synced flag is set to 1
4. ppmp_lib_mappings table has records
5. LIB exists for department/fiscal year

**Solution:**
- Run sync manually: Call `sync_ppmp_to_lib.php` with ppmp_id
- Check error logs for sync failures

### Duplicate LIB Items

**Cause:** Multiple PPMP items linked to same LIB expense

**Expected Behavior:** Amounts are added together in single LIB item

**Verify:** Check lib_items.amount matches sum of linked PPMP items

### Sync Fails Silently

**Check:**
1. PHP error logs
2. Browser console for JavaScript errors
3. Network tab for API call failures
4. Database transaction logs

## Future Enhancements

1. **Bulk Linking:** Link multiple PPMP items at once
2. **Smart Suggestions:** AI-powered expense category suggestions
3. **Budget Validation:** Warn if PPMP budget exceeds LIB allocation
4. **Sync History:** View sync history and rollback capability
5. **Partial Sync:** Sync individual items instead of all at once
6. **LIB Preview:** Preview LIB changes before finalizing PPMP

## Support

For issues or questions:
1. Check error logs in browser console and PHP error log
2. Verify database schema matches expected structure
3. Test API endpoints directly using tools like Postman
4. Review ppmp_lib_mappings table for mapping issues

## Conclusion

This integration provides a seamless connection between PPMP and LIB, automating budget allocation and reducing manual data entry. The modern UI makes it easy for users to link procurement items to expense categories, while the automatic synchronization ensures data consistency across the system.
