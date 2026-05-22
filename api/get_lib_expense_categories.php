<?php
/**
 * Get LIB Expense Categories
 * Returns available expense categories from existing LIBs for PPMP-LIB linking
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDB();
    
    $departmentId = $_GET['department_id'] ?? null;
    $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
    
    if (!$departmentId) {
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        exit;
    }
    
    // Get distinct expense categories from existing LIB items for this department
    // This gives us the actual categories they've used before
    $query = "SELECT DISTINCT 
                lib_items.category,
                lib_items.particulars,
                lib_items.account_code
              FROM line_item_budget_items lib_items
              INNER JOIN line_item_budgets lib ON lib_items.lib_id = lib.id
              WHERE lib.department_id = ?
              AND lib.fiscal_year = ?
              ORDER BY 
                CASE 
                    WHEN lib_items.category LIKE 'A.%' THEN 1
                    WHEN lib_items.category LIKE 'B.%' THEN 2
                    WHEN lib_items.category LIKE 'C.%' THEN 3
                    ELSE 4
                END,
                lib_items.category,
                lib_items.particulars";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$departmentId, $fiscalYear]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // If no items found for this year, try to get from any year for this department
    if (empty($items)) {
        $query = "SELECT DISTINCT 
                    lib_items.category,
                    lib_items.particulars,
                    lib_items.account_code
                  FROM line_item_budget_items lib_items
                  INNER JOIN line_item_budgets lib ON lib_items.lib_id = lib.id
                  WHERE lib.department_id = ?
                  ORDER BY 
                    CASE 
                        WHEN lib_items.category LIKE 'A.%' THEN 1
                        WHEN lib_items.category LIKE 'B.%' THEN 2
                        WHEN lib_items.category LIKE 'C.%' THEN 3
                        ELSE 4
                    END,
                    lib_items.category,
                    lib_items.particulars";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$departmentId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Always provide comprehensive standard categories (merge with existing)
    $standardCategories = [
        // A. PERSONAL SERVICES
        [
            'category' => 'A. PERSONAL SERVICES',
            'particulars' => 'Salaries and Wages - Regular',
            'account_code' => '5010101000'
        ],
        [
            'category' => 'A. PERSONAL SERVICES',
            'particulars' => 'Salaries and Wages - Casual/Contractual',
            'account_code' => '5010102000'
        ],
        [
            'category' => 'A. PERSONAL SERVICES',
            'particulars' => 'Other Compensation',
            'account_code' => '5010201000'
        ],
        [
            'category' => 'A. PERSONAL SERVICES',
            'particulars' => 'Personnel Benefit Contributions',
            'account_code' => '5010301000'
        ],
        [
            'category' => 'A. PERSONAL SERVICES',
            'particulars' => 'Other Personnel Benefits',
            'account_code' => '5010400000'
        ],
        
        // B. MAINTENANCE & OTHER OPERATING EXPENSES
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Traveling Expenses - Local',
            'account_code' => '5020101000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Traveling Expenses - Foreign',
            'account_code' => '5020102000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Training Expenses',
            'account_code' => '5020201001'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Scholarship Grants/Expenses',
            'account_code' => '5020201002'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Office Supplies Expenses',
            'account_code' => '5020301000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Accountable Forms Expenses',
            'account_code' => '5020302000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Food Supplies Expenses',
            'account_code' => '5020303000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Drugs and Medicines Expenses',
            'account_code' => '5020304000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Medical, Dental and Laboratory Supplies Expenses',
            'account_code' => '5020305000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Fuel, Oil and Lubricants Expenses',
            'account_code' => '5020309000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Agricultural and Marine Supplies Expenses',
            'account_code' => '5020310000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Textbooks and Instructional Materials Expenses',
            'account_code' => '5020311000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Semi-Expendable Machinery and Equipment Expenses',
            'account_code' => '5020321000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Semi-Expendable Furniture, Fixtures and Books Expenses',
            'account_code' => '5020322000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Semi-Expendable Office Equipment',
            'account_code' => '5020321001'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Other Supplies and Materials Expenses',
            'account_code' => '5020399000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Water Expenses',
            'account_code' => '5020401000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Electricity Expenses',
            'account_code' => '5020402000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Gas/Heating Expenses',
            'account_code' => '5020403000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Postage and Courier Services',
            'account_code' => '5020501000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Telephone Expenses',
            'account_code' => '5020502000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Internet Subscription Expenses',
            'account_code' => '5020503000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Cable, Satellite, Telegraph and Radio Expenses',
            'account_code' => '5020504000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Awards/Rewards and Prizes',
            'account_code' => '5020601000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Rewards and Incentives',
            'account_code' => '5020602000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Survey, Research, Exploration and Development Expenses',
            'account_code' => '5020701000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Demolition and Relocation Expenses',
            'account_code' => '5020702000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Generation, Transmission and Distribution Expenses',
            'account_code' => '5020703000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Confidential, Intelligence and Extraordinary Expenses',
            'account_code' => '5021003000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Legal Services',
            'account_code' => '5021101000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Auditing Services',
            'account_code' => '5021102000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Consultancy Services',
            'account_code' => '5021103000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Other Professional Services',
            'account_code' => '5021199000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Janitorial Services',
            'account_code' => '5021202000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Security Services',
            'account_code' => '5021203000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Other General Services',
            'account_code' => '5021299000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Repairs and Maintenance - Buildings and Other Structures',
            'account_code' => '5021304000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Repairs and Maintenance - Machinery and Equipment',
            'account_code' => '5021305000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Repairs and Maintenance - Transportation Equipment',
            'account_code' => '5021306000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Repairs and Maintenance - Furniture and Fixtures',
            'account_code' => '5021307000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Advertising, Promotional and Marketing Expense',
            'account_code' => '5021401000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Printing and Publication Expenses',
            'account_code' => '5021402000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Representation Expenses',
            'account_code' => '5021403000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Transportation and Delivery Expenses',
            'account_code' => '5021404000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Rent/Lease Expenses',
            'account_code' => '5021501000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Subscription Expenses',
            'account_code' => '5021502000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Donations',
            'account_code' => '5021601000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Insurance Expenses',
            'account_code' => '5021602000'
        ],
        [
            'category' => 'B. Maintenance & Other Operating Expenses',
            'particulars' => 'Other Maintenance and Operating Expenses',
            'account_code' => '5029900000'
        ],
        
        // C. CAPITAL OUTLAY
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Land',
            'account_code' => '5060401000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Land Improvements',
            'account_code' => '5060402000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Buildings',
            'account_code' => '5060403000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Office Equipment',
            'account_code' => '5060404001'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'ICT Equipment',
            'account_code' => '5060404002'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Machinery and Equipment',
            'account_code' => '5060404000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Transportation Equipment',
            'account_code' => '5060405000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Furniture and Fixtures',
            'account_code' => '5060406000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Books',
            'account_code' => '5060407000'
        ],
        [
            'category' => 'C. Capital Outlay',
            'particulars' => 'Other Property, Plant and Equipment',
            'account_code' => '5060499000'
        ]
    ];
    
    // Merge existing items with standard categories (remove duplicates)
    $allItems = array_merge($items, $standardCategories);
    
    // Remove duplicates based on category + particulars + account_code
    $uniqueItems = [];
    $seen = [];
    foreach ($allItems as $item) {
        $key = $item['category'] . '|' . $item['particulars'] . '|' . $item['account_code'];
        if (!isset($seen[$key])) {
            $uniqueItems[] = $item;
            $seen[$key] = true;
        }
    }
    
    $items = $uniqueItems;
    
    // Group by category
    $categories = [];
    foreach ($items as $item) {
        $category = $item['category'];
        if (!isset($categories[$category])) {
            $categories[$category] = [];
        }
        
        // Check if this particular expense already exists in the category
        $exists = false;
        foreach ($categories[$category] as $existing) {
            if ($existing['name'] === $item['particulars'] && $existing['code'] === $item['account_code']) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $categories[$category][] = [
                'name' => $item['particulars'],
                'code' => $item['account_code']
            ];
        }
    }
    
    echo json_encode([
        'success' => true,
        'categories' => $categories
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_lib_expense_categories.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error loading expense categories: ' . $e->getMessage()
    ]);
}
?>
