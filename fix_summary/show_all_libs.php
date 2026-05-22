<?php
/**
 * Show ALL LIBs in the system
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "<h2>ALL LIBs in System</h2>";
    echo "<hr>";
    
    // Get all departments
    $stmt = $db->query("SELECT id, dept_name FROM departments ORDER BY dept_name");
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Departments:</h3>";
    echo "<ul>";
    foreach ($departments as $dept) {
        echo "<li>ID: {$dept['id']} - {$dept['dept_name']}</li>";
    }
    echo "</ul>";
    echo "<hr>";
    
    // Find ALL LIBs
    $stmt = $db->query("
        SELECT l.id, l.department_id, l.fiscal_year, l.status, l.created_at, d.dept_name
        FROM line_item_budgets l
        LEFT JOIN departments d ON l.department_id = d.id
        ORDER BY l.created_at DESC
    ");
    $libs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($libs)) {
        echo "<p style='color: orange;'>No LIBs found in the entire system!</p>";
        exit;
    }
    
    echo "<h3>Found " . count($libs) . " LIB(s) Total:</h3>";
    
    foreach ($libs as $lib) {
        echo "<div style='border: 3px solid #800000; padding: 15px; margin: 15px 0; background: #fff5f5;'>";
        echo "<h4 style='color: #800000;'>🗂️ LIB ID: {$lib['id']}</h4>";
        echo "<p><strong>Department:</strong> {$lib['dept_name']} (ID: {$lib['department_id']})</p>";
        echo "<p><strong>Fiscal Year:</strong> {$lib['fiscal_year']}</p>";
        echo "<p><strong>Status:</strong> {$lib['status']}</p>";
        echo "<p><strong>Created:</strong> {$lib['created_at']}</p>";
        
        // Get items count
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM line_item_budget_items WHERE lib_id = ?");
        $stmt->execute([$lib['id']]);
        $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        echo "<p><strong>Total Items:</strong> {$count}</p>";
        
        // Get items
        $stmt = $db->prepare("
            SELECT id, category, particulars, account_code, amount, source 
            FROM line_item_budget_items 
            WHERE lib_id = ?
            ORDER BY category, id
        ");
        $stmt->execute([$lib['id']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($items)) {
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
                echo "<h5 style='color: red; background: #ffe6e6; padding: 10px;'>⚠️ PPMP-Linked Items (" . count($ppmpItems) . "):</h5>";
                echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; background: white;'>";
                echo "<tr style='background: #ffcccc;'><th>ID</th><th>Category</th><th>Particulars</th><th>Amount</th></tr>";
                foreach ($ppmpItems as $item) {
                    echo "<tr>";
                    echo "<td>{$item['id']}</td>";
                    echo "<td>{$item['category']}</td>";
                    echo "<td><strong style='color: red;'>" . htmlspecialchars($item['particulars']) . "</strong></td>";
                    echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            if (!empty($manualItems)) {
                echo "<h5 style='color: green; background: #e6ffe6; padding: 10px; margin-top: 10px;'>✓ Manual Items (" . count($manualItems) . "):</h5>";
                echo "<table border='1' cellpadding='8' style='border-collapse: collapse; width: 100%; background: white;'>";
                echo "<tr style='background: #ccffcc;'><th>ID</th><th>Category</th><th>Particulars</th><th>Amount</th></tr>";
                foreach ($manualItems as $item) {
                    echo "<tr>";
                    echo "<td>{$item['id']}</td>";
                    echo "<td>{$item['category']}</td>";
                    echo "<td>" . htmlspecialchars($item['particulars']) . "</td>";
                    echo "<td>₱" . number_format($item['amount'], 2) . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
