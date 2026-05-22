# LIB Sub-Categories Visual Guide

## Feature Overview

This guide shows how the sub-category feature works visually.

## Before Sub-Categories

```
┌─────────────────────────────────────────────────────────────┐
│ B. Maintenance & Other Operating Expenses                   │
├─────────────────────────────────────────────────────────────┤
│ Particulars                          │ Account Code │ Amount│
├──────────────────────────────────────┼──────────────┼───────┤
│ Other Maintenance and Operating      │ 5-02-99-990  │₱25,000│
│ Expenses                             │              │       │
└─────────────────────────────────────────────────────────────┘

Problem: No breakdown of what the ₱25,000 covers
```

## After Sub-Categories

```
┌─────────────────────────────────────────────────────────────┐
│ B. Maintenance & Other Operating Expenses                   │
├─────────────────────────────────────────────────────────────┤
│ Particulars                          │ Account Code │ Amount│
├──────────────────────────────────────┼──────────────┼───────┤
│ Other Maintenance and Operating      │ 5-02-99-990  │₱25,000│
│ Expenses                             │              │       │
│   [Manage Sub-Categories] button                            │
│                                                              │
│   Sub-Categories:                                           │
│   ├─ Office Supplies                 │ 5-02-99-990  │₱5,000 │
│   ├─ Janitorial Services             │ 5-02-99-990  │₱3,000 │
│   ├─ Repairs and Maintenance         │ 5-02-99-990  │₱7,000 │
│   ├─ Communication Expenses          │ 5-02-99-990  │₱4,000 │
│   └─ Utilities                       │ 5-02-99-990  │₱6,000 │
│                                                              │
│   Total: ₱25,000 (auto-calculated)                          │
└─────────────────────────────────────────────────────────────┘

Solution: Clear breakdown with automatic total calculation
```

## User Interface Flow

### Step 1: Add Parent Item
```
┌────────────────────────────────────────────────────┐
│ Add Item to Category                               │
├────────────────────────────────────────────────────┤
│                                                    │
│ Particulars: [Other Maintenance and Operating...] │
│                                                    │
│ Account Code: [5-02-99-990] (auto-filled)        │
│                                                    │
│ Amount: [0.00] (will be calculated)               │
│                                                    │
│         [Save Item]  [Cancel]                     │
└────────────────────────────────────────────────────┘
```

### Step 2: Manage Sub-Categories Button Appears
```
┌────────────────────────────────────────────────────┐
│ Other Maintenance and Operating Expenses           │
│ Account Code: 5-02-99-990                         │
│ Amount: ₱0.00                                      │
│                                                    │
│ [Manage Sub-Categories] ← Click here              │
└────────────────────────────────────────────────────┘
```

### Step 3: Sub-Category Management Modal
```
┌──────────────────────────────────────────────────────────┐
│ Manage Sub-Categories: Other Maintenance and Operating   │
│ Expenses                                          [X]     │
├──────────────────────────────────────────────────────────┤
│                                                          │
│ ┌────────────────────────────────────────────────────┐  │
│ │ Add New Sub-Category                               │  │
│ ├────────────────────────────────────────────────────┤  │
│ │ Sub-Category Name: [Office Supplies          ]    │  │
│ │ Amount:           [5000.00                   ]    │  │
│ │                                                    │  │
│ │                        [Add Sub-Category]          │  │
│ └────────────────────────────────────────────────────┘  │
│                                                          │
│ ┌────────────────────────────────────────────────────┐  │
│ │ Sub-Categories                                     │  │
│ ├──────────────────────────────┬─────────┬──────────┤  │
│ │ Sub-Category Name            │ Amount  │ Actions  │  │
│ ├──────────────────────────────┼─────────┼──────────┤  │
│ │ Office Supplies              │ ₱5,000  │[Edit][X] │  │
│ │ Janitorial Services          │ ₱3,000  │[Edit][X] │  │
│ │ Repairs and Maintenance      │ ₱7,000  │[Edit][X] │  │
│ └──────────────────────────────┴─────────┴──────────┘  │
│                                                          │
│ ┌────────────────────────────────────────────────────┐  │
│ │ Total Amount:                        ₱15,000.00    │  │
│ └────────────────────────────────────────────────────┘  │
│                                                          │
│                                          [Close]         │
└──────────────────────────────────────────────────────────┘
```

