<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $parentId = $_GET['parent_id'] ?? null;
    
    if (!$parentId) {
        echo json_encode(['success' => false, 'message' => 'Parent ID required']);
        exit;
    }
    
    $db = getDB();
    
    // Get all sub-categories for this parent
    $stmt = $db->prepare("
        SELECT id, sub_category_name, amount, created_at 
        FROM line_item_budget_items 
        WHERE parent_id = ? 
        ORDER BY created_at ASC
    ");
    $stmt->execute([$parentId]);
    $subCategories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'sub_categories' => $subCategories
    ]);
    
} catch (Exception $e) {
    error_log("Error getting LIB sub-categories: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
