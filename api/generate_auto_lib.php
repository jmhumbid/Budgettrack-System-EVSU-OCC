<?php
require_once '../config/database.php';
require_once '../classes/ActivityLog.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$department_id = $_POST['department_id'] ?? null;
$year = $_POST['year'] ?? date('Y');

if (!$department_id) {
    echo json_encode(['success' => false, 'message' => 'Department ID required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Get allocation data for this department and year
    $query = "SELECT 
                a.id as allocation_id,
                a.allocation_data,
                a.fiscal_year,
                d.dept_name
              FROM budget_allocations a
              JOIN departments d ON a.department_id = d.id
              WHERE a.department_id = :department_id 
              AND a.fiscal_year = :year
              AND a.status = 'active'
              ORDER BY a.created_at DESC
              LIMIT 1";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':department_id', $department_id);
    $stmt->bindParam(':year', $year);
    $stmt->execute();
    
    $allocation_record = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$allocation_record) {
        echo json_encode([
            'success' => false, 
            'message' => 'No allocations found for this department and year'
        ]);
        exit;
    }
    
    // Parse the allocation_data JSON
    $allocation_data = json_decode($allocation_record['allocation_data'], true);
    
    if (empty($allocation_data)) {
        echo json_encode([
            'success' => false, 
            'message' => 'No allocation items found in the allocation data'
        ]);
        exit;
    }
    
    // Extract LIB items from the allocation structure
    $lib_items = [];
    
    // Process non_fiduciary deductions
    if (isset($allocation_data['non_fiduciary']) && is_array($allocation_data['non_fiduciary'])) {
        foreach ($allocation_data['non_fiduciary'] as $category => $data) {
            if (isset($data['deductions']) && is_array($data['deductions'])) {
                foreach ($data['deductions'] as $deduction) {
                    // Clean amount (remove ₱ and commas)
                    $amount_str = $deduction['amount'] ?? '0';
                    $amount_clean = preg_replace('/[₱,]/', '', $amount_str);
                    $amount = floatval($amount_clean);
                    
                    if ($amount > 0) {
                        $remark = $deduction['remarks'] ?? 'Unnamed Item';
                        
                        $lib_items[] = [
                            'allocation_id' => $allocation_record['allocation_id'],
                            'uacs_code' => '',
                            'general_desc' => $remark,
                            'total_amount' => $amount,
                            'quarter_1' => $amount / 4,
                            'quarter_2' => $amount / 4,
                            'quarter_3' => $amount / 4,
                            'quarter_4' => $amount / 4,
                            'source' => 'allocation',
                            'category' => ucfirst(str_replace('_', ' ', $category)),
                            'is_custom' => false
                        ];
                    }
                }
            }
        }
    }
    
    // Process fiduciary items
    if (isset($allocation_data['fiduciary']) && is_array($allocation_data['fiduciary'])) {
        foreach ($allocation_data['fiduciary'] as $item) {
            if (isset($item['deductions']) && is_array($item['deductions'])) {
                foreach ($item['deductions'] as $deduction) {
                    // Clean amount (remove ₱ and commas)
                    $amount_str = $deduction['amount'] ?? '0';
                    $amount_clean = preg_replace('/[₱,]/', '', $amount_str);
                    $amount = floatval($amount_clean);
                    
                    if ($amount > 0) {
                        $remark = $deduction['remarks'] ?? 'Unnamed Item';
                        
                        $lib_items[] = [
                            'allocation_id' => $allocation_record['allocation_id'],
                            'uacs_code' => '',
                            'general_desc' => $remark,
                            'total_amount' => $amount,
                            'quarter_1' => $amount / 4,
                            'quarter_2' => $amount / 4,
                            'quarter_3' => $amount / 4,
                            'quarter_4' => $amount / 4,
                            'source' => 'allocation',
                            'category' => $item['item_name'] ?? 'Fiduciary',
                            'is_custom' => false
                        ];
                    }
                }
            }
        }
    }
    
    // Get any existing custom items for this department/year
    $custom_query = "SELECT * FROM lib_custom_items 
                     WHERE department_id = :department_id 
                     AND year = :year 
                     AND deleted_at IS NULL
                     ORDER BY created_at";
    
    $custom_stmt = $db->prepare($custom_query);
    $custom_stmt->bindParam(':department_id', $department_id);
    $custom_stmt->bindParam(':year', $year);
    $custom_stmt->execute();
    
    $custom_items = $custom_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($custom_items as $item) {
        $lib_items[] = [
            'custom_item_id' => $item['id'],
            'uacs_code' => $item['uacs_code'],
            'general_desc' => $item['general_desc'],
            'total_amount' => floatval($item['total_amount']),
            'quarter_1' => floatval($item['quarter_1']),
            'quarter_2' => floatval($item['quarter_2']),
            'quarter_3' => floatval($item['quarter_3']),
            'quarter_4' => floatval($item['quarter_4']),
            'source' => 'custom',
            'is_custom' => true
        ];
    }
    
    // Get PPMP items that are linked to LIB expense categories
    $ppmp_query = "SELECT 
                    pi.lib_category,
                    pi.lib_particulars,
                    pi.lib_account_code,
                    SUM(pi.estimated_budget) as total_amount
                   FROM ppmp_items pi
                   INNER JOIN ppmp p ON pi.ppmp_id = p.id
                   WHERE p.department_id = :department_id
                   AND p.fiscal_year = :year
                   AND pi.lib_category IS NOT NULL
                   AND pi.lib_category != ''
                   AND pi.lib_particulars IS NOT NULL
                   AND pi.lib_particulars != ''
                   AND pi.lib_account_code IS NOT NULL
                   AND pi.lib_account_code != ''
                   GROUP BY pi.lib_category, pi.lib_particulars, pi.lib_account_code
                   ORDER BY pi.lib_category, pi.lib_particulars";
    
    $ppmp_stmt = $db->prepare($ppmp_query);
    $ppmp_stmt->bindParam(':department_id', $department_id);
    $ppmp_stmt->bindParam(':year', $year);
    $ppmp_stmt->execute();
    
    $ppmp_items = $ppmp_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($ppmp_items as $item) {
        $total_amount = floatval($item['total_amount']);
        
        $lib_items[] = [
            'uacs_code' => $item['lib_account_code'],
            'general_desc' => $item['lib_particulars'],
            'total_amount' => $total_amount,
            'quarter_1' => $total_amount / 4,
            'quarter_2' => $total_amount / 4,
            'quarter_3' => $total_amount / 4,
            'quarter_4' => $total_amount / 4,
            'source' => 'ppmp',
            'category' => $item['lib_category'],
            'is_custom' => false
        ];
    }
    
    echo json_encode([
        'success' => true,
        'items' => $lib_items,
        'department_name' => $allocation_record['dept_name'] ?? '',
        'year' => $year
    ]);
    
} catch (Exception $e) {
    error_log("Auto LIB Generation Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error generating auto LIB: ' . $e->getMessage()
    ]);
}
