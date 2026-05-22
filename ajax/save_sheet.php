<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $sheetName = trim($_POST['sheet_name'] ?? '');
    $departmentId = (int)($_POST['department_id'] ?? 0);
    $fiscalYear = (int)($_POST['fiscal_year'] ?? date('Y'));
    $headers = json_encode($_POST['headers'] ?? []);
    $columns = json_encode($_POST['columns'] ?? []);
    $data = json_encode($_POST['data'] ?? []);
    $cellFormats = json_encode($_POST['cell_formats'] ?? []);
    $sheetId = isset($_POST['sheet_id']) ? (int)$_POST['sheet_id'] : null;

    if (empty($sheetName) || $departmentId <= 0) {
        echo json_encode(['success' => false, 'message' => 'Sheet name and department are required']);
        exit;
    }

    $db = getDB();
    
    // Ensure table exists
    $db->exec("CREATE TABLE IF NOT EXISTS saved_sheets (
        id INT PRIMARY KEY AUTO_INCREMENT,
        sheet_name VARCHAR(255) NOT NULL,
        department_id INT NOT NULL,
        fiscal_year YEAR NOT NULL,
        headers TEXT NOT NULL,
        columns TEXT NOT NULL,
        data TEXT NOT NULL,
        cell_formats TEXT,
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_department (department_id),
        INDEX idx_fiscal_year (fiscal_year),
        INDEX idx_created_by (created_by)
    )");
    
    // Add cell_formats column if it doesn't exist
    try {
        $db->exec("ALTER TABLE saved_sheets ADD COLUMN cell_formats TEXT");
    } catch (PDOException $e) {
        // Column might already exist, ignore error
    }

    if ($sheetId) {
        // Update existing sheet
        $stmt = $db->prepare("UPDATE saved_sheets SET sheet_name = :name, department_id = :dept, fiscal_year = :year, headers = :headers, columns = :columns, data = :data, cell_formats = :formats, updated_at = NOW() WHERE id = :id AND created_by = :user");
        $stmt->execute([
            ':name' => $sheetName,
            ':dept' => $departmentId,
            ':year' => $fiscalYear,
            ':headers' => $headers,
            ':columns' => $columns,
            ':data' => $data,
            ':formats' => $cellFormats,
            ':id' => $sheetId,
            ':user' => $_SESSION['user_id']
        ]);
        echo json_encode(['success' => true, 'sheet_id' => $sheetId, 'message' => 'Sheet updated successfully']);
    } else {
        // Create new sheet
        $stmt = $db->prepare("INSERT INTO saved_sheets (sheet_name, department_id, fiscal_year, headers, columns, data, cell_formats, created_by) VALUES (:name, :dept, :year, :headers, :columns, :data, :formats, :user)");
        $stmt->execute([
            ':name' => $sheetName,
            ':dept' => $departmentId,
            ':year' => $fiscalYear,
            ':headers' => $headers,
            ':columns' => $columns,
            ':data' => $data,
            ':formats' => $cellFormats,
            ':user' => $_SESSION['user_id']
        ]);
        $newId = $db->lastInsertId();
        echo json_encode(['success' => true, 'sheet_id' => $newId, 'message' => 'Sheet saved successfully']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

