<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

// Allow both procurement and department roles
$userRole = $_SESSION['user_role'] ?? '';
if (!in_array($userRole, ['procurement', 'offices', 'supply_office'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/PurchaseRequest.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

// Get PRs based on role
$pr = new PurchaseRequest();
$departmentPRs = [];
$archivedPRs = [];

if ($userRole === 'procurement') {
    // Procurement sees all PRs
    $archivedFilters = [];
    if (isset($_GET['archived_dept']) && $_GET['archived_dept']) {
        $archivedFilters['department_id'] = (int)$_GET['archived_dept'];
    }
    if (isset($_GET['archived_date']) && $_GET['archived_date']) {
        $archivedFilters['date_from'] = $_GET['archived_date'];
        $archivedFilters['date_to'] = $_GET['archived_date'];
    }
    $allPRs = $pr->getPRsForProcurement([]);
    // Filter out completed ones from main list
    $departmentPRs = array_filter($allPRs, function($pr) {
        return $pr['status'] !== 'complete';
    });
    $archivedPRs = $pr->getArchivedPRsForProcurement($archivedFilters);
} else {
    // Departments see only their own PRs
    if ($departmentId) {
        $departmentPRs = $pr->getPRsForDepartment($departmentId);
        $archivedPRs = $pr->getArchivedPRsForDepartment($departmentId);
    }
}

// Get department name
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;
if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

// Set portal label based on role
if ($userRole === 'procurement') {
    $portalLabel = $departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal";
} else {
    $portalLabel = $departmentName ? "Department Portal | " . htmlspecialchars($departmentName) : "Department Portal";
}

// Check if user is from Admin department
$isAdminDepartment = false;
if ($departmentName && stripos($departmentName, 'admin') !== false) {
    $isAdminDepartment = true;
}

$fileSubmission = new FileSubmission();
$userSubmissions = $fileSubmission->getUserSubmissions($userId, 20);
$notification = new Notification();
$notificationsForBell = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Track Requests</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: '#800000',
                        'maroon-dark': '#5a0000',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <div id="sidebar" class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto w-64">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
                    <p class="text-sm text-gray-600 sidebar-text"><?php echo htmlspecialchars($portalLabel); ?></p>
                </div>
                <button onclick="toggleSidebar()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                    <svg id="sidebarToggleIcon" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
                    </svg>
                </button>
    </div>
            
            <?php 
            if ($userRole === 'procurement') {
                include __DIR__ . '/../components/proc_sidebar.php';
            } else {
                include __DIR__ . '/../components/dept_sidebar.php';
            }
            ?>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="text-white max-w-2xl">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Track Request</h1>
                                    <p class="text-red-100 text-sm">Stay updated on your Purchase Request status.</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <?php 
                                $notifications = $notificationsForBell;
                                include __DIR__ . '/../components/notification_bell.php'; 
                            ?>
                            <div class="relative">
                                <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-white bg-opacity-20 backdrop-blur-sm px-4 py-2 rounded-xl hover:bg-opacity-30 transition-colors border border-white border-opacity-30">
                                    <?php render_profile_avatar(['classes' => 'bg-white bg-opacity-30 text-white font-semibold border border-white border-opacity-50']); ?>
                                    <div class="text-white text-sm">
                                        <div class="font-medium"><?php echo htmlspecialchars($username); ?></div>
                                        <div class="text-xs text-red-100"><?php echo htmlspecialchars($userEmail); ?></div>
                                    </div>
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                                <div id="profileDropdown" class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl z-50 hidden border border-gray-100">
                                    <div class="py-2">
                                        <a href="profile.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        Profile
                                    </a>
                                        <a href="change_password.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Change Password
                                    </a>
                                        <div class="border-t border-gray-100 my-1"></div>
                                        <button onclick="confirmLogout()" class="flex items-center w-full px-4 py-3 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                        </svg>
                                        Logout
                                    </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-6">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4"><?php echo $userRole === 'procurement' ? 'All Purchase Requests' : 'Purchase Request Status'; ?></h2>
                    
                    <?php if (empty($departmentPRs)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p><?php echo $userRole === 'procurement' ? 'No purchase requests found.' : 'No purchase requests found for your department.'; ?></p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($departmentPRs as $prItem): 
                                $prFiles = $pr->getPRFiles($prItem['id']);
                                $statusMessages = [
                                    'pending' => 'Your purchase request is pending.',
                                    'processing' => 'Your purchase request is being processed, please wait for the delivery. You will get informed right away.',
                                    'delivered' => 'Your purchase request has been delivered to the Supply Office and is ready for pickup.',
                                    'received' => 'Your purchase request has been received.',
                                    'complete' => 'Your purchase request has been completed.'
                                ];
                                $statusMessage = $statusMessages[$prItem['status']] ?? 'Status: ' . $prItem['status'];
                                $canReceive = $prItem['status'] === 'delivered';
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($prItem['pr_number']); ?></h3>
                                            <?php if ($userRole === 'procurement' && isset($prItem['dept_name'])): ?>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($prItem['dept_name']); ?></p>
                                            <?php endif; ?>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo date('M j, Y g:i A', strtotime($prItem['submitted_at'])); ?></p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                            echo $prItem['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                ($prItem['status'] === 'delivered' ? 'bg-purple-100 text-purple-800' : 
                                                ($prItem['status'] === 'complete' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'));
                                        ?>">
                                            <?php echo strtoupper($prItem['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                        <p class="text-sm text-gray-700"><?php echo htmlspecialchars($statusMessage); ?></p>
                                    </div>
                                    
                                    <div class="flex gap-2 flex-wrap">
                                        <button onclick="viewPRFiles(<?php echo $prItem['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                            View Files (<?php echo $prItem['file_count']; ?>)
                                        </button>
                                        <?php if ($userRole !== 'procurement'): ?>
                                            <?php if ($canReceive): ?>
                                                <button onclick="markAsReceived(<?php echo $prItem['id']; ?>)" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                                    Order Received
                                                </button>
                                            <?php else: ?>
                                                <button disabled class="px-3 py-1 bg-gray-300 text-gray-500 rounded text-sm cursor-not-allowed">
                                                    Order Received
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mt-6">
                    <div class="flex justify-end">
                        <button onclick="openArchivedModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                            Completed & Archived
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Archived PRs Modal -->
            <div id="archivedModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center p-6 border-b border-gray-200">
                            <h3 class="text-xl font-bold text-gray-900">Completed & Archived Purchase Requests</h3>
                            <button onclick="closeArchivedModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex gap-3">
                                <?php if ($userRole === 'procurement'): ?>
                                    <?php
                                    require_once __DIR__ . '/../classes/Department.php';
                                    $dept = new Department();
                                    $allDepartments = $dept->getAllDepartments();
                                    ?>
                                    <select id="archivedFilterDepartment" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                        <option value="">All Departments</option>
                                        <?php foreach ($allDepartments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                                <input type="date" id="archivedFilterDate" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Filter by date">
                                <button onclick="filterArchivedPRs()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">Filter</button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6">
                            <div id="archivedPRListContainer" class="space-y-4">
                                <?php if (empty($archivedPRs)): ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <p>No archived purchase requests found.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($archivedPRs as $prItem): 
                                        $prFiles = $pr->getPRFiles($prItem['id']);
                                    ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex justify-between items-start mb-3">
                                                <div>
                                                    <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($prItem['pr_number']); ?></h3>
                                                    <?php if ($userRole === 'procurement' && isset($prItem['dept_name'])): ?>
                                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($prItem['dept_name']); ?></p>
                                                    <?php endif; ?>
                                                    <p class="text-sm text-gray-600 mt-1">Submitted: <?php echo date('M j, Y g:i A', strtotime($prItem['submitted_at'])); ?></p>
                                                    <p class="text-sm text-gray-600 mt-1">Completed: <?php echo $prItem['completed_at'] ? date('M j, Y g:i A', strtotime($prItem['completed_at'])) : 'N/A'; ?></p>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                    COMPLETE
                                                </span>
                                            </div>
                                            
                                            <div class="bg-gray-50 rounded-lg p-3 mb-3">
                                                <p class="text-sm text-gray-700">Your purchase request has been completed.</p>
                                            </div>
                                            
                                            <div class="flex gap-2 flex-wrap">
                                                <button onclick="viewPRFiles(<?php echo $prItem['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                                    View Files (<?php echo $prItem['file_count']; ?>)
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- File Viewer Modal -->
            <div id="fileViewerModal" class="fixed inset-0 bg-gray-900 bg-opacity-75 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg max-w-6xl w-full max-h-screen overflow-auto">
                        <div class="flex justify-between items-center p-4 border-b">
                            <h3 id="fileViewerTitle" class="text-lg font-semibold">PR Files</h3>
                            <button onclick="closeFileViewer()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div id="fileViewerContent" class="p-4"></div>
                    </div>
                </div>
            </div>
      </div>
</div>

    <!-- Order Received Confirmation Modal -->
    <div id="receiveConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Order Received</h3>
                    <button onclick="closeReceiveConfirmModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="flex items-center justify-center mb-4">
                        <div class="bg-blue-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-gray-600 text-center">Have you received the items from the Supply Office? This will mark the Purchase Request as received and complete.</p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeReceiveConfirmModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmReceive()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        Confirm Received
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Order Received Success Modal -->
    <div id="receivedSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Order Received</h3>
                    <button onclick="closeReceivedSuccessModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="flex items-center justify-center mb-4">
                        <div class="bg-green-100 rounded-full p-3">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                    </div>
                    <p class="text-gray-600 text-center">Purchase Request has been marked as received and completed. All relevant parties have been notified.</p>
                </div>
                <div class="flex justify-end">
                    <button onclick="closeReceivedSuccessModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Logout</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to logout?</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeLogoutModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button onclick="logout()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Logout
                    </button>
      </div>
    </div>
  </div>
</div>

<script>
        // Profile dropdown functionality
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function confirmLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function logout() {
            window.location.href = '../auth/logout.php';
        }
        
        // Sidebar toggle functionality - set initial state immediately to prevent animation
        (function() {
            const storageKey = 'sidebarCollapsed';
            const initialState = localStorage.getItem(storageKey) === 'true';
            if (initialState) {
                document.body.classList.add('sidebar-collapsed');
            }
        })();
        
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('sidebarToggle');
            const body = document.body;
            const mainContent = document.querySelector('[data-main-content]');
            const storageKey = 'sidebarCollapsed';

            function applyState(collapsed) {
                if (collapsed) {
                    body.classList.add('sidebar-collapsed');
                } else {
                    body.classList.remove('sidebar-collapsed');
                }
            }

            // Enable transitions after initial load
            if (mainContent) {
                setTimeout(function() {
                    mainContent.classList.add('sidebar-ready');
                }, 10);
            }

            toggleBtn?.addEventListener('click', function() {
                const collapsed = !body.classList.contains('sidebar-collapsed');
                applyState(collapsed);
                localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            });
        });
        
        function viewPRFiles(prId) {
            fetch('../ajax/get_pr_files.php?pr_id=' + prId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const modal = document.getElementById('fileViewerModal');
                    const title = document.getElementById('fileViewerTitle');
                    const content = document.getElementById('fileViewerContent');
                    
                    title.textContent = 'PR Files';
                    content.innerHTML = data.files.map(file => `
                        <div class="border border-gray-200 rounded-lg p-4 mb-3">
                            <div class="flex justify-between items-center">
                                <div>
                                    <h4 class="font-semibold text-gray-900">${file.file_name}</h4>
                                    <p class="text-sm text-gray-500">${new Date(file.uploaded_at).toLocaleString()}</p>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="viewFile('${file.file_path}', '${file.file_name}')" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">View</button>
                                    <a href="../${file.file_path}" download class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">Download</a>
                                </div>
                            </div>
                        </div>
                    `).join('');
                    modal.classList.remove('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading files');
            });
        }
        
        function viewFile(filePath, fileName) {
            const ext = fileName.split('.').pop().toLowerCase();
            const fullPath = '../' + filePath;
            const content = document.getElementById('fileViewerContent');
            
            if (['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'jfif'].includes(ext)) {
                content.innerHTML = `<img src="${fullPath}" class="max-w-full h-auto mx-auto" alt="${fileName}">`;
            } else if (ext === 'pdf') {
                content.innerHTML = `<iframe src="${fullPath}" class="w-full" style="min-height: 600px;"></iframe>`;
            } else if (['xlsx', 'xls', 'csv'].includes(ext)) {
                content.innerHTML = `<iframe src="../ajax/view_excel.php?file=${encodeURIComponent(filePath)}" class="w-full" style="min-height: 600px;"></iframe>`;
            } else {
                content.innerHTML = `<div class="text-center py-8"><p class="text-gray-600 mb-4">Preview not available.</p><a href="${fullPath}" download class="px-4 py-2 bg-blue-600 text-white rounded-lg">Download</a></div>`;
            }
        }
        
        function closeFileViewer() {
            document.getElementById('fileViewerModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        document.getElementById('receiveConfirmModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReceiveConfirmModal();
            }
        });
        
        document.getElementById('receivedSuccessModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeReceivedSuccessModal();
            }
        });
        
        let pendingReceivePRId = null;
        
        function markAsReceived(prId) {
            pendingReceivePRId = prId;
            showReceiveConfirmModal();
        }
        
        function showReceiveConfirmModal() {
            document.getElementById('receiveConfirmModal').classList.remove('hidden');
        }
        
        function closeReceiveConfirmModal() {
            document.getElementById('receiveConfirmModal').classList.add('hidden');
            pendingReceivePRId = null;
        }
        
        function confirmReceive() {
            if (!pendingReceivePRId) {
                return;
            }
            
            // Save PR ID before closing modal (which resets pendingReceivePRId)
            const prId = pendingReceivePRId;
            closeReceiveConfirmModal();
            
            const formData = new FormData();
            formData.append('pr_id', prId);
            formData.append('action', 'received');
            
            fetch('../ajax/update_pr_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showReceivedSuccessModal();
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred');
            });
        }
        
        function showReceivedSuccessModal() {
            document.getElementById('receivedSuccessModal').classList.remove('hidden');
        }
        
        function closeReceivedSuccessModal() {
            document.getElementById('receivedSuccessModal').classList.add('hidden');
        }
        
        function openArchivedModal() {
            document.getElementById('archivedModal').classList.remove('hidden');
        }
        
        function closeArchivedModal() {
            document.getElementById('archivedModal').classList.add('hidden');
        }
        
        function filterArchivedPRs() {
            const filterDate = document.getElementById('archivedFilterDate').value;
            const deptFilter = document.getElementById('archivedFilterDepartment');
            const deptValue = deptFilter ? deptFilter.value : '';
            
            // For now, just reload the page - could be enhanced with AJAX
            let url = window.location.pathname;
            const params = [];
            if (filterDate) params.push('archived_date=' + filterDate);
            if (deptValue) params.push('archived_dept=' + deptValue);
            if (params.length > 0) url += '?' + params.join('&');
            
            window.location.href = url;
        }
    </script>
    <style>
        :root {
            --sidebar-expanded-width: 256px;
            --sidebar-collapsed-width: 80px;
        }

        [data-main-content] {
            margin-left: var(--sidebar-expanded-width);
        }
        
        [data-main-content].sidebar-ready {
            transition: margin-left 0.3s ease;
        }

        #sidebar {
            width: var(--sidebar-expanded-width);
            transition: width 0.3s ease;
        }

        body.sidebar-collapsed [data-main-content] {
            margin-left: var(--sidebar-collapsed-width);
        }

        body.sidebar-collapsed #sidebar {
            width: var(--sidebar-collapsed-width);
        }

        body.sidebar-collapsed #sidebar .sidebar-text {
            display: none;
        }

        body.sidebar-collapsed #sidebar .sidebar-icon {
            margin-right: 0;
        }

        body.sidebar-collapsed #sidebar nav a {
            justify-content: center;
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        body.sidebar-collapsed #sidebar #sidebarToggleIcon {
            transform: rotate(180deg);
        }
    </style>

</body>
</html>
