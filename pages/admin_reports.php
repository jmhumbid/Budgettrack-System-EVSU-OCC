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
$weeklyStats = $activityLogger->getSubmissionStats(null, 7);
$monthlyStats = $activityLogger->getSubmissionStats(null, 30);
$yearlyStats = $activityLogger->getSubmissionStats(null, 365);
$recentActivities = $activityLogger->getRecentActivities(10);

$notification = new Notification();
$notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
$unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - System Activity Report</title>
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
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-slate-100 font-inter">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
        <div class="flex-1 flex flex-col" data-main-content>
            <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Automated Reports</h1>
                        <p class="text-red-100 text-sm mt-1">Weekly, monthly, and yearly user activity in one place</p>
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
            </header>
            <div class="flex-1 p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 max-w-7xl mx-auto">
                    <!-- Report Card -->
                    <a href="reports_viewer.php" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-maroon via-red-800 to-red-900 text-white shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-50 group-hover:opacity-70 transition-opacity"></div>
                        <div class="relative p-8 lg:p-12">
                            <div class="flex items-center justify-between mb-6">
                                <div class="bg-white/20 rounded-2xl p-4 backdrop-blur-sm">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <svg class="w-6 h-6 text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold mb-3">Report</h2>
                            <p class="text-white/80 text-lg mb-4">View all automatically generated reports. Filter by weekly, monthly, or yearly periods.</p>
                            <div class="flex items-center gap-2 text-sm text-white/70">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Auto-generated at end of period</span>
                            </div>
                        </div>
                    </a>

                    <!-- Activity Card -->
                    <a href="activity_viewer.php" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-900 via-indigo-800 to-purple-900 text-white shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-50 group-hover:opacity-70 transition-opacity"></div>
                        <div class="relative p-8 lg:p-12">
                            <div class="flex items-center justify-between mb-6">
                                <div class="bg-white/20 rounded-2xl p-4 backdrop-blur-sm">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <svg class="w-6 h-6 text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold mb-3">Activity</h2>
                            <p class="text-white/80 text-lg mb-4">View all recent activities, submissions, notifications, and announcements</p>
                            <div class="flex items-center gap-2 text-sm text-white/70">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                </svg>
                                <span>Real-time activity log</span>
                            </div>
                        </div>
                    </a>

                    <!-- Recent User Activity Card -->
                    <a href="recent_activity.php" class="group relative overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-900 via-teal-800 to-cyan-900 text-white shadow-2xl border border-white/20 hover:shadow-3xl transition-all duration-300 transform hover:scale-[1.02]">
                        <div class="absolute inset-0 bg-gradient-to-br from-white/10 to-transparent opacity-50 group-hover:opacity-70 transition-opacity"></div>
                        <div class="relative p-8 lg:p-12">
                            <div class="flex items-center justify-between mb-6">
                                <div class="bg-white/20 rounded-2xl p-4 backdrop-blur-sm">
                                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                </div>
                                <svg class="w-6 h-6 text-white/60 group-hover:text-white group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </div>
                            <h2 class="text-3xl font-bold mb-3">Recent Activity</h2>
                            <p class="text-white/80 text-lg mb-4">View latest user activities and system events</p>
                            <div class="flex items-center gap-2 text-sm text-white/70">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                <span><?php echo count($recentActivities); ?> recent activities</span>
                            </div>
                        </div>
                    </a>
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
            window.location.href = '../auth/logout.php';
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

