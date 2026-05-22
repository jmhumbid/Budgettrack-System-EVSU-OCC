# LIB Finalization Notification Feature

## Overview
When a LIB is finalized, all Budget Office users are automatically notified so they can begin utilization tracking.

## Feature Details

### Who Gets Notified
- **All users with "budget" role**
- Only active users (is_active = 1)
- Notifications appear in their notification bell

### When Notification is Sent
- **Trigger**: When a department user finalizes their LIB
- **Timing**: Immediately after successful finalization
- **Condition**: Only if finalization succeeds (after database commit)

### Notification Content

**Title:**
```
LIB Finalized - [Department Name]
```

**Message:**
```
[User Name] from [Department Name] has finalized their Line-Item Budget (LIB) 
for [Fiscal Year]. The budget is now available for utilization tracking.
```

**Type:** `success` (green notification)

### Example Notification

```
Title: LIB Finalized - Computer Studies Department

Message: John Doe from Computer Studies Department has finalized their 
Line-Item Budget (LIB) for FY 2026. The budget is now available for 
utilization tracking.

Type: Success (green)
```

## Implementation

### File Modified
`api/finalize_lib.php`

### Code Added

```php
// Send notification to Budget Office users
try {
    $notification = new Notification();
    
    // Get department name
    $deptName = $lib['dept_name'] ?? 'Unknown Department';
    
    // Get user name
    $userStmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
    $userStmt->execute([$userId]);
    $userRow = $userStmt->fetch(PDO::FETCH_ASSOC);
    $userName = $userRow ? $userRow['full_name'] : 'Unknown User';
    
    // Create notification for budget office users
    $title = "LIB Finalized - {$deptName}";
    $message = "{$userName} from {$deptName} has finalized their Line-Item Budget (LIB) for {$fiscalYear}. The budget is now available for utilization tracking.";
    
    // Notify all budget role users
    $notification->notifyUsersByRoles(['budget'], $title, $message, 'success');
    
} catch (Exception $e) {
    // Log notification error but don't fail the finalization
    error_log("Error sending LIB finalization notification: " . $e->getMessage());
}
```

### Error Handling
- Notification errors are caught and logged
- **Finalization still succeeds** even if notification fails
- This ensures LIB finalization is not blocked by notification issues

## User Flow

### Department User Perspective

```
1. User finalizes LIB
2. System validates PPMP
3. System finalizes LIB
4. System syncs to utilization
5. System sends notifications
6. ✅ Success message shown
```

### Budget Office User Perspective

```
1. Notification bell shows new notification
2. Click bell to see notification
3. See: "LIB Finalized - Computer Studies Department"
4. Click notification to view details
5. Can now track utilization for that department
```

## Notification Details

### Database Table
Table: `notifications`

### Columns Used
- `user_id` - Budget office user ID
- `title` - "LIB Finalized - [Department]"
- `message` - Full notification message
- `type` - 'success'
- `is_read` - 0 (unread)
- `created_at` - Current timestamp

### Query Used
```php
$notification->notifyUsersByRoles(['budget'], $title, $message, 'success');
```

This method:
1. Finds all users with 'budget' role
2. Filters to active users only
3. Creates notification for each user
4. Returns array of notified user IDs

## Benefits

1. **Immediate Awareness**: Budget office knows when LIBs are finalized
2. **No Manual Checking**: Don't need to constantly check for new LIBs
3. **Audit Trail**: Notifications are logged with timestamps
4. **User-Friendly**: Clear, informative messages
5. **Non-Blocking**: Notification errors don't prevent finalization

## Testing

### Test Case 1: Successful Notification
1. Create LIB as department user
2. Finalize LIB
3. Login as budget office user
4. Check notification bell
5. **Expected**: See "LIB Finalized" notification

### Test Case 2: Multiple Budget Users
1. Create 3 budget office users
2. Finalize LIB as department user
3. Login as each budget user
4. **Expected**: All 3 users see the notification

### Test Case 3: Notification Failure
1. Temporarily break notification system
2. Finalize LIB
3. **Expected**: LIB still finalizes successfully
4. **Expected**: Error logged but not shown to user

### Test Case 4: Inactive Budget User
1. Create budget user with is_active = 0
2. Finalize LIB
3. **Expected**: Inactive user does NOT receive notification

## Related Features

- **LIB Finalization**: Main feature that triggers notification
- **PPMP Validation**: Must pass before notification is sent
- **Utilization Sync**: Happens before notification
- **Notification System**: Existing notification infrastructure

## Notification Class Methods Used

### `notifyUsersByRoles(array $roles, $title, $message, $type = 'info')`
- **Purpose**: Notify all users with specific roles
- **Parameters**:
  - `$roles`: Array of role names (e.g., ['budget'])
  - `$title`: Notification title
  - `$message`: Notification message
  - `$type`: Notification type ('info', 'success', 'warning', 'error')
- **Returns**: Array of notified user IDs

### `getUserIdsByRoles(array $roles)`
- **Purpose**: Get all user IDs with specific roles
- **Parameters**: Array of role names
- **Returns**: Array of user IDs
- **Filters**: Only active users (is_active = 1)

## Future Enhancements

Possible improvements:
1. Email notification option
2. SMS notification for urgent items
3. Notification preferences per user
4. Batch notification summary
5. Notification history/archive

## Status

✅ **IMPLEMENTED** - Notifications are sent on LIB finalization

## Implementation Date
April 14, 2026
