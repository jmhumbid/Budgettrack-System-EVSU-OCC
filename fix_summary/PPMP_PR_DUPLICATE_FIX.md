# PPMP Purchase Request Duplicate & Persistence Fix

## Issues Fixed

### 1. Particulars and PR NO./PO NO. Not Saving
**Problem:** When PPMP items were added to Purchase Requests, the manually entered fields (Particulars and PR NO./PO NO.) were not being saved to the database, causing them to disappear after closing the modal or refreshing the page.

**Root Cause:** The PPMP reference fields (ppmp_item_id, ppmp_id, ppmp_description) were not being included in the save/load operations.

**Solution:**
- Updated `api/save_single_purchase_request.php` to save PPMP reference fields
- Updated `api/load_purchase_requests.php` to load PPMP reference fields
- Updated JavaScript `autoSavePurchaseRequestEntry()` function to include PPMP references in the save payload

### 2. Duplicate PPMP Items
**Problem:** When reopening the Purchase Request modal, PPMP items were being duplicated - the same items appeared multiple times in the list.

**Root Cause:** The system was not checking if a PPMP item already existed in the purchase request list before adding it again.

**Solution:**
- Added duplicate detection in `addSelectedPPMPItems()` function
- Checks existing PPMP item IDs in the table before adding new items
- Filters out duplicates and shows a message about skipped items
- Only adds items that don't already exist

### 3. Visual Indicators for PPMP Items
**Enhancement:** Added visual distinction for PPMP-based purchase requests to make them easily identifiable.

**Implementation:**
- Purple background and border for PPMP-based rows
- Purple icon indicator in the Purchase Request field
- "From PPMP" badge below the description
- Read-only Purchase Request field for PPMP items (prevents accidental editing)
- Purple-styled amount field for PPMP items

## Files Modified

### 1. `api/load_purchase_requests.php`
**Changes:**
- Added `ppmp_item_id`, `ppmp_id`, `ppmp_description` to SELECT queries
- All three query variations (budget with dept, budget without dept, other roles) updated

```sql
SELECT id, purchase_request, particulars, pr_number, po_number, date, amount, created_by,
       ppmp_item_id, ppmp_id, ppmp_description
FROM utilization_purchase_requests
WHERE department_id = :dept_id AND fiscal_year = :year
ORDER BY id ASC
```

### 2. `api/save_single_purchase_request.php`
**Changes:**
- Added handling for PPMP reference fields in both INSERT and UPDATE operations
- Extracts PPMP fields from entry data
- Binds PPMP fields to SQL statements with proper NULL handling

**INSERT:**
```sql
INSERT INTO utilization_purchase_requests 
(department_id, purchase_request, particulars, pr_number, po_number, date, amount, fiscal_year, created_by,
 ppmp_item_id, ppmp_id, ppmp_description)
VALUES (:dept_id, :pr, :particulars, :pr_number, :po_number, :date, :amount, :year, :user_id,
        :ppmp_item_id, :ppmp_id, :ppmp_description)
```

**UPDATE:**
```sql
UPDATE utilization_purchase_requests 
SET purchase_request = :pr,
    particulars = :particulars,
    pr_number = :pr_number,
    po_number = :po_number,
    date = :date,
    amount = :amount,
    ppmp_item_id = :ppmp_item_id,
    ppmp_id = :ppmp_id,
    ppmp_description = :ppmp_description
WHERE id = :id
```

### 3. `pages/utilization.php` - JavaScript Functions

#### `autoSavePurchaseRequestEntry(entryId)`
**Changes:**
- Retrieves PPMP references from row data attributes
- Includes PPMP fields in the entry data payload

```javascript
// Get PPMP references if this entry is from PPMP
const ppmpItemId = row.getAttribute('data-ppmp-item-id');
const ppmpId = row.getAttribute('data-ppmp-id');

// Prepare entry data
const entryData = {
    purchaseRequest: purchaseRequest,
    particulars: particulars,
    prNumber: prNumber,
    date: date,
    amount: amount,
    deducted_from_entry_id: deductFromEntryId ? parseInt(deductFromEntryId) : null,
    ppmp_item_id: ppmpItemId ? parseInt(ppmpItemId) : null,
    ppmp_id: ppmpId ? parseInt(ppmpId) : null,
    ppmp_description: ppmpItemId ? purchaseRequest : null
};
```

#### Load Purchase Requests Function
**Changes:**
- Stores PPMP references as data attributes on rows
- Applies purple styling for PPMP-based entries
- Makes Purchase Request field read-only for PPMP items
- Adds visual indicators (icon, badge)
- Adds edit icons for Particulars and PR Number fields

```javascript
// Check if this is a PPMP-based entry
const isFromPPMP = entry.ppmp_item_id && entry.ppmp_id;
row.className = isFromPPMP ? 'hover:bg-gray-50 transition-colors bg-purple-50' : 'hover:bg-gray-50 transition-colors';

// Store PPMP references if this is from PPMP
if (entry.ppmp_item_id) {
    row.setAttribute('data-ppmp-item-id', entry.ppmp_item_id);
}
if (entry.ppmp_id) {
    row.setAttribute('data-ppmp-id', entry.ppmp_id);
}
```

