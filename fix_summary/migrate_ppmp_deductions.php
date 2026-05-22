<?php
/**
 * Migration Script: Create PPMP Deductions from Existing Utilization Summaries
 * 
 * This script processes existing utilization summaries and creates ppmp_deductions records
 * to link expense categories to PPMP items, so they appear in the PPMP remarks column.
 * 
 * Run this once to populate existing data.
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>PPMP Deductions Migration</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}.warning{color:orange;}";
echo "pre{background:#fff;padding:10px;border:1px solid #ddd;border-radius:4px;}</style></head><body>";
echo "<h1>PPMP Deductions Migration</h1>";
echo "<p>Creating ppmp_deductions records from existing utilization summaries...</p><hr>";

try {
    $db = getDB();
    $db->beginTransaction();
    
    // First, fix the foreign key constraint if needed
    echo "<h3>Step 1: Checking and fixing foreign key constraints...</h3>";
    
    $checkTable = $db->query("SHOW TABLES LIKE 'ppmp_deductions'");
    if ($checkTable->rowCount() > 0) {
        // Get existing foreign keys
        $fkQuery = "
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'ppmp_deductions' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ";
        $fkStmt = $db->query($fkQuery);
        $foreignKeys = $fkStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<p class='info'>Found " . count($foreignKeys) . " foreign keys.</p>";
        
        // Drop ALL foreign key constraints
        foreach ($foreignKeys as $fkName) {
            echo "<p class='info'>Dropping foreign key: {$fkName}</p>";
            try {
                $db->exec("ALTER TABLE ppmp_deductions DROP FOREIGN KEY `{$fkName}`");
                echo "<p class='success'>✓ Dropped: {$fkName}</p>";
            } catch (Exception $e) {
                echo "<p class='warning'>Could not drop {$fkName}: " . $e->getMessage() . "</p>";
            }
        }
        
        // Add correct foreign keys
        echo "<p class='info'>Adding correct foreign key constraints...</p>";
        
        try {
            $db->exec("ALTER TABLE ppmp_deductions ADD CONSTRAINT fk_ppmp_deductions_ppmp FOREIGN KEY (ppmp_id) REFERENCES ppmp(id) ON DELETE CASCADE");
            echo "<p class='success'>✓ Added FK for ppmp_id</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>FK ppmp_id: " . $e->getMessage() . "</p>";
        }
        
        try {
            $db->exec("ALTER TABLE ppmp_deductions ADD CONSTRAINT fk_ppmp_deductions_ppmp_item FOREIGN KEY (ppmp_item_id) REFERENCES ppmp_items(id) ON DELETE CASCADE");
            echo "<p class='success'>✓ Added FK for ppmp_item_id</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>FK ppmp_item_id: " . $e->getMessage() . "</p>";
        }
        
        try {
            $db->exec("ALTER TABLE ppmp_deductions ADD CONSTRAINT fk_ppmp_deductions_pr FOREIGN KEY (purchase_request_id) REFERENCES utilization_purchase_requests(id) ON DELETE CASCADE");
            echo "<p class='success'>✓ Added FK for purchase_request_id → utilization_purchase_requests</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>FK purchase_request_id: " . $e->getMessage() . "</p>";
        }
        
        try {
            $db->exec("ALTER TABLE ppmp_deductions ADD CONSTRAINT fk_ppmp_deductions_dept FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE");
            echo "<p class='success'>✓ Added FK for department_id</p>";
        } catch (Exception $e) {
            echo "<p class='warning'>FK department_id: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<hr><h3>Step 2: Creating ppmp_deductions records...</h3>";
    
    $totalProcessed = 0;
    $totalCreated = 0;
    $totalSkipped = 0;
    $errors = [];
    
    // Get all utilization summaries
    $summariesQuery = "SELECT * FROM utilization_summaries ORDER BY fiscal_year DESC, department_id";
    $summariesStmt = $db->query($summariesQuery);
    $summaries = $summariesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p class='info'>Found " . count($summaries) . " utilization summaries to process.</p>";
    
    foreach ($summaries as $summary) {
        $departmentId = $summary['department_id'];
        $fiscalYear = $summary['fiscal_year'];
        $departmentName = $summary['department_name'];
        
        echo "<h3>Processing: {$departmentName} (Dept ID: {$departmentId}, Fiscal Year: {$fiscalYear})</h3>";
        
        // Decode PR deductions
        $prDeductions = json_decode($summary['pr_deductions'], true);
        
        if (empty($prDeductions)) {
            echo "<p class='warning'>No PR deductions found in this summary. Skipping.</p>";
            continue;
        }
        
        echo "<p class='info'>Found " . count($prDeductions) . " expense categories with PR deductions.</p>";
        
        // Process each expense category
        foreach ($prDeductions as $deduction) {
            $expenseCategory = $deduction['category'] ?? '';
            $items = $deduction['items'] ?? [];
            
            if (empty($expenseCategory) || empty($items)) {
                echo "<p class='warning'>Skipping deduction with missing category or items.</p>";
                continue;
            }
            
            echo "<p><strong>Category:</strong> {$expenseCategory} (" . count($items) . " items)</p>";
            
            // Process each PR item in this category
            foreach ($items as $item) {
                $purchaseRequest = $item['purchaseRequest'] ?? '';
                $amount = $item['amount'] ?? 0;
                
                $totalProcessed++;
                
                if (empty($purchaseRequest) || $purchaseRequest === 'N/A' || $amount <= 0) {
                    echo "<p class='warning'>  - Skipping invalid item: {$purchaseRequest}</p>";
                    $totalSkipped++;
                    continue;
                }
                
                // Extract just the item name (before the comma if it exists)
                // PR format: "Item Name, Type: Goods, Qty: 1.00, Unit: pcs, Amount: ₱200.00"
                // PPMP format: "Item Name"
                $itemName = $purchaseRequest;
                if (strpos($purchaseRequest, ',') !== false) {
                    $itemName = trim(explode(',', $purchaseRequest)[0]);
                }
                
                // Find matching PPMP items by description
                $ppmpItemQuery = "
                    SELECT pi.id, pi.ppmp_id, pi.general_description
                    FROM ppmp_items pi
                    INNER JOIN ppmp p ON pi.ppmp_id = p.id
                    WHERE p.department_id = :department_id 
                    AND p.fiscal_year = :fiscal_year
                    AND (pi.general_description LIKE :description 
                         OR pi.general_description LIKE :description_exact)
                    LIMIT 1
                ";
                
                $ppmpItemStmt = $db->prepare($ppmpItemQuery);
                $ppmpItemStmt->execute([
                    ':department_id' => $departmentId,
                    ':fiscal_year' => $fiscalYear,
                    ':description' => '%' . $itemName . '%',
                    ':description_exact' => $itemName
                ]);
                
                $ppmpItem = $ppmpItemStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($ppmpItem) {
                    // Try to find the actual purchase_request_id from the database
                    // Match by description and amount
                    $prQuery = "
                        SELECT id FROM utilization_purchase_requests 
                        WHERE department_id = :department_id 
                        AND fiscal_year = :fiscal_year
                        AND (purchase_request LIKE :description1 OR particulars LIKE :description2)
                        AND amount = :amount
                        LIMIT 1
                    ";
                    
                    $prStmt = $db->prepare($prQuery);
                    $searchPattern = '%' . $itemName . '%';
                    $prStmt->execute([
                        ':department_id' => $departmentId,
                        ':fiscal_year' => $fiscalYear,
                        ':description1' => $searchPattern,
                        ':description2' => $searchPattern,
                        ':amount' => $amount
                    ]);
                    
                    $prRecord = $prStmt->fetch(PDO::FETCH_ASSOC);
                    $purchaseRequestId = $prRecord ? $prRecord['id'] : null;
                    
                    // Check if deduction record already exists
                    $checkDeductionQuery = "
                        SELECT id FROM ppmp_deductions 
                        WHERE ppmp_id = :ppmp_id 
                        AND ppmp_item_id = :ppmp_item_id 
                        AND expense_category = :expense_category
                        AND fiscal_year = :fiscal_year
                    ";
                    
                    $checkDeductionStmt = $db->prepare($checkDeductionQuery);
                    $checkDeductionStmt->execute([
                        ':ppmp_id' => $ppmpItem['ppmp_id'],
                        ':ppmp_item_id' => $ppmpItem['id'],
                        ':expense_category' => $expenseCategory,
                        ':fiscal_year' => $fiscalYear
                    ]);
                    
                    $existingDeduction = $checkDeductionStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!$existingDeduction && $purchaseRequestId) {
                        // Create new deduction record only if we have a valid PR ID
                        $insertDeductionQuery = "
                            INSERT INTO ppmp_deductions 
                            (ppmp_id, ppmp_item_id, purchase_request_id, utilization_entry_id, department_id, expense_category, amount, fiscal_year, created_at)
                            VALUES 
                            (:ppmp_id, :ppmp_item_id, :purchase_request_id, :utilization_entry_id, :department_id, :expense_category, :amount, :fiscal_year, NOW())
                        ";
                        
                        $insertDeductionStmt = $db->prepare($insertDeductionQuery);
                        $insertDeductionStmt->execute([
                            ':ppmp_id' => $ppmpItem['ppmp_id'],
                            ':ppmp_item_id' => $ppmpItem['id'],
                            ':purchase_request_id' => $purchaseRequestId,
                            ':utilization_entry_id' => 0,
                            ':department_id' => $departmentId,
                            ':expense_category' => $expenseCategory,
                            ':amount' => $amount,
                            ':fiscal_year' => $fiscalYear
                        ]);
                        
                        echo "<p class='success'>  ✓ Created deduction: \"{$ppmpItem['general_description']}\" → {$expenseCategory} (₱" . number_format($amount, 2) . ")</p>";
                        $totalCreated++;
                    } elseif (!$existingDeduction && !$purchaseRequestId) {
                        echo "<p class='warning'>  ⚠ No matching purchase request found in database for: \"{$itemName}\" (Amount: ₱" . number_format($amount, 2) . ")</p>";
                        $errors[] = "No PR record: {$itemName} (Dept: {$departmentName}, FY: {$fiscalYear}, Amount: ₱" . number_format($amount, 2) . ")";
                        $totalSkipped++;
                    } else {
                        echo "<p class='info'>  - Already exists: \"{$ppmpItem['general_description']}\" → {$expenseCategory}</p>";
                        $totalSkipped++;
                    }
                } else {
                    echo "<p class='warning'>  ⚠ No matching PPMP item found for: \"{$purchaseRequest}\"</p>";
                    $errors[] = "No PPMP match: {$purchaseRequest} (Dept: {$departmentName}, FY: {$fiscalYear})";
                    $totalSkipped++;
                }
            }
        }
        
        echo "<hr>";
    }
    
    $db->commit();
    
    echo "<h2 class='success'>Migration Complete!</h2>";
    echo "<p><strong>Total Items Processed:</strong> {$totalProcessed}</p>";
    echo "<p><strong>Deductions Created:</strong> {$totalCreated}</p>";
    echo "<p><strong>Items Skipped:</strong> {$totalSkipped}</p>";
    
    if (!empty($errors)) {
        echo "<h3 class='warning'>Warnings/Errors:</h3>";
        echo "<pre>" . implode("\n", $errors) . "</pre>";
    }
    
    echo "<p class='success'>✓ All ppmp_deductions records have been created successfully!</p>";
    echo "<p class='info'>You can now view your PPMP and the remarks column will show the expense categories.</p>";
    echo "<p><a href='pages/ppmp.php'>Go to PPMP Page</a></p>";
    
} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    echo "<h2 class='error'>Error!</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
