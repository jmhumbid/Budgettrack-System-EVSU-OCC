# LIB View Status Fix

## Issue
The LIB tab in `ppmp_view.php` was showing "No LIBs Found" even when departments had approved LIBs. Budget office users with the 'budget' role could not see any LIBs.

## Root Cause
The API endpoint `api/get_lib_list.php` was filtering for `status = 'final'`, but the database schema defines the status column as an ENUM with values:
- `'draft'`
- `'pending_approval'`
- `'approved'`
- `'rejected'`

The value `'final'` does not exist in the ENUM, so the query returned zero results.

## Solution

### 1. Fixed API Endpoint (`api/get_lib_list.php`)
Changed the status filter from `'final'` to `'approved'`:

```php
if ($requestedDepartmentId && (in_array($userRole, ['budget', 'school_admin']) || $isAdminDepartment)) {
    // Admin/Budget office can view any department's LIBs, but only APPROVED ones
    $departmentId = $requestedDepartmentId;
    $statusFilter = 'approved'; // Changed from 'final' to 'approved'
}
```

### 2. Added Admin Department Support
Added logic to recognize users from departments with "admin" in the name:

```php
// Check if user is from Admin department
$isAdminDepartment = false;
if ($sessionDepartmentId) {
    $stmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
    $stmt->execute([$sessionDepartmentId]);
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dept && stripos($dept['dept_name'], 'admin') !== false) {
        $isAdminDepartment = true;
    }
}
```

### 3. Updated Display Functions (`pages/ppmp_view.php`)
Updated the `displayLIBs()` and `generateLIBViewHTML()` functions to use the correct status values:

```javascript
const statusColors = {
    'draft': 'bg-gray-100 text-gray-800',
    'pending_approval': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800'
};

const statusLabels = {
    'draft': 'DRAFT',
    'pending_approval': 'PENDING',
    'approved': 'APPROVED',
    'rejected': 'REJECTED'
};
```

### 4. Added Debug Logging
Added console logging and API debug information to help troubleshoot future issues:

```javascript
console.log('Loading LIBs for department:', departmentId);
console.log('LIB API Response:', data);
if (data.debug) {
    console.log('Debug info:', data.debug);
}
```

## Testing
Use the test script `test_lib_query.php` to verify LIB queries:
```
http://localhost/budgettrack/test_lib_query.php?dept_id=1
```

## Result
- Budget office users can now see approved LIBs from all departments
- Admin department users can also view approved LIBs
- Department users can see all their own LIBs (draft and approved)
- Status badges display correctly (DRAFT, PENDING, APPROVED, REJECTED)

## Files Modified
1. `api/get_lib_list.php` - Fixed status filter and added admin department support
2. `pages/ppmp_view.php` - Updated status display logic
3. `test_lib_query.php` - Created for debugging (can be deleted after testing)
