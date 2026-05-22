<?php
require_once __DIR__ . '/../config/database.php';

class FileSubmission {
    private $conn;
    private $table_name = 'file_submissions';

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Submit a file
     */
    public function submitFile($user_id, $department_id, $submission_type, $fiscal_year, $file_name, $file_path, $file_size, $file_type, $initial_status = null) {
        // If department_id is null, get it from the user's record
        if ($department_id === null) {
            $user_query = "SELECT department_id FROM users WHERE id = :user_id";
            $user_stmt = $this->conn->prepare($user_query);
            $user_stmt->bindParam(':user_id', $user_id);
            $user_stmt->execute();
            $department_id = $user_stmt->fetchColumn();
        }
        
        // If still null, try to get a default department or handle gracefully
        if ($department_id === null) {
            // Try to get the first available department as fallback
            $dept_query = "SELECT id FROM departments LIMIT 1";
            $dept_stmt = $this->conn->prepare($dept_query);
            $dept_stmt->execute();
            $department_id = $dept_stmt->fetchColumn();
        }
        
        // For APP, set status to 'approved' directly; otherwise use provided status or default to 'pending'
        $status = ($submission_type === 'APP') ? 'approved' : ($initial_status ?? 'pending');
        
        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, department_id, submission_type, fiscal_year, file_name, file_path, file_size, file_type, status) 
                  VALUES (:user_id, :department_id, :submission_type, :fiscal_year, :file_name, :file_path, :file_size, :file_type, :status)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':department_id', $department_id);
        $stmt->bindParam(':submission_type', $submission_type);
        $stmt->bindParam(':fiscal_year', $fiscal_year);
        $stmt->bindParam(':file_name', $file_name);
        $stmt->bindParam(':file_path', $file_path);
        $stmt->bindParam(':file_size', $file_size);
        $stmt->bindParam(':file_type', $file_type);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $submission_id = $this->conn->lastInsertId();
            
            // Create notification for budget/admin users
            $this->createSubmissionNotification($submission_id, $user_id, $department_id, $submission_type);
            
            return $submission_id;
        }
        return false;
    }

    /**
     * Create notification for budget/admin users about new submission
     */
    private function createSubmissionNotification($submission_id, $user_id, $department_id, $submission_type) {
        // Get department name
        $dept_query = "SELECT dept_name FROM departments WHERE id = :department_id";
        $dept_stmt = $this->conn->prepare($dept_query);
        $dept_stmt->bindParam(':department_id', $department_id);
        $dept_stmt->execute();
        $department_name = $dept_stmt->fetchColumn() ?: 'Unknown Department';
        
        // Get user name
        $user_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = :user_id";
        $user_stmt = $this->conn->prepare($user_query);
        $user_stmt->bindParam(':user_id', $user_id);
        $user_stmt->execute();
        $user_name = $user_stmt->fetchColumn() ?: 'Unknown User';
        
        // Create notification message
        $timestamp = date('M j, Y g:i A');
        $message = "New {$submission_type} submission from {$user_name} ({$department_name}) on {$timestamp}";
        
        // Get all budget/admin users
        $budget_users_query = "SELECT id FROM users WHERE role_id IN (1, 2) AND is_active = 1";
        $budget_stmt = $this->conn->prepare($budget_users_query);
        $budget_stmt->execute();
        $budget_users = $budget_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Insert notifications for each budget/admin user
        // Note: notifications schema does not include file_id; embed submission id in message instead
        $notification_query = "INSERT INTO notifications (user_id, title, message, type, is_read, created_at) 
                             VALUES (:user_id, :title, :message, :type, 0, NOW())";
        $notification_stmt = $this->conn->prepare($notification_query);
        
        $title = "New File Submission";
        // Use a valid enum value based on schema (info|warning|success|error)
        $type = 'info';
        
        foreach ($budget_users as $budget_user_id) {
            $notification_stmt->bindParam(':user_id', $budget_user_id);
            $notification_stmt->bindParam(':title', $title);
            // Include submission id in the message for traceability
            $fullMessage = $message . " (Submission ID: " . $submission_id . ")";
            $notification_stmt->bindParam(':message', $fullMessage);
            $notification_stmt->bindParam(':type', $type);
            $notification_stmt->execute();
        }
    }

    /**
     * Get submissions by user
     * Excludes submissions that have been removed by the user
     */
    public function getUserSubmissions($user_id, $limit = 10) {
        $query = "SELECT fs.*, d.dept_name, u.first_name, u.last_name
                  FROM " . $this->table_name . " fs
                  LEFT JOIN departments d ON fs.department_id = d.id
                  LEFT JOIN users u ON fs.user_id = u.id
                  WHERE fs.user_id = :user_id 
                  AND (fs.removed_by_user_at IS NULL OR fs.removed_by_user_at = '')
                  ORDER BY fs.submitted_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all submissions for admin view
     */
    public function getAllSubmissions($limit = 20) {
        $query = "SELECT fs.*, d.dept_name, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " fs
                  LEFT JOIN departments d ON fs.department_id = d.id
                  LEFT JOIN users u ON fs.user_id = u.id
                  ORDER BY fs.submitted_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get submissions with optional filters regardless of status
     */
    public function getFilteredSubmissions($fiscal_year = null, $submission_type = null, $department_id = null, $limit = 200) {
        $query = "SELECT fs.*, d.dept_name, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " fs
                  LEFT JOIN departments d ON fs.department_id = d.id
                  LEFT JOIN users u ON fs.user_id = u.id
                  WHERE 1=1";

        $params = [];

        if (!empty($fiscal_year)) {
            $query .= " AND fs.fiscal_year = :fiscal_year";
            $params[':fiscal_year'] = $fiscal_year;
        }

        if (!empty($submission_type)) {
            $query .= " AND fs.submission_type = :submission_type";
            $params[':submission_type'] = $submission_type;
        }

        if (!empty($department_id)) {
            $query .= " AND fs.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }

        $query .= " ORDER BY fs.submitted_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get the latest submission of a specific type for a user
     * Excludes submissions that have been removed by the user (for regular users)
     * Budget/admin users can see all submissions including removed ones
     */
    public function getLatestSubmissionByType(int $user_id, string $submission_type, bool $include_removed = false) {
        $query = "SELECT fs.*, d.dept_name
                  FROM " . $this->table_name . " fs
                  LEFT JOIN departments d ON fs.department_id = d.id
                  WHERE fs.user_id = :user_id AND fs.submission_type = :submission_type";
        
        // Exclude removed files unless explicitly requested (for budget/admin views)
        if (!$include_removed) {
            $query .= " AND (fs.removed_by_user_at IS NULL OR fs.removed_by_user_at = '')";
        }
        
        $query .= " ORDER BY fs.submitted_at DESC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':submission_type', $submission_type, PDO::PARAM_STR);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Update an existing submission's file metadata
     */
    public function updateSubmissionFile(int $submission_id, string $file_name, string $file_path, int $file_size, string $file_type, int $fiscal_year, ?string $preserve_status = null) {
        // For APP, preserve 'approved' status; for others, reset to 'pending'
        $status = ($preserve_status === 'approved') ? 'approved' : 'pending';
        
        $query = "UPDATE " . $this->table_name . "
                  SET file_name = :file_name,
                      file_path = :file_path,
                      file_size = :file_size,
                      file_type = :file_type,
                      fiscal_year = :fiscal_year,
                      status = :status,
                      submitted_at = NOW()";
        
        // Only reset review fields if status is being reset to pending
        if ($status === 'pending') {
            $query .= ", reviewed_at = NULL, reviewed_by = NULL, review_notes = NULL";
        }
        
        $query .= " WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':file_name', $file_name);
        $stmt->bindParam(':file_path', $file_path);
        $stmt->bindParam(':file_size', $file_size);
        $stmt->bindParam(':file_type', $file_type);
        $stmt->bindParam(':status', $status);
        $stmt->bindValue(':fiscal_year', $fiscal_year, PDO::PARAM_INT);
        $stmt->bindValue(':id', $submission_id, PDO::PARAM_INT);

        return $stmt->execute();
    }
    
    /**
     * Update submission status directly
     */
    public function setSubmissionStatus(int $submission_id, string $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindValue(':id', $submission_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Delete a submission belonging to a specific user
     */
    public function deleteSubmissionByOwner(int $submission_id, int $user_id): bool {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $submission_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Mark a submission as removed by user (soft delete)
     * This keeps the record in the database for budget office but hides it from the user
     */
    public function markAsRemovedByUser(int $submission_id, int $user_id): bool {
        // Verify the submission belongs to the user
        $checkQuery = "SELECT id FROM " . $this->table_name . " WHERE id = :id AND user_id = :user_id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindValue(':id', $submission_id, PDO::PARAM_INT);
        $checkStmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() === 0) {
            return false; // Submission doesn't belong to this user
        }
        
        // Mark as removed (soft delete)
        $query = "UPDATE " . $this->table_name . " 
                  SET removed_by_user_at = NOW() 
                  WHERE id = :id AND user_id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id', $submission_id, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        return $stmt->execute();
    }

    /**
     * Get approved submissions with filters
     */
    public function getApprovedSubmissions($fiscal_year = null, $submission_type = null, $department_id = null) {
        $query = "SELECT fs.*, d.dept_name, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " fs
                  LEFT JOIN departments d ON fs.department_id = d.id
                  LEFT JOIN users u ON fs.user_id = u.id
                  WHERE fs.status = 'approved'";
        
        $params = [];
        
        if ($fiscal_year) {
            $query .= " AND fs.fiscal_year = :fiscal_year";
            $params[':fiscal_year'] = $fiscal_year;
        }
        
        if ($submission_type) {
            $query .= " AND fs.submission_type = :submission_type";
            $params[':submission_type'] = $submission_type;
        }
        
        if ($department_id) {
            $query .= " AND fs.department_id = :department_id";
            $params[':department_id'] = $department_id;
        }
        
        $query .= " ORDER BY fs.submitted_at DESC";

        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getSubmissionCountsByType(array $types = ['PPMP', 'LIB', 'APP']): array {
        if (empty($types)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($types), '?'));
        $query = "SELECT submission_type, COUNT(*) AS count
                  FROM " . $this->table_name . "
                  WHERE submission_type IN ($placeholders)
                  GROUP BY submission_type";

        $stmt = $this->conn->prepare($query);
        foreach ($types as $index => $type) {
            $stmt->bindValue($index + 1, $type, PDO::PARAM_STR);
        }
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $counts = [];
        foreach ($result as $row) {
            $counts[$row['submission_type']] = (int)$row['count'];
        }

        return $counts;
    }

    /**
     * Get pending submissions count
     */
    public function getPendingCount() {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE status = 'pending'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }


    /**
     * Check if a user has any non-approved (pending/not approved) submissions
     * Optionally restricted to a fiscal year. Returns true if any PPMP or LIB
     * submission exists with status other than 'approved'.
     */
    public function userHasOpenSubmission(int $user_id, ?int $fiscal_year = null): bool {
        // Only block when there is a PENDING submission (rejected is allowed to resubmit)
        $query = "SELECT COUNT(*) AS c FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                    AND submission_type IN ('PPMP','LIB') 
                    AND status = 'pending'";
        $params = [':user_id' => $user_id];
        if ($fiscal_year !== null) {
            $query .= " AND fiscal_year = :fy";
            $params[':fy'] = $fiscal_year;
        }
        $stmt = $this->conn->prepare($query);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return isset($row['c']) ? ((int)$row['c'] > 0) : false;
    }


    /**
     * Update submission status
     */
    public function updateStatus($submission_id, $status, $reviewed_by, $review_notes = null) {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = :status, reviewed_at = NOW(), reviewed_by = :reviewed_by, review_notes = :review_notes
                  WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':reviewed_by', $reviewed_by);
        $stmt->bindParam(':review_notes', $review_notes);
        $stmt->bindParam(':id', $submission_id);

        $ok = $stmt->execute();
        if (!$ok) { return false; }

        // Notify the submitter about the decision
        try {
            // Fetch submission info to get the submitter
            $infoStmt = $this->conn->prepare("SELECT user_id, submission_type, file_name FROM " . $this->table_name . " WHERE id = :id");
            $infoStmt->bindParam(':id', $submission_id);
            $infoStmt->execute();
            $info = $infoStmt->fetch(PDO::FETCH_ASSOC);
            if ($info && isset($info['user_id'])) {
                require_once __DIR__ . '/Notification.php';
                $notification = new Notification();
                $type = 'info';
                if ($status === 'approved') { $type = 'success'; }
                elseif ($status === 'rejected') { $type = 'error'; }
                $title = $info['submission_type'] . ' ' . ucfirst($status);
                $message = ($info['submission_type'] ?: 'Submission') . " '" . ($info['file_name'] ?: '') . "' has been " . $status . ".";
                if (!empty($review_notes)) { $message .= " Notes: " . $review_notes; }
                // Create notification for the submitter
                $notification->createNotification((int)$info['user_id'], $title, $message, $type);
            }
        } catch (Exception $e) {
            // swallow notification errors to avoid blocking status update
        }

        return true;
    }

    /**
     * Get departments for filter dropdown
     */
    public function getDepartments() {
        $query = "SELECT id, dept_name FROM departments ORDER BY dept_name";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get fiscal years for filter dropdown
     */
    public function getFiscalYears() {
        $query = "SELECT DISTINCT fiscal_year FROM " . $this->table_name . " ORDER BY fiscal_year DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Format file size
     */
    public function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
?>
