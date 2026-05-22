<?php
session_start();

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/utilization_deductions_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['travel_id']) || !isset($data['department_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$travel_id = (int)$data['travel_id'];
$department_id = (int)$data['department_id'];

try {
    $db = getDB();
    $db->beginTransaction();
    
    $user_role = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : '';
    
    // Check if deducted_from_entry_id column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM utilization_travels LIKE 'deducted_from_entry_id'");
    $hasDeductedFromColumn = $checkColumn->rowCount() > 0;
    
    // Build select fields based on column existence
    if ($hasDeductedFromColumn) {
        $selectFields = "id, amount, deducted_from_entry_id, department_id";
    } else {
        $selectFields = "id, amount, department_id";
    }
    
    // For budget role users, allow deletion regardless of department_id match
    // For other roles, require department_id to match
    if ($user_role === 'budget') {
        // Get the travel entry without department_id check for budget users
        $stmt = $db->prepare("SELECT $selectFields FROM utilization_travels WHERE id = :id");
        $stmt->execute([':id' => $travel_id]);
        $travel = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Get the travel entry to reverse deductions (with department_id check)
        $stmt = $db->prepare("SELECT $selectFields FROM utilization_travels WHERE id = :id AND department_id = :dept_id");
        $stmt->execute([':id' => $travel_id, ':dept_id' => $department_id]);
        $travel = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$travel) {
        $db->rollBack();
        // Log for debugging
        error_log("Travel Delete - Entry not found. Travel ID: $travel_id, Department ID: $department_id, User Role: $user_role");
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Travel entry not found. It may have been already deleted or the ID is invalid.']);
        exit;
    }
    
    // Parse amount - handle both string and numeric values, remove currency symbols
    $amountStr = $travel['amount'];
    if (is_string($amountStr)) {
        $amountStr = str_replace(['₱', ',', ' '], '', $amountStr);
    }
    $travelAmount = (float)$amountStr;
    
    $deductedFromEntryId = null;
    if ($hasDeductedFromColumn && !empty($travel['deducted_from_entry_id'])) {
        $deductedFromEntryId = (int)$travel['deducted_from_entry_id'];
    }
    
    // Delete the travel entry
    // For budget role users, delete without department_id check
    if ($user_role === 'budget') {
        $deleteStmt = $db->prepare("DELETE FROM utilization_travels WHERE id = :id");
        $deleteStmt->execute([':id' => $travel_id]);
    } else {
        $deleteStmt = $db->prepare("DELETE FROM utilization_travels WHERE id = :id AND department_id = :dept_id");
        $deleteStmt->execute([':id' => $travel_id, ':dept_id' => $department_id]);
    }
    
    // Check if deletion was successful
    if ($deleteStmt->rowCount() === 0) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Travel entry not found or could not be deleted']);
        exit;
    }
    
    // Remove this travel entry from deduction sources
    // This ensures the deduction sources table stays in sync
    $checkDeductionSourcesTable = $db->query("SHOW TABLES LIKE 'budget_utilization_deduction_sources'");
    if ($checkDeductionSourcesTable->rowCount() > 0) {
        // Get all deduction sources that might contain this travel entry
        $sourcesStmt = $db->prepare("
            SELECT id, source_entries, amount 
            FROM budget_utilization_deduction_sources 
            WHERE department_id = :dept_id 
            AND source_type = 'travels'
        ");
        $sourcesStmt->execute([':dept_id' => $department_id]);
        $sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($sources as $source) {
            $entries = json_decode($source['source_entries'], true);
            $originalCount = count($entries);
            
            // Remove this travel entry from the entries array
            $entries = array_filter($entries, function($entry) use ($travel_id) {
                $entryId = isset($entry['sourceEntryId']) ? $entry['sourceEntryId'] : null;
                return $entryId != $travel_id && (string)$entryId !== (string)$travel_id;
            });
            
            // If entries were removed, update or delete the source
            if (count($entries) < $originalCount) {
                if (count($entries) > 0) {
                    // Recalculate amount
                    $newAmount = array_reduce($entries, function($sum, $entry) {
                        return $sum + (isset($entry['amount']) ? floatval($entry['amount']) : 0);
                    }, 0);
                    
                    // Update the source
                    $updateStmt = $db->prepare("
                        UPDATE budget_utilization_deduction_sources 
                        SET source_entries = :entries, amount = :amount 
                        WHERE id = :id
                    ");
                    $updateStmt->execute([
                        ':entries' => json_encode(array_values($entries)),
                        ':amount' => $newAmount,
                        ':id' => $source['id']
                    ]);
                } else {
                    // No entries left, delete the source
                    $deleteSourceStmt = $db->prepare("DELETE FROM budget_utilization_deduction_sources WHERE id = :id");
                    $deleteSourceStmt->execute([':id' => $source['id']]);
                }
            }
        }
    }
    
    // Recalculate deductions for the affected entry (only if column exists and value is set)
    if ($deductedFromEntryId && $hasDeductedFromColumn) {
        recalculateDeductionsForEntry($db, $deductedFromEntryId);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Travel entry deleted successfully and deduction removed']);
    
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $errorMessage = 'Database error: ' . $e->getMessage();
    error_log('Travel Delete PDO Error: ' . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    $errorMessage = 'Error deleting travel entry: ' . $e->getMessage();
    error_log('Travel Delete Error: ' . $errorMessage);
    echo json_encode(['success' => false, 'message' => $errorMessage]);
}

