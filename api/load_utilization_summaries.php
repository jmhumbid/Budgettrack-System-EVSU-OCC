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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$departmentId = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$fiscalYear = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : null;

try {
    $db = getDB();
    
    // Check if table exists
    $checkTable = $db->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkTable->rowCount() == 0) {
        echo json_encode(['success' => true, 'summaries' => []]);
        exit;
    }
    
    if ($departmentId) {
        // Get summaries for specific department only (NOT including child departments)
        // Child departments will be loaded separately in the sub-department tab
        if ($fiscalYear) {
            // Filter by fiscal year if provided
            $stmt = $db->prepare("
                SELECT id, department_id, fiscal_year, department_name, totals, created_at, updated_at,
                       CASE WHEN updated_at IS NOT NULL AND updated_at != created_at THEN 1 ELSE 0 END as is_updated
                FROM utilization_summaries
                WHERE department_id = ? AND fiscal_year = ?
                ORDER BY COALESCE(updated_at, created_at) DESC, fiscal_year DESC
            ");
            $stmt->execute([$departmentId, $fiscalYear]);
        } else {
            // Get all summaries for this department only (all fiscal years)
            // Sort by most recently saved/updated first
            $stmt = $db->prepare("
                SELECT id, department_id, fiscal_year, department_name, totals, created_at, updated_at,
                       CASE WHEN updated_at IS NOT NULL AND updated_at != created_at THEN 1 ELSE 0 END as is_updated
                FROM utilization_summaries
                WHERE department_id = ?
                ORDER BY COALESCE(updated_at, created_at) DESC, fiscal_year DESC
            ");
            $stmt->execute([$departmentId]);
        }
    } else {
        // Get all summaries for current fiscal year (or all if not specified)
        if ($fiscalYear) {
            $stmt = $db->prepare("
                SELECT id, department_id, fiscal_year, department_name, totals, created_at, updated_at,
                       CASE WHEN updated_at IS NOT NULL AND updated_at != created_at THEN 1 ELSE 0 END as is_updated
                FROM utilization_summaries
                WHERE fiscal_year = ?
                ORDER BY fiscal_year DESC, created_at DESC
            ");
            $stmt->execute([$fiscalYear]);
        } else {
            $stmt = $db->prepare("
                SELECT id, department_id, fiscal_year, department_name, totals, created_at, updated_at,
                       CASE WHEN updated_at IS NOT NULL AND updated_at != created_at THEN 1 ELSE 0 END as is_updated
                FROM utilization_summaries
                ORDER BY fiscal_year DESC, created_at DESC
            ");
            $stmt->execute();
        }
    }
    
    $summaries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'summaries' => $summaries]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error loading summaries: ' . $e->getMessage()]);
}

