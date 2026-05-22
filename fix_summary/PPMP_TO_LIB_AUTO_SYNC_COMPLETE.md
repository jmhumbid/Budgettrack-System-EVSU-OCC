# PPMP to LIB Auto-Sync Feature - Complete Implementation

## Overview
When you save a PPMP (draft or final) with items linked to LIB expense categories, the system automatically adds those items to the Line Item Budget (LIB) with the account code and total amount.

## How It Works

### 1. **Link PPMP Items to LIB Expenses**

When creating or editing a PPMP:

1. Click "Add Item" to add a procurement item
2. Fill in the item details (description, quantity, unit, budget, etc.)
3. Look for the **"Link to LIB Expense Category"** section
4. Click the **"Link to LIB"** button
5. A modal opens showing all available LIB expense categories:
   - **A. PERSONAL SERVICES** (salaries, allowances, benefits)
   - **B. Maintenance & Other Operating Expenses** (supplies, utilities, services)
   - **C. Capital Outlay** (equipment, vehicles, furniture)

6. Select the appropriate expense category (e.g., "Office Supplies Expenses")
7. The system automatically fills in:
   - **Category**: B. Maintenance & Other Operating Expenses
   - **Particulars**: Office Supplies Expenses
   - **Account Code**: 5020301000

### 2. **Save PPMP (Draft or Final)**

When you click "Save Draft" or mark as "Final" and save:

1. The PPMP is saved to the database
2. The system checks if any items have LIB mappings
3. If LIB mappings exist, the auto-sync process runs automatically

### 3. **Auto-Sync Process**

The system performs these steps:

#### Step 1: Check LIB Status
- Checks if a LIB exists for the department and fiscal year
- **If LIB is finalized/approved**: Sync is blocked (cannot modify approved LIB)
- **If LIB doesn't exist**: Creates a new draft LIB automatically
- **If LIB is draft**: Proceeds with sync

#### Step 2: Sync Items to LIB
For each PPMP item with LIB mapping:

