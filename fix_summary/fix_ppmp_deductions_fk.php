<?php
/**
 * Fix ppmp_deductions Foreign Key Constraint
 * 
 * This script updates the foreign key constraint on ppmp_deductions.purchase_request_id
 * to reference utilization_purchase_requests instead of purchase_requests
 */

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html><html><head><title>Fix PPMP Deductions FK</title>";
echo "<style>body{font-family:Arial,sans-serif;padding:20px;background:#f5f5f5;}";
echo ".success{color:green;}.error{color:red;}.info{color:blue;}</style></head><body>";
echo "<h1>Fix PPMP Deductions Foreign Key</h1>";
echo "<p>Updating foreign key constraint...</p><hr>";

try {
    $db = getDB();
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'ppmp_deductions'");
    if ($checkTable->rowCount() == 0) {
        echo "<p class='info'>Table ppmp_deductions does not exist yet. No fix needed.</p>";
        echo "<p><a href='migrate_ppmp_deductions.php'>Run Migration Script</a></p>";
        echo "</body></html>";
        exit;
    }
    
    echo "<p class='info'>Table ppmp_deductions exists. Checking foreign keys...</p>";
    
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
    
    echo "<p class='info'>Found " . count($foreignKeys) . " foreign keys: " . implode(', ', $foreignKeys) . "</p>";
    
    // Drop ALL foreign key constraints to start fresh
    foreach ($foreignKeys as $fkName) {
        echo "<p class='info'>Dropping foreign key: {$fkName}</p>";
        try {
            $db->exec("ALTER TABLE ppmp_deductions DROP FOREIGN KEY `{$fkName}`");
            echo "<p class='success'>✓ Dropped foreign key: {$fkName}</p>";
        } catch (Exception $e) {
            echo "<p class='error'>Failed to drop {$fkName}: " . $e->getMessage() . "</p>";
        }
    }
    
    // Add the correct foreign key constraints
    echo "<p class='info'>Adding new foreign key constraints...</p>";
    
    // FK for ppmp_id
    try {
        $db->exec("
            ALTER TABLE ppmp_deductions 
            ADD CONSTRAINT fk_ppmp_deductions_ppmp 
            FOREIGN KEY (ppmp_id) 
            REFERENCES ppmp(id) 
            ON DELETE CASCADE
        ");
        echo "<p class='success'>✓ Added FK for ppmp_id</p>";
    } catch (Exception $e) {
        echo "<p class='error'>FK for ppmp_id: " . $e->getMessage() . "</p>";
    }
    
    // FK for ppmp_item_id
    try {
        $db->exec("
            ALTER TABLE ppmp_deductions 
            ADD CONSTRAINT fk_ppmp_deductions_ppmp_item 
            FOREIGN KEY (ppmp_item_id) 
            REFERENCES ppmp_items(id) 
            ON DELETE CASCADE
        ");
        echo "<p class='success'>✓ Added FK for ppmp_item_id</p>";
    } catch (Exception $e) {
        echo "<p class='error'>FK for ppmp_item_id: " . $e->getMessage() . "</p>";
    }
    
    // FK for purchase_request_id (pointing to utilization_purchase_requests)
    try {
        $db->exec("
            ALTER TABLE ppmp_deductions 
            ADD CONSTRAINT fk_ppmp_deductions_pr 
            FOREIGN KEY (purchase_request_id) 
            REFERENCES utilization_purchase_requests(id) 
            ON DELETE CASCADE
        ");
        echo "<p class='success'>✓ Added FK for purchase_request_id → utilization_purchase_requests</p>";
    } catch (Exception $e) {
        echo "<p class='error'>FK for purchase_request_id: " . $e->getMessage() . "</p>";
    }
    
    // FK for department_id
    try {
        $db->exec("
            ALTER TABLE ppmp_deductions 
            ADD CONSTRAINT fk_ppmp_deductions_dept 
            FOREIGN KEY (department_id) 
            REFERENCES departments(id) 
            ON DELETE CASCADE
        ");
        echo "<p class='success'>✓ Added FK for department_id</p>";
    } catch (Exception $e) {
        echo "<p class='error'>FK for department_id: " . $e->getMessage() . "</p>";
    }
    
    echo "<h2 class='success'>Success!</h2>";
    echo "<p class='success'>✓ Foreign key constraints updated successfully!</p>";
    echo "<p class='info'>The ppmp_deductions table now correctly references utilization_purchase_requests.</p>";
    echo "<p><a href='migrate_ppmp_deductions.php'>Run Migration Script</a></p>";
    
} catch (Exception $e) {
    echo "<h2 class='error'>Error!</h2>";
    echo "<p class='error'>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
