<?php
/**
 * Cleanup Script: Remove duplicate LIB entries with PPMP references
 * and consolidate amounts into single rows per expense category
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Starting LIB duplicate cleanup...\n\n";
    
    // Find all LIB items with PPMP references (containing "PPMP #")
    $query = "SELECT * FROM line_item_budget_items 
              WHERE particulars LIKE '%PPMP #%' 
              ORDER BY lib_id, category, particulars";
    $stmt = $db->query($query);
    $itemsWithRefs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($itemsWithRefs) . " items with PPMP references\n\n";
    
    if (empty($itemsWithRefs)) {
        echo "No duplicate items to clean up.\n";
        exit(0);
    }
    
    $db->beginTransaction();
    
    // Group by lib_id and base particulars (without PPMP reference)
    $groups = [];
    foreach ($itemsWithRefs as $item) {
        // Extract base particulars (remove PPMP reference)
        $particulars = $item['particulars'];
        if (preg_match('/^(.+?)\s*\(PPMP #.+\)$/', $particulars, $matches)) {
            $baseParticulars = trim($matches[1]);
        } else {
            $baseParticulars = $particulars;
        }
        
        $key = $item['lib_id'] . '|' . $item['category'] . '|' . $baseParticulars . '|' . $item['account_code'];
        
        if (!isset($groups[$key])) {
            $groups[$key] = [
                'lib_id' => $item['lib_id'],
                'category' => $item['category'],
                'particulars' => $baseParticulars,
                'account_code' => $item['account_code'],
                'total_amount' => 0,
                'items_to_delete' => [],
                'sort_order' => $item['sort_order']
            ];
        }
        
        $groups[$key]['total_amount'] += floatval($item['amount']);
        $groups[$key]['items_to_delete'][] = $item['id'];
    }
    
    echo "Grouped into " . count($groups) . " unique expense categories\n\n";
    
    $itemsDeleted = 0;
    $itemsCreated = 0;
    $itemsUpdated = 0;
    
    foreach ($groups as $group) {
        echo "Processing: {$group['category']} - {$group['particulars']}\n";
        echo "  Total amount: ₱" . number_format($group['total_amount'], 2) . "\n";
        echo "  Items to consolidate: " . count($group['items_to_delete']) . "\n";
        
        // Check if base category already exists (without PPMP reference)
        $checkQuery = "SELECT id, amount FROM line_item_budget_items 
                       WHERE lib_id = ? 
                       AND category = ? 
                       AND particulars = ?
                       AND particulars NOT LIKE '%PPMP #%'";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->execute([$group['lib_id'], $group['category'], $group['particulars']]);
        $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            // Update existing base entry with consolidated amount
            $updateQuery = "UPDATE line_item_budget_items 
                           SET amount = ?, 
                               account_code = ?,
                               updated_at = CURRENT_TIMESTAMP 
                           WHERE id = ?";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->execute([
                $group['total_amount'],
                $group['account_code'],
                $existing['id']
            ]);
            $itemsUpdated++;
            echo "  ✓ Updated existing base entry (ID: {$existing['id']})\n";
        } else {
            // Create new base entry with consolidated amount
            $insertQuery = "INSERT INTO line_item_budget_items 
                           (lib_id, category, particulars, account_code, amount, sort_order) 
                           VALUES (?, ?, ?, ?, ?, ?)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->execute([
                $group['lib_id'],
                $group['category'],
                $group['particulars'],
                $group['account_code'],
                $group['total_amount'],
                $group['sort_order']
            ]);
            $newId = $db->lastInsertId();
            $itemsCreated++;
            echo "  ✓ Created new consolidated entry (ID: $newId)\n";
        }
        
        // Delete all items with PPMP references
        $deleteQuery = "DELETE FROM line_item_budget_items WHERE id IN (" . implode(',', $group['items_to_delete']) . ")";
        $db->exec($deleteQuery);
        $itemsDeleted += count($group['items_to_delete']);
        echo "  ✓ Deleted " . count($group['items_to_delete']) . " duplicate items\n\n";
    }
    
    $db->commit();
    
    echo "\n=== Cleanup Complete ===\n";
    echo "Items deleted: $itemsDeleted\n";
    echo "Items created: $itemsCreated\n";
    echo "Items updated: $itemsUpdated\n";
    echo "\nAll LIB entries have been consolidated!\n";
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
?>
