<?php
require_once '../config/database.php';
require_once '../classes/ActivityLog.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

$custom_item_id = $data['custom_item_id'] ?? null;
$uacs_code = $data['uacs_code'] ?? null;
$general_desc = $data['general_desc'] ?? null;
$total_amount = $data['total_amount'] ?? 0;
$quarter_1 = $data['quarter_1'] ?? 0;
$quarter_2 = $data['quarter_2'] ?? 0;
$quarter_3 = $data['quarter_3'] ?? 0;
$quarter_4 = $data['quarter_4'] ?? 0;

if (!$custom_item_id) {
    echo json_encode(['success' => false, 'message' => 'Custom item ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Update custom item
    $query = "UPDATE lib_custom_items 
              SET uacs_code = :uacs_code,
                  general_desc = :general_desc,
                  total_amount = :total_amount,
                  quarter_1 = :quarter_1,
                  quarter_2 = :quarter_2,
                  quarter_3 = :quarter_3,
                  quarter_4 = :quarter_4,
                  updated_at = NOW()
              WHERE id = :custom_item_id
              AND deleted_at IS NULL";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':custom_item_id', $custom_item_id);
    $stmt->bindParam(':uacs_code', $uacs_code);
    $stmt->bindParam(':general_desc', $general_desc);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->bindParam(':quarter_1', $quarter_1);
    $stmt->bindParam(':quarter_2', $quarter_2);
    $stmt->bindParam(':quarter_3', $quarter_3);
    $stmt->bindParam(':quarter_4', $quarter_4);
    
    if ($stmt->execute()) {
        // Log activity
        ActivityLog::log(
            $db,
            $user_id,
            'lib_custom_item_updated',
            'lib_custom_items',
            $custom_item_id,
            "Updated custom LIB item: {$general_desc}"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom item updated successfully'
        ]);
    } else {
        throw new Exception('Failed to update custom item');
    }
    
} catch (Exception $e) {
    error_log("Update Custom LIB Item Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error updating custom item: ' . $e->getMessage()
    ]);
}
