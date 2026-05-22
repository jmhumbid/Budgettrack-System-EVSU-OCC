<?php
require 'config/database.php';
$db = getDB();
$stmt = $db->query("SELECT id, ppmp_number, department_id, fiscal_year, status FROM ppmp WHERE id = 45");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    echo "PPMP #45 exists:\n";
    echo "  Number: {$row['ppmp_number']}\n";
    echo "  Dept ID: {$row['department_id']}\n";
    echo "  Year: {$row['fiscal_year']}\n";
    echo "  Status: {$row['status']}\n";
} else {
    echo "PPMP #45 not found\n";
}
?>
