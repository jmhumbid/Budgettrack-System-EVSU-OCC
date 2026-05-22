<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_role']) || in_array($_SESSION['user_role'], ['budget', 'school_admin'], true)) {
    header('Location: ../login.php');
    exit;
}

$activeSidebar = 'utilization_view';

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../components/profile_avatar.php';

$username = $_SESSION['user_name'] ?? 'User';
$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;

try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM allocations_files WHERE department_id IS NULL ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->execute();
    $generalFile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $generalFile = null;
}

// Get utilization history for archived files
$utilizationHistoryList = [];
try {
    $db = getDB();
    $historyStmt = $db->prepare("SELECT * FROM utilization_history ORDER BY created_at DESC");
    $historyStmt->execute();
    $utilizationHistoryList = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $utilizationHistoryList = [];
}

$departmentId = $_SESSION['department_id'] ?? null;
$departmentName = $_SESSION['department_name'] ?? null;
if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

// Get child departments (departments that have this department as parent)
$childDepartments = [];
if ($departmentId) {
    try {
        $childStmt = $db->prepare("
            SELECT id, dept_name, dept_code FROM departments 
            WHERE parent_department_id = ? AND is_active = 1
        ");
        $childStmt->execute([$departmentId]);
        $childDepartments = $childStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $childDepartments = [];
    }
}

$portalLabel = ($_SESSION['user_role'] === 'procurement')
    ? ($departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal")
    : ($departmentName ? "Department Portal | " . htmlspecialchars($departmentName) : "Department Portal");

// Check if user is from Admin department
$isAdminDepartment = false;
if ($departmentName && stripos($departmentName, 'admin') !== false) {
    $isAdminDepartment = true;
}

$tag = ($_SESSION['user_role'] === 'procurement') ? 'Procurement Office' : ($isAdminDepartment ? 'Admin Office' : 'Department Office');

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Utilization Viewer</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        };
    </script>
    <style>
        :root {
            --sidebar-expanded-width: 260px;
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
    </style>
</head>

<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <div id="sidebar"
            class="fixed left-0 top-0 h-screen bg-white shadow-lg border-r border-gray-200 transition-all duration-300 z-40 overflow-y-auto">
            <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-maroon sidebar-text">BudgetTrack</h2>
                    <p class="text-sm text-gray-600 sidebar-text"><?php echo htmlspecialchars($portalLabel); ?></p>
                </div>
                <button id="sidebarToggle" type="button" class="p-2 rounded-lg hover:bg-gray-100 transition-colors"
                    aria-label="Toggle sidebar">
                    <svg class="w-5 h-5 text-gray-600 sidebar-toggle-icon" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5l-7 7 7 7M20 5l-7 7 7 7"></path>
                    </svg>
                </button>
            </div>
            <?php if ($_SESSION['user_role'] === 'procurement'): ?>
                <?php include __DIR__ . '/../components/proc_sidebar.php'; ?>
            <?php else: ?>
                <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
            <?php endif; ?>
        </div>

        <div class="flex-1 flex flex-col overflow-y-auto" data-main-content>
            <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="text-white">
                            <div class="flex items-center gap-3 mb-2">
                                <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0012.586 3H7a2 2 0 01-2 2v14a2 2 0 002 2h10a2 2 0 002-2v-5m-5 5l-5-5">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Utilization</h1>
                                    <p class="text-red-100 text-sm">Access the latest utilization upload</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-white bg-opacity-20 backdrop-blur-sm text-white border border-white border-opacity-30">
                                    <span class="w-2 h-2 bg-green-300 rounded-full mr-2 animate-pulse"></span>
                                    <?php echo htmlspecialchars($tag); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-4">
                            <?php include __DIR__ . '/../components/notification_bell.php'; ?>
                            <div class="relative">
                                <button onclick="toggleProfileDropdown()"
                                    class="flex items-center space-x-3 bg-white bg-opacity-20 backdrop-blur-sm px-4 py-2 rounded-xl hover:bg-opacity-30 transition-colors border border-white border-opacity-30">
                                    <?php render_profile_avatar(['classes' => 'bg-white bg-opacity-30 text-white font-semibold border border-white border-opacity-50']); ?>
                                    <div class="text-white text-sm">
                                        <div class="font-medium"><?php echo htmlspecialchars($username); ?></div>
                                        <div class="text-xs text-red-100"><?php echo htmlspecialchars($userEmail); ?>
                                        </div>
                                    </div>
                                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7"></path>
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                            </svg>
                                            Change Password
                                        </a>
                                        <a href="account_settings.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Account Settings
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

            <main class="flex-1 overflow-y-auto p-8 bg-gradient-to-br from-gray-50 via-white to-gray-50">
                <!-- History Modal -->
                <div id="historyModal"
                    class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                    <div
                        class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div
                            class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-6 py-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <h3 class="text-xl font-bold text-white">Utilization History</h3>
                                <p class="text-gray-200 text-sm" id="historyDepartmentName">View all changes and
                                    activities</p>
                            </div>
                            <button onclick="toggleHistoryModal()"
                                class="text-white hover:text-red-200 transition-colors p-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-6">
                            <div id="historyBody" class="space-y-4">
                                <!-- History entries will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary View Modal -->
                <div id="summaryViewModal"
                    class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
                    <div
                        class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div
                            class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-6 py-4 flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-white">Budget Utilization Report</h2>
                                <p class="text-red-100 text-sm mt-1" id="summaryViewDepartmentName">Department/Office: -
                                </p>
                            </div>
                            <button onclick="closeSummaryViewModal()"
                                class="text-white hover:text-red-200 transition-colors p-2">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="flex-1 overflow-y-auto p-8">
                            <div id="summaryViewContent">
                                <!-- Summary content will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($childDepartments)): ?>
                    <!-- Tab Navigation -->
                    <div class="mb-6 border-b border-gray-200">
                        <div class="flex gap-2">
                            <button id="utilTab-myUtilization" onclick="switchUtilizationTab('myUtilization')"
                                class="util-tab-btn px-4 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5 rounded-t-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                                My Utilization
                            </button>
                            <button id="utilTab-subDepartments" onclick="switchUtilizationTab('subDepartments')"
                                class="util-tab-btn px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                    </path>
                                </svg>
                                Sub-Departments (<?php echo count($childDepartments); ?>)
                            </button>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Fiscal Year Filter -->
                <div class="mb-6 flex items-center gap-4 bg-white rounded-xl shadow-sm border border-gray-200 p-4">
                    <label class="text-sm font-semibold text-gray-700 flex items-center gap-2">
                        <svg class="w-5 h-5 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        Filter by Fiscal Year:
                    </label>
                    <select id="fiscalYearFilter" onchange="filterByFiscalYear()" 
                        class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon bg-white text-gray-900 font-medium">
                        <option value="">All Years</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
                        <option value="2030">2030</option>
                    </select>
                    <div id="fiscalYearInfo" class="text-sm text-gray-600 ml-auto">
                        <span class="font-medium">Viewing:</span> <span id="currentFiscalYearText" class="text-maroon font-semibold">2026</span>
                    </div>
                </div>

                <!-- My Utilization Tab Panel -->
                <div id="utilPanel-myUtilization" class="util-tab-panel<?php echo empty($childDepartments) ? '' : ''; ?>">
                    <!-- Saved Utilization Summary Display -->
                    <div id="savedSummaryDisplay" class="space-y-6">
                        <!-- Summary will be displayed here -->
                    </div>
                </div>

                <?php if (!empty($childDepartments)): ?>
                    <!-- Sub-Departments Tab Panel -->
                    <div id="utilPanel-subDepartments" class="util-tab-panel hidden">
                        <div class="space-y-6">
                            <?php
                            foreach ($childDepartments as $childIndex => $child):
                                // Get child department's most recent utilization summary from ANY fiscal year
                                $childSummary = null;
                                $childTotals = null;
                                $childPrEntries = [];
                                $childTravelsEntries = [];
                                $childHonorariaEntries = [];
                                $childUtilizationEntries = [];
                                try {
                                    // Get most recently saved/updated summary across all fiscal years
                                    $childSummaryStmt = $db->prepare("
                                    SELECT * FROM utilization_summaries 
                                    WHERE department_id = ?
                                    ORDER BY COALESCE(updated_at, created_at) DESC, fiscal_year DESC
                                    LIMIT 1
                                ");
                                    $childSummaryStmt->execute([$child['id']]);
                                    $childSummary = $childSummaryStmt->fetch(PDO::FETCH_ASSOC);

                                    if ($childSummary) {
                                        $childTotals = json_decode($childSummary['totals'] ?? '{}', true);
                                        $childPrEntries = json_decode($childSummary['pr_entries'] ?? '[]', true) ?: [];
                                        $childTravelsEntries = json_decode($childSummary['travels_entries'] ?? '[]', true) ?: [];
                                        $childHonorariaEntries = json_decode($childSummary['honoraria_entries'] ?? '[]', true) ?: [];
                                        $childUtilizationEntries = json_decode($childSummary['utilization_entries'] ?? '[]', true) ?: [];
                                    }
                                } catch (Exception $e) {
                                }
                                ?>
                                <?php
                                $childLastUpdated = $childSummary ? date('M j, Y g:i A', strtotime($childSummary['updated_at'] ?? $childSummary['created_at'])) : '';
                                ?>
                                <script>
                                    if (!window._childDeptData) window._childDeptData = {};
                                    window._childDeptData[<?php echo $childIndex; ?>] = {
                                        departmentId: <?php echo $child['id']; ?>,
                                        departmentName: <?php echo json_encode($child['dept_name']); ?>,
                                        prEntries: <?php echo json_encode($childPrEntries); ?>,
                                        travelsEntries: <?php echo json_encode($childTravelsEntries); ?>,
                                        honorariaEntries: <?php echo json_encode($childHonorariaEntries); ?>,
                                        totals: <?php echo json_encode($childTotals ?: new stdClass()); ?>
                                    };
                                </script>
                                <!-- Child Department Card - Matching My Utilization Layout -->
                                <div
                                    class="bg-gradient-to-br from-white via-gray-50 to-gray-100 rounded-3xl shadow-xl border border-gray-200 overflow-hidden <?php echo $childIndex > 0 ? 'mt-8' : ''; ?>">
                                    <!-- Header with gradient -->
                                    <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-8 py-6">
                                        <div class="flex flex-wrap items-center justify-between gap-4">
                                            <div class="flex items-center gap-4">
                                                <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-xl p-3">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                        </path>
                                                    </svg>
                                                </div>
                                                <div>
                                                    <div class="flex items-center gap-3">
                                                        <h2 class="text-2xl font-bold text-white">
                                                            <?php echo htmlspecialchars($child['dept_name']); ?>
                                                        </h2>
                                                        <span
                                                            class="px-3 py-1 bg-white bg-opacity-20 text-white rounded-full text-xs font-medium">Sub-Dept</span>
                                                        <?php if ($childSummary): ?>
                                                            <span
                                                                class="px-3 py-1 bg-yellow-500 bg-opacity-90 text-white rounded-full text-xs font-bold">
                                                                FY <?php echo htmlspecialchars($childSummary['fiscal_year']); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="text-red-100 text-sm">
                                                        <?php echo htmlspecialchars($child['dept_code']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <?php if ($childSummary): ?>
                                                    <button
                                                        onclick="downloadChildUtilizationPDF(<?php echo $childSummary['id']; ?>)"
                                                        class="px-5 py-2.5 bg-white bg-opacity-20 backdrop-blur-sm text-white rounded-xl hover:bg-opacity-30 transition-all font-semibold text-sm flex items-center gap-2 border border-white border-opacity-30 shadow-lg">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                            </path>
                                                        </svg>
                                                        Download PDF
                                                    </button>
                                                    <button
                                                        onclick="showChildUtilizationHistory(<?php echo $child['id']; ?>, '<?php echo htmlspecialchars($child['dept_name'], ENT_QUOTES); ?>')"
                                                        class="px-5 py-2.5 bg-white bg-opacity-20 backdrop-blur-sm text-white rounded-xl hover:bg-opacity-30 transition-all font-semibold text-sm flex items-center gap-2 border border-white border-opacity-30 shadow-lg">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                        </svg>
                                                        History
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($childSummary): ?>
                                        <div class="p-8">
                                            <!-- Summary Cards -->
                                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                                <div
                                                    class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 border border-blue-200 shadow-md hover:shadow-lg transition-shadow">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="bg-blue-500 rounded-xl p-3">
                                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">
                                                        Total Allocated</p>
                                                    <p class="text-xl font-bold text-blue-900">
                                                        ₱<?php echo number_format($childTotals['totalAllocated'] ?? 0, 2); ?></p>
                                                </div>
                                                <div
                                                    class="bg-gradient-to-br from-red-50 to-red-100 rounded-2xl p-6 border border-red-200 shadow-md hover:shadow-lg transition-shadow">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="bg-red-500 rounded-xl p-3">
                                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs font-semibold text-red-600 uppercase tracking-wide mb-1">Total
                                                        Deductions</p>
                                                    <p class="text-xl font-bold text-red-900">
                                                        ₱<?php echo number_format($childTotals['totalDeductions'] ?? 0, 2); ?></p>
                                                </div>
                                                <div
                                                    class="bg-gradient-to-br from-maroon to-red-700 rounded-2xl p-6 border border-red-300 shadow-md hover:shadow-lg transition-shadow">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs font-semibold text-red-100 uppercase tracking-wide mb-1">Total
                                                        Balance</p>
                                                    <p class="text-2xl font-bold text-white">
                                                        ₱<?php echo number_format($childTotals['totalBalance'] ?? 0, 2); ?></p>
                                                </div>
                                                <div
                                                    class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200 shadow-md hover:shadow-lg transition-shadow">
                                                    <div class="flex items-center justify-between mb-3">
                                                        <div class="bg-gray-500 rounded-xl p-3">
                                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                        </div>
                                                    </div>
                                                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Last
                                                        Updated</p>
                                                    <p class="text-lg font-bold text-gray-900"><?php echo $childLastUpdated; ?></p>
                                                </div>
                                            </div>

                                            <!-- View Details Buttons - Compact -->
                                            <div class="mb-6">
                                                <div class="flex items-center gap-3 mb-4">
                                                    <div class="bg-gray-500 rounded-lg p-2">
                                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                    <h3 class="text-xl font-bold text-gray-800">View Details</h3>
                                                </div>
                                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                                    <!-- Purchase Requests Button -->
                                                    <button onclick='showChildDetailModal(<?php echo $childIndex; ?>, "pr")'
                                                        class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-blue-50 to-blue-100 border border-blue-200 rounded-xl hover:shadow-md hover:border-blue-300 transition-all group">
                                                        <div
                                                            class="bg-blue-500 rounded-lg p-2 group-hover:scale-110 transition-transform">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                        <span class="text-sm font-semibold text-blue-800">Purchase Requests</span>
                                                    </button>
                                                    <!-- Travels Button -->
                                                    <button onclick='showChildDetailModal(<?php echo $childIndex; ?>, "travels")'
                                                        class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-green-50 to-green-100 border border-green-200 rounded-xl hover:shadow-md hover:border-green-300 transition-all group">
                                                        <div
                                                            class="bg-green-500 rounded-lg p-2 group-hover:scale-110 transition-transform">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z">
                                                                </path>
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            </svg>
                                                        </div>
                                                        <span class="text-sm font-semibold text-green-800">Travels</span>
                                                    </button>
                                                    <!-- Prior Years Button -->
                                                    <button onclick='showChildDetailModal(<?php echo $childIndex; ?>, "priorYears")'
                                                        class="flex items-center gap-3 px-4 py-3 bg-gradient-to-r from-orange-50 to-orange-100 border border-orange-200 rounded-xl hover:shadow-md hover:border-orange-300 transition-all group">
                                                        <div
                                                            class="bg-orange-500 rounded-lg p-2 group-hover:scale-110 transition-transform">
                                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                                viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                                    stroke-width="2"
                                                                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                                </path>
                                                            </svg>
                                                        </div>
                                                        <span class="text-sm font-semibold text-orange-800">Prior Years</span>
                                                    </button>
                                                </div>
                                            </div>


                                            <!-- Budget Utilization Breakdown -->
                                            <div class="mb-4">
                                                <div class="flex items-center gap-3 mb-5">
                                                    <div class="bg-maroon rounded-lg p-2">
                                                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                                            </path>
                                                        </svg>
                                                    </div>
                                                    <h3 class="text-xl font-bold text-gray-800">Budget Utilization Breakdown</h3>
                                                </div>
                                                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                                                    <table class="w-full text-sm">
                                                        <thead>
                                                            <tr
                                                                class="bg-gradient-to-r from-gray-100 to-gray-50 border-b-2 border-gray-300">
                                                                <th class="text-left py-4 px-6 font-bold text-gray-800">Expense
                                                                    Category</th>
                                                                <th class="text-left py-4 px-6 font-bold text-gray-800">Account
                                                                    Code</th>
                                                                <th class="text-right py-4 px-6 font-bold text-gray-800">Allocated
                                                                    Budget</th>
                                                                <th class="text-right py-4 px-6 font-bold text-gray-800">Deductions
                                                                </th>
                                                                <th class="text-right py-4 px-6 font-bold text-gray-800">Total
                                                                    Balance</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody class="bg-white divide-y divide-gray-100">
                                                            <?php if (empty($childUtilizationEntries)): ?>
                                                                <tr>
                                                                    <td colspan="5" class="py-6 px-6 text-center text-gray-500 italic">
                                                                        No budget utilization entries found</td>
                                                                </tr>
                                                            <?php else: ?>
                                                                <?php foreach ($childUtilizationEntries as $entry): ?>
                                                                    <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100">
                                                                        <td class="py-4 px-6 font-semibold text-gray-800">
                                                                            <?php echo htmlspecialchars($entry['category'] ?? '-'); ?>
                                                                        </td>
                                                                        <td class="py-4 px-6 text-gray-700">
                                                                            <?php echo htmlspecialchars($entry['accountCode'] ?? $entry['account_code'] ?? '-'); ?>
                                                                        </td>
                                                                        <td class="py-4 px-6 text-right text-gray-700 font-medium">
                                                                            ₱<?php echo number_format($entry['allocated'] ?? 0, 2); ?></td>
                                                                        <td class="py-4 px-6 text-right text-red-600 font-medium">
                                                                            ₱<?php echo number_format($entry['deduction'] ?? 0, 2); ?></td>
                                                                        <td
                                                                            class="py-4 px-6 text-right font-bold text-lg <?php echo ($entry['balance'] ?? 0) < 0 ? 'text-red-600' : 'text-green-600'; ?>">
                                                                            ₱<?php echo number_format($entry['balance'] ?? 0, 2); ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="p-8">
                                            <div class="flex items-center justify-between">
                                                <p class="text-gray-500 italic">No utilization summary has been set for this
                                                    department yet.</p>
                                                <button
                                                    onclick="showChildUtilizationHistory(<?php echo $child['id']; ?>, '<?php echo htmlspecialchars($child['dept_name'], ENT_QUOTES); ?>')"
                                                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition-colors flex items-center gap-2 border border-gray-300">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                    History
                                                </button>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="noSummariesMessage" class="text-center py-16 hidden">
                    <div class="bg-white rounded-3xl shadow-xl border-2 border-gray-200 p-12 max-w-md mx-auto">
                        <div class="bg-gray-100 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6">
                            <svg class="w-10 h-10 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-800 mb-2">No Summaries Available</h3>
                        <p class="text-gray-500">No saved utilization summaries found for this department/office.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        (function () {
            const storageKey = 'sidebarCollapsed';
            if (localStorage.getItem(storageKey) === 'true') {
                document.body.classList.add('sidebar-collapsed');
            }
        })();

        document.addEventListener('DOMContentLoaded', function () {
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
                setTimeout(function () {
                    mainContent.classList.add('sidebar-ready');
                }, 10);
            }

            toggleBtn?.addEventListener('click', function () {
                const collapsed = !body.classList.contains('sidebar-collapsed');
                applyState(collapsed);
                localStorage.setItem(storageKey, collapsed ? 'true' : 'false');
            });
        });

        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown) return;
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
            if (!button && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        // Store current summary ID for download
        let currentSummaryId = null;
        let currentFiscalYear = '2026'; // Default to current year

        // Filter by fiscal year
        function filterByFiscalYear() {
            const fiscalYearFilter = document.getElementById('fiscalYearFilter');
            const currentFiscalYearText = document.getElementById('currentFiscalYearText');
            
            currentFiscalYear = fiscalYearFilter.value;
            
            // Update display text
            if (currentFiscalYearText) {
                currentFiscalYearText.textContent = currentFiscalYear || 'All Years';
            }
            
            // Reload summaries with the selected fiscal year
            loadSavedSummaries();
            
            // Reload history if modal is open
            const historyModal = document.getElementById('historyModal');
            if (historyModal && !historyModal.classList.contains('hidden')) {
                showHistory();
            }
        }

        // Load saved utilization summaries on page load
        function loadSavedSummaries() {
            const departmentId = <?php echo $departmentId ? $departmentId : 'null'; ?>;
            console.log('Loading summaries for department ID:', departmentId, 'Fiscal Year:', currentFiscalYear);
            
            if (!departmentId) {
                console.log('No department ID, showing no summaries message');
                document.getElementById('noSummariesMessage').classList.remove('hidden');
                return;
            }

            // Build API URL with optional fiscal year filter
            let apiUrl = `../api/load_utilization_summaries.php?department_id=${departmentId}`;
            if (currentFiscalYear) {
                apiUrl += `&fiscal_year=${currentFiscalYear}`;
            }
            
            console.log('Fetching summaries from:', apiUrl);
            
            fetch(apiUrl)
                .then(response => {
                    console.log('Response status:', response.status);
                    return response.json();
                })
                .then(data => {
                    console.log('Summaries data received:', data);
                    
                    const summaryDisplay = document.getElementById('savedSummaryDisplay');
                    const noSummariesMessage = document.getElementById('noSummariesMessage');
                    const summaryActions = document.getElementById('summaryActions');

                    if (!summaryDisplay) {
                        console.error('summaryDisplay element not found');
                        return;
                    }

                    if (data.success && data.summaries && data.summaries.length > 0) {
                        console.log('Found', data.summaries.length, 'summaries');
                        summaryDisplay.innerHTML = '';
                        noSummariesMessage.classList.add('hidden');

                        // Show action buttons
                        if (summaryActions) {
                            summaryActions.classList.remove('hidden');
                        }

                        // Update history badge and modal
                        const historyBadge = document.getElementById('historyBadge');
                        const historyModalCount = document.getElementById('historyModalCount');
                        const historyList = document.getElementById('summaryHistoryList');

                        if (data.summaries.length > 0) {
                            // Update badge
                            if (historyBadge) {
                                historyBadge.textContent = data.summaries.length;
                                historyBadge.classList.remove('hidden');
                            }

                            // Update modal count
                            if (historyModalCount) {
                                historyModalCount.textContent = `${data.summaries.length} ${data.summaries.length === 1 ? 'summary' : 'summaries'}`;
                            }

                            // Populate history list
                            if (historyList) {
                                historyList.innerHTML = '';

                                data.summaries.forEach((summary, index) => {
                                    const historyItem = document.createElement('div');
                                    historyItem.className = `border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow ${index === 0 ? 'border-maroon border-2' : ''}`;

                                    const createdDate = new Date(summary.created_at);
                                    const updatedDate = summary.updated_at ? new Date(summary.updated_at) : null;
                                    const isUpdated = updatedDate && updatedDate.getTime() !== createdDate.getTime();

                                    const formattedDate = createdDate.toLocaleDateString('en-US', {
                                        year: 'numeric',
                                        month: 'long',
                                        day: 'numeric',
                                        hour: '2-digit',
                                        minute: '2-digit'
                                    });

                                    // Calculate overall total from totals JSON if available
                                    let totalAmount = 'N/A';
                                    if (summary.totals) {
                                        try {
                                            const totals = typeof summary.totals === 'string' ? JSON.parse(summary.totals) : summary.totals;
                                            const totalBalance = totals.totalBalance || 0;
                                            totalAmount = formatNumber(totalBalance);
                                        } catch (e) {
                                            console.error('Error parsing totals:', e);
                                        }
                                    }

                                    historyItem.innerHTML = `
                                        <div class="bg-gradient-to-br from-white to-gray-50 border-2 border-gray-200 rounded-2xl p-6 hover:shadow-xl hover:border-maroon transition-all duration-300">
                                            <div class="flex items-center justify-between mb-4">
                                                <div class="flex-1">
                                                    <div class="flex items-center gap-3 mb-2">
                                                        <div class="bg-gradient-to-br from-maroon to-red-700 rounded-xl p-2">
                                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                        </div>
                                                        <div>
                                                            <h3 class="text-xl font-bold text-gray-900">${summary.department_name || 'Department/Office'}</h3>
                                                            <div class="flex items-center gap-2 mt-1">
                                                                ${isUpdated ? '<span class="px-3 py-1 bg-blue-500 text-white text-xs font-semibold rounded-full shadow-md">Updated</span>' : ''}
                                                                ${index === 0 ? '<span class="px-3 py-1 bg-maroon text-white text-xs font-semibold rounded-full shadow-md">Current</span>' : ''}
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="ml-12 space-y-1">
                                                        <p class="text-sm text-gray-600 flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                            </svg>
                                                            ${formattedDate}
                                                        </p>
                                                        <p class="text-xs text-gray-500 flex items-center gap-2">
                                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                            </svg>
                                                            Fiscal Year: ${summary.fiscal_year || 'N/A'}
                                                        </p>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-3">
                                                    <button 
                                                        onclick="event.stopPropagation(); viewUtilizationSummary(${summary.id});" 
                                                        class="px-5 py-2.5 bg-gradient-to-r from-maroon to-red-700 text-white rounded-xl hover:from-red-800 hover:to-red-900 transition-all font-semibold text-sm flex items-center gap-2 shadow-lg hover:shadow-xl"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                        </svg>
                                                        View
                                                    </button>
                                                    <button 
                                                        onclick="event.stopPropagation(); downloadUtilizationPDF(${summary.id});" 
                                                        class="px-5 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold text-sm flex items-center gap-2 shadow-lg hover:shadow-xl"
                                                    >
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        PDF
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="ml-12 mt-4 pt-4 border-t border-gray-200">
                                                <div class="flex items-center justify-between">
                                                    <div>
                                                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Overall Total Balance</p>
                                                        <p class="font-bold text-maroon text-2xl">${totalAmount}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    `;

                                    historyList.appendChild(historyItem);
                                });
                            }
                        } else {
                            if (historyBadge) historyBadge.classList.add('hidden');
                            if (historyModalCount) historyModalCount.textContent = '0';
                            if (historyList) historyList.innerHTML = '<p class="text-center text-gray-500 py-8">No summaries found</p>';
                        }

                        // Get the most recently saved/updated summary (not necessarily the highest fiscal year)
                        // Sort by updated_at or created_at to get the one that was just saved
                        const sortedByDate = [...data.summaries].sort((a, b) => {
                            const dateA = new Date(a.updated_at || a.created_at);
                            const dateB = new Date(b.updated_at || b.created_at);
                            return dateB - dateA; // Most recent first
                        });
                        
                        const latestSummary = sortedByDate[0];
                        currentSummaryId = latestSummary.id;
                        
                        console.log('Loading most recently saved summary ID:', latestSummary.id);
                        console.log('Most recently saved summary fiscal year:', latestSummary.fiscal_year);
                        console.log('Most recently saved summary date:', latestSummary.updated_at || latestSummary.created_at);

                        // Load and display the summary
                        loadSummaryById(latestSummary.id);
                    } else {
                        summaryDisplay.innerHTML = '';
                        noSummariesMessage.classList.remove('hidden');
                        if (summaryActions) summaryActions.classList.add('hidden');
                        if (historySection) historySection.classList.add('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading saved summaries:', error);
                    document.getElementById('noSummariesMessage').classList.remove('hidden');
                });
        }

        // Function to load summary by ID
        function loadSummaryById(summaryId) {
            console.log('loadSummaryById called with ID:', summaryId);
            
            if (!summaryId) {
                console.error('No summary ID provided');
                return;
            }

            const summaryDisplay = document.getElementById('savedSummaryDisplay');
            const noSummariesMessage = document.getElementById('noSummariesMessage');

            console.log('summaryDisplay element:', summaryDisplay);
            console.log('noSummariesMessage element:', noSummariesMessage);

            // Show loading state
            if (summaryDisplay) {
                summaryDisplay.innerHTML = '<div class="text-center py-8"><p class="text-gray-500">Loading summary...</p></div>';
            }
            if (noSummariesMessage) {
                noSummariesMessage.classList.add('hidden');
            }

            const apiUrl = `../api/get_utilization_summary.php?id=${summaryId}`;
            console.log('Fetching summary from:', apiUrl);

            fetch(apiUrl)
                .then(response => {
                    console.log('Summary response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(summaryData => {
                    console.log('Summary data received:', summaryData);
                    
                    if (summaryData.success && summaryData.summary) {
                        console.log('Calling displaySummaryOnPage with summary:', summaryData.summary);
                        currentSummaryId = summaryId;
                        displaySummaryOnPage(summaryData.summary).catch(err => {
                            console.error('Error in displaySummaryOnPage:', err);
                            if (summaryDisplay) summaryDisplay.innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error displaying summary: ' + err.message + '</p></div>';
                        });
                    } else {
                        console.error('Summary not found or error:', summaryData.message);
                        if (summaryDisplay) summaryDisplay.innerHTML = '';
                        if (noSummariesMessage) {
                            noSummariesMessage.textContent = summaryData.message || 'Summary not found';
                            noSummariesMessage.classList.remove('hidden');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error loading summary:', error);
                    if (summaryDisplay) summaryDisplay.innerHTML = '';
                    if (noSummariesMessage) {
                        noSummariesMessage.textContent = 'Error loading summary. Please try again.';
                        noSummariesMessage.classList.remove('hidden');
                    }
                });
        }

        // Function to display summary directly on the page
        async function displaySummaryOnPage(summary) {
            const summaryDisplay = document.getElementById('savedSummaryDisplay');
            if (!summaryDisplay) return;

            try {

            const utilizationEntries = (() => { try { return JSON.parse(summary.utilization_entries || '[]'); } catch(e) { return []; } })();
            const prEntries = (() => { try { return JSON.parse(summary.pr_entries || '[]'); } catch(e) { return []; } })();
            const travelsEntries = (() => { try { return JSON.parse(summary.travels_entries || '[]'); } catch(e) { return []; } })();
            const honorariaEntries = (() => { try { return JSON.parse(summary.honoraria_entries || '[]'); } catch(e) { return []; } })();

            // Parse deduction data with proper error handling
            let prDeductions = [];
            let travelsDeductions = [];
            let honorariaDeductions = [];

            try {
                if (summary.pr_deductions) {
                    if (typeof summary.pr_deductions === 'string') {
                        prDeductions = summary.pr_deductions.trim() === '' ? [] : JSON.parse(summary.pr_deductions);
                    } else {
                        prDeductions = Array.isArray(summary.pr_deductions) ? summary.pr_deductions : [];
                    }
                }
            } catch (e) {
                console.error('Error parsing pr_deductions:', e);
                prDeductions = [];
            }

            try {
                if (summary.travels_deductions) {
                    if (typeof summary.travels_deductions === 'string') {
                        travelsDeductions = summary.travels_deductions.trim() === '' ? [] : JSON.parse(summary.travels_deductions);
                    } else {
                        travelsDeductions = Array.isArray(summary.travels_deductions) ? summary.travels_deductions : [];
                    }
                }
            } catch (e) {
                console.error('Error parsing travels_deductions:', e);
                travelsDeductions = [];
            }

            try {
                if (summary.honoraria_deductions) {
                    if (typeof summary.honoraria_deductions === 'string') {
                        honorariaDeductions = summary.honoraria_deductions.trim() === '' ? [] : JSON.parse(summary.honoraria_deductions);
                    } else {
                        honorariaDeductions = Array.isArray(summary.honoraria_deductions) ? summary.honoraria_deductions : [];
                    }
                }
            } catch (e) {
                console.error('Error parsing honoraria_deductions:', e);
                honorariaDeductions = [];
            }

            const totals = (() => { try { return JSON.parse(summary.totals || '{}'); } catch(e) { return {}; } })();
            
            // Fetch latest prior years total for this department
            try {
                const priorYearsResponse = await fetch(`../api/load_prior_years.php?department_id=${summary.department_id}&all_years=1`);
                const priorYearsData = await priorYearsResponse.json();
                
                if (priorYearsData.success && priorYearsData.years && Object.keys(priorYearsData.years).length > 0) {
                    // Get the latest year (highest year number)
                    const latestYear = Math.max(...Object.keys(priorYearsData.years).map(Number));
                    const latestEntries = priorYearsData.years[latestYear];
                    
                    // Calculate total from latest year
                    const priorYearsTotal = latestEntries.reduce((sum, entry) => {
                        return sum + 
                            parseFloat(entry.student_development || 0) + 
                            parseFloat(entry.faculty_development || 0) + 
                            parseFloat(entry.curriculum_development || 0) + 
                            parseFloat(entry.facilities_development || 0) + 
                            parseFloat(entry.development_fee || 0) + 
                            parseFloat(entry.laboratory_fee || 0) + 
                            parseFloat(entry.computer_fee || 0);
                    }, 0);
                    
                    totals.priorYearsTotal = priorYearsTotal;
                } else {
                    totals.priorYearsTotal = 0;
                }
            } catch (e) {
                console.error('Error loading prior years total:', e);
                totals.priorYearsTotal = 0;
            }

            // Cache the data for the detail modal
            window._viewSummaryCache = {
                prEntries, travelsEntries, honorariaEntries,
                prDeductions, travelsDeductions, honorariaDeductions,
                totals, summary, utilizationEntries,
                prRows: prEntries.length > 0 ? prEntries.map(entry => `
                    <tr class="hover:bg-blue-50 transition-colors">
                        <td class="py-3 px-4 text-gray-900 font-medium">${entry.purchaseRequest || entry.purchase_request || '-'}</td>
                        <td class="py-3 px-4 text-gray-900">${entry.particulars || '-'}</td>
                        <td class="py-3 px-4 text-gray-900">${entry.prNumber || entry.pr_number || entry.pr_no || '-'}</td>
                        <td class="py-3 px-4 text-gray-500">${entry.date || '-'}</td>
                        <td class="py-3 px-4 text-right text-blue-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                    </tr>
                `).join('') : '<tr><td colspan="5" class="py-6 px-4 text-center text-gray-500 italic">No purchase requests found</td></tr>',
                travelsRows: travelsEntries.length > 0 ? travelsEntries.map(entry => `
                    <tr class="hover:bg-green-50 transition-colors">
                        <td class="py-3 px-4 text-gray-900 font-medium">${entry.travelled || '-'}</td>
                        <td class="py-3 px-4 text-gray-900">${entry.event_activity || entry.event || '-'}</td>
                        <td class="py-3 px-4 text-gray-500">${entry.date || '-'}</td>
                        <td class="py-3 px-4 text-right text-green-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                    </tr>
                `).join('') : '<tr><td colspan="4" class="py-6 px-4 text-center text-gray-500 italic">No travels found</td></tr>',
                honorariaRows: honorariaEntries.length > 0 ? honorariaEntries.map(entry => `
                    <tr class="hover:bg-purple-50 transition-colors">
                        <td class="py-3 px-4 text-gray-500">${entry.date || '-'}</td>
                        <td class="py-3 px-4 text-right text-purple-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                    </tr>
                `).join('') : '<tr><td colspan="2" class="py-6 px-4 text-center text-gray-500 italic">No honoraria found</td></tr>'
            };

            // Determine if it's a department or office
            const departmentNames = ['Computer studies', 'Education', 'Industrial Technology', 'Engineering', 'Hospitality Management'];
            let isDepartment = false;
            let isOffice = false;

            if (summary.department_name) {
                isDepartment = departmentNames.some(name =>
                    summary.department_name.toLowerCase().includes(name.toLowerCase())
                );
                isOffice = !isDepartment;
            }

            const label = isDepartment ? 'Department' : (isOffice ? 'Office' : 'Department/Office');
            const createdDate = new Date(summary.created_at);
            const formattedDate = createdDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Use totalBalance from totals instead of calculating remainingBalance
            const totalBalance = totals.totalBalance || 0;

            // Build utilization table rows
            let utilizationRows = '';
            if (utilizationEntries.length === 0) {
                utilizationRows = '<tr><td colspan="5" class="py-6 px-6 text-center text-gray-500 italic">No budget utilization entries found</td></tr>';
            } else {
                utilizationEntries.forEach(entry => {
                    utilizationRows += `
                        <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100">
                            <td class="py-4 px-6 font-semibold text-gray-800">${entry.category || '-'}</td>
                            <td class="py-4 px-6 text-gray-700">${entry.accountCode || entry.account_code || '-'}</td>
                            <td class="py-4 px-6 text-right text-gray-700 font-medium">${formatNumber(entry.allocated)}</td>
                            <td class="py-4 px-6 text-right text-red-600 font-medium">${formatNumber(entry.deduction)}</td>
                            <td class="py-4 px-6 text-right font-bold text-lg ${entry.balance < 0 ? 'text-red-600' : 'text-green-600'}">${formatNumber(entry.balance)}</td>
                        </tr>
                    `;
                });
            }

            // Build PR table rows
            let prRows = '';
            if (prEntries.length === 0) {
                prRows = '<tr><td colspan="5" class="py-6 px-4 text-center text-gray-500 italic">No purchase requests found</td></tr>';
            } else {
                prEntries.forEach(entry => {
                    const particulars = entry.particulars || '-';
                    prRows += `
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.purchaseRequest || entry.purchase_request || '-'}</td>
                            <td class="py-3 px-4 text-gray-700">${particulars}</td>
                            <td class="py-3 px-4 text-gray-700">${entry.prNumber || entry.pr_number || entry.pr_no || '-'}</td>
                            <td class="py-3 px-4 text-gray-700">${entry.date || '-'}</td>
                            <td class="py-3 px-4 text-right text-blue-600 font-semibold">${formatNumber(entry.amount)}</td>
                        </tr>
                    `;
                });
            }

            // Build travels table rows
            let travelsRows = '';
            if (travelsEntries.length === 0) {
                travelsRows = '<tr><td colspan="4" class="py-6 px-4 text-center text-gray-500 italic">No travels found</td></tr>';
            } else {
                travelsEntries.forEach(entry => {
                    const event = entry.event_activity || entry.event || '-';
                    travelsRows += `
                        <tr class="hover:bg-green-50 transition-colors">
                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.travelled || '-'}</td>
                            <td class="py-3 px-4 text-gray-700">${event}</td>
                            <td class="py-3 px-4 text-gray-700">${entry.date || '-'}</td>
                            <td class="py-3 px-4 text-right text-green-600 font-semibold">${formatNumber(entry.amount)}</td>
                        </tr>
                    `;
                });
            }

            // Build Honoraria table rows
            let honorariaRows = '';
            if (honorariaEntries.length === 0) {
                honorariaRows = '<tr><td colspan="2" class="py-6 px-4 text-center text-gray-500 italic">No Honoraria entries found</td></tr>';
            } else {
                honorariaEntries.forEach(entry => {
                    honorariaRows += `
                        <tr class="hover:bg-purple-50 transition-colors">
                            <td class="py-3 px-4 text-gray-700 font-medium">${entry.date || '-'}</td>
                            <td class="py-3 px-4 text-right text-purple-600 font-semibold">${formatNumber(entry.amount)}</td>
                        </tr>
                    `;
                });
            }

            const updatedDate = summary.updated_at ? new Date(summary.updated_at) : createdDate;
            const lastUpdated = updatedDate.toLocaleDateString('en-US', {
                month: 'short',
                day: 'numeric',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            summaryDisplay.innerHTML = `
                <!-- Budget Utilization Details Header -->
                <div class="bg-gradient-to-br from-white via-gray-50 to-gray-100 rounded-3xl shadow-xl border border-gray-200 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-8 py-6">
                        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-4">
                                <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-xl p-3">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-3xl font-bold text-white mb-1">Budget Utilization Details</h2>
                                    <p class="text-red-100 text-sm">Comprehensive financial overview and breakdown</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button 
                                    onclick="downloadUtilizationPDF(${summary.id})" 
                                    class="px-5 py-2.5 bg-white bg-opacity-20 backdrop-blur-sm text-white rounded-xl hover:bg-opacity-30 transition-all font-semibold text-sm flex items-center gap-2 border border-white border-opacity-30 shadow-lg"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download PDF
                                </button>
                                <button 
                                    onclick="toggleHistoryModal()" 
                                    class="px-5 py-2.5 bg-white bg-opacity-20 backdrop-blur-sm text-white rounded-xl hover:bg-opacity-30 transition-all font-semibold text-sm flex items-center gap-2 border border-white border-opacity-30 shadow-lg"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    History
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 border border-blue-200 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-blue-500 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs font-semibold text-blue-600 uppercase tracking-wide mb-1">${label}</p>
                                <p class="text-xl font-bold text-blue-900">${summary.department_name || 'Department/Office'}</p>
                            </div>
                            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-6 border border-purple-200 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-purple-500 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs font-semibold text-purple-600 uppercase tracking-wide mb-1">Fiscal Year</p>
                                <p class="text-xl font-bold text-purple-900">${summary.fiscal_year}</p>
                            </div>
                            <div class="bg-gradient-to-br from-maroon to-red-700 rounded-2xl p-6 border border-red-300 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs font-semibold text-red-100 uppercase tracking-wide mb-1">Overall Total Budget</p>
                                <p class="text-2xl font-bold text-white">${formatNumber(totalBalance)}</p>
                            </div>
                            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl p-6 border border-gray-200 shadow-md hover:shadow-lg transition-shadow">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="bg-gray-500 rounded-xl p-3">
                                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                </div>
                                <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide mb-1">Last Updated</p>
                                <p class="text-lg font-bold text-gray-900">${lastUpdated}</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Budget Utilization Breakdown Table (Always Visible) -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-5 border-b border-gray-200">
                        <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <div class="bg-maroon rounded-lg p-2">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            Budget Utilization Breakdown
                        </h3>
                    </div>
                    <div class="p-8">
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gradient-to-r from-maroon to-red-700 text-white border-b-2 border-red-800">
                                        <th class="text-left py-4 px-6 font-bold">Expense Category</th>
                                        <th class="text-left py-4 px-6 font-bold">Account Code</th>
                                        <th class="text-right py-4 px-6 font-bold">Allocated Budget</th>
                                        <th class="text-right py-4 px-6 font-bold">Deductions</th>
                                        <th class="text-right py-4 px-6 font-bold">Total Balance</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${utilizationRows}
                                </tbody>
                                <tfoot class="bg-gradient-to-r from-gray-100 to-gray-200 border-t-2 border-gray-400">
                                    <tr class="font-bold">
                                        <td class="py-5 px-6 text-gray-900 text-lg">Total</td>
                                        <td class="py-5 px-6"></td>
                                        <td class="py-5 px-6 text-right text-gray-900 text-lg">${formatNumber(totals.totalAllocated || 0)}</td>
                                        <td class="py-5 px-6 text-right text-gray-900 text-lg">${formatNumber(totals.totalDeductions || 0)}</td>
                                        <td class="py-5 px-6 text-right text-maroon text-xl">${formatNumber(totals.totalBalance || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detail Section Buttons -->
                <div class="bg-white rounded-3xl shadow-xl border border-gray-200 overflow-hidden mb-8">
                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-5 border-b border-gray-200">
                        <h3 class="text-2xl font-bold text-gray-900 flex items-center gap-3">
                            <div class="bg-gray-600 rounded-lg p-2">
                                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                </svg>
                            </div>
                            View Details
                        </h3>
                        <p class="text-gray-500 text-sm mt-1 ml-11">Click on any section below to view its details</p>
                    </div>
                    <div class="p-8">
                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            <!-- Purchase Requests Button -->
                            <button onclick="showViewDetailModal('Purchase Requests', 'pr')" class="group flex flex-col items-center gap-3 p-6 bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl border-2 border-blue-200 hover:border-blue-400 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                <div class="bg-blue-500 group-hover:bg-blue-600 rounded-xl p-3 transition-colors shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-blue-800">Purchase Requests</span>
                                <span class="text-xs font-semibold text-blue-600 bg-blue-200 px-3 py-1 rounded-full">${formatNumber(totals.prTotal || 0)}</span>
                            </button>
                            
                            <!-- Travels Button -->
                            <button onclick="showViewDetailModal('Travels', 'travels')" class="group flex flex-col items-center gap-3 p-6 bg-gradient-to-br from-green-50 to-green-100 rounded-2xl border-2 border-green-200 hover:border-green-400 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                <div class="bg-green-500 group-hover:bg-green-600 rounded-xl p-3 transition-colors shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-green-800">Travels</span>
                                <span class="text-xs font-semibold text-green-600 bg-green-200 px-3 py-1 rounded-full">${formatNumber(totals.travelsTotal || 0)}</span>
                            </button>


                            <!-- PR Deductions Button -->
                            <button onclick="showViewDetailModal('Purchase Request Deductions', 'prDeductions')" class="group flex flex-col items-center gap-3 p-6 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-2xl border-2 border-indigo-200 hover:border-indigo-400 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                <div class="bg-indigo-500 group-hover:bg-indigo-600 rounded-xl p-3 transition-colors shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-indigo-800">PR Deductions</span>
                                <span class="text-xs font-semibold text-indigo-600 bg-indigo-200 px-3 py-1 rounded-full">${formatNumber(prDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</span>
                            </button>

                            <!-- Travels Deductions Button -->
                            <button onclick="showViewDetailModal('Travels Deductions', 'travelsDeductions')" class="group flex flex-col items-center gap-3 p-6 bg-gradient-to-br from-emerald-50 to-emerald-100 rounded-2xl border-2 border-emerald-200 hover:border-emerald-400 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                <div class="bg-emerald-500 group-hover:bg-emerald-600 rounded-xl p-3 transition-colors shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-emerald-800">Travels Deductions</span>
                                <span class="text-xs font-semibold text-emerald-600 bg-emerald-200 px-3 py-1 rounded-full">${formatNumber(travelsDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</span>
                            </button>

                            <!-- Prior Years Button -->
                            <button onclick="showViewDetailModal('Prior Years', 'priorYears')" class="group flex flex-col items-center gap-3 p-6 bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl border-2 border-orange-200 hover:border-orange-400 hover:shadow-lg transition-all duration-300 transform hover:-translate-y-1">
                                <div class="bg-orange-500 group-hover:bg-orange-600 rounded-xl p-3 transition-colors shadow-md">
                                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <span class="text-sm font-bold text-orange-800">Prior Years</span>
                                <span class="text-xs font-semibold text-orange-600 bg-orange-200 px-3 py-1 rounded-full">${formatNumber(totals.priorYearsTotal || 0)}</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Overall Total Budget Allocation Footer -->
                <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 rounded-b-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between text-white">
                        <div>
                            <p class="text-xl font-bold mb-1">Overall Total Budget Allocation</p>
                            <p class="text-sm text-red-100">Fiscal Year ${summary.fiscal_year}</p>
                        </div>
                        <p class="text-4xl font-bold">${formatNumber(totalBalance)}</p>
                    </div>
                </div>
            `;

            // Highlight category from URL param if present
            highlightCategoryFromUrl();

            } catch (err) {
                console.error('Error rendering summary:', err);
                summaryDisplay.innerHTML = '<div class="text-center py-8"><p class="text-red-500 font-bold">Error displaying summary:</p><p class="text-red-400 text-sm mt-2">' + (err.message || String(err)) + '</p></div>';
                throw err;
            }
        }


        function formatNumber(num) {
            if (num === undefined || num === null) num = 0;
            const number = parseFloat(num.toString().replace(/[₱,]/g, '')) || 0;
            return '₱' + number.toLocaleString('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Function to toggle history modal
        function toggleHistoryModal() {
            const modal = document.getElementById('historyModal');
            if (modal) {
                if (modal.classList.contains('hidden')) {
                    showHistory();
                } else {
                    modal.classList.add('hidden');
                }
            }
        }

        // Function to show utilization history
        function showHistory() {
            const departmentId = <?php echo $departmentId ? $departmentId : 'null'; ?>;

            if (!departmentId) {
                alert('Department/Office information not available.');
                return;
            }

            // Show loading state
            const modal = document.getElementById('historyModal');
            const historyBody = document.getElementById('historyBody');
            const historyDeptName = document.getElementById('historyDepartmentName');

            if (!modal || !historyBody) {
                console.error('History modal elements not found');
                return;
            }

            // Show modal
            modal.classList.remove('hidden');

            // Set department name in modal header
            const departmentName = '<?php echo htmlspecialchars($departmentName ?? "Department/Office"); ?>';
            if (historyDeptName) {
                const yearText = currentFiscalYear ? ` (${currentFiscalYear})` : ' (All Years)';
                historyDeptName.textContent = `History for: ${departmentName}${yearText}`;
            }

            // Show loading
            historyBody.innerHTML = `
                <div class="flex flex-col items-center justify-center py-12">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-gray-900"></div>
                    <span class="text-gray-600 mt-2">Loading history...</span>
                </div>
            `;

            // Load history from API with optional fiscal year filter
            let historyUrl = `../api/get_utilization_history.php?department_id=${departmentId}`;
            if (currentFiscalYear) {
                historyUrl += `&fiscal_year=${currentFiscalYear}`;
            }
            
            fetch(historyUrl)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.history && data.history.length > 0) {
                        // Filter to show only summaries (like allocation history shows allocations)
                        const summaries = data.history.filter(item => item.type === 'summary');

                        if (summaries.length > 0) {
                            historyBody.innerHTML = '';
                            summaries.forEach((summary) => {
                                // Format timestamp
                                const timestamp = new Date(summary.timestamp);
                                const formattedDate = timestamp.toLocaleDateString('en-US', {
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric',
                                    hour: 'numeric',
                                    minute: '2-digit'
                                });

                                const card = document.createElement('div');
                                card.className = 'border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow';

                                card.innerHTML = `
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <h3 class="text-lg font-bold text-gray-900">${summary.department_name || departmentName}</h3>
                                            <p class="text-sm text-gray-500">${formattedDate}</p>
                                            <p class="text-xs text-gray-400 mt-1">Fiscal Year: ${summary.fiscal_year || currentYear}</p>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            ${summary.id ? `
                                                <button 
                                                    onclick="viewUtilizationSummary(${summary.id})" 
                                                    class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors font-semibold text-sm flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View
                                                </button>
                                                <button 
                                                    onclick="downloadUtilizationPDF(${summary.id})" 
                                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm flex items-center gap-2"
                                                >
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                    </svg>
                                                    PDF
                                                </button>
                                            ` : ''}
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-500">Total Allocated</p>
                                            <p class="font-semibold text-gray-900">${formatNumber(summary.totalAllocated || 0)}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Total Deductions</p>
                                            <p class="font-semibold text-gray-900">${formatNumber(summary.totalDeductions || 0)}</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500">Total Balance</p>
                                            <p class="font-semibold ${(summary.totalBalance || 0) < 0 ? 'text-red-600' : 'text-maroon'} text-lg">${formatNumber(summary.totalBalance || 0)}</p>
                                        </div>
                                    </div>
                                `;
                                historyBody.appendChild(card);
                            });
                        } else {
                            historyBody.innerHTML = `
                                <div class="text-center py-12 text-gray-500 italic">
                                    No utilization summaries found for this department/office
                                </div>
                            `;
                        }
                    } else {
                        historyBody.innerHTML = `
                            <div class="text-center py-12 text-gray-500 italic">
                                No history found for this department/office
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading history:', error);
                    historyBody.innerHTML = `
                        <div class="text-center py-12 text-red-500">
                            Error loading history: ${error.message || 'Please try again.'}
                        </div>
                    `;
                });
        }

        // Close modal when clicking outside or pressing Escape
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('historyModal');
            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) {
                        toggleHistoryModal();
                    }
                });
            }

            // Close on Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const historyModal = document.getElementById('historyModal');
                    if (historyModal && !historyModal.classList.contains('hidden')) {
                        toggleHistoryModal();
                    }
                }
            });
        });

        // Function to view a utilization summary in a modal
        function viewUtilizationSummary(summaryId) {
            if (!summaryId) {
                alert('Invalid summary ID');
                return;
            }

            // Update current summary ID
            currentSummaryId = summaryId;

            // Close history modal
            toggleHistoryModal();

            // Show summary view modal
            const summaryModal = document.getElementById('summaryViewModal');
            const summaryContent = document.getElementById('summaryViewContent');
            const summaryDeptName = document.getElementById('summaryViewDepartmentName');

            if (!summaryModal || !summaryContent) return;

            // Show loading state
            summaryContent.innerHTML = '<div class="flex items-center justify-center h-64"><p class="text-gray-500">Loading summary...</p></div>';
            summaryModal.classList.remove('hidden');

            // Fetch and display summary
            fetch(`../api/get_utilization_summary.php?id=${summaryId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(summaryData => {
                    if (summaryData.success && summaryData.summary) {
                        displaySummaryInViewModal(summaryData.summary);
                    } else {
                        summaryContent.innerHTML = '<div class="flex items-center justify-center h-64"><p class="text-red-500">Error loading summary</p></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading summary:', error);
                    summaryContent.innerHTML = '<div class="flex items-center justify-center h-64"><p class="text-red-500">Error loading summary. Please try again.</p></div>';
                });
        }

        // Function to display summary in the view modal
        function displaySummaryInViewModal(summary) {
            const summaryContent = document.getElementById('summaryViewContent');
            const summaryDeptName = document.getElementById('summaryViewDepartmentName');

            if (!summaryContent) return;

            const utilizationEntries = JSON.parse(summary.utilization_entries || '[]');
            const prEntries = JSON.parse(summary.pr_entries || '[]');
            const travelsEntries = JSON.parse(summary.travels_entries || '[]');
            const honorariaEntries = JSON.parse(summary.honoraria_entries || '[]');

            // Parse deduction data with proper error handling
            let prDeductions = [];
            let travelsDeductions = [];
            let honorariaDeductions = [];

            try {
                if (summary.pr_deductions) {
                    if (typeof summary.pr_deductions === 'string') {
                        prDeductions = summary.pr_deductions.trim() === '' ? [] : JSON.parse(summary.pr_deductions);
                    } else {
                        prDeductions = Array.isArray(summary.pr_deductions) ? summary.pr_deductions : [];
                    }
                }
            } catch (e) {
                console.error('Error parsing pr_deductions:', e);
                prDeductions = [];
            }

            try {
                if (summary.travels_deductions) {
                    if (typeof summary.travels_deductions === 'string') {
                        travelsDeductions = summary.travels_deductions.trim() === '' ? [] : JSON.parse(summary.travels_deductions);
                    } else {
                        travelsDeductions = Array.isArray(summary.travels_deductions) ? summary.travels_deductions : [];
                    }
                }
            } catch (e) {
                console.error('Error parsing travels_deductions:', e);
                travelsDeductions = [];
            }

            try {
                if (summary.honoraria_deductions) {
                    if (typeof summary.honoraria_deductions === 'string') {
                        honorariaDeductions = summary.honoraria_deductions.trim() === '' ? [] : JSON.parse(summary.honoraria_deductions);
                    } else {
                        honorariaDeductions = Array.isArray(summary.honoraria_deductions) ? summary.honoraria_deductions : [];
                    }
                }
            } catch (e) {
                console.error('Error parsing honoraria_deductions:', e);
                honorariaDeductions = [];
            }

            const totals = JSON.parse(summary.totals || '{}');

            // Determine if it's a department or office
            const departmentNames = ['Computer studies', 'Education', 'Industrial Technology', 'Engineering', 'Hospitality Management'];
            let isDepartment = false;
            let isOffice = false;

            if (summary.department_name) {
                isDepartment = departmentNames.some(name =>
                    summary.department_name.toLowerCase().includes(name.toLowerCase())
                );
                isOffice = !isDepartment;
            }

            const label = isDepartment ? 'Department' : (isOffice ? 'Office' : 'Department/Office');
            const createdDate = new Date(summary.created_at);
            const formattedDate = createdDate.toLocaleDateString('en-US', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });

            // Set department name in header
            if (summaryDeptName) {
                summaryDeptName.textContent = `${label}: ${summary.department_name || 'Department/Office'}`;
            }

            // Use totalBalance from totals instead of calculating remainingBalance
            const totalBalance = totals.totalBalance || 0;
            const totalBalanceColor = totalBalance < 0 ? 'text-red-600' : 'text-maroon';

            // Build utilization table rows
            let utilizationRows = '';
            if (utilizationEntries.length === 0) {
                utilizationRows = '<tr><td colspan="5" class="py-6 px-6 text-center text-gray-500 italic">No budget utilization entries found</td></tr>';
            } else {
                utilizationEntries.forEach(entry => {
                    utilizationRows += `
                        <tr class="hover:bg-gray-50 transition-colors border-b border-gray-100">
                            <td class="py-4 px-6 font-semibold text-gray-800">${entry.category || '-'}</td>
                            <td class="py-4 px-6 text-gray-700">${entry.accountCode || entry.account_code || '-'}</td>
                            <td class="py-4 px-6 text-right text-gray-700 font-medium">${formatNumber(entry.allocated)}</td>
                            <td class="py-4 px-6 text-right text-red-600 font-medium">${formatNumber(entry.deduction)}</td>
                            <td class="py-4 px-6 text-right font-bold text-lg ${entry.balance < 0 ? 'text-red-600' : 'text-green-600'}">${formatNumber(entry.balance)}</td>
                        </tr>
                    `;
                });
            }

            // Build PR table rows
            let prRows = '';
            if (prEntries.length === 0) {
                prRows = '<tr><td colspan="5" class="py-6 px-4 text-center text-gray-500 italic">No purchase requests found</td></tr>';
            } else {
                prEntries.forEach(entry => {
                    const particulars = entry.particulars || '-';
                    prRows += `
                        <tr class="hover:bg-blue-50 transition-colors">
                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.purchaseRequest || entry.purchase_request || '-'}</td>
                            <td class="py-3 px-4 text-gray-700">${particulars}</td>
                            <td class="py-3 px-4 text-gray-700">${entry.prNumber || entry.pr_number || entry.pr_no || '-'}</td>
                            <td class="py-3 px-4 text-gray-700">${entry.date || '-'}</td>
                            <td class="py-3 px-4 text-right text-blue-600 font-semibold">${formatNumber(entry.amount)}</td>
                        </tr>
                    `;
                });
            }

            // Build travels table rows
            let travelsRows = '';
            if (travelsEntries.length === 0) {
                travelsRows = '<tr><td colspan="4" class="py-6 px-4 text-center text-gray-500 italic">No travels found</td></tr>';
            } else {
                travelsEntries.forEach(entry => {
                    const event = entry.event_activity || entry.event || '-';
                    travelsRows += `
                        <tr class="hover:bg-green-50 transition-colors">
                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.travelled || '-'}</td>
                            <td class="py-3 px-4 text-gray-700">${event}</td>
                            <td class="py-3 px-4 text-gray-700">${entry.date || '-'}</td>
                            <td class="py-3 px-4 text-right text-green-600 font-semibold">${formatNumber(entry.amount)}</td>
                        </tr>
                    `;
                });
            }

            // Build Honoraria table rows
            let honorariaRows = '';
            if (honorariaEntries.length === 0) {
                honorariaRows = '<tr><td colspan="2" class="py-6 px-4 text-center text-gray-500 italic">No Honoraria entries found</td></tr>';
            } else {
                honorariaEntries.forEach(entry => {
                    honorariaRows += `
                        <tr class="hover:bg-purple-50 transition-colors">
                            <td class="py-3 px-4 text-gray-700 font-medium">${entry.date || '-'}</td>
                            <td class="py-3 px-4 text-right text-purple-600 font-semibold">${formatNumber(entry.amount)}</td>
                        </tr>
                    `;
                });
            }

            summaryContent.innerHTML = `
                <!-- Summary Header -->
                <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 rounded-2xl p-6 mb-8 text-white">
                    <div class="flex items-center gap-4 mb-4">
                        <div class="bg-white bg-opacity-20 backdrop-blur-sm rounded-xl p-3">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-3xl font-bold mb-1">Budget Utilization Report</h2>
                            <p class="text-red-100 text-sm">${formattedDate}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-4 border border-white border-opacity-20">
                            <p class="text-xs text-red-100 mb-1">${label}</p>
                            <p class="text-lg font-bold">${summary.department_name || 'Department/Office'}</p>
                        </div>
                        <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-4 border border-white border-opacity-20">
                            <p class="text-xs text-red-100 mb-1">Fiscal Year</p>
                            <p class="text-lg font-bold">${summary.fiscal_year}</p>
                        </div>
                        <div class="bg-white bg-opacity-10 backdrop-blur-sm rounded-lg p-4 border border-white border-opacity-20">
                            <p class="text-xs text-red-100 mb-1">Total Balance</p>
                            <p class="text-xl font-bold">${formatNumber(totalBalance)}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Purchase Requests Breakdown -->
                <div class="mb-8 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-50 to-blue-100 px-6 py-4 border-b border-blue-200">
                        <div class="flex items-center gap-3">
                            <div class="bg-blue-500 rounded-lg p-2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Purchase Requests</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm" style="min-width: 800px;">
                                <thead>
                                    <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-b-2 border-blue-200">
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 150px;">Purchase Request</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 200px;">Particulars</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 200px;">PR No. / PO No.</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 100px;">Date</th>
                                        <th class="text-right py-3 px-4 font-bold text-gray-800" style="min-width: 120px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${prRows}
                                </tbody>
                                <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="4">Total</td>
                                        <td class="text-right py-4 px-4 text-blue-700 text-lg">${formatNumber(totals.prTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Travels Breakdown -->
                <div class="mb-8 bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <div class="bg-gradient-to-r from-green-50 to-green-100 px-6 py-4 border-b border-green-200">
                        <div class="flex items-center gap-3">
                            <div class="bg-green-500 rounded-lg p-2">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800">Travels</h3>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="w-full text-sm" style="min-width: 700px;">
                                <thead>
                                    <tr class="bg-gradient-to-r from-green-50 to-green-100 border-b-2 border-green-200">
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 150px;">Travelled</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 250px;">Event/Activity</th>
                                        <th class="text-left py-3 px-4 font-bold text-gray-800" style="min-width: 100px;">Date</th>
                                        <th class="text-right py-3 px-4 font-bold text-gray-800" style="min-width: 120px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${travelsRows}
                                </tbody>
                                <tfoot class="bg-green-50 border-t-2 border-green-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="3">Total</td>
                                        <td class="text-right py-4 px-4 text-green-700 text-lg">${formatNumber(totals.travelsTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                
                <!-- Purchase Request Deductions -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Purchase Request Deductions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Expense Category</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Purchase Request</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${prDeductions.length > 0 ? prDeductions.map(entry => {
                                    let rows = '';
                                    if (entry.items && entry.items.length > 0) {
                                        entry.items.forEach((item, index) => {
                                            if (index === 0) {
                                                rows += `<tr class="border-b border-gray-200">
                                                    <td class="py-2 px-3 text-gray-900 font-semibold align-top" rowspan="${entry.items.length}">${entry.category || '-'}</td>
                                                    <td class="py-2 px-3 text-gray-700">${item.purchaseRequest || '-'}</td>
                                                    <td class="py-2 px-3 text-right text-blue-600 align-top font-semibold" rowspan="${entry.items.length}">${formatNumber(entry.amount || 0)}</td>
                                                </tr>`;
                                            } else {
                                                rows += `<tr class="border-b border-gray-200">
                                                    <td class="py-2 px-3 text-gray-700">${item.purchaseRequest || '-'}</td>
                                                </tr>`;
                                            }
                                        });
                                    } else {
                                        rows = `<tr class="border-b border-gray-200">
                                            <td class="py-2 px-3 text-gray-900">${entry.category || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700">-</td>
                                            <td class="py-2 px-3 text-right text-blue-600">${formatNumber(entry.amount || 0)}</td>
                                        </tr>`;
                                    }
                                    return rows;
                                }).join('') : '<tr><td colspan="3" class="py-2 px-3 text-center text-gray-500 italic">No purchase request deductions found</td></tr>'}
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3" colspan="2">Total</td>
                                    <td class="text-right py-2 px-3 text-blue-600">${formatNumber(prDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Travels Deductions -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Travels Deductions</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Expense Category</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Travelled</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Event/Activity</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Date</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${travelsDeductions.length > 0 ? travelsDeductions.flatMap(entry => {
                                    const items = entry.items && entry.items.length > 0 ? entry.items : [{ travelled: '-', event: '-', date: '-', amount: entry.amount }];
                                    return items.map((item, idx) => idx === 0
                                        ? `<tr class="border-b border-gray-200">
                                            <td class="py-2 px-3 text-gray-900 font-semibold align-top" rowspan="${items.length}">${entry.category || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700">${item.travelled || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700">${item.event || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700">${item.date || '-'}</td>
                                            <td class="py-2 px-3 text-right text-green-600">${formatNumber(item.amount || 0)}</td>
                                           </tr>`
                                        : `<tr class="border-b border-gray-200">
                                            <td class="py-2 px-3 text-gray-700">${item.travelled || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700">${item.event || '-'}</td>
                                            <td class="py-2 px-3 text-gray-700">${item.date || '-'}</td>
                                            <td class="py-2 px-3 text-right text-green-600">${formatNumber(item.amount || 0)}</td>
                                           </tr>`
                                    );
                                }).join('') : '<tr><td colspan="5" class="py-2 px-3 text-center text-gray-500 italic">No travels deductions found</td></tr>'}
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3" colspan="4">Total</td>
                                    <td class="text-right py-2 px-3 text-green-600">${formatNumber(travelsDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Budget Utilization Breakdown -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Budget Utilization Breakdown</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Expense Category</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Account Code</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Allocated Budget</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Deductions</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Total Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${utilizationRows}
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3">Total</td>
                                    <td class="py-2 px-3"></td>
                                    <td class="text-right py-2 px-3">${formatNumber(totals.totalAllocated || 0)}</td>
                                    <td class="text-right py-2 px-3">${formatNumber(totals.totalDeductions || 0)}</td>
                                    <td class="text-right py-2 px-3 ${totalBalanceColor}">${formatNumber(totals.totalBalance || 0)}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                
                <!-- Overall Total Summary -->
                <div class="mt-6 pt-6 border-t-2 border-maroon">
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold text-maroon">Overall Total</span>
                        <span class="text-xl font-bold ${totalBalanceColor}">${formatNumber(totalBalance)}</span>
                    </div>
                </div>
            `;
        }

        // Function to close summary view modal
        function closeSummaryViewModal() {
            const modal = document.getElementById('summaryViewModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function () {
            const summaryModal = document.getElementById('summaryViewModal');
            if (summaryModal) {
                summaryModal.addEventListener('click', function (e) {
                    if (e.target === summaryModal) {
                        closeSummaryViewModal();
                    }
                });
            }
        });

        // Function to download utilization summary PDF
        function downloadUtilizationPDF(summaryId) {
            if (!summaryId) {
                alert('No summary available to download.');
                return;
            }

            // Fetch the summary data to build a proper print version
            fetch(`../api/get_utilization_summary.php?id=${summaryId}`)
                .then(response => response.json())
                .then(summaryData => {
                    if (!summaryData.success || !summaryData.summary) {
                        alert('Error loading summary data.');
                        return;
                    }

                    const summary = summaryData.summary;
                    const utilizationEntries = JSON.parse(summary.utilization_entries || '[]');
                    const prEntries = JSON.parse(summary.pr_entries || '[]');
                    const travelsEntries = JSON.parse(summary.travels_entries || '[]');
                    const honorariaEntries = JSON.parse(summary.honoraria_entries || '[]');

                    // Parse deduction data with proper error handling
                    let prDeductions = [];
                    let travelsDeductions = [];
                    let honorariaDeductions = [];

                    try {
                        if (summary.pr_deductions) {
                            if (typeof summary.pr_deductions === 'string') {
                                prDeductions = summary.pr_deductions.trim() === '' ? [] : JSON.parse(summary.pr_deductions);
                            } else {
                                prDeductions = Array.isArray(summary.pr_deductions) ? summary.pr_deductions : [];
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing pr_deductions:', e);
                        prDeductions = [];
                    }

                    try {
                        if (summary.travels_deductions) {
                            if (typeof summary.travels_deductions === 'string') {
                                travelsDeductions = summary.travels_deductions.trim() === '' ? [] : JSON.parse(summary.travels_deductions);
                            } else {
                                travelsDeductions = Array.isArray(summary.travels_deductions) ? summary.travels_deductions : [];
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing travels_deductions:', e);
                        travelsDeductions = [];
                    }

                    try {
                        if (summary.honoraria_deductions) {
                            if (typeof summary.honoraria_deductions === 'string') {
                                honorariaDeductions = summary.honoraria_deductions.trim() === '' ? [] : JSON.parse(summary.honoraria_deductions);
                            } else {
                                honorariaDeductions = Array.isArray(summary.honoraria_deductions) ? summary.honoraria_deductions : [];
                            }
                        }
                    } catch (e) {
                        console.error('Error parsing honoraria_deductions:', e);
                        honorariaDeductions = [];
                    }

                    const totals = JSON.parse(summary.totals || '{}');

                    // Determine if it's a department or office
                    const departmentNames = ['Computer studies', 'Education', 'Industrial Technology', 'Engineering', 'Hospitality Management'];
                    let isDepartment = false;
                    let isOffice = false;

                    if (summary.department_name) {
                        isDepartment = departmentNames.some(name =>
                            summary.department_name.toLowerCase().includes(name.toLowerCase())
                        );
                        isOffice = !isDepartment;
                    }

                    const label = isDepartment ? 'Department' : (isOffice ? 'Office' : 'Department/Office');
                    const createdDate = new Date(summary.created_at);
                    const formattedDate = createdDate.toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });

                    // Use totalBalance from totals instead of calculating remainingBalance
                    const totalBalance = totals.totalBalance || 0;

                    // Build utilization table rows
                    let utilizationRows = '';
                    if (utilizationEntries.length === 0) {
                        utilizationRows = '<tr><td colspan="5" style="text-align: center; padding: 16px; font-style: italic;">No budget utilization entries found</td></tr>';
                    } else {
                        utilizationEntries.forEach(entry => {
                            const balanceClass = entry.balance < 0 ? 'negative' : '';
                            utilizationRows += `
                                <tr>
                                    <td><strong>${entry.category || '-'}</strong></td>
                                    <td>${entry.accountCode || entry.account_code || '-'}</td>
                                    <td class="text-right">₱${formatNumber(entry.allocated)}</td>
                                    <td class="text-right ${entry.deduction < 0 ? 'negative' : ''}">₱${formatNumber(entry.deduction)}</td>
                                    <td class="text-right ${balanceClass}"><strong>₱${formatNumber(entry.balance)}</strong></td>
                                </tr>
                            `;
                        });
                    }

                    // Build PR table rows
                    let prRows = '';
                    if (prEntries.length === 0) {
                        prRows = '<tr><td colspan="6" style="text-align: center; padding: 16px; font-style: italic;">No purchase requests found</td></tr>';
                    } else {
                        prEntries.forEach(entry => {
                            const particulars = entry.particulars || '-';
                            prRows += `
                                <tr>
                                    <td>${entry.purchaseRequest || entry.purchase_request || '-'}</td>
                                    <td>${particulars}</td>
                                    <td>${entry.prNumber || entry.pr_number || entry.pr_no || '-'}</td>
                                    <td>${entry.date || '-'}</td>
                                    <td class="text-right">₱${formatNumber(entry.amount)}</td>
                                </tr>
                            `;
                        });
                    }

                    // Build travels table rows
                    let travelsRows = '';
                    if (travelsEntries.length === 0) {
                        travelsRows = '<tr><td colspan="4" style="text-align: center; padding: 16px; font-style: italic;">No travels found</td></tr>';
                    } else {
                        travelsEntries.forEach(entry => {
                            const event = entry.event_activity || entry.event || '-';
                            travelsRows += `
                                <tr>
                                    <td>${entry.travelled || '-'}</td>
                                    <td>${event}</td>
                                    <td>${entry.date || '-'}</td>
                                    <td class="text-right">₱${formatNumber(entry.amount)}</td>
                                </tr>
                            `;
                        });
                    }


                    // Create a new window with the summary content
                    const printWindow = window.open('', '_blank');

                    const generatedDate = new Date().toLocaleString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    const createdDateFormatted = new Date(summary.created_at).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    });

                    printWindow.document.write(`
                        <!DOCTYPE html>
                        <html>
                        <head>
                            <meta charset="UTF-8">
                            <title>Budget Utilization Report - ${summary.department_name || 'Department/Office'}</title>
                            <style>
                                @page {
                                    size: landscape;
                                    margin: 1cm;
                                }
                                * {
                                    margin: 0;
                                    padding: 0;
                                    box-sizing: border-box;
                                }
                                body { 
                                    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
                                    padding: 15px;
                                    font-size: 10px;
                                    color: #333;
                                }
                                .header {
                                    border-bottom: 4px solid #800000;
                                    padding-bottom: 15px;
                                    margin-bottom: 20px;
                                }
                                .header h1 { 
                                    color: #800000; 
                                    font-size: 24px;
                                    margin-bottom: 5px;
                                }
                                .header-info {
                                    display: flex;
                                    justify-content: space-between;
                                    margin-top: 10px;
                                    font-size: 9px;
                                    color: #666;
                                }
                                h2 { 
                                    color: #800000; 
                                    margin-top: 20px;
                                    margin-bottom: 10px;
                                    font-size: 14px;
                                    border-bottom: 2px solid #800000;
                                    padding-bottom: 5px;
                                }
                                table { 
                                    width: 100%; 
                                    border-collapse: collapse; 
                                    margin: 10px 0;
                                    font-size: 9px;
                                }
                                th, td { 
                                    border: 1px solid #ddd; 
                                    padding: 6px 8px; 
                                    text-align: left;
                                }
                                th { 
                                    background: linear-gradient(to bottom, #800000, #a00000);
                                    color: white; 
                                    font-weight: bold;
                                    font-size: 9px;
                                    text-transform: uppercase;
                                }
                                tr:nth-child(even) {
                                    background-color: #f9f9f9;
                                }
                                .text-right { text-align: right; }
                                .summary { 
                                    background: linear-gradient(to bottom, #f8f8f8, #f0f0f0);
                                    padding: 12px; 
                                    border: 2px solid #800000;
                                    border-radius: 5px; 
                                    margin: 15px 0;
                                }
                                .summary h2 {
                                    margin-top: 0;
                                }
                                .total { 
                                    font-size: 12px; 
                                    font-weight: bold; 
                                    color: #800000; 
                                }
                                .negative { 
                                    color: #d32f2f; 
                                    font-weight: bold; 
                                }
                                .footer {
                                    margin-top: 20px;
                                    padding-top: 10px;
                                    border-top: 2px solid #ddd;
                                    text-align: center;
                                    font-size: 8px;
                                    color: #666;
                                }
                                .section-title {
                                    background-color: #800000;
                                    color: white;
                                    padding: 8px;
                                    font-weight: bold;
                                    margin-top: 15px;
                                }
                            </style>
                        </head>
                        <body>
                            <div class="header">
                                <h1>Budget Utilization Report</h1>
                                <div class="header-info">
                                    <div>
                                        <strong>${label}:</strong> ${summary.department_name || 'Department/Office'}<br>
                                        <strong>Fiscal Year:</strong> ${summary.fiscal_year}
                                    </div>
                                    <div>
                                        <strong>Generated:</strong> ${generatedDate}<br>
                                        <strong>Created:</strong> ${createdDateFormatted}
                                    </div>
                                </div>
                            </div>
                            
                            <h2>Purchase Requests</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Purchase Request</th>
                                        <th>Particulars</th>
                                        <th>PR No. / PO No.</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${prRows}
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                                        <td colspan="4"><strong>Total</strong></td>
                                        <td class="text-right total">₱${formatNumber(totals.prTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <h2>Travels</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Travelled</th>
                                        <th>Event/Activity</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${travelsRows}
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                                        <td colspan="3"><strong>Total</strong></td>
                                        <td class="text-right total">₱${formatNumber(totals.travelsTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <h2>Purchase Request Deductions</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Expense Category</th>
                                        <th>Purchase Request</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${prDeductions.length > 0 ? prDeductions.map(entry => {
                                        let rows = '';
                                        if (entry.items && entry.items.length > 0) {
                                            entry.items.forEach((item, index) => {
                                                if (index === 0) {
                                                    rows += `<tr>
                                                        <td rowspan="${entry.items.length}" style="font-weight: bold; vertical-align: top;">${entry.category || '-'}</td>
                                                        <td>${item.purchaseRequest || '-'}</td>
                                                        <td class="text-right" rowspan="${entry.items.length}" style="font-weight: bold; vertical-align: top;">₱${formatNumber(entry.amount || 0)}</td>
                                                    </tr>`;
                                                } else {
                                                    rows += `<tr><td>${item.purchaseRequest || '-'}</td></tr>`;
                                                }
                                            });
                                        } else {
                                            rows = `<tr>
                                                <td>${entry.category || '-'}</td>
                                                <td>-</td>
                                                <td class="text-right">₱${formatNumber(entry.amount || 0)}</td>
                                            </tr>`;
                                        }
                                        return rows;
                                    }).join('') : '<tr><td colspan="3" style="text-align: center; padding: 16px; font-style: italic;">No purchase request deductions found</td></tr>'}
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                                        <td colspan="2"><strong>Total</strong></td>
                                        <td class="text-right total">₱${formatNumber(prDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <h2>Travels Deductions</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Expense Category</th>
                                        <th>Travelled</th>
                                        <th>Event/Activity</th>
                                        <th>Date</th>
                                        <th class="text-right">Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${travelsDeductions.length > 0 ? travelsDeductions.flatMap(entry => {
                                        const items = entry.items && entry.items.length > 0 ? entry.items : [{ travelled: '-', event: '-', date: '-', amount: entry.amount }];
                                        return items.map((item, idx) => idx === 0
                                            ? `<tr><td rowspan="${items.length}">${entry.category || '-'}</td><td>${item.travelled || '-'}</td><td>${item.event || '-'}</td><td>${item.date || '-'}</td><td class="text-right">₱${formatNumber(item.amount || 0)}</td></tr>`
                                            : `<tr><td>${item.travelled || '-'}</td><td>${item.event || '-'}</td><td>${item.date || '-'}</td><td class="text-right">₱${formatNumber(item.amount || 0)}</td></tr>`
                                        );
                                    }).join('') : '<tr><td colspan="5" style="text-align: center; padding: 16px; font-style: italic;">No travels deductions found</td></tr>'}
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                                        <td colspan="4"><strong>Total</strong></td>
                                        <td class="text-right total">₱${formatNumber(travelsDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <h2>Budget Utilization Breakdown</h2>
                            <table>
                                <thead>
                                    <tr>
                                        <th>Expense Category</th>
                                        <th>Account Code</th>
                                        <th class="text-right">Allocated Budget</th>
                                        <th class="text-right">Deductions</th>
                                        <th class="text-right">Total Balance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${utilizationRows}
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f0f0f0; font-weight: bold;">
                                        <td><strong>Total</strong></td>
                                        <td></td>
                                        <td class="text-right total">₱${formatNumber(totals.totalAllocated || 0)}</td>
                                        <td class="text-right total ${totals.totalDeductions < 0 ? 'negative' : ''}">₱${formatNumber(totals.totalDeductions || 0)}</td>
                                        <td class="text-right total ${totals.totalBalance < 0 ? 'negative' : ''}">₱${formatNumber(totals.totalBalance || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                            
                            <div class="summary" style="margin-top: 20px;">
                                <h2 style="margin-top: 0;">Overall Summary</h2>
                                <p style="font-size: 16px; font-weight: bold; color: #800000; text-align: center; padding: 10px;">
                                    Overall Total Balance: ₱${formatNumber(totalBalance)}
                                </p>
                            </div>
                            
                            <div class="footer">
                                <p>This document was generated on ${generatedDate} | Budget Utilization System</p>
                            </div>
                        
</body>
                        </html>
                    `);

                    printWindow.document.close();

                    // Wait for content to load, then trigger print
                    setTimeout(() => {
                        // Set up print settings for landscape
                        const style = printWindow.document.createElement("style");
                        style.textContent = "@media print { @page { size: landscape; margin: 1cm; } }";
                        printWindow.document.head.appendChild(style);

                        // Trigger print dialog
                        printWindow.print();
                    }, 250);
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading summary data. Please try again.');
                });
        }

        // Function to download current utilization summary (for the download button)
        function downloadUtilizationSummary() {
            if (!currentSummaryId) {
                alert('No summary available to download.');
                return;
            }
            downloadUtilizationPDF(currentSummaryId);
        }

        // Function to switch utilization tabs
        function switchUtilizationTab(tabName) {
            // Hide all panels
            document.querySelectorAll('.util-tab-panel').forEach(panel => {
                panel.classList.add('hidden');
            });

            // Show selected panel
            const selectedPanel = document.getElementById('utilPanel-' + tabName);
            if (selectedPanel) {
                selectedPanel.classList.remove('hidden');
            }

            // Update tab button styles
            document.querySelectorAll('.util-tab-btn').forEach(btn => {
                btn.classList.remove('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
                btn.classList.add('border-transparent', 'text-gray-500', 'font-medium');
            });

            // Highlight selected tab
            const selectedTab = document.getElementById('utilTab-' + tabName);
            if (selectedTab) {
                selectedTab.classList.remove('border-transparent', 'text-gray-500', 'font-medium');
                selectedTab.classList.add('border-maroon', 'text-maroon', 'font-semibold', 'bg-maroon', 'bg-opacity-5');
            }

            // Save current tab to localStorage
            localStorage.setItem('activeUtilizationTab', tabName);
        }

        // Function to refresh sub-departments while preserving tab state
        function refreshSubDepartments() {
            // Save that we want to stay on sub-departments tab
            localStorage.setItem('activeUtilizationTab', 'subDepartments');
            // Reload the page
            location.reload();
        }

        // Restore active tab on page load
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (!empty($childDepartments)): ?>
            // Only use tab system if there are child departments
            const savedTab = localStorage.getItem('activeUtilizationTab');
            if (savedTab && savedTab === 'subDepartments') {
                // Switch to the saved tab
                switchUtilizationTab(savedTab);
            }
            <?php else: ?>
            // No child departments - clear any saved tab preference and ensure My Utilization is visible
            localStorage.removeItem('activeUtilizationTab');
            const myUtilPanel = document.getElementById('utilPanel-myUtilization');
            if (myUtilPanel) {
                myUtilPanel.classList.remove('hidden');
            }
            <?php endif; ?>
        });

        // Function to view child department utilization summary
        function viewChildUtilization(summaryId) {
            if (!summaryId) {
                alert('Invalid summary ID');
                return;
            }

            // Use the existing viewUtilizationSummary function
            viewUtilizationSummary(summaryId);
        }

        // Function to download child department utilization PDF
        function downloadChildUtilizationPDF(summaryId) {
            if (!summaryId) {
                alert('No summary available to download.');
                return;
            }
            downloadUtilizationPDF(summaryId);
        }

        // Function to show child department utilization history
        function showChildUtilizationHistory(departmentId, departmentName) {
            // Show the history modal
            const modal = document.getElementById('historyModal');
            if (modal) {
                modal.classList.remove('hidden');
            }

            // Update modal title
            const deptNameEl = document.getElementById('historyDepartmentName');
            if (deptNameEl) {
                deptNameEl.textContent = departmentName;
            }

            // Load history for the child department
            const historyBody = document.getElementById('historyBody');
            if (historyBody) {
                historyBody.innerHTML = '<div class="text-center py-12"><div class="animate-spin rounded-full h-10 w-10 border-b-2 border-maroon mx-auto"></div><p class="text-gray-500 mt-4 font-medium">Loading history...</p></div>';
            }

            // Load history from API (all fiscal years)
            fetch(`../api/get_utilization_history.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(data => {
                    // Filter for summary entries only
                    const summaryEntries = (data.history || []).filter(entry => entry.type === 'summary');

                    if (data.success && summaryEntries.length > 0) {
                        let html = '';
                        summaryEntries.forEach((entry, index) => {
                            const date = new Date(entry.timestamp || entry.created_at);
                            const formattedDate = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
                            const formattedTime = date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
                            const totalBalance = entry.totalBalance || 0;

                            html += `
                                <div class="bg-gradient-to-br from-white to-gray-50 rounded-2xl p-6 border border-gray-200 shadow-sm hover:shadow-md transition-all ${index > 0 ? 'mt-4' : ''}">
                                    <div class="flex flex-wrap items-start justify-between gap-4 mb-4">
                                        <div class="flex items-center gap-4">
                                            <div class="bg-maroon bg-opacity-10 rounded-xl p-3">
                                                <svg class="w-6 h-6 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                            </div>
                                            <div>
                                                <h4 class="font-bold text-gray-900 text-lg">${departmentName}</h4>
                                                <p class="text-gray-500 text-sm">Fiscal Year ${entry.fiscal_year}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            <p class="text-2xl font-bold ${totalBalance < 0 ? 'text-red-600' : 'text-green-600'}">₱${parseFloat(totalBalance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</p>
                                            <p class="text-xs text-gray-500">Total Balance</p>
                                        </div>
                                    </div>
                                    <div class="flex flex-wrap items-center justify-between gap-4 pt-4 border-t border-gray-200">
                                        <div class="flex items-center gap-2 text-sm text-gray-500">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>${formattedDate} at ${formattedTime}</span>
                                        </div>
                                        <div class="flex gap-2">
                                            <button onclick="viewChildUtilization(${entry.id})" class="px-4 py-2 bg-maroon text-white rounded-lg text-sm font-medium hover:bg-maroon-dark transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </button>
                                            <button onclick="downloadChildUtilizationPDF(${entry.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors flex items-center gap-2">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                Download
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        historyBody.innerHTML = html;
                    } else {
                        historyBody.innerHTML = `
                            <div class="text-center py-16">
                                <div class="bg-gray-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">No History Found</h3>
                                <p class="text-gray-500">No utilization history available for this department.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading history:', error);
                    historyBody.innerHTML = `
                        <div class="text-center py-16">
                            <div class="bg-red-100 rounded-full w-16 h-16 flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-red-800 mb-2">Error Loading History</h3>
                            <p class="text-red-600">Please try again later.</p>
                        </div>
                    `;
                });
        }

        // Load summaries on page load
        document.addEventListener('DOMContentLoaded', function () {
            loadSavedSummaries();
        });
        // ==========================================
        // VIEW DETAIL MODAL FUNCTIONS
        // ==========================================
        function showViewDetailModal(title, type) {
            const cache = window._viewSummaryCache;
            if (!cache) {
                alert('No summary data available. Please load a summary first.');
                return;
            }

            let modalBody = '';
            let headerColor = 'from-gray-600 to-gray-700';

            const colorMap = {
                pr: { header: 'from-blue-600 to-blue-700', text: 'text-blue-100' },
                travels: { header: 'from-green-600 to-green-700', text: 'text-green-100' },
                honoraria: { header: 'from-purple-600 to-purple-700', text: 'text-purple-100' },
                priorYears: { header: 'from-orange-500 to-orange-600', text: 'text-orange-100' },
                prDeductions: { header: 'from-indigo-600 to-indigo-700', text: 'text-indigo-100' },
                travelsDeductions: { header: 'from-emerald-600 to-emerald-700', text: 'text-emerald-100' },
                honorariaDeductions: { header: 'from-fuchsia-600 to-fuchsia-700', text: 'text-fuchsia-100' }
            };

            const colors = colorMap[type] || { header: 'from-gray-600 to-gray-700', text: 'text-gray-100' };
            headerColor = colors.header;

            switch (type) {
                case 'pr':
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm" style="min-width: 800px;">
                                <thead>
                                    <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-b-2 border-blue-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 150px;">Purchase Request</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 200px;">Particulars</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 200px;">PR No. / PO No.</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 100px;">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800" style="min-width: 120px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${cache.prRows}
                                </tbody>
                                <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="4">Total</td>
                                        <td class="text-right py-4 px-4 text-blue-700 text-lg">${formatNumber(cache.totals.prTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'travels':
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm" style="min-width: 700px;">
                                <thead>
                                    <tr class="bg-gradient-to-r from-green-50 to-green-100 border-b-2 border-green-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 150px;">Travelled</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 250px;">Event/Activity</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width: 100px;">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800" style="min-width: 120px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${cache.travelsRows}
                                </tbody>
                                <tfoot class="bg-green-50 border-t-2 border-green-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="3">Total</td>
                                        <td class="text-right py-4 px-4 text-green-700 text-lg">${formatNumber(cache.totals.travelsTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'honoraria':
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-purple-50 to-purple-100 border-b-2 border-purple-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${cache.honorariaRows}
                                </tbody>
                                <tfoot class="bg-purple-50 border-t-2 border-purple-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4">Total</td>
                                        <td class="text-right py-4 px-4 text-purple-700 text-lg">${formatNumber(cache.totals.honorariaTotal || 0)}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'priorYears':
                    modalBody = `<div id="priorYearsViewContent" class="text-center py-8"><p class="text-gray-500">Loading prior years history...</p></div>`;
                    // Load after modal is shown
                    setTimeout(() => loadPriorYearsForView(), 100);
                    break;

                case 'prDeductions':
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-indigo-50 to-indigo-100 border-b-2 border-indigo-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Expense Category</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Purchase Request</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${cache.prDeductions.length > 0 ? cache.prDeductions.map(entry => {
                                        let rows = '';
                                        if (entry.items && entry.items.length > 0) {
                                            entry.items.forEach((item, index) => {
                                                if (index === 0) {
                                                    rows += `<tr class="hover:bg-indigo-50 transition-colors">
                                                        <td class="py-3 px-4 text-gray-900 font-semibold align-top" rowspan="${entry.items.length}">${entry.category || '-'}</td>
                                                        <td class="py-3 px-4 text-gray-700">${item.purchaseRequest || '-'}</td>
                                                        <td class="py-3 px-4 text-right text-indigo-600 font-semibold align-top" rowspan="${entry.items.length}">${formatNumber(entry.amount || 0)}</td>
                                                    </tr>`;
                                                } else {
                                                    rows += `<tr class="hover:bg-indigo-50 transition-colors">
                                                        <td class="py-3 px-4 text-gray-700">${item.purchaseRequest || '-'}</td>
                                                    </tr>`;
                                                }
                                            });
                                        } else {
                                            rows = `<tr class="hover:bg-indigo-50 transition-colors">
                                                <td class="py-3 px-4 text-gray-900 font-medium">${entry.category || '-'}</td>
                                                <td class="py-3 px-4 text-gray-700">-</td>
                                                <td class="py-3 px-4 text-right text-indigo-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                                            </tr>`;
                                        }
                                        return rows;
                                    }).join('') : '<tr><td colspan="3" class="py-6 px-4 text-center text-gray-500 italic">No purchase request deductions found</td></tr>'}
                                </tbody>
                                <tfoot class="bg-indigo-50 border-t-2 border-indigo-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="2">Total</td>
                                        <td class="text-right py-4 px-4 text-indigo-700 text-lg">${formatNumber(cache.prDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'travelsDeductions':
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm" style="min-width:700px;">
                                <thead>
                                    <tr class="bg-gradient-to-r from-emerald-50 to-emerald-100 border-b-2 border-emerald-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width:140px;">Expense Category</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width:140px;">Travelled</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width:200px;">Event/Activity</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800" style="min-width:100px;">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800" style="min-width:110px;">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${cache.travelsDeductions.length > 0 ? cache.travelsDeductions.flatMap(entry => {
                                        const items = entry.items && entry.items.length > 0 ? entry.items : [{ travelled: '-', event: '-', date: '-', amount: entry.amount }];
                                        return items.map((item, idx) => idx === 0
                                            ? `<tr class="hover:bg-emerald-50 transition-colors">
                                                <td class="py-3 px-4 text-gray-900 font-semibold align-top" rowspan="${items.length}">${entry.category || '-'}</td>
                                                <td class="py-3 px-4 text-gray-700">${item.travelled || '-'}</td>
                                                <td class="py-3 px-4 text-gray-700">${item.event || '-'}</td>
                                                <td class="py-3 px-4 text-gray-700">${item.date || '-'}</td>
                                                <td class="py-3 px-4 text-right text-emerald-600 font-semibold">${formatNumber(item.amount || 0)}</td>
                                               </tr>`
                                            : `<tr class="hover:bg-emerald-50 transition-colors">
                                                <td class="py-3 px-4 text-gray-700">${item.travelled || '-'}</td>
                                                <td class="py-3 px-4 text-gray-700">${item.event || '-'}</td>
                                                <td class="py-3 px-4 text-gray-700">${item.date || '-'}</td>
                                                <td class="py-3 px-4 text-right text-emerald-600 font-semibold">${formatNumber(item.amount || 0)}</td>
                                               </tr>`
                                        );
                                    }).join('') : '<tr><td colspan="5" class="py-6 px-4 text-center text-gray-500 italic">No travels deductions found</td></tr>'}
                                </tbody>
                                <tfoot class="bg-emerald-50 border-t-2 border-emerald-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="4">Total</td>
                                        <td class="text-right py-4 px-4 text-emerald-700 text-lg">${formatNumber(cache.travelsDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'honorariaDeductions':
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-fuchsia-50 to-fuchsia-100 border-b-2 border-fuchsia-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Expense Category</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${cache.honorariaDeductions.length > 0 ? cache.honorariaDeductions.map(entry => `
                                        <tr class="hover:bg-fuchsia-50 transition-colors">
                                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.category || '-'}</td>
                                            <td class="py-3 px-4 text-right text-fuchsia-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="2" class="py-6 px-4 text-center text-gray-500 italic">No honoraria deductions found</td></tr>'}
                                </tbody>
                                <tfoot class="bg-fuchsia-50 border-t-2 border-fuchsia-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4">Total</td>
                                        <td class="text-right py-4 px-4 text-fuchsia-700 text-lg">${formatNumber(cache.honorariaDeductions.reduce((sum, e) => sum + (e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;
            }

            // Create and show the modal
            let modal = document.getElementById('viewDetailModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'viewDetailModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4';
                modal.onclick = function (e) { if (e.target === modal) closeViewDetailModal(); };
                document.body.appendChild(modal);
            }

            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col transform transition-all duration-300">
                    <div class="bg-gradient-to-r ${headerColor} px-8 py-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">${title}</h2>
                            <p class="${colors.text} text-sm mt-1">${cache.summary.department_name || 'Department/Office'}</p>
                        </div>
                        <button onclick="closeViewDetailModal()" class="text-white hover:text-gray-200 transition-colors p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-8">
                        ${modalBody}
                    </div>
                    <div class="px-8 py-4 bg-gray-50 border-t border-gray-200 flex justify-end items-center">
                        <button onclick="closeViewDetailModal()" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                            Close
                        </button>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');
        }

        function closeViewDetailModal() {
            const modal = document.getElementById('viewDetailModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        async function loadPriorYearsForView() {
            const cache = window._viewSummaryCache;
            if (!cache || !cache.summary) return;

            const container = document.getElementById('priorYearsViewContent');
            if (!container) return;

            try {
                const departmentId = cache.summary.department_id;
                const response = await fetch(`../api/load_prior_years.php?department_id=${departmentId}&all_years=1`);
                const data = await response.json();

                if (data.success && data.years && Object.keys(data.years).length > 0) {
                    const years = Object.keys(data.years).sort((a, b) => b - a);
                    let yearCards = years.map((year, idx) => {
                        const entries = data.years[year];
                        const entryCount = entries.length;
                        const grandTotal = entries.reduce((s, e) => {
                            return s + parseFloat(e.student_development || 0) + parseFloat(e.faculty_development || 0) + parseFloat(e.curriculum_development || 0) + parseFloat(e.facilities_development || 0) + parseFloat(e.development_fee || 0) + parseFloat(e.laboratory_fee || 0) + parseFloat(e.computer_fee || 0);
                        }, 0);

                        return `
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                                <div class="bg-gradient-to-r from-orange-50 to-orange-100 px-5 py-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold text-gray-900">FY ${year}</h4>
                                            <p class="text-sm text-gray-500">${entryCount} ${entryCount === 1 ? 'entry' : 'entries'} &bull; Total: <span class="font-semibold text-orange-600">${formatNumber(grandTotal)}</span></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button onclick="togglePriorYearDetail('${year}')" class="flex items-center gap-1.5 px-4 py-2 bg-white border border-orange-300 text-orange-700 rounded-lg hover:bg-orange-50 transition-all text-sm font-semibold shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </button>
                                        <button onclick="downloadPriorYearPDFFromView(${departmentId}, '${year}')" class="flex items-center gap-1.5 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all text-sm font-semibold shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Download
                                        </button>
                                    </div>
                                </div>
                                <div id="priorYearDetail_${year}" class="hidden">
                                    <div class="p-4 overflow-x-auto">
                                        <table class="w-full text-sm" style="min-width: 900px;">
                                            <thead>
                                                <tr class="bg-orange-50 border-b border-orange-200">
                                                    <th class="text-left py-3 px-3 font-bold text-gray-800 text-xs" style="min-width: 160px;">Expense Category</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Student Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Faculty Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Curriculum Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Facilities Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Dev Fee</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Lab Fee</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Computer Fee</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-100">
                                                ${entries.map(entry => `
                                                    <tr class="hover:bg-orange-50 transition-colors">
                                                        <td class="py-2.5 px-3 text-gray-900 font-medium">${entry.expense_category || '-'}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.student_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.student_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.faculty_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.faculty_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.curriculum_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.curriculum_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.facilities_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.facilities_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.development_fee||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.development_fee || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.laboratory_fee||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.laboratory_fee || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.computer_fee||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.computer_fee || 0)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                            <tfoot class="bg-orange-50 border-t-2 border-orange-300">
                                                <tr class="font-bold">
                                                    <td class="py-3 px-3 text-gray-900">Total</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.student_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.faculty_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.curriculum_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.facilities_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.development_fee || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.laboratory_fee || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.computer_fee || 0), 0))}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    container.innerHTML = `
                        <div class="mb-4 flex items-center justify-between">
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold text-orange-600">${years.length}</span> ${years.length === 1 ? 'year' : 'years'} recorded
                            </p>
                        </div>
                        <div class="space-y-3">
                            ${yearCards}
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-orange-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">No prior years data found</p>
                            <p class="text-gray-400 text-sm mt-1">Prior years data has not been entered for this department/office yet.</p>
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Error loading prior years:', e);
                container.innerHTML = `
                    <div class="text-center py-8">
                        <p class="text-red-500">Error loading prior years data. Please try again.</p>
                    </div>
                `;
            }
        }

        function togglePriorYearDetail(year) {
            const detail = document.getElementById(`priorYearDetail_${year}`);
            if (detail) {
                detail.classList.toggle('hidden');
            }
        }

        function downloadPriorYearPDFFromView(departmentId, year) {
            window.open(`../api/generate_prior_years_pdf.php?department_id=${departmentId}&fiscal_year=${year}`, '_blank');
        }

        function downloadAllPriorYearsPDFFromView() {
            const cache = window._viewSummaryCache;
            if (!cache || !cache.summary) return;
            const departmentId = cache.summary.department_id;
            window.open(`../api/generate_prior_years_pdf.php?department_id=${departmentId}&all_years=1`, '_blank');
        }

        // ==========================================
        // CHILD DEPARTMENT DETAIL MODAL
        // ==========================================
        function showChildDetailModal(childIndex, section) {
            const childData = window._childDeptData ? window._childDeptData[childIndex] : null;
            if (!childData) {
                alert('No data available for this section.');
                return;
            }

            let title = '';
            let headerColor = '';
            let modalBody = '';

            switch (section) {
                case 'pr':
                    title = 'Purchase Requests';
                    headerColor = 'from-blue-500 to-blue-600';
                    const prEntries = childData.prEntries || [];
                    console.log('=== PR Modal Debug ===');
                    console.log('prEntries:', prEntries);
                    if (prEntries.length > 0) {
                        console.log('First entry:', prEntries[0]);
                        console.log('Keys:', Object.keys(prEntries[0]));
                    }
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-blue-50 to-blue-100 border-b-2 border-blue-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Purchase Request</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Particulars</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">PR No. / PO No.</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${prEntries.length > 0 ? prEntries.map(entry => `
                                        <tr class="hover:bg-blue-50 transition-colors">
                                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.purchaseRequest || entry.purchase_request || '-'}</td>
                                            <td class="py-3 px-4 text-gray-700">${entry.particulars || '-'}</td>
                                            <td class="py-3 px-4 text-gray-700">${entry.prNumber || entry.pr_number || entry.pr_no || '-'}</td>
                                            <td class="py-3 px-4 text-gray-700">${entry.date || '-'}</td>
                                            <td class="py-3 px-4 text-right text-blue-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="5" class="py-6 px-4 text-center text-gray-500 italic">No purchase requests found</td></tr>'}
                                </tbody>
                                <tfoot class="bg-blue-50 border-t-2 border-blue-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="4">Total</td>
                                        <td class="text-right py-4 px-4 text-blue-700 text-lg">${formatNumber((childData.totals || {}).prTotal || prEntries.reduce((s, e) => s + parseFloat(e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'travels':
                    title = 'Travels';
                    headerColor = 'from-green-500 to-green-600';
                    const travelsEntries = childData.travelsEntries || [];
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-green-50 to-green-100 border-b-2 border-green-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Travelled</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Event/Activity</th>
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${travelsEntries.length > 0 ? travelsEntries.map(entry => `
                                        <tr class="hover:bg-green-50 transition-colors">
                                            <td class="py-3 px-4 text-gray-900 font-medium">${entry.travelled || '-'}</td>
                                            <td class="py-3 px-4 text-gray-700">${entry.event_activity || entry.event || '-'}</td>
                                            <td class="py-3 px-4 text-gray-700">${entry.date || '-'}</td>
                                            <td class="py-3 px-4 text-right text-green-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="4" class="py-6 px-4 text-center text-gray-500 italic">No travels found</td></tr>'}
                                </tbody>
                                <tfoot class="bg-green-50 border-t-2 border-green-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4" colspan="3">Total</td>
                                        <td class="text-right py-4 px-4 text-green-700 text-lg">${formatNumber((childData.totals || {}).travelsTotal || travelsEntries.reduce((s, e) => s + parseFloat(e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'honoraria':
                    title = 'Honoraria';
                    headerColor = 'from-purple-500 to-purple-600';
                    const honorariaEntries = childData.honorariaEntries || [];
                    modalBody = `
                        <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gradient-to-r from-purple-50 to-purple-100 border-b-2 border-purple-200">
                                        <th class="text-left py-4 px-4 font-bold text-gray-800">Date</th>
                                        <th class="text-right py-4 px-4 font-bold text-gray-800">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    ${honorariaEntries.length > 0 ? honorariaEntries.map(entry => `
                                        <tr class="hover:bg-purple-50 transition-colors">
                                            <td class="py-3 px-4 text-gray-700 font-medium">${entry.date || '-'}</td>
                                            <td class="py-3 px-4 text-right text-purple-600 font-semibold">${formatNumber(entry.amount || 0)}</td>
                                        </tr>
                                    `).join('') : '<tr><td colspan="2" class="py-6 px-4 text-center text-gray-500 italic">No honoraria entries found</td></tr>'}
                                </tbody>
                                <tfoot class="bg-purple-50 border-t-2 border-purple-300">
                                    <tr class="font-bold">
                                        <td class="py-4 px-4">Total</td>
                                        <td class="text-right py-4 px-4 text-purple-700 text-lg">${formatNumber((childData.totals || {}).honorariaTotal || honorariaEntries.reduce((s, e) => s + parseFloat(e.amount || 0), 0))}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    `;
                    break;

                case 'priorYears':
                    title = 'Prior Years';
                    headerColor = 'from-orange-500 to-orange-600';
                    modalBody = `<div id="childPriorYearsContent_${childIndex}"data-department-id="${childData.departmentId}"><div class="text-center py-8"><p class="text-gray-500">Loading prior years history...</p></div></div>`;
                    break;
            }

            // Create and show the modal (reuse or create)
            let modal = document.getElementById('childDetailModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'childDetailModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[100] hidden flex items-center justify-center p-4';
                modal.onclick = function (e) { if (e.target === modal) closeChildDetailModal(); };
                document.body.appendChild(modal);
            }

            modal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col transform transition-all duration-300">
                    <div class="bg-gradient-to-r ${headerColor} px-8 py-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">${title}</h2>
                            <p class="text-white text-opacity-80 text-sm mt-1">${childData.departmentName || 'Sub-Department'}</p>
                        </div>
                        <button onclick="closeChildDetailModal()" class="text-white hover:text-gray-200 transition-colors p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-8">
                        ${modalBody}
                    </div>
                    <div class="px-8 py-4 bg-gray-50 border-t border-gray-200 flex justify-end items-center">
                        <button onclick="closeChildDetailModal()" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                            Close
                        </button>
                    </div>
                </div>
            `;

            modal.classList.remove('hidden');

            // Load prior years data if needed
            if (section === 'priorYears') {
                loadChildPriorYears(childData.departmentId, childIndex);
            }
        }

        function closeChildDetailModal() {
            const modal = document.getElementById('childDetailModal');
            if (modal) modal.classList.add('hidden');
        }

        async function loadChildPriorYears(departmentId, childIndex) {
            const container = document.getElementById(`childPriorYearsContent_${childIndex}`);
            if (!container) return;

            try {
                const response = await fetch(`../api/load_prior_years.php?department_id=${departmentId}&all_years=1`);
                const data = await response.json();

                if (data.success && data.years && Object.keys(data.years).length > 0) {
                    const years = Object.keys(data.years).sort((a, b) => b - a);
                    let yearCards = years.map((year) => {
                        const entries = data.years[year];
                        const entryCount = entries.length;
                        const grandTotal = entries.reduce((s, e) => {
                            return s + parseFloat(e.student_development || 0) + parseFloat(e.faculty_development || 0) + parseFloat(e.curriculum_development || 0) + parseFloat(e.facilities_development || 0) + parseFloat(e.development_fee || 0) + parseFloat(e.laboratory_fee || 0) + parseFloat(e.computer_fee || 0);
                        }, 0);

                        return `
                            <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden hover:shadow-md transition-shadow">
                                <div class="bg-gradient-to-r from-orange-50 to-orange-100 px-5 py-4 flex items-center justify-between">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center">
                                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                        </div>
                                        <div>
                                            <h4 class="text-lg font-bold text-gray-900">FY ${year}</h4>
                                            <p class="text-sm text-gray-500">${entryCount} ${entryCount === 1 ? 'entry' : 'entries'} &bull; Total: <span class="font-semibold text-orange-600">${formatNumber(grandTotal)}</span></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button onclick="toggleChildPriorYearDetail('${childIndex}_${year}')" class="flex items-center gap-1.5 px-4 py-2 bg-white border border-orange-300 text-orange-700 rounded-lg hover:bg-orange-50 transition-all text-sm font-semibold shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                            View
                                        </button>
                                        <button onclick="downloadPriorYearPDFFromView(${departmentId}, '${year}')" class="flex items-center gap-1.5 px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600 transition-all text-sm font-semibold shadow-sm">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Download
                                        </button>
                                    </div>
                                </div>
                                <div id="childPriorYearDetail_${childIndex}_${year}" class="hidden">
                                    <div class="p-4 overflow-x-auto">
                                        <table class="w-full text-sm" style="min-width: 900px;">
                                            <thead>
                                                <tr class="bg-orange-50 border-b border-orange-200">
                                                    <th class="text-left py-3 px-3 font-bold text-gray-800 text-xs" style="min-width: 160px;">Expense Category</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Student Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Faculty Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Curriculum Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Facilities Dev</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Dev Fee</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Lab Fee</th>
                                                    <th class="text-right py-3 px-3 font-bold text-gray-800 text-xs">Computer Fee</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-100">
                                                ${entries.map(entry => `
                                                    <tr class="hover:bg-orange-50 transition-colors">
                                                        <td class="py-2.5 px-3 text-gray-900 font-medium">${entry.expense_category || '-'}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.student_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.student_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.faculty_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.faculty_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.curriculum_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.curriculum_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.facilities_development||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.facilities_development || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.development_fee||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.development_fee || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.laboratory_fee||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.laboratory_fee || 0)}</td>
                                                        <td class="py-2.5 px-3 text-right font-semibold ${parseFloat(entry.computer_fee||0)>0?'text-orange-600':'text-gray-900'}">${formatNumber(entry.computer_fee || 0)}</td>
                                                    </tr>
                                                `).join('')}
                                            </tbody>
                                            <tfoot class="bg-orange-50 border-t-2 border-orange-300">
                                                <tr class="font-bold">
                                                    <td class="py-3 px-3 text-gray-900">Total</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.student_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.faculty_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.curriculum_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.facilities_development || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.development_fee || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.laboratory_fee || 0), 0))}</td>
                                                    <td class="text-right py-3 px-3 text-orange-700">${formatNumber(entries.reduce((s, e) => s + parseFloat(e.computer_fee || 0), 0))}</td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');

                    container.innerHTML = `
                        <div class="mb-4 flex items-center justify-between">
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold text-orange-600">${years.length}</span> ${years.length === 1 ? 'year' : 'years'} recorded
                            </p>
                        </div>
                        <div class="space-y-3">
                            ${yearCards}
                        </div>
                    `;
                } else {
                    container.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 mx-auto text-orange-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">No prior years data found</p>
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Error loading prior years:', e);
                container.innerHTML = '<div class="text-center py-8"><p class="text-red-500">Error loading prior years data.</p></div>';
            }
        }

        function toggleChildPriorYearDetail(key) {
            const detail = document.getElementById(`childPriorYearDetail_${key}`);
            if (detail) {
                detail.classList.toggle('hidden');
            }
        }

        function downloadChildPriorYearsPDF(departmentId) {
            window.open(`../api/generate_prior_years_pdf.php?department_id=${departmentId}&all_years=1`, '_blank');
        }

        // Close view detail modal on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeViewDetailModal();
                closeChildDetailModal();
            }
        });

        // Smooth scroll functionality
        function scrollToBottom() {
            window.scrollTo({
                top: document.body.scrollHeight,
                behavior: 'smooth'
            });
        }

        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Toggle scroll button based on scroll position
        function toggleScrollButton() {
            const scrollButton = document.getElementById('scrollToBottomBtn');
            const scrollIcon = document.getElementById('scrollIcon');
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
            const windowHeight = window.innerHeight;
            const documentHeight = document.body.scrollHeight;
            
            // Check if at bottom (within 100px threshold)
            const isAtBottom = scrollTop + windowHeight >= documentHeight - 100;
            // Check if at top (within 100px threshold)
            const isAtTop = scrollTop <= 100;
            
            if (isAtBottom) {
                // At bottom - show scroll to top button
                scrollButton.style.display = 'flex';
                scrollButton.onclick = scrollToTop;
                scrollButton.title = 'Scroll to top';
                scrollIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path>
                `;
                scrollIcon.classList.remove('group-hover:translate-y-1');
                scrollIcon.classList.add('group-hover:-translate-y-1');
            } else if (isAtTop) {
                // At top - hide button
                scrollButton.style.display = 'none';
            } else {
                // In middle - show scroll to bottom button
                scrollButton.style.display = 'flex';
                scrollButton.onclick = scrollToBottom;
                scrollButton.title = 'Scroll to bottom';
                scrollIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                `;
                scrollIcon.classList.remove('group-hover:-translate-y-1');
                scrollIcon.classList.add('group-hover:translate-y-1');
            }
        }

        // Add scroll event listener
        window.addEventListener('scroll', toggleScrollButton);
        
        // Initial check
        document.addEventListener('DOMContentLoaded', toggleScrollButton);

    </script>

    <!-- Dual-Purpose Scroll Button -->
    <button 
        id="scrollToBottomBtn"
        onclick="scrollToBottom()"
        class="fixed bottom-6 right-6 bg-gradient-to-r from-maroon to-red-700 hover:from-maroon-dark hover:to-red-800 text-white p-4 rounded-full shadow-lg hover:shadow-xl transition-all duration-300 transform hover:scale-110 z-50 flex items-center justify-center group"
        style="display: none;"
        title="Scroll to bottom"
    >
        <svg id="scrollIcon" class="w-6 h-6 transition-transform duration-300 group-hover:translate-y-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
        </svg>
    </button>


<script>
function highlightCategoryFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const category = params.get('highlight');
    if (!category) return;
    const rows = document.querySelectorAll('#savedSummaryDisplay tbody tr');
    rows.forEach(row => {
        const firstCell = row.querySelector('td:first-child');
        if (firstCell && firstCell.textContent.trim().toLowerCase() === category.toLowerCase()) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('ring-4', 'ring-yellow-400', 'bg-yellow-50');
            let flashes = 0;
            const flash = setInterval(() => {
                row.classList.toggle('bg-yellow-100');
                if (++flashes >= 6) { clearInterval(flash); row.classList.remove('bg-yellow-100'); row.classList.add('bg-yellow-50'); }
            }, 350);
        }
    });
}
</script>

</body>

</html>



<script>
function highlightCategoryFromUrl() {
    const params = new URLSearchParams(window.location.search);
    const category = params.get('highlight');
    if (!category) return;
    const rows = document.querySelectorAll('#savedSummaryDisplay tbody tr');
    rows.forEach(row => {
        const firstCell = row.querySelector('td:first-child');
        if (firstCell && firstCell.textContent.trim().toLowerCase() === category.toLowerCase()) {
            row.scrollIntoView({ behavior: 'smooth', block: 'center' });
            row.classList.add('ring-4', 'ring-yellow-400', 'bg-yellow-50');
            let flashes = 0;
            const flash = setInterval(() => {
                row.classList.toggle('bg-yellow-100');
                if (++flashes >= 6) {
                    clearInterval(flash);
                    row.classList.remove('bg-yellow-100');
                    row.classList.add('bg-yellow-50');
                }
            }, 350);
        }
    });
}

// Real-time auto-refresh: listen for utilization summary updates from notification bell
window.addEventListener('utilizationSummaryUpdated', function () {
    if (typeof loadSavedSummaries === 'function') {
        loadSavedSummaries();
    }
});
</script>