### Step 4: Updated LIB Display
```
┌─────────────────────────────────────────────────────────────┐
│ Line Item Budget - FY 2026                                  │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│ B. Maintenance & Other Operating Expenses                   │
│                                                             │
│ Other Maintenance and Operating Expenses                    │
│   └─ Office Supplies              ₱5,000.00                │
│   └─ Janitorial Services          ₱3,000.00                │
│   └─ Repairs and Maintenance      ₱7,000.00                │
│                                                             │
│ Sub-Total:                                    ₱15,000.00    │
│                                                             │
│ Grand Total:                                  ₱15,000.00    │
└─────────────────────────────────────────────────────────────┘
```

## Data Flow Diagram

```
┌─────────────┐
│   User      │
│  Interface  │
└──────┬──────┘
       │
       │ 1. Add Sub-Category
       │    (name + amount)
       ▼
┌─────────────────────┐
│ add_lib_subcategory │
│      .php           │
└──────┬──────────────┘
       │
       │ 2. Insert into DB
       │    parent_id = X
       ▼
┌─────────────────────┐
│   Database          │
│ line_item_budget_   │
│     items           │
└──────┬──────────────┘
       │
       │ 3. Calculate SUM
       │    WHERE parent_id = X
       ▼
┌─────────────────────┐
│ Update Parent       │
│ amount = SUM        │
└──────┬──────────────┘
       │
       │ 4. Return new total
       ▼
┌─────────────┐
│   User      │
│  Interface  │
│  (Updated)  │
└─────────────┘
```

## Database Structure

### Parent Item
```
┌────────────────────────────────────────────────────┐
│ line_item_budget_items                             │
├────────────────────────────────────────────────────┤
│ id: 123                                            │
│ lib_id: 1                                          │
│ parent_id: NULL                    ← No parent     │
│ is_parent: 1                       ← Has children  │
│ category: "B. Maintenance & Other Operating..."    │
│ particulars: "Other Maintenance and Operating..."  │
│ sub_category_name: NULL            ← Not a child   │
│ account_code: "5-02-99-990"                       │
│ amount: 15000.00                   ← Auto-calc     │
└────────────────────────────────────────────────────┘
```

### Sub-Category Items
```
┌────────────────────────────────────────────────────┐
│ line_item_budget_items                             │
├────────────────────────────────────────────────────┤
│ id: 456                                            │
│ lib_id: 1                                          │
│ parent_id: 123                     ← Points to 123 │
│ is_parent: 0                       ← No children   │
│ category: "B. Maintenance & Other Operating..."    │
│ particulars: "Other Maintenance and Operating..."  │
│ sub_category_name: "Office Supplies" ← Sub name   │
│ account_code: "5-02-99-990"                       │
│ amount: 5000.00                    ← Manual entry  │
└────────────────────────────────────────────────────┘

┌────────────────────────────────────────────────────┐
│ line_item_budget_items                             │
├────────────────────────────────────────────────────┤
│ id: 457                                            │
│ lib_id: 1                                          │
│ parent_id: 123                     ← Points to 123 │
│ is_parent: 0                       ← No children   │
│ category: "B. Maintenance & Other Operating..."    │
│ particulars: "Other Maintenance and Operating..."  │
│ sub_category_name: "Janitorial Services"          │
│ account_code: "5-02-99-990"                       │
│ amount: 3000.00                    ← Manual entry  │
└────────────────────────────────────────────────────┘
```

## Calculation Logic

