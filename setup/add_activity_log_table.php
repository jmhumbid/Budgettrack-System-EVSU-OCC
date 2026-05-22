<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDB();
    
    // Check if table already exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'user_activity_log'");
    if ($checkTable->rowCount() > 0) {
        echo "Table 'user_activity_log' already exists.\n";
        exit;
    }
    
    // Create the user_activity_log table
    $createTable = "CREATE TABLE user_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type ENUM('login', 'logout', 'password_change', 'profile_update') NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        activity_details JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_user_activity (user_id, created_at),
        INDEX idx_activity_type (activity_type, created_at)
    )";
    
    $conn->exec($createTable);
    echo "Table 'user_activity_log' created successfully!\n";
    
    // Insert some sample activity data for existing users
    $sampleActivities = [
        ['user_id' => 1, 'activity_type' => 'login', 'ip_address' => '192.168.1.100'],
        ['user_id' => 2, 'activity_type' => 'login', 'ip_address' => '192.168.1.101'],
        ['user_id' => 3, 'activity_type' => 'login', 'ip_address' => '192.168.1.102'],
    ];
    
    $insertStmt = $conn->prepare("INSERT INTO user_activity_log (user_id, activity_type, ip_address, activity_details) VALUES (?, ?, ?, ?)");
    
    foreach ($sampleActivities as $activity) {
        $details = json_encode(['timestamp' => date('Y-m-d H:i:s'), 'action' => 'user_' . $activity['activity_type']]);
        $insertStmt->execute([
            $activity['user_id'],
            $activity['activity_type'],
            $activity['ip_address'],
            $details
        ]);
    }
    
    echo "Sample activity data inserted successfully!\n";
    echo "Recent Activity functionality is now ready to use.\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
