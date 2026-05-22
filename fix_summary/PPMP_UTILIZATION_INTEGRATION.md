# PPMP-Utilization Integration Implementation

## Overview
Complete workflow integration between PPMP, Purchase Requests, Utilization, and LIB with bidirectional notifications and automatic deductions.

## Workflow Steps

### Step 1: PPMP Creation & Notification
- **Actor**: Department User (e.g., Computer Studies)
- **Action**: Create and save PPMP as FINAL
- **Result**: 
  - Budget Office gets notified
  - PPMP appears in PPMP/LIB View

### Step 2: Purchase Request from PPMP
- **Actor**: Budget Office (Budget Role/Administrator)
- **Action**: 
  1. Go to Utilization page
  2. Select department (e.g., Computer Studies)
  3. Click Purchase Request button
  4. Select PPMP items (can select multiple rows)
- **Data Flow**:
  - From PPMP: General Description & Objective, Type, Quantity, Unit, Amount
  - Format: "Logbook 304 Pages, Type: Goods, Qty: 3, Unit: pcs, Amount: 700"
  - Amount auto-fills in Purchase Request
  - Particulars, PR No./PO No., Date of Obligation are manual entry
- **Result**: Purchase Request row created with PPMP reference

### Step 3: Deduction from Purchase Request
- **Actor**: Budget Office
- **Action**:
  1. Add deduction to Utilization entry (e.g., Office Supplies Expenses from LIB)
  2. Select source: Purchase Request
  3. Select Purchase Request row(s)
  4. Add selected
- **Result**: Amount automatically deducts from Total Balance

### Step 4: Generate Summary & Notify
- **Actor**: Budget Office
- **Action**: Generate and save summary
- **Result**:
  - Department User gets notified about deductions
  - PPMP remarks column updated with expense category

### Step 5: View PPMP Deductions
- **Actor**: Department User
- **Location**: ppmp.php
- **Display**: Remarks column shows expense category (e.g., "Office Supplies Expenses")

### Step 6: Utilization View - PPMP Button
- **Location**: utilization_view.php "View Details" container
- **Action**: Click PPMP button
- **Modal Display**: 
  - Columns: Object/Purchase Request, Amount, Remarks
  - Example: "Logbook 304 Pages, ₱700.00, Office Supplies Expenses"

## Database Schema Changes

### 1. Add PPMP reference to purchase_requests table
```sql
ALTER TABLE purchase_requests 
ADD COLUMN ppmp_item_id INT NULL,
ADD COLUMN ppmp_id INT NULL,
ADD COLUMN ppmp_description TEXT NULL,
ADD INDEX idx_ppmp_item (ppmp_item_id),
ADD INDEX idx_ppmp (ppmp_id);
```

### 2. Add remarks to ppmp_items table
```sql
ALTER TABLE ppmp_items 
ADD COLUMN deduction_remarks TEXT NULL,
ADD COLUMN deducted_amount DECIMAL(15,2) DEFAULT 0,
ADD COLUMN expense_category VARCHAR(255) NULL;
```

### 3. Create ppmp_deductions tracking table
```sql
CREATE TABLE IF NOT EXISTS ppmp_deductions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ppmp_id INT NOT NULL,
    ppmp_item_id INT NOT NULL,
    purchase_request_id INT NOT NULL,
    utilization_entry_id INT NOT NULL,
    expense_category VARCHAR(255) NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ppmp (ppmp_id),
    INDEX idx_ppmp_item (ppmp_item_id),
    INDEX idx_pr (purchase_request_id),
    INDEX idx_utilization (utilization_entry_id)
);
```

## API Endpoints to Create/Modify

### 1. `api/get_ppmp_items_for_pr.php`
- Get PPMP items for selected department
- Return: id, description, type, quantity, unit, amount
- Filter: Only FINAL/approved PPMPs

### 2. `api/create_pr_from_ppmp.php`
- Create Purchase Request from selected PPMP items
- Link PPMP item to PR
- Store PPMP reference

### 3. `api/link_pr_to_deduction.php`
- Link Purchase Request to Utilization deduction
- Update PPMP item remarks
- Create deduction tracking record

### 4. `api/get_ppmp_deductions.php`
- Get deductions for a specific PPMP
- Return: item description, amount, expense category

### 5. `api/notify_ppmp_deduction.php`
- Send notification to department user
- Notification: "Deduction applied to your PPMP"

## Files to Modify

1. **pages/utilization.php**
   - Modify Purchase Request modal
   - Add PPMP item selection
   - Link to deduction source

2. **pages/ppmp.php**
   - Add remarks column display
   - Show deduction information

3. **pages/utilization__view.php**
   - Add PPMP button in View Details
   - Create PPMP deductions modal

4. **assets/js/ppmp.js**
   - Add notification handling on save
   - Display remarks in view

5. **api/create_ppmp.php** / **api/update_ppmp.php**
   - Add notification trigger on FINAL save

## Implementation Order

1. ✅ Database schema updates
2. ✅ API endpoints creation
3. ✅ Utilization page - PPMP item selection in PR
4. ✅ Deduction linking functionality
5. ✅ Notification system integration
6. ✅ PPMP remarks display
7. ✅ Utilization view - PPMP button and modal
8. ✅ Testing and validation

## Testing Checklist

- [ ] PPMP save triggers notification
- [ ] PPMP items appear in PR selection
- [ ] Multiple PPMP items can be selected
- [ ] PR amount auto-fills from PPMP
- [ ] PR can be linked to deduction
- [ ] Deduction updates Total Balance
- [ ] Summary generation includes PPMP data
- [ ] Department user receives notification
- [ ] PPMP remarks show expense category
- [ ] Utilization view PPMP button works
- [ ] PPMP modal displays correct data
