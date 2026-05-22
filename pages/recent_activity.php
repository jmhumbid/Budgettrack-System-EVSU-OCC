<?php
session_start();

$allowedRoles = ['budget', 'school_admin'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles, true)) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../components/profile_avatar.php';

$username = $_SESSION['user_name'] ?? 'Administrator';
$userEmail = $_SESSION['user_email'] ?? '';
$activeSidebar = 'reports';

$activityLogger = new UserActivity();
$recentActivities = $activityLogger->getRecentActivities(100);

$notification = new Notification();
$notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
$unreadCount = $notification->getUnreadCount($_SESSION['user_id']);

// Function to format activity details
function formatActivityDetails($activityDetails) {
    if (empty($activityDetails)) {
        return null;
    }
    
    $decoded = json_decode($activityDetails, true);
    if (!is_array($decoded)) {
        return $activityDetails;
    }
    
    $parts = [];
    
    // Action
    if (!empty($decoded['action'])) {
        $parts[] = ucfirst($decoded['action']);
    }
    
    // Submission Type
    if (!empty($decoded['submission_type'])) {
        $parts[] = strtoupper($decoded['submission_type']);
    }
    
    // File Name
    if (!empty($decoded['file_name'])) {
        $parts[] = '"' . htmlspecialchars($decoded['file_name']) . '"';
    }
    
    // Year
    if (!empty($decoded['year'])) {
        $parts[] = 'Year ' . $decoded['year'];
    }
    
    return !empty($parts) ? implode(' • ', $parts) : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Recent User Activity</title>
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
        <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
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
                                    <h1 class="text-3xl font-bold text-white">Recent User Activity</h1>
                                    <p class="text-red-100 text-sm mt-1">Latest user activities and system events</p>
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
                    <!-- Activity List -->
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div>
                                <h2 class="text-xl font-bold text-gray-900">Recent User Activities</h2>
                                <p class="text-sm text-gray-500 mt-1">Complete log of recent user activities and system events</p>
                            </div>
                            <div class="text-sm text-gray-600">
                                <span class="font-semibold">Total:</span> <?php echo count($recentActivities); ?> activities
                            </div>
                        </div>
                        
                        <?php if (empty($recentActivities)): ?>
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                                </svg>
                                <h3 class="text-lg font-semibold text-gray-900 mb-2">No Activities Found</h3>
                                <p class="text-gray-500">No recent activities have been recorded yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="border border-gray-200 rounded-xl p-4 hover:bg-gray-50 transition-colors">
                                        <div class="flex items-start gap-4">
                                            <div class="flex-shrink-0 mt-1">
                                                <?php echo $activityLogger->getActivityIcon($activity['activity_type']); ?>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between mb-2">
                                                    <p class="text-sm font-semibold text-gray-900">
                                                        <span class="font-bold"><?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?></span>
                                                        <?php echo $activityLogger->formatActivityType($activity['activity_type']); ?>
                                                    </p>
                                                    <p class="text-xs text-gray-500 whitespace-nowrap ml-4">
                                                        <?php echo date('M j, Y g:i A', strtotime($activity['created_at'])); ?>
                                                    </p>
                                                </div>
                                                <div class="flex items-center space-x-2 text-xs text-gray-500">
                                                    <span class="font-medium">Department:</span>
                                                    <span><?php echo htmlspecialchars($activity['dept_name'] ?? 'No Department'); ?></span>
                                                    <span class="mx-1">•</span>
                                                    <span class="font-medium">Role:</span>
                                                    <span><?php echo htmlspecialchars($activity['role_name'] ?? 'No Role'); ?></span>
                                                    <?php if ($activity['ip_address']): ?>
                                                        <span class="mx-1">•</span>
                                                        <span class="font-medium">IP:</span>
                                                        <span><?php echo htmlspecialchars($activity['ip_address']); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php 
                                                $formattedDetails = formatActivityDetails($activity['activity_details'] ?? '');
                                                if ($formattedDetails): ?>
                                                    <div class="mt-2 text-sm text-gray-700 bg-gray-50 rounded-lg p-3 border border-gray-200">
                                                        <div class="flex items-start gap-2">
                                                            <svg class="w-4 h-4 text-gray-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            <div class="flex-1">
                                                                <span class="font-medium text-gray-900">Details:</span>
                                                                <span class="ml-2"><?php echo $formattedDetails; ?></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
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
    </script>

</body>
</html>

