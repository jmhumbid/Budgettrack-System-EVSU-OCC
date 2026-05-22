<?php
/**
 * Installation script for Supplemental PPMP feature
 * This adds the ppmp_type column to distinguish between regular PPMP and Supplemental
 */

require_once __DIR__ . '/config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install Supplemental PPMP Feature</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; padding: 10px; background: #e8f5e9; border-left: 4px solid green; margin: 10px 0; }
        .error { color: red; padding: 10px; background: #ffebee; border-left: 4px solid red; margin: 10px 0; }
        .info { color: blue; padding: 10px; background: #e3f2fd; border-left: 4px solid blue; margin: 10px 0; }
        h1 { color: #333; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Supplemental PPMP Installation</h1>
    <p>This script will add supplemental PPMP support to your database.</p>
    
    <?php
    try {
        $db = getDB();
        
        echo '<div class="info">Starting installation...</div>';
        
        // Check if ppmp_type column already exists
        $checkColumn = $db->query("SHOW COLUMNS FROM ppmp LIKE 'ppmp_type'");
        
        if ($checkColumn->rowCount() > 0) {
            echo '<div class="info">Column ppmp_type already exists. Skipping creation.</div>';
        } else {
            echo '<div class="info">Adding ppmp_type column to ppmp table...</div>';
            
            // Add ppmp_type column
            $db->exec("
                ALTER TABLE ppmp 
                ADD COLUMN ppmp_type ENUM('ppmp', 'supplemental') DEFAULT 'ppmp' AFTER ppmp_number
            ");
            
            echo '<div class="success">✓ Added ppmp_type column</div>';
            
            // Update existing records
            $db->exec("UPDATE ppmp SET ppmp_type = 'ppmp' WHERE ppmp_type IS NULL");
            echo '<div class="success">✓ Updated existing records to ppmp type</div>';
        }
        
        // Add indexes if they don't exist
        echo '<div class="info">Adding indexes for better performance...</div>';
        
        try {
            $db->exec("ALTER TABLE ppmp ADD INDEX idx_ppmp_type (ppmp_type)");
            echo '<div class="success">✓ Added index on ppmp_type</div>';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo '<div class="info">Index idx_ppmp_type already exists</div>';
            } else {
                throw $e;
            }
        }
        
        try {
            $db->exec("ALTER TABLE ppmp ADD INDEX idx_dept_type (department_id, ppmp_type, fiscal_year)");
            echo '<div class="success">✓ Added composite index on department_id, ppmp_type, fiscal_year</div>';
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo '<div class="info">Index idx_dept_type already exists</div>';
            } else {
                throw $e;
            }
        }
        
        echo '<div class="success"><strong>Installation completed successfully!</strong></div>';
        echo '<div class="info">You can now use the Supplemental PPMP feature.</div>';
        
        // Show current structure
        echo '<h2>Current PPMP Table Structure:</h2>';
        $columns = $db->query("SHOW COLUMNS FROM ppmp")->fetchAll(PDO::FETCH_ASSOC);
        echo '<pre>';
        foreach ($columns as $col) {
            echo sprintf("%-20s %-30s %s\n", $col['Field'], $col['Type'], $col['Null'] === 'YES' ? 'NULL' : 'NOT NULL');
        }
        echo '</pre>';
        
    } catch (PDOException $e) {
        echo '<div class="error"><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    }
    ?>
    
    <p><a href="pages/ppmp.php">← Go to PPMP Page</a></p>
</body>
</html>
