<?php
session_start();
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/Notification.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

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

// Notification data
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$notificationsForBell = [];
$notificationsForPage = [];
$unreadCount = 0;

if ($userId) {
    $notification = new Notification();
    $notificationsForBell = $notification->getUserNotifications($userId, 10);
    $notificationsForPage = $notification->getUserNotifications($userId, 100);
    $unreadCount = $notification->getUnreadCount($userId);
} else {
    $notification = null;
}

// Get department name
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;
if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}
$portalLabel = $departmentName ? "Department Portal | " . htmlspecialchars($departmentName) : "Department Portal";

// Check if user is from Admin department
$isAdminDepartment = false;
if ($departmentName && stripos($departmentName, 'admin') !== false) {
    $isAdminDepartment = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Department • Notifications</title>
<link rel="icon" type="image/png" href="../img/evsu_logo.png">
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config={theme:{extend:{colors:{maroon:'#800000','maroon-dark':'#5a0000'}}}}</script>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
  <aside id="sidebar" class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto w-64">
    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
      <div>
        <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
        <p class="text-sm text-gray-600 sidebar-text"><?php echo htmlspecialchars($portalLabel); ?></p>
      </div>
      <button onclick="toggleSidebar()" class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
        <svg id="sidebarToggleIcon" class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"></path>
        </svg>
      </button>
    </div>
    
    <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
  </aside>
  <main class="flex-1 flex flex-col" data-main-content>
    <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
      <div class="px-6 py-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div>
            <h1 class="text-3xl font-bold text-white">Notifications</h1>
            <p class="text-red-100 text-sm mt-1">Department Portal<?php echo $departmentName ? ' | ' . htmlspecialchars($departmentName) : ''; ?></p>
          </div>
          <div class="flex items-center space-x-4">
            <!-- Notification Bell -->
            <?php 
              $notifications = $notificationsForBell;
              include __DIR__ . '/../components/notification_bell.php'; 
            ?>
            
            <div class="relative">
              <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-white bg-opacity-20 backdrop-blur-sm px-4 py-2 rounded-xl hover:bg-opacity-30 transition-colors border border-white border-opacity-30">
                <?php render_profile_avatar(['classes' => 'bg-white bg-opacity-30 text-white font-semibold border border-white border-opacity-50']); ?>
                <div class="text-white">
                  <div class="font-medium"><?php echo htmlspecialchars($username); ?></div>
                  <div class="text-xs text-red-100"><?php echo htmlspecialchars($userEmail); ?></div>
                </div>
                <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
              </button>
              
              <!-- Profile Dropdown -->
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
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Change Password
                  </a>
                  <a href="account_settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    </svg>
                    Settings
                  </a>
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
      <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
          <div>
            <h2 class="text-lg font-semibold text-gray-900">All Notifications</h2>
            <?php if ($unreadCount > 0): ?>
              <p class="text-sm text-gray-500 mt-1"><?php echo $unreadCount; ?> unread <?php echo $unreadCount === 1 ? 'notification' : 'notifications'; ?></p>
            <?php endif; ?>
          </div>
          <div class="flex flex-wrap gap-2">
            <?php if ($unreadCount > 0): ?>
              <button onclick="markAllAsRead()" class="px-4 py-2 bg-maroon/10 text-maroon rounded-lg hover:bg-maroon/20 transition-colors text-sm">Mark all as read</button>
            <?php endif; ?>
            <?php if (!empty($notificationsForPage)): ?>
              <button onclick="location.reload()" class="px-4 py-2 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 transition-colors text-sm">Refresh</button>
            <?php endif; ?>
          </div>
        </div>
        <div class="divide-y divide-gray-200">
          <?php if (empty($notificationsForPage)): ?>
            <div class="px-6 py-12 text-center text-gray-500">
              <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M18.364 5.636A9 9 0 1119 12m-1.5 0A7.5 7.5 0 1012 4.5m-2.837 5.443a4.25 4.25 0 116.01 6.01"></path>
              </svg>
              <p class="text-lg font-medium">No notifications</p>
              <p class="text-sm">You're all caught up! New notifications will appear here.</p>
            </div>
          <?php else: ?>
            <?php foreach ($notificationsForPage as $notif): ?>
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
              <div class="px-6 py-4 hover:bg-gray-50 transition-colors cursor-pointer notification-item <?php echo $notif['is_read'] ? '' : 'bg-blue-50'; ?>" data-notification-id="<?php echo $notif['id']; ?>">
                <div class="flex items-start gap-4">
                  <div class="flex-shrink-0">
                    <div class="w-10 h-10 <?php echo $bgClass; ?> rounded-full flex items-center justify-center">
                      <svg class="w-5 h-5 <?php echo $iconClass; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?php echo $iconPath; ?>
                      </svg>
                    </div>
                  </div>
                  <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between gap-4">
                      <p class="text-sm font-medium text-gray-900 flex items-center gap-2">
                        <?php echo htmlspecialchars($notif['title']); ?>
                        <?php if (!$notif['is_read']): ?>
                          <span class="w-2 h-2 bg-red-500 rounded-full inline-block"></span>
                        <?php endif; ?>
                      </p>
                      <p class="text-xs text-gray-400 whitespace-nowrap">
                        <?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?>
                      </p>
                    </div>
                    <p class="text-sm text-gray-600 mt-2"><?php echo htmlspecialchars($notif['message']); ?></p>
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
    const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
    
    if (!button && !dropdown.contains(event.target)) {
      dropdown.classList.add('hidden');
    }
  });

  function confirmLogout() {
    document.getElementById('logoutModal').classList.remove('hidden');
  }

  function closeLogoutModal() {
    document.getElementById('logoutModal').classList.add('hidden');
  }

  function performLogout() {
    window.location.href = '../auth/logout.php';
  }
  
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

    const notificationItems = document.querySelectorAll('.notification-item');
    notificationItems.forEach(item => {
      item.addEventListener('click', function() {
        const notificationId = this.dataset.notificationId;
        if (notificationId) {
          markNotificationAsRead(notificationId);
        }
      });
    });
  });

  function markNotificationAsRead(notificationId) {
    fetch('../ajax/mark_notification_read.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
      if (data.success) {
        const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
        if (notificationElement) {
          notificationElement.classList.remove('bg-blue-50');
          const dot = notificationElement.querySelector('.bg-red-500');
          if (dot) dot.remove();
        }
        if (typeof updateUnreadCount === 'function') {
          updateUnreadCount();
        }
      }
    })
    .catch(error => console.error('Error:', error));
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

  body.sidebar-collapsed #sidebar #sidebarToggleIcon {
    transform: rotate(180deg);
  }
</style>

</body>
</html>