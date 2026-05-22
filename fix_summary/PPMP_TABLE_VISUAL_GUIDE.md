# PPMP Table Visual Guide

## Before vs After Comparison

### BEFORE: Wide, Cluttered Table
```
┌────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ General Description | Type  | Qty | Unit | Recommended Mode      | Pre-Proc | Start    | End Ads  | Delivery | Source | Budget      | Allocated   | Remarks                      │
├────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ Office Supplies...  │ Goods │ 10  │ box  │ Agency to Agency      │ N        │ Apr 2026 │ May 2026 │ Jun 2026 │ IGF    │ ₱13,000.00  │ ₱10.00      │ Computer Studies, Engineering │
│ Printer Toner...    │ Goods │ 5   │ pcs  │ Small Value Proc...   │ Y        │ May 2026 │ Jun 2026 │ Jul 2026 │ RAF    │ ₱25,000.00  │ ₱5,000.00   │ Arts and Sciences            │
└────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
**Problems:**
- ❌ Too many columns (13 columns!)
- ❌ Requires horizontal scrolling
- ❌ Hard to read on smaller screens
- ❌ Information overload
- ❌ Difficult to focus on specific items

---

### AFTER: Clean, Collapsible Design

#### Collapsed View (Default)
```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ #  │ General Description & Objective                    │ Budget        │ Allocated    │ Remarks              │
├─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 1  │ Office Supplies for Department Operations      ▼   │ ₱13,000.00    │ ₱10.00       │ Computer Studies...  │
│ 2  │ Printer Toner Cartridges for Admin Office      ▼   │ ₱25,000.00    │ ₱5,000.00    │ Arts and Sciences    │
│ 3  │ Cleaning Materials and Janitorial Supplies     ▼   │ ₱8,500.00     │ ₱2,000.00    │                      │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
**Benefits:**
- ✅ Only 5 essential columns
- ✅ No horizontal scrolling
- ✅ Easy to scan and read
- ✅ Focus on key information
- ✅ Click to see more details

#### Expanded View (Click on Row)
```
┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ 1  │ Office Supplies for Department Operations      ▲   │ ₱13,000.00    │ ₱10.00       │ Computer Studies...  │
├─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│    ┌─────────────────────────────────────────────────────────────────────────────────────────────────────────┐  │
│    │  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐                              │  │
│    │  │ Type         │  │ Quantity     │  │ Pre-Proc     │  │ Source       │                              │  │
│    │  │ Goods        │  │ 10 box       │  │ No           │  │ IGF          │                              │  │
│    │  └──────────────┘  └──────────────┘  └──────────────┘  └──────────────┘                              │  │
│    │                                                                                                         │  │
│    │  ┌────────────────────────────────────────────────────────────────────────────────────────────────┐   │  │
│    │  │ Recommended Mode: Agency to Agency                                                             │   │  │
│    │  └────────────────────────────────────────────────────────────────────────────────────────────────┘   │  │
│    │                                                                                                         │  │
│    │  ┌──────────────────────┐  ┌──────────────────────┐  ┌──────────────────────┐                        │  │
│    │  │ Start Procurement    │  │ End Ads/Posting      │  │ Expected Delivery    │                        │  │
│    │  │ Apr 2026             │  │ May 2026             │  │ Jun 2026             │                        │  │
│    │  └──────────────────────┘  └──────────────────────┘  └──────────────────────┘                        │  │
│    └─────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
├─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 2  │ Printer Toner Cartridges for Admin Office      ▼   │ ₱25,000.00    │ ₱5,000.00    │ Arts and Sciences    │
└─────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```
**Benefits:**
- ✅ Beautiful card-based layout
- ✅ Organized information groups
- ✅ Easy to read and understand
- ✅ Professional appearance
- ✅ Smooth animations

---

## Action Buttons

### New Button Layout
```
┌────────────────────────────────────────────────────────────────────────────────────────┐
│  [Edit]  [Delete]  [Expand All]  [Collapse All]  [Print]                              │
└────────────────────────────────────────────────────────────────────────────────────────┘
```

**Button Functions:**
- **Edit** - Modify draft PPMP (only for drafts)
- **Delete** - Remove draft PPMP (only for drafts)
- **Expand All** - Open all item details at once
- **Collapse All** - Close all item details
- **Print** - Print with full table format

---

## Print View (Automatic Full Format)

When you click Print, the table automatically shows ALL columns:

