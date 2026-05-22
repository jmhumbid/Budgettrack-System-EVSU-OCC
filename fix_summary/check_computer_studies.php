<?php
require_once __DIR__ . '/config/database.php';

$db = getDB();

// Find Computer Studies department
$stmt = $db->query("SELECT id, dept_name FROM departments WHERE dept_name LIKE '%Computer%' OR dept_name LIKE '%computer%'");
$depts = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Computer Studies Departments:\n";
foreach ($depts as $dept) {
    echo "ID: {$dept['id']} - {$dept['dept_name']}\n";
    
    // Check PPMPs for this department
    $stmt2 = $db->prepare("
        SELECT id, ppmp_number, fiscal_year, status, is_final, COALESCE(ppmp_type, 'NULL') as ppmp_type
        FROM ppmp 
        WHERE department_id = ?
        ORDER BY created_at DESC
    ");
    $stmt2->execute([$dept['id']]);
    $ppmps = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    
    echo "  PPMPs found: " . count($ppmps) . "\n";
    foreach ($ppmps as $ppmp) {
        echo "    - PPMP #{$ppmp['ppmp_number']} | FY: {$ppmp['fiscal_year']} | Status: {$ppmp['status']} | Final: {$ppmp['is_final']} | Type: {$ppmp['ppmp_type']}\n";
        
        // Count items
        $stmt3 = $db->prepare("SELECT COUNT(*) as cnt FROM ppmp_items WHERE ppmp_id = ?");
        $stmt3->execute([$ppmp['id']]);
        $count = $stmt3->fetch(PDO::FETCH_ASSOC);
        echo "      Items: {$count['cnt']}\n";
    }
}
?>
