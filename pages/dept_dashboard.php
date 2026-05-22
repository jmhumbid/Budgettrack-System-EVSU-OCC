<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../classes/Notification.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userRole = isset($_SESSION['user_role']) ? $_SESSION['user_role'] : 'user';
include __DIR__ . '/../components/profile_avatar.php';
$userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
$departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;

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

// Get file submissions
$fileSubmission = new FileSubmission();
$submissions = $fileSubmission->getUserSubmissions($userId, 5);

// Get notifications
$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);

// Calculate statistics
$allUserSubmissions = $fileSubmission->getUserSubmissions($userId, 100);
$totalSubmissions = count($allUserSubmissions);

// Get overall balance from utilization summary for selected fiscal year
$totalBalance = 0;
$utilizationCount = 0;
$selectedUtilizationYear = isset($_GET['utilization_year']) ? intval($_GET['utilization_year']) : date('Y');
$availableUtilizationYears = [];

if ($departmentId) {
    try {
        require_once __DIR__ . '/../config/database.php';
        $conn = getDB();
        
        // Get all available fiscal years for utilization
        $checkSummaryTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
        if ($checkSummaryTable->rowCount() > 0) {
            $yearsStmt = $conn->prepare("
                SELECT DISTINCT fiscal_year 
                FROM utilization_summaries 
                WHERE department_id = ?
                ORDER BY fiscal_year DESC
            ");
            $yearsStmt->execute([$departmentId]);
            $availableUtilizationYears = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
        }
        
        // If no years from summaries, check entries table
        if (empty($availableUtilizationYears)) {
            $checkTable = $conn->query("SHOW TABLES LIKE 'budget_utilization_entries'");
            if ($checkTable->rowCount() > 0) {
                $yearsStmt = $conn->prepare("
                    SELECT DISTINCT fiscal_year 
                    FROM budget_utilization_entries 
                    WHERE department_id = ?
                    ORDER BY fiscal_year DESC
                ");
                $yearsStmt->execute([$departmentId]);
                $availableUtilizationYears = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
            }
        }
        
        // If no years found, use current year
        if (empty($availableUtilizationYears)) {
            $availableUtilizationYears = [date('Y')];
        }
        
        // If selected year not in available years, use the most recent
        if (!in_array($selectedUtilizationYear, $availableUtilizationYears)) {
            $selectedUtilizationYear = $availableUtilizationYears[0];
        }
        
        // Get utilization data for selected fiscal year
        $foundSummary = false;
        $checkSummaryTable = $conn->query("SHOW TABLES LIKE 'utilization_summaries'");
        if ($checkSummaryTable->rowCount() > 0) {
            $summaryStmt = $conn->prepare("
                SELECT totals, fiscal_year
                FROM utilization_summaries 
                WHERE department_id = ? AND fiscal_year = ?
                ORDER BY updated_at DESC, created_at DESC
                LIMIT 1
            ");
            $summaryStmt->execute([$departmentId, $selectedUtilizationYear]);
            $summaryData = $summaryStmt->fetch(PDO::FETCH_ASSOC);
            if ($summaryData && $summaryData['totals']) {
                $totals = json_decode($summaryData['totals'], true);
                if ($totals && isset($totals['totalBalance'])) {
                    $totalBalance = floatval($totals['totalBalance']);
                    $foundSummary = true;
                }
                // Count entries from the totals data (if available)
                if ($totals && isset($totals['entries']) && is_array($totals['entries'])) {
                    $utilizationCount = count($totals['entries']);
                }
            }
        }
        
        // Always get the count from budget_utilization_entries table (more reliable)
        $checkTable = $conn->query("SHOW TABLES LIKE 'budget_utilization_entries'");
        if ($checkTable->rowCount() > 0) {
            $countStmt = $conn->prepare("
                SELECT COUNT(*) as entry_count
                FROM budget_utilization_entries 
                WHERE department_id = ? AND fiscal_year = ?
            ");
            $countStmt->execute([$departmentId, $selectedUtilizationYear]);
            $countData = $countStmt->fetch(PDO::FETCH_ASSOC);
            if ($countData) {
                $utilizationCount = intval($countData['entry_count']);
            }
            
            // If no summary was found, also get the balance from entries
            if (!$foundSummary) {
                $balanceStmt = $conn->prepare("
                    SELECT COALESCE(SUM(CAST(total_balance AS DECIMAL(15,2))), 0) as total_balance 
                    FROM budget_utilization_entries 
                    WHERE department_id = ? AND fiscal_year = ?
                ");
                $balanceStmt->execute([$departmentId, $selectedUtilizationYear]);
                $balanceData = $balanceStmt->fetch(PDO::FETCH_ASSOC);
                if ($balanceData) {
                    $totalBalance = floatval($balanceData['total_balance']);
                }
            }
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// Get latest submissions by type (for submission status section)
$latestPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
$latestLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
$latestApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');

// Get PPMP statistics (approved/final only)
$ppmpCount = 0;
$ppmpTotal = 0;
$ppmpFiscalYear = date('Y');
if ($departmentId) {
    try {
        $ppmpStmt = $conn->prepare("
            SELECT COUNT(DISTINCT p.id) as count,
                   COALESCE(SUM(pi.estimated_budget), 0) as total,
                   p.fiscal_year
            FROM ppmp p
            LEFT JOIN ppmp_items pi ON p.id = pi.ppmp_id
            WHERE p.department_id = ? AND p.status = 'approved'
            GROUP BY p.fiscal_year
            ORDER BY p.fiscal_year DESC
            LIMIT 1
        ");
        $ppmpStmt->execute([$departmentId]);
        $ppmpData = $ppmpStmt->fetch(PDO::FETCH_ASSOC);
        if ($ppmpData) {
            $ppmpCount = intval($ppmpData['count']);
            $ppmpTotal = floatval($ppmpData['total']);
            $ppmpFiscalYear = $ppmpData['fiscal_year'];
        }
    } catch (Exception $e) {
        // Silently fail
    }
}

// Get LIB statistics (approved/final only)
$libCount = 0;
$libTotal = 0;
$libFiscalYear = date('Y');
if ($departmentId) {
    try {
        $libStmt = $conn->prepare("
            SELECT COUNT(DISTINCT l.id) as count,
                   COALESCE(SUM(li.amount), 0) as total,
                   l.fiscal_year
            FROM line_item_budgets l
            LEFT JOIN line_item_budget_items li ON l.id = li.lib_id
            WHERE l.department_id = ? AND l.status = 'approved'
            GROUP BY l.fiscal_year
            ORDER BY l.fiscal_year DESC
            LIMIT 1
        ");
        $libStmt->execute([$departmentId]);
        $libData = $libStmt->fetch(PDO::FETCH_ASSOC);
        if ($libData) {
            $libCount = intval($libData['count']);
            $libTotal = floatval($libData['total']);
            $libFiscalYear = $libData['fiscal_year'];
        }
    } catch (Exception $e) {
        // Silently fail
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Department Dashboard</title>
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
        <!-- Sidebar -->
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
            
            <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
        </div>
        
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                </div>
                    <div>
                                    <h1 class="text-3xl font-bold mb-1">DASHBOARD</h1>
                                    <p class="text-red-100 text-sm">Welcome back, <span class="font-semibold"><?php echo htmlspecialchars($username); ?></span>! Manage your department's/office's budget and submissions.</p>
                                </div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 backdrop-blur-sm text-white border border-white border-opacity-30">
                                    <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                                    <?php echo $isAdminDepartment ? 'Admin Office' : 'Department Office'; ?>
                                </span>
                                <?php 
                                // Get fiduciary type for current user's department
                                if (isset($departmentId) && $departmentId) {
                                    try {
                                        $conn = getDB();
                                        $deptStmt = $conn->prepare("SELECT fiduciary_type FROM departments WHERE id = :id");
                                        $deptStmt->bindParam(':id', $departmentId);
                                        $deptStmt->execute();
                                        $deptData = $deptStmt->fetch(PDO::FETCH_ASSOC);
                                        if ($deptData) {
                                            $fidType = $deptData['fiduciary_type'] ?? 'Non-Fiduciary';
                                            $isFid = ($fidType === 'Fiduciary');
                                            echo '<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 backdrop-blur-sm text-white border border-white border-opacity-30">' . htmlspecialchars($fidType) . '</span>';
                                        }
                                    } catch (Exception $e) {}
                                }
                                ?>
                            <?php if ($userRole === 'supply_office'): ?>
                                <a href="purchase_orders.php" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold text-maroon bg-white bg-opacity-70 border border-white border-opacity-40 hover:bg-opacity-90 transition-colors">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 10h10M7 14h5m2-11h-4a1 1 0 00-1 1v1H7a2 2 0 00-2 2v8a2 2 0 002 2h10a2 2 0 002-2v-8a2 2 0 00-2-2h-1V4a1 1 0 00-1-1z"></path>
                                    </svg>
                                    Purchase Orders
                                </a>
                            <?php endif; ?>
                            </div>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Notification Bell -->
                        <?php 
                        require_once __DIR__ . '/../classes/Notification.php';
                        $notification = new Notification();
                        $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
                        $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
                        include __DIR__ . '/../components/notification_bell.php'; 
                        ?>
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
            <div class="flex-1 p-4 space-y-4">
                <!-- Statistics Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Budget Allocation Card -->
                    <?php
                    $budgetAmount = 0;
                    $selectedFiscalYear = isset($_GET['allocation_year']) ? intval($_GET['allocation_year']) : date('Y');
                    $availableFiscalYears = [];
                    
                    if ($departmentId) {
                        try {
                            require_once __DIR__ . '/../config/database.php';
                            $conn = getDB();
                            
                            // Check if budget_allocations table exists
                            $checkTable = $conn->query("SHOW TABLES LIKE 'budget_allocations'");
                            if ($checkTable->rowCount() > 0) {
                                // Get all available fiscal years for this department
                                $yearsStmt = $conn->prepare("
                                    SELECT DISTINCT fiscal_year 
                                    FROM budget_allocations 
                                    WHERE department_id = ?
                                    ORDER BY fiscal_year DESC
                                ");
                                $yearsStmt->execute([$departmentId]);
                                $availableFiscalYears = $yearsStmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                // If no years found, use current year
                                if (empty($availableFiscalYears)) {
                                    $availableFiscalYears = [date('Y')];
                                }
                                
                                // If selected year not in available years, use the most recent
                                if (!in_array($selectedFiscalYear, $availableFiscalYears)) {
                                    $selectedFiscalYear = $availableFiscalYears[0];
                                }
                                
                                // Get budget for selected fiscal year
                                $budgetStmt = $conn->prepare("
                                    SELECT overall_total 
                                    FROM budget_allocations 
                                    WHERE department_id = ? AND fiscal_year = ?
                                    ORDER BY created_at DESC 
                                    LIMIT 1
                                ");
                                $budgetStmt->execute([$departmentId, $selectedFiscalYear]);
                                $budgetData = $budgetStmt->fetch(PDO::FETCH_ASSOC);
                                if ($budgetData) {
                                    $budgetAmount = floatval($budgetData['overall_total']);
                                }
                            }
                        } catch (Exception $e) {
                            // Silently fail
                        }
                    }
                    ?>
                    <a href="allocations_view.php?year=<?php echo $selectedFiscalYear; ?>" class="bg-gradient-to-br from-maroon to-red-800 rounded-2xl shadow-lg border border-maroon p-8 hover:shadow-xl transition-all duration-300 text-white block group">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-white">Budget Allocation</h3>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <?php if ($budgetAmount > 0): ?>
                                <button onclick="event.preventDefault(); event.stopPropagation(); showBudgetBreakdown(<?php echo $departmentId; ?>, <?php echo $selectedFiscalYear; ?>);" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-semibold transition-colors border border-white border-opacity-30 whitespace-nowrap">
                                    View Details
                                </button>
                                <?php endif; ?>
                                <?php if (count($availableFiscalYears) > 1): ?>
                                <select id="allocationYearSelect" onclick="event.stopPropagation(); event.preventDefault();" onchange="event.stopPropagation(); changeAllocationYear(this.value);" class="px-3 py-1.5 text-xs bg-white bg-opacity-20 border border-white border-opacity-30 rounded-lg text-white font-semibold hover:bg-opacity-30 transition-colors cursor-pointer w-full">
                                    <?php foreach ($availableFiscalYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $selectedFiscalYear ? 'selected' : ''; ?> class="text-gray-900">
                                        FY <?php echo $year; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <div class="px-3 py-1.5 text-xs bg-white bg-opacity-10 border border-white border-opacity-20 rounded-lg text-white font-semibold">
                                    FY <?php echo $selectedFiscalYear; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p class="text-4xl font-bold text-white mb-2">₱<?php echo number_format($budgetAmount, 2); ?></p>
                        <p class="text-sm text-red-100">
                            Fiscal Year <?php echo $selectedFiscalYear; ?>
                            <?php if ($budgetAmount == 0): ?>
                            <span class="text-xs opacity-75"> • No allocation set</span>
                            <?php endif; ?>
                        </p>
                        <div class="mt-3 flex items-center gap-2 text-xs text-white opacity-75 group-hover:opacity-100 transition-opacity">
                            <span>Click to view allocation page</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>

                    <!-- Total Balance for Utilization Card -->
                    <a href="utilization__view.php?year=<?php echo $selectedUtilizationYear; ?>" id="utilizationCardLink" class="bg-gradient-to-br <?php echo $totalBalance < 0 ? 'from-red-50 to-red-100 border-red-200' : 'from-green-50 to-green-100 border-green-200'; ?> rounded-2xl shadow-lg border p-8 hover:shadow-xl transition-all duration-300 cursor-pointer block group">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="w-14 h-14 bg-gradient-to-br <?php echo $totalBalance < 0 ? 'from-red-500 to-red-700' : 'from-green-500 to-green-700'; ?> rounded-xl flex items-center justify-center shadow-lg flex-shrink-0 group-hover:scale-110 transition-transform">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : 'text-green-700'; ?>">Utilization</h3>
                                </div>
                            </div>
                            <div class="flex flex-col items-end gap-2">
                                <?php if ($totalBalance != 0 && $departmentId): ?>
                                <button onclick="event.preventDefault(); event.stopPropagation(); showUtilizationBreakdown(<?php echo $departmentId; ?>, <?php echo $selectedUtilizationYear; ?>);" class="px-4 py-2 <?php echo $totalBalance < 0 ? 'bg-red-200 hover:bg-red-300 text-red-800 border-red-300' : 'bg-green-200 hover:bg-green-300 text-green-800 border-green-300'; ?> rounded-lg text-sm font-semibold transition-colors border whitespace-nowrap">
                                    View Details
                                </button>
                                <?php endif; ?>
                                <?php if (count($availableUtilizationYears) > 1): ?>
                                <select id="utilizationYearSelect" onclick="event.stopPropagation(); event.preventDefault();" onchange="event.stopPropagation(); updateUtilizationCardLink(this.value);" class="px-3 py-1.5 text-xs <?php echo $totalBalance < 0 ? 'bg-red-100 border-red-300 text-red-800' : 'bg-green-100 border-green-300 text-green-800'; ?> border rounded-lg font-semibold hover:opacity-80 transition-colors cursor-pointer w-full">
                                    <?php foreach ($availableUtilizationYears as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $year == $selectedUtilizationYear ? 'selected' : ''; ?> class="text-gray-900">
                                        FY <?php echo $year; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php else: ?>
                                <div class="px-3 py-1.5 text-xs <?php echo $totalBalance < 0 ? 'bg-red-100 border-red-200 text-red-800' : 'bg-green-100 border-green-200 text-green-800'; ?> border rounded-lg font-semibold">
                                    FY <?php echo $selectedUtilizationYear; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <p id="utilizationAmount" class="text-4xl font-bold <?php echo $totalBalance < 0 ? 'text-red-700' : ($utilizationCount == 0 ? 'text-gray-400' : 'text-green-700'); ?> mb-2">
                            ₱<?php echo $utilizationCount == 0 ? '0.00' : number_format($totalBalance, 2); ?>
                        </p>
                        <p class="text-sm text-gray-500">
                            <span id="utilizationCount"><?php echo $utilizationCount; ?></span> <?php echo $utilizationCount == 1 ? 'entry' : 'entries'; ?> • Fiscal Year <span id="utilizationYearDisplay"><?php echo $selectedUtilizationYear; ?></span>
                        </p>
                        <div class="mt-3 flex items-center gap-2 text-xs text-gray-600 opacity-75 group-hover:opacity-100 transition-opacity">
                            <span>Click to view utilization page</span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </div>
                    </a>

                    <!-- PPMP Quick Action Card -->
                    <a href="ppmp.php" class="bg-gradient-to-br from-maroon to-red-800 rounded-2xl shadow-lg border border-red-900 p-8 hover:shadow-xl transition-all duration-300 cursor-pointer block group">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform flex-shrink-0">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-white">Project Procurement Management Plan (PPMP)</h3>
                            </div>
                            <?php if ($ppmpCount > 0): ?>
                            <button onclick="event.preventDefault(); showPPMPBreakdown(<?php echo $departmentId; ?>, <?php echo $fiscalYear; ?>);" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-semibold transition-colors border border-white border-opacity-30 text-white">
                                View Details
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($ppmpCount > 0): ?>
                            <p class="text-4xl font-bold text-white mb-2">₱<?php echo number_format($ppmpTotal, 2); ?></p>
                            <p class="text-sm text-red-100"><?php echo $ppmpCount; ?> approved • FY <?php echo $ppmpFiscalYear; ?></p>
                        <?php else: ?>
                            <p class="text-sm text-red-100 mt-2">No approved PPMP yet</p>
                        <?php endif; ?>
                    </a>

                    <!-- LIB Quick Action Card -->
                    <a href="lib.php" class="bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl shadow-lg border border-blue-900 p-8 hover:shadow-xl transition-all duration-300 cursor-pointer block group">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-4 flex-1">
                                <div class="w-14 h-14 bg-white bg-opacity-20 rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform flex-shrink-0">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-bold text-white">Line Item Budget (LIB)</h3>
                            </div>
                            <?php if ($libCount > 0): ?>
                            <button onclick="event.preventDefault(); showLIBBreakdown(<?php echo $departmentId; ?>, <?php echo $fiscalYear; ?>);" class="px-4 py-2 bg-white bg-opacity-20 hover:bg-opacity-30 rounded-lg text-sm font-semibold transition-colors border border-white border-opacity-30 text-white">
                                View Details
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($libCount > 0): ?>
                            <p class="text-4xl font-bold text-white mb-2">₱<?php echo number_format($libTotal, 2); ?></p>
                            <p class="text-sm text-blue-100"><?php echo $libCount; ?> approved • FY <?php echo $libFiscalYear; ?></p>
                        <?php else: ?>
                            <p class="text-sm text-blue-100 mt-2">No approved LIB yet</p>
                        <?php endif; ?>
                    </a>
                </div>

                <!-- Quick Actions Section -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-900">Quick Actions</h2>
                        <span class="text-sm text-gray-500">Common tasks</span>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <a href="submit_documents.php" class="flex flex-col items-center justify-center px-6 py-5 bg-gradient-to-br from-maroon to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span class="font-semibold text-center">Submissions</span>
                        </a>
                        <a href="track_requests.php" class="flex flex-col items-center justify-center px-6 py-5 bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl hover:from-blue-600 hover:to-blue-700 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <span class="font-semibold text-center">Track Requests</span>
                        </a>
                        <a href="cabac_view.php" class="flex flex-col items-center justify-center px-6 py-5 bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-xl hover:from-indigo-600 hover:to-indigo-700 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h10M4 18h6"></path>
                            </svg>
                            <span class="font-semibold text-center">CABAC Viewer</span>
                        </a>
                        <a href="notifications.php" class="flex flex-col items-center justify-center px-6 py-5 bg-gradient-to-br from-amber-500 to-amber-600 text-white rounded-xl hover:from-amber-600 hover:to-amber-700 transition-all duration-300 shadow-md hover:shadow-lg transform hover:-translate-y-1">
                            <svg class="w-8 h-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 102.828 2.828l6.414 6.414a2 2 0 01-2.828 2.828L4.828 7z"></path>
                            </svg>
                            <span class="font-semibold text-center">Notifications</span>
                            <?php if ($unreadCount > 0): ?>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"><?php echo $unreadCount > 9 ? '9+' : $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
                
                <!-- Main Content Grid -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Notifications Section -->
                    <div class="lg:col-span-2 bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-xl font-bold text-gray-900">Recent Notifications</h2>
                            <a href="notifications.php" class="text-sm text-maroon hover:text-maroon-dark font-medium">View all →</a>
                        </div>
                        <div class="space-y-3" id="recentNotificationsContainer">
                            <!-- Loading indicator -->
                            <div class="text-center py-12 text-gray-400">
                                <svg class="w-8 h-8 mx-auto mb-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <p class="text-sm">Loading notifications...</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submission Status Section -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-gray-900 mb-6">Submission Status</h2>
                        <div class="space-y-4">
                            <!-- PPMP Status -->
                            <div class="p-4 rounded-xl border-2 <?php echo $latestPpmp ? 'border-green-200 bg-green-50' : 'border-gray-200 bg-gray-50'; ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-900">PPMP</span>
                                    <?php if ($latestPpmp): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Available</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600">Not Submitted</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($latestPpmp): ?>
                                    <p class="text-sm text-gray-600">Last updated: <?php echo date('M j, Y', strtotime($latestPpmp['submitted_at'])); ?></p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">No submission yet</p>
                                <?php endif; ?>
                            </div>

                            <!-- LIB Status -->
                            <div class="p-4 rounded-xl border-2 <?php echo $latestLib ? 'border-purple-200 bg-purple-50' : 'border-gray-200 bg-gray-50'; ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-900">LIB</span>
                                    <?php if ($latestLib): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800">Available</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600">Not Submitted</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($latestLib): ?>
                                    <p class="text-sm text-gray-600">Last updated: <?php echo date('M j, Y', strtotime($latestLib['submitted_at'])); ?></p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">No submission yet</p>
                                <?php endif; ?>
                            </div>

                            <!-- APP Status -->
                            <div class="p-4 rounded-xl border-2 <?php echo $latestApp ? 'border-blue-200 bg-blue-50' : 'border-gray-200 bg-gray-50'; ?>">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-semibold text-gray-900">APP</span>
                                    <?php if ($latestApp): ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Available</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-200 text-gray-600">Not Submitted</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($latestApp): ?>
                                    <p class="text-sm text-gray-600">Last updated: <?php echo date('M j, Y', strtotime($latestApp['submitted_at'])); ?></p>
                                <?php else: ?>
                                    <p class="text-sm text-gray-500">Requires PPMP & LIB</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
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
    
    <script>
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
        });
        
        // Coming Soon functionality
        function showComingSoon(feature) {
            alert(feature + ' functionality will be available soon!');
        }

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
        
        // Close modal when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (modal && event.target === modal) {
                closeLogoutModal();
            }
        });
        
        function showBudgetBreakdown(departmentId, fiscalYear) {
            // Fetch budget breakdown data
            fetch(`../api/get_budget_breakdown.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayBudgetBreakdown(data.data, data.child_departments || []);
                    } else {
                        alert('Error loading budget breakdown: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading budget breakdown');
                });
        }
        
        function displayBudgetBreakdown(data, childDepartments = []) {
            const modal = document.getElementById('budgetBreakdownModal');
            if (!modal) {
                // Create modal if it doesn't exist
                const modalHTML = `
                    <div id="budgetBreakdownModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6 rounded-t-2xl flex justify-between items-center">
                                <h2 class="text-2xl font-bold">Budget Allocation Breakdown</h2>
                                <button onclick="closeBudgetBreakdown()" class="text-white hover:text-red-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="budgetBreakdownContent" class="p-6">
                                <!-- Content will be inserted here -->
                            </div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
            }
            
            const content = document.getElementById('budgetBreakdownContent');
            if (!content) return;
            
            // Check if it's an office
            const allocData = data.allocation_data ? (typeof data.allocation_data === 'string' ? JSON.parse(data.allocation_data) : data.allocation_data) : {};
            const isOffice = allocData.is_office === true;
            
            let html = '';
            
            // Add tabs if there are child departments
            if (childDepartments.length > 0) {
                html += `
                    <div class="mb-6 border-b border-gray-200">
                        <div class="flex gap-2">
                            <button id="bdTab-myAllocation" onclick="switchBudgetBreakdownTab('myAllocation')" class="bd-tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5 rounded-t-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                My Allocation
                            </button>
                            <button id="bdTab-subDepartments" onclick="switchBudgetBreakdownTab('subDepartments')" class="bd-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Sub-Departments (${childDepartments.length})
                            </button>
                        </div>
                    </div>
                `;
            }
            
            // My Allocation Tab Panel
            html += `<div id="bdPanel-myAllocation" class="bd-tab-panel">`;
            html += `
                <div class="mb-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Allocation Details</h3>
                    <div class="grid grid-cols-2 gap-4 mb-4">
            `;
            
            // Show Fiscal Year for all (departments and offices)
            html += `
                        <div>
                            <p class="text-sm text-gray-600">Fiscal Year</p>
                            <p class="text-lg font-semibold text-maroon">${data.fiscal_year || fiscalYear}</p>
                        </div>
            `;
            
            // Only show these fields for departments (not offices)
            if (!isOffice) {
                html += `
                        <div>
                            <p class="text-sm text-gray-600">Total Tuition Fee</p>
                            <p class="text-lg font-semibold">₱${parseFloat(data.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">50% Instructional</p>
                            <p class="text-lg font-semibold">₱${parseFloat(data.instructional_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                `;
            }
            
            html += `
                        <div>
                            <p class="text-sm text-gray-600">Overall Total</p>
                            <p class="text-lg font-semibold text-maroon">₱${parseFloat(data.overall_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        </div>
                    </div>
                </div>
            `;
            
            if (data.allocation_data) {
                // Non-Fiduciary Fund - only show for departments (not offices)
                if (allocData.non_fiduciary && !isOffice) {
                    html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Non-Fiduciary Fund</h4>';
                    html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse">';
                    html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Category</th><th class="border p-2 text-right">Percent</th><th class="border p-2 text-right">50%</th><th class="border p-2 text-right">Deductions</th><th class="border p-2 text-right">Budget Allocation</th></tr></thead><tbody>';
                    
                    const categories = {
                        facultyStaff: 'Faculty and Staff Development',
                        curriculum: 'Curriculum Development',
                        student: 'Student Development',
                        facilities: 'Facilities Development'
                    };
                    
                    for (const [key, name] of Object.entries(categories)) {
                        if (allocData.non_fiduciary[key]) {
                            const item = allocData.non_fiduciary[key];
                            const deductions = item.deductions || [];
                            const deductionTotal = deductions.reduce((sum, d) => sum + parseFloat(d.amount.replace(/[₱,]/g, '') || 0), 0);
                            html += `<tr><td class="border p-2">${name}</td>`;
                            html += `<td class="border p-2 text-right">${item.percent || '0%'}</td>`;
                            html += `<td class="border p-2 text-right">${item.instructional || '₱0.00'}</td>`;
                            html += `<td class="border p-2 text-right">₱${deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;
                            html += `<td class="border p-2 text-right font-semibold">${item.budget_allocation || '₱0.00'}</td></tr>`;
                        }
                    }
                    html += '</tbody></table></div></div>';
                }
                
                // Fiduciary Fund
                if (allocData.fiduciary) {
                    const isOffice = allocData.is_office === true;
                    
                    if (isOffice) {
                        // Office format: Allocated Budget, Deductions, Total Budget
                        const fiduciary = allocData.fiduciary;
                        const deductions = fiduciary.deductions || [];
                        const totalBudget = fiduciary.total_budget || '₱0.00';
                        
                        // Calculate deduction total
                        let deductionTotal = 0;
                        deductions.forEach(ded => {
                            deductionTotal += parseFloat(ded.amount.replace(/[₱,]/g, '') || 0);
                        });
                        
                        // Calculate allocated budget (total budget + deductions)
                        const totalBudgetValue = parseFloat(totalBudget.replace(/[₱,]/g, '') || 0);
                        const allocatedBudget = totalBudgetValue + deductionTotal;
                        
                        html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Fiduciary Fund</h4>';
                        html += '<div class="bg-white rounded-lg border-2 border-gray-200 p-6">';
                        html += '<div class="mb-4 pb-4 border-b-2 border-gray-300">';
                        html += '<div class="flex justify-between items-center">';
                        html += `<span class="text-lg font-bold text-gray-800">Allocated Budget:</span>`;
                        html += `<span class="text-lg font-bold text-maroon">₱${allocatedBudget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
                        html += '</div></div>';
                        
                        html += '<div class="mb-4">';
                        html += '<h4 class="text-md font-bold text-gray-800 mb-3 flex items-center gap-2">';
                        html += '<svg class="w-4 h-4 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                        html += '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>';
                        html += '</svg>Deductions:</h4>';
                        
                        if (deductions.length > 0) {
                            html += '<div class="space-y-2 mb-3">';
                            deductions.forEach(ded => {
                                html += '<div class="flex items-center py-2">';
                                html += `<span class="text-sm font-semibold text-gray-900">${ded.amount || '₱0.00'}</span>`;
                                if (ded.remarks && ded.remarks.trim() !== '') {
                                    html += `<span class="text-sm text-gray-600 ml-3">- ${ded.remarks}</span>`;
                                }
                                html += '</div>';
                            });
                            html += '</div>';
                        } else {
                            html += '<p class="text-sm text-gray-500 italic mb-3">No deductions added</p>';
                        }
                        
                        html += '<div class="pt-3 border-t-2 border-gray-300">';
                        html += '<div class="flex justify-between items-center">';
                        html += '<span class="text-md font-bold text-gray-800">Sub-total:</span>';
                        html += `<span class="text-md font-bold text-red-600">₱${deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>`;
                        html += '</div></div></div>';
                        
                        html += '<div class="pt-4 border-t-2 border-gray-300">';
                        html += '<div class="flex justify-between items-center">';
                        html += '<span class="text-xl font-bold text-red-900">Total Budget:</span>';
                        html += `<span class="text-xl font-bold ${totalBudgetValue < 0 ? 'text-red-600' : 'text-red-900'}">${totalBudget}</span>`;
                        html += '</div></div></div></div>';
                    } else {
                        // Department format: Table with items
                        html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Fiduciary Fund</h4>';
                        html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse">';
                        html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Item</th><th class="border p-2 text-right">Budget Collected</th><th class="border p-2 text-right">Deductions</th><th class="border p-2 text-right">Total Budget</th></tr></thead><tbody>';
                    
                    for (const [key, item] of Object.entries(allocData.fiduciary)) {
                            // Skip if it's not an item (like if key is 'deductions' or 'total_budget' for offices)
                            if (typeof item === 'object' && item !== null && (item.item_name || item.instructional)) {
                            const deductions = item.deductions || [];
                            const deductionTotal = deductions.reduce((sum, d) => sum + parseFloat(d.amount.replace(/[₱,]/g, '') || 0), 0);
                            html += `<tr><td class="border p-2">${item.item_name || 'Item ' + key}</td>`;
                            html += `<td class="border p-2 text-right">${item.instructional || '₱0.00'}</td>`;
                            html += `<td class="border p-2 text-right">₱${deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;
                                // Use total_budget if available, otherwise fall back to budget_allocation for backward compatibility
                                html += `<td class="border p-2 text-right font-semibold">${item.total_budget || item.budget_allocation || '₱0.00'}</td></tr>`;
                            }
                        }
                        html += '</tbody></table></div></div>';
                    }
                }
            }
            
            // Close My Allocation panel
            html += '</div>';
            
            // Add Sub-Departments panel if there are child departments
            if (childDepartments.length > 0) {
                html += `<div id="bdPanel-subDepartments" class="bd-tab-panel hidden">`;
                
                childDepartments.forEach((child, index) => {
                    const childDept = child.department;
                    const childAlloc = child.allocation;
                    const childAllocData = childAlloc?.allocation_data || {};
                    const childIsOffice = childAllocData.is_office === true;
                    
                    html += `<div class="mb-6 ${index > 0 ? 'pt-6 border-t border-gray-200' : ''}">`;
                    html += `<div class="flex items-center gap-3 mb-4">`;
                    html += `<h3 class="text-lg font-semibold text-gray-800">${childDept.dept_name}</h3>`;
                    html += `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Sub-Dept</span>`;
                    html += `</div>`;
                    
                    if (childAlloc) {
                        html += `<div class="grid grid-cols-2 gap-4 mb-4">`;
                        html += `<div><p class="text-sm text-gray-600">Fiscal Year</p><p class="text-lg font-semibold text-maroon">${childAlloc.fiscal_year || fiscalYear}</p></div>`;
                        if (!childIsOffice) {
                            html += `<div><p class="text-sm text-gray-600">Total Tuition Fee</p><p class="text-lg font-semibold">₱${parseFloat(childAlloc.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p></div>`;
                        }
                        html += `<div><p class="text-sm text-gray-600">Overall Total</p><p class="text-lg font-semibold text-maroon">₱${parseFloat(childAlloc.overall_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p></div>`;
                        html += `</div>`;
                        
                        // Non-Fiduciary for child
                        if (childAllocData.non_fiduciary && !childIsOffice) {
                            html += '<div class="mb-4"><h4 class="text-md font-semibold text-gray-800 mb-3">Non-Fiduciary Fund</h4>';
                            html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse">';
                            html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Category</th><th class="border p-2 text-right">%</th><th class="border p-2 text-right">50%</th><th class="border p-2 text-right">Deductions</th><th class="border p-2 text-right">Budget</th></tr></thead><tbody>';
                            
                            const categories = {facultyStaff: 'Faculty and Staff Development', curriculum: 'Curriculum Development', student: 'Student Development', facilities: 'Facilities Development'};
                            for (const [key, name] of Object.entries(categories)) {
                                if (childAllocData.non_fiduciary[key]) {
                                    const item = childAllocData.non_fiduciary[key];
                                    const deductions = item.deductions || [];
                                    const deductionTotal = deductions.reduce((sum, d) => sum + parseFloat((d.amount || '0').toString().replace(/[₱,]/g, '') || 0), 0);
                                    html += `<tr><td class="border p-2">${name}</td>`;
                                    html += `<td class="border p-2 text-right">${item.percent || '0%'}</td>`;
                                    html += `<td class="border p-2 text-right">${item.instructional || '₱0.00'}</td>`;
                                    html += `<td class="border p-2 text-right">₱${deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;
                                    html += `<td class="border p-2 text-right font-semibold">${item.budget_allocation || '₱0.00'}</td></tr>`;
                                }
                            }
                            html += '</tbody></table></div></div>';
                        }
                        
                        // Fiduciary for child
                        if (childAllocData.fiduciary) {
                            if (childIsOffice) {
                                const fiduciary = childAllocData.fiduciary;
                                const deductions = fiduciary.deductions || [];
                                let deductionTotal = 0;
                                deductions.forEach(ded => { deductionTotal += parseFloat((ded.amount || '0').toString().replace(/[₱,]/g, '') || 0); });
                                const totalBudget = fiduciary.total_budget || '₱0.00';
                                const totalBudgetValue = parseFloat(totalBudget.toString().replace(/[₱,]/g, '') || 0);
                                const allocatedBudget = totalBudgetValue + deductionTotal;
                                
                                html += '<div class="mb-4"><h4 class="text-md font-semibold text-gray-800 mb-3">Fiduciary Fund</h4>';
                                html += `<div class="bg-gray-50 rounded-lg p-4 space-y-2">`;
                                html += `<div class="flex justify-between"><span>Allocated Budget:</span><span class="font-semibold">₱${allocatedBudget.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>`;
                                html += `<div class="flex justify-between"><span>Deductions:</span><span class="font-semibold text-red-600">₱${deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span></div>`;
                                html += `<div class="flex justify-between border-t pt-2"><span class="font-bold">Total Budget:</span><span class="font-bold text-maroon">${totalBudget}</span></div>`;
                                html += '</div></div>';
                            } else {
                                html += '<div class="mb-4"><h4 class="text-md font-semibold text-gray-800 mb-3">Fiduciary Fund</h4>';
                                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse">';
                                html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Item</th><th class="border p-2 text-right">Budget Collected</th><th class="border p-2 text-right">Deductions</th><th class="border p-2 text-right">Total</th></tr></thead><tbody>';
                                
                                for (const [key, item] of Object.entries(childAllocData.fiduciary)) {
                                    if (typeof item === 'object' && item !== null && (item.item_name || item.instructional)) {
                                        const deductions = item.deductions || [];
                                        const deductionTotal = deductions.reduce((sum, d) => sum + parseFloat((d.amount || '0').toString().replace(/[₱,]/g, '') || 0), 0);
                                        html += `<tr><td class="border p-2">${item.item_name || 'Item ' + key}</td>`;
                                        html += `<td class="border p-2 text-right">${item.instructional || '₱0.00'}</td>`;
                                        html += `<td class="border p-2 text-right">₱${deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;
                                        html += `<td class="border p-2 text-right font-semibold">${item.total_budget || item.budget_allocation || '₱0.00'}</td></tr>`;
                                    }
                                }
                                html += '</tbody></table></div></div>';
                            }
                        }
                    } else {
                        html += `<p class="text-gray-500 italic">No budget allocation has been set for this department yet.</p>`;
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            content.innerHTML = html;
            document.getElementById('budgetBreakdownModal').classList.remove('hidden');
        }
        
        function switchBudgetBreakdownTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.bd-tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Show selected panel
            const selectedPanel = document.getElementById('bdPanel-' + tabName);
            if (selectedPanel) {
                selectedPanel.classList.remove('hidden');
            }
            
            // Update tab button styles
            document.querySelectorAll('.bd-tab-btn').forEach(btn => {
                btn.classList.remove('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
                btn.classList.add('border-transparent', 'text-gray-500', 'font-medium');
            });
            
            // Highlight selected tab
            const selectedTab = document.getElementById('bdTab-' + tabName);
            if (selectedTab) {
                selectedTab.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
                selectedTab.classList.add('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
            }
        }
        
        function closeBudgetBreakdown() {
            const modal = document.getElementById('budgetBreakdownModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function showUtilizationBreakdown(departmentId, fiscalYear) {
            // Fetch utilization breakdown data
            fetch(`../api/get_utilization_breakdown.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayUtilizationBreakdown(data.data, data.child_departments || []);
                    } else {
                        alert('Error loading utilization breakdown: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading utilization breakdown');
                });
        }
        
        function displayUtilizationBreakdown(data, childDepartments = []) {
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
            
            let html = '';
            
            // Add tabs if there are child departments
            if (childDepartments.length > 0) {
                html += `
                    <div class="mb-6 border-b border-gray-200">
                        <div class="flex gap-2">
                            <button id="utTab-myUtilization" onclick="switchUtilizationBreakdownTab('myUtilization')" class="ut-tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5 rounded-t-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                My Utilization
                            </button>
                            <button id="utTab-subDepartments" onclick="switchUtilizationBreakdownTab('subDepartments')" class="ut-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                Sub-Departments (${childDepartments.length})
                            </button>
                        </div>
                    </div>
                `;
            }
            
            // My Utilization Tab Panel
            html += `<div id="utPanel-myUtilization" class="ut-tab-panel">`;
            html += `
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
            
            // Close My Utilization panel
            html += '</div>';
            
            // Add Sub-Departments panel if there are child departments
            if (childDepartments.length > 0) {
                html += `<div id="utPanel-subDepartments" class="ut-tab-panel hidden">`;
                
                childDepartments.forEach((child, index) => {
                    const childDept = child.department;
                    const childUtil = child.utilization;
                    
                    html += `<div class="mb-6 ${index > 0 ? 'pt-6 border-t border-gray-200' : ''}">`;
                    html += `<div class="flex items-center gap-3 mb-4">`;
                    html += `<h3 class="text-lg font-semibold text-gray-800">${childDept.dept_name}</h3>`;
                    html += `<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-medium">Sub-Dept</span>`;
                    html += `</div>`;
                    
                    if (childUtil) {
                        const childTotals = childUtil.totals || {};
                        const childPrEntries = childUtil.pr_entries || [];
                        const childTravelsEntries = childUtil.travels_entries || [];
                        const childHonorariaEntries = childUtil.honoraria_entries || [];
                        const childUtilizationEntries = childUtil.utilization_entries || [];
                        
                        // Summary cards
                        html += `<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">`;
                        html += `<div class="bg-blue-50 p-3 rounded-lg border border-blue-200"><p class="text-xs text-gray-600 mb-1">Total Allocated</p><p class="text-lg font-bold text-blue-700">${formatNumber(childTotals.totalAllocated || 0)}</p></div>`;
                        html += `<div class="bg-red-50 p-3 rounded-lg border border-red-200"><p class="text-xs text-gray-600 mb-1">Total Deductions</p><p class="text-lg font-bold text-red-700">${formatNumber(childTotals.totalDeductions || 0)}</p></div>`;
                        html += `<div class="bg-green-50 p-3 rounded-lg border border-green-200"><p class="text-xs text-gray-600 mb-1">Total Balance</p><p class="text-lg font-bold ${(childTotals.totalBalance || 0) < 0 ? 'text-red-700' : 'text-green-700'}">${formatNumber(childTotals.totalBalance || 0)}</p></div>`;
                        html += `<div class="bg-purple-50 p-3 rounded-lg border border-purple-200"><p class="text-xs text-gray-600 mb-1">Fiscal Year</p><p class="text-lg font-bold text-purple-700">${childUtil.fiscal_year || new Date().getFullYear()}</p></div>`;
                        html += `</div>`;
                        
                        // PR Entries for child
                        if (childPrEntries.length > 0) {
                            html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-800 mb-2">Purchase Requests</h4>';
                            html += '<div class="overflow-x-auto"><table class="w-full text-xs border-collapse border border-gray-300">';
                            html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">PR Number</th><th class="border p-2 text-left">Particulars</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                            childPrEntries.forEach(entry => {
                                const particulars = entry.particulars ? (entry.particulars.length > 30 ? entry.particulars.substring(0, 30) + '...' : entry.particulars) : '-';
                                html += `<tr><td class="border p-2">${entry.prNumber || entry.purchaseRequest || '-'}</td>`;
                                html += `<td class="border p-2">${particulars}</td>`;
                                html += `<td class="border p-2 text-right text-blue-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                            });
                            html += `<tr class="bg-blue-50"><td colspan="2" class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-blue-700">${formatNumber(childTotals.prTotal || 0)}</td></tr>`;
                            html += '</tbody></table></div></div>';
                        }
                        
                        // Travels for child
                        if (childTravelsEntries.length > 0) {
                            html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-800 mb-2">Travels</h4>';
                            html += '<div class="overflow-x-auto"><table class="w-full text-xs border-collapse border border-gray-300">';
                            html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Travelled</th><th class="border p-2 text-left">Event</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                            childTravelsEntries.forEach(entry => {
                                const event = (entry.event_activity || entry.event) ? ((entry.event_activity || entry.event).length > 30 ? (entry.event_activity || entry.event).substring(0, 30) + '...' : (entry.event_activity || entry.event)) : '-';
                                html += `<tr><td class="border p-2">${entry.travelled || '-'}</td>`;
                                html += `<td class="border p-2">${event}</td>`;
                                html += `<td class="border p-2 text-right text-green-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                            });
                            html += `<tr class="bg-green-50"><td colspan="2" class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-green-700">${formatNumber(childTotals.travelsTotal || 0)}</td></tr>`;
                            html += '</tbody></table></div></div>';
                        }
                        
                        // Honoraria for child
                        if (childHonorariaEntries.length > 0) {
                            html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-800 mb-2">Honoraria</h4>';
                            html += '<div class="overflow-x-auto"><table class="w-full text-xs border-collapse border border-gray-300">';
                            html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Date</th><th class="border p-2 text-right">Amount</th></tr></thead><tbody>';
                            childHonorariaEntries.forEach(entry => {
                                html += `<tr><td class="border p-2">${entry.date || '-'}</td>`;
                                html += `<td class="border p-2 text-right text-red-600">${formatNumber(entry.amount || 0)}</td></tr>`;
                            });
                            html += `<tr class="bg-red-50"><td class="border p-2 font-semibold text-right">Total:</td><td class="border p-2 text-right font-bold text-red-700">${formatNumber(childTotals.honorariaTotal || 0)}</td></tr>`;
                            html += '</tbody></table></div></div>';
                        }
                        
                        // Budget Utilization Breakdown for child
                        if (childUtilizationEntries.length > 0) {
                            html += '<div class="mb-4"><h4 class="text-sm font-semibold text-gray-800 mb-2">Budget Utilization Breakdown</h4>';
                            html += '<div class="overflow-x-auto"><table class="w-full text-xs border-collapse border border-gray-300">';
                            html += '<thead><tr class="bg-gray-100"><th class="border p-2 text-left">Category</th><th class="border p-2 text-left">Account Code</th><th class="border p-2 text-right">Allocated</th><th class="border p-2 text-right">Deductions</th><th class="border p-2 text-right">Balance</th></tr></thead><tbody>';
                            childUtilizationEntries.forEach(entry => {
                                html += `<tr><td class="border p-2">${entry.category || '-'}</td>`;
                                html += `<td class="border p-2">${entry.accountCode || entry.account_code || '-'}</td>`;
                                html += `<td class="border p-2 text-right">${formatNumber(entry.allocated || 0)}</td>`;
                                html += `<td class="border p-2 text-right text-red-600">${formatNumber(entry.deduction || 0)}</td>`;
                                html += `<td class="border p-2 text-right font-semibold ${(entry.balance || 0) < 0 ? 'text-red-600' : 'text-green-600'}">${formatNumber(entry.balance || 0)}</td></tr>`;
                            });
                            html += '</tbody></table></div></div>';
                        }
                    } else {
                        html += `<p class="text-gray-500 italic">No utilization summary has been set for this department yet.</p>`;
                    }
                    
                    html += '</div>';
                });
                
                html += '</div>';
            }
            
            content.innerHTML = html;
            document.getElementById('utilizationBreakdownModal').classList.remove('hidden');
        }
        
        function switchUtilizationBreakdownTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.ut-tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Show selected panel
            const selectedPanel = document.getElementById('utPanel-' + tabName);
            if (selectedPanel) {
                selectedPanel.classList.remove('hidden');
            }
            
            // Update tab button styles
            document.querySelectorAll('.ut-tab-btn').forEach(btn => {
                btn.classList.remove('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
                btn.classList.add('border-transparent', 'text-gray-500', 'font-medium');
            });
            
            // Highlight selected tab
            const selectedTab = document.getElementById('utTab-' + tabName);
            if (selectedTab) {
                selectedTab.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
                selectedTab.classList.add('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
            }
        }
        
        function closeUtilizationBreakdown() {
            const modal = document.getElementById('utilizationBreakdownModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
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

        // Prevent accidental form submissions and ensure buttons work correctly
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent default form submission on buttons that aren't submit buttons
            document.querySelectorAll('button[type="button"]').forEach(button => {
                button.addEventListener('click', function(e) {
                    // Ensure button clicks don't accidentally trigger form submissions
                    if (this.type === 'button') {
                        e.preventDefault();
                    }
                });
            });
            
            // Ensure links work correctly
            document.querySelectorAll('a[href]').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Only prevent default if it's a logout link and user hasn't confirmed
                    if (this.getAttribute('href') && this.getAttribute('href').includes('logout.php')) {
                        if (!confirm('Are you sure you want to logout?')) {
                            e.preventDefault();
                        }
                    }
                });
            });
        });
    </script>

    <script>
        // PPMP Breakdown Modal
        function showPPMPBreakdown(departmentId, fiscalYear) {
            // Fetch both regular and supplemental PPMP
            Promise.all([
                fetch(`../api/get_ppmp_list.php?department_id=${departmentId}&fiscal_year=${fiscalYear}&status=approved`).then(r => r.json()),
            ]).then(([ppmpData]) => {
                if (ppmpData.success && ppmpData.ppmps) {
                    // Separate regular and supplemental using ppmp_type field
                    const regularPPMP = ppmpData.ppmps.filter(p => p.ppmp_type !== 'supplemental');
                    const supplementalPPMP = ppmpData.ppmps.filter(p => p.ppmp_type === 'supplemental');
                    displayPPMPBreakdown(regularPPMP, supplementalPPMP, departmentId, fiscalYear);
                } else {
                    alert('Error loading PPMP data: ' + (ppmpData.message || 'Unknown error'));
                }
            }).catch(error => {
                console.error('Error:', error);
                alert('Error loading PPMP data');
            });
        }

        function displayPPMPBreakdown(regularPPMP, supplementalPPMP, departmentId, fiscalYear) {
            let modal = document.getElementById('ppmpBreakdownModal');
            if (!modal) {
                const modalHTML = `
                    <div id="ppmpBreakdownModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-[95vw] w-full max-h-[90vh] flex flex-col">
                            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6 rounded-t-2xl flex justify-between items-center flex-shrink-0">
                                <h2 class="text-2xl font-bold">Project Procurement Management Plan (PPMP)</h2>
                                <button onclick="closePPMPBreakdown()" class="text-white hover:text-red-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="ppmpBreakdownContent" class="overflow-y-auto flex-1 p-6"></div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                modal = document.getElementById('ppmpBreakdownModal');
            }

            const content = document.getElementById('ppmpBreakdownContent');
            
            if (regularPPMP.length === 0 && supplementalPPMP.length === 0) {
                content.innerHTML = '<p class="text-gray-500 text-center py-8">No approved PPMP found</p>';
                modal.classList.remove('hidden');
                return;
            }

            let html = '';
            
            // Add tabs if there are both regular and supplemental
            if (regularPPMP.length > 0 && supplementalPPMP.length > 0) {
                html += `
                    <div class="mb-6 border-b border-gray-200">
                        <div class="flex gap-2">
                            <button onclick="switchPPMPTab('regular')" id="ppmpTab-regular" class="ppmp-tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5">
                                Regular PPMP
                            </button>
                            <button onclick="switchPPMPTab('supplemental')" id="ppmpTab-supplemental" class="ppmp-tab-btn px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50">
                                Supplemental PPMP
                            </button>
                        </div>
                    </div>
                `;
            } else if (supplementalPPMP.length > 0) {
                // Only supplemental exists, show a label
                html += `
                    <div class="mb-6">
                        <span class="px-3 py-1 text-sm font-semibold rounded bg-yellow-100 text-yellow-800">Supplemental PPMP</span>
                    </div>
                `;
            }
            
            // Regular PPMP Panel
            if (regularPPMP.length > 0) {
                html += `<div id="ppmpPanel-regular" class="ppmp-tab-panel">`;
                // Fetch items first to calculate grand total
                fetch(`../api/get_ppmp_details.php?id=${regularPPMP[0].id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.items) {
                            const grandTotal = data.items.reduce((sum, item) => sum + parseFloat(item.estimated_budget || 0), 0);
                            document.getElementById('ppmpPanel-regular').innerHTML = generatePPMPTable(regularPPMP[0], grandTotal);
                            populatePPMPItems(regularPPMP[0].id, data.items);
                        }
                    });
                html += `</div>`;
            }
            
            // Supplemental PPMP Panel
            if (supplementalPPMP.length > 0) {
                html += `<div id="ppmpPanel-supplemental" class="ppmp-tab-panel ${regularPPMP.length > 0 ? 'hidden' : ''}">`;
                // Fetch items first to calculate grand total
                fetch(`../api/get_ppmp_details.php?id=${supplementalPPMP[0].id}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.items) {
                            const grandTotal = data.items.reduce((sum, item) => sum + parseFloat(item.estimated_budget || 0), 0);
                            document.getElementById('ppmpPanel-supplemental').innerHTML = generatePPMPTable(supplementalPPMP[0], grandTotal);
                            populatePPMPItems(supplementalPPMP[0].id, data.items);
                        }
                    });
                html += `</div>`;
            }
            
            content.innerHTML = html;
            modal.classList.remove('hidden');
        }

        function generatePPMPTable(ppmp, grandTotal = 0) {
            const ppmpType = (ppmp.ppmp_type === 'supplemental') ? 'Supplemental' : 'Regular';
            const typeColor = (ppmp.ppmp_type === 'supplemental') ? 'bg-yellow-100 text-yellow-800' : 'bg-maroon text-white';
            
            return `
                <div class="mb-6">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <div class="flex items-center gap-2 mb-2">
                                <span class="px-2 py-1 text-xs font-semibold rounded ${typeColor}">${ppmpType}</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Approved</span>
                            </div>
                            <h3 class="text-lg font-bold text-gray-900">PPMP - FY ${ppmp.fiscal_year}</h3>
                        </div>
                        <div class="text-right">
                            <p class="text-2xl font-bold text-maroon" id="ppmpHeaderTotal-${ppmp.id}">₱${parseFloat(grandTotal || 0).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                            <p class="text-xs text-gray-500">Grand Total</p>
                        </div>
                    </div>
                    
                    <div class="overflow-x-auto border-2 border-gray-300 rounded-lg">
                        <table class="w-full border-collapse" style="min-width: 1800px;">
                            <thead>
                                <tr class="bg-maroon text-white">
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 40px;">#</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-left" style="min-width: 250px;">General Description & Objective</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 100px;">Type</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 60px;">Qty</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 80px;">Unit</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-left" style="min-width: 180px;">Recommended Mode</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 70px;">Pre-Proc</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 100px;">Start</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 100px;">End Ads</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 100px;">Delivery</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-center" style="min-width: 80px;">Source</th>
                                    <th class="border border-gray-300 px-2 py-2 text-xs font-bold text-right" style="min-width: 120px;">Budget</th>
                                </tr>
                            </thead>
                            <tbody id="ppmpItems-${ppmp.id}">
                                <tr><td colspan="12" class="text-center py-4 text-gray-500">Loading items...</td></tr>
                            </tbody>
                            <tfoot>
                                <tr class="bg-maroon text-white font-bold">
                                    <td colspan="11" class="border border-gray-300 px-2 py-2 text-right text-sm">GRAND TOTAL:</td>
                                    <td class="border border-gray-300 px-2 py-2 text-right text-sm" id="ppmpGrandTotal-${ppmp.id}">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div class="mt-2 text-sm text-gray-500 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Scroll horizontally to see all columns
                    </div>
                </div>
            `;
        }

        function populatePPMPItems(ppmpId, items) {
            const tbody = document.getElementById(`ppmpItems-${ppmpId}`);
            if (!tbody) return;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="12" class="text-center py-4 text-gray-500">No items found</td></tr>';
                return;
            }
            
            // Helper function to format month (YYYY-MM-DD to Month YYYY)
            const formatMonth = (dateStr) => {
                if (!dateStr || dateStr === '0000-00-00' || dateStr === '0000-00' || dateStr === 'null') {
                    return '';
                }
                try {
                    const parts = dateStr.split('-');
                    if (parts.length >= 2) {
                        const year = parts[0];
                        const month = parts[1];
                        
                        if (year === '0000' || month === '00' || !year || !month) {
                            return '';
                        }
                        
                        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        const monthIndex = parseInt(month) - 1;
                        
                        if (monthIndex >= 0 && monthIndex < 12) {
                            return `${monthNames[monthIndex]} ${year}`;
                        }
                    }
                    return '';
                } catch (e) {
                    return '';
                }
            };
            
            let html = '';
            let totalBudget = 0;
            
            items.forEach((item, index) => {
                const budget = parseFloat(item.estimated_budget || 0);
                totalBudget += budget;
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${index + 1}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs">${item.general_description || ''}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${item.project_type || ''}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-right">${parseInt(item.quantity || 0)}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${item.unit || ''}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs">${item.recommended_mode || ''}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${item.pre_procurement_conference || ''}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${formatMonth(item.start_procurement)}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${formatMonth(item.end_ads_posting)}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${formatMonth(item.expected_delivery)}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-center">${item.source_of_funds || ''}</td>
                        <td class="border border-gray-300 px-2 py-2 text-xs text-right">₱${budget.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
            
            // Update totals in footer
            const grandTotalCell = document.getElementById(`ppmpGrandTotal-${ppmpId}`);
            if (grandTotalCell) grandTotalCell.textContent = '₱' + totalBudget.toLocaleString('en-US', {minimumFractionDigits: 2});
            
            // Update header total
            const headerTotal = document.getElementById(`ppmpHeaderTotal-${ppmpId}`);
            if (headerTotal) headerTotal.textContent = '₱' + totalBudget.toLocaleString('en-US', {minimumFractionDigits: 2});
        }

        function switchPPMPTab(tabName) {
            // Update tab buttons
            document.querySelectorAll('.ppmp-tab-btn').forEach(btn => {
                btn.classList.remove('border-maroon', 'text-maroon', 'bg-maroon', 'bg-opacity-5', 'font-semibold');
                btn.classList.add('border-transparent', 'text-gray-500', 'font-medium');
            });
            const activeTab = document.getElementById(`ppmpTab-${tabName}`);
            if (activeTab) {
                activeTab.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
                activeTab.classList.add('border-maroon', 'text-maroon', 'bg-maroon', 'bg-opacity-5', 'font-semibold');
            }
            
            // Update panels
            document.querySelectorAll('.ppmp-tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });
            const activePanel = document.getElementById(`ppmpPanel-${tabName}`);
            if (activePanel) {
                activePanel.classList.remove('hidden');
            }
        }

        function closePPMPBreakdown() {
            const modal = document.getElementById('ppmpBreakdownModal');
            if (modal) modal.classList.add('hidden');
        }

        // LIB Breakdown Modal
        function showLIBBreakdown(departmentId, fiscalYear) {
            fetch(`../api/get_lib_list.php?department_id=${departmentId}&fiscal_year=${fiscalYear}&status=approved`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.libs) {
                        displayLIBBreakdown(data.libs, departmentId, fiscalYear);
                    } else {
                        alert('Error loading LIB data: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading LIB data');
                });
        }

        function displayLIBBreakdown(libList, departmentId, fiscalYear) {
            let modal = document.getElementById('libBreakdownModal');
            if (!modal) {
                const modalHTML = `
                    <div id="libBreakdownModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] flex flex-col">
                            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-800 text-white p-6 rounded-t-2xl flex justify-between items-center flex-shrink-0">
                                <h2 class="text-2xl font-bold">Line Item Budget (LIB)</h2>
                                <button onclick="closeLIBBreakdown()" class="text-white hover:text-blue-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="libBreakdownContent" class="overflow-y-auto flex-1 p-6"></div>
                        </div>
                    </div>
                `;
                document.body.insertAdjacentHTML('beforeend', modalHTML);
                modal = document.getElementById('libBreakdownModal');
            }

            const content = document.getElementById('libBreakdownContent');
            
            if (libList.length === 0) {
                content.innerHTML = '<p class="text-gray-500 text-center py-8">No approved LIB found</p>';
                modal.classList.remove('hidden');
                return;
            }

            // Fetch items for the LIB
            const lib = libList[0];
            fetch(`../api/get_lib_details.php?id=${lib.id}`)
                .then(res => res.json())
                .then(data => {
                    // Calculate grand total from items
                    let calculatedTotal = 0;
                    if (data.items && data.items.length > 0) {
                        calculatedTotal = data.items.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
                    }
                    
                    let html = `
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-4">
                                <div>
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-800">Approved</span>
                                    </div>
                                    <h3 class="text-lg font-bold text-gray-900">LIB - FY ${lib.fiscal_year}</h3>
                                </div>
                                <div class="text-right">
                                    <p class="text-2xl font-bold text-blue-700">₱${calculatedTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                                    <p class="text-xs text-gray-500">Grand Total</p>
                                </div>
                            </div>
                            
                            <div class="border-2 border-gray-300 rounded-lg overflow-hidden">
                                <table class="w-full border-collapse">
                                    <thead>
                                        <tr class="bg-maroon text-white">
                                            <th class="border border-gray-300 px-4 py-3 text-left font-bold">PARTICULARS</th>
                                            <th class="border border-gray-300 px-4 py-3 text-center font-bold" style="width: 200px;">ACCOUNT CODE</th>
                                            <th class="border border-gray-300 px-4 py-3 text-right font-bold" style="width: 200px;">AMOUNT</th>
                                        </tr>
                                    </thead>
                                    <tbody id="libItems-${lib.id}">
                                        <tr><td colspan="3" class="text-center py-4 text-gray-500">Loading items...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="mt-6 flex justify-end">
                            <a href="lib.php" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold">View All LIB</a>
                        </div>
                    `;
                    
                    content.innerHTML = html;
                    
                    // Populate items with categories
                    if (data.items) {
                        populateLIBItemsWithCategories(lib.id, data.items);
                    }
                    
                    modal.classList.remove('hidden');
                });
        }

        function populateLIBItemsWithCategories(libId, items) {
            const tbody = document.getElementById(`libItems-${libId}`);
            if (!tbody) return;
            
            if (items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="3" class="text-center py-4 text-gray-500">No items found</td></tr>';
                return;
            }
            
            // Group items by category
            const categories = {};
            items.forEach(item => {
                const category = item.category || 'B. Maintenance & Other Operating Expenses';
                if (!categories[category]) {
                    categories[category] = [];
                }
                categories[category].push(item);
            });
            
            let html = '';
            let grandTotal = 0;
            
            // Render each category
            Object.keys(categories).forEach(categoryName => {
                const categoryItems = categories[categoryName];
                let categoryTotal = 0;
                
                // Category header row
                html += `
                    <tr class="bg-maroon text-white">
                        <td colspan="3" class="border border-gray-300 px-4 py-2 font-bold">${categoryName}</td>
                    </tr>
                `;
                
                // Category items
                categoryItems.forEach(item => {
                    const amount = parseFloat(item.amount || 0);
                    categoryTotal += amount;
                    
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-4 py-2 text-sm">${item.particulars || '-'}</td>
                            <td class="border border-gray-300 px-4 py-2 text-sm text-center">${item.account_code || '-'}</td>
                            <td class="border border-gray-300 px-4 py-2 text-sm text-right">${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                        </tr>
                    `;
                });
                
                // Category subtotal
                html += `
                    <tr class="bg-gray-100">
                        <td colspan="2" class="border border-gray-300 px-4 py-2 text-right font-semibold">Sub-Total</td>
                        <td class="border border-gray-300 px-4 py-2 text-right font-bold">₱${categoryTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    </tr>
                `;
                
                grandTotal += categoryTotal;
            });
            
            // Grand total row
            html += `
                <tr class="bg-maroon text-white">
                    <td colspan="2" class="border border-gray-300 px-4 py-3 text-right font-bold text-lg">Grand Total</td>
                    <td class="border border-gray-300 px-4 py-3 text-right font-bold text-lg">₱${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                </tr>
            `;
            
            tbody.innerHTML = html;
        }

        function closeLIBBreakdown() {
            const modal = document.getElementById('libBreakdownModal');
            if (modal) modal.classList.add('hidden');
        }

        // Update allocation card link without page refresh
        function updateAllocationCardLink(year) {
            const cardLink = document.getElementById('allocationCardLink');
            const yearDisplay = document.getElementById('allocationYearDisplay');
            const amountDisplay = document.getElementById('allocationAmount');
            
            if (cardLink) {
                // Update the href to include the selected year
                cardLink.href = `allocations_view.php?year=${year}`;
            }
            
            if (yearDisplay) {
                // Update the displayed year text
                yearDisplay.textContent = year;
            }
            
            // Fetch and update the allocation amount for the selected year
            if (amountDisplay) {
                // Show loading state
                amountDisplay.innerHTML = '<span class="opacity-50">Loading...</span>';
                
                fetch(`../api/get_allocation_amount.php?department_id=<?php echo $departmentId; ?>&year=${year}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const amount = parseFloat(data.amount || 0);
                            amountDisplay.textContent = '₱' + amount.toLocaleString('en-US', {
                                minimumFractionDigits: 2,
                                maximumFractionDigits: 2
                            });
                        } else {
                            amountDisplay.textContent = '₱0.00';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching allocation amount:', error);
                        amountDisplay.textContent = '₱0.00';
                    });
            }
        }

        // Change allocation year (kept for backward compatibility)
        function changeAllocationYear(year) {
            // Reload page with selected year parameter
            const url = new URL(window.location.href);
            url.searchParams.set('allocation_year', year);
            window.location.href = url.toString();
        }

        // Update utilization card link without page refresh
        function updateUtilizationCardLink(year) {
            const cardLink = document.getElementById('utilizationCardLink');
            const yearDisplay = document.getElementById('utilizationYearDisplay');
            const amountDisplay = document.getElementById('utilizationAmount');
            const countDisplay = document.getElementById('utilizationCount');
            
            if (cardLink) {
                // Update the href to include the selected year
                cardLink.href = `utilization__view.php?year=${year}`;
            }
            
            if (yearDisplay) {
                // Update the displayed year text
                yearDisplay.textContent = year;
            }
            
            // Fetch and update the utilization amount for the selected year
            if (amountDisplay) {
                // Show loading state
                amountDisplay.innerHTML = '<span class="opacity-50">Loading...</span>';
                
                fetch(`../api/get_utilization_amount.php?department_id=<?php echo $departmentId; ?>&year=${year}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const amount = parseFloat(data.amount || 0);
                            const count = parseInt(data.count || 0);
                            
                            // Update amount display
                            if (count == 0) {
                                amountDisplay.className = 'text-4xl font-bold text-gray-400 mb-2';
                                amountDisplay.textContent = '₱0.00';
                            } else {
                                const isNegative = amount < 0;
                                amountDisplay.className = `text-4xl font-bold ${isNegative ? 'text-red-700' : 'text-green-700'} mb-2`;
                                amountDisplay.textContent = '₱' + amount.toLocaleString('en-US', {
                                    minimumFractionDigits: 2,
                                    maximumFractionDigits: 2
                                });
                            }
                            
                            // Update count display
                            if (countDisplay) {
                                countDisplay.textContent = count;
                            }
                        } else {
                            amountDisplay.className = 'text-4xl font-bold text-gray-400 mb-2';
                            amountDisplay.textContent = '₱0.00';
                            if (countDisplay) {
                                countDisplay.textContent = '0';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching utilization amount:', error);
                        amountDisplay.className = 'text-4xl font-bold text-gray-400 mb-2';
                        amountDisplay.textContent = '₱0.00';
                        if (countDisplay) {
                            countDisplay.textContent = '0';
                        }
                    });
            }
        }
    </script>

    <script>
        // Real-time Recent Notifications Update
        function refreshRecentNotifications() {
            fetch('../ajax/get_recent_notifications.php', {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.html) {
                    const container = document.getElementById('recentNotificationsContainer');
                    if (container) {
                        container.innerHTML = data.html;
                    }
                }
            })
            .catch(error => {
                console.error('Error refreshing recent notifications:', error);
            });
        }

        // Initial load
        document.addEventListener('DOMContentLoaded', function() {
            refreshRecentNotifications();
            
            // Refresh every 10 seconds
            setInterval(refreshRecentNotifications, 10000);
        });

        // Pause updates when page is hidden, resume when visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                refreshRecentNotifications();
            }
        });
    </script>

</body>

