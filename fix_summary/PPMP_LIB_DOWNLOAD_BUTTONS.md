# PPMP & LIB Download Buttons Implementation

## Overview
Added download PDF buttons for all tabs (PPMP, Supplemental, and LIB) in the `ppmp_view.php` page. The download buttons are placed below the "View Details" button for each item.

## Features Added

### 1. Download Buttons in UI
- **PPMP Tab**: Green "Download PDF" button below "View Details"
- **Supplemental Tab**: Green "Download PDF" button below "View Details"  
- **LIB Tab**: Green "Download PDF" button below "View Details"

All buttons feature:
- Green background (#10b981) with hover effect
- Download icon (arrow down into document)
- Opens PDF in new window for printing/saving

### 2. PDF Generation APIs

#### `api/download_ppmp_pdf.php`
Generates PDF for PPMP and Supplemental PPMP with:
- Department/Office name
- Fiscal year
- PPMP number
- Status badge (Draft/Approved)
- Supplemental badge (if applicable)
- Full item listing with:
  - Description
  - Type
  - Quantity & Unit
  - Mode of procurement
  - Estimated budget
  - Allocated funds
- Total calculations
- Landscape orientation for better table display
- Color-coded headers (maroon for PPMP, purple for Supplemental)

#### `api/download_lib_pdf.php`
Generates PDF for LIB with:
- Department/Office name
- Fiscal year
- Generated LIB number (format: DEPT-LIB-YEAR-ID)
- Status badge (Draft/Pending/Approved/Rejected)
- Full item listing with:
  - Particular
  - Account code
  - Amount
- Total calculation
- Portrait orientation
- Blue color scheme

### 3. JavaScript Functions

```javascript
function downloadPPMP(ppmpId) {
    window.open(`../api/download_ppmp_pdf.php?id=${ppmpId}`, '_blank');
}

function downloadLIB(libId) {
    window.open(`../api/download_lib_pdf.php?id=${libId}`, '_blank');
}
```

## UI Changes

### PPMP Tab
```html
<button onclick="event.stopPropagation(); downloadPPMP(${ppmp.id})" 
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
    <svg>...</svg>
    Download PDF
</button>
```

### Supplemental Tab
Same button structure as PPMP, uses the same `downloadPPMP()` function since supplemental PPMPs are stored in the same table.

### LIB Tab
```html
<button onclick="event.stopPropagation(); downloadLIB(${lib.id})" 
        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
    <svg>...</svg>
    Download PDF
</button>
```

## PDF Features

### Auto-Print
Both PDF generators include auto-print functionality:
```javascript
window.onload = function() {
    window.print();
};
```

### Responsive Design
- PPMP: Landscape orientation for wide tables
- LIB: Portrait orientation for simpler layout
- Print-optimized styling
- Proper page margins

### Branding
- Color-coded headers matching the system theme
- Footer with generation timestamp
- "BudgetTrack System" branding

## Files Modified
1. `pages/ppmp_view.php`
   - Added download buttons to `displayPPMPs()`
   - Added download buttons to `displaySupplementals()`
   - Added download buttons to `displayLIBs()`
   - Added `downloadPPMP()` and `downloadLIB()` JavaScript functions

## Files Created
1. `api/download_ppmp_pdf.php` - PPMP PDF generator
2. `api/download_lib_pdf.php` - LIB PDF generator

## Usage
1. Navigate to PPMP & LIB View page
2. Select a department or office
3. Switch between PPMP, Supplemental, or LIB tabs
4. Click "Download PDF" button on any item
5. PDF opens in new window with print dialog
6. User can save or print the PDF

## Security
- Session validation required
- User must be logged in
- Only authorized users can access (budget office, school admin, admin department)
- Department-based access control maintained

## Browser Compatibility
- Works in all modern browsers
- Uses standard window.print() API
- PDF rendering handled by browser's print functionality
