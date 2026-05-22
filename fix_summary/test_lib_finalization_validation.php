<?php
/**
 * Test script for LIB finalization validation
 * Tests the PPMP finalization check before allowing LIB finalization
 */

require_once __DIR__ . '/config/database.php';

echo "=== LIB Finalization Validation Test ===\n\n";

try {
    $db = getDB();
    
    // Test parameters - adjust these to match your test data
    $testLibId = 1; // Change to a valid LIB ID
    $testDepartmentId = 1; // Change to match the LIB's department
    
    echo "Testing LIB ID: {$testLibId}\n";
    echo "Department ID: {$testDepartmentId}\n\n";
    
    // Step 1: Get LIB details
    echo "Step 1: Fetching LIB details...\n";
    $stmt = $db->prepare("SELECT * FROM line_item_budgets WHERE id = ?");
    $stmt->execute([$testLibId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo "❌ LIB not found!\n";
        exit;
    }
    
    echo "✓ LIB found: {$lib['fiscal_year']}\n";
    echo "  Status: {$lib['status']}\n";
    echo "  Department: {$lib['department_id']}\n\n";
    
    // Step 2: Check for PPMP-linked items
    echo "Step 2: Checking for PPMP-linked items...\n";
    $stmt = $db->prepare("
        SELECT DISTINCT lib_category, lib_particulars, lib_account_code, source
        FROM line_item_budget_items
        WHERE lib_id = ? AND source = 'ppmp'
    ");
    $stmt->execute([$testLibId]);
    $ppmpLinkedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ppmpLinkedItems)) {
        echo "✓ No PPMP-linked items found\n";
        echo "  Result: LIB can be finalized (no PPMP validation needed)\n";
        exit;
    }
    
    echo "✓ Found " . count($ppmpLinkedItems) . " PPMP-linked item(s)\n\n";
    
    // Step 3: Check each linked item's source PPMP
    echo "Step 3: Checking source PPMP finalization status...\n";
    $unfinalizedPPMPs = [];
    
    foreach ($ppmpLinkedItems as $index => $linkedItem) {
        echo "\nItem " . ($index + 1) . ":\n";
        echo "  Category: {$linkedItem['lib_category']}\n";
        echo "  Particulars: {$linkedItem['lib_particulars']}\n";
        echo "  Account Code: {$linkedItem['lib_account_code']}\n";
        
        // Find source PPMP
        $ppmpCheckStmt = $db->prepare("
            SELECT DISTINCT p.id, p.ppmp_number, p.status, p.is_final, p.fiscal_year
            FROM ppmp p
            INNER JOIN ppmp_items pi ON p.id = pi.ppmp_id
            WHERE p.department_id = ?
            AND p.fiscal_year = ?
            AND pi.lib_category = ?
            AND pi.lib_particulars = ?
            AND pi.lib_account_code = ?
        ");
        
        $ppmpCheckStmt->execute([
            $lib['department_id'],
            $lib['fiscal_year'],
            $linkedItem['lib_category'],
            $linkedItem['lib_particulars'],
            $linkedItem['lib_account_code']
        ]);
        
        $sourcePPMP = $ppmpCheckStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$sourcePPMP) {
            echo "  ⚠️  No source PPMP found (orphaned item)\n";
            continue;
        }
        
        echo "  Source PPMP: {$sourcePPMP['ppmp_number']}\n";
        echo "  PPMP Status: {$sourcePPMP['status']}\n";
        echo "  Is Final: " . ($sourcePPMP['is_final'] ? 'Yes' : 'No') . "\n";
        
        // Check if PPMP is finalized
        $isFinalized = ($sourcePPMP['is_final'] == 1 && $sourcePPMP['status'] == 'approved');
        
        if ($isFinalized) {
            echo "  ✓ PPMP is finalized\n";
        } else {
            echo "  ❌ PPMP is NOT finalized\n";
            $unfinalizedPPMPs[] = $sourcePPMP['ppmp_number'] ?? 'PPMP #' . $sourcePPMP['id'];
        }
    }
    
    // Step 4: Final result
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "FINAL RESULT:\n";
    echo str_repeat("=", 50) . "\n\n";
    
    if (empty($unfinalizedPPMPs)) {
        echo "✅ LIB CAN BE FINALIZED\n";
        echo "   All linked PPMPs are finalized.\n";
    } else {
        echo "❌ LIB CANNOT BE FINALIZED\n\n";
        $ppmpList = implode(', ', array_unique($unfinalizedPPMPs));
        echo "   Error Message:\n";
        echo "   \"Cannot finalize LIB: The following PPMP(s) linked to this LIB\n";
        echo "   are not finalized: {$ppmpList}.\n";
        echo "   Please finalize all linked PPMPs before finalizing the LIB.\"\n\n";
        echo "   Action Required:\n";
        echo "   1. Finalize the following PPMP(s): {$ppmpList}\n";
        echo "   2. Return to LIB and try finalization again\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
