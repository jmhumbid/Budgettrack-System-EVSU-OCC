<?php
/**
 * Database Migration Script
 * Adds PPMP reference columns to utilization_purchase_requests table
 * 
 * Run this file once by accessing it in your browser:
 * http://localhost/BudgetTrack/migrate_ppmp_columns.php
 */

session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    die('Unauthorized. Please login as admin/budget user first.');
}

require_once __DIR__ . '/config/database.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>PPMP Columns Migration</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #e8f5e9; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .error { color: red; background: #ffebee; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .info { color: blue; background: #e3f2fd; padding: 10px; margin: 10px 0; border-radius: 5px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 5px; overflow-x: auto; }
        h1 { color: #800000; }
    </style>
</head>
<body>
    <h1>PPMP Columns Migration</h1>
    <p>This script will add PPMP reference columns to the utilization_purchase_requests table.</p>
";

try {
    $db = getDB();
    
    echo "<div class='info'>Connected to database successfully.</div>";
    
    // Check current table structure
    echo "<h2>Current Table Structure</h2>";
    $stmt = $db->query("DESCRIBE utilization_purchase_requests");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasColumns = false;
    $columnNames = array_column($columns, 'Field');
    
    if (in_array('ppmp_item_id', $columnNames) && 
        in_array('ppmp_id', $columnNames) && 
        in_array('ppmp_description', $columnNames)) {
        echo "<div class='success'>✓ All PPMP columns already exist!</div>";
        $hasColumns = true;
    } else {
        echo "<div class='info'>PPMP columns need to be added.</div>";
    }
    
    echo "<pre>";
    foreach ($columns as $col) {
        $highlight = in_array($col['Field'], ['ppmp_item_id', 'ppmp_id', 'ppmp_description']) ? ' <-- PPMP COLUMN' : '';
        echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . $highlight . "\n";
    }
    echo "</pre>";
    
    if (!$hasColumns) {
        echo "<h2>Adding PPMP Columns...</h2>";
        
        // Add ppmp_item_id
        if (!in_array('ppmp_item_id', $columnNames)) {
            $db->exec("ALTER TABLE utilization_purchase_requests 
                       ADD COLUMN ppmp_item_id INT NULL COMMENT 'Reference to ppmp_items.id'");
            echo "<div class='success'>✓ Added column: ppmp_item_id</div>";
        }
        
        // Add ppmp_id
        if (!in_array('ppmp_id', $columnNames)) {
            $db->exec("ALTER TABLE utilization_purchase_requests 
                       ADD COLUMN ppmp_id INT NULL COMMENT 'Reference to ppmp.id'");
            echo "<div class='success'>✓ Added column: ppmp_id</div>";
        }
        
        // Add ppmp_description
        if (!in_array('ppmp_description', $columnNames)) {
            $db->exec("ALTER TABLE utilization_purchase_requests 
                       ADD COLUMN ppmp_description TEXT NULL COMMENT 'Formatted PPMP item description'");
            echo "<div class='success'>✓ Added column: ppmp_description</div>";
        }
        
        // Add indexes
        echo "<h2>Adding Indexes...</h2>";
        
        try {
            $db->exec("ALTER TABLE utilization_purchase_requests ADD INDEX idx_ppmp_item (ppmp_item_id)");
            echo "<div class='success'>✓ Added index: idx_ppmp_item</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='info'>Index idx_ppmp_item already exists</div>";
            } else {
                throw $e;
            }
        }
        
        try {
            $db->exec("ALTER TABLE utilization_purchase_requests ADD INDEX idx_ppmp (ppmp_id)");
            echo "<div class='success'>✓ Added index: idx_ppmp</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<div class='info'>Index idx_ppmp already exists</div>";
            } else {
                throw $e;
            }
        }
        
        echo "<h2>Updated Table Structure</h2>";
        $stmt = $db->query("DESCRIBE utilization_purchase_requests");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<pre>";
        foreach ($columns as $col) {
            $highlight = in_array($col['Field'], ['ppmp_item_id', 'ppmp_id', 'ppmp_description']) ? ' <-- NEW!' : '';
            echo $col['Field'] . " | " . $col['Type'] . " | " . $col['Null'] . " | " . $col['Key'] . $highlight . "\n";
        }
        echo "</pre>";
        
        echo "<div class='success'><strong>✓ Migration completed successfully!</strong></div>";
        echo "<p>You can now use the PPMP-Purchase Request integration feature.</p>";
    }
    
    echo "<h2>Verification</h2>";
    $stmt = $db->query("SELECT COUNT(*) as count FROM utilization_purchase_requests WHERE ppmp_item_id IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<div class='info'>Purchase requests linked to PPMP: " . $result['count'] . "</div>";
    
    echo "<p><a href='pages/utilization.php'>← Back to Utilization Page</a></p>";
    
} catch (PDOException $e) {
    echo "<div class='error'><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
?>
