<?php
/**
 * Diagnose fiscal year format mismatch between LIB and PPMP
 */

require_once __DIR__ . '/config/database.php';

echo "=== Fiscal Year Format Diagnosis ===\n\n";

try {
    $db = getDB();
    
    // Check LIB fiscal years
    echo "1. LIB Fiscal Year Formats:\n";
    $stmt = $db->query("SELECT DISTINCT fiscal_year, COUNT(*) as count FROM line_item_budgets GROUP BY fiscal_year ORDER BY fiscal_year DESC");
    $libYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($libYears)) {
        echo "   No LIBs found\n\n";
    } else {
        foreach ($libYears as $row) {
            echo "   '{$row['fiscal_year']}' ({$row['count']} LIBs)\n";
        }
        echo "\n";
    }
    
    // Check PPMP fiscal years
    echo "2. PPMP Fiscal Year Formats:\n";
    $stmt = $db->query("SELECT DISTINCT fiscal_year, COUNT(*) as count FROM ppmp GROUP BY fiscal_year ORDER BY fiscal_year DESC");
    $ppmpYears = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($ppmpYears)) {
        echo "   No PPMPs found\n\n";
    } else {
        foreach ($ppmpYears as $row) {
            echo "   '{$row['fiscal_year']}' ({$row['count']} PPMPs)\n";
        }
        echo "\n";
    }
    
    // Check finalized PPMPs
    echo "3. Finalized PPMPs by Fiscal Year:\n";
    $stmt = $db->query("
        SELECT fiscal_year, COUNT(*) as count, GROUP_CONCAT(ppmp_number SEPARATOR ', ') as ppmp_numbers
        FROM ppmp
        WHERE is_final = 1 AND status = 'approved'
        GROUP BY fiscal_year
        ORDER BY fiscal_year DESC
    ");
    $finalizedPPMPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalizedPPMPs)) {
        echo "   No finalized PPMPs found\n\n";
    } else {
        foreach ($finalizedPPMPs as $row) {
            echo "   '{$row['fiscal_year']}': {$row['count']} finalized ({$row['ppmp_numbers']})\n";
        }
        echo "\n";
    }
    
    // Check for format mismatches
    echo "4. Format Mismatch Analysis:\n";
    $libYearFormats = array_column($libYears, 'fiscal_year');
    $ppmpYearFormats = array_column($ppmpYears, 'fiscal_year');
    
    $mismatch = false;
    foreach ($libYearFormats as $libYear) {
        if (!in_array($libYear, $ppmpYearFormats)) {
            // Check if there's a similar year with different format
            foreach ($ppmpYearFormats as $ppmpYear) {
                if (strpos($libYear, $ppmpYear) !== false || strpos($ppmpYear, $libYear) !== false) {
                    echo "   ⚠️  Potential mismatch:\n";
                    echo "      LIB uses: '{$libYear}'\n";
                    echo "      PPMP uses: '{$ppmpYear}'\n";
                    $mismatch = true;
                }
            }
        }
    }
    
    if (!$mismatch) {
        echo "   ✓ No obvious format mismatches detected\n";
    }
    echo "\n";
    
    // Show sample data
    echo "5. Sample Data:\n";
    echo "\n   Recent LIBs:\n";
    $stmt = $db->query("SELECT id, fiscal_year, status, department_id FROM line_item_budgets ORDER BY id DESC LIMIT 5");
    $recentLIBs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recentLIBs as $lib) {
        echo "      LIB #{$lib['id']}: FY '{$lib['fiscal_year']}' | {$lib['status']} | Dept {$lib['department_id']}\n";
    }
    
    echo "\n   Recent PPMPs:\n";
    $stmt = $db->query("SELECT id, ppmp_number, fiscal_year, is_final, status, department_id FROM ppmp ORDER BY id DESC LIMIT 5");
    $recentPPMPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($recentPPMPs as $ppmp) {
        $final = $ppmp['is_final'] == 1 ? 'FINAL' : 'Draft';
        echo "      {$ppmp['ppmp_number']}: FY '{$ppmp['fiscal_year']}' | {$final} | {$ppmp['status']} | Dept {$ppmp['department_id']}\n";
    }
    
    echo "\n";
    echo "=== Diagnosis Complete ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
