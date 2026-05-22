<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "Checking cabac_programs table:\n";

try {
    $stmt = $db->query("SELECT * FROM cabac_programs ORDER BY type, program_name");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total programs: " . count($data) . "\n\n";
    
    $fiduciary = array_filter($data, fn($p) => $p['type'] === 'fiduciary');
    $nonFiduciary = array_filter($data, fn($p) => $p['type'] === 'non-fiduciary');
    
    echo "Fiduciary programs (" . count($fiduciary) . "):\n";
    foreach ($fiduciary as $p) {
        echo "  - " . $p['program_name'] . "\n";
    }
    
    echo "\nNon-Fiduciary programs (" . count($nonFiduciary) . "):\n";
    foreach ($nonFiduciary as $p) {
        echo "  - " . $p['program_name'] . "\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
