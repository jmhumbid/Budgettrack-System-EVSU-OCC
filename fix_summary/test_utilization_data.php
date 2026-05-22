<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Set a test department ID - replace with your actual department ID
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : 1;

echo "<h2>Testing Utilization Data</h2>";
echo "<p>Department ID: $departmentId</p>";

try {
    $conn = getDB();
    
    // Check utilization_summaries table
    echo "<h3>1. Checking utilization_summaries table:</h3>";
    $checkTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Table exists<br>";
        
        $stmt = $conn->prepare("
            SELECT id, department_id, fiscal_year, totals, created_at, updated_at
            FROM utilization_summaries 
            WHERE department_id = ?
            ORDER BY fiscal_year DESC, updated_at DESC
        ");
        $stmt->execute([$departmentId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            echo "Found " . count($results) . " records:<br><br>";
            foreach ($results as $row) {
                echo "<strong>Fiscal Year: {$row['fiscal_year']}</strong><br>";
                echo "ID: {$row['id']}<br>";
                echo "Created: {$row['created_at']}<br>";
                echo "Updated: {$row['updated_at']}<br>";
                
                if ($row['totals']) {
                    $totals = json_decode($row['totals'], true);
                    echo "Totals JSON:<br>";
                    echo "<pre>" . print_r($totals, true) . "</pre>";
                    
                    if (isset($totals['totalBalance'])) {
                        echo "Total Balance: ₱" . number_format($totals['totalBalance'], 2) . "<br>";
                    }
                    if (isset($totals['entries'])) {
                        echo "Entry Count: " . count($totals['entries']) . "<br>";
                    }
                }
                echo "<hr>";
            }
        } else {
            echo "No records found for department $departmentId<br>";
        }
    } else {
        echo "✗ Table does not exist<br>";
    }
    
    // Check budget_utilization_entries table
    echo "<h3>2. Checking budget_utilization_entries table:</h3>";
    $checkTable2 = $conn->query("SHOW TABLES LIKE 'budget_utilization_entries'");
    if ($checkTable2->rowCount() > 0) {
        echo "✓ Table exists<br>";
        
        $stmt2 = $conn->prepare("
            SELECT fiscal_year, COUNT(*) as entry_count,
                   SUM(CAST(total_balance AS DECIMAL(15,2))) as total_balance
            FROM budget_utilization_entries 
            WHERE department_id = ?
            GROUP BY fiscal_year
            ORDER BY fiscal_year DESC
        ");
        $stmt2->execute([$departmentId]);
        $results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results2) {
            echo "Found data for " . count($results2) . " fiscal years:<br><br>";
            foreach ($results2 as $row) {
                echo "<strong>Fiscal Year: {$row['fiscal_year']}</strong><br>";
                echo "Entry Count: {$row['entry_count']}<br>";
                echo "Total Balance: ₱" . number_format($row['total_balance'], 2) . "<br>";
                echo "<hr>";
            }
        } else {
            echo "No records found for department $departmentId<br>";
        }
        
        // Show sample entries
        echo "<h4>Sample entries:</h4>";
        $stmt3 = $conn->prepare("
            SELECT id, fiscal_year, total_balance, created_at
            FROM budget_utilization_entries 
            WHERE department_id = ?
            ORDER BY fiscal_year DESC, created_at DESC
            LIMIT 5
        ");
        $stmt3->execute([$departmentId]);
        $samples = $stmt3->fetchAll(PDO::FETCH_ASSOC);
        
        if ($samples) {
            echo "<pre>" . print_r($samples, true) . "</pre>";
        }
    } else {
        echo "✗ Table does not exist<br>";
    }
    
    // Test the API endpoint
    echo "<h3>3. Testing API endpoint:</h3>";
    echo "<p>Testing for fiscal year 2025:</p>";
    
    $testYear = 2025;
    include __DIR__ . '/api/get_utilization_amount.php';
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
