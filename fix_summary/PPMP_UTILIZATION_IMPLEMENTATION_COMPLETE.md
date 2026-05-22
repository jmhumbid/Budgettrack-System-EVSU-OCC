# PPMP-Utilization Integration - Implementation Complete

## Overview
Complete implementation of the PPMP-Utilization integration workflow as specified by the user. This allows Budget Office to create Purchase Requests from PPMP items, link them to utilization deductions, and automatically notify department users.

## What Was Implemented

### 1. Database Schema Updates
**File:** `database/ppmp_utilization_integration.sql`

- Added PPMP reference columns to `purchase_requests` table:
  - `ppmp_item_id` - Links to specific PPMP item
  - `ppmp_id` - Links to parent PPMP
  - `ppmp_description` - Formatted description for display

- Added deduction tracking columns to `ppmp_items` table:
  - `deduction_remarks` - Stores expense category from deduction
  - `deducted_amount` - Tracks total amount deducted
  - `expense_category` - Linked expense category name

- Created `ppmp_deductions` tracking table:
  - Tracks all deductions from PPMP items through purchase requests
  - Links PPMP items to utilization entries
  - Stores fiscal year, department, expense category, and amount

- Added `notification_sent` flag to `ppmp` table
- Created indexes for performance optimization

### 2. API Endpoints Created

#### `api/get_ppmp_items_for_pr.php`
- Fetches PPMP items from FINAL/approved PPMPs for a specific department
- Returns formatted data including:
  - Item description, type, quantity, unit, amount
  - Deducted amount and remaining amount
  - PPMP number and fiscal year
  - Formatted string for display in Purchase Request

#### `api/link_pr_to_ppmp.php`
- Links Purchase Request to PPMP item
- Updates PPMP item with deduction information
- Creates deduction tracking record
- Sends notification to department user about the deduction
- Handles all operations in a database transaction

#### `api/get_ppmp_deductions.php`
- Retrieves all deductions for a specific PPMP or department
- Returns formatted data for display in modals
- Includes PPMP item details, PR details, and expense category
- Calculates totals

### 3. Notification System Integration

#### Updated `api/create_ppmp.php`
- Added notification logic when PPMP is saved as FINAL
- Notifies all Budget Office/Admin users
- Marks notification as sent to prevent duplicates

#### Updated `api/update_ppmp.php`
- Added notification logic when PPMP is updated to FINAL
- Only sends notification if not previously sent
- Notifies Budget Office/Admin users about the update

### 4. Purchase Request Modal Enhancement

#### Modified `pages/utilization.php`
- Added "Select from PPMP" section at the top of Purchase Request modal
- Added button to open PPMP Selection Modal
- Displays informational message about PPMP item selection

#### Created PPMP Selection Modal
- Full-screen modal for selecting PPMP items
- Displays all approved PPMP items for the selected department
- Shows:
  - Item description, type, quantity, unit
  - Original amount, deducted amount, remaining amount
  - PPMP number and fiscal year
  - Status indicators (Fully Deducted, etc.)
- Multi-select capability with checkboxes
- Visual feedback for selected items (purple border and background)
- Selected count display in footer

#### JavaScript Functions Added
- `openPPMPSelectionModal()` - Opens the PPMP selection modal
- `closePPMPSelectionModal()` - Closes the modal and resets selection
- `loadPPMPItems(departmentId)` - Fetches PPMP items from API
- `displayPPMPItems(items)` - Renders PPMP items in the modal
- `togglePPMPItemSelection(itemId)` - Handles item selection/deselection
- `updatePPMPSelectedCount()` - Updates the selected count display
- `addSelectedPPMPItems()` - Adds selected items to Purchase Request table
- `addPurchaseRequestEntryFromPPMP(ppmpItem)` - Creates PR entry from PPMP item

### 5. Purchase Request Entry from PPMP

When a PPMP item is selected and added:
- Creates a new Purchase Request row with purple highlight
- Auto-fills Purchase Request field with formatted description:
  - Format: "Description, Type: X, Qty: Y, Unit: Z, Amount: A"
- Auto-fills Amount field with PPMP item amount
- Displays "From PPMP #XXX" badge below the description
- Stores PPMP item ID and PPMP ID as data attributes on the row
- Particulars, PR No./PO No., and Date of Obligation remain manual entry
- Auto-saves the entry to database

