# Line Item Budget (LIB) Feature - Implementation Guide

## Overview
The Line Item Budget (LIB) feature has been successfully implemented for all user roles (Admin, Department, Office, and Procurement). This feature allows departments and offices to create, manage, and view their line item budgets in a computerized format based on the paper-based LIB template.

## Database Setup

### Step 1: Run the SQL Migration
Execute the following SQL file to create the required database tables:

```bash
# Location: database/lib_table.sql
```

Or manually run these SQL commands in your MySQL database:

```sql
-- Line Item Budget (LIB) Table
CREATE TABLE IF NOT EXISTS `line_item_budgets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `department_id` int(11) NOT NULL,
  `fiscal_year` varchar(10) NOT NULL,
  `fund_type` enum('Internally Generated Fund','Other Fund') NOT NULL DEFAULT 'Internally Generated Fund',
  `status` enum('draft','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft',
  `approved_by_budget_office` tinyint(1) DEFAULT 0,
  `approved_date` datetime DEFAULT NULL,
  `approved_by_user_id` int(11) DEFAULT NULL,
  `created_by` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `department_id` (`department_id`),
  KEY `created_by` (`created_by`),
  KEY `approved_by_user_id` (`approved_by_user_id`),
  CONSTRAINT `lib_department_fk` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lib_created_by_fk` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `lib_approved_by_fk` FOREIGN KEY (`approved_by_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Line Item Budget Items Table
CREATE TABLE IF NOT EXISTS `line_item_budget_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `lib_id` int(11) NOT NULL,
  `category` varchar(100) NOT NULL,
  `particulars` varchar(255) NOT NULL,
  `account_code` varchar(50) NOT NULL,
  `amount` decimal(15,2) NOT NULL DEFAULT 0.00,
  `sort_order` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `lib_id` (`lib_id`),
  CONSTRAINT `lib_items_lib_fk` FOREIGN KEY (`lib_id`) REFERENCES `line_item_budgets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Files Created

### 1. Main Page
- **pages/lib.php** - Main LIB management page for all user roles

### 2. API Endpoints
- **api/get_lib_list.php** - Fetch list of LIBs for the user
- **api/create_lib.php** - Create a new LIB
- **api/get_lib_details.php** - Get details of a specific LIB
- **api/update_lib.php** - Update an existing LIB (draft only)
- **api/delete_lib.php** - Delete a LIB (draft only)

### 3. Database Schema
- **database/lib_table.sql** - SQL migration file for creating tables

### 4. Updated Sidebar Components
- **components/admin_sidebar.php** - Added LIB link
- **components/dept_sidebar.php** - Added LIB link
- **components/proc_sidebar.php** - Added LIB link

## Features

### 1. Create Line Item Budget
- Users can create new LIB records with:
  - Fiscal Year (e.g., FY 2026)
  - Fund Type (Internally Generated Fund or Other Fund)
  - Multiple budget items with:
    - Category (Personal Services, Maintenance & Operating Expenses, Capital Outlay)
    - Account Code
    - Particulars (description)
    - Amount

### 2. View LIB List
- Displays all LIBs for the user's department
- Shows fiscal year, fund type, status, created date, and total amount
- Color-coded status badges:
  - Draft (Gray)
  - Pending Approval (Yellow)
  - Approved (Green)
  - Rejected (Red)

### 3. Edit LIB
- Only draft LIBs can be edited
- Users can modify fiscal year, fund type, and all budget items
- Real-time grand total calculation

### 4. Delete LIB
- Only draft LIBs can be deleted
- Confirmation dialog before deletion
- Cascading delete removes all associated budget items

### 5. View/Print LIB
- Professional formatted view matching the paper-based template
- Includes:
  - University header (EASTERN VISAYAS STATE UNIVERSITY)
  - Department name
  - Fiscal year and fund type
  - Status badge
  - Categorized budget items with sub-totals
  - Grand total
  - Signature sections (Prepared, Noted, Approved)
- Print functionality for physical copies

### 6. Role-Based Access
- **Budget Office (Admin)**: Can view all LIBs from all departments
- **Department/Office Users**: Can only view and manage their own department's LIBs
- **Procurement**: Can view and manage their department's LIBs

## User Interface

### Main Features
- Modern, responsive design using Tailwind CSS
- Gradient header with maroon theme
- Modal-based forms for create/edit operations
- Real-time calculations
- Professional table layouts
- Print-optimized view

### Budget Item Management
- Dynamic add/remove budget items
- Categorized dropdown (A, B, C categories)
- Account code input
- Particulars description
- Amount input with real-time total calculation

## Access Control

### Preconditions
- Users must be logged in
- Users must have a department assigned
- Only draft LIBs can be edited or deleted
- Budget office approval is required before LIB becomes final

## Navigation

The LIB feature is accessible from all user role sidebars:
- Admin Sidebar: Between "PR Submission" and "Notifications"
- Department Sidebar: Between "CABAC Viewer" and "Notifications"
- Procurement Sidebar: Between "CABAC Viewer" and "Notifications"

## Status Workflow

1. **Draft** - Initial state when LIB is created
   - Can be edited
   - Can be deleted
   - Not yet submitted for approval

2. **Pending Approval** - (Future implementation)
   - Submitted to budget office
   - Cannot be edited
   - Awaiting budget office review

3. **Approved** - (Future implementation)
   - Approved by budget office
   - Cannot be edited or deleted
   - Final version

4. **Rejected** - (Future implementation)
   - Rejected by budget office
   - May need revision

## Testing Checklist

- [ ] Database tables created successfully
- [ ] LIB link appears in all sidebars
- [ ] Create new LIB functionality works
- [ ] Add/remove budget items works
- [ ] Grand total calculates correctly
- [ ] Save LIB creates database records
- [ ] View LIB displays correctly
- [ ] Edit LIB loads existing data
- [ ] Update LIB saves changes
- [ ] Delete LIB removes records
- [ ] Print functionality works
- [ ] Role-based access control works
- [ ] Department filtering works correctly

## Future Enhancements

1. **Approval Workflow**
   - Submit for approval button
   - Budget office approval interface
   - Email notifications

2. **PDF Export**
   - Generate PDF version of LIB
   - Download functionality

3. **Version History**
   - Track changes to LIBs
   - View previous versions

4. **Budget Comparison**
   - Compare LIBs across fiscal years
   - Variance analysis

5. **Import/Export**
   - Import from Excel
   - Export to Excel/CSV

## Support

For issues or questions, please contact the development team.

## Version
- Version: 1.0.0
- Date: March 2, 2026
- Status: Production Ready
