<?php
/**
 * Test script to check PPMP data
 */

require_once __DIR__ . '/config/database.php';

try {
    $db = getDB();
    
    echo "=== Checking PPMP table structure ===\n";
    $stmt = $db->query("DESCRIBE ppmp");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($columns as $col) {
        if ($col['Field'] === 'ppmp_type') {
            echo "✓ ppmp_type column exists: {$col['Type']}\n";
        }
    }
    
    echo "\n=== Checking all PPMPs ===\n";
    $stmt = $db->query("
        SELECT 
            id, 
            department_id, 
            ppmp_number, 
            fiscal_year, 
            status, 
            is_final, 
            is_indicative,
            COALESCE(ppmp_type, 'NULL') as ppmp_type
        FROM ppmp 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $ppmps = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($ppmps as $ppmp) {
        echo sprintf(
            "ID: %d | Dept: %d | Number: %s | FY: %s | Status: %s | Final: %d | Type: %s\n",
            $ppmp['id'],
            $ppmp['department_id'],
            $ppmp['ppmp_number'],
            $ppmp['fiscal_year'],
            $ppmp['status'],
            $ppmp['is_final'],
            $ppmp['ppmp_type']
        );
    }
    
    echo "\n=== Checking PPMP items for department 1 (PPMP type) ===\n";
    $stmt = $db->prepare("
        SELECT 
            pi.id,
            pi.ppmp_id,
            pi.general_description,
            p.ppmp_number,
            p.fiscal_year,
            p.is_final,
            p.status,
            COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
        FROM ppmp_items pi
        INNER JOIN ppmp p ON pi.ppmp_id = p.id
        WHERE p.department_id = 1
            AND (p.is_final = 1 OR p.status = 'approved')
            AND COALESCE(p.ppmp_type, 'ppmp') = 'ppmp'
        LIMIT 5
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($items) . " PPMP items\n";
    foreach ($items as $item) {
        echo sprintf(
            "  - Item ID: %d | PPMP: %s | Type: %s | Desc: %s\n",
            $item['id'],
            $item['ppmp_number'],
            $item['ppmp_type'],
            substr($item['general_description'], 0, 50)
        );
    }
    
    echo "\n=== Checking PPMP items for department 1 (Supplemental type) ===\n";
    $stmt = $db->prepare("
        SELECT 
            pi.id,
            pi.ppmp_id,
            pi.general_description,
            p.ppmp_number,
            p.fiscal_year,
            p.is_final,
            p.status,
            COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
        FROM ppmp_items pi
        INNER JOIN ppmp p ON pi.ppmp_id = p.id
        WHERE p.department_id = 1
            AND (p.is_final = 1 OR p.status = 'approved')
            AND COALESCE(p.ppmp_type, 'ppmp') = 'supplemental'
        LIMIT 5
    ");
    $stmt->execute();
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($items) . " Supplemental items\n";
    foreach ($items as $item) {
        echo sprintf(
            "  - Item ID: %d | PPMP: %s | Type: %s | Desc: %s\n",
            $item['id'],
            $item['ppmp_number'],
            $item['ppmp_type'],
            substr($item['general_description'], 0, 50)
        );
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
