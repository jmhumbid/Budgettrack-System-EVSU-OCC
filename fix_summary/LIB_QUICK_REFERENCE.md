# Line Item Budget (LIB) - Quick Reference Card

## 🚀 Quick Start

### Installation (One-time setup)
```
1. Open: http://localhost/budgettrack/install_lib_feature.php
2. Wait for success message
3. Delete install_lib_feature.php
4. Done!
```

### Access
```
Login → Sidebar → Click "LIB"
```

## 📋 Main Actions

| Action | Button | Location | Permission |
|--------|--------|----------|------------|
| Create LIB | `+ Create New LIB` | Top left | All users |
| View LIB | `View` | Table row | All users |
| Edit LIB | `Edit` | Table row | Draft only |
| Delete LIB | `Delete` | Table row | Draft only |
| Print LIB | `🖨️ Print` | View modal | All users |
| Refresh List | `🔄 Refresh` | Top right | All users |

## 🎯 Budget Categories

| Code | Category | Examples |
|------|----------|----------|
| A | Personal Services | Honoraria, Salaries |
| B | Maintenance & Operating | Travel, Supplies, Utilities |
| C | Capital Outlay | Equipment, Buildings |

## 📊 Status Workflow

```
DRAFT → PENDING APPROVAL → APPROVED
  ↓
REJECTED
```

| Status | Can Edit? | Can Delete? | Color |
|--------|-----------|-------------|-------|
| Draft | ✅ Yes | ✅ Yes | Gray |
| Pending Approval | ❌ No | ❌ No | Yellow |
| Approved | ❌ No | ❌ No | Green |
| Rejected | ❌ No | ❌ No | Red |

## 🔐 Access Control

| Role | Can View | Can Create | Can Edit | Can Delete | Special Access |
|------|----------|------------|----------|------------|----------------|
| Budget Office | All LIBs | ✅ | Own drafts | Own drafts | Approve LIBs |
| Department | Own LIBs | ✅ | Own drafts | Own drafts | - |
| Office | Own LIBs | ✅ | Own drafts | Own drafts | - |
| Procurement | Own LIBs | ✅ | Own drafts | Own drafts | - |

## 💾 Database Tables

### line_item_budgets
```
id, department_id, fiscal_year, fund_type, status,
approved_by_budget_office, approved_date, approved_by_user_id,
created_by, created_at, updated_at
```

### line_item_budget_items
```
id, lib_id, category, particulars, account_code,
amount, sort_order, created_at, updated_at
```

## 📁 File Structure

```
budgettrack/
├── pages/
│   └── lib.php                    (Main page)
├── api/
│   ├── get_lib_list.php          (List LIBs)
│   ├── create_lib.php            (Create LIB)
│   ├── get_lib_details.php       (Get details)
│   ├── update_lib.php            (Update LIB)
│   └── delete_lib.php            (Delete LIB)
├── database/
│   └── lib_table.sql             (Schema)
├── components/
│   ├── admin_sidebar.php         (Updated)
│   ├── dept_sidebar.php          (Updated)
│   └── proc_sidebar.php          (Updated)
└── install_lib_feature.php       (Installer)
```

## 🎨 UI Components

### Modal Forms
- **Create/Edit**: Dynamic budget items with add/remove
- **View**: Formatted display with print option

### Tables
- **List View**: Sortable, filterable (future)
- **Print View**: Professional format matching paper template

### Buttons
- **Primary**: Maroon gradient
- **Secondary**: Gray
- **Danger**: Red

## 🔧 Common Tasks

### Create a New LIB
```
1. Click "+ Create New LIB"
2. Enter Fiscal Year (e.g., "FY 2026")
3. Select Fund Type
4. Click "+ Add Item"
5. Fill in: Category, Account Code, Particulars, Amount
6. Repeat step 4-5 for more items
7. Review Grand Total
8. Click "Save LIB"
```

### Edit an Existing LIB
```
1. Find LIB in list (must be DRAFT status)
2. Click "Edit" button
3. Modify fields as needed
4. Add/remove items using +/🗑️ buttons
5. Click "Save LIB"
```

### Print a LIB
```
1. Click "View" on any LIB
2. Review the formatted display
3. Click "🖨️ Print" button
4. Select printer or "Save as PDF"
5. Print/Save
```

### Delete a LIB
```
1. Find LIB in list (must be DRAFT status)
2. Click "Delete" button
3. Confirm deletion
4. LIB is removed
```

## 🐛 Troubleshooting

| Problem | Solution |
|---------|----------|
| Can't see LIB link | Check sidebar file is updated |
| Database error | Run install_lib_feature.php |
| Can't edit LIB | Only drafts can be edited |
| Can't delete LIB | Only drafts can be deleted |
| No LIBs showing | Check department assignment |
| Print not working | Check browser print settings |

## 📞 Support Checklist

Before asking for help:
- ✅ Database tables created?
- ✅ User logged in?
- ✅ Department assigned to user?
- ✅ Browser console errors?
- ✅ PHP error log checked?

## 🎯 Keyboard Shortcuts

| Key | Action |
|-----|--------|
| Esc | Close modal |
| Ctrl+P | Print (in view modal) |
| Tab | Navigate form fields |
| Enter | Submit form |

## 💡 Tips & Best Practices

1. **Always save drafts** before closing
2. **Review grand total** before saving
3. **Use consistent account codes** across LIBs
4. **Print for records** after approval
5. **Delete unused drafts** to keep list clean
6. **Use descriptive particulars** for clarity

## 📊 Sample Account Codes

### Personal Services (5 01 XX XXX XX)
- `5 01 02 100 01` - Honoraria
- `5 01 02 100 01` - Honoraria - Overload
- `5 01 02 100 01` - Honoraria - Part-time

### Maintenance & Operating (5 02 XX XXX XX)
- `5 02 01 010 00` - Traveling Expenses - Local
- `5 02 02 010 00` - Training Expenses
- `5 02 03 010 00` - Office Supplies Expense
- `5 02 04 010 00` - Water Expenses
- `5 02 05 020 01` - Electricity Expenses
- `5 02 05 020 02` - Telephone Expenses-Mobile
- `5 02 06 030 00` - Internet Subscription

### Capital Outlay (5 06 XX XXX XX)
- `5 06 04 040 02` - School Building
- `5 06 04 050 02` - Office Equipment Expenses
- `5 06 04 050 03` - ICT Equipment Expenses

## 🎉 Success Indicators

You're doing it right when:
- ✅ Grand total calculates automatically
- ✅ Status badges show correct colors
- ✅ Print view matches paper template
- ✅ Can only edit/delete drafts
- ✅ Department name shows in view
- ✅ All items have proper formatting

## 📈 Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.0 | 2026-03-02 | Initial release |

## 🔗 Related Documentation

- `LIB_FEATURE_GUIDE.md` - Comprehensive guide
- `LIB_IMPLEMENTATION_SUMMARY.md` - Technical details
- `LIB_VISUAL_GUIDE.md` - UI/UX reference

---

**Need Help?** Check the full documentation in `LIB_FEATURE_GUIDE.md`
