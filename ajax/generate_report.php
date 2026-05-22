<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$allowedRoles = ['budget', 'school_admin'];
if (!in_array($_SESSION['user_role'] ?? '', $allowedRoles, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

require_once __DIR__ . '/../classes/ReportGenerator.php';

header('Content-Type: application/json');

$reportType = $_POST['report_type'] ?? null;
$periodStart = $_POST['period_start'] ?? null;
$periodEnd = $_POST['period_end'] ?? null;

if (!in_array($reportType, ['weekly', 'monthly', 'yearly'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid report type']);
    exit;
}

if (!$periodStart || !$periodEnd) {
    http_response_code(400);
    echo json_encode(['error' => 'Period dates required']);
    exit;
}

try {
    $reportGenerator = new ReportGenerator();
    $result = $reportGenerator->generateReport(
        $reportType,
        $periodStart,
        $periodEnd,
        $_SESSION['user_id']
    );
    
    echo json_encode([
        'success' => true,
        'report' => $result
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to generate report: ' . $e->getMessage()
    ]);
}

