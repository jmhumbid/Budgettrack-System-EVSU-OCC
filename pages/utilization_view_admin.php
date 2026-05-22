<?php
session_start();

// Allow budget, school_admin, and users from Admin department
$allowedRoles = ['budget', 'school_admin'];
$isAdminDepartment = false;

if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../components/profile_avatar.php';

// Check if user is from Admin department/office
if (isset($_SESSION['department_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
        $stmt->execute([$_SESSION['department_id']]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dept && stripos($dept['dept_name'], 'admin') !== false) {
            $isAdminDepartment = true;
        }
    } catch (Exception $e) {
        // Continue with normal access check
    }
}

// Check access: must be in allowed roles OR from Admin department
if (!in_array($_SESSION['user_role'], $allowedRoles) && !$isAdminDepartment) {
    header('Location: ../login.php');
    exit;
}

$activeSidebar = 'utilization_view_admin';

$username = $_SESSION['user_name'] ?? 'Administrator';
$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'];

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId ?? 0, 10);
$unreadCount = $notification->getUnreadCount($userId ?? 0);

// Separate departments and offices
$departmentNames = ['Computer studies', 'Education', 'Industrial Technology', 'Engineering', 'Hospitality Management'];
$departments = [];
$offices = [];

try {
    $db = getDB();
    
    // Admin department users can see all departments (not restricted)
    // Budget and school_admin can also see all departments
    $stmt = $db->query("SELECT id, dept_name, dept_code FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $allDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allDepartments as $dept) {
        $deptName = $dept['dept_name'];
        $isDepartment = false;
        foreach ($departmentNames as $deptNameCheck) {
            if (stripos($deptName, $deptNameCheck) !== false) {
                $isDepartment = true;
                break;
            }
        }
        if ($isDepartment) {
            $departments[] = $dept;
        } else {
            $offices[] = $dept;
        }
    }
} catch (PDOException $e) {
    $departments = [];
    $offices = [];
}

$fiscalYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Utilization View</title>
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

        body.sidebar-collapsed #sidebar .sidebar-toggle-icon {
            transform: rotate(180deg);
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <?php if ($isAdminDepartment): ?>
            <!-- Department Sidebar Wrapper for Admin Department Users -->
            <aside id="sidebar" class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto w-64">
                <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
                        <p class="text-sm text-gray-600 sidebar-text">Utilization View</p>
                    </div>
                    <button id="sidebarToggle" type="button" class="p-2 rounded-lg hover:bg-gray-100 transition-colors" aria-label="Toggle sidebar">
                        <svg class="w-5 h-5 text-gray-600 sidebar-toggle-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5l-7 7 7 7M20 5l-7 7 7 7"></path>
                        </svg>
                    </button>
                </div>
                <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
            </aside>
        <?php else: ?>
            <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
        <?php endif; ?>

        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Utilization View</h1>
                                    <p class="text-red-100 text-sm">View budget utilization data for all departments and offices</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 backdrop-blur-sm text-white border border-white border-opacity-30">
                                    <span class="w-2 h-2 bg-green-300 rounded-full mr-2 animate-pulse"></span>
                                    <?php echo $isAdminDepartment ? 'Admin Office' : 'Budget Office'; ?>
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
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <!-- Selection Section -->
                    <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Department Selection -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Select Department
                                </label>
                                <select id="departmentSelect" onchange="loadUtilizationData()" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-medium">
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['id']); ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Office Selection -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Select Office
                                </label>
                                <select id="officeSelect" onchange="loadUtilizationData()" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-medium">
                                    <option value="">-- Select Office --</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo htmlspecialchars($office['id']); ?>"><?php echo htmlspecialchars($office['dept_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Fiscal Year Selection -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Fiscal Year
                                </label>
                                <select id="fiscalYearSelect" onchange="loadUtilizationData()" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-medium">
                                    <?php
                                    $currentYear = (int)date('Y');
                                    for ($y = $currentYear + 1; $y >= $currentYear - 3; $y--) {
                                        $sel = ($y === $currentYear) ? 'selected' : '';
                                        echo "<option value=\"$y\" $sel>$y</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div id="selectedDisplay" class="mt-3 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-sm font-semibold text-maroon hidden" id="selectedInfo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-gray-600">Viewing: </span><span id="selectedName" class="text-maroon font-bold"></span>
                            </div>
                            <div class="flex items-center gap-3">
                                <button onclick="loadUtilizationData()" id="refreshBtn" class="hidden px-4 py-2 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-lg hover:from-gray-600 hover:to-gray-700 transition-all font-semibold text-sm flex items-center gap-2 shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Refresh
                                </button>
                                <button onclick="addToDownloadQueue()" id="addToQueueBtn" class="hidden px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all font-semibold text-sm flex items-center gap-2 shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Add to Download Queue
                                </button>
                                <button onclick="showDownloadQueue()" id="queueBtn" class="hidden px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all font-semibold text-sm flex items-center gap-2 shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    Queue (<span id="queueCount">0</span>)
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Utilization Data Container -->
                    <div id="utilizationContainer" class="hidden">
                        <div class="p-6">
                            <!-- Download PDF Button -->
                            <div class="flex justify-end mb-4">
                                <button onclick="downloadUtilizationPDF()" id="downloadPdfBtn" class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg hover:shadow-xl flex items-center gap-2 transform hover:scale-105">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download PDF
                                </button>
                            </div>
                            <div class="overflow-x-auto rounded-xl border-2 border-gray-200 shadow-lg">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-gradient-to-r from-maroon via-red-700 to-red-800">
                                            <th class="border-b-2 border-red-900 py-4 px-6 text-left font-bold text-white uppercase text-sm tracking-wider">Expense Category</th>
                                            <th class="border-b-2 border-red-900 py-4 px-6 text-left font-bold text-white uppercase text-sm tracking-wider">Account Code</th>
                                            <th class="border-b-2 border-red-900 py-4 px-6 text-right font-bold text-white uppercase text-sm tracking-wider">Allocated Budget</th>
                                            <th class="border-b-2 border-red-900 py-4 px-6 text-right font-bold text-white uppercase text-sm tracking-wider">Deductions</th>
                                            <th class="border-b-2 border-red-900 py-4 px-6 text-right font-bold text-white uppercase text-sm tracking-wider">Total Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody id="utilizationTableBody" class="divide-y divide-gray-100">
                                        <!-- Data will be loaded here -->
                                    </tbody>
                                    <tfoot class="bg-gradient-to-r from-gray-100 to-gray-50">
                                        <tr class="font-bold">
                                            <td class="py-4 px-6 text-gray-800 uppercase text-sm">Grand Total</td>
                                            <td class="py-4 px-6"></td>
                                            <td id="totalAllocated" class="py-4 px-6 text-right text-gray-800 text-lg">₱0.00</td>
                                            <td id="totalDeductions" class="py-4 px-6 text-right text-red-600 text-lg">₱0.00</td>
                                            <td id="totalBalance" class="py-4 px-6 text-right text-green-600 text-lg">₱0.00</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="p-8">
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-white p-12 text-center">
                            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">No Department/Office Selected</h3>
                            <p class="text-gray-500 max-w-md mx-auto">Select a department or office above to view their budget utilization data.</p>
                        </div>
                    </div>

                    <!-- No Data State -->
                    <div id="noDataState" class="p-8 hidden">
                        <div class="rounded-2xl border-2 border-dashed border-orange-300 bg-gradient-to-br from-orange-50 to-white p-12 text-center">
                            <div class="mx-auto w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">No Utilization Data</h3>
                            <p class="text-gray-500 max-w-md mx-auto">No budget utilization entries found for the selected department/office.</p>
                        </div>
                    </div>
                </div>
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

            if (mainContent) {
                setTimeout(() => mainContent.classList.add('sidebar-ready'), 100);
            }

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    body.classList.toggle('sidebar-collapsed');
                    localStorage.setItem(storageKey, body.classList.contains('sidebar-collapsed'));
                });
            }

            // Budget workflow dropdown
            const budgetDropdown = document.getElementById('budgetDropdown');
            if (budgetDropdown) {
                const dropdownBtn = budgetDropdown.querySelector('button');
                const dropdownMenu = document.getElementById('budgetWorkflowMenu');
                if (dropdownBtn && dropdownMenu) {
                    dropdownBtn.addEventListener('click', function() {
                        dropdownMenu.classList.toggle('hidden');
                        const arrow = dropdownBtn.querySelector('.sidebar-dropdown-arrow');
                        if (arrow) arrow.classList.toggle('rotate-180');
                    });
                }
            }

            // Restore last selection from localStorage
            setTimeout(() => {
                restoreSelection();
            }, 100);
        });

        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) dropdown.classList.toggle('hidden');
        }

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        // Format currency
        function formatCurrency(amount) {
            const num = parseFloat(amount) || 0;
            return '₱' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Store current entries for PDF generation
        let currentUtilizationEntries = [];
        let currentSelectedName = '';
        let currentFiscalYear = null;
        
        // Download queue for multiple departments/offices
        let downloadQueue = [];

        // LocalStorage key for saving selection
        const UTILIZATION_VIEW_STORAGE_KEY = 'utilizationViewSelection';

        // Save selection to localStorage
        function saveSelection(type, id, name) {
            localStorage.setItem(UTILIZATION_VIEW_STORAGE_KEY, JSON.stringify({
                type: type,
                id: id,
                name: name
            }));
        }

        // Load saved selection from localStorage
        function loadSavedSelection() {
            const saved = localStorage.getItem(UTILIZATION_VIEW_STORAGE_KEY);
            if (saved) {
                try {
                    return JSON.parse(saved);
                } catch (e) {
                    return null;
                }
            }
            return null;
        }

        // Restore selection on page load
        function restoreSelection() {
            const saved = loadSavedSelection();
            if (!saved) return;

            const deptSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');

            if (saved.type === 'department' && deptSelect) {
                // Check if the option exists
                for (let i = 0; i < deptSelect.options.length; i++) {
                    if (deptSelect.options[i].value === saved.id) {
                        deptSelect.value = saved.id;
                        officeSelect.value = '';
                        loadUtilizationDataWithId(saved.id, saved.name);
                        return;
                    }
                }
            } else if (saved.type === 'office' && officeSelect) {
                for (let i = 0; i < officeSelect.options.length; i++) {
                    if (officeSelect.options[i].value === saved.id) {
                        officeSelect.value = saved.id;
                        deptSelect.value = '';
                        loadUtilizationDataWithId(saved.id, saved.name);
                        return;
                    }
                }
            }
        }

        // Load utilization data with specific ID (used for restore)
        function loadUtilizationDataWithId(departmentId, selectedName) {
            const emptyState = document.getElementById('emptyState');
            const noDataState = document.getElementById('noDataState');
            const utilizationContainer = document.getElementById('utilizationContainer');
            const selectedDisplay = document.getElementById('selectedDisplay');
            const selectedNameEl = document.getElementById('selectedName');
            const selectedInfo = document.getElementById('selectedInfo');
            const addToQueueBtn = document.getElementById('addToQueueBtn');
            const refreshBtn = document.getElementById('refreshBtn');

            emptyState.classList.add('hidden');
            noDataState.classList.add('hidden');
            selectedDisplay.classList.remove('hidden');
            selectedInfo.classList.remove('hidden');
            selectedNameEl.textContent = selectedName;

            const fiscalYear = document.getElementById('fiscalYearSelect').value;
            fetch(`../api/load_utilization_entries.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.entries && data.entries.length > 0) {
                        currentUtilizationEntries = data.entries;
                        currentSelectedName = selectedName;
                        currentFiscalYear = data.fiscal_year; // Store the fiscal year from response
                        renderUtilizationTable(data.entries);
                        utilizationContainer.classList.remove('hidden');
                        noDataState.classList.add('hidden');
                        addToQueueBtn.classList.remove('hidden');
                        refreshBtn.classList.remove('hidden');
                    } else {
                        currentUtilizationEntries = [];
                        currentSelectedName = '';
                        currentFiscalYear = null;
                        utilizationContainer.classList.add('hidden');
                        noDataState.classList.remove('hidden');
                        addToQueueBtn.classList.add('hidden');
                        refreshBtn.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading utilization data:', error);
                    utilizationContainer.classList.add('hidden');
                    noDataState.classList.remove('hidden');
                    addToQueueBtn.classList.add('hidden');
                    refreshBtn.classList.add('hidden');
                });
        }

        // Load utilization data
        function loadUtilizationData() {
            const deptSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            
            let departmentId = null;
            let selectedName = '';

            // Check which one was selected (clear the other)
            let selectionType = '';
            if (document.activeElement === deptSelect && deptSelect.value) {
                departmentId = deptSelect.value;
                selectedName = deptSelect.options[deptSelect.selectedIndex].text;
                officeSelect.value = '';
                selectionType = 'department';
            } else if (document.activeElement === officeSelect && officeSelect.value) {
                departmentId = officeSelect.value;
                selectedName = officeSelect.options[officeSelect.selectedIndex].text;
                deptSelect.value = '';
                selectionType = 'office';
            } else if (deptSelect.value) {
                departmentId = deptSelect.value;
                selectedName = deptSelect.options[deptSelect.selectedIndex].text;
                selectionType = 'department';
            } else if (officeSelect.value) {
                departmentId = officeSelect.value;
                selectedName = officeSelect.options[officeSelect.selectedIndex].text;
                selectionType = 'office';
            }

            // Save selection to localStorage
            if (departmentId && selectionType) {
                saveSelection(selectionType, departmentId, selectedName);
            }

            const emptyState = document.getElementById('emptyState');
            const noDataState = document.getElementById('noDataState');
            const utilizationContainer = document.getElementById('utilizationContainer');
            const selectedDisplay = document.getElementById('selectedDisplay');
            const selectedNameEl = document.getElementById('selectedName');
            const selectedInfo = document.getElementById('selectedInfo');
            const addToQueueBtn = document.getElementById('addToQueueBtn');
            const refreshBtn = document.getElementById('refreshBtn');

            if (!departmentId) {
                emptyState.classList.remove('hidden');
                noDataState.classList.add('hidden');
                utilizationContainer.classList.add('hidden');
                selectedDisplay.classList.add('hidden');
                selectedInfo.classList.add('hidden');
                addToQueueBtn.classList.add('hidden');
                refreshBtn.classList.add('hidden');
                return;
            }

            // Show loading
            emptyState.classList.add('hidden');
            noDataState.classList.add('hidden');
            selectedDisplay.classList.remove('hidden');
            selectedInfo.classList.remove('hidden');
            selectedNameEl.textContent = selectedName;

            const fiscalYear = document.getElementById('fiscalYearSelect').value;
            fetch(`../api/load_utilization_entries.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.entries && data.entries.length > 0) {
                        // Store entries for PDF generation
                        currentUtilizationEntries = data.entries;
                        currentSelectedName = selectedName;
                        currentFiscalYear = data.fiscal_year; // Store the fiscal year from response
                        
                        renderUtilizationTable(data.entries);
                        utilizationContainer.classList.remove('hidden');
                        noDataState.classList.add('hidden');
                        addToQueueBtn.classList.remove('hidden');
                        refreshBtn.classList.remove('hidden');
                    } else {
                        currentUtilizationEntries = [];
                        currentSelectedName = '';
                        currentFiscalYear = null;
                        utilizationContainer.classList.add('hidden');
                        noDataState.classList.remove('hidden');
                        addToQueueBtn.classList.add('hidden');
                        refreshBtn.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading utilization data:', error);
                    utilizationContainer.classList.add('hidden');
                    noDataState.classList.remove('hidden');
                    addToQueueBtn.classList.add('hidden');
                    refreshBtn.classList.add('hidden');
                });
        }

        // Add current selection to download queue
        function addToDownloadQueue() {
            const deptSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            
            let deptId, deptName;
            if (deptSelect.value) {
                deptId = deptSelect.value;
                deptName = deptSelect.options[deptSelect.selectedIndex].text;
            } else if (officeSelect.value) {
                deptId = officeSelect.value;
                deptName = officeSelect.options[officeSelect.selectedIndex].text;
            } else {
                alert('Please select a department or office first');
                return;
            }
            
            // Check if already in queue
            if (downloadQueue.find(item => item.id === deptId)) {
                alert(deptName + ' is already in the download queue');
                return;
            }
            
            downloadQueue.push({
                id: deptId,
                name: deptName,
                entries: currentUtilizationEntries,
                fiscalYear: currentFiscalYear
            });
            
            updateQueueDisplay();
            alert(deptName + ' added to download queue');
        }
        
        // Update queue button display
        function updateQueueDisplay() {
            const queueBtn = document.getElementById('queueBtn');
            const queueCount = document.getElementById('queueCount');
            
            queueCount.textContent = downloadQueue.length;
            
            if (downloadQueue.length > 0) {
                queueBtn.classList.remove('hidden');
            } else {
                queueBtn.classList.add('hidden');
            }
        }
        
        // Show download queue modal
        function showDownloadQueue() {
            if (downloadQueue.length === 0) {
                alert('Download queue is empty');
                return;
            }
            
            let html = `
                <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4" id="queueModal">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[80vh] overflow-hidden flex flex-col">
                        <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4 flex items-center justify-between">
                            <h2 class="text-2xl font-bold text-white">Download Queue</h2>
                            <button onclick="closeQueueModal()" class="text-white hover:text-purple-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="space-y-3">
            `;
            
            downloadQueue.forEach((item, index) => {
                html += `
                    <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex items-center gap-3">
                            <div class="bg-purple-100 text-purple-700 rounded-full w-8 h-8 flex items-center justify-center font-bold">
                                ${index + 1}
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">${item.name}</div>
                                <div class="text-sm text-gray-500">FY ${item.fiscalYear || 'N/A'} • ${item.entries.length} entries</div>
                            </div>
                        </div>
                        <button onclick="removeFromQueue(${index})" class="text-red-600 hover:text-red-800 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                `;
            });
            
            html += `
                            </div>
                        </div>
                        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center justify-between">
                            <button onclick="clearQueue()" class="px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors font-semibold">
                                Clear All
                            </button>
                            <div class="flex gap-3">
                                <button onclick="closeQueueModal()" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-semibold">
                                    Cancel
                                </button>
                                <button onclick="downloadAllPDFs()" class="px-6 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-lg hover:from-purple-700 hover:to-purple-800 transition-all font-semibold shadow-lg flex items-center gap-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download All (${downloadQueue.length})
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            document.body.insertAdjacentHTML('beforeend', html);
        }
        
        function closeQueueModal() {
            const modal = document.getElementById('queueModal');
            if (modal) modal.remove();
        }
        
        function removeFromQueue(index) {
            downloadQueue.splice(index, 1);
            updateQueueDisplay();
            closeQueueModal();
            if (downloadQueue.length > 0) {
                showDownloadQueue();
            }
        }
        
        function clearQueue() {
            if (confirm('Are you sure you want to clear the entire download queue?')) {
                downloadQueue = [];
                updateQueueDisplay();
                closeQueueModal();
            }
        }
        
        // Download all PDFs in queue
        async function downloadAllPDFs() {
            if (downloadQueue.length === 0) {
                alert('Download queue is empty');
                return;
            }
            
            closeQueueModal();
            
            // Show progress
            const loadingMsg = `Generating combined PDF for ${downloadQueue.length} department(s)/office(s)...`;
            alert(loadingMsg);
            
            try {
                // Extract department IDs from queue
                const departmentIds = downloadQueue.map(item => item.id);
                const fiscalYear = downloadQueue[0]?.fiscalYear || new Date().getFullYear();
                
                // Send POST request to combined PDF endpoint
                const response = await fetch('../api/download_combined_utilization_pdf.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        department_ids: departmentIds,
                        fiscal_year: fiscalYear
                    })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to generate combined PDF');
                }
                
                // Get the HTML content
                const htmlContent = await response.text();
                
                // Create a more descriptive filename
                const today = new Date();
                const dateStr = today.toISOString().split('T')[0];
                const filename = `Budget_Utilization_Summary_FY${fiscalYear}_${dateStr}`;
                
                // Open in new window for printing
                const printWindow = window.open('', '_blank');
                printWindow.document.write(htmlContent);
                printWindow.document.close();
                
                // Set the document title for better PDF filename suggestion
                printWindow.document.title = filename;
                
                // Wait for content to load, then trigger print dialog
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 1000);
                
                // Clear queue after successful generation
                downloadQueue = [];
                updateQueueDisplay();
                
            } catch (error) {
                console.error('Error generating combined PDF:', error);
                alert('Error generating combined PDF. Please try again.');
            }
        }
        
        // Download a single PDF from queue item (kept for potential future use)
        function downloadSinglePDF(item) {
            return new Promise((resolve) => {
                const url = `../api/download_utilization_pdf.php?id=${item.summaryId || ''}&department_id=${item.id}&fiscal_year=${item.fiscalYear || ''}`;
                const printWindow = window.open(url, '_blank');
                setTimeout(() => {
                    if (printWindow) {
                        printWindow.print();
                    }
                    resolve();
                }, 1000);
            });
        }

        function renderUtilizationTable(entries) {
            const tbody = document.getElementById('utilizationTableBody');
            tbody.innerHTML = '';

            let totalAllocated = 0;
            let totalDeductions = 0;
            let totalBalance = 0;

            entries.forEach((entry, index) => {
                const allocated = parseFloat(entry.allocated_budget) || 0;
                const deductions = parseFloat(entry.deductions) || 0;
                const balance = parseFloat(entry.total_balance) || 0;

                totalAllocated += allocated;
                totalDeductions += deductions;
                totalBalance += balance;

                const row = document.createElement('tr');
                row.className = index % 2 === 0 ? 'bg-white hover:bg-gray-50' : 'bg-gray-50 hover:bg-gray-100';
                row.innerHTML = `
                    <td class="py-4 px-6 text-gray-800 font-medium">${entry.expense_category || 'N/A'}</td>
                    <td class="py-4 px-6 text-gray-700">${entry.account_code || 'N/A'}</td>
                    <td class="py-4 px-6 text-right text-gray-800">${formatCurrency(allocated)}</td>
                    <td class="py-4 px-6 text-right text-red-600">${formatCurrency(deductions)}</td>
                    <td class="py-4 px-6 text-right ${balance >= 0 ? 'text-green-600' : 'text-red-600'}">${formatCurrency(balance)}</td>
                `;
                tbody.appendChild(row);
            });

            document.getElementById('totalAllocated').textContent = formatCurrency(totalAllocated);
            document.getElementById('totalDeductions').textContent = formatCurrency(totalDeductions);
            document.getElementById('totalBalance').textContent = formatCurrency(totalBalance);
        }

        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const profileDropdown = document.getElementById('profileDropdown');
            if (profileDropdown && !e.target.closest('[onclick="toggleProfileDropdown()"]') && !e.target.closest('#profileDropdown')) {
                profileDropdown.classList.add('hidden');
            }
        });

        // Download PDF function
        function downloadUtilizationPDF() {
            const button = document.getElementById('downloadPdfBtn');
            const originalText = button.innerHTML;
            
            button.disabled = true;
            button.innerHTML = `
                <svg class="w-5 h-5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                </svg>
                Generating PDF...
            `;

            try {
                if (typeof window.jspdf === 'undefined') {
                    alert('PDF library not loaded. Please refresh the page.');
                    button.disabled = false;
                    button.innerHTML = originalText;
                    return;
                }

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                const selectedName = currentSelectedName || 'Budget Utilization';
                const fiscalYear = <?php echo $fiscalYear; ?>;
                const currentDate = new Date().toLocaleDateString('en-PH', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });

                // Header background
                doc.setFillColor(128, 0, 0);
                doc.rect(0, 0, 210, 40, 'F');

                // Title
                doc.setTextColor(255, 255, 255);
                doc.setFontSize(20);
                doc.setFont('helvetica', 'bold');
                doc.text('Budget Utilization Report', 105, 18, { align: 'center' });

                doc.setFontSize(12);
                doc.setFont('helvetica', 'normal');
                doc.text(selectedName, 105, 28, { align: 'center' });
                doc.text(`Fiscal Year: ${fiscalYear}`, 105, 35, { align: 'center' });

                // Date generated
                doc.setTextColor(100, 100, 100);
                doc.setFontSize(10);
                doc.text(`Generated: ${currentDate}`, 14, 50);

                // Format currency for PDF (without peso sign that causes encoding issues)
                function formatCurrencyPDF(amount) {
                    const num = parseFloat(amount) || 0;
                    return 'PHP ' + num.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }

                // Prepare table data
                const tableData = currentUtilizationEntries.map(entry => [
                    entry.expense_category || 'N/A',
                    entry.account_code || 'N/A',
                    formatCurrencyPDF(entry.allocated_budget),
                    formatCurrencyPDF(entry.deductions),
                    formatCurrencyPDF(entry.total_balance)
                ]);

                // Calculate totals
                let totalAllocated = 0;
                let totalDeductions = 0;
                let totalBalance = 0;
                currentUtilizationEntries.forEach(entry => {
                    totalAllocated += parseFloat(entry.allocated_budget) || 0;
                    totalDeductions += parseFloat(entry.deductions) || 0;
                    totalBalance += parseFloat(entry.total_balance) || 0;
                });

                // Add totals row
                tableData.push([
                    'GRAND TOTAL',
                    '',
                    formatCurrencyPDF(totalAllocated),
                    formatCurrencyPDF(totalDeductions),
                    formatCurrencyPDF(totalBalance)
                ]);

                // Generate table using autoTable
                doc.autoTable({
                    startY: 55,
                    head: [['Expense Category', 'Account Code', 'Allocated Budget', 'Deductions', 'Total Balance']],
                    body: tableData,
                    theme: 'grid',
                    headStyles: {
                        fillColor: [128, 0, 0],
                        textColor: [255, 255, 255],
                        fontStyle: 'bold',
                        halign: 'center'
                    },
                    columnStyles: {
                        0: { halign: 'left' },
                        1: { halign: 'right' },
                        2: { halign: 'right' },
                        3: { halign: 'right' }
                    },
                    styles: {
                        fontSize: 10,
                        cellPadding: 4
                    },
                    alternateRowStyles: {
                        fillColor: [248, 248, 248]
                    },
                    didParseCell: function(data) {
                        // Style the last row (totals)
                        if (data.row.index === tableData.length - 1) {
                            data.cell.styles.fontStyle = 'bold';
                            data.cell.styles.fillColor = [240, 240, 240];
                        }
                    }
                });

                // Footer
                const pageHeight = doc.internal.pageSize.height;
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text('BudgetTrack - Budget Management System', 105, pageHeight - 10, { align: 'center' });

                // Save PDF
                const fileName = `Utilization_${selectedName.replace(/[^a-zA-Z0-9]/g, '_')}_${fiscalYear}.pdf`;
                doc.save(fileName);

                button.disabled = false;
                button.innerHTML = originalText;

            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF. Please try again.');
                button.disabled = false;
                button.innerHTML = originalText;
            }
        }

        // Real-time auto-refresh: listen for utilization summary updates from notification bell
        window.addEventListener('utilizationSummaryUpdated', function () {
            // Only refresh if a department/office is currently loaded
            const saved = loadSavedSelection();
            if (!saved) return;

            // Silently reload the current data without any alert
            const deptSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const utilizationContainer = document.getElementById('utilizationContainer');

            // Only refresh if data is currently displayed
            if (utilizationContainer && !utilizationContainer.classList.contains('hidden')) {
                loadUtilizationDataWithId(saved.id, saved.name);
            }
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

</body>
</html>