## Workflow Implementation

### Step 1: PPMP Creation & Notification ✅
- Department user creates PPMP and marks as FINAL
- Budget Office receives notification
- PPMP appears in PPMP/LIB View

### Step 2: Purchase Request from PPMP ✅
- Budget Office goes to Utilization page
- Selects department
- Opens Purchase Request modal
- Clicks "Select PPMP Items" button
- Selects multiple PPMP items
- Items are added to Purchase Request table with auto-filled data

### Step 3: Deduction from Purchase Request ✅
- Budget Office adds deduction to utilization entry
- Selects source: Purchase Request
- Purchase Request is linked to PPMP item
- Amount automatically deducts from Total Balance

### Step 4: Generate Summary & Notify ✅
- Budget Office generates and saves summary
- Department user receives notification about deductions
- PPMP items are updated with deduction information

### Step 5: View PPMP Deductions (Pending)
- Department user views PPMP in ppmp.php
- Remarks column shows expense category
- **Note:** This requires updating ppmp.php to display the remarks column

### Step 6: Utilization View - PPMP Button (Pending)
- Add PPMP button in utilization__view.php "View Details" container
- Modal displays: Object/Purchase Request, Amount, Remarks
- **Note:** This requires updating utilization__view.php

## Files Modified

1. `database/ppmp_utilization_integration.sql` - NEW
2. `api/get_ppmp_items_for_pr.php` - NEW
3. `api/link_pr_to_ppmp.php` - NEW
4. `api/get_ppmp_deductions.php` - NEW
5. `api/create_ppmp.php` - MODIFIED (added notifications)
6. `api/update_ppmp.php` - MODIFIED (added notifications)
7. `pages/utilization.php` - MODIFIED (added PPMP selection)

## Remaining Tasks

### 1. Update ppmp.php to Display Remarks Column
- Add "Remarks" column to PPMP items table
- Display expense category from deductions
- Show deducted amount if applicable

### 2. Add PPMP Button to utilization__view.php
- Add PPMP button in "View Details" section
- Create modal to display PPMP deductions
- Show: Object/Purchase Request, Amount, Remarks

### 3. Database Migration
- Run `database/ppmp_utilization_integration.sql` to apply schema changes
- Ensure all tables are updated correctly

### 4. Testing
- Test complete workflow end-to-end
- Verify notifications are sent correctly
- Test PPMP item selection with multiple items
- Verify deduction tracking works correctly
- Test with different departments and fiscal years

## Usage Instructions

### For Department Users:
1. Create PPMP in ppmp.php
2. Mark as FINAL when ready
3. Budget Office will be notified
4. Wait for deductions to be applied
5. View deductions in PPMP remarks column (once implemented)

### For Budget Office:
1. Receive notification when PPMP is saved as FINAL
2. Go to Utilization page
3. Select the department
4. Click "Purchase Request" button
5. Click "Select PPMP Items" button
6. Select one or more PPMP items
7. Click "Add Selected Items"
8. Fill in Particulars, PR No./PO No., and Date of Obligation
9. Add deduction to utilization entry
10. Select Purchase Request as source
11. Generate and save summary
12. Department user will be notified

## Technical Notes

- All database operations use transactions for data integrity
- PPMP items track deducted amounts to prevent over-deduction
- Notifications are sent asynchronously and don't block main operations
- Purchase Request entries from PPMP are visually distinguished with purple highlighting
- Multi-select capability allows bulk addition of PPMP items
- Auto-save functionality ensures data is not lost

## Security Considerations

- All API endpoints check for user authentication
- Department ID is validated from session
- Only FINAL/approved PPMPs are available for selection
- Database transactions ensure data consistency
- Error logging for debugging without exposing sensitive data

## Performance Optimizations

- Database indexes on foreign keys and frequently queried columns
- Caching of PPMP items in JavaScript to avoid repeated API calls
- Efficient SQL queries with proper JOINs
- Lazy loading of PPMP items only when modal is opened

## Next Steps

1. Run database migration script
2. Test the complete workflow
3. Implement remaining features (ppmp.php remarks, utilization__view.php PPMP button)
4. User acceptance testing
5. Deploy to production

---

**Implementation Status:** Core functionality complete, pending UI enhancements for viewing deductions.
**Date:** March 5, 2026
**Developer:** Kiro AI Assistant
