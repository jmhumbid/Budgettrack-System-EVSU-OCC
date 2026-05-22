<?php
session_start();

// Check if user is logged in and has budget access (system administrator)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/BudgetAllocation.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../classes/PurchaseRequest.php';

$user = new User();
$budgetAllocation = new BudgetAllocation();
$notification = new Notification();
$userActivity = new UserActivity();
$fileSubmission = new FileSubmission();
$purchaseRequest = new PurchaseRequest();

// Get real data
$budgetSummary = $budgetAllocation->getBudgetSummary();
$recentActivities = $userActivity->getRecentActivities(10);
$notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
$unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
$submissionCounts = $fileSubmission->getSubmissionCountsByType();

// Get PR statistics
$allPRs = $purchaseRequest->getPRsForProcurement([]);
$prStats = [
    'processing' => 0,
    'delivered' => 0,
    'complete' => 0,
    'total' => count($allPRs)
];
foreach ($allPRs as $pr) {
    if (isset($prStats[$pr['status']])) {
        $prStats[$pr['status']]++;
    }
}

// Get active offices (users with assigned departments)
$activeOffices = $user->getUsersWithDepartments();

// Calculate totals
$totalAllocated = array_sum(array_column($budgetSummary, 'total_allocated'));
$totalUtilized = array_sum(array_column($budgetSummary, 'total_utilized'));
$totalRemaining = array_sum(array_column($budgetSummary, 'total_remaining'));

