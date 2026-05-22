<?php
/**
 * Check LIB items to see what PPMP references exist
 */

require_once __DIR__ . '/config/database.php';

// CHANGE THIS to your department ID
$testDepartmentId = 1;

try {
    $db = getDB();
    
    echo "<h2>LIB Items Check - ALL Fiscal Years</h2>";
    echo "<p>Department ID: {$testDepartmentId}</p>";
    echo "<hr>";
    
    // Find ALL LIBs for this department
    $stmt = $db->prepare("
        SELECT id, fiscal_year, status, created_at 
        FROM line_item_budgets 
        WHERE department_id = ? 
        ORDER BY fiscal_year DESC, created_at DESC
    ");
    $stmt->execute([$testDepartmentId]);
    $libs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($libs)) {
        echo "<p style='color: orange;'>No LIBs found for department {$testDepartmentId}!</p>";
        exit;
    }
    
    echo "<h3>Found " . count($libs) . " LIB(s):</h3>";
    foreach ($libs as $lib) {
        echo "<div style='border: 2px solid #333; padding: 15px; margin: 15px 0; background: #f9f9f9;'>";
        echo "<h4 style='color: #800000;'>LIB ID: {$lib['id']} | Fiscal Year: {$lib['fiscal_year']} | Status: {$lib['status']} | Created: {$lib['created_at']}</h4>";
        
        // Get all items
        $stmt = $db->prepare("
            SELECT id, category, particulars, account_code, amount, source 
            FROM line_item_budget_items 
            WHERE lib_id = ?
            ORDER BY category, id
        ");
        $stmt->execute([$lib['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total items: " . count($items) . "</strong></p>";
        
        if (empty($items)) {
            echo "<p style='color: orange;'>No items in this LIB</p>";
        } else {
            // Separate PPMP items from manual items
            $ppmpItems = [];
            $manualItems = [];
            
            foreach ($items as $item) {
                if (strpos($item['particulars'], 'PPMP #') !== false) {
                    $ppmpItems[] = $item;
                } else {
                    $manualItems[] = $item;
                }
            }
            
            if (!empty($ppmpItems)) {
                echo "<h5 style='color: red; background: #ffe6e6; padding: 10px;'>⚠️ PPMP-Linked Items (" . count($ppmpItems) . ") - THESE SHOULD BE DELETED WHEN PPMP IS DELETED:</h5>";
                echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; background: white;'>";
                echo "<tr style='background: #ffcccc;'><th>ID</th><th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th><th>Source</th></tr>";
                foreach ($ppmpItems as $item) {
                    echo "<tr>";
                    echo "<td>{$item['id']}</td>";
                    echo "<td>{$item['category']}</td>";
                    echo "<td><strong style='color: red;'>" . htmlspecialchars($item['particulars']) . "</strong></td>";
                    echo "<td>{$item['account_code']}</td>";
                    echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
                    echo "<td>{$item['source']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            if (!empty($manualItems)) {
                echo "<h5 style='color: green; background: #e6ffe6; padding: 10px; margin-top: 15px;'>✓ Manual Items (" . count($manualItems) . ") - These are NOT deleted:</h5>";
                echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; background: white;'>";
                echo "<tr style='background: #ccffcc;'><th>ID</th><th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th><th>Source</th></tr>";
                foreach ($manualItems as $item) {
                    echo "<tr>";
                    echo "<td>{$item['id']}</td>";
                    echo "<td>{$item['category']}</td>";
                    echo "<td>" . htmlspecialchars($item['particulars']) . "</td>";
                    echo "<td>{$item['account_code']}</td>";
                    echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
                    echo "<td>{$item['source']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "</div>";
    }
    
    echo "<hr>";
    echo "<h3 style='background: #fff3cd; padding: 15px; border-left: 5px solid #ffc107;'>📋 What to look for:</h3>";
    echo "<ul>";
    echo "<li><strong style='color: red;'>PPMP-Linked Items:</strong> Have '(PPMP #X - Item #Y)' in Particulars - these SHOULD be deleted when you delete the PPMP</li>";
    echo "<li><strong style='color: green;'>Manual Items:</strong> Don't have PPMP reference - these stay even after PPMP deletion</li>";
    echo "<li><strong>If you see 'Office Supplies Expenses' with ₱0.00:</strong> That's likely a leftover row that should be removed</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
