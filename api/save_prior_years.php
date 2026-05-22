<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    
    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS prior_years_entries (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_id INT NOT NULL,
        expense_category VARCHAR(500) NOT NULL,
        student_development DECIMAL(15,2) DEFAULT 0,
        faculty_development DECIMAL(15,2) DEFAULT 0,
        curriculum_development DECIMAL(15,2) DEFAULT 0,
        facilities_development DECIMAL(15,2) DEFAULT 0,
        development_fee DECIMAL(15,2) DEFAULT 0,
        laboratory_fee DECIMAL(15,2) DEFAULT 0,
        computer_fee DECIMAL(15,2) DEFAULT 0,
        fiscal_year INT NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_dept_year (department_id, fiscal_year),
        INDEX idx_category (expense_category)
    )");
    
    // Add sort_order column if it doesn't exist
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM prior_years_entries LIKE 'sort_order'");
        if ($checkCol->rowCount() == 0) {
            $db->exec("ALTER TABLE prior_years_entries ADD COLUMN sort_order INT DEFAULT 0 AFTER fiscal_year");
        }
    } catch (Exception $e) {
        // Column might already exist
    }

    $db->exec("CREATE TABLE IF NOT EXISTS prior_years_custom_columns (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_id INT NOT NULL,
        fiscal_year INT NOT NULL,
        col_key VARCHAR(100) NOT NULL,
        col_name VARCHAR(255) NOT NULL,
        sort_order INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_col (department_id, fiscal_year, col_key),
        INDEX idx_dept_year_col (department_id, fiscal_year)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS prior_years_custom_values (
        id INT PRIMARY KEY AUTO_INCREMENT,
        department_id INT NOT NULL,
        fiscal_year INT NOT NULL,
        col_key VARCHAR(100) NOT NULL,
        expense_category VARCHAR(500) NOT NULL,
        value DECIMAL(15,2) DEFAULT 0,
        UNIQUE KEY uq_val (department_id, fiscal_year, col_key, expense_category),
        INDEX idx_dept_year_val (department_id, fiscal_year)
    )");

    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $action = $input['action'] ?? 'save';
    $departmentId = $input['department_id'] ?? null;
    $fiscalYear = $input['fiscal_year'] ?? date('Y');
    
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }
    
    if ($action === 'save') {
        $entries = $input['entries'] ?? [];
        
        // Delete existing entries for this department/year first
        $deleteStmt = $db->prepare("DELETE FROM prior_years_entries WHERE department_id = ? AND fiscal_year = ?");
        $deleteStmt->execute([$departmentId, $fiscalYear]);
        
        // Insert new entries with sort_order to preserve order
        $insertStmt = $db->prepare("INSERT INTO prior_years_entries 
            (department_id, expense_category, student_development, faculty_development, 
             curriculum_development, facilities_development, development_fee, 
             laboratory_fee, computer_fee, fiscal_year, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $sortOrder = 0;
        foreach ($entries as $entry) {
            if (empty($entry['expense_category'])) continue;
            
            $insertStmt->execute([
                $departmentId,
                $entry['expense_category'],
                floatval($entry['student_development'] ?? 0),
                floatval($entry['faculty_development'] ?? 0),
                floatval($entry['curriculum_development'] ?? 0),
                floatval($entry['facilities_development'] ?? 0),
                floatval($entry['development_fee'] ?? 0),
                floatval($entry['laboratory_fee'] ?? 0),
                floatval($entry['computer_fee'] ?? 0),
                $fiscalYear,
                $sortOrder++
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Prior years entries saved successfully']);
    } elseif ($action === 'delete') {
        $entryId = $input['entry_id'] ?? null;
        if ($entryId) {
            $stmt = $db->prepare("DELETE FROM prior_years_entries WHERE id = ? AND department_id = ?");
            $stmt->execute([$entryId, $departmentId]);
            echo json_encode(['success' => true, 'message' => 'Entry deleted']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Entry ID required']);
        }
    } elseif ($action === 'save_column') {
        $colKey  = $input['col_key']  ?? null;
        $colName = $input['col_name'] ?? null;
        $sortOrder = $input['sort_order'] ?? 0;
        if (!$colKey || !$colName) {
            echo json_encode(['success' => false, 'message' => 'col_key and col_name required']);
            exit;
        }
        $stmt = $db->prepare("INSERT INTO prior_years_custom_columns (department_id, fiscal_year, col_key, col_name, sort_order)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE col_name = VALUES(col_name), sort_order = VALUES(sort_order)");
        $stmt->execute([$departmentId, $fiscalYear, $colKey, $colName, $sortOrder]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_column') {
        $colKey = $input['col_key'] ?? null;
        if (!$colKey) {
            echo json_encode(['success' => false, 'message' => 'col_key required']);
            exit;
        }
        $db->prepare("DELETE FROM prior_years_custom_columns WHERE department_id = ? AND fiscal_year = ? AND col_key = ?")
           ->execute([$departmentId, $fiscalYear, $colKey]);
        $db->prepare("DELETE FROM prior_years_custom_values WHERE department_id = ? AND fiscal_year = ? AND col_key = ?")
           ->execute([$departmentId, $fiscalYear, $colKey]);
        echo json_encode(['success' => true]);
    } elseif ($action === 'save_column_values') {
        $colKey  = $input['col_key']  ?? null;
        $values  = $input['values']   ?? [];
        if (!$colKey) {
            echo json_encode(['success' => false, 'message' => 'col_key required']);
            exit;
        }
        $db->prepare("DELETE FROM prior_years_custom_values WHERE department_id = ? AND fiscal_year = ? AND col_key = ?")
           ->execute([$departmentId, $fiscalYear, $colKey]);
        $stmt = $db->prepare("INSERT INTO prior_years_custom_values (department_id, fiscal_year, col_key, expense_category, value) VALUES (?, ?, ?, ?, ?)");
        foreach ($values as $cat => $val) {
            if ($cat === '') continue;
            $stmt->execute([$departmentId, $fiscalYear, $colKey, $cat, floatval($val)]);
        }
        echo json_encode(['success' => true]);
    } elseif ($action === 'delete_year') {
        // Delete all entries for a specific fiscal year
        $stmt = $db->prepare("DELETE FROM prior_years_entries WHERE department_id = ? AND fiscal_year = ?");
        $stmt->execute([$departmentId, $fiscalYear]);
        
        // Also delete custom columns and values for that year
        $db->prepare("DELETE FROM prior_years_custom_columns WHERE department_id = ? AND fiscal_year = ?")
           ->execute([$departmentId, $fiscalYear]);
        $db->prepare("DELETE FROM prior_years_custom_values WHERE department_id = ? AND fiscal_year = ?")
           ->execute([$departmentId, $fiscalYear]);
        
        echo json_encode(['success' => true, 'message' => 'Fiscal year data deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
