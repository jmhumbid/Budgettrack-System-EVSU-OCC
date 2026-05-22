<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/BudgetAllocation.php';

try {
    $data = json_decode($_POST['data'] ?? '{}', true);
    if (!$data || !isset($data['rows'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid data']);
        exit;
    }

    $db = getDB();
    $alloc = new BudgetAllocation();
    $fiscalYear = $data['fiscal_year'] ?? date('Y');
    $headers = $data['headers'] ?? [];
    $rows = $data['rows'] ?? [];
    $newIds = [];

    // Map common column names to database fields
    $deptMap = ['Department', 'department', 'dept', 'Department Name'];
    $catMap = ['Category', 'category', 'cat', 'Category Name'];
    $allocMap = ['Allocated Amount', 'allocated_amount', 'Allocated', 'allocated'];
    $utilMap = ['Utilized Amount', 'utilized_amount', 'Utilized', 'utilized'];
    $remMap = ['Remaining Amount', 'remaining_amount', 'Remaining', 'remaining'];
    $yearMap = ['Fiscal Year', 'fiscal_year', 'Year', 'year'];

    // Get departments and categories for mapping
    $deptStmt = $db->prepare("SELECT id, dept_name FROM departments WHERE is_active = 1");
    $deptStmt->execute();
    $depts = [];
    while ($row = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
        $depts[strtolower($row['dept_name'])] = $row['id'];
    }

    $catStmt = $db->prepare("SELECT id, category_name FROM budget_categories WHERE is_active = 1");
    $catStmt->execute();
    $cats = [];
    while ($row = $catStmt->fetch(PDO::FETCH_ASSOC)) {
        $cats[strtolower($row['category_name'])] = $row['id'];
    }

    foreach ($rows as $row) {
        // Skip empty rows
        $hasData = false;
        foreach ($row as $key => $val) {
            if ($key !== '_id' && $val !== '' && $val !== null) {
                $hasData = true;
                break;
            }
        }
        if (!$hasData) continue;

        // Find department
        $deptId = null;
        $deptName = null;
        foreach ($deptMap as $mapKey) {
            if (isset($row[$mapKey]) && $row[$mapKey]) {
                $deptName = trim($row[$mapKey]);
                $deptId = $depts[strtolower($deptName)] ?? null;
                if ($deptId) break;
            }
        }

        // Find category
        $catId = null;
        $catName = null;
        foreach ($catMap as $mapKey) {
            if (isset($row[$mapKey]) && $row[$mapKey]) {
                $catName = trim($row[$mapKey]);
                $catId = $cats[strtolower($catName)] ?? null;
                if ($catId) break;
            }
        }

        // Find fiscal year
        $year = $fiscalYear;
        foreach ($yearMap as $mapKey) {
            if (isset($row[$mapKey]) && $row[$mapKey]) {
                $year = (int)$row[$mapKey];
                break;
            }
        }

        // Find allocated amount
        $allocated = 0;
        foreach ($allocMap as $mapKey) {
            if (isset($row[$mapKey]) && $row[$mapKey] !== '') {
                $allocated = (float)$row[$mapKey];
                break;
            }
        }

        if (!$deptId || !$catId) {
            continue; // Skip rows without valid department/category
        }

        if ($row['_id']) {
            // Update existing
            $stmt = $db->prepare("UPDATE budget_allocations SET allocated_amount = :amount WHERE id = :id");
            $stmt->execute([':amount' => $allocated, ':id' => $row['_id']]);
        } else {
            // Create new
            $stmt = $db->prepare("INSERT INTO budget_allocations (department_id, category_id, fiscal_year, allocated_amount, utilized_amount, remaining_amount, created_by) VALUES (:dept, :cat, :year, :alloc, 0, :alloc, :user)");
            $stmt->execute([
                ':dept' => $deptId,
                ':cat' => $catId,
                ':year' => $year,
                ':alloc' => $allocated,
                ':user' => $_SESSION['user_id']
            ]);
            $newIds[] = $db->lastInsertId();
        }
    }

    // Save grid structure as metadata (optional - for preserving custom columns)
    // Check if table exists, if not create it
    try {
        $db->exec("CREATE TABLE IF NOT EXISTS allocation_grid_metadata (
            fiscal_year YEAR NOT NULL PRIMARY KEY,
            headers TEXT NOT NULL,
            columns TEXT NOT NULL,
            updated_by INT NOT NULL,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE RESTRICT
        )");
        
        $metaStmt = $db->prepare("INSERT INTO allocation_grid_metadata (fiscal_year, headers, columns, updated_by) VALUES (:year, :headers, :columns, :user) ON DUPLICATE KEY UPDATE headers = VALUES(headers), columns = VALUES(columns), updated_by = VALUES(updated_by), updated_at = NOW()");
        $metaStmt->execute([
            ':year' => $fiscalYear,
            ':headers' => json_encode($headers),
            ':columns' => json_encode($data['columns'] ?? []),
            ':user' => $_SESSION['user_id']
        ]);
    } catch (Exception $e) {
        // Table creation or insert failed, but continue with main save
        error_log("Metadata save failed: " . $e->getMessage());
    }

    echo json_encode(['success' => true, 'ids' => $newIds, 'message' => 'Grid saved successfully']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

