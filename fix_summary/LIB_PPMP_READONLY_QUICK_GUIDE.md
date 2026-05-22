# LIB PPMP-Linked Items - Quick User Guide

## 🔒 What Changed?

Items in the LIB that come from PPMP are now **locked** and can only be edited through the PPMP.

## 🎯 Why This Change?

To prevent confusion and ensure that:
- PPMP and LIB always match
- Budget amounts are consistent
- You know where to edit each item

## 📋 How to Identify PPMP-Linked Items

### Visual Indicators:

1. **Blue "PPMP" Badge**
   - Appears next to the item name
   - Has a link icon
   - Indicates the item is from PPMP

2. **"Locked" Status**
   - Appears where edit/delete buttons normally are
   - Gray badge with lock icon
   - Means you can't edit it in the LIB

### Example:
```
Office Supplies Expenses [PPMP Badge]  |  5020301000  |  ₱3,000.00  [Locked]
Water Expenses                         |  5020401000  |  ₱5,000.00  [Edit][Delete]
```

## ✏️ How to Edit Items

### PPMP-Linked Items (with PPMP badge):
1. Go to **PPMP page**
2. Find the PPMP that contains the item
3. Click **Edit**
4. Modify the item
5. **Save** - LIB will update automatically

### Manual Items (no PPMP badge):
1. Stay on **LIB page**
2. Click **Edit** button next to the item
3. Modify the item
4. **Save**

## 🗑️ How to Delete Items

### PPMP-Linked Items:
1. Go to **PPMP page**
2. Find the PPMP
3. Either:
   - Delete individual items (Edit PPMP → Remove item)
   - Delete entire PPMP (Delete button)
4. LIB will update automatically

### Manual Items:
1. Stay on **LIB page**
2. Click **Delete** button next to the item
3. Confirm deletion

## 💡 Quick Tips

### Tip 1: Check the Badge
- **Has PPMP badge** = Edit in PPMP
- **No badge** = Edit in LIB

### Tip 2: Hover for Info
- Hover over the PPMP badge for explanation
- Hover over the Locked badge for details

### Tip 3: Automatic Updates
- When you edit PPMP, LIB updates automatically
- When you delete PPMP, LIB items are removed automatically
- No manual sync needed!

## 📊 Common Scenarios

### Scenario 1: Need to Change Budget Amount
**Item has PPMP badge:**
1. Go to PPMP page
2. Edit the PPMP
3. Change the budget amount
4. Save
5. ✓ LIB updates automatically

**Item has no badge:**
1. Stay on LIB page
2. Click Edit
3. Change amount
4. Save

### Scenario 2: Need to Delete an Item
**Item has PPMP badge:**
1. Go to PPMP page
2. Edit the PPMP
3. Remove the item
4. Save
5. ✓ LIB updates automatically

**Item has no badge:**
1. Stay on LIB page
2. Click Delete
3. Confirm

### Scenario 3: Need to Add New Item
**From PPMP:**
1. Go to PPMP page
2. Create or edit PPMP
3. Add item and link to LIB category
4. Save
5. ✓ Item appears in LIB with PPMP badge

**Directly to LIB:**
1. Stay on LIB page
2. Click "Add Item"
3. Fill in details
4. Save
5. ✓ Item appears without PPMP badge (can be edited in LIB)

## ❓ FAQ

**Q: Why can't I edit this item in the LIB?**
A: It has a PPMP badge, which means it's linked to a PPMP. Edit it through the PPMP page instead.

**Q: How do I know which PPMP an item belongs to?**
A: The PPMP badge indicates it's from a PPMP. You can search for the item in the PPMP page to find which one.

**Q: Can I convert a PPMP item to a manual item?**
A: Not directly. You would need to delete it from the PPMP and manually add it to the LIB.

**Q: What happens if I delete the PPMP?**
A: All items linked to that PPMP are automatically removed from the LIB.

**Q: Can I have both PPMP and manual items in the same LIB?**
A: Yes! You can have a mix of both. Each item is independently locked or unlocked.

**Q: Will this affect my existing LIBs?**
A: Existing items will be automatically categorized as PPMP or manual based on their content. No data is lost.

## 🚀 Benefits

### For You:
- ✓ Clear indication of item source
- ✓ No accidental edits to PPMP items
- ✓ Automatic synchronization
- ✓ Less confusion

### For Your Department:
- ✓ Consistent budget data
- ✓ Better audit trail
- ✓ Clearer workflow
- ✓ Fewer errors

## ⚠️ Important Notes

1. **PPMP is the source of truth** for linked items
2. **LIB automatically updates** when PPMP changes
3. **Manual items** can still be edited in LIB
4. **Locked items** can only be edited through PPMP

## 🔧 Setup Required

**First Time Only**: Your system administrator needs to run a migration script to enable this feature.

After migration:
- All existing items are categorized
- New items are automatically tracked
- Feature works immediately

## ✅ Status

**ACTIVE** - This feature is now enabled and working!

---

**Need Help?** Contact your system administrator or refer to the detailed documentation.
