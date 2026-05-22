# LIB Sub-Categories Feature

## Overview
This feature allows Line Item Budget (LIB) items, specifically "Other Maintenance and Operating Expenses", to have sub-categories. The parent item's amount is automatically calculated as the sum of all its sub-category amounts.

## Database Changes

### New Columns in `line_item_budget_items`
- `parent_id` (int): References the parent item ID (NULL for top-level items)
- `is_parent` (tinyint): Flag indicating if this item has sub-categories (1 = yes, 0 = no)
- `sub_category_name` (varchar): Name of the sub-category (only for child items)

### Installation
Run the installation script:
```bash
php install_lib_subcategories.php
```

## API Endpoints

### 1. Add Sub-Category
**Endpoint:** `api/add_lib_subcategory.php`
**Method:** POST
**Payload:**
```json
{
  "parent_id": 123,
  "sub_category_name": "Office Supplies",
  "amount": 5000.00
}
```
**Response:**
```json
{
  "success": true,
  "message": "Sub-category added successfully",
  "sub_category_id": 456,
  "parent_new_total": 15000.00
}
```

### 2. Update Sub-Category
**Endpoint:** `api/update_lib_subcategory.php`
**Method:** POST
**Payload:**
```json
{
  "id": 456,
  "sub_category_name": "Office Supplies (Updated)",
  "amount": 6000.00
}
```

### 3. Delete Sub-Category
**Endpoint:** `api/delete_lib_subcategory.php`
**Method:** POST
**Payload:**
```json
{
  "id": 456
}
```

### 4. Get Sub-Categories
**Endpoint:** `api/get_lib_subcategories.php?parent_id=123`
**Method:** GET
**Response:**
```json
{
  "success": true,
  "sub_categories": [
    {
      "id": 456,
      "sub_category_name": "Office Supplies",
      "amount": "5000.00",
      "created_at": "2026-04-13 10:30:00"
    }
  ]
}
```

## Frontend Implementation

### Key Features
1. **Detect "Other Maintenance and Operating Expenses"**: When this item is selected, show sub-category management UI
2. **Sub-Category Table**: Display all sub-categories with edit/delete actions
3. **Add Sub-Category Form**: Allow users to add new sub-categories with name and amount
4. **Auto-Calculate Parent Total**: Parent amount updates automatically when sub-categories change
5. **Read-Only Parent Amount**: Parent item amount cannot be edited directly when it has sub-categories

### UI Flow
1. User adds "Other Maintenance and Operating Expenses" item
2. System detects this special item and shows "Manage Sub-Categories" button
3. User clicks button to open sub-category modal
4. User can add/edit/delete sub-categories
5. Parent item amount updates automatically

## Usage Example

### Scenario
Department needs to budget for "Other Maintenance and Operating Expenses" with breakdown:
- Office Supplies: ₱5,000.00
- Janitorial Services: ₱3,000.00
- Repairs and Maintenance: ₱7,000.00
- **Total: ₱15,000.00** (auto-calculated)

### Steps
1. Add "Other Maintenance and Operating Expenses" to LIB
2. Click "Manage Sub-Categories"
3. Add each sub-category with its amount
4. Parent item shows total: ₱15,000.00

## Benefits
- **Detailed Breakdown**: Track specific expense types within broader categories
- **Automatic Calculation**: No manual math errors
- **Flexibility**: Add/remove sub-categories as needed
- **Audit Trail**: Each sub-category is tracked separately
- **Reporting**: Can generate detailed reports showing sub-category breakdowns

## Technical Notes
- Parent items with sub-categories have `is_parent = 1`
- Sub-categories reference parent via `parent_id`
- Deleting a parent item cascades to delete all sub-categories
- Parent amount is recalculated on every sub-category add/update/delete operation
- Sub-categories inherit category and account_code from parent

## Future Enhancements
- Allow sub-categories for other expense types
- Export sub-category breakdown to Excel/PDF
- Sub-category templates for common expense types
- Bulk import sub-categories from CSV
