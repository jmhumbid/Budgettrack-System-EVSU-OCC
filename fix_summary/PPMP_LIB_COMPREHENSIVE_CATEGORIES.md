# PPMP-LIB Comprehensive Categories Feature

## What Changed

### Problem
Previously, the LIB expense selector only showed categories that already existed in the department's LIB. If you wanted to link a PPMP item to "Office Supplies Expenses" but it wasn't in your LIB yet, you couldn't select it.

### Solution
Now the system shows **ALL standard government expense categories** (based on UACS codes), regardless of whether they exist in your LIB or not. When you select a category and save the PPMP, the system automatically:

1. **Creates the base category entry** in the LIB (if it doesn't exist)
2. **Adds the PPMP item** under that category with the PPMP reference

## How It Works Now

### 1. Comprehensive Category List
The LIB expense selector now shows **100+ standard expense categories** organized into:

- **A. PERSONAL SERVICES** (5 categories)
  - Salaries and Wages - Regular
  - Salaries and Wages - Casual/Contractual
  - Other Compensation
  - Personnel Benefit Contributions
  - Other Personnel Benefits

- **B. Maintenance & Other Operating Expenses** (50+ categories)
  - Office Supplies Expenses
  - Accountable Forms Expenses
  - Food Supplies Expenses
  - Drugs and Medicines Expenses
  - Fuel, Oil and Lubricants Expenses
  - Training Expenses
  - Traveling Expenses - Local/Foreign
  - Electricity Expenses
  - Water Expenses
  - Internet Subscription Expenses
  - Telephone Expenses
  - Janitorial Services
  - Security Services
  - Repairs and Maintenance (various types)
  - And many more...

- **C. Capital Outlay** (8 categories)
  - Land
  - Buildings
  - Machinery and Equipment
  - Transportation Equipment
  - Furniture and Fixtures
  - Books
  - And more...

### 2. Automatic Category Creation

**Example Scenario:**

You create a PPMP for fiscal year 2026 with these items:
- Item 1: "Bond papers and office supplies" - ₱15,000
- Item 2: "Printer ink cartridges" - ₱8,000

**Step 1:** Link both items to "Office Supplies Expenses"
- You can select this even if it doesn't exist in your LIB yet
- The category shows with UACS code: 5020301000

**Step 2:** Save the PPMP (draft or final)

**Step 3:** System automatically creates in LIB:

```
B. Maintenance & Other Operating Expenses
├─ Office Supplies Expenses (Base Entry) - ₱0.00
├─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #1) - ₱15,000
└─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #2) - ₱8,000
```

**Note:** The base entry with ₱0.00 is created automatically as a placeholder. This ensures the category structure exists in the LIB.

### 3. Smart Duplicate Prevention

The system is smart about duplicates:

- **Base category exists?** → Skip creating it, just add the PPMP item
- **PPMP item already synced?** → Update the amount instead of creating duplicate
- **Different PPMP items?** → Add as separate entries with different references

## Benefits

### ✅ No More Manual LIB Setup
You don't need to manually create expense categories in the LIB before creating a PPMP. Just link your PPMP items to the standard categories, and they'll be created automatically.

### ✅ Standardized Categories
All departments use the same standard UACS expense categories, ensuring consistency across the system.

### ✅ Searchable Categories
The selector has a search box - just type "office" to find "Office Supplies Expenses" quickly.

### ✅ Complete Category List
You can see all available expense categories, making it easier to choose the right one for your procurement items.

## Example Workflow

### Scenario: Computer Studies Department needs to procure office supplies

**Before (Old Way):**
1. Go to LIB page
2. Create draft LIB for 2026
3. Manually add "Office Supplies Expenses" category
4. Save LIB
5. Go to PPMP page
6. Create PPMP
7. Link items to "Office Supplies Expenses"
8. Save PPMP

**Now (New Way):**
1. Go to PPMP page
2. Create PPMP for 2026
3. Add items
4. Link items to "Office Supplies Expenses" (select from comprehensive list)
5. Save PPMP
6. ✅ Done! Category is automatically created in LIB

## Technical Details

### Files Modified

1. **api/get_lib_expense_categories.php**
   - Now returns 100+ standard expense categories
   - Merges existing LIB categories with standard categories
   - Removes duplicates

2. **api/sync_ppmp_to_lib_helper.php**
   - Checks if base category exists in LIB
   - Creates base category entry if it doesn't exist
   - Adds PPMP item under the category
   - Prevents duplicates

### Category Structure in LIB

After syncing, the LIB will have this structure:

```
Category: B. Maintenance & Other Operating Expenses
├─ Office Supplies Expenses (₱0.00) ← Base entry (auto-created)
├─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #1) (₱15,000)
├─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #2) (₱8,000)
├─ Office Supplies Expenses (PPMP #CS-2026-002 - Item #1) (₱5,000)
└─ Total: ₱28,000
```

The base entry serves as:
- A category header/placeholder
- A place to add manual budget allocations if needed
- A visual separator in the LIB

## Standard UACS Categories Included

The system includes all standard government expense categories based on the Unified Accounts Code Structure (UACS). Each category has:
- **Category** - The main expense group (A, B, or C)
- **Particulars** - The specific expense name
- **Account Code** - The official UACS code

Examples:
- Office Supplies Expenses (5020301000)
- Training Expenses (5020201001)
- Machinery and Equipment (5060404000)
- Traveling Expenses - Local (5020101000)
- Internet Subscription Expenses (5020503000)

## Summary

The PPMP-LIB integration now provides a **comprehensive list of standard expense categories**, making it easier to create PPMPs without worrying about whether categories exist in the LIB. The system automatically creates the necessary category structure when you save the PPMP, streamlining the budget planning process.

**Status: ✅ COMPLETE AND READY TO USE**
