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

if (!$custom_item_id) {
    echo json_encode(['success' => false, 'message' => 'Custom item ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Soft delete custom item
    $query = "UPDATE lib_custom_items 
              SET deleted_at = NOW(),
                  deleted_by = :deleted_by
              WHERE id = :custom_item_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':custom_item_id', $custom_item_id);
    $stmt->bindParam(':deleted_by', $user_id);
    
    if ($stmt->execute()) {
        // Log activity
        ActivityLog::log(
            $db,
            $user_id,
            'lib_custom_item_deleted',
            'lib_custom_items',
            $custom_item_id,
            "Deleted custom LIB item"
        );
        
        echo json_encode([
            'success' => true,
            'message' => 'Custom item deleted successfully'
        ]);
    } else {
        throw new Exception('Failed to delete custom item');
    }
    
} catch (Exception $e) {
    error_log("Delete Custom LIB Item Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error deleting custom item: ' . $e->getMessage()
    ]);
}
