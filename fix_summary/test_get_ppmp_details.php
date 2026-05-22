<?php
/**
 * Test get_ppmp_details API
 */

session_start();
$_SESSION['user_id'] = 1;
$_SESSION['department_id'] = 13; // Computer Studies
$_SESSION['user_role'] = 'department';

$_GET['id'] = 45; // PPMP ID from debug

ob_start();
include 'api/get_ppmp_details.php';
$output = ob_get_clean();

echo "=== Testing get_ppmp_details.php ===\n\n";
echo "Raw output:\n";
echo $output . "\n\n";

$data = json_decode($output, true);
if ($data) {
    echo "Parsed JSON:\n";
    echo "Success: " . ($data['success'] ? 'YES' : 'NO') . "\n";
    if ($data['success']) {
        echo "PPMP ID: " . $data['ppmp']['id'] . "\n";
        echo "PPMP Number: " . $data['ppmp']['ppmp_number'] . "\n";
        echo "Fiscal Year: " . $data['ppmp']['fiscal_year'] . "\n";
        echo "Status: " . $data['ppmp']['status'] . "\n";
        echo "Items count: " . count($data['items']) . "\n";
        
        if (!empty($data['items'])) {
            echo "\nFirst item:\n";
            $item = $data['items'][0];
            echo "  Description: " . $item['general_description'] . "\n";
            echo "  Budget: " . $item['estimated_budget'] . "\n";
            echo "  LIB Category: " . ($item['lib_category'] ?? 'NULL') . "\n";
            echo "  LIB Particulars: " . ($item['lib_particulars'] ?? 'NULL') . "\n";
            echo "  LIB Account Code: " . ($item['lib_account_code'] ?? 'NULL') . "\n";
        }
    } else {
        echo "Error: " . $data['message'] . "\n";
    }
} else {
    echo "Failed to parse JSON\n";
}
?>