// Get overall balance from latest utilization summary
$totalBalance = 0;
$fiscalYear = date('Y');
$displayFiscalYear = $fiscalYear; // Track which fiscal year is being displayed
// For budget role on admin dashboard, always show ONLY Budget Office (Fiduciary) data
// Don't use session department_id as it might be from viewing other departments
$departmentId = null;
$utilizationCount = 0;
try {
    require_once __DIR__ . '/../config/database.php';
    $conn = getDB();
    
    // First try to get from utilization_summaries (preferred - shows overall balance from summary)
    // Get the MOST RECENT summary from Budget Office (any fiscal year)
    $checkSummaryTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
    if ($checkSummaryTable->rowCount() > 0) {
        // For budget role, show ONLY Budget Office (Fiduciary) utilization
        // Get the most recently saved/updated summary from ANY fiscal year
        $summaryStmt = $conn->prepare("
            SELECT us.totals, us.department_id, us.department_name, us.fiscal_year
            FROM utilization_summaries us
            INNER JOIN departments d ON us.department_id = d.id
            WHERE d.fiduciary_type = 'Fiduciary'
            ORDER BY COALESCE(us.updated_at, us.created_at) DESC, us.id DESC
            LIMIT 1
        ");
        $summaryStmt->execute();
        $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);
        if ($summaryData && $summaryData['totals']) {
            $totals = json_decode($summaryData['totals'], true);
            if ($totals && isset($totals['totalBalance'])) {
                $totalBalance = floatval($totals['totalBalance']);
            }
            // Count entries from the totals data
            if ($totals && isset($totals['entries']) && is_array($totals['entries'])) {
                $utilizationCount = count($totals['entries']);
            }
            // Get department_id and fiscal_year from the latest summary
            if (isset($summaryData['department_id'])) {
                $departmentId = $summaryData['department_id'];
            }
            if (isset($summaryData['fiscal_year'])) {
                $displayFiscalYear = $summaryData['fiscal_year'];
            }
        }
    }
    
    // Fallback to budget_utilization_entries if no summary found
    if ($totalBalance == 0 && $departmentId === null) {
        $checkTable = $conn->query("SHOW TABLES LIKE 'budget_utilization_entries'");
        if ($checkTable->rowCount() > 0) {
            // Get the most recent fiscal year with Budget Office entries
            $yearStmt = $conn->prepare("
                SELECT bue.fiscal_year 
                FROM budget_utilization_entries bue
                INNER JOIN departments d ON bue.department_id = d.id
                WHERE d.fiduciary_type = 'Fiduciary'
                ORDER BY bue.fiscal_year DESC
                LIMIT 1
            ");
            $yearStmt->execute();
            $yearData = $yearStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($yearData) {
                $displayFiscalYear = $yearData['fiscal_year'];
                
                // For budget role, only get from Budget Office (Fiduciary departments)
                $balanceStmt = $conn->prepare("
                    SELECT 
                        COUNT(*) as entry_count,
                        COALESCE(SUM(CAST(bue.total_balance AS DECIMAL(15,2))), 0) as total_balance,
                        bue.department_id
                    FROM budget_utilization_entries bue
                    INNER JOIN departments d ON bue.department_id = d.id
                    WHERE bue.fiscal_year = ? AND d.fiduciary_type = 'Fiduciary'
                    GROUP BY bue.department_id
                    ORDER BY COALESCE(bue.updated_at, bue.created_at) DESC
                    LIMIT 1
                ");
                $balanceStmt->execute([$displayFiscalYear]);
                $balanceData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
                if ($balanceData) {
                    $totalBalance = floatval($balanceData['total_balance']);
                    $utilizationCount = intval($balanceData['entry_count']);
                    $departmentId = $balanceData['department_id'];
                }
            }
        }
    }
} catch (Exception $e) {
    // Silently fail
}

// Check if user has permission to view admin dashboard
if (!$user->hasPermission($_SESSION['user_id'], 'view_admin_dashboard')) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$userRole = $_SESSION['user_role'];

// User is Budget/Finance Office (system administrator)
$isBudgetOffice = true;
$activeSidebar = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Budget Office Dashboard</title>
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
        <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col" data-main-content>
            <!-- Header with Gradient -->
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
        </div>
                    <div>
                                    <h1 class="text-3xl font-bold mb-1">
                            <?php if ($isBudgetOffice): ?>
                                Budget Office Dashboard
                            <?php else: ?>
                                Admin Dashboard
                            <?php endif; ?>
                        </h1>
                                    <p class="text-red-100 text-sm">
                                        Welcome back, <span class="font-semibold"><?php echo htmlspecialchars($username); ?></span>! 
                            <?php if ($isBudgetOffice): ?>
                                You have full control over the budget system and all user permissions.
                            <?php else: ?>
                                Monitor and manage the budget system.
                            <?php endif; ?>
                        </p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 backdrop-blur-sm text-white border border-white border-opacity-30">
                                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                <?php echo $isBudgetOffice ? 'Budget/Accounting Office - Full Control' : ucfirst($userRole); ?>
                            </span>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Notification Bell -->
                        <?php include __DIR__ . '/../components/notification_bell.php'; ?>
                        
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
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        </svg>
                                        Change Password
                                    </a>
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'budget'): ?>
                                        <a href="super_admin_dashboard.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
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
            
            <!-- Content Area -->
            <div class="flex-1 p-6">
                <!-- KPI Cards -->
                <div class="flex flex-wrap gap-6 mb-8">
                    <a href="cabac.php" class="flex-1 min-w-[280px] bg-gradient-to-br from-maroon to-red-700 rounded-2xl shadow-lg border border-red-100 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group cursor-pointer block text-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-4xl font-extrabold tracking-tight mb-2">CABAC</h2>
                                <p class="text-sm text-red-100 max-w-xs">
                                    Comparative Approve Budget and Actual Collection
                                </p>
                            </div>
                            <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-maroon rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform border border-white/40">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                    
                    <!-- PPMP Submissions Card -->
                    <?php
                    // Count departments with approved PPMP
                    $ppmpDeptCount = 0;
                    try {
                        $stmt = $conn->prepare("
                            SELECT COUNT(DISTINCT department_id) as dept_count
                            FROM ppmp
                            WHERE status = 'approved' AND fiscal_year = ?
                        ");
                        $stmt->execute([$fiscalYear]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $ppmpDeptCount = $result['dept_count'] ?? 0;
                    } catch (Exception $e) {
                        error_log("Error counting PPMP departments: " . $e->getMessage());
                    }
                    ?>
                    <a href="ppmp_view.php" class="flex-1 min-w-[280px] bg-gradient-to-br from-maroon to-red-800 rounded-2xl shadow-lg border border-red-900 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group cursor-pointer block">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-extrabold text-white tracking-tight mb-2">PPMP Submissions</h2>
                                <p class="text-sm text-red-100 max-w-xs">
                                    Departments with approved PPMP
                                </p>
                                <div class="mt-4">
                                    <div class="text-3xl font-bold text-white">
                                        <?php echo number_format($ppmpDeptCount); ?>
                                    </div>
                                    <p class="text-xs uppercase tracking-wider text-red-100">departments submitted</p>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                    
                    <!-- LIB Submissions Card -->
                    <?php
                    // Count departments with approved LIB
                    $libDeptCount = 0;
                    try {
                        $stmt = $conn->prepare("
                            SELECT COUNT(DISTINCT department_id) as dept_count
                            FROM line_item_budgets
                            WHERE status = 'approved' AND fiscal_year = ?
                        ");
                        $stmt->execute([$fiscalYear]);
                        $result = $stmt->fetch(PDO::FETCH_ASSOC);
                        $libDeptCount = $result['dept_count'] ?? 0;
                    } catch (Exception $e) {
                        error_log("Error counting LIB departments: " . $e->getMessage());
                    }
                    ?>
                    <a href="ppmp_view.php" class="flex-1 min-w-[280px] bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl shadow-lg border border-blue-900 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group cursor-pointer block">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-extrabold text-white tracking-tight mb-2">LIB Submissions</h2>
                                <p class="text-sm text-blue-100 max-w-xs">
                                    Departments with approved LIB
                                </p>
                                <div class="mt-4">
                                    <div class="text-3xl font-bold text-white">
                                        <?php echo number_format($libDeptCount); ?>
                                    </div>
                                    <p class="text-xs uppercase tracking-wider text-blue-100">departments submitted</p>
                                </div>
                            </div>
                            <div class="w-16 h-16 bg-white bg-opacity-20 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Allocation Card -->
                    <a href="allocations.php" class="flex-1 min-w-[280px] bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl shadow-lg border border-purple-200 p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 group cursor-pointer block">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-3xl font-extrabold text-gray-900 tracking-tight mb-2">Allocation</h2>
                                <p class="text-sm text-gray-500 max-w-xs">
                                    Manage budget allocations
                                </p>
                            </div>
                            <div class="w-16 h-16 bg-gradient-to-br from-purple-500 to-purple-700 rounded-2xl flex items-center justify-center shadow-xl group-hover:scale-110 transition-transform">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                        </div>
                    </a>
                    
                    <!-- Total Balance for Utilization Card -->
                    <a href="utilization_view_admin.php" class="bg-gradient-to-br <?php echo $totalBalance < 0 ? 'from-red-50 to-red-100 border-red-200' : 'from-green-50 to-green-100 border-green-200'; ?> rounded-2xl shadow-lg border p-6 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 cursor-pointer block">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-600 uppercase tracking-wide mb-2">Total Balance for Utilization</h2>
                                <div class="mt-4">
                                    <div class="text-3xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : 'text-green-700'; ?>">₱<?php echo number_format($totalBalance, 2); ?></div>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo $utilizationCount; ?> <?php echo $utilizationCount == 1 ? 'entry' : 'entries'; ?> • Fiscal Year <?php echo $displayFiscalYear; ?></p>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <div class="w-16 h-16 bg-gradient-to-br <?php echo $totalBalance < 0 ? 'from-red-500 to-red-700' : 'from-green-500 to-green-700'; ?> rounded-2xl flex items-center justify-center shadow-xl">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <?php if ($totalBalance != 0 && isset($departmentId) && $departmentId): ?>
                                <button onclick="event.preventDefault(); showUtilizationBreakdown(<?php echo $departmentId; ?>, <?php echo $displayFiscalYear; ?>);" class="px-3 py-1.5 <?php echo $totalBalance < 0 ? 'bg-red-200 hover:bg-red-300 text-red-800 border-red-300' : 'bg-green-200 hover:bg-green-300 text-green-800 border-green-300'; ?> rounded-lg text-xs font-semibold transition-colors border">
                                    View Details
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                </div>
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="submit_documents.php" class="flex items-center space-x-3 p-4 bg-gradient-to-r from-blue-50 to-blue-100 rounded-lg hover:from-blue-100 hover:to-blue-200 transition-all border border-blue-200">
                            <div class="w-12 h-12 bg-blue-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Upload</div>
                                <div class="text-sm text-gray-600">Upload documents</div>
                            </div>
                        </a>
                        <a href="admin_pr_submission.php" class="flex items-center space-x-3 p-4 bg-gradient-to-r from-maroon/10 to-red-100 rounded-lg hover:from-red-100 hover:to-red-200 transition-all border border-red-200">
                            <div class="w-12 h-12 bg-maroon rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5h6l2 5H7l2-5zM7 10l1 4h8l1-4m-4 5v4m-4-4v4"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">PR Submission</div>
                                <div class="text-sm text-gray-600">View purchase requests</div>
                            </div>
                        </a>
                        <a href="utilization.php" class="flex items-center space-x-3 p-4 bg-gradient-to-r from-green-50 to-green-100 rounded-lg hover:from-green-100 hover:to-green-200 transition-all border border-green-200">
                            <div class="w-12 h-12 bg-green-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Utilization</div>
                                <div class="text-sm text-gray-600">Track budget utilization</div>
                            </div>
                        </a>
                        <a href="utilization_view_admin.php" class="flex items-center space-x-3 p-4 bg-gradient-to-r from-orange-50 to-orange-100 rounded-lg hover:from-orange-100 hover:to-orange-200 transition-all border border-orange-200">
                            <div class="w-12 h-12 bg-orange-600 rounded-lg flex items-center justify-center">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                            </div>
                            <div>
                                <div class="font-semibold text-gray-900">Utilization View</div>
                                <div class="text-sm text-gray-600">View budget utilization</div>
                            </div>
                        </a>
                    </div>
                </div>
                
                <!-- Active Users Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Active Department Users</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($activeOffices)): ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-4 text-center text-gray-500">No active department users found</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($activeOffices as $officeUser): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="w-10 h-10 bg-maroon rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                                        <?php echo strtoupper(substr($officeUser['first_name'], 0, 1) . substr($officeUser['last_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900">
                                                            <?php echo htmlspecialchars($officeUser['first_name'] . ' ' . $officeUser['last_name']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <div class="flex items-center gap-2">
                                                    <span><?php echo htmlspecialchars($officeUser['dept_name'] ?? 'No Department'); ?></span>
                                                    <?php 
                                                    // Get fiduciary type for this department
                                                    $deptId = $officeUser['department_id'] ?? null;
                                                    if ($deptId) {
                                                        try {
                                                            $conn = getDB();
                                                            $deptStmt = $conn->prepare("SELECT fiduciary_type FROM departments WHERE id = :id");
                                                            $deptStmt->bindParam(':id', $deptId);
                                                            $deptStmt->execute();
                                                            $deptData = $deptStmt->fetch(PDO::FETCH_ASSOC);
                                                            if ($deptData) {
                                                                $fidType = $deptData['fiduciary_type'] ?? 'Non-Fiduciary';
                                                                $isFid = ($fidType === 'Fiduciary');
                                                                echo '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' . ($isFid ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . '">' . htmlspecialchars($fidType) . '</span>';
                                                            }
                                                        } catch (Exception $e) {}
                                                    }
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($officeUser['role_name'] ?? 'No Role'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($officeUser['email'] ?? 'No Email'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                    Active
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- System Notifications -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">System Notifications</h3>
                        <div class="space-y-4">
                            <?php if (empty($notifications)): ?>
                                <p class="text-gray-500 text-center py-4">No notifications</p>
                            <?php else: ?>
                                <?php foreach ($notifications as $notif): ?>
                                    <?php
                                    $bgColor = '';
                                    $borderColor = '';
                                    $textColor = '';
                                    $iconColor = '';
                                    
                                    switch ($notif['type']) {
                                        case 'success':
                                            $bgColor = 'bg-green-50';
                                            $borderColor = 'border-green-400';
                                            $textColor = 'text-green-800';
                                            $iconColor = 'text-green-600';
                                            break;
                                        case 'warning':
                                            $bgColor = 'bg-yellow-50';
                                            $borderColor = 'border-yellow-400';
                                            $textColor = 'text-yellow-800';
                                            $iconColor = 'text-yellow-600';
                                            break;
                                        case 'error':
                                            $bgColor = 'bg-red-50';
                                            $borderColor = 'border-red-400';
                                            $textColor = 'text-red-800';
                                            $iconColor = 'text-red-600';
                                            break;
                                        default:
                                            $bgColor = 'bg-blue-50';
                                            $borderColor = 'border-blue-400';
                                            $textColor = 'text-blue-800';
                                            $iconColor = 'text-blue-600';
                                    }
                                    ?>
                                    <div class="flex items-start space-x-3 p-3 <?php echo $bgColor; ?> rounded-lg border-l-4 <?php echo $borderColor; ?>">
                                        <svg class="w-5 h-5 <?php echo $iconColor; ?> mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                            <p class="font-medium <?php echo $textColor; ?>"><?php echo htmlspecialchars($notif['title']); ?></p>
                                            <p class="text-sm <?php echo $iconColor; ?>"><?php echo htmlspecialchars($notif['message']); ?></p>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo date('M j, Y g:i A', strtotime($notif['created_at'])); ?></p>
                                </div>
                            </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                    </div>
                </div>
                
                <!-- Budget/Accounting Office Controls -->
                <?php if ($isBudgetOffice): ?>
                <div class="bg-red-50 border border-red-200 rounded-xl shadow-sm p-6 mb-8">
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-red-600 rounded-full flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-red-800">Budget/Accounting Office Controls</h3>
                    </div>
                    <p class="text-red-700 mb-4">As Budget/Accounting Office, you have complete control over the system, including admin permissions.</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <a href="user_management.php" class="flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Manage All Users
                        </a>
                        <a href="role_management.php" class="flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Control Admin Permissions
                        </a>
                        <a href="admin_control.php" class="flex items-center justify-center px-4 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            System Override
                        </a>
                    </div>
                </div>
                <?php endif; ?>

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
        
        // Close modal when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (modal && event.target === modal) {
                closeLogoutModal();
            }
        });
        
        function showUtilizationBreakdown(departmentId, fiscalYear) {
            // Fetch utilization breakdown data
            fetch(`../api/get_utilization_breakdown.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUtilizationBreakdown(data.data);
                    } else {
                        alert('Error loading utilization breakdown: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading utilization breakdown');
                });
        }
        
        function displayUtilizationBreakdown(data) {
            const modal = document.getElementById('utilizationBreakdownModal');
            if (!modal) {
                // Create modal if it doesn't exist
                const modalHTML = `
                    <div id="utilizationBreakdownModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
                            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6 rounded-t-2xl flex justify-between items-center">
                                <h2 class="text-2xl font-bold">Budget Utilization Breakdown</h2>
                                <button onclick="closeUtilizationBreakdown()" class="text-white hover:text-red-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="utilizationBreakdownContent" class="p-6">
                                <!-- Content will be inserted here -->
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            }
            
            const content = document.getElementById('utilizationBreakdownContent');
            if (!content) return;
            
            const utilizationEntries = data.utilization_entries || [];
            const prEntries = data.pr_entries || [];
            const travelsEntries = data.travels_entries || [];
            const honorariaEntries = data.honoraria_entries || [];
            const prDeductions = data.pr_deductions || [];
            const travelsDeductions = data.travels_deductions || [];
            const honorariaDeductions = data.honoraria_deductions || [];
            const totals = data.totals || {};
            
            // Helper function to format numbers
            function formatNumber(num) {
                return '₱' + parseFloat(num || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
            
            let html = `
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Summary Overview</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <p class="text-sm text-gray-600 mb-1">Total Allocated</p>
                            <p class="text-xl font-bold text-blue-700">${formatNumber(totals.totalAllocated || 0)}</p>
                        </div>
                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <p class="text-sm text-gray-600 mb-1">Total Deductions</p>
                            <p class="text-xl font-bold text-red-700">${formatNumber(totals.totalDeductions || 0)}</p>
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <p class="text-sm text-gray-600 mb-1">Total Balance</p>
                            <p class="text-xl font-bold ${(totals.totalBalance || 0) < 0 ? 'text-red-700' : 'text-green-700'}">${formatNumber(totals.totalBalance || 0)}</p>
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                            <p class="text-sm text-gray-600 mb-1">Fiscal Year</p>
                            <p class="text-xl font-bold text-purple-700">${data.fiscal_year || new Date().getFullYear()}</p>
                        </div>
                    </div>
                </div>
            `;
            
            // Purchase Requests Breakdown
            if (prEntries.length > 0) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Purchase Requests</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">PR Number</th><th class="border p-2 text-left">Particulars</th><th class="border p-2 text-left">Date</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                
                prEntries.forEach(entry => {
                    const particulars = entry.particulars ? (entry.particulars.length > 50 ? entry.particulars.substring(0, 50) + '...' : entry.particulars) : '-';
                    html += `<tr><td class="border p-2">${entry.prNumber || entry.purchaseRequest || '-'}</td>`;
                    html += `<td class="border p-2">${particulars}</td>`;
                    html += `<td class="border p-2">${entry.date || '-'}</td>`;
                    html += `<td class="border p-2 text-right text-blue-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                });
                
                html += `<tr class="bg-blue-50"><td colspan="3" class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-blue-700">${formatNumber(totals.prTotal || 0)}</td></tr>`;
                html += '</tbody></table></div></div>';
            }
            
            // Travels Breakdown
            if (travelsEntries.length > 0) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Travels</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Travelled</th><th class="border p-2 text-left">Event/Activity</th><th class="border p-2 text-left">Date</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                
                travelsEntries.forEach(entry => {
                    const event = (entry.event_activity || entry.event) ? ((entry.event_activity || entry.event).length > 50 ? (entry.event_activity || entry.event).substring(0, 50) + '...' : (entry.event_activity || entry.event)) : '-';
                    html += `<tr><td class="border p-2">${entry.travelled || '-'}</td>`;
                    html += `<td class="border p-2">${event}</td>`;
                    html += `<td class="border p-2">${entry.date || '-'}</td>`;
                    html += `<td class="border p-2 text-right text-green-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                });
                
                html += `<tr class="bg-green-50"><td colspan="3" class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-green-700">${formatNumber(totals.travelsTotal || 0)}</td></tr>`;
                html += '</tbody></table></div></div>';
            }
            
            // Honoraria Breakdown
            if (honorariaEntries.length > 0) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Honoraria</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Date</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                
                honorariaEntries.forEach(entry => {
                    html += `<tr><td class="border p-2">${entry.date || '-'}</td>`;
                    html += `<td class="border p-2 text-right text-red-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                });
                
                html += `<tr class="bg-red-50"><td class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-red-700">${formatNumber(totals.honorariaTotal || 0)}</td></tr>`;
                html += '</tbody></table></div></div>';
            }
            
            // Purchase Request Deductions Breakdown
            if (prDeductions.length > 0) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Purchase Request Deductions</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Expense Category</th><th class="border p-2 text-left">Purchase Request</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                
                let prDeductionsTotal = 0;
                prDeductions.forEach(entry => {
                    prDeductionsTotal += entry.amount || 0;
                    
                    // If there are multiple items, create rows for each
                    if (entry.items && entry.items.length > 0) {
                        entry.items.forEach((item, index) => {
                            // Only show category name on first row
                            if (index === 0) {
                                html += `<tr><td class="border p-2 font-semibold align-top" rowspan="${entry.items.length}">${entry.category || '-'}</td>`;
                                html += `<td class="border p-2">${item.purchaseRequest || '-'}</td>`;
                                html += `<td class="border p-2 text-right text-blue-600 align-top font-semibold" rowspan="${entry.items.length}">${formatNumber(entry.amount || 0)}</td></tr>`;
                            } else {
                                html += `<tr><td class="border p-2">${item.purchaseRequest || '-'}</td></tr>`;
                            }
                        });
                    } else {
                        // Fallback for entries without items array
                        html += `<tr><td class="border p-2">${entry.category || '-'}</td>`;
                        html += `<td class="border p-2">-</td>`;
                        html += `<td class="border p-2 text-right text-blue-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                    }
                });
                
                html += `<tr class="bg-blue-50"><td class="border p-2 font-semibold text-right" colspan="2">Total:</td><td class="border p-2 text-right font-bold text-blue-700">${formatNumber(prDeductionsTotal)}</td></tr>`;
                html += '</tbody></table></div></div>';
            }
            
            // Travels Deductions Breakdown
            if (travelsDeductions.length > 0) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Travels Deductions</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Expense Category</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                
                let travelsDeductionsTotal = 0;
                travelsDeductions.forEach(entry => {
                    travelsDeductionsTotal += entry.amount || 0;
                    html += `<tr><td class="border p-2">${entry.category || '-'}</td>`;
                    html += `<td class="border p-2 text-right text-green-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                });
                
                html += `<tr class="bg-green-50"><td class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-green-700">${formatNumber(travelsDeductionsTotal)}</td></tr>`;
                html += '</tbody></table></div></div>';
            }
            
            // Honoraria Deductions Breakdown
            if (honorariaDeductions.length > 0) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Honoraria Deductions</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Expense Category</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                
                let honorariaDeductionsTotal = 0;
                honorariaDeductions.forEach(entry => {
                    honorariaDeductionsTotal += entry.amount || 0;
                    html += `<tr><td class="border p-2">${entry.category || '-'}</td>`;
                    html += `<td class="border p-2 text-right text-purple-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                });
                
                html += `<tr class="bg-purple-50"><td class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-purple-700">${formatNumber(honorariaDeductionsTotal)}</td></tr>`;
                html += '</tbody></table></div></div>';
            }
            
            // Budget Utilization Breakdown (moved to last)
            html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Budget Utilization Breakdown</h4>';
            html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
            html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Expense Category</th><th class="border p-2 text-left">Account Code</th><th class="border p-2 text-right">Allocated Budget</th><th class="border p-2 text-right">Deductions</th><th class="border p-2 text-right">Total Balance</th></tr></thead><tbody>';
            
            if (utilizationEntries.length === 0) {
                html += '<tr><td colspan="5" class="border p-2 text-center text-gray-500 italic">No utilization entries found</td></tr>';
            } else {
                utilizationEntries.forEach(entry => {
                    html += `<tr><td class="border p-2">${entry.category || '-'}</td>`;
                    html += `<td class="border p-2">${entry.accountCode || entry.account_code || '-'}</td>`;
                    html += `<td class="border p-2 text-right">${formatNumber(entry.allocated || 0)}</td>`;
                    html += `<td class="border p-2 text-right text-red-600">${formatNumber(entry.deduction || 0)}</td>`;
                    html += `<td class="border p-2 text-right font-semibold ${(entry.balance || 0) < 0 ? 'text-red-600' : 'text-green-600'}">${formatNumber(entry.balance || 0)}</td></tr>`;
                });
            }
            html += '</tbody></table></div></div>';
            
            content.innerHTML = html;
            document.getElementById('utilizationBreakdownModal').classList.remove('hidden');
        }
        
        function closeUtilizationBreakdown() {
            const modal = document.getElementById('utilizationBreakdownModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
    </script>

</body>
</html>