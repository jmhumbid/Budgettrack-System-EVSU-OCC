# PPMP Collapsible Table Enhancement

## Overview
Enhanced the PPMP table view with a modern, collapsible design that improves readability and user experience while maintaining full functionality for printing.

## What Changed

### Screen View (Interactive)
The table now displays in a **simplified, collapsible format**:

**Main Columns:**
- **#** - Item number
- **General Description & Objective** - Main item description (clickable to expand)
- **Budget** - Estimated budget amount
- **Allocated** - Allocated supporting funds
- **Remarks** - Deduction remarks with links to utilization

**Expandable Details:**
When you click on any row, it expands to show additional details in a beautiful card layout:
- Type (Goods/Service/Infrastructure)
- Quantity & Unit
- Pre-Procurement Conference (Yes/No)
- Source of Funds
- Recommended Mode of Procurement
- Start Procurement Date
- End Ads/Posting Date
- Expected Delivery Date

### Print View (Full Details)
When printing, the table automatically switches to show **ALL columns** in the traditional format:
- # | General Description | Type | Qty | Unit | Recommended Mode | Pre-Proc | Start | End Ads | Delivery | Source | Budget | Allocated | Remarks

## New Features

### 1. **Click to Expand/Collapse**
- Click any row to toggle details
- Visual arrow indicator rotates when expanded
- Smooth animations for better UX

### 2. **Expand/Collapse All Buttons**
New action buttons added:
- **Expand All** - Opens all item details at once
- **Collapse All** - Closes all item details
- Located next to Edit, Delete, and Print buttons

### 3. **Visual Enhancements**
- Hover effects on rows
- Color-coded budget amounts (green for budget, blue for allocated)
- Gradient backgrounds in expanded details
- Card-based layout for expanded information
- Smooth transitions and animations

### 4. **Responsive Design**
- Works perfectly on desktop, tablet, and mobile
- Details cards adapt to screen size
- Grid layout adjusts automatically

## User Benefits

### For Screen Viewing
✅ **Cleaner Interface** - Only see what you need  
✅ **Faster Scanning** - Focus on descriptions and budgets  
✅ **On-Demand Details** - Expand only items you're interested in  
✅ **Better Organization** - Grouped information in logical cards  
✅ **Less Scrolling** - Horizontal scrolling eliminated  

### For Printing
✅ **Complete Information** - All columns printed automatically  
✅ **Professional Format** - Traditional table layout for official documents  
✅ **No Manual Expansion** - Print view shows everything by default  

## How to Use

### Viewing PPMP Items
1. Navigate to the PPMP page
2. Select a PPMP to view
3. See the simplified table with key information
4. **Click any row** to see full details
5. Click again to collapse

### Expanding All Items
1. Click the **"Expand All"** button above the table
2. All items will show their details
3. Scroll through to review all information

### Collapsing All Items
1. Click the **"Collapse All"** button
2. All items return to compact view
3. Table becomes easier to scan

### Printing
1. Click the **"Print"** button
2. Print preview automatically shows full table
3. All columns visible in landscape format
4. No need to expand items manually

## Technical Implementation

### Files Modified
1. **assets/js/ppmp.js**
   - Updated `generatePPMPView()` function
   - Added `togglePPMPDetails()` function
   - Added `expandAllPPMPDetails()` function
   - Added `collapseAllPPMPDetails()` function
   - Modified table header structure
   - Implemented collapsible row logic

2. **pages/ppmp.php**
   - Updated print styles for full column display
   - Optimized print layout for landscape orientation

### Key Functions

```javascript
// Toggle individual item details
function togglePPMPDetails(itemIndex) {
    // Shows/hides expanded details row
    // Rotates arrow indicator
}

// Expand all items
function expandAllPPMPDetails() {
    // Opens all detail rows
    // Useful for reviewing all items
}

// Collapse all items
function collapseAllPPMPDetails() {
    // Closes all detail rows
    // Returns to compact view
}
```

### CSS Classes
- `.screen-only-row` - Visible on screen, hidden in print
- `.print-only-row` - Hidden on screen, visible in print
- `cursor-pointer` - Indicates clickable rows
- `hover:bg-gray-50` - Hover effect on rows

## Browser Compatibility
✅ Chrome/Edge - Full support  
✅ Firefox - Full support  
✅ Safari - Full support  
✅ Mobile browsers - Full support  

## Performance
- **Fast rendering** - No performance impact
- **Smooth animations** - CSS transitions
- **Efficient DOM** - Minimal JavaScript overhead
- **Print optimized** - Separate print layout

## Future Enhancements (Optional)
- [ ] Remember expanded state per user
- [ ] Keyboard shortcuts (Space to expand/collapse)
- [ ] Export to Excel with full details
- [ ] Filter by expanded/collapsed state
- [ ] Bulk actions on expanded items

## Troubleshooting

### Issue: Rows not expanding
**Solution:** Clear browser cache and reload page

### Issue: Print shows collapsed view
**Solution:** Ensure print styles are loaded (check browser console)

### Issue: Arrow not rotating
**Solution:** Check if JavaScript is enabled

## Summary
This enhancement transforms the PPMP table from a wide, hard-to-read format into a modern, user-friendly interface that:
- **Reduces visual clutter** on screen
- **Maintains full functionality** for printing
- **Improves user experience** with interactive elements
- **Keeps all data accessible** through expand/collapse

The table is now **easier to scan**, **faster to navigate**, and **more professional** while preserving all original functionality.
