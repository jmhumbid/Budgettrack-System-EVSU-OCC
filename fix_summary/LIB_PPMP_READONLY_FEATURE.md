# LIB PPMP-Linked Items Read-Only Feature

## Overview

PPMP-linked items in the LIB are now **read-only** and can only be edited or deleted through the PPMP. This prevents data inconsistencies and ensures that PPMP and LIB stay synchronized.

## Problem Solved

**Before**: Users could edit or delete LIB items that were created from PPMP, causing:
- Data inconsistencies between PPMP and LIB
- Budget amounts not matching
- Confusion about which system is the source of truth

**After**: PPMP-linked items are locked in the LIB with clear visual indicators, ensuring:
- PPMP is the single source of truth for linked items
- LIB automatically updates when PPMP changes
- Manual items can still be edited/deleted normally

## Features

### 1. **Source Tracking**
- New `source` field in `line_item_budget_items` table
- Values: `'manual'` or `'ppmp'`
- Automatically set when items are created

### 2. **Visual Indicators**
- **PPMP Badge**: Blue badge with link icon next to PPMP-linked items
- **Locked Status**: "Locked" badge replaces edit/delete buttons
- **Tooltip**: Hover over badges for explanation

### 3. **Read-Only Enforcement**
- Edit button: Hidden for PPMP-linked items
- Delete button: Hidden for PPMP-linked items
- Locked badge: Shows instead of action buttons

### 4. **Manual Items**
- Can still be edited and deleted normally
- No restrictions on manual items
- Clear distinction from PPMP items

## Visual Design

### PPMP-Linked Item Display:
```
┌─────────────────────────────────────────────────────────┐
│ Particulars                    │ UACS Code │ Amount     │
├─────────────────────────────────────────────────────────┤
│ Office Supplies Expenses [PPMP]│ 5020301000│ ₱3,000.00  │
│                                 │           │   [Locked] │
└─────────────────────────────────────────────────────────┘
```

### Manual Item Display:
```
┌─────────────────────────────────────────────────────────┐
│ Particulars                    │ UACS Code │ Amount     │
├─────────────────────────────────────────────────────────┤
│ Water Expenses                 │ 5020401000│ ₱5,000.00  │
│                                 │           │ [Edit][Del]│
└─────────────────────────────────────────────────────────┘
```

## Technical Implementation

### Database Changes

**Migration File**: `database/add_source_to_lib_items.sql`

```sql
ALTER TABLE `line_item_budget_items` 
ADD COLUMN `source` ENUM('manual', 'ppmp') NOT NULL DEFAULT 'manual' AFTER `amount`;

ALTER TABLE `line_item_budget_items` 
ADD INDEX `idx_source` (`source`);

UPDATE `line_item_budget_items` 
SET `source` = 'ppmp' 
WHERE `particulars` LIKE '%PPMP #%';
```

### Files Modified

1. **`database/add_source_to_lib_items.sql`** - Migration SQL
2. **`migrate_lib_source_field.php`** - Migration script
3. **`api/sync_ppmp_to_lib_helper.php`** - Set source='ppmp' when syncing
4. **`api/get_lib_details.php`** - Include source field and is_ppmp_linked flag
5. **`pages/lib.php`** - Display PPMP badge and lock buttons
6. **`api/add_lib_item.php`** - Already sets source='manual' ✓

### Key Changes

#### 1. Sync Helper (`api/sync_ppmp_to_lib_helper.php`)
```php
$insertQuery = "INSERT INTO line_item_budget_items 
               (lib_id, category, particulars, account_code, amount, source, sort_order) 
               VALUES (?, ?, ?, ?, ?, 'ppmp', ?)";
```

#### 2. Get LIB Details (`api/get_lib_details.php`)
```php
foreach ($items as &$item) {
    // Add is_ppmp_linked flag for easier frontend handling
    $item['is_ppmp_linked'] = ($item['source'] === 'ppmp');
    // ...
}
```

