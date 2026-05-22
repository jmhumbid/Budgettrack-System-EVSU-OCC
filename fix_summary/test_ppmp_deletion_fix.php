<?php
/**
 * Test PPMP Deletion and LIB Cleanup
 * This script simulates what happens when you delete a PPMP
 */

require_once __DIR__ . '/config/database.php';

$testDepartmentId = 13; // Computer Studies
$testFiscalYear = '2026';

try {
    $db = getDB();
    
    echo "<h2>PPMP Deletion Test - Before State</h2>";
    echo "<hr>";
    
    // Check current LIBs
    echo "<h3>Step 1: Find LIB for Department {$testDepartmentId}, Fiscal Year {$testFiscalYear}</h3>";
    
    // OLD WAY (doesn't work)
    $stmt = $db->prepare("
        SELECT id, fiscal_year FROM line_item_budgets 
        WHERE department_id = ? AND fiscal_year = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$testDepartmentId, $testFiscalYear]);
    $oldWayLib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($oldWayLib) {
        echo "<p style='color: green;'>✓ OLD WAY (exact match): Found LIB #{$oldWayLib['id']} with fiscal_year = '{$oldWayLib['fiscal_year']}'</p>";
    } else {
        echo "<p style='color: red;'>✗ OLD WAY (exact match): No LIB found!</p>";
    }
    
    // NEW WAY (should work)
    $stmt = $db->prepare("
        SELECT id, fiscal_year FROM line_item_budgets 
        WHERE department_id = ? AND (fiscal_year = ? OR fiscal_year LIKE ?) 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$testDepartmentId, $testFiscalYear, "%{$testFiscalYear}%"]);
    $newWayLib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($newWayLib) {
        echo "<p style='color: green;'>✓ NEW WAY (flexible match): Found LIB #{$newWayLib['id']} with fiscal_year = '{$newWayLib['fiscal_year']}'</p>";
        
        // Show items in this LIB
        echo "<h3>Step 2: Items in LIB #{$newWayLib['id']}</h3>";
        $stmt = $db->prepare("
            SELECT id, category, particulars, amount 
            FROM line_item_budget_items 
            WHERE lib_id = ?
            ORDER BY id
        ");
        $stmt->execute([$newWayLib['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($items)) {
            echo "<p style='color: orange;'>No items in this LIB</p>";
        } else {
            echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Category</th><th>Particulars</th><th>Amount</th><th>Type</th></tr>";
            
            foreach ($items as $item) {
                $isPPMP = strpos($item['particulars'], 'PPMP #') !== false;
                $rowColor = $isPPMP ? '#ffe6e6' : '#e6ffe6';
                $type = $isPPMP ? 'PPMP-Linked' : 'Manual';
                
                echo "<tr style='background: {$rowColor};'>";
                echo "<td>{$item['id']}</td>";
                echo "<td>{$item['category']}</td>";
                echo "<td>" . htmlspecialchars($item['particulars']) . "</td>";
                echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
                echo "<td><strong>{$type}</strong></td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "<p style='color: red;'>✗ NEW WAY (flexible match): No LIB found!</p>";
    }
    
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>Department ID:</strong> {$testDepartmentId}</li>";
    echo "<li><strong>Fiscal Year (PPMP):</strong> {$testFiscalYear}</li>";
    if ($newWayLib) {
        echo "<li><strong>Fiscal Year (LIB):</strong> {$newWayLib['fiscal_year']}</li>";
        echo "<li><strong>LIB ID:</strong> {$newWayLib['id']}</li>";
        echo "<li style='color: green;'><strong>✓ Fix Status:</strong> The flexible matching will now find this LIB when deleting PPMP!</li>";
    } else {
        echo "<li style='color: red;'><strong>✗ Fix Status:</strong> Still no LIB found - there may be no LIB for this department/year</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
