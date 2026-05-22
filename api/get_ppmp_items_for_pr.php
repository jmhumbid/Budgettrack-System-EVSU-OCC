<?php
/**
 * Get PPMP Items for Purchase Request Selection
 * Returns PPMP items from FINAL PPMPs for a specific department
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

try {
    $db = getDB();
    
    // Get department ID and ppmp_type from request
    $departmentId = $_GET['department_id'] ?? null;
    $ppmpType = $_GET['ppmp_type'] ?? 'ppmp'; // Default to 'ppmp' if not specified
    
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }
    
    // Log the request for debugging
    error_log("get_ppmp_items_for_pr.php - Dept: $departmentId, Type: $ppmpType");
    
    // Get PPMP items from FINAL PPMPs only, filtered by ppmp_type
    // A PPMP is considered "final" if either is_final=1 OR status='approved'
    $query = "
        SELECT 
            pi.id,
            pi.ppmp_id,
            pi.general_description,
            pi.project_type as type,
            pi.quantity,
            pi.unit,
            pi.estimated_budget as amount,
            pi.deducted_amount,
            pi.expense_category,
            pi.deduction_remarks,
            p.ppmp_number,
            p.fiscal_year,
            p.is_final,
            p.status,
            COALESCE(p.ppmp_type, 'ppmp') as ppmp_type
        FROM ppmp_items pi
        INNER JOIN ppmp p ON pi.ppmp_id = p.id
        WHERE p.department_id = :department_id
            AND (p.is_final = 1 OR p.status = 'approved')
            AND (
                COALESCE(p.ppmp_type, 'ppmp') = :ppmp_type
            )
        ORDER BY p.fiscal_year DESC, p.created_at DESC, pi.sort_order ASC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':department_id', $departmentId, PDO::PARAM_INT);
    $stmt->bindParam(':ppmp_type', $ppmpType, PDO::PARAM_STR);
    $stmt->execute();
    
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the count
    error_log("get_ppmp_items_for_pr.php - Found " . count($items) . " items");
    
    // Format items for display
    $formattedItems = array_map(function($item) {
        return [
            'id' => $item['id'],
            'ppmp_id' => $item['ppmp_id'],
            'description' => $item['general_description'],
            'type' => $item['type'],
            'quantity' => $item['quantity'],
            'unit' => $item['unit'],
            'amount' => floatval($item['amount']),
            'deducted_amount' => floatval($item['deducted_amount'] ?? 0),
            'remaining_amount' => floatval($item['amount']) - floatval($item['deducted_amount'] ?? 0),
            'expense_category' => $item['expense_category'],
            'deduction_remarks' => $item['deduction_remarks'],
            'ppmp_number' => $item['ppmp_number'],
            'fiscal_year' => $item['fiscal_year'],
            'ppmp_type' => $item['ppmp_type'] ?? 'ppmp',
            // Format for display in PR: "Description, Type: X, Qty: Y, Unit: Z, Amount: A"
            'formatted' => sprintf(
                "%s, Type: %s, Qty: %s, Unit: %s, Amount: %s",
                $item['general_description'],
                $item['type'],
                $item['quantity'],
                $item['unit'],
                number_format($item['amount'], 2)
            )
        ];
    }, $items);
    
    echo json_encode([
        'success' => true,
        'items' => $formattedItems,
        'count' => count($formattedItems)
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching PPMP items: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
