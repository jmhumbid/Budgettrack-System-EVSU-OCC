<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['fiscal_year']) || !isset($data['rows'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;

// Create a CSV file from the rows and save into uploads/ppmp
$uploadDir = __DIR__ . '/../uploads/ppmp/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
$fileName = 'PPMP_' . $userId . '_' . time() . '.csv';
$filePath = $uploadDir . $fileName;

$fp = fopen($filePath, 'w');
if ($fp === false) { http_response_code(500); echo json_encode(['success'=>false,'message'=>'Cannot write file']); exit; }
fputcsv($fp, ['Item/Project','Unit','Qty','Unit Cost','Total','Semester','Remarks']);
foreach ($data['rows'] as $r) {
    fputcsv($fp, [
        $r['item'] ?? '',
        $r['unit'] ?? '',
        $r['qty'] ?? 0,
        $r['unit_cost'] ?? 0,
        $r['total'] ?? 0,
        ($r['semester'] ?? $r['quarter'] ?? ''),
        $r['remarks'] ?? ''
    ]);
}
fclose($fp);

// Record as submission
$submission = new FileSubmission();

// Check if this is an update (if there's an existing PPMP submission before creating new one)
$existingPpmp = $submission->getLatestSubmissionByType($userId, 'PPMP');
$isUpdate = (bool)$existingPpmp;

$submissionId = $submission->submitFile($userId, $departmentId, 'PPMP', (int)$data['fiscal_year'], $fileName, $filePath, filesize($filePath), 'text/csv');

if ($submissionId) {
    // Send notification to budget/admin users
    require_once __DIR__ . '/../classes/Notification.php';
    require_once __DIR__ . '/../classes/Department.php';
    
    $notif = new Notification();
    
    // Get department name
    $deptName = 'Unknown Department';
    if ($departmentId) {
        $dept = new Department();
        $deptInfo = $dept->getDepartmentById($departmentId);
        $deptName = $deptInfo ? $deptInfo['dept_name'] : 'Unknown Department';
    } elseif (isset($_SESSION['department_name'])) {
        $deptName = $_SESSION['department_name'];
    }
    
    // Notify budget/admin users
    $notif->notifyBudgetAdmins('PPMP', $userId, $deptName, $isUpdate);
    
    echo json_encode(['success'=>true, 'submission_id'=>$submissionId]);
} else {
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Failed to create submission']);
}
?>


