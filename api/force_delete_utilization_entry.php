<?php
/**
 * Force Delete Utilization Entry
 * This is a more aggressive deletion script that can delete entries even when
 * normal deletion fails. Use with caution!
 * 
 * Usage: POST with either:
 * - entry_id: Direct ID of entry to delete
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
$fiscal_year = isset($data['fiscal_year']) ? (int)$data['fiscal_year'] : null;

if (!$entry_id && (!$category_name || !$department_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields. Provide either entry_id OR (category_name + department_id)']);
    exit;
}

try {
    $db = getDB();
    $db->beginTransaction();
    
    $entry = null;
    $entries_to_delete = [];
    
    // If entry_id is provided, use it directly
    if ($entry_id) {
        $stmt = $db->prepare("SELECT id, expense_category, department_id, fiscal_year FROM budget_utilization_entries WHERE id = :entry_id");
        $stmt->execute([':entry_id' => $entry_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($entry) {
            $entries_to_delete[] = $entry['id'];
        }
    } 
    // If category_name is provided, find all matching entries
    else if ($category_name && $department_id) {
        // Try multiple search strategies
        $searchQueries = [
            // Exact match
            "SELECT id, expense_category, department_id, fiscal_year FROM budget_utilization_entries WHERE expense_category = :cat AND department_id = :dept",
            // Case-insensitive
            "SELECT id, expense_category, department_id, fiscal_year FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:cat)) AND department_id = :dept",
            // Without department check
            "SELECT id, expense_category, department_id, fiscal_year FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:cat))",
        ];
        
        foreach ($searchQueries as $query) {
            $stmt = $db->prepare($query);
            if (strpos($query, ':dept') !== false) {
                $stmt->execute([':cat' => $category_name, ':dept' => $department_id]);
            } else {
                $stmt->execute([':cat' => $category_name]);
            }
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($results) > 0) {
                foreach ($results as $result) {
                    if (!in_array($result['id'], $entries_to_delete)) {
                        $entries_to_delete[] = $result['id'];
                    }
                }
                break; // Found matches, stop searching
            }
        }
    }
    
    if (empty($entries_to_delete)) {
        $db->rollBack();
        $searchTerm = $entry_id ? "ID: $entry_id" : "category: '$category_name' (department: $department_id)";
        echo json_encode(['success' => false, 'message' => 'No entries found to delete with ' . $searchTerm]);
        exit;
    }
    
    // Delete all found entries
    $deleted_count = 0;
    $pr_count = 0;
    $travel_count = 0;
    
    // Check if deducted_from_entry_id column exists in both tables
    $hasPrColumn = false;
    $hasTravelColumn = false;
    
    try {
        $checkPrColumn = $db->query("SHOW COLUMNS FROM utilization_purchase_requests LIKE 'deducted_from_entry_id'");
        $hasPrColumn = $checkPrColumn && $checkPrColumn->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Could not check for deducted_from_entry_id column in utilization_purchase_requests: ' . $e->getMessage());
    }
    
    try {
        $checkTravelColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'deducted_from_entry_id'");
        $hasTravelColumn = $checkTravelColumn && $checkTravelColumn->rowCount() > 0;
    } catch (Exception $e) {
        error_log('Could not check for deducted_from_entry_id column in utilization_travels: ' . $e->getMessage());
    }
    
    foreach ($entries_to_delete as $id_to_delete) {
        // Find related PR and Travel entries (only if column exists)
        if ($hasPrColumn) {
            try {
                $prStmt = $db->prepare("SELECT COUNT(*) as count FROM utilization_purchase_requests WHERE deducted_from_entry_id = :entry_id");
                $prStmt->execute([':entry_id' => $id_to_delete]);
                $prResult = $prStmt->fetch(PDO::FETCH_ASSOC);
                $pr_count += (int)$prResult['count'];
                
                // Remove deductions from PR entries
                if ($prResult['count'] > 0) {
                    $updatePrStmt = $db->prepare("UPDATE utilization_purchase_requests SET deducted_from_entry_id = NULL WHERE deducted_from_entry_id = :entry_id");
                    $updatePrStmt->execute([':entry_id' => $id_to_delete]);
                }
            } catch (Exception $e) {
                error_log('Error processing PR entries for entry ID ' . $id_to_delete . ': ' . $e->getMessage());
            }
        }
        
        if ($hasTravelColumn) {
            try {
                $travelStmt = $db->prepare("SELECT COUNT(*) as count FROM utilization_travels WHERE deducted_from_entry_id = :entry_id");
                $travelStmt->execute([':entry_id' => $id_to_delete]);
                $travelResult = $travelStmt->fetch(PDO::FETCH_ASSOC);
                $travel_count += (int)$travelResult['count'];
                
                // Remove deductions from Travel entries
                if ($travelResult['count'] > 0) {
                    $updateTravelStmt = $db->prepare("UPDATE utilization_travels SET deducted_from_entry_id = NULL WHERE deducted_from_entry_id = :entry_id");
                    $updateTravelStmt->execute([':entry_id' => $id_to_delete]);
                }
            } catch (Exception $e) {
                error_log('Error processing Travel entries for entry ID ' . $id_to_delete . ': ' . $e->getMessage());
            }
        }
        
        // Force delete the entry (no department_id check)
        $deleteStmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE id = :entry_id");
        $deleteStmt->execute([':entry_id' => $id_to_delete]);
        
        if ($deleteStmt->rowCount() > 0) {
            $deleted_count++;
            error_log("Force deleted utilization entry ID: $id_to_delete");
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Force deleted $deleted_count entry/entries. Removed deductions from $pr_count PR entries and $travel_count Travel entries.",
        'deleted_count' => $deleted_count
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error force deleting utilization entry: ' . $e->getMessage()]);
}

