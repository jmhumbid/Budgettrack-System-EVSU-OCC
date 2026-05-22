<?php
// Ensure session is started with proper configuration
if (session_status() === PHP_SESSION_NONE) {
    // Configure session cookie to be accessible across the site
    ini_set('session.cookie_path', '/');
    session_start();
}

header('Content-Type: application/json');

// Check if session exists and has user data
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized: Session expired or not found. Please refresh the page and try again.'
    ]);
    exit;
}

if ($_SESSION['user_role'] !== 'procurement') {
    http_response_code(403);
    echo json_encode([
        'success' => false, 
        'message' => 'Unauthorized: Only procurement role can submit PRs. Your role: ' . htmlspecialchars($_SESSION['user_role'])
    ]);
    exit;
}

require_once __DIR__ . '/../classes/PurchaseRequest.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Department.php';

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$departmentId = isset($_POST['department_id']) ? (int)$_POST['department_id'] : 0;
$fiscalYear = isset($_POST['fiscal_year']) ? (int)$_POST['fiscal_year'] : (int)date('Y');
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if (!$departmentId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Department is required']);
    exit;
}

// Validate department exists
$dept = new Department();
$deptInfo = $dept->getDepartmentById($departmentId);
if (!$deptInfo) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid department']);
    exit;
}

try {
    $pr = new PurchaseRequest();
    $notification = new Notification();
    
    // Create PR record
    $prId = $pr->createPR($userId, $departmentId, $fiscalYear, $notes);
    
    if (!$prId) {
        throw new Exception('Failed to create purchase request');
    }
    
    // Handle file uploads
    $uploadDir = __DIR__ . '/../uploads/pr/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $uploadedFiles = [];
    $errors = [];
    
    if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
        $fileCount = count($_FILES['files']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                $originalName = $_FILES['files']['name'][$i];
                $tmpName = $_FILES['files']['tmp_name'][$i];
                $fileSize = $_FILES['files']['size'][$i];
                $fileType = $_FILES['files']['type'][$i];
                
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                $uniqueName = 'PR_' . $prId . '_' . time() . '_' . $i . '_' . bin2hex(random_bytes(4));
                if ($ext) {
                    $uniqueName .= '.' . $ext;
                }
                
                $filePath = $uploadDir . $uniqueName;
                $relativePath = 'uploads/pr/' . $uniqueName;
                
                if (move_uploaded_file($tmpName, $filePath)) {
                    // Add file to database
                    if ($pr->addFile($prId, $originalName, $relativePath, $fileSize, $fileType)) {
                        $uploadedFiles[] = $originalName;
                    } else {
                        $errors[] = "Failed to save file: $originalName";
                    }
                } else {
                    $errors[] = "Failed to upload file: $originalName";
                }
            } else {
                $errors[] = "Error uploading file: " . $_FILES['files']['name'][$i];
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        // Delete PR if no files uploaded
        $pr->updateStatus($prId, 'pending');
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No files were uploaded']);
        exit;
    }
    
    // Mark as processing
    $pr->markAsProcessing($prId);
    
    // Get PR details
    $prDetails = $pr->getPRById($prId);
    
    // Notify department users
    require_once __DIR__ . '/../config/database.php';
    $db = getDB();
    $deptUsersQuery = "SELECT id FROM users WHERE department_id = :dept_id AND is_active = 1";
    $deptUsersStmt = $db->prepare($deptUsersQuery);
    $deptUsersStmt->bindParam(':dept_id', $departmentId);
    $deptUsersStmt->execute();
    $deptUserIds = $deptUsersStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $title = 'New Purchase Request';
    $message = "A new Purchase Request (PR #{$prDetails['pr_number']}) has been submitted for {$deptInfo['dept_name']}. Your purchase request is being processed, please wait for the delivery. You will get informed right away.";
    
    foreach ($deptUserIds as $deptUserId) {
        $notification->createNotification($deptUserId, $title, $message, 'info');
    }
    
    // Notify supply office
    $supplyUsers = $notification->getUserIdsByRoles(['supply_office']);
    $supplyMessage = "A new Purchase Request (PR #{$prDetails['pr_number']}) has been submitted for {$deptInfo['dept_name']}. Please check your Purchase Orders page.";
    foreach ($supplyUsers as $supplyUserId) {
        $notification->createNotification($supplyUserId, $title, $supplyMessage, 'info');
    }
    
    // Notify budget office when PR status changes to PROCESSING
    $budgetUsers = $notification->getUserIdsByRoles(['budget', 'school_admin']);
    $budgetTitle = 'Purchase Request Status Updated';
    $budgetMessage = "Purchase Request (PR #{$prDetails['pr_number']}) for {$deptInfo['dept_name']} has been marked as PROCESSING.";
    foreach ($budgetUsers as $budgetUserId) {
        $notification->createNotification($budgetUserId, $budgetTitle, $budgetMessage, 'info');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Purchase Request submitted successfully',
        'pr_id' => $prId,
        'pr_number' => $prDetails['pr_number'],
        'uploaded_files' => $uploadedFiles,
        'errors' => $errors
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

