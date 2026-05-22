<?php
session_start();

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../includes/utilization_deductions_helper.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $db = getDB();
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid data received');
    }
    
    $departmentId = $data['department_id'] ?? null;
    $fiscalYear = $data['fiscal_year'] ?? date('Y');
    $departmentName = $data['department_name'] ?? '';
    $utilizationEntries = $data['utilization_entries'] ?? [];
    $prEntries = $data['pr_entries'] ?? [];
    $travelsEntries = $data['travels_entries'] ?? [];
    $honorariaEntries = $data['honoraria_entries'] ?? [];
    $prDeductions = $data['pr_deductions'] ?? [];
    $travelsDeductions = $data['travels_deductions'] ?? [];
    $honorariaDeductions = $data['honoraria_deductions'] ?? [];
    $totals = $data['totals'] ?? [];
    $createdBy = $_SESSION['user_id'] ?? null;
    
    if (!$departmentId) {
        throw new Exception('Department ID is required');
    }
    
    // Check if table exists, create if not
    $checkTable = $db->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkTable->rowCount() == 0) {
        $createTable = "
            CREATE TABLE `utilization_summaries` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `department_id` INT(11) NOT NULL,
                `fiscal_year` YEAR(4) NOT NULL,
                `department_name` VARCHAR(255) NOT NULL,
                `utilization_entries` TEXT,
                `pr_entries` TEXT,
                `travels_entries` TEXT,
                `honoraria_entries` TEXT,
                `totals` TEXT,
                `created_by` INT(11) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_department` (`department_id`),
                KEY `idx_fiscal_year` (`fiscal_year`),
                KEY `idx_created_by` (`created_by`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ";
        $db->exec($createTable);
    } else {
        // Check if honoraria_entries column exists, add it if not
        $checkColumn = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'honoraria_entries'");
        if ($checkColumn->rowCount() == 0) {
            $db->exec("ALTER TABLE utilization_summaries ADD COLUMN honoraria_entries TEXT AFTER travels_entries");
        }
        
        // Check if deduction breakdown columns exist, add them if not
        $checkPrDeductions = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'pr_deductions'");
        if ($checkPrDeductions->rowCount() == 0) {
            $db->exec("ALTER TABLE utilization_summaries ADD COLUMN pr_deductions TEXT AFTER honoraria_entries");
        }
        
        $checkTravelsDeductions = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'travels_deductions'");
        if ($checkTravelsDeductions->rowCount() == 0) {
            $db->exec("ALTER TABLE utilization_summaries ADD COLUMN travels_deductions TEXT AFTER pr_deductions");
        }
        
        $checkHonorariaDeductions = $db->query("SHOW COLUMNS FROM utilization_summaries LIKE 'honoraria_deductions'");
        if ($checkHonorariaDeductions->rowCount() == 0) {
            $db->exec("ALTER TABLE utilization_summaries ADD COLUMN honoraria_deductions TEXT AFTER travels_deductions");
        }
    }
    
    // Check if summary already exists for this department and fiscal year
    $checkStmt = $db->prepare("SELECT id FROM utilization_summaries WHERE department_id = ? AND fiscal_year = ?");
    $checkStmt->execute([$departmentId, $fiscalYear]);
    $existingSummary = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    // Prepare data for storage
    $utilizationData = json_encode($utilizationEntries);
    $prData = json_encode($prEntries);
    $travelsData = json_encode($travelsEntries);
    $honorariaData = json_encode($honorariaEntries);
    $prDeductionsData = json_encode($prDeductions);
    $travelsDeductionsData = json_encode($travelsDeductions);
    $honorariaDeductionsData = json_encode($honorariaDeductions);
    $totalsData = json_encode($totals);
    
    if ($existingSummary) {
        // Update existing summary
        $stmt = $db->prepare("
            UPDATE utilization_summaries 
            SET department_name = ?,
                utilization_entries = ?,
                pr_entries = ?,
                travels_entries = ?,
                honoraria_entries = ?,
                pr_deductions = ?,
                travels_deductions = ?,
                honoraria_deductions = ?,
                totals = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $departmentName,
            $utilizationData,
            $prData,
            $travelsData,
            $honorariaData,
            $prDeductionsData,
            $travelsDeductionsData,
            $honorariaDeductionsData,
            $totalsData,
            $existingSummary['id']
        ]);
    } else {
        // Insert new summary
        $stmt = $db->prepare("
            INSERT INTO utilization_summaries 
            (department_id, fiscal_year, department_name, utilization_entries, pr_entries, travels_entries, honoraria_entries, pr_deductions, travels_deductions, honoraria_deductions, totals, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $departmentId,
            $fiscalYear,
            $departmentName,
            $utilizationData,
            $prData,
            $travelsData,
            $honorariaData,
            $prDeductionsData,
            $travelsDeductionsData,
            $honorariaDeductionsData,
            $totalsData,
            $createdBy
        ]);
    }
    
    // Send notifications to users in the selected department/office
    try {
        // Get current user name (the one saving the summary)
        $currentUserName = 'Budget Office';
        if ($createdBy) {
            $userStmt = $db->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
            $userStmt->execute([$createdBy]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['full_name']) {
                $currentUserName = $user['full_name'];
            }
        }
        
        // Get all active users in this department/office
        $usersStmt = $db->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1");
        $usersStmt->execute([$departmentId]);
        $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine if this is an update or new summary
        $isUpdate = $existingSummary ? true : false;
        $action = $isUpdate ? 'updated' : 'created';
        
        // Calculate total expenditures
        $totalExpenditures = ($totals['totalDeductions'] ?? 0) + ($totals['prTotal'] ?? 0) + ($totals['travelsTotal'] ?? 0) + ($totals['honorariaTotal'] ?? 0);
        
        // Create notification message
        $title = 'Budget Utilization Summary ' . ucfirst($action);
        $message = $isUpdate 
            ? "{$currentUserName} has updated the budget utilization summary for {$departmentName} (Fiscal Year {$fiscalYear}). Total Expenditures: ₱" . number_format($totalExpenditures, 2) . "."
            : "{$currentUserName} has created a new budget utilization summary for {$departmentName} (Fiscal Year {$fiscalYear}). Total Expenditures: ₱" . number_format($totalExpenditures, 2) . ".";
        
        // Add timestamp
        $message .= " (" . date('M j, Y g:i A') . ")";
        
        // Create notifications for each user in the department/office
        $notification = new Notification();
        $notifiedCount = 0;
        foreach ($userIds as $userId) {
            if ($notification->createNotification($userId, $title, $message, 'info')) {
                $notifiedCount++;
            }
        }
        
        // Also notify parent department users if this is a sub-department
        $parentDeptStmt = $db->prepare("SELECT parent_department_id FROM departments WHERE id = ?");
        $parentDeptStmt->execute([$departmentId]);
        $parentDeptResult = $parentDeptStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($parentDeptResult && $parentDeptResult['parent_department_id']) {
            $parentDeptId = $parentDeptResult['parent_department_id'];
            
            // Get parent department name
            $parentNameStmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
            $parentNameStmt->execute([$parentDeptId]);
            $parentDeptInfo = $parentNameStmt->fetch(PDO::FETCH_ASSOC);
            $parentDeptName = $parentDeptInfo ? $parentDeptInfo['dept_name'] : 'Parent Department';
            
            // Get all active users in the parent department
            $parentUsersStmt = $db->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1");
            $parentUsersStmt->execute([$parentDeptId]);
            $parentUserIds = $parentUsersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Create notification message for parent department
            $parentTitle = "Sub-Department {$departmentName} Utilization " . ucfirst($action);
            $parentMessage = $isUpdate 
                ? "The budget utilization for {$departmentName} has been updated. Fiscal Year {$fiscalYear} - Total Expenditures: ₱" . number_format($totalExpenditures, 2) . "."
                : "A new budget utilization has been created for {$departmentName}. Fiscal Year {$fiscalYear} - Total Expenditures: ₱" . number_format($totalExpenditures, 2) . ".";
            
            // Create notifications for each user in the parent department
            foreach ($parentUserIds as $parentUserId) {
                $notification->createNotification($parentUserId, $parentTitle, $parentMessage, 'info');
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't fail the save operation
        error_log('Error creating notifications: ' . $e->getMessage());
    }
    
    // Create PPMP deductions records for expense category tracking
    try {
        // Step 1: Get all existing deductions for this department and fiscal year
        $existingDeductionsQuery = "
            SELECT id, ppmp_item_id, expense_category, purchase_request_id
            FROM ppmp_deductions 
            WHERE department_id = :department_id 
            AND fiscal_year = :fiscal_year
        ";
        
        $existingDeductionsStmt = $db->prepare($existingDeductionsQuery);
        $existingDeductionsStmt->execute([
            ':department_id' => $departmentId,
            ':fiscal_year' => $fiscalYear
        ]);
        
        $existingDeductions = $existingDeductionsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Create a map of existing deductions for comparison
        // Key format: "ppmp_item_id|expense_category|purchase_request_id"
        $existingDeductionsMap = [];
        foreach ($existingDeductions as $deduction) {
            $key = $deduction['ppmp_item_id'] . '|' . $deduction['expense_category'] . '|' . $deduction['purchase_request_id'];
            $existingDeductionsMap[$key] = $deduction['id'];
        }
        
        // Track which deductions are still valid (present in the new data)
        $validDeductionKeys = [];
        
        // Step 2: Process PR deductions to create/update ppmp_deductions records
        if (!empty($prDeductions)) {
            foreach ($prDeductions as $deduction) {
                $expenseCategory = $deduction['category'] ?? '';
                $items = $deduction['items'] ?? [];
                
                if (empty($expenseCategory) || empty($items)) {
                    continue;
                }
                
                // For each PR item in this category
                foreach ($items as $item) {
                    $purchaseRequest = $item['purchaseRequest'] ?? '';
                    $amount = $item['amount'] ?? 0;
                    
                    if (empty($purchaseRequest) || $purchaseRequest === 'N/A' || $amount <= 0) {
                        continue;
                    }
                    
                    // Extract just the item name (before the comma if it exists)
                    // PR format: "Item Name, Type: Goods, Qty: 1.00, Unit: pcs, Amount: ₱200.00"
                    // PPMP format: "Item Name"
                    $itemName = $purchaseRequest;
                    if (strpos($purchaseRequest, ',') !== false) {
                        $itemName = trim(explode(',', $purchaseRequest)[0]);
                    }
                    
                        // Find matching PPMP items by description
                        // The purchaseRequest field contains the item description
                        $ppmpItemQuery = "
                            SELECT pi.id, pi.ppmp_id 
                            FROM ppmp_items pi
                            INNER JOIN ppmp p ON pi.ppmp_id = p.id
                            WHERE p.department_id = :department_id 
                            AND p.fiscal_year = :fiscal_year
                            AND (pi.general_description LIKE :description 
                                 OR pi.general_description LIKE :description_exact)
                            LIMIT 1
                        ";
                        
                        $ppmpItemStmt = $db->prepare($ppmpItemQuery);
                        $ppmpItemStmt->execute([
                            ':department_id' => $departmentId,
                            ':fiscal_year' => $fiscalYear,
                            ':description' => '%' . $itemName . '%',
                            ':description_exact' => $itemName
                        ]);
                        
                        $ppmpItem = $ppmpItemStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($ppmpItem) {
                        // Try to find the actual purchase_request_id from the database
                        // Match by description and amount
                        $prQuery = "
                            SELECT id FROM utilization_purchase_requests 
                            WHERE department_id = :department_id 
                            AND fiscal_year = :fiscal_year
                            AND (purchase_request LIKE :description1 OR particulars LIKE :description2)
                            AND amount = :amount
                            LIMIT 1
                        ";
                        
                        $prStmt = $db->prepare($prQuery);
                        $searchPattern = '%' . $itemName . '%';
                        $prStmt->execute([
                            ':department_id' => $departmentId,
                            ':fiscal_year' => $fiscalYear,
                            ':description1' => $searchPattern,
                            ':description2' => $searchPattern,
                            ':amount' => $amount
                        ]);
                        
                        $prRecord = $prStmt->fetch(PDO::FETCH_ASSOC);
                        $purchaseRequestId = $prRecord ? $prRecord['id'] : null;
                        
                        if (!$purchaseRequestId) {
                            error_log("No purchase request record found for: {$itemName} in department {$departmentId}, fiscal year {$fiscalYear}");
                            continue;
                        }
                        
                        // Mark this deduction as valid (still present in new data)
                        $deductionKey = $ppmpItem['id'] . '|' . $expenseCategory . '|' . $purchaseRequestId;
                        $validDeductionKeys[] = $deductionKey;
                        
                        // Check if deduction record already exists
                        $checkDeductionQuery = "
                            SELECT id FROM ppmp_deductions 
                            WHERE ppmp_id = :ppmp_id 
                            AND ppmp_item_id = :ppmp_item_id 
                            AND expense_category = :expense_category
                            AND fiscal_year = :fiscal_year
                        ";
                        
                        $checkDeductionStmt = $db->prepare($checkDeductionQuery);
                        $checkDeductionStmt->execute([
                            ':ppmp_id' => $ppmpItem['ppmp_id'],
                            ':ppmp_item_id' => $ppmpItem['id'],
                            ':expense_category' => $expenseCategory,
                            ':fiscal_year' => $fiscalYear
                        ]);
                        
                        $existingDeduction = $checkDeductionStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$existingDeduction) {
                            // Create new deduction record
                            $insertDeductionQuery = "
                                INSERT INTO ppmp_deductions 
                                (ppmp_id, ppmp_item_id, purchase_request_id, utilization_entry_id, department_id, expense_category, amount, fiscal_year, created_at)
                                VALUES 
                                (:ppmp_id, :ppmp_item_id, :purchase_request_id, :utilization_entry_id, :department_id, :expense_category, :amount, :fiscal_year, NOW())
                            ";
                            
                            $insertDeductionStmt = $db->prepare($insertDeductionQuery);
                            $insertDeductionStmt->execute([
                                ':ppmp_id' => $ppmpItem['ppmp_id'],
                                ':ppmp_item_id' => $ppmpItem['id'],
                                ':purchase_request_id' => $purchaseRequestId,
                                ':utilization_entry_id' => 0,
                                ':department_id' => $departmentId,
                                ':expense_category' => $expenseCategory,
                                ':amount' => $amount,
                                ':fiscal_year' => $fiscalYear
                            ]);
                            
                            error_log("Created ppmp_deduction: PPMP Item ID {$ppmpItem['id']}, PR ID {$purchaseRequestId}, Category: {$expenseCategory}, Amount: {$amount}");
                        } else {
                            // Update existing deduction amount
                            $updateDeductionQuery = "
                                UPDATE ppmp_deductions 
                                SET amount = :amount, updated_at = NOW()
                                WHERE id = :id
                            ";
                            
                            $updateDeductionStmt = $db->prepare($updateDeductionQuery);
                            $updateDeductionStmt->execute([
                                ':amount' => $amount,
                                ':id' => $existingDeduction['id']
                            ]);
                            
                            error_log("Updated ppmp_deduction ID {$existingDeduction['id']}: Amount: {$amount}");
                        }
                    } else {
                        error_log("No matching PPMP item found for: {$itemName} in department {$departmentId}, fiscal year {$fiscalYear}");
                    }
                } // End foreach items
            }
        }
        
        // Step 3: Delete deductions that are no longer present in the new data
        $deductionsToDelete = [];
        foreach ($existingDeductionsMap as $key => $deductionId) {
            if (!in_array($key, $validDeductionKeys)) {
                $deductionsToDelete[] = $deductionId;
            }
        }
        
        if (!empty($deductionsToDelete)) {
            // Get the ppmp_item_ids before deleting so we can clear their remarks
            $ppmpItemsToUpdate = [];
            foreach ($deductionsToDelete as $deductionId) {
                $getItemQuery = "SELECT ppmp_item_id FROM ppmp_deductions WHERE id = :id";
                $getItemStmt = $db->prepare($getItemQuery);
                $getItemStmt->execute([':id' => $deductionId]);
                $itemResult = $getItemStmt->fetch(PDO::FETCH_ASSOC);
                if ($itemResult) {
                    $ppmpItemsToUpdate[] = $itemResult['ppmp_item_id'];
                }
            }
            
            // Delete the deduction records
            $deleteQuery = "DELETE FROM ppmp_deductions WHERE id IN (" . implode(',', array_map('intval', $deductionsToDelete)) . ")";
            $db->exec($deleteQuery);
            
            error_log("Deleted " . count($deductionsToDelete) . " removed deductions");
            
            // Step 4: Clear remarks for PPMP items that no longer have any deductions
            foreach (array_unique($ppmpItemsToUpdate) as $ppmpItemId) {
                // Check if this item still has any deductions
                $checkRemainingQuery = "SELECT COUNT(*) as count FROM ppmp_deductions WHERE ppmp_item_id = :ppmp_item_id";
                $checkRemainingStmt = $db->prepare($checkRemainingQuery);
                $checkRemainingStmt->execute([':ppmp_item_id' => $ppmpItemId]);
                $remainingCount = $checkRemainingStmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                // If no deductions remain, clear the remarks
                if ($remainingCount == 0) {
                    $clearRemarksQuery = "UPDATE ppmp_items SET remarks = '' WHERE id = :id";
                    $clearRemarksStmt = $db->prepare($clearRemarksQuery);
                    $clearRemarksStmt->execute([':id' => $ppmpItemId]);
                    
                    error_log("Cleared remarks for PPMP item ID {$ppmpItemId} (no remaining deductions)");
                }
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't fail the save operation
        error_log('Error creating PPMP deductions: ' . $e->getMessage());
    }
    
    // Directly sync deductions from the summary data into budget_utilization_entries
    // This is the most reliable approach since utilizationEntries already has the correct values
    if (!empty($utilizationEntries)) {
        $syncStmt = $db->prepare("
            UPDATE budget_utilization_entries
            SET deductions = :deductions, total_balance = :total_balance
            WHERE department_id = :dept_id AND fiscal_year = :year AND expense_category = :category
        ");
        foreach ($utilizationEntries as $entry) {
            $category = $entry['category'] ?? $entry['expense_category'] ?? null;
            $deduction = isset($entry['deduction']) ? (float)$entry['deduction'] : (isset($entry['deductions']) ? (float)$entry['deductions'] : 0);
            $balance = isset($entry['balance']) ? (float)$entry['balance'] : (isset($entry['total_balance']) ? (float)$entry['total_balance'] : 0);
            if ($category === null || $category === '') continue;
            $syncStmt->execute([
                ':deductions'    => $deduction,
                ':total_balance' => $balance,
                ':dept_id'       => $departmentId,
                ':year'          => $fiscalYear,
                ':category'      => $category,
            ]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Budget utilization summary saved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

