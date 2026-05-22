<?php
/**
 * Enhanced LIB Items Diagnostic Script
 * This script shows all LIBs and their items with detailed source tracking
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "<style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .lib-box { border: 3px solid #800000; padding: 20px; margin: 20px 0; background: white; border-radius: 8px; }
        .lib-header { background: #800000; color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 5px 5px 0 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #800000; color: white; padding: 12px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #ddd; }
        tr:hover { background: #f9f9f9; }
        .ppmp-item { background: #d4edda !important; }
        .manual-item { background: #fff3cd !important; }
        .warning { background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; margin: 20px 0; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin: 20px 0; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; margin: 20px 0; }
        .btn { display: inline-block; padding: 10px 20px; margin: 10px 5px; background: #800000; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .btn:hover { background: #600000; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
    </style>";
    
    echo "<h1 style='color: #800000;'>🔍 LIB Items Diagnostic Report</h1>";
    echo "<p><strong>Generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
    echo "<hr>";
    
    // Get all LIBs for Computer Studies, 2026
    $libQuery = "SELECT l.*, d.dept_name 
                 FROM line_item_budgets l
                 LEFT JOIN departments d ON l.department_id = d.id
                 WHERE d.dept_name LIKE '%Computer%' 
                 AND l.fiscal_year = '2026'
                 ORDER BY l.created_at DESC";
    $libStmt = $db->prepare($libQuery);
    $libStmt->execute();
    $libs = $libStmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($libs)) {
        echo "<div class='error'><strong>❌ No LIBs found</strong><br>No LIBs found for Computer Studies 2026</div>";
        exit;
    }
    
    echo "<div class='info'><strong>📊 Summary:</strong> Found " . count($libs) . " LIB(s) for Computer Studies 2026</div>";
    
    // Check for duplicates
    if (count($libs) > 1) {
        echo "<div class='warning'>";
        echo "<strong>⚠️ MULTIPLE LIBs DETECTED!</strong><br>";
        echo "You have <strong>" . count($libs) . " LIBs</strong> for the same department and year. This can cause confusion.<br>";
        echo "The PPMP sync will only update the MOST RECENT LIB (by created_at date).<br>";
        echo "If you're viewing an older LIB, you won't see the synced items!";
        echo "</div>";
    }
    
    // Analyze each LIB
    foreach ($libs as $index => $lib) {
        $libNumber = $index + 1;
        $isNewest = ($index === 0);
        
        echo "<div class='lib-box'>";
        echo "<div class='lib-header'>";
        echo "<h2>LIB #{$libNumber} - ID: {$lib['id']}" . ($isNewest ? " 🌟 NEWEST (PPMP syncs here)" : " ⚠️ OLDER") . "</h2>";
        echo "</div>";
        
        echo "<table style='width: auto; margin-bottom: 20px;'>";
        echo "<tr><td><strong>Department:</strong></td><td>{$lib['dept_name']}</td></tr>";
        echo "<tr><td><strong>Fiscal Year:</strong></td><td>{$lib['fiscal_year']}</td></tr>";
        echo "<tr><td><strong>Status:</strong></td><td><span style='padding: 5px 10px; background: " . 
             ($lib['status'] === 'approved' ? '#28a745' : '#6c757d') . "; color: white; border-radius: 3px;'>" . 
             strtoupper($lib['status']) . "</span></td></tr>";
        echo "<tr><td><strong>Fund Type:</strong></td><td>{$lib['fund_type']}</td></tr>";
        echo "<tr><td><strong>Created:</strong></td><td>{$lib['created_at']}</td></tr>";
        echo "<tr><td><strong>Updated:</strong></td><td>{$lib['updated_at']}</td></tr>";
        echo "</table>";
        
        // Get items for this LIB
        $itemsQuery = "SELECT * FROM line_item_budget_items 
                       WHERE lib_id = ? 
                       ORDER BY 
                           CASE category
                               WHEN 'A. PERSONAL SERVICES' THEN 1
                               WHEN 'B. Maintenance & Other Operating Expenses' THEN 2
                               WHEN 'C. Capital Outlay' THEN 3
                               ELSE 4
                           END,
                           sort_order, id";
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute([$lib['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo "<div class='warning'><strong>⚠️ Empty LIB</strong><br>This LIB has no items.</div>";
        } else {
            $ppmpCount = 0;
            $manualCount = 0;
            $grandTotal = 0;
            
            // Count items by source
            foreach ($items as $item) {
                $grandTotal += floatval($item['amount']);
                if (strpos($item['particulars'], 'PPMP') !== false) {
                    $ppmpCount++;
                } else {
                    $manualCount++;
                }
            }
            
            echo "<div class='info'>";
            echo "<strong>📋 Items Summary:</strong><br>";
            echo "Total Items: <strong>" . count($items) . "</strong><br>";
            echo "Manual Items: <strong style='color: #856404;'>$manualCount</strong><br>";
            echo "PPMP Items: <strong style='color: #155724;'>$ppmpCount</strong><br>";
            echo "Grand Total: <strong style='color: #800000;'>₱" . number_format($grandTotal, 2) . "</strong>";
            echo "</div>";
            
            echo "<h3>📝 Item Details:</h3>";
            echo "<table>";
            echo "<tr>";
            echo "<th>ID</th><th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th><th>Source</th>";
            echo "</tr>";
            
            $currentCategory = '';
            $categoryTotal = 0;
            
            foreach ($items as $item) {
                $amount = floatval($item['amount']);
                
                // Check if category changed
                if ($currentCategory !== $item['category']) {
                    // Show subtotal for previous category
                    if ($currentCategory !== '') {
                        echo "<tr style='background: #f0f0f0; font-weight: bold;'>";
                        echo "<td colspan='4' style='text-align: right;'>Sub-Total:</td>";
                        echo "<td>₱" . number_format($categoryTotal, 2) . "</td>";
                        echo "<td></td>";
                        echo "</tr>";
                    }
                    
                    // Reset for new category
                    $currentCategory = $item['category'];
                    $categoryTotal = 0;
                    
                    // Show category header
                    echo "<tr style='background: #800000; color: white; font-weight: bold;'>";
                    echo "<td colspan='6'>{$currentCategory}</td>";
                    echo "</tr>";
                }
                
                $categoryTotal += $amount;
                
                // Determine source
                $isPPMP = (strpos($item['particulars'], 'PPMP') !== false);
                $source = $isPPMP ? 'PPMP' : 'Manual';
                $rowClass = $isPPMP ? 'ppmp-item' : 'manual-item';
                
                echo "<tr class='$rowClass'>";
                echo "<td>{$item['id']}</td>";
                echo "<td>{$item['category']}</td>";
                echo "<td>{$item['particulars']}</td>";
                echo "<td>{$item['account_code']}</td>";
                echo "<td>₱" . number_format($amount, 2) . "</td>";
                echo "<td><strong>{$source}</strong></td>";
                echo "</tr>";
            }
            
            // Show last category subtotal
            if ($currentCategory !== '') {
                echo "<tr style='background: #f0f0f0; font-weight: bold;'>";
                echo "<td colspan='4' style='text-align: right;'>Sub-Total:</td>";
                echo "<td>₱" . number_format($categoryTotal, 2) . "</td>";
                echo "<td></td>";
                echo "</tr>";
            }
            
            // Show grand total
            echo "<tr style='background: #800000; color: white; font-weight: bold; font-size: 16px;'>";
            echo "<td colspan='4' style='text-align: right;'>GRAND TOTAL:</td>";
            echo "<td>₱" . number_format($grandTotal, 2) . "</td>";
            echo "<td></td>";
            echo "</tr>";
            
            echo "</table>";
        }
        
        // Show action buttons for older LIBs
        if (!$isNewest && count($libs) > 1) {
            echo "<div class='warning'>";
            echo "<strong>⚠️ This is an older LIB</strong><br>";
            echo "PPMP items will NOT sync to this LIB. They will sync to the newest LIB (ID: {$libs[0]['id']}).<br>";
            echo "Consider deleting this LIB if it's no longer needed.";
            echo "</div>";
        }
        
        echo "</div>";
    }
    
    // Show recommendations
    echo "<hr>";
    echo "<h2 style='color: #800000;'>💡 Recommendations</h2>";
    
    if (count($libs) > 1) {
        echo "<div class='error'>";
        echo "<h3>🚨 ACTION REQUIRED: Multiple LIBs Detected</h3>";
        echo "<p><strong>Problem:</strong> You have " . count($libs) . " LIBs for the same department and year.</p>";
        echo "<p><strong>Impact:</strong></p>";
        echo "<ul>";
        echo "<li>PPMP sync only updates the NEWEST LIB (ID: {$libs[0]['id']})</li>";
        echo "<li>If you're viewing an older LIB on the LIB page, you won't see synced PPMP items</li>";
        echo "<li>This causes confusion about where items are</li>";
        echo "</ul>";
        echo "<p><strong>Solution:</strong></p>";
        echo "<ol>";
        echo "<li><strong>Option A (Recommended):</strong> Delete the older LIBs and keep only the newest one</li>";
        echo "<li><strong>Option B:</strong> Manually merge items from older LIBs into the newest one, then delete the old ones</li>";
        echo "</ol>";
        
        // Show delete buttons for older LIBs
        echo "<form method='POST' action='delete_old_libs.php' style='margin-top: 20px;'>";
        echo "<p><strong>Quick Action:</strong> Delete older LIBs (keeps the newest one)</p>";
        for ($i = 1; $i < count($libs); $i++) {
            $oldLib = $libs[$i];
            echo "<label style='display: block; margin: 10px 0;'>";
            echo "<input type='checkbox' name='delete_libs[]' value='{$oldLib['id']}'> ";
            echo "Delete LIB ID {$oldLib['id']} (Created: {$oldLib['created_at']}, Status: {$oldLib['status']})";
            echo "</label>";
        }
        echo "<button type='submit' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete the selected LIBs? This cannot be undone!\")'>🗑️ Delete Selected LIBs</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "<h3>✅ Good News!</h3>";
        echo "<p>You have only ONE LIB for Computer Studies 2026, which is correct.</p>";
        
        // Check if it has both manual and PPMP items
        $itemsQuery = "SELECT * FROM line_item_budget_items WHERE lib_id = ?";
        $itemsStmt = $db->prepare($itemsQuery);
        $itemsStmt->execute([$libs[0]['id']]);
        $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        $hasManual = false;
        $hasPPMP = false;
        
        foreach ($items as $item) {
            if (strpos($item['particulars'], 'PPMP') !== false) {
                $hasPPMP = true;
            } else {
                $hasManual = true;
            }
        }
        
        if ($hasManual && $hasPPMP) {
            echo "<p>✅ Your LIB has both manual items and PPMP items - Perfect!</p>";
            echo "<p>Everything is working as expected.</p>";
        } elseif ($hasPPMP && !$hasManual) {
            echo "<p>⚠️ Your LIB only has PPMP items. If you had manual items before, they might have been accidentally deleted.</p>";
            echo "<p>Check if you need to re-add any manual items.</p>";
        } elseif ($hasManual && !$hasPPMP) {
            echo "<p>ℹ️ Your LIB only has manual items. PPMP items will be added when you save a PPMP with LIB mappings.</p>";
        } else {
            echo "<p>⚠️ Your LIB is empty. Add items manually or sync from PPMP.</p>";
        }
        echo "</div>";
    }
    
    // Show PPMP sync info
    echo "<div class='info'>";
    echo "<h3>ℹ️ How PPMP Sync Works</h3>";
    echo "<ul>";
    echo "<li>When you save a PPMP (draft or final) with LIB mappings, items are automatically synced to the LIB</li>";
    echo "<li>Sync ONLY updates the NEWEST LIB for the department/year (ordered by created_at DESC)</li>";
    echo "<li>Sync NEVER deletes manual items - it only adds/updates PPMP items</li>";
    echo "<li>Each PPMP item is identified by a reference like '(PPMP #1 - Item #1)'</li>";
    echo "<li>If a PPMP item already exists (same reference), it updates the amount</li>";
    echo "<li>If a PPMP item is new, it adds a new row</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<hr>";
    echo "<p><a href='pages/lib.php' class='btn'>← Back to LIB Page</a></p>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>Error:</strong> " . $e->getMessage() . "</div>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
