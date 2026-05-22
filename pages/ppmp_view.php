<?php
session_start();

// Allow budget, school_admin, and users from Admin department
$allowedRoles = ['budget', 'school_admin'];
$isAdminDepartment = false;

if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../components/profile_avatar.php';

// Check if user is from Admin department/office
if (isset($_SESSION['department_id'])) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT dept_name FROM departments WHERE id = ?");
        $stmt->execute([$_SESSION['department_id']]);
        $dept = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($dept && stripos($dept['dept_name'], 'admin') !== false) {
            $isAdminDepartment = true;
        }
    } catch (Exception $e) {
        // Continue with normal access check
    }
}

// Check access: must be in allowed roles OR from Admin department
if (!in_array($_SESSION['user_role'], $allowedRoles) && !$isAdminDepartment) {
    header('Location: ../login.php');
    exit;
}

$activeSidebar = 'ppmp_view';

$username = $_SESSION['user_name'] ?? 'Administrator';
$userEmail = $_SESSION['user_email'] ?? '';
$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'];

$notification = new Notification();
$notifications = $notification->getUserNotifications($userId ?? 0, 10);
$unreadCount = $notification->getUnreadCount($userId ?? 0);

// Separate departments and offices
$departmentNames = ['Computer studies', 'Education', 'Industrial Technology', 'Engineering', 'Hospitality Management'];
$departments = [];
$offices = [];

try {
    $db = getDB();
    
    $stmt = $db->query("SELECT id, dept_name, dept_code FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $allDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($allDepartments as $dept) {
        $deptName = $dept['dept_name'];
        $isDepartment = false;
        foreach ($departmentNames as $deptNameCheck) {
            if (stripos($deptName, $deptNameCheck) !== false) {
                $isDepartment = true;
                break;
            }
        }
        if ($isDepartment) {
            $departments[] = $dept;
        } else {
            $offices[] = $dept;
        }
    }
} catch (PDOException $e) {
    $departments = [];
    $offices = [];
}

