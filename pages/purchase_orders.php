<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'supply_office') {
    header('Location: ./dept_dashboard.php');
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/PurchaseRequest.php';
include __DIR__ . '/../components/profile_avatar.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Supply Officer';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;

if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

$portalLabel = $departmentName ? "Supply Office | " . htmlspecialchars($departmentName) : "Supply Office Portal";

// Get PRs for supply office
$pr = new PurchaseRequest();
$filters = [];
if (isset($_GET['department_id']) && $_GET['department_id']) {
    $filters['department_id'] = (int)$_GET['department_id'];
}
if (isset($_GET['date']) && $_GET['date']) {
    $filters['date_from'] = $_GET['date'];
    $filters['date_to'] = $_GET['date'];
}
$purchaseOrders = $pr->getPRsForSupplyOffice($filters);
$totalPurchaseOrders = count($purchaseOrders);

// Get departments for filter
require_once __DIR__ . '/../classes/Department.php';
$department = new Department();
$departments = $department->getAllDepartments();

// Get archived PRs with separate filters
$archivedFilters = [];
if (isset($_GET['archived_dept']) && $_GET['archived_dept']) {
    $archivedFilters['department_id'] = (int)$_GET['archived_dept'];
}
if (isset($_GET['archived_date']) && $_GET['archived_date']) {
    $archivedFilters['date_from'] = $_GET['archived_date'];
    $archivedFilters['date_to'] = $_GET['archived_date'];
}
$archivedPRs = $pr->getArchivedPRsForSupplyOffice($archivedFilters);

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Purchase Orders</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: '#800000',
                        'maroon-dark': '#5a0000'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <div id="sidebar" class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto w-64">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
                    <p class="text-sm text-gray-600 sidebar-text"><?php echo htmlspecialchars($portalLabel); ?></p>
                </div>
                <button id="sidebarToggle" type="button" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Toggle sidebar">
                    <svg class="w-5 h-5 text-gray-600 sidebar-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5l-7 7 7 7M20 5l-7 7 7 7"></path>
                    </svg>
                </button>
            </div>
            <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
        </div>
        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Purchase Orders</h1>
                                    <p class="text-red-100 text-sm">Manage purchase requests and mark as delivered</p>
                                </div>
                            </div>
                            <div class="mt-3 space-x-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 text-white border border-white border-opacity-30">
                                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                    Purchase Order Queue
                                </span>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 text-white border border-white border-opacity-30">
                                    <?php echo $totalPurchaseOrders; ?> total
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <?php include __DIR__ . '/../components/notification_bell.php'; ?>
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
            <div class="flex-1 p-6 space-y-6">
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Purchase Order Queue</h2>
                        <div class="flex gap-3">
                            <select id="filterDepartment" onchange="filterOrders()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="date" id="filterDate" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>" onchange="filterOrders()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <button onclick="filterOrders()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">Filter</button>
                            <button onclick="openArchivedModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                                Completed & Archived
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($purchaseOrders)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No purchase orders found.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($purchaseOrders as $order): 
                                $prFiles = $pr->getPRFiles($order['id']);
                                $statusLabels = [
                                    'pending' => 'Waiting for Processing',
                                    'processing' => 'Waiting for Delivery',
                                    'delivered' => 'Delivered - Awaiting Pickup'
                                ];
                                $statusLabel = $statusLabels[$order['status']] ?? ucfirst($order['status']);
                                $canDeliver = $order['status'] === 'processing';
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($order['pr_number']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($order['dept_name']); ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Submitted: <?php echo date('M j, Y g:i A', strtotime($order['submitted_at'])); ?></p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php 
                                            echo $order['status'] === 'processing' ? 'bg-blue-100 text-blue-800' : 
                                                ($order['status'] === 'delivered' ? 'bg-purple-100 text-purple-800' : 'bg-yellow-100 text-yellow-800');
                                        ?>">
                                            <?php echo $statusLabel; ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex gap-2 flex-wrap">
                                        <button onclick="viewPRFiles(<?php echo $order['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                            View Files (<?php echo $order['file_count']; ?>)
                                        </button>
                                        <?php if ($canDeliver): ?>
                                            <button onclick="markAsDelivered(<?php echo $order['id']; ?>)" class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                                Delivered
                                            </button>
                                        <?php else: ?>
                                            <button disabled class="px-3 py-1 bg-gray-300 text-gray-500 rounded text-sm cursor-not-allowed">
                                                Delivered
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
            </div>
            
            <!-- Archived PRs Modal -->
            <div id="archivedModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
                <div class="flex items-center justify-center min-h-screen p-4">
                    <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div class="flex justify-between items-center p-6 border-b border-gray-200">
                            <h3 class="text-xl font-bold text-gray-900">Completed & Archived Purchase Orders</h3>
                            <button onclick="closeArchivedModal()" class="text-gray-400 hover:text-gray-600">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex gap-3">
                                <select id="archivedFilterDepartment" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                    <option value="">All Departments</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['archived_dept']) && $_GET['archived_dept'] == $dept['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="date" id="archivedFilterDate" value="<?php echo isset($_GET['archived_date']) ? htmlspecialchars($_GET['archived_date']) : ''; ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm" placeholder="Filter by date">
                                <button onclick="filterArchivedOrders()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">Filter</button>
                            </div>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6">
                            <div id="archivedPRListContainer" class="space-y-4">
                                <?php if (empty($archivedPRs)): ?>
                                    <div class="text-center py-8 text-gray-500">
                                        <p>No archived purchase orders found.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($archivedPRs as $order): 
                                        $prFiles = $pr->getPRFiles($order['id']);
                                    ?>
                                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div class="flex justify-between items-start mb-3">
                                                <div>
                                                    <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($order['pr_number']); ?></h3>
                                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($order['dept_name']); ?></p>
                                                    <p class="text-xs text-gray-500 mt-1">Submitted: <?php echo date('M j, Y g:i A', strtotime($order['submitted_at'])); ?></p>
                                                    <p class="text-xs text-gray-500 mt-1">Completed: <?php echo $order['completed_at'] ? date('M j, Y g:i A', strtotime($order['completed_at'])) : 'N/A'; ?></p>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                    COMPLETE
                                                </span>
                                            </div>
                                            
                                            <div class="flex gap-2 flex-wrap">
                                                <button onclick="viewPRFiles(<?php echo $order['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                                    View Files (<?php echo $order['file_count']; ?>)
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
    <!-- Deliver Confirmation Modal -->
    <div id="deliverConfirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Delivery</h3>
                    <button onclick="closeDeliverConfirmModal()" class="text-gray-400 hover:text-gray-600">
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
                    <p class="text-gray-600 text-center">Have you completed the delivery of items to the Supply Office? This action will notify the requesting department that their items are ready for pickup.</p>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeDeliverConfirmModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmDeliver()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        Confirm Delivery
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delivered Success Modal -->
    <div id="deliveredSuccessModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Item Delivered</h3>
                    <button onclick="closeDeliveredSuccessModal()" class="text-gray-400 hover:text-gray-600">
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
                    <p class="text-gray-600 text-center">Item has been delivered and is ready for pickup. The department has been notified.</p>
                </div>
                <div class="flex justify-end">
                    <button onclick="closeDeliveredSuccessModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        OK
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
                    <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <p class="text-gray-600 mb-6">Are you sure you want to logout? You will need to login again to access the dashboard.</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button onclick="performLogout()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function confirmLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function performLogout() {
            window.location.href = '../auth/logout.php';
        }

        function toggleProfileDropdown() {
            document.getElementById('profileDropdown')?.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');

            if (!button && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

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

            const initialState = localStorage.getItem(storageKey) === 'true';
            applyState(initialState);

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
        
        let pendingPRId = null;
        
        function markAsDelivered(prId) {
            pendingPRId = prId;
            showDeliverConfirmModal();
        }
        
        function showDeliverConfirmModal() {
            document.getElementById('deliverConfirmModal').classList.remove('hidden');
        }
        
        function closeDeliverConfirmModal() {
            document.getElementById('deliverConfirmModal').classList.add('hidden');
            pendingPRId = null;
        }
        
        function confirmDeliver() {
            if (!pendingPRId) {
                return;
            }
            
            // Save PR ID before closing modal (which resets pendingPRId)
            const prId = pendingPRId;
            closeDeliverConfirmModal();
            
            const formData = new FormData();
            formData.append('pr_id', prId);
            formData.append('action', 'delivered');
            
            fetch('../ajax/update_pr_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showDeliveredSuccessModal();
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
        
        function showDeliveredSuccessModal() {
            document.getElementById('deliveredSuccessModal').classList.remove('hidden');
        }
        
        function closeDeliveredSuccessModal() {
            document.getElementById('deliveredSuccessModal').classList.add('hidden');
        }
        
        // Close modals when clicking outside
        document.getElementById('deliverConfirmModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeliverConfirmModal();
            }
        });
        
        document.getElementById('deliveredSuccessModal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeliveredSuccessModal();
            }
        });
        
        function filterOrders() {
            const deptFilter = document.getElementById('filterDepartment').value;
            const filterDate = document.getElementById('filterDate').value;
            
            let url = window.location.pathname + '?';
            if (deptFilter) url += 'department_id=' + deptFilter + '&';
            if (filterDate) url += 'date=' + filterDate;
            
            window.location.href = url;
        }
        
        function openArchivedModal() {
            document.getElementById('archivedModal').classList.remove('hidden');
        }
        
        function closeArchivedModal() {
            document.getElementById('archivedModal').classList.add('hidden');
        }
        
        function filterArchivedOrders() {
            const deptFilter = document.getElementById('archivedFilterDepartment').value;
            const filterDate = document.getElementById('archivedFilterDate').value;
            
            // Reload page with archived filters
            let url = window.location.pathname + '?';
            const mainDept = document.getElementById('filterDepartment').value;
            const mainDate = document.getElementById('filterDate').value;
            if (mainDept) url += 'department_id=' + mainDept + '&';
            if (mainDate) url += 'date=' + mainDate + '&';
            if (deptFilter) url += 'archived_dept=' + deptFilter + '&';
            if (filterDate) url += 'archived_date=' + filterDate;
            
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

        body.sidebar-collapsed #sidebar .sidebar-toggle-icon {
            transform: rotate(180deg);
        }
    </style>

</body>
</html>

