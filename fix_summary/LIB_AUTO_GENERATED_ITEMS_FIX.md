# LIB Auto-Generated Items Fix

## Issue
When generating and saving a LIB, all items showed edit/delete buttons when they shouldn't. Auto-generated items should be read-only.

## Root Cause
The `INSERT` statement in `api/create_lib.php` was missing the `source` field, so all items were defaulting to `'manual'` instead of `'auto'`.

## Fix Applied

### 1. Updated `api/create_lib.php`
**Before:**
```sql
INSERT INTO line_item_budget_items 
(lib_id, category, particulars, account_code, amount, sort_order) 
VALUES (?, ?, ?, ?, ?, ?)
```

**After:**
```sql
INSERT INTO line_item_budget_items 
(lib_id, category, particulars, account_code, amount, source, sort_order) 
VALUES (?, ?, ?, ?, ?, 'auto', ?)
```

Now all items created via "Generate LIB" are marked as `source = 'auto'`.

### 2. Fixed Existing Items
Ran `fix_recent_lib_items_source.php` to update items 542-547 from 'manual' to 'auto'.

## How It Works Now

### Auto-Generated Items (source = 'auto')
```
When you click "Auto-Generate from Allocations" and save:
  ↓
Items are created with source = 'auto'
  ↓
Display shows NO edit/delete buttons
  ↓
Items are read-only ✓
```

### Manually Added Items (source = 'manual')
```
When you click "Add Item" and save:
  ↓
Item is created with source = 'manual'
  ↓
Display shows edit/delete buttons
  ↓
Item can be edited/deleted ✓
```

## Visual Comparison

### Auto-Generated Items (Read-Only)
```
┌─────────────────────────────────────────────┐
│ Honoraria - Overload  5010210001  ₱30,000  │ ← No buttons
│ Honoraria - Part-time 5010210001  ₱50,000  │ ← No buttons
│ Water Expenses        5020401000  ₱10,000  │ ← No buttons
│ Labor and Wages       5021601000  ₱100,000 │ ← No buttons
│ Security Services     5021602000  ₱50,000  │ ← No buttons
│ Electricity Expenses  5020402000  ₱20,000  │ ← No buttons
└─────────────────────────────────────────────┘
```

### Manually Added Items (Editable)
```
┌─────────────────────────────────────────────────────┐
│ Custom Item  5010210002  ₱15,000  [✏️ Edit] [🗑️ Delete] │
└─────────────────────────────────────────────────────┘
```

## Testing

1. **Generate a new LIB**:
   - Click "Auto-Generate from Allocations"
   - Select year and generate
   - Click "Save LIB"
   - ✅ All items should have NO edit/delete buttons

2. **Add a manual item**:
   - Click "Add Item"
   - Fill in details and save
   - ✅ Your item should have edit/delete buttons

3. **Verify source values**:
   ```bash
   php check_lib_items_source.php
   ```
   - Auto-generated items: `source = 'auto'`
   - Manual items: `source = 'manual'`

## Files Modified

1. ✅ `api/create_lib.php` - Added `source` field to INSERT statement
2. ✅ `fix_recent_lib_items_source.php` - Fixed existing items
3. ✅ `check_lib_items_source.php` - Verification script

## Database State

### Before Fix
```
ID: 547 | Electricity Expenses | Source: manual ❌
ID: 546 | Security Services    | Source: manual ❌
ID: 545 | Labor and Wages      | Source: manual ❌
```

### After Fix
```
ID: 547 | Electricity Expenses | Source: auto ✓
ID: 546 | Security Services    | Source: auto ✓
ID: 545 | Labor and Wages      | Source: auto ✓
```

## Status: ✅ FIXED

- Auto-generated items are now correctly marked as `source = 'auto'`
- They show NO edit/delete buttons
- Only manually added items can be edited/deleted
- Future LIB generations will work correctly

## Next Steps

1. **Refresh your browser** (Ctrl+F5)
2. **Check existing LIB** - Auto-generated items should have no buttons
3. **Generate new LIB** - Items will be correctly marked as 'auto'
4. **Add manual item** - Will have edit/delete buttons

Everything is now working as expected! 🎉
