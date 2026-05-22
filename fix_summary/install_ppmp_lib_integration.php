<?php
/**
 * Installation script for PPMP-LIB Integration
 * This script adds the necessary database columns and tables for linking PPMP items to LIB expenses
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "Starting PPMP-LIB Integration Installation...\n\n";
    
    // Step 1: Add LIB integration columns to ppmp_items table
    echo "Step 1: Adding LIB integration columns to ppmp_items table...\n";
    
    $columns = [
        "ALTER TABLE ppmp_items ADD COLUMN IF NOT EXISTS lib_category VARCHAR(255) DEFAULT NULL COMMENT 'LIB category (A/B/C)'",
        "ALTER TABLE ppmp_items ADD COLUMN IF NOT EXISTS lib_particulars VARCHAR(500) DEFAULT NULL COMMENT 'LIB expense description'",
        "ALTER TABLE ppmp_items ADD COLUMN IF NOT EXISTS lib_account_code VARCHAR(50) DEFAULT NULL COMMENT 'UACS account code'",
        "ALTER TABLE ppmp_items ADD COLUMN IF NOT EXISTS lib_synced TINYINT(1) DEFAULT 0 COMMENT 'Whether synced to LIB'"
    ];
    
    foreach ($columns as $sql) {
        try {
            $db->exec($sql);
            echo "  ✓ Column added successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "  ℹ Column already exists, skipping\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Step 2: Create indexes
    echo "\nStep 2: Creating indexes...\n";
    
    $indexes = [
        "CREATE INDEX IF NOT EXISTS idx_ppmp_lib_sync ON ppmp_items(lib_synced, ppmp_id)",
        "CREATE INDEX IF NOT EXISTS idx_ppmp_lib_category ON ppmp_items(lib_category)"
    ];
    
    foreach ($indexes as $sql) {
        try {
            $db->exec($sql);
            echo "  ✓ Index created successfully\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "  ℹ Index already exists, skipping\n";
            } else {
                throw $e;
            }
        }
    }
    
    // Step 3: Create ppmp_lib_mappings table
    echo "\nStep 3: Creating ppmp_lib_mappings table...\n";
    
    $createTableSQL = "
    CREATE TABLE IF NOT EXISTS ppmp_lib_mappings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        ppmp_id INT NOT NULL,
        ppmp_item_id INT NOT NULL,
        lib_id INT DEFAULT NULL,
        lib_item_id INT DEFAULT NULL,
        fiscal_year VARCHAR(20) NOT NULL,
        department_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_ppmp_id (ppmp_id),
        INDEX idx_ppmp_item_id (ppmp_item_id),
        INDEX idx_lib_id (lib_id),
        INDEX idx_lib_item_id (lib_item_id),
        UNIQUE KEY unique_ppmp_item (ppmp_item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ";
    
    try {
        $db->exec($createTableSQL);
        echo "  ✓ Table created successfully\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "  ℹ Table already exists, skipping\n";
        } else {
            throw $e;
        }
    }
    
    echo "\n✅ PPMP-LIB Integration installation completed successfully!\n\n";
    echo "Next steps:\n";
    echo "1. Users can now link PPMP items to LIB expense categories when creating PPMPs\n";
    echo "2. When a PPMP is marked as FINAL, items will automatically sync to the LIB\n";
    echo "3. LIB will be automatically created or updated with PPMP budget allocations\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error during installation: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
?>
