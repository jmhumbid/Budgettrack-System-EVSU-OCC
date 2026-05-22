<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role'])) {
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

    $departmentId = $_GET['department_id'] ?? null;
    $fiscalYear = $_GET['fiscal_year'] ?? date('Y');

    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }

    $allYears = isset($_GET['all_years']) && $_GET['all_years'] == '1';

    if ($allYears) {
        $stmt = $db->prepare("SELECT * FROM prior_years_entries WHERE department_id = ? ORDER BY fiscal_year DESC, sort_order ASC, id ASC");
        $stmt->execute([$departmentId]);
    } else {
        $stmt = $db->prepare("SELECT * FROM prior_years_entries WHERE department_id = ? AND fiscal_year = ? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$departmentId, $fiscalYear]);
    }
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $response = ['success' => true, 'entries' => $entries];

    // Group entries by fiscal year when all_years is requested
    if ($allYears) {
        $years = [];
        foreach ($entries as $entry) {
            $fy = $entry['fiscal_year'];
            if (!isset($years[$fy])) {
                $years[$fy] = [];
            }
            $years[$fy][] = $entry;
        }
        $response['years'] = $years;
    }

    // Load custom columns for this department/year (skip if all_years)
    if (!$allYears) {
        try {
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

            $colStmt = $db->prepare("SELECT col_key, col_name, sort_order FROM prior_years_custom_columns WHERE department_id = ? AND fiscal_year = ? ORDER BY sort_order ASC, id ASC");
            $colStmt->execute([$departmentId, $fiscalYear]);
            $customColumns = $colStmt->fetchAll(PDO::FETCH_ASSOC);

            $customValues = [];
            if (!empty($customColumns)) {
                $valStmt = $db->prepare("SELECT col_key, expense_category, value FROM prior_years_custom_values WHERE department_id = ? AND fiscal_year = ?");
                $valStmt->execute([$departmentId, $fiscalYear]);
                foreach ($valStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $customValues[$row['col_key']][$row['expense_category']] = $row['value'];
                }
            }

            $response['custom_columns'] = $customColumns;
            $response['custom_values']  = $customValues;
        } catch (Exception $e) {
            $response['custom_columns'] = [];
            $response['custom_values']  = [];
        }
    }

    echo json_encode($response);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage(), 'entries' => []]);
}