$fiscalYear = date('Y');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - PPMP & LIB View</title>
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
        }
    </script>
    <style>
        body { font-family: 'Inter', sans-serif; }
        .ppmp-card { transition: all 0.3s ease; }
        .ppmp-card:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .tab-content { transition: opacity 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
    <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col" data-main-content>
        <!-- Header -->
        <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start">
                    <div class="text-white">
                        <div class="flex items-center gap-3 mb-2">
                            <div class="bg-white bg-opacity-20 rounded-xl p-3">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold mb-1">PPMP & LIB View</h1>
                                <p class="text-red-100 text-sm">View Project Procurement Management Plans and Library of Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
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
                                        </svg>
                                        Change Password
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
        </div>

        <!-- Content Area -->
        <div class="flex-1 p-6">
            <div class="max-w-7xl mx-auto">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <!-- Selection Section -->
                    <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Department Selection -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg>
                                    Select Department
                                </label>
                                <select id="departmentSelect" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-medium">
                                    <option value="">-- Select Department --</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo htmlspecialchars($dept['id']); ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Office Selection -->
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                    Select Office
                                </label>
                                <select id="officeSelect" class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-medium">
                                    <option value="">-- Select Office --</option>
                                    <?php foreach ($offices as $office): ?>
                                        <option value="<?php echo htmlspecialchars($office['id']); ?>"><?php echo htmlspecialchars($office['dept_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div id="selectedDisplay" class="mt-3 flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2 text-sm font-semibold text-maroon hidden" id="selectedInfo">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span class="text-gray-600">Viewing: </span><span id="selectedName" class="text-maroon font-bold"></span>
                            </div>
                        </div>
                    </div>

                    <!-- PPMP & LIB Data Container -->
                    <div id="ppmpLibContainer" class="hidden">
                        <div class="p-6">
                            <!-- Tab Navigation -->
                            <div class="flex border-b border-gray-200 mb-6">
                                <button onclick="switchTab('ppmp')" id="ppmpTab" class="px-6 py-3 text-sm font-semibold text-maroon border-b-2 border-maroon bg-maroon bg-opacity-5">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    PPMP
                                </button>
                                <button onclick="switchTab('supplemental')" id="supplementalTab" class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-yellow-600 transition-colors">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Supplemental
                                </button>
                                <button onclick="switchTab('lib')" id="libTab" class="px-6 py-3 text-sm font-semibold text-gray-600 hover:text-maroon transition-colors">
                                    <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                    LIB
                                </button>
                            </div>

                            <!-- PPMP Tab Content -->
                            <div id="ppmpTabContent" class="tab-content">
                                <div id="ppmpListContainer">
                                    <!-- PPMPs will be loaded here -->
                                </div>
                            </div>

                            <!-- Supplemental Tab Content -->
                            <div id="supplementalTabContent" class="tab-content hidden">
                                <div id="supplementalListContainer">
                                    <!-- Supplemental PPMPs will be loaded here -->
                                </div>
                            </div>

                            <!-- LIB Tab Content -->
                            <div id="libTabContent" class="tab-content hidden">
                                <div id="libListContainer">
                                    <!-- LIBs will be loaded here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div id="emptyState" class="p-8">
                        <div class="rounded-2xl border-2 border-dashed border-gray-300 bg-gradient-to-br from-gray-50 to-white p-12 text-center">
                            <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-700 mb-2">No Department or Office Selected</h3>
                            <p class="text-gray-500 mb-6">Please select a department or office from the dropdowns above to view their PPMPs and LIBs.</p>
                            <div class="flex items-center justify-center gap-2 text-sm text-gray-400">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>Select from either Departments or Offices</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View PPMP Modal -->
<div id="viewPPMPModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl z-10">
                <h3 class="text-2xl font-bold">View PPMP</h3>
                <button onclick="closeViewPPMPModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="viewPPMPContent" class="p-6">
                <div class="text-center py-8 text-gray-500">
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View LIB Modal -->
<div id="viewLIBModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-7xl max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl z-10">
                <h3 class="text-2xl font-bold">View LIB</h3>
                <button onclick="closeViewLIBModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="viewLIBContent" class="p-6">
                <div class="text-center py-8 text-gray-500">
                    <p>Loading...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const departments = <?php echo json_encode($departments); ?>;
const offices = <?php echo json_encode($offices); ?>;
const userRole = '<?php echo $userRole; ?>';
let currentDepartmentId = null;
let currentDepartmentName = '';

const PPMP_VIEW_STATE_KEY = 'ppmpViewState';
let activeTabName = 'ppmp';

function saveViewState() {
    const deptSelect = document.getElementById('departmentSelect');
    const officeSelect = document.getElementById('officeSelect');
    localStorage.setItem(PPMP_VIEW_STATE_KEY, JSON.stringify({
        deptId: deptSelect?.value || '',
        officeId: officeSelect?.value || '',
        tab: activeTabName,
        scrollY: window.scrollY
    }));
}

function restoreViewState() {
    const raw = localStorage.getItem(PPMP_VIEW_STATE_KEY);
    if (!raw) return;
    try {
        const state = JSON.parse(raw);
        const deptSelect = document.getElementById('departmentSelect');
        const officeSelect = document.getElementById('officeSelect');

        if (state.deptId && deptSelect) {
            deptSelect.value = state.deptId;
            if (deptSelect.value) {
                officeSelect.value = '';
                loadPPMPData('department');
            }
        } else if (state.officeId && officeSelect) {
            officeSelect.value = state.officeId;
            if (officeSelect.value) {
                deptSelect.value = '';
                loadPPMPData('office');
            }
        }

        // Restore tab after data loads (slight delay so content is rendered)
        if (state.tab) {
            setTimeout(() => {
                switchTab(state.tab);
                // Restore scroll position after tab switch
                if (state.scrollY) {
                    setTimeout(() => window.scrollTo({ top: state.scrollY, behavior: 'instant' }), 150);
                }
            }, 300);
        }
    } catch (e) {}
}

function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('hidden');
}

document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
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

function loadPPMPData(sourceDropdown = null) {
    const deptSelect = document.getElementById('departmentSelect');
    const officeSelect = document.getElementById('officeSelect');
    
    let selectedId = null;
    let selectedName = '';
    
    // Determine which dropdown has a value and clear the other
    if (sourceDropdown === 'department' && deptSelect.value) {
        selectedId = deptSelect.value;
        selectedName = deptSelect.options[deptSelect.selectedIndex].text;
        officeSelect.value = ''; // Clear office selection
    } else if (sourceDropdown === 'office' && officeSelect.value) {
        selectedId = officeSelect.value;
        selectedName = officeSelect.options[officeSelect.selectedIndex].text;
        deptSelect.value = ''; // Clear department selection
    } else if (!sourceDropdown) {
        // Fallback for direct calls without source specification
        if (deptSelect.value) {
            selectedId = deptSelect.value;
            selectedName = deptSelect.options[deptSelect.selectedIndex].text;
        } else if (officeSelect.value) {
            selectedId = officeSelect.value;
            selectedName = officeSelect.options[officeSelect.selectedIndex].text;
        }
    }
    
    if (!selectedId) {
        document.getElementById('ppmpLibContainer').classList.add('hidden');
        document.getElementById('emptyState').classList.remove('hidden');
        document.getElementById('selectedInfo').classList.add('hidden');
        return;
    }
    
    currentDepartmentId = selectedId;
    currentDepartmentName = selectedName;
    
    // Show selected info
    document.getElementById('selectedName').textContent = selectedName;
    document.getElementById('selectedInfo').classList.remove('hidden');
    
    // Show loading
    document.getElementById('emptyState').classList.add('hidden');
    document.getElementById('ppmpLibContainer').classList.remove('hidden');
    
    // Load PPMP, Supplemental, and LIB data
    loadPPMPs(selectedId);
    loadSupplementals(selectedId);
    loadLIBs(selectedId);

    // Persist selection
    saveViewState();
}

// Add separate event handlers for each dropdown to ensure proper clearing
document.addEventListener('DOMContentLoaded', function() {
    const deptSelect = document.getElementById('departmentSelect');
    const officeSelect = document.getElementById('officeSelect');
    
    if (deptSelect) {
        deptSelect.addEventListener('change', function() {
            loadPPMPData('department');
        });
    }
    
    if (officeSelect) {
        officeSelect.addEventListener('change', function() {
            loadPPMPData('office');
        });
    }

    // Restore last state (selection + tab + scroll)
    restoreViewState();

    // Save scroll position as user scrolls
    window.addEventListener('scroll', function() {
        const raw = localStorage.getItem(PPMP_VIEW_STATE_KEY);
        if (!raw) return;
        try {
            const state = JSON.parse(raw);
            state.scrollY = window.scrollY;
            localStorage.setItem(PPMP_VIEW_STATE_KEY, JSON.stringify(state));
        } catch (e) {}
    }, { passive: true });
});

function switchTab(tabName) {
    // Update tab buttons
    const ppmpTab = document.getElementById('ppmpTab');
    const supplementalTab = document.getElementById('supplementalTab');
    const libTab = document.getElementById('libTab');
    const ppmpContent = document.getElementById('ppmpTabContent');
    const supplementalContent = document.getElementById('supplementalTabContent');
    const libContent = document.getElementById('libTabContent');
    
    // Reset all tabs
    ppmpTab.className = 'px-6 py-3 text-sm font-semibold text-gray-600 hover:text-maroon transition-colors';
    supplementalTab.className = 'px-6 py-3 text-sm font-semibold text-gray-600 hover:text-yellow-600 transition-colors';
    libTab.className = 'px-6 py-3 text-sm font-semibold text-gray-600 hover:text-maroon transition-colors';
    ppmpContent.classList.add('hidden');
    supplementalContent.classList.add('hidden');
    libContent.classList.add('hidden');
    
    if (tabName === 'ppmp') {
        ppmpTab.className = 'px-6 py-3 text-sm font-semibold text-maroon border-b-2 border-maroon bg-maroon bg-opacity-5';
        ppmpContent.classList.remove('hidden');
    } else if (tabName === 'supplemental') {
        supplementalTab.className = 'px-6 py-3 text-sm font-semibold text-yellow-600 border-b-2 border-yellow-600 bg-yellow-600 bg-opacity-5';
        supplementalContent.classList.remove('hidden');
    } else {
        libTab.className = 'px-6 py-3 text-sm font-semibold text-blue-600 border-b-2 border-blue-600 bg-blue-600 bg-opacity-5';
        libContent.classList.remove('hidden');
    }

    // Tag active tab for state saving and persist
    activeTabName = tabName;
    saveViewState();
}

function loadPPMPs(departmentId) {
    document.getElementById('ppmpListContainer').innerHTML = '<div class="text-center py-8 text-gray-500"><p>Loading PPMPs...</p></div>';
    
    // Load PPMPs (regular only, not supplemental)
    fetch(`../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=ppmp`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displayPPMPs(data.ppmps);
            } else {
                document.getElementById('ppmpListContainer').innerHTML = '<div class="text-center py-8 text-red-500"><p>Error loading PPMPs</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('ppmpListContainer').innerHTML = '<div class="text-center py-8 text-red-500"><p>Network error</p></div>';
        });
}

function loadSupplementals(departmentId) {
    document.getElementById('supplementalListContainer').innerHTML = '<div class="text-center py-8 text-gray-500"><p>Loading Supplemental PPMPs...</p></div>';
    
    // Load Supplemental PPMPs
    fetch(`../api/get_ppmp_list.php?department_id=${departmentId}&ppmp_type=supplemental`)
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                displaySupplementals(data.ppmps);
            } else {
                document.getElementById('supplementalListContainer').innerHTML = '<div class="text-center py-8 text-red-500"><p>Error loading Supplemental PPMPs</p></div>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('supplementalListContainer').innerHTML = '<div class="text-center py-8 text-red-500"><p>Network error</p></div>';
        });
}

