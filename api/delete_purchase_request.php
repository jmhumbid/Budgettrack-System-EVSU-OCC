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

if (!isset($data['pr_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$pr_id = (int)$data['pr_id'];

try {
    $db = getDB();
    $db->beginTransaction();
    
    // Check if deducted_from_entry_id column exists
    $checkColumn = $db->query("SHOW COLUMNS FROM utilization_purchase_requests LIKE 'deducted_from_entry_id'");
    $hasDeductedFromColumn = $checkColumn->rowCount() > 0;
    
    // Get the entry - only select deducted_from_entry_id if column exists
    if ($hasDeductedFromColumn) {
        $stmt = $db->prepare("SELECT amount, deducted_from_entry_id FROM utilization_purchase_requests WHERE id = :id");
    } else {
        $stmt = $db->prepare("SELECT amount FROM utilization_purchase_requests WHERE id = :id");
    }
    $stmt->execute([':id' => $pr_id]);
    $entry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Purchase request not found']);
        exit;
    }
    
    // Parse amount - handle both string and numeric values, remove currency symbols
    $amountStr = $entry['amount'];
    if (is_string($amountStr)) {
        $amountStr = str_replace(['₱', ',', ' '], '', $amountStr);
    }
    $amount = (float)$amountStr;
    
    $deductedFromEntryId = null;
    if ($hasDeductedFromColumn && !empty($entry['deducted_from_entry_id'])) {
        $deductedFromEntryId = (int)$entry['deducted_from_entry_id'];
    }
    
    // Get department_id before deletion (needed for deduction sources cleanup)
    $deptStmt = $db->prepare("SELECT department_id FROM utilization_purchase_requests WHERE id = :id");
    $deptStmt->execute([':id' => $pr_id]);
    $prDept = $deptStmt->fetch(PDO::FETCH_ASSOC);
    $pr_department_id = $prDept ? $prDept['department_id'] : null;
    
    // Delete the purchase request entry
    $deleteStmt = $db->prepare("DELETE FROM utilization_purchase_requests WHERE id = :id");
    $deleteStmt->execute([':id' => $pr_id]);
    
    // Remove this PR entry from deduction sources
    // This ensures the deduction sources table stays in sync
    if ($pr_department_id) {
        $checkDeductionSourcesTable = $db->query("SHOW TABLES LIKE 'budget_utilization_deduction_sources'");
        if ($checkDeductionSourcesTable->rowCount() > 0) {
            // Get all deduction sources that might contain this PR entry
            $sourcesStmt = $db->prepare("
                SELECT id, source_entries, amount 
                FROM budget_utilization_deduction_sources 
                WHERE department_id = :dept_id 
                AND source_type = 'purchase_request'
            ");
            $sourcesStmt->execute([':dept_id' => $pr_department_id]);
            $sources = $sourcesStmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($sources as $source) {
                $entries = json_decode($source['source_entries'], true);
                $originalCount = count($entries);
                
                // Remove this PR entry from the entries array
                $entries = array_filter($entries, function($entry) use ($pr_id) {
                    $entryId = isset($entry['sourceEntryId']) ? $entry['sourceEntryId'] : null;
                    return $entryId != $pr_id && (string)$entryId !== (string)$pr_id;
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
    }
    
    // Recalculate deductions for the affected entry (only if column exists and value is set)
    if ($deductedFromEntryId && $hasDeductedFromColumn) {
        recalculateDeductionsForEntry($db, $deductedFromEntryId);
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Purchase request deleted successfully']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    error_log('Purchase Request Delete Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error deleting purchase request: ' . $e->getMessage()]);
}

