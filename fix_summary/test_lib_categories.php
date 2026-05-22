<?php
/**
 * Test LIB Expense Categories API
 * Verifies that comprehensive categories are returned
 */

// Start session before including the API
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 1;
$_GET['department_id'] = 1;
$_GET['fiscal_year'] = 2026;

// Capture output
ob_start();
include 'api/get_lib_expense_categories.php';
$output = ob_get_clean();

// Remove PHP notices from output
$lines = explode("\n", $output);
$jsonLine = '';
foreach ($lines as $line) {
    if (strpos($line, '{') === 0) {
        $jsonLine = $line;
        break;
    }
}

// Decode JSON
$data = json_decode($jsonLine, true);

echo "=== LIB Expense Categories Test ===\n\n";

if ($data && isset($data['success']) && $data['success']) {
    echo "✅ API returned successfully\n\n";
    
    $categories = $data['categories'];
    $totalCount = 0;
    
    foreach ($categories as $categoryName => $expenses) {
        $count = count($expenses);
        $totalCount += $count;
        echo "📁 $categoryName ($count items)\n";
        
        // Show first 3 items as examples
        $shown = 0;
        foreach ($expenses as $expense) {
            if ($shown < 3) {
                echo "   - {$expense['name']} ({$expense['code']})\n";
                $shown++;
            }
        }
        if ($count > 3) {
            echo "   ... and " . ($count - 3) . " more\n";
        }
        echo "\n";
    }
    
    echo str_repeat("=", 50) . "\n";
    echo "Total Categories: " . count($categories) . "\n";
    echo "Total Expense Items: $totalCount\n";
    echo "\n";
    
    // Check for specific categories
    echo "Checking for key categories:\n";
    $keyCategories = [
        'Office Supplies Expenses',
        'Training Expenses',
        'Machinery and Equipment',
        'Traveling Expenses - Local'
    ];
    
    foreach ($keyCategories as $key) {
        $found = false;
        foreach ($categories as $expenses) {
            foreach ($expenses as $expense) {
                if ($expense['name'] === $key) {
                    echo "✅ Found: $key ({$expense['code']})\n";
                    $found = true;
                    break 2;
                }
            }
        }
        if (!$found) {
            echo "❌ Missing: $key\n";
        }
    }
    
} else {
    echo "❌ API failed\n";
    if (isset($data['message'])) {
        echo "Error: " . $data['message'] . "\n";
    } else {
        echo "Raw output:\n";
        echo $output . "\n";
    }
}
?>
