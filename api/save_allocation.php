<?php
session_start();

// Check if user is logged in and has allocations access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $conn = getDB();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        throw new Exception('Invalid data received');
    }
    
    $departmentId = $data['department_id'] ?? null;
    $fiscalYear = $data['fiscal_year'] ?? date('Y');
    $numStudents = $data['num_students'] ?? 0;
    $totalTuitionFee = $data['total_tuition_fee'] ?? 0;
    $instructionalAmount = $data['instructional_amount'] ?? 0;
    $budgetAllocated = $data['budget_allocated'] ?? 0;
    $overallTotal = $data['overall_total'] ?? 0;
    $additionalAmount = $data['additional_amount'] ?? 0;
    $additionalDescription = $data['additional_description'] ?? null;
    $allocationData = json_encode($data['allocation_data'] ?? []);
    $createdBy = $_SESSION['user_id'] ?? null;
    
    if (!$departmentId) {
        throw new Exception('Department ID is required');
    }
    
    // Check if table exists, create if not
    $checkTable = $conn->query("SHOW TABLES LIKE 'budget_allocations'");
    if ($checkTable->rowCount() == 0) {
        $createTable = "
        CREATE TABLE `budget_allocations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `department_id` int(11) NOT NULL,
          `fiscal_year` year(4) NOT NULL,
          `num_students` int(11) DEFAULT NULL,
          `total_tuition_fee` decimal(15,2) NOT NULL DEFAULT 0.00,
          `instructional_amount` decimal(15,2) NOT NULL DEFAULT 0.00,
          `budget_allocated` decimal(15,2) NOT NULL DEFAULT 0.00,
          `overall_total` decimal(15,2) NOT NULL DEFAULT 0.00,
          `allocation_data` longtext NOT NULL,
          `created_by` int(11) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `department_id` (`department_id`),
          KEY `fiscal_year` (`fiscal_year`),
          KEY `idx_dept_fiscal` (`department_id`, `fiscal_year`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ";
        $conn->exec($createTable);
    } else {
        // Check for problematic foreign keys and remove them
        try {
            $foreignKeys = $conn->query("
                SELECT 
                    CONSTRAINT_NAME,
                    COLUMN_NAME,
                    REFERENCED_TABLE_NAME
                FROM information_schema.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'budget_allocations' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($foreignKeys as $fk) {
                $constraintName = $fk['CONSTRAINT_NAME'];
                $columnName = $fk['COLUMN_NAME'] ?? '';
                $refTable = $fk['REFERENCED_TABLE_NAME'] ?? '';
                
                // Remove foreign keys that reference budget_categories or use category_id
                // Also remove budget_allocations_ibfk_2 specifically (the one causing the error)
                if ($refTable === 'budget_categories' || 
                    $columnName === 'category_id' ||
                    $constraintName === 'budget_allocations_ibfk_2' ||
                    strpos($constraintName, 'category') !== false) {
                    try {
                        $conn->exec("ALTER TABLE budget_allocations DROP FOREIGN KEY `$constraintName`");
                    } catch (Exception $e) {
                        // Ignore if already dropped or doesn't exist
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore errors in foreign key checking
        }
        
        // Remove category_id column if it exists
        try {
            $columns = $conn->query("SHOW COLUMNS FROM budget_allocations")->fetchAll(PDO::FETCH_COLUMN);
            if (in_array('category_id', $columns)) {
                // First try to remove any foreign key on category_id
                try {
                    $fkOnCategory = $conn->query("
                        SELECT CONSTRAINT_NAME 
                        FROM information_schema.KEY_COLUMN_USAGE 
                        WHERE TABLE_SCHEMA = DATABASE() 
                        AND TABLE_NAME = 'budget_allocations' 
                        AND COLUMN_NAME = 'category_id'
                        AND REFERENCED_TABLE_NAME IS NOT NULL
                    ")->fetchAll(PDO::FETCH_COLUMN);
                    
                    foreach ($fkOnCategory as $fkName) {
                        try {
                            $conn->exec("ALTER TABLE budget_allocations DROP FOREIGN KEY `$fkName`");
                        } catch (Exception $e) {}
                    }
                } catch (Exception $e) {}
                
                // Now remove the column
                try {
                    $conn->exec("ALTER TABLE budget_allocations DROP COLUMN category_id");
                } catch (Exception $e) {
                    // Column might be in use or doesn't exist
                }
            }
        } catch (Exception $e) {
            // Ignore column removal errors
        }
        // Table exists, check for missing columns and add them
        $columns = $conn->query("SHOW COLUMNS FROM budget_allocations")->fetchAll(PDO::FETCH_COLUMN);
        
        if (!in_array('num_students', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN num_students int(11) DEFAULT NULL AFTER fiscal_year");
        }
        if (!in_array('total_tuition_fee', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN total_tuition_fee decimal(15,2) NOT NULL DEFAULT 0.00 AFTER num_students");
        }
        if (!in_array('instructional_amount', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN instructional_amount decimal(15,2) NOT NULL DEFAULT 0.00 AFTER total_tuition_fee");
        }
        if (!in_array('overall_total', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN overall_total decimal(15,2) NOT NULL DEFAULT 0.00 AFTER instructional_amount");
        }
        if (!in_array('allocation_data', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN allocation_data longtext NOT NULL AFTER overall_total");
        }
        if (!in_array('created_by', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN created_by int(11) DEFAULT NULL AFTER allocation_data");
        }
        if (!in_array('budget_allocated', $columns)) {
            $conn->exec("ALTER TABLE budget_allocations ADD COLUMN budget_allocated decimal(15,2) NOT NULL DEFAULT 0.00 AFTER instructional_amount");
        }
        
        // Remove unique constraints if they exist (to allow multiple history entries per department/fiscal year)
        // Check for unique indexes using information_schema
        try {
            $uniqueIndexes = $conn->query("
                SELECT DISTINCT INDEX_NAME
                FROM information_schema.STATISTICS
                WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME = 'budget_allocations'
                AND NON_UNIQUE = 0
                AND INDEX_NAME != 'PRIMARY'
            ")->fetchAll(PDO::FETCH_COLUMN);
            
            // Drop all unique indexes except PRIMARY
            foreach ($uniqueIndexes as $indexName) {
                try {
                    $conn->exec("ALTER TABLE budget_allocations DROP INDEX `$indexName`");
                } catch (Exception $e) {
                    // Ignore if index doesn't exist or can't be dropped
                }
            }
        } catch (Exception $e) {
            // Ignore errors in index checking - try alternative method
            try {
                // Fallback: Try to drop specific known unique constraint names
                $uniqueConstraints = ['unique_dept_fiscal', 'unique_dept_category_year'];
                foreach ($uniqueConstraints as $constraintName) {
                    try {
                        $conn->exec("ALTER TABLE budget_allocations DROP INDEX `$constraintName`");
                    } catch (Exception $e2) {
                        // Ignore if constraint doesn't exist
                    }
                }
            } catch (Exception $e3) {
                // Ignore all errors
            }
        }
        
        // Ensure we have a regular index (not unique) for performance
        try {
            $indexes = $conn->query("SHOW INDEXES FROM budget_allocations WHERE Key_name = 'idx_dept_fiscal'")->fetchAll();
            if (empty($indexes)) {
                $conn->exec("ALTER TABLE budget_allocations ADD KEY idx_dept_fiscal (department_id, fiscal_year)");
            } else {
                // Check if the existing index is unique, if so drop and recreate as non-unique
                $indexInfo = $indexes[0];
                if (isset($indexInfo['Non_unique']) && $indexInfo['Non_unique'] == 0) {
                    $conn->exec("ALTER TABLE budget_allocations DROP INDEX idx_dept_fiscal");
                    $conn->exec("ALTER TABLE budget_allocations ADD KEY idx_dept_fiscal (department_id, fiscal_year)");
                }
            }
        } catch (Exception $e) {
            // Index might already exist, ignore
        }
        
        // Ensure foreign keys are correct (only department_id and created_by if users table exists)
        try {
            // Check if users table exists
            $usersTable = $conn->query("SHOW TABLES LIKE 'users'")->rowCount();
            if ($usersTable > 0) {
                // Try to add foreign key for created_by if it doesn't exist
                $fkExists = $conn->query("
                    SELECT CONSTRAINT_NAME 
                    FROM information_schema.KEY_COLUMN_USAGE 
                    WHERE TABLE_SCHEMA = DATABASE() 
                    AND TABLE_NAME = 'budget_allocations' 
                    AND COLUMN_NAME = 'created_by'
                    AND REFERENCED_TABLE_NAME = 'users'
                ")->rowCount();
                
                if ($fkExists == 0) {
                    try {
                        $conn->exec("ALTER TABLE budget_allocations ADD CONSTRAINT budget_allocations_ibfk_created_by FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL");
                    } catch (Exception $e) {
                        // Ignore if constraint already exists or can't be added
                    }
                }
            }
        } catch (Exception $e) {
            // Ignore foreign key setup errors
        }
    }
    
    // Check if an allocation already exists for this department and fiscal year
    $checkStmt = $conn->prepare("
        SELECT id FROM budget_allocations 
        WHERE department_id = ? AND fiscal_year = ? 
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $checkStmt->execute([$departmentId, $fiscalYear]);
    $existingAllocation = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingAllocation) {
        // Update existing allocation
        $stmt = $conn->prepare("
            UPDATE budget_allocations 
            SET num_students = ?,
                total_tuition_fee = ?,
                instructional_amount = ?,
                budget_allocated = ?,
                overall_total = ?,
                additional_amount = ?,
                additional_description = ?,
                allocation_data = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        
        $stmt->execute([
            $numStudents,
            $totalTuitionFee,
            $instructionalAmount,
            $budgetAllocated,
            $overallTotal,
            $additionalAmount,
            $additionalDescription,
            $allocationData,
            $existingAllocation['id']
        ]);
    } else {
        // Insert new allocation if none exists
        $stmt = $conn->prepare("
            INSERT INTO budget_allocations 
            (department_id, fiscal_year, num_students, total_tuition_fee, instructional_amount, budget_allocated, overall_total, additional_amount, additional_description, allocation_data, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $departmentId,
            $fiscalYear,
            $numStudents,
            $totalTuitionFee,
            $instructionalAmount,
            $budgetAllocated,
            $overallTotal,
            $additionalAmount,
            $additionalDescription,
            $allocationData,
            $createdBy
        ]);
    }
    
    // Update department_budgets table
    $budgetStmt = $conn->prepare("
        INSERT INTO department_budgets (department_id, fiscal_year, total_allocated)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE
        total_allocated = VALUES(total_allocated),
        last_updated = CURRENT_TIMESTAMP
    ");
    $budgetStmt->execute([$departmentId, $fiscalYear, $overallTotal]);
    
    // Send notifications to users in the selected department/office
    try {
        // Get department/office name
        $deptStmt = $conn->prepare("SELECT dept_name, fiduciary_type FROM departments WHERE id = ?");
        $deptStmt->execute([$departmentId]);
        $department = $deptStmt->fetch(PDO::FETCH_ASSOC);
        $departmentName = $department ? $department['dept_name'] : 'Department/Office';
        $isOffice = $department && $department['fiduciary_type'] === 'Fiduciary';
        
        // Get current user name (the one saving the allocation)
        $currentUserName = 'Budget Office';
        if ($createdBy) {
            $userStmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM users WHERE id = ?");
            $userStmt->execute([$createdBy]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
            if ($user && $user['full_name']) {
                $currentUserName = $user['full_name'];
            }
        }
        
        // Get all active users in this department/office
        $usersStmt = $conn->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1");
        $usersStmt->execute([$departmentId]);
        $userIds = $usersStmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Determine if this is an update or new allocation
        $isUpdate = $existingAllocation ? true : false;
        $action = $isUpdate ? 'updated' : 'created';
        
        // Create notification message
        $title = 'Budget Allocation ' . ucfirst($action);
        $message = $isUpdate 
            ? "{$currentUserName} has updated the budget allocation for {$departmentName} (Fiscal Year {$fiscalYear}). Overall Total: ₱" . number_format($overallTotal, 2) . "."
            : "{$currentUserName} has created a new budget allocation for {$departmentName} (Fiscal Year {$fiscalYear}). Overall Total: ₱" . number_format($overallTotal, 2) . ".";
        
        // Add timestamp
        $message .= " (" . date('M j, Y g:i A') . ")";
        
        // Create notifications for each user in the department/office
        $notification = new Notification();
        $notifiedCount = 0;
        foreach ($userIds as $userId) {
            if ($notification->createNotification($userId, $title, $message, 'success')) {
                $notifiedCount++;
            }
        }
        
        // Also notify parent department if this is a sub-department
        $parentStmt = $conn->prepare("SELECT parent_department_id FROM departments WHERE id = ?");
        $parentStmt->execute([$departmentId]);
        $parentDeptData = $parentStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($parentDeptData && !empty($parentDeptData['parent_department_id'])) {
            $parentDeptId = $parentDeptData['parent_department_id'];
            
            // Get parent department name
            $parentDeptStmt = $conn->prepare("SELECT dept_name FROM departments WHERE id = ?");
            $parentDeptStmt->execute([$parentDeptId]);
            $parentDept = $parentDeptStmt->fetch(PDO::FETCH_ASSOC);
            $parentDeptName = $parentDept ? $parentDept['dept_name'] : 'Parent Department';
            
            // Get all active users in parent department
            $parentUsersStmt = $conn->prepare("SELECT id FROM users WHERE department_id = ? AND is_active = 1");
            $parentUsersStmt->execute([$parentDeptId]);
            $parentUserIds = $parentUsersStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Create notification message for parent department
            $parentTitle = 'Sub-Department Allocation ' . ucfirst($action);
            $parentMessage = $isUpdate 
                ? "{$currentUserName} has updated the budget allocation for your sub-department {$departmentName} (Fiscal Year {$fiscalYear}). Overall Total: ₱" . number_format($overallTotal, 2) . "."
                : "{$currentUserName} has created a new budget allocation for your sub-department {$departmentName} (Fiscal Year {$fiscalYear}). Overall Total: ₱" . number_format($overallTotal, 2) . ".";
            $parentMessage .= " (" . date('M j, Y g:i A') . ")";
            
            // Notify parent department users
            foreach ($parentUserIds as $parentUserId) {
                $notification->createNotification($parentUserId, $parentTitle, $parentMessage, 'info');
            }
        }
        
    } catch (Exception $e) {
        // Log error but don't fail the save operation
        error_log('Error creating notifications: ' . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Budget allocation saved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

