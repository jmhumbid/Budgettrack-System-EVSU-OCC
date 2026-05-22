<?php
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_role'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$departmentId = $_GET['department_id'] ?? null;
$fiscalYear   = $_GET['fiscal_year']   ?? null;

if (!$departmentId) {
    exit('Department ID is required');
}

try {
    $db = getDB();

    // Get department name
    $deptStmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
    $deptStmt->execute([$departmentId]);
    $dept = $deptStmt->fetch(PDO::FETCH_ASSOC);
    $deptName = $dept ? $dept['dept_name'] : 'Unknown Department';

    // Fetch entries
    if ($fiscalYear) {
        $stmt = $db->prepare("SELECT * FROM prior_years_entries WHERE department_id = ? AND fiscal_year = ? ORDER BY id ASC");
        $stmt->execute([$departmentId, $fiscalYear]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $years = [$fiscalYear => $entries];
    } else {
        $stmt = $db->prepare("SELECT * FROM prior_years_entries WHERE department_id = ? ORDER BY fiscal_year DESC, id ASC");
        $stmt->execute([$departmentId]);
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $years = [];
        foreach ($entries as $entry) {
            $fy = $entry['fiscal_year'];
            if (!isset($years[$fy])) $years[$fy] = [];
            $years[$fy][] = $entry;
        }
    }

    if (empty($years) || array_sum(array_map('count', $years)) === 0) {
        exit('No prior years data found for this department.');
    }

    // Helper: format number
    function fmt($val) {
        return number_format(floatval($val ?? 0), 2);
    }

    $cols = [
        'student_development'   => 'Student Dev',
        'faculty_development'   => 'Faculty Dev',
        'curriculum_development'=> 'Curriculum Dev',
        'facilities_development'=> 'Facilities Dev',
        'development_fee'       => 'Dev Fee',
        'laboratory_fee'        => 'Lab Fee',
        'computer_fee'          => 'Computer Fee',
    ];

    $title = $fiscalYear
        ? "Prior Years Report – FY {$fiscalYear}"
        : "Prior Years Report – All Years";

    $generatedAt = date('F j, Y g:i A');

    // Build table sections per year
    $tablesHtml = '';
    foreach ($years as $fy => $fyEntries) {
        if (empty($fyEntries)) continue;

        // Compute totals
        $totals = array_fill_keys(array_keys($cols), 0);
        foreach ($fyEntries as $e) {
            foreach ($cols as $key => $label) {
                $totals[$key] += floatval($e[$key] ?? 0);
            }
        }
        $grandTotal = array_sum($totals);

        $rows = '';
        foreach ($fyEntries as $e) {
            $rowTotal = 0;
            $cells = '';
            foreach ($cols as $key => $label) {
                $v = floatval($e[$key] ?? 0);
                $rowTotal += $v;
                $cells .= "<td class='num'>" . fmt($v) . "</td>";
            }
            $rows .= "<tr>
                <td class='cat'>" . htmlspecialchars($e['expense_category'] ?? '-') . "</td>
                {$cells}
                <td class='num total-col'>" . fmt($rowTotal) . "</td>
            </tr>";
        }

        $totalCells = '';
        foreach ($cols as $key => $label) {
            $totalCells .= "<td class='num total-row'>" . fmt($totals[$key]) . "</td>";
        }

        $tablesHtml .= "
        <div class='year-block'>
            <div class='year-header'>
                <span>Fiscal Year {$fy}</span>
                <span class='year-grand'>Grand Total: ₱" . fmt($grandTotal) . "</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th class='th-cat'>Expense Category</th>";
        foreach ($cols as $key => $label) {
            $tablesHtml .= "<th class='th-num'>{$label}</th>";
        }
        $tablesHtml .= "
                        <th class='th-num'>Row Total</th>
                    </tr>
                </thead>
                <tbody>
                    {$rows}
                </tbody>
                <tfoot>
                    <tr>
                        <td class='total-row cat'>TOTAL</td>
                        {$totalCells}
                        <td class='num total-row'>" . fmt($grandTotal) . "</td>
                    </tr>
                </tfoot>
            </table>
        </div>";
    }

    // Output HTML with print-to-PDF styling
    header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?php echo htmlspecialchars($title); ?></title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        font-family: Arial, sans-serif;
        font-size: 10px;
        color: #1a1a1a;
        background: #fff;
        padding: 20px;
    }
    .report-header {
        text-align: center;
        margin-bottom: 18px;
        border-bottom: 2px solid #c0392b;
        padding-bottom: 12px;
    }
    .report-header .school-name {
        font-size: 13px;
        font-weight: bold;
        color: #7b1c1c;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .report-header .report-title {
        font-size: 15px;
        font-weight: bold;
        color: #1a1a1a;
        margin-top: 4px;
    }
    .report-header .dept-name {
        font-size: 11px;
        color: #555;
        margin-top: 3px;
    }
    .report-header .generated {
        font-size: 9px;
        color: #888;
        margin-top: 4px;
    }
    .year-block {
        margin-bottom: 22px;
        page-break-inside: avoid;
    }
    .year-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #ea580c;
        color: #fff;
        padding: 6px 10px;
        border-radius: 4px 4px 0 0;
        font-size: 11px;
        font-weight: bold;
    }
    .year-grand {
        font-size: 10px;
        font-weight: normal;
        opacity: 0.9;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 9px;
    }
    thead tr {
        background: #fff3e0;
    }
    th {
        padding: 5px 4px;
        border: 1px solid #f97316;
        font-weight: bold;
        color: #7c2d12;
        text-align: center;
    }
    .th-cat { text-align: left; min-width: 130px; }
    .th-num { text-align: right; min-width: 70px; }
    td {
        padding: 4px;
        border: 1px solid #e5e7eb;
        vertical-align: top;
    }
    .cat { text-align: left; }
    .num { text-align: right; }
    tbody tr:nth-child(even) { background: #fff7ed; }
    tbody tr:hover { background: #ffedd5; }
    tfoot tr {
        background: #fff3e0;
    }
    .total-row {
        font-weight: bold;
        color: #c2410c;
        border-top: 2px solid #f97316;
    }
    .total-col {
        font-weight: bold;
        color: #374151;
        background: #f9fafb;
    }
    .print-btn {
        position: fixed;
        top: 16px;
        right: 16px;
        background: #c0392b;
        color: #fff;
        border: none;
        padding: 10px 20px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: bold;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        z-index: 999;
    }
    .print-btn:hover { background: #a93226; }
    @media print {
        .print-btn { display: none; }
        body { padding: 10px; }
        .year-block { page-break-inside: avoid; }
    }
</style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Print / Save PDF</button>

<div class="report-header">
    <div class="school-name">Eastern Visayas State University – OCC</div>
    <div class="report-title"><?php echo htmlspecialchars($title); ?></div>
    <div class="dept-name"><?php echo htmlspecialchars($deptName); ?></div>
    <div class="generated">Generated: <?php echo $generatedAt; ?></div>
</div>

<?php echo $tablesHtml; ?>

<script>
    // Auto-trigger print dialog after page loads
    window.onload = function() {
        // Small delay to let styles render
        setTimeout(function() { window.print(); }, 400);
    };
</script>
</body>
</html>
<?php
} catch (Exception $e) {
    exit('Error generating report: ' . $e->getMessage());
}
?>
