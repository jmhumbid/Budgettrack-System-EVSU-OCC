<?php
/**
 * Debug PPMP-LIB Sync
 * Check why sync is not working
 */

require_once __DIR__ . '/config/database.php';

echo "=== PPMP-LIB Sync Debug ===\n\n";

try {
    $db = getDB();
    
    // 1. Check if there are any PPMPs with LIB mappings
    echo "1. Checking for PPMPs with LIB mappings...\n";
    $query = "SELECT p.id, p.ppmp_number, p.fiscal_year, p.department_id, p.status,
              COUNT(pi.id) as total_items,
              SUM(CASE WHEN pi.lib_category IS NOT NULL AND pi.lib_category != '' THEN 1 ELSE 0 END) as mapped_items
              FROM ppmp p
              LEFT JOIN ppmp_items pi ON p.id = pi.ppmp_id
              GROUP BY p.id
              ORDER BY p.created_at DESC
              LIMIT 5";
    $stmt = $db->query($query);
    $ppmps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ppmps)) {
        echo "   ❌ No PPMPs found\n";
        exit;
    }
    
    foreach ($ppmps as $ppmp) {
        echo "   PPMP #{$ppmp['id']} ({$ppmp['ppmp_number']}) - {$ppmp['fiscal_year']}\n";
        echo "      Status: {$ppmp['status']}\n";
        echo "      Total items: {$ppmp['total_items']}\n";
        echo "      Mapped items: {$ppmp['mapped_items']}\n";
        
        if ($ppmp['mapped_items'] > 0) {
            // Show the mapped items
            $itemQuery = "SELECT id, general_description, estimated_budget, lib_category, lib_particulars, lib_account_code
                         FROM ppmp_items
                         WHERE ppmp_id = ? AND lib_category IS NOT NULL AND lib_category != ''";
            $itemStmt = $db->prepare($itemQuery);
            $itemStmt->execute([$ppmp['id']]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as $item) {
                echo "      ✓ Item #{$item['id']}: {$item['general_description']}\n";
                echo "        → {$item['lib_category']} / {$item['lib_particulars']} ({$item['lib_account_code']})\n";
                echo "        → Budget: ₱" . number_format($item['estimated_budget'], 2) . "\n";
            }
        }
        echo "\n";
    }
    
    // 2. Check if there are draft LIBs for these departments
    echo "\n2. Checking for draft LIBs...\n";
    $libQuery = "SELECT l.id, l.fiscal_year, l.status, d.dept_name,
                 COUNT(li.id) as item_count
                 FROM line_item_budgets l
                 LEFT JOIN departments d ON l.department_id = d.id
                 LEFT JOIN line_item_budget_items li ON l.id = li.lib_id
                 WHERE l.status = 'draft'
                 GROUP BY l.id
                 ORDER BY l.created_at DESC
                 LIMIT 5";
    $libStmt = $db->query($libQuery);
    $libs = $libStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($libs)) {
        echo "   ❌ No draft LIBs found\n";
        echo "   → You need to create a draft LIB first!\n";
    } else {
        foreach ($libs as $lib) {
            echo "   LIB #{$lib['id']} - {$lib['dept_name']} ({$lib['fiscal_year']})\n";
            echo "      Status: {$lib['status']}\n";
            echo "      Items: {$lib['item_count']}\n";
            
            // Check if any items are from PPMP
            $ppmpItemQuery = "SELECT id, category, particulars, amount
                             FROM line_item_budget_items
                             WHERE lib_id = ? AND particulars LIKE '%PPMP #%'
                             LIMIT 5";
            $ppmpItemStmt = $db->prepare($ppmpItemQuery);
            $ppmpItemStmt->execute([$lib['id']]);
            $ppmpItems = $ppmpItemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($ppmpItems)) {
                echo "      PPMP-synced items:\n";
                foreach ($ppmpItems as $item) {
                    echo "         - {$item['particulars']} (₱" . number_format($item['amount'], 2) . ")\n";
                }
            }
            echo "\n";
        }
    }
    
    // 3. Test sync for the most recent PPMP with mappings
    echo "\n3. Testing sync for most recent PPMP with mappings...\n";
    $testPPMP = null;
    foreach ($ppmps as $ppmp) {
        if ($ppmp['mapped_items'] > 0) {
            $testPPMP = $ppmp;
            break;
        }
    }
    
    if (!$testPPMP) {
        echo "   ❌ No PPMP with LIB mappings found to test\n";
        exit;
    }
    
    echo "   Testing PPMP #{$testPPMP['id']} ({$testPPMP['ppmp_number']})\n";
    
    // Call sync function
    require_once __DIR__ . '/api/sync_ppmp_to_lib_helper.php';
    $syncResult = syncPPMPToLIB($testPPMP['id'], 1);
    
    echo "\n   Sync Result:\n";
    echo "   Success: " . ($syncResult['success'] ? 'YES' : 'NO') . "\n";
    echo "   Message: " . ($syncResult['message'] ?? 'N/A') . "\n";
    if (isset($syncResult['synced_count'])) {
        echo "   Items synced: " . $syncResult['synced_count'] . "\n";
    }
    if (isset($syncResult['updated_count'])) {
        echo "   Items updated: " . $syncResult['updated_count'] . "\n";
    }
    if (isset($syncResult['lib_id'])) {
        echo "   LIB ID: " . $syncResult['lib_id'] . "\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
