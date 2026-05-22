<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    
    $input = json_decode(file_get_contents('php://input'), true);
    $departmentId = $input['department_id'] ?? null;
    
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    try {
        $deletedTables = [];
        
        // Delete all budget allocations for this department (MAIN TABLE)
        try {
            $stmt = $db->prepare("DELETE FROM budget_allocations WHERE department_id = ?");
            $stmt->execute([$departmentId]);
            $deletedTables[] = 'budget_allocations';
        } catch (Exception $e) {
            // Log but continue
        }
        
        // Try to delete allocation history if table exists
        try {
            $stmt = $db->prepare("DELETE FROM allocation_history WHERE department_id = ?");
            $stmt->execute([$departmentId]);
            $deletedTables[] = 'allocation_history';
        } catch (Exception $e) {
            // Table might not exist, ignore this error
        }
        
        // Commit transaction
        $db->commit();
        
        $message = 'All allocation data has been cleared successfully';
        if (!empty($deletedTables)) {
            $message .= ' (Cleared: ' . implode(', ', $deletedTables) . ')';
        }
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error clearing allocation data: ' . $e->getMessage()
    ]);
}
