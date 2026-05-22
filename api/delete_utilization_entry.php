<?php
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

// Support both entry_id OR category_name + department_id + fiscal_year
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
    
    $entry = null;
    
    // If entry_id is provided, use it
    if ($entry_id) {
        // First, try to get the entry with department_id check
        $stmt = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE id = :entry_id AND department_id = :dept_id");
        $stmt->execute([':entry_id' => $entry_id, ':dept_id' => $department_id]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found with department_id match, try without department_id check (for entries with wrong/missing department_id)
        if (!$entry) {
            $stmt2 = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE id = :entry_id");
            $stmt2->execute([':entry_id' => $entry_id]);
            $entry = $stmt2->fetch(PDO::FETCH_ASSOC);
            
            if ($entry) {
                // Found entry but with different department_id - log warning but allow deletion
                error_log("Warning: Deleting utilization entry ID $entry_id with department_id {$entry['department_id']} but requested department_id was $department_id");
            }
        }
    } 
    // If category_name is provided instead, find entry by category name
    else if ($category_name && $department_id) {
        // Try multiple matching strategies
        // 1. Exact match with all conditions
        $stmt = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE expense_category = :category_name AND department_id = :dept_id AND fiscal_year = :fiscal_year");
        $stmt->execute([
            ':category_name' => $category_name,
            ':dept_id' => $department_id,
            ':fiscal_year' => $fiscal_year
        ]);
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 2. Case-insensitive with all conditions
        if (!$entry) {
            $stmt2 = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:category_name)) AND department_id = :dept_id AND fiscal_year = :fiscal_year");
            $stmt2->execute([
                ':category_name' => $category_name,
                ':dept_id' => $department_id,
                ':fiscal_year' => $fiscal_year
            ]);
            $entry = $stmt2->fetch(PDO::FETCH_ASSOC);
        }
        
        // 3. Without fiscal_year check (in case year is wrong)
        if (!$entry) {
            $stmt3 = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:category_name)) AND department_id = :dept_id");
            $stmt3->execute([
                ':category_name' => $category_name,
                ':dept_id' => $department_id
            ]);
            $entry = $stmt3->fetch(PDO::FETCH_ASSOC);
        }
        
        // 4. Without department_id check (in case department_id is wrong)
        if (!$entry) {
            $stmt4 = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:category_name)) AND fiscal_year = :fiscal_year");
            $stmt4->execute([
                ':category_name' => $category_name,
                ':fiscal_year' => $fiscal_year
            ]);
            $entry = $stmt4->fetch(PDO::FETCH_ASSOC);
            
            if ($entry) {
                error_log("Warning: Deleting utilization entry by category '$category_name' with department_id {$entry['department_id']} but requested department_id was $department_id");
            }
        }
        
        // 5. Last resort: match by category name only (any department, any year)
        if (!$entry) {
            $stmt5 = $db->prepare("SELECT id, deductions, department_id FROM budget_utilization_entries WHERE LOWER(TRIM(expense_category)) = LOWER(TRIM(:category_name)) LIMIT 1");
            $stmt5->execute([
                ':category_name' => $category_name
            ]);
            $entry = $stmt5->fetch(PDO::FETCH_ASSOC);
            
            if ($entry) {
                error_log("Warning: Deleting utilization entry by category '$category_name' only (department_id: {$entry['department_id']}, requested: $department_id)");
            }
        }
        
        if ($entry) {
            $entry_id = $entry['id']; // Set entry_id for later use
            // Update department_id to match the found entry if it was different
            if ($entry['department_id'] != $department_id) {
                $department_id = $entry['department_id'];
            }
        }
    }
    
    if (!$entry) {
        $db->rollBack();
        $searchTerm = $entry_id ? "ID: $entry_id" : "category: '$category_name' (department: $department_id, year: $fiscal_year)";
        echo json_encode(['success' => false, 'message' => 'Utilization entry not found with ' . $searchTerm]);
        exit;
    }
    
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
    
    $prEntries = [];
    $travelEntries = [];
    
    // Find all PR entries that were deducting from this entry (only if column exists)
    if ($hasPrColumn) {
        try {
            $prStmt = $db->prepare("SELECT id, amount FROM utilization_purchase_requests WHERE deducted_from_entry_id = :entry_id");
            $prStmt->execute([':entry_id' => $entry_id]);
            $prEntries = $prStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error finding PR entries: ' . $e->getMessage());
        }
    }
    
    // Find all Travel entries that were deducting from this entry (only if column exists)
    if ($hasTravelColumn) {
        try {
            $travelStmt = $db->prepare("SELECT id, amount FROM utilization_travels WHERE deducted_from_entry_id = :entry_id");
            $travelStmt->execute([':entry_id' => $entry_id]);
            $travelEntries = $travelStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Error finding Travel entries: ' . $e->getMessage());
        }
    }
    
    // Remove deductions from PR entries (set deducted_from_entry_id to NULL) - only if column exists
    if ($hasPrColumn && count($prEntries) > 0) {
        try {
            $updatePrStmt = $db->prepare("UPDATE utilization_purchase_requests SET deducted_from_entry_id = NULL WHERE deducted_from_entry_id = :entry_id");
            $updatePrStmt->execute([':entry_id' => $entry_id]);
            error_log("Removed deductions from " . count($prEntries) . " PR entries for utilization entry ID: $entry_id");
        } catch (Exception $e) {
            error_log('Error updating PR entries: ' . $e->getMessage());
        }
    }
    
    // Remove deductions from Travel entries (set deducted_from_entry_id to NULL) - only if column exists
    if ($hasTravelColumn && count($travelEntries) > 0) {
        try {
            $updateTravelStmt = $db->prepare("UPDATE utilization_travels SET deducted_from_entry_id = NULL WHERE deducted_from_entry_id = :entry_id");
            $updateTravelStmt->execute([':entry_id' => $entry_id]);
            error_log("Removed deductions from " . count($travelEntries) . " Travel entries for utilization entry ID: $entry_id");
        } catch (Exception $e) {
            error_log('Error updating Travel entries: ' . $e->getMessage());
        }
    }
    
    // Delete the utilization entry (try with department_id first, then without if it fails)
    $deleteStmt = $db->prepare("DELETE FROM budget_utilization_entries WHERE id = :entry_id AND department_id = :dept_id");
    $deleteStmt->execute([':entry_id' => $entry_id, ':dept_id' => $department_id]);
    
    // If no rows were deleted (department_id mismatch), try without department_id check
    if ($deleteStmt->rowCount() === 0) {
        $deleteStmt2 = $db->prepare("DELETE FROM budget_utilization_entries WHERE id = :entry_id");
        $deleteStmt2->execute([':entry_id' => $entry_id]);
        
        if ($deleteStmt2->rowCount() === 0) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to delete utilization entry. Entry may have been already deleted or does not exist.']);
            exit;
        } else {
            error_log("Deleted utilization entry ID $entry_id without department_id check (department_id mismatch)");
        }
    }
    
    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'Utilization entry deleted successfully. Deductions removed from ' . (count($prEntries) + count($travelEntries)) . ' related entries.'
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error deleting utilization entry: ' . $e->getMessage()]);
}

