<?php
session_start();
require_once __DIR__ . '/config/database.php';

// Get department ID from session
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : 'Unknown';

if (!$departmentId) {
    die("Error: No department ID in session. Please log in first.");
}

echo "<h1>Utilization Data Debug Report</h1>";
echo "<p><strong>Department:</strong> $departmentName (ID: $departmentId)</p>";
echo "<p><strong>Session User ID:</strong> " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<hr>";

try {
    $conn = getDB();
    
    // ===== CHECK UTILIZATION_SUMMARIES TABLE =====
    echo "<h2>1. UTILIZATION_SUMMARIES TABLE</h2>";
    $checkTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
    
    if ($checkTable->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table exists</p>";
        
        // Get all years for this department
        $yearsStmt = $conn->prepare("
            SELECT DISTINCT fiscal_year 
            FROM utilization_summaries 
            WHERE department_id = ?
            ORDER BY fiscal_year DESC
        ");
        $yearsStmt->execute([$departmentId]);
        $years = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
        
        if ($years) {
            echo "<p><strong>Available fiscal years:</strong> " . implode(', ', $years) . "</p>";
            
            // Get details for each year
            foreach ($years as $year) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
                echo "<h3>Fiscal Year: $year</h3>";
                
                $stmt = $conn->prepare("
                    SELECT id, totals, created_at, updated_at
                    FROM utilization_summaries 
                    WHERE department_id = ? AND fiscal_year = ?
                    ORDER BY updated_at DESC, created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$departmentId, $year]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($row) {
                    echo "<p><strong>Record ID:</strong> {$row['id']}</p>";
                    echo "<p><strong>Created:</strong> {$row['created_at']}</p>";
                    echo "<p><strong>Updated:</strong> {$row['updated_at']}</p>";
                    
                    if ($row['totals']) {
                        $totals = json_decode($row['totals'], true);
                        
                        if ($totals) {
                            echo "<p><strong>Total Balance:</strong> ";
                            if (isset($totals['totalBalance'])) {
                                echo "₱" . number_format($totals['totalBalance'], 2);
                            } else {
                                echo "<span style='color: red;'>NOT SET in JSON</span>";
                            }
                            echo "</p>";
                            
                            echo "<p><strong>Entry Count:</strong> ";
                            if (isset($totals['entries']) && is_array($totals['entries'])) {
                                echo count($totals['entries']);
                            } else {
                                echo "<span style='color: red;'>NOT SET or not an array</span>";
                            }
                            echo "</p>";
                            
                            echo "<details><summary>View Full JSON</summary>";
                            echo "<pre>" . json_encode($totals, JSON_PRETTY_PRINT) . "</pre>";
                            echo "</details>";
                        } else {
                            echo "<p style='color: red;'>ERROR: Could not decode JSON</p>";
                            echo "<p>Raw totals field: " . htmlspecialchars($row['totals']) . "</p>";
                        }
                    } else {
                        echo "<p style='color: red;'>ERROR: totals field is empty</p>";
                    }
                } else {
                    echo "<p style='color: red;'>No record found for this year</p>";
                }
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ No records found for department $departmentId</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table does not exist</p>";
    }
    
    echo "<hr>";
    
    // ===== CHECK BUDGET_UTILIZATION_ENTRIES TABLE =====
    echo "<h2>2. BUDGET_UTILIZATION_ENTRIES TABLE</h2>";
    $checkTable2 = $conn->query("SHOW TABLES LIKE 'budget_utilization_entries'");
    
    if ($checkTable2->rowCount() > 0) {
        echo "<p style='color: green;'>✓ Table exists</p>";
        
        // Get summary by fiscal year
        $stmt = $conn->prepare("
            SELECT fiscal_year, 
                   COUNT(*) as entry_count,
                   SUM(CAST(total_balance AS DECIMAL(15,2))) as total_balance,
                   MIN(created_at) as first_entry,
                   MAX(created_at) as last_entry
            FROM budget_utilization_entries 
            WHERE department_id = ?
            GROUP BY fiscal_year
            ORDER BY fiscal_year DESC
        ");
        $stmt->execute([$departmentId]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            echo "<p><strong>Found data for " . count($results) . " fiscal year(s)</strong></p>";
            
            foreach ($results as $row) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f9f9f9;'>";
                echo "<h3>Fiscal Year: {$row['fiscal_year']}</h3>";
                echo "<p><strong>Entry Count:</strong> {$row['entry_count']}</p>";
                echo "<p><strong>Total Balance:</strong> ₱" . number_format($row['total_balance'], 2) . "</p>";
                echo "<p><strong>First Entry:</strong> {$row['first_entry']}</p>";
                echo "<p><strong>Last Entry:</strong> {$row['last_entry']}</p>";
                
                // Show sample entries
                $sampleStmt = $conn->prepare("
                    SELECT id, total_balance, created_at
                    FROM budget_utilization_entries 
                    WHERE department_id = ? AND fiscal_year = ?
                    ORDER BY created_at DESC
                    LIMIT 3
                ");
                $sampleStmt->execute([$departmentId, $row['fiscal_year']]);
                $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if ($samples) {
                    echo "<details><summary>View Sample Entries (3 most recent)</summary>";
                    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                    echo "<tr><th>ID</th><th>Balance</th><th>Created</th></tr>";
                    foreach ($samples as $sample) {
                        echo "<tr>";
                        echo "<td>{$sample['id']}</td>";
                        echo "<td>₱" . number_format($sample['total_balance'], 2) . "</td>";
                        echo "<td>{$sample['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "</details>";
                }
                echo "</div>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ No records found for department $departmentId</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table does not exist</p>";
    }
    
    echo "<hr>";
    
    // ===== TEST API ENDPOINT =====
    echo "<h2>3. TEST API ENDPOINT</h2>";
    
    $testYears = [2025, 2026, date('Y')];
    foreach ($testYears as $testYear) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #f0f8ff;'>";
        echo "<h3>Testing Year: $testYear</h3>";
        
        $apiUrl = "api/get_utilization_amount.php?department_id=$departmentId&year=$testYear";
        echo "<p><strong>API URL:</strong> <code>$apiUrl</code></p>";
        
        // Simulate API call
        $_GET['department_id'] = $departmentId;
        $_GET['year'] = $testYear;
        
        ob_start();
        include __DIR__ . '/api/get_utilization_amount.php';
        $apiResponse = ob_get_clean();
        
        echo "<p><strong>API Response:</strong></p>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
        
        $decoded = json_decode($apiResponse, true);
        if ($decoded) {
            echo "<p><strong>Parsed Response:</strong></p>";
            echo "<ul>";
            echo "<li>Success: " . ($decoded['success'] ? 'Yes' : 'No') . "</li>";
            if (isset($decoded['amount'])) {
                echo "<li>Amount: ₱" . number_format($decoded['amount'], 2) . "</li>";
            }
            if (isset($decoded['count'])) {
                echo "<li>Count: {$decoded['count']}</li>";
            }
            if (isset($decoded['fiscal_year'])) {
                echo "<li>Fiscal Year: {$decoded['fiscal_year']}</li>";
            }
            if (isset($decoded['message'])) {
                echo "<li style='color: red;'>Message: {$decoded['message']}</li>";
            }
            echo "</ul>";
        }
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><em>Debug report generated at " . date('Y-m-d H:i:s') . "</em></p>";
?>
