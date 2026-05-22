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
    
    $parentId = $data['parent_id'] ?? null;
    $subCategoryName = trim($data['sub_category_name'] ?? '');
    $amount = floatval($data['amount'] ?? 0);
    
    if (!$parentId || empty($subCategoryName) || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid input data']);
        exit;
    }
    
    $db = getDB();
    
    // Verify parent item exists and get its details
    $stmt = $db->prepare("SELECT lib_id, category, particulars, account_code FROM line_item_budget_items WHERE id = ?");
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$parent) {
        echo json_encode(['success' => false, 'message' => 'Parent item not found']);
        exit;
    }
    
    // Mark parent as parent item if not already
    $stmt = $db->prepare("UPDATE line_item_budget_items SET is_parent = 1 WHERE id = ?");
    $stmt->execute([$parentId]);
    
    // Insert sub-category
    $stmt = $db->prepare("
        INSERT INTO line_item_budget_items 
        (lib_id, parent_id, category, particulars, sub_category_name, account_code, amount, is_parent) 
        VALUES (?, ?, ?, ?, ?, ?, ?, 0)
    ");
    
    $stmt->execute([
        $parent['lib_id'],
        $parentId,
        $parent['category'],
        $parent['particulars'],
        $subCategoryName,
        $parent['account_code'],
        $amount
    ]);
    
    $subCategoryId = $db->lastInsertId();
    
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
        'message' => 'Sub-category added successfully',
        'sub_category_id' => $subCategoryId,
        'parent_new_total' => $newTotal
    ]);
    
} catch (Exception $e) {
    error_log("Error adding LIB sub-category: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
