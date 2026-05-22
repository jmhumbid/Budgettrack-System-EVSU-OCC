# Line Item Budget (LIB) - Visual Guide

## 🎨 User Interface Overview

### 1. Sidebar Navigation
The LIB link appears in all user role sidebars:

```
┌─────────────────────────┐
│  BudgetTrack            │
│  Administration Panel   │
├─────────────────────────┤
│  📊 Dashboard           │
│  💰 Budget Workflow     │
│  📄 File Submission     │
│  📤 Upload              │
│  📋 PR Submission       │
│  📝 LIB                 │ ← NEW!
│  🔔 Notifications       │
│  📊 Reports             │
└─────────────────────────┘
```

### 2. Main LIB Page Layout

```
┌────────────────────────────────────────────────────────────┐
│  Line Item Budget (LIB)                                    │
│  Create and manage line item budgets                       │
│                                                            │
│  [+ Create New LIB]                          [🔄 Refresh] │
├────────────────────────────────────────────────────────────┤
│  Your Line Item Budgets                                    │
│                                                            │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ Fiscal Year │ Fund Type │ Status │ Date │ Total │ Actions │
│  ├──────────────────────────────────────────────────────┤ │
│  │ FY 2026     │ IGF       │ DRAFT  │ 3/2  │ ₱8.9M │ View Edit Delete │
│  │ FY 2025     │ IGF       │ APPROVED│ 1/15 │ ₱7.2M │ View │
│  └──────────────────────────────────────────────────────┘ │
└────────────────────────────────────────────────────────────┘
```

### 3. Create/Edit LIB Modal

```
┌────────────────────────────────────────────────────────────┐
│  Create Line Item Budget                              [X]  │
├────────────────────────────────────────────────────────────┤
│                                                            │
│  Fiscal Year: [FY 2026____]  Fund Type: [IGF ▼]          │
│                                                            │
│  Budget Items                              [+ Add Item]    │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ Item #1                                        [🗑️]  │ │
│  │ Category: [A. PERSONAL SERVICES ▼]                   │ │
│  │ Account Code: [5 01 02 100 01]                       │ │
│  │ Particulars: [Honoraria]                             │ │
│  │ Amount: [728,562.92]                                 │ │
│  └──────────────────────────────────────────────────────┘ │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ Item #2                                        [🗑️]  │ │
│  │ Category: [B. Maintenance & Other Operating... ▼]    │ │
│  │ Account Code: [5 02 01 010 00]                       │ │
│  │ Particulars: [Traveling Expenses - Local]            │ │
│  │ Amount: [400,000.00]                                 │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                            │
│  Grand Total: ₱8,991,193.96                               │
│                                                            │
│                                    [Cancel] [Save LIB]     │
└────────────────────────────────────────────────────────────┘
```

### 4. View/Print LIB Modal

```
┌────────────────────────────────────────────────────────────┐
│  View Line Item Budget                    [🖨️ Print] [X]  │
├────────────────────────────────────────────────────────────┤
│                                                            │
│         EASTERN VISAYAS STATE UNIVERSITY                   │
│                  ORMOC CAMPUS                              │
│                  Ormoc City                                │
│                                                            │
│      DEPARTMENT OF INFORMATION TECHNOLOGY                  │
│              LINE ITEM BUDGET                              │
│                  FY 2026                                   │
│          Internally Generated Fund                         │
│                                                            │
│              [DRAFT] Status Badge                          │
│                                                            │
│  ┌──────────────────────────────────────────────────────┐ │
│  │ PARTICULARS          │ ACCOUNT CODE │ AMOUNT         │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │ A. PERSONAL SERVICES                                 │ │
│  │ Honoraria            │ 5 01 02 100 01│ 728,562.92    │ │
│  │ Honoraria - Overload │ 5 01 02 100 01│ 567,390.00    │ │
│  │                      │ Sub-Total     │ ₱1,295,952.92 │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │ B. Maintenance & Other Operating Expenses            │ │
│  │ Traveling Expenses   │ 5 02 01 010 00│ 400,000.00    │ │
│  │ Training Expenses    │ 5 02 02 010 00│ 150,000.00    │ │
│  │ ...                  │ ...           │ ...           │ │
│  │                      │ Sub-Total     │ ₱3,577,182.04 │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │ C. Capital Outlay                                    │ │
│  │ ICT Equipment        │ 5 06 04 050 03│ 3,888,059.00  │ │
│  │                      │ Sub-Total     │ ₱3,888,059.00 │ │
│  ├──────────────────────────────────────────────────────┤ │
│  │                      │ Grand Total   │ ₱8,991,193.96 │ │
│  └──────────────────────────────────────────────────────┘ │
│                                                            │
│  Prepared:          Noted:              Approved:          │
│  ____________       ____________        ____________       │
│  Dept Head          Budget Officer      Director           │
│                                                            │
└────────────────────────────────────────────────────────────┘
```

