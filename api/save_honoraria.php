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
    
    // Create table if it doesn't exist
    $db->exec("
        CREATE TABLE IF NOT EXISTS utilization_honoraria (
            id INT PRIMARY KEY AUTO_INCREMENT,
            department_id INT NOT NULL,
            date VARCHAR(10) NULL,
            amount DECIMAL(15, 2) NOT NULL DEFAULT 0.00,
            fiscal_year INT NOT NULL,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_department (department_id),
            INDEX idx_fiscal_year (fiscal_year),
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    
    // Delete all existing entries for this department and fiscal year
    $stmt = $db->prepare("DELETE FROM utilization_honoraria WHERE department_id = :dept_id AND fiscal_year = :year");
    $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    
    // Prepare insert statement
    $insertSql = "
        INSERT INTO utilization_honoraria 
        (department_id, date, amount, fiscal_year, created_by)
        VALUES (:dept_id, :date, :amount, :year, :user_id)
    ";
    
    foreach ($entries as $entry) {
        // Parse amount - remove currency symbols and commas, then convert to float
        $amountStr = $entry['amount'] ?? '0';
        $amountStr = preg_replace('/[₱,\s]/', '', $amountStr); // Remove currency symbols, commas, and spaces
        $amount = !empty($amountStr) && is_numeric($amountStr) ? (float)$amountStr : 0.00;
        
        // Skip entries with 0 amount
        if ($amount <= 0) {
            continue;
        }
        
        $date = isset($entry['date']) && !empty($entry['date']) ? $entry['date'] : null;
        
        $stmt = $db->prepare($insertSql);
        $stmt->bindValue(':dept_id', $department_id, PDO::PARAM_INT);
        $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':year', $fiscal_year, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        
        $stmt->execute();
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Honoraria entries saved successfully']);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving honoraria entries: ' . $e->getMessage()]);
}