```
Parent Amount = SUM(Sub-Category Amounts)

Example:
┌─────────────────────────┬──────────┐
│ Sub-Category            │ Amount   │
├─────────────────────────┼──────────┤
│ Office Supplies         │  5,000   │
│ Janitorial Services     │  3,000   │
│ Repairs and Maintenance │  7,000   │
│ Communication Expenses  │  4,000   │
│ Utilities               │  6,000   │
├─────────────────────────┼──────────┤
│ TOTAL (Parent Amount)   │ 25,000   │
└─────────────────────────┴──────────┘

SQL Query:
SELECT SUM(amount) 
FROM line_item_budget_items 
WHERE parent_id = 123

Result: 25000.00

UPDATE line_item_budget_items 
SET amount = 25000.00 
WHERE id = 123
```

## Print/PDF Output

```
═══════════════════════════════════════════════════════════
    EASTERN VISAYAS STATE UNIVERSITY - ORMOC CAMPUS
                  LINE ITEM BUDGET
                      FY 2026
═══════════════════════════════════════════════════════════

B. Maintenance & Other Operating Expenses

┌──────────────────────────────────────┬──────────┬─────────┐
│ Particulars                          │ Account  │ Amount  │
│                                      │ Code     │         │
├──────────────────────────────────────┼──────────┼─────────┤
│ Other Maintenance and Operating      │5-02-99-  │₱25,000  │
│ Expenses                             │990       │         │
│                                      │          │         │
│   Sub-Categories:                    │          │         │
│   • Office Supplies                  │5-02-99-  │ ₱5,000  │
│                                      │990       │         │
│   • Janitorial Services              │5-02-99-  │ ₱3,000  │
│                                      │990       │         │
│   • Repairs and Maintenance          │5-02-99-  │ ₱7,000  │
│                                      │990       │         │
│   • Communication Expenses           │5-02-99-  │ ₱4,000  │
│                                      │990       │         │
│   • Utilities                        │5-02-99-  │ ₱6,000  │
│                                      │990       │         │
├──────────────────────────────────────┴──────────┼─────────┤
│ Sub-Total                                       │₱25,000  │
└─────────────────────────────────────────────────┴─────────┘

═══════════════════════════════════════════════════════════
```

## Benefits Visualization

### Without Sub-Categories
```
❌ Single line item: ₱25,000
❌ No breakdown
❌ Hard to audit
❌ Unclear allocation
❌ Manual tracking needed
```

### With Sub-Categories
```
✅ Detailed breakdown
✅ Automatic calculation
✅ Easy to audit
✅ Clear allocation
✅ Built-in tracking
✅ Professional reporting
```

## Common Use Cases

### Use Case 1: Office Operations
```
Other Maintenance and Operating Expenses: ₱50,000
├─ Office Supplies (Paper, Pens, etc.): ₱8,000
├─ Janitorial Services: ₱12,000
├─ Repairs and Maintenance: ₱15,000
├─ Communication (Phone, Internet): ₱7,000
└─ Utilities (Electricity, Water): ₱8,000
```

### Use Case 2: Event Management
```
Other Maintenance and Operating Expenses: ₱30,000
├─ Venue Rental: ₱10,000
├─ Catering Services: ₱12,000
├─ Audio-Visual Equipment: ₱5,000
└─ Promotional Materials: ₱3,000
```

### Use Case 3: Research Activities
```
Other Maintenance and Operating Expenses: ₱40,000
├─ Laboratory Supplies: ₱15,000
├─ Field Work Expenses: ₱10,000
├─ Data Collection Tools: ₱8,000
└─ Publication Fees: ₱7,000
```

## Summary

The sub-category feature provides:
- 📊 **Detailed Breakdown**: See exactly where money goes
- 🔢 **Auto-Calculation**: No manual math needed
- 📝 **Easy Management**: Simple add/edit/delete interface
- 🖨️ **Professional Output**: Clean reports and printouts
- ✅ **Data Integrity**: Automatic validation and consistency
- 🔍 **Better Auditing**: Clear paper trail for all expenses

This makes budget management more transparent, accurate, and professional!
