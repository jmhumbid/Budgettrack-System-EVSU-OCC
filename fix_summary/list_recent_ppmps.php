<?php
require 'config/database.php';
$db = getDB();
$stmt = $db->query("SELECT id, ppmp_number, department_id, fiscal_year, status FROM ppmp ORDER BY id DESC LIMIT 10");
echo "Recent PPMPs:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  PPMP #{$row['id']} - {$row['ppmp_number']} - Dept {$row['department_id']} - {$row['fiscal_year']} - {$row['status']}\n";
}
?>
