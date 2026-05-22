# LIB Inline Sub-Categories Feature Guide

## Overview
This feature allows users to add sub-categories directly under "Other Maintenance and Operating Expenses" items in the LIB creation form. The parent item's amount is automatically calculated from the sum of all sub-category amounts.

## How It Works

### Step 1: Add Item with "Other Maintenance and Operating Expenses"
When you type or select "Other Maintenance and Operating Expenses" in the Particulars field, the system automatically detects it and shows a sub-category section.

### Step 2: Sub-Category Section Appears
Below the item row, you'll see:
```
┌─────────────────────────────────────────────────────┐
│ Sub-Categories                    [+ Add Sub-Category]│
├─────────────────────────────────────────────────────┤
│ No sub-categories yet. Click "Add Sub-Category"     │
│ to add one.                                          │
├─────────────────────────────────────────────────────┤
│ Total Amount: ₱0.00                                  │
└─────────────────────────────────────────────────────┘
```

### Step 3: Add Sub-Categories
Click "+ Add Sub-Category" button to add a new row:
```
┌──────────────────────────────────────┬──────────┬───┐
│ Sub-category name                    │ Amount   │ X │
├──────────────────────────────────────┼──────────┼───┤
│ [Office Supplies                  ]  │ [5000.00]│[X]│
│ [Janitorial Services              ]  │ [3000.00]│[X]│
│ [Repairs and Maintenance          ]  │ [7000.00]│[X]│
└──────────────────────────────────────┴──────────┴───┘
Total Amount: ₱15,000.00
```

### Step 4: Automatic Calculation
- As you enter amounts, the total updates automatically
- The parent item's amount field becomes read-only
- The parent amount equals the sum of all sub-category amounts

## User Interface

### Visual Example

**Before entering sub-categories:**
```
Particulars: [Other Maintenance and Operating Expenses]
Account Code: [5-02-99-990]
Amount: [0.00] (read-only)

┌─────────────────────────────────────────────────────┐
│ Sub-Categories                    [+ Add Sub-Category]│
├─────────────────────────────────────────────────────┤
│ No sub-categories yet.                               │
├─────────────────────────────────────────────────────┤
│ Total Amount: ₱0.00                                  │
└─────────────────────────────────────────────────────┘
```

**After adding sub-categories:**
```
Particulars: [Other Maintenance and Operating Expenses]
Account Code: [5-02-99-990]
Amount: [15000.00] (read-only, auto-calculated)

┌─────────────────────────────────────────────────────┐
│ Sub-Categories                    [+ Add Sub-Category]│
├─────────────────────────────────────────────────────┤
│ [Office Supplies              ] [5000.00] [X]        │
│ [Janitorial Services          ] [3000.00] [X]        │
│ [Repairs and Maintenance      ] [7000.00] [X]        │
├─────────────────────────────────────────────────────┤
│ Total Amount: ₱15,000.00                             │
└─────────────────────────────────────────────────────┘
```

## Features

### ✅ Automatic Detection
- System detects "Other Maintenance and Operating Expenses" automatically
- Sub-category section appears instantly
- No manual activation needed

### ✅ Easy Management
- Click "+ Add Sub-Category" to add new rows
- Click "X" to remove a sub-category
- Type name and amount directly in the table

### ✅ Real-Time Calculation
- Total updates as you type
- Parent amount updates automatically
- No manual calculation needed

### ✅ Data Validation
- Parent amount is read-only when sub-categories exist
- Cannot save without sub-category names
- Amounts must be positive numbers

### ✅ Flexible
- Add unlimited sub-categories
- Remove any sub-category anytime
- Change amounts and see instant updates

## Common Use Cases

### Example 1: Office Operations
```
Other Maintenance and Operating Expenses: ₱25,000
├─ Office Supplies: ₱5,000
├─ Janitorial Services: ₱3,000
├─ Repairs and Maintenance: ₱7,000
├─ Communication Expenses: ₱4,000
└─ Utilities: ₱6,000
```

### Example 2: Event Management
```
Other Maintenance and Operating Expenses: ₱30,000
├─ Venue Rental: ₱10,000
├─ Catering Services: ₱12,000
├─ Audio-Visual Equipment: ₱5,000
└─ Promotional Materials: ₱3,000
```

### Example 3: Research Activities
```
Other Maintenance and Operating Expenses: ₱40,000
├─ Laboratory Supplies: ₱15,000
├─ Field Work Expenses: ₱10,000
├─ Data Collection Tools: ₱8,000
└─ Publication Fees: ₱7,000
```

## Step-by-Step Tutorial

### Creating a LIB with Sub-Categories

