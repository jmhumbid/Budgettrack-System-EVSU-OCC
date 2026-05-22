<?php
session_start();

// Require login; restrict to procurement role; redirect others to their dashboards
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'procurement') {
    switch ($_SESSION['user_role'] ?? '') {
        case 'budget':
            header('Location: ./admin_dashboard.php');
            break;
        case 'school_admin':
            header('Location: ./school_admin_dashboard.php');
            break;
        case 'offices':
        case 'supply_office':
            header('Location: ./dept_dashboard.php');
            break;
        default:
            header('Location: ../login.php');
            break;
    }
    exit;
}

require_once __DIR__ . '/../classes/Notification.php';

// Function to get icon SVG based on notification title
function getNotificationIcon($title) {
    $title = strtolower($title);
    
    // CABAC notifications
    if (strpos($title, 'cabac') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"></path>';
    }
    
    // Budget Allocation notifications
    if (strpos($title, 'allocation') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>';
    }
    
    // Budget Utilization notifications
    if (strpos($title, 'utilization') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>';
    }
    
    // Purchase Request / PR notifications
    if (strpos($title, 'purchase') !== false || strpos($title, 'pr ') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>';
    }
    
    // PPMP notifications
    if (strpos($title, 'ppmp') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>';
    }
    
    // Announcement notifications
    if (strpos($title, 'announcement') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>';
    }
    
    // User/Profile notifications
    if (strpos($title, 'user') !== false || strpos($title, 'profile') !== false || strpos($title, 'account') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>';
    }
    
    // Department notifications
    if (strpos($title, 'department') !== false || strpos($title, 'sub-department') !== false) {
        return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>';
    }
    
    // Default info icon
    return '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
}

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Procurement';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';

$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;

if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

$portalLabel = $departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal";

// Get notifications
$notification = new Notification();
$notifications = $notification->getUserNotifications($_SESSION['user_id'], 20);
$unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Procurement Notifications</title>
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
            <?php include __DIR__ . '/../components/proc_sidebar.php'; ?>
        </div>
        
        <div class="flex-1 flex flex-col" data-main-content>
            <!-- Header with Gradient -->
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="text-white max-w-2xl">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm text-red-100 mb-2">Procurement Office • <?php echo htmlspecialchars($portalLabel); ?></p>
                                    <h1 class="text-3xl font-bold mb-1">Procurement Notifications</h1>
                                    <p class="text-red-100 text-sm">All the alerts you need, right here.</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Notification Bell -->
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
            </div>
            
            <!-- Content Area -->
            <div class="flex-1 p-6">
                <!-- Notifications Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-maroon">All Notifications</h2>
                        <?php if ($unreadCount > 0): ?>
                            <span class="bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                                <?php echo $unreadCount; ?> unread
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="space-y-4">
                        <?php if (empty($notifications)): ?>
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 102.828 2.828l6.414 6.414a2 2 0 01-2.828 2.828L4.828 7z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                                <p class="text-gray-500">You'll see important updates and alerts here when they arrive.</p>
                            </div>
                        <?php else: ?>
                            <?php foreach ($notifications as $notif): ?>
                                <?php $iconPath = getNotificationIcon($notif['title']); ?>
                                <div class="flex items-start space-x-4 p-4 rounded-lg border-l-4 <?php 
                                    switch($notif['type']) {
                                        case 'success': echo 'border-green-400 bg-green-50'; break;
                                        case 'error': echo 'border-red-400 bg-red-50'; break;
                                        case 'warning': echo 'border-yellow-400 bg-yellow-50'; break;
                                        default: echo 'border-blue-400 bg-blue-50'; break;
                                    }
                                ?> hover:shadow-sm transition-shadow">
                                    <div class="flex-shrink-0">
                                        <svg class="w-6 h-6 <?php 
                                            switch($notif['type']) {
                                                case 'success': echo 'text-green-600'; break;
                                                case 'error': echo 'text-red-600'; break;
                                                case 'warning': echo 'text-yellow-600'; break;
                                                default: echo 'text-blue-600'; break;
                                            }
                                        ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <?php echo $iconPath; ?>
                                        </svg>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($notif['title']); ?>
                                            </h3>
                                            <div class="flex items-center space-x-2">
                                                <span class="text-xs text-gray-500">
                                                    <?php 
                                                        $ts = isset($notif['created_at']) ? strtotime($notif['created_at']) : false;
                                                        echo $ts ? date('M j, Y g:i A', $ts) : '—';
                                                    ?>
                                                </span>
                                                <?php if (!$notif['is_read']): ?>
                                                    <span class="w-2 h-2 bg-red-500 rounded-full"></span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <?php if (!empty($notif['message'])): ?>
                                            <p class="mt-1 text-sm text-gray-600">
                                                <?php echo htmlspecialchars($notif['message']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Logout Confirmation Modal -->
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
        // Logout functionality
        function confirmLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }
        
        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }
        
        function performLogout() {
            window.location.href = '../auth/logout.php';
        }
        
        // Close modal when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (modal && event.target === modal) {
                closeLogoutModal();
            }
        });
        
        // Profile dropdown functionality
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown?.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown) {
                return;
            }
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
            
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

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