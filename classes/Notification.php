<?php
require_once __DIR__ . '/../config/database.php';

class Notification {
    private $conn;
    private $table_name = 'notifications';

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Get notifications for a user
     */
    public function getUserNotifications($user_id, $limit = 10, $unread_only = false) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id";
        
        if ($unread_only) {
            $query .= " AND is_read = FALSE";
        }
        
        $query .= " ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all notifications (for admin)
     */
    public function getAllNotifications($limit = 20) {
        $query = "SELECT n.*, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " n
                  LEFT JOIN users u ON n.user_id = u.id
                  ORDER BY n.created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new notification
     */
    public function createNotification($user_id, $title, $message, $type = 'info') {
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, title, message, type) 
                  VALUES (:user_id, :title, :message, :type)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':message', $message);
        $stmt->bindParam(':type', $type);

        return $stmt->execute();
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = TRUE, read_at = NOW() 
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $notification_id);

        return $stmt->execute();
    }

    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                  WHERE user_id = :user_id AND is_read = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET is_read = TRUE, read_at = NOW() 
                  WHERE user_id = :user_id AND is_read = FALSE";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    /**
     * Delete all notifications for a user
     */
    public function deleteAllForUser($user_id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE user_id = :user_id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);

        return $stmt->execute();
    }

    /**
     * Notify all budget/admin users about a PPMP or LIB update
     * @param string $submission_type PPMP or LIB
     * @param int $submitting_user_id The user who submitted/updated the file
     * @param string $department_name The name of the department
     * @param bool $is_update Whether this is an update (true) or new submission (false)
     */
    public function notifyBudgetAdmins($submission_type, $submitting_user_id, $department_name, $is_update = false) {
        // Get submitting user's name and role
        $user_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name, role_id FROM users WHERE id = :user_id";
        $user_stmt = $this->conn->prepare($user_query);
        $user_stmt->bindParam(':user_id', $submitting_user_id);
        $user_stmt->execute();
        $user_row = $user_stmt->fetch(PDO::FETCH_ASSOC);
        $user_name = $user_row ? $user_row['full_name'] : 'Unknown User';
        
        // Check if submitter is a budget role user
        $role_query = "SELECT role_name FROM roles WHERE id = :role_id";
        $role_stmt = $this->conn->prepare($role_query);
        $role_stmt->bindParam(':role_id', $user_row['role_id']);
        $role_stmt->execute();
        $submitter_role = $role_stmt->fetchColumn();
        $submitter_is_budget = ($submitter_role === 'budget');
        
        // Get all budget/admin users (by role name)
        $budget_users_query = "SELECT u.id FROM users u 
                              INNER JOIN roles r ON u.role_id = r.id 
                              WHERE r.role_name IN ('budget', 'school_admin', 'procurement') AND u.is_active = 1";
        $budget_stmt = $this->conn->prepare($budget_users_query);
        $budget_stmt->execute();
        $budget_users = $budget_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($budget_users)) {
            return false;
        }
        
        // Create custom message based on submission type and action
        $action = $is_update ? 'updated' : 'submitted';
        $title = $submission_type . ' ' . ucfirst($action);
        
        if ($submission_type === 'PPMP') {
            $message = $is_update 
                ? "{$user_name} from {$department_name} has updated their Project Procurement Management Plan (PPMP). Please review the changes."
                : "{$user_name} from {$department_name} has submitted a new Project Procurement Management Plan (PPMP). Please review it.";
        } elseif ($submission_type === 'LIB') {
            $message = $is_update
                ? "{$user_name} from {$department_name} has updated their Line-Item Budget (LIB). Please review the changes."
                : "{$user_name} from {$department_name} has submitted a new Line-Item Budget (LIB). Please review it.";
        } else {
            $message = "{$user_name} from {$department_name} has {$action} a {$submission_type} file.";
        }
        
        // Add timestamp
        $message .= " (" . date('M j, Y g:i A') . ")";
        
        // Insert notifications for each budget/admin user
        // Skip notifying the submitter themselves if they are a budget role user
        $notification_query = "INSERT INTO " . $this->table_name . " 
                               (user_id, title, message, type, is_read, created_at) 
                               VALUES (:user_id, :title, :message, :type, 0, NOW())";
        $notification_stmt = $this->conn->prepare($notification_query);
        
        $type = 'info';
        
        $success_count = 0;
        foreach ($budget_users as $budget_user_id) {
            // Skip notifying the submitter themselves (budget users don't need to review their own submissions)
            if ($submitter_is_budget && $budget_user_id == $submitting_user_id) {
                continue;
            }
            
            $notification_stmt->bindParam(':user_id', $budget_user_id);
            $notification_stmt->bindParam(':title', $title);
            $notification_stmt->bindParam(':message', $message);
            $notification_stmt->bindParam(':type', $type);
            
            if ($notification_stmt->execute()) {
                $success_count++;
            }
        }
        
        return $success_count > 0;
    }

    public function getUserIdsByRoles(array $roles): array {
        $roles = array_filter(array_map('trim', $roles));
        if (empty($roles)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($roles), '?'));
        $query = "SELECT DISTINCT u.id FROM users u 
                  INNER JOIN roles r ON u.role_id = r.id 
                  WHERE r.role_name IN ($placeholders) AND u.is_active = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute($roles);

        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function notifyUsersByRoles(array $roles, $title, $message, $type = 'info'): array {
        $userIds = $this->getUserIdsByRoles($roles);

        if (empty($userIds)) {
            return [];
        }

        $notification_query = "INSERT INTO " . $this->table_name . " 
                               (user_id, title, message, type, is_read, created_at) 
                               VALUES (:user_id, :title, :message, :type, 0, NOW())";
        $notification_stmt = $this->conn->prepare($notification_query);

        foreach ($userIds as $userId) {
            $notification_stmt->bindParam(':user_id', $userId);
            $notification_stmt->bindParam(':title', $title);
            $notification_stmt->bindParam(':message', $message);
            $notification_stmt->bindParam(':type', $type);
            $notification_stmt->execute();
        }

        return $userIds;
    }

    public function notifyAllActiveUsers($title, $message, $type = 'info'): array {
        $user_query = "SELECT id FROM users WHERE is_active = 1";
        $stmt = $this->conn->prepare($user_query);
        $stmt->execute();
        $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($userIds)) {
            return [];
        }

        $notification_query = "INSERT INTO " . $this->table_name . " 
                               (user_id, title, message, type, is_read, created_at) 
                               VALUES (:user_id, :title, :message, :type, 0, NOW())";
        $notification_stmt = $this->conn->prepare($notification_query);

        foreach ($userIds as $userId) {
            $notification_stmt->bindParam(':user_id', $userId);
            $notification_stmt->bindParam(':title', $title);
            $notification_stmt->bindParam(':message', $message);
            $notification_stmt->bindParam(':type', $type);
            $notification_stmt->execute();
        }

        return $userIds;
    }
}
?>
