<?php
session_start();

$allowedRoles = ['budget', 'school_admin', 'procurement', 'offices', 'supply_office'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles, true)) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../components/profile_avatar.php';

$username = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$role = $_SESSION['user_role'];
$userId = $_SESSION['user_id'] ?? null;

$activityLogger = new UserActivity();
$notification = new Notification();
$fileSubmission = new FileSubmission();

// Get all activities
$allActivities = $activityLogger->getAllActivities(200, ['login', 'logout']);

// Get all notifications
$allNotifications = $notification->getAllNotifications(200);

// Get all file submissions
$db = getDB();
$submissionsQuery = "SELECT fs.*, u.first_name, u.last_name, u.email, d.dept_name
                     FROM file_submissions fs
                     LEFT JOIN users u ON fs.user_id = u.id
                     LEFT JOIN departments d ON fs.department_id = d.id
                     ORDER BY fs.submitted_at DESC
                     LIMIT 200";
$submissionsStmt = $db->prepare($submissionsQuery);
$submissionsStmt->execute();
$allSubmissions = $submissionsStmt->fetchAll(PDO::FETCH_ASSOC);

function summarizeActivityDetail($activity) {
    $raw = $activity['activity_details'] ?? '';
    $decoded = json_decode($raw, true);
    if (!is_array($decoded)) {
        return [
            'description' => $raw ?: 'Activity recorded',
            'file_name' => '',
            'year' => '',
            'action' => ''
        ];
    }
    return [
        'description' => implode(' • ', array_filter([
            !empty($decoded['action']) ? ucfirst($decoded['action']) : null,
            !empty($decoded['submission_type']) ? strtoupper($decoded['submission_type']) : null,
            !empty($decoded['file_name']) ? "\"{$decoded['file_name']}\"" : null,
            !empty($decoded['year']) ? "Year {$decoded['year']}" : null,
        ])),
        'file_name' => $decoded['file_name'] ?? '',
        'year' => $decoded['year'] ?? '',
        'action' => $decoded['action'] ?? ''
    ];
}

// Format all activities for display
$formattedAllActivities = array_map(function ($activity) use ($activityLogger) {
    $summary = summarizeActivityDetail($activity);
    $user = trim(($activity['first_name'] ?? '') . ' ' . ($activity['last_name'] ?? ''));
    $department = $activity['dept_name'] ?? ($activity['department_name'] ?? 'Unknown Department');
    return [
        'type' => 'activity',
        'activity_type' => $activityLogger->formatActivityType($activity['activity_type']),
        'activity_details' => $summary['description'] ?: 'Activity recorded',
        'file_name' => $summary['file_name'],
        'year' => $summary['year'],
        'created_at' => $activity['created_at'],
        'display_date' => date('M j, Y g:i A', strtotime($activity['created_at'])),
        'user_name' => $user ?: '—',
        'department' => $department ?: '—',
    ];
}, $allActivities);

// Format notifications
$formattedNotifications = array_map(function ($notif) {
    $user = trim(($notif['first_name'] ?? '') . ' ' . ($notif['last_name'] ?? ''));
    return [
        'type' => 'notification',
        'title' => $notif['title'],
        'message' => $notif['message'],
        'notification_type' => ucfirst($notif['type']),
        'created_at' => $notif['created_at'],
        'display_date' => date('M j, Y g:i A', strtotime($notif['created_at'])),
        'user_name' => $user ?: 'System',
    ];
}, $allNotifications);

// Format submissions
$formattedSubmissions = array_map(function ($submission) {
    $user = trim(($submission['first_name'] ?? '') . ' ' . ($submission['last_name'] ?? ''));
    return [
        'type' => 'submission',
        'submission_type' => $submission['submission_type'],
        'file_name' => $submission['file_name'],
        'status' => ucfirst($submission['status']),
        'fiscal_year' => $submission['fiscal_year'],
        'created_at' => $submission['submitted_at'],
        'display_date' => date('M j, Y g:i A', strtotime($submission['submitted_at'])),
        'user_name' => $user ?: '—',
        'department' => $submission['dept_name'] ?? '—',
    ];
}, $allSubmissions);

