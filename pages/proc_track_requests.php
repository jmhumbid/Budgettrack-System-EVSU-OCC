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

require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../classes/Notification.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Procurement';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';

$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;

if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

$portalLabel = $departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal";

// Get all submissions for tracking
$fileSubmission = new FileSubmission();
$allSubmissions = $fileSubmission->getAllSubmissions();

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Procurement Track Requests</title>
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
                                    <h1 class="text-3xl font-bold mb-1">Track Requests</h1>
                                    <p class="text-red-100 text-sm">Monitor department submissions and stay on top of pending approvals.</p>
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
                <!-- Track Requests Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-maroon">All Department Requests</h2>
                        <div class="flex space-x-4">
                            <select id="statusFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon">
                                <option value="">All Status</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <select id="typeFilter" class="px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon">
                                <option value="">All Types</option>
                                <option value="PPMP">PPMP</option>
                                <option value="LIB">LIB</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fiscal Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($allSubmissions)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">No requests found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($allSubmissions as $submission): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($submission['department_name'] ?? 'Unknown'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($submission['submission_type']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($submission['fiscal_year']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                                    switch($submission['status']) {
                                                        case 'approved': echo 'bg-green-100 text-green-800'; break;
                                                        case 'rejected': echo 'bg-red-100 text-red-800'; break;
                                                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                                                        default: echo 'bg-blue-100 text-blue-800'; break;
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($submission['status']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php 
                                                    $ts = isset($submission['submitted_at']) ? strtotime($submission['submitted_at']) : (isset($submission['created_at']) ? strtotime($submission['created_at']) : false);
                                                    echo $ts ? date('M j, Y', $ts) : '—';
                                                ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <div class="flex space-x-2">
                                                    <button onclick="viewDetails(<?php echo $submission['id']; ?>)" class="text-blue-600 hover:text-blue-900">
                                                        View
                                                    </button>
                                                    <?php if ($submission['status'] === 'pending'): ?>
                                                        <button onclick="approveRequest(<?php echo $submission['id']; ?>)" class="text-green-600 hover:text-green-900">
                                                            Approve
                                                        </button>
                                                        <button onclick="rejectRequest(<?php echo $submission['id']; ?>)" class="text-red-600 hover:text-red-900">
                                                            Reject
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
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

            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');

            if (statusFilter) {
                statusFilter.addEventListener('change', filterTable);
            }

            if (typeFilter) {
                typeFilter.addEventListener('change', filterTable);
            }
        });

        function viewDetails(submissionId) {
            alert('View details for submission ID: ' + submissionId);
        }

        function approveRequest(submissionId) {
            if (confirm('Are you sure you want to approve this request?')) {
                alert('Request approved: ' + submissionId);
            }
        }

        function rejectRequest(submissionId) {
            if (confirm('Are you sure you want to reject this request?')) {
                alert('Request rejected: ' + submissionId);
            }
        }

        function filterTable() {
            const statusFilter = document.getElementById('statusFilter');
            const typeFilter = document.getElementById('typeFilter');
            const rows = document.querySelectorAll('tbody tr');

            const statusValue = statusFilter ? statusFilter.value : '';
            const typeValue = typeFilter ? typeFilter.value : '';

            rows.forEach(row => {
                const cells = row.cells;
                if (cells.length < 4) {
                    return;
                }

                const statusCell = cells[3];
                const typeCell = cells[1];

                const statusMatch = !statusValue || statusCell.textContent.toLowerCase().includes(statusValue.toLowerCase());
                const typeMatch = !typeValue || typeCell.textContent.includes(typeValue);

                row.style.display = (statusMatch && typeMatch) ? '' : 'none';
            });
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