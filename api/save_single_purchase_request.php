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
$pr_id = isset($data['pr_id']) ? (int)$data['pr_id'] : null; // For updates

try {
    $db = getDB();
    
    // Parse amount - remove currency symbols and commas
    $amountStr = $entry['amount'] ?? '0';
    $amountStr = preg_replace('/[₱,\s]/', '', $amountStr);
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
    
    // Handle both camelCase and snake_case field names
    $purchaseRequest = $entry['purchaseRequest'] ?? $entry['purchase_request'] ?? '';
    $particulars = $entry['particulars'] ?? '';
    
    // Handle PPMP references
    $ppmpItemId = isset($entry['ppmp_item_id']) ? (int)$entry['ppmp_item_id'] : null;
    $ppmpId = isset($entry['ppmp_id']) ? (int)$entry['ppmp_id'] : null;
    $ppmpDescription = $entry['ppmp_description'] ?? null;
    
    if ($pr_id) {
        // Update existing entry
        $updateSql = "
            UPDATE utilization_purchase_requests 
            SET purchase_request = :pr,
                particulars = :particulars,
                pr_number = :pr_number,
                po_number = :po_number,
                date = :date,
                amount = :amount,
                ppmp_item_id = :ppmp_item_id,
                ppmp_id = :ppmp_id,
                ppmp_description = :ppmp_description
            WHERE id = :id
        ";
        
        $stmt = $db->prepare($updateSql);
        $stmt->bindValue(':pr', $purchaseRequest, PDO::PARAM_STR);
        $stmt->bindValue(':particulars', $particulars, PDO::PARAM_STR);
        $stmt->bindValue(':pr_number', $prNumber, PDO::PARAM_STR);
        $stmt->bindValue(':po_number', $poNumber, PDO::PARAM_STR);
        $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':ppmp_item_id', $ppmpItemId, $ppmpItemId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ppmp_id', $ppmpId, $ppmpId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ppmp_description', $ppmpDescription, $ppmpDescription ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':id', $pr_id, PDO::PARAM_INT);
        $stmt->execute();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Purchase request updated successfully', 
            'pr_id' => $pr_id
        ]);
    } else {
        // Insert new entry
        $insertSql = "
            INSERT INTO utilization_purchase_requests 
            (department_id, purchase_request, particulars, pr_number, po_number, date, amount, fiscal_year, created_by,
             ppmp_item_id, ppmp_id, ppmp_description)
            VALUES (:dept_id, :pr, :particulars, :pr_number, :po_number, :date, :amount, :year, :user_id,
                    :ppmp_item_id, :ppmp_id, :ppmp_description)
        ";
        
        $stmt = $db->prepare($insertSql);
        $stmt->bindValue(':dept_id', $department_id, PDO::PARAM_INT);
        $stmt->bindValue(':pr', $purchaseRequest, PDO::PARAM_STR);
        $stmt->bindValue(':particulars', $particulars, PDO::PARAM_STR);
        $stmt->bindValue(':pr_number', $prNumber, PDO::PARAM_STR);
        $stmt->bindValue(':po_number', $poNumber, PDO::PARAM_STR);
        $stmt->bindValue(':date', $date, $date ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->bindValue(':amount', $amount, PDO::PARAM_STR);
        $stmt->bindValue(':year', $fiscal_year, PDO::PARAM_INT);
        $stmt->bindValue(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindValue(':ppmp_item_id', $ppmpItemId, $ppmpItemId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ppmp_id', $ppmpId, $ppmpId ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt->bindValue(':ppmp_description', $ppmpDescription, $ppmpDescription ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $stmt->execute();
        
        $pr_id = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Purchase request saved successfully', 
            'pr_id' => $pr_id
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error saving purchase request: ' . $e->getMessage()]);
}

