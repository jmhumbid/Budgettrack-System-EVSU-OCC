<?php
session_start();
header('Content-Type: application/json');

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    // Get parameters
    $itemId = $_POST['item_id'] ?? null;
    
    error_log("delete_lib_item.php - Received item_id: $itemId");
    
    if (!$itemId) {
        echo json_encode(['success' => false, 'message' => 'Item ID required']);
        exit;
    }
    
    // Check if item exists, is manual, and LIB is draft
    $stmt = $db->prepare("
        SELECT i.source, l.status, l.department_id 
        FROM line_item_budget_items i
        JOIN line_item_budgets l ON i.lib_id = l.id
        WHERE i.id = ?
    ");
    $stmt->execute([$itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        error_log("delete_lib_item.php - Item not found: $itemId");
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }
    
    error_log("delete_lib_item.php - Item found: source={$item['source']}, status={$item['status']}, dept_id={$item['department_id']}");
    
    if ($item['source'] !== 'manual') {
        error_log("delete_lib_item.php - Cannot delete auto item: {$item['source']}");
        echo json_encode(['success' => false, 'message' => 'Cannot delete auto-generated items']);
        exit;
    }
    
    if ($item['status'] !== 'draft') {
        error_log("delete_lib_item.php - Cannot delete from finalized LIB: {$item['status']}");
        echo json_encode(['success' => false, 'message' => 'Cannot delete items from a finalized LIB']);
        exit;
    }
    
    // Verify user has access
    $userDeptId = $_SESSION['department_id'] ?? null;
    error_log("delete_lib_item.php - User dept_id: $userDeptId, Item dept_id: {$item['department_id']}");
    
    if ($userDeptId != $item['department_id']) {
        error_log("delete_lib_item.php - Access denied: user dept $userDeptId != item dept {$item['department_id']}");
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
    
    // Delete the item
    $stmt = $db->prepare("DELETE FROM line_item_budget_items WHERE id = ?");
    $result = $stmt->execute([$itemId]);
    
    if ($result) {
        error_log("delete_lib_item.php - Item deleted successfully: $itemId");
        echo json_encode([
            'success' => true,
            'message' => 'Item deleted successfully'
        ]);
    } else {
        error_log("delete_lib_item.php - Delete failed for item: $itemId");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete item'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in delete_lib_item.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
