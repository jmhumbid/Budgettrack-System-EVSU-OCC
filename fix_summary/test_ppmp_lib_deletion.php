<?php
/**
 * Test script to verify PPMP-LIB deletion logic
 * Run this to check if LIB items are being found and deleted correctly
 */

require_once __DIR__ . '/config/database.php';

// Test parameters - CHANGE THESE to match your test data
$testPpmpId = 53;  // The PPMP ID you're trying to delete
$testDepartmentId = 1;  // Your department ID

try {
    $db = getDB();
    
    echo "<h2>PPMP-LIB Deletion Test</h2>";
    echo "<hr>";
    
    // Get PPMP details
    $stmt = $db->prepare("SELECT * FROM ppmp WHERE id = ?");
    $stmt->execute([$testPpmpId]);
    $ppmp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        echo "<p style='color: red;'>PPMP ID {$testPpmpId} not found!</p>";
        exit;
    }
    
    echo "<h3>PPMP Details:</h3>";
    echo "<ul>";
    echo "<li>ID: {$ppmp['id']}</li>";
    echo "<li>Number: {$ppmp['ppmp_number']}</li>";
    echo "<li>Fiscal Year: {$ppmp['fiscal_year']}</li>";
    echo "<li>Department ID: {$ppmp['department_id']}</li>";
    echo "<li>Status: {$ppmp['status']}</li>";
    echo "</ul>";
    
    $ppmpNumber = $ppmp['ppmp_number'];
    $fiscalYear = $ppmp['fiscal_year'];
    $departmentId = $ppmp['department_id'];
    
    // Find corresponding LIB
    $stmt = $db->prepare("
        SELECT id, fiscal_year, status, created_at 
        FROM line_item_budgets 
        WHERE department_id = ? AND fiscal_year = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$departmentId, $fiscalYear]);
    $libs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Found LIBs for Department {$departmentId}, Fiscal Year {$fiscalYear}:</h3>";
    if (empty($libs)) {
        echo "<p style='color: orange;'>No LIBs found!</p>";
    } else {
        echo "<ul>";
        foreach ($libs as $lib) {
            echo "<li>LIB ID: {$lib['id']}, Status: {$lib['status']}, Created: {$lib['created_at']}</li>";
        }
        echo "</ul>";
        
        $libId = $libs[0]['id'];
        echo "<p><strong>Using LIB ID: {$libId}</strong></p>";
        
        // Get all LIB items
        $stmt = $db->prepare("
            SELECT id, category, particulars, account_code, amount 
            FROM line_item_budget_items 
            WHERE lib_id = ?
            ORDER BY id
        ");
        $stmt->execute([$libId]);
        $allItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>All LIB Items (Total: " . count($allItems) . "):</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Category</th><th>Particulars</th><th>Account Code</th><th>Amount</th></tr>";
        foreach ($allItems as $item) {
            echo "<tr>";
            echo "<td>{$item['id']}</td>";
            echo "<td>{$item['category']}</td>";
            echo "<td>{$item['particulars']}</td>";
            echo "<td>{$item['account_code']}</td>";
            echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test pattern matching
        $patterns = [
            "(PPMP #{$ppmpNumber} - Item #%",
            "(PPMP #" . $ppmpNumber . " - Item #%",
            "%PPMP #{$ppmpNumber}%",
            "%PPMP #" . $ppmpNumber . "%",
        ];
        
        echo "<h3>Pattern Matching Test:</h3>";
        foreach ($patterns as $pattern) {
            $stmt = $db->prepare("
                SELECT id, particulars, amount 
                FROM line_item_budget_items 
                WHERE lib_id = ? AND particulars LIKE ?
            ");
            $stmt->execute([$libId, $pattern]);
            $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h4>Pattern: <code>" . htmlspecialchars($pattern) . "</code></h4>";
            if (empty($matches)) {
                echo "<p style='color: orange;'>No matches found</p>";
            } else {
                echo "<p style='color: green;'>Found " . count($matches) . " matches:</p>";
                echo "<ul>";
                foreach ($matches as $match) {
                    echo "<li>ID {$match['id']}: {$match['particulars']} - ₱" . number_format($match['amount'], 2) . "</li>";
                }
                echo "</ul>";
            }
        }
    }
    
    echo "<hr>";
    echo "<p><strong>Note:</strong> This is a READ-ONLY test. No data was deleted.</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
