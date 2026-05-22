<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/PurchaseRequest.php';
require_once __DIR__ . '/../classes/Notification.php';

$prId = isset($_POST['pr_id']) ? (int)$_POST['pr_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$userRole = $_SESSION['user_role'] ?? '';

if (!$prId || !$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
    exit;
}

try {
    $pr = new PurchaseRequest();
    $notification = new Notification();
    
    // Get PR details
    $prDetails = $pr->getPRById($prId);
    if (!$prDetails) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Purchase Request not found']);
        exit;
    }
    
    $success = false;
    $message = '';
    
    switch ($action) {
        case 'delivered':
            // Only supply office can mark as delivered
            if ($userRole !== 'supply_office') {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Only Supply Office can mark as delivered']);
                exit;
            }
            
            if ($prDetails['status'] === 'processing') {
                $success = $pr->markAsDelivered($prId);
                
                if ($success) {
                    // Notify department users
                    require_once __DIR__ . '/../config/database.php';
                    $db = getDB();
                    $deptUsersQuery = "SELECT id FROM users WHERE department_id = :dept_id AND is_active = 1";
                    $deptUsersStmt = $db->prepare($deptUsersQuery);
                    $deptUsersStmt->bindParam(':dept_id', $prDetails['department_id']);
                    $deptUsersStmt->execute();
                    $deptUserIds = $deptUsersStmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $title = 'Purchase Request Delivered';
                    $message = "Your Purchase Request (PR #{$prDetails['pr_number']}) has been delivered to the Supply Office and is ready for pickup. Please click 'Order Received' once you have received the items.";
                    
                    foreach ($deptUserIds as $deptUserId) {
                        $notification->createNotification($deptUserId, $title, $message, 'success');
                    }
                    
                    // Notify budget office
                    $budgetUsers = $notification->getUserIdsByRoles(['budget', 'school_admin']);
                    $budgetTitle = 'Purchase Request Status Updated';
                    $budgetMessage = "Purchase Request (PR #{$prDetails['pr_number']}) for {$prDetails['dept_name']} has been marked as DELIVERED.";
                    foreach ($budgetUsers as $budgetUserId) {
                        $notification->createNotification($budgetUserId, $budgetTitle, $budgetMessage, 'info');
                    }
                    
                    $message = 'Status updated successfully. Department has been notified.';
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'PR must be in processing status']);
                exit;
            }
            break;
            
        case 'received':
            // Only department users can mark as received
            if (!in_array($userRole, ['offices', 'supply_office'])) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
                exit;
            }
            
            // Check if user belongs to the department (for offices role)
            if ($userRole === 'offices') {
                $userDeptId = $_SESSION['department_id'] ?? 0;
                if ($userDeptId != $prDetails['department_id']) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'You can only receive PRs for your department']);
                    exit;
                }
            }
            
            if ($prDetails['status'] === 'delivered') {
                $success = $pr->markAsReceived($prId);
                
                if ($success) {
                    // Automatically mark as complete
                    $pr->markAsComplete($prId);
                    
                    // Notify procurement, supply office, and budget office
                    $procurementUsers = $notification->getUserIdsByRoles(['procurement']);
                    $supplyUsers = $notification->getUserIdsByRoles(['supply_office']);
                    $budgetUsers = $notification->getUserIdsByRoles(['budget', 'school_admin']);
                    
                    $title = 'Purchase Request Completed';
                    $message = "Purchase Request (PR #{$prDetails['pr_number']}) has been received and marked as complete by the department.";
                    
                    foreach (array_merge($procurementUsers, $supplyUsers, $budgetUsers) as $userId) {
                        $notification->createNotification($userId, $title, $message, 'success');
                    }
                }
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'PR must be in delivered status']);
                exit;
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Status updated successfully',
            'new_status' => $pr->getPRById($prId)['status']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update status']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>

