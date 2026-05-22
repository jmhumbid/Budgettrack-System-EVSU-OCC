<?php
/**
 * Test PPMP-LIB Sync Setup
 * Verifies that all necessary components are in place
 */

require_once __DIR__ . '/config/database.php';

echo "=== PPMP-LIB Sync Setup Test ===\n\n";

try {
    $db = getDB();
    $allGood = true;
    
    // 1. Check if ppmp_items table has LIB mapping fields
    echo "1. Checking ppmp_items table structure...\n";
    $query = "SHOW COLUMNS FROM ppmp_items";
    $stmt = $db->query($query);
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $requiredColumns = ['lib_category', 'lib_particulars', 'lib_account_code'];
    $missingColumns = [];
    
    foreach ($requiredColumns as $col) {
        if (!in_array($col, $columns)) {
            $missingColumns[] = $col;
            $allGood = false;
        }
    }
    
    if (empty($missingColumns)) {
        echo "   ✅ All LIB mapping fields exist\n";
    } else {
        echo "   ❌ Missing columns: " . implode(', ', $missingColumns) . "\n";
        echo "   → Run: php install_ppmp_lib_mapping.php\n";
    }
    
    // 2. Check if sync helper file exists
    echo "\n2. Checking sync helper file...\n";
    if (file_exists(__DIR__ . '/api/sync_ppmp_to_lib_helper.php')) {
        echo "   ✅ sync_ppmp_to_lib_helper.php exists\n";
    } else {
        echo "   ❌ sync_ppmp_to_lib_helper.php not found\n";
        $allGood = false;
    }
    
    // 3. Check if get_lib_expense_categories API exists
    echo "\n3. Checking LIB expense categories API...\n";
    if (file_exists(__DIR__ . '/api/get_lib_expense_categories.php')) {
        echo "   ✅ get_lib_expense_categories.php exists\n";
    } else {
        echo "   ❌ get_lib_expense_categories.php not found\n";
        $allGood = false;
    }
    
    // 4. Check if create_ppmp.php calls sync
    echo "\n4. Checking create_ppmp.php integration...\n";
    $createContent = file_get_contents(__DIR__ . '/api/create_ppmp.php');
    if (strpos($createContent, 'syncPPMPToLIB') !== false) {
        echo "   ✅ create_ppmp.php calls sync function\n";
    } else {
        echo "   ❌ create_ppmp.php does not call sync function\n";
        $allGood = false;
    }
    
    // 5. Check if update_ppmp.php calls sync
    echo "\n5. Checking update_ppmp.php integration...\n";
    $updateContent = file_get_contents(__DIR__ . '/api/update_ppmp.php');
    if (strpos($updateContent, 'syncPPMPToLIB') !== false) {
        echo "   ✅ update_ppmp.php calls sync function\n";
    } else {
        echo "   ❌ update_ppmp.php does not call sync function\n";
        $allGood = false;
    }
    
    // 6. Check if ppmp.js has LIB linking functions
    echo "\n6. Checking JavaScript integration...\n";
    if (file_exists(__DIR__ . '/assets/js/ppmp.js')) {
        $jsContent = file_get_contents(__DIR__ . '/assets/js/ppmp.js');
        if (strpos($jsContent, 'showLibExpenseSelector') !== false) {
            echo "   ✅ ppmp.js has LIB linking functions\n";
        } else {
            echo "   ❌ ppmp.js missing LIB linking functions\n";
            $allGood = false;
        }
    } else {
        echo "   ❌ ppmp.js not found\n";
        $allGood = false;
    }
    
    // 7. Test database connection and tables
    echo "\n7. Checking database tables...\n";
    $tables = ['ppmp', 'ppmp_items', 'line_item_budgets', 'line_item_budget_items'];
    foreach ($tables as $table) {
        $query = "SHOW TABLES LIKE '$table'";
        $stmt = $db->query($query);
        if ($stmt->rowCount() > 0) {
            echo "   ✅ Table '$table' exists\n";
        } else {
            echo "   ❌ Table '$table' not found\n";
            $allGood = false;
        }
    }
    
    // Summary
    echo "\n" . str_repeat("=", 50) . "\n";
    if ($allGood) {
        echo "✅ ALL CHECKS PASSED!\n\n";
        echo "Your PPMP-LIB sync feature is ready to use.\n\n";
        echo "Next steps:\n";
        echo "1. Create a draft LIB for your department and fiscal year\n";
        echo "2. Create a PPMP and link items to LIB expense categories\n";
        echo "3. Save the PPMP - items will auto-sync to the LIB\n";
    } else {
        echo "❌ SOME CHECKS FAILED\n\n";
        echo "Please fix the issues above before using the sync feature.\n";
    }
    echo str_repeat("=", 50) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}
?>
