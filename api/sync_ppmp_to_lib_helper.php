<?php
/**
 * PPMP to LIB Sync Helper Function
 * This file provides a direct function to sync PPMP items to LIB
 * without making HTTP requests (which can timeout)
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Sync PPMP items to LIB
 * 
 * @param int $ppmpId The PPMP ID to sync
 * @param int $userId The user ID performing the sync
 * @return array Result array with success status and message
 */
function syncPPMPToLIB($ppmpId, $userId) {
    try {
        $db = getDB();
        
        if (!$ppmpId) {
            return ['success' => false, 'message' => 'PPMP ID is required'];
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
            return ['success' => false, 'message' => 'PPMP not found'];
        }
        
        // Get PPMP items with LIB mappings
        $itemsQuery = "SELECT * FROM ppmp_items 
                       WHERE ppmp_id = ? 
                       AND lib_category IS NOT NULL 
                       AND lib_category != '' 
                       AND lib_particulars IS NOT NULL 
                       AND lib_particulars != ''
                       AND lib_account_code IS NOT NULL
                       AND lib_account_code != ''
                       ORDER BY sort_order";
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute([$ppmpId]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            return [
                'success' => true, 
                'message' => 'No items with LIB mappings to sync',
                'synced_count' => 0,
                'updated_count' => 0
            ];
        }
        
        // Check if LIB exists for this department and fiscal year
        // IMPORTANT: We prioritize DRAFT LIBs first, then fall back to most recent
        // This ensures we add to existing draft LIBs instead of creating new ones
        // Handle both "2026" and "FY 2026" formats
        $libQuery = "SELECT id, status FROM line_item_budgets 
                     WHERE department_id = ? 
                     AND (fiscal_year = ? OR fiscal_year = CONCAT('FY ', ?) OR fiscal_year = ?)
                     ORDER BY 
                         CASE WHEN status = 'draft' THEN 0 ELSE 1 END,
                         created_at DESC 
                     LIMIT 1";
        $libStmt = $db->prepare($libQuery);
        $libStmt->execute([$ppmp['department_id'], $ppmp['fiscal_year'], $ppmp['fiscal_year'], 'FY ' . $ppmp['fiscal_year']]);
        $lib = $libStmt->fetch(PDO::FETCH_ASSOC);
        
        // Log which LIB we're syncing to
        if ($lib) {
            error_log("PPMP Sync: Found existing LIB #{$lib['id']} (status: {$lib['status']}) for dept {$ppmp['department_id']}, year {$ppmp['fiscal_year']}");
        }
        
        // Check if LIB is finalized (approved)
        if ($lib && $lib['status'] === 'approved') {
            return [
                'success' => false, 
                'message' => 'Cannot sync to LIB: LIB is already finalized/approved. Please create a new draft LIB or edit the existing one.'
            ];
        }
        
        // If no LIB exists at all, return error instead of creating one
        // User should create a LIB first before syncing PPMP items
        if (!$lib) {
            return [
                'success' => false, 
                'message' => 'No LIB found for this department and fiscal year. Please create a LIB first, then sync PPMP items to it.'
            ];
        }
        
        $libId = $lib['id'];
        error_log("PPMP Sync: Syncing PPMP #$ppmpId to existing LIB #$libId");
        
        $db->beginTransaction();
        
        $itemsSynced = 0;
        $itemsUpdated = 0;
        
        // Group items by expense category to aggregate amounts
        $categoryGroups = [];
        foreach ($items as $item) {
            $key = $item['lib_category'] . '|' . $item['lib_particulars'] . '|' . $item['lib_account_code'];
            if (!isset($categoryGroups[$key])) {
                $categoryGroups[$key] = [
                    'category' => $item['lib_category'],
                    'particulars' => $item['lib_particulars'],
                    'account_code' => $item['lib_account_code'] ?? '',
                    'total_amount' => 0,
                    'sort_order' => $item['sort_order']
                ];
            }
            $categoryGroups[$key]['total_amount'] += floatval($item['estimated_budget']);
        }
        
        // Now sync the aggregated amounts to LIB
        foreach ($categoryGroups as $group) {
            // Check if this expense category already exists in LIB
            $checkQuery = "SELECT id, amount FROM line_item_budget_items 
                           WHERE lib_id = ? 
                           AND category = ? 
                           AND particulars = ?
                           AND particulars NOT LIKE '%PPMP #%'";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([$libId, $group['category'], $group['particulars']]);
            $existingItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingItem) {
                // Update existing item with the new total amount
                $updateQuery = "UPDATE line_item_budget_items 
                               SET amount = ?, 
                                   account_code = ?,
                                   updated_at = CURRENT_TIMESTAMP 
                               WHERE id = ?";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([
                    $group['total_amount'],
                    $group['account_code'],
                    $existingItem['id']
                ]);
                $itemsUpdated++;
                error_log("PPMP Sync: Updated existing item #{$existingItem['id']} in LIB #$libId (category: {$group['category']}, particulars: {$group['particulars']}, amount: {$group['total_amount']})");
            } else {
                // Add new item to LIB (without PPMP reference, just the expense category name)
                $insertQuery = "INSERT INTO line_item_budget_items 
                               (lib_id, category, particulars, account_code, amount, source, sort_order) 
                               VALUES (?, ?, ?, ?, ?, 'ppmp', ?)";
                $insertStmt = $db->prepare($insertQuery);
                $insertStmt->execute([
                    $libId,
                    $group['category'],
                    $group['particulars'],
                    $group['account_code'],
                    $group['total_amount'],
                    $group['sort_order']
                ]);
                $itemsSynced++;
                $newItemId = $db->lastInsertId();
                error_log("PPMP Sync: Added new item #$newItemId to LIB #$libId (category: {$group['category']}, particulars: {$group['particulars']}, amount: {$group['total_amount']}, source: ppmp)");
            }
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => "Successfully synced PPMP items to LIB",
            'synced_count' => $itemsSynced,
            'updated_count' => $itemsUpdated,
            'lib_id' => $libId
        ];
        
    } catch (Exception $e) {
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Error in syncPPMPToLIB: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error syncing to LIB: ' . $e->getMessage()
        ];
    }
}
?>
