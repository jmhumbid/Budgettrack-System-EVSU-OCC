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
    $checkTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => false, 'message' => 'No utilization summaries found']);
        exit;
    }
    
    $stmt = $conn->prepare("
        SELECT * FROM utilization_summaries 
        WHERE department_id = ? AND fiscal_year = ?
        ORDER BY updated_at DESC, created_at DESC
        LIMIT 1
    ");
    
    $stmt->execute([$departmentId, $fiscalYear]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data) {
        // Decode JSON fields if they're strings
        if (is_string($data['utilization_entries'])) {
            $data['utilization_entries'] = json_decode($data['utilization_entries'], true);
        }
        if (is_string($data['pr_entries'])) {
            $data['pr_entries'] = json_decode($data['pr_entries'], true);
        }
        if (is_string($data['travels_entries'])) {
            $data['travels_entries'] = json_decode($data['travels_entries'], true);
        }
        if (is_string($data['honoraria_entries'])) {
            $data['honoraria_entries'] = json_decode($data['honoraria_entries'], true);
        }
        if (isset($data['pr_deductions']) && is_string($data['pr_deductions'])) {
            $data['pr_deductions'] = json_decode($data['pr_deductions'], true);
        }
        if (isset($data['travels_deductions']) && is_string($data['travels_deductions'])) {
            $data['travels_deductions'] = json_decode($data['travels_deductions'], true);
        }
        if (isset($data['honoraria_deductions']) && is_string($data['honoraria_deductions'])) {
            $data['honoraria_deductions'] = json_decode($data['honoraria_deductions'], true);
        }
        if (is_string($data['totals'])) {
            $data['totals'] = json_decode($data['totals'], true);
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
            
            // Get utilization for each child department
            foreach ($children as $child) {
                $childUtilStmt = $conn->prepare("
                    SELECT * FROM utilization_summaries 
                    WHERE department_id = ? AND fiscal_year = ?
                    ORDER BY updated_at DESC, created_at DESC LIMIT 1
                ");
                $childUtilStmt->execute([$child['id'], $fiscalYear]);
                $childUtil = $childUtilStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($childUtil) {
                    // Decode JSON fields
                    $jsonFields = ['utilization_entries', 'pr_entries', 'travels_entries', 'honoraria_entries', 'pr_deductions', 'travels_deductions', 'honoraria_deductions', 'totals'];
                    foreach ($jsonFields as $field) {
                        if (isset($childUtil[$field]) && is_string($childUtil[$field])) {
                            $childUtil[$field] = json_decode($childUtil[$field], true);
                        }
                    }
                }
                
                $childDepts[] = [
                    'department' => $child,
                    'utilization' => $childUtil
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
            'message' => 'No utilization summary found for this department and fiscal year'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