```
┌──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
│ # │ General Description              │ Type  │ Qty │ Unit │ Recommended Mode  │ Pre │ Start  │ End Ads │ Delivery │ Source │ Budget      │ Allocated   │ Remarks              │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│ 1 │ Office Supplies for Dept Ops     │ Goods │ 10  │ box  │ Agency to Agency  │ N   │ Apr 26 │ May 26  │ Jun 26   │ IGF    │ ₱13,000.00  │ ₱10.00      │ Computer Studies...  │
│ 2 │ Printer Toner Cartridges         │ Goods │ 5   │ pcs  │ Small Value Proc  │ Y   │ May 26 │ Jun 26  │ Jul 26   │ RAF    │ ₱25,000.00  │ ₱5,000.00   │ Arts and Sciences    │
│ 3 │ Cleaning Materials               │ Goods │ 20  │ set  │ Agency to Agency  │ N   │ Jun 26 │ Jul 26  │ Aug 26   │ IGF    │ ₱8,500.00   │ ₱2,000.00   │                      │
├──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┤
│                                                                                                    GRAND TOTAL: │ ₱46,500.00  │ ₱7,010.00   │                      │
└──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
```

**Print Features:**
- ✅ All 14 columns visible
- ✅ Landscape orientation
- ✅ Professional formatting
- ✅ Official document ready
- ✅ No manual expansion needed

---

## User Interaction Flow

### Scenario 1: Quick Review
```
1. Open PPMP page
2. See collapsed table with key info
3. Scan descriptions and budgets
4. Done! ✓
```

### Scenario 2: Detailed Review
```
1. Open PPMP page
2. Click "Expand All" button
3. Review all details in card format
4. Click "Collapse All" when done
5. Done! ✓
```

### Scenario 3: Check Specific Item
```
1. Open PPMP page
2. Find item in collapsed view
3. Click on the row
4. Review expanded details
5. Click again to collapse
6. Done! ✓
```

### Scenario 4: Print Official Document
```
1. Open PPMP page
2. Click "Print" button
3. Preview shows full table automatically
4. Print or save as PDF
5. Done! ✓
```

---

## Visual Indicators

### Row States

**Collapsed (Default)**
```
│ 1  │ Office Supplies...  ▼  │ ₱13,000.00 │ ₱10.00 │ Remarks │
     ↑                      ↑
   Number              Down Arrow
```

**Expanded**
```
│ 1  │ Office Supplies...  ▲  │ ₱13,000.00 │ ₱10.00 │ Remarks │
├────┴─────────────────────────────────────────────────────────┤
│    [Detailed Information Cards]                               │
     ↑                      ↑
   Number               Up Arrow
```

**Hover Effect**
```
│ 1  │ Office Supplies...  ▼  │ ₱13,000.00 │ ₱10.00 │ Remarks │
└────────────────────────────────────────────────────────────────┘
     ↑ Light gray background on hover
```

---

## Color Coding

### Budget Amounts
- **Budget Column**: Green text (₱13,000.00)
- **Allocated Column**: Blue text (₱10.00)
- **Remarks with Deductions**: Yellow background

### Expanded Details
- **Card Background**: White with shadow
- **Container Background**: Gradient blue-purple
- **Labels**: Gray uppercase text
- **Values**: Bold dark text

---

## Responsive Behavior

### Desktop (1920px+)
```
┌─────────────────────────────────────────────────────────────────────────────────────────┐
│ Full width table, 4-column grid in expanded details                                    │
└─────────────────────────────────────────────────────────────────────────────────────────┘
```

### Tablet (768px - 1919px)
```
┌───────────────────────────────────────────────────────────────────┐
│ Adjusted table, 2-column grid in expanded details                │
└───────────────────────────────────────────────────────────────────┘
```

### Mobile (< 768px)
```
┌─────────────────────────────────────────┐
│ Stacked columns, 1-column grid         │
│ in expanded details                     │
└─────────────────────────────────────────┘
```

---

## Summary

### What You See
- **Screen**: Clean, 5-column table with expandable rows
- **Print**: Complete, 14-column traditional table

### How to Use
- **Click row**: Toggle details
- **Expand All**: See everything
- **Collapse All**: Clean view
- **Print**: Automatic full format

### Why It's Better
- ✅ Easier to read
- ✅ Faster to navigate
- ✅ Professional appearance
- ✅ Mobile-friendly
- ✅ Print-ready

**The best of both worlds: Simple on screen, complete on paper!**
