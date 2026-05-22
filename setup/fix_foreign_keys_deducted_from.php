<?php
/**
 * Fix foreign key constraints to reference deducted_from_entry_id instead of id
 */

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDB();
    
    echo "<!DOCTYPE html><html><head><title>Fix Foreign Keys</title></head><body>";
    echo "<h2>Fixing Foreign Key Constraints</h2>";
    echo "<pre>";
    
    // Check if deducted_from_entry_id has a unique index in budget_utilization_entries
    $checkUnique = $db->query("SHOW INDEX FROM budget_utilization_entries WHERE Column_name = 'deducted_from_entry_id' AND Non_unique = 0");
    $hasUnique = $checkUnique->rowCount() > 0;
    
    if (!$hasUnique) {
        echo "⚠ deducted_from_entry_id does not have a unique index. Adding unique index...\n";
        try {
            $db->exec("ALTER TABLE budget_utilization_entries ADD UNIQUE INDEX unique_deducted_from_entry_id (deducted_from_entry_id)");
            echo "✓ Added unique index on deducted_from_entry_id\n";
        } catch (Exception $e) {
            echo "✗ Error adding unique index: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ deducted_from_entry_id has a unique index\n";
    }
    
    // Fix foreign keys for utilization_purchase_requests
    echo "\nFixing foreign key for utilization_purchase_requests...\n";
    try {
        // Drop old foreign key if it exists
        $db->exec("ALTER TABLE utilization_purchase_requests DROP FOREIGN KEY IF EXISTS fk_pr_entry");
        echo "✓ Dropped old foreign key\n";
        
        // Add new foreign key referencing deducted_from_entry_id
        $db->exec("ALTER TABLE utilization_purchase_requests 
            ADD CONSTRAINT fk_pr_entry 
            FOREIGN KEY (deducted_from_entry_id) 
            REFERENCES budget_utilization_entries(deducted_from_entry_id) 
            ON DELETE SET NULL");
        echo "✓ Added new foreign key referencing deducted_from_entry_id\n";
    } catch (Exception $e) {
        echo "⚠ Could not update foreign key: " . $e->getMessage() . "\n";
    }
    
    // Fix foreign keys for utilization_travels
    echo "\nFixing foreign key for utilization_travels...\n";
    try {
        // Drop old foreign key if it exists
        $db->exec("ALTER TABLE utilization_travels DROP FOREIGN KEY IF EXISTS fk_travel_entry");
        echo "✓ Dropped old foreign key\n";
        
        // Add new foreign key referencing deducted_from_entry_id
        $db->exec("ALTER TABLE utilization_travels 
            ADD CONSTRAINT fk_travel_entry 
            FOREIGN KEY (deducted_from_entry_id) 
            REFERENCES budget_utilization_entries(deducted_from_entry_id) 
            ON DELETE SET NULL");
        echo "✓ Added new foreign key referencing deducted_from_entry_id\n";
    } catch (Exception $e) {
        echo "⚠ Could not update foreign key: " . $e->getMessage() . "\n";
    }
    
    // Fix foreign keys for utilization_honoraria
    echo "\nFixing foreign key for utilization_honoraria...\n";
    try {
        // Drop old foreign key if it exists
        $db->exec("ALTER TABLE utilization_honoraria DROP FOREIGN KEY IF EXISTS fk_honoraria_entry");
        echo "✓ Dropped old foreign key\n";
        
        // Add new foreign key referencing deducted_from_entry_id
        $db->exec("ALTER TABLE utilization_honoraria 
            ADD CONSTRAINT fk_honoraria_entry 
            FOREIGN KEY (deducted_from_entry_id) 
            REFERENCES budget_utilization_entries(deducted_from_entry_id) 
            ON DELETE SET NULL");
        echo "✓ Added new foreign key referencing deducted_from_entry_id\n";
    } catch (Exception $e) {
        echo "⚠ Could not update foreign key: " . $e->getMessage() . "\n";
    }
    
    echo "\n✓ Foreign key constraints fixed!\n";
    echo "</pre></body></html>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

