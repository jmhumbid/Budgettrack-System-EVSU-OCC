# LIB Sub-Categories Save Fix

## Issue
Sub-categories were not being saved to the database, so the dropdown didn't appear after saving.

## Solution Implemented

### 1. Updated `api/add_lib_item.php`

**Added sub-categories parameter:**
```php
$subCategories = isset($_POST['sub_categories']) ? json_decode($_POST['sub_categories'], true) : [];
```

**Added is_parent flag:**
```php
$isParent = !empty($subCategories) ? 1 : 0;
```

**Added sub-category insertion:**
```php
if (!empty($subCategories)) {
    $subStmt = $db->prepare("
        INSERT INTO line_item_budget_items 
        (lib_id, parent_id, category, particulars, sub_category_name, account_code, amount, is_parent, source) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'manual')
    ");
    
    foreach ($subCategories as $sub) {
        if (!empty($sub['name']) && isset($sub['amount']) && $sub['amount'] > 0) {
            $subStmt->execute([
                $libId, $itemId, $category, $particulars,
                $sub['name'], $accountCode, $sub['amount']
            ]);
        }
    }
}
```

### 2. Updated `pages/lib.php` - `saveInlineItem()` function

**Added sub-category retrieval:**
```javascript
const subCategories = getInlineSubCategories(categoryKey);
```

**Added validation:**
```javascript
if (subCategories.length > 0) {
    const hasInvalidSub = subCategories.some(sub => !sub.name || sub.amount <= 0);
    if (hasInvalidSub) {
        alert('Please ensure all sub-categories have names and valid amounts');
        return;
    }
    
    const subTotal = subCategories.reduce((sum, sub) => sum + sub.amount, 0);
    if (Math.abs(amount - subTotal) > 0.01) {
        alert(`Amount mismatch`);
        return;
    }
}
```

**Added sub-categories to form data:**
```javascript
if (subCategories.length > 0) {
    formData.append('sub_categories', JSON.stringify(subCategories));
}
```

## Database Requirements

Make sure you've run the installation script:
```bash
php install_lib_subcategories.php
```

This creates the necessary columns:
- `parent_id` - Links sub-category to parent
- `is_parent` - Flags parent items (1 = has sub-categories)
- `sub_category_name` - Name of the sub-category

## Testing Steps

### 1. Create New LIB
1. Go to LIB page
2. Click "Create New LIB"
3. Fill in Fiscal Year and Fund Type
4. Add category "B. Maintenance & Other Operating Expenses"

### 2. Add Item with Sub-Categories
1. Click "Add Item"
2. Type "other" in Particulars field
3. **Click** "Other Maintenance and Operating Expenses" from dropdown
4. Sub-category section should appear
5. Click "+ Add Sub-Category"
6. Enter:
   - Name: "Office Supplies"
   - Amount: 5000
7. Click "+ Add Sub-Category" again
8. Enter:
   - Name: "Janitorial Services"
   - Amount: 3000
9. Verify total shows ₱8,000.00
10. Click "Save"

### 3. Verify Display
After saving, you should see:
```
▼ Other Maintenance and Operating Expenses    ₱8,000.00
```

Click the arrow (▼) to expand and see:
```
▼ Other Maintenance and Operating Expenses    ₱8,000.00
  ┌─────────────────────────┬────────────┐
  │ Sub-Category Name       │ Amount     │
  ├─────────────────────────┼────────────┤
  │ Office Supplies         │ ₱5,000.00  │
  │ Janitorial Services     │ ₱3,000.00  │
  ├─────────────────────────┼────────────┤
  │ Total:                  │ ₱8,000.00  │
  └─────────────────────────┴────────────┘
```

## Debugging

### Check Browser Console
Open browser console (F12) and look for:
```
Saving item: {
  lib_id: 1,
  category: "B. Maintenance & Other Operating Expenses",
  particulars: "Other Maintenance and Operating Expenses",
  account_code: "5029999099",
  amount: 8000,
  sub_categories: [
    {name: "Office Supplies", amount: 5000},
    {name: "Janitorial Services", amount: 3000}
  ]
}
```

### Check PHP Error Log
Look for:
```
add_lib_item.php - Item added successfully: item_id=123
add_lib_item.php - Sub-category added: Office Supplies = 5000
add_lib_item.php - Sub-category added: Janitorial Services = 3000
```

### Check Database
```sql
-- Check parent item
SELECT * FROM line_item_budget_items WHERE id = 123;
-- Should show: is_parent = 1

-- Check sub-categories
SELECT * FROM line_item_budget_items WHERE parent_id = 123;
-- Should show 2 rows with sub_category_name filled
```

## Common Issues

### Issue 1: No dropdown arrow appears
**Cause:** Sub-categories not saved to database
**Solution:** Check PHP error log, verify database columns exist

### Issue 2: Sub-categories not saving
**Cause:** Database columns missing
**Solution:** Run `php install_lib_subcategories.php`

### Issue 3: Amount mismatch error
**Cause:** Parent amount doesn't match sub-category total
**Solution:** Verify sub-category amounts add up correctly

### Issue 4: JavaScript error
**Cause:** `getInlineSubCategories` function not found
**Solution:** Verify `lib_subcategories_inline.js` is loaded

## Files Modified

1. **api/add_lib_item.php** - Added sub-category saving logic
2. **pages/lib.php** - Updated `saveInlineItem()` to send sub-categories
3. **api/get_lib_details.php** - Already updated to load sub-categories
4. **pages/lib.php** - Updated `generateLIBView()` to display dropdown

## Success Criteria

✅ Sub-category section appears when clicking "Other Maintenance and Operating Expenses"
✅ Can add multiple sub-categories
✅ Total calculates automatically
✅ Saves to database successfully
✅ Dropdown arrow appears after save
✅ Click arrow to expand/collapse sub-categories
✅ Sub-categories display in table format
✅ Total shows at bottom

---

**Status:** ✅ Fixed and Ready
**Date:** April 13, 2026
