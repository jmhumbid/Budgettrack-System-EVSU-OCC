# PPMP-to-LIB Auto-Sync - Quick Start Guide

## 🚀 Quick Start (5 Steps)

### Step 1: Clear Browser Cache
Press **Ctrl+Shift+R** to reload the page with fresh JavaScript

### Step 2: Create a PPMP
1. Go to PPMP page
2. Select fiscal year (e.g., 2026) from filter dropdown
3. Click "Create New PPMP"
4. Add items with descriptions and budgets

### Step 3: Link Items to LIB
1. Click "Link to LIB" button on each item
2. Search for category (e.g., type "office" to find "Office Supplies Expenses")
3. Click the category to select it
4. Repeat for all items

### Step 4: Save PPMP
- Click "Save Draft" (for draft PPMP)
- OR check "Mark as Final" and save (for final PPMP)

### Step 5: Verify in LIB
1. Go to LIB page
2. Open the draft LIB for your fiscal year
3. See your PPMP items automatically added!

## ✨ What Happens Automatically

When you save a PPMP with linked items:

1. ✅ System finds your draft LIB for the same fiscal year
2. ✅ Creates base category entries if they don't exist
3. ✅ Adds PPMP items under the correct categories
4. ✅ Includes PPMP reference for tracking
5. ✅ Prevents duplicate entries

## 📋 Example

**PPMP Items:**
- "Bond papers and pens" - ₱15,000 → Linked to "Office Supplies Expenses"
- "Printer ink" - ₱8,000 → Linked to "Office Supplies Expenses"

**Result in LIB:**
```
B. Maintenance & Other Operating Expenses
├─ Office Supplies Expenses (₱0.00)
├─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #1) (₱15,000)
└─ Office Supplies Expenses (PPMP #CS-2026-001 - Item #2) (₱8,000)
```

## 🔍 Available Categories

**61 standard expense categories** including:
- Office Supplies Expenses
- Training Expenses
- Traveling Expenses - Local
- Electricity Expenses
- Internet Subscription Expenses
- Machinery and Equipment
- Furniture and Fixtures
- And 54 more...

## ❓ Common Questions

**Q: Do I need to create the LIB first?**
A: Yes, create a draft LIB for your fiscal year first. The system will add items to it.

**Q: What if the category doesn't exist in my LIB?**
A: No problem! The system automatically creates it when you save the PPMP.

**Q: Can I link multiple PPMP items to the same category?**
A: Yes! Each item will be added as a separate entry with its own PPMP reference.

**Q: What if I edit the PPMP later?**
A: The system will update the amounts in the LIB automatically.

**Q: Can I sync to a finalized LIB?**
A: No, the LIB must be in draft status. Finalized LIBs cannot be modified.

## 🎯 Tips

- Use the **search box** in the category selector to find categories quickly
- Link items to the **most specific category** that matches your procurement
- You can **save as draft** first, then finalize later
- Check the **LIB page** after saving to verify items were added correctly

## 🐛 Troubleshooting

**Buttons not working?**
→ Clear browser cache (Ctrl+Shift+R)

**Categories not showing?**
→ Check that you're logged in and have a department assigned

**Items not syncing?**
→ Ensure you have a draft LIB for the same fiscal year

**Can't find a category?**
→ Use the search box or scroll through the comprehensive list

## 📚 More Information

- See `PPMP_LIB_FEATURE_COMPLETE.md` for full feature documentation
- See `PPMP_LIB_COMPREHENSIVE_CATEGORIES.md` for complete category list
- Run `php test_lib_categories.php` to verify setup

---

**Status: ✅ Ready to Use**

The PPMP-to-LIB auto-sync feature is fully implemented and tested. Start creating PPMPs with automatic LIB integration today!
