<?php
/**
 * Test PPMP to LIB Sync
 * This script tests if PPMP items are being synced to LIB correctly
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/api/sync_ppmp_to_lib_helper.php';

try {
    $db = getDB();
    
    echo "<h2>PPMP to LIB Sync Test</h2>";
    echo "<hr>";
    
    // Get the most recent PPMP
    $ppmpQuery = "SELECT p.*, d.dept_name 
                  FROM ppmp p 
                  LEFT JOIN departments d ON p.department_id = d.id 
                  ORDER BY p.created_at DESC 
                  LIMIT 1";
    $ppmpStmt = $db->prepare($ppmpQuery);
    $ppmpStmt->execute();
    $ppmp = $ppmpStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        echo "<p style='color: red;'>No PPMP found in database</p>";
        exit;
    }
    
    echo "<h3>Most Recent PPMP:</h3>";
    echo "<ul>";
    echo "<li><strong>ID:</strong> {$ppmp['id']}</li>";
    echo "<li><strong>Department:</strong> {$ppmp['dept_name']}</li>";
    echo "<li><strong>Fiscal Year:</strong> {$ppmp['fiscal_year']}</li>";
    echo "<li><strong>PPMP Number:</strong> {$ppmp['ppmp_number']}</li>";
    echo "<li><strong>Status:</strong> {$ppmp['status']}</li>";
    echo "</ul>";
    
    // Get PPMP items with LIB mappings
    echo "<h3>PPMP Items with LIB Mappings:</h3>";
    $itemsQuery = "SELECT * FROM ppmp_items WHERE ppmp_id = ?";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->execute([$ppmp['id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "<p style='color: orange;'>No items found for this PPMP</p>";
    } else {
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background: #800000; color: white;'>";
        echo "<th>ID</th><th>Description</th><th>Budget</th><th>LIB Category</th><th>LIB Particulars</th><th>LIB Account Code</th>";
        echo "</tr>";
        
        $hasLibMappings = false;
        foreach ($items as $item) {
            $hasMapping = !empty($item['lib_category']) && !empty($item['lib_particulars']) && !empty($item['lib_account_code']);
            if ($hasMapping) $hasLibMappings = true;
            
            $rowColor = $hasMapping ? '#d4edda' : '#f8d7da';
            echo "<tr style='background: {$rowColor};'>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['general_description']}</td>";
            echo "<td>₱" . number_format($item['estimated_budget'], 2) . "</td>";
            echo "<td>" . ($item['lib_category'] ?: '<em>Not set</em>') . "</td>";
            echo "<td>" . ($item['lib_particulars'] ?: '<em>Not set</em>') . "</td>";
            echo "<td>" . ($item['lib_account_code'] ?: '<em>Not set</em>') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        if (!$hasLibMappings) {
            echo "<p style='color: red; font-weight: bold;'>⚠️ No items have LIB mappings! You need to link items to LIB expense categories.</p>";
        }
    }
    
    // Check if LIB exists
    echo "<h3>LIB Status:</h3>";
    $libQuery = "SELECT * FROM line_item_budgets 
                 WHERE department_id = ? AND fiscal_year = ? 
                 ORDER BY created_at DESC LIMIT 1";
    $libStmt = $db->prepare($libQuery);
    $libStmt->execute([$ppmp['department_id'], $ppmp['fiscal_year']]);
    $lib = $libStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo "<p style='color: orange;'>No LIB exists for this department and fiscal year. It will be created during sync.</p>";
    } else {
        echo "<ul>";
        echo "<li><strong>LIB ID:</strong> {$lib['id']}</li>";
        echo "<li><strong>Status:</strong> {$lib['status']}</li>";
        echo "<li><strong>Fund Type:</strong> {$lib['fund_type']}</li>";
        echo "</ul>";
        
        if ($lib['status'] === 'approved') {
            echo "<p style='color: red; font-weight: bold;'>⚠️ LIB is FINALIZED! Cannot sync to finalized LIB.</p>";
        }
        
        // Check LIB items
        echo "<h3>Current LIB Items:</h3>";
        $libItemsQuery = "SELECT * FROM line_item_budget_items WHERE lib_id = ? ORDER BY category, sort_order";
        $libItemsStmt = $db->prepare($libItemsQuery);
        $libItemsStmt->execute([$lib['id']]);
        $libItems = $libItemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($libItems)) {
            echo "<p style='color: orange;'>No items in LIB yet</p>";
        } else {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #800000; color: white;'>";
            echo "<th>ID</th><th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th><th>Source</th>";
            echo "</tr>";
            
            foreach ($libItems as $libItem) {
                echo "<tr>";
                echo "<td>{$libItem['id']}</td>";
                echo "<td>{$libItem['category']}</td>";
                echo "<td>{$libItem['particulars']}</td>";
                echo "<td>{$libItem['account_code']}</td>";
                echo "<td>₱" . number_format($libItem['amount'], 2) . "</td>";
                echo "<td>" . ($libItem['source'] ?: 'manual') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    // Test the sync function
    echo "<hr>";
    echo "<h3>Testing Sync Function:</h3>";
    
    $syncResult = syncPPMPToLIB($ppmp['id'], 1); // Use user ID 1 for test
    
    echo "<pre>";
    print_r($syncResult);
    echo "</pre>";
    
    if ($syncResult['success']) {
        echo "<p style='color: green; font-weight: bold;'>✅ Sync completed successfully!</p>";
        echo "<ul>";
        echo "<li>Items synced: {$syncResult['synced_count']}</li>";
        echo "<li>Items updated: {$syncResult['updated_count']}</li>";
        if (isset($syncResult['lib_id'])) {
            echo "<li>LIB ID: {$syncResult['lib_id']}</li>";
        }
        echo "</ul>";
        
        // Show updated LIB items
        $libId = $syncResult['lib_id'] ?? $lib['id'];
        if ($libId) {
            echo "<h3>LIB Items After Sync:</h3>";
            $libItemsQuery = "SELECT * FROM line_item_budget_items WHERE lib_id = ? ORDER BY category, sort_order";
            $libItemsStmt = $db->prepare($libItemsQuery);
            $libItemsStmt->execute([$libId]);
            $libItems = $libItemsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background: #800000; color: white;'>";
            echo "<th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th>";
            echo "</tr>";
            
            foreach ($libItems as $libItem) {
                echo "<tr>";
                echo "<td>{$libItem['category']}</td>";
                echo "<td>{$libItem['particulars']}</td>";
                echo "<td>{$libItem['account_code']}</td>";
                echo "<td>₱" . number_format($libItem['amount'], 2) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Sync failed: {$syncResult['message']}</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
