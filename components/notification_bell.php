<?php
// Notification Bell Component
// Usage: include this file in any page that needs a notification bell
// Requires: $unreadCount variable to be set before including this component
?>

<style>
    /* Hide elements with x-cloak until Alpine.js is ready - CRITICAL for preventing flash */
    [x-cloak] { 
        display: none !important; 
    }
    /* Only hide dropdown when it has x-cloak (before Alpine initializes) */
    /* Alpine.js will remove x-cloak and control visibility via x-show="open" */
    .notification-dropdown[x-cloak] {
        display: none !important;
    }
</style>

<div class="relative" x-data="{ open: false }" x-init="
    $watch('open', value => {
        if (value === true) {
            markAllNotificationsAsRead();
        }
    })
">
    <!-- Notification Bell Button -->
    <!-- White text with semi-transparent white background for colored headers (red/blue gradients) -->
    <!-- For white backgrounds, JavaScript will adapt the styling -->
    <button @click="open = !open" class="notification-bell relative p-3 text-white bg-white bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30 focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50 rounded-full transition-colors duration-200 border border-white border-opacity-30">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>
        <!-- Yellow badge for unread notifications - visible on both white and colored backgrounds -->
        <?php if (isset($unreadCount) && $unreadCount > 0): ?>
            <span class="absolute -top-1 -right-1 bg-yellow-400 text-gray-900 text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold border-2 border-white shadow-lg animate-pulse z-10">
                <?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?>
            </span>
        <?php endif; ?>
    </button>
    <script>
        // Adapt notification bell for white backgrounds
        document.addEventListener('DOMContentLoaded', function() {
            const bell = document.querySelector('.notification-bell');
            if (bell) {
                // Check if parent header or nearby element has white background
                const header = bell.closest('header');
                const parent = bell.closest('div');
                const isWhiteBg = (header && header.classList.contains('bg-white')) || 
                                 (parent && window.getComputedStyle(parent).backgroundColor.includes('rgb(255, 255, 255)')) ||
                                 (parent && window.getComputedStyle(parent).backgroundColor === 'white');
                
                if (isWhiteBg) {
                    bell.classList.remove('text-white', 'bg-white', 'bg-opacity-20', 'border-white', 'border-opacity-30');
                    bell.classList.add('text-maroon', 'bg-red-50', 'border-red-200', 'hover:bg-red-100');
                    bell.querySelector('svg').style.stroke = '#800000';
                }
            }
        });
    </script>
    
    <!-- Notification Dropdown -->
    <div x-show="open" @click.away="open = false" x-transition x-cloak class="notification-dropdown absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
        <div class="p-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Notifications</h3>
                <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                    <button onclick="markAllAsRead()" class="text-sm text-red-600 hover:text-red-800">Mark all as read</button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="max-h-96 overflow-y-auto" id="notificationsContainer">
            <?php 
            // Ensure notifications variable exists and is an array
            if (!isset($notifications) || !is_array($notifications)) {
                $notifications = [];
            }
            if (empty($notifications)): ?>
                <div class="p-4 text-center text-gray-500">
                    <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                    </svg>
                    <p>No notifications</p>
                    <?php if (isset($unreadCount) && $unreadCount > 0): ?>
                        <p class="text-xs text-gray-400 mt-2">You have <?php echo $unreadCount; ?> unread notification(s). <a href="<?php echo isset($notifications_page) ? $notifications_page : 'notifications.php'; ?>" class="text-red-600 hover:underline">View all</a></p>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="p-4 border-b border-gray-100 hover:bg-gray-50 cursor-pointer" onclick="markAsRead(<?php echo $notif['id']; ?>)">
                        <div class="flex items-start space-x-3">
                            <div class="flex-shrink-0">
                                <?php
                                $iconClass = '';
                                $bgClass = '';
                                switch ($notif['type']) {
                                    case 'success':
                                        $iconClass = 'text-green-600';
                                        $bgClass = 'bg-green-100';
                                        break;
                                    case 'warning':
                                        $iconClass = 'text-yellow-600';
                                        $bgClass = 'bg-yellow-100';
                                        break;
                                    case 'error':
                                        $iconClass = 'text-red-600';
                                        $bgClass = 'bg-red-100';
                                        break;
                                    default:
                                        $iconClass = 'text-blue-600';
                                        $bgClass = 'bg-blue-100';
                                }
                                ?>
                                <div class="w-8 h-8 <?php echo $bgClass; ?> rounded-full flex items-center justify-center">
                                    <svg class="w-4 h-4 <?php echo $iconClass; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($notif['title']); ?></p>
                                <p class="text-sm text-gray-500 truncate"><?php echo htmlspecialchars($notif['message']); ?></p>
                                <p class="text-xs text-gray-400 mt-1"><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></p>
                            </div>
                            <?php if (!$notif['is_read']): ?>
                                <div class="w-2 h-2 bg-red-500 rounded-full flex-shrink-0"></div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="p-4 border-t border-gray-200">
            <div class="flex justify-between items-center">
                <?php
                // Determine the correct notifications page based on user role
                $notifications_page = 'notifications.php'; // Default for department users
                if (isset($_SESSION['user_role'])) {
                    if (in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
                        $notifications_page = 'notifications_admin.php';
                    }
                }
                ?>
                <a href="<?php echo $notifications_page; ?>" class="text-sm text-red-600 hover:text-red-800">View all notifications</a>
                <?php if (!empty($notifications)): ?>
                    <button onclick="clearNotifications()" class="text-sm text-gray-500 hover:text-gray-700">Clear all</button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
