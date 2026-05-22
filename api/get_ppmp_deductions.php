<?php
/**
 * Get PPMP Deductions
 * Returns all deductions for a specific PPMP or department
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
    
    $ppmpId = $_GET['ppmp_id'] ?? null;
    $departmentId = $_GET['department_id'] ?? null;
    $fiscalYear = $_GET['fiscal_year'] ?? null;
    
    if (!$ppmpId && !$departmentId) {
        echo json_encode(['success' => false, 'message' => 'PPMP ID or Department ID is required']);
        exit;
    }
    
    // Build query based on parameters
    $query = "
        SELECT 
            pd.id,
            pd.ppmp_id,
            pd.ppmp_item_id,
            pd.purchase_request_id,
            pd.expense_category,
            pd.amount,
            pd.fiscal_year,
            pd.created_at,
            pi.general_description as item_description,
            pi.project_type as item_type,
            pi.quantity as item_quantity,
            pi.unit as item_unit,
            pi.estimated_budget as item_budget,
            pr.purchase_request as pr_description,
            pr.particulars as pr_particulars,
            pr.pr_po_number,
            pr.date_of_obligation,
            p.ppmp_number,
            d.dept_name as department_name
        FROM ppmp_deductions pd
        INNER JOIN ppmp_items pi ON pd.ppmp_item_id = pi.id
        INNER JOIN ppmp p ON pd.ppmp_id = p.id
        INNER JOIN departments d ON pd.department_id = d.id
        LEFT JOIN purchase_requests pr ON pd.purchase_request_id = pr.id
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($ppmpId) {
        $query .= " AND pd.ppmp_id = :ppmp_id";
        $params[':ppmp_id'] = $ppmpId;
    }
    
    if ($departmentId) {
        $query .= " AND pd.department_id = :department_id";
        $params[':department_id'] = $departmentId;
    }
    
    if ($fiscalYear) {
        $query .= " AND pd.fiscal_year = :fiscal_year";
        $params[':fiscal_year'] = $fiscalYear;
    }
    
    $query .= " ORDER BY pd.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format deductions for display
    $formattedDeductions = array_map(function($deduction) {
        return [
            'id' => $deduction['id'],
            'ppmp_id' => $deduction['ppmp_id'],
            'ppmp_number' => $deduction['ppmp_number'],
            'ppmp_item_id' => $deduction['ppmp_item_id'],
            'purchase_request_id' => $deduction['purchase_request_id'],
            'expense_category' => $deduction['expense_category'],
            'amount' => floatval($deduction['amount']),
            'fiscal_year' => $deduction['fiscal_year'],
            'created_at' => $deduction['created_at'],
            'department_name' => $deduction['department_name'],
            // PPMP Item details
            'item_description' => $deduction['item_description'],
            'item_type' => $deduction['item_type'],
            'item_quantity' => $deduction['item_quantity'],
            'item_unit' => $deduction['item_unit'],
            'item_budget' => floatval($deduction['item_budget']),
            // Purchase Request details
            'pr_description' => $deduction['pr_description'],
            'pr_particulars' => $deduction['pr_particulars'],
            'pr_po_number' => $deduction['pr_po_number'],
            'date_of_obligation' => $deduction['date_of_obligation'],
            // Formatted display
            'formatted_object' => sprintf(
                "%s, Type: %s, Qty: %s, Unit: %s",
                $deduction['item_description'],
                $deduction['item_type'],
                $deduction['item_quantity'],
                $deduction['item_unit']
            ),
            'formatted_amount' => '₱' . number_format($deduction['amount'], 2),
            'formatted_date' => date('M j, Y', strtotime($deduction['created_at']))
        ];
    }, $deductions);
    
    // Calculate totals
    $totalAmount = array_sum(array_column($formattedDeductions, 'amount'));
    
    echo json_encode([
        'success' => true,
        'deductions' => $formattedDeductions,
        'count' => count($formattedDeductions),
        'total_amount' => $totalAmount,
        'formatted_total' => '₱' . number_format($totalAmount, 2)
    ]);
    
} catch (PDOException $e) {
    error_log("Error fetching PPMP deductions: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