#### 3. LIB Display (`pages/lib.php`)
```javascript
// Show PPMP badge
${item.is_ppmp_linked ? `
    <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">
        PPMP
    </span>
` : ''}

// Hide edit/delete buttons for PPMP items
${canEdit && !item.is_ppmp_linked ? `
    <button onclick="showEditItemRow(...)">Edit</button>
    <button onclick="deleteLibItem(...)">Delete</button>
` : item.is_ppmp_linked ? `
    <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded text-xs">
        Locked
    </span>
` : ''}
```

## Installation Steps

### Step 1: Run Migration
```bash
# Open in browser
http://localhost/budgettrack/migrate_lib_source_field.php
```

### Step 2: Verify Migration
The script will:
1. Add `source` column to `line_item_budget_items`
2. Add index for performance
3. Update existing items based on PPMP references
4. Show summary of manual vs PPMP items

### Step 3: Test
1. Create a PPMP with items linked to LIB
2. View the LIB - items should show PPMP badge
3. Try to edit/delete - buttons should be replaced with "Locked"
4. Add a manual item - should have edit/delete buttons

## User Experience

### For Department Users:

**Creating PPMP**:
1. Create PPMP with items
2. Link items to LIB categories
3. Save PPMP
4. LIB automatically creates items with source='ppmp'

**Viewing LIB**:
1. Open LIB page
2. See PPMP-linked items with blue "PPMP" badge
3. See "Locked" status instead of edit/delete buttons
4. Tooltip explains: "This item is linked to a PPMP and can only be edited through the PPMP"

**Editing Budget**:
1. **PPMP items**: Edit through PPMP page
2. **Manual items**: Edit directly in LIB
3. Clear visual distinction between the two

### For Budget Office:

**Reviewing LIB**:
1. Can see which items are from PPMP
2. Can see which items are manually added
3. Better understanding of budget composition

## Benefits

### 1. **Data Integrity**
- Single source of truth for PPMP-linked items
- No conflicting edits between PPMP and LIB
- Automatic synchronization

### 2. **User Clarity**
- Clear visual indicators
- Obvious which items can be edited
- Helpful tooltips

### 3. **Workflow Enforcement**
- PPMP items must be edited in PPMP
- Manual items can be edited in LIB
- Prevents accidental modifications

### 4. **Audit Trail**
- Source field tracks item origin
- Easy to filter PPMP vs manual items
- Better reporting capabilities

## Edge Cases Handled

### 1. **Existing Data**
- Migration updates existing items based on PPMP references
- Items with "PPMP #" in particulars are marked as ppmp
- Others remain as manual

### 2. **Mixed LIB**
- LIB can have both PPMP and manual items
- Each item is independently locked/unlocked
- No conflicts

### 3. **PPMP Deletion**
- When PPMP is deleted, linked LIB items are removed
- Manual items remain untouched
- Clean separation

### 4. **PPMP Updates**
- When PPMP is updated, LIB items are updated
- Source remains 'ppmp'
- Amounts stay synchronized

## Future Enhancements (Optional)

1. **Bulk Operations**: Allow bulk editing of manual items only
2. **Filtering**: Add filter to show only PPMP or manual items
3. **Reports**: Generate reports showing PPMP vs manual budget breakdown
4. **Warnings**: Show warning when trying to finalize LIB with pending PPMP changes
5. **History**: Track when items switch from manual to PPMP

## Testing Checklist

- [x] Migration script runs successfully
- [x] Source column is added to database
- [x] Existing items are updated correctly
- [x] PPMP sync sets source='ppmp'
- [x] Manual add sets source='manual'
- [x] PPMP badge shows for linked items
- [x] Locked badge shows instead of edit/delete
- [x] Manual items still have edit/delete buttons
- [x] Tooltips provide helpful information
- [x] Print view doesn't show badges (no-print class)

## Status

✅ **IMPLEMENTED** - PPMP-linked items are now read-only in the LIB!

## Migration Required

⚠️ **IMPORTANT**: Run `migrate_lib_source_field.php` before using this feature!

---

**Questions?** Contact your system administrator for assistance.
