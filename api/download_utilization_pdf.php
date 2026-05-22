<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

$summaryId = $_GET['id'] ?? null;

if (!$summaryId) {
    die('Summary ID is required');
}

try {
    $db = getDB();
    
    // Get summary data
    $stmt = $db->prepare("
        SELECT us.*, d.dept_name as department_name
        FROM utilization_summaries us
        LEFT JOIN departments d ON us.department_id = d.id
        WHERE us.id = ?
    ");
    $stmt->execute([$summaryId]);
    $summary = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$summary) {
        die('Summary not found');
    }
    
    // Parse JSON data
    $utilizationEntries = json_decode($summary['utilization_entries'] ?? '[]', true) ?: [];
    $prEntries = json_decode($summary['pr_entries'] ?? '[]', true) ?: [];
    $travelsEntries = json_decode($summary['travels_entries'] ?? '[]', true) ?: [];
    $prDeductions = json_decode($summary['pr_deductions'] ?? '[]', true) ?: [];
    $travelsDeductions = json_decode($summary['travels_deductions'] ?? '[]', true) ?: [];
    
    // Calculate totals
    $totalAllocated = 0;
    $totalDeductions = 0;
    $totalBalance = 0;
    foreach ($utilizationEntries as $entry) {
        $totalAllocated += floatval($entry['allocated'] ?? 0);
        $totalDeductions += floatval($entry['deductions'] ?? 0);
        $totalBalance += floatval($entry['total'] ?? 0);
    }
    
    $prTotal = array_sum(array_column($prEntries, 'amount'));
    $travelsTotal = array_sum(array_column($travelsEntries, 'amount'));
    $prDeductionsTotal = array_sum(array_column($prDeductions, 'amount'));
    $travelsDeductionsTotal = array_sum(array_column($travelsDeductions, 'amount'));
    
    // Format number helper
    function formatNum($num) {
        return '₱' . number_format(floatval($num), 2);
    }
    
    // Generate HTML for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Budget Utilization Summary - <?php echo htmlspecialchars($summary['department_name'] ?? 'Department'); ?></title>
        <style>
            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                padding: 20px;
                font-size: 11px;
                color: #333;
            }
            .header {
                border-bottom: 4px solid #800000;
                padding-bottom: 15px;
                margin-bottom: 20px;
                text-align: center;
            }
            .header h1 { 
                color: #800000; 
                font-size: 22px;
                margin-bottom: 5px;
            }
            .header-info {
                font-size: 12px;
                color: #666;
            }
            .section {
                margin-bottom: 20px;
            }
            .section-title {
                background: #800000;
                color: white;
                padding: 8px 12px;
                font-size: 13px;
                font-weight: bold;
                margin-bottom: 10px;
            }
            .section-title.blue { background: #2563eb; }
            .section-title.green { background: #16a34a; }
            .section-title.purple { background: #7c3aed; }
            .section-title.indigo { background: #4f46e5; }
            .section-title.emerald { background: #059669; }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 10px;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 6px 8px;
                text-align: left;
                font-size: 10px;
            }
            th {
                background: #f5f5f5;
                font-weight: bold;
            }
            .text-right { text-align: right; }
            .total-row {
                background: #f0f0f0;
                font-weight: bold;
            }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 2px solid #800000;
                font-size: 10px;
                color: #666;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Budget Utilization Summary</h1>
            <div class="header-info">
                <strong><?php echo htmlspecialchars($summary['department_name'] ?? 'Department'); ?></strong><br>
                Fiscal Year: <?php echo htmlspecialchars($summary['fiscal_year'] ?? date('Y')); ?><br>
                Generated: <?php echo date('F j, Y g:i A'); ?>
            </div>
        </div>
        
        <!-- Utilization Entries -->
        <div class="section">
            <div class="section-title">Utilization Entries</div>
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
                            <td><?php echo htmlspecialchars($entry['category'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($entry['accountCode'] ?? $entry['account_code'] ?? '-'); ?></td>
                            <td class="text-right"><?php echo formatNum($entry['allocated'] ?? 0); ?></td>
                            <td class="text-right"><?php echo formatNum($entry['deductions'] ?? 0); ?></td>
                            <td class="text-right"><?php echo formatNum($entry['total'] ?? 0); ?></td>
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
                        <tr><td colspan="5" style="text-align:center;color:#999;">No entries</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Purchase Requests -->
        <div class="section">
            <div class="section-title blue">Purchase Requests</div>
            <table>
                <thead>
                    <tr>
                        <th>Description</th>
                        <th>PR Number</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($prEntries) > 0): ?>
                        <?php foreach ($prEntries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['purchaseRequest'] ?? $entry['purchase_request'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($entry['prNumber'] ?? $entry['pr_number'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($entry['date'] ?? '-'); ?></td>
                            <td class="text-right"><?php echo formatNum($entry['amount'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3">TOTAL</td>
                            <td class="text-right"><?php echo formatNum($prTotal); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;color:#999;">No entries</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Travels -->
        <div class="section">
            <div class="section-title green">Travels</div>
            <table>
                <thead>
                    <tr>
                        <th>Travelled</th>
                        <th>Event/Activity</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($travelsEntries) > 0): ?>
                        <?php foreach ($travelsEntries as $entry): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($entry['travelled'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($entry['event'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($entry['date'] ?? '-'); ?></td>
                            <td class="text-right"><?php echo formatNum($entry['amount'] ?? 0); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="3">TOTAL</td>
                            <td class="text-right"><?php echo formatNum($travelsTotal); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="4" style="text-align:center;color:#999;">No entries</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Purchase Request Deductions -->
        <div class="section">
            <div class="section-title indigo">Purchase Request Deductions</div>
            <table>
                <thead>
                    <tr>
                        <th>Expense Category</th>
                        <th>Purchase Request</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($prDeductions) > 0): ?>
                        <?php foreach ($prDeductions as $entry): ?>
                            <?php if (isset($entry['items']) && is_array($entry['items']) && count($entry['items']) > 0): ?>
                                <?php foreach ($entry['items'] as $index => $item): ?>
                                    <tr>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo count($entry['items']); ?>" style="font-weight: bold; vertical-align: top;">
                                                <?php echo htmlspecialchars($entry['category'] ?? '-'); ?>
                                            </td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($item['purchaseRequest'] ?? '-'); ?></td>
                                        <?php if ($index === 0): ?>
                                            <td rowspan="<?php echo count($entry['items']); ?>" class="text-right" style="font-weight: bold; vertical-align: top;">
                                                <?php echo formatNum($entry['amount'] ?? 0); ?>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['category'] ?? '-'); ?></td>
                                    <td>-</td>
                                    <td class="text-right"><?php echo formatNum($entry['amount'] ?? 0); ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="2">TOTAL</td>
                            <td class="text-right"><?php echo formatNum($prDeductionsTotal); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;color:#999;">No purchase request deductions</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Travels Deductions -->
        <div class="section">
            <div class="section-title emerald">Travels Deductions</div>
            <table>
                <thead>
                    <tr>
                        <th>Expense Category</th>
                        <th>Travelled</th>
                        <th>Event/Activity</th>
                        <th>Date</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($travelsDeductions) > 0): ?>
                        <?php foreach ($travelsDeductions as $entry): ?>
                            <?php
                            $items = isset($entry['items']) && count($entry['items']) > 0
                                ? $entry['items']
                                : [['travelled' => '-', 'event' => '-', 'date' => '-', 'amount' => $entry['amount'] ?? 0]];
                            $rowspan = count($items);
                            ?>
                            <?php foreach ($items as $idx => $item): ?>
                            <tr>
                                <?php if ($idx === 0): ?>
                                <td rowspan="<?php echo $rowspan; ?>"><?php echo htmlspecialchars($entry['category'] ?? '-'); ?></td>
                                <?php endif; ?>
                                <td><?php echo htmlspecialchars($item['travelled'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['event'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($item['date'] ?? '-'); ?></td>
                                <td class="text-right"><?php echo formatNum($item['amount'] ?? 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                        <tr class="total-row">
                            <td colspan="4">TOTAL</td>
                            <td class="text-right"><?php echo formatNum($travelsDeductionsTotal); ?></td>
                        </tr>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center;color:#999;">No travels deductions</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        
        <div class="footer">
            BudgetTrack System - Generated on <?php echo date('F j, Y g:i A'); ?>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    
    // Output as HTML for printing (user can use browser's print to PDF)
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    
} catch (PDOException $e) {
    error_log('PDF Generation Error: ' . $e->getMessage());
    die('Error generating PDF: ' . $e->getMessage());
}
?>
