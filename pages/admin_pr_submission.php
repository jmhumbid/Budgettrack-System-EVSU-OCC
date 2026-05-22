<?php
session_start();

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Department.php';
require_once __DIR__ . '/../classes/PurchaseRequest.php';
include __DIR__ . '/../components/profile_avatar.php';

$username = $_SESSION['user_name'] ?? 'Administrator';
$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$portalLabel = 'Administration Panel';
$activeSidebar = 'pr_submission';

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);

// Get departments for filter
$department = new Department();
$departments = $department->getAllDepartments();

// Get PRs with filters
$pr = new PurchaseRequest();
$filters = [];
if (isset($_GET['department_id']) && $_GET['department_id']) {
    $filters['department_id'] = (int)$_GET['department_id'];
}
if (isset($_GET['status']) && $_GET['status']) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['date']) && $_GET['date']) {
    $filters['date_from'] = $_GET['date'];
    $filters['date_to'] = $_GET['date'];
}

// Only show PROCESSING, DELIVERED, and COMPLETE statuses
$allPRs = $pr->getPRsForProcurement($filters);
$filteredPRs = array_filter($allPRs, function($pr) {
    return in_array($pr['status'], ['processing', 'delivered', 'complete']);
});

// Get archived PRs
$archivedFilters = [];
if (isset($_GET['archived_dept']) && $_GET['archived_dept']) {
    $archivedFilters['department_id'] = (int)$_GET['archived_dept'];
}
if (isset($_GET['archived_date']) && $_GET['archived_date']) {
    $archivedFilters['date_from'] = $_GET['archived_date'];
    $archivedFilters['date_to'] = $_GET['archived_date'];
}
$archivedPRs = $pr->getArchivedPRsForProcurement($archivedFilters);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - PR Submission</title>
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
        <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
        
        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6l2 5H7l2-5zM7 10l1 4h8l1-4m-4 5v4m-4-4v4"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Purchase Request Submission</h1>
                                    <p class="text-red-100 text-sm">View Purchase Request status and tracking</p>
                                </div>
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Change Password
                                        </a>
                                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'budget'): ?>
                                            <a href="super_admin_dashboard.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                                <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                Admin Panel
                                            </a>
                                        <?php endif; ?>
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
                <!-- PR Status Section -->
                <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h2 class="text-xl font-bold text-gray-900">Purchase Request Status</h2>
                        <div class="flex gap-3">
                            <select id="filterDepartment" onchange="filterPRs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo (isset($_GET['department_id']) && $_GET['department_id'] == $dept['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <select id="filterStatus" onchange="filterPRs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                                <option value="">All Status</option>
                                <option value="processing" <?php echo (isset($_GET['status']) && $_GET['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="delivered" <?php echo (isset($_GET['status']) && $_GET['status'] == 'delivered') ? 'selected' : ''; ?>>Delivered</option>
                                <option value="complete" <?php echo (isset($_GET['status']) && $_GET['status'] == 'complete') ? 'selected' : ''; ?>>Complete</option>
                            </select>
                            <input type="date" id="filterDate" value="<?php echo isset($_GET['date']) ? htmlspecialchars($_GET['date']) : ''; ?>" onchange="filterPRs()" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
                            <button onclick="filterPRs()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">Filter</button>
                            <button onclick="openArchivedModal()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                                </svg>
                                Completed & Archived
                            </button>
                        </div>
                    </div>
                    
                    <?php if (empty($filteredPRs)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No purchase requests found.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($filteredPRs as $prItem): 
                                $prFiles = $pr->getPRFiles($prItem['id']);
                                $statusColors = [
                                    'processing' => 'bg-blue-100 text-blue-800',
                                    'delivered' => 'bg-purple-100 text-purple-800',
                                    'complete' => 'bg-green-100 text-green-800'
                                ];
                                $statusColor = $statusColors[$prItem['status']] ?? 'bg-gray-100 text-gray-800';
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="flex justify-between items-start mb-3">
                                        <div>
                                            <h3 class="font-semibold text-gray-900 text-lg"><?php echo htmlspecialchars($prItem['pr_number']); ?></h3>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($prItem['dept_name'] ?? 'Unknown Department'); ?></p>
                                            <p class="text-xs text-gray-500 mt-1">Submitted: <?php echo date('M j, Y g:i A', strtotime($prItem['submitted_at'])); ?></p>
                                        </div>
                                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusColor; ?>">
                                            <?php echo strtoupper($prItem['status']); ?>
                                        </span>
                                    </div>
                                    
                                    <div class="flex gap-2 flex-wrap">
                                        <button onclick="viewPRFiles(<?php echo $prItem['id']; ?>)" class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                            View Files (<?php echo $prItem['file_count']; ?>)
                                        </button>
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
                            <h3 class="text-xl font-bold text-gray-900">Completed & Archived Purchase Requests</h3>
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
                                <input type="date" id="archivedFilterDate" value="<?php echo isset($_GET['archived_date']) ? htmlspecialchars($_GET['archived_date']) : ''; ?>" class="border border-gray-300 rounded-lg px-3 py-2 text-sm">
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
                                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($prItem['dept_name'] ?? 'Unknown Department'); ?></p>
                                                    <p class="text-xs text-gray-500 mt-1">Submitted: <?php echo date('M j, Y g:i A', strtotime($prItem['submitted_at'])); ?></p>
                                                    <p class="text-xs text-gray-500 mt-1">Completed: <?php echo $prItem['completed_at'] ? date('M j, Y g:i A', strtotime($prItem['completed_at'])) : 'N/A'; ?></p>
                                                </div>
                                                <span class="px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-800">
                                                    COMPLETE
                                                </span>
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
            const storageKey = 'adminSidebarCollapsed';

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
        
        function filterPRs() {
            const deptFilter = document.getElementById('filterDepartment').value;
            const statusFilter = document.getElementById('filterStatus').value;
            const filterDate = document.getElementById('filterDate').value;
            
            let url = window.location.pathname + '?';
            if (deptFilter) url += 'department_id=' + deptFilter + '&';
            if (statusFilter) url += 'status=' + statusFilter + '&';
            if (filterDate) url += 'date=' + filterDate;
            
            window.location.href = url;
        }
        
        function openArchivedModal() {
            document.getElementById('archivedModal').classList.remove('hidden');
        }
        
        function closeArchivedModal() {
            document.getElementById('archivedModal').classList.add('hidden');
        }
        
        function filterArchivedPRs() {
            const deptFilter = document.getElementById('archivedFilterDepartment').value;
            const filterDate = document.getElementById('archivedFilterDate').value;
            
            let url = window.location.pathname + '?';
            const mainDept = document.getElementById('filterDepartment').value;
            const mainStatus = document.getElementById('filterStatus').value;
            const mainDate = document.getElementById('filterDate').value;
            if (mainDept) url += 'department_id=' + mainDept + '&';
            if (mainStatus) url += 'status=' + mainStatus + '&';
            if (mainDate) url += 'date=' + mainDate + '&';
            if (deptFilter) url += 'archived_dept=' + deptFilter + '&';
            if (filterDate) url += 'archived_date=' + filterDate;
            
            window.location.href = url;
        }
        
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

        body.sidebar-collapsed #sidebar .sidebar-toggle-icon {
            transform: rotate(180deg);
        }
    </style>

</body>
</html>