## 🎨 Color Scheme

### Status Badges
- **DRAFT** - Gray background (#f3f4f6), Gray text (#1f2937)
- **PENDING APPROVAL** - Yellow background (#fef3c7), Yellow text (#92400e)
- **APPROVED** - Green background (#d1fae5), Green text (#065f46)
- **REJECTED** - Red background (#fee2e2), Red text (#991b1b)

### Theme Colors
- **Primary (Maroon)**: #800000
- **Maroon Dark**: #5a0000
- **Maroon Light**: #a00000
- **Gradient**: from-maroon via-red-700 to-red-800

### Category Colors (in modal)
- **Personal Services**: Blue gradient (from-blue-50 to-blue-100)
- **Maintenance & Operating**: Green gradient (from-green-50 to-green-100)
- **Capital Outlay**: Purple gradient (from-purple-50 to-purple-100)

## 📱 Responsive Design

### Desktop View (1024px+)
- Full sidebar visible
- Wide modal dialogs
- Multi-column layouts
- All features accessible

### Tablet View (768px - 1023px)
- Collapsible sidebar
- Adjusted modal widths
- Responsive tables
- Touch-friendly buttons

### Mobile View (< 768px)
- Hamburger menu
- Full-width modals
- Stacked layouts
- Larger touch targets

## 🖨️ Print Layout

When printing, the system automatically:
- Hides navigation elements
- Removes action buttons
- Optimizes for A4/Letter paper
- Maintains professional formatting
- Shows signature sections
- Includes all budget details

## 🎯 Interactive Elements

### Buttons
- **Primary Actions**: Maroon gradient with hover effect
- **Secondary Actions**: Gray with hover effect
- **Danger Actions**: Red with hover effect
- **Icon Buttons**: SVG icons with hover states

### Forms
- **Input Fields**: Border focus with maroon ring
- **Dropdowns**: Custom styled with arrow indicator
- **Number Inputs**: Decimal support with formatting
- **Dynamic Lists**: Add/remove with smooth animations

### Tables
- **Header**: Maroon background, white text
- **Rows**: Hover effect with light gray background
- **Borders**: Subtle gray borders
- **Responsive**: Horizontal scroll on small screens

## 🔔 User Feedback

### Success Messages
```
✓ Line Item Budget created successfully
✓ Line Item Budget updated successfully
✓ Line Item Budget deleted successfully
```

### Error Messages
```
✗ Error: Missing required fields
✗ Error: LIB not found or cannot be edited
✗ Database error: [error details]
```

### Confirmation Dialogs
```
⚠️ Are you sure you want to delete this LIB?
   This action cannot be undone.
   [Cancel] [Delete]
```

## 📊 Data Display

### Currency Formatting
- Format: ₱#,###.##
- Example: ₱8,991,193.96
- Decimal places: 2
- Thousands separator: comma

### Date Formatting
- Format: MM/DD/YYYY
- Example: 03/02/2026
- Timezone: Server timezone

### Status Display
- Uppercase text
- Colored badges
- Icon indicators (future)

## 🎨 Icons Used

All icons are from Heroicons (outline style):
- 📝 Document: LIB feature
- ➕ Plus: Add new item
- 🗑️ Trash: Delete item
- ✏️ Pencil: Edit item
- 👁️ Eye: View item
- 🖨️ Printer: Print function
- 🔄 Refresh: Reload data
- ❌ X: Close modal
- 💰 Currency: Budget amounts
- 📊 Chart: Dashboard/Reports

## 🎯 User Flow

### Creating a LIB
1. Click "Create New LIB" button
2. Enter fiscal year and select fund type
3. Click "Add Item" to add budget items
4. Fill in category, account code, particulars, amount
5. Add more items as needed
6. Review grand total
7. Click "Save LIB"
8. Success message appears
9. LIB appears in list

### Editing a LIB
1. Click "Edit" button on draft LIB
2. Modal opens with existing data
3. Modify fields as needed
4. Add/remove items
5. Click "Save LIB"
6. Success message appears
7. List refreshes with updated data

### Viewing/Printing a LIB
1. Click "View" button on any LIB
2. Modal opens with formatted view
3. Review all details
4. Click "Print" button
5. Browser print dialog opens
6. Select printer or save as PDF
7. Print/save document

## 🎨 Accessibility Features

- Semantic HTML structure
- ARIA labels on interactive elements
- Keyboard navigation support
- Focus indicators
- Color contrast compliance
- Screen reader friendly
- Alt text for icons (future enhancement)

## 📱 Browser Compatibility

Tested and compatible with:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+
- ✅ Opera 76+

## 🎉 Animation Effects

- Fade-in animations for modals
- Smooth transitions on hover
- Loading spinners (future)
- Success/error toast notifications (future)
- Slide animations for dropdowns

This visual guide provides a comprehensive overview of the LIB feature's user interface and user experience!
