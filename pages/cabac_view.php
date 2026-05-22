<?php
session_start();

if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

$userRole = $_SESSION['user_role'];
if ($userRole === 'budget') {
    header('Location: cabac.php');
    exit;
}

$activeSidebar = 'cabac_view';

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
$heroTitle = ($userRole === 'procurement') ? 'CABAC Library' : (($userRole === 'school-admin') ? 'School-wide CABAC Viewer' : 'Comparative Approve Budget and Actual Collection (CABAC)');
$heroDescription = ($userRole === 'procurement') ? 'Reference the approved CABAC workbook while coordinating procurement requests.' : (($userRole === 'school-admin') ? 'Provide transparency by sharing the final CABAC workbook with campus stakeholders.' : 'Stay aligned with the latest CABAC releases for your department.');

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId ?? 0, 10);
$unreadCount = $notification->getUnreadCount($userId ?? 0);

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cabac_files ORDER BY id DESC LIMIT 1");
    $stmt->execute();
    $currentFile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currentFile = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - CABAC Viewer</title>
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
                                <button type="button" onclick="document.getElementById('sidebarToggle').click()" class="bg-white bg-opacity-20 rounded-xl p-3 hover:bg-opacity-30 transition-colors cursor-pointer" aria-label="Toggle sidebar">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"></path>
                                    </svg>
                                </button>
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
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Header Section -->
                    <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-8 py-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex items-center gap-4">
                                <div class="p-3 rounded-xl bg-white/20 backdrop-blur-sm">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14h6m-6 4h6M7 7h10M7 3h10a2 2 0 012 2v16a2 2 0 01-2 2H7a2 2 0 01-2-2V5a2 2 0 012-2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-white leading-tight">CABAC Receipt Summary</h2>
                                    <p class="text-red-100 text-sm mt-1">Pick a program to generate a clean summary of saved entries with totals.</p>
                                </div>
                            </div>
                            <div id="cabacViewNotice" class="hidden"></div>
                        </div>
                    </div>

                    <!-- Program Selection Section -->
                    <div class="p-6 bg-gradient-to-b from-gray-50 to-white border-b border-gray-100">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Non-Fiduciary Programs -->
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-orange-100">
                                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wide">Non-Fiduciary Programs</label>
                                    </div>
                                </div>
                                <div class="relative" id="nonFiduciaryDropdownWrapper">
                                    <div class="flex items-stretch">
                                        <button type="button" id="nonFiduciaryDropdownBtn" onclick="toggleCustomDropdown('nonFiduciary')" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 bg-gray-50 hover:bg-white text-left flex items-center justify-between transition-all duration-200">
                                            <span id="nonFiduciarySelectedText" class="text-gray-400 text-sm">Select program...</span>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <button type="button" id="clearNonFiduciaryBtn" onclick="clearProgramSelection('nonFiduciary')" class="ml-2 px-3 py-3 border-2 border-gray-200 rounded-xl bg-white hover:bg-red-50 hover:border-red-200 text-gray-500 hover:text-red-500 transition-all duration-200 hidden" title="Clear selection">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="nonFiduciaryDropdownList" class="absolute w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-xl z-20 hidden max-h-60 overflow-y-auto">
                                        <div class="p-3 border-b border-gray-100 sticky top-0 bg-white">
                                            <div class="relative">
                                                <input type="text" id="nonFiduciarySearch" placeholder="Search programs..." class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500/30 focus:border-orange-500 text-sm" oninput="filterDropdownItems('nonFiduciary')">
                                                <button type="button" onclick="clearSearch('nonFiduciary')" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" title="Clear search">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="nonFiduciaryDropdownItems" class="py-1"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- Fiduciary Programs -->
                            <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-200 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 rounded-lg bg-emerald-100">
                                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <label class="text-sm font-bold text-gray-700 uppercase tracking-wide">Fiduciary Programs</label>
                                    </div>
                                </div>
                                <div class="relative" id="fiduciaryDropdownWrapper">
                                    <div class="flex items-stretch">
                                        <button type="button" id="fiduciaryDropdownBtn" onclick="toggleCustomDropdown('fiduciary')" class="w-full px-4 py-3 border-2 border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 bg-gray-50 hover:bg-white text-left flex items-center justify-between transition-all duration-200">
                                            <span id="fiduciarySelectedText" class="text-gray-400 text-sm">Select program...</span>
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <button type="button" id="clearFiduciaryBtn" onclick="clearProgramSelection('fiduciary')" class="ml-2 px-3 py-3 border-2 border-gray-200 rounded-xl bg-white hover:bg-red-50 hover:border-red-200 text-gray-500 hover:text-red-500 transition-all duration-200 hidden" title="Clear selection">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="fiduciaryDropdownList" class="absolute w-full mt-2 bg-white border border-gray-200 rounded-xl shadow-xl z-20 hidden max-h-60 overflow-y-auto">
                                        <div class="p-3 border-b border-gray-100 sticky top-0 bg-white">
                                            <div class="relative">
                                                <input type="text" id="fiduciarySearch" placeholder="Search programs..." class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500/30 focus:border-emerald-500 text-sm" oninput="filterDropdownItems('fiduciary')">
                                                <button type="button" onclick="clearSearch('fiduciary')" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors" title="Clear search">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="fiduciaryDropdownItems" class="py-1"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Receipt Container -->
                    <div id="receiptContainer" class="hidden">
                        <div class="bg-white">
                            <div class="bg-gradient-to-r from-red-800 to-red-700 text-white px-8 py-5">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-4">
                                        <div class="p-2 rounded-lg bg-white/20">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <div class="text-xs text-red-200 uppercase tracking-wider font-semibold">Program</div>
                                            <div id="receiptProgramName" class="text-xl font-bold"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="p-6">
                                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                                    <table class="w-full">
                                        <thead>
                                            <tr class="bg-gradient-to-r from-gray-100 to-gray-50 text-gray-700 text-xs uppercase tracking-wider border-b-2 border-gray-200">
                                                <th class="px-5 py-4 text-left font-bold">Entry</th>
                                                <th class="px-5 py-4 text-right font-bold text-blue-700">Approved Budget</th>
                                                <th class="px-5 py-4 text-right font-bold text-green-700">Available Allotment</th>
                                                <th class="px-5 py-4 text-right font-bold text-red-700">Balance</th>
                                            </tr>
                                        </thead>
                                        <tbody id="receiptTableBody" class="divide-y divide-gray-100"></tbody>
                                    </table>
                                </div>

                                <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div class="rounded-xl border-2 border-blue-200 p-5 bg-gradient-to-br from-blue-50 to-white hover:shadow-md transition-shadow">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="p-2 rounded-lg bg-blue-200">
                                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                            <div class="text-xs text-blue-600 uppercase tracking-wider font-bold">Total Approved</div>
                                        </div>
                                        <div id="totalApproved" class="text-2xl font-bold text-blue-600"></div>
                                    </div>
                                    <div class="rounded-xl border-2 border-green-200 p-5 bg-gradient-to-br from-green-50 to-white hover:shadow-md transition-shadow">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="p-2 rounded-lg bg-green-200">
                                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                            </div>
                                            <div class="text-xs text-green-600 uppercase tracking-wider font-bold">Total Allotment</div>
                                        </div>
                                        <div id="totalAllotment" class="text-2xl font-bold text-green-600"></div>
                                    </div>
                                    <div class="rounded-xl border-2 border-red-200 p-5 bg-gradient-to-br from-red-50 to-white hover:shadow-md transition-shadow">
                                        <div class="flex items-center gap-3 mb-2">
                                            <div class="p-2 rounded-lg bg-red-200">
                                                <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"></path>
                                                </svg>
                                            </div>
                                            <div class="text-xs text-red-600 uppercase tracking-wider font-bold">Total Balance</div>
                                        </div>
                                        <div id="totalBalance" class="text-2xl font-bold text-red-600"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="receiptEmpty" class="p-8">
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-white p-12 text-center">
                            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">No program selected</h3>
                            <p class="text-gray-500 max-w-md mx-auto">Choose a Fiduciary or Non-Fiduciary program above to view its saved entries and generate a summary.</p>
                        </div>
                    </div>
                </div>
                
                <!-- Archived Section - Always Visible (Bottom) -->
                <?php
                $cabacHistoryList = [];
                try {
                    $db = getDB();
                    $historyStmt = $db->prepare("SELECT * FROM cabac_history ORDER BY created_at DESC");
                    $historyStmt->execute();
                    $cabacHistoryList = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $cabacHistoryList = [];
                }
                ?>
            </div>
        </div>
    </div>

    <script>
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

        function toggleFullscreen(targetId = 'fileViewer') {
            const viewer = document.getElementById(targetId);
            if (!viewer) return;

            if (!document.fullscreenElement) {
                if (viewer.requestFullscreen) {
                    viewer.requestFullscreen();
                } else if (viewer.webkitRequestFullscreen) {
                    viewer.webkitRequestFullscreen();
                } else if (viewer.msRequestFullscreen) {
                    viewer.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

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

        function toggleHistoryModal(show = true) {
            const modal = document.getElementById('cabacHistoryModal');
            if (!modal) return;
            modal.classList.toggle('hidden', !show);
        }

        function showCabacViewNotice(message, type = 'info') {
            const notice = document.getElementById('cabacViewNotice');
            if (!notice) return;

            const base = 'px-4 py-3 rounded-lg text-sm font-semibold';
            const map = {
                info: 'bg-blue-50 text-blue-800 border border-blue-200',
                success: 'bg-emerald-50 text-emerald-800 border border-emerald-200',
                error: 'bg-red-50 text-red-800 border border-red-200',
                warning: 'bg-yellow-50 text-yellow-800 border border-yellow-200'
            };

            notice.className = base + ' ' + (map[type] || map.info);
            notice.textContent = message;
            notice.classList.remove('hidden');
        }

        function hideCabacViewNotice() {
            const notice = document.getElementById('cabacViewNotice');
            if (!notice) return;
            notice.classList.add('hidden');
        }

        function toggleCustomDropdown(type) {
            const list = document.getElementById(type + 'DropdownList');
            if (!list) return;

            const otherType = type === 'fiduciary' ? 'nonFiduciary' : 'fiduciary';
            const otherList = document.getElementById(otherType + 'DropdownList');
            if (otherList) otherList.classList.add('hidden');

            list.classList.toggle('hidden');
        }

        function filterDropdownItems(type) {
            const search = document.getElementById(type + 'Search');
            const items = document.getElementById(type + 'DropdownItems');
            if (!search || !items) return;

            const term = search.value.toLowerCase();
            const children = items.querySelectorAll('button[data-value]');
            children.forEach(btn => {
                const value = (btn.getAttribute('data-value') || '').toLowerCase();
                btn.classList.toggle('hidden', term && !value.includes(term));
            });
        }

        function clearSearch(type) {
            const search = document.getElementById(type + 'Search');
            if (search) {
                search.value = '';
                filterDropdownItems(type);
                search.focus();
            }
        }

        function clearProgramSelection(type) {
            const selectedText = document.getElementById(type + 'SelectedText');
            const clearBtn = document.getElementById('clear' + (type === 'fiduciary' ? 'Fiduciary' : 'NonFiduciary') + 'Btn');

            if (selectedText) {
                selectedText.textContent = 'Select program...';
                selectedText.className = 'text-gray-400 text-sm';
            }
            if (clearBtn) clearBtn.classList.add('hidden');

            // Clear program ID for PDF download
            currentProgramId = null;
            currentProgramName = null;

            document.getElementById('receiptContainer')?.classList.add('hidden');
            document.getElementById('receiptEmpty')?.classList.remove('hidden');
            hideCabacViewNotice();

            // Clear persisted state
            clearCabacViewState();
        }

        function formatCurrency(value) {
            const number = Number(value) || 0;
            return '₱ ' + number.toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        // Track current program for PDF download
        let currentProgramId = null;
        let currentProgramName = null;

        const CABAC_VIEW_STATE_KEY = 'cabacViewState';

        function saveCabacViewState(type, apiType, programName) {
            localStorage.setItem(CABAC_VIEW_STATE_KEY, JSON.stringify({
                type, apiType, programName,
                scrollY: window.scrollY
            }));
        }

        function clearCabacViewState() {
            localStorage.removeItem(CABAC_VIEW_STATE_KEY);
        }

        function downloadCabacPdf() {
            if (!currentProgramId) {
                alert('Please select a program first');
                return;
            }
            
            // Open PDF in new window for printing
            const pdfUrl = '../api/generate_cabac_pdf.php?program_id=' + encodeURIComponent(currentProgramId);
            const printWindow = window.open(pdfUrl, '_blank');
            
            // Auto-trigger print dialog after page loads
            if (printWindow) {
                printWindow.onload = function() {
                    printWindow.print();
                };
            }
        }

        async function loadProgramsForDropdown(type, apiType) {
            const items = document.getElementById(type + 'DropdownItems');
            if (!items) return;
            items.innerHTML = '';

            const response = await fetch('../api/cabac_programs.php?action=get_programs&type=' + encodeURIComponent(apiType));
            const data = await response.json();
            if (!data.success) {
                throw new Error(data.message || 'Failed to load programs');
            }

            data.programs.forEach(p => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'w-full text-left px-4 py-2 hover:bg-gray-50 transition-colors text-sm text-gray-700';
                btn.setAttribute('data-value', p.program_name);
                btn.textContent = p.program_name;
                btn.addEventListener('click', () => selectProgram(type, apiType, p.program_name));
                items.appendChild(btn);
            });
        }

        async function selectProgram(type, apiType, programName) {
            const selectedText = document.getElementById(type + 'SelectedText');
            const list = document.getElementById(type + 'DropdownList');
            const clearBtn = document.getElementById('clear' + (type === 'fiduciary' ? 'Fiduciary' : 'NonFiduciary') + 'Btn');
            const otherType = type === 'fiduciary' ? 'nonFiduciary' : 'fiduciary';
            const otherClearBtn = document.getElementById('clear' + (otherType === 'fiduciary' ? 'Fiduciary' : 'NonFiduciary') + 'Btn');
            const otherSelectedText = document.getElementById(otherType + 'SelectedText');

            if (otherSelectedText) {
                otherSelectedText.textContent = 'Select program...';
                otherSelectedText.className = 'text-gray-400 text-sm';
            }
            if (otherClearBtn) otherClearBtn.classList.add('hidden');

            if (selectedText) {
                selectedText.textContent = programName;
                selectedText.className = 'text-sm text-gray-900 font-bold';
            }
            if (clearBtn) clearBtn.classList.remove('hidden');
            if (list) list.classList.add('hidden');

            await loadReceipt(programName, apiType);

            // Persist selection so page restores here on reload
            saveCabacViewState(type, apiType, programName);
        }

        async function loadReceipt(programName, programType) {
            try {
                // Notice hidden - loading silently
                document.getElementById('receiptEmpty')?.classList.add('hidden');

                const programsResponse = await fetch('../api/cabac_programs.php?action=get_programs&type=' + encodeURIComponent(programType));
                const programsData = await programsResponse.json();
                if (!programsData.success) {
                    throw new Error(programsData.message || 'Error getting programs');
                }

                const program = programsData.programs.find(p => p.program_name === programName);
                if (!program) {
                    throw new Error('Selected program not found in database');
                }

                // Store program ID for PDF download
                currentProgramId = program.id;
                currentProgramName = programName;

                const entriesResponse = await fetch('../api/cabac_programs.php?action=get_entries&program_id=' + encodeURIComponent(program.id));
                const entriesData = await entriesResponse.json();
                if (!entriesData.success) {
                    throw new Error(entriesData.message || 'Error loading entries');
                }

                const entries = entriesData.entries || [];

                document.getElementById('receiptProgramName').textContent = programName;

                const tbody = document.getElementById('receiptTableBody');
                tbody.innerHTML = '';

                let totalApproved = 0;
                let totalAllotment = 0;
                let totalBalance = 0;

                entries.forEach(e => {
                    const approved = Number(e.approved_budget) || 0;
                    const allotment = Number(e.available_allotment) || 0;
                    const balance = Number(e.balance) || (approved - allotment);

                    totalApproved += approved;
                    totalAllotment += allotment;
                    totalBalance += balance;

                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="px-4 py-3 text-sm text-gray-800 font-bold">${(e.program_name || '').replace(/</g, '&lt;')}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-right text-blue-600">${formatCurrency(approved)}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-right text-green-600">${formatCurrency(allotment)}</td>
                        <td class="px-4 py-3 text-sm font-semibold text-right text-red-600">${formatCurrency(balance)}</td>
                    `;
                    tbody.appendChild(tr);
                });

                document.getElementById('totalApproved').textContent = formatCurrency(totalApproved);
                document.getElementById('totalAllotment').textContent = formatCurrency(totalAllotment);

                const totalBalanceEl = document.getElementById('totalBalance');
                totalBalanceEl.textContent = formatCurrency(totalBalance);
                totalBalanceEl.className = 'text-lg font-bold text-red-600';

                document.getElementById('receiptContainer')?.classList.remove('hidden');
            } catch (err) {
                document.getElementById('receiptContainer')?.classList.add('hidden');
                document.getElementById('receiptEmpty')?.classList.remove('hidden');
                showCabacViewNotice(err.message || 'Failed to load receipt', 'error');
            }
        }

        document.addEventListener('click', function(e) {
            const fiduciaryWrapper = document.getElementById('fiduciaryDropdownWrapper');
            const nonFiduciaryWrapper = document.getElementById('nonFiduciaryDropdownWrapper');
            const fiduciaryList = document.getElementById('fiduciaryDropdownList');
            const nonFiduciaryList = document.getElementById('nonFiduciaryDropdownList');

            if (fiduciaryWrapper && !fiduciaryWrapper.contains(e.target) && fiduciaryList) {
                fiduciaryList.classList.add('hidden');
            }
            if (nonFiduciaryWrapper && !nonFiduciaryWrapper.contains(e.target) && nonFiduciaryList) {
                nonFiduciaryList.classList.add('hidden');
            }
        });

        document.addEventListener('DOMContentLoaded', async function() {
            try {
                await loadProgramsForDropdown('nonFiduciary', 'non-fiduciary');
                await loadProgramsForDropdown('fiduciary', 'fiduciary');

                // Restore last selected program
                const raw = localStorage.getItem(CABAC_VIEW_STATE_KEY);
                if (raw) {
                    try {
                        const state = JSON.parse(raw);
                        if (state.type && state.apiType && state.programName) {
                            await selectProgram(state.type, state.apiType, state.programName);
                            // Restore scroll position after content renders
                            if (state.scrollY) {
                                setTimeout(() => window.scrollTo({ top: state.scrollY, behavior: 'instant' }), 200);
                            }
                        }
                    } catch (e) {}
                }

                // Save scroll position as user scrolls
                window.addEventListener('scroll', function() {
                    const r = localStorage.getItem(CABAC_VIEW_STATE_KEY);
                    if (!r) return;
                    try {
                        const s = JSON.parse(r);
                        s.scrollY = window.scrollY;
                        localStorage.setItem(CABAC_VIEW_STATE_KEY, JSON.stringify(s));
                    } catch (e) {}
                }, { passive: true });

            } catch (e) {
                showCabacViewNotice(e.message || 'Failed to load programs', 'error');
            }
        });

        function openHistoryFile(path, fileName) {
            if (!path) return;
            const ext = path.split('.').pop().toLowerCase();
            const fileUrl = '../' + path;
            
            document.getElementById('historyViewerFileName').textContent = fileName || path.split('/').pop();
            const viewerContent = document.getElementById('historyViewerContent');
            const viewerModal = document.getElementById('historyViewerModal');
            viewerContent.innerHTML = '';
            
            if (['xls', 'xlsx', 'csv'].includes(ext)) {
                const iframe = document.createElement('iframe');
                iframe.src = '../ajax/view_excel.php?file=' + encodeURIComponent(path);
                iframe.className = 'w-full border-0';
                iframe.style.minHeight = '600px';
                iframe.style.width = '100%';
                iframe.setAttribute('allow', 'fullscreen');
                iframe.setAttribute('allowfullscreen', '');
                viewerContent.appendChild(iframe);
            } else if (ext === 'pdf') {
                const iframe = document.createElement('iframe');
                iframe.src = fileUrl + '#toolbar=1';
                iframe.className = 'w-full border-0';
                iframe.style.minHeight = '600px';
                iframe.style.width = '100%';
                iframe.setAttribute('allow', 'fullscreen');
                iframe.setAttribute('allowfullscreen', '');
                viewerContent.appendChild(iframe);
            } else if (['jpg', 'jpeg', 'jfif', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(ext)) {
                const imgContainer = document.createElement('div');
                imgContainer.className = 'flex items-center justify-center p-8 bg-gray-100';
                imgContainer.style.minHeight = '600px';
                const img = document.createElement('img');
                img.src = fileUrl;
                img.alt = fileName || 'File';
                img.className = 'max-w-full max-h-full object-contain rounded-lg shadow-lg';
                imgContainer.appendChild(img);
                viewerContent.appendChild(imgContainer);
            } else {
                const messageDiv = document.createElement('div');
                messageDiv.className = 'flex flex-col items-center justify-center p-8 bg-gray-50';
                messageDiv.style.minHeight = '400px';
                messageDiv.innerHTML = `
                    <svg class="w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                    <p class="text-gray-600 font-semibold mb-2">File Type: ${ext.toUpperCase()}</p>
                    <p class="text-gray-500 text-sm mb-4">Preview not available for this file type</p>
                    <a href="${fileUrl}" download="${fileName || path.split('/').pop()}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        Download to View
                    </a>
                `;
                viewerContent.appendChild(messageDiv);
            }
            viewerModal.classList.remove('hidden');
        }

        function closeHistoryViewer() {
            document.getElementById('historyViewerModal').classList.add('hidden');
            const viewerContent = document.getElementById('historyViewerContent');
            if (viewerContent) {
                viewerContent.innerHTML = '';
            }
        }
        
        // Download all Non-Fiduciary Programs
        function downloadNonFiduciaryPrograms() {
            window.open('../api/generate_cabac_pdf.php?type=non-fiduciary&download=all', '_blank');
        }
        
        // Download all Fiduciary Programs
        function downloadFiduciaryPrograms() {
            window.open('../api/generate_cabac_pdf.php?type=fiduciary&download=all', '_blank');
        }
    </script>
    
    <!-- History Modal -->
    <div id="cabacHistoryModal" class="fixed inset-0 z-50 hidden flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0 bg-black/40" onclick="toggleHistoryModal(false)"></div>
        <div class="relative w-full max-w-4xl rounded-3xl bg-white shadow-2xl border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-900">CABAC History Archive</h2>
                    <p class="text-xs text-gray-500">Refer to archived CABAC uploads.</p>
                </div>
                <button type="button" onclick="toggleHistoryModal(false)" class="text-gray-400 hover:text-gray-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="space-y-6 px-6 py-5" style="max-height: 70vh; overflow-y: auto;">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.3em] text-gray-800">CABAC Files</h3>
                    <span class="text-xs text-gray-500"><?php echo count($cabacHistoryList); ?> records</span>
                </div>
                <div class="space-y-3">
                    <?php foreach ($cabacHistoryList as $history): ?>
                        <?php $status = ucfirst($history['status'] ?? 'new'); ?>
                        <div class="flex flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50 px-4 py-3">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900"><?php echo htmlspecialchars($history['file_name']); ?></p>
                                    <p class="text-xs text-gray-500">Uploaded <?php echo date('M j, Y g:i A', strtotime($history['created_at'])); ?></p>
                                </div>
                                <span class="text-xs font-semibold uppercase tracking-[0.3em] <?php echo ($history['status'] === 'new' ? 'text-emerald-600 bg-emerald-50 border border-emerald-100' : 'text-indigo-600 bg-indigo-50 border border-indigo-100'); ?> px-2 py-1 rounded-full">
                                    <?php echo $status; ?>
                                </span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="openHistoryFile('<?php echo htmlspecialchars($history['file_path'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($history['file_name'], ENT_QUOTES); ?>')" class="px-3 py-1 rounded-lg border border-gray-200 text-xs font-semibold text-maroon hover:bg-maroon hover:text-white transition">View</button>
                                <a href="../<?php echo htmlspecialchars($history['file_path']); ?>" class="px-3 py-1 rounded-lg bg-blue-600 text-xs font-semibold text-white hover:bg-blue-700 transition" download="<?php echo htmlspecialchars($history['file_name']); ?>">Download</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- History File Viewer Modal -->
    <div id="historyViewerModal" class="fixed inset-0 z-50 hidden flex items-center justify-center px-4 py-6">
        <div class="absolute inset-0 bg-black bg-opacity-50" onclick="closeHistoryViewer()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl border border-gray-200 max-w-6xl w-full" style="max-height: 90vh;">
            <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4">
                <div>
                    <h3 class="text-xl font-bold text-gray-900">File Viewer</h3>
                    <p class="text-sm text-gray-500" id="historyViewerFileName"></p>
                </div>
                <button onclick="closeHistoryViewer()" class="text-gray-400 hover:text-gray-700 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="historyViewerContent" class="overflow-auto p-4" style="max-height: calc(90vh - 80px);">
            </div>
        </div>
    </div>

</body>
</html>