function loadLIBs(departmentId) {
    document.getElementById('libListContainer').innerHTML = '<div class="text-center py-8 text-gray-500"><p>Loading LIBs...</p></div>';
    
    console.log('Loading LIBs for department:', departmentId);
    
    // Load LIBs
    fetch(`../api/get_lib_list.php?department_id=${departmentId}`)
        .then(res => res.json())
        .then(data => {
            console.log('LIB API Response:', data);
            if (data.success) {
                console.log('LIBs found:', data.libs.length);
                if (data.debug) {
                    console.log('Debug info:', data.debug);
                }
                displayLIBs(data.libs);
            } else {
                console.error('API returned error:', data.message);
                document.getElementById('libListContainer').innerHTML = '<div class="text-center py-8 text-red-500"><p>Error loading LIBs: ' + (data.message || 'Unknown error') + '</p></div>';
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('libListContainer').innerHTML = '<div class="text-center py-8 text-red-500"><p>Network error</p></div>';
        });
}

function displayPPMPs(ppmps) {
    const container = document.getElementById('ppmpListContainer');
    
    // Filter out draft PPMPs if user is budget office
    if (userRole === 'budget' || userRole === 'school_admin') {
        ppmps = ppmps.filter(ppmp => ppmp.status === 'approved');
    }
    
    if (ppmps.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">No PPMPs Found</h3>
                <p class="text-gray-500">This department/office has not created any ${userRole === 'budget' || userRole === 'school_admin' ? 'final ' : ''}PPMPs yet.</p>
            </div>
        `;
        return;
    }
    
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'approved': 'bg-green-100 text-green-800'
    };
    
    let html = '<div class="space-y-4">';
    
    ppmps.forEach(ppmp => {
        const statusClass = statusColors[ppmp.status] || 'bg-gray-100 text-gray-800';
        const statusText = ppmp.status === 'approved' ? 'FINAL' : 'DRAFT';
        const typeLabels = [];
        if (ppmp.is_indicative == 1) typeLabels.push('Indicative');
        if (ppmp.is_final == 1) typeLabels.push('Final');
        const typeText = typeLabels.length > 0 ? typeLabels.join(', ') : '';
        
        html += `
            <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all cursor-pointer hover:border-maroon" onclick="viewPPMP(${ppmp.id})">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-800 mb-1">PPMP ${ppmp.ppmp_number}</h3>
                        <p class="text-sm text-gray-500">Fiscal Year: ${ppmp.fiscal_year}</p>
                        ${typeText ? `<p class="text-sm text-gray-500">${typeText}</p>` : ''}
                        <p class="text-xs text-gray-400 mt-2">Created: ${new Date(ppmp.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
                        <button onclick="event.stopPropagation(); viewPPMP(${ppmp.id})" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Details
                        </button>
                        <button onclick="event.stopPropagation(); downloadPPMP(${ppmp.id})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function displaySupplementals(ppmps) {
    const container = document.getElementById('supplementalListContainer');
    
    // Filter out draft PPMPs if user is budget office
    if (userRole === 'budget' || userRole === 'school_admin') {
        ppmps = ppmps.filter(ppmp => ppmp.status === 'approved');
    }
    
    if (ppmps.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 rounded-full bg-yellow-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">No Supplemental PPMPs Found</h3>
                <p class="text-gray-500">This department/office has not created any ${userRole === 'budget' || userRole === 'school_admin' ? 'final ' : ''}Supplemental PPMPs yet.</p>
            </div>
        `;
        return;
    }
    
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'approved': 'bg-green-100 text-green-800'
    };
    
    let html = '<div class="space-y-4">';
    
    ppmps.forEach(ppmp => {
        const statusClass = statusColors[ppmp.status] || 'bg-gray-100 text-gray-800';
        const statusText = ppmp.status === 'approved' ? 'FINAL' : 'DRAFT';
        const typeLabels = [];
        if (ppmp.is_indicative == 1) typeLabels.push('Indicative');
        if (ppmp.is_final == 1) typeLabels.push('Final');
        const typeText = typeLabels.length > 0 ? typeLabels.join(', ') : '';
        
        html += `
            <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all cursor-pointer hover:border-yellow-600" onclick="viewPPMP(${ppmp.id})">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <h3 class="text-xl font-bold text-gray-800">Supplemental ${ppmp.ppmp_number}</h3>
                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">SUPPLEMENTAL</span>
                        </div>
                        <p class="text-sm text-gray-500">Fiscal Year: ${ppmp.fiscal_year}</p>
                        ${typeText ? `<p class="text-sm text-gray-500">${typeText}</p>` : ''}
                        <p class="text-xs text-gray-400 mt-2">Created: ${new Date(ppmp.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
                        <button onclick="event.stopPropagation(); viewPPMP(${ppmp.id})" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Details
                        </button>
                        <button onclick="event.stopPropagation(); downloadPPMP(${ppmp.id})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function viewPPMP(ppmpId) {
    document.getElementById('viewPPMPModal').classList.remove('hidden');
    document.getElementById('viewPPMPContent').innerHTML = '<div class="text-center py-8 text-gray-500"><p>Loading...</p></div>';
    
    // Reset to page 1
    window.currentPPMPPage = 1;
    
    fetch(`../api/get_ppmp_details.php?id=${ppmpId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store data for pagination
                currentPPMPData = {
                    ppmp: data.ppmp,
                    items: data.items,
                    department: data.department
                };
                document.getElementById('viewPPMPContent').innerHTML = generatePPMPViewHTML(data.ppmp, data.items, data.department);
            } else {
                console.error('Error loading PPMP:', data.message);
                document.getElementById('viewPPMPContent').innerHTML = `<div class="text-center py-8 text-red-500"><p>Error loading PPMP: ${data.message || 'Unknown error'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('viewPPMPContent').innerHTML = '<div class="text-center py-8 text-red-500"><p>Network error loading PPMP</p></div>';
        });
}

function closeViewPPMPModal() {
    document.getElementById('viewPPMPModal').classList.add('hidden');
}

function displayLIBs(libs) {
    const container = document.getElementById('libListContainer');
    
    if (libs.length === 0) {
        container.innerHTML = `
            <div class="text-center py-12">
                <div class="mx-auto w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-gray-700 mb-2">No LIBs Found</h3>
                <p class="text-gray-500">This department/office has not created any approved LIBs yet.</p>
            </div>
        `;
        return;
    }
    
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'pending_approval': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    
    const statusLabels = {
        'draft': 'DRAFT',
        'pending_approval': 'PENDING',
        'approved': 'APPROVED',
        'rejected': 'REJECTED'
    };
    
    let html = '<div class="space-y-4">';
    
    libs.forEach(lib => {
        const statusClass = statusColors[lib.status] || 'bg-gray-100 text-gray-800';
        const statusText = statusLabels[lib.status] || lib.status.toUpperCase();
        
        // Generate LIB number using dept_code if available, otherwise use department name initials
        let deptCode = lib.dept_code || '';
        if (!deptCode && lib.dept_name) {
            deptCode = lib.dept_name.split(' ').map(word => word.charAt(0).toUpperCase()).join('').substring(0, 4);
        }
        const libNumber = `${deptCode}-LIB-${lib.fiscal_year}-${String(lib.id).padStart(3, '0')}`;
        
        html += `
            <div class="bg-white border-2 border-gray-200 rounded-xl p-6 hover:shadow-lg transition-all cursor-pointer hover:border-blue-600" onclick="viewLIB(${lib.id})">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-gray-800 mb-1">${libNumber}</h3>
                        <p class="text-sm text-gray-500">Fiscal Year: ${lib.fiscal_year}</p>
                        <p class="text-sm text-gray-500">Fund Type: ${lib.fund_type || 'N/A'}</p>
                        <p class="text-xs text-gray-400 mt-2">Created: ${new Date(lib.created_at).toLocaleDateString()}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
                        <button onclick="event.stopPropagation(); viewLIB(${lib.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Details
                        </button>
                        <button onclick="event.stopPropagation(); downloadLIB(${lib.id})" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download PDF
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += '</div>';
    container.innerHTML = html;
}

function viewLIB(libId) {
    document.getElementById('viewLIBModal').classList.remove('hidden');
    document.getElementById('viewLIBContent').innerHTML = '<div class="text-center py-8 text-gray-500"><p>Loading...</p></div>';
    
    fetch(`../api/get_lib_details.php?id=${libId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('viewLIBContent').innerHTML = generateLIBViewHTML(data.lib, data.items, data.department);
            } else {
                console.error('Error loading LIB:', data.message);
                document.getElementById('viewLIBContent').innerHTML = `<div class="text-center py-8 text-red-500"><p>Error loading LIB: ${data.message || 'Unknown error'}</p></div>`;
            }
        })
        .catch(error => {
            console.error('Fetch error:', error);
            document.getElementById('viewLIBContent').innerHTML = '<div class="text-center py-8 text-red-500"><p>Network error loading LIB</p></div>';
        });
}

function closeViewLIBModal() {
    document.getElementById('viewLIBModal').classList.add('hidden');
}

function downloadPPMP(ppmpId) {
    // Open PDF in new window for printing/downloading
    window.open(`../api/download_ppmp_pdf.php?id=${ppmpId}`, '_blank');
}

function downloadLIB(libId) {
    // Open PDF in new window for printing/downloading
    window.open(`../api/download_lib_pdf.php?id=${libId}`, '_blank');
}

function generateLIBViewHTML(lib, items, department) {
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'pending_approval': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    
    const statusLabels = {
        'draft': 'DRAFT',
        'pending_approval': 'PENDING',
        'approved': 'APPROVED',
        'rejected': 'REJECTED'
    };
    
    const statusClass = statusColors[lib.status] || 'bg-gray-100 text-gray-800';
    const statusText = statusLabels[lib.status] || lib.status.toUpperCase();
    
    // Generate LIB number from department code and ID
    const deptCode = department.dept_code || 'DEPT';
    const libNumber = `${deptCode}-LIB-${lib.fiscal_year}-${String(lib.id).padStart(3, '0')}`;
    
    let totalAmount = 0;
    items.forEach(item => {
        totalAmount += parseFloat(item.amount || 0);
    });
    
    let html = `
        <div class="mb-6">
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Department</p>
                    <p class="font-bold text-gray-800">${department.dept_name}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fiscal Year</p>
                    <p class="font-bold text-gray-800">${lib.fiscal_year}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">LIB Number</p>
                    <p class="font-bold text-gray-800">${libNumber}</p>
                </div>
            </div>
            <div class="flex justify-end">
                <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="w-full border-collapse border border-gray-300">
                <thead>
                    <tr class="bg-blue-600 text-white">
                        <th class="border border-gray-300 px-4 py-2 text-left text-xs">Particular</th>
                        <th class="border border-gray-300 px-4 py-2 text-left text-xs">Account Code</th>
                        <th class="border border-gray-300 px-4 py-2 text-right text-xs">Amount</th>
                    </tr>
                </thead>
                <tbody>
    `;
    
    items.forEach(item => {
        html += `
            <tr class="hover:bg-gray-50">
                <td class="border border-gray-300 px-4 py-2 text-sm">${item.particulars || ''}</td>
                <td class="border border-gray-300 px-4 py-2 text-sm">${item.account_code || ''}</td>
                <td class="border border-gray-300 px-4 py-2 text-sm text-right">₱${parseFloat(item.amount).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            </tr>
        `;
    });
    
    html += `
                    <tr class="bg-blue-600 text-white font-bold">
                        <td colspan="2" class="border border-gray-300 px-4 py-2 text-right">TOTAL:</td>
                        <td class="border border-gray-300 px-4 py-2 text-right">₱${totalAmount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    `;
    
    return html;
}

function generatePPMPViewHTML(ppmp, items, department) {
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'approved': 'bg-green-100 text-green-800'
    };
    const statusText = ppmp.status === 'approved' ? 'FINAL' : 'DRAFT';
    const statusClass = statusColors[ppmp.status] || 'bg-gray-100 text-gray-800';
    
    // Determine if this is a supplemental PPMP
    const isSupplemental = ppmp.ppmp_type === 'supplemental';
    const ppmpLabel = isSupplemental ? 'Supplemental Number' : 'PPMP Number';
    const ppmpTitle = isSupplemental ? 'Supplemental PPMP' : 'PPMP';
    
    // Pagination setup
    const itemsPerPage = 10;
    const totalPages = Math.ceil(items.length / itemsPerPage);
    const currentPage = window.currentPPMPPage || 1;
    const startIndex = (currentPage - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const paginatedItems = items.slice(startIndex, endIndex);
    
    let totalBudget = 0;
    items.forEach(item => {
        totalBudget += parseFloat(item.estimated_budget);
    });
    
    let html = `
        <div class="mb-6">
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div>
                    <p class="text-sm text-gray-500">Department</p>
                    <p class="font-bold text-gray-800">${department.dept_name}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fiscal Year</p>
                    <p class="font-bold text-gray-800">${ppmp.fiscal_year}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">${ppmpLabel}</p>
                    <div class="flex items-center gap-2">
                        <p class="font-bold text-gray-800">${ppmp.ppmp_number}</p>
                        ${isSupplemental ? '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">SUPPLEMENTAL</span>' : ''}
                    </div>
                </div>
            </div>
            <div class="flex justify-between items-center">
                <p class="text-sm text-gray-600">Showing ${startIndex + 1}-${Math.min(endIndex, items.length)} of ${items.length} items</p>
                <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
            </div>
        </div>
        
        <style>
            .ppmp-item-row {
                transition: all 0.2s ease;
            }
            .ppmp-item-row:hover {
                background-color: #f9fafb;
            }
            .ppmp-details-row {
                background-color: #f8f9fa;
                border-left: 4px solid ${isSupplemental ? '#ca8a04' : '#800000'};
            }
            .ppmp-toggle-icon {
                transition: transform 0.3s ease;
            }
            .ppmp-toggle-icon.expanded {
                transform: rotate(180deg);
            }
            .ppmp-detail-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 1rem;
            }
        </style>
        
        <div class="space-y-2">
    `;
    
    paginatedItems.forEach((item, index) => {
        const itemId = `ppmp_item_${startIndex + index}`;
        html += `
            <div class="border border-gray-200 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                <!-- Main Row -->
                <div class="ppmp-item-row flex items-center justify-between px-4 py-3 cursor-pointer bg-white" onclick="togglePPMPDetails('${itemId}')">
                    <div class="flex items-center gap-3 flex-1">
                        <button class="ppmp-toggle-icon text-${isSupplemental ? 'yellow' : 'maroon'}-600 hover:text-${isSupplemental ? 'yellow' : 'maroon'}-800 transition-colors" id="toggle_${itemId}">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-semibold text-gray-500 bg-gray-100 px-2 py-1 rounded">ITEM #${startIndex + index + 1}</span>
                                <span class="text-sm font-bold text-gray-900">${item.general_description}</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-4">
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Budget</p>
                            <p class="text-sm font-bold text-${isSupplemental ? 'yellow' : 'maroon'}-600">₱${parseFloat(item.estimated_budget).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                        </div>
                    </div>
                </div>
                
                <!-- Details Row (Hidden by default) -->
                <div id="${itemId}" class="ppmp-details-row hidden px-4 py-4">
                    <div class="ppmp-detail-grid">
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Type</p>
                            <p class="text-sm font-medium text-gray-900">${item.project_type}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Quantity</p>
                            <p class="text-sm font-medium text-gray-900">${parseInt(item.quantity)}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Unit</p>
                            <p class="text-sm font-medium text-gray-900">${item.unit}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Recommended Mode</p>
                            <p class="text-sm font-medium text-gray-900">${item.recommended_mode}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Pre-Proc</p>
                            <p class="text-sm font-medium text-gray-900">${item.pre_proc_conference || 'N/A'}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Start</p>
                            <p class="text-sm font-medium text-gray-900">${item.ads_posting_start || 'N/A'}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">End Ads</p>
                            <p class="text-sm font-medium text-gray-900">${item.ads_posting_end || 'N/A'}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Delivery</p>
                            <p class="text-sm font-medium text-gray-900">${item.delivery_date || 'N/A'}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Source</p>
                            <p class="text-sm font-medium text-gray-900">${item.source_of_funds || 'N/A'}</p>
                        </div>
                        <div class="bg-white rounded-lg p-3 border border-gray-200">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Budget</p>
                            <p class="text-sm font-bold text-${isSupplemental ? 'yellow' : 'maroon'}-600">₱${parseFloat(item.estimated_budget).toLocaleString('en-US', {minimumFractionDigits: 2})}</p>
                        </div>
                        ${item.remarks ? `
                        <div class="bg-white rounded-lg p-3 border border-gray-200 col-span-full">
                            <p class="text-xs font-semibold text-gray-500 uppercase mb-1">Remarks</p>
                            <p class="text-sm font-medium text-gray-900">${item.remarks}</p>
                        </div>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
        </div>
        
        <!-- Grand Total -->
        <div class="mt-6 bg-gradient-to-r from-${isSupplemental ? 'yellow' : 'maroon'}-600 to-${isSupplemental ? 'yellow' : 'red'}-700 text-white rounded-lg p-4 shadow-lg">
            <div class="flex justify-between items-center">
                <span class="text-lg font-bold">GRAND TOTAL:</span>
                <span class="text-2xl font-bold">₱${totalBudget.toLocaleString('en-US', {minimumFractionDigits: 2})}</span>
            </div>
        </div>
    `;
    
    // Add pagination controls if there are multiple pages
    if (totalPages > 1) {
        html += `
            <div class="flex justify-center items-center gap-2 mt-6">
                <button onclick="changePPMPPage(${currentPage - 1})" 
                        ${currentPage === 1 ? 'disabled' : ''} 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    Previous
                </button>
                
                <div class="flex gap-1">
        `;
        
        for (let i = 1; i <= totalPages; i++) {
            if (i === 1 || i === totalPages || (i >= currentPage - 1 && i <= currentPage + 1)) {
                html += `
                    <button onclick="changePPMPPage(${i})" 
                            class="px-4 py-2 ${i === currentPage ? 'bg-maroon text-white' : 'bg-gray-200 text-gray-700'} rounded-lg hover:bg-maroon hover:text-white">
                        ${i}
                    </button>
                `;
            } else if (i === currentPage - 2 || i === currentPage + 2) {
                html += `<span class="px-2 py-2 text-gray-500">...</span>`;
            }
        }
        
        html += `
                </div>
                
                <button onclick="changePPMPPage(${currentPage + 1})" 
                        ${currentPage === totalPages ? 'disabled' : ''} 
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 disabled:opacity-50 disabled:cursor-not-allowed">
                    Next
                </button>
            </div>
        `;
    }
    
    return html;
}

// Toggle PPMP item details
function togglePPMPDetails(itemId) {
    const detailsRow = document.getElementById(itemId);
    const toggleIcon = document.getElementById(`toggle_${itemId}`);
    
    if (detailsRow.classList.contains('hidden')) {
        detailsRow.classList.remove('hidden');
        toggleIcon.classList.add('expanded');
    } else {
        detailsRow.classList.add('hidden');
        toggleIcon.classList.remove('expanded');
    }
}

// Store PPMP data for pagination
let currentPPMPData = null;

function changePPMPPage(page) {
    if (!currentPPMPData) return;
    
    const totalPages = Math.ceil(currentPPMPData.items.length / 10);
    if (page < 1 || page > totalPages) return;
    
    window.currentPPMPPage = page;
    document.getElementById('viewPPMPContent').innerHTML = generatePPMPViewHTML(
        currentPPMPData.ppmp, 
        currentPPMPData.items, 
        currentPPMPData.department
    );
}
</script>

</body>
</html>
