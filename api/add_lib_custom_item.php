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

$department_id = $data['department_id'] ?? null;
$year = $data['year'] ?? date('Y');
$uacs_code = $data['uacs_code'] ?? null;
$general_desc = $data['general_desc'] ?? null;
$total_amount = $data['total_amount'] ?? 0;
$quarter_1 = $data['quarter_1'] ?? 0;
$quarter_2 = $data['quarter_2'] ?? 0;
$quarter_3 = $data['quarter_3'] ?? 0;
$quarter_4 = $data['quarter_4'] ?? 0;

if (!$department_id || !$uacs_code || !$general_desc) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Insert custom item
    $query = "INSERT INTO lib_custom_items 
              (department_id, year, uacs_code, general_desc, total_amount, 
               quarter_1, quarter_2, quarter_3, quarter_4, created_by, created_at)
              VALUES 
              (:department_id, :year, :uacs_code, :general_desc, :total_amount,
               :quarter_1, :quarter_2, :quarter_3, :quarter_4, :created_by, NOW())";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':department_id', $department_id);
    $stmt->bindParam(':year', $year);
    $stmt->bindParam(':uacs_code', $uacs_code);
    $stmt->bindParam(':general_desc', $general_desc);
    $stmt->bindParam(':total_amount', $total_amount);
    $stmt->bindParam(':quarter_1', $quarter_1);
    $stmt->bindParam(':quarter_2', $quarter_2);
    $stmt->bindParam(':quarter_3', $quarter_3);
    $stmt->bindParam(':quarter_4', $quarter_4);
    $stmt->bindParam(':created_by', $user_id);
    
    if ($stmt->execute()) {
        $custom_item_id = $db->lastInsertId();
        
        // Log activity
        ActivityLog::log(
            $db,
            $user_id,
            'lib_custom_item_added',
            'lib_custom_items',
            $custom_item_id,
            "Added custom LIB item: {$general_desc}"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom item added successfully',
            'custom_item_id' => $custom_item_id
        ]);
    } else {
        throw new Exception('Failed to insert custom item');
    }
    
} catch (Exception $e) {
    error_log("Add Custom LIB Item Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error adding custom item: ' . $e->getMessage()
    ]);
}
