# LIB Sub-Categories Quick Start Guide

## Installation

### Step 1: Run Database Migration
```bash
php install_lib_subcategories.php
```

This will add the necessary columns to support sub-categories.

### Step 2: Verify Installation
Check that the following columns exist in `line_item_budget_items` table:
- `parent_id`
- `is_parent`
- `sub_category_name`

## Usage

### For Users

#### Adding "Other Maintenance and Operating Expenses" with Sub-Categories

1. **Create or Edit a LIB**
   - Go to LIB page
   - Click "Create New LIB" or edit an existing draft

2. **Add the Parent Item**
   - Add category "B. Maintenance & Other Operating Expenses"
   - Add item "Other Maintenance and Operating Expenses"
   - Select the appropriate UACS code

3. **Manage Sub-Categories**
   - After adding the item, you'll see a "Manage Sub-Categories" button
   - Click it to open the sub-category management modal

4. **Add Sub-Categories**
   - Enter sub-category name (e.g., "Office Supplies")
   - Enter amount (e.g., 5000.00)
   - Click "Add Sub-Category"
   - Repeat for all sub-categories

5. **View Auto-Calculated Total**
   - The parent item amount updates automatically
   - Shows sum of all sub-category amounts

### Example Breakdown

**Other Maintenance and Operating Expenses** (Total: ₱25,000.00)
- Office Supplies: ₱5,000.00
- Janitorial Services: ₱3,000.00
- Repairs and Maintenance: ₱7,000.00
- Communication Expenses: ₱4,000.00
- Utilities: ₱6,000.00

## Features

### ✅ Automatic Calculation
- Parent amount = Sum of all sub-category amounts
- Updates in real-time

### ✅ Easy Management
- Add, edit, delete sub-categories
- Simple modal interface

### ✅ Data Integrity
- Sub-categories linked to parent
- Deleting parent removes all sub-categories
- Cannot manually edit parent amount when sub-categories exist

### ✅ Reporting
- Sub-categories appear in LIB printouts
- Detailed breakdown for auditing

## API Endpoints

### Add Sub-Category
```javascript
POST /api/add_lib_subcategory.php
{
  "parent_id": 123,
  "sub_category_name": "Office Supplies",
  "amount": 5000.00
}
```

### Update Sub-Category
```javascript
POST /api/update_lib_subcategory.php
{
  "id": 456,
  "sub_category_name": "Office Supplies (Updated)",
  "amount": 6000.00
}
```

### Delete Sub-Category
```javascript
POST /api/delete_lib_subcategory.php
{
  "id": 456
}
```

### Get Sub-Categories
```javascript
GET /api/get_lib_subcategories.php?parent_id=123
```

## Troubleshooting

### Sub-Category Button Not Showing
- Ensure item particulars contains "Other Maintenance" and "Operating Expenses"
- Check that JavaScript file is loaded: `lib_subcategories.js`

### Parent Amount Not Updating
- Check browser console for errors
- Verify API endpoints are accessible
- Ensure database columns exist

### Cannot Add Sub-Category
- Verify parent item exists
- Check that amount is greater than 0
- Ensure sub-category name is not empty

## Best Practices

1. **Use Descriptive Names**
   - Be specific: "Office Supplies - Paper" instead of just "Paper"

2. **Group Related Expenses**
   - Keep similar items together
   - Use consistent naming conventions

3. **Regular Review**
   - Review sub-categories periodically
   - Remove unused categories
   - Update amounts as needed

4. **Documentation**
   - Keep notes on what each sub-category covers
   - Document any special allocations

## Support

For issues or questions:
1. Check the main documentation: `LIB_SUBCATEGORIES_FEATURE.md`
2. Review API responses in browser console
3. Check database logs for errors
4. Contact system administrator

## Next Steps

After installation:
1. Test with a draft LIB
2. Add sample sub-categories
3. Verify calculations
4. Print/export to verify formatting
5. Train users on the feature
