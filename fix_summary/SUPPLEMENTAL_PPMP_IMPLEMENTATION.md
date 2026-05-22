# Supplemental PPMP Implementation Plan

## Overview
Implement a Supplemental PPMP feature that works alongside the regular PPMP, allowing departments to create supplemental procurement plans with the same format as PPMP.

## Database Changes

### 1. Add `ppmp_type` column to `ppmp` table
```sql
ALTER TABLE ppmp 
ADD COLUMN ppmp_type ENUM('ppmp', 'supplemental') DEFAULT 'ppmp' AFTER ppmp_number;
```

## User Flow

### For Department/Office Users (pages/ppmp.php)
1. **Tab Structure**: PPMP | Supplemental | Draft
   - PPMP tab: Shows regular PPMP entries
   - Supplemental tab: Shows supplemental PPMP entries (appears when user creates first supplemental)
   - Draft tab: Filter by type (PPMP or Supplemental)

2. **Create Supplemental**:
   - Same form as PPMP
   - Marked as `ppmp_type = 'supplemental'`
   - Can be saved as Draft or Final

### For Budget Office (pages/ppmp_view.php)
1. **Tab Structure**: PPMP | LIB | Supplemental
   - Supplemental tab: View all departments' supplemental PPMPs
   - Only appears when at least one department has a Final supplemental

### For Purchase Request Creation (pages/utilization.php)
1. **Select PPMP Items Modal**:
   - Tab Structure: PPMP | Supplemental
   - Supplemental tab only appears if department has Final supplemental
   - Can select items from either PPMP or Supplemental

## Files to Modify

### Database
- [x] `database/supplemental_ppmp.sql` - Migration script
- [x] `install_supplemental_ppmp.php` - Installation script

### API Endpoints (Need to update to support ppmp_type)
- [ ] `api/create_ppmp.php` - Add ppmp_type parameter
- [ ] `api/update_ppmp.php` - Handle ppmp_type
- [ ] `api/get_ppmp_list.php` - Filter by ppmp_type
- [ ] `api/get_ppmp_details.php` - Include ppmp_type
- [ ] `api/get_ppmp_items_for_pr.php` - Filter by ppmp_type
- [ ] `api/load_ppmp_draft.php` - Filter by ppmp_type

### Pages
- [ ] `pages/ppmp.php` - Add Supplemental tab
- [ ] `pages/ppmp_view.php` - Add Supplemental tab
- [ ] `pages/utilization.php` - Add Supplemental tab in PPMP selection modal

### JavaScript
- [ ] `assets/js/ppmp.js` - Update to handle ppmp_type

## Implementation Steps

1. Run database migration: `install_supplemental_ppmp.php`
2. Update API endpoints to support `ppmp_type`
3. Update `pages/ppmp.php` to add Supplemental tab
4. Update `pages/ppmp_view.php` to add Supplemental tab
5. Update `pages/utilization.php` PPMP selection modal
6. Test all flows

## Key Features

- Supplemental PPMP uses same format as regular PPMP
- Separate tabs for easy navigation
- Draft filter can distinguish between PPMP and Supplemental
- Budget office can view all supplementals in one place
- Purchase requests can link to either PPMP or Supplemental items
