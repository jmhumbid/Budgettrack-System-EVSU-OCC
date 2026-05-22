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

if (!isset($data['department_id']) || !isset($data['entry'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$department_id = $data['department_id'];
$entry = $data['entry'];
$fiscal_year = isset($data['fiscal_year']) ? $data['fiscal_year'] : date('Y');
$travel_id = isset($data['travel_id']) ? (int)$data['travel_id'] : null; // For updates

try {
    $db = getDB();
    
    // Parse amount - remove currency symbols and commas
    $amountStr = $entry['amount'] ?? '0';
    $amountStr = preg_replace('/[₱,\s]/', '', $amountStr);
    $amount = !empty($amountStr) && is_numeric($amountStr) ? (float)$amountStr : 0.00;
    
    // Handle date
    $date = null;
    if (!empty($entry['date'])) {
        $dateStr = trim($entry['date']);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
            $date = $dateStr;
        } elseif ($dateStr !== '') {
            $timestamp = strtotime($dateStr);
            if ($timestamp !== false) {
                $date = date('Y-m-d', $timestamp);
            }
        }
    }
    
    $travelled = $entry['travelled'] ?? '';
    $eventActivity = $entry['event_activity'] ?? $entry['event'] ?? '';
    $entryId = isset($entry['entry_id']) ? (int)$entry['entry_id'] : null;
    
    if ($travel_id) {
        // Update existing entry
        $stmt = $db->prepare("
            UPDATE utilization_travels 
            SET travelled = :travelled,
                event_activity = :event_activity,
                date = :date,
                amount = :amount,
                entry_id = :entry_id
            WHERE id = :id
        ");
        
        $stmt->bindValue(':travelled', $travelled, PDO::PARAM_STR);
        $stmt->bindValue(':event_activity', $eventActivity, PDO::PARAM_STR);
        $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':entry_id', $entryId, $entryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $travel_id, PDO::PARAM_INT);
        
        $stmt->execute();
    } else {
        // Insert new entry
        $stmt = $db->prepare("
            INSERT INTO utilization_travels 
            (department_id, travelled, event_activity, date, amount, fiscal_year, created_by, entry_id)
            VALUES (:dept_id, :travelled, :event_activity, :date, :amount, :year, :user_id, :entry_id)
        ");
        
        $stmt->bindValue(':dept_id', $department_id, PDO::PARAM_INT);
        $stmt->bindValue(':travelled', $travelled, PDO::PARAM_STR);
        $stmt->bindValue(':event_activity', $eventActivity, PDO::PARAM_STR);
        $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':year', $fiscal_year, PDO::PARAM_STR);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':entry_id', $entryId, $entryId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        
        $stmt->execute();
        $travel_id = $db->lastInsertId();
    }
    
    echo json_encode([
        'success' => true,
        'travel_id' => $travel_id ?? null,
        'message' => 'Travel entry saved successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving travel entry: ' . $e->getMessage()]);
}

