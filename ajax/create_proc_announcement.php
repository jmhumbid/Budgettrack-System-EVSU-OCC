<?php
session_start();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'procurement') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Department.php';

header('Content-Type: application/json');

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$title = trim($input['title'] ?? '');
$priority = trim($input['priority'] ?? '');
$content = trim($input['content'] ?? '');
$departmentIds = $input['departments'] ?? [];

// Validation
if (empty($title) || empty($priority) || empty($content)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

if (empty($departmentIds) || !is_array($departmentIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please select at least one department']);
    exit;
}

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    // Create announcements table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_by (created_by),
        INDEX idx_created_at (created_at)
    )");
    
    // Add foreign key constraint if it doesn't exist (with ON DELETE SET NULL if column allows NULL, otherwise handle in code)
    try {
        $checkFk = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'announcements' 
                               AND COLUMN_NAME = 'created_by' 
                               AND REFERENCED_TABLE_NAME = 'users'");
        if ($checkFk->rowCount() == 0) {
            // Try to add constraint with SET NULL (will fail if created_by is NOT NULL, but that's handled in User::hardDelete)
            try {
                $db->exec("ALTER TABLE announcements ADD CONSTRAINT fk_announcements_created_by 
                          FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
            } catch (PDOException $e) {
                // If created_by is NOT NULL, use RESTRICT (handled in User::hardDelete)
                try {
                    $db->exec("ALTER TABLE announcements ADD CONSTRAINT fk_announcements_created_by 
                              FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT");
                } catch (PDOException $e2) {
                    // Constraint might already exist, ignore
                }
            }
        }
    } catch (PDOException $e) {
        // Ignore if information_schema query fails
    }
    
    // Create announcement_departments table to track which departments receive each announcement
    $db->exec("CREATE TABLE IF NOT EXISTS announcement_departments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        announcement_id INT NOT NULL,
        department_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
        FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
        UNIQUE KEY unique_announcement_department (announcement_id, department_id),
        INDEX idx_announcement_id (announcement_id),
        INDEX idx_department_id (department_id)
    )");
    
    // Insert announcement
    $stmt = $db->prepare("INSERT INTO announcements (title, content, priority, created_by) VALUES (:title, :content, :priority, :created_by)");
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':priority', $priority);
    $stmt->bindParam(':created_by', $userId);
    $stmt->execute();
    
    $announcementId = $db->lastInsertId();
    
    // Insert department associations
    $deptStmt = $db->prepare("INSERT INTO announcement_departments (announcement_id, department_id) VALUES (:announcement_id, :department_id)");
    $deptStmt->bindParam(':announcement_id', $announcementId);
    
    $department = new Department();
    $notification = new Notification();
    
    // Send notifications to users in selected departments
    $notifiedCount = 0;
    foreach ($departmentIds as $deptId) {
        $deptId = (int)$deptId;
        
        // Insert into announcement_departments
        $deptStmt->bindParam(':department_id', $deptId);
        $deptStmt->execute();
        
        // Get all users in this department
        $userStmt = $db->prepare("SELECT id FROM users WHERE department_id = :dept_id AND is_active = 1");
        $userStmt->bindParam(':dept_id', $deptId);
        $userStmt->execute();
        $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Get department name for notification
        $deptInfo = $department->getDepartmentById($deptId);
        $deptName = $deptInfo ? $deptInfo['dept_name'] : 'Department';
        
        // Create notification for each user
        foreach ($userIds as $targetUserId) {
            $notificationTitle = 'New Announcement: ' . $title;
            $notificationMessage = $content . "\n\nPriority: " . ucfirst($priority);
            
            $notification->createNotification($targetUserId, $notificationTitle, $notificationMessage, 'info');
            $notifiedCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Announcement published successfully. Notifications sent to {$notifiedCount} users.",
        'announcement_id' => $announcementId
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

