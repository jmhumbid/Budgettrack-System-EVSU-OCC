<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $subCategoryId = $data['id'] ?? null;
    
    if (!$subCategoryId) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }
    
    $db = getDB();
    
    // Get sub-category details
    $stmt = $db->prepare("SELECT parent_id FROM line_item_budget_items WHERE id = ?");
    $stmt->execute([$subCategoryId]);
    $subCategory = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$subCategory || !$subCategory['parent_id']) {
        echo json_encode(['success' => false, 'message' => 'Sub-category not found']);
        exit;
    }
    
    $parentId = $subCategory['parent_id'];
    
    // Delete sub-category
    $stmt = $db->prepare("DELETE FROM line_item_budget_items WHERE id = ?");
    $stmt->execute([$subCategoryId]);
    
    // Check if parent still has sub-categories
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM line_item_budget_items WHERE parent_id = ?");
    $stmt->execute([$parentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasSubCategories = $result['count'] > 0;
    
    if ($hasSubCategories) {
        // Recalculate parent total
        $stmt = $db->prepare("
            SELECT SUM(amount) as total 
            FROM line_item_budget_items 
            WHERE parent_id = ?
        ");
        $stmt->execute([$parentId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $newTotal = $result['total'] ?? 0;
        
        // Update parent amount
        $stmt = $db->prepare("UPDATE line_item_budget_items SET amount = ? WHERE id = ?");
        $stmt->execute([$newTotal, $parentId]);
    } else {
        // No more sub-categories, mark parent as non-parent and reset amount to 0
        $stmt = $db->prepare("UPDATE line_item_budget_items SET is_parent = 0, amount = 0 WHERE id = ?");
        $stmt->execute([$parentId]);
        $newTotal = 0;
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Sub-category deleted successfully',
        'parent_new_total' => $newTotal,
        'has_sub_categories' => $hasSubCategories
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting LIB sub-category: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
