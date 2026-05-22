# Dashboard Quick Action Cards Update

## Overview
Replaced the "Total Submissions" card with two new quick action cards for PPMP and LIB management on department dashboards. These cards provide quick navigation to the respective pages.

## Changes Made

### Department Dashboard (`pages/dept_dashboard.php`)

#### Removed:
- **Total Submissions Card** - Static card showing count of all document submissions

#### Added:
1. **PPMP Quick Action Card**
   - Maroon gradient background (matching PPMP theme)
   - Links to `ppmp.php`
   - Icon: Document with lines
   - Text: "Manage PPMP" / "Create & view procurement plans"
   - Hover effects: Scale icon, show arrow, enhanced shadow

2. **LIB Quick Action Card**
   - Blue gradient background (matching LIB theme)
   - Links to `lib.php`
   - Icon: Library/box icon
   - Text: "Manage LIB" / "Create & view line item budgets"
   - Hover effects: Scale icon, show arrow, enhanced shadow

## Card Design

### PPMP Card
```html
<a href="ppmp.php" class="bg-gradient-to-br from-maroon to-red-800 rounded-2xl shadow-lg border border-red-900 p-6 hover:shadow-xl transition-all duration-300 cursor-pointer block group">
    <!-- Icon with scale animation on hover -->
    <!-- Arrow indicator that slides in on hover -->
    <!-- Title and description -->
</a>
```

### LIB Card
```html
<a href="lib.php" class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl shadow-lg border border-blue-900 p-6 hover:shadow-xl transition-all duration-300 cursor-pointer block group">
    <!-- Icon with scale animation on hover -->
    <!-- Arrow indicator that slides in on hover -->
    <!-- Title and description -->
</a>
```

## Features

### Interactive Elements
- **Hover Effects**: 
  - Icon scales up (110%)
  - Arrow fades in and slides right
  - Shadow intensifies
  - Smooth transitions

- **Visual Hierarchy**:
  - White semi-transparent icon background
  - Clear typography with opacity variations
  - Consistent spacing and sizing

### Color Scheme
- **PPMP**: Maroon (#800000) to Red-800 gradient
- **LIB**: Blue-600 (#2563eb) to Blue-800 gradient
- Both use white text with opacity variations for depth

## User Experience

### Before
- Users saw a static "Total Submissions" count
- No quick access to PPMP or LIB pages
- Had to navigate through sidebar menu

### After
- One-click access to PPMP management
- One-click access to LIB management
- Visual distinction between the two systems
- Engaging hover animations encourage interaction

## Dashboard Layout

The cards are positioned in the statistics grid alongside:
1. Budget Allocation card (clickable, shows allocation details)
2. **PPMP Quick Action card** (NEW - links to ppmp.php)
3. **LIB Quick Action card** (NEW - links to lib.php)
4. Total Balance for Utilization card (clickable, shows utilization)

## Roles Affected

### Updated:
- **Department Users** (`dept_dashboard.php`) - Main users who create PPMP and LIB

### Not Updated (as per requirements):
- **Budget Office** (`admin_dashboard.php`) - Excluded per user request
- **Procurement** (`proc_dashboard.php`) - Doesn't create PPMP/LIB
- **School Admin** (`school_admin_dashboard.php`) - Can be updated if needed
- **Super Admin** (`super_admin_dashboard.php`) - Can be updated if needed

## Benefits

1. **Faster Navigation**: Direct links to frequently used pages
2. **Visual Clarity**: Color-coded cards match the system's color scheme
3. **Better UX**: Hover animations provide feedback
4. **Space Efficiency**: Replaced less useful "Total Submissions" with actionable cards
5. **Consistency**: Matches the design language of other dashboard cards

## Technical Details

- Uses Tailwind CSS classes for styling
- Implements CSS transitions for smooth animations
- Group hover utilities for coordinated animations
- Maintains responsive design
- Accessible with proper semantic HTML (anchor tags)

## Future Enhancements

If needed, similar cards can be added to:
- School Admin dashboard
- Super Admin dashboard
- Other role-specific dashboards

The pattern is reusable and can be adapted for other quick actions like:
- Utilization management
- File submissions
- Purchase requests
- Reports