// Clear any existing localStorage that might be hiding notifications
if (localStorage.getItem('notificationsCleared') === 'true') {
    localStorage.removeItem('notificationsCleared');
}

function markAsRead(notificationId) {
    // AJAX call to mark notification as read
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
            // Update UI - remove red dot and mark as read
            // Only target notification red dots (w-2 h-2 rounded-full) to avoid removing delete buttons
            const notificationElement = document.querySelector(`[onclick="markAsRead(${notificationId})"]`);
            if (notificationElement) {
                // Target the specific notification dot with classes w-2 h-2 bg-red-500 rounded-full
                const redDot = notificationElement.querySelector('.w-2.h-2.bg-red-500.rounded-full');
                if (redDot) {
                    redDot.remove();
                } else {
                    // Fallback: look for small rounded red dots within this notification element
                    const allRedDots = notificationElement.querySelectorAll('.bg-red-500.rounded-full');
                    allRedDots.forEach(dot => {
                        // Only remove if it's a small notification dot (check computed size)
                        const computedStyle = window.getComputedStyle(dot);
                        const width = parseFloat(computedStyle.width);
                        const height = parseFloat(computedStyle.height);
                        if (width <= 10 && height <= 10) {
                            dot.remove();
                        }
                    });
                }
                notificationElement.classList.remove('bg-blue-50');
            }
            // Update unread count
            updateUnreadCount();
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllNotificationsAsRead() {
    // AJAX call to mark all notifications as read
    fetch('../ajax/mark_all_notifications_read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin',
        body: JSON.stringify({})
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove all unread indicators and update UI
            // Only target notification red dots (w-2 h-2 rounded-full) to avoid removing delete buttons
            document.querySelectorAll('.w-2.h-2.bg-red-500.rounded-full').forEach(dot => dot.remove());
            // Fallback: target red dots only within notification container
            const notificationsContainer = document.getElementById('notificationsContainer');
            if (notificationsContainer) {
                notificationsContainer.querySelectorAll('.bg-red-500.rounded-full').forEach(dot => {
                    // Only remove if it's a small notification dot (w-2 h-2 or similar small size)
                    const computedStyle = window.getComputedStyle(dot);
                    const width = parseFloat(computedStyle.width);
                    const height = parseFloat(computedStyle.height);
                    if (width <= 10 && height <= 10) {
                        dot.remove();
                    }
                });
            }
            document.querySelectorAll('[onclick^="markAsRead"]').forEach(notif => {
                notif.classList.remove('bg-blue-50');
            });
            // Hide the notification badge
            const badge = document.querySelector('.bg-yellow-400');
            if (badge) badge.style.display = 'none';
            // Hide mark all as read button
            const markAllButton = document.querySelector('[onclick="markAllAsRead()"]');
            if (markAllButton) markAllButton.style.display = 'none';
        }
    })
    .catch(error => console.error('Error:', error));
}

function markAllAsRead() {
    markAllNotificationsAsRead();
}

function clearNotifications() {
    if (confirm('Are you sure you want to clear all notifications from this dropdown? This will only hide them temporarily. Refresh the page to see them again.')) {
        // Clear the notifications dropdown content temporarily
        clearNotificationsUI();
        // Note: We don't store in localStorage anymore to prevent permanent hiding
    }
}

function clearNotificationsUI() {
    const notificationsContainer = document.getElementById('notificationsContainer');
    if (notificationsContainer) {
        notificationsContainer.innerHTML = `
            <div class="p-4 text-center text-gray-500">
                <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                <p>No notifications</p>
            </div>
        `;
    }
    
    // Hide the "Mark all as read" and "Clear all" buttons
    const markAllButton = document.querySelector('[onclick="markAllAsRead()"]');
    const clearButton = document.querySelector('[onclick="clearNotifications()"]');
    if (markAllButton) markAllButton.style.display = 'none';
    if (clearButton) clearButton.style.display = 'none';
    
    // Hide the notification badge
    const badge = document.querySelector('.bg-yellow-400');
    if (badge) badge.style.display = 'none';
}

