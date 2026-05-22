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
    $userId = $_SESSION['user_id'];
    $departmentId = $_SESSION['department_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? '';
    
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
    
    $input = json_decode(file_get_contents('php://input'), true);
    $libId = $input['id'] ?? 0;
    
    if (!$libId) {
        echo json_encode(['success' => false, 'message' => 'Invalid LIB ID']);
        exit;
    }
    
    // Verify ownership (temporarily allow deletion of any status)
    $sql = "SELECT * FROM line_item_budgets WHERE id = ? AND department_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$libId, $departmentId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo json_encode(['success' => false, 'message' => 'LIB not found or you do not have permission to delete it']);
        exit;
    }
    
    $db->beginTransaction();
    
    try {
        // Get fiscal year from the LIB
        $fiscalYear = $lib['fiscal_year'];
        
        // Delete ALL utilization entries for this department and fiscal year
        // (since they're synced from LIB, they should be recreated when a new LIB is created)
        // But keep Prior Years data intact
        $deleteUtilizationStmt = $db->prepare("
            DELETE FROM budget_utilization_entries 
            WHERE department_id = ? 
            AND fiscal_year = ?
        ");
        $deleteUtilizationStmt->execute([$departmentId, $fiscalYear]);
        
        // Delete items (due to foreign key)
        $sql = "DELETE FROM line_item_budget_items WHERE lib_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$libId]);
        
        // Delete LIB
        $sql = "DELETE FROM line_item_budgets WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$libId]);
        
        $db->commit();
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Line Item Budget and associated utilization entries deleted successfully',
        'clear_utilization' => true,
        'department_id' => $departmentId,
        'fiscal_year' => $fiscalYear
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error in delete_lib.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
