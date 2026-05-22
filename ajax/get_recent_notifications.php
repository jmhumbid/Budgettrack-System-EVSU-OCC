<?php
header('Content-Type: application/json');
session_start();
require_once __DIR__ . '/../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

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

try {
    $db = getDB();
    $userId = $_SESSION['user_id'];
    
    // Get recent notifications (limit to 5 for dashboard)
    $stmt = $db->prepare("
        SELECT id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate HTML for notifications
    $html = '';
    
    if (empty($notifications)) {
        $html = '
            <div class="text-center py-12 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 102.828 2.828l6.414 6.414a2 2 0 01-2.828 2.828L4.828 7z"></path>
                </svg>
                <p class="font-medium">No notifications</p>
                <p class="text-sm mt-1">You\'ll see important updates here</p>
            </div>
        ';
    } else {
        foreach ($notifications as $notif) {
            $borderClass = '';
            $bgClass = '';
            $iconBgClass = '';
            $iconTextClass = '';
            
            switch($notif['type']) {
                case 'success':
                    $borderClass = 'border-green-500';
                    $bgClass = 'bg-green-50';
                    $iconBgClass = 'bg-green-100';
                    $iconTextClass = 'text-green-600';
                    break;
                case 'error':
                    $borderClass = 'border-red-500';
                    $bgClass = 'bg-red-50';
                    $iconBgClass = 'bg-red-100';
                    $iconTextClass = 'text-red-600';
                    break;
                case 'warning':
                    $borderClass = 'border-yellow-500';
                    $bgClass = 'bg-yellow-50';
                    $iconBgClass = 'bg-yellow-100';
                    $iconTextClass = 'text-yellow-600';
                    break;
                default:
                    $borderClass = 'border-blue-500';
                    $bgClass = 'bg-blue-50';
                    $iconBgClass = 'bg-blue-100';
                    $iconTextClass = 'text-blue-600';
            }
            
            $unreadDot = !$notif['is_read'] ? '<div class="flex-shrink-0"><span class="w-2 h-2 bg-maroon rounded-full"></span></div>' : '';
            $title = htmlspecialchars($notif['title']);
            $message = htmlspecialchars($notif['message'] ?? '');
            $date = date('M j, Y g:i A', strtotime($notif['created_at']));
            $iconPath = getNotificationIcon($notif['title']);
            
            $html .= "
                <div class=\"flex items-start space-x-4 p-4 rounded-xl border-l-4 hover:bg-gray-50 transition-colors {$borderClass} {$bgClass}\">
                    <div class=\"flex-shrink-0\">
                        <div class=\"w-10 h-10 rounded-full flex items-center justify-center {$iconBgClass} {$iconTextClass}\">
                            <svg class=\"w-5 h-5\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">
                                {$iconPath}
                            </svg>
                        </div>
                    </div>
                    <div class=\"flex-1 min-w-0\">
                        <p class=\"font-semibold text-gray-900\">{$title}</p>
                        <p class=\"text-sm text-gray-600 mt-1 line-clamp-2\">{$message}</p>
                        <p class=\"text-xs text-gray-500 mt-2\">{$date}</p>
                    </div>
                    {$unreadDot}
                </div>
            ";
        }
    }
    
    echo json_encode([
        'success' => true,
        'html' => $html
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching notifications: ' . $e->getMessage()
    ]);
}
