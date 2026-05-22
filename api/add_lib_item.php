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
    $libId = $_POST['lib_id'] ?? null;
    $category = $_POST['category'] ?? null;
    $particulars = $_POST['particulars'] ?? null;
    $accountCode = $_POST['account_code'] ?? null;
    $amount = $_POST['amount'] ?? null;
    $subCategories = isset($_POST['sub_categories']) ? json_decode($_POST['sub_categories'], true) : [];
    
    // Log received data for debugging
    error_log("add_lib_item.php - Received data: lib_id=$libId, category=$category, particulars=$particulars, account_code=$accountCode, amount=$amount, sub_categories=" . json_encode($subCategories));
    
    // Validate required fields
    if (!$libId || !$category || !$particulars || !$accountCode || !$amount) {
        $missing = [];
        if (!$libId) $missing[] = 'lib_id';
        if (!$category) $missing[] = 'category';
        if (!$particulars) $missing[] = 'particulars';
        if (!$accountCode) $missing[] = 'account_code';
        if (!$amount) $missing[] = 'amount';
        
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing)
        ]);
        exit;
    }
    
    // Check if LIB exists and is a draft
    $stmt = $db->prepare("SELECT status, department_id FROM line_item_budgets WHERE id = ?");
    $stmt->execute([$libId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        error_log("add_lib_item.php - LIB not found: $libId");
        echo json_encode(['success' => false, 'message' => 'LIB not found']);
        exit;
    }
    
    error_log("add_lib_item.php - LIB found: status={$lib['status']}, dept_id={$lib['department_id']}");
    
    // Only allow adding items to draft LIBs
    if ($lib['status'] !== 'draft') {
        error_log("add_lib_item.php - LIB is not draft: {$lib['status']}");
        echo json_encode(['success' => false, 'message' => 'Cannot add items to a finalized LIB. Only draft LIBs can be edited.']);
        exit;
    }
    
    // Verify user has access to this LIB's department
    $userDeptId = $_SESSION['department_id'] ?? null;
    error_log("add_lib_item.php - User dept_id: $userDeptId, LIB dept_id: {$lib['department_id']}");
    
    if ($userDeptId != $lib['department_id']) {
        error_log("add_lib_item.php - Access denied: user dept $userDeptId != lib dept {$lib['department_id']}");
        echo json_encode(['success' => false, 'message' => 'Access denied - Department mismatch']);
        exit;
    }
    
    // Insert the new item
    $isParent = !empty($subCategories) ? 1 : 0;
    
    $stmt = $db->prepare("
        INSERT INTO line_item_budget_items 
        (lib_id, category, particulars, account_code, amount, is_parent, source, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'manual', NOW())
    ");
    
    $result = $stmt->execute([
        $libId,
        $category,
        $particulars,
        $accountCode,
        $amount,
        $isParent
    ]);
    
    if ($result) {
        $itemId = $db->lastInsertId();
        error_log("add_lib_item.php - Item added successfully: item_id=$itemId");
        
        // If there are sub-categories, insert them
        if (!empty($subCategories)) {
            $subStmt = $db->prepare("
                INSERT INTO line_item_budget_items 
                (lib_id, parent_id, category, particulars, sub_category_name, account_code, amount, is_parent, source, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 0, 'manual', NOW())
            ");
            
            foreach ($subCategories as $sub) {
                if (!empty($sub['name']) && isset($sub['amount']) && $sub['amount'] > 0) {
                    $subStmt->execute([
                        $libId,
                        $itemId,
                        $category,
                        $particulars,
                        $sub['name'],
                        $accountCode,
                        $sub['amount']
                    ]);
                    error_log("add_lib_item.php - Sub-category added: {$sub['name']} = {$sub['amount']}");
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Item added successfully',
            'item_id' => $itemId,
            'sub_categories_count' => count($subCategories)
        ]);
    } else {
        error_log("add_lib_item.php - Insert failed");
        echo json_encode([
            'success' => false,
            'message' => 'Failed to insert item'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in add_lib_item.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
