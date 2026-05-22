<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$departmentId = $_GET['department_id'] ?? null;
$fiscalYear = $_GET['fiscal_year'] ?? date('Y');

if (!$departmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department ID is required']);
    exit;
}

try {
    $conn = getDB();
    
    // Check if table exists
    $checkTable = $conn->query("SHOW TABLES LIKE 'budget_allocations'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'No budget allocations found']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT * FROM budget_allocations 
        WHERE department_id = ? AND fiscal_year = ?
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    
    $stmt->execute([$departmentId, $fiscalYear]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Decode allocation_data if it's a string
        if (is_string($data['allocation_data'])) {
            $data['allocation_data'] = json_decode($data['allocation_data'], true);
        }
        
        // Fetch child departments (sub-departments assigned to this department)
        $childDepts = [];
        try {
            $childStmt = $conn->prepare("
                SELECT d.id, d.dept_name, d.dept_code, d.fiduciary_type
                FROM departments d
                WHERE d.parent_department_id = ? AND d.is_active = 1
            ");
            $childStmt->execute([$departmentId]);
            $children = $childStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get allocation for each child department
            foreach ($children as $child) {
                $childAllocStmt = $conn->prepare("
                    SELECT * FROM budget_allocations 
                    WHERE department_id = ? AND fiscal_year = ?
                    ORDER BY created_at DESC LIMIT 1
                ");
                $childAllocStmt->execute([$child['id'], $fiscalYear]);
                $childAlloc = $childAllocStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($childAlloc && is_string($childAlloc['allocation_data'])) {
                    $childAlloc['allocation_data'] = json_decode($childAlloc['allocation_data'], true);
                }
                
                $childDepts[] = [
                    'department' => $child,
                    'allocation' => $childAlloc
                ];
            }
        } catch (Exception $e) {
            // Ignore child department errors
        }
        
        echo json_encode([
            'success' => true,
            'data' => $data,
            'child_departments' => $childDepts
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No budget allocation found for this department and fiscal year'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

