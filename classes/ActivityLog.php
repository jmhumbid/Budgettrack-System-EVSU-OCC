<?php
require_once __DIR__ . '/../config/database.php';

class ActivityLog {
    private $conn;
    private $table_name = 'activity_logs';

    public function __construct() {
        $this->conn = getDB();
    }

    /**
     * Log user activity
     */
    public function logActivity($user_id, $action, $description = null, $module = null) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';

        $query = "INSERT INTO " . $this->table_name . " 
                  (user_id, action, description, module, ip_address, user_agent) 
                  VALUES (:user_id, :action, :description, :module, :ip_address, :user_agent)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':action', $action);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':module', $module);
        $stmt->bindParam(':ip_address', $ip_address);
        $stmt->bindParam(':user_agent', $user_agent);

        return $stmt->execute();
    }

    /**
     * Get recent activities
     */
    public function getRecentActivities($limit = 10) {
        $query = "SELECT al.*, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " al
                  LEFT JOIN users u ON al.user_id = u.id
                  ORDER BY al.created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activities for a specific user
     */
    public function getUserActivities($user_id, $limit = 20) {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE user_id = :user_id 
                  ORDER BY created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activities by module
     */
    public function getModuleActivities($module, $limit = 20) {
        $query = "SELECT al.*, u.first_name, u.last_name, u.email
                  FROM " . $this->table_name . " al
                  LEFT JOIN users u ON al.user_id = u.id
                  WHERE al.module = :module
                  ORDER BY al.created_at DESC LIMIT :limit";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':module', $module);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats($days = 30) {
        $query = "SELECT 
                    DATE(created_at) as activity_date,
                    COUNT(*) as activity_count,
                    COUNT(DISTINCT user_id) as unique_users
                  FROM " . $this->table_name . " 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)
                  GROUP BY DATE(created_at)
                  ORDER BY activity_date DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':days', $days, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
