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

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['department_id']) || !isset($data['entries'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$department_id = $data['department_id'];
$entries = $data['entries'];
$fiscal_year = isset($data['fiscal_year']) ? $data['fiscal_year'] : date('Y');

try {
    $db = getDB();
    $db->beginTransaction();
    
    // NOTE: This is a shared database for all budget role users
    // The frontend should load ALL entries (from all budget users) before saving
    // Use UPSERT strategy: update existing entries (by ppmp_item_id match or position), insert new ones
    // This preserves IDs so localStorage deduction_selections remain valid
    
    // Get existing entries to determine which to update vs insert
    $existingStmt = $db->prepare("SELECT id, ppmp_item_id, purchase_request FROM utilization_purchase_requests WHERE department_id = :dept_id AND fiscal_year = :year ORDER BY id ASC");
    $existingStmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    $existingEntries = $existingStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build a map of existing entries by ppmp_item_id (for PPMP entries) and by position (for manual entries)
    $existingByPpmpItemId = [];
    $existingManual = []; // Non-PPMP entries in order
    foreach ($existingEntries as $existing) {
        if ($existing['ppmp_item_id']) {
            $existingByPpmpItemId[$existing['ppmp_item_id']] = $existing['id'];
        } else {
            $existingManual[] = $existing['id'];
        }
    }
    
    // Track which existing IDs are still in use (to delete removed entries)
    $usedIds = [];
    $manualIndex = 0;
    
    // Insert new entries
    $insertSql = "
        INSERT INTO utilization_purchase_requests 
        (department_id, purchase_request, particulars, pr_number, po_number, date, amount, fiscal_year, created_by,
         ppmp_item_id, ppmp_id, ppmp_description, entry_id)
        VALUES (:dept_id, :pr, :particulars, :pr_number, :po_number, :date, :amount, :year, :user_id,
                :ppmp_item_id, :ppmp_id, :ppmp_description, :entry_id)
    ";
    $updateSql = "
        UPDATE utilization_purchase_requests SET
        purchase_request=:pr, particulars=:particulars, pr_number=:pr_number, po_number=:po_number,
        date=:date, amount=:amount, created_by=:user_id,
        ppmp_item_id=:ppmp_item_id, ppmp_id=:ppmp_id, ppmp_description=:ppmp_description, entry_id=:entry_id
        WHERE id=:id
    ";
    
    foreach ($entries as $entry) {
        // Parse amount - remove currency symbols and commas, then convert to float
        $amountStr = $entry['amount'] ?? '0';
        $amountStr = preg_replace('/[₱,\s]/', '', $amountStr); // Remove currency symbols, commas, and spaces
        $amount = !empty($amountStr) && is_numeric($amountStr) ? (float)$amountStr : 0.00;
        
        // Handle PR/PO number - split if combined
        $prPoNumber = $entry['prNumber'] ?? $entry['pr_number'] ?? '';
        $prNumber = '';
        $poNumber = '';
        if ($prPoNumber) {
            if (strpos($prPoNumber, ' / ') !== false) {
                list($prNumber, $poNumber) = explode(' / ', $prPoNumber, 2);
            } else {
                $prNumber = $prPoNumber;
            }
        }
        
        // Handle date - ensure it's valid or null
        $date = null;
        if (!empty($entry['date'])) {
            $dateStr = trim($entry['date']);
            // Try to parse the date
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                $date = $dateStr;
            } elseif ($dateStr !== '') {
                // Try to convert other date formats
                $timestamp = strtotime($dateStr);
                if ($timestamp !== false) {
                    $date = date('Y-m-d', $timestamp);
                }
            }
        }
        
        // Handle both camelCase and snake_case field names from JavaScript
        $purchaseRequest = $entry['purchaseRequest'] ?? $entry['purchase_request'] ?? '';
        $particulars = $entry['particulars'] ?? null;  // Allow NULL
        
        // Handle PPMP references - support both single and multiple (comma-separated) IDs
        $ppmpItemIds = [];
        $ppmpIds = [];
        
        if (isset($entry['ppmp_item_id']) && !empty($entry['ppmp_item_id'])) {
            $itemIdStr = trim($entry['ppmp_item_id']);
            if (strpos($itemIdStr, ',') !== false) {
                // Multiple IDs (combined PPMP items)
                $ppmpItemIds = array_map('intval', array_filter(explode(',', $itemIdStr)));
            } else {
                // Single ID
                $ppmpItemIds = [(int)$itemIdStr];
            }
        }
        
        if (isset($entry['ppmp_id']) && !empty($entry['ppmp_id'])) {
            $ppmpIdStr = trim($entry['ppmp_id']);
            if (strpos($ppmpIdStr, ',') !== false) {
                // Multiple IDs (combined PPMP items)
                $ppmpIds = array_map('intval', array_filter(explode(',', $ppmpIdStr)));
            } else {
                // Single ID
                $ppmpIds = [(int)$ppmpIdStr];
            }
        }
        
        // For database storage, use the first ID (or null if empty)
        $ppmpItemId = !empty($ppmpItemIds) ? $ppmpItemIds[0] : null;
        $ppmpId = !empty($ppmpIds) ? $ppmpIds[0] : null;
        $ppmpDescription = $entry['ppmp_description'] ?? null;
        $entryId = isset($entry['entry_id']) ? (int)$entry['entry_id'] : null;
        
        // Determine if we should UPDATE an existing record or INSERT a new one
        // Match by ppmp_item_id for PPMP entries, or by position for manual entries
        $existingId = null;
        if ($ppmpItemId && isset($existingByPpmpItemId[$ppmpItemId])) {
            $existingId = $existingByPpmpItemId[$ppmpItemId];
        } elseif (!$ppmpItemId && $manualIndex < count($existingManual)) {
            $existingId = $existingManual[$manualIndex];
            $manualIndex++;
        }
        
        if ($existingId) {
            // UPDATE existing record (preserves ID)
            $usedIds[] = $existingId;
            $stmt = $db->prepare($updateSql);
            $stmt->bindValue(':id', $existingId, PDO::PARAM_INT);
            $stmt->bindValue(':pr', $purchaseRequest, PDO::PARAM_STR);
            $stmt->bindValue(':particulars', $particulars, $particulars ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':pr_number', $prNumber, $prNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':po_number', $poNumber, $poNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':ppmp_item_id', $ppmpItemId, $ppmpItemId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':ppmp_id', $ppmpId, $ppmpId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':ppmp_description', $ppmpDescription, $ppmpDescription ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':entry_id', $entryId, $entryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $purchaseRequestId = $existingId;
        } else {
            // INSERT new record
            $stmt = $db->prepare($insertSql);
            $stmt->bindValue(':dept_id', $department_id, PDO::PARAM_INT);
            $stmt->bindValue(':pr', $purchaseRequest, PDO::PARAM_STR);
            $stmt->bindValue(':particulars', $particulars, $particulars ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':pr_number', $prNumber, $prNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':po_number', $poNumber, $poNumber ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
            $stmt->bindValue(':year', $fiscal_year, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':ppmp_item_id', $ppmpItemId, $ppmpItemId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':ppmp_id', $ppmpId, $ppmpId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->bindValue(':ppmp_description', $ppmpDescription, $ppmpDescription ? PDO::PARAM_STR : PDO::PARAM_NULL);
            $stmt->bindValue(':entry_id', $entryId, $entryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
            $stmt->execute();
            $purchaseRequestId = $db->lastInsertId();
            $usedIds[] = $purchaseRequestId;
        }
        
        // If this purchase request is linked to PPMP items, create deduction records
        if (!empty($ppmpItemIds) && !empty($ppmpIds) && $entryId && $amount > 0) {
            // Get the expense category from the utilization entry
            $categoryStmt = $db->prepare("SELECT expense_category FROM budget_utilization_entries WHERE id = ? OR deducted_from_entry_id = ?");
            $categoryStmt->execute([$entryId, $entryId]);
            $categoryResult = $categoryStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($categoryResult && !empty($categoryResult['expense_category'])) {
                $expenseCategory = $categoryResult['expense_category'];
                
                // Calculate amount per item for combined entries
                $amountPerItem = $amount / count($ppmpItemIds);
                
                // Create deduction records for each PPMP item
                for ($i = 0; $i < count($ppmpItemIds); $i++) {
                    $currentPpmpItemId = $ppmpItemIds[$i];
                    $currentPpmpId = isset($ppmpIds[$i]) ? $ppmpIds[$i] : $ppmpIds[0]; // Use first PPMP ID if not enough IDs
                    
                    // Check if deduction record already exists
                    $checkDeductionStmt = $db->prepare("
                        SELECT id FROM ppmp_deductions 
                        WHERE ppmp_id = ? AND ppmp_item_id = ? AND purchase_request_id = ? AND expense_category = ? AND fiscal_year = ?
                    ");
                    $checkDeductionStmt->execute([$currentPpmpId, $currentPpmpItemId, $purchaseRequestId, $expenseCategory, $fiscal_year]);
                    
                    if (!$checkDeductionStmt->fetch()) {
                        // Create deduction record
                        $deductionStmt = $db->prepare("
                            INSERT INTO ppmp_deductions 
                            (ppmp_id, ppmp_item_id, purchase_request_id, utilization_entry_id, department_id, expense_category, amount, fiscal_year, created_at)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                        ");
                        $deductionStmt->execute([
                            $currentPpmpId, 
                            $currentPpmpItemId, 
                            $purchaseRequestId, 
                            $entryId, 
                            $department_id, 
                            $expenseCategory, 
                            $amountPerItem, 
                            $fiscal_year
                        ]);
                        
                        error_log("Created PPMP deduction: PPMP Item ID {$currentPpmpItemId}, PR ID {$purchaseRequestId}, Category: {$expenseCategory}, Amount: {$amountPerItem}");
                        
                        // Debug: Verify the deduction was created
                        $verifyStmt = $db->prepare("SELECT * FROM ppmp_deductions WHERE ppmp_item_id = ? AND expense_category = ? ORDER BY id DESC LIMIT 1");
                        $verifyStmt->execute([$currentPpmpItemId, $expenseCategory]);
                        $createdDeduction = $verifyStmt->fetch(PDO::FETCH_ASSOC);
                        if ($createdDeduction) {
                            error_log("✓ Verified deduction created with ID: {$createdDeduction['id']}");
                        } else {
                            error_log("✗ Failed to verify deduction creation for PPMP Item ID {$currentPpmpItemId}");
                        }
                    }
                }
            }
        }
    }
    
    // Delete entries that were removed (not in usedIds)
    if (!empty($existingEntries)) {
        $allExistingIds = array_column($existingEntries, 'id');
        $removedIds = array_diff($allExistingIds, $usedIds);
        if (!empty($removedIds)) {
            $placeholders = implode(',', array_fill(0, count($removedIds), '?'));
            $deleteStmt = $db->prepare("DELETE FROM utilization_purchase_requests WHERE id IN ($placeholders)");
            $deleteStmt->execute(array_values($removedIds));
        }
    }
    
    $db->commit();
    
    // Recalculate deductions in DB for all affected utilization entries
    // This ensures the deductions column stays in sync so page refresh shows correct values
    $affectedEntryIds = [];
    $affectedStmt = $db->prepare("SELECT DISTINCT entry_id FROM utilization_purchase_requests WHERE department_id = :dept_id AND fiscal_year = :year AND entry_id IS NOT NULL");
    $affectedStmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    while ($row = $affectedStmt->fetch(PDO::FETCH_ASSOC)) {
        $affectedEntryIds[] = (int)$row['entry_id'];
    }
    foreach (array_unique($affectedEntryIds) as $entryId) {
        recalculateDeductionsForEntry($db, $entryId);
    }
    
    echo json_encode(['success' => true, 'message' => 'Purchase requests saved successfully']);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving purchase requests: ' . $e->getMessage()]);
}