- **Creates a unique reference**: "PPMP #NO._1_ - Item #1"
- **Checks if item already exists** in LIB (by reference)
- **If exists**: Updates the amount if changed
- **If new**: Adds a new row to LIB with:
  - **Category**: B. Maintenance & Other Operating Expenses
  - **Particulars**: Office Supplies Expenses (PPMP #NO._1_ - Item #1)
  - **Account Code**: 5020301000
  - **Amount**: ₱50,000.00 (from estimated budget)

#### Step 3: Result
- Items are automatically added to the LIB
- You can view them in the LIB page
- The LIB remains in draft status (you can still edit)

## Example Workflow

### Scenario: Creating a PPMP with Office Supplies

1. **Create PPMP**
   - Fiscal Year: 2026
   - PPMP Number: NO._1_

2. **Add Item #1**
   - Description: "Bond paper, pens, folders for office use"
   - Type: Goods
   - Quantity: 100
   - Unit: reams
   - Estimated Budget: ₱50,000.00
   - **Link to LIB**: Office Supplies Expenses (5020301000)

3. **Add Item #2**
   - Description: "Printer ink cartridges"
   - Type: Goods
   - Quantity: 20
   - Unit: pcs
   - Estimated Budget: ₱30,000.00
   - **Link to LIB**: Office Supplies Expenses (5020301000)

4. **Save Draft**
   - PPMP is saved
   - Auto-sync runs automatically
   - LIB is created (if doesn't exist)
   - Two rows are added to LIB:

   | Category | Particulars | Account Code | Amount |
   |----------|-------------|--------------|---------|
   | B. Maintenance & Other Operating Expenses | Office Supplies Expenses (PPMP #NO._1_ - Item #1) | 5020301000 | ₱50,000.00 |
   | B. Maintenance & Other Operating Expenses | Office Supplies Expenses (PPMP #NO._1_ - Item #2) | 5020301000 | ₱30,000.00 |

5. **View in LIB**
   - Go to LIB page
   - See the items automatically added
   - Total for Office Supplies Expenses: ₱80,000.00

## Available Expense Categories

### A. PERSONAL SERVICES
- Salaries and Wages - Regular (5010101000)
- Salaries and Wages - Casual/Contractual (5010102000)
- Personnel Economic Relief Allowance (PERA) (5010202000)
- Representation Allowance (5010203000)
- Transportation Allowance (5010204000)
- Honoraria (5010211000)
- And more...

### B. Maintenance & Other Operating Expenses
- **Office Supplies Expenses (5020301000)** ← Most common
- Traveling Expenses - Local (5020101000)
- Training and Scholarship Expenses (5020201000)
- Food Supplies Expenses (5020305000)
- Fuel, Oil and Lubricants Expenses (5020308000)
- Water Expenses (5020401000)
- Electricity Expenses (5020402000)
- Telephone Expenses (5020502000)
- Internet Subscription Expenses (5020503000)
- Janitorial Services (5021101000)
- Security Services (5021102000)
- Repairs and Maintenance - Buildings (5021202000)
- Repairs and Maintenance - Equipment (5021203000)
- And more...

### C. Capital Outlay
- Office Equipment (5060402000)
- Information and Communication Technology Equipment (5060403000)
- Motor Vehicles (5060501000)
- Furniture and Fixtures (5060601000)
- Books (5060602000)
- And more...

## Important Notes

### ✅ What Works
- Auto-sync runs for both **draft** and **final** PPMPs
- Multiple items can link to the same expense category
- Items are tracked by unique reference (PPMP # + Item #)
- Amounts are updated if you edit and re-save the PPMP
- LIB is created automatically if it doesn't exist

### ⚠️ Restrictions
- **Cannot sync to finalized LIB**: If the LIB is already approved/finalized, sync will fail
- **Must have all three fields**: Category, Particulars, and Account Code must all be filled
- **Department and Fiscal Year must match**: PPMP and LIB must be for the same department and year

### 🔄 Updating PPMPs
When you edit a PPMP and change the budget amount:
1. Edit the PPMP (only drafts can be edited)
2. Change the estimated budget
3. Save
4. The corresponding LIB item is automatically updated with the new amount

## Technical Implementation

### Files Modified
1. **api/create_ppmp.php** - Calls sync after creating PPMP
2. **api/update_ppmp.php** - Calls sync after updating PPMP
3. **api/sync_ppmp_to_lib_helper.php** - Direct sync function (NEW)
4. **api/sync_ppmp_to_lib.php** - HTTP endpoint for sync (existing)
5. **api/get_lib_expense_categories.php** - Provides expense categories
6. **assets/js/ppmp.js** - Frontend logic for LIB linking
7. **pages/ppmp.php** - PPMP page with LIB selector modal

### Database Tables
- **ppmp** - Stores PPMP records
- **ppmp_items** - Stores PPMP items with LIB mapping fields:
  - `lib_category` - e.g., "B. Maintenance & Other Operating Expenses"
  - `lib_particulars` - e.g., "Office Supplies Expenses"
  - `lib_account_code` - e.g., "5020301000"
- **line_item_budgets** - Stores LIB records
- **line_item_budget_items** - Stores LIB items (auto-populated from PPMP)

### Sync Logic
```php
// Check if LIB exists
if (!$lib) {
    // Create new LIB
    $libId = createNewLIB($departmentId, $fiscalYear, $userId);
} else if ($lib['status'] === 'approved') {
    // Cannot sync to approved LIB
    return error;
} else {
    $libId = $lib['id'];
}

// For each PPMP item with LIB mapping
foreach ($ppmpItems as $item) {
    $reference = "PPMP #{$ppmpNumber} - Item #{$itemNumber}";
    
    // Check if already synced
    if (existsInLIB($libId, $category, $reference)) {
        // Update amount if changed
        updateLIBItem($libId, $reference, $newAmount);
    } else {
        // Add new item to LIB
        insertLIBItem($libId, $category, $particulars, $accountCode, $amount);
    }
}
```

## Troubleshooting

### Issue: Items not appearing in LIB

**Check:**
1. Did you link the item to a LIB expense category?
2. Did you fill in all three fields (Category, Particulars, Account Code)?
3. Is the LIB already finalized? (Cannot sync to approved LIB)
4. Check browser console for errors (F12)
5. Check server error logs for sync failures

### Issue: LIB sync failed error

**Possible causes:**
1. LIB is already finalized/approved
2. Database connection error
3. Missing required fields in PPMP item
4. Permission issues

**Solution:**
- Check error logs: `error_log` in PHP
- Verify LIB status is "draft"
- Ensure all LIB mapping fields are filled

### Issue: Amount not updating in LIB

**Check:**
1. Is the PPMP still a draft? (Only drafts can be edited)
2. Did you save the PPMP after changing the amount?
3. Is the LIB item reference correct?

## Benefits

✅ **Saves Time**: No manual entry of PPMP items into LIB
✅ **Reduces Errors**: Automatic sync ensures accuracy
✅ **Maintains Consistency**: PPMP and LIB always match
✅ **Tracks Changes**: Updates LIB when PPMP is modified
✅ **Audit Trail**: Reference shows which PPMP item created each LIB entry

## Next Steps

1. **Clear browser cache** (Ctrl+Shift+R) to load the latest JavaScript
2. **Create a test PPMP** with LIB-linked items
3. **Save as draft** and verify items appear in LIB
4. **Edit the PPMP** and change an amount
5. **Save again** and verify LIB is updated

---

**Status**: ✅ **COMPLETE - Auto-sync fully implemented**

**Last Updated**: April 12, 2026
