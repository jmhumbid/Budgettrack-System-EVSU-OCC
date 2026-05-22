<?php
session_start();

// Check if user is logged in and has budget role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $conn = getDB();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid data received');
    }
    
    $departmentId = $data['department_id'] ?? null;
    $draftData = json_encode($data['draft_data'] ?? []);
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$departmentId) {
        throw new Exception('Department ID is required');
    }
    
    // Check if table exists, create if not
    $checkTable = $conn->query("SHOW TABLES LIKE 'allocation_drafts'");
    if ($checkTable->rowCount() == 0) {
        $createTable = "
        CREATE TABLE `allocation_drafts` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `department_id` int(11) NOT NULL,
          `draft_data` longtext NOT NULL,
          `updated_by` int(11) DEFAULT NULL,
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_dept_draft` (`department_id`),
          KEY `department_id` (`department_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $conn->exec($createTable);
    }
    
    // Insert or update draft
    $stmt = $conn->prepare("
        INSERT INTO allocation_drafts (department_id, draft_data, updated_by, updated_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
        ON DUPLICATE KEY UPDATE
        draft_data = VALUES(draft_data),
        updated_by = VALUES(updated_by),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    $stmt->execute([$departmentId, $draftData, $userId]);
    
    // Get the updated timestamp
    $timestampStmt = $conn->prepare("SELECT updated_at FROM allocation_drafts WHERE department_id = ?");
    $timestampStmt->execute([$departmentId]);
    $result = $timestampStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Draft saved successfully',
        'updated_at' => $result['updated_at'] ?? null
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
