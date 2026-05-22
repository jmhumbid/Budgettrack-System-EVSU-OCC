<?php
/**
 * Manual Delete Utilization Entry
 * This script directly deletes an entry from the database without checking for related records.
 * Use this as a last resort to remove stuck entries.
 * 
 * Usage: POST with:
 * - entry_id: Direct ID of entry to delete
 * OR
 * - category_name + department_id: Delete by category name
 */

session_start();

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$entry_id = isset($data['entry_id']) ? (int)$data['entry_id'] : null;
$category_name = isset($data['category_name']) ? trim($data['category_name']) : null;
$department_id = isset($data['department_id']) ? (int)$data['department_id'] : null;
$fiscal_year = isset($data['fiscal_year']) ? (int)$data['fiscal_year'] : date('Y');

if (!$entry_id && (!$category_name || !$department_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields. Provide either entry_id OR (category_name + department_id)']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    $entry_id_to_delete = null;
    
    // If entry_id is provided, use it directly
    if ($entry_id) {
        $stmt = $db->prepare("SELECT id FROM budget_utilization_entries WHERE id = :entry_id");
        $stmt->execute([':entry_id' => $entry_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($entry) {
            $entry_id_to_delete = $entry['id'];
        }
    } 
    // If category_name is provided, find the entry
    else if ($category_name && $department_id) {
        // Try multiple search strategies
        $searchQueries = [
            // Exact match
            "SELECT id FROM budget_utilization_entries WHERE expense_category = :cat AND department_id = :dept AND fiscal_year = :year",
            // Case-insensitive
            "SELECT id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:cat)) AND department_id = :dept AND fiscal_year = :year",
            // Without fiscal_year
            "SELECT id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:cat)) AND department_id = :dept",
            // Without department_id
            "SELECT id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:cat)) AND fiscal_year = :year",
            // Category only
            "SELECT id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:cat)) LIMIT 1",
        ];
        
        foreach ($searchQueries as $query) {
            $stmt = $db->prepare($query);
            if (strpos($query, ':dept') !== false && strpos($query, ':year') !== false) {
                $stmt->execute([':cat' => $category_name, ':dept' => $department_id, ':year' => $fiscal_year]);
            } else if (strpos($query, ':dept') !== false) {
                $stmt->execute([':cat' => $category_name, ':dept' => $department_id]);
            } else if (strpos($query, ':year') !== false) {
                $stmt->execute([':cat' => $category_name, ':year' => $fiscal_year]);
            } else {
                $stmt->execute([':cat' => $category_name]);
            }
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['id']) {
                $entry_id_to_delete = $result['id'];
                break;
            }
        }
    }
    
    if (!$entry_id_to_delete) {
        $db->rollBack();
        $searchTerm = $entry_id ? "ID: $entry_id" : "category: '$category_name' (department: $department_id)";
        echo json_encode(['success' => false, 'message' => 'No entry found to delete with ' . $searchTerm]);
        exit;
    }
    
    // Direct delete without checking for related records (this is a manual/force delete)
    $deleteStmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE id = :entry_id");
    $deleteStmt->execute([':entry_id' => $entry_id_to_delete]);
    
    if ($deleteStmt->rowCount() === 0) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Entry not found or already deleted']);
        exit;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Entry ID $entry_id_to_delete deleted successfully from database.",
        'deleted_entry_id' => $entry_id_to_delete
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting entry: ' . $e->getMessage()]);
}

