# Supplemental Color Change: Purple to Yellow

## Overview
Changed all supplemental PPMP colors from purple to yellow across the entire application for better visual distinction and consistency.

## Color Mapping

### Old Colors (Purple)
- Primary: `#7c3aed` / `#9333ea` (purple-600)
- Light backgrounds: `bg-purple-50`, `bg-purple-100`
- Text: `text-purple-600`, `text-purple-700`, `text-purple-800`
- Borders: `border-purple-300`, `border-purple-600`
- Hover: `hover:text-purple-600`, `hover:bg-purple-700`

### New Colors (Yellow)
- Primary: `#eab308` (yellow-600)
- Light backgrounds: `bg-yellow-50`, `bg-yellow-100`
- Text: `text-yellow-600`, `text-yellow-800`
- Borders: `border-yellow-300`, `border-yellow-600`
- Hover: `hover:text-yellow-600`, `hover:bg-yellow-700`

## Files Modified

### 1. `pages/ppmp_view.php`
**Changes:**
- Tab button hover color: `hover:text-yellow-600`
- Active tab styling: `border-yellow-600`, `text-yellow-600`, `bg-yellow-600 bg-opacity-5`
- Empty state icon: `bg-yellow-100`, `text-yellow-600`
- Supplemental badge: `bg-yellow-100 text-yellow-800`
- Card hover border: `hover:border-yellow-600`
- View Details button: `bg-yellow-600 hover:bg-yellow-700`
- Table headers: `bg-yellow-600` (for supplemental)
- Total row: `bg-yellow-600` (for supplemental)

### 2. `api/download_ppmp_pdf.php`
**Changes:**
- Header color for supplemental: `#eab308` (yellow-600)
- Supplemental badge background: `#fef3c7` (yellow-100)
- Supplemental badge text: `#92400e` (yellow-900)

### 3. `assets/js/ppmp.js`
**Changes:**
- PPMP number icon background: `bg-yellow-100`
- PPMP number icon color: `text-yellow-600`

### 4. `pages/file_submission.php`
**Changes:**
- Supplemental document badge: `bg-yellow-100 text-yellow-800`

### 5. `pages/utilization.php`
**Changes (bulk replacement):**
- All tab buttons: `hover:text-yellow-600`
- Active supplemental tab: `border-yellow-600`, `text-yellow-600`, `bg-yellow-600`
- PPMP item cards: `border-yellow-600`, `bg-yellow-50`
- Supplemental badges: `bg-yellow-100 text-yellow-800`
- Input fields from supplemental: `border-yellow-300`, `ring-yellow-500`, `bg-yellow-50`
- Checkboxes: `text-yellow-600`
- Amount displays: `text-yellow-600`
- Row backgrounds: `bg-yellow-50`

## Visual Impact

### Before (Purple)
- Supplemental items had purple badges and borders
- Purple was used for supplemental tabs and buttons
- Purple backgrounds for supplemental-sourced entries

### After (Yellow)
- Supplemental items now have yellow badges and borders
- Yellow is used for supplemental tabs and buttons
- Yellow backgrounds for supplemental-sourced entries
- Better contrast and distinction from regular PPMP (maroon) and LIB (blue)

## Color Scheme Summary

| Type | Primary Color | Use Case |
|------|--------------|----------|
| Regular PPMP | Maroon (#800000) | Standard procurement plans |
| Supplemental PPMP | Yellow (#eab308) | Additional/supplemental plans |
| LIB | Blue (#2563eb) | Line item budgets |

## Benefits
1. **Better Visual Distinction**: Yellow stands out more clearly from maroon and blue
2. **Improved Accessibility**: Yellow provides better contrast in most contexts
3. **Consistent Branding**: Yellow is often associated with "additional" or "supplemental" content
4. **PDF Downloads**: Supplemental PDFs now use yellow headers and badges

## Testing Checklist
- [ ] PPMP View page - Supplemental tab styling
- [ ] PPMP View page - Supplemental list items
- [ ] PPMP View page - Supplemental detail modal
- [ ] PPMP PDF download - Supplemental header color
- [ ] Utilization page - Supplemental deduction source tab
- [ ] Utilization page - PPMP selection modal supplemental tab
- [ ] Utilization page - Supplemental-sourced PR rows
- [ ] File submission page - Supplemental document badges

## Notes
- All purple references related to supplemental have been changed to yellow
- Regular PPMP remains maroon
- LIB remains blue
- Other purple colors (not related to supplemental) remain unchanged
