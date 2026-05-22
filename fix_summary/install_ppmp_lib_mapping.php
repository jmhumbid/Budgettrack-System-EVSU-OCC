<?php
/**
 * Install PPMP-LIB Mapping Fields
 * This script adds the necessary fields to ppmp_items table for LIB integration
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Installing PPMP-LIB mapping fields...\n\n";
    
    // Check if columns already exist
    $checkQuery = "SHOW COLUMNS FROM ppmp_items LIKE 'lib_category'";
    $result = $db->query($checkQuery);
    
    if ($result->rowCount() > 0) {
        echo "✓ LIB mapping fields already exist in ppmp_items table.\n";
    } else {
        echo "Adding LIB mapping fields to ppmp_items table...\n";
        
        // Add lib_category column
        $db->exec("ALTER TABLE ppmp_items 
                   ADD COLUMN lib_category VARCHAR(100) DEFAULT NULL 
                   COMMENT 'LIB expense category (e.g., B. Maintenance & Other Operating Expenses)'");
        echo "✓ Added lib_category column\n";
        
        // Add lib_particulars column
        $db->exec("ALTER TABLE ppmp_items 
                   ADD COLUMN lib_particulars VARCHAR(255) DEFAULT NULL 
                   COMMENT 'LIB expense particulars (e.g., Office Supplies Expenses)'");
        echo "✓ Added lib_particulars column\n";
        
        // Add lib_account_code column
        $db->exec("ALTER TABLE ppmp_items 
                   ADD COLUMN lib_account_code VARCHAR(50) DEFAULT NULL 
                   COMMENT 'UACS account code for the expense'");
        echo "✓ Added lib_account_code column\n";
        
        // Add index for faster lookups
        try {
            $db->exec("ALTER TABLE ppmp_items 
                       ADD INDEX idx_lib_mapping (lib_category, lib_particulars, lib_account_code)");
            echo "✓ Added index for LIB mapping fields\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "✓ Index already exists\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✅ PPMP-LIB mapping installation completed successfully!\n\n";
    echo "You can now:\n";
    echo "1. Create a PPMP with items\n";
    echo "2. Link each PPMP item to a LIB expense category using the 'Link to LIB' button\n";
    echo "3. Save the PPMP (draft or final)\n";
    echo "4. Items will automatically sync to the existing draft LIB for that fiscal year\n\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
