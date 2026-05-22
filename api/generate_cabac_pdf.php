<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

$programId = $_GET['program_id'] ?? null;
$downloadAll = $_GET['download'] ?? null;
$type = $_GET['type'] ?? null;

// Handle bulk download
if ($downloadAll === 'all' && $type) {
    try {
        $conn = getDB();
        
        // Get all programs of this type
        $programsStmt = $conn->prepare("SELECT id, program_name, type FROM cabac_programs WHERE type = ? ORDER BY program_name ASC");
        $programsStmt->execute([$type]);
        $programs = $programsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($programs)) {
            die('No programs found for this type');
        }
        
        // Generate HTML for PDF
        ob_start();
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>CABAC - All <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $type))); ?> Programs</title>
            <style>
                @page {
                    size: landscape;
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
                    font-size: 10px;
                    color: #333;
                }
                .header {
                    border-bottom: 4px solid #800000;
                    padding-bottom: 15px;
                    margin-bottom: 25px;
                }
                .header h1 { 
                    color: #800000; 
                    font-size: 24px;
                    margin-bottom: 5px;
                }
                .header h2 {
                    color: #333;
                    font-size: 16px;
                    font-weight: normal;
                    margin-top: 5px;
                }
                .program-section {
                    margin-bottom: 40px;
                    page-break-inside: avoid;
                }
                .program-title {
                    background: linear-gradient(to right, #800000, #a00000);
                    color: white;
                    padding: 10px 15px;
                    border-radius: 8px;
                    font-size: 14px;
                    font-weight: bold;
                    margin-bottom: 15px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin: 10px 0;
                    font-size: 9px;
                }
                th, td { 
                    border: 1px solid #ddd; 
                    padding: 8px 10px; 
                    text-align: left;
                }
                th { 
                    background: linear-gradient(to bottom, #800000, #a00000);
                    color: white; 
                    font-weight: bold;
                    font-size: 9px;
                    text-transform: uppercase;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .text-right { text-align: right; }
                .totals-row {
                    background: linear-gradient(to bottom, #f0f0f0, #e8e8e8) !important;
                    font-weight: bold;
                }
                .totals-row td {
                    border-top: 3px solid #800000;
                    padding: 10px;
                }
                .footer {
                    margin-top: 30px;
                    padding-top: 15px;
                    border-top: 2px solid #ddd;
                    text-align: center;
                    font-size: 8px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>CABAC Report - All <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $type))); ?> Programs</h1>
                <h2>Comparative Approved Budget and Actual Collection</h2>
                <div style="margin-top: 10px; font-size: 10px; color: #666;">
                    <strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?>
                </div>
            </div>
            
            <?php foreach ($programs as $program): ?>
                <?php
                // Get entries for this program
                $entriesStmt = $conn->prepare("
                    SELECT program_name, approved_budget, available_allotment, balance 
                    FROM cabac_program_entries 
                    WHERE program_id = ? 
                    ORDER BY id ASC
                ");
                $entriesStmt->execute([$program['id']]);
                $entries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Calculate totals
                $totalApproved = 0;
                $totalAllotment = 0;
                $totalBalance = 0;
                
                foreach ($entries as $entry) {
                    $totalApproved += floatval($entry['approved_budget'] ?? 0);
                    $totalAllotment += floatval($entry['available_allotment'] ?? 0);
                    $totalBalance += floatval($entry['balance'] ?? 0);
                }
                ?>
                
                <div class="program-section">
                    <div class="program-title"><?php echo htmlspecialchars($program['program_name']); ?></div>
                    
                    <?php if (empty($entries)): ?>
                        <p style="text-align: center; padding: 20px; color: #666; font-style: italic;">No entries found for this program.</p>
                    <?php else: ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 40%;">Entry</th>
                                    <th class="text-right" style="width: 20%;">Approved Budget</th>
                                    <th class="text-right" style="width: 20%;">Available Allotment</th>
                                    <th class="text-right" style="width: 20%;">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entries as $entry): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($entry['program_name'] ?? '-'); ?></td>
                                    <td class="text-right">₱<?php echo number_format(floatval($entry['approved_budget'] ?? 0), 2); ?></td>
                                    <td class="text-right">₱<?php echo number_format(floatval($entry['available_allotment'] ?? 0), 2); ?></td>
                                    <td class="text-right">₱<?php echo number_format(floatval($entry['balance'] ?? 0), 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <tr class="totals-row">
                                    <td><strong>TOTAL</strong></td>
                                    <td class="text-right">₱<?php echo number_format($totalApproved, 2); ?></td>
                                    <td class="text-right">₱<?php echo number_format($totalAllotment, 2); ?></td>
                                    <td class="text-right">₱<?php echo number_format($totalBalance, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
            
            <div class="footer">
                <p>Eastern Visayas State University - Budget Tracking System</p>
                <p>This document was automatically generated. For official records, please verify with the Budget Office.</p>
            </div>
        </body>
        </html>
        <?php
        $html = ob_get_clean();
        
        // Output as HTML for browser printing
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
        
    } catch (PDOException $e) {
        die('Database error: ' . $e->getMessage());
    }
}

// Original single program download code
if (!$programId) {
    die('Program ID is required');
}

try {
    $conn = getDB();
    
    // Get program info
    $programStmt = $conn->prepare("SELECT id, program_name, type FROM cabac_programs WHERE id = ?");
    $programStmt->execute([$programId]);
    $program = $programStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$program) {
        die('Program not found');
    }
    
    // Get entries for this program
    $entriesStmt = $conn->prepare("
        SELECT program_name, approved_budget, available_allotment, balance 
        FROM cabac_program_entries 
        WHERE program_id = ? 
        ORDER BY id ASC
    ");
    $entriesStmt->execute([$programId]);
    $entries = $entriesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalApproved = 0;
    $totalAllotment = 0;
    $totalBalance = 0;
    
    foreach ($entries as $entry) {
        $totalApproved += floatval($entry['approved_budget'] ?? 0);
        $totalAllotment += floatval($entry['available_allotment'] ?? 0);
        $totalBalance += floatval($entry['balance'] ?? 0);
    }
    
    // Generate HTML for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>CABAC - <?php echo htmlspecialchars($program['program_name']); ?></title>
        <style>
            @page {
                size: portrait;
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
                margin-bottom: 25px;
            }
            .header h1 { 
                color: #800000; 
                font-size: 22px;
                margin-bottom: 5px;
            }
            .header h2 {
                color: #333;
                font-size: 16px;
                font-weight: normal;
                margin-top: 5px;
            }
            .header-info {
                display: flex;
                justify-content: space-between;
                margin-top: 15px;
                font-size: 10px;
                color: #666;
            }
            .program-badge {
                display: inline-block;
                background: linear-gradient(to right, #800000, #a00000);
                color: white;
                padding: 5px 15px;
                border-radius: 20px;
                font-size: 11px;
                font-weight: bold;
                text-transform: uppercase;
                margin-top: 10px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 20px 0;
                font-size: 10px;
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 10px 12px; 
                text-align: left;
            }
            th { 
                background: linear-gradient(to bottom, #800000, #a00000);
                color: white; 
                font-weight: bold;
                font-size: 10px;
                text-transform: uppercase;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            tr:hover {
                background-color: #f5f5f5;
            }
            .text-right { text-align: right; }
            .entry-name {
                font-weight: bold;
                color: #333;
            }
            .amount-approved {
                color: #1d4ed8;
                font-weight: 600;
            }
            .amount-allotment {
                color: #166534;
                font-weight: 600;
            }
            .amount-balance {
                color: #dc2626;
                font-weight: 600;
            }
            .totals-row {
                background: linear-gradient(to bottom, #f0f0f0, #e8e8e8) !important;
                font-weight: bold;
                font-size: 11px;
            }
            .totals-row td {
                border-top: 3px solid #800000;
                padding: 12px;
            }
            .summary-cards {
                display: flex;
                justify-content: space-between;
                margin-top: 25px;
                gap: 15px;
            }
            .summary-card {
                flex: 1;
                border: 2px solid #ddd;
                border-radius: 8px;
                padding: 15px;
                text-align: center;
            }
            .summary-card.approved {
                border-color: #1d4ed8;
                background: linear-gradient(to bottom, #eff6ff, #dbeafe);
            }
            .summary-card.allotment {
                border-color: #166534;
                background: linear-gradient(to bottom, #f0fdf4, #dcfce7);
            }
            .summary-card.balance {
                border-color: #dc2626;
                background: linear-gradient(to bottom, #fef2f2, #fee2e2);
            }
            .summary-card .label {
                font-size: 9px;
                text-transform: uppercase;
                font-weight: bold;
                color: #666;
                margin-bottom: 5px;
            }
            .summary-card .value {
                font-size: 16px;
                font-weight: bold;
            }
            .summary-card.approved .value { color: #1d4ed8; }
            .summary-card.allotment .value { color: #166534; }
            .summary-card.balance .value { color: #dc2626; }
            .footer {
                margin-top: 30px;
                padding-top: 15px;
                border-top: 2px solid #ddd;
                text-align: center;
                font-size: 9px;
                color: #666;
            }
            .no-entries {
                text-align: center;
                padding: 40px;
                color: #666;
                font-style: italic;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>CABAC Report</h1>
            <h2>Comparative Approved Budget and Actual Collection</h2>
            <div class="program-badge"><?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $program['type']))); ?></div>
            <div class="header-info">
                <div>
                    <strong>Program:</strong> <?php echo htmlspecialchars($program['program_name']); ?>
                </div>
                <div>
                    <strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?>
                </div>
            </div>
        </div>
        
        <?php if (empty($entries)): ?>
        <div class="no-entries">
            <p>No entries found for this program.</p>
        </div>
        <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th style="width: 40%;">Entry</th>
                    <th class="text-right" style="width: 20%;">Approved Budget</th>
                    <th class="text-right" style="width: 20%;">Available Allotment</th>
                    <th class="text-right" style="width: 20%;">Balance</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($entries as $entry): ?>
                <tr>
                    <td class="entry-name"><?php echo htmlspecialchars($entry['program_name'] ?? '-'); ?></td>
                    <td class="text-right amount-approved">₱<?php echo number_format(floatval($entry['approved_budget'] ?? 0), 2); ?></td>
                    <td class="text-right amount-allotment">₱<?php echo number_format(floatval($entry['available_allotment'] ?? 0), 2); ?></td>
                    <td class="text-right amount-balance">₱<?php echo number_format(floatval($entry['balance'] ?? 0), 2); ?></td>
                </tr>
                <?php endforeach; ?>
                <tr class="totals-row">
                    <td><strong>TOTAL</strong></td>
                    <td class="text-right amount-approved">₱<?php echo number_format($totalApproved, 2); ?></td>
                    <td class="text-right amount-allotment">₱<?php echo number_format($totalAllotment, 2); ?></td>
                    <td class="text-right amount-balance">₱<?php echo number_format($totalBalance, 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <div class="summary-cards">
            <div class="summary-card approved">
                <div class="label">Total Approved Budget</div>
                <div class="value">₱<?php echo number_format($totalApproved, 2); ?></div>
            </div>
            <div class="summary-card allotment">
                <div class="label">Total Available Allotment</div>
                <div class="value">₱<?php echo number_format($totalAllotment, 2); ?></div>
            </div>
            <div class="summary-card balance">
                <div class="label">Total Balance</div>
                <div class="value">₱<?php echo number_format($totalBalance, 2); ?></div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="footer">
            <p>Eastern Visayas State University - Budget Tracking System</p>
            <p>This document was automatically generated. For official records, please verify with the Budget Office.</p>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    
    // Output as HTML for browser printing
    header('Content-Type: text/html; charset=utf-8');
    echo $html;
    
} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
?>
