<?php
require 'config/database.php';
$db = getDB();
$stmt = $db->prepare("SELECT id, category, particulars, amount FROM line_item_budget_items WHERE lib_id = 58 ORDER BY id DESC LIMIT 10");
$stmt->execute();
echo "LIB #58 Items:\n";
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "  Item #{$row['id']}: {$row['category']}\n";
    echo "    → {$row['particulars']}\n";
    echo "    → ₱" . number_format($row['amount'], 2) . "\n\n";
}
?>
