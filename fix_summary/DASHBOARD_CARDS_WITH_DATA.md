# Dashboard Cards with PPMP & LIB Data

## Overview
Enhanced the PPMP and LIB quick action cards on the department dashboard to display real-time statistics including fiscal year, grand total, and count of approved/final items.

## Data Displayed

### PPMP Card
Shows data for the most recent fiscal year with approved PPMPs:
- **Grand Total**: Sum of all `estimated_budget` from approved PPMP items
- **Count**: Number of approved PPMPs
- **Fiscal Year**: The fiscal year of the data
- **Status**: Only shows data for `status = 'approved'` PPMPs

### LIB Card
Shows data for the most recent fiscal year with approved LIBs:
- **Grand Total**: Sum of all `amount` from approved LIB items
- **Count**: Number of approved LIBs
- **Fiscal Year**: The fiscal year of the data
- **Status**: Only shows data for `status = 'approved'` LIBs

## Database Queries

### PPMP Query
```php
SELECT COUNT(DISTINCT p.id) as count,
       COALESCE(SUM(pi.estimated_budget), 0) as total,
       p.fiscal_year
FROM ppmp p
LEFT JOIN ppmp_items pi ON p.id = pi.ppmp_id
WHERE p.department_id = ? AND p.status = 'approved'
GROUP BY p.fiscal_year
ORDER BY p.fiscal_year DESC
LIMIT 1
```

### LIB Query
```php
SELECT COUNT(DISTINCT l.id) as count,
       COALESCE(SUM(li.amount), 0) as total,
       l.fiscal_year
FROM line_item_budgets l
LEFT JOIN line_item_budget_items li ON l.id = li.lib_id
WHERE l.department_id = ? AND l.status = 'approved'
GROUP BY l.fiscal_year
ORDER BY l.fiscal_year DESC
LIMIT 1
```

## Card Display Logic

### When Data Exists (Approved Items Found)
```html
<h3>Manage PPMP</h3>
<p class="text-2xl">₱1,234,567.89</p>
<p class="text-xs">3 approved • FY 2026</p>
```

### When No Data (No Approved Items)
```html
<h3>Manage PPMP</h3>
<p class="text-xs">No approved PPMP yet</p>
```

## Visual Design

### PPMP Card (with data)
- **Title**: "Manage PPMP" (white, uppercase)
- **Amount**: Large, bold white text (₱X,XXX,XXX.XX)
- **Details**: Small text showing count and fiscal year
- **Background**: Maroon gradient
- **Hover**: Icon scales, arrow slides in

### LIB Card (with data)
- **Title**: "Manage LIB" (white, uppercase)
- **Amount**: Large, bold white text (₱X,XXX,XXX.XX)
- **Details**: Small text showing count and fiscal year
- **Background**: Blue gradient
- **Hover**: Icon scales, arrow slides in

## Features

### Real-Time Data
- Queries run on every page load
- Shows most recent fiscal year data
- Only counts approved/final items
- Gracefully handles missing data

### User Experience
- Clear visual hierarchy
- Amount prominently displayed
- Count and year provide context
- Empty state message when no data

### Performance
- Efficient queries with proper JOINs
- Uses COALESCE for null safety
- LIMIT 1 for single fiscal year
- Indexed columns (department_id, status)

## Example Displays

### PPMP Card Examples

**With Data:**
```
Manage PPMP
₱2,450,000.00
5 approved • FY 2026
```

**Without Data:**
```
Manage PPMP
No approved PPMP yet
```

### LIB Card Examples

**With Data:**
```
Manage LIB
₱1,850,000.00
3 approved • FY 2026
```

**Without Data:**
```
Manage LIB
No approved LIB yet
```

## Variables Added

### PHP Variables
```php
// PPMP
$ppmpCount = 0;        // Number of approved PPMPs
$ppmpTotal = 0;        // Sum of estimated budgets
$ppmpFiscalYear = date('Y'); // Fiscal year

// LIB
$libCount = 0;         // Number of approved LIBs
$libTotal = 0;         // Sum of amounts
$libFiscalYear = date('Y');  // Fiscal year
```

## Error Handling
- Try-catch blocks prevent crashes
- Silently fails if tables don't exist
- Defaults to 0 and current year
- Shows "No approved" message gracefully

## Benefits

1. **Transparency**: Users see their approved totals at a glance
2. **Context**: Fiscal year and count provide useful context
3. **Motivation**: Seeing totals encourages completion
4. **Quick Access**: Still functions as navigation to full pages
5. **Status Awareness**: Only shows finalized/approved data

## Technical Notes

- Queries only run if `$departmentId` is set
- Uses prepared statements for security
- Joins ensure accurate totals
- Groups by fiscal year for proper aggregation
- Orders DESC to get most recent year first

## Future Enhancements

Possible additions:
- Show draft count separately
- Add trend indicators (up/down from previous year)
- Click to filter by fiscal year
- Show supplemental PPMP separately
- Add loading states for async queries
