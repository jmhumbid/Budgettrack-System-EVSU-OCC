<?php
// Start output buffering to prevent any accidental output
ob_start();
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    ob_end_clean();
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Clear output buffer and set proper headers
ob_end_clean();
header('Content-Type: application/json');

$allocationId = $_GET['id'] ?? null;

if (!$allocationId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Allocation ID is required']);
    exit;
}

try {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT 
            ba.*,
            d.dept_name as department_name,
            d.fiduciary_type
        FROM budget_allocations ba
        LEFT JOIN departments d ON ba.department_id = d.id
        WHERE ba.id = ?
    ");
    
    $stmt->execute([$allocationId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Decode allocation_data if it's a string
        if (isset($data['allocation_data']) && $data['allocation_data'] !== null && is_string($data['allocation_data']) && !empty(trim($data['allocation_data']))) {
            $decoded = json_decode($data['allocation_data'], true);
            // Check if json_decode was successful
            if (json_last_error() === JSON_ERROR_NONE && $decoded !== null) {
                $data['allocation_data'] = $decoded;
            } else {
                // If JSON decode fails, set to empty array
                $data['allocation_data'] = [];
            }
        } else {
            // If allocation_data doesn't exist, is null, or is empty, set to empty array
            $data['allocation_data'] = [];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Allocation not found'
        ]);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    error_log('PDO Error in get_allocation_breakdown_by_id.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
} catch (Exception $e) {
    http_response_code(500);
    // Log the error for debugging (you can remove this in production if needed)
    error_log('Error in get_allocation_breakdown_by_id.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

