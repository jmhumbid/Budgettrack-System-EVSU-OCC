<?php
/**
 * Test script to debug LIB finalization fiscal year validation
 */

require_once __DIR__ . '/config/database.php';

echo "=== LIB PPMP Fiscal Year Validation Test ===\n\n";

try {
    $db = getDB();
    
    // Test parameters - adjust these
    $testLibId = 1; // Change to your LIB ID
    $testDepartmentId = 1; // Change to your department ID
    
    echo "Testing LIB ID: {$testLibId}\n";
    echo "Department ID: {$testDepartmentId}\n\n";
    
    // Step 1: Get LIB details
    echo "Step 1: Fetching LIB details...\n";
    $stmt = $db->prepare("SELECT * FROM line_item_budgets WHERE id = ?");
    $stmt->execute([$testLibId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        echo "❌ LIB not found!\n";
        exit;
    }
    
    echo "✓ LIB found\n";
    echo "  Fiscal Year: '{$lib['fiscal_year']}'\n";
    echo "  Department: {$lib['department_id']}\n";
    echo "  Status: {$lib['status']}\n\n";
    
    // Step 2: Check for finalized PPMPs
    echo "Step 2: Checking for finalized PPMPs...\n";
    echo "Looking for PPMPs with:\n";
    echo "  - department_id = {$lib['department_id']}\n";
    echo "  - fiscal_year = '{$lib['fiscal_year']}'\n";
    echo "  - is_final = 1\n";
    echo "  - status = 'approved'\n\n";
    
    // First, let's see ALL PPMPs for this department
    echo "All PPMPs for this department:\n";
    $stmt = $db->prepare("
        SELECT id, ppmp_number, fiscal_year, is_final, status, department_id
        FROM ppmp
        WHERE department_id = ?
        ORDER BY fiscal_year DESC, id DESC
    ");
    $stmt->execute([$lib['department_id']]);
    $allPPMPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($allPPMPs)) {
        echo "  ⚠️  No PPMPs found for this department\n\n";
    } else {
        foreach ($allPPMPs as $ppmp) {
            $finalStatus = $ppmp['is_final'] == 1 ? '✓ FINAL' : '✗ Draft';
            $approvedStatus = $ppmp['status'] == 'approved' ? '✓ Approved' : "✗ {$ppmp['status']}";
            echo "  - {$ppmp['ppmp_number']}: FY '{$ppmp['fiscal_year']}' | {$finalStatus} | {$approvedStatus}\n";
        }
        echo "\n";
    }
    
    // Now check for finalized PPMPs matching the LIB fiscal year
    echo "Finalized PPMPs matching LIB fiscal year:\n";
    $stmt = $db->prepare("
        SELECT id, ppmp_number, fiscal_year, is_final, status
        FROM ppmp
        WHERE department_id = ?
        AND fiscal_year = ?
        AND is_final = 1
        AND status = 'approved'
    ");
    $stmt->execute([$lib['department_id'], $lib['fiscal_year']]);
    $finalizedPPMPs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($finalizedPPMPs)) {
        echo "  ❌ No finalized PPMPs found matching fiscal year '{$lib['fiscal_year']}'\n\n";
        
        // Check for fiscal year format issues
        echo "Checking for fiscal year format mismatches...\n";
        $stmt = $db->prepare("
            SELECT DISTINCT fiscal_year
            FROM ppmp
            WHERE department_id = ?
            AND is_final = 1
            AND status = 'approved'
        ");
        $stmt->execute([$lib['department_id']]);
        $finalizedYears = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($finalizedYears)) {
            echo "  Found finalized PPMPs in these fiscal years:\n";
            foreach ($finalizedYears as $year) {
                echo "    - '{$year}'\n";
            }
            echo "\n";
            echo "  LIB fiscal year: '{$lib['fiscal_year']}'\n";
            echo "  ⚠️  Fiscal year format mismatch detected!\n\n";
        }
    } else {
        echo "  ✓ Found " . count($finalizedPPMPs) . " finalized PPMP(s):\n";
        foreach ($finalizedPPMPs as $ppmp) {
            echo "    - {$ppmp['ppmp_number']} (FY: '{$ppmp['fiscal_year']}')\n";
        }
        echo "\n";
    }
    
    // Step 3: Final result
    echo str_repeat("=", 50) . "\n";
    echo "FINAL RESULT:\n";
    echo str_repeat("=", 50) . "\n\n";
    
    if (empty($finalizedPPMPs)) {
        echo "❌ LIB CANNOT BE FINALIZED\n\n";
        echo "Reason: No finalized PPMP found for fiscal year '{$lib['fiscal_year']}'\n\n";
        echo "Possible issues:\n";
        echo "1. PPMP fiscal year format doesn't match LIB fiscal year format\n";
        echo "   LIB: '{$lib['fiscal_year']}'\n";
        if (!empty($finalizedYears)) {
            echo "   PPMP: '" . implode("', '", $finalizedYears) . "'\n";
        }
        echo "2. PPMP is not marked as final (is_final = 0)\n";
        echo "3. PPMP status is not 'approved'\n";
        echo "4. PPMP is in different department\n\n";
        
        echo "Action needed:\n";
        echo "- Check fiscal year format consistency\n";
        echo "- Ensure PPMP is marked as final\n";
        echo "- Ensure PPMP status is 'approved'\n";
    } else {
        echo "✅ LIB CAN BE FINALIZED\n\n";
        echo "Found " . count($finalizedPPMPs) . " finalized PPMP(s) for fiscal year '{$lib['fiscal_year']}'\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
