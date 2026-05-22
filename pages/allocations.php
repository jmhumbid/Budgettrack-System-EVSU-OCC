<?php
session_start();

// Check if user is logged in and has allocations access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../classes/Department.php';

$notification = new Notification();
$activityLogger = new UserActivity();

// Get all active departments
$department = new Department();
$allDepartments = $department->getAllDepartments();

// Categorize departments vs offices based on fiduciary_type
// Non-Fiduciary = Department, Fiduciary = Office
$departments = [];
$offices = [];
foreach ($allDepartments as $dept) {
    $fiduciaryType = $dept['fiduciary_type'] ?? 'Non-Fiduciary';
    if ($fiduciaryType === 'Fiduciary') {
        $offices[] = $dept;
    } else {
        $departments[] = $dept;
    }
}

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$activeSidebar = 'allocations';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Allocations</title>
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
    <style>
        #inputSection {
            display: grid !important;
            grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
            gap: 1.5rem;
        }
        #inputSection > div {
            min-height: 120px;
            display: flex;
            flex-direction: column;
        }
        @media (max-width: 640px) {
            #inputSection {
                grid-template-columns: repeat(1, minmax(0, 1fr)) !important;
            }
        }
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .animate-fade-in {
            animation: fadeIn 0.3s ease-out;
        }
    </style>
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                <div>
                                    <h1 class="text-3xl font-bold mb-1">Allocations</h1>
                                <p class="text-red-100 text-sm">Calculate department budget allocations</p>
                                </div>
                            </div>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php 
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
            </div>

        <!-- Content Area -->
            <div class="flex-1 p-6">
            <div class="w-full mx-auto">
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center gap-4">
                            <h2 class="text-2xl font-bold text-maroon">Budget Allocation</h2>
                            <!-- Sync Status Indicator -->
                            <div id="syncStatusIndicator" class="hidden flex items-center gap-2 px-3 py-1 bg-green-50 border border-green-200 rounded-lg text-sm text-green-700">
                                <svg class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                <span>Auto-sync active</span>
                            </div>
                        </div>
                        <div class="flex items-end gap-4">
                            <div class="flex-1">
                                <div class="grid grid-cols-[1fr_1fr_auto_auto] gap-4 items-end">
                                    <!-- Fiscal Year Selector -->
                                    <div>
                                        <label for="fiscalYearSelect" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Fiscal Year
                                        </label>
                                        <select 
                                            id="fiscalYearSelect" 
                                            name="fiscalYearSelect" 
                                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-semibold"
                                        >
                                            <?php 
                                            $currentYear = date('Y');
                                            for ($year = $currentYear - 5; $year <= $currentYear + 1; $year++): 
                                            ?>
                                                <option value="<?php echo $year; ?>" <?php echo ($year == $currentYear) ? 'selected' : ''; ?>>
                                                    <?php echo $year; ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                    </div>
                                    
                                    <!-- Departments Selector -->
                                    <div>
                                        <label for="departmentSearch" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Departments
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="departmentSearch" 
                                                placeholder="Search department..."
                                                class="w-full pl-12 pr-20 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 placeholder-gray-400"
                                                autocomplete="off"
                                            />
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-2">
                                                <button 
                                                    id="departmentClearBtn" 
                                                    type="button"
                                                    onclick="clearDepartmentSearch()"
                                                    class="hidden w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                                                    title="Clear search"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                                <svg id="departmentDropdownIcon" class="w-5 h-5 text-gray-400 cursor-pointer hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                            <input type="hidden" id="departmentSelect" name="departmentSelect" value="">
                                            <div id="departmentDropdown" class="absolute z-50 w-full mt-1 bg-white border-2 border-gray-300 rounded-lg shadow-xl max-h-60 overflow-auto hidden">
                                                <div class="py-2">
                                                    <?php foreach ($departments as $dept): ?>
                                                        <div 
                                                            class="department-option px-4 py-3 hover:bg-maroon hover:text-white cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                                                            data-id="<?php echo htmlspecialchars($dept['id']); ?>"
                                                            data-name="<?php echo htmlspecialchars($dept['dept_name']); ?>"
                                                            data-type="department"
                                                        >
                                                            <div class="font-medium"><?php echo htmlspecialchars($dept['dept_name']); ?></div>
                                                            <?php if (!empty($dept['dept_code'])): ?>
                                                                <div class="text-xs opacity-75"><?php echo htmlspecialchars($dept['dept_code']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Offices Selector -->
                                    <div>
                                        <label for="officeSearch" class="block text-sm font-semibold text-gray-700 mb-2">
                                            Offices
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                            </div>
                                            <input 
                                                type="text" 
                                                id="officeSearch" 
                                                placeholder="Search office..."
                                                class="w-full pl-12 pr-20 py-3 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 placeholder-gray-400"
                                                autocomplete="off"
                                            />
                                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-2">
                                                <button 
                                                    id="officeClearBtn" 
                                                    type="button"
                                                    onclick="clearOfficeSearch()"
                                                    class="hidden w-5 h-5 text-gray-400 hover:text-gray-600 transition-colors cursor-pointer"
                                                    title="Clear search"
                                                >
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                                <svg id="officeDropdownIcon" class="w-5 h-5 text-gray-400 cursor-pointer hover:text-gray-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </div>
                                            <input type="hidden" id="officeSelect" name="officeSelect" value="">
                                            <div id="officeDropdown" class="absolute z-50 w-full mt-1 bg-white border-2 border-gray-300 rounded-lg shadow-xl max-h-60 overflow-auto hidden">
                                                <div class="py-2">
                                                    <?php foreach ($offices as $office): ?>
                                                        <div 
                                                            class="office-option px-4 py-3 hover:bg-maroon hover:text-white cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                                                            data-id="<?php echo htmlspecialchars($office['id']); ?>"
                                                            data-name="<?php echo htmlspecialchars($office['dept_name']); ?>"
                                                            data-type="office"
                                                        >
                                                            <div class="font-medium"><?php echo htmlspecialchars($office['dept_name']); ?></div>
                                                            <?php if (!empty($office['dept_code'])): ?>
                                                                <div class="text-xs opacity-75"><?php echo htmlspecialchars($office['dept_code']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- History Button - Fourth Column -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2 invisible">
                                            History
                                        </label>
                                        <button 
                                            onclick="showAllocationHistory()" 
                                            class="px-6 py-3 bg-gradient-to-r from-gray-700 to-gray-800 text-white rounded-lg hover:from-gray-800 hover:to-gray-900 transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl"
                                            title="View Allocation History"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <span>History</span>
                                        </button>
                                    </div>
                                </div>
                                <div id="selectedDepartment" class="mt-2 text-sm font-semibold text-maroon hidden">
                                    <span class="text-gray-600">Selected: </span><span id="selectedDepartmentName"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Allocate Budget Input (For Offices Only) -->
                    <div id="budgetAllocatedSection" class="mb-8 hidden">
                        <div class="bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl p-5 border-2 border-indigo-200 shadow-md max-w-md">
                            <label for="budgetAllocated" class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Allocate Budget
                            </label>
                            <input 
                                type="text" 
                                id="budgetAllocated" 
                                name="budgetAllocated" 
                                class="w-full px-4 py-3 border-2 border-indigo-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition-all bg-white text-gray-900 font-semibold shadow-sm"
                                inputmode="decimal"
                                placeholder="₱0.00"
                            >
                        </div>
                    </div>
                    
                    <div id="inputSection" class="grid gap-6 mb-8">
                        <!-- Input Box 1: Number of Students - HIDDEN as per requirements -->
                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-5 border-2 border-blue-200 shadow-md flex flex-col" style="display: none;">
                            <label for="numStudents" class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                </svg>
                                Number of Students
                            </label>
                            <input 
                                type="text" 
                                id="numStudents" 
                                name="numStudents" 
                                class="w-full px-4 py-3 border-2 border-blue-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all bg-white text-gray-900 font-semibold shadow-sm flex-1"
                                inputmode="numeric"
                                placeholder="0"
                            >
                        </div>

                        <!-- Input Box 2: Total Tuition Fee -->
                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-5 border-2 border-green-200 shadow-md flex flex-col">
                            <label for="totalTuitionFee" class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                                Total Tuition Fee
                            </label>
                            <input 
                                type="text" 
                                id="totalTuitionFee" 
                                name="totalTuitionFee" 
                                class="w-full px-4 py-2 border-2 border-green-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all bg-white text-gray-900 font-semibold shadow-sm flex-1"
                                inputmode="decimal"
                                placeholder="₱0.00"
                            >
                        </div>

                        <!-- Output Box: 50% Instructional -->
                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-5 border-2 border-purple-200 shadow-md flex flex-col">
                            <label for="instructionalAmount" class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                </svg>
                                50% (Instructional)
                            </label>
                            <input 
                                type="text" 
                                id="instructionalAmount" 
                                name="instructionalAmount" 
                                class="w-full px-4 py-2 border-2 border-purple-300 rounded-lg bg-white focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none transition-all text-gray-900 font-bold shadow-sm flex-1"
                                readonly
                                placeholder="₱0.00"
                            >
                        </div>
                        
                        <!-- Additional Amount Box -->
                        <div class="bg-gradient-to-br from-amber-50 to-amber-100 rounded-xl p-5 border-2 border-amber-200 shadow-md flex flex-col">
                            <label for="additionalAmount" class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Additional Amount
                            </label>
                            <input 
                                type="text" 
                                id="additionalAmount" 
                                name="additionalAmount" 
                                class="w-full px-4 py-2 border-2 border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all bg-white text-gray-900 font-semibold shadow-sm mb-2"
                                inputmode="decimal"
                                placeholder="₱0.00"
                            >
                            <label for="additionalDescription" class="block text-xs font-semibold text-gray-700 mb-1">
                                Description
                            </label>
                            <textarea 
                                id="additionalDescription" 
                                name="additionalDescription" 
                                rows="2"
                                class="w-full px-3 py-2 border-2 border-amber-300 rounded-lg focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition-all bg-white text-gray-900 resize-none text-sm flex-1"
                                placeholder="Enter description..."
                            ></textarea>
                        </div>
                    </div>

                    <!-- Non-Fiduciary Fund Breakdown Section -->
                    <div id="nonFiduciarySection" class="mt-10">
                        <div class="flex items-center justify-between mb-6 pb-4 border-b-2 border-maroon">
                            <div class="flex items-center gap-3">
                                <div class="bg-gradient-to-r from-maroon to-red-700 rounded-lg p-3 shadow-lg">
                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-2xl font-bold text-maroon">Non-Fiduciary Fund</h3>
                            </div>
                            <button 
                                type="button"
                                onclick="openSetPercentModal()" 
                                class="px-4 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-semibold flex items-center gap-2 shadow-lg hover:shadow-xl"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                                </svg>
                                Set % for All
                            </button>
                        </div>
                        
                        <!-- Table Header -->
                        <div class="flex items-center gap-6 mb-3 pb-2 border-b border-gray-300">
                            <div class="flex-1 min-w-[200px]">
                                <span class="text-sm font-semibold text-gray-700">Instructional</span>
                            </div>
                            <div class="w-40">
                                <span class="text-sm font-semibold text-gray-700">Percent</span>
                            </div>
                            <div class="w-48">
                                <span class="text-sm font-semibold text-gray-700">50%</span>
                            </div>
                            <div class="flex-1">
                                <span class="text-sm font-semibold text-gray-700">Deductions</span>
                            </div>
                            <div class="w-48">
                                <span class="text-sm font-semibold text-gray-700">Budget Allocation</span>
                            </div>
                        </div>
                        
                        <div class="space-y-4">
                            <!-- Faculty and Staff Development -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-sm font-semibold text-gray-700">Faculty and Staff Development</span>
                                    </div>
                                    <div class="w-40">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                id="facultyStaffPercent" 
                                                name="facultyStaffPercent" 
                                                class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition bg-gray-100 cursor-not-allowed"
                                                readonly
                                                placeholder="0%"
                                                inputmode="decimal"
                                                placeholder="0"
                                                maxlength="6"
                                            >
                                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                                        </div>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="facultyStaffInstructional" 
                                            name="facultyStaffInstructional" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="facultyStaffDeductionsContainer" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addDeduction('facultyStaff')" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                                            <span>Add Deduction</span>
                                            </button>
                                        <div id="facultyStaffDeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="facultyStaffDeductionTotalAmount" class="text-maroon">₱0.00</span>
                    </div>
                </div>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="facultyStaffBudgetAllocation" 
                                            name="facultyStaffBudgetAllocation" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Curriculum Development -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-sm font-semibold text-gray-700">Curriculum Development</span>
                                        </div>
                                    <div class="w-40">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                id="curriculumPercent" 
                                                name="curriculumPercent" 
                                                class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition bg-gray-100 cursor-not-allowed"
                                                readonly
                                                placeholder="0%"
                                                inputmode="decimal"
                                                placeholder="0"
                                                maxlength="6"
                                            >
                                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                                        </div>
                                        </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="curriculumInstructional" 
                                            name="curriculumInstructional" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="curriculumDeductionsContainer" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addDeduction('curriculum')" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                            <span>Add Deduction</span>
                                    </button>
                                        <div id="curriculumDeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="curriculumDeductionTotalAmount" class="text-maroon">₱0.00</span>
                                </div>
                                        </div>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="curriculumBudgetAllocation" 
                                            name="curriculumBudgetAllocation" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Student Development -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-sm font-semibold text-gray-700">Student Development</span>
                                    </div>
                                    <div class="w-40">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                id="studentPercent" 
                                                name="studentPercent" 
                                                class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition bg-gray-100 cursor-not-allowed"
                                                readonly
                                                placeholder="0%"
                                                inputmode="decimal"
                                                placeholder="0"
                                                maxlength="6"
                                            >
                                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                                        </div>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="studentInstructional" 
                                            name="studentInstructional" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="studentDeductionsContainer" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addDeduction('student')" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            <span>Add Deduction</span>
                                    </button>
                                        <div id="studentDeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="studentDeductionTotalAmount" class="text-maroon">₱0.00</span>
                                        </div>
                                </div>
                            </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="studentBudgetAllocation" 
                                            name="studentBudgetAllocation" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                        </div>
                            </div>
                        </div>

                            <!-- Facilities Development -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-sm font-semibold text-gray-700">Facilities Development</span>
                                    </div>
                                    <div class="w-40">
                                        <div class="relative">
                                            <input 
                                                type="text" 
                                                id="facilitiesPercent" 
                                                name="facilitiesPercent" 
                                                class="w-full px-4 py-3 pr-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition bg-gray-100 cursor-not-allowed"
                                                readonly
                                                placeholder="0%"
                                                inputmode="decimal"
                                                placeholder="0"
                                                maxlength="6"
                                            >
                                            <span class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-500 font-semibold">%</span>
                                        </div>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="facilitiesInstructional" 
                                            name="facilitiesInstructional" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="facilitiesDeductionsContainer" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addDeduction('facilities')" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                            <span>Add Deduction</span>
                                        </button>
                                        <div id="facilitiesDeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="facilitiesDeductionTotalAmount" class="text-maroon">₱0.00</span>
                                    </div>
                                </div>
                        </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="facilitiesBudgetAllocation" 
                                            name="facilitiesBudgetAllocation" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                        </div>
                                </div>
                            </div>
                            
                            <!-- Total Row for Non-Fiduciary Fund -->
                            <div class="border-2 border-maroon rounded-lg p-4 bg-gradient-to-r from-maroon to-red-700">
                                <div class="flex items-center gap-6">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-lg font-bold text-white">Total</span>
                                    </div>
                                    <div class="w-40">
                                        <span class="text-sm font-semibold text-white">-</span>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="nonFiduciaryTotalInstructional" 
                                            class="w-full px-4 py-3 border border-white border-opacity-30 rounded-lg bg-white bg-opacity-20 text-white font-bold focus:ring-2 focus:ring-white focus:border-white outline-none transition"
                                            readonly
                                            value="₱0.00"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div class="mt-2">
                                            <div class="pt-2 border-t border-white border-opacity-30">
                                                <span class="text-sm font-semibold text-white">Sub-total: </span>
                                                <span id="nonFiduciaryTotalDeductions" class="text-white font-bold">₱0.00</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="w-48">
                                        <span class="text-sm font-semibold text-white">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>

                    <!-- Fiduciary Fund Section -->
                    <div id="fiduciarySection" class="mt-10">
                        <div class="flex items-center gap-3 mb-6 pb-4 border-b-2 border-maroon">
                            <div class="bg-gradient-to-r from-maroon to-red-700 rounded-lg p-3 shadow-lg">
                                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                        </div>
                            <h3 class="text-2xl font-bold text-maroon">Fiduciary Fund</h3>
                                        </div>
                        
                        <!-- Table Header -->
                        <div class="flex items-center gap-6 mb-3 pb-2 border-b border-gray-300">
                            <div class="flex-1 min-w-[200px]">
                                <span class="text-sm font-semibold text-gray-700">Fiduciary</span>
                                    </div>
                            <div class="w-48">
                                <span class="text-sm font-semibold text-gray-700">Budget Collected</span>
                            </div>
                            <div class="flex-1">
                                <span class="text-sm font-semibold text-gray-700">Deductions</span>
                            </div>
                        </div>
                        
                        <div class="space-y-4" id="fiduciaryFundRows">
                            <!-- Laboratory Fee -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <input 
                                            type="text" 
                                            id="fiduciaryItem1" 
                                            name="fiduciaryItem1" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            placeholder="Enter item name"
                                        >
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="fiduciaryInstructional1" 
                                            name="fiduciaryInstructional1" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            inputmode="decimal"
                                            oninput="calculateFiduciaryRow(1)"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="fiduciaryDeductionsContainer1" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addFiduciaryDeduction(1)" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            <span>Add Deduction</span>
                                            </button>
                                        <div id="fiduciary1DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="fiduciary1DeductionTotalAmount" class="text-maroon">₱0.00</span>
                                            </div>
                                            <div class="pt-2 mt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                                    <span id="fiduciary1TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>

                            <!-- Computer Fee -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <input 
                                            type="text" 
                                            id="fiduciaryItem2" 
                                            name="fiduciaryItem2" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            placeholder="Enter item name"
                                        >
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="fiduciaryInstructional2" 
                                            name="fiduciaryInstructional2" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            inputmode="decimal"
                                            oninput="calculateFiduciaryRow(2)"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="fiduciaryDeductionsContainer2" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addFiduciaryDeduction(2)" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            <span>Add Deduction</span>
                                        </button>
                                        <div id="fiduciary2DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="fiduciary2DeductionTotalAmount" class="text-maroon">₱0.00</span>
                                            </div>
                                            <div class="pt-2 mt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                                    <span id="fiduciary2TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Computer lab -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <input 
                                            type="text" 
                                            id="fiduciaryItem3" 
                                            name="fiduciaryItem3" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            placeholder="Enter item name"
                                        >
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="fiduciaryInstructional3" 
                                            name="fiduciaryInstructional3" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            inputmode="decimal"
                                            oninput="calculateFiduciaryRow(3)"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="fiduciaryDeductionsContainer3" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addFiduciaryDeduction(3)" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                            <span>Add Deduction</span>
                                    </button>
                                        <div id="fiduciary3DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="fiduciary3DeductionTotalAmount" class="text-maroon">₱0.00</span>
                                            </div>
                                            <div class="pt-2 mt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                                    <span id="fiduciary3TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Internet Fee -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <input 
                                            type="text" 
                                            id="fiduciaryItem4" 
                                            name="fiduciaryItem4" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            placeholder="Enter item name"
                                        >
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="fiduciaryInstructional4" 
                                            name="fiduciaryInstructional4" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            inputmode="decimal"
                                            oninput="calculateFiduciaryRow(4)"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="fiduciaryDeductionsContainer4" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addFiduciaryDeduction(4)" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            <span>Add Deduction</span>
                                        </button>
                                        <div id="fiduciary4DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="fiduciary4DeductionTotalAmount" class="text-maroon">₱0.00</span>
                                            </div>
                                            <div class="pt-2 mt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                                    <span id="fiduciary4TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CCNA -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <input 
                                            type="text" 
                                            id="fiduciaryItem5" 
                                            name="fiduciaryItem5" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            placeholder="Enter item name"
                                        >
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="fiduciaryInstructional5" 
                                            name="fiduciaryInstructional5" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            inputmode="decimal"
                                            oninput="calculateFiduciaryRow(5)"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="fiduciaryDeductionsContainer5" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addFiduciaryDeduction(5)" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                        </svg>
                                            <span>Add Deduction</span>
                                        </button>
                                        <div id="fiduciary5DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="fiduciary5DeductionTotalAmount" class="text-maroon">₱0.00</span>
                                            </div>
                                            <div class="pt-2 mt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                                    <span id="fiduciary5TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                </div>
                
                            <!-- Development Fee -->
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-center gap-6 mb-3">
                                    <div class="flex-1 min-w-[200px]">
                                        <input 
                                            type="text" 
                                            id="fiduciaryItem6" 
                                            name="fiduciaryItem6" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            placeholder="Enter item name"
                                        >
                        </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="fiduciaryInstructional6" 
                                            name="fiduciaryInstructional6" 
                                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            inputmode="decimal"
                                            oninput="calculateFiduciaryRow(6)"
                                        >
                                    </div>
                                    <div class="flex-1">
                                        <div id="fiduciaryDeductionsContainer6" class="space-y-2">
                                            <!-- Deductions will be added here dynamically -->
                                        </div>
                                        <button 
                                            type="button"
                                            onclick="addFiduciaryDeduction(6)" 
                                            class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                                            <span>Add Deduction</span>
                        </button>
                                        <div id="fiduciary6DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                            <div class="pt-2 border-t border-gray-300">
                                                <span class="text-gray-600">Sub-total: </span>
                                                <span id="fiduciary6DeductionTotalAmount" class="text-maroon">₱0.00</span>
                                            </div>
                                            <div class="pt-2 mt-2">
                                                <div class="flex items-center justify-between">
                                                    <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                                    <span id="fiduciary6TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        
                        <!-- Add Entry Button -->
                        <div class="mt-4 mb-4">
                            <button 
                                type="button"
                                onclick="addFiduciaryEntry()" 
                                class="px-6 py-3 text-sm font-semibold bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span>Add Entry</span>
                            </button>
                        </div>
                            
                            <!-- Total Row for Fiduciary Fund -->
                            <div class="flex items-center gap-6 mt-4 pt-3 border-t-2 border-gray-400">
                                <div class="flex-1 min-w-[200px]">
                                    <span class="text-sm font-bold text-gray-900">Total</span>
                                </div>
                                <div class="w-48">
                                    <input 
                                        type="text" 
                                        id="fiduciaryTotal50" 
                                        class="w-full px-4 py-3 border border-gray-400 rounded-lg bg-gray-100 font-bold text-gray-900 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                        readonly
                                    >
                                </div>
                                <div class="flex-1">
                                    <input 
                                        type="text" 
                                        id="fiduciaryTotalDeduction" 
                                        class="w-full px-4 py-3 border border-gray-400 rounded-lg bg-gray-100 font-bold text-gray-900 focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                        readonly
                                    >
                                </div>
                            </div>
                
                            <!-- Total Amount and Overall Total Section -->
                            <div class="mt-4 space-y-3">
                                <!-- Total Amount (without additional) -->
                                <div id="totalAmountRow" class="flex items-center gap-6 pt-3 border-t-2 border-gray-400">
                                    <div class="flex-1 min-w-[200px]">
                                        <span id="totalAmountLabel" class="text-lg font-bold text-gray-900">Total Amount</span>
                                    </div>
                                    <div class="w-48">
                                        <span class="text-sm font-semibold text-gray-700">-</span>
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-gray-700">-</span>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="totalAmountBudget" 
                                            class="w-full px-4 py-3 border-2 border-gray-400 rounded-lg bg-gray-100 font-bold text-gray-900 focus:ring-2 focus:ring-gray-500 focus:border-gray-500 outline-none transition"
                                            readonly
                                        >
                                    </div>
                                </div>
                                
                                <!-- Additional Amount Display (if present) -->
                                <div id="additionalAmountDisplayRow" class="flex items-center gap-6 hidden">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-md font-semibold text-amber-700">Additional Amount</span>
                                    </div>
                                    <div class="w-48">
                                        <span class="text-sm font-semibold text-gray-700">-</span>
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-gray-700">-</span>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="additionalAmountDisplay" 
                                            class="w-full px-4 py-3 border-2 border-amber-300 rounded-lg bg-amber-50 font-bold text-amber-900 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition"
                                            readonly
                                        >
                                    </div>
                                </div>
                                
                                <!-- Overall Total (with additional) -->
                                <div id="overallTotalRow" class="flex items-center gap-6 pt-3 border-t-2 border-maroon hidden">
                                    <div class="flex-1 min-w-[200px]">
                                        <span class="text-lg font-bold text-maroon">Overall Total</span>
                                    </div>
                                    <div class="w-48">
                                        <span class="text-sm font-semibold text-gray-700">-</span>
                                    </div>
                                    <div class="flex-1">
                                        <span class="text-sm font-semibold text-gray-700">-</span>
                                    </div>
                                    <div class="w-48">
                                        <input 
                                            type="text" 
                                            id="overallTotalBudgetAllocation" 
                                            class="w-full px-4 py-3 border-2 border-maroon rounded-lg bg-maroon bg-opacity-10 font-bold text-maroon focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                                            readonly
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-10 flex justify-center gap-4 relative z-10">
                        <button 
                            type="button"
                            onclick="generateSummary()" 
                            class="px-10 py-4 bg-gradient-to-r from-maroon to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5 flex items-center gap-3 cursor-pointer"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Generate Summary
                        </button>
                        <button 
                            type="button"
                            onclick="clearAllocationData(); return false;" 
                            class="px-10 py-4 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-bold text-lg shadow-xl hover:shadow-2xl transform hover:-translate-y-0.5 flex items-center gap-3 cursor-pointer"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Clear Data
                        </button>
                    </div>

                    <!-- Summary/Receipt Section -->
                    <div id="summarySection" class="mt-8 hidden">
                        <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-8">
                            <div class="text-center mb-6">
                                <h2 class="text-2xl font-bold text-maroon mb-2">Budget Allocation Summary</h2>
                                <p class="text-sm text-gray-600" id="summaryDate"></p>
                                <p class="text-sm text-gray-600" id="summaryDepartment"></p>
                            </div>
                            
                            <!-- Summary Header Info -->
                            <div class="grid grid-cols-3 gap-4 mb-6 pb-4 border-b border-gray-300" id="summaryHeaderInfo">
                                <!-- Number of Students - HIDDEN as per requirements -->
                                <div id="summaryStudentsDiv" style="display: none;">
                                    <p class="text-xs text-gray-500 mb-1">Number of Students</p>
                                    <p class="text-sm font-semibold text-gray-900" id="summaryStudents"></p>
                                </div>
                                <div id="summaryTotalTuitionDiv">
                                    <p class="text-xs text-gray-500 mb-1">Total Tuition Fee</p>
                                    <p class="text-sm font-semibold text-gray-900" id="summaryTotalTuition"></p>
                                </div>
                                <div id="summaryInstructionalDiv">
                                    <p class="text-xs text-gray-500 mb-1">50% Instructional</p>
                                    <p class="text-sm font-semibold text-gray-900" id="summaryInstructional"></p>
                                </div>
                                <!-- Office-specific: Allocate Budget -->
                                <div id="summaryBudgetAllocatedDiv" style="display: none;">
                                    <p class="text-xs text-gray-500 mb-1">Allocate Budget</p>
                                    <p class="text-sm font-semibold text-gray-900" id="summaryBudgetAllocated"></p>
                                </div>
                            </div>
                            
                            <!-- Non-Fiduciary Fund Summary -->
                            <div class="mb-6" id="nonFiduciarySummarySection">
                                <h3 class="text-lg font-bold text-gray-800 mb-4">Non-Fiduciary Fund</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-300">
                                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Instructional</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Percent</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">50%</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Deductions</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Budget Allocation</th>
                                            </tr>
                                        </thead>
                                        <tbody id="nonFiduciarySummaryBody">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="border-t-2 border-gray-400">
                                            <tr class="font-bold">
                                                <td class="py-2 px-3">Total</td>
                                                <td class="text-right py-2 px-3" id="summaryNonFiduciaryTotalPercent"></td>
                                                <td class="text-right py-2 px-3" id="summaryNonFiduciaryTotal50"></td>
                                                <td class="text-right py-2 px-3" id="summaryNonFiduciaryTotalDeduction"></td>
                                                <td class="py-2 px-3">-</td>
                                                <td class="text-right py-2 px-3" id="summaryNonFiduciaryTotalBudget"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Fiduciary Fund Summary -->
                            <div class="mb-6" id="fiduciarySummarySection">
                                <h3 class="text-lg font-bold text-gray-800 mb-4">Fiduciary Fund</h3>
                                <div class="overflow-x-auto" id="fiduciarySummaryTableContainer">
                                    <table class="w-full text-sm" id="fiduciarySummaryTable">
                                        <thead>
                                            <tr class="border-b border-gray-300">
                                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Fiduciary</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Budget Collected</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Deductions</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Total Budget</th>
                                            </tr>
                                        </thead>
                                        <tbody id="fiduciarySummaryBody">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="border-t-2 border-gray-400" id="fiduciarySummaryFooter">
                                            <tr class="font-bold">
                                                <td class="py-2 px-3">Total</td>
                                                <td class="text-right py-2 px-3" id="summaryFiduciaryTotal50"></td>
                                                <td class="text-right py-2 px-3" id="summaryFiduciaryTotalDeduction"></td>
                                                <td class="py-2 px-3">-</td>
                                                <td class="text-right py-2 px-3" id="summaryFiduciaryTotalBudget"></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Total Amount / Overall Total Summary -->
                            <div class="mt-6 pt-6 border-t-2 border-maroon">
                                <!-- Total Amount (shown when additional amount exists) -->
                                <div class="flex items-center justify-between mb-3" id="summaryTotalAmountRow" style="display: none;">
                                    <span class="text-lg font-semibold text-gray-700">Total Amount</span>
                                    <span class="text-lg font-semibold text-gray-700" id="summaryTotalAmount">₱0.00</span>
                                </div>
                                <!-- Additional Amount (shown when additional amount exists) -->
                                <div class="flex items-center justify-between mb-3" id="summaryAdditionalAmountRow" style="display: none;">
                                    <span class="text-lg font-semibold text-amber-600">Additional Amount</span>
                                    <span class="text-lg font-semibold text-amber-600" id="summaryAdditionalAmountValue">₱0.00</span>
                                </div>
                                <!-- Overall Total (always shown, label changes based on additional amount) -->
                                <div class="flex items-center justify-between">
                                    <span class="text-xl font-bold text-maroon" id="summaryOverallTotalLabel">Overall Total</span>
                                    <span class="text-xl font-bold text-maroon" id="summaryOverallTotal">₱0.00</span>
                                </div>
                            </div>
                            
                            <!-- Deduction Breakdown by Type -->
                            <div class="mt-6 pt-6 border-t-2 border-gray-300" id="deductionBreakdownSection">
                                <h3 class="text-lg font-bold text-gray-800 mb-4">Total Deduction Breakdown by Type</h3>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm">
                                        <thead>
                                            <tr class="border-b border-gray-300 bg-gray-100">
                                                <th class="text-left py-2 px-3 font-semibold text-gray-700">Deduction Type</th>
                                                <th class="text-right py-2 px-3 font-semibold text-gray-700">Total Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody id="deductionBreakdownBody">
                                            <!-- Will be populated by JavaScript -->
                                        </tbody>
                                        <tfoot class="border-t-2 border-gray-400">
                                            <tr class="font-bold">
                                                <td class="py-2 px-3">Grand Total Deductions</td>
                                                <td class="text-right py-2 px-3" id="deductionBreakdownGrandTotal">₱0.00</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="mt-6 flex justify-center gap-4">
                                <button 
                                    id="saveAllocationBtn"
                                    onclick="confirmAndSaveAllocation()" 
                                    class="px-6 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all font-semibold shadow-lg flex items-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Save Allocation
                                </button>
                                <button 
                                    onclick="downloadSummary()" 
                                    class="px-6 py-2 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg flex items-center gap-2"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    Download Summary
                                </button>
                                <button 
                                    onclick="closeSummary()" 
                                    class="px-6 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors font-semibold"
                                >
                                    Close
                        </button>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <div class="mt-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <p class="text-sm text-blue-800">
                            <strong>Note:</strong> When you enter the total tuition fee, it will be automatically divided into 2 (50% Instructional / 50% Administration). 
                            The 50% Instructional amount will automatically appear in the output box, and it will be further divided into 4 equal parts for the breakdown categories above.
                        </p>
                        </div>
                </div>
                    </div>
                </div>
        </div>
    </div>
    
    <script>
    function formatNumber(num) {
        // Remove any existing commas, peso signs and parse the number
        const number = parseFloat(num.toString().replace(/[₱,]/g, '')) || 0;
        // Format with peso sign, commas and 2 decimal places
        return '₱' + number.toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatNumberInput(num) {
        // Remove any existing commas and peso signs
        let cleanValue = num.toString().replace(/[₱,]/g, '');
        
        // Allow decimal points
        if (cleanValue === '' || cleanValue === '.') {
            return cleanValue;
        }
        
        // Check if it's a valid number (including decimals)
        if (!isNaN(cleanValue) || cleanValue.endsWith('.')) {
            // If it has decimals, preserve them
            if (cleanValue.includes('.')) {
                const parts = cleanValue.split('.');
                const integerPart = parts[0] === '' ? '0' : parts[0];
                const decimalPart = parts[1] || '';
                const formattedInteger = integerPart === '' ? '' : parseFloat(integerPart || 0).toLocaleString('en-US');
                return formattedInteger + (decimalPart !== '' ? '.' + decimalPart : '');
    } else { 
                // No decimal point, format as integer
                const number = parseFloat(cleanValue) || 0;
                return number.toLocaleString('en-US');
            }
        }
        return cleanValue;
    }
    
    // Percentage Sync Functions
    function savePercentagesToStorage() {
        const fiscalYearSelect = document.getElementById('fiscalYearSelect');
        const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
        
        const percentages = {
            facultyStaff: parseFloat(document.getElementById('facultyStaffPercent').value) || 0,
            curriculum: parseFloat(document.getElementById('curriculumPercent').value) || 0,
            student: parseFloat(document.getElementById('studentPercent').value) || 0,
            facilities: parseFloat(document.getElementById('facilitiesPercent').value) || 0
        };
        localStorage.setItem(`nonFiduciaryPercentages_${fiscalYear}`, JSON.stringify(percentages));
    }
    
    function loadPercentagesFromStorage() {
        const fiscalYearSelect = document.getElementById('fiscalYearSelect');
        const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
        
        const stored = localStorage.getItem(`nonFiduciaryPercentages_${fiscalYear}`);
        if (stored) {
            try {
                const percentages = JSON.parse(stored);
                if (percentages.facultyStaff || percentages.curriculum || percentages.student || percentages.facilities) {
                    document.getElementById('facultyStaffPercent').value = percentages.facultyStaff || '';
                    document.getElementById('curriculumPercent').value = percentages.curriculum || '';
                    document.getElementById('studentPercent').value = percentages.student || '';
                    document.getElementById('facilitiesPercent').value = percentages.facilities || '';
                    
                    // Trigger calculations
                    calculateBreakdownRow('facultyStaff');
                    calculateBreakdownRow('curriculum');
                    calculateBreakdownRow('student');
                    calculateBreakdownRow('facilities');
                    calculateNonFiduciaryTotals();
                    
                    return true; // Percentages were loaded
                }
            } catch (e) {
                console.error('Error loading percentages from storage:', e);
            }
        }
        return false; // No percentages loaded
    }
    
    function clearPercentagesFromStorage() {
        const fiscalYearSelect = document.getElementById('fiscalYearSelect');
        const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
        
        localStorage.removeItem(`nonFiduciaryPercentages_${fiscalYear}`);
        // Clear the input fields
        document.getElementById('facultyStaffPercent').value = '';
        document.getElementById('curriculumPercent').value = '';
        document.getElementById('studentPercent').value = '';
        document.getElementById('facilitiesPercent').value = '';
        // Recalculate
        calculateBreakdownRow('facultyStaff');
        calculateBreakdownRow('curriculum');
        calculateBreakdownRow('student');
        calculateBreakdownRow('facilities');
        calculateNonFiduciaryTotals();
    }

    function calculateInstructional() {
        // Get the value and remove commas and peso signs for calculation
        const totalTuitionFeeInput = document.getElementById('totalTuitionFee').value.replace(/[₱,]/g, '');
        const totalTuitionFee = parseFloat(totalTuitionFeeInput) || 0;
        const instructionalAmount = totalTuitionFee * 0.5; // 50% of total
        
        // Format the instructional amount with peso sign, commas and 2 decimal places
        document.getElementById('instructionalAmount').value = formatNumber(instructionalAmount);
        
        // Recalculate all breakdown rows
        calculateBreakdownRow('facultyStaff');
        calculateBreakdownRow('curriculum');
        calculateBreakdownRow('student');
        calculateBreakdownRow('facilities');
        
        // Calculate totals
        calculateNonFiduciaryTotals();
        calculateFiduciaryTotals();
        
        // Update summary in real-time if visible
        updateSummaryIfVisible();
    }

    // Track deduction counts for each category
    let deductionCounts = {
        facultyStaff: 0,
        curriculum: 0,
        student: 0,
        facilities: 0
    };
    
    let fiduciaryDeductionCounts = {
        1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0
    };

    function addDeduction(category) {
        const container = document.getElementById(category + 'DeductionsContainer');
        if (!container) return;
        
        deductionCounts[category] = (deductionCounts[category] || 0) + 1;
        const deductionId = deductionCounts[category];
        const deductionRowId = category + 'Deduction' + deductionId;
        
        const deductionRow = document.createElement('div');
        deductionRow.className = 'flex flex-wrap items-center gap-2';
        deductionRow.id = 'deductionRow_' + deductionRowId;
        deductionRow.innerHTML = `
            <div class="flex-1 min-w-[100px]">
                <input 
                    type="text" 
                    id="${deductionRowId}_amount" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition text-sm"
                    placeholder="Amount"
                    inputmode="decimal"
                    oninput="updateDeductionTotal('${category}')"
                >
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <select 
                    id="${deductionRowId}_remarks" 
                    class="w-28 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition bg-white text-sm"
                    onchange="updateDeductionIndicator('${deductionRowId}'); updateDeductionTotal('${category}')"
                >
                    <option value="">-- Select --</option>
                    <option value="Honoraria Overload">Honoraria Overload</option>
                    <option value="Part-time">Part-time</option>
                    <option value="Electricity">Electricity</option>
                    <option value="COS">COS</option>
                    <option value="Security">Security</option>
                    <option value="Water">Water</option>
                    <option value="Labor & Wages">Labor & Wages</option>
                    <option value="+ Custom">+ Custom</option>
                </select>
                <div class="w-6 flex items-center justify-center" id="${deductionRowId}_indicator">
                    <!-- Green checkmark will appear here -->
                </div>
                <button 
                    type="button"
                    onclick="removeDeduction('${category}', '${deductionRowId}')" 
                    class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-xs"
                    title="Remove deduction"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        container.appendChild(deductionRow);
        
        // Add event listeners for peso formatting
        setupDeductionInputListeners(deductionRowId + '_amount', category);
    }

    function addFiduciaryDeduction(rowNumber) {
        const container = document.getElementById('fiduciaryDeductionsContainer' + rowNumber);
        if (!container) return;
        
        fiduciaryDeductionCounts[rowNumber] = (fiduciaryDeductionCounts[rowNumber] || 0) + 1;
        const deductionId = fiduciaryDeductionCounts[rowNumber];
        const deductionRowId = 'fiduciary' + rowNumber + 'Deduction' + deductionId;
        
        const deductionRow = document.createElement('div');
        deductionRow.className = 'flex flex-wrap items-center gap-2';
        deductionRow.id = 'deductionRow_' + deductionRowId;
        deductionRow.innerHTML = `
            <div class="flex-1 min-w-[100px]">
                <input 
                    type="text" 
                    id="${deductionRowId}_amount" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition text-sm"
                    placeholder="Amount"
                    inputmode="decimal"
                    oninput="updateFiduciaryDeductionTotal(${rowNumber})"
                >
            </div>
            <div class="flex items-center gap-1 shrink-0">
                <select 
                    id="${deductionRowId}_remarks" 
                    class="w-28 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition bg-white text-sm"
                    onchange="updateDeductionIndicator('${deductionRowId}'); updateFiduciaryDeductionTotal(${rowNumber})"
                >
                    <option value="">-- Select --</option>
                    <option value="Honoraria Overload">Honoraria Overload</option>
                    <option value="Part-time">Part-time</option>
                    <option value="Electricity">Electricity</option>
                    <option value="COS">COS</option>
                    <option value="Security">Security</option>
                    <option value="Water">Water</option>
                    <option value="Labor & Wages">Labor & Wages</option>
                    <option value="+ Custom">+ Custom</option>
                </select>
                <div class="w-6 flex items-center justify-center" id="${deductionRowId}_indicator">
                    <!-- Green checkmark will appear here -->
                </div>
                <button 
                    type="button"
                    onclick="removeFiduciaryDeduction(${rowNumber}, '${deductionRowId}')" 
                    class="px-2 py-2 bg-red-500 text-white rounded hover:bg-red-600 transition-colors text-xs"
                    title="Remove deduction"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
        
        container.appendChild(deductionRow);
        
        // Add event listeners for peso formatting
        setupFiduciaryDeductionInputListeners(deductionRowId + '_amount', rowNumber);
    }

    function removeDeduction(category, deductionRowId) {
        const row = document.getElementById('deductionRow_' + deductionRowId);
        if (row) {
            row.remove();
            updateDeductionTotal(category);
            // Auto-save to localStorage
            if (window.saveFormDataToLocalStorage) {
                window.saveFormDataToLocalStorage();
            }
        }
    }

    function removeFiduciaryDeduction(rowNumber, deductionRowId) {
        const row = document.getElementById('deductionRow_' + deductionRowId);
        if (row) {
            row.remove();
            updateFiduciaryDeductionTotal(rowNumber);
            // Auto-save to localStorage
            if (window.saveFormDataToLocalStorage) {
                window.saveFormDataToLocalStorage();
            }
        }
    }

    function updateDeductionIndicator(deductionRowId) {
        const remarksSelect = document.getElementById(deductionRowId + '_remarks');
        const indicator = document.getElementById(deductionRowId + '_indicator');
        
        // Check if "+ Custom" was selected
        if (remarksSelect && remarksSelect.value === '+ Custom') {
            // Store the select element ID for later use
            window.currentCustomDeductionSelectId = remarksSelect.id;
            // Open custom deduction modal
            openCustomDeductionModal();
            // Reset select to empty
            remarksSelect.value = '';
            return;
        }
        
        if (remarksSelect && indicator) {
            if (remarksSelect.value) {
                indicator.innerHTML = `
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                `;
            } else {
                indicator.innerHTML = '';
            }
        }
        
        // Auto-save to localStorage when remarks change
        if (window.saveFormDataToLocalStorage) {
            window.saveFormDataToLocalStorage();
        }
    }

    // Custom Deduction Modal Functions
    let currentCustomDeductionSelectId = null;

    function openCustomDeductionModal() {
        const modal = document.getElementById('customDeductionModal');
        const input = document.getElementById('customDeductionInput');
        if (modal && input) {
            modal.classList.remove('hidden');
            input.value = '';
            input.focus();
        }
    }

    function closeCustomDeductionModal() {
        const modal = document.getElementById('customDeductionModal');
        if (modal) {
            modal.classList.add('hidden');
        }
        currentCustomDeductionSelectId = null;
    }

    function confirmCustomDeduction() {
        const input = document.getElementById('customDeductionInput');
        const customValue = input ? input.value.trim() : '';
        
        if (!customValue) {
            alert('Please enter a custom deduction name.');
            return;
        }
        
        if (currentCustomDeductionSelectId) {
            const select = document.getElementById(currentCustomDeductionSelectId);
            if (select) {
                // Check if this custom option already exists
                let optionExists = false;
                for (let i = 0; i < select.options.length; i++) {
                    if (select.options[i].value === customValue) {
                        optionExists = true;
                        break;
                    }
                }
                
                // Add new option if it doesn't exist
                if (!optionExists) {
                    const newOption = document.createElement('option');
                    newOption.value = customValue;
                    newOption.textContent = customValue;
                    // Insert before the "+ Custom" option
                    const customOption = Array.from(select.options).find(opt => opt.value === '+ Custom');
                    if (customOption) {
                        select.insertBefore(newOption, customOption);
                    } else {
                        select.appendChild(newOption);
                    }
                }
                
                // Set the custom value as selected
                select.value = customValue;
                
                // Trigger the indicator update
                const deductionRowId = currentCustomDeductionSelectId.replace('_remarks', '');
                const indicator = document.getElementById(deductionRowId + '_indicator');
                if (indicator) {
                    indicator.innerHTML = `
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    `;
                }
                
                // Auto-save to localStorage
                if (window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            }
        }
        
        closeCustomDeductionModal();
    }

    // Allow Enter key to confirm
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure summary section is hidden on page load
        const summarySection = document.getElementById('summarySection');
        if (summarySection) {
            summarySection.classList.add('hidden');
        }
        
        const customInput = document.getElementById('customDeductionInput');
        if (customInput) {
            customInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    confirmCustomDeduction();
                }
            });
        }
    });


    // Helper function to update summary if it's visible
    function updateSummaryIfVisible() {
        const summarySection = document.getElementById('summarySection');
        if (summarySection && !summarySection.classList.contains('hidden')) {
            // Use setTimeout to debounce rapid updates
            if (window.summaryUpdateTimeout) {
                clearTimeout(window.summaryUpdateTimeout);
            }
            window.summaryUpdateTimeout = setTimeout(() => {
                saveAndDisplaySummary(false);
            }, 300); // 300ms debounce
        }
    }

    function updateDeductionTotal(category) {
        const container = document.getElementById(category + 'DeductionsContainer');
        if (!container) return;
        
        let totalDeduction = 0;
        const deductionInputs = container.querySelectorAll('[id$="_amount"]');
        const deductionCount = deductionInputs.length;
        
        deductionInputs.forEach(input => {
            const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
            totalDeduction += value;
        });
        
        // Show/hide semi-total
        const semiTotalDiv = document.getElementById(category + 'DeductionTotal');
        const semiTotalAmount = document.getElementById(category + 'DeductionTotalAmount');
        if (semiTotalDiv && semiTotalAmount) {
            if (deductionCount > 0 && totalDeduction > 0) {
                semiTotalDiv.classList.remove('hidden');
                semiTotalAmount.textContent = formatNumber(totalDeduction);
                } else {
                semiTotalDiv.classList.add('hidden');
            }
        }
        
        // Calculate Budget Allocation
        const instructionalField = document.getElementById(category + 'Instructional');
        const instructionalValue = parseFloat(instructionalField ? instructionalField.value.replace(/[₱,]/g, '') : '0') || 0;
        const budgetAllocation = instructionalValue - totalDeduction;
        
        // Update Budget Allocation field with color
        const budgetAllocationField = document.getElementById(category + 'BudgetAllocation');
        if (budgetAllocationField) {
            budgetAllocationField.value = formatNumber(budgetAllocation);
            // Color red if negative
            if (budgetAllocation < 0) {
                budgetAllocationField.classList.add('text-red-600', 'font-bold');
                budgetAllocationField.classList.remove('text-gray-900');
            } else {
                budgetAllocationField.classList.remove('text-red-600', 'font-bold');
                budgetAllocationField.classList.add('text-gray-900');
            }
        }
        
        // Update totals
        calculateNonFiduciaryTotals();
        calculateOverallTotal();
        
        // Update summary in real-time if visible
        updateSummaryIfVisible();
    }

    function updateFiduciaryDeductionTotal(rowNumber) {
        const container = document.getElementById('fiduciaryDeductionsContainer' + rowNumber);
        if (!container) return;
        
        let totalDeduction = 0;
        const deductionInputs = container.querySelectorAll('[id$="_amount"]');
        const deductionCount = deductionInputs.length;
        
        deductionInputs.forEach(input => {
            const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
            totalDeduction += value;
        });
        
        // Get instructional amount
        const instructionalField = document.getElementById('fiduciaryInstructional' + rowNumber);
        const instructionalValue = parseFloat(instructionalField ? instructionalField.value.replace(/[₱,]/g, '') : '0') || 0;
        
        // Calculate Total Budget (instructional - deductions)
        const totalBudget = instructionalValue - totalDeduction;
        
        // Show/hide semi-total and total budget
        const semiTotalDiv = document.getElementById('fiduciary' + rowNumber + 'DeductionTotal');
        const semiTotalAmount = document.getElementById('fiduciary' + rowNumber + 'DeductionTotalAmount');
        const totalBudgetSpan = document.getElementById('fiduciary' + rowNumber + 'TotalBudget');
        
        if (semiTotalDiv && semiTotalAmount) {
            if (deductionCount > 0 && totalDeduction > 0) {
                semiTotalDiv.classList.remove('hidden');
                semiTotalAmount.textContent = formatNumber(totalDeduction);
                
                // Show and update Total Budget
                if (totalBudgetSpan) {
                    totalBudgetSpan.textContent = formatNumber(totalBudget);
                }
            } else if (instructionalValue > 0) {
                // Show total budget even if no deductions
                semiTotalDiv.classList.remove('hidden');
                semiTotalAmount.textContent = formatNumber(0);
                if (totalBudgetSpan) {
                    totalBudgetSpan.textContent = formatNumber(totalBudget);
                }
            } else {
                semiTotalDiv.classList.add('hidden');
            }
        }
        
        // Update totals
        calculateFiduciaryTotals();
        calculateOverallTotal();
        
        // Auto-save to localStorage
        if (window.saveFormDataToLocalStorage) {
            window.saveFormDataToLocalStorage();
        }
        
        // Update summary in real-time if visible
        updateSummaryIfVisible();
    }

    function calculateBreakdownRow(category) {
        // Get the main instructional amount (50% of total tuition fee)
        const instructionalAmountEl = document.getElementById('instructionalAmount');
        if (!instructionalAmountEl) return;
        
        const instructionalAmountInput = instructionalAmountEl.value.replace(/[₱,]/g, '');
        const instructionalAmount = parseFloat(instructionalAmountInput) || 0;
        
        // Get percent value
        const percentInput = document.getElementById(category + 'Percent');
        if (!percentInput) return;
        
        const percentValue = parseFloat(percentInput.value.replace(/%/g, '')) || 0;
        
        // Calculate 50% (Instructional) = instructionalAmount * (percent / 100)
        const instructional50Percent = instructionalAmount * (percentValue / 100);
        
        // Update instructional field
        const instructionalField = document.getElementById(category + 'Instructional');
        if (instructionalField) {
            instructionalField.value = formatNumber(instructional50Percent);
        }
        
        // Recalculate deduction total
        updateDeductionTotal(category);
        
        // Note: updateDeductionTotal already calls updateSummaryIfVisible()
    }
    
    function calculateNonFiduciaryTotals() {
        const categories = ['facultyStaff', 'curriculum', 'student', 'facilities'];
        let totalPercent = 0;
        let total50 = 0;
        let totalDeduction = 0;
        let totalBudgetAllocation = 0;
        
        categories.forEach(category => {
            // Sum percent
            const percentInput = document.getElementById(category + 'Percent');
            const percentValue = parseFloat(percentInput.value.replace(/%/g, '')) || 0;
            totalPercent += percentValue;
            
            // Sum 50%
            const instructionalField = document.getElementById(category + 'Instructional');
            const instructionalValue = parseFloat(instructionalField ? instructionalField.value.replace(/[₱,]/g, '') : '0') || 0;
            total50 += instructionalValue;
            
            // Sum all deductions from container
            const container = document.getElementById(category + 'DeductionsContainer');
            if (container) {
                const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                deductionInputs.forEach(input => {
                    const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                    totalDeduction += value;
                });
            }
            
            // Sum budget allocation
            const budgetAllocationField = document.getElementById(category + 'BudgetAllocation');
            const budgetAllocationValue = parseFloat(budgetAllocationField ? budgetAllocationField.value.replace(/[₱,]/g, '') : '0') || 0;
            totalBudgetAllocation += budgetAllocationValue;
        });
        
        // Update total row fields
        const totalInstructionalField = document.getElementById('nonFiduciaryTotalInstructional');
        if (totalInstructionalField) {
            totalInstructionalField.value = formatNumber(total50);
        }
        
        const totalDeductionsField = document.getElementById('nonFiduciaryTotalDeductions');
        if (totalDeductionsField) {
            totalDeductionsField.textContent = formatNumber(totalDeduction);
        }
        
        // Update summary in real-time if visible
        updateSummaryIfVisible();
    }

    function calculateFiduciaryRow(rowNumber) {
        // Recalculate deduction total for this row
        updateFiduciaryDeductionTotal(rowNumber);
    }
    
    // Track the current number of fiduciary entries (starts at 6)
    let fiduciaryEntryCount = 6;
    
    function addFiduciaryEntry() {
        fiduciaryEntryCount++;
        const rowNumber = fiduciaryEntryCount;
        
        const container = document.getElementById('fiduciaryFundRows');
        if (!container) return;
        
        // Create new entry HTML
        const newEntry = document.createElement('div');
        newEntry.className = 'border border-gray-200 rounded-lg p-4';
        newEntry.id = `fiduciaryRow${rowNumber}`;
        newEntry.innerHTML = `
            <div class="flex items-center gap-6 mb-3">
                <div class="flex-1 min-w-[200px]">
                    <input 
                        type="text" 
                        id="fiduciaryItem${rowNumber}" 
                        name="fiduciaryItem${rowNumber}" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                        placeholder="Enter item name"
                    >
                </div>
                <div class="w-48">
                    <input 
                        type="text" 
                        id="fiduciaryInstructional${rowNumber}" 
                        name="fiduciaryInstructional${rowNumber}" 
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition"
                        inputmode="decimal"
                        oninput="calculateFiduciaryRow(${rowNumber})"
                    >
                </div>
                <div class="flex-1">
                    <div id="fiduciaryDeductionsContainer${rowNumber}" class="space-y-2">
                        <!-- Deductions will be added here dynamically -->
                    </div>
                    <div class="flex items-center gap-2 mt-2">
                        <button 
                            type="button"
                            onclick="addFiduciaryDeduction(${rowNumber})" 
                            class="px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <span>Add Deduction</span>
                        </button>
                        <button 
                            type="button"
                            onclick="removeFiduciaryEntry(${rowNumber})" 
                            class="px-4 py-2 text-sm font-semibold bg-red-600 text-white rounded-lg hover:bg-red-700 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2"
                            title="Remove this entry"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                    <div id="fiduciary${rowNumber}DeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                        <div class="pt-2 border-t border-gray-300">
                            <span class="text-gray-600">Sub-total: </span>
                            <span id="fiduciary${rowNumber}DeductionTotalAmount" class="text-maroon">₱0.00</span>
                        </div>
                        <div class="pt-2 mt-2">
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                <span id="fiduciary${rowNumber}TotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Append before the Add Entry button
        container.appendChild(newEntry);
        
        // Setup input formatting for the new instructional field
        setupFiduciaryInstructionalInput(rowNumber);
        
        // Auto-save after adding entry
        if (window.saveFormDataToLocalStorage) {
            window.saveFormDataToLocalStorage();
        }
    }
    
    function removeFiduciaryEntry(rowNumber) {
        if (rowNumber <= 6) {
            alert('Cannot remove default entries (1-6). You can only remove added entries.');
            return;
        }
        
        if (confirm('Are you sure you want to remove this entry?')) {
            const row = document.getElementById(`fiduciaryRow${rowNumber}`);
            if (row) {
                row.remove();
                calculateFiduciaryTotals();
                
                // Auto-save after removing entry
                if (window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            }
        }
    }
    
    function setupFiduciaryInstructionalInput(rowNumber) {
        const input = document.getElementById(`fiduciaryInstructional${rowNumber}`);
        if (!input) return;
        
        let originalValue = '';
        
        input.addEventListener('focus', function(e) {
            originalValue = e.target.value;
            e.target.value = e.target.value.replace(/[₱,]/g, '');
        });
        
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value === '' || value === '.' || !isNaN(value)) {
                calculateFiduciaryRow(rowNumber);
            } else {
                e.target.value = originalValue.replace(/[₱,]/g, '');
            }
        });
        
        input.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value !== '' && !isNaN(value)) {
                e.target.value = formatNumber(parseFloat(value));
            }
        });
    }

    function setupDeductionInputListeners(inputId, category) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        let originalValue = '';
        
        input.addEventListener('focus', function(e) {
            originalValue = e.target.value;
            e.target.value = e.target.value.replace(/[₱,]/g, '');
        });
        
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value === '' || value === '.' || !isNaN(value)) {
                updateDeductionTotal(category);
            } else {
                const prevValue = originalValue.replace(/[₱,]/g, '');
                e.target.value = prevValue;
            }
        });
        
        input.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value !== '' && !isNaN(value)) {
                e.target.value = formatNumber(parseFloat(value));
                originalValue = e.target.value;
            }
        });
    }

    function setupFiduciaryDeductionInputListeners(inputId, rowNumber) {
        const input = document.getElementById(inputId);
        if (!input) return;
        
        let originalValue = '';
        
        input.addEventListener('focus', function(e) {
            originalValue = e.target.value;
            e.target.value = e.target.value.replace(/[₱,]/g, '');
        });
        
        input.addEventListener('input', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value === '' || value === '.' || !isNaN(value)) {
                updateFiduciaryDeductionTotal(rowNumber);
            } else {
                const prevValue = originalValue.replace(/[₱,]/g, '');
                e.target.value = prevValue;
            }
        });
        
        input.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value !== '' && !isNaN(value)) {
                e.target.value = formatNumber(parseFloat(value));
                originalValue = e.target.value;
            }
        });
    }
    
    function calculateFiduciaryTotals() {
        let total50 = 0;
        let totalDeduction = 0;
        let totalBudget = 0;
        
        // Loop through all fiduciary rows (including dynamically added ones)
        for (let i = 1; i <= fiduciaryEntryCount; i++) {
            // Check if row exists (might have been removed)
            const instructionalField = document.getElementById('fiduciaryInstructional' + i);
            if (!instructionalField) continue;
            
            // Sum 50%
            const instructionalValue = parseFloat(instructionalField.value.replace(/[₱,]/g, '') || '0') || 0;
            total50 += instructionalValue;
            
            // Sum all deductions from container
            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
            let rowDeduction = 0;
            if (container) {
                const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                deductionInputs.forEach(input => {
                    const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                    rowDeduction += value;
                });
            }
            totalDeduction += rowDeduction;
            
            // Calculate total budget for this row (instructional - deductions)
            totalBudget += (instructionalValue - rowDeduction);
        }
        
        // Update total fields
        const total50Field = document.getElementById('fiduciaryTotal50');
        const totalDeductionField = document.getElementById('fiduciaryTotalDeduction');
        
        if (total50Field) {
            total50Field.value = total50 > 0 ? formatNumber(total50) : '';
        }
        if (totalDeductionField) {
            totalDeductionField.value = totalDeduction > 0 ? formatNumber(totalDeduction) : '';
        }
        
        // Calculate overall total
        calculateOverallTotal();
        
        // Update summary in real-time if visible
        updateSummaryIfVisible();
    }

    function calculateOverallTotal() {
        // Sum all budget allocations from both sections
        const categories = ['facultyStaff', 'curriculum', 'student', 'facilities'];
        let nonFiduciaryTotal = 0;
        let fiduciaryTotal = 0;
        
        // Sum Non-Fiduciary budget allocations
        categories.forEach(category => {
            const budgetAllocationField = document.getElementById(category + 'BudgetAllocation');
            const value = parseFloat(budgetAllocationField ? budgetAllocationField.value.replace(/[₱,]/g, '') : '0') || 0;
            nonFiduciaryTotal += value;
        });
        
        // Sum Fiduciary total budgets (instructional - deductions)
        for (let i = 1; i <= 6; i++) {
            const instructionalField = document.getElementById('fiduciaryInstructional' + i);
            const instructionalValue = parseFloat(instructionalField ? instructionalField.value.replace(/[₱,]/g, '') : '0') || 0;
            
            // Sum deductions for this row
            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
            let rowDeduction = 0;
            if (container) {
                const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                deductionInputs.forEach(input => {
                    const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                    rowDeduction += value;
                });
            }
            
            // Total budget = instructional - deductions
            fiduciaryTotal += (instructionalValue - rowDeduction);
        }
        
        // Total Amount (without additional)
        const totalAmount = nonFiduciaryTotal + fiduciaryTotal;
        
        // Get additional amount
        const additionalAmountField = document.getElementById('additionalAmount');
        const additionalAmount = additionalAmountField ? (parseFloat(additionalAmountField.value.replace(/[₱,]/g, '')) || 0) : 0;
        
        // Overall Total (with additional)
        const overallTotal = totalAmount + additionalAmount;
        
        // Update Total Amount field
        const totalAmountField = document.getElementById('totalAmountBudget');
        if (totalAmountField) {
            totalAmountField.value = formatNumber(totalAmount);
            if (totalAmount < 0) {
                totalAmountField.classList.add('text-red-600');
            } else {
                totalAmountField.classList.remove('text-red-600');
            }
        }
        
        // ALWAYS set the overall total field value (used for saving to database)
        const overallTotalField = document.getElementById('overallTotalBudgetAllocation');
        if (overallTotalField) {
            overallTotalField.value = formatNumber(overallTotal);
            if (overallTotal < 0) {
                overallTotalField.classList.add('text-red-600');
            } else {
                overallTotalField.classList.remove('text-red-600');
            }
        }
        
        // Update Additional Amount Display and Overall Total visibility
        const additionalAmountDisplayRow = document.getElementById('additionalAmountDisplayRow');
        const additionalAmountDisplay = document.getElementById('additionalAmountDisplay');
        const overallTotalRow = document.getElementById('overallTotalRow');
        const totalAmountLabel = document.getElementById('totalAmountLabel');
        
        if (additionalAmount > 0) {
            // Show additional amount and overall total rows
            if (additionalAmountDisplayRow) additionalAmountDisplayRow.classList.remove('hidden');
            if (additionalAmountDisplay) additionalAmountDisplay.value = formatNumber(additionalAmount);
            if (overallTotalRow) overallTotalRow.classList.remove('hidden');
            if (totalAmountLabel) totalAmountLabel.textContent = 'Total Amount';
        } else {
            // Hide additional amount and overall total rows, rename Total Amount to Overall Total
            if (additionalAmountDisplayRow) additionalAmountDisplayRow.classList.add('hidden');
            if (overallTotalRow) overallTotalRow.classList.add('hidden');
            if (totalAmountLabel) totalAmountLabel.textContent = 'Overall Total';
        }
    }

    // Format input fields on input
    document.addEventListener('DOMContentLoaded', function() {
        // Setup budget allocated input formatting (for offices)
        const budgetAllocatedInput = document.getElementById('budgetAllocated');
        if (budgetAllocatedInput) {
            let originalValue = '';
            
            budgetAllocatedInput.addEventListener('focus', function(e) {
                originalValue = e.target.value;
                e.target.value = e.target.value.replace(/[₱,]/g, '');
            });
            
            budgetAllocatedInput.addEventListener('input', function(e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value === '' || value === '.' || !isNaN(value)) {
                    // Recalculate office fiduciary row
                    if (window.calculateOfficeFiduciaryRow) {
                        window.calculateOfficeFiduciaryRow(0);
                    }
                    // Valid input - auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                } else {
                    e.target.value = originalValue.replace(/[₱,]/g, '');
                }
            });
            
            budgetAllocatedInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value !== '' && !isNaN(value)) {
                    e.target.value = formatNumber(parseFloat(value));
                    // Recalculate office fiduciary row
                    if (window.calculateOfficeFiduciaryRow) {
                        window.calculateOfficeFiduciaryRow(0);
                    }
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                }
            });
        }
        
        const totalTuitionFeeInput = document.getElementById('totalTuitionFee');
        
        totalTuitionFeeInput.addEventListener('input', function(e) {
            const value = e.target.value;
            
            // Remove commas and peso signs for calculation
            const numValue = value.replace(/[₱,]/g, '');
            
            // Allow empty, decimal point, or valid number
            if (numValue === '' || numValue === '.' || !isNaN(numValue)) {
                // Just allow the input, format on blur
                // Calculate instructional amount
                calculateInstructional();
                // Auto-save to localStorage
                if (window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            } else {
                // Invalid input, revert to previous valid value
                e.target.value = e.target.value.slice(0, -1);
            }
        });
        
        // Format on blur
        totalTuitionFeeInput.addEventListener('blur', function(e) {
            const value = e.target.value.replace(/[₱,]/g, '');
            if (value !== '' && !isNaN(value)) {
                e.target.value = formatNumber(parseFloat(value));
            }
            // Auto-save to localStorage
            if (window.saveFormDataToLocalStorage) {
                window.saveFormDataToLocalStorage();
            }
        });

        const numStudentsInput = document.getElementById('numStudents');
        
        numStudentsInput.addEventListener('input', function(e) {
            const value = e.target.value;
            const numValue = value.replace(/,/g, '');
            
            if (numValue === '' || !isNaN(numValue)) {
                const formatted = formatNumberInput(numValue);
                e.target.value = formatted;
                // Auto-save to localStorage
                if (window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            }
        });
        
        numStudentsInput.addEventListener('blur', function(e) {
            // Auto-save to localStorage
            if (window.saveFormDataToLocalStorage) {
                window.saveFormDataToLocalStorage();
            }
        });

        // Format percent inputs and calculate on change
        const percentInputs = ['facultyStaff', 'curriculum', 'student', 'facilities'];
        percentInputs.forEach(category => {
            const percentInput = document.getElementById(category + 'Percent');
            const deductionInput = document.getElementById(category + 'Deduction');
            
            if (percentInput) {
                percentInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^0-9.]/g, '').trim();
                    // Allow decimal numbers for percentages (numerical only, no % symbol)
                    if (value === '' || !isNaN(value)) {
                        e.target.value = value;
                        calculateBreakdownRow(category);
                        // Save percentages to localStorage for sync
                        savePercentagesToStorage();
                        // Auto-save to localStorage
                        if (window.saveFormDataToLocalStorage) {
                            window.saveFormDataToLocalStorage();
                        }
                    }
                });
                
                // Validate on blur
                percentInput.addEventListener('blur', function(e) {
                    let value = e.target.value.replace(/[^0-9.]/g, '').trim();
                    if (value !== '' && !isNaN(value)) {
                        // Ensure it's a valid percentage
                        const numValue = parseFloat(value);
                        if (numValue > 100) {
                            e.target.value = '100';
                            calculateBreakdownRow(category);
                        }
                    }
                    // Save percentages to localStorage for sync
                    savePercentagesToStorage();
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                });
            }
            
        });
        
        // Setup Additional Amount field
        const additionalAmountInput = document.getElementById('additionalAmount');
        if (additionalAmountInput) {
            let originalAdditionalValue = '';
            
            additionalAmountInput.addEventListener('focus', function(e) {
                originalAdditionalValue = e.target.value;
                e.target.value = e.target.value.replace(/[₱,]/g, '');
            });
            
            additionalAmountInput.addEventListener('input', function(e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value === '' || value === '.' || !isNaN(value)) {
                    // Recalculate overall total
                    calculateOverallTotal();
                    // Save to separate additional amount storage
                    saveAdditionalAmountToStorage();
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                } else {
                    e.target.value = originalAdditionalValue.replace(/[₱,]/g, '');
                }
            });
            
            additionalAmountInput.addEventListener('blur', function(e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value !== '' && !isNaN(value)) {
                    e.target.value = formatNumber(parseFloat(value));
                    calculateOverallTotal();
                }
                // Save to separate additional amount storage
                saveAdditionalAmountToStorage();
                // Auto-save to localStorage
                if (window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            });
        }
        
        // Setup Additional Description field
        const additionalDescriptionInput = document.getElementById('additionalDescription');
        if (additionalDescriptionInput) {
            additionalDescriptionInput.addEventListener('input', function(e) {
                // Save to separate additional amount storage
                saveAdditionalAmountToStorage();
                // Auto-save to localStorage
                if (window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            });
        }
        
        // ---- Additional Amount separate localStorage (per department, per fiscal year) ----
        window.saveAdditionalAmountToStorage = function() {
            const deptId = window.selectedDepartmentId;
            if (!deptId) return;
            const fiscalYearEl = document.getElementById('fiscalYearSelect');
            const fiscalYear = fiscalYearEl ? fiscalYearEl.value : new Date().getFullYear();
            const amountEl = document.getElementById('additionalAmount');
            const descEl = document.getElementById('additionalDescription');
            const data = {
                amount: amountEl ? amountEl.value : '',
                description: descEl ? descEl.value : ''
            };
            localStorage.setItem(`additional_amount_${deptId}_${fiscalYear}`, JSON.stringify(data));
        };
        
        window.loadAdditionalAmountFromStorage = function() {
            const deptId = window.selectedDepartmentId;
            if (!deptId) return false;
            const fiscalYearEl = document.getElementById('fiscalYearSelect');
            const fiscalYear = fiscalYearEl ? fiscalYearEl.value : new Date().getFullYear();
            const stored = localStorage.getItem(`additional_amount_${deptId}_${fiscalYear}`);
            const amountEl = document.getElementById('additionalAmount');
            const descEl = document.getElementById('additionalDescription');
            // Always clear first
            if (amountEl) amountEl.value = '';
            if (descEl) descEl.value = '';
            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    if (amountEl && data.amount) amountEl.value = data.amount;
                    if (descEl && data.description) descEl.value = data.description;
                    calculateOverallTotal();
                    return true;
                } catch (e) {}
            }
            calculateOverallTotal();
            return false;
        };
        
        // Alias for inline calls
        function saveAdditionalAmountToStorage() { if (window.saveAdditionalAmountToStorage) window.saveAdditionalAmountToStorage(); }
        function loadAdditionalAmountFromStorage() { return window.loadAdditionalAmountFromStorage ? window.loadAdditionalAmountFromStorage() : false; }
        
        // Setup Fiscal Year selector
        const fiscalYearSelect = document.getElementById('fiscalYearSelect');
        if (fiscalYearSelect) {
            fiscalYearSelect.addEventListener('change', function(e) {
                const selectedYear = e.target.value;
                // Save to localStorage
                localStorage.setItem('selectedFiscalYear', selectedYear);
                
                // Reload allocation data for the selected fiscal year if a department is selected
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                
                // Always reload percentages for the new fiscal year immediately
                // This ensures Set % for All is separate per fiscal year
                if (typeof loadPercentagesFromStorage === 'function') {
                    loadPercentagesFromStorage();
                }
                
                if (departmentId) {
                    // Clear the form first to remove data from previous year
                    clearForm(false);
                    
                    // Reload data for the new fiscal year
                    loadSavedAllocation(departmentId);
                    
                    // Load additional amount for the new fiscal year
                    setTimeout(() => {
                        if (typeof loadAdditionalAmountFromStorage === 'function') {
                            loadAdditionalAmountFromStorage();
                        }
                    }, 500);
                }
            });
            
            // Load saved fiscal year from localStorage
            const savedFiscalYear = localStorage.getItem('selectedFiscalYear');
            if (savedFiscalYear) {
                fiscalYearSelect.value = savedFiscalYear;
            }
        }

        // Format Fiduciary Fund 50% inputs
        for (let i = 1; i <= 6; i++) {
            // Format item name field
            const fiduciaryItemInput = document.getElementById('fiduciaryItem' + i);
            if (fiduciaryItemInput) {
                fiduciaryItemInput.addEventListener('input', function(e) {
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                });
                
                fiduciaryItemInput.addEventListener('blur', function(e) {
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                });
            }
            
            // Format 50% Instructional field
            const fiduciaryInstructionalInput = document.getElementById('fiduciaryInstructional' + i);
            if (fiduciaryInstructionalInput) {
                let originalValue50 = '';
                
                fiduciaryInstructionalInput.addEventListener('focus', function(e) {
                    // Store the current value before removing formatting
                    originalValue50 = e.target.value;
                    // Remove peso and commas for easier editing
                    e.target.value = e.target.value.replace(/[₱,]/g, '');
                });
                
                fiduciaryInstructionalInput.addEventListener('input', function(e) {
                    const value = e.target.value.replace(/[₱,]/g, '');
                    // Allow empty, decimal point, or valid number - don't format while typing
                    if (value === '' || value === '.' || !isNaN(value)) {
                        // Just allow the input as-is, format on blur
                        calculateFiduciaryRow(i);
                        // Auto-save to localStorage
                        if (window.saveFormDataToLocalStorage) {
                            window.saveFormDataToLocalStorage();
                        }
                    } else {
                        // Invalid input, revert to previous valid value
                        const prevValue = originalValue50.replace(/[₱,]/g, '');
                        e.target.value = prevValue;
                    }
                });
                
                // Format on blur with peso sign
                fiduciaryInstructionalInput.addEventListener('blur', function(e) {
                    const value = e.target.value.replace(/[₱,]/g, '');
                    if (value !== '' && !isNaN(value)) {
                        e.target.value = formatNumber(parseFloat(value));
                        originalValue50 = e.target.value;
                    }
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                });
            }
        }
        
            function updateFiduciaryInstructional() {
                // No longer auto-filling - users will input manually
                // Just recalculate budget allocations if values exist
                for (let i = 1; i <= 6; i++) {
                    calculateFiduciaryRow(i);
                }
            }
            
            // Department and Office Search and Dropdown Functionality
            const departmentSearch = document.getElementById('departmentSearch');
            const departmentDropdown = document.getElementById('departmentDropdown');
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSearch = document.getElementById('officeSearch');
            const officeDropdown = document.getElementById('officeDropdown');
            const officeSelect = document.getElementById('officeSelect');
            const selectedDepartmentDiv = document.getElementById('selectedDepartment');
            const selectedDepartmentName = document.getElementById('selectedDepartmentName');
            const departmentOptions = document.querySelectorAll('.department-option');
            const officeOptions = document.querySelectorAll('.office-option');
            const departmentSearchContainer = departmentSearch ? departmentSearch.closest('.relative') : null;
            const officeSearchContainer = officeSearch ? officeSearch.closest('.relative') : null;
            
            // Department dropdown functions
            function showDepartmentDropdown() {
                if (departmentDropdown) {
                    departmentDropdown.classList.remove('hidden');
                    filterDepartmentOptions();
                }
            }
            
            function hideDepartmentDropdown() {
                if (departmentDropdown) {
                    setTimeout(() => {
                        departmentDropdown.classList.add('hidden');
                    }, 200);
                }
            }
            
            function filterDepartmentOptions() {
                if (!departmentSearch || !departmentOptions.length) return;
                const searchTerm = departmentSearch.value.toLowerCase();
                departmentOptions.forEach(option => {
                    const name = option.dataset.name.toLowerCase();
                    const code = option.querySelector('.text-xs') ? option.querySelector('.text-xs').textContent.toLowerCase() : '';
                    if (name.includes(searchTerm) || code.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            }
            
            function toggleDepartmentClearButton() {
                const clearBtn = document.getElementById('departmentClearBtn');
                if (clearBtn && departmentSearch) {
                    if (departmentSearch.value.length > 0) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                }
            }
            
            // Make function globally accessible
            window.toggleDepartmentClearButton = toggleDepartmentClearButton;
            
            function clearDepartmentSearch() {
                const deptSearch = document.getElementById('departmentSearch');
                if (deptSearch) {
                    deptSearch.value = '';
                    // Update clear button visibility
                    const clearBtn = document.getElementById('departmentClearBtn');
                    if (clearBtn) {
                        clearBtn.classList.add('hidden');
                    }
                    // Filter options - get all department options and show them
                    const deptOptions = document.querySelectorAll('.department-option');
                    deptOptions.forEach(option => {
                        option.style.display = '';
                    });
                    // Hide dropdown
                    const deptDropdown = document.getElementById('departmentDropdown');
                    if (deptDropdown) {
                        setTimeout(() => {
                            deptDropdown.classList.add('hidden');
                        }, 200);
                    }
                }
            }
            
            // Office dropdown functions
            function showOfficeDropdown() {
                if (officeDropdown) {
                    officeDropdown.classList.remove('hidden');
                    filterOfficeOptions();
                }
            }
            
            function hideOfficeDropdown() {
                if (officeDropdown) {
                    setTimeout(() => {
                        officeDropdown.classList.add('hidden');
                    }, 200);
                }
            }
            
            function filterOfficeOptions() {
                if (!officeSearch || !officeOptions.length) return;
                const searchTerm = officeSearch.value.toLowerCase();
                officeOptions.forEach(option => {
                    const name = option.dataset.name.toLowerCase();
                    const code = option.querySelector('.text-xs') ? option.querySelector('.text-xs').textContent.toLowerCase() : '';
                    if (name.includes(searchTerm) || code.includes(searchTerm)) {
                        option.style.display = '';
                    } else {
                        option.style.display = 'none';
                    }
                });
            }
            
            function toggleOfficeClearButton() {
                const clearBtn = document.getElementById('officeClearBtn');
                if (clearBtn && officeSearch) {
                    if (officeSearch.value.length > 0) {
                        clearBtn.classList.remove('hidden');
                    } else {
                        clearBtn.classList.add('hidden');
                    }
                }
            }
            
            // Make function globally accessible
            window.toggleOfficeClearButton = toggleOfficeClearButton;
            
            function clearOfficeSearch() {
                const offSearch = document.getElementById('officeSearch');
                if (offSearch) {
                    offSearch.value = '';
                    // Update clear button visibility
                    const clearBtn = document.getElementById('officeClearBtn');
                    if (clearBtn) {
                        clearBtn.classList.add('hidden');
                    }
                    // Filter options - get all office options and show them
                    const offOptions = document.querySelectorAll('.office-option');
                    offOptions.forEach(option => {
                        option.style.display = '';
                    });
                    // Hide dropdown
                    const offDropdown = document.getElementById('officeDropdown');
                    if (offDropdown) {
                        setTimeout(() => {
                            offDropdown.classList.add('hidden');
                        }, 200);
                    }
                }
            }
            
            // Make clear functions global so onclick handlers can access them
            window.clearDepartmentSearch = clearDepartmentSearch;
            window.clearOfficeSearch = clearOfficeSearch;
            
            function selectDepartment(id, name, type = 'department') {
                // Cancel any pending load timeouts to prevent duplicates
                if (window.pendingLoadTimeout) {
                    clearTimeout(window.pendingLoadTimeout);
                    window.pendingLoadTimeout = null;
                }
                if (window.pendingLocalStorageTimeout) {
                    clearTimeout(window.pendingLocalStorageTimeout);
                    window.pendingLocalStorageTimeout = null;
                }
                
                // Save current data BEFORE switching (use old ID to save correctly)
                const oldDepartmentId = window.selectedDepartmentId;
                const oldDepartmentType = window.selectedDepartmentType;
                if (oldDepartmentId && oldDepartmentId !== id && window.saveFormDataToLocalStorage) {
                    // Temporarily restore old values to save correctly
                    const tempId = window.selectedDepartmentId;
                    const tempType = window.selectedDepartmentType;
                    window.selectedDepartmentId = oldDepartmentId;
                    window.selectedDepartmentType = oldDepartmentType;
                    window.saveFormDataToLocalStorage();
                    // Restore new values
                    window.selectedDepartmentId = tempId;
                    window.selectedDepartmentType = tempType;
                }
                
                // Reset loading state when switching to a different department/office
                if (window.selectedDepartmentId !== id) {
                    window.currentLoadingDepartmentId = null;
                    window.isLoadingAllocation = false;
                }
                
                // Clear the other selector
                if (type === 'department') {
                    if (officeSearch) officeSearch.value = '';
                    if (officeSelect) officeSelect.value = '';
                } else {
                    if (departmentSearch) departmentSearch.value = '';
                    if (departmentSelect) departmentSelect.value = '';
                }
                
                // Set the selected value
                const selectElement = type === 'department' ? departmentSelect : officeSelect;
                const searchElement = type === 'department' ? departmentSearch : officeSearch;
                
                if (selectElement) selectElement.value = id;
                if (searchElement) searchElement.value = name;
                // Update clear button visibility
                if (type === 'department') {
                    toggleDepartmentClearButton();
                } else {
                    toggleOfficeClearButton();
                }
                if (selectedDepartmentName) selectedDepartmentName.textContent = name;
                if (selectedDepartmentDiv) selectedDepartmentDiv.classList.remove('hidden');
                
                if (type === 'department') {
                    hideDepartmentDropdown();
                } else {
                    hideOfficeDropdown();
                }
                
                // Determine if it's an office based on type
                const isOffice = type === 'office';
                
                // Store in global variables
                window.selectedDepartmentType = isOffice ? 'office' : 'department';
                window.selectedDepartmentId = id;
                window.selectedDepartmentName = name;
                
                // Save selection to localStorage for page refresh persistence
                try {
                    localStorage.setItem('lastSelectedDepartment', JSON.stringify({
                        id: id,
                        name: name,
                        type: isOffice ? 'office' : 'department'
                    }));
                } catch (error) {
                    console.error('Error saving selection to localStorage:', error);
                }
                
                // Clear form and hide summary before loading new data
                clearForm(false);
                hideSummary();
                
                // Update UI based on type
                toggleSectionsForType(isOffice, name);
                
                // Restructure Fiduciary section for offices
                if (isOffice) {
                    restructureFiduciaryForOffice();
                    // Wait a bit for structure to be created before loading data
                    window.pendingLoadTimeout = setTimeout(() => {
                        loadSavedAllocation(id);
                        // After loading from database, check localStorage if no database data
                        window.pendingLocalStorageTimeout = setTimeout(() => {
                            // Check if database has data by looking at any filled field
                            const budgetAllocated = document.getElementById('budgetAllocated')?.value || '';
                            const hasDatabaseData = budgetAllocated && budgetAllocated !== '0' && budgetAllocated !== '₱0.00' && budgetAllocated !== '0.00';
                            
                            // Only load from localStorage if no database data exists
                            if (!hasDatabaseData && window.loadFormDataFromLocalStorage) {
                                window.loadFormDataFromLocalStorage(id, 'office');
                            }
                            window.pendingLocalStorageTimeout = null;
                        }, 500);
                        window.pendingLoadTimeout = null;
                    }, 200);
                } else {
                    restoreFiduciaryForDepartment();
                    loadSavedAllocation(id);
                    // After loading from database, check localStorage if no database data
                    window.pendingLocalStorageTimeout = setTimeout(() => {
                        // Check if database has data by looking at any filled field
                        const numStudents = document.getElementById('numStudents')?.value || '';
                        const totalTuitionFee = document.getElementById('totalTuitionFee')?.value || '';
                        const hasDatabaseData = (numStudents && numStudents !== '0') || (totalTuitionFee && totalTuitionFee !== '₱0.00' && totalTuitionFee !== '0.00');
                        
                        // If no database data, load from localStorage
                        if (!hasDatabaseData && window.loadFormDataFromLocalStorage) {
                            window.loadFormDataFromLocalStorage(id, 'department');
                        }
                        window.pendingLocalStorageTimeout = null;
                    }, 300);
                }
            }
            
            function fetchDepartmentDetails(departmentId, departmentName) {
                fetch(`../api/get_department_details.php?id=${departmentId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.department) {
                            const fiduciaryType = data.department.fiduciary_type || 'Non-Fiduciary';
                            const isOffice = fiduciaryType === 'Fiduciary';
                            
                            // Store in a global variable for use in other functions
                            window.selectedDepartmentType = isOffice ? 'office' : 'department';
                            window.selectedDepartmentId = departmentId;
                            window.selectedDepartmentName = departmentName;
                            
                            // Show/hide sections based on type
                            toggleSectionsForType(isOffice, departmentName);
                            
                            // Restructure Fiduciary section for offices
                            if (isOffice) {
                                restructureFiduciaryForOffice();
                            } else {
                                restoreFiduciaryForDepartment();
                            }
                        } else {
                            // Default to department view
                            window.selectedDepartmentType = 'department';
                            window.selectedDepartmentName = departmentName || '';
                            toggleSectionsForType(false, departmentName || '');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching department details:', error);
                        // Default to department view
                        window.selectedDepartmentType = 'department';
                        window.selectedDepartmentName = departmentName || '';
                        toggleSectionsForType(false, departmentName || '');
                    });
            }
            
            function toggleSectionsForType(isOffice, departmentName = '') {
                // Get department name from input if not provided
                if (!departmentName) {
                    const departmentSearch = document.getElementById('departmentSearch');
                    const officeSearch = document.getElementById('officeSearch');
                    departmentName = departmentSearch ? departmentSearch.value : (officeSearch ? officeSearch.value : '');
                }
                
                // For offices: completely remove non-fiduciary section (not just hide)
                // Store the HTML before removing so we can restore it for departments
                const nonFiduciarySection = document.getElementById('nonFiduciarySection');
                if (nonFiduciarySection) {
                    if (isOffice) {
                        // Store the HTML before removing
                        if (!window.nonFiduciarySectionHTML) {
                            window.nonFiduciarySectionHTML = nonFiduciarySection.outerHTML;
                            window.nonFiduciarySectionParent = nonFiduciarySection.parentElement;
                            window.nonFiduciarySectionNextSibling = nonFiduciarySection.nextElementSibling;
                        }
                        // Completely remove the element for offices
                        nonFiduciarySection.remove();
                    } else {
                        // For departments, restore it if it was removed
                        if (!document.getElementById('nonFiduciarySection')) {
                            if (window.nonFiduciarySectionHTML && window.nonFiduciarySectionParent) {
                                // Create a temporary container to parse the HTML
                                const temp = document.createElement('div');
                                temp.innerHTML = window.nonFiduciarySectionHTML;
                                const restoredSection = temp.firstElementChild;
                                
                                // Insert it back in the correct position
                                if (window.nonFiduciarySectionNextSibling) {
                                    window.nonFiduciarySectionParent.insertBefore(restoredSection, window.nonFiduciarySectionNextSibling);
                                } else {
                                    window.nonFiduciarySectionParent.appendChild(restoredSection);
                                }
                                // Ensure it's visible after restoration
                                if (restoredSection) {
                                    restoredSection.style.display = 'block';
                                }
                            }
                        } else {
                            // It exists, just make sure it's visible
                            nonFiduciarySection.style.display = 'block';
                        }
                    }
                } else if (!isOffice) {
                    // Section doesn't exist but we need it for departments - try to restore
                    if (window.nonFiduciarySectionHTML && window.nonFiduciarySectionParent) {
                        const temp = document.createElement('div');
                        temp.innerHTML = window.nonFiduciarySectionHTML;
                        const restoredSection = temp.firstElementChild;
                        
                        if (restoredSection) {
                            if (window.nonFiduciarySectionNextSibling) {
                                window.nonFiduciarySectionParent.insertBefore(restoredSection, window.nonFiduciarySectionNextSibling);
                            } else {
                                window.nonFiduciarySectionParent.appendChild(restoredSection);
                            }
                            restoredSection.style.display = 'block';
                        }
                    }
                }
                
                // For offices: hide input fields (Number of Students, Total Tuition Fee, 50% Instructional)
                // For departments: show all input fields
                const shouldShowInputFields = !isOffice;
                
                // Show Fiduciary section (always visible)
                const fiduciarySection = document.getElementById('fiduciarySection');
                if (fiduciarySection) {
                    fiduciarySection.style.display = 'block';
                }
                
                // Hide input boxes (Number of Students, Total Tuition Fee, 50% Instructional)
                // for offices unless it's an exception department
                const inputSection = document.getElementById('inputSection');
                if (inputSection) {
                    if (shouldShowInputFields) {
                        inputSection.style.display = 'grid';
                        inputSection.style.visibility = 'visible';
                    } else {
                        inputSection.style.display = 'none';
                        inputSection.style.visibility = 'hidden';
                    }
                    // Force update
                    inputSection.setAttribute('data-display-state', shouldShowInputFields ? 'show' : 'hide');
                }
                
                // Show Budget Allocated input for offices (always show for offices)
                const budgetAllocatedSection = document.getElementById('budgetAllocatedSection');
                if (budgetAllocatedSection) {
                    budgetAllocatedSection.style.display = isOffice ? 'block' : 'none';
                }
            }
            
            // Make function globally accessible
            window.toggleSectionsForType = toggleSectionsForType;
            
            function restructureFiduciaryForOffice() {
                // For offices, only deductions list and budget allocation
                // Deductions subtract directly from the allocated budget
                const fiduciaryFundRows = document.getElementById('fiduciaryFundRows');
                if (!fiduciaryFundRows) return;
                
                // Store the original structure if not already stored
                if (!window.originalFiduciaryStructure) {
                    window.originalFiduciaryStructure = fiduciaryFundRows.innerHTML;
                }
                
                // Create office-specific structure - just one section with deductions
                let officeHTML = '';
                
                officeHTML += `
                    <div class="border-2 border-gray-300 rounded-xl p-6 bg-gradient-to-br from-white to-gray-50 shadow-md hover:shadow-lg transition-all">
                        <div class="flex items-start gap-6">
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-gray-800 mb-2 flex items-center gap-2">
                                    <svg class="w-4 h-4 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    Deductions
                                </label>
                                <div id="officeFiduciaryDeductionsContainer" class="space-y-2">
                                    <!-- Deductions will be added here dynamically -->
                                </div>
                                <button 
                                    type="button"
                                    onclick="addOfficeDeduction(0)" 
                                    class="mt-2 px-4 py-2 text-sm font-semibold bg-gradient-to-r from-red-600 to-red-800 text-white rounded-lg hover:from-red-700 hover:to-red-900 transition-all duration-200 shadow-md hover:shadow-lg flex items-center gap-2 border border-red-500"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <span>Add Deduction</span>
                                </button>
                                <div id="officeFiduciaryDeductionTotal" class="mt-2 text-sm font-semibold text-gray-700 hidden">
                                    <div class="pt-2 border-t border-gray-300">
                                        <span class="text-gray-600">Sub-total: </span>
                                        <span id="officeFiduciaryDeductionTotalAmount" class="text-maroon">₱0.00</span>
                                    </div>
                                    <div class="pt-2 mt-2">
                                        <div class="flex items-center justify-between">
                                            <span class="text-lg font-bold text-red-900">Total Budget:</span>
                                            <span id="officeFiduciaryTotalBudget" class="text-lg font-bold text-red-900">₱0.00</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                fiduciaryFundRows.innerHTML = officeHTML;
                
                // Hide table header for offices (no need for column headers)
                const tableHeader = document.querySelector('#fiduciarySection .flex.items-center.gap-6');
                if (tableHeader) {
                    tableHeader.style.display = 'none';
                }
                
                // Hide Fiduciary Fund heading and icon for offices
                const fiduciaryHeading = document.querySelector('#fiduciarySection .flex.items-center.gap-3.mb-6.pb-4');
                if (fiduciaryHeading) {
                    fiduciaryHeading.style.display = 'none';
                }
                
                // Setup formatting for office inputs
                setupOfficeFiduciaryInputs();
                
                // Setup listener for budget allocated input to recalculate when changed
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                if (budgetAllocatedInput) {
                    budgetAllocatedInput.addEventListener('input', function() {
                        calculateOfficeFiduciaryRow(0);
                    });
                    budgetAllocatedInput.addEventListener('blur', function() {
                        calculateOfficeFiduciaryRow(0);
                    });
                }
            }
            
            function restoreFiduciaryForDepartment() {
                // Restore original department structure
                const fiduciaryFundRows = document.getElementById('fiduciaryFundRows');
                if (fiduciaryFundRows && window.originalFiduciaryStructure) {
                    fiduciaryFundRows.innerHTML = window.originalFiduciaryStructure;
                }
                
                // Show Fiduciary Fund heading and icon for departments
                const fiduciaryHeading = document.querySelector('#fiduciarySection .flex.items-center.gap-3.mb-6.pb-4');
                if (fiduciaryHeading) {
                    fiduciaryHeading.style.display = 'flex';
                }
                
                // Show table header for departments
                const tableHeader = document.querySelector('#fiduciarySection .flex.items-center.gap-6');
                if (tableHeader) {
                    tableHeader.style.display = 'flex';
                }
            }
            
            function setupOfficeFiduciaryInputs() {
                // No longer needed since we removed the 50% input field
                // This function is kept for compatibility but does nothing now
            }
            
            window.calculateOfficeFiduciaryRow = function(rowNum) {
                // For offices, rowNum is 0 (single section)
                const deductionContainer = document.getElementById('officeFiduciaryDeductionsContainer');
                const deductionTotal = document.getElementById('officeFiduciaryDeductionTotal');
                const deductionTotalAmount = document.getElementById('officeFiduciaryDeductionTotalAmount');
                const totalBudgetSpan = document.getElementById('officeFiduciaryTotalBudget');
                
                // Get the allocated budget from the top input
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                const allocatedBudget = budgetAllocatedInput ? parseFloat(budgetAllocatedInput.value.replace(/[₱,]/g, '')) || 0 : 0;
                
                // Calculate total deductions
                let totalDeduction = 0;
                if (deductionContainer) {
                    const deductionInputs = deductionContainer.querySelectorAll('[id$="_amount"]');
                    deductionInputs.forEach(input => {
                        const deductionValue = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                        totalDeduction += deductionValue;
                    });
                }
                
                // Calculate Total Budget (Allocated Budget - Total Deductions)
                const totalBudget = allocatedBudget - totalDeduction;
                
                // Show/hide deduction total and total budget
                if (deductionTotal && deductionTotalAmount) {
                    if (totalDeduction > 0 || allocatedBudget > 0) {
                        deductionTotal.classList.remove('hidden');
                        deductionTotalAmount.textContent = formatNumber(totalDeduction);
                        
                        // Update total budget display
                        if (totalBudgetSpan) {
                            totalBudgetSpan.textContent = formatNumber(totalBudget);
                        }
                    } else {
                        deductionTotal.classList.add('hidden');
                    }
                }
                
                // Update overall total
                const overallTotalField = document.getElementById('overallTotalBudgetAllocation');
                if (overallTotalField) {
                    overallTotalField.value = formatNumber(totalBudget);
                }
                
                // Update summary in real-time if visible
                updateSummaryIfVisible();
            };
            
            // Define addOfficeDeduction function
            window.addOfficeDeduction = function(rowNum, skipAutoSave = false) {
                // For offices, rowNum is 0 (single section)
                const container = document.getElementById('officeFiduciaryDeductionsContainer');
                if (!container) return null;
                
                const deductionCount = container.querySelectorAll('[id^="officeDeductionRow_"]').length + 1;
                const deductionId = 'officeDeduction' + deductionCount;
                const deductionRowId = 'officeDeductionRow_' + deductionCount;
                
                const officeRemarks = ['Honoraria Overload', 'Part-time', 'Electricity', 'COS', 'Security', 'Water', 'Labor & Wages', '+ Custom'];
                
                const deductionHTML = `
                    <div id="${deductionRowId}" class="flex flex-wrap items-center gap-2 p-2 bg-gray-50 rounded-lg border border-gray-200">
                        <div class="flex-1 min-w-[100px]">
                            <input 
                                type="text" 
                                id="${deductionId}_amount" 
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-semibold text-sm"
                                placeholder="₱0.00"
                                inputmode="decimal"
                                oninput="calculateOfficeFiduciaryRow(0)"
                            >
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <select 
                                id="${deductionId}_remarks" 
                                class="w-28 px-2 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-semibold text-sm"
                                onchange="updateOfficeDeductionIndicator('${deductionRowId}')"
                            >
                                <option value="">Select Remarks</option>
                                ${officeRemarks.map(remark => `<option value="${remark}">${remark}</option>`).join('')}
                            </select>
                            <div class="w-6 flex items-center justify-center deduction-indicator-container">
                                <!-- Green checkmark will appear here -->
                            </div>
                            <button 
                                type="button"
                                onclick="removeOfficeDeduction('${deductionRowId}')"
                                class="px-2 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm"
                                title="Remove Deduction"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                `;
                
                container.insertAdjacentHTML('beforeend', deductionHTML);
                
                // Setup input formatting
                const amountInput = document.getElementById(deductionId + '_amount');
                if (amountInput) {
                    setupOfficeDeductionInputListeners(amountInput.id, 0, skipAutoSave);
                }
                
                calculateOfficeFiduciaryRow(0);
                
                // Auto-save to localStorage (unless loading)
                if (!skipAutoSave && window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
                
                // Return the row ID for reference
                return deductionRowId;
            };
            
            
            window.removeOfficeDeduction = function(deductionRowId) {
                const deductionRow = document.getElementById(deductionRowId);
                if (deductionRow) {
                    deductionRow.remove();
                    calculateOfficeFiduciaryRow(0);
                    
                    // Auto-save to localStorage
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                }
            };
            
            window.updateOfficeDeductionIndicator = function(deductionRowId) {
                const deductionRow = document.getElementById(deductionRowId);
                if (!deductionRow) return;
                
                const remarksSelect = deductionRow.querySelector('select');
                
                // Check if "+ Custom" was selected
                if (remarksSelect && remarksSelect.value === '+ Custom') {
                    // Store the select element ID for later use
                    window.currentCustomDeductionSelectId = remarksSelect.id;
                    // Open custom deduction modal
                    openCustomDeductionModal();
                    // Reset select to empty
                    remarksSelect.value = '';
                    return;
                }
                
                if (remarksSelect && remarksSelect.value) {
                    // Add checkmark indicator
                    if (!deductionRow.querySelector('.deduction-checkmark')) {
                        const checkmark = document.createElement('span');
                        checkmark.className = 'deduction-checkmark text-green-600 ml-2';
                        checkmark.innerHTML = '✓';
                        remarksSelect.parentElement.appendChild(checkmark);
                    }
                } else {
                    // Remove checkmark
                    const checkmark = deductionRow.querySelector('.deduction-checkmark');
                    if (checkmark) {
                        checkmark.remove();
                    }
                }
                
                // Auto-save to localStorage when remarks change (unless loading)
                if (!window.isLoadingOfficeDeductions && window.saveFormDataToLocalStorage) {
                    window.saveFormDataToLocalStorage();
                }
            }
            
            function setupOfficeDeductionInputListeners(inputId, rowNum, skipAutoSave = false) {
                const input = document.getElementById(inputId);
                if (!input) return;
                
                let originalValue = '';
                
                input.addEventListener('focus', function(e) {
                    originalValue = e.target.value;
                    e.target.value = e.target.value.replace(/[₱,]/g, '');
                });
                
                input.addEventListener('input', function(e) {
                    const value = e.target.value.replace(/[₱,]/g, '');
                    if (value === '' || value === '.' || !isNaN(value)) {
                        calculateOfficeFiduciaryRow(0);
                        // Auto-save to localStorage (unless loading)
                        if (!skipAutoSave && !window.isLoadingOfficeDeductions && window.saveFormDataToLocalStorage) {
                            window.saveFormDataToLocalStorage();
                        }
                    } else {
                        e.target.value = originalValue.replace(/[₱,]/g, '');
                    }
                });
                
                input.addEventListener('blur', function(e) {
                    const value = e.target.value.replace(/[₱,]/g, '');
                    if (value !== '' && !isNaN(value)) {
                        e.target.value = formatNumber(parseFloat(value));
                        // Auto-save to localStorage (unless loading)
                        if (!skipAutoSave && !window.isLoadingOfficeDeductions && window.saveFormDataToLocalStorage) {
                            window.saveFormDataToLocalStorage();
                        }
                    }
                });
            }
            
            // Save all form data to localStorage (works for both departments and offices)
            window.saveFormDataToLocalStorage = function() {
                // Skip saving if we're currently loading deductions
                if (window.isLoadingOfficeDeductions) {
                    return;
                }
                
                const deptId = window.selectedDepartmentId;
                const isOffice = window.selectedDepartmentType === 'office';
                
                if (!deptId) {
                    return; // No department/office selected
                }
                
                try {
                    const formData = {
                        type: isOffice ? 'office' : 'department',
                        timestamp: new Date().toISOString()
                    };
                    
                    if (isOffice) {
                        // Save office data
                        const budgetAllocatedInput = document.getElementById('budgetAllocated');
                        formData.budgetAllocated = budgetAllocatedInput ? budgetAllocatedInput.value : '';
                        
                        // Collect all deductions
                        const container = document.getElementById('officeFiduciaryDeductionsContainer');
                        formData.deductions = [];
                        
                        if (container) {
                            const deductionRows = container.querySelectorAll('[id^="officeDeductionRow_"]');
                            deductionRows.forEach(row => {
                                const amountInput = row.querySelector('[id$="_amount"]');
                                const remarksSelect = row.querySelector('select');
                                if (amountInput || remarksSelect) {
                                    formData.deductions.push({
                                        amount: amountInput ? amountInput.value : '',
                                        remarks: remarksSelect ? remarksSelect.value : ''
                                    });
                                }
                            });
                        }
                    } else {
                        // Save department data
                        formData.numStudents = document.getElementById('numStudents')?.value || '';
                        formData.totalTuitionFee = document.getElementById('totalTuitionFee')?.value || '';
                        formData.instructionalAmount = document.getElementById('instructionalAmount')?.value || '';
                        formData.additionalAmount = document.getElementById('additionalAmount')?.value || '';
                        formData.additionalDescription = document.getElementById('additionalDescription')?.value || '';
                        
                        // Save non-fiduciary categories
                        // NOTE: Percent values are NOT saved here - they are managed globally via "Set % for All"
                        formData.nonFiduciary = {};
                        ['facultyStaff', 'curriculum', 'student', 'facilities'].forEach(category => {
                            formData.nonFiduciary[category] = {
                                // percent is excluded - managed globally
                                instructional: document.getElementById(category + 'Instructional')?.value || '',
                                budgetAllocation: document.getElementById(category + 'BudgetAllocation')?.value || '',
                                deductions: []
                            };
                            
                            // Collect deductions for this category
                            const container = document.getElementById(category + 'DeductionsContainer');
                            if (container) {
                                const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                                deductionRows.forEach(row => {
                                    const amountInput = row.querySelector('[id$="_amount"]');
                                    const remarksSelect = row.querySelector('select');
                                    if (amountInput && remarksSelect) {
                                        formData.nonFiduciary[category].deductions.push({
                                            amount: amountInput.value || '',
                                            remarks: remarksSelect.value || ''
                                        });
                                    }
                                });
                            }
                        });
                        
                        // Save fiduciary rows
                        formData.fiduciary = {};
                        for (let i = 1; i <= 6; i++) {
                            formData.fiduciary[i] = {
                                itemName: document.getElementById('fiduciaryItem' + i)?.value || '',
                                instructional: document.getElementById('fiduciaryInstructional' + i)?.value || '',
                                deductions: []
                            };
                            
                            // Collect deductions for this fiduciary row
                            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                            if (container) {
                                const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                                deductionRows.forEach(row => {
                                    const amountInput = row.querySelector('[id$="_amount"]');
                                    const remarksSelect = row.querySelector('select');
                                    if (amountInput && remarksSelect) {
                                        formData.fiduciary[i].deductions.push({
                                            amount: amountInput.value || '',
                                            remarks: remarksSelect.value || ''
                                        });
                                    }
                                });
                            }
                        }
                    }
                    
                    // Save to localStorage (for local browser)
                    // Include fiscal year in the key to separate data by year
                    const fiscalYearSelect = document.getElementById('fiscalYearSelect');
                    const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
                    const storageKey = `form_data_${deptId}_${fiscalYear}`;
                    localStorage.setItem(storageKey, JSON.stringify(formData));
                    
                    // SHARED LOCALSTORAGE: Save to database so all budget role accounts see the same data
                    // Clear any existing timeout
                    if (window.draftSaveTimeout) {
                        clearTimeout(window.draftSaveTimeout);
                    }
                    
                    // Debounce: Save to database after 500ms of no changes
                    window.draftSaveTimeout = setTimeout(() => {
                        fetch('../api/save_allocation_draft.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                department_id: deptId,
                                draft_data: formData
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.updated_at) {
                                // Update the last known draft timestamp to prevent false sync
                                window.lastKnownDraftTime = data.updated_at;
                            }
                        })
                        .catch(error => {
                            console.error('Error saving draft to database:', error);
                        });
                    }, 500);
                    
                } catch (error) {
                    console.error('Error saving form data to localStorage:', error);
                }
            };
            
            // Save office data to localStorage (kept for backward compatibility)
            window.saveOfficeDataToLocalStorage = function() {
                window.saveFormDataToLocalStorage();
            };
            
            // Load all form data from localStorage (works for both departments and offices)
            window.loadFormDataFromLocalStorage = function(deptId, type) {
                if (!deptId) {
                    return false;
                }
                
                try {
                    // Include fiscal year in the key to load data for the correct year
                    const fiscalYearSelect = document.getElementById('fiscalYearSelect');
                    const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
                    const storageKey = `form_data_${deptId}_${fiscalYear}`;
                    const savedData = localStorage.getItem(storageKey);
                    
                    if (!savedData) {
                        return false;
                    }
                    
                    const formData = JSON.parse(savedData);
                    
                    // Only load if the type matches
                    if (formData.type !== type) {
                        return false;
                    }
                    
                    if (type === 'office') {
                        // Load office data
                        const budgetAllocatedInput = document.getElementById('budgetAllocated');
                        if (budgetAllocatedInput && formData.budgetAllocated) {
                            budgetAllocatedInput.value = formData.budgetAllocated;
                        }
                        
                        // Load deductions - use recursive function to wait for container
                        function loadOfficeDeductionsFromStorage() {
                            const container = document.getElementById('officeFiduciaryDeductionsContainer');
                            if (!container) {
                                // Container not ready yet, try again
                                setTimeout(loadOfficeDeductionsFromStorage, 50);
                                return;
                            }
                            
                            // Check if deductions already exist (might have been loaded from database)
                            const existingDeductions = container.querySelectorAll('[id^="officeDeductionRow_"]');
                            if (existingDeductions.length > 0) {
                                console.log('Deductions already exist, skipping localStorage load to prevent duplication');
                                return;
                            }
                            
                            if (formData.deductions && formData.deductions.length > 0) {
                                // Clear container before loading to prevent duplicates
                                container.innerHTML = '';
                                
                                // Set flag to prevent auto-save during loading
                                window.isLoadingOfficeDeductions = true;
                                
                                // Load deductions sequentially to avoid race conditions
                                let currentIndex = 0;
                                
                                function loadNextDeduction() {
                                    if (currentIndex >= formData.deductions.length) {
                                        // All deductions loaded, calculate totals
                                        window.isLoadingOfficeDeductions = false;
                                        setTimeout(() => {
                                            if (window.calculateOfficeFiduciaryRow) {
                                                window.calculateOfficeFiduciaryRow(0);
                                            }
                                            // Save after loading is complete
                                            if (window.saveFormDataToLocalStorage) {
                                                window.saveFormDataToLocalStorage();
                                            }
                                        }, 100);
                                        return;
                                    }
                                    
                                    const deduction = formData.deductions[currentIndex];
                                    
                                    if (window.addOfficeDeduction) {
                                        // Add deduction row (skip auto-save during loading)
                                        const rowId = window.addOfficeDeduction(0, true);
                                        
                                        if (rowId) {
                                            // Find the row that was just added
                                            const row = document.getElementById(rowId);
                                            if (row) {
                                                const amountInput = row.querySelector('[id$="_amount"]');
                                                const remarksSelect = row.querySelector('select');
                                                
                                                // Set amount value
                                                if (amountInput && deduction.amount) {
                                                    amountInput.value = deduction.amount;
                                                }
                                                
                                                // Set remarks value
                                                if (remarksSelect && deduction.remarks) {
                                                    remarksSelect.value = deduction.remarks;
                                                    if (window.updateOfficeDeductionIndicator) {
                                                        window.updateOfficeDeductionIndicator(rowId);
                                                    }
                                                }
                                            }
                                        }
                                        
                                        // Move to next deduction
                                        currentIndex++;
                                        setTimeout(loadNextDeduction, 10);
                                    }
                                }
                                
                                // Start loading first deduction
                                loadNextDeduction();
                            }
                        }
                        
                        // Start loading deductions
                        loadOfficeDeductionsFromStorage();
                    } else {
                        // Load department data
                        if (formData.numStudents) {
                            document.getElementById('numStudents').value = formData.numStudents;
                        }
                        if (formData.totalTuitionFee) {
                            document.getElementById('totalTuitionFee').value = formData.totalTuitionFee;
                            calculateInstructional();
                        }
                        if (formData.instructionalAmount) {
                            document.getElementById('instructionalAmount').value = formData.instructionalAmount;
                        }
                        if (formData.additionalAmount) {
                            document.getElementById('additionalAmount').value = formData.additionalAmount;
                        }
                        if (formData.additionalDescription) {
                            document.getElementById('additionalDescription').value = formData.additionalDescription;
                        }
                        
                        // Reset deduction counts and clear all deduction containers before loading
                        deductionCounts = { facultyStaff: 0, curriculum: 0, student: 0, facilities: 0 };
                        fiduciaryDeductionCounts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };
                        
                        // Clear all deduction containers first
                        ['facultyStaff', 'curriculum', 'student', 'facilities'].forEach(category => {
                            const container = document.getElementById(category + 'DeductionsContainer');
                            if (container) container.innerHTML = '';
                        });
                        for (let i = 1; i <= 6; i++) {
                            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                            if (container) container.innerHTML = '';
                        }
                        
                        // Load non-fiduciary categories
                        if (formData.nonFiduciary) {
                            ['facultyStaff', 'curriculum', 'student', 'facilities'].forEach(category => {
                                if (formData.nonFiduciary[category]) {
                                    const catData = formData.nonFiduciary[category];
                                    
                                    // Percent is NOT loaded from here - it's managed globally via "Set % for All"
                                    // if (catData.percent) {
                                    //     document.getElementById(category + 'Percent').value = catData.percent;
                                    // }
                                    if (catData.instructional) {
                                        document.getElementById(category + 'Instructional').value = catData.instructional;
                                    }
                                    if (catData.budgetAllocation) {
                                        document.getElementById(category + 'BudgetAllocation').value = catData.budgetAllocation;
                                    }
                                    
                                    // Load deductions
                                    if (catData.deductions && catData.deductions.length > 0) {
                                        catData.deductions.forEach(deduction => {
                                            addDeduction(category);
                                            const container = document.getElementById(category + 'DeductionsContainer');
                                            if (container) {
                                                const rows = container.querySelectorAll('[id^="deductionRow_"]');
                                                const lastRow = rows[rows.length - 1];
                                                if (lastRow) {
                                                    const amountInput = lastRow.querySelector('[id$="_amount"]');
                                                    const remarksSelect = lastRow.querySelector('select');
                                                    
                                                    if (amountInput && deduction.amount) {
                                                        amountInput.value = deduction.amount;
                                                        setupDeductionInputListeners(amountInput.id, category);
                                                    }
                                                    if (remarksSelect && deduction.remarks) {
                                                        remarksSelect.value = deduction.remarks;
                                                        const deductionRowId = amountInput ? amountInput.id.replace('_amount', '') : '';
                                                        if (deductionRowId) {
                                                            updateDeductionIndicator(deductionRowId);
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                    
                                    calculateBreakdownRow(category);
                                }
                            });
                        }
                        
                        // Load fiduciary rows
                        if (formData.fiduciary) {
                            for (let i = 1; i <= 6; i++) {
                                if (formData.fiduciary[i]) {
                                    const fidData = formData.fiduciary[i];
                                    
                                    if (fidData.itemName) {
                                        document.getElementById('fiduciaryItem' + i).value = fidData.itemName;
                                    }
                                    if (fidData.instructional) {
                                        document.getElementById('fiduciaryInstructional' + i).value = fidData.instructional;
                                    }
                                    
                                    // Load deductions
                                    if (fidData.deductions && fidData.deductions.length > 0) {
                                        fidData.deductions.forEach(deduction => {
                                            addFiduciaryDeduction(i);
                                            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                                            if (container) {
                                                const rows = container.querySelectorAll('[id^="deductionRow_"]');
                                                const lastRow = rows[rows.length - 1];
                                                if (lastRow) {
                                                    const amountInput = lastRow.querySelector('[id$="_amount"]');
                                                    const remarksSelect = lastRow.querySelector('select');
                                                    
                                                    if (amountInput && deduction.amount) {
                                                        amountInput.value = deduction.amount;
                                                        setupFiduciaryDeductionInputListeners(amountInput.id, i);
                                                    }
                                                    if (remarksSelect && deduction.remarks) {
                                                        remarksSelect.value = deduction.remarks;
                                                        const deductionRowId = amountInput ? amountInput.id.replace('_amount', '') : '';
                                                        if (deductionRowId) {
                                                            updateDeductionIndicator(deductionRowId);
                                                        }
                                                    }
                                                }
                                            }
                                        });
                                    }
                                    
                                    calculateFiduciaryRow(i);
                                }
                            }
                        }
                        
                        // Recalculate totals
                        setTimeout(() => {
                            calculateNonFiduciaryTotals();
                            calculateFiduciaryTotals();
                            calculateOverallTotal();
                        }, 200);
                    }
                    
                    return true;
                } catch (error) {
                    console.error('Error loading form data from localStorage:', error);
                    return false;
                }
                
                // IMPORTANT: After loading form data, reapply global percentages from "Set % for All"
                // This ensures that global percentages override individual department percentages
                if (typeof loadPercentagesFromStorage === 'function') {
                    loadPercentagesFromStorage();
                }
                
                return true;
            };
            
            // Load office data from localStorage (kept for backward compatibility)
            window.loadOfficeDataFromLocalStorage = function(officeId) {
                return window.loadFormDataFromLocalStorage(officeId, 'office');
            };
            
            // Clear form data from localStorage (works for both departments and offices)
            window.clearOfficeDataFromLocalStorage = function(deptId) {
                if (!deptId) {
                    return;
                }
                
                try {
                    const storageKey = `form_data_${deptId}`;
                    localStorage.removeItem(storageKey);
                } catch (error) {
                    console.error('Error clearing form data from localStorage:', error);
                }
            };
            
            // Alias for consistency - clear form data for any department/office
            window.clearFormDataFromLocalStorage = function(deptId) {
                window.clearOfficeDataFromLocalStorage(deptId);
            };
            
            // Event listeners
            if (departmentSearch) {
                departmentSearch.addEventListener('focus', function(e) {
                    e.stopPropagation();
                    showDepartmentDropdown();
                });
                
                departmentSearch.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showDepartmentDropdown();
                });
                
                departmentSearch.addEventListener('input', function() {
                    filterDepartmentOptions();
                    toggleDepartmentClearButton();
                    if (departmentDropdown) {
                        departmentDropdown.classList.remove('hidden');
                    }
                });
            }
            
            const dropdownIcon = document.getElementById('departmentDropdownIcon');
            if (dropdownIcon) {
                dropdownIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (departmentDropdown && departmentDropdown.classList.contains('hidden')) {
                        showDepartmentDropdown();
                    } else {
                        hideDepartmentDropdown();
                    }
                });
            }
            
            if (departmentOptions.length > 0) {
                departmentOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        selectDepartment(this.dataset.id, this.dataset.name, 'department');
                    });
                });
            }
            
            // Office event listeners
            if (officeSearch) {
                officeSearch.addEventListener('focus', function(e) {
                    e.stopPropagation();
                    showOfficeDropdown();
                });
                
                officeSearch.addEventListener('click', function(e) {
                    e.stopPropagation();
                    showOfficeDropdown();
                });
                
                officeSearch.addEventListener('input', function() {
                    filterOfficeOptions();
                    toggleOfficeClearButton();
                    if (officeDropdown) {
                        officeDropdown.classList.remove('hidden');
                    }
                });
            }
            
            const officeDropdownIcon = document.getElementById('officeDropdownIcon');
            if (officeDropdownIcon) {
                officeDropdownIcon.addEventListener('click', function(e) {
                    e.stopPropagation();
                    if (officeDropdown && officeDropdown.classList.contains('hidden')) {
                        showOfficeDropdown();
                    } else {
                        hideOfficeDropdown();
                    }
                });
            }
            
            if (officeOptions.length > 0) {
                officeOptions.forEach(option => {
                    option.addEventListener('click', function(e) {
                        e.stopPropagation();
                        selectDepartment(this.dataset.id, this.dataset.name, 'office');
                    });
                });
            }
            
            // Close dropdowns when clicking outside
            document.addEventListener('click', function(e) {
                if (departmentSearchContainer && !departmentSearchContainer.contains(e.target)) {
                    hideDepartmentDropdown();
                }
                if (officeSearchContainer && !officeSearchContainer.contains(e.target)) {
                    hideOfficeDropdown();
                }
            });
            
            // Load saved allocation on page load - restore last selection from localStorage
            function restoreSelectionOnPageLoad() {
                // First check localStorage for last selection
                try {
                    const lastSelection = localStorage.getItem('lastSelectedDepartment');
                    if (lastSelection) {
                        const selection = JSON.parse(lastSelection);
                        if (selection && selection.id && selection.name) {
                            // Restore the selection
                            if (selection.type === 'office') {
                                if (officeSelect) officeSelect.value = selection.id;
                                if (officeSearch) officeSearch.value = selection.name;
                            } else {
                                if (departmentSelect) departmentSelect.value = selection.id;
                                if (departmentSearch) departmentSearch.value = selection.name;
                            }
                            
                            if (selectedDepartmentName) selectedDepartmentName.textContent = selection.name;
                            if (selectedDepartmentDiv) selectedDepartmentDiv.classList.remove('hidden');
                            
                            // Set global variables
                            window.selectedDepartmentType = selection.type;
                            window.selectedDepartmentId = selection.id;
                            window.selectedDepartmentName = selection.name;
                            
                            // Update clear button visibility after setting values
                            toggleDepartmentClearButton();
                            toggleOfficeClearButton();
                            
                            // Update UI based on type
                            const isOffice = selection.type === 'office';
                            toggleSectionsForType(isOffice, selection.name);
                            
                            // Restructure Fiduciary section for offices
                            if (isOffice) {
                                restructureFiduciaryForOffice();
                                setTimeout(() => {
                                    // DATABASE IS THE SINGLE SOURCE OF TRUTH
                                    // Load from database first, then restore localStorage to preserve unsaved edits
                                    loadSavedAllocation(selection.id);
                                }, 200);
                            } else {
                                restoreFiduciaryForDepartment();
                                // DATABASE IS THE SINGLE SOURCE OF TRUTH
                                // Load from database first, then restore localStorage to preserve unsaved edits
                                loadSavedAllocation(selection.id);
                            }
                            return true;
                        }
                    }
                } catch (error) {
                    console.error('Error restoring selection:', error);
                }
                
                // Fallback: check hidden inputs
                if (departmentSelect && departmentSelect.value) {
                    const selectedName = departmentSearch ? departmentSearch.value : '';
                    if (selectedName && selectedDepartmentName) {
                        selectedDepartmentName.textContent = selectedName;
                        if (selectedDepartmentDiv) selectedDepartmentDiv.classList.remove('hidden');
                    }
                    // Update clear button visibility
                    toggleDepartmentClearButton();
                    toggleOfficeClearButton();
                    loadSavedAllocation(departmentSelect.value);
                    return true;
                }
                
                if (officeSelect && officeSelect.value) {
                    const selectedName = officeSearch ? officeSearch.value : '';
                    if (selectedName && selectedDepartmentName) {
                        selectedDepartmentName.textContent = selectedName;
                        if (selectedDepartmentDiv) selectedDepartmentDiv.classList.remove('hidden');
                    }
                    // Update clear button visibility
                    toggleDepartmentClearButton();
                    toggleOfficeClearButton();
                    const isOffice = true;
                    toggleSectionsForType(isOffice, selectedName);
                    restructureFiduciaryForOffice();
                    setTimeout(() => {
                        loadSavedAllocation(officeSelect.value);
                        setTimeout(() => {
                            if (window.loadFormDataFromLocalStorage) {
                                window.loadFormDataFromLocalStorage(officeSelect.value, 'office');
                            }
                        }, 500);
                    }, 200);
                    return true;
                }
                
                // Update clear button visibility even if no selection is restored
                toggleDepartmentClearButton();
                toggleOfficeClearButton();
                
                return false;
            }
            
            // Restore selection on page load
            restoreSelectionOnPageLoad();
        });
        
        // Initialize clear button visibility on page load (fallback)
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                // Small delay to ensure restoreSelectionOnPageLoad has completed
                setTimeout(() => {
                    toggleDepartmentClearButton();
                    toggleOfficeClearButton();
                }, 100);
            });
        } else {
            // Small delay to ensure restoreSelectionOnPageLoad has completed
            setTimeout(() => {
                toggleDepartmentClearButton();
                toggleOfficeClearButton();
            }, 100);
        }

        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
    if (dropdown) {
        dropdown.classList.toggle('hidden');
            }
        }

document.addEventListener('click', function(event) {
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
        
        // Set % for All Modal Functions
        function openSetPercentModal() {
            const modal = document.getElementById('setPercentModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Load current percentages if they exist
                const categories = ['facultyStaff', 'curriculum', 'student', 'facilities'];
                categories.forEach(category => {
                    const percentInput = document.getElementById(category + 'Percent');
                    const modalInput = document.getElementById(category + 'Percent_modal');
                    if (percentInput && modalInput) {
                        const currentValue = percentInput.value.replace('%', '').trim();
                        modalInput.value = currentValue || '';
                    }
                });
            }
        }
        
        function closeSetPercentModal() {
            const modal = document.getElementById('setPercentModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        function applyPercentToAll() {
            const categories = [
                { id: 'facultyStaff', name: 'Faculty and Staff Development' },
                { id: 'curriculum', name: 'Curriculum Development' },
                { id: 'student', name: 'Student Development' },
                { id: 'facilities', name: 'Facilities Development' }
            ];
            
            // Get values from modal
            const percentages = {};
            let hasValues = false;
            let totalPercent = 0;
            
            categories.forEach(category => {
                const modalInput = document.getElementById(category.id + 'Percent_modal');
                if (modalInput && modalInput.value) {
                    const value = parseFloat(modalInput.value) || 0;
                    
                    // Validate: minimum percentage is 7.5%
                    if (value > 0 && value < 7.5) {
                        alert(`${category.name} percentage must be at least 7.5% or 0% (to skip).`);
                        throw new Error('Invalid percentage');
                    }
                    
                    // Validate: each percentage must be between 0 and 100
                    if (value < 0 || value > 100) {
                        alert(`${category.name} percentage must be between 7.5% and 100%.`);
                        throw new Error('Invalid percentage');
                    }
                    
                    percentages[category.id] = value;
                    totalPercent += value;
                    hasValues = true;
                }
            });
            
            if (!hasValues) {
                alert('Please enter at least one percentage value.');
                return;
            }
            
            // Validate: total percentage must not exceed 100%
            if (totalPercent > 100) {
                alert(`Total percentage (${totalPercent.toFixed(2)}%) exceeds 100%. The maximum total is 100%. Please adjust the values.`);
                return;
            }
            
            // Apply to current form
            categories.forEach(category => {
                if (percentages[category.id] !== undefined) {
                    const percentInput = document.getElementById(category.id + 'Percent');
                    if (percentInput) {
                        percentInput.value = percentages[category.id];
                        // Trigger calculation using the correct function name
                        if (typeof calculateBreakdownRow === 'function') {
                            calculateBreakdownRow(category.id);
                        }
                    }
                }
            });
            
            // Recalculate totals
            if (typeof calculateNonFiduciaryTotals === 'function') {
                calculateNonFiduciaryTotals();
            }
            
            // Save to localStorage for sync across departments/offices
            // Use the correct key names that match loadPercentagesFromStorage
            // Include fiscal year in the key to separate data by year
            const fiscalYearSelect = document.getElementById('fiscalYearSelect');
            const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
            
            const syncData = {
                facultyStaff: percentages.facultyStaff || 0,
                curriculum: percentages.curriculum || 0,
                student: percentages.student || 0,
                facilities: percentages.facilities || 0,
                timestamp: new Date().getTime()
            };
            
            localStorage.setItem(`nonFiduciaryPercentages_${fiscalYear}`, JSON.stringify(syncData));
            
            // Show success message
            alert(`Percentages applied successfully for Fiscal Year ${fiscalYear}! Total: ${totalPercent.toFixed(2)}%`);
            
            // Close modal
            closeSetPercentModal();
            
            // Auto-save to localStorage
            if (window.saveFormDataToLocalStorage) {
                window.saveFormDataToLocalStorage();
            }
        }
        
        // Close modal when clicking outside
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('setPercentModal');
            if (modal && event.target === modal) {
                closeSetPercentModal();
            }
        });
        
        // Cleanup polling when page unloads
        window.addEventListener('beforeunload', function() {
            stopSyncPolling();
        });

    // Set default fiduciary items for specific departments
    function setDefaultFiduciaryItems(departmentName) {
        if (!departmentName) {
            // console.log('setDefaultFiduciaryItems: No department name provided');
            return;
        }
        
        // console.log('setDefaultFiduciaryItems called with:', departmentName);
        
        // Departments that should have default fiduciary items
        const departmentsWithDefaults = [
            'Computer Studies',
            'Education',
            'Engineering',
            'Industrial',
            'Hospitality Management'
        ];
        
        // Default items
        const defaultItems = [
            'Laboratory Fee',
            'Computer Fee',
            'Computer Lab',
            'Internet Fee',
            'CCNA',
            'Development Fee'
        ];
        
        // Check if department name matches (case-insensitive)
        const hasDefaults = departmentsWithDefaults.some(dept => 
            departmentName.toLowerCase().includes(dept.toLowerCase())
        );
        
        // console.log('hasDefaults:', hasDefaults, 'for department:', departmentName);
        
        if (hasDefaults) {
            // Set defaults - use longer timeout to ensure form is cleared
            setTimeout(() => {
                // console.log('Setting default fiduciary items...');
                for (let i = 1; i <= 6; i++) {
                    const itemField = document.getElementById('fiduciaryItem' + i);
                    if (itemField) {
                        // Always set defaults for these departments (can be edited later)
                        itemField.value = defaultItems[i - 1] || '';
                        // console.log(`Set fiduciaryItem${i} to:`, defaultItems[i - 1]);
                    } else {
                        console.warn(`fiduciaryItem${i} not found`);
                    }
                }
            }, 500);
        } else {
            // console.log('Department does not match default list');
        }
    }
    
    // Real-time sync: Poll database for updates from other budget role accounts
    let syncPollingInterval = null;
    let lastKnownUpdateTime = null;
    let lastKnownDraftTime = null;
    let userIsTyping = false;
    let lastUserActivity = Date.now();
    
    // Track user activity to prevent sync interruptions while typing
    document.addEventListener('input', function() {
        userIsTyping = true;
        lastUserActivity = Date.now();
        
        // Reset typing flag after 2 seconds of no input
        clearTimeout(window.typingTimeout);
        window.typingTimeout = setTimeout(() => {
            userIsTyping = false;
        }, 2000);
    });
    
    // Also track focus on input fields
    document.addEventListener('focusin', function(e) {
        if (e.target.matches('input, textarea, select')) {
            userIsTyping = true;
            lastUserActivity = Date.now();
        }
    });
    
    document.addEventListener('focusout', function(e) {
        if (e.target.matches('input, textarea, select')) {
            // Wait a bit before allowing sync (user might be tabbing to next field)
            setTimeout(() => {
                userIsTyping = false;
            }, 1000);
        }
    });
    
    function startSyncPolling(departmentId) {
        // Clear any existing polling
        if (syncPollingInterval) {
            clearInterval(syncPollingInterval);
        }
        
        // Sync indicator is hidden - no need to show it
        
        // Poll every 1 second to check for updates (faster sync for real-time collaboration)
        syncPollingInterval = setInterval(() => {
            if (!window.selectedDepartmentId || window.selectedDepartmentId !== departmentId) {
                // Department changed, stop polling
                clearInterval(syncPollingInterval);
                return;
            }
            
            // CRITICAL: Don't sync if user is actively typing or recently active (5 second idle time)
            // Also don't sync if any modal is open (user is interacting with UI)
            const hasOpenModal = document.querySelector('.fixed.inset-0:not(.hidden)') !== null;
            if (userIsTyping || (Date.now() - lastUserActivity < 5000) || hasOpenModal) {
                // console.log('Skipping sync - user is active or modal is open');
                return;
            }
            
            // Check for draft updates (shared localStorage across accounts)
            fetch(`../api/get_allocation_draft.php?department_id=${departmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const serverDraftTime = data.data.updated_at;
                        
                        // If this is the first poll, load the data and store the timestamp
                        if (!lastKnownDraftTime) {
                            lastKnownDraftTime = serverDraftTime;
                            
                            // Load the draft data from database on first poll
                            const draftData = data.data.draft_data;
                            if (draftData) {
                                // Include fiscal year in the key to load data for the correct year
                                const fiscalYearSelect = document.getElementById('fiscalYearSelect');
                                const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
                                const storageKey = `form_data_${departmentId}_${fiscalYear}`;
                                const localData = localStorage.getItem(storageKey);
                                
                                // Only load from database if local storage is empty or different
                                if (!localData || localData !== JSON.stringify(draftData)) {
                                    localStorage.setItem(storageKey, JSON.stringify(draftData));
                                    
                                    // Reload the form with the draft data from database
                                    const isOffice = window.selectedDepartmentType === 'office';
                                    if (window.loadFormDataFromLocalStorage) {
                                        window.loadFormDataFromLocalStorage(departmentId, isOffice ? 'office' : 'department');
                                    }
                                    console.log('Loaded draft data from database on initial sync');
                                }
                            }
                            return;
                        }
                        
                        // Check if draft was updated by another user
                        if (serverDraftTime !== lastKnownDraftTime) {
                            lastKnownDraftTime = serverDraftTime;
                            
                            // Double-check user is not typing before updating (5 second idle time)
                            // Also check if any modal is open
                            const hasOpenModal = document.querySelector('.fixed.inset-0:not(.hidden)') !== null;
                            if (userIsTyping || (Date.now() - lastUserActivity < 5000) || hasOpenModal) {
                                console.log('Draft update available but user is active or modal is open - will sync later');
                                return;
                            }
                            
                            // Update local localStorage with the shared draft data
                            const draftData = data.data.draft_data;
                            if (draftData) {
                                // Include fiscal year in the key to load data for the correct year
                                const fiscalYearSelect = document.getElementById('fiscalYearSelect');
                                const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
                                const storageKey = `form_data_${departmentId}_${fiscalYear}`;
                                localStorage.setItem(storageKey, JSON.stringify(draftData));
                                
                                // Show notification
                                showSyncNotification('Draft synced from another account');
                                
                                // Reload the form with the updated draft data
                                const isOffice = window.selectedDepartmentType === 'office';
                                if (window.loadFormDataFromLocalStorage) {
                                    window.loadFormDataFromLocalStorage(departmentId, isOffice ? 'office' : 'department');
                                }
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Draft sync polling error:', error);
                });
            
            // Also check for saved allocation updates
            fetch(`../api/get_budget_breakdown.php?department_id=${departmentId}&fiscal_year=${new Date().getFullYear()}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        const serverUpdateTime = data.data.updated_at;
                        
                        // If this is the first poll, load the data and store the timestamp
                        if (!lastKnownUpdateTime) {
                            lastKnownUpdateTime = serverUpdateTime;
                            
                            // Load saved allocation from database on first poll
                            // This ensures data is synced even if the other account isn't logged in
                            if (data.data && Object.keys(data.data).length > 0) {
                                // Check if we have any actual allocation data (not just metadata)
                                const hasAllocationData = data.data.ps_salaries || data.data.ps_other_compensation || 
                                                         data.data.mooe || data.data.capital_outlay || data.data.fiduciary;
                                
                                if (hasAllocationData) {
                                    // Load data from database
                                    populateFormFromData(data.data);
                                    
                                    // Recalculate and update summary
                                    setTimeout(() => {
                                        generateSummaryForEdit();
                                    }, 300);
                                    console.log('Loaded saved allocation from database on initial sync');
                                }
                            }
                            return;
                        }
                        
                        // Check if data was updated by another user
                        if (serverUpdateTime !== lastKnownUpdateTime) {
                            lastKnownUpdateTime = serverUpdateTime;
                            
                            // Double-check user is not typing before updating (5 second idle time)
                            // Also check if any modal is open
                            const hasOpenModal = document.querySelector('.fixed.inset-0:not(.hidden)') !== null;
                            if (userIsTyping || (Date.now() - lastUserActivity < 5000) || hasOpenModal) {
                                console.log('Saved allocation update available but user is active or modal is open - will sync later');
                                return;
                            }
                            
                            // Show notification that data was synced
                            showSyncNotification('Saved allocation synced from another account');
                            
                            // Reload data from database (this will sync across all budget accounts)
                            populateFormFromData(data.data);
                            
                            // Recalculate and update summary
                            setTimeout(() => {
                                generateSummaryForEdit();
                            }, 300);
                        }
                    }
                })
                .catch(error => {
                    console.error('Sync polling error:', error);
                });
        }, 1000); // Poll every 1 second for faster real-time sync
    }
    
    function stopSyncPolling() {
        if (syncPollingInterval) {
            clearInterval(syncPollingInterval);
            syncPollingInterval = null;
        }
        lastKnownUpdateTime = null;
        lastKnownDraftTime = null;
        
        // Sync indicator is hidden - no action needed
    }
    
    function showSyncNotification(message = 'Data synced from another account') {
        // Sync notification is disabled - silent sync only
        // Only log in development mode (comment out for production)
        // console.log(message);
    }
    
    // Load saved allocation when department is selected
    function loadSavedAllocation(departmentId) {
        if (!departmentId) {
            clearForm();
            stopSyncPolling(); // Stop polling when no department selected
            // Set defaults if department is selected
            const departmentSearch = document.getElementById('departmentSearch');
            const officeSearch = document.getElementById('officeSearch');
            const selectedName = (departmentSearch && departmentSearch.value) ? departmentSearch.value : (officeSearch && officeSearch.value ? officeSearch.value : '');
            if (selectedName) {
                setDefaultFiduciaryItems(selectedName);
            }
            return;
        }
        
        // Always reset and allow loading when called (the selectDepartment function handles preventing duplicates)
        // Mark that we're loading
        window.currentLoadingDepartmentId = departmentId;
        window.isLoadingAllocation = true;
        
        // Start real-time sync polling for this department
        startSyncPolling(departmentId);
        
        fetch(`../api/get_budget_breakdown.php?department_id=${departmentId}&fiscal_year=${new Date().getFullYear()}`)
            .then(response => response.json())
            .then(data => {
                // Check if we're still loading the same department
                if (window.currentLoadingDepartmentId !== departmentId) {
                    return;
                }
                
                if (data.success && data.data) {
                    // Store the initial update timestamp for sync polling
                    lastKnownUpdateTime = data.data.updated_at;
                    
                    // DATABASE IS THE SINGLE SOURCE OF TRUTH - All budget role accounts share the same database
                    // Load database data first
                    populateFormFromData(data.data);
                    
                    // Apply "Set % for All" percentages from localStorage (overrides database values)
                    // This ensures percentages sync across all departments when set globally
                    if (typeof loadPercentagesFromStorage === 'function') {
                        loadPercentagesFromStorage();
                    }
                    
                    // Load additional amount from separate per-dept/per-year storage
                    setTimeout(() => {
                        if (typeof loadAdditionalAmountFromStorage === 'function') {
                            loadAdditionalAmountFromStorage();
                        }
                    }, 600);
                    
                    // Automatically display summary if data exists (edit mode)
                    setTimeout(() => {
                        generateSummaryForEdit();
                        // After loading from database, restore localStorage data to preserve unsaved edits
                        // This allows users to continue editing and their work persists on refresh
                        const isOffice = window.selectedDepartmentType === 'office';
                        if (window.loadFormDataFromLocalStorage && window.currentLoadingDepartmentId === departmentId) {
                            setTimeout(() => {
                                // Double-check we're still loading the same department
                                if (window.currentLoadingDepartmentId === departmentId) {
                                    window.loadFormDataFromLocalStorage(departmentId, isOffice ? 'office' : 'department');
                                }
                            }, 300);
                        }
                    }, 500);
                } else {
                    // No database data found - clear form and show defaults
                    lastKnownUpdateTime = null;
                    clearForm(true); // Pass true to set defaults after clearing (includes loadPercentagesFromStorage)
                    hideSummary();
                    
                    // Apply "Set % for All" percentages from localStorage
                    // This ensures percentages sync across all departments when set globally
                    if (typeof loadPercentagesFromStorage === 'function') {
                        loadPercentagesFromStorage();
                    }
                    
                    // Load additional amount from separate per-dept/per-year storage
                    setTimeout(() => {
                        if (typeof loadAdditionalAmountFromStorage === 'function') {
                            loadAdditionalAmountFromStorage();
                        }
                    }, 400);
                    
                    // Load from localStorage to restore unsaved work
                    // This preserves user's work even if not saved to database yet
                    const isOffice = window.selectedDepartmentType === 'office';
                    if (window.loadFormDataFromLocalStorage && window.currentLoadingDepartmentId === departmentId) {
                        setTimeout(() => {
                            // Double-check we're still loading the same department
                            if (window.currentLoadingDepartmentId === departmentId) {
                                window.loadFormDataFromLocalStorage(departmentId, isOffice ? 'office' : 'department');
                            }
                        }, 300);
                    }
                    console.log('No database allocation found for department:', departmentId, '- Loading from localStorage if available');
                }
                window.isLoadingAllocation = false;
            })
            .catch(error => {
                console.error('Error loading allocation:', error);
                clearForm(true); // Pass true to set defaults after clearing (includes loadPercentagesFromStorage)
                hideSummary();
                
                // Apply "Set % for All" percentages from localStorage
                // This ensures percentages sync across all departments when set globally
                if (typeof loadPercentagesFromStorage === 'function') {
                    loadPercentagesFromStorage();
                }
                
                // On error, try loading from localStorage to preserve unsaved work
                const isOffice = window.selectedDepartmentType === 'office';
                if (window.loadFormDataFromLocalStorage && window.currentLoadingDepartmentId === departmentId) {
                    setTimeout(() => {
                        // Double-check we're still loading the same department
                        if (window.currentLoadingDepartmentId === departmentId) {
                            window.loadFormDataFromLocalStorage(departmentId, isOffice ? 'office' : 'department');
                        }
                    }, 300);
                }
                window.isLoadingAllocation = false;
            });
    }
    
    function populateFormFromData(allocationData) {
        // Ensure correct display state after populating form
        if (window.selectedDepartmentType && window.selectedDepartmentName) {
            const isOffice = window.selectedDepartmentType === 'office';
            if (typeof toggleSectionsForType === 'function') {
                toggleSectionsForType(isOffice, window.selectedDepartmentName);
            }
        }
        
        // Reset deduction counts
        deductionCounts = { facultyStaff: 0, curriculum: 0, student: 0, facilities: 0 };
        fiduciaryDeductionCounts = { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0, 6: 0 };
        
        // Clear all deduction containers first
        ['facultyStaff', 'curriculum', 'student', 'facilities'].forEach(category => {
            const container = document.getElementById(category + 'DeductionsContainer');
            if (container) container.innerHTML = '';
        });
        for (let i = 1; i <= 6; i++) {
            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
            if (container) container.innerHTML = '';
        }
        
        // Populate basic fields
        if (allocationData.num_students) {
            document.getElementById('numStudents').value = allocationData.num_students;
        }
        if (allocationData.total_tuition_fee) {
            document.getElementById('totalTuitionFee').value = formatNumber(parseFloat(allocationData.total_tuition_fee));
            calculateInstructional();
        }
        
        // Parse allocation data
        const allocData = typeof allocationData.allocation_data === 'string' 
            ? JSON.parse(allocationData.allocation_data) 
            : allocationData.allocation_data;
        
        if (!allocData) return;
        
        // Populate Non-Fiduciary Fund
        if (allocData.non_fiduciary) {
            const categories = ['facultyStaff', 'curriculum', 'student', 'facilities'];
            categories.forEach(category => {
                if (allocData.non_fiduciary[category]) {
                    const item = allocData.non_fiduciary[category];
                    
                    // Set percent
                    if (item.percent) {
                        document.getElementById(category + 'Percent').value = item.percent;
                    }
                    
                    // Set instructional (50%)
                    if (item.instructional) {
                        document.getElementById(category + 'Instructional').value = item.instructional;
                    }
                    
                    // Add deductions
                    if (item.deductions && item.deductions.length > 0) {
                        item.deductions.forEach((deduction) => {
                            addDeduction(category);
                            // Get the last added deduction row
                            const container = document.getElementById(category + 'DeductionsContainer');
                            if (container) {
                                const rows = container.querySelectorAll('[id^="deductionRow_"]');
                                const lastRow = rows[rows.length - 1];
                                if (lastRow) {
                                    const amountInput = lastRow.querySelector('[id$="_amount"]');
                                    const remarksSelect = lastRow.querySelector('select');
                                    if (amountInput && deduction.amount) {
                                        amountInput.value = deduction.amount;
                                        setupDeductionInputListeners(amountInput.id, category);
                                    }
                                    if (remarksSelect && deduction.remarks) {
                                        remarksSelect.value = deduction.remarks;
                                        const deductionRowId = amountInput ? amountInput.id.replace('_amount', '') : '';
                                        if (deductionRowId) {
                                            updateDeductionIndicator(deductionRowId);
                                        }
                                    }
                                }
                            }
                        });
                    }
                    
                    // Recalculate
                    calculateBreakdownRow(category);
                }
            });
        }
        
        // Populate Fiduciary Fund
        if (allocData.fiduciary) {
            // Check if this is office data (has deductions array directly)
            if (allocData.fiduciary.deductions && Array.isArray(allocData.fiduciary.deductions)) {
                // This is office data - ensure fiduciary section is restructured for office
                const isOffice = window.selectedDepartmentType === 'office';
                if (isOffice) {
                    restructureFiduciaryForOffice();
                }
                
                // Populate office data
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                if (allocationData.budget_allocated && budgetAllocatedInput) {
                    budgetAllocatedInput.value = formatNumber(parseFloat(allocationData.budget_allocated));
                }
                
                // Wait a bit for the structure to be created, then populate deductions
                setTimeout(() => {
                    const container = document.getElementById('officeFiduciaryDeductionsContainer');
                    if (container) {
                        // Clear container first to prevent duplicates
                        container.innerHTML = '';
                        
                        if (allocData.fiduciary.deductions && allocData.fiduciary.deductions.length > 0) {
                            // Set flag to prevent auto-save during loading
                            window.isLoadingOfficeDeductions = true;
                            
                            // Load deductions sequentially to avoid race conditions
                            let currentIndex = 0;
                            
                            function loadNextDeductionFromDB() {
                                if (currentIndex >= allocData.fiduciary.deductions.length) {
                                    // All deductions loaded, calculate totals
                                    window.isLoadingOfficeDeductions = false;
                                    if (window.calculateOfficeFiduciaryRow) {
                                        window.calculateOfficeFiduciaryRow(0);
                                    }
                                    return;
                                }
                                
                                const deduction = allocData.fiduciary.deductions[currentIndex];
                                
                                if (window.addOfficeDeduction) {
                                    // Add deduction row (skip auto-save during loading)
                                    const rowId = window.addOfficeDeduction(0, true);
                                    
                                    if (rowId) {
                                        // Find the row that was just added
                                        const row = document.getElementById(rowId);
                                        if (row) {
                                            const amountInput = row.querySelector('[id$="_amount"]');
                                            const remarksSelect = row.querySelector('select');
                                            
                                            // Set amount value
                                            if (amountInput && deduction.amount) {
                                                amountInput.value = deduction.amount;
                                            }
                                            
                                            // Set remarks value
                                            if (remarksSelect && deduction.remarks) {
                                                remarksSelect.value = deduction.remarks;
                                                if (window.updateOfficeDeductionIndicator) {
                                                    window.updateOfficeDeductionIndicator(rowId);
                                                }
                                            }
                                        }
                                    }
                                    
                                    // Move to next deduction
                                    currentIndex++;
                                    setTimeout(loadNextDeductionFromDB, 10);
                                }
                            }
                            
                            // Start loading first deduction
                            loadNextDeductionFromDB();
                        } else {
                            // No deductions in database, clear the loading flag
                            window.isLoadingOfficeDeductions = false;
                        }
                    }
                }, 100);
            } else {
                // This is department data (has rows 1-6)
                for (let i = 1; i <= 6; i++) {
                    if (allocData.fiduciary[i]) {
                        const item = allocData.fiduciary[i];
                        
                        // Set item name
                        if (item.item_name) {
                            document.getElementById('fiduciaryItem' + i).value = item.item_name;
                        }
                        
                        // Set instructional (50%)
                        if (item.instructional) {
                            document.getElementById('fiduciaryInstructional' + i).value = item.instructional;
                        }
                        
                        // Add deductions
                        if (item.deductions && item.deductions.length > 0) {
                            item.deductions.forEach((deduction) => {
                                addFiduciaryDeduction(i);
                                // Get the last added deduction row
                                const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                                if (container) {
                                    const rows = container.querySelectorAll('[id^="deductionRow_"]');
                                    const lastRow = rows[rows.length - 1];
                                    if (lastRow) {
                                        const amountInput = lastRow.querySelector('[id$="_amount"]');
                                        const remarksSelect = lastRow.querySelector('select');
                                        if (amountInput && deduction.amount) {
                                            amountInput.value = deduction.amount;
                                            setupFiduciaryDeductionInputListeners(amountInput.id, i);
                                        }
                                        if (remarksSelect && deduction.remarks) {
                                            remarksSelect.value = deduction.remarks;
                                            const deductionRowId = amountInput ? amountInput.id.replace('_amount', '') : '';
                                            if (deductionRowId) {
                                                updateDeductionIndicator(deductionRowId);
                                            }
                                        }
                                    }
                        }
                    });
                }

                        // Recalculate
                        calculateFiduciaryRow(i);
                    }
                }
            }
        }
        
        // Recalculate totals
        setTimeout(() => {
            calculateNonFiduciaryTotals();
            calculateFiduciaryTotals();
            calculateOverallTotal();
        }, 200);
    }
    
    function clearAllEntries() {
        // Stop sync polling when clearing
        stopSyncPolling();
        
        // Clear department selection
        const departmentSearch = document.getElementById('departmentSearch');
        const departmentSelect = document.getElementById('departmentSelect');
        const officeSearch = document.getElementById('officeSearch');
        const officeSelect = document.getElementById('officeSelect');
        const selectedDepartmentDiv = document.getElementById('selectedDepartment');
        
        if (departmentSearch) departmentSearch.value = '';
        if (departmentSelect) departmentSelect.value = '';
        if (officeSearch) officeSearch.value = '';
        if (officeSelect) officeSelect.value = '';
        if (selectedDepartmentDiv) selectedDepartmentDiv.classList.add('hidden');
        
        // Clear budget allocated (for offices)
        const budgetAllocatedInput = document.getElementById('budgetAllocated');
        if (budgetAllocatedInput) budgetAllocatedInput.value = '';
        
        // Clear office-specific elements
        const officeFiduciaryDeductionsContainer = document.getElementById('officeFiduciaryDeductionsContainer');
        if (officeFiduciaryDeductionsContainer) officeFiduciaryDeductionsContainer.innerHTML = '';
        
        const officeFiduciaryTotalBudget = document.getElementById('officeFiduciaryTotalBudget');
        if (officeFiduciaryTotalBudget) officeFiduciaryTotalBudget.textContent = '₱0.00';
        
        const officeFiduciaryDeductionTotal = document.getElementById('officeFiduciaryDeductionTotal');
        if (officeFiduciaryDeductionTotal) officeFiduciaryDeductionTotal.classList.add('hidden');
        
        const officeFiduciaryDeductionTotalAmount = document.getElementById('officeFiduciaryDeductionTotalAmount');
        if (officeFiduciaryDeductionTotalAmount) officeFiduciaryDeductionTotalAmount.textContent = '₱0.00';
        
        // Clear all form inputs
        clearForm(false);
        
        // Hide summary section
        hideSummary();
        
        // Reset department type
        window.selectedDepartmentType = null;
        window.selectedDepartmentName = null;
        
        window.selectedDepartmentId = null;
        
        // Restore non-fiduciary section if it was removed
        if (!document.getElementById('nonFiduciarySection') && window.nonFiduciarySectionHTML && window.nonFiduciarySectionParent) {
            const temp = document.createElement('div');
            temp.innerHTML = window.nonFiduciarySectionHTML;
            const restoredSection = temp.firstElementChild;
            if (window.nonFiduciarySectionNextSibling) {
                window.nonFiduciarySectionParent.insertBefore(restoredSection, window.nonFiduciarySectionNextSibling);
            } else {
                window.nonFiduciarySectionParent.appendChild(restoredSection);
            }
        }
        
        // Reset sections visibility
        const nonFiduciarySection = document.getElementById('nonFiduciarySection');
        const fiduciarySection = document.getElementById('fiduciarySection');
        const inputSection = document.getElementById('inputSection');
        const budgetAllocatedSection = document.getElementById('budgetAllocatedSection');
        
        if (nonFiduciarySection) nonFiduciarySection.style.display = 'block';
        if (fiduciarySection) fiduciarySection.style.display = 'block';
        if (inputSection) inputSection.style.display = 'grid';
        if (budgetAllocatedSection) budgetAllocatedSection.style.display = 'none';
        
        // Restore fiduciary section for departments
        restoreFiduciaryForDepartment();
    }
    
    function clearForm(setDefaults = false) {
        // Clear all inputs
        const numStudentsEl = document.getElementById('numStudents');
        const totalTuitionFeeEl = document.getElementById('totalTuitionFee');
        const instructionalAmountEl = document.getElementById('instructionalAmount');
        if (numStudentsEl) numStudentsEl.value = '';
        if (totalTuitionFeeEl) totalTuitionFeeEl.value = '';
        if (instructionalAmountEl) instructionalAmountEl.value = '';
        
        // Clear additional amount fields
        const additionalAmountEl = document.getElementById('additionalAmount');
        const additionalDescriptionEl = document.getElementById('additionalDescription');
        if (additionalAmountEl) additionalAmountEl.value = '';
        if (additionalDescriptionEl) additionalDescriptionEl.value = '';
        
        // Clear office-specific fields
        const budgetAllocatedInput = document.getElementById('budgetAllocated');
        if (budgetAllocatedInput) budgetAllocatedInput.value = '';
        
        const officeFiduciaryDeductionsContainer = document.getElementById('officeFiduciaryDeductionsContainer');
        if (officeFiduciaryDeductionsContainer) officeFiduciaryDeductionsContainer.innerHTML = '';
        
        const officeFiduciaryTotalBudget = document.getElementById('officeFiduciaryTotalBudget');
        if (officeFiduciaryTotalBudget) officeFiduciaryTotalBudget.textContent = '₱0.00';
        
        const officeFiduciaryDeductionTotal = document.getElementById('officeFiduciaryDeductionTotal');
        if (officeFiduciaryDeductionTotal) officeFiduciaryDeductionTotal.classList.add('hidden');
        
        const officeFiduciaryDeductionTotalAmount = document.getElementById('officeFiduciaryDeductionTotalAmount');
        if (officeFiduciaryDeductionTotalAmount) officeFiduciaryDeductionTotalAmount.textContent = '₱0.00';
        
        // Clear Non-Fiduciary
        ['facultyStaff', 'curriculum', 'student', 'facilities'].forEach(category => {
            const percentEl = document.getElementById(category + 'Percent');
            const instructionalEl = document.getElementById(category + 'Instructional');
            const budgetAllocationEl = document.getElementById(category + 'BudgetAllocation');
            if (percentEl) percentEl.value = '';
            if (instructionalEl) instructionalEl.value = '';
            if (budgetAllocationEl) budgetAllocationEl.value = '';
            const container = document.getElementById(category + 'DeductionsContainer');
            if (container) container.innerHTML = '';
        });
        
        // Clear Fiduciary
        for (let i = 1; i <= 6; i++) {
            const itemField = document.getElementById('fiduciaryItem' + i);
            const instructionalField = document.getElementById('fiduciaryInstructional' + i);
            if (itemField) itemField.value = '';
            if (instructionalField) instructionalField.value = '';
            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
            if (container) container.innerHTML = '';
            // Reset total budget display
            const totalBudgetSpan = document.getElementById('fiduciary' + i + 'TotalBudget');
            if (totalBudgetSpan) totalBudgetSpan.textContent = '₱0.00';
            const deductionTotalDiv = document.getElementById('fiduciary' + i + 'DeductionTotal');
            if (deductionTotalDiv) deductionTotalDiv.classList.add('hidden');
        }
        
        // Load percentages from localStorage (Set % for All feature)
        // This ensures percentages sync across all departments
        if (typeof loadPercentagesFromStorage === 'function') {
            loadPercentagesFromStorage();
        }
        
        calculateOverallTotal();
        
        // Set defaults after clearing if requested
        if (setDefaults) {
            const departmentSearch = document.getElementById('departmentSearch');
            if (departmentSearch && departmentSearch.value) {
                // Use longer delay to ensure form is fully cleared
                setTimeout(() => {
                    setDefaultFiduciaryItems(departmentSearch.value);
                }, 300);
            }
        }
    }
    
    // Clear Allocation Data Function
    window.clearAllocationData = function() {
        console.log('clearAllocationData called');
        // Show the confirmation modal
        document.getElementById('clearDataModal').classList.remove('hidden');
    };
    
    function closeClearDataModal() {
        document.getElementById('clearDataModal').classList.add('hidden');
    }
    
    function confirmClearData() {
        // Close first modal
        closeClearDataModal();
        
        // Clear all input fields
        const numStudents = document.getElementById('numStudents');
        const totalTuitionFee = document.getElementById('totalTuitionFee');
        const instructionalAmount = document.getElementById('instructionalAmount');
        const budgetAllocated = document.getElementById('budgetAllocated');
        
        if (numStudents) numStudents.value = '';
        if (totalTuitionFee) totalTuitionFee.value = '';
        if (instructionalAmount) instructionalAmount.value = '';
        if (budgetAllocated) budgetAllocated.value = '';
        
        // Clear Non-Fiduciary categories
        ['facultyStaff', 'curriculum', 'student', 'facilities'].forEach(function(category) {
            const percentEl = document.getElementById(category + 'Percent');
            const instructionalEl = document.getElementById(category + 'Instructional');
            const budgetAllocationEl = document.getElementById(category + 'BudgetAllocation');
            if (percentEl) percentEl.value = '';
            if (instructionalEl) instructionalEl.value = '';
            if (budgetAllocationEl) budgetAllocationEl.value = '';
            
            // Clear deductions
            const container = document.getElementById(category + 'DeductionsContainer');
            if (container) container.innerHTML = '';
        });
        
        // Clear Fiduciary items
        for (let i = 1; i <= 6; i++) {
            const itemField = document.getElementById('fiduciaryItem' + i);
            const instructionalField = document.getElementById('fiduciaryInstructional' + i);
            if (itemField) itemField.value = '';
            if (instructionalField) instructionalField.value = '';
            
            // Clear deductions
            const container = document.getElementById('fiduciaryDeductionsContainer' + i);
            if (container) container.innerHTML = '';
            
            // Reset total budget display
            const totalBudgetSpan = document.getElementById('fiduciary' + i + 'TotalBudget');
            if (totalBudgetSpan) totalBudgetSpan.textContent = '₱0.00';
            
            const deductionTotalDiv = document.getElementById('fiduciary' + i + 'DeductionTotal');
            if (deductionTotalDiv) deductionTotalDiv.classList.add('hidden');
        }
        
        // Clear office-specific fields
        const officeFiduciaryDeductionsContainer = document.getElementById('officeFiduciaryDeductionsContainer');
        if (officeFiduciaryDeductionsContainer) officeFiduciaryDeductionsContainer.innerHTML = '';
        
        const officeFiduciaryTotalBudget = document.getElementById('officeFiduciaryTotalBudget');
        if (officeFiduciaryTotalBudget) officeFiduciaryTotalBudget.textContent = '₱0.00';
        
        const officeFiduciaryDeductionTotal = document.getElementById('officeFiduciaryDeductionTotal');
        if (officeFiduciaryDeductionTotal) officeFiduciaryDeductionTotal.classList.add('hidden');
        
        const officeFiduciaryDeductionTotalAmount = document.getElementById('officeFiduciaryDeductionTotalAmount');
        if (officeFiduciaryDeductionTotalAmount) officeFiduciaryDeductionTotalAmount.textContent = '₱0.00';
        
        // Recalculate totals (will show 0)
        calculateOverallTotal();
        
        // Hide summary section
        const summarySection = document.getElementById('summarySection');
        if (summarySection) summarySection.classList.add('hidden');
        
        // Show second modal asking about database
        document.getElementById('clearDatabaseModal').classList.remove('hidden');
    }
    
    function closeClearDatabaseModal() {
        document.getElementById('clearDatabaseModal').classList.add('hidden');
    }
    
    function confirmClearDatabase() {
        const departmentSelect = document.getElementById('departmentSelect');
        const officeSelect = document.getElementById('officeSelect');
        const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
        
        if (!departmentId) {
            alert('⚠️ Please select a department or office first.');
            closeClearDatabaseModal();
            return;
        }
        
        fetch('../api/clear_allocation_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                department_id: departmentId
            })
        })
        .then(function(response) {
            return response.json();
        })
        .then(function(data) {
            closeClearDatabaseModal();
            if (data.success) {
                // Clear ALL localStorage related to allocations for this department
                const keysToRemove = [];
                for (let i = 0; i < localStorage.length; i++) {
                    const key = localStorage.key(i);
                    if (key && (
                        key.includes('allocation') || 
                        key.includes('department_' + departmentId) ||
                        key.includes('dept_' + departmentId) ||
                        key.includes('deduction') ||
                        key.includes('fiduciary') ||
                        key.includes('non_fiduciary')
                    )) {
                        keysToRemove.push(key);
                    }
                }
                
                // Remove all identified keys
                keysToRemove.forEach(key => {
                    localStorage.removeItem(key);
                    console.log('Removed localStorage key:', key);
                });
                
                console.log(`Cleared ${keysToRemove.length} localStorage keys`);
                
                alert('✓ Success! All allocation data has been cleared from the database and localStorage.');
                
                // Reload the page to show empty state
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                alert('❌ Error clearing data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(function(error) {
            console.error('Error:', error);
            closeClearDatabaseModal();
            alert('❌ Error clearing allocation data. Please try again.');
        });
    }
    
    function generateSummary() {
        // Pass true for scrollToSummary so it scrolls when user explicitly clicks Generate Summary button
        saveAndDisplaySummary(false, true);
    }
    
    function confirmAndSaveAllocation() {
        if (confirm('Are you sure you want to save this budget allocation? This will update the existing allocation for the selected department and fiscal year.')) {
            saveAllocationToDatabase();
        }
    }
    
    function saveAllocationToDatabase() {
        try {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            // Get fiscal year from selector
            const fiscalYearSelect = document.getElementById('fiscalYearSelect');
            const fiscalYear = fiscalYearSelect ? fiscalYearSelect.value : new Date().getFullYear();
            
            if (!departmentId) {
                alert('Please select a department or office first.');
                return;
            }
            
            // Check if this is an office or department
            const isOffice = window.selectedDepartmentType === 'office';
            
            // Collect all allocation data
            const allocationData = {
                non_fiduciary: {},
                fiduciary: {},
                is_office: isOffice
            };
            
            let numStudents = 0;
            let totalTuitionFee = 0;
            let instructionalAmount = 0;
            let budgetAllocated = 0;
            let overallTotal = 0;
            
            // Get additional amount and description
            const additionalAmountInput = document.getElementById('additionalAmount');
            const additionalDescriptionInput = document.getElementById('additionalDescription');
            const additionalAmount = additionalAmountInput ? (parseFloat(additionalAmountInput.value.replace(/[₱,]/g, '')) || 0) : 0;
            const additionalDescription = additionalDescriptionInput ? additionalDescriptionInput.value.trim() : '';
            
            if (isOffice) {
                // For offices: get budget allocated
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                budgetAllocated = budgetAllocatedInput ? parseFloat(budgetAllocatedInput.value.replace(/[₱,]/g, '')) || 0 : 0;
                
                // Collect office deductions (single section, no rows)
                const container = document.getElementById('officeFiduciaryDeductionsContainer');
                const deductions = [];
                let totalDeduction = 0;
                if (container) {
                    const deductionRows = container.querySelectorAll('[id^="officeDeductionRow_"]');
                    deductionRows.forEach(row => {
                        const amountInput = row.querySelector('[id$="_amount"]');
                        const remarksSelect = row.querySelector('select');
                        if (amountInput) {
                            const amount = amountInput.value || '₱0.00';
                            deductions.push({
                                amount: amount,
                                remarks: remarksSelect ? (remarksSelect.value || '') : ''
                            });
                            totalDeduction += parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                        }
                    });
                }
                
                // Calculate total budget (allocated budget - deductions)
                const totalBudget = budgetAllocated - totalDeduction;
                const totalBudgetFormatted = formatNumber(totalBudget);
                
                // Set overallTotal to the total budget for offices (this is what gets saved to database and displayed in dashboard)
                overallTotal = totalBudget;
                
                allocationData.fiduciary = {
                    deductions: deductions,
                    total_budget: totalBudgetFormatted
                };
            } else {
                // For departments: get num students, total tuition fee, instructional amount
                numStudents = parseInt(document.getElementById('numStudents')?.value.replace(/,/g, '') || '0') || 0;
                totalTuitionFee = parseFloat(document.getElementById('totalTuitionFee')?.value.replace(/[₱,]/g, '') || '0.00') || 0;
                instructionalAmount = parseFloat(document.getElementById('instructionalAmount')?.value.replace(/[₱,]/g, '') || '₱0.00') || 0;
                
                // Collect non-fiduciary categories
                const nonFiduciaryCategoriesForSave = ['facultyStaff', 'curriculum', 'student', 'facilities'];
                nonFiduciaryCategoriesForSave.forEach(category => {
                    const percent = document.getElementById(category + 'Percent')?.value || '0%';
                    const instructional = document.getElementById(category + 'Instructional')?.value || '₱0.00';
                    const budgetAllocation = document.getElementById(category + 'BudgetAllocation')?.value || '₱0.00';
                    
                    const container = document.getElementById(category + 'DeductionsContainer');
                    const deductions = [];
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput && remarksSelect) {
                                deductions.push({
                                    amount: amountInput.value || '₱0.00',
                                    remarks: remarksSelect.value || ''
                                });
                            }
                        });
                    }
                    
                    allocationData.non_fiduciary[category] = {
                        percent: percent,
                        instructional: instructional,
                        deductions: deductions,
                        budget_allocation: budgetAllocation
                    };
                });
                
                // Collect fiduciary rows (for departments)
                for (let i = 1; i <= 6; i++) {
                    const itemName = document.getElementById('fiduciaryItem' + i)?.value || '';
                    const instructional = document.getElementById('fiduciaryInstructional' + i)?.value || '₱0.00';
                    const instructionalValue = parseFloat(instructional.replace(/[₱,]/g, '')) || 0;
                    
                    const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                    const deductions = [];
                    let totalDeduction = 0;
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput && remarksSelect) {
                                const amount = amountInput.value || '₱0.00';
                                deductions.push({
                                    amount: amount,
                                    remarks: remarksSelect.value || ''
                                });
                                totalDeduction += parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                            }
                        });
                    }
                    
                    // Calculate total budget (instructional - deductions)
                    const totalBudget = instructionalValue - totalDeduction;
                    const totalBudgetFormatted = formatNumber(totalBudget);
                    
                    if (itemName || instructional !== '₱0.00') {
                        allocationData.fiduciary[i] = {
                            item_name: itemName,
                            instructional: instructional,
                            deductions: deductions,
                            total_budget: totalBudgetFormatted
                        };
                    }
                }
                
                // For departments: calculate overallTotal directly (don't rely on hidden field)
                // Sum non-fiduciary totals
                let nonFiduciaryTotal = 0;
                nonFiduciaryCategoriesForSave.forEach(category => {
                    const budgetAllocation = document.getElementById(category + 'BudgetAllocation')?.value || '₱0.00';
                    nonFiduciaryTotal += parseFloat(budgetAllocation.replace(/[₱,]/g, '')) || 0;
                });
                
                // Sum fiduciary totals
                let fiduciaryTotal = 0;
                for (let i = 1; i <= 6; i++) {
                    const instructional = document.getElementById('fiduciaryInstructional' + i)?.value || '₱0.00';
                    const instructionalValue = parseFloat(instructional.replace(/[₱,]/g, '')) || 0;
                    
                    // Sum deductions for this row
                    const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                    let rowDeduction = 0;
                    if (container) {
                        const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                        deductionInputs.forEach(input => {
                            const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                            rowDeduction += value;
                        });
                    }
                    
                    fiduciaryTotal += (instructionalValue - rowDeduction);
                }
                
                // Calculate overall total: non-fiduciary + fiduciary + additional
                overallTotal = nonFiduciaryTotal + fiduciaryTotal + additionalAmount;
            }
            
            // Save to database
            fetch('../api/save_allocation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    fiscal_year: fiscalYear,
                    num_students: numStudents,
                    total_tuition_fee: totalTuitionFee,
                    instructional_amount: instructionalAmount,
                    budget_allocated: budgetAllocated, // For offices
                    overall_total: overallTotal, // Total budget after deductions
                    additional_amount: additionalAmount,
                    additional_description: additionalDescription,
                    allocation_data: allocationData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Budget allocation saved successfully!');
                    
                    // Update the last known timestamp to prevent false sync notification
                    // Get the current timestamp from server
                    fetch(`../api/get_budget_breakdown.php?department_id=${departmentId}&fiscal_year=${fiscalYear}`)
                        .then(response => response.json())
                        .then(syncData => {
                            if (syncData.success && syncData.data) {
                                lastKnownUpdateTime = syncData.data.updated_at;
                            }
                        })
                        .catch(err => console.error('Error updating sync timestamp:', err));
                    
                    // DATABASE IS THE SINGLE SOURCE OF TRUTH
                    // After saving, update localStorage with the saved data so it matches database
                    // This ensures localStorage is in sync with database
                    if (window.saveFormDataToLocalStorage) {
                        window.saveFormDataToLocalStorage();
                    }
                    // Reload the saved data from database (SINGLE SOURCE OF TRUTH)
                    // This ensures all budget role accounts see the same data
                    if (window.selectedDepartmentId && window.loadSavedAllocation) {
                        setTimeout(() => {
                            window.loadSavedAllocation(window.selectedDepartmentId);
                        }, 500);
                    }
                    // Refresh allocation history if modal is open
                    const historyModal = document.getElementById('allocationHistoryModal');
                    if (historyModal && !historyModal.classList.contains('hidden')) {
                        setTimeout(() => {
                            refreshAllocationHistory();
                        }, 1000);
                    }
                } else {
                    alert('Error saving allocation: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error saving allocation:', error);
                alert('Error saving allocation. Please try again.');
            });
        } catch (error) {
            console.error('Error in saveAllocationToDatabase:', error);
            alert('An error occurred while saving.');
        }
    }

    function saveAndDisplaySummary(shouldSave = false, scrollToSummary = false) {
        try {
            // console.log('saveAndDisplaySummary called');
            
            // Check if this is an office or department
            const isOffice = window.selectedDepartmentType === 'office';
            
            // Get header information
            let numStudents = '0';
            let totalTuitionFee = '0.00';
            let instructionalAmount = '₱0.00';
            let budgetAllocated = '₱0.00';
            
            const departmentSearch = document.getElementById('departmentSearch');
            const officeSearch = document.getElementById('officeSearch');
            const departmentName = (departmentSearch && departmentSearch.value) 
                ? departmentSearch.value 
                : (officeSearch && officeSearch.value ? officeSearch.value : 'Not Selected');
            
            if (isOffice) {
                // For offices: get budget allocated
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                budgetAllocated = budgetAllocatedInput ? budgetAllocatedInput.value : '₱0.00';
                
                // Check if there's actual data
                const budgetAllocatedClean = budgetAllocated ? budgetAllocated.toString().trim() : '';
                const hasData = budgetAllocatedClean && budgetAllocatedClean !== '₱0.00' && budgetAllocatedClean !== '0.00';
                
                if (!hasData) {
                    alert('Please enter the budget allocated before generating summary.');
                    if (typeof hideSummary === 'function') {
                        hideSummary();
                    }
                    return;
                }
            } else {
                // For departments: get num students, total tuition fee, instructional amount
                numStudents = document.getElementById('numStudents')?.value || '0';
                totalTuitionFee = document.getElementById('totalTuitionFee')?.value || '0.00';
                instructionalAmount = document.getElementById('instructionalAmount')?.value || '₱0.00';
                
                // No validation needed - removed number of students requirement
            }
            
            // console.log('Data check:', { isOffice, numStudents, totalTuitionFee, instructionalAmount, budgetAllocated, departmentName });
            
            // Set header info
            const summaryDateEl = document.getElementById('summaryDate');
            if (summaryDateEl) {
                summaryDateEl.textContent = 'Date: ' + new Date().toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric' 
                });
            }
            
            const summaryDepartmentEl = document.getElementById('summaryDepartment');
            if (summaryDepartmentEl) {
                if (isOffice) {
                    summaryDepartmentEl.textContent = 'Office: ' + departmentName;
                } else {
                    summaryDepartmentEl.textContent = 'Department: ' + departmentName;
                }
            }
            
            // Set header info based on office or department
            if (isOffice) {
                // For offices: show Budget Allocated in the header (using the first column, hide others)
                const summaryHeaderInfo = document.getElementById('summaryHeaderInfo');
                if (summaryHeaderInfo) {
                    summaryHeaderInfo.style.display = 'grid';
                }
                
                // Hide student and tuition fields
                const summaryStudentsDiv = document.getElementById('summaryStudentsDiv');
                const summaryTotalTuitionDiv = document.getElementById('summaryTotalTuitionDiv');
                const summaryInstructionalDiv = document.getElementById('summaryInstructionalDiv');
                const summaryBudgetAllocatedDiv = document.getElementById('summaryBudgetAllocatedDiv');
                
                if (summaryStudentsDiv) summaryStudentsDiv.style.display = 'none';
                if (summaryTotalTuitionDiv) summaryTotalTuitionDiv.style.display = 'none';
                if (summaryInstructionalDiv) summaryInstructionalDiv.style.display = 'none';
                // Show Budget Allocated in the first position (it will take the first column)
                if (summaryBudgetAllocatedDiv) {
                    summaryBudgetAllocatedDiv.style.display = 'block';
                    const summaryBudgetAllocatedEl = document.getElementById('summaryBudgetAllocated');
                    if (summaryBudgetAllocatedEl) {
                        summaryBudgetAllocatedEl.textContent = budgetAllocated;
                    }
                }
            } else {
                // For departments: show num students, total tuition fee, instructional amount
                const summaryHeaderInfo = document.getElementById('summaryHeaderInfo');
                if (summaryHeaderInfo) {
                    summaryHeaderInfo.style.display = 'grid';
                }
                
                const summaryStudentsEl = document.getElementById('summaryStudents');
                if (summaryStudentsEl) {
                    const formattedStudents = numStudents ? numStudents.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',') : '0';
                    summaryStudentsEl.textContent = formattedStudents;
                }
                
                const summaryTotalTuitionEl = document.getElementById('summaryTotalTuition');
                if (summaryTotalTuitionEl) {
                    summaryTotalTuitionEl.textContent = totalTuitionFee ? formatNumber(parseFloat(totalTuitionFee.replace(/[₱,]/g, ''))) : '₱0.00';
                }
                
                const summaryInstructionalEl = document.getElementById('summaryInstructional');
                if (summaryInstructionalEl) {
                    summaryInstructionalEl.textContent = instructionalAmount;
                }
            }
        
            // Populate Non-Fiduciary Fund Summary (only for departments)
            const nonFiduciaryBody = document.getElementById('nonFiduciarySummaryBody');
            const nonFiduciarySection = document.getElementById('nonFiduciarySummarySection');
            
            if (isOffice) {
                // Hide non-fiduciary section for offices - offices don't have non-fiduciary funds
                if (nonFiduciarySection) {
                    nonFiduciarySection.style.display = 'none';
                }
                // Skip non-fiduciary population for offices
            } else {
                // Show and populate for departments
                if (nonFiduciarySection) {
                    nonFiduciarySection.style.display = 'block';
                }
                if (!nonFiduciaryBody) {
                    console.error('nonFiduciarySummaryBody not found');
                } else {
                    nonFiduciaryBody.innerHTML = '';
                    const nonFiduciaryCategories = [
                        { id: 'facultyStaff', name: 'Faculty and Staff Development' },
                        { id: 'curriculum', name: 'Curriculum Development' },
                        { id: 'student', name: 'Student Development' },
                        { id: 'facilities', name: 'Facilities Development' }
                    ];
                    
                    nonFiduciaryCategories.forEach(category => {
                const percentEl = document.getElementById(category.id + 'Percent');
                const instructionalEl = document.getElementById(category.id + 'Instructional');
                const budgetAllocationEl = document.getElementById(category.id + 'BudgetAllocation');
                
                if (!percentEl || !instructionalEl || !budgetAllocationEl) {
                    console.warn('Missing element for category:', category.id);
                    return;
                }
                
                const percent = percentEl.value || '0%';
                // Ensure percent always has % symbol
                const percentDisplay = percent.includes('%') ? percent : percent + '%';
                const instructional50 = instructionalEl.value || '₱0.00';
                const budgetAllocation = budgetAllocationEl.value || '₱0.00';
                const budgetAllocationValue = parseFloat(budgetAllocation.replace(/[₱,]/g, '')) || 0;
            
            // Get all deductions for this category
            const container = document.getElementById(category.id + 'DeductionsContainer');
            let deductionsHtml = '';
            let totalDeduction = 0;
            
            if (container) {
                const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                deductionRows.forEach(row => {
                    const amountInput = row.querySelector('[id$="_amount"]');
                    const remarksSelect = row.querySelector('select');
                    if (amountInput && remarksSelect) {
                        const amount = amountInput.value || '₱0.00';
                        const remarks = remarksSelect.value || '-';
                        const amountValue = parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                        totalDeduction += amountValue;
                        deductionsHtml += `<div class="text-xs">${amount} - ${remarks}</div>`;
                    }
                });
            }
            
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                            <td class="py-2 px-3">${category.name}</td>
                            <td class="text-right py-2 px-3">${percentDisplay}</td>
                            <td class="text-right py-2 px-3">${instructional50}</td>
                            <td class="text-right py-2 px-3">${deductionsHtml || '<span class="text-gray-400">-</span>'}
                                ${totalDeduction > 0 ? `<div class="text-xs font-semibold mt-1">Total: ${formatNumber(totalDeduction)}</div>` : ''}
                            </td>
                            <td class="text-right py-2 px-3 font-semibold ${budgetAllocationValue < 0 ? 'text-red-600' : ''}">${budgetAllocation}</td>
                        `;
                        nonFiduciaryBody.appendChild(row);
                    });
                    
                    // Calculate Non-Fiduciary totals for summary
                    const categories = ['facultyStaff', 'curriculum', 'student', 'facilities'];
                    let summaryTotalPercent = 0;
                    let summaryTotal50 = 0;
                    let summaryTotalDeduction = 0;
                    let summaryTotalBudget = 0;
                    
                    categories.forEach(category => {
                        const percentValue = parseFloat(document.getElementById(category + 'Percent').value.replace(/%/g, '')) || 0;
                        const instructionalValue = parseFloat(document.getElementById(category + 'Instructional').value.replace(/[₱,]/g, '')) || 0;
                        const budgetValue = parseFloat(document.getElementById(category + 'BudgetAllocation').value.replace(/[₱,]/g, '')) || 0;
                        
                        summaryTotalPercent += percentValue;
                        summaryTotal50 += instructionalValue;
                        summaryTotalBudget += budgetValue;
                        
                        // Sum deductions
                        const container = document.getElementById(category + 'DeductionsContainer');
                        if (container) {
                            const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                            deductionInputs.forEach(input => {
                                const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                                summaryTotalDeduction += value;
                            });
                        }
                    });
                    
                    // Set Non-Fiduciary totals
                    const summaryNonFiduciaryTotalPercentEl = document.getElementById('summaryNonFiduciaryTotalPercent');
                    if (summaryNonFiduciaryTotalPercentEl) {
                        summaryNonFiduciaryTotalPercentEl.textContent = summaryTotalPercent > 0 ? summaryTotalPercent.toFixed(2) + '%' : '0%';
                    }
                    
                    const summaryNonFiduciaryTotal50El = document.getElementById('summaryNonFiduciaryTotal50');
                    if (summaryNonFiduciaryTotal50El) {
                        summaryNonFiduciaryTotal50El.textContent = formatNumber(summaryTotal50);
                    }
                    
                    const summaryNonFiduciaryTotalDeductionEl = document.getElementById('summaryNonFiduciaryTotalDeduction');
                    if (summaryNonFiduciaryTotalDeductionEl) {
                        summaryNonFiduciaryTotalDeductionEl.textContent = formatNumber(summaryTotalDeduction);
                    }
                    
                    const summaryNonFiduciaryTotalBudgetEl = document.getElementById('summaryNonFiduciaryTotalBudget');
                    if (summaryNonFiduciaryTotalBudgetEl) {
                        summaryNonFiduciaryTotalBudgetEl.textContent = formatNumber(summaryTotalBudget);
                    }
                }
            }
            
            // Populate Fiduciary Fund Summary
            let fiduciaryBody = document.getElementById('fiduciarySummaryBody');
            const fiduciarySummarySection = document.getElementById('fiduciarySummarySection');
            const tableContainer = document.getElementById('fiduciarySummaryTableContainer');
            
            if (!fiduciaryBody) {
                console.error('fiduciarySummaryBody not found');
                return;
            }
            
            // For both offices and departments: ensure table structure exists
            if (tableContainer) {
                // Check if the original table structure exists, if not restore it
                const originalTable = tableContainer.querySelector('#fiduciarySummaryTable');
                if (!originalTable) {
                    // Restore the original structure (same for both offices and departments)
                    tableContainer.innerHTML = `
                        <table class="w-full text-sm" id="fiduciarySummaryTable">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Fiduciary</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Budget Collected</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Deductions</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Total Budget</th>
                                </tr>
                            </thead>
                            <tbody id="fiduciarySummaryBody">
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400" id="fiduciarySummaryFooter">
                                <tr class="font-bold">
                                    <td class="py-2 px-3">Total</td>
                                    <td class="text-right py-2 px-3" id="summaryFiduciaryTotal50"></td>
                                    <td class="text-right py-2 px-3" id="summaryFiduciaryTotalDeduction"></td>
                                    <td class="py-2 px-3">-</td>
                                    <td class="text-right py-2 px-3" id="summaryFiduciaryTotalBudget"></td>
                                </tr>
                            </tfoot>
                        </table>
                    `;
                    // Get fresh reference after restoration
                    fiduciaryBody = document.getElementById('fiduciarySummaryBody');
                    if (fiduciaryBody) {
                        fiduciaryBody.innerHTML = '';
                    }
                } else {
                    // Structure exists, just clear it
                    fiduciaryBody.innerHTML = '';
                }
                
                // Ensure table and footer are visible for both offices and departments
                const fiduciarySummaryTable = document.getElementById('fiduciarySummaryTable');
                const fiduciarySummaryFooter = document.getElementById('fiduciarySummaryFooter');
                if (fiduciarySummaryTable) {
                    fiduciarySummaryTable.style.display = 'table';
                }
                if (fiduciarySummaryFooter) {
                    fiduciarySummaryFooter.style.display = 'table-footer-group';
                }
                tableContainer.style.display = 'block';
            } else {
                // Not an office, just clear the body
                fiduciaryBody.innerHTML = '';
            }
            
            // Get references to table elements (they should exist now)
            const fiduciarySummaryTable = document.getElementById('fiduciarySummaryTable');
            const fiduciarySummaryFooter = document.getElementById('fiduciarySummaryFooter');
            
            if (isOffice) {
                // For offices: use the same table structure as departments
                // Show the summary section
                if (fiduciarySummarySection) {
                    fiduciarySummarySection.style.display = 'block';
                }
                
                // Get fresh references
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                const budgetAllocated = budgetAllocatedInput ? budgetAllocatedInput.value || '₱0.00' : '₱0.00';
                const budgetAllocatedValue = parseFloat(budgetAllocated.replace(/[₱,]/g, '')) || 0;
                
                // Get all deductions
                const container = document.getElementById('officeFiduciaryDeductionsContainer');
                let deductionsHtml = '';
                let totalDeduction = 0;
                
                if (container) {
                    const deductionRows = container.querySelectorAll('[id^="officeDeductionRow_"]');
                    deductionRows.forEach(row => {
                        const amountInput = row.querySelector('[id$="_amount"]');
                        const remarksSelect = row.querySelector('select');
                        if (amountInput) {
                            const amount = amountInput.value || '₱0.00';
                            const remarks = remarksSelect ? (remarksSelect.value || '-- Select --') : '-- Select --';
                            const amountValue = parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                            if (amountValue > 0) {
                                totalDeduction += amountValue;
                                deductionsHtml += `<div class="text-xs">${amount} - ${remarks}</div>`;
                            }
                        }
                    });
                }
                
                // Calculate total budget (Budget Allocated - deductions)
                const totalBudget = budgetAllocatedValue - totalDeduction;
                const totalBudgetFormatted = formatNumber(totalBudget);
                
                // Create a single row for offices using the same format as departments
                const row = document.createElement('tr');
                row.className = 'border-b border-gray-200';
                row.innerHTML = `
                    <td class="py-2 px-3">Budget Allocated</td>
                    <td class="text-right py-2 px-3">${budgetAllocated}</td>
                    <td class="text-right py-2 px-3">
                        ${deductionsHtml || '<span class="text-gray-400">-</span>'}
                        ${totalDeduction > 0 ? `<div class="text-xs font-semibold mt-1">Total: ${formatNumber(totalDeduction)}</div>` : ''}
                    </td>
                    <td class="text-right py-2 px-3 font-semibold ${totalBudget < 0 ? 'text-red-600' : 'text-red-900'}">${totalBudgetFormatted}</td>
                `;
                fiduciaryBody.appendChild(row);
                
            } else {
                // For departments: show table and populate 6 rows
                // Restore original structure if it was replaced (for offices)
                if (tableContainer) {
                    const originalTable = tableContainer.querySelector('#fiduciarySummaryTable');
                    if (!originalTable) {
                        // Restore the original structure
                        tableContainer.innerHTML = `
                            <table class="w-full text-sm" id="fiduciarySummaryTable">
                                <thead>
                                    <tr class="border-b border-gray-300">
                                        <th class="text-left py-2 px-3 font-semibold text-gray-700">Fiduciary</th>
                                        <th class="text-right py-2 px-3 font-semibold text-gray-700">Budget Collected</th>
                                        <th class="text-right py-2 px-3 font-semibold text-gray-700">Deductions</th>
                                        <th class="text-right py-2 px-3 font-semibold text-gray-700">Total Budget</th>
                                    </tr>
                                </thead>
                                <tbody id="fiduciarySummaryBody">
                                    <!-- Will be populated by JavaScript -->
                                </tbody>
                                <tfoot class="border-t-2 border-gray-400" id="fiduciarySummaryFooter">
                                    <tr class="font-bold">
                                        <td class="py-2 px-3">Total</td>
                                        <td class="text-right py-2 px-3" id="summaryFiduciaryTotal50"></td>
                                        <td class="text-right py-2 px-3" id="summaryFiduciaryTotalDeduction"></td>
                                        <td class="py-2 px-3">-</td>
                                        <td class="text-right py-2 px-3" id="summaryFiduciaryTotalBudget"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        `;
                        // Update references after restoration
                        const newFiduciaryBody = document.getElementById('fiduciarySummaryBody');
                        if (newFiduciaryBody) {
                            newFiduciaryBody.innerHTML = '';
                        }
                    }
                    tableContainer.style.display = 'block';
                }
                
                // Get fresh references after potential restoration
                const deptFiduciarySummaryTable = document.getElementById('fiduciarySummaryTable');
                const deptFiduciarySummaryFooter = document.getElementById('fiduciarySummaryFooter');
                const deptFiduciaryBody = document.getElementById('fiduciarySummaryBody');
                
                if (deptFiduciarySummaryTable) {
                    deptFiduciarySummaryTable.style.display = 'table';
                }
                if (deptFiduciarySummaryFooter) {
                    deptFiduciarySummaryFooter.style.display = 'table-footer-group';
                }
                
                // Use the restored body reference
                const bodyToUse = deptFiduciaryBody || fiduciaryBody;
                
                // Clear the body before populating
                if (bodyToUse) {
                    bodyToUse.innerHTML = '';
                }
                
                for (let i = 1; i <= 6; i++) {
                    const itemNameEl = document.getElementById('fiduciaryItem' + i);
                    const instructionalEl = document.getElementById('fiduciaryInstructional' + i);
                    
                    if (!itemNameEl || !instructionalEl) {
                        continue;
                    }
                    
                    const itemName = itemNameEl.value || 'Item ' + i;
                    const instructional50 = instructionalEl.value || '₱0.00';
                    const instructionalValue = parseFloat(instructional50.replace(/[₱,]/g, '')) || 0;
                    
                    // Get all deductions for this fiduciary item
                    const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                    let deductionsHtml = '';
                    let totalDeduction = 0;
                    
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput) {
                                const amount = amountInput.value || '₱0.00';
                                const remarks = remarksSelect ? (remarksSelect.value || '-- Select --') : '-- Select --';
                                const amountValue = parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                                if (amountValue > 0) {
                                    totalDeduction += amountValue;
                                    deductionsHtml += `<div class="text-xs">${amount} - ${remarks}</div>`;
                                }
                            }
                        });
                    }
                    
                    // Calculate total budget (instructional - deductions)
                    const totalBudget = instructionalValue - totalDeduction;
                    const totalBudgetFormatted = formatNumber(totalBudget);

                    // Only show rows with data
                    if (instructional50 !== '₱0.00' || totalDeduction > 0 || totalBudget !== 0) {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                            <td class="py-2 px-3">${itemName}</td>
                            <td class="text-right py-2 px-3">${instructional50}</td>
                            <td class="text-right py-2 px-3">
                                ${deductionsHtml || '<span class="text-gray-400">-</span>'}
                                ${totalDeduction > 0 ? `<div class="text-xs font-semibold mt-1">Total: ${formatNumber(totalDeduction)}</div>` : ''}
                            </td>
                            <td class="text-right py-2 px-3 font-semibold ${totalBudget < 0 ? 'text-red-600' : 'text-red-900'}">${totalBudgetFormatted}</td>
                        `;
                        if (bodyToUse) {
                            bodyToUse.appendChild(row);
                        } else {
                            fiduciaryBody.appendChild(row);
                        }
                    }
                }
            }
        
            // Set Fiduciary totals
            let fiduciaryTotal50 = 0;
            let fiduciaryTotalDeduction = 0;
            let fiduciaryTotalBudget = 0;
            
            if (isOffice) {
                // For offices: calculate from budget allocated and deductions
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                fiduciaryTotal50 = parseFloat(budgetAllocatedInput ? budgetAllocatedInput.value.replace(/[₱,]/g, '') : '0') || 0;
                
                const container = document.getElementById('officeFiduciaryDeductionsContainer');
                if (container) {
                    const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                    deductionInputs.forEach(input => {
                        fiduciaryTotalDeduction += parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                    });
                }
                
                fiduciaryTotalBudget = fiduciaryTotal50 - fiduciaryTotalDeduction;
            } else {
                // For departments: get from form fields and calculate from rows
                const fiduciaryTotal50El = document.getElementById('fiduciaryTotal50');
                if (fiduciaryTotal50El && fiduciaryTotal50El.value) {
                    fiduciaryTotal50 = parseFloat(fiduciaryTotal50El.value.replace(/[₱,]/g, '')) || 0;
                }
                
                const fiduciaryTotalDeductionEl = document.getElementById('fiduciaryTotalDeduction');
                if (fiduciaryTotalDeductionEl && fiduciaryTotalDeductionEl.value) {
                    fiduciaryTotalDeduction = parseFloat(fiduciaryTotalDeductionEl.value.replace(/[₱,]/g, '')) || 0;
                }
                
                // Calculate total budget from all fiduciary rows (instructional - deductions)
                for (let i = 1; i <= 6; i++) {
                    const instructionalField = document.getElementById('fiduciaryInstructional' + i);
                    const instructionalValue = parseFloat(instructionalField ? instructionalField.value.replace(/[₱,]/g, '') : '0') || 0;
                    
                    const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                    let rowDeduction = 0;
                    if (container) {
                        const deductionInputs = container.querySelectorAll('[id$="_amount"]');
                        deductionInputs.forEach(input => {
                            const value = parseFloat(input.value.replace(/[₱,]/g, '')) || 0;
                            rowDeduction += value;
                        });
                    }
                    
                    fiduciaryTotalBudget += (instructionalValue - rowDeduction);
                }
            }
            
            // Set summary totals (same format for both offices and departments)
            const summaryFiduciaryTotal50El = document.getElementById('summaryFiduciaryTotal50');
            if (summaryFiduciaryTotal50El) {
                summaryFiduciaryTotal50El.textContent = formatNumber(fiduciaryTotal50);
            }
            
            const summaryFiduciaryTotalDeductionEl = document.getElementById('summaryFiduciaryTotalDeduction');
            if (summaryFiduciaryTotalDeductionEl) {
                summaryFiduciaryTotalDeductionEl.textContent = formatNumber(fiduciaryTotalDeduction);
            }
            
            const summaryFiduciaryTotalBudgetEl = document.getElementById('summaryFiduciaryTotalBudget');
            if (summaryFiduciaryTotalBudgetEl) {
                summaryFiduciaryTotalBudgetEl.textContent = formatNumber(fiduciaryTotalBudget);
            }
            
            // Calculate and display Overall Total
            let overallTotal = 0;
            let totalAmountBeforeAdditional = 0;
            
            if (isOffice) {
                // For offices: use the fiduciary total budget (same as departments, just no non-fiduciary)
                totalAmountBeforeAdditional = fiduciaryTotalBudget;
            } else {
                // For departments: sum non-fiduciary total + fiduciary total
                const summaryNonFiduciaryTotalBudgetEl = document.getElementById('summaryNonFiduciaryTotalBudget');
                const nonFiduciaryTotal = parseFloat(summaryNonFiduciaryTotalBudgetEl ? summaryNonFiduciaryTotalBudgetEl.textContent.replace(/[₱,]/g, '') : '0') || 0;
                totalAmountBeforeAdditional = nonFiduciaryTotal + fiduciaryTotalBudget;
            }
            
            // Get Additional Amount if present
            const additionalAmountInput = document.getElementById('additionalAmount');
            const additionalDescriptionInput = document.getElementById('additionalDescription');
            const additionalAmount = additionalAmountInput ? (parseFloat(additionalAmountInput.value.replace(/[₱,]/g, '')) || 0) : 0;
            const additionalDescription = additionalDescriptionInput ? additionalDescriptionInput.value.trim() : '';
            
            // Calculate overall total
            overallTotal = totalAmountBeforeAdditional + additionalAmount;
            
            // Update display based on whether additional amount exists
            const summaryTotalAmountRow = document.getElementById('summaryTotalAmountRow');
            const summaryAdditionalAmountRow = document.getElementById('summaryAdditionalAmountRow');
            const summaryOverallTotalLabel = document.getElementById('summaryOverallTotalLabel');
            const summaryTotalAmountEl = document.getElementById('summaryTotalAmount');
            const summaryAdditionalAmountValueEl = document.getElementById('summaryAdditionalAmountValue');
            
            if (additionalAmount > 0) {
                // Show Total Amount, Additional Amount, and Overall Total
                if (summaryTotalAmountRow) summaryTotalAmountRow.style.display = 'flex';
                if (summaryAdditionalAmountRow) summaryAdditionalAmountRow.style.display = 'flex';
                if (summaryOverallTotalLabel) summaryOverallTotalLabel.textContent = 'Overall Total';
                
                if (summaryTotalAmountEl) {
                    summaryTotalAmountEl.textContent = formatNumber(totalAmountBeforeAdditional);
                }
                
                if (summaryAdditionalAmountValueEl) {
                    summaryAdditionalAmountValueEl.textContent = formatNumber(additionalAmount);
                }
            } else {
                // Hide Total Amount and Additional Amount rows, show only Overall Total
                if (summaryTotalAmountRow) summaryTotalAmountRow.style.display = 'none';
                if (summaryAdditionalAmountRow) summaryAdditionalAmountRow.style.display = 'none';
                if (summaryOverallTotalLabel) summaryOverallTotalLabel.textContent = 'Overall Total';
            }
            
            const summaryOverallTotalEl = document.getElementById('summaryOverallTotal');
            if (summaryOverallTotalEl) {
                summaryOverallTotalEl.textContent = formatNumber(overallTotal);
                // Color red if negative
                if (overallTotal < 0) {
                    summaryOverallTotalEl.classList.add('text-red-600');
                    summaryOverallTotalEl.classList.remove('text-maroon');
                } else {
                    summaryOverallTotalEl.classList.remove('text-red-600');
                    summaryOverallTotalEl.classList.add('text-maroon');
                }
            }
            
            // Calculate and display Deduction Breakdown by Type
            const deductionBreakdown = {
                'COS': 0,
                'Honoraria Overload': 0,
                'Part-time': 0,
                'Water': 0,
                'Electricity': 0,
                'Security': 0
            };
            
            // Collect deductions from non-fiduciary categories (for departments only)
            if (!isOffice) {
                const nonFiduciaryCategories = ['facultyStaff', 'curriculum', 'student', 'facilities'];
                nonFiduciaryCategories.forEach(category => {
                    const container = document.getElementById(category + 'DeductionsContainer');
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput && remarksSelect && remarksSelect.value) {
                                const amount = parseFloat(amountInput.value.replace(/[₱,]/g, '')) || 0;
                                const remarks = remarksSelect.value.trim();
                                if (amount > 0 && deductionBreakdown.hasOwnProperty(remarks)) {
                                    deductionBreakdown[remarks] += amount;
                                }
                            }
                        });
                    }
                });
            }
            
            // Collect deductions from fiduciary items
            if (isOffice) {
                // For offices: get deductions from office fiduciary container
                const container = document.getElementById('officeFiduciaryDeductionsContainer');
                if (container) {
                    const deductionRows = container.querySelectorAll('[id^="officeDeductionRow_"]');
                    deductionRows.forEach(row => {
                        const amountInput = row.querySelector('[id$="_amount"]');
                        const remarksSelect = row.querySelector('select');
                        if (amountInput && remarksSelect && remarksSelect.value) {
                            const amount = parseFloat(amountInput.value.replace(/[₱,]/g, '')) || 0;
                            const remarks = remarksSelect.value.trim();
                            if (amount > 0 && deductionBreakdown.hasOwnProperty(remarks)) {
                                deductionBreakdown[remarks] += amount;
                            }
                        }
                    });
                }
            } else {
                // For departments: get deductions from all fiduciary items (1-6)
                for (let i = 1; i <= 6; i++) {
                    const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput && remarksSelect && remarksSelect.value) {
                                const amount = parseFloat(amountInput.value.replace(/[₱,]/g, '')) || 0;
                                const remarks = remarksSelect.value.trim();
                                if (amount > 0 && deductionBreakdown.hasOwnProperty(remarks)) {
                                    deductionBreakdown[remarks] += amount;
                                }
                            }
                        });
                    }
                }
            }
            
            // Display the deduction breakdown
            const deductionBreakdownBody = document.getElementById('deductionBreakdownBody');
            const deductionBreakdownSection = document.getElementById('deductionBreakdownSection');
            let grandTotalDeductions = 0;
            
            if (deductionBreakdownBody && deductionBreakdownSection) {
                deductionBreakdownBody.innerHTML = '';
                
                // Define the order and display names
                const deductionTypes = [
                    { key: 'COS', label: 'COS' },
                    { key: 'Honoraria Overload', label: 'Overload' },
                    { key: 'Part-time', label: 'Part-time' },
                    { key: 'Water', label: 'Water' },
                    { key: 'Electricity', label: 'Electricity' },
                    { key: 'Security', label: 'Security' }
                ];
                
                let hasAnyDeductions = false;
                
                deductionTypes.forEach(type => {
                    const amount = deductionBreakdown[type.key] || 0;
                    if (amount > 0) {
                        hasAnyDeductions = true;
                        grandTotalDeductions += amount;
                        
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                            <td class="py-2 px-3 font-semibold">${type.label}</td>
                            <td class="text-right py-2 px-3">${formatNumber(amount)}</td>
                        `;
                        deductionBreakdownBody.appendChild(row);
                    }
                });
                
                // Show/hide the section based on whether there are any deductions
                if (hasAnyDeductions) {
                    deductionBreakdownSection.style.display = 'block';
                } else {
                    deductionBreakdownSection.style.display = 'none';
                }
                
                // Update grand total
                const deductionBreakdownGrandTotal = document.getElementById('deductionBreakdownGrandTotal');
                if (deductionBreakdownGrandTotal) {
                    deductionBreakdownGrandTotal.textContent = formatNumber(grandTotalDeductions);
                }
            }
            
            // Show save button after summary is generated
            const saveBtn = document.getElementById('saveAllocationBtn');
            if (saveBtn) {
                saveBtn.classList.remove('hidden');
            }
            
            // Show summary section only if explicitly requested (not on auto-load)
            const summarySection = document.getElementById('summarySection');
            if (summarySection && scrollToSummary) {
                summarySection.classList.remove('hidden');
                // Only scroll to summary if explicitly requested (e.g., when Generate Summary button is clicked)
                if (scrollToSummary) {
                    setTimeout(() => {
                        summarySection.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            }
            // Note: summarySection may be hidden intentionally on auto-load, no error needed
        } catch (error) {
            console.error('Error in saveAndDisplaySummary:', error);
            alert('An error occurred while generating the summary. Please check the console for details.');
        }
    }

    function closeSummary() {
        document.getElementById('summarySection').classList.add('hidden');
    }
    
    function downloadSummary() {
        try {
            // Get all summary data from the DOM first
            const summarySection = document.getElementById('summarySection');
            if (!summarySection || summarySection.classList.contains('hidden')) {
                alert('Please generate a summary first.');
                return;
            }
            
            // Get department/office ID
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (!departmentId) {
                alert('Please select a department or office first.');
                return;
            }
            
            const fiscalYear = new Date().getFullYear();
            const isOffice = window.selectedDepartmentType === 'office';
            
            // Always save the current form data first to ensure PDF has latest data (including new deductions)
            // Collect all allocation data from current form state
            const allocationData = {
                non_fiduciary: {},
                fiduciary: {},
                is_office: isOffice
            };
            
            let numStudents = 0;
            let totalTuitionFee = 0;
            let instructionalAmount = 0;
            let budgetAllocated = 0;
            let overallTotal = 0;
            
            if (isOffice) {
                // For offices: get budget allocated
                const budgetAllocatedInput = document.getElementById('budgetAllocated');
                budgetAllocated = budgetAllocatedInput ? parseFloat(budgetAllocatedInput.value.replace(/[₱,]/g, '')) || 0 : 0;
                
                // Collect office deductions (single section, no rows) - this gets the current form state
                const container = document.getElementById('officeFiduciaryDeductionsContainer');
                const deductions = [];
                let totalDeduction = 0;
                if (container) {
                    const deductionRows = container.querySelectorAll('[id^="officeDeductionRow_"]');
                    deductionRows.forEach(row => {
                        const amountInput = row.querySelector('[id$="_amount"]');
                        const remarksSelect = row.querySelector('select');
                        if (amountInput) {
                            const amount = amountInput.value || '₱0.00';
                            deductions.push({
                                amount: amount,
                                remarks: remarksSelect ? (remarksSelect.value || '') : ''
                            });
                            totalDeduction += parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                        }
                    });
                }
                
                // Calculate total budget (allocated budget - deductions)
                const totalBudget = budgetAllocated - totalDeduction;
                const totalBudgetFormatted = formatNumber(totalBudget);
                overallTotal = totalBudget;
                
                allocationData.fiduciary = {
                    deductions: deductions,
                    total_budget: totalBudgetFormatted
                };
            } else {
                // For departments: get num students, total tuition fee, instructional amount
                numStudents = parseInt(document.getElementById('numStudents')?.value.replace(/,/g, '') || '0') || 0;
                totalTuitionFee = parseFloat(document.getElementById('totalTuitionFee')?.value.replace(/[₱,]/g, '') || '0.00') || 0;
                instructionalAmount = parseFloat(document.getElementById('instructionalAmount')?.value.replace(/[₱,]/g, '') || '₱0.00') || 0;
                
                // Collect non-fiduciary categories
                const nonFiduciaryCategoriesForSave = ['facultyStaff', 'curriculum', 'student', 'facilities'];
                nonFiduciaryCategoriesForSave.forEach(category => {
                    const percent = document.getElementById(category + 'Percent')?.value || '0%';
                    const instructional = document.getElementById(category + 'Instructional')?.value || '₱0.00';
                    const budgetAllocation = document.getElementById(category + 'BudgetAllocation')?.value || '₱0.00';
                    
                    const container = document.getElementById(category + 'DeductionsContainer');
                    const deductions = [];
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput && remarksSelect) {
                                deductions.push({
                                    amount: amountInput.value || '₱0.00',
                                    remarks: remarksSelect.value || ''
                                });
                            }
                        });
                    }
                    
                    allocationData.non_fiduciary[category] = {
                        percent: percent,
                        instructional: instructional,
                        deductions: deductions,
                        budget_allocation: budgetAllocation
                    };
                });
                
                // Collect fiduciary rows (for departments)
                for (let i = 1; i <= 6; i++) {
                    const itemName = document.getElementById('fiduciaryItem' + i)?.value || '';
                    const instructional = document.getElementById('fiduciaryInstructional' + i)?.value || '₱0.00';
                    const instructionalValue = parseFloat(instructional.replace(/[₱,]/g, '')) || 0;
                    
                    const container = document.getElementById('fiduciaryDeductionsContainer' + i);
                    const deductions = [];
                    let totalDeduction = 0;
                    if (container) {
                        const deductionRows = container.querySelectorAll('[id^="deductionRow_"]');
                        deductionRows.forEach(row => {
                            const amountInput = row.querySelector('[id$="_amount"]');
                            const remarksSelect = row.querySelector('select');
                            if (amountInput && remarksSelect) {
                                const amount = amountInput.value || '₱0.00';
                                deductions.push({
                                    amount: amount,
                                    remarks: remarksSelect.value || ''
                                });
                                totalDeduction += parseFloat(amount.replace(/[₱,]/g, '')) || 0;
                            }
                        });
                    }
                    
                    // Calculate total budget (instructional - deductions)
                    const totalBudget = instructionalValue - totalDeduction;
                    const totalBudgetFormatted = formatNumber(totalBudget);
                    
                    if (itemName || instructional !== '₱0.00') {
                        allocationData.fiduciary[i] = {
                            item_name: itemName,
                            instructional: instructional,
                            deductions: deductions,
                            total_budget: totalBudgetFormatted
                        };
                    }
                }
                
                // For departments: calculate overallTotal from the form field
                const overallTotalField = document.getElementById('overallTotalBudgetAllocation');
                overallTotal = overallTotalField ? parseFloat(overallTotalField.value.replace(/[₱,]/g, '')) || 0 : 0;
            }
            
            // Save current form data to database (this includes all current deductions)
            fetch('../api/save_allocation.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    fiscal_year: fiscalYear,
                    num_students: numStudents,
                    total_tuition_fee: totalTuitionFee,
                    instructional_amount: instructionalAmount,
                    budget_allocated: budgetAllocated,
                    overall_total: overallTotal,
                    allocation_data: allocationData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // After saving, get the allocation ID and open PDF
                    setTimeout(() => {
                        fetch(`../api/get_allocation_history.php?department_id=${departmentId}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.success && data.allocations && data.allocations.length > 0) {
                                    // Get the most recent allocation (should be the one we just saved)
                                    const allocation = data.allocations[0];
                                    if (allocation && allocation.id) {
                                        window.open(`../api/generate_allocation_pdf.php?id=${allocation.id}`, '_blank');
                                    } else {
                                        alert('Error: Could not find allocation ID after saving. Please try again.');
                                    }
                                } else {
                                    alert('Error: Could not find allocation after saving. Please try again.');
                                }
                            })
                            .catch(error => {
                                console.error('Error getting allocation ID:', error);
                                alert('Error generating PDF. Please try again.');
                            });
                    }, 500);
                } else {
                    alert('Error saving allocation: ' + (data.message || 'Unknown error') + '. Please try saving manually first.');
                }
            })
            .catch(error => {
                console.error('Error saving allocation:', error);
                alert('Error saving allocation. Please try saving manually first, then download the PDF.');
            });
            
        } catch (error) {
            console.error('Error downloading summary:', error);
            alert('An error occurred while downloading the summary. Please try again.');
        }
    }
    
    function hideSummary() {
        const summarySection = document.getElementById('summarySection');
        if (summarySection) {
            summarySection.classList.add('hidden');
        }
        const saveBtn = document.getElementById('saveAllocationBtn');
        if (saveBtn) {
            saveBtn.classList.add('hidden');
        }
    }
    
    function generateSummaryForEdit() {
        // Use the existing saveAndDisplaySummary function but don't save and don't show
        // This prepares the summary data but keeps it hidden
        saveAndDisplaySummary(false, false);
        
        // Ensure summary stays hidden (don't auto-show on edit mode)
        const summarySection = document.getElementById('summarySection');
        if (summarySection) {
            summarySection.classList.add('hidden');
        }
    }
    
    let allAllocations = []; // Store all allocations for client-side filtering
    
    function showAllocationHistory() {
        // Ensure modal exists first
        const existingModal = document.getElementById('allocationHistoryModal');
        if (!existingModal) {
            // Create modal with filter dropdown
            const departments = <?php echo json_encode($departments); ?>;
            const offices = <?php echo json_encode($offices); ?>;
            let departmentOptions = '<option value="">All Departments</option>';
            departments.forEach(dept => {
                departmentOptions += `<option value="${dept.id}">${dept.dept_name}</option>`;
            });
            
            let officeOptions = '<option value="">All Offices</option>';
            offices.forEach(office => {
                officeOptions += `<option value="${office.id}">${office.dept_name}</option>`;
            });
            
            const modalHTML = `
                <div id="allocationHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6">
                            <div class="flex justify-between items-center mb-4">
                                <h2 class="text-2xl font-bold">Allocation History</h2>
                                <div class="flex items-center gap-3">
                                    <button 
                                        onclick="deleteAllAllocations()" 
                                        id="deleteAllBtn"
                                        class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors font-semibold text-sm flex items-center gap-2"
                                        title="Delete all allocations"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Delete All
                                    </button>
                                    <button onclick="closeAllocationHistory()" class="text-white hover:text-red-200 transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                                    </button>
                                </div>
                </div>
                            <div class="bg-white bg-opacity-20 rounded-lg p-3">
                                <div class="grid grid-cols-3 gap-3">
                                    <div>
                                        <label for="historyDepartmentFilter" class="block text-xs font-semibold mb-1">Department:</label>
                                        <select 
                                            id="historyDepartmentFilter" 
                                            onchange="filterHistoryByDepartment('department')"
                                            class="w-full px-2 py-1.5 text-sm rounded-lg bg-white text-gray-900 border-2 border-white border-opacity-30 focus:ring-2 focus:ring-white focus:border-white outline-none transition-all"
                                        >
                                            ${departmentOptions}
                                        </select>
            </div>
                                    <div>
                                        <label for="historyOfficeFilter" class="block text-xs font-semibold mb-1">Office:</label>
                                        <select 
                                            id="historyOfficeFilter" 
                                            onchange="filterHistoryByDepartment('office')"
                                            class="w-full px-2 py-1.5 text-sm rounded-lg bg-white text-gray-900 border-2 border-white border-opacity-30 focus:ring-2 focus:ring-white focus:border-white outline-none transition-all"
                                        >
                                            ${officeOptions}
                                        </select>
            </div>
                                    <div>
                                        <label for="historyYearFilter" class="block text-xs font-semibold mb-1">Year:</label>
                                        <select 
                                            id="historyYearFilter" 
                                            onchange="filterHistoryByDepartment('year')"
                                            class="w-full px-2 py-1.5 text-sm rounded-lg bg-white text-gray-900 border-2 border-white border-opacity-30 focus:ring-2 focus:ring-white focus:border-white outline-none transition-all"
                                        >
                                            <option value="">All Years</option>
                                        </select>
        </div>
    </div>
                </div>
            </div>
                        <div id="allocationHistoryContent" class="flex-1 overflow-y-auto p-6">
                            <div class="text-center py-8">
                                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-maroon mx-auto"></div>
                                <p class="text-gray-600 mt-4">Loading history...</p>
            </div>
        </div>
    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
        
        // Show modal
        const modal = document.getElementById('allocationHistoryModal');
        if (modal) {
            modal.classList.remove('hidden');
        }
        
        const departmentFilter = document.getElementById('historyDepartmentFilter')?.value || '';
        const officeFilter = document.getElementById('historyOfficeFilter')?.value || '';
        const yearFilter = document.getElementById('historyYearFilter')?.value || '';
        
        let url = '../api/get_allocation_history.php?';
        const params = [];
        // Use office filter if selected, otherwise use department filter
        const selectedFilter = officeFilter || departmentFilter;
        if (selectedFilter) params.push(`department_id=${selectedFilter}`);
        if (yearFilter) params.push(`year=${yearFilter}`);
        url += params.length > 0 ? params.join('&') : '';
        
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allAllocations = data.allocations; // Store for reference
                    displayAllocationHistory(data.allocations);
                    
                    // Populate year filter with available years from data
                    populateYearFilter(data.allocations);
                } else {
                    alert('Error loading history: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error loading allocation history');
            });
    }
    
    // Populate year filter dropdown with available years
    function populateYearFilter(allocations) {
        const yearFilter = document.getElementById('historyYearFilter');
        if (!yearFilter) return;
        
        const currentValue = yearFilter.value;
        const years = new Set();
        
        allocations.forEach(allocation => {
            if (allocation.created_at) {
                const year = new Date(allocation.created_at).getFullYear();
                years.add(year);
            }
        });
        
        const sortedYears = Array.from(years).sort((a, b) => b - a);
        
        yearFilter.innerHTML = '<option value="">All Years</option>';
        sortedYears.forEach(year => {
            const option = document.createElement('option');
            option.value = year;
            option.textContent = year;
            if (year.toString() === currentValue) {
                option.selected = true;
            }
            yearFilter.appendChild(option);
        });
    }
    
    // Refresh history after saving allocation
    function refreshAllocationHistory() {
        if (typeof showAllocationHistory === 'function') {
            // Get current filters
            const departmentFilter = document.getElementById('historyDepartmentFilter')?.value || '';
            const officeFilter = document.getElementById('historyOfficeFilter')?.value || '';
            const yearFilter = document.getElementById('historyYearFilter')?.value || '';
            
            // Reload history with same filters
            let url = '../api/get_allocation_history.php?';
            const params = [];
            // Use office filter if selected, otherwise use department filter
            const selectedFilter = officeFilter || departmentFilter;
            if (selectedFilter) params.push(`department_id=${selectedFilter}`);
            if (yearFilter) params.push(`year=${yearFilter}`);
            url += params.length > 0 ? params.join('&') : '';
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allAllocations = data.allocations;
                        displayAllocationHistory(data.allocations);
                    }
                })
                .catch(error => {
                    console.error('Error refreshing history:', error);
                });
        }
    }
    
    function filterHistoryByDepartment(filterType) {
        // Clear the other filter when one is selected
        const departmentFilter = document.getElementById('historyDepartmentFilter');
        const officeFilter = document.getElementById('historyOfficeFilter');
        
        if (departmentFilter && officeFilter) {
            if (filterType === 'department' && departmentFilter.value) {
                // Department was selected, clear office filter
                officeFilter.value = '';
            } else if (filterType === 'office' && officeFilter.value) {
                // Office was selected, clear department filter
                departmentFilter.value = '';
            }
        }
        
        showAllocationHistory();
    }
    
    function displayAllocationHistory(allocations) {
        const modal = document.getElementById('allocationHistoryModal');
        if (!modal) {
            // Modal should already be created by showAllocationHistory
            console.error('Modal not found');
            return;
        }
        
        const content = document.getElementById('allocationHistoryContent');
        if (!content) return;
        
        if (!allocations || allocations.length === 0) {
            content.innerHTML = `
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <p class="text-gray-600 font-semibold">No allocation history found</p>
                    <p class="text-gray-500 text-sm mt-2">Saved allocations will appear here</p>
                </div>
            `;
        } else {
            let html = '<div class="space-y-4">';
            allocations.forEach(allocation => {
                // Check if allocation was updated
                const isUpdated = allocation.updated_at && allocation.updated_at !== allocation.created_at;
                const displayDate = isUpdated ? allocation.updated_at : allocation.created_at;
                const date = new Date(displayDate);
                const formattedDate = date.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'long', 
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                // Status badge
                const status = isUpdated ? 'updated' : 'created';
                const statusText = isUpdated ? 'Updated' : 'Created';
                const statusColor = isUpdated 
                    ? 'text-blue-600 bg-blue-50 border border-blue-100' 
                    : 'text-green-600 bg-green-50 border border-green-100';
                
                html += `
                    <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex items-center justify-between mb-3">
                <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h3 class="text-lg font-bold text-gray-900">${allocation.department_name || 'Unknown Department'}</h3>
                                    <span class="text-xs font-semibold uppercase tracking-[0.1em] ${statusColor} px-2 py-0.5 rounded-full">
                                        ${statusText}
                                    </span>
                                </div>
                                <p class="text-sm text-gray-500">${isUpdated ? 'Updated' : 'Created'}: ${formattedDate}</p>
                                ${isUpdated ? `<p class="text-xs text-gray-400 mt-0.5">Created: ${new Date(allocation.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric', hour: '2-digit', minute: '2-digit' })}</p>` : ''}
                                <p class="text-xs text-gray-400 mt-1">Fiscal Year: ${allocation.fiscal_year}</p>
                </div>
                            <div class="flex items-center gap-2">
                                <button 
                                    onclick="viewAllocationBreakdown(${allocation.id})" 
                                    class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors font-semibold text-sm flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                                    View
                                </button>
                                <button 
                                    onclick="downloadAllocationPDF(${allocation.id})" 
                                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-semibold text-sm flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    PDF
                                </button>
                                <button 
                                    onclick="deleteAllocation(${allocation.id}, '${(allocation.department_name || 'Unknown Department').replace(/'/g, "\\'")}', this)" 
                                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-semibold text-sm flex items-center gap-2"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
            </div>
            </div>
                        <div class="grid ${allocation.fiduciary_type === 'Fiduciary' ? 'grid-cols-1' : 'grid-cols-3'} gap-4 text-sm">
                            ${allocation.fiduciary_type === 'Fiduciary' ? '' : `
                            <div>
                                <p class="text-gray-500">Total Tuition</p>
                                <p class="font-semibold text-gray-900">₱${parseFloat(allocation.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                            </div>
                            ${parseFloat(allocation.additional_amount || 0) > 0 ? `
                            <div>
                                <p class="text-gray-500">Additional Amount</p>
                                <p class="font-semibold text-amber-600">₱${parseFloat(allocation.additional_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                            </div>` : ''}
                            `}
                            <div>
                                <p class="text-gray-500">Overall Total</p>
                                <p class="font-semibold text-maroon text-lg">₱${parseFloat(allocation.overall_total || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            content.innerHTML = html;
        }
        
        document.getElementById('allocationHistoryModal').classList.remove('hidden');
    }
    
    function closeAllocationHistory() {
        const modal = document.getElementById('allocationHistoryModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    function deleteAllAllocations() {
        // Check if there are any allocations to delete
        if (!allAllocations || allAllocations.length === 0) {
            alert('No allocations to delete.');
            return;
        }
        
        const count = allAllocations.length;
        
        // Strong confirmation for delete all
        const confirmMessage = `⚠️ WARNING: You are about to delete ALL ${count} allocation(s)!\n\n` +
                              `This action CANNOT be undone.\n\n` +
                              `Are you absolutely sure you want to proceed?`;
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Double confirmation
        if (!confirm('This is your last chance to cancel. Click OK to permanently delete all allocations.')) {
            return;
        }
        
        const deleteAllBtn = document.getElementById('deleteAllBtn');
        const originalText = deleteAllBtn ? deleteAllBtn.innerHTML : '';
        
        // Show loading state
        if (deleteAllBtn) {
            deleteAllBtn.disabled = true;
            deleteAllBtn.innerHTML = '<svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...';
        }
        
        // Delete all allocations one by one
        let deletedCount = 0;
        let failedCount = 0;
        const deletePromises = allAllocations.map(allocation => {
            const formData = new FormData();
            formData.append('id', allocation.id);
            
            return fetch('../ajax/delete_allocation.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    deletedCount++;
                } else {
                    failedCount++;
                }
            })
            .catch(error => {
                console.error('Error deleting allocation:', error);
                failedCount++;
            });
        });
        
        // Wait for all deletions to complete
        Promise.all(deletePromises)
            .then(() => {
                if (deleteAllBtn) {
                    deleteAllBtn.disabled = false;
                    deleteAllBtn.innerHTML = originalText;
                }
                
                if (failedCount === 0) {
                    alert(`Successfully deleted ${deletedCount} allocation(s)!`);
                } else {
                    alert(`Deleted ${deletedCount} allocation(s), but ${failedCount} failed.`);
                }
                
                // Refresh the history
                showAllocationHistory();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error deleting allocations. Please try again.');
                if (deleteAllBtn) {
                    deleteAllBtn.disabled = false;
                    deleteAllBtn.innerHTML = originalText;
                }
            });
    }
    
    function deleteAllocation(allocationId, departmentName, buttonElement) {
        // Confirm deletion
        if (!confirm(`Are you sure you want to delete the allocation for "${departmentName}"?\n\nThis action cannot be undone.`)) {
            return;
        }
        
        // Store original button content
        const deleteBtn = buttonElement || document.querySelector(`button[onclick*="deleteAllocation(${allocationId}"]`);
        const originalText = deleteBtn ? deleteBtn.innerHTML : '';
        
        // Show loading state
        if (deleteBtn) {
            deleteBtn.disabled = true;
            deleteBtn.innerHTML = '<svg class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Deleting...';
        }
        
        // Create form data
        const formData = new FormData();
        formData.append('id', allocationId);
        
        // Send delete request
        fetch('../ajax/delete_allocation.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                alert('Allocation deleted successfully!');
                // Refresh the history
                showAllocationHistory();
            } else {
                // Show error message
                alert('Error deleting allocation: ' + (data.message || 'Unknown error'));
                // Restore button
                if (deleteBtn) {
                    deleteBtn.disabled = false;
                    deleteBtn.innerHTML = originalText;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting allocation. Please try again.');
            // Restore button
            if (deleteBtn) {
                deleteBtn.disabled = false;
                deleteBtn.innerHTML = originalText;
            }
        });
    }
    
    function viewAllocationBreakdown(allocationId) {
        fetch(`../api/get_allocation_breakdown_by_id.php?id=${allocationId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    displayBreakdownModal(data.data);
                } else {
                    alert('Error loading breakdown: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error loading breakdown:', error);
                alert('Error loading breakdown: ' + (error.message || 'Please check the console for details'));
            });
    }
    
    function displayBreakdownModal(allocationData) {
        const modal = document.getElementById('breakdownViewModal');
        if (!modal) {
            const modalHTML = `
                <div id="breakdownViewModal" class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                        <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6 flex justify-between items-center">
                            <h2 class="text-2xl font-bold">Budget Allocation Breakdown</h2>
                            <button onclick="closeBreakdownModal()" class="text-white hover:text-red-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
                        <div id="breakdownViewContent" class="flex-1 overflow-y-auto p-6">
                            <!-- Content will be inserted here -->
                    </div>
                    </div>
                </div>
            `;
            document.body.insertAdjacentHTML('beforeend', modalHTML);
        }
        
        const content = document.getElementById('breakdownViewContent');
        if (!content) return;
        
        // Determine if this is an office (fiduciary_type === 'Fiduciary')
        const isOffice = allocationData.fiduciary_type === 'Fiduciary';
        
        let html = `
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Allocation Details</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                    <div>
                        <p class="text-sm text-gray-600">${isOffice ? 'Office' : 'Department'}</p>
                        <p class="text-lg font-semibold">${allocationData.department_name || 'N/A'}</p>
                    </div>
        `;
        
        // Show department-specific fields only for departments
        if (!isOffice) {
            html += `
                    <div>
                        <p class="text-sm text-gray-600">Total Tuition Fee</p>
                        <p class="text-lg font-semibold">₱${parseFloat(allocationData.total_tuition_fee || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">50% Instructional</p>
                        <p class="text-lg font-semibold">₱${parseFloat(allocationData.instructional_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>
            `;
        } else {
            // For offices, show budget allocated instead
            let allocData;
            try {
                allocData = typeof allocationData.allocation_data === 'string' 
                    ? JSON.parse(allocationData.allocation_data) 
                    : (allocationData.allocation_data || {});
                if (!allocData || typeof allocData !== 'object') {
                    allocData = {};
                }
            } catch (e) {
                console.error('Error parsing allocation_data:', e);
                allocData = {};
            }
            const budgetAllocated = allocData?.budget_allocated || allocationData.budget_allocated || '₱0.00';
            html += `
                    <div>
                        <p class="text-sm text-gray-600">Budget Allocated</p>
                        <p class="text-lg font-semibold">${typeof budgetAllocated === 'string' ? budgetAllocated : '₱' + parseFloat(budgetAllocated || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>
            `;
        }
        
        html += `
                    ${(() => {
                        const additionalAmt = parseFloat(allocationData.additional_amount || 0);
                        const overallTotal = parseFloat(allocationData.overall_total || 0);
                        if (additionalAmt > 0) {
                            const totalBeforeAdditional = overallTotal - additionalAmt;
                            return `
                    <div>
                        <p class="text-sm text-gray-600">Total Amount</p>
                        <p class="text-lg font-semibold text-gray-700">₱${totalBeforeAdditional.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Additional Amount</p>
                        <p class="text-lg font-semibold text-amber-600">₱${additionalAmt.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        ${allocationData.additional_description ? `<p class="text-xs text-gray-500 mt-1">${allocationData.additional_description}</p>` : ''}
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Overall Total</p>
                        <p class="text-lg font-semibold text-maroon">₱${overallTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>`;
                        } else {
                            return `
                    <div>
                        <p class="text-sm text-gray-600">Overall Total</p>
                        <p class="text-lg font-semibold text-maroon">₱${overallTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Additional Amount</p>
                        <p class="text-lg font-semibold text-gray-400">₱0.00</p>
                    </div>`;
                        }
                    })()}
                    <div>
                        <p class="text-sm text-gray-600">Fiscal Year</p>
                        <p class="text-lg font-semibold">${allocationData.fiscal_year || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-600">Created</p>
                        <p class="text-lg font-semibold">${new Date(allocationData.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
                    </div>
                </div>
            </div>
        `;
        
        if (allocationData.allocation_data) {
            let allocData;
            try {
                allocData = typeof allocationData.allocation_data === 'string' 
                    ? JSON.parse(allocationData.allocation_data) 
                    : allocationData.allocation_data;
                // Ensure allocData is an object
                if (!allocData || typeof allocData !== 'object') {
                    allocData = {};
                }
            } catch (e) {
                console.error('Error parsing allocation_data:', e);
                allocData = {};
            }
            
            // Non-Fiduciary Fund (only for departments, not offices)
            if (!isOffice && allocData.non_fiduciary) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Non-Fiduciary Fund</h4>';
                html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Category</th><th class="border p-3 text-right font-bold">Percent</th><th class="border p-3 text-right font-bold">50%</th><th class="border p-3 text-right font-bold">Deductions</th><th class="border p-3 text-right font-bold">Budget Allocation</th></tr></thead><tbody>';
                
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
                        let deductionTotal = 0;
                        let deductionDetails = '';
                        deductions.forEach(ded => {
                            const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                            const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                            deductionTotal += amount;
                            deductionDetails += `<div class="text-xs py-1 border-b border-gray-100">${ded.amount || '₱0.00'} ${ded.remarks ? '(' + ded.remarks + ')' : ''}</div>`;
                        });
                        
                        html += `<tr><td class="border p-3 font-semibold">${name}</td>`;
                        html += `<td class="border p-3 text-right">${item.percent || '0%'}</td>`;
                        html += `<td class="border p-3 text-right">${item.instructional || '₱0.00'}</td>`;
                        html += `<td class="border p-3 text-right"><div>${deductionDetails || '<span class="text-gray-400">-</span>'}</div>${deductionTotal > 0 ? '<div class="text-xs font-semibold mt-1 pt-1 border-t">Total: ₱' + deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>' : ''}</td>`;
                        const budgetAllocStr = (item.budget_allocation && typeof item.budget_allocation === 'string') ? item.budget_allocation : (item.budget_allocation ? String(item.budget_allocation) : '0');
                        const budgetAlloc = parseFloat(budgetAllocStr.replace(/[₱,]/g, '') || 0);
                        html += `<td class="border p-3 text-right font-semibold ${budgetAlloc < 0 ? 'text-red-600' : ''}">${item.budget_allocation || '₱0.00'}</td></tr>`;
                    }
                }
                html += '</tbody></table></div></div>';
            }
            
            // Fiduciary Fund
            if (allocData.fiduciary) {
                html += '<div class="mb-6"><h4 class="text-md font-semibold text-gray-800 mb-3">Fiduciary Fund</h4>';
                
                if (isOffice) {
                    // Office format: Show allocated budget, deductions, and total budget
                    const fiduciary = allocData.fiduciary;
                    const deductions = fiduciary.deductions || [];
                    const totalBudget = fiduciary.total_budget || '₱0.00';
                    
                    // Get allocated budget from allocationData.budget_allocated (from database field)
                    const allocatedBudgetValue = allocationData.budget_allocated || 0;
                    const allocatedBudgetFormatted = typeof allocatedBudgetValue === 'string' 
                        ? allocatedBudgetValue 
                        : '₱' + parseFloat(allocatedBudgetValue || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    
                    // Calculate deduction total
                    let deductionTotal = 0;
                    let deductionDetails = '';
                    deductions.forEach(ded => {
                        const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                        const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                        deductionTotal += amount;
                        deductionDetails += `<div class="text-xs py-1 border-b border-gray-100">${ded.amount || '₱0.00'} ${ded.remarks ? '(' + ded.remarks + ')' : ''}</div>`;
                    });
                    
                    html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                    html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Description</th><th class="border p-3 text-right font-bold">Amount</th></tr></thead>';
                    html += '<tbody>';
                    html += `<tr><td class="border p-3 font-semibold">Allocated Budget</td><td class="border p-3 text-right font-semibold">${allocatedBudgetFormatted}</td></tr>`;
                    html += `<tr><td class="border p-3 font-semibold">Total Deductions</td><td class="border p-3 text-right">${deductionTotal > 0 ? '₱' + deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '₱0.00'}</td></tr>`;
                    html += `<tr class="bg-gray-50"><td class="border p-3 font-bold">Total Budget</td><td class="border p-3 text-right font-bold text-maroon">${totalBudget}</td></tr>`;
                    html += '</tbody></table></div>';
                    
                    // Show deductions breakdown if any
                    if (deductions.length > 0) {
                        html += '<div class="mt-4"><h5 class="text-sm font-semibold text-gray-700 mb-2">Deductions Breakdown:</h5>';
                        html += '<div class="bg-gray-50 rounded-lg p-3 border border-gray-200">';
                        html += deductionDetails || '<span class="text-gray-400">No deductions</span>';
                        html += '</div></div>';
                    }
                    html += '</div>';
                } else {
                    // Department format: Show rows with items
                    html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                    html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Item</th><th class="border p-3 text-right font-bold">50%</th><th class="border p-3 text-right font-bold">Deductions</th><th class="border p-3 text-right font-bold">Budget Allocation</th></tr></thead>';
                    html += '<tbody>';
                    
                    for (const [key, item] of Object.entries(allocData.fiduciary)) {
                        // Skip non-item keys like 'deductions' or 'total_budget' for offices
                        if (typeof item === 'object' && item !== null && (item.item_name || item.instructional)) {
                            const deductions = item.deductions || [];
                            let deductionTotal = 0;
                            let deductionDetails = '';
                            deductions.forEach(ded => {
                                const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                deductionTotal += amount;
                                deductionDetails += `<div class="text-xs py-1 border-b border-gray-100">${ded.amount || '₱0.00'} ${ded.remarks ? '(' + ded.remarks + ')' : ''}</div>`;
                            });
                            
                            html += `<tr><td class="border p-3 font-semibold">${item.item_name || 'Item ' + key}</td>`;
                            html += `<td class="border p-3 text-right">${item.instructional || '₱0.00'}</td>`;
                            html += `<td class="border p-3 text-right"><div>${deductionDetails || '<span class="text-gray-400">-</span>'}</div>${deductionTotal > 0 ? '<div class="text-xs font-semibold mt-1 pt-1 border-t">Total: ₱' + deductionTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) + '</div>' : ''}</td>`;
                            const budgetAllocStr = (item.budget_allocation && typeof item.budget_allocation === 'string') ? item.budget_allocation : (item.total_budget && typeof item.total_budget === 'string') ? item.total_budget : (item.budget_allocation ? String(item.budget_allocation) : (item.total_budget ? String(item.total_budget) : '0'));
                            const budgetAlloc = parseFloat(budgetAllocStr.replace(/[₱,]/g, '') || 0);
                            html += `<td class="border p-3 text-right font-semibold ${budgetAlloc < 0 ? 'text-red-600' : ''}">${item.budget_allocation || item.total_budget || '₱0.00'}</td></tr>`;
                        }
                    }
                    html += '</tbody></table></div></div>';
                }
                
                // Calculate and display Deduction Breakdown by Type
                const deductionBreakdown = {
                    'COS': 0,
                    'Honoraria Overload': 0,
                    'Part-time': 0,
                    'Water': 0,
                    'Electricity': 0,
                    'Security': 0
                };
                
                // Collect deductions from non-fiduciary categories (for departments only)
                if (!isOffice && allocData.non_fiduciary) {
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
                            deductions.forEach(ded => {
                                const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                const remarks = ded.remarks ? ded.remarks.trim() : '';
                                if (amount > 0 && remarks && deductionBreakdown.hasOwnProperty(remarks)) {
                                    deductionBreakdown[remarks] += amount;
                                }
                            });
                        }
                    }
                }
                
                // Collect deductions from fiduciary items
                if (allocData.fiduciary) {
                    if (isOffice) {
                        // For offices: get deductions from fiduciary object
                        const fiduciary = allocData.fiduciary;
                        const deductions = fiduciary.deductions || [];
                        deductions.forEach(ded => {
                            const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                            const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                            const remarks = ded.remarks ? ded.remarks.trim() : '';
                            if (amount > 0 && remarks && deductionBreakdown.hasOwnProperty(remarks)) {
                                deductionBreakdown[remarks] += amount;
                            }
                        });
                    } else {
                        // For departments: get deductions from all fiduciary items
                        for (const [key, item] of Object.entries(allocData.fiduciary)) {
                            if (typeof item === 'object' && item !== null && (item.item_name || item.instructional)) {
                                const deductions = item.deductions || [];
                                deductions.forEach(ded => {
                                    const amountStr = (ded.amount && typeof ded.amount === 'string') ? ded.amount : (ded.amount ? String(ded.amount) : '0');
                                    const amount = parseFloat(amountStr.replace(/[₱,]/g, '') || 0);
                                    const remarks = ded.remarks ? ded.remarks.trim() : '';
                                    if (amount > 0 && remarks && deductionBreakdown.hasOwnProperty(remarks)) {
                                        deductionBreakdown[remarks] += amount;
                                    }
                                });
                            }
                        }
                    }
                }
                
                // Display the deduction breakdown
                let grandTotalDeductions = 0;
                let hasAnyDeductions = false;
                
                const deductionTypes = [
                    { key: 'COS', label: 'COS' },
                    { key: 'Honoraria Overload', label: 'Overload' },
                    { key: 'Part-time', label: 'Part-time' },
                    { key: 'Water', label: 'Water' },
                    { key: 'Electricity', label: 'Electricity' },
                    { key: 'Security', label: 'Security' }
                ];
                
                deductionTypes.forEach(type => {
                    const amount = deductionBreakdown[type.key] || 0;
                    if (amount > 0) {
                        hasAnyDeductions = true;
                        grandTotalDeductions += amount;
                    }
                });
                
                if (hasAnyDeductions) {
                    html += '<div class="mt-6 pt-6 border-t-2 border-gray-300"><h4 class="text-md font-semibold text-gray-800 mb-3">Total Deduction Breakdown by Type</h4>';
                    html += '<div class="overflow-x-auto"><table class="w-full text-sm border-collapse border border-gray-300">';
                    html += '<thead><tr class="bg-gray-100"><th class="border p-3 text-left font-bold">Deduction Type</th><th class="border p-3 text-right font-bold">Total Amount</th></tr></thead>';
                    html += '<tbody>';
                    
                    deductionTypes.forEach(type => {
                        const amount = deductionBreakdown[type.key] || 0;
                        if (amount > 0) {
                            html += `<tr><td class="border p-3 font-semibold">${type.label}</td>`;
                            html += `<td class="border p-3 text-right">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td></tr>`;
                        }
                    });
                    
                    html += '</tbody>';
                    html += '<tfoot class="border-t-2 border-gray-400"><tr class="font-bold">';
                    html += '<td class="border p-3">Grand Total Deductions</td>';
                    html += `<td class="border p-3 text-right">₱${grandTotalDeductions.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>`;
                    html += '</tr></tfoot></table></div></div>';
                }
            }
        }
        
        content.innerHTML = html;
        document.getElementById('breakdownViewModal').classList.remove('hidden');
    }
    
    function closeBreakdownModal() {
        const modal = document.getElementById('breakdownViewModal');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
    
    function downloadAllocationPDF(allocationId) {
        // Get department name for filename
        fetch(`../api/get_allocation_breakdown_by_id.php?id=${allocationId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const deptName = data.data.department_name || 'Allocation';
                    const date = new Date(data.data.created_at);
                    const dateStr = date.toISOString().split('T')[0];
                    const filename = `Budget_Allocation_${deptName.replace(/\s+/g, '_')}_${dateStr}.pdf`;
                    
                    // Open in new window for printing/downloading
                    const printWindow = window.open(`../api/generate_allocation_pdf.php?id=${allocationId}`, '_blank');
                    
                    // After window loads, try to trigger download
                    if (printWindow) {
                        printWindow.onload = function() {
                            // The print dialog will appear, user can save as PDF
                            // Browser will handle the download with the filename
                        };
                    }
                } else {
                    // Fallback if we can't get department name
                    window.open(`../api/generate_allocation_pdf.php?id=${allocationId}`, '_blank');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                window.open(`../api/generate_allocation_pdf.php?id=${allocationId}`, '_blank');
            });
    }
    </script>
    
    <!-- Clear Data Confirmation Modal -->
    <div id="clearDataModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-red-600 to-red-700 text-white p-6 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                    <h3 class="text-xl font-bold">Clear All Entries?</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-6">This will reset all current entries to 0. You will need to re-enter all data.</p>
                <p class="text-gray-600 text-sm mb-6">Do you want to continue?</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeClearDataModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        Cancel
                    </button>
                    <button onclick="confirmClearData()" class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold">
                        Yes, Clear
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clear Database Modal -->
    <div id="clearDatabaseModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-red-600 to-red-700 text-white p-6 rounded-t-2xl">
                <div class="flex items-center gap-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    <h3 class="text-xl font-bold">Clear from Database?</h3>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">Entries cleared successfully!</p>
                <p class="text-gray-700 mb-6">Do you also want to clear the saved data from allocation_view?</p>
                <p class="text-red-600 text-sm font-semibold mb-6">⚠️ This will permanently delete all saved allocations for this department!</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeClearDatabaseModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        No, Keep Saved Data
                    </button>
                    <button onclick="confirmClearDatabase()" class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold">
                        Yes, Delete from Database
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Custom Deduction Modal -->
    <div id="customDeductionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 text-white p-6 rounded-t-2xl">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <h3 class="text-xl font-bold">Custom Deduction</h3>
                    </div>
                    <button onclick="closeCustomDeductionModal()" class="text-white hover:text-red-200 transition-colors p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-6">
                <p class="text-gray-700 mb-4">Enter a custom deduction name:</p>
                <input 
                    type="text" 
                    id="customDeductionInput" 
                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon transition-all text-gray-900 font-semibold"
                    placeholder="e.g., Maintenance, Supplies, etc."
                    maxlength="50"
                >
                <div class="flex gap-3 justify-end mt-6">
                    <button onclick="closeCustomDeductionModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        Cancel
                    </button>
                    <button onclick="confirmCustomDeduction()" class="px-6 py-2.5 bg-gradient-to-r from-maroon to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold">
                        Add Custom
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Set % for All Modal -->
    <div id="setPercentModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 animate-fade-in">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-6 py-4 rounded-t-2xl">
                <h3 class="text-xl font-bold flex items-center gap-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path>
                    </svg>
                    Set Percentage for All Categories
                </h3>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 mb-6">
                    Set the same percentage for all Non-Fiduciary Fund categories. This will apply to all departments and offices.
                </p>
                
                <div class="space-y-4">
                    <div>
                        <label for="facultyStaffPercent_modal" class="block text-sm font-semibold text-gray-700 mb-2">
                            Faculty and Staff Development (%)
                        </label>
                        <input 
                            type="number" 
                            id="facultyStaffPercent_modal" 
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="e.g., 10"
                            min="0"
                            max="100"
                            step="0.01"
                        >
                    </div>
                    
                    <div>
                        <label for="curriculumPercent_modal" class="block text-sm font-semibold text-gray-700 mb-2">
                            Curriculum Development (%)
                        </label>
                        <input 
                            type="number" 
                            id="curriculumPercent_modal" 
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="e.g., 10"
                            min="0"
                            max="100"
                            step="0.01"
                        >
                    </div>
                    
                    <div>
                        <label for="studentPercent_modal" class="block text-sm font-semibold text-gray-700 mb-2">
                            Student Development (%)
                        </label>
                        <input 
                            type="number" 
                            id="studentPercent_modal" 
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="e.g., 10"
                            min="0"
                            max="100"
                            step="0.01"
                        >
                    </div>
                    
                    <div>
                        <label for="facilitiesPercent_modal" class="block text-sm font-semibold text-gray-700 mb-2">
                            Facilities Development (%)
                        </label>
                        <input 
                            type="number" 
                            id="facilitiesPercent_modal" 
                            class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition-all"
                            placeholder="e.g., 10"
                            min="0"
                            max="100"
                            step="0.01"
                        >
                    </div>
                </div>
                
                <div class="flex gap-3 justify-end mt-6">
                    <button onclick="closeSetPercentModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        Cancel
                    </button>
                    <button onclick="applyPercentToAll()" class="px-6 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all font-semibold">
                        Apply to All
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" onload="console.log('jsPDF loaded:', typeof window.jspdf)"></script>

</body>
</html>