#### `addSelectedPPMPItems()`
**Changes:**
- Added duplicate detection logic
- Checks existing PPMP item IDs in the table
- Filters out duplicates before adding
- Shows informative message about skipped duplicates

```javascript
// Check for duplicates - get existing PPMP item IDs in the table
const existingPPMPItemIds = [];
const tbody = document.getElementById('purchaseRequestTableBody');
if (tbody) {
    const rows = tbody.querySelectorAll('tr[data-ppmp-item-id]');
    rows.forEach(row => {
        const ppmpItemId = row.getAttribute('data-ppmp-item-id');
        if (ppmpItemId) {
            existingPPMPItemIds.push(parseInt(ppmpItemId));
        }
    });
}

// Filter out items that are already in the table
const newItems = itemsToAdd.filter(item => !existingPPMPItemIds.includes(item.id));
const duplicateItems = itemsToAdd.filter(item => existingPPMPItemIds.includes(item.id));
```

## How It Works Now

### Adding PPMP Items to Purchase Request
1. User clicks "Select PPMP Items" button
2. Modal opens showing approved PPMP items
3. User selects one or more items (checkboxes)
4. User clicks "Add Selected Items"
5. System checks for duplicates by comparing PPMP item IDs
6. Only new items are added to the table
7. Each row stores PPMP references as data attributes
8. Visual indicators (purple styling, badge) show PPMP origin
9. Entry is auto-saved to database with PPMP references

### Saving Purchase Request Data
1. User enters Particulars (via modal)
2. User enters PR NO./PO NO. (via modal)
3. User selects Date of Obligation
4. Auto-save triggers after 1 second of inactivity
5. All fields including PPMP references are saved to database
6. Database ID is stored on the row for future updates

### Loading Purchase Request Data
1. Modal opens or page refreshes
2. System fetches all purchase requests for the department
3. Each entry includes PPMP reference fields
4. Rows are created with proper styling based on PPMP origin
5. PPMP references are stored as data attributes
6. Visual indicators are applied to PPMP-based entries
7. All fields (including Particulars and PR NO.) are populated

### Preventing Duplicates
1. When adding PPMP items, system scans existing rows
2. Extracts all PPMP item IDs currently in the table
3. Compares selected items against existing IDs
4. Only adds items that don't already exist
5. Shows message about skipped duplicates if any

## Visual Indicators

### PPMP-Based Purchase Requests
- **Row Background:** Light purple (bg-purple-50)
- **Purchase Request Field:** Purple border and background
- **Amount Field:** Purple border and background
- **Icon:** Purple document icon in Purchase Request field
- **Badge:** "From PPMP" text below description
- **Read-Only:** Purchase Request field cannot be edited

### Regular Purchase Requests
- **Row Background:** White
- **Fields:** Gray borders, white background
- **Editable:** All fields can be edited

## Testing Checklist

- [x] PPMP items can be selected and added to Purchase Request
- [x] Particulars field saves and persists after modal close
- [x] PR NO./PO NO. field saves and persists after modal close
- [x] Date of Obligation saves and persists
- [x] Amount auto-fills from PPMP item
- [x] Duplicate PPMP items are prevented
- [x] Visual indicators show PPMP origin
- [x] Page refresh loads all data correctly
- [x] Modal close/reopen shows all data correctly
- [x] Multiple PPMP items can be added at once
- [x] Mixed PPMP and manual entries work together

## Database Requirements

Ensure the database schema includes PPMP reference columns:

```sql
ALTER TABLE utilization_purchase_requests 
ADD COLUMN IF NOT EXISTS ppmp_item_id INT NULL,
ADD COLUMN IF NOT EXISTS ppmp_id INT NULL,
ADD COLUMN IF NOT EXISTS ppmp_description TEXT NULL,
ADD INDEX IF NOT EXISTS idx_ppmp_item (ppmp_item_id),
ADD INDEX IF NOT EXISTS idx_ppmp (ppmp_id);
```

This is already included in `database/ppmp_utilization_integration.sql`.

## Benefits

1. **Data Persistence:** All fields now save correctly and persist across sessions
2. **No Duplicates:** System prevents adding the same PPMP item multiple times
3. **Visual Clarity:** Easy to identify which entries came from PPMP
4. **Data Integrity:** PPMP references are maintained throughout the workflow
5. **Better UX:** Users can see at a glance which items are from PPMP
6. **Audit Trail:** PPMP references allow tracking back to original PPMP items

## Notes

- PPMP-based Purchase Request fields are read-only to prevent accidental modification
- Particulars and PR NO./PO NO. remain editable for all entries
- The system uses data attributes to store PPMP references on DOM elements
- Auto-save ensures data is persisted even if user doesn't explicitly save
- Duplicate detection works by comparing PPMP item IDs, not descriptions

---

**Status:** Complete and tested
**Date:** March 5, 2026
**Developer:** Kiro AI Assistant
