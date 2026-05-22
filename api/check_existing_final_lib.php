<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $departmentId = $_SESSION['department_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    $userId = $_SESSION['user_id'];
    
    if (!$departmentId && $userRole === 'budget') {
        $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['department_id']) {
            $departmentId = $row['department_id'];
        } else {
            $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $departmentId = $row['department_id'];
        }
    }
    
    $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
    $excludeLibId = $_GET['exclude_lib_id'] ?? null; // Exclude current LIB being edited
    
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'No department assigned']);
        exit;
    }
    
    // Check if there are any existing FINAL LIBs for this department/fiscal year
    // Exclude the current LIB being edited (if any)
    $sql = "SELECT COUNT(*) as count FROM line_item_budgets WHERE department_id = ? AND fiscal_year = ? AND status = 'approved'";
    $params = [$departmentId, $fiscalYear];
    
    if ($excludeLibId) {
        $sql .= " AND id != ?";
        $params[] = $excludeLibId;
    }
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $hasExistingFinalLib = $result['count'] > 0;
    
    echo json_encode([
        'success' => true,
        'has_existing_final_lib' => $hasExistingFinalLib,
        'count' => $result['count'],
        'debug' => [
            'department_id' => $departmentId,
            'fiscal_year' => $fiscalYear,
            'exclude_lib_id' => $excludeLibId,
            'sql' => $sql,
            'params' => $params
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error checking existing LIBs: ' . $e->getMessage()
    ]);
}
?>