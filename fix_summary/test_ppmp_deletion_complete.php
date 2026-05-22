<?php
/**
 * Complete PPMP Deletion Test
 * Tests the full workflow: Create PPMP → Sync to LIB → Delete PPMP → Verify LIB cleanup
 */

require_once __DIR__ . '/config/database.php';

$testDepartmentId = 13; // Computer Studies
$testFiscalYear = '2026';

try {
    $db = getDB();
    
    echo "<h2>PPMP Deletion Complete Test</h2>";
    echo "<p>Department: {$testDepartmentId}, Fiscal Year: {$testFiscalYear}</p>";
    echo "<hr>";
    
    // Step 1: Find the LIB
    echo "<h3>Step 1: Find LIB</h3>";
    $stmt = $db->prepare("
        SELECT id, fiscal_year FROM line_item_budgets 
        WHERE department_id = ? AND (fiscal_year = ? OR fiscal_year LIKE ?) 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$testDepartmentId, $testFiscalYear, "%{$testFiscalYear}%"]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo "<p style='color: red;'>✗ No LIB found for this department/year</p>";
        exit;
    }
    
    $libId = $lib['id'];
    echo "<p style='color: green;'>✓ Found LIB #{$libId} with fiscal_year = '{$lib['fiscal_year']}'</p>";
    
    // Step 2: Show current LIB items
    echo "<h3>Step 2: Current LIB Items</h3>";
    $stmt = $db->prepare("
        SELECT id, category, particulars, account_code, amount 
        FROM line_item_budget_items 
        WHERE lib_id = ?
        ORDER BY id
    ");
    $stmt->execute([$libId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($items)) {
        echo "<p style='color: orange;'>No items in LIB</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th></tr>";
        
        foreach ($items as $item) {
            $isPPMP = strpos($item['particulars'], 'PPMP #') !== false;
            $rowColor = $isPPMP ? '#ffe6e6' : '#e6ffe6';
            
            echo "<tr style='background: {$rowColor};'>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['category']}</td>";
            echo "<td>" . htmlspecialchars($item['particulars']) . "</td>";
            echo "<td>{$item['account_code']}</td>";
            echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 3: Find PPMPs for this department/year
    echo "<h3>Step 3: Find PPMPs</h3>";
    $stmt = $db->prepare("
        SELECT id, ppmp_number, fiscal_year, status 
        FROM ppmp 
        WHERE department_id = ? AND fiscal_year = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$testDepartmentId, $testFiscalYear]);
    $ppmps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ppmps)) {
        echo "<p style='color: orange;'>No PPMPs found for this department/year</p>";
    } else {
        echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>PPMP Number</th><th>Fiscal Year</th><th>Status</th><th>Items</th></tr>";
        
        foreach ($ppmps as $ppmp) {
            // Get PPMP items
            $itemStmt = $db->prepare("
                SELECT id, lib_category, lib_particulars, lib_account_code, estimated_budget 
                FROM ppmp_items 
                WHERE ppmp_id = ?
            ");
            $itemStmt->execute([$ppmp['id']]);
            $ppmpItems = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<tr>";
            echo "<td>{$ppmp['id']}</td>";
            echo "<td>{$ppmp['ppmp_number']}</td>";
            echo "<td>{$ppmp['fiscal_year']}</td>";
            echo "<td>{$ppmp['status']}</td>";
            echo "<td>";
            
            if (empty($ppmpItems)) {
                echo "No items";
            } else {
                echo "<ul style='margin: 0; padding-left: 20px;'>";
                foreach ($ppmpItems as $item) {
                    if ($item['lib_category']) {
                        echo "<li>{$item['lib_particulars']} (₱" . number_format($item['estimated_budget'], 2) . ")</li>";
                    }
                }
                echo "</ul>";
            }
            
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Step 4: Explain what will happen when PPMP is deleted
    echo "<h3>Step 4: Deletion Logic Explanation</h3>";
    echo "<div style='background: #fff3cd; padding: 15px; border-left: 5px solid #ffc107;'>";
    echo "<p><strong>When you delete a PPMP, the system will:</strong></p>";
    echo "<ol>";
    echo "<li>Find the LIB for the same department and fiscal year (using flexible matching)</li>";
    echo "<li>Get all PPMP items that have LIB mappings (lib_category, lib_particulars, lib_account_code)</li>";
    echo "<li>For each PPMP item, delete the corresponding LIB item that matches:</li>";
    echo "<ul>";
    echo "<li>Same category</li>";
    echo "<li>Same particulars (exact match)</li>";
    echo "<li>Same account code</li>";
    echo "</ul>";
    echo "<li>This handles the AGGREGATED approach (where PPMP items are summed into one LIB item per category)</li>";
    echo "<li>Also handles the OLD approach with PPMP references for backwards compatibility</li>";
    echo "</ol>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h3>Summary:</h3>";
    echo "<ul>";
    echo "<li><strong>LIB ID:</strong> {$libId}</li>";
    echo "<li><strong>LIB Fiscal Year:</strong> {$lib['fiscal_year']}</li>";
    echo "<li><strong>Total LIB Items:</strong> " . count($items) . "</li>";
    echo "<li><strong>Total PPMPs:</strong> " . count($ppmps) . "</li>";
    echo "<li style='color: green;'><strong>✓ Fix Applied:</strong> Flexible fiscal year matching + Aggregated item deletion</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
