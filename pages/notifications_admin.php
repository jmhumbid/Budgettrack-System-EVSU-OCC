<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/FileSubmission.php';

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

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

$notification = new Notification();
$fileSubmission = new FileSubmission();
$activeSidebar = 'notifications';

// Get notifications for current user
$notifications = $notification->getUserNotifications($userId, 100);
$unreadCount = $notification->getUnreadCount($userId);

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'mark_all_read') {
        if ($notification->markAllAsRead($userId)) {
            $success_message = 'All notifications marked as read!';
        } else {
            $error_message = 'Failed to mark all notifications as read.';
        }
    } elseif ($_POST['action'] === 'clear_all') {
        if ($notification->deleteAllForUser($userId)) {
            $success_message = 'All notifications deleted.';
        } else {
            $error_message = 'Failed to delete notifications.';
        }
    }

    // Refresh after any action
    $notifications = $notification->getUserNotifications($userId, 100);
    $unreadCount = $notification->getUnreadCount($userId);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin • Notifications</title>
<link rel="icon" type="image/png" href="../img/evsu_logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{maroon:'#800000','maroon-dark':'#5a0000'}}}}</script>
</head>
<body class="bg-gray-50 font-inter">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
  <main class="flex-1" data-main-content>
    <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
      <div class="px-6 py-6">
        <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
          <div class="text-white">
            <h1 class="text-3xl font-bold">Notifications</h1>
            <p class="text-red-100 text-sm mt-1">System and account notifications</p>
        </div>
          <div class="flex flex-wrap items-center gap-3">
            <div class="flex flex-wrap items-center gap-3">
          <!-- Notification Bell -->
          <?php 
          require_once __DIR__ . '/../classes/Notification.php';
          $notification = new Notification();
          $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
          $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
          include __DIR__ . '/../components/notification_bell.php'; 
          ?>
          
          <?php if ($unreadCount > 0): ?>
                <form method="POST" class="inline-flex">
              <input type="hidden" name="action" value="mark_all_read">
                  <button type="submit" class="px-4 py-2 bg-white text-maroon rounded-lg hover:bg-gray-100">
                <i class="fas fa-check-double mr-2"></i>Mark All as Read
              </button>
            </form>
          <?php endif; ?>
          <?php if (!empty($notifications)): ?>
                <form method="POST" class="inline-flex">
              <input type="hidden" name="action" value="clear_all">
                  <button type="submit" class="px-4 py-2 border border-white/60 text-white rounded-lg hover:bg-white hover:text-maroon transition-colors">
                <i class="fas fa-trash mr-2"></i>Clear All
              </button>
            </form>
          <?php endif; ?>
              <button onclick="goBack()" class="px-4 py-2 border border-white/40 text-white bg-white bg-opacity-20 rounded-lg hover:bg-opacity-30 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back
          </button>
            </div>
          <div class="relative">
              <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-white bg-opacity-20 px-4 py-2 rounded-lg hover:bg-opacity-30 transition-colors backdrop-blur">
                <?php render_profile_avatar(['classes' => 'bg-maroon text-white font-semibold']); ?>
              <div>
                  <div class="font-medium text-white"><?php echo htmlspecialchars($username); ?></div>
                  <div class="text-xs text-red-100"><?php echo htmlspecialchars($userEmail); ?></div>
              </div>
                <svg class="w-4 h-4 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
              </svg>
            </button>
            
            <!-- Profile Dropdown -->
            <div id="profileDropdown" class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 hidden">
              <div class="py-1">
                <a href="profile.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                  </svg>
                  Profile
                </a>
                <a href="change_password.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                  </svg>
                  Change Password
                </a>
                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'budget'): ?>
                    <a href="super_admin_dashboard.php" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                        <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Admin Panel
                    </a>
                <?php endif; ?>
                <div class="border-t border-gray-100"></div>
                <button onclick="confirmLogout()" class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                  <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
    
    <section class="p-6">
      <?php if (isset($success_message)): ?>
        <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
          <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
        </div>
      <?php endif; ?>
      
      <?php if (isset($error_message)): ?>
        <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
          <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
        </div>
      <?php endif; ?>
      
      <div class="bg-white border rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h2 class="text-lg font-semibold text-gray-900">
            All Notifications 
            <?php if ($unreadCount > 0): ?>
              <span class="ml-2 bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                <?php echo $unreadCount; ?> unread
              </span>
            <?php endif; ?>
          </h2>
        </div>
        
        <div class="divide-y divide-gray-200">
          <?php if (empty($notifications)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
              <i class="fas fa-bell-slash text-4xl mb-4"></i>
              <p class="text-lg font-medium">No notifications</p>
              <p class="text-sm">You're all caught up! New notifications will appear here.</p>
            </div>
          <?php else: ?>
            <?php foreach ($notifications as $notif): ?>
              <?php
                $iconClass = 'text-blue-500';
                $bgClass = 'bg-blue-100';
                if ($notif['type'] === 'success') {
                    $iconClass = 'text-green-500';
                    $bgClass = 'bg-green-100';
                } elseif ($notif['type'] === 'warning') {
                    $iconClass = 'text-yellow-500';
                    $bgClass = 'bg-yellow-100';
                } elseif ($notif['type'] === 'error') {
                    $iconClass = 'text-red-500';
                    $bgClass = 'bg-red-100';
                }
                $iconPath = getNotificationIcon($notif['title']);
              ?>
              <div class="px-6 py-4 hover:bg-gray-50 transition-colors cursor-pointer notification-item" 
                   data-notification-id="<?php echo $notif['id']; ?>"
                   data-file-id="<?php echo $notif['file_id'] ?? ''; ?>"
                   data-type="<?php echo $notif['type']; ?>">
                <div class="flex items-start space-x-3">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 <?php echo $bgClass; ?> rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 <?php echo $iconClass; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php echo $iconPath; ?>
                      </svg>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                      <p class="text-sm font-medium text-gray-900">
                        <?php echo htmlspecialchars($notif['title']); ?>
                        <?php if (!$notif['is_read']): ?>
                          <span class="ml-2 w-2 h-2 bg-red-500 rounded-full inline-block"></span>
                        <?php endif; ?>
                      </p>
                      <p class="text-xs text-gray-500">
                        <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                      </p>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                      <?php echo htmlspecialchars($notif['message']); ?>
                    </p>
                    <?php if ($notif['type'] === 'submission' && !empty($notif['file_id'])): ?>
                      <p class="text-xs text-blue-600 mt-1">
                        <i class="fas fa-download mr-1"></i>Click to download file
                      </p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>
  </main>
</div>
<div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
  <div class="flex items-center justify-center min-h-screen p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
      <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
        <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600">✕</button>
      </div>
      <p class="text-gray-600 mb-6">Are you sure you want to logout?</p>
      <div class="flex justify-end gap-3">
        <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
        <button onclick="performLogout()" class="px-4 py-2 bg-red-600 text-white rounded">Logout</button>
      </div>
    </div>
  </div>
</div>
<script>
// Profile dropdown functionality
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const button = event.target.closest('button');
    
    if (!button || !button.onclick || button.onclick.toString().indexOf('toggleProfileDropdown') === -1) {
        dropdown.classList.add('hidden');
    }
});

