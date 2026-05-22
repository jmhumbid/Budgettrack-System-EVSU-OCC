<?php
session_start();

$allowedRoles = ['budget', 'school_admin', 'procurement', 'offices', 'supply_office'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles, true)) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/ReportGenerator.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../components/profile_avatar.php';

$username = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'] ?? null;

// Get filter
$filterType = $_GET['filter'] ?? 'all'; // all, weekly, monthly, yearly

$reportGenerator = new ReportGenerator();
$reports = $reportGenerator->getReports($filterType === 'all' ? null : $filterType, 100);

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId ?? 0, 10);
$unreadCount = $notification->getUnreadCount($userId ?? 0);

// Set portal label
$departmentId = $_SESSION['department_id'] ?? null;
$departmentName = $_SESSION['department_name'] ?? null;
if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

if ($role === 'procurement') {
    $portalLabel = $departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal";
} elseif ($role === 'supply_office') {
    $portalLabel = $departmentName ? "Supply Office | " . htmlspecialchars($departmentName) : "Supply Office Portal";
} elseif ($role === 'budget') {
    $portalLabel = "Budget Office Portal";
} elseif ($role === 'school_admin') {
    $portalLabel = "School Admin Portal";
} else {
    $portalLabel = $departmentName ? "Department Portal | " . htmlspecialchars($departmentName) : "Department Portal";
}

// Determine sidebar
switch ($role) {
    case 'procurement':
        $sidebarPath = __DIR__ . '/../components/proc_sidebar.php';
        break;
    case 'offices':
    case 'supply_office':
        $sidebarPath = __DIR__ . '/../components/dept_sidebar.php';
        break;
    default:
        $sidebarPath = __DIR__ . '/../components/admin_sidebar.php';
        break;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Reports Viewer</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
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
</head>
<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <?php if ($role === 'budget' || $role === 'school_admin'): ?>
            <?php include $sidebarPath; ?>
        <?php else: ?>
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
                <?php 
                if ($role === 'supply_office' || $role === 'offices') {
                    include __DIR__ . '/../components/dept_sidebar.php';
                } else {
                    include $sidebarPath;
                }
                ?>
            </div>
        <?php endif; ?>
        <div class="flex-1 flex flex-col" data-main-content>
            <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <div>
                            <div class="flex items-center gap-3 mb-2">
                                <a href="admin_reports.php" class="text-white hover:text-red-100 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                </a>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Reports</h1>
                                    <p class="text-red-100 text-sm mt-1">View and download automatically generated reports</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
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
            </header>

            <div class="flex-1 p-6">
                <div class="max-w-7xl mx-auto">
                    <!-- Filter Buttons -->
                    <div class="mb-6 flex flex-wrap gap-3">
                        <a href="?filter=all" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $filterType === 'all' ? 'bg-maroon text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'; ?>">
                            All Reports
                        </a>
                        <a href="?filter=weekly" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $filterType === 'weekly' ? 'bg-maroon text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'; ?>">
                            Weekly
                        </a>
                        <a href="?filter=monthly" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $filterType === 'monthly' ? 'bg-maroon text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'; ?>">
                            Monthly
                        </a>
                        <a href="?filter=yearly" class="px-4 py-2 rounded-lg font-semibold transition-colors <?php echo $filterType === 'yearly' ? 'bg-maroon text-white' : 'bg-white text-gray-700 hover:bg-gray-100 border border-gray-200'; ?>">
                            Yearly
                        </a>
                    </div>

                    <!-- Reports List -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <?php if (empty($reports)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Reports Found</h3>
                                <p class="text-gray-500">No reports have been generated yet. Reports are automatically generated at the end of each week, month, or year.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($reports as $report): ?>
                                    <div class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:bg-gray-50 transition-colors">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-maroon/10 rounded-lg p-3">
                                                <svg class="w-6 h-6 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h3 class="font-semibold text-gray-900">
                                                    <?php echo ucfirst($report['report_type']); ?> Report
                                                </h3>
                                                <p class="text-sm text-gray-500">
                                                    <?php echo date('M d, Y', strtotime($report['report_period_start'])); ?> - 
                                                    <?php echo date('M d, Y', strtotime($report['report_period_end'])); ?>
                                                </p>
                                                <p class="text-xs text-gray-400 mt-1">
                                                    Generated: <?php echo date('M j, Y g:i A', strtotime($report['generated_at'])); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <a href="../<?php echo htmlspecialchars($report['file_path']); ?>" download="<?php echo htmlspecialchars($report['file_name']); ?>" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors text-sm font-semibold flex items-center gap-2">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Download
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
        }
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
            if (!button && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

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

