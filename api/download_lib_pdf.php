<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

$libId = $_GET['id'] ?? null;

if (!$libId) {
    die('LIB ID is required');
}

try {
    $db = getDB();
    
    // Get LIB details
    $stmt = $db->prepare("
        SELECT l.*, d.dept_name, d.dept_code
        FROM line_item_budgets l
        LEFT JOIN departments d ON l.department_id = d.id
        WHERE l.id = ?
    ");
    $stmt->execute([$libId]);
    $lib = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lib) {
        die('LIB not found');
    }
    
    // Get LIB items
    $stmt = $db->prepare("
        SELECT * FROM line_item_budget_items 
        WHERE lib_id = ? 
        ORDER BY sort_order, id
    ");
    $stmt->execute([$libId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate LIB number
    $deptCode = $lib['dept_code'] ?: 'DEPT';
    $libNumber = $deptCode . '-LIB-' . $lib['fiscal_year'] . '-' . str_pad($lib['id'], 3, '0', STR_PAD_LEFT);
    
    // Calculate total
    $totalAmount = 0;
    foreach ($items as $item) {
        $totalAmount += floatval($item['amount']);
    }
    
    // Status labels
    $statusLabels = [
        'draft' => 'DRAFT',
        'pending_approval' => 'PENDING APPROVAL',
        'approved' => 'APPROVED',
        'rejected' => 'REJECTED'
    ];
    $statusLabel = $statusLabels[$lib['status']] ?? strtoupper($lib['status']);
    
    // Generate HTML for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Line Item Budget - <?php echo htmlspecialchars($lib['dept_name']); ?></title>
        <style>
            @page {
                size: portrait;
                margin: 1cm;
            }
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body { 
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                padding: 15px;
                font-size: 11px;
                color: #333;
            }
            .header {
                border-bottom: 4px solid #2563eb;
                padding-bottom: 15px;
                margin-bottom: 20px;
                text-align: center;
            }
            .header h1 { 
                color: #2563eb; 
                font-size: 24px;
                margin-bottom: 10px;
            }
            .header-info {
                display: grid;
                grid-template-columns: 1fr 1fr 1fr;
                gap: 20px;
                margin-top: 15px;
                font-size: 11px;
            }
            .info-item {
                text-align: center;
            }
            .info-label {
                color: #666;
                font-size: 9px;
                text-transform: uppercase;
                margin-bottom: 5px;
            }
            .info-value {
                font-weight: bold;
                font-size: 12px;
                color: #333;
            }
            .status-badge {
                display: inline-block;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: bold;
                margin-top: 10px;
            }
            .status-draft {
                background-color: #e5e7eb;
                color: #374151;
            }
            .status-pending_approval {
                background-color: #fef3c7;
                color: #92400e;
            }
            .status-approved {
                background-color: #d1fae5;
                color: #065f46;
            }
            .status-rejected {
                background-color: #fee2e2;
                color: #991b1b;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 10px 0;
                font-size: 10px;
            }
            th { 
                background-color: #2563eb; 
                color: white; 
                padding: 12px 10px;
                text-align: left;
                font-weight: 600;
                font-size: 10px;
            }
            td { 
                padding: 10px;
                border-bottom: 1px solid #e5e7eb;
            }
            tr:hover {
                background-color: #f9fafb;
            }
            .text-right {
                text-align: right;
            }
            .total-row {
                background-color: #2563eb;
                color: white;
                font-weight: bold;
            }
            .total-row td {
                padding: 12px 10px;
                border-bottom: none;
            }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 2px solid #e5e7eb;
                font-size: 8px;
                color: #666;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Line Item Budget (LIB)</h1>
            <div class="header-info">
                <div class="info-item">
                    <div class="info-label">Department/Office</div>
                    <div class="info-value"><?php echo htmlspecialchars($lib['dept_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fiscal Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($lib['fiscal_year']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">LIB Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($libNumber); ?></div>
                </div>
            </div>
            <div style="text-align: center;">
                <span class="status-badge status-<?php echo $lib['status']; ?>">
                    <?php echo $statusLabel; ?>
                </span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Particular</th>
                    <th style="width: 25%;">Account Code</th>
                    <th style="width: 25%;" class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['particulars']); ?></td>
                    <td><?php echo htmlspecialchars($item['account_code']); ?></td>
                    <td class="text-right">₱<?php echo number_format($item['amount'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="2" class="text-right">TOTAL:</td>
                    <td class="text-right">₱<?php echo number_format($totalAmount, 2); ?></td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            Generated on <?php echo date('F d, Y h:i A'); ?> | BudgetTrack System
        </div>

        <script>
            window.onload = function() {
                window.print();
            };
        </script>
    </body>
    </html>
    <?php
    
    $html = ob_get_clean();
    echo $html;
    
} catch (Exception $e) {
    error_log("Error in download_lib_pdf.php: " . $e->getMessage());
    die('Error generating PDF: ' . $e->getMessage());
}
?>
