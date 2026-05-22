<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

$userRole = $_SESSION['user_role'];
if ($userRole === 'budget') {
    header('Location: allocations.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../components/profile_avatar.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

// Get department name
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;
if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

$portalLabel = ($userRole === 'procurement') 
    ? ($departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal")
    : (($userRole === 'school-admin') 
        ? 'School Admin Portal' 
        : ($departmentName ? "Department Portal | " . htmlspecialchars($departmentName) : "Department Portal"));

// Check if user is from Admin department
$isAdminDepartment = false;
if ($departmentName && stripos($departmentName, 'admin') !== false) {
    $isAdminDepartment = true;
}

$tag = ($userRole === 'procurement') ? 'Procurement Office' : (($userRole === 'school-admin') ? 'Campus Admin' : ($isAdminDepartment ? 'Admin Office' : 'Department Office'));
$heroTitle = ($userRole === 'procurement') ? 'Budget Allocation Breakdown' : (($userRole === 'school-admin') ? 'School-wide Allocations Viewer' : 'Budget Allocation Breakdown');
$heroDescription = ($userRole === 'procurement') ? 'View detailed budget allocation breakdown.' : (($userRole === 'school-admin') ? 'View allocation overviews for campus-wide transparency.' : 'View detailed breakdown of your department\'s budget allocation.');

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId ?? 0, 10);
$unreadCount = $notification->getUnreadCount($userId ?? 0);

// Get budget allocation data
$allocationData = null;
$fiscalYear = isset($_GET['fiscal_year']) ? intval($_GET['fiscal_year']) : date('Y');
$allocationHistory = [];
$childDepartments = [];
$allDepartmentIds = [];

if ($departmentId) {
    try {
        $db = getDB();
        
        // Get child departments (departments that have this department as parent)
        try {
            $childStmt = $db->prepare("
                SELECT id, dept_name, dept_code FROM departments 
                WHERE parent_department_id = ? AND is_active = 1
            ");
            $childStmt->execute([$departmentId]);
            $childDepartments = $childStmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            $childDepartments = [];
        }
        
        // Build array of all department IDs (current + children)
        $allDepartmentIds = [$departmentId];
        foreach ($childDepartments as $child) {
            $allDepartmentIds[] = $child['id'];
        }
        
        // Check if table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'budget_allocations'");
        if ($checkTable->rowCount() > 0) {
            $stmt = $db->prepare("
                SELECT * FROM budget_allocations 
                WHERE department_id = ? AND fiscal_year = ?
                ORDER BY created_at DESC 
                LIMIT 1
            ");
            $stmt->execute([$departmentId, $fiscalYear]);
            $allocationData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($allocationData && is_string($allocationData['allocation_data'])) {
                $allocationData['allocation_data'] = json_decode($allocationData['allocation_data'], true);
            }
            
            // Get allocation history for this department and child departments
            $placeholders = implode(',', array_fill(0, count($allDepartmentIds), '?'));
            $historyStmt = $db->prepare("
                SELECT ba.*, d.dept_name as allocation_dept_name 
                FROM budget_allocations ba
                LEFT JOIN departments d ON ba.department_id = d.id
                WHERE ba.department_id IN ($placeholders)
                ORDER BY ba.created_at DESC
            ");
            $historyStmt->execute($allDepartmentIds);
            $allocationHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Exception $e) {
        $allocationData = null;
        $allocationHistory = [];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Budget Allocation Breakdown</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: '#800000',
                        'maroon-dark': '#5a0000',
                        'maroon-light': '#a00000',
                    }
                }
            }
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
            <?php if ($userRole === 'procurement'): ?>
                <?php include __DIR__ . '/../components/proc_sidebar.php'; ?>
            <?php else: ?>
                <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
            <?php endif; ?>
        </div>

        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1"><?php echo htmlspecialchars($heroTitle); ?></h1>
                                    <p class="text-red-100 text-sm"><?php echo htmlspecialchars($heroDescription); ?></p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 backdrop-blur-sm text-white border border-white border-opacity-30">
                                    <span class="w-2 h-2 bg-green-300 rounded-full mr-2 animate-pulse"></span>
                                    <?php echo htmlspecialchars($tag); ?>
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                            </svg>
                                            Change Password
                                        </a>
                                        <a href="account_settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Account Settings
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
                <!-- Fiscal Year Selector -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
                    <div class="flex items-center gap-4">
                        <label for="fiscalYearSelect" class="text-sm font-semibold text-gray-700">
                            Fiscal Year:
                        </label>
                        <select 
                            id="fiscalYearSelect" 
                            name="fiscalYearSelect" 
                            onchange="changeFiscalYear(this.value)"
                            class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-semibold"
                        >
                            <?php 
                            $currentYear = date('Y');
                            for ($year = $currentYear - 5; $year <= $currentYear + 1; $year++): 
                            ?>
                                <option value="<?php echo $year; ?>" <?php echo ($year == $fiscalYear) ? 'selected' : ''; ?>>
                                    <?php echo $year; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <span class="text-sm text-gray-600">
                            Viewing allocation for fiscal year <?php echo $fiscalYear; ?>
                        </span>
                    </div>
                </div>
                
                <?php if ($allocationData): ?>
                    <!-- Tab Navigation -->
                    <?php if (!empty($childDepartments)): ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="border-b border-gray-200">
                            <nav class="flex" aria-label="Tabs">
                                <button onclick="switchTab('myAllocation')" id="tab-myAllocation" class="tab-btn flex-1 py-4 px-6 text-center border-b-2 border-maroon text-maroon font-semibold bg-maroon bg-opacity-5 transition-all">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        My Allocation
                                    </div>
                                </button>
                                <button onclick="switchTab('subDepartments')" id="tab-subDepartments" class="tab-btn flex-1 py-4 px-6 text-center border-b-2 border-transparent text-gray-500 font-medium hover:text-gray-700 hover:border-gray-300 transition-all">
                                    <div class="flex items-center justify-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        Sub-Departments (<?php echo count($childDepartments); ?>)
                                    </div>
                                </button>
                            </nav>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- My Allocation Tab Content -->
                    <div id="panel-myAllocation" class="tab-panel">
                    <!-- Header Information with Download and History buttons -->
                    <?php 
                    $allocData = $allocationData['allocation_data'] ?? null;
                    $isOffice = isset($allocData['is_office']) && $allocData['is_office'] === true;
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Budget Allocation Details</h2>
                            <div class="flex items-center gap-3">
                                <?php if ($allocationData && isset($allocationData['id'])): ?>
                                    <button onclick="downloadAllocationPDF(<?php echo $allocationData['id']; ?>)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download PDF
                                    </button>
                                <?php endif; ?>
                                <?php if (!empty($allocationHistory)): ?>
                                    <button onclick="toggleHistoryModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors font-semibold text-sm flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        History
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <p class="text-xs text-gray-500 mb-1"><?php echo $isOffice ? 'Office' : 'Department'; ?></p>
                                <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($departmentName); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Fiscal Year</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($fiscalYear); ?></p>
                            </div>
                            <?php if ($isOffice): ?>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Budget Allocated</p>
                                <p class="text-lg font-bold text-gray-900">₱<?php echo number_format(floatval($allocationData['budget_allocated'] ?? 0), 2); ?></p>
                            </div>
                            <div></div>
                            <?php else: ?>
                            <!-- Number of Students - HIDDEN as per requirements -->
                            <div style="display: none;">
                                <p class="text-xs text-gray-500 mb-1">Number of Students</p>
                                <p class="text-lg font-bold text-gray-900"><?php echo number_format($allocationData['num_students'] ?? 0); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">Total Tuition Fee</p>
                                <p class="text-lg font-bold text-gray-900">₱<?php echo number_format(floatval($allocationData['total_tuition_fee'] ?? 0), 2); ?></p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 mb-1">50% Instructional</p>
                                <p class="text-lg font-bold text-gray-900">₱<?php echo number_format(floatval($allocationData['instructional_amount'] ?? 0), 2); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Total Amount / Additional Amount / Overall Total Display -->
                        <div class="mt-6 pt-6 border-t-2 border-gray-200">
                            <?php 
                            // Get additional amount and overall total from database
                            $additionalAmount = isset($allocationData['additional_amount']) ? floatval($allocationData['additional_amount']) : 0;
                            $overallTotal = isset($allocationData['overall_total']) ? floatval($allocationData['overall_total']) : 0;
                            
                            if ($additionalAmount > 0): 
                                // WITH Additional Amount: Show Total Amount, Additional Amount, and Overall Total
                                $totalAmountBeforeAdditional = $overallTotal - $additionalAmount;
                            ?>
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Total Amount</p>
                                        <p class="text-lg font-semibold text-gray-700">₱<?php echo number_format($totalAmountBeforeAdditional, 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Additional Amount</p>
                                        <p class="text-lg font-semibold text-amber-600">₱<?php echo number_format($additionalAmount, 2); ?></p>
                                        <?php if (!empty($allocationData['additional_description'])): ?>
                                            <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars($allocationData['additional_description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Overall Total</p>
                                        <p class="text-2xl font-bold text-maroon">₱<?php echo number_format($overallTotal, 2); ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <!-- WITHOUT Additional Amount: Show Overall Total and Additional Amount (0) -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Overall Total</p>
                                        <p class="text-2xl font-bold text-maroon">₱<?php echo number_format($overallTotal, 2); ?></p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-gray-500 mb-1">Additional Amount</p>
                                        <p class="text-lg font-semibold text-gray-400">₱0.00</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mt-4">
                            <p class="text-xs text-gray-500 mb-1">Last Updated</p>
                            <p class="text-sm font-semibold text-gray-700"><?php echo date('M j, Y g:i A', strtotime($allocationData['updated_at'])); ?></p>
                        </div>
                    </div>

                    <?php 
                    if ($allocData): 
                    ?>
                        <!-- Non-Fiduciary Fund Breakdown -->
                        <?php if (isset($allocData['non_fiduciary']) && !$isOffice): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Non-Fiduciary Fund Breakdown</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300">
                                            <th class="text-left py-4 px-4 font-bold text-gray-800">Category</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Percent</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">50% (Instructional)</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Deductions</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Budget Allocation</th>
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
                                        $nonFiduciaryTotalInstructional = 0;
                                        $nonFiduciaryTotalDeductions = 0;
                                        foreach ($categories as $key => $name): 
                                            if (isset($allocData['non_fiduciary'][$key])):
                                                $item = $allocData['non_fiduciary'][$key];
                                                $deductions = $item['deductions'] ?? [];
                                                $deductionTotal = 0;
                                                foreach ($deductions as $ded) {
                                                    $deductionTotal += floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                                                }
                                                $budgetAlloc = floatval(str_replace(['₱', ','], '', $item['budget_allocation'] ?? '0'));
                                                $nonFiduciaryTotal += $budgetAlloc;
                                                
                                                // Calculate totals
                                                $percentValue = floatval(str_replace('%', '', $item['percent'] ?? '0'));
                                                $nonFiduciaryTotalPercent += $percentValue;
                                                
                                                $instructionalValue = floatval(str_replace(['₱', ','], '', $item['instructional'] ?? '0'));
                                                $nonFiduciaryTotalInstructional += $instructionalValue;
                                                
                                                $nonFiduciaryTotalDeductions += $deductionTotal;
                                        ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="py-4 px-4 font-semibold text-gray-800"><?php echo htmlspecialchars($name); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-700"><?php 
                                                $percentValue = $item['percent'] ?? '0';
                                                // Ensure % symbol is always present
                                                echo htmlspecialchars(strpos($percentValue, '%') !== false ? $percentValue : $percentValue . '%'); 
                                            ?></td>
                                            <td class="py-4 px-4 text-right text-gray-700"><?php echo htmlspecialchars($item['instructional'] ?? '₱0.00'); ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <?php if (!empty($deductions)): ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($deductions as $ded): ?>
                                                            <div class="text-sm">
                                                                <span class="text-gray-700"><?php echo htmlspecialchars($ded['amount'] ?? '₱0.00'); ?></span>
                                                                <?php if (!empty($ded['remarks'])): ?>
                                                                    <span class="text-gray-500 ml-2">(<?php echo htmlspecialchars($ded['remarks']); ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <div class="pt-2 border-t border-gray-200 mt-2">
                                                            <span class="font-semibold text-gray-800">Total: ₱<?php echo number_format($deductionTotal, 2); ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-4 text-right font-bold <?php echo $budgetAlloc < 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($item['budget_allocation'] ?? '₱0.00'); ?>
                                            </td>
                                        </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                        <tr class="bg-gray-100 border-t-2 border-gray-400 font-bold">
                                            <td class="py-4 px-4 text-gray-900">Total Non-Fiduciary Fund</td>
                                            <td class="py-4 px-4 text-right text-gray-900"><?php echo number_format($nonFiduciaryTotalPercent, 2); ?>%</td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($nonFiduciaryTotalInstructional, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($nonFiduciaryTotalDeductions, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-maroon text-lg">₱<?php echo number_format($nonFiduciaryTotal, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Fiduciary Fund Breakdown -->
                        <?php if (isset($allocData['fiduciary'])): 
                            if ($isOffice):
                                // Office format: Use same table format as departments
                                $fiduciary = $allocData['fiduciary'];
                                $deductions = $fiduciary['deductions'] ?? [];
                                $totalBudget = $fiduciary['total_budget'] ?? '₱0.00';
                                
                                // Get allocated budget from database
                                $allocatedBudget = floatval($allocationData['budget_allocated'] ?? 0);
                                
                                // Calculate deduction total
                                $deductionTotal = 0;
                                foreach ($deductions as $ded) {
                                    $deductionTotal += floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                                }
                                $totalBudgetValue = floatval(str_replace(['₱', ','], '', $totalBudget));
                        ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Fiduciary Fund Breakdown</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300">
                                            <th class="text-left py-4 px-4 font-bold text-gray-800">Fiduciary</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Budget Collected</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Deductions</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Total Budget</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="py-4 px-4 font-semibold text-gray-800">Budget Allocated</td>
                                            <td class="py-4 px-4 text-right text-gray-700">₱<?php echo number_format($allocatedBudget, 2); ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <?php if (!empty($deductions)): ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($deductions as $ded): ?>
                                                            <div class="text-sm">
                                                                <span class="text-gray-700"><?php echo htmlspecialchars($ded['amount'] ?? '₱0.00'); ?></span>
                                                                <?php if (!empty($ded['remarks'])): ?>
                                                                    <span class="text-gray-500 ml-2">(<?php echo htmlspecialchars($ded['remarks']); ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <div class="pt-2 border-t border-gray-200 mt-2">
                                                            <span class="font-semibold text-gray-800">Total: ₱<?php echo number_format($deductionTotal, 2); ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-4 text-right font-bold <?php echo $totalBudgetValue < 0 ? 'text-red-600' : 'text-red-900'; ?>">
                                                <?php echo htmlspecialchars($totalBudget); ?>
                                            </td>
                                        </tr>
                                        <tr class="bg-gray-100 border-t-2 border-gray-400 font-bold">
                                            <td class="py-4 px-4 text-gray-900">Total Fiduciary Fund</td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($allocatedBudget, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($deductionTotal, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-maroon text-lg">₱<?php echo number_format($totalBudgetValue, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php else: 
                                // Department format: Table with items
                        ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Fiduciary Fund Breakdown</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300">
                                            <th class="text-left py-4 px-4 font-bold text-gray-800">Item</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Budget Collected</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Deductions</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Total Budget</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $fiduciaryTotal = 0;
                                        $fiduciaryTotalBudgetCollected = 0;
                                        $fiduciaryTotalDeductions = 0;
                                        foreach ($allocData['fiduciary'] as $key => $item): 
                                            if (!empty($item['item_name']) || !empty($item['instructional'])):
                                                $deductions = $item['deductions'] ?? [];
                                                $deductionTotal = 0;
                                                foreach ($deductions as $ded) {
                                                    $deductionTotal += floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                                                }
                                                // Use total_budget if available, otherwise calculate from budget_allocation
                                                $totalBudgetItem = $item['total_budget'] ?? $item['budget_allocation'] ?? '₱0.00';
                                                $totalBudgetItemValue = floatval(str_replace(['₱', ','], '', $totalBudgetItem));
                                                $fiduciaryTotal += $totalBudgetItemValue;
                                                
                                                // Calculate totals
                                                $instructionalValue = floatval(str_replace(['₱', ','], '', $item['instructional'] ?? '0'));
                                                $fiduciaryTotalBudgetCollected += $instructionalValue;
                                                
                                                $fiduciaryTotalDeductions += $deductionTotal;
                                        ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="py-4 px-4 font-semibold text-gray-800"><?php echo htmlspecialchars($item['item_name'] ?? 'Item ' . $key); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-700"><?php echo htmlspecialchars($item['instructional'] ?? '₱0.00'); ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <?php if (!empty($deductions)): ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($deductions as $ded): ?>
                                                            <div class="text-sm">
                                                                <span class="text-gray-700"><?php echo htmlspecialchars($ded['amount'] ?? '₱0.00'); ?></span>
                                                                <?php if (!empty($ded['remarks'])): ?>
                                                                    <span class="text-gray-500 ml-2">(<?php echo htmlspecialchars($ded['remarks']); ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <div class="pt-2 border-t border-gray-200 mt-2">
                                                            <span class="font-semibold text-gray-800">Total: ₱<?php echo number_format($deductionTotal, 2); ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-4 text-right font-bold <?php echo $totalBudgetItemValue < 0 ? 'text-red-600' : 'text-red-900'; ?>">
                                                <?php echo htmlspecialchars($totalBudgetItem); ?>
                                            </td>
                                        </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                        <tr class="bg-gray-100 border-t-2 border-gray-400 font-bold">
                                            <td class="py-4 px-4 text-gray-900">Total Fiduciary Fund</td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($fiduciaryTotalBudgetCollected, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($fiduciaryTotalDeductions, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-maroon text-lg">₱<?php echo number_format($fiduciaryTotal, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>

                        <!-- Deduction Breakdown by Type -->
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
                        if (isset($allocData['non_fiduciary']) && !$isOffice) {
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
                        if (isset($allocData['fiduciary'])) {
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
                                    if (!empty($item['item_name']) || !empty($item['instructional'])) {
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
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Total Deduction Breakdown by Type</h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300">
                                            <th class="text-left py-4 px-4 font-bold text-gray-800">Deduction Type</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Total Amount</th>
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
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="py-4 px-4 font-semibold text-gray-800"><?php echo htmlspecialchars($type['label']); ?></td>
                                            <td class="py-4 px-4 text-right font-bold text-gray-900">₱<?php echo number_format($amount, 2); ?></td>
                                        </tr>
                                        <?php
                                            endif;
                                        endforeach;
                                        ?>
                                        <tr class="bg-gray-100 border-t-2 border-gray-400 font-bold">
                                            <td class="py-4 px-4 text-gray-900">Grand Total Deductions</td>
                                            <td class="py-4 px-4 text-right text-maroon text-lg">₱<?php echo number_format($grandTotalDeductions, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Overall Summary -->
                        <div class="bg-gradient-to-r from-maroon to-red-800 rounded-2xl shadow-lg border border-maroon p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-2xl font-bold mb-2">Overall Total Budget Allocation</h3>
                                    <p class="text-red-100">Fiscal Year <?php echo htmlspecialchars($fiscalYear); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-4xl font-bold">₱<?php echo number_format(floatval($allocationData['overall_total'] ?? 0), 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <p class="text-gray-600">Allocation data is not available.</p>
                        </div>
                    <?php endif; ?>
                    </div><!-- End My Allocation Tab Panel -->
                    
                    <?php if (!empty($childDepartments)): ?>
                    <!-- Sub-Departments Tab Content -->
                    <div id="panel-subDepartments" class="tab-panel hidden space-y-6">
                        <?php 
                        foreach ($childDepartments as $child): 
                            // Get child department's allocation
                            $childAllocation = null;
                            $childAllocData = null;
                            try {
                                $childStmt = $db->prepare("
                                    SELECT * FROM budget_allocations 
                                    WHERE department_id = ? AND fiscal_year = ?
                                    ORDER BY created_at DESC LIMIT 1
                                ");
                                $childStmt->execute([$child['id'], $fiscalYear]);
                                $childAllocation = $childStmt->fetch(PDO::FETCH_ASSOC);
                                if ($childAllocation && is_string($childAllocation['allocation_data'])) {
                                    $childAllocData = json_decode($childAllocation['allocation_data'], true);
                                } elseif ($childAllocation) {
                                    $childAllocData = $childAllocation['allocation_data'];
                                }
                            } catch (Exception $e) {}
                            
                            $childIsOffice = isset($childAllocData['is_office']) && $childAllocData['is_office'] === true;
                        ?>
                        
                        <?php if ($childAllocation && $childAllocData): ?>
                        <!-- Child Department: <?php echo htmlspecialchars($child['dept_name']); ?> -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-3">
                                    <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($child['dept_name']); ?></h2>
                                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Sub-Dept</span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button onclick="downloadAllocationPDF(<?php echo $childAllocation['id']; ?>)" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm flex items-center gap-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download PDF
                                    </button>
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div>
                                    <p class="text-xs text-gray-500 mb-1"><?php echo $childIsOffice ? 'Office' : 'Department'; ?></p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($child['dept_name']); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Fiscal Year</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($fiscalYear); ?></p>
                                </div>
                                <?php if ($childIsOffice): ?>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Budget Allocated</p>
                                    <p class="text-lg font-bold text-gray-900">₱<?php echo number_format(floatval($childAllocation['budget_allocated'] ?? 0), 2); ?></p>
                                </div>
                                <div></div>
                                <?php else: ?>
                                <div style="display: none;">
                                    <p class="text-xs text-gray-500 mb-1">Number of Students</p>
                                    <p class="text-lg font-bold text-gray-900"><?php echo number_format($childAllocation['num_students'] ?? 0); ?></p>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500 mb-1">Total Tuition Fee</p>
                                    <p class="text-lg font-bold text-gray-900">₱<?php echo number_format(floatval($childAllocation['total_tuition_fee'] ?? 0), 2); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Non-Fiduciary Fund Breakdown for Child -->
                        <?php if (isset($childAllocData['non_fiduciary']) && !empty($childAllocData['non_fiduciary'])): ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Non-Fiduciary Fund Breakdown - <?php echo htmlspecialchars($child['dept_name']); ?></h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300">
                                            <th class="text-left py-4 px-4 font-bold text-gray-800">Item</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">%</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">50% Instructional</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Deductions</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Budget Allocation</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $childNonFiduciaryTotal = 0;
                                        $childNonFiduciaryTotalPercent = 0;
                                        $childNonFiduciaryTotalInstructional = 0;
                                        $childNonFiduciaryTotalDeductions = 0;
                                        foreach ($childAllocData['non_fiduciary'] as $key => $item): 
                                            if (!empty($item['item_name']) || !empty($item['instructional'])):
                                                $deductions = $item['deductions'] ?? [];
                                                $deductionTotal = 0;
                                                foreach ($deductions as $ded) {
                                                    $deductionTotal += floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                                                }
                                                $budgetAlloc = floatval(str_replace(['₱', ','], '', $item['budget_allocation'] ?? '0'));
                                                $childNonFiduciaryTotal += $budgetAlloc;
                                                $percentValue = floatval($item['percent'] ?? 0);
                                                $childNonFiduciaryTotalPercent += $percentValue;
                                                $instructionalValue = floatval(str_replace(['₱', ','], '', $item['instructional'] ?? '0'));
                                                $childNonFiduciaryTotalInstructional += $instructionalValue;
                                                $childNonFiduciaryTotalDeductions += $deductionTotal;
                                        ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="py-4 px-4 font-semibold text-gray-800"><?php echo htmlspecialchars($item['item_name'] ?? 'Item ' . $key); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-700"><?php 
                                                $percentValue = $item['percent'] ?? '0';
                                                // Ensure % symbol is always present
                                                echo htmlspecialchars(strpos($percentValue, '%') !== false ? $percentValue : $percentValue . '%'); 
                                            ?></td>
                                            <td class="py-4 px-4 text-right text-gray-700"><?php echo htmlspecialchars($item['instructional'] ?? '₱0.00'); ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <?php if (!empty($deductions)): ?>
                                                    <span class="font-semibold text-gray-800">₱<?php echo number_format($deductionTotal, 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-4 text-right font-bold <?php echo $budgetAlloc < 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($item['budget_allocation'] ?? '₱0.00'); ?>
                                            </td>
                                        </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                        <tr class="bg-gray-100 border-t-2 border-gray-400 font-bold">
                                            <td class="py-4 px-4 text-gray-900">Total Non-Fiduciary Fund</td>
                                            <td class="py-4 px-4 text-right text-gray-900"><?php echo number_format($childNonFiduciaryTotalPercent, 2); ?>%</td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($childNonFiduciaryTotalInstructional, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($childNonFiduciaryTotalDeductions, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-maroon text-lg">₱<?php echo number_format($childNonFiduciaryTotal, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Fiduciary Fund Breakdown for Child -->
                        <?php if (isset($childAllocData['fiduciary']) && !empty($childAllocData['fiduciary'])): ?>
                        <?php
                        // Check if fiduciary is an array of items or a single object (for offices)
                        $childFiduciaryItems = $childAllocData['fiduciary'];
                        $childFiduciaryIsSingleObject = isset($childFiduciaryItems['budget_allocated']) || isset($childFiduciaryItems['total_budget']);
                        if ($childFiduciaryIsSingleObject) {
                            // Convert single object to array format
                            $childFiduciaryItems = [['item_name' => 'Budget Allocation', 'instructional' => $childFiduciaryItems['budget_allocated'] ?? $childFiduciaryItems['total_budget'] ?? '₱0.00', 'deductions' => $childFiduciaryItems['deductions'] ?? [], 'total_budget' => $childFiduciaryItems['total_budget'] ?? '₱0.00']];
                        }
                        ?>
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-6">Fiduciary Fund Breakdown - <?php echo htmlspecialchars($child['dept_name']); ?></h3>
                            <div class="overflow-x-auto">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-gray-50 to-gray-100 border-b-2 border-gray-300">
                                            <th class="text-left py-4 px-4 font-bold text-gray-800">Item</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Budget Collected</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Deductions</th>
                                            <th class="text-right py-4 px-4 font-bold text-gray-800">Total Budget</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $childFiduciaryTotal = 0;
                                        $childFiduciaryTotalBudget = 0;
                                        $childFiduciaryTotalDeductions = 0;
                                        foreach ($childFiduciaryItems as $key => $item): 
                                            if (!empty($item['item_name']) || !empty($item['instructional']) || !empty($item['budget_allocated'])):
                                                $deductions = $item['deductions'] ?? [];
                                                $deductionTotal = 0;
                                                foreach ($deductions as $ded) {
                                                    $deductionTotal += floatval(str_replace(['₱', ','], '', $ded['amount'] ?? '0'));
                                                }
                                                $totalBudgetItem = $item['total_budget'] ?? $item['budget_allocation'] ?? '₱0.00';
                                                $totalBudgetItemValue = floatval(str_replace(['₱', ','], '', $totalBudgetItem));
                                                $childFiduciaryTotal += $totalBudgetItemValue;
                                                $instructionalValue = floatval(str_replace(['₱', ','], '', $item['instructional'] ?? $item['budget_allocated'] ?? '0'));
                                                $childFiduciaryTotalBudget += $instructionalValue;
                                                $childFiduciaryTotalDeductions += $deductionTotal;
                                        ?>
                                        <tr class="border-b border-gray-200 hover:bg-gray-50 transition-colors">
                                            <td class="py-4 px-4 font-semibold text-gray-800"><?php echo htmlspecialchars($item['item_name'] ?? 'Item ' . $key); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-700"><?php echo htmlspecialchars($item['instructional'] ?? $item['budget_allocated'] ?? '₱0.00'); ?></td>
                                            <td class="py-4 px-4 text-right">
                                                <?php if (!empty($deductions)): ?>
                                                    <div class="space-y-1">
                                                        <?php foreach ($deductions as $ded): ?>
                                                            <div class="text-sm">
                                                                <span class="text-gray-700"><?php echo htmlspecialchars($ded['amount'] ?? '₱0.00'); ?></span>
                                                                <?php if (!empty($ded['remarks'])): ?>
                                                                    <span class="text-gray-500 ml-2">(<?php echo htmlspecialchars($ded['remarks']); ?>)</span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                        <div class="pt-2 border-t border-gray-200 mt-2">
                                                            <span class="font-semibold text-gray-800">Total: ₱<?php echo number_format($deductionTotal, 2); ?></span>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-4 px-4 text-right font-bold <?php echo $totalBudgetItemValue < 0 ? 'text-red-600' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($totalBudgetItem); ?>
                                            </td>
                                        </tr>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                        <tr class="bg-gray-100 border-t-2 border-gray-400 font-bold">
                                            <td class="py-4 px-4 text-gray-900">Total Fiduciary Fund</td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($childFiduciaryTotalBudget, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-gray-900">₱<?php echo number_format($childFiduciaryTotalDeductions, 2); ?></td>
                                            <td class="py-4 px-4 text-right text-maroon text-lg">₱<?php echo number_format($childFiduciaryTotal, 2); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Overall Total for Child -->
                        <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 rounded-2xl shadow-lg p-6 text-white">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h3 class="text-xl font-bold">Overall Total Budget - <?php echo htmlspecialchars($child['dept_name']); ?></h3>
                                    <p class="text-red-100">Fiscal Year <?php echo htmlspecialchars($fiscalYear); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-4xl font-bold">₱<?php echo number_format(floatval($childAllocation['overall_total'] ?? 0), 2); ?></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons for Child Department -->
                        <div class="flex justify-end gap-2 mt-4">
                            <button onclick="showChildAllocationHistory(<?php echo $child['id']; ?>, '<?php echo htmlspecialchars($child['dept_name'], ENT_QUOTES); ?>')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center gap-2 border border-gray-300">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                History
                            </button>
                            <button onclick="downloadChildAllocationPDF(<?php echo $child['id']; ?>, <?php echo $fiscalYear; ?>)" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Download PDF
                            </button>
                        </div>
                        
                        <?php else: ?>
                        <!-- No allocation for this child department -->
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($child['dept_name']); ?></h2>
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Sub-Dept</span>
                            </div>
                            <p class="text-gray-500 italic">No budget allocation has been set for this department/office yet.</p>
                            <div class="flex justify-end mt-4">
                                <button onclick="showChildAllocationHistory(<?php echo $child['id']; ?>, '<?php echo htmlspecialchars($child['dept_name'], ENT_QUOTES); ?>')" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center gap-2 border border-gray-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    History
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php endforeach; ?>
                    </div><!-- End Sub-Departments Tab Panel -->
                    <?php endif; ?>
                <?php else: ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12 text-center">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">No Budget Allocation Found</h3>
                        <p class="text-gray-500">Your department's budget allocation has not been set up yet. Please contact the Budget Office.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Sidebar toggle functionality
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

        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown) return;
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
        
        // Tab switching functionality
        function switchTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Show selected panel
            const selectedPanel = document.getElementById('panel-' + tabName);
            if (selectedPanel) {
                selectedPanel.classList.remove('hidden');
            }
            
            // Update tab button styles
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
                btn.classList.add('border-transparent', 'text-gray-500', 'font-medium');
            });
            
            // Highlight selected tab
            const selectedTab = document.getElementById('tab-' + tabName);
            if (selectedTab) {
                selectedTab.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
                selectedTab.classList.add('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
            }
        }
        
        function downloadAllocationPDF(allocationId) {
            if (!allocationId) {
                alert('Error generating PDF. Please try again.');
                return;
            }
            
            // Use the same API endpoint as generate_allocation_pdf.php
            // Get department name for filename
            fetch(`../api/get_allocation_breakdown_by_id.php?id=${allocationId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const deptName = data.data.department_name || 'Allocation';
                        const date = new Date(data.data.created_at);
                        const dateStr = date.toISOString().split('T')[0];
                        const filename = `Budget_Allocation_${deptName.replace(/\s+/g, '_')}_${dateStr}.pdf`;
                        
                        // Open in new window for printing/downloading
                        const printWindow = window.open(`../api/generate_allocation_pdf.php?id=${allocationId}`, '_blank');
                        
                        // After window loads, try to trigger download
                        if (printWindow) {
                            printWindow.onload = function() {
                                // The print dialog will appear, user can save as PDF
                                // Browser will handle the download with the filename
                            };
                        }
                    } else {
                        // Fallback if we can't get department name
                        window.open(`../api/generate_allocation_pdf.php?id=${allocationId}`, '_blank');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    window.open(`../api/generate_allocation_pdf.php?id=${allocationId}`, '_blank');
                });
        }
        
        function changeFiscalYear(year) {
            // Reload the page with the selected fiscal year
            const url = new URL(window.location.href);
            url.searchParams.set('fiscal_year', year);
            window.location.href = url.toString();
        }
        
        function toggleHistoryModal() {
            const modal = document.getElementById('historyModal');
            if (modal) {
                modal.classList.toggle('hidden');
            }
        }
        
        // Function to download child department allocation PDF
        function downloadChildAllocationPDF(departmentId, fiscalYear) {
            if (!departmentId) {
                alert('Error: Department ID not found.');
                return;
            }
            
            // Fetch the allocation ID for this department and fiscal year
            fetch(`../api/get_budget_breakdown.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.id) {
                        window.open(`../api/generate_allocation_pdf.php?id=${data.data.id}`, '_blank');
                    } else {
                        alert('No allocation found for this department.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading allocation data.');
                });
        }
        
        // Function to show child department allocation history
        function showChildAllocationHistory(departmentId, departmentName) {
            // Create or show history modal
            let modal = document.getElementById('childHistoryModal');
            if (!modal) {
                const modalHTML = `
                    <div id="childHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden flex flex-col">
                            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-6 py-4 flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h3 class="text-xl font-bold text-white">Allocation History</h3>
                                    <span id="childHistoryDeptName" class="text-gray-200 text-sm"></span>
                                </div>
                                <button onclick="closeChildHistoryModal()" class="text-white hover:text-red-200 transition-colors p-2">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div class="flex-1 overflow-y-auto p-6">
                                <div id="childHistoryBody" class="space-y-4"></div>
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                modal = document.getElementById('childHistoryModal');
            } else {
                modal.classList.remove('hidden');
            }
            
            // Update modal title
            document.getElementById('childHistoryDeptName').textContent = departmentName;
            
            // Load history
            const historyBody = document.getElementById('childHistoryBody');
            historyBody.innerHTML = '<div class="text-center py-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-maroon mx-auto"></div><p class="text-gray-500 mt-2">Loading history...</p></div>';
            
            fetch(`../api/get_allocation_history.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        let html = '';
                        data.data.forEach(entry => {
                            const date = new Date(entry.created_at);
                            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                            html += `
                                <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-semibold text-gray-800">${departmentName}</span>
                                        <span class="text-sm text-gray-500">${formattedDate}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <span class="text-sm text-gray-600">Fiscal Year: ${entry.fiscal_year}</span>
                                            <span class="ml-4 text-sm font-semibold text-maroon">₱${parseFloat(entry.overall_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="viewAllocation(${entry.id})" class="px-3 py-1 bg-maroon text-white rounded text-xs hover:bg-maroon-dark transition-colors">View</button>
                                            <button onclick="downloadAllocationPDF(${entry.id})" class="px-3 py-1 bg-blue-600 text-white rounded text-xs hover:bg-blue-700 transition-colors">Download</button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        historyBody.innerHTML = html;
                    } else {
                        historyBody.innerHTML = '<div class="text-center py-8 text-gray-500">No history found for this department.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading history:', error);
                    historyBody.innerHTML = '<div class="text-center py-8 text-red-500">Error loading history. Please try again.</div>';
                });
        }
        
        function closeChildHistoryModal() {
            const modal = document.getElementById('childHistoryModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function viewAllocation(allocationId) {
            fetch(`../api/get_allocation_breakdown_by_id.php?id=${allocationId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        displayBreakdownModal(data.data);
                    } else {
                        alert('Error loading breakdown: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading breakdown:', error);
                    alert('Error loading breakdown: ' + (error.message || 'Please check the console for details'));
                });
        }
        
        function displayBreakdownModal(allocationData) {
            const modal = document.getElementById('breakdownViewModal');
            if (!modal) {
                const modalHTML = `
                    <div id="breakdownViewModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6 flex justify-between items-center">
                                <h2 class="text-2xl font-bold">Budget Allocation Breakdown</h2>
                                <button onclick="closeBreakdownModal()" class="text-white hover:text-red-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="breakdownViewContent" class="flex-1 overflow-y-auto p-6">
                                <!-- Content will be inserted here -->
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            }
            
            const content = document.getElementById('breakdownViewContent');
            if (!content) return;
            
            // Determine if this is an office (fiduciary_type === 'Fiduciary')
            const isOffice = allocationData.fiduciary_type === 'Fiduciary';
            
            let html = `
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Allocation Details</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div>
                            <p class="text-sm text-gray-600">${isOffice ? 'Office' : 'Department'}</p>
                            <p class="text-lg font-semibold">${allocationData.department_name || 'N/A'}</p>
                        </div>
            `;
            
            // Show department-specific fields only for departments
            if (!isOffice) {
                html += `
                        <div style="display: none;">
                            <p class="text-sm text-gray-600">Number of Students</p>
                            <p class="text-lg font-semibold">${allocationData.num_students || 0}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Total Tuition Fee</p>
                            <p class="text-lg font-semibold">₱${parseFloat(allocationData.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">50% Instructional</p>
                            <p class="text-lg font-semibold">₱${parseFloat(allocationData.instructional_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                `;
            } else {
                // For offices, show budget allocated instead
                let allocData;
                try {
                    allocData = typeof allocationData.allocation_data === 'string' 
                        ? JSON.parse(allocationData.allocation_data) 
                        : (allocationData.allocation_data || {});
                    if (!allocData || typeof allocData !== 'object') {
                        allocData = {};
                    }
                } catch (e) {
                    console.error('Error parsing allocation_data:', e);
                    allocData = {};
                }
                const budgetAllocated = allocData?.budget_allocated || allocationData.budget_allocated || '₱0.00';
                html += `
                        <div>
                            <p class="text-sm text-gray-600">Budget Allocated</p>
                            <p class="text-lg font-semibold">${typeof budgetAllocated === 'string' ? budgetAllocated : '₱' + parseFloat(budgetAllocated || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                `;
            }
            
            html += `
                        <div>
                            <p class="text-sm text-gray-600">Overall Total</p>
                            <p class="text-lg font-semibold text-maroon">₱${parseFloat(allocationData.overall_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Fiscal Year</p>
                            <p class="text-lg font-semibold">${allocationData.fiscal_year || 'N/A'}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Created</p>
                            <p class="text-lg font-semibold">${new Date(allocationData.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                        </div>
                    </div>
                </div>
            `;
            
            if (allocationData.allocation_data) {
                let allocData;
                try {
                    allocData = typeof allocationData.allocation_data === 'string' 
                        ? JSON.parse(allocationData.allocation_data) 
                        : allocationData.allocation_data;
                    // Ensure allocData is an object
                    if (!allocData || typeof allocData !== 'object') {
                        allocData = {};
                    }
                } catch (e) {
                    console.error('Error parsing allocation_data:', e);
                    allocData = {};
                }
                
                // Non-Fiduciary Fund (only for departments, not offices)
                if (!isOffice && allocData.non_fiduciary) {
                    html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Non-Fiduciary Fund</h4>';
                    html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                    html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Category</th><th class="border p-3 text-right font-bold">Percent</th><th class="border p-3 text-right font-bold">50%</th><th class="border p-3 text-right font-bold">Deductions</th><th class="border p-3 text-right font-bold">Budget Allocation</th></tr></thead><tbody>';
                    
                    const categories = {
                        facultyStaff: 'Faculty and Staff Development',
                        curriculum: 'Curriculum Development',
                        student: 'Student Development',
                        facilities: 'Facilities Development'
                    };
                    
                    for (const [key, name] of Object.entries(categories)) {
                        if (allocData.non_fiduciary[key]) {
                            const item = allocData.non_fiduciary[key];
                            const deductions = item.deductions || [];
                            let deductionTotal = 0;
                            let deductionDetails = '';
                            deductions.forEach(ded => {
                                const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                deductionTotal += amount;
                                deductionDetails += `<div class="text-xs py-1 border-b border-gray-100">${ded.amount || '₱0.00'} ${ded.remarks ? '(' + ded.remarks + ')' : ''}</div>`;
                            });
                            
                            html += `<tr><td class="border p-3 font-semibold">${name}</td>`;
                            html += `<td class="border p-3 text-right">${item.percent || '0%'}</td>`;
                            html += `<td class="border p-3 text-right">${item.instructional || '₱0.00'}</td>`;
                            html += `<td class="border p-3 text-right"><div>${deductionDetails || '<span class="text-gray-400">-</span>'}</div>${deductionTotal > 0 ? '<div class="text-xs font-semibold mt-1 pt-1 border-t">Total: ₱' + deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>' : ''}</td>`;
                            const budgetAllocStr = (item.budget_allocation && typeof item.budget_allocation === 'string') ? item.budget_allocation : (item.budget_allocation ? String(item.budget_allocation) : '0');
                            const budgetAlloc = parseFloat(budgetAllocStr.replace(/[₱,]/g, '') || 0);
                            html += `<td class="border p-3 text-right font-semibold ${budgetAlloc < 0 ? 'text-red-600' : ''}">${item.budget_allocation || '₱0.00'}</td></tr>`;
                        }
                    }
                    html += '</tbody></table></div></div>';
                }
                
                // Fiduciary Fund
                if (allocData.fiduciary) {
                    html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Fiduciary Fund</h4>';
                    
                    if (isOffice) {
                        // Office format: Show allocated budget, deductions, and total budget
                        const fiduciary = allocData.fiduciary;
                        const deductions = fiduciary.deductions || [];
                        const totalBudget = fiduciary.total_budget || '₱0.00';
                        
                        // Get allocated budget from allocationData.budget_allocated (from database field)
                        const allocatedBudgetValue = allocationData.budget_allocated || 0;
                        const allocatedBudgetFormatted = typeof allocatedBudgetValue === 'string' 
                            ? allocatedBudgetValue 
                            : '₱' + parseFloat(allocatedBudgetValue || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                        
                        // Calculate deduction total
                        let deductionTotal = 0;
                        let deductionDetails = '';
                        deductions.forEach(ded => {
                            const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                            const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                            deductionTotal += amount;
                            deductionDetails += `<div class="text-xs py-1 border-b border-gray-100">${ded.amount || '₱0.00'} ${ded.remarks ? '(' + ded.remarks + ')' : ''}</div>`;
                        });
                        
                        html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                        html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Description</th><th class="border p-3 text-right font-bold">Amount</th></tr></thead>';
                        html += '<tbody>';
                        html += `<tr><td class="border p-3 font-semibold">Allocated Budget</td><td class="border p-3 text-right font-semibold">${allocatedBudgetFormatted}</td></tr>`;
                        html += `<tr><td class="border p-3 font-semibold">Total Deductions</td><td class="border p-3 text-right">${deductionTotal > 0 ? '₱' + deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '₱0.00'}</td></tr>`;
                        html += `<tr class="bg-gray-50"><td class="border p-3 font-bold">Total Budget</td><td class="border p-3 text-right font-bold text-maroon">${totalBudget}</td></tr>`;
                        html += '</tbody></table></div>';
                        
                        // Show deductions breakdown if any
                        if (deductions.length > 0) {
                            html += '<div class="mt-4"><h5 class="text-sm font-semibold text-gray-700 mb-2">Deductions Breakdown:</h5>';
                            html += '<div class="bg-gray-50 rounded-lg p-3 border border-gray-200">';
                            html += deductionDetails || '<span class="text-gray-400">No deductions</span>';
                            html += '</div></div>';
                        }
                        html += '</div>';
                    } else {
                        // Department format: Show rows with items
                        html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                        html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Item</th><th class="border p-3 text-right font-bold">50%</th><th class="border p-3 text-right font-bold">Deductions</th><th class="border p-3 text-right font-bold">Budget Allocation</th></tr></thead>';
                        html += '<tbody>';
                        
                        // Handle both array and object formats
                        const fiduciaryItems = Array.isArray(allocData.fiduciary) 
                            ? allocData.fiduciary 
                            : Object.entries(allocData.fiduciary).map(([key, item]) => item);
                        
                        fiduciaryItems.forEach((item, index) => {
                            // Skip non-item keys like 'deductions' or 'total_budget' for offices
                            if (typeof item === 'object' && item !== null && (item.item_name || item.instructional)) {
                                const deductions = item.deductions || [];
                                let deductionTotal = 0;
                                let deductionDetails = '';
                                deductions.forEach(ded => {
                                    const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                    const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                    deductionTotal += amount;
                                    deductionDetails += `<div class="text-xs py-1 border-b border-gray-100">${ded.amount || '₱0.00'} ${ded.remarks ? '(' + ded.remarks + ')' : ''}</div>`;
                                });
                                
                                html += `<tr><td class="border p-3 font-semibold">${item.item_name || 'Item ' + (index + 1)}</td>`;
                                html += `<td class="border p-3 text-right">${item.instructional || '₱0.00'}</td>`;
                                html += `<td class="border p-3 text-right"><div>${deductionDetails || '<span class="text-gray-400">-</span>'}</div>${deductionTotal > 0 ? '<div class="text-xs font-semibold mt-1 pt-1 border-t">Total: ₱' + deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>' : ''}</td>`;
                                const budgetAllocStr = (item.budget_allocation && typeof item.budget_allocation === 'string') ? item.budget_allocation : (item.total_budget && typeof item.total_budget === 'string') ? item.total_budget : (item.budget_allocation ? String(item.budget_allocation) : (item.total_budget ? String(item.total_budget) : '0'));
                                const budgetAlloc = parseFloat(budgetAllocStr.replace(/[₱,]/g, '') || 0);
                                html += `<td class="border p-3 text-right font-semibold ${budgetAlloc < 0 ? 'text-red-600' : ''}">${item.budget_allocation || item.total_budget || '₱0.00'}</td></tr>`;
                            }
                        });
                        html += '</tbody></table></div></div>';
                    }
                }
                
                // Calculate and display Deduction Breakdown by Type
                const deductionBreakdown = {
                    'COS': 0,
                    'Honoraria Overload': 0,
                    'Part-time': 0,
                    'Water': 0,
                    'Electricity': 0,
                    'Security': 0
                };
                
                // Collect deductions from non-fiduciary categories (for departments only)
                if (!isOffice && allocData.non_fiduciary) {
                    const categories = {
                        facultyStaff: 'Faculty and Staff Development',
                        curriculum: 'Curriculum Development',
                        student: 'Student Development',
                        facilities: 'Facilities Development'
                    };
                    
                    for (const [key, name] of Object.entries(categories)) {
                        if (allocData.non_fiduciary[key]) {
                            const item = allocData.non_fiduciary[key];
                            const deductions = item.deductions || [];
                            deductions.forEach(ded => {
                                const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                const remarks = ded.remarks ? ded.remarks.trim() : '';
                                if (amount > 0 && remarks && deductionBreakdown.hasOwnProperty(remarks)) {
                                    deductionBreakdown[remarks] += amount;
                                }
                            });
                        }
                    }
                }
                
                // Collect deductions from fiduciary items
                if (allocData.fiduciary) {
                    if (isOffice) {
                        // For offices: get deductions from fiduciary object
                        const fiduciary = allocData.fiduciary;
                        const deductions = fiduciary.deductions || [];
                        deductions.forEach(ded => {
                            const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                            const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                            const remarks = ded.remarks ? ded.remarks.trim() : '';
                            if (amount > 0 && remarks && deductionBreakdown.hasOwnProperty(remarks)) {
                                deductionBreakdown[remarks] += amount;
                            }
                        });
                    } else {
                        // For departments: get deductions from all fiduciary items
                        const fiduciaryItems = Array.isArray(allocData.fiduciary) 
                            ? allocData.fiduciary 
                            : Object.entries(allocData.fiduciary).map(([key, item]) => item);
                        
                        fiduciaryItems.forEach((item) => {
                            if (typeof item === 'object' && item !== null && (item.item_name || item.instructional)) {
                                const deductions = item.deductions || [];
                                deductions.forEach(ded => {
                                    const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                    const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                    const remarks = ded.remarks ? ded.remarks.trim() : '';
                                    if (amount > 0 && remarks && deductionBreakdown.hasOwnProperty(remarks)) {
                                        deductionBreakdown[remarks] += amount;
                                    }
                                });
                            }
                        });
                    }
                }
                
                // Display the deduction breakdown
                let grandTotalDeductions = 0;
                let hasAnyDeductions = false;
                
                const deductionTypes = [
                    { key: 'COS', label: 'COS' },
                    { key: 'Honoraria Overload', label: 'Overload' },
                    { key: 'Part-time', label: 'Part-time' },
                    { key: 'Water', label: 'Water' },
                    { key: 'Electricity', label: 'Electricity' },
                    { key: 'Security', label: 'Security' }
                ];
                
                deductionTypes.forEach(type => {
                    const amount = deductionBreakdown[type.key] || 0;
                    if (amount > 0) {
                        hasAnyDeductions = true;
                        grandTotalDeductions += amount;
                    }
                });
                
                if (hasAnyDeductions) {
                    html += '<div class="mt-6 pt-6 border-t-2 border-gray-300"><h4 class="text-md font-semibold text-gray-800 mb-3">Total Deduction Breakdown by Type</h4>';
                    html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                    html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Deduction Type</th><th class="border p-3 text-right font-bold">Total Amount</th></tr></thead>';
                    html += '<tbody>';
                    
                    deductionTypes.forEach(type => {
                        const amount = deductionBreakdown[type.key] || 0;
                        if (amount > 0) {
                            html += `<tr><td class="border p-3 font-semibold">${type.label}</td>`;
                            html += `<td class="border p-3 text-right">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>`;
                        }
                    });
                    
                    html += '</tbody>';
                    html += '<tfoot class="border-t-2 border-gray-400"><tr class="font-bold">';
                    html += '<td class="border p-3">Grand Total Deductions</td>';
                    html += `<td class="border p-3 text-right">₱${grandTotalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;
                    html += '</tr></tfoot></table></div></div>';
                }
            }
            
            content.innerHTML = html;
            document.getElementById('breakdownViewModal').classList.remove('hidden');
        }
        
        function closeBreakdownModal() {
            const modal = document.getElementById('breakdownViewModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
    </script>
    
    <!-- History Modal -->
    <?php if (!empty($allocationHistory)): ?>
    <div id="historyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0 bg-black/40" onclick="toggleHistoryModal()" style="cursor: pointer;"></div>
        <div class="relative w-full max-w-5xl rounded-2xl bg-white shadow-2xl border border-gray-200 overflow-hidden flex flex-col" style="max-height: 90vh;">
            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-2xl font-bold">Allocation History</h2>
                    <button onclick="toggleHistoryModal()" class="text-white hover:text-red-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
                </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div class="space-y-4">
                    <?php foreach ($allocationHistory as $history): 
                        $historyDate = new DateTime($history['created_at']);
                        $formattedDate = $historyDate->format('F j, Y g:i A');
                    ?>
                        <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                            <div class="flex items-center justify-between mb-3">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($departmentName); ?></h3>
                                    <p class="text-sm text-gray-500"><?php echo $formattedDate; ?></p>
                                    <p class="text-xs text-gray-400 mt-1">Fiscal Year: <?php echo htmlspecialchars($history['fiscal_year'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="flex items-center gap-2">
                                <?php if (isset($history['id'])): ?>
                                        <button 
                                            onclick="viewAllocation(<?php echo $history['id']; ?>)" 
                                            class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors font-semibold text-sm flex items-center gap-2"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </button>
                                        <button 
                                            onclick="downloadAllocationPDF(<?php echo $history['id']; ?>)" 
                                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm flex items-center gap-2"
                                        >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                            PDF
                                    </button>
                                <?php endif; ?>
                                </div>
                            </div>
                            <div class="grid grid-cols-3 gap-4 text-sm">
                                <div style="display: none;">
                                    <p class="text-gray-500">Students</p>
                                    <p class="font-semibold text-gray-900"><?php echo number_format($history['num_students'] ?? 0); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Total Tuition</p>
                                    <p class="font-semibold text-gray-900">₱<?php echo number_format(floatval($history['total_tuition_fee'] ?? 0), 2); ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500">Overall Total</p>
                                    <p class="font-semibold text-maroon text-lg">₱<?php echo number_format(floatval($history['overall_total'] ?? 0), 2); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" onload="console.log('jsPDF loaded:', typeof window.jspdf)"></script>

</body>
</html>
