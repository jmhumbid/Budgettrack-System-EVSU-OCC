<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

// Get department IDs from POST request
$requestData = json_decode(file_get_contents('php://input'), true);
$departmentIds = $requestData['department_ids'] ?? [];
$fiscalYear = $requestData['fiscal_year'] ?? date('Y');

if (empty($departmentIds)) {
    die('No departments selected');
}

try {
    $db = getDB();
    
    // Format number helper
    function formatNum($num) {
        return '₱' . number_format(floatval($num), 2);
    }
    
    // Start HTML output
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Budget Utilization Summary</title>
        <style>
            @page {
                size: A4 portrait;
                margin: 0.8cm;
            }
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body { 
                font-family: Arial, sans-serif; 
                font-size: 9px;
                color: #333;
                line-height: 1.1;
            }
            .main-header {
                text-align: center;
                margin-bottom: 8px;
                padding-bottom: 4px;
                border-bottom: 2px solid #800000;
            }
            .main-header h1 { 
                color: #800000; 
                font-size: 14px;
                margin-bottom: 1px;
                font-weight: bold;
            }
            .main-header-info {
                font-size: 8px;
                color: #555;
                line-height: 1.2;
            }
            .department-section {
                page-break-inside: avoid;
                margin-bottom: 12px;
            }
            .header {
                background: #800000;
                color: white;
                padding: 4px 8px;
                margin-bottom: 2px;
            }
            .header h2 { 
                font-size: 11px;
                margin-bottom: 1px;
                font-weight: bold;
            }
            .header-info {
                font-size: 7px;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 0;
            }
            th, td {
                border: 1px solid #999;
                padding: 3px 6px;
                text-align: left;
                font-size: 8px;
            }
            th {
                background: #f5f5f5;
                font-weight: bold;
                color: #333;
            }
            .text-right { 
                text-align: right; 
            }
            .total-row {
                background: #e8e8e8;
                font-weight: bold;
                border-top: 1.5px solid #666;
            }
            .no-data {
                text-align: center;
                color: #999;
                font-style: italic;
                padding: 8px;
                background: #f9f9f9;
                font-size: 8px;
            }
            .footer {
                margin-top: 8px;
                padding-top: 4px;
                border-top: 1px solid #800000;
                font-size: 7px;
                color: #666;
                text-align: center;
                page-break-inside: avoid;
            }
            @media print {
                body {
                    padding: 0;
                    margin: 0;
                }
                .department-section {
                    page-break-inside: avoid;
                }
                .main-header {
                    page-break-after: avoid;
                }
                .header {
                    page-break-after: avoid;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                table {
                    page-break-inside: auto;
                }
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
                thead {
                    display: table-header-group;
                }
                .footer {
                    page-break-inside: avoid;
                }
                .total-row, th {
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
            }
        </style>
    </head>
    <body>
        <div class="main-header">
            <h1>Budget Utilization Summary</h1>
            <div class="main-header-info">
                Fiscal Year: <?php echo htmlspecialchars($fiscalYear); ?><br>
                Generated: <?php echo date('F j, Y g:i A'); ?><br>
                Total Departments/Offices: <?php echo count($departmentIds); ?>
            </div>
        </div>
        
        <?php
        // Check if account_code column exists in budget_utilization_entries
        $hasAccountCode = false;
        $hasLibId = false;
        try {
            $colCheck = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'account_code'");
            $hasAccountCode = $colCheck->rowCount() > 0;
            $colCheck2 = $db->query("SHOW COLUMNS FROM budget_utilization_entries LIKE 'lib_id'");
            $hasLibId = $colCheck2->rowCount() > 0;
        } catch (Exception $e) {}

        // Loop through each department
        foreach ($departmentIds as $index => $deptId):
            // Get department info
            $stmt = $db->prepare("SELECT dept_name, dept_code FROM departments WHERE id = ?");
            $stmt->execute([$deptId]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$department) {
                continue;
            }
            
            // Get utilization entries, falling back to LIB account_code when entry's own is empty
            if ($hasAccountCode && $hasLibId) {
                $entriesStmt = $db->prepare("
                    SELECT 
                        bue.expense_category,
                        COALESCE(
                            NULLIF(TRIM(bue.account_code), ''),
                            libi.account_code
                        ) AS account_code,
                        bue.allocated_budget,
                        bue.deductions,
                        bue.total_balance
                    FROM budget_utilization_entries bue
                    LEFT JOIN line_item_budget_items libi
                        ON bue.lib_id IS NOT NULL
                        AND libi.lib_id = bue.lib_id
                        AND LOWER(TRIM(libi.particulars)) = LOWER(TRIM(bue.expense_category))
                    WHERE bue.department_id = ? AND bue.fiscal_year = ?
                    ORDER BY bue.id ASC
                ");
            } elseif ($hasAccountCode) {
                $entriesStmt = $db->prepare("
                    SELECT expense_category, account_code, allocated_budget, deductions, total_balance
                    FROM budget_utilization_entries
                    WHERE department_id = ? AND fiscal_year = ?
                    ORDER BY id ASC
                ");
            } else {
                $entriesStmt = $db->prepare("
                    SELECT expense_category, '' AS account_code, allocated_budget, deductions, total_balance
                    FROM budget_utilization_entries
                    WHERE department_id = ? AND fiscal_year = ?
                    ORDER BY id ASC
                ");
            }
            $entriesStmt->execute([$deptId, $fiscalYear]);
            $utilizationEntries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);

            // Get last updated timestamp from utilization_summaries for display
            $summaryStmt = $db->prepare("SELECT created_at FROM utilization_summaries WHERE department_id = ? AND fiscal_year = ? ORDER BY created_at DESC LIMIT 1");
            $summaryStmt->execute([$deptId, $fiscalYear]);
            $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

            // Calculate totals
            $totalAllocated = 0;
            $totalDeductions = 0;
            $totalBalance = 0;
            foreach ($utilizationEntries as $entry) {
                $totalAllocated  += floatval($entry['allocated_budget'] ?? 0);
                $totalDeductions += floatval($entry['deductions'] ?? 0);
                $totalBalance    += floatval($entry['total_balance'] ?? 0);
            }
            ?>
            <div class="department-section">
                <div class="header">
                    <h2><?php echo htmlspecialchars($department['dept_name']); ?></h2>
                    <div class="header-info">
                        Department Code: <?php echo htmlspecialchars($department['dept_code'] ?? 'N/A'); ?>
                         | Fiscal Year: <?php echo htmlspecialchars($fiscalYear); ?>
                        <?php if ($summary): ?>
                         | Last Updated: <?php echo date('M j, Y', strtotime($summary['created_at'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Utilization Entries Only -->
                <table>
                    <thead>
                        <tr>
                            <th>Expense Category</th>
                            <th>Account Code</th>
                            <th class="text-right">Allocated Budget</th>
                            <th class="text-right">Deductions</th>
                            <th class="text-right">Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($utilizationEntries) > 0): ?>
                            <?php foreach ($utilizationEntries as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['expense_category'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($entry['account_code'] ?? '-'); ?></td>
                                <td class="text-right"><?php echo formatNum($entry['allocated_budget'] ?? 0); ?></td>
                                <td class="text-right"><?php echo formatNum($entry['deductions'] ?? 0); ?></td>
                                <td class="text-right"><?php echo formatNum($entry['total_balance'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td>TOTAL</td>
                                <td></td>
                                <td class="text-right"><?php echo formatNum($totalAllocated); ?></td>
                                <td class="text-right"><?php echo formatNum($totalDeductions); ?></td>
                                <td class="text-right"><?php echo formatNum($totalBalance); ?></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="5" class="no-data">No utilization entries</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
        
        <div class="footer">
            BudgetTrack System - Distributed Report Generated on <?php echo date('F j, Y g:i A'); ?>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    
    // Output as HTML for printing (user can use browser's print to PDF)
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    
} catch (PDOException $e) {
    error_log('Combined PDF Generation Error: ' . $e->getMessage());
    die('Error generating combined PDF: ' . $e->getMessage());
}
?>
