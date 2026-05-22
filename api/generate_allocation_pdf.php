<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    die('Unauthorized');
}

require_once __DIR__ . '/../config/database.php';

$allocationId = $_GET['id'] ?? null;

if (!$allocationId) {
    die('Allocation ID is required');
}

try {
    $conn = getDB();
    
    $stmt = $conn->prepare("
        SELECT 
            ba.*,
            d.dept_name as department_name,
            d.fiduciary_type
        FROM budget_allocations ba
        LEFT JOIN departments d ON ba.department_id = d.id
        WHERE ba.id = ?
    ");
    
    $stmt->execute([$allocationId]);
    $allocation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$allocation) {
        die('Allocation not found');
    }
    
    // Parse allocation data
    $allocData = is_string($allocation['allocation_data']) 
        ? json_decode($allocation['allocation_data'], true) 
        : $allocation['allocation_data'];
    
    // Determine if this is an office
    $isOffice = ($allocation['fiduciary_type'] === 'Fiduciary');
    
    // Generate HTML for PDF
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Budget Allocation - <?php echo htmlspecialchars($allocation['department_name']); ?></title>
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
                border-bottom: 4px solid #800000;
                padding-bottom: 15px;
                margin-bottom: 20px;
            }
            .header h1 { 
                color: #800000; 
                font-size: 24px;
                margin-bottom: 5px;
            }
            .header-info {
                display: flex;
                justify-content: space-between;
                margin-top: 10px;
                font-size: 9px;
                color: #666;
            }
            h2 { 
                color: #800000; 
                margin-top: 20px;
                margin-bottom: 10px;
                font-size: 14px;
                border-bottom: 2px solid #800000;
                padding-bottom: 5px;
            }
            table { 
                width: 100%; 
                border-collapse: collapse; 
                margin: 10px 0;
                font-size: 9px;
            }
            th, td { 
                border: 1px solid #ddd; 
                padding: 6px 8px; 
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
            .summary { 
                background: linear-gradient(to bottom, #f8f8f8, #f0f0f0);
                padding: 12px; 
                border: 2px solid #800000;
                border-radius: 5px; 
                margin: 15px 0;
            }
            .summary h2 {
                margin-top: 0;
            }
            .total { 
                font-size: 12px; 
                font-weight: bold; 
                color: #800000; 
            }
            .negative { 
                color: #d32f2f; 
                font-weight: bold; 
            }
            .footer {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 2px solid #ddd;
                text-align: center;
                font-size: 8px;
                color: #666;
            }
            .section-title {
                background-color: #800000;
                color: white;
                padding: 8px;
                font-weight: bold;
                margin-top: 15px;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Budget Allocation Report</h1>
            <div class="header-info">
                <div>
                    <strong>Department/Office:</strong> <?php echo htmlspecialchars($allocation['department_name']); ?><br>
                    <strong>Fiscal Year:</strong> <?php echo htmlspecialchars($allocation['fiscal_year']); ?>
                </div>
                <div>
                    <strong>Generated:</strong> <?php echo date('F j, Y g:i A'); ?><br>
                    <strong>Created:</strong> <?php echo date('F j, Y', strtotime($allocation['created_at'])); ?>
                </div>
            </div>
        </div>
        
        <div class="summary">
            <h2>Summary Information</h2>
            <?php if (!$isOffice): ?>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 25%;"><strong>Total Tuition Fee:</strong></td>
                    <td style="width: 25%;">₱<?php echo number_format(floatval($allocation['total_tuition_fee'] ?? 0), 2); ?></td>
                    <td style="width: 25%;"><strong>50% Instructional:</strong></td>
                    <td style="width: 25%;">₱<?php echo number_format(floatval($allocation['instructional_amount'] ?? 0), 2); ?></td>
                </tr>
                <tr>
                    <?php 
                    $additionalAmount = floatval($allocation['additional_amount'] ?? 0);
                    $overallTotal = floatval($allocation['overall_total'] ?? 0);
                    if ($additionalAmount > 0): 
                        $totalAmountBeforeAdditional = $overallTotal - $additionalAmount;
                    ?>
                    <td><strong>Total Amount:</strong></td>
                    <td class="total">₱<?php echo number_format($totalAmountBeforeAdditional, 2); ?></td>
                    <td><strong>Additional Amount:</strong></td>
                    <td style="color: #d97706; font-weight: bold;">
                        ₱<?php echo number_format($additionalAmount, 2); ?>
                        <?php if (!empty($allocation['additional_description'])): ?>
                            <span style="color: #666; font-weight: normal; font-size: 9px;"> - <?php echo htmlspecialchars($allocation['additional_description']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <td><strong>Overall Total:</strong></td>
                    <td colspan="3" class="total">₱<?php echo number_format($overallTotal, 2); ?></td>
                </tr>
                <?php else: ?>
                <tr>
                    <td><strong>Overall Total:</strong></td>
                    <td colspan="3" class="total">₱<?php echo number_format($overallTotal, 2); ?></td>
                </tr>
                <?php endif; ?>
            </table>
            <?php else: ?>
            <table style="width: 100%;">
                <tr>
                    <td style="width: 50%;"><strong>Budget Allocated:</strong></td>
                    <td style="width: 50%;">₱<?php echo number_format(floatval($allocation['budget_allocated'] ?? 0), 2); ?></td>
                </tr>
                <?php 
                $additionalAmount = floatval($allocation['additional_amount'] ?? 0);
                $overallTotal = floatval($allocation['overall_total'] ?? 0);
                if ($additionalAmount > 0): 
                    $totalAmountBeforeAdditional = $overallTotal - $additionalAmount;
                ?>
                <tr>
                    <td><strong>Total Amount:</strong></td>
                    <td class="total">₱<?php echo number_format($totalAmountBeforeAdditional, 2); ?></td>
                </tr>
                <tr>
                    <td><strong>Additional Amount:</strong></td>
                    <td style="color: #d97706; font-weight: bold;">
                        ₱<?php echo number_format($additionalAmount, 2); ?>
                        <?php if (!empty($allocation['additional_description'])): ?>
                            <span style="color: #666; font-weight: normal; font-size: 9px;"> - <?php echo htmlspecialchars($allocation['additional_description']); ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr>
                    <td><strong>Overall Total:</strong></td>
                    <td class="total">₱<?php echo number_format($overallTotal, 2); ?></td>
                </tr>
            </table>
            <?php endif; ?>
        </div>
        
        <?php if (!$isOffice && $allocData && isset($allocData['non_fiduciary'])): ?>
        <h2>Non-Fiduciary Fund</h2>
        <table>
            <thead>
                <tr>
                    <th>Instructional</th>
                    <th class="text-right">Percent</th>
                    <th class="text-right">50%</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Budget Allocation</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $categories = [
                    'facultyStaff' => 'Faculty and Staff Development',
                    'curriculum' => 'Curriculum Development',
                    'student' => 'Student Development',
                    'facilities' => 'Facilities Development'
                ];
                $nonFiduciaryTotal = 0;
                $nonFiduciaryTotalPercent = 0;
                $nonFiduciaryTotal50 = 0;
                $nonFiduciaryTotalDeduction = 0;
                foreach ($categories as $key => $name): 
                    if (isset($allocData['non_fiduciary'][$key])):
                        $item = $allocData['non_fiduciary'][$key];
                        $deductions = $item['deductions'] ?? [];
                        $deductionTotal = 0;
                        $deductionList = '';
                        foreach ($deductions as $ded) {
                            $amount = floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                            $deductionTotal += $amount;
                            $deductionList .= ($deductionList ? '<br>' : '') . ($ded['amount'] ?? '₱0.00');
                            if (!empty($ded['remarks'])) {
                                $deductionList .= ' (' . htmlspecialchars($ded['remarks']) . ')';
                            }
                        }
                        $budgetAlloc = floatval(str_replace(['₱', ','], '', $item['budget_allocation'] ?? '0'));
                        $percent = floatval(str_replace('%', '', $item['percent'] ?? '0'));
                        $fiftyPercent = floatval(str_replace(['₱', ','], '', $item['instructional'] ?? '0'));
                        $nonFiduciaryTotal += $budgetAlloc;
                        $nonFiduciaryTotalPercent += $percent;
                        $nonFiduciaryTotal50 += $fiftyPercent;
                        $nonFiduciaryTotalDeduction += $deductionTotal;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($name); ?></strong></td>
                    <td class="text-right"><?php echo htmlspecialchars($item['percent'] ?? '0%'); ?></td>
                    <td class="text-right"><?php echo htmlspecialchars($item['instructional'] ?? '₱0.00'); ?></td>
                    <td class="text-right">
                        <?php if ($deductionList): ?>
                            <?php echo $deductionList; ?>
                            <?php if ($deductionTotal > 0): ?>
                                <br><strong>Total: ₱<?php echo number_format($deductionTotal, 2); ?></strong>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-right <?php echo $budgetAlloc < 0 ? 'negative' : ''; ?>">
                        <strong><?php echo htmlspecialchars($item['budget_allocation'] ?? '₱0.00'); ?></strong>
                    </td>
                </tr>
                <?php 
                    endif;
                endforeach; 
                ?>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td><strong>Total</strong></td>
                    <td class="text-right total"><?php echo number_format($nonFiduciaryTotalPercent, 2); ?>%</td>
                    <td class="text-right total">₱<?php echo number_format($nonFiduciaryTotal50, 2); ?></td>
                    <td class="text-right total">₱<?php echo number_format($nonFiduciaryTotalDeduction, 2); ?></td>
                    <td class="text-right total">₱<?php echo number_format($nonFiduciaryTotal, 2); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
        
        <?php if ($allocData && isset($allocData['fiduciary'])): ?>
        <h2>Fiduciary Fund</h2>
        <table>
            <thead>
                <tr>
                    <th>Fiduciary</th>
                    <th class="text-right">Budget Collected</th>
                    <th class="text-right">Deductions</th>
                    <th class="text-right">Total Budget</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $fiduciaryTotal = 0;
                $fiduciaryTotal50 = 0;
                $fiduciaryTotalDeduction = 0;
                
                if ($isOffice) {
                    // Office format: single row with Budget Allocated
                    $fiduciary = $allocData['fiduciary'];
                    $deductions = $fiduciary['deductions'] ?? [];
                    $allocatedBudget = floatval($allocation['budget_allocated'] ?? 0);
                    
                    $deductionTotal = 0;
                    $deductionList = '';
                    foreach ($deductions as $ded) {
                        $amount = floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                        $deductionTotal += $amount;
                        $deductionList .= ($deductionList ? '<br>' : '') . ($ded['amount'] ?? '₱0.00');
                        if (!empty($ded['remarks'])) {
                            $deductionList .= ' (' . htmlspecialchars($ded['remarks']) . ')';
                        }
                    }
                    
                    $totalBudget = floatval(str_replace(['₱', ','], '', $fiduciary['total_budget'] ?? '0'));
                    $fiduciaryTotal = $totalBudget;
                    $fiduciaryTotal50 = $allocatedBudget;
                    $fiduciaryTotalDeduction = $deductionTotal;
                ?>
                <tr>
                    <td><strong>Budget Allocated</strong></td>
                    <td class="text-right">₱<?php echo number_format($allocatedBudget, 2); ?></td>
                    <td class="text-right">
                        <?php if ($deductionList): ?>
                            <?php echo $deductionList; ?>
                            <?php if ($deductionTotal > 0): ?>
                                <br><strong>Total: ₱<?php echo number_format($deductionTotal, 2); ?></strong>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-right <?php echo $totalBudget < 0 ? 'negative' : ''; ?>">
                        <strong>₱<?php echo number_format($totalBudget, 2); ?></strong>
                    </td>
                </tr>
                <?php 
                } else {
                    // Department format: multiple items
                    foreach ($allocData['fiduciary'] as $key => $item): 
                        if (!empty($item['item_name']) || !empty($item['instructional']) || !empty($item['total_budget'])):
                            $deductions = $item['deductions'] ?? [];
                            $deductionTotal = 0;
                            $deductionList = '';
                            foreach ($deductions as $ded) {
                                $amount = floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                                $deductionTotal += $amount;
                                $deductionList .= ($deductionList ? '<br>' : '') . ($ded['amount'] ?? '₱0.00');
                                if (!empty($ded['remarks'])) {
                                    $deductionList .= ' (' . htmlspecialchars($ded['remarks']) . ')';
                                }
                            }
                            $budgetCollected = floatval(str_replace(['₱', ','], '', $item['instructional'] ?? '0'));
                            $totalBudget = floatval(str_replace(['₱', ','], '', $item['total_budget'] ?? $item['budget_allocation'] ?? '0'));
                            $fiduciaryTotal += $totalBudget;
                            $fiduciaryTotal50 += $budgetCollected;
                            $fiduciaryTotalDeduction += $deductionTotal;
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($item['item_name'] ?? 'Item ' . $key); ?></strong></td>
                    <td class="text-right"><?php echo htmlspecialchars($item['instructional'] ?? '₱0.00'); ?></td>
                    <td class="text-right">
                        <?php if ($deductionList): ?>
                            <?php echo $deductionList; ?>
                            <?php if ($deductionTotal > 0): ?>
                                <br><strong>Total: ₱<?php echo number_format($deductionTotal, 2); ?></strong>
                            <?php endif; ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-right <?php echo $totalBudget < 0 ? 'negative' : ''; ?>">
                        <strong>₱<?php echo number_format($totalBudget, 2); ?></strong>
                    </td>
                </tr>
                <?php 
                        endif;
                    endforeach; 
                }
                ?>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td><strong>Total</strong></td>
                    <td class="text-right total">₱<?php echo number_format($fiduciaryTotal50, 2); ?></td>
                    <td class="text-right total">₱<?php echo number_format($fiduciaryTotalDeduction, 2); ?></td>
                    <td class="text-right total">₱<?php echo number_format($fiduciaryTotal, 2); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
        
        <?php
        // Calculate deduction breakdown by type
        $deductionBreakdown = [
            'COS' => 0,
            'Honoraria Overload' => 0,
            'Part-time' => 0,
            'Water' => 0,
            'Electricity' => 0,
            'Security' => 0
        ];
        
        // Collect deductions from non-fiduciary categories (for departments only)
        if (!$isOffice && $allocData && isset($allocData['non_fiduciary'])) {
            foreach ($allocData['non_fiduciary'] as $key => $item) {
                $deductions = $item['deductions'] ?? [];
                foreach ($deductions as $ded) {
                    $amount = floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                    $remarks = isset($ded['remarks']) ? trim($ded['remarks']) : '';
                    if ($amount > 0 && $remarks && isset($deductionBreakdown[$remarks])) {
                        $deductionBreakdown[$remarks] += $amount;
                    }
                }
            }
        }
        
        // Collect deductions from fiduciary items
        if ($allocData && isset($allocData['fiduciary'])) {
            if ($isOffice) {
                // For offices: get deductions from fiduciary object
                $fiduciary = $allocData['fiduciary'];
                $deductions = $fiduciary['deductions'] ?? [];
                foreach ($deductions as $ded) {
                    $amount = floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                    $remarks = isset($ded['remarks']) ? trim($ded['remarks']) : '';
                    if ($amount > 0 && $remarks && isset($deductionBreakdown[$remarks])) {
                        $deductionBreakdown[$remarks] += $amount;
                    }
                }
            } else {
                // For departments: get deductions from all fiduciary items
                foreach ($allocData['fiduciary'] as $key => $item) {
                    if (!empty($item['item_name']) || !empty($item['instructional']) || !empty($item['total_budget'])) {
                        $deductions = $item['deductions'] ?? [];
                        foreach ($deductions as $ded) {
                            $amount = floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                            $remarks = isset($ded['remarks']) ? trim($ded['remarks']) : '';
                            if ($amount > 0 && $remarks && isset($deductionBreakdown[$remarks])) {
                                $deductionBreakdown[$remarks] += $amount;
                            }
                        }
                    }
                }
            }
        }
        
        // Check if there are any deductions
        $hasAnyDeductions = false;
        $grandTotalDeductions = 0;
        foreach ($deductionBreakdown as $amount) {
            if ($amount > 0) {
                $hasAnyDeductions = true;
                $grandTotalDeductions += $amount;
            }
        }
        
        if ($hasAnyDeductions):
        ?>
        <h2>Total Deduction Breakdown by Type</h2>
        <table>
            <thead>
                <tr>
                    <th>Deduction Type</th>
                    <th class="text-right">Total Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $deductionTypes = [
                    ['key' => 'COS', 'label' => 'COS'],
                    ['key' => 'Honoraria Overload', 'label' => 'Overload'],
                    ['key' => 'Part-time', 'label' => 'Part-time'],
                    ['key' => 'Water', 'label' => 'Water'],
                    ['key' => 'Electricity', 'label' => 'Electricity'],
                    ['key' => 'Security', 'label' => 'Security']
                ];
                
                foreach ($deductionTypes as $type):
                    $amount = $deductionBreakdown[$type['key']] ?? 0;
                    if ($amount > 0):
                ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($type['label']); ?></strong></td>
                    <td class="text-right"><strong>₱<?php echo number_format($amount, 2); ?></strong></td>
                </tr>
                <?php
                    endif;
                endforeach;
                ?>
                <tr style="background-color: #f0f0f0; font-weight: bold;">
                    <td><strong>Grand Total Deductions</strong></td>
                    <td class="text-right total">₱<?php echo number_format($grandTotalDeductions, 2); ?></td>
                </tr>
            </tbody>
        </table>
        <?php endif; ?>
        
        <div class="summary" style="margin-top: 20px;">
            <h2 style="margin-top: 0;">Overall Summary</h2>
            <p style="font-size: 16px; font-weight: bold; color: #800000; text-align: center; padding: 10px;">
                Overall Total Budget Allocation: ₱<?php echo number_format(floatval($allocation['overall_total'] ?? 0), 2); ?>
            </p>
        </div>
        
        <div class="footer">
            <p>This document was generated on <?php echo date('F j, Y g:i A'); ?> | Budget Allocation System</p>
        </div>
    </body>
    </html>
    <?php
    $html = ob_get_clean();
    
    // Output HTML for PDF generation
    header('Content-Type: text/html; charset=UTF-8');
    echo $html;
    echo '<script>
        window.onload = function() {
            // Set up print settings for landscape
            const style = document.createElement("style");
            style.textContent = "@media print { @page { size: landscape; margin: 1cm; } }";
            document.head.appendChild(style);
            
            // Trigger print dialog
            setTimeout(function() {
                window.print();
            }, 250);
        };
    </script>';
    
} catch (Exception $e) {
    die('Error: ' . $e->getMessage());
}