// Back button functionality
function goBack() {
    // Check user role and redirect to appropriate dashboard
    <?php if ($_SESSION['user_role'] === 'budget'): ?>
        window.location.href = 'admin_dashboard.php';
    <?php elseif ($_SESSION['user_role'] === 'school_admin'): ?>
        window.location.href = 'school_admin_dashboard.php';
    <?php else: ?>
        window.location.href = 'dept_dashboard.php';
    <?php endif; ?>
}

function confirmLogout(){document.getElementById('logoutModal').classList.remove('hidden')}
function closeLogoutModal(){document.getElementById('logoutModal').classList.add('hidden')}
function performLogout(){window.location.href='../auth/logout.php'}

// Notification click functionality
document.addEventListener('DOMContentLoaded', function() {
    const notificationItems = document.querySelectorAll('.notification-item');
    
    notificationItems.forEach(item => {
        item.addEventListener('click', function() {
            const notificationId = this.dataset.notificationId;
            const fileId = this.dataset.fileId;
            const type = this.dataset.type;
            
            // Mark notification as read
            if (notificationId) {
                markNotificationAsRead(notificationId);
            }
            
            // If it's a submission notification with a file, download the file
            if (type === 'submission' && fileId) {
                downloadFile(fileId);
            }
        });
    });
});

function markNotificationAsRead(notificationId) {
    fetch('../ajax/mark_notification_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'notification_id=' + notificationId
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove unread indicator
            const notification = document.querySelector(`[data-notification-id="${notificationId}"]`);
            if (notification) {
                const unreadDot = notification.querySelector('.bg-red-500');
                if (unreadDot) {
                    unreadDot.remove();
                }
            }
        }
    })
    .catch(error => console.error('Error:', error));
}

function downloadFile(fileId) {
    // Create a form to download the file
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '../ajax/download_file.php';
    
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'file_id';
    input.value = fileId;
    
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>

</body>
</html>