// Combine all activities and sort by date
$allActivityItems = array_merge($formattedAllActivities, $formattedNotifications, $formattedSubmissions);
usort($allActivityItems, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

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
    <title>BudgetTrack - Activity Viewer</title>
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
                                    <h1 class="text-3xl font-bold text-white">Activity</h1>
                                    <p class="text-red-100 text-sm mt-1">All recent activities, submissions, notifications, and announcements</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <?php include __DIR__ . '/../components/notification_bell.php'; ?>
                            <button id="exportActivityPDF" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-xl text-sm font-semibold flex items-center gap-2 transition-colors backdrop-blur-sm border border-white border-opacity-30 text-white">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 12v8m-4-4l4 4 4-4m0-6V4"></path>
                                </svg>
                                Export PDF
                            </button>
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
                    <!-- Activity List -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">All Activities</h2>
                                <p class="text-sm text-gray-500 mt-1">Complete log of all system activities, submissions, and notifications</p>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold">Total:</span> <?php echo count($allActivityItems); ?> items
                            </div>
                        </div>
                        
                        <?php if (empty($allActivityItems)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Activities Found</h3>
                                <p class="text-gray-500">No activities have been recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($allActivityItems as $item): ?>
                                    <div class="border border-gray-200 rounded-xl p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start justify-between gap-4">
                                            <div class="flex items-start gap-4 flex-1">
                                                <!-- Type Badge -->
                                                <div class="flex-shrink-0">
                                                    <?php if ($item['type'] === 'activity'): ?>
                                                        <div class="bg-blue-100 text-blue-800 rounded-lg px-3 py-1 text-xs font-semibold">
                                                            ACTIVITY
                                                        </div>
                                                    <?php elseif ($item['type'] === 'notification'): ?>
                                                        <div class="bg-green-100 text-green-800 rounded-lg px-3 py-1 text-xs font-semibold">
                                                            NOTIFICATION
                                                        </div>
                                                    <?php elseif ($item['type'] === 'submission'): ?>
                                                        <div class="bg-purple-100 text-purple-800 rounded-lg px-3 py-1 text-xs font-semibold">
                                                            SUBMISSION
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <!-- Content -->
                                                <div class="flex-1">
                                                    <?php if ($item['type'] === 'activity'): ?>
                                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['activity_type']); ?></p>
                                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($item['activity_details']); ?></p>
                                                        <p class="text-xs text-gray-500 mt-2">
                                                            <span class="font-medium">User:</span> <?php echo htmlspecialchars($item['user_name']); ?>
                                                            <?php if ($item['department']): ?>
                                                                <span class="mx-2">•</span>
                                                                <span class="font-medium">Department:</span> <?php echo htmlspecialchars($item['department']); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php elseif ($item['type'] === 'notification'): ?>
                                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['title']); ?></p>
                                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($item['message']); ?></p>
                                                        <p class="text-xs text-gray-500 mt-2">
                                                            <span class="font-medium">Type:</span> <?php echo htmlspecialchars($item['notification_type']); ?>
                                                            <?php if ($item['user_name'] !== 'System'): ?>
                                                                <span class="mx-2">•</span>
                                                                <span class="font-medium">User:</span> <?php echo htmlspecialchars($item['user_name']); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php elseif ($item['type'] === 'submission'): ?>
                                                        <p class="font-semibold text-gray-900"><?php echo htmlspecialchars($item['submission_type']); ?> Submission</p>
                                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($item['file_name']); ?></p>
                                                        <p class="text-xs text-gray-500 mt-2">
                                                            <span class="font-medium">Status:</span> <?php echo htmlspecialchars($item['status']); ?>
                                                            <span class="mx-2">•</span>
                                                            <span class="font-medium">Year:</span> <?php echo htmlspecialchars($item['fiscal_year']); ?>
                                                            <span class="mx-2">•</span>
                                                            <span class="font-medium">User:</span> <?php echo htmlspecialchars($item['user_name']); ?>
                                                            <?php if ($item['department']): ?>
                                                                <span class="mx-2">•</span>
                                                                <span class="font-medium">Department:</span> <?php echo htmlspecialchars($item['department']); ?>
                                                            <?php endif; ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            
                                            <!-- Timestamp -->
                                            <div class="text-xs text-gray-500 whitespace-nowrap text-right">
                                                <div class="font-medium"><?php echo date('M j, Y', strtotime($item['created_at'])); ?></div>
                                                <div><?php echo date('g:i A', strtotime($item['created_at'])); ?></div>
                                            </div>
                                        </div>
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

        // Export Activity PDF functionality
        document.getElementById('exportActivityPDF')?.addEventListener('click', function() {
            const button = this;
            const originalText = button.innerHTML;
            button.disabled = true;
            button.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Generating...';
            
            // Prepare activity data
            const activityData = <?php echo json_encode($allActivityItems, JSON_UNESCAPED_SLASHES); ?>;
            
            // Generate PDF using jsPDF
            if (typeof window.jspdf === 'undefined') {
                alert('PDF library not loaded. Please refresh the page.');
                button.disabled = false;
                button.innerHTML = originalText;
                return;
            }
            
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            
            // Set up fonts and colors
            doc.setFillColor(128, 0, 0); // Maroon
            doc.setTextColor(128, 0, 0);
            
            // Title
            doc.setFontSize(20);
            doc.setFont(undefined, 'bold');
            doc.text('Activity Report', 20, 20);
            
            // Subtitle
            doc.setFontSize(12);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(100, 100, 100);
            doc.text('Generated: ' + new Date().toLocaleString(), 20, 30);
            doc.text('Total Activities: ' + activityData.length, 20, 36);
            
            let y = 50;
            const pageHeight = doc.internal.pageSize.height;
            const margin = 20;
            const lineHeight = 8;
            
            // Group activities by date
            const groupedByDate = {};
            activityData.forEach(item => {
                const date = new Date(item.created_at).toLocaleDateString();
                if (!groupedByDate[date]) {
                    groupedByDate[date] = [];
                }
                groupedByDate[date].push(item);
            });
            
            // Sort dates
            const sortedDates = Object.keys(groupedByDate).sort((a, b) => new Date(b) - new Date(a));
            
            sortedDates.forEach(date => {
                // Check if we need a new page
                if (y > pageHeight - 40) {
                    doc.addPage();
                    y = 20;
                }
                
                // Date header
                doc.setFontSize(14);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(128, 0, 0);
                doc.text(date, margin, y);
                y += lineHeight + 2;
                
                // Activities for this date
                groupedByDate[date].forEach(item => {
                    // Check if we need a new page
                    if (y > pageHeight - 30) {
                        doc.addPage();
                        y = 20;
                    }
                    
                    doc.setFontSize(10);
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(0, 0, 0);
                    
                    // Time
                    const time = new Date(item.created_at).toLocaleTimeString();
                    doc.text(time, margin, y);
                    
                    // Type badge
                    let typeText = '';
                    let typeColor = [100, 100, 100];
                    if (item.type === 'activity') {
                        typeText = 'ACTIVITY';
                        typeColor = [59, 130, 246]; // Blue
                    } else if (item.type === 'notification') {
                        typeText = 'NOTIFICATION';
                        typeColor = [16, 185, 129]; // Green
                    } else if (item.type === 'submission') {
                        typeText = 'SUBMISSION';
                        typeColor = [139, 92, 246]; // Purple
                    }
                    
                    doc.setFillColor(...typeColor);
                    doc.roundedRect(50, y - 5, 30, 5, 1, 1, 'F');
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(8);
                    doc.text(typeText, 52, y - 1);
                    doc.setTextColor(0, 0, 0);
                    
                    // Content
                    doc.setFontSize(10);
                    let contentText = '';
                    if (item.type === 'activity') {
                        contentText = item.activity_type + ': ' + item.activity_details;
                    } else if (item.type === 'notification') {
                        contentText = item.title + ': ' + item.message;
                    } else if (item.type === 'submission') {
                        contentText = item.submission_type + ' - ' + item.file_name + ' (Status: ' + item.status + ')';
                    }
                    
                    const lines = doc.splitTextToSize(contentText, 150);
                    doc.text(lines, 90, y);
                    y += lines.length * lineHeight + 3;
                    
                    // User info
                    doc.setFontSize(8);
                    doc.setTextColor(100, 100, 100);
                    let userInfo = 'User: ' + item.user_name;
                    if (item.department) {
                        userInfo += ' | Department: ' + item.department;
                    }
                    doc.text(userInfo, 90, y);
                    y += lineHeight + 5;
                });
                
                y += 5; // Space between date groups
            });
            
            // Save PDF
            const fileName = 'Activity_Report_' + new Date().toISOString().split('T')[0] + '.pdf';
            doc.save(fileName);
            
            // Reset button
            button.disabled = false;
            button.innerHTML = originalText;
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
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

