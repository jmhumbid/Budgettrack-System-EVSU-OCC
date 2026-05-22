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
    // This delete-replace pattern works because the frontend sends ALL entries that should exist
    // Delete existing entries for this department and fiscal year
    $stmt = $db->prepare("DELETE FROM utilization_travels WHERE department_id = :dept_id AND fiscal_year = :year");
    $stmt->execute([':dept_id' => $department_id, ':year' => $fiscal_year]);
    
    // Insert new entries
    $insertSql = "
        INSERT INTO utilization_travels 
        (department_id, travelled, event_activity, date, amount, fiscal_year, created_by, entry_id)
        VALUES (:dept_id, :travelled, :event_activity, :date, :amount, :year, :user_id, :entry_id)
    ";
    
    foreach ($entries as $entry) {
        // Parse amount - remove currency symbols and commas, then convert to float
        $amountStr = $entry['amount'] ?? '0';
        $amountStr = preg_replace('/[₱,\s]/', '', $amountStr); // Remove currency symbols, commas, and spaces
        $amount = !empty($amountStr) && is_numeric($amountStr) ? (float)$amountStr : 0.00;
        
        $date = !empty($entry['date']) ? $entry['date'] : null;
        $travelled = $entry['travelled'] ?? '';
        $eventActivity = $entry['event_activity'] ?? $entry['event'] ?? '';
        $entryId = isset($entry['entry_id']) ? (int)$entry['entry_id'] : null;
        
        $stmt = $db->prepare($insertSql);
        $stmt->bindValue(':dept_id', $department_id, PDO::PARAM_INT);
        $stmt->bindValue(':travelled', $travelled, PDO::PARAM_STR);
        $stmt->bindValue(':event_activity', $eventActivity, $eventActivity ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':year', $fiscal_year, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':entry_id', $entryId, $entryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        
        $stmt->execute();
    }
    
    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Travels saved successfully']);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving travels: ' . $e->getMessage()]);
}

