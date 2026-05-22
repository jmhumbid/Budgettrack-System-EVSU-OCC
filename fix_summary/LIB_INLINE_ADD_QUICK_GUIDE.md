# LIB Inline Add Item - Quick Guide

## What's New?

✅ All three categories (A, B, C) always display, even if empty  
✅ "+ Add Item" button on each category header  
✅ Inline form for quick item entry  
✅ UACS autocomplete with category-specific codes  
✅ Auto-fill Account Code when UACS selected  

## How to Use

### Adding an Item to a Category

1. **Open a Draft LIB**
   - View any LIB in draft status
   - You'll see all three categories displayed

2. **Click "+ Add Item"**
   - Located on the right side of the category header
   - Example: Click "+ Add Item" on "A. PERSONAL SERVICES"

3. **Enter Particulars**
   - Type in the Particulars field (e.g., "honoraria")
   - UACS dropdown appears with matching codes
   - Click the desired UACS code

4. **Account Code Auto-Fills**
   - Account Code field automatically fills
   - Field is read-only (cannot be manually edited)

5. **Enter Amount**
   - Type the amount (e.g., 50000.00)
   - Must be a positive number

6. **Save**
   - Click "Save" button
   - Item is added to the LIB
   - Page refreshes to show the new item

### Canceling

- Click "Cancel" button to close the form
- All fields are cleared
- No changes are saved

## Category-Specific UACS Codes

The autocomplete shows only relevant codes for each category:

| Category | UACS Codes | Examples |
|----------|------------|----------|
| A. PERSONAL SERVICES | 5-01-xxx | Honoraria, Salaries, Allowances |
| B. MOOE | 5-02-xxx | Water, Electricity, Supplies, Security |
| C. Capital Outlay | 5-06-xxx | Equipment, Buildings, Furniture |

## Visual Guide

```
┌─────────────────────────────────────────────────────────┐
│ A. PERSONAL SERVICES                    [+ Add Item]    │
├─────────────────────────────────────────────────────────┤
│ [Inline Add Form - Hidden by default]                  │
│ Particulars: [Type to search...]  ▼                    │
│ Account Code: [Auto-filled]                            │
│ Amount: [0.00]                                          │
│ [Save] [Cancel]                                         │
├─────────────────────────────────────────────────────────┤
│ Honoraria - Part-time    5010210001    ₱987,390.00     │
│ Honoraria - Overload     5010210001    ₱728,562.92     │
├─────────────────────────────────────────────────────────┤
│ Sub-Total                              ₱1,715,952.92    │
└─────────────────────────────────────────────────────────┘
```

## Features

### UACS Autocomplete
- **Real-time search**: Results appear as you type
- **Minimum 2 characters**: Start typing to see results
- **Category-filtered**: Only shows codes for that category
- **Click to select**: Auto-fills both Particulars and Account Code

### Empty Categories
- All categories always visible
- Shows "No items in this category" if empty
- Can add items to any category anytime

### Validation
- ✓ Particulars required
- ✓ Account Code required (via UACS selection)
- ✓ Amount must be positive
- ✓ Only works on draft LIBs

## Tips

1. **Search Smart**: Type keywords like "honoraria", "water", "equipment"
2. **Use Autocomplete**: Don't type the full UACS name manually
3. **Tab Navigation**: Tab through fields for faster entry
4. **Check Category**: Make sure you're adding to the right category
5. **Save Often**: Add items one at a time for accuracy

## Common UACS Searches

| Search Term | Finds |
|-------------|-------|
| honoraria | Honoraria - Part-time, Honoraria - Overload |
| water | Water Expenses |
| electricity | Electricity Expenses |
| security | Security Services |
| internet | Internet Subscription Expenses |
| supplies | Office Supplies Expenses |
| equipment | Various equipment types |
| furniture | Furniture and Fixtures |

## Restrictions

- **Draft Only**: "+ Add Item" only appears for draft LIBs
- **Department Access**: Can only add to your department's LIBs
- **One at a Time**: Add one item per form submission
- **No Edit**: Use main edit modal to modify existing items

## Troubleshooting

### UACS dropdown not appearing
- Type at least 2 characters
- Check if you're in the right category
- Try a different search term

### Account Code not filling
- Make sure you clicked a UACS code from the dropdown
- Don't type manually in the Particulars field

### Save button not working
- Check all fields are filled
- Verify amount is a positive number
- Ensure LIB is in draft status
- Check you have permission to edit this LIB

### "+ Add Item" button not showing
- LIB must be in draft status
- Final LIBs cannot be edited
- Check if you're viewing your own department's LIB

## Example Workflow

**Adding "Honoraria - Part-time" to Personal Services:**

1. Click "+ Add Item" on "A. PERSONAL SERVICES"
2. Type "part" in Particulars field
3. Dropdown shows "Honoraria - Part-time"
4. Click "Honoraria - Part-time"
5. Particulars fills: "Honoraria - Part-time"
6. Account Code fills: "5010210001"
7. Type amount: "987390.00"
8. Click "Save"
9. Item appears in the table!

**Adding "Water Expenses" to MOOE:**

1. Click "+ Add Item" on "B. Maintenance & Other Operating Expenses"
2. Type "water" in Particulars field
3. Dropdown shows "Water Expenses"
4. Click "Water Expenses"
5. Particulars fills: "Water Expenses"
6. Account Code fills: "5020401000"
7. Type amount: "191400.00"
8. Click "Save"
9. Item appears in the table!

## Benefits

- **Faster**: No modal dialogs to open
- **Easier**: UACS codes auto-complete
- **Clearer**: Add items exactly where they belong
- **Safer**: Validation prevents errors
- **Organized**: All categories always visible

## Next Steps

After adding items:
- Review the LIB for accuracy
- Add more items as needed
- When complete, mark as Final
- Print or download the LIB

## Support

If you encounter issues:
1. Check this guide first
2. Verify you're on a draft LIB
3. Clear browser cache
4. Try a different browser
5. Contact system administrator

---

**Remember**: This feature only works on draft LIBs. Final LIBs cannot be edited to maintain data integrity!
