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
    $subCategoryName = trim($data['sub_category_name'] ?? '');
    $amount = floatval($data['amount'] ?? 0);
    
    if (!$subCategoryId || empty($subCategoryName) || $amount <= 0) {
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
    
    // Update sub-category
    $stmt = $db->prepare("
        UPDATE line_item_budget_items 
        SET sub_category_name = ?, amount = ? 
        WHERE id = ?
    ");
    $stmt->execute([$subCategoryName, $amount, $subCategoryId]);
    
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
    
    echo json_encode([
        'success' => true,
        'message' => 'Sub-category updated successfully',
        'parent_new_total' => $newTotal
    ]);
    
} catch (Exception $e) {
    error_log("Error updating LIB sub-category: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
