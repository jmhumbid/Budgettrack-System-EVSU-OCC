<?php
require 'config/database.php';
$db = getDB();
$stmt = $db->query("SELECT id, fiscal_year, department_id, status FROM line_item_budgets ORDER BY id DESC LIMIT 10");
echo "LIBs in database:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "LIB #{$row['id']} - Fiscal Year: [{$row['fiscal_year']}] - Dept: {$row['department_id']} - Status: {$row['status']}\n";
}
?>
