<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

$ppmpId = $_GET['id'] ?? null;

if (!$ppmpId) {
    die('PPMP ID is required');
}

try {
    $db = getDB();
    
    // Get PPMP details
    $stmt = $db->prepare("
        SELECT p.*, d.dept_name, d.dept_code
        FROM ppmp p
        LEFT JOIN departments d ON p.department_id = d.id
        WHERE p.id = ?
    ");
    $stmt->execute([$ppmpId]);
    $ppmp = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$ppmp) {
        die('PPMP not found');
    }
    
    // Get PPMP items
    $stmt = $db->prepare("
        SELECT * FROM ppmp_items 
        WHERE ppmp_id = ? 
        ORDER BY sort_order, id
    ");
    $stmt->execute([$ppmpId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Determine if supplemental
    $isSupplemental = ($ppmp['ppmp_type'] === 'supplemental');
    $ppmpLabel = $isSupplemental ? 'Supplemental PPMP' : 'PPMP';
    $headerColor = $isSupplemental ? '#eab308' : '#800000'; // Yellow for supplemental, maroon for regular
    
    // Calculate totals
    $totalBudget = 0;
    $totalAllocated = 0;
    foreach ($items as $item) {
        $totalBudget += floatval($item['estimated_budget']);
        $totalAllocated += floatval($item['allocated_supporting_funds']);
    }
    
    // Generate HTML for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo $ppmpLabel; ?> - <?php echo htmlspecialchars($ppmp['dept_name']); ?></title>
        <style>
            @page {
                size: landscape;
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
                font-size: 10px;
                color: #333;
            }
            .header {
                border-bottom: 4px solid <?php echo $headerColor; ?>;
                padding-bottom: 15px;
                margin-bottom: 20px;
                text-align: center;
            }
            .header h1 { 
                color: <?php echo $headerColor; ?>; 
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
            .status-approved {
                background-color: #d1fae5;
                color: #065f46;
            }
            .supplemental-badge {
                background-color: #fef3c7;
                color: #92400e;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 10px;
                font-weight: bold;
                margin-left: 10px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 10px 0;
                font-size: 9px;
            }
            th { 
                background-color: <?php echo $headerColor; ?>; 
                color: white; 
                padding: 10px 8px;
                text-align: left;
                font-weight: 600;
                font-size: 9px;
            }
            td { 
                padding: 8px;
                border-bottom: 1px solid #e5e7eb;
            }
            tr:hover {
                background-color: #f9fafb;
            }
            .text-right {
                text-align: right;
            }
            .total-row {
                background-color: <?php echo $headerColor; ?>;
                color: white;
                font-weight: bold;
            }
            .total-row td {
                padding: 12px 8px;
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
            <h1>
                <?php echo $ppmpLabel; ?>
                <?php if ($isSupplemental): ?>
                    <span class="supplemental-badge">SUPPLEMENTAL</span>
                <?php endif; ?>
            </h1>
            <div class="header-info">
                <div class="info-item">
                    <div class="info-label">Department/Office</div>
                    <div class="info-value"><?php echo htmlspecialchars($ppmp['dept_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Fiscal Year</div>
                    <div class="info-value"><?php echo htmlspecialchars($ppmp['fiscal_year']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">PPMP Number</div>
                    <div class="info-value"><?php echo htmlspecialchars($ppmp['ppmp_number']); ?></div>
                </div>
            </div>
            <div style="text-align: center;">
                <span class="status-badge status-<?php echo $ppmp['status']; ?>">
                    <?php echo strtoupper($ppmp['status']); ?>
                </span>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 25%;">Description</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 8%;" class="text-right">Quantity</th>
                    <th style="width: 10%;">Unit</th>
                    <th style="width: 12%;">Mode</th>
                    <th style="width: 15%;" class="text-right">Estimated Budget</th>
                    <th style="width: 18%;" class="text-right">Allocated Funds</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['general_description']); ?></td>
                    <td><?php echo htmlspecialchars($item['project_type']); ?></td>
                    <td class="text-right"><?php echo number_format($item['quantity'], 0); ?></td>
                    <td><?php echo htmlspecialchars($item['unit']); ?></td>
                    <td><?php echo htmlspecialchars($item['recommended_mode']); ?></td>
                    <td class="text-right">₱<?php echo number_format($item['estimated_budget'], 2); ?></td>
                    <td class="text-right">₱<?php echo number_format($item['allocated_supporting_funds'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td colspan="5" class="text-right">TOTAL:</td>
                    <td class="text-right">₱<?php echo number_format($totalBudget, 2); ?></td>
                    <td class="text-right">₱<?php echo number_format($totalAllocated, 2); ?></td>
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
    error_log("Error in download_ppmp_pdf.php: " . $e->getMessage());
    die('Error generating PDF: ' . $e->getMessage());
}
?>