1. **Start Creating a LIB**
   - Click "Create New LIB"
   - Fill in Fiscal Year and Fund Type
   - Add a category (e.g., "B. Maintenance & Other Operating Expenses")

2. **Add the Parent Item**
   - Click "Add Item" in the category
   - In Particulars field, type: "Other Maintenance and Operating Expenses"
   - Select or enter the UACS code
   - Notice the sub-category section appears below

3. **Add First Sub-Category**
   - Click "+ Add Sub-Category" button
   - Enter name: "Office Supplies"
   - Enter amount: 5000
   - See total update to ₱5,000.00

4. **Add More Sub-Categories**
   - Click "+ Add Sub-Category" again
   - Enter name: "Janitorial Services"
   - Enter amount: 3000
   - See total update to ₱8,000.00
   - Repeat for all sub-categories

5. **Review and Save**
   - Check that all sub-categories are correct
   - Verify the total amount
   - Click "Save Draft" or "Save LIB"

## Tips and Best Practices

### 📝 Naming Conventions
- Be specific: "Office Supplies - Paper" instead of just "Paper"
- Use consistent formatting
- Include details when needed

### 💰 Amount Entry
- Enter amounts without commas
- Use decimal points for cents (e.g., 5000.50)
- Double-check calculations

### 🗑️ Removing Sub-Categories
- Click the "X" button to remove
- Total updates automatically
- Cannot undo, so be careful

### ✏️ Editing Sub-Categories
- Click in the field to edit
- Changes save automatically
- Total updates in real-time

### 💾 Saving
- Sub-categories save with the LIB
- All data is preserved
- Can edit later if saved as draft

## Troubleshooting

### Issue: Sub-Category Section Not Showing
**Cause:** Particulars doesn't match "Other Maintenance and Operating Expenses"
**Solution:** 
- Ensure you type exactly: "Other Maintenance and Operating Expenses"
- Or select it from the UACS dropdown
- Check for typos

### Issue: Cannot Edit Parent Amount
**Cause:** Parent amount is read-only when sub-categories exist
**Solution:** 
- This is by design
- Edit sub-category amounts instead
- Total will update automatically

### Issue: Total Not Updating
**Cause:** JavaScript not loaded or browser issue
**Solution:**
- Refresh the page
- Check browser console for errors
- Try a different browser

### Issue: Sub-Category Removed by Accident
**Cause:** Clicked "X" button
**Solution:**
- Click "+ Add Sub-Category" to add it back
- Re-enter the name and amount
- No undo feature available

## Technical Details

### Files Involved
- `assets/js/lib_subcategories_inline.js` - Main JavaScript logic
- `pages/lib.php` - LIB page with inline functionality
- `database/lib_subcategories.sql` - Database schema

### Key Functions
- `handleParticularsChange()` - Detects "Other Maintenance" and shows sub-category section
- `addSubCategoryRow()` - Adds new sub-category row
- `removeSubCategory()` - Removes sub-category
- `updateSubCategoryTotal()` - Calculates and updates total

### Data Storage
- Sub-categories stored in `line_item_budget_items` table
- `parent_id` links sub-category to parent
- `is_parent` flag marks parent items
- `sub_category_name` stores sub-category name

## Benefits

### For Users
- ✅ Easy to use interface
- ✅ No manual calculations
- ✅ Clear breakdown of expenses
- ✅ Professional presentation

### For Administrators
- ✅ Better budget oversight
- ✅ Detailed expense tracking
- ✅ Improved auditing
- ✅ Data integrity

### For Auditors
- ✅ Clear paper trail
- ✅ Detailed breakdowns
- ✅ Easy verification
- ✅ Comprehensive reports

## Frequently Asked Questions

**Q: Can I add sub-categories to other expense types?**
A: Currently, only "Other Maintenance and Operating Expenses" supports sub-categories.

**Q: Is there a limit to how many sub-categories I can add?**
A: No limit, but keep it reasonable for readability.

**Q: Can I edit sub-categories after saving?**
A: Yes, if the LIB is saved as a draft. Final LIBs cannot be edited.

**Q: What happens if I change the Particulars field?**
A: The sub-category section will disappear if you change it to something else.

**Q: Can I have sub-categories within sub-categories?**
A: No, only one level of sub-categories is supported.

**Q: Will sub-categories appear in reports?**
A: Yes, they will appear in LIB printouts and PDF exports.

## Summary

The inline sub-category feature makes it easy to:
- Break down "Other Maintenance and Operating Expenses" into detailed categories
- Automatically calculate totals
- Maintain clear and professional budget records
- Improve transparency and accountability

Simply type "Other Maintenance and Operating Expenses" in the Particulars field, and the sub-category section appears automatically. Add your sub-categories, and the system handles the rest!
