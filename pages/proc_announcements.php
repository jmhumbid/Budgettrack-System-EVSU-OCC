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

require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Department.php';
$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);

// Get all departments for selection
$department = new Department();
$allDepartments = $department->getAllDepartments();

// Get announcements from database
require_once __DIR__ . '/../config/database.php';
$announcements = [];
try {
    $db = getDB();
    // Create announcements table if it doesn't exist
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT PRIMARY KEY AUTO_INCREMENT,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        priority ENUM('high', 'medium', 'low') DEFAULT 'medium',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_created_by (created_by),
        INDEX idx_created_at (created_at)
    )");
    
    // Add foreign key constraint if it doesn't exist
    try {
        $checkFk = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE 
                               WHERE TABLE_SCHEMA = DATABASE() 
                               AND TABLE_NAME = 'announcements' 
                               AND COLUMN_NAME = 'created_by' 
                               AND REFERENCED_TABLE_NAME = 'users'");
        if ($checkFk->rowCount() == 0) {
            try {
                $db->exec("ALTER TABLE announcements ADD CONSTRAINT fk_announcements_created_by 
                          FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT");
            } catch (PDOException $e) {
                // Constraint might already exist, ignore
            }
        }
    } catch (PDOException $e) {
        // Ignore if information_schema query fails
    }
    
    // Get announcements with department information
    $stmt = $db->prepare("SELECT a.*, 
                          GROUP_CONCAT(DISTINCT d.dept_name ORDER BY d.dept_name SEPARATOR ', ') as department_names,
                          COUNT(DISTINCT ad.department_id) as department_count,
                          (SELECT COUNT(*) FROM departments WHERE is_active = 1) as total_departments
                          FROM announcements a 
                          LEFT JOIN announcement_departments ad ON a.id = ad.announcement_id
                          LEFT JOIN departments d ON ad.department_id = d.id
                          GROUP BY a.id
                          ORDER BY a.created_at DESC 
                          LIMIT 50");
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table might not exist yet or error occurred
    $announcements = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Procurement Announcements</title>
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
                                    <h1 class="text-3xl font-bold mb-1">Procurement Announcements</h1>
                                    <p class="text-red-100 text-sm">Stay updated on procurement memos and system notices.</p>
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
                <!-- Create Announcement Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-maroon">Create New Announcement</h2>
                        <button onclick="toggleCreateForm()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                            <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            New Announcement
                        </button>
                    </div>
                    
                    <!-- Create Announcement Form -->
                    <div id="createForm" class="hidden">
                        <form class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                                    <input type="text" id="announcementTitle" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent" placeholder="Enter announcement title">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Priority *</label>
                                    <select id="announcementPriority" required class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent">
                                        <option value="">Select Priority</option>
                                        <option value="high">High Priority</option>
                                        <option value="medium">Medium Priority</option>
                                        <option value="low">Low Priority</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Content *</label>
                                <textarea id="announcementContent" required rows="6" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent resize-none" placeholder="Enter announcement content..."></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select Departments *</label>
                                <div class="border border-gray-300 rounded-lg p-4 bg-gray-50 max-h-60 overflow-y-auto">
                                    <div class="mb-3">
                                        <label class="flex items-center space-x-2 cursor-pointer">
                                            <input type="checkbox" id="selectAllDepartments" onchange="toggleAllDepartments()" class="w-4 h-4 text-maroon border-gray-300 rounded focus:ring-maroon">
                                            <span class="text-sm font-semibold text-gray-900">Select All Offices</span>
                                        </label>
                                    </div>
                                    <div class="border-t border-gray-300 pt-3 space-y-2">
                                        <?php foreach ($allDepartments as $dept): ?>
                                            <label class="flex items-center space-x-2 cursor-pointer hover:bg-gray-100 p-2 rounded">
                                                <input type="checkbox" name="selectedDepartments[]" value="<?php echo $dept['id']; ?>" class="department-checkbox w-4 h-4 text-maroon border-gray-300 rounded focus:ring-maroon">
                                                <span class="text-sm text-gray-700"><?php echo htmlspecialchars($dept['dept_name']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">Only selected departments will receive notifications for this announcement.</p>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="cancelCreate()" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                                    Cancel
                                </button>
                                <button type="button" onclick="createAnnouncement()" class="px-6 py-3 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                                    <svg class="w-5 h-5 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                    </svg>
                                    Publish Announcement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Announcements Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-maroon">Important Announcements</h2>
                        <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($announcements); ?> announcements
                        </span>
                    </div>
                    
                    <div class="space-y-6">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="border-l-4 <?php 
                                switch($announcement['priority']) {
                                    case 'high': echo 'border-red-500 bg-red-50'; break;
                                    case 'medium': echo 'border-yellow-500 bg-yellow-50'; break;
                                    case 'low': echo 'border-blue-500 bg-blue-50'; break;
                                    default: echo 'border-gray-500 bg-gray-50'; break;
                                }
                            ?> rounded-lg p-6 hover:shadow-md transition-shadow">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($announcement['title']); ?>
                                            </h3>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full <?php 
                                                switch($announcement['priority']) {
                                                    case 'high': echo 'bg-red-100 text-red-800'; break;
                                                    case 'medium': echo 'bg-yellow-100 text-yellow-800'; break;
                                                    case 'low': echo 'bg-blue-100 text-blue-800'; break;
                                                    default: echo 'bg-gray-100 text-gray-800'; break;
                                                }
                                            ?>">
                                                <?php echo ucfirst($announcement['priority']); ?> Priority
                                            </span>
                                        </div>
                                        <p class="text-gray-700 mb-3">
                                            <?php echo htmlspecialchars($announcement['content']); ?>
                                        </p>
                                        <div class="flex flex-col gap-2 text-sm text-gray-500">
                                            <div class="flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                                <?php echo date('F j, Y', strtotime($announcement['created_at'] ?? $announcement['date'] ?? 'now')); ?>
                                                <span class="mx-2">•</span>
                                                <span>By Procurement Office</span>
                                            </div>
                                            <div class="flex items-center flex-wrap gap-1">
                                                <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                </svg>
                                                <?php 
                                                $deptCount = (int)($announcement['department_count'] ?? 0);
                                                $totalDepts = (int)($announcement['total_departments'] ?? 0);
                                                // Show "All Departments" only if department_count equals total_departments and is greater than 0
                                                if ($deptCount > 0 && $deptCount >= $totalDepts): 
                                                ?>
                                                    <span class="font-medium text-blue-600">All Departments</span>
                                                <?php elseif ($deptCount > 0): 
                                                    $deptNames = $announcement['department_names'] ?? '';
                                                ?>
                                                    <span class="font-medium text-gray-700">Specific Departments:</span>
                                                    <span class="ml-1 text-gray-600"><?php echo htmlspecialchars($deptNames); ?></span>
                                                <?php else: ?>
                                                    <span class="text-gray-400 italic">No departments specified</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="ml-4 flex-shrink-0">
                                        <svg class="w-6 h-6 <?php 
                                            switch($announcement['priority']) {
                                                case 'high': echo 'text-red-600'; break;
                                                case 'medium': echo 'text-yellow-600'; break;
                                                case 'low': echo 'text-blue-600'; break;
                                                default: echo 'text-gray-600'; break;
                                            }
                                        ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if (empty($announcements)): ?>
                            <div class="text-center py-12 text-gray-500">
                                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No announcements</h3>
                                <p class="text-gray-500">Important updates and notices will appear here.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Message Modal -->
    <div id="messageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div id="messageModalIcon" class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4">
                <svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h3 id="messageModalTitle" class="text-xl font-bold text-gray-900 text-center mb-2">Message</h3>
            <p id="messageModalMessage" class="text-gray-600 text-center mb-6"></p>
            <button id="messageModalButton" onclick="closeMessageModal()" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold">
                OK
            </button>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-yellow-100 mb-4">
                <svg class="w-12 h-12 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            </div>
            <h3 id="confirmModalTitle" class="text-xl font-bold text-gray-900 text-center mb-2">Confirm</h3>
            <p id="confirmModalMessage" class="text-gray-600 text-center mb-6"></p>
            <div class="flex gap-3">
                <button onclick="closeConfirmModal()" class="flex-1 px-6 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold">
                    Cancel
                </button>
                <button id="confirmModalConfirmBtn" class="flex-1 px-6 py-3 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors font-semibold">
                    Confirm
                </button>
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
        
        // Close modals when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const logoutModal = document.getElementById('logoutModal');
            if (logoutModal && event.target === logoutModal) {
                closeLogoutModal();
            }
            
            const messageModal = document.getElementById('messageModal');
            if (messageModal && event.target === messageModal) {
                closeMessageModal();
            }
            
            const confirmModal = document.getElementById('confirmModal');
            if (confirmModal && event.target === confirmModal) {
                closeConfirmModal();
            }
        });
        
        // Close message modal on Escape key
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                const messageModal = document.getElementById('messageModal');
                if (messageModal && !messageModal.classList.contains('hidden')) {
                    closeMessageModal();
                }
                
                const confirmModal = document.getElementById('confirmModal');
                if (confirmModal && !confirmModal.classList.contains('hidden')) {
                    closeConfirmModal();
                }
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

        // Preserve sidebar collapse state
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

        // Announcement creation functionality
        function toggleCreateForm() {
            const form = document.getElementById('createForm');
            if (!form) {
                return;
            }
            form.classList.toggle('hidden');
            
            if (!form.classList.contains('hidden')) {
                form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function cancelCreate() {
            const form = document.getElementById('createForm');
            if (form) {
                form.classList.add('hidden');
            }
            document.getElementById('announcementTitle').value = '';
            document.getElementById('announcementPriority').value = '';
            document.getElementById('announcementContent').value = '';
            // Uncheck all department checkboxes
            document.querySelectorAll('.department-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('selectAllDepartments').checked = false;
            document.getElementById('selectAllDepartments').indeterminate = false;
        }

        function toggleAllDepartments() {
            const selectAll = document.getElementById('selectAllDepartments');
            const checkboxes = document.querySelectorAll('.department-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAll.checked;
            });
        }

        // Update "Select All" when individual checkboxes change
        document.addEventListener('DOMContentLoaded', function() {
            const checkboxes = document.querySelectorAll('.department-checkbox');
            const selectAll = document.getElementById('selectAllDepartments');
            
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                    const someChecked = Array.from(checkboxes).some(cb => cb.checked);
                    selectAll.checked = allChecked;
                    selectAll.indeterminate = someChecked && !allChecked;
                });
            });
        });

        // Message Modal Functions
        function showMessageModal(type, title, message) {
            const modal = document.getElementById('messageModal');
            const modalIcon = document.getElementById('messageModalIcon');
            const modalTitle = document.getElementById('messageModalTitle');
            const modalMessage = document.getElementById('messageModalMessage');
            const modalButton = document.getElementById('messageModalButton');
            
            if (!modal) return;
            
            // Set icon and colors based on type
            if (type === 'success') {
                modalIcon.innerHTML = '<svg class="w-12 h-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                modalIcon.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4';
                modalButton.className = 'w-full px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-semibold';
            } else if (type === 'error') {
                modalIcon.innerHTML = '<svg class="w-12 h-12 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                modalIcon.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4';
                modalButton.className = 'w-full px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold';
            } else {
                modalIcon.innerHTML = '<svg class="w-12 h-12 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                modalIcon.className = 'mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-blue-100 mb-4';
                modalButton.className = 'w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold';
            }
            
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            modal.classList.remove('hidden');
        }
        
        function closeMessageModal() {
            const modal = document.getElementById('messageModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        // Confirmation Modal Functions
        function showConfirmModal(title, message, onConfirm) {
            const modal = document.getElementById('confirmModal');
            const modalTitle = document.getElementById('confirmModalTitle');
            const modalMessage = document.getElementById('confirmModalMessage');
            const confirmBtn = document.getElementById('confirmModalConfirmBtn');
            
            if (!modal) {
                // Fallback to browser confirm
                if (confirm(message)) {
                    onConfirm();
                }
                return;
            }
            
            modalTitle.textContent = title;
            modalMessage.textContent = message;
            
            // Remove old event listeners by cloning
            const newConfirmBtn = confirmBtn.cloneNode(true);
            confirmBtn.parentNode.replaceChild(newConfirmBtn, confirmBtn);
            
            newConfirmBtn.addEventListener('click', function() {
                closeConfirmModal();
                onConfirm();
            });
            
            modal.classList.remove('hidden');
        }
        
        function closeConfirmModal() {
            const modal = document.getElementById('confirmModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function createAnnouncement() {
            const title = document.getElementById('announcementTitle').value.trim();
            const priority = document.getElementById('announcementPriority').value;
            const content = document.getElementById('announcementContent').value.trim();
            const selectedDepartments = Array.from(document.querySelectorAll('.department-checkbox:checked')).map(cb => cb.value);

            if (!title || !priority || !content) {
                showMessageModal('error', 'Validation Error', 'Please fill in all required fields.');
                return;
            }

            if (selectedDepartments.length === 0) {
                showMessageModal('error', 'Validation Error', 'Please select at least one department.');
                return;
            }

            showConfirmModal(
                'Confirm Publication',
                'Are you sure you want to publish this announcement to the selected departments?',
                function() {
                    // Send AJAX request
                    fetch('../ajax/create_proc_announcement.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        credentials: 'same-origin',
                        body: JSON.stringify({
                            title: title,
                            priority: priority,
                            content: content,
                            departments: selectedDepartments
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showMessageModal('success', 'Success', data.message || 'Announcement published successfully!');
                            cancelCreate();
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showMessageModal('error', 'Error', data.message || 'Failed to publish announcement');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showMessageModal('error', 'Error', 'An error occurred while publishing the announcement.');
                    });
                }
            );
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