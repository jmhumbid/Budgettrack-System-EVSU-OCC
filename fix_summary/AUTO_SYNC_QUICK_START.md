# Auto-Sync LIB - Quick Start Guide

## ⚡ 3-Step Setup

### Step 1: Install Database (30 seconds)
```bash
php install_lib_custom_items.php
```
✅ Creates `lib_custom_items` table

### Step 2: Test Installation (1 minute)
```bash
php test_auto_lib_simple.php
```
✅ Verifies everything works

### Step 3: Use in Browser (2 minutes)
1. Login to BudgetTrack
2. Go to **LIB** page
3. Click **"Auto-Generate from Allocations"** (green button)
4. Select year → Click **"Generate LIB"**
5. Review items → Add custom items (optional)
6. Click **"Save LIB"**

---

## 🎯 What It Does

### Automatically:
- ✅ Pulls approved budget allocations
- ✅ Populates LIB with allocation data
- ✅ Calculates totals
- ✅ Categorizes items

### Manually (Optional):
- ➕ Add custom items not in allocations
- ✏️ Edit custom items
- 🗑️ Delete custom items

---

## 🎨 Visual Guide

### Button Location
```
LIB Page → Top Left
┌────────────────────────────────────────┐
│ [🔄 Auto-Generate] [➕ Create] [📄 Drafts] │
└────────────────────────────────────────┘
```

### Item Badges
- 🟢 **Green** = From Allocation (read-only)
- 🔵 **Blue** = Custom Item (editable)

---

## 📋 Quick Reference

### Files Created
```
✅ api/generate_auto_lib.php
✅ api/add_lib_custom_item.php
✅ api/update_lib_custom_item.php
✅ api/delete_lib_custom_item.php
✅ database/lib_custom_items_table.sql
✅ install_lib_custom_items.php
```

### Files Modified
```
✅ pages/lib.php (added auto-generate feature)
```

---

## 🚨 Common Issues

### "No approved allocations found"
→ Create allocations and mark as "approved"

### Button not visible
→ Clear browser cache and refresh

### Modal not opening
→ Check browser console for errors

---

## 📞 Need Help?

1. Run: `php test_auto_lib_generation.php`
2. Check: Browser console (F12)
3. Review: `LIB_AUTO_SYNC_COMPLETE.md`

---

## ✅ Success Checklist

- [ ] Database table installed
- [ ] Test script passes
- [ ] Button visible on LIB page
- [ ] Modal opens when clicked
- [ ] Items generate successfully
- [ ] Can add custom items
- [ ] Can save LIB

---

**Status:** ✅ READY TO USE  
**Time to Setup:** ~5 minutes  
**Time Saved per LIB:** ~30-45 minutes

🎉 **You're all set!**