function updateUnreadCount() {
    // Update the yellow badge count in the notification bell
    const badge = document.querySelector('.bg-yellow-400');
    if (badge) {
        const currentCount = parseInt(badge.textContent.replace('+', '')) || 0;
        const newCount = Math.max(0, currentCount - 1);
        if (newCount === 0) {
            badge.style.display = 'none';
        } else {
            badge.textContent = newCount > 9 ? '9+' : newCount;
        }
    }
    // Also hide the badge if count reaches 0
    if (badge && parseInt(badge.textContent.replace('+', '')) === 0) {
        badge.style.display = 'none';
    }
}

// Function to reset cleared state (for testing or if needed)
function resetNotificationsState() {
    localStorage.removeItem('notificationsCleared');
    location.reload();
}

// Real-time notification polling
let lastNotificationCheck = Date.now();
let notificationCheckInterval = null;
let lastKnownCount = <?php echo isset($unreadCount) ? $unreadCount : 0; ?>;

function checkForNewNotifications() {
    fetch('../ajax/get_unread_notifications.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const currentUnreadCount = data.unread_count || 0;
            const badge = document.querySelector('.notification-bell .bg-yellow-400');
            
            // Check if there are new notifications
            const hasNewNotifications = currentUnreadCount > lastKnownCount;
            lastKnownCount = currentUnreadCount;

            // If new notifications arrived, check if any are utilization summary updates
            // and dispatch a custom event so utilization pages can auto-refresh
            if (hasNewNotifications && data.latest && data.latest.length > 0) {
                const hasUtilizationUpdate = data.latest.some(n =>
                    n.title && n.title.toLowerCase().includes('budget utilization summary')
                );
                if (hasUtilizationUpdate) {
                    window.dispatchEvent(new CustomEvent('utilizationSummaryUpdated'));
                }
            }
            
            // Update badge
            if (currentUnreadCount > 0) {
                if (badge) {
                    badge.textContent = currentUnreadCount > 9 ? '9+' : currentUnreadCount;
                    badge.style.display = 'flex';
                } else {
                    // Create badge if it doesn't exist
                    const bellButton = document.querySelector('.notification-bell');
                    if (bellButton) {
                        const newBadge = document.createElement('span');
                        newBadge.className = 'absolute -top-1 -right-1 bg-yellow-400 text-gray-900 text-xs rounded-full h-5 w-5 flex items-center justify-center font-bold border-2 border-white shadow-lg animate-pulse z-10';
                        newBadge.textContent = currentUnreadCount > 9 ? '9+' : currentUnreadCount;
                        bellButton.appendChild(newBadge);
                    }
                }
                
                // ALWAYS refresh notifications dropdown content when there are new notifications
                // This ensures the notification details are updated in real-time
                if (hasNewNotifications) {
                    refreshNotificationsDropdown();
                }
                
                // Also refresh if dropdown is currently open
                const dropdown = document.querySelector('.notification-dropdown');
                if (dropdown && !dropdown.hasAttribute('x-cloak') && dropdown.style.display !== 'none') {
                    refreshNotificationsDropdown();
                }
            } else {
                if (badge) {
                    badge.style.display = 'none';
                }
            }
        }
    })
    .catch(error => {
        console.error('Error checking notifications:', error);
    });
}

function refreshNotificationsDropdown() {
    fetch('../ajax/get_notifications_dropdown.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.html) {
            const container = document.getElementById('notificationsContainer');
            if (container) {
                container.innerHTML = data.html;
            }
        }
    })
    .catch(error => {
        console.error('Error refreshing notifications:', error);
    });
}

// Start polling when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Check immediately
    checkForNewNotifications();
    
    // Then check every 10 seconds
    notificationCheckInterval = setInterval(checkForNewNotifications, 10000);
});

// Stop polling when page is hidden (tab switched)
document.addEventListener('visibilitychange', function() {
    if (document.hidden) {
        if (notificationCheckInterval) {
            clearInterval(notificationCheckInterval);
            notificationCheckInterval = null;
        }
    } else {
        // Resume polling when tab becomes visible again
        if (!notificationCheckInterval) {
            checkForNewNotifications();
            notificationCheckInterval = setInterval(checkForNewNotifications, 10000);
        }
    }
});

</script>


<!-- Include Alpine.js for dropdown functionality -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
