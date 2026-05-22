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
    
    // Get input
    $input = json_decode(file_get_contents('php://input'), true);
    $ppmpId = $input['ppmp_id'] ?? null;
    
    if (!$ppmpId) {
        echo json_encode(['success' => false, 'message' => 'PPMP ID is required']);
        exit;
    }
    
    // Get PPMP details
    $ppmpQuery = "SELECT p.*, d.dept_name 
                  FROM ppmp p 
                  LEFT JOIN departments d ON p.department_id = d.id 
                  WHERE p.id = ?";
    $ppmpStmt = $db->prepare($ppmpQuery);
    $ppmpStmt->execute([$ppmpId]);
    $ppmp = $ppmpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        echo json_encode(['success' => false, 'message' => 'PPMP not found']);
        exit;
    }
    
    // Get PPMP items with LIB mappings
    $itemsQuery = "SELECT * FROM ppmp_items 
                   WHERE ppmp_id = ? 
                   AND lib_category IS NOT NULL 
                   AND lib_category != '' 
                   AND lib_particulars IS NOT NULL 
                   AND lib_particulars != ''
                   ORDER BY sort_order";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$ppmpId]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo json_encode([
            'success' => true, 
            'message' => 'No items with LIB mappings to sync',
            'items_synced' => 0
        ]);
        exit;
    }
    
    // Check if LIB exists for this department and fiscal year
    $libQuery = "SELECT id, status FROM line_item_budgets 
                 WHERE department_id = ? 
                 AND fiscal_year = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1";
    $libStmt = $db->prepare($libQuery);
    $libStmt->execute([$ppmp['department_id'], $ppmp['fiscal_year']]);
    $lib = $libStmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if LIB is finalized (approved)
    if ($lib && $lib['status'] === 'approved') {
        echo json_encode([
            'success' => false, 
            'message' => 'Cannot sync to LIB: LIB is already finalized/approved'
        ]);
        exit;
    }
    
    // If no LIB exists, create one
    if (!$lib) {
        $createLibQuery = "INSERT INTO line_item_budgets 
                          (department_id, fiscal_year, fund_type, status, created_by) 
                          VALUES (?, ?, 'Internally Generated Fund', 'draft', ?)";
        $createLibStmt = $db->prepare($createLibQuery);
        $createLibStmt->execute([$ppmp['department_id'], $ppmp['fiscal_year'], $_SESSION['user_id']]);
        $libId = $db->lastInsertId();
    } else {
        $libId = $lib['id'];
    }
    
    $db->beginTransaction();
    
    $itemsSynced = 0;
    $itemsUpdated = 0;
    
    foreach ($items as $item) {
        // Check if this PPMP item is already synced to LIB
        $checkQuery = "SELECT id, amount FROM line_item_budget_items 
                       WHERE lib_id = ? 
                       AND category = ? 
                       AND particulars LIKE ?";
        $checkStmt = $db->prepare($checkQuery);
        $ppmpReference = "PPMP #" . $ppmp['ppmp_number'] . " - Item #" . ($item['sort_order'] + 1);
        $checkStmt->execute([$libId, $item['lib_category'], "%$ppmpReference%"]);
        $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingItem) {
            // Update existing item if amount changed
            if ($existingItem['amount'] != $item['estimated_budget']) {
                $updateQuery = "UPDATE line_item_budget_items 
                               SET amount = ?, 
                                   account_code = ?,
                                   updated_at = CURRENT_TIMESTAMP 
                               WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([
                    $item['estimated_budget'],
                    $item['lib_account_code'] ?? '',
                    $existingItem['id']
                ]);
                $itemsUpdated++;
            }
        } else {
            // Add new item to LIB
            $particulars = $item['lib_particulars'] . " (" . $ppmpReference . ")";
            
            $insertQuery = "INSERT INTO line_item_budget_items 
                           (lib_id, category, particulars, account_code, amount, sort_order) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                $libId,
                $item['lib_category'],
                $particulars,
                $item['lib_account_code'] ?? '',
                $item['estimated_budget'],
                $item['sort_order']
            ]);
            $itemsSynced++;
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully synced PPMP items to LIB",
        'items_synced' => $itemsSynced,
        'items_updated' => $itemsUpdated,
        'lib_id' => $libId
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error in sync_ppmp_to_lib.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error syncing to LIB: ' . $e->getMessage()
    ]);
}
?>
