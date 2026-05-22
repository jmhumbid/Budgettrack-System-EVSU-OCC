<?php
session_start();

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';

$notification = new Notification();

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$activeSidebar = 'cabac';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - CABAC</title>
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
        /* Force select dropdowns to open downward with scrolling */
        select.particulars-select,
        select.programs-select,
        select.honoraria-select {
            appearance: none;
            -webkit-appearance: none;
            -moz-appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%23374151'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 1.5em 1.5em;
            padding-right: 2.5rem;
            cursor: pointer;
            position: relative;
        }
        
        select.particulars-select:focus,
        select.programs-select:focus,
        select.honoraria-select:focus,
        select.honoraria-select:focus {
            outline: none;
            border-color: #800000;
            box-shadow: 0 0 0 2px rgba(128, 0, 0, 0.2);
            z-index: 10;
        }
        
        /* Style for optgroups */
        select.particulars-select optgroup,
        select.programs-select optgroup,
        select.honoraria-select optgroup {
            font-weight: 600;
            color: #374151;
            padding: 0.25rem 0;
        }
        
        /* Limit select dropdown height and make scrollable */
        select.particulars-select option,
        select.programs-select option,
        select.honoraria-select option {
            padding: 0.5rem 0.75rem;
            cursor: pointer;
        }
        
        select.particulars-select option:hover,
        select.programs-select option:hover,
        select.honoraria-select option:hover {
            background-color: #800000;
            color: white;
        }
        
        /* Ensure selects have proper spacing in table cells */
        td select.particulars-select,
        td select.programs-select,
        td select.honoraria-select {
            min-height: 2.75rem;
        }
        
        /* Ensure table cells align properly */
        table {
            border-collapse: collapse;
        }
        
        tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        tbody td {
            vertical-align: middle;
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold mb-1">Comparative Approve Budget and Actual Collection (CABAC)</h1>
                                    <p class="text-red-100 text-sm">Budget allocation and tracking system</p>
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
                            <button onclick="toggleProfileDropdown(event)" class="flex items-center space-x-3 bg-white bg-opacity-20 backdrop-blur-sm px-4 py-2 rounded-xl hover:bg-opacity-30 transition-colors border border-white border-opacity-30">
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
            <div class="flex-1 p-6 bg-gray-50">
                <div class="max-w-full mx-auto">
                    <!-- Search Bars and Controls -->
                    <div class="bg-white rounded-xl shadow-sm p-5 mb-5 border border-gray-100">
                        <div class="flex items-center justify-between gap-6 mb-4">
                            <div class="flex-1 relative">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Non-Fiduciary Programs</label>
                                <div class="relative" id="nonFiduciaryDropdownWrapper">
                                    <div class="flex items-stretch">
                                        <button 
                                            type="button"
                                            id="nonFiduciaryDropdownBtn"
                                            onclick="toggleCustomDropdown('nonFiduciary')"
                                            class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50 hover:bg-white text-left flex items-center justify-between transition-all duration-200"
                                        >
                                            <span id="nonFiduciarySelectedText" class="text-gray-400 text-sm">Select program...</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            type="button"
                                            id="clearNonFiduciaryBtn"
                                            onclick="clearProgramSelection('nonFiduciary')"
                                            class="hidden ml-2 px-3 border border-gray-200 rounded-lg bg-gray-50 hover:bg-red-50 hover:border-red-300 hover:text-red-500 text-gray-400 transition-all duration-200 flex items-center justify-center"
                                            title="Clear selection"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="nonFiduciaryDropdownList" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg">
                                        <div class="p-2 border-b border-gray-200 sticky top-0 bg-white">
                                            <input 
                                                type="text" 
                                                id="nonFiduciarySearchInput"
                                                placeholder="Search programs..."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent text-sm"
                                                onclick="event.stopPropagation()"
                                                oninput="filterDropdownItems('nonFiduciary', this.value)"
                                            >
                                        </div>
                                        <div class="max-h-48 overflow-y-auto py-1" id="nonFiduciaryOptions">
                                            <!-- Options loaded from database -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-1 relative">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Fiduciary Programs</label>
                                <div class="relative" id="fiduciaryDropdownWrapper">
                                    <div class="flex items-stretch">
                                        <button 
                                            type="button"
                                            id="fiduciaryDropdownBtn"
                                            onclick="toggleCustomDropdown('fiduciary')"
                                            class="w-full px-4 py-2.5 pr-10 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 bg-gray-50 hover:bg-white text-left flex items-center justify-between transition-all duration-200"
                                        >
                                            <span id="fiduciarySelectedText" class="text-gray-400 text-sm">Select program...</span>
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            type="button"
                                            id="clearFiduciaryBtn"
                                            onclick="clearProgramSelection('fiduciary')"
                                            class="hidden ml-2 px-3 border border-gray-200 rounded-lg bg-gray-50 hover:bg-red-50 hover:border-red-300 hover:text-red-500 text-gray-400 transition-all duration-200 flex items-center justify-center"
                                            title="Clear selection"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <div id="fiduciaryDropdownList" class="hidden absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-lg shadow-lg">
                                        <div class="p-2 border-b border-gray-200 sticky top-0 bg-white">
                                            <input 
                                                type="text" 
                                                id="fiduciarySearchInput"
                                                placeholder="Search programs..."
                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent text-sm"
                                                onclick="event.stopPropagation()"
                                                oninput="filterDropdownItems('fiduciary', this.value)"
                                            >
                                        </div>
                                        <div class="max-h-48 overflow-y-auto py-1" id="fiduciaryOptions">
                                            <!-- Options loaded from database -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0 relative">
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2 invisible">Settings</label>
                                <button 
                                    id="settingsBtn"
                                    onclick="toggleSettingsDropdown()"
                                    class="p-2.5 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg transition-all duration-200"
                                    title="Settings"
                                >
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg>
                                </button>
                                <!-- Settings Dropdown -->
                                <div id="settingsDropdown" class="hidden absolute right-0 top-full mt-2 w-56 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden">
                                    <div class="py-1">
                                        <button 
                                            onclick="selectEntryType('fiduciary')"
                                            class="w-full px-4 py-3 text-left text-gray-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 flex items-center gap-3 text-sm"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Add Fiduciary Entry
                                        </button>
                                        <button 
                                            onclick="selectEntryType('non-fiduciary')"
                                            class="w-full px-4 py-3 text-left text-gray-600 hover:bg-red-50 hover:text-red-700 transition-all duration-200 flex items-center gap-3 text-sm"
                                        >
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                            </svg>
                                            Add Non-Fiduciary Entry
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 pt-3 border-t border-gray-100">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-600 uppercase tracking-wider">Selected:</span>
                                <span id="selectedType" class="text-base font-bold text-red-600 bg-red-50 px-3 py-1.5 rounded-lg"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Program Table -->
                    <div class="bg-white rounded-xl shadow-sm overflow-hidden border border-gray-100">
                        <!-- Table -->
                        <table class="w-full">
                            <thead>
                                <tr class="bg-gradient-to-r from-red-800 to-red-700 text-white text-base">
                                    <th class="py-4 px-4 text-left font-bold tracking-wide uppercase" style="width: 20%;">Program</th>
                                    <th class="py-4 px-3 text-center font-bold tracking-wide uppercase" style="width: 20%;">Approved Budget</th>
                                    <th class="py-4 px-3 text-center font-bold tracking-wide uppercase" style="width: 25%;">Available Allotment</th>
                                    <th class="py-4 px-3 text-center font-bold tracking-wide uppercase" style="width: 25%;">Balance</th>
                                    <th class="py-4 px-3 text-center font-bold tracking-wide uppercase" style="width: 10%;">Action</th>
                                </tr>
                            </thead>
                            <tbody id="budgetTableBody" class="bg-white">
                                <!-- Programs will be dynamically added here -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-between items-center mt-6">
                        <button 
                            id="addProgramBtn"
                            onclick="addSimpleProgramRow()"
                            class="bg-gradient-to-r from-red-700 to-red-600 hover:from-red-800 hover:to-red-700 text-white font-semibold px-6 py-3 rounded-xl flex items-center gap-2 transition-all duration-200 shadow-md hover:shadow-lg active:scale-[0.98]"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                            </svg>
                            ADD PROGRAM
                        </button>
                        <div class="flex items-center gap-3">
                            <button 
                                id="saveBtn"
                                onclick="saveBudgetEntries()"
                                class="bg-gradient-to-r from-emerald-600 to-emerald-500 hover:from-emerald-700 hover:to-emerald-600 text-white font-semibold px-8 py-3 rounded-xl transition-all duration-200 shadow-md hover:shadow-lg active:scale-[0.98]"
                            >
                                SAVE
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Entry Modal (for Fiduciary/Non-Fiduciary) -->
    <div id="addEntryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md shadow-xl">
            <h3 id="addEntryModalTitle" class="text-xl font-bold mb-4 text-gray-800">Add New Entry</h3>
            <p id="addEntryModalDesc" class="text-gray-600 mb-4">Enter the name of the new entry to add to the selection list.</p>
            <input 
                type="text" 
                id="newEntryInput" 
                placeholder="Enter entry name..." 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600 mb-4"
            >
            <input type="hidden" id="entryTypeHidden" value="">
            <div class="flex justify-end gap-3">
                <button 
                    onclick="closeAddEntryModal()"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    onclick="saveNewEntry()"
                    class="px-4 py-2 bg-red-700 text-white rounded-lg hover:bg-red-800 transition-colors"
                >
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Input Amount Modal -->
    <div id="inputAmountModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Input Amount</h3>
            <input 
                type="number" 
                id="amountInput" 
                step="0.01" 
                min="0"
                placeholder="Enter amount..." 
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon mb-4"
            >
            <div class="flex justify-end gap-3">
                <button 
                    id="cancelAmountBtn"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    id="confirmAmountBtn"
                    class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors"
                >
                    Confirm
                </button>
            </div>
        </div>
    </div>

    <!-- Settings Modal for Adding New Items -->
    <div id="settingsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 w-full max-w-md">
            <h3 class="text-xl font-bold mb-4 text-gray-800">Add New Entry</h3>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Type</label>
                <select id="newEntryType" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon mb-4">
                    <option value="non-fiduciary">Non-Fiduciary</option>
                    <option value="fiduciary">Fiduciary</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Program Name</label>
                <input 
                    type="text" 
                    id="newEntryName" 
                    placeholder="Enter program name..." 
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon"
                >
            </div>
            <div class="flex justify-end gap-3">
                <button 
                    id="cancelSettingsBtn"
                    class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors"
                >
                    Cancel
                </button>
                <button 
                    id="confirmSettingsBtn"
                    class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors"
                >
                    Add
                </button>
            </div>
        </div>
    </div>

    <script>
        // Budget entry data storage
        let budgetRows = [];
        let currentRow = null;
        let currentAllotmentInput = null;
        let currentAvailableAllotmentInput = null;
        let currentOperationType = null; // 'add' or 'deduct'
        
        // Store template select HTML for cloning
        let templateParticularsHTML = '';
        let templateProgramsHTML = '';
        let templateHonorariaHTML = '';

        // Initialize template HTML (create from hardcoded options)
        function initializeTemplateHTML() {
            // Create particulars select template
            const particularsTemplate = document.createElement('select');
            particularsTemplate.className = 'particulars-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            particularsTemplate.innerHTML = `
                <option value="">SELECT</option>
                <option value="ps">Personal Server (PS)</option>
                <option value="co">Capital Outlay (CO)</option>
                <option value="mooe">MOOE</option>
            `;
            
            // Store old template for reference but use simplified version
            const oldTemplate = `
                <optgroup label="MOOE - Capital Assets">
                    <option value="mooe-power">Power Supply</option>
                    <option value="mooe-building">Building</option>
                    <option value="mooe-school-building">School Building</option>
                    <option value="mooe-office-equip">Office Equipment</option>
                    <option value="mooe-ict-equip">ICT Equipment</option>
                    <option value="mooe-machinery">Other Machinery & Equipment</option>
                    <option value="mooe-ict-software">ICT Software</option>
                    <option value="mooe-vehicle">Motor Vehicle</option>
                    <option value="mooe-furniture">Furniture & Fixture</option>
                    <option value="mooe-disaster">Disaster Response & Rescue Equipment</option>
                </optgroup>
                <optgroup label="MOOE - Honoraria">
                    <option value="mooe-honoraria">Honoraria</option>
                    <option value="mooe-honoraria-pt">Honoraria - Part Time</option>
                    <option value="mooe-honoraria-overload">Honoraria - Overload</option>
                </optgroup>
                <optgroup label="MOOE - Operating Expenses">
                    <option value="mooe-travel-local">Travel Expenses - Local</option>
                    <option value="mooe-travel-foreign">Travel Expenses - Foreign</option>
                    <option value="mooe-training">Training Expenses</option>
                    <option value="mooe-scholarship">Scholarship Expenses</option>
                    <option value="mooe-office-supplies">Office Supplies Expenses</option>
                    <option value="mooe-water">Water Expenses</option>
                    <option value="mooe-electricity">Electricity Expenses</option>
                    <option value="mooe-insurance">Insurance Expenses</option>
                    <option value="mooe-subscription">Subscription Expenses</option>
                    <option value="mooe-labor">Labor and Wages</option>
                    <option value="mooe-fuel">Fuel, Oil and Lubricants Expenses</option>
                    <option value="mooe-printing">Printing and Publication Expenses</option>
                    <option value="mooe-rewards">Rewards and Incentives</option>
                    <option value="mooe-textbooks">Textbooks & Instructional Materials</option>
                    <option value="mooe-forms">Accountable Forms</option>
                    <option value="mooe-bond">Facility Bond Prem.</option>
                    <option value="mooe-membership">Membership Dues</option>
                    <option value="mooe-taxes">Taxes, Duties and Licenses</option>
                    <option value="mooe-supplies">Other Supplies and Materials</option>
                    <option value="mooe-professional">Other Professional Services</option>
                    <option value="mooe-consultancy">Consultancy Services</option>
                    <option value="mooe-janitor">Janitor Services</option>
                    <option value="mooe-security">Security Services</option>
                    <option value="mooe-repair-printing">Repairs and Maintenance - Printing Equipment</option>
                    <option value="mooe-repair-office">Repairs and Maintenance - Office Equipment</option>
                    <option value="mooe-repair-structures">Repairs and Maintenance - Other Structures</option>
                    <option value="mooe-repair-machinery">Repairs and Maintenance - Other Machinery</option>
                    <option value="mooe-repair-ict">Repairs and Maintenance - ICT</option>
                    <option value="mooe-repair-mv">Repairs and Maintenance - MV</option>
                    <option value="mooe-other">Other MOOE</option>
                </optgroup>
                <option value="__add_new__">+ Add New Particular</option>
            `;
            templateParticularsHTML = particularsTemplate.outerHTML;
            
            // Create programs select template
            const programsTemplate = document.createElement('select');
            programsTemplate.className = 'programs-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            programsTemplate.innerHTML = `
                <option value="">SELECT</option>
                <option value="__add_new__">+ Add New</option>
                <option value="faculty-staff">Faculty & Staff Development</option>
                <option value="curriculum">Curriculum Development</option>
                <option value="student">Student Development</option>
                <option value="facilities">Facilities Development</option>
                <option value="research">Research</option>
                <option value="extension">Extension</option>
                <option value="production">Production</option>
                <option value="admin">Admin</option>
                <option value="petition">Petition</option>
            `;
            templateProgramsHTML = programsTemplate.outerHTML;
            
            // Create subparticulars select template
            const subparticularsTemplate = document.createElement('select');
            subparticularsTemplate.className = 'subparticulars-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            subparticularsTemplate.innerHTML = `
                <option value="">SELECT</option>
                <option value="__add_new__">+ Add New Subparticular</option>
            `;
            templateSubparticularsHTML = subparticularsTemplate.outerHTML;
            
            // Create honoraria select template
            const honorariaTemplate = document.createElement('select');
            honorariaTemplate.className = 'honoraria-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            honorariaTemplate.innerHTML = `
                <option value="">SELECT</option>
                <option value="honoraria">Honoraria</option>
                <option value="honoraria-part-time">Honoraria - Part Time</option>
                <option value="honoraria-overload">Honoraria- Overload</option>
                <option value="__add_new__">+ Add New</option>
            `;
            templateHonorariaHTML = honorariaTemplate.outerHTML;
        }

        // Handle "Add New" option in selects
        function handleAddNewSelect(select, type) {
            if (select.value === '__add_new__') {
                openAddNewModal(type, select);
                select.value = ''; // Reset selection
            }
        }

        // Open modal for adding new item
        function openAddNewModal(type, selectElement) {
            let modalId;
            if (type === 'particular') {
                modalId = 'addNewParticularModal';
            } else if (type === 'program') {
                modalId = 'addNewProgramModal';
            } else if (type === 'honoraria') {
                modalId = 'addNewHonorariaModal';
            } else if (type === 'subparticular') {
                modalId = 'addNewSubparticularModal';
            }
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.remove('hidden');
                // Store reference to select element
                if (selectElement) {
                    modal.dataset.targetSelectId = selectElement.id || '';
                    modal.dataset.targetSelectClass = selectElement.className;
                }
            }
        }

        // Close add new modal
        function closeAddNewModal(type) {
            let modalId, inputId;
            if (type === 'particular') {
                modalId = 'addNewParticularModal';
                inputId = 'newParticularInput';
            } else if (type === 'program') {
                modalId = 'addNewProgramModal';
                inputId = 'newProgramInput';
            } else if (type === 'honoraria') {
                modalId = 'addNewHonorariaModal';
                inputId = 'newHonorariaInput';
            } else if (type === 'subparticular') {
                modalId = 'addNewSubparticularModal';
                inputId = 'newSubparticularInput';
            }
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.classList.add('hidden');
                // Clear input
                const input = document.getElementById(inputId);
                if (input) input.value = '';
            }
        }

        // Save new item
        function saveNewItem(type) {
            let inputId, selectClass;
            if (type === 'particular') {
                inputId = 'newParticularInput';
                selectClass = '.particulars-select';
            } else if (type === 'program') {
                inputId = 'newProgramInput';
                selectClass = '.programs-select';
            } else if (type === 'honoraria') {
                inputId = 'newHonorariaInput';
                selectClass = '.honoraria-select';
            } else if (type === 'subparticular') {
                inputId = 'newSubparticularInput';
                selectClass = '.subparticular-select, .mooe-subparticular-select';
            }
            
            const input = document.getElementById(inputId);
            const newValue = input.value.trim();
            
            if (!newValue) {
                alert('Please enter a value');
                return;
            }
            
            // Create a safe value (lowercase, replace spaces with hyphens)
            const safeValue = newValue.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
            
            // Handle selects
            const allSelects = document.querySelectorAll(selectClass.split(',')[0].trim());
            allSelects.forEach(select => {
                if (select.tagName === 'SELECT') {
                    // Check if option already exists
                    const existingOption = Array.from(select.options).find(opt => opt.value === safeValue || opt.textContent === newValue);
                    if (!existingOption) {
                        // Find the "Add New" option
                        const addNewOption = Array.from(select.options).find(opt => opt.value === '__add_new__');
                        if (addNewOption) {
                            // Insert before "Add New" option
                            const newOption = document.createElement('option');
                            newOption.value = safeValue;
                            newOption.textContent = newValue;
                            select.insertBefore(newOption, addNewOption);
                        } else {
                            // Just append if no "Add New" option
                            const newOption = document.createElement('option');
                            newOption.value = safeValue;
                            newOption.textContent = newValue;
                            select.appendChild(newOption);
                        }
                    }
                }
            });
            
            // Handle MOOE selects (if subparticular type)
            if (type === 'subparticular') {
                const mooeWrappers = document.querySelectorAll('.mooe-select-wrapper');
                mooeWrappers.forEach(wrapper => {
                    // Add to the options list in the wrapper if it exists
                    if (wrapper.mooeOptions) {
                        // Check if option already exists
                        const exists = wrapper.mooeOptions.some(opt => opt.value === safeValue || opt.text === newValue);
                        if (!exists) {
                            wrapper.mooeOptions.push({ value: safeValue, text: newValue });
                            // Add to select element if it exists
                            const selectElement = wrapper.querySelector('.mooe-subparticular-select');
                            if (selectElement) {
                                const newOption = document.createElement('option');
                                newOption.value = safeValue;
                                newOption.textContent = newValue;
                                selectElement.appendChild(newOption);
                            }
                            // Repopulate dropdown if in search mode
                            if (wrapper.populateDropdown) {
                                wrapper.populateDropdown(wrapper.searchInput ? wrapper.searchInput.value : '');
                            }
                        }
                    }
                });
            }
            
            // Set value in the element that triggered this
            let modalId;
            if (type === 'particular') {
                modalId = 'addNewParticularModal';
            } else if (type === 'program') {
                modalId = 'addNewProgramModal';
            } else if (type === 'honoraria') {
                modalId = 'addNewHonorariaModal';
            } else if (type === 'subparticular') {
                modalId = 'addNewSubparticularModal';
            }
            const modal = document.getElementById(modalId);
            if (modal && modal.dataset.targetSelectClass) {
                const targetSelectClass = modal.dataset.targetSelectClass.split(' ')[0];
                // Find the element that opened the modal
                const targetElements = document.querySelectorAll('.' + targetSelectClass);
                if (targetElements.length > 0) {
                    const targetElement = targetElements[targetElements.length - 1];
                    // Set value - handle select, input, and custom dropdown button
                    if (targetElement.tagName === 'SELECT') {
                        targetElement.value = safeValue;
                    } else if (targetElement.tagName === 'INPUT') {
                        targetElement.value = newValue;
                        targetElement.setAttribute('data-value', safeValue);
                        // If it's a MOOE search input, update the dropdown
                        if (targetElement.classList.contains('mooe-search-input')) {
                            const wrapper = targetElement.closest('.mooe-select-wrapper');
                            if (wrapper && wrapper.populateDropdown) {
                                wrapper.populateDropdown('');
                            }
                        }
                    }
                }
            }
            
            closeAddNewModal(type);
        }

        // Clear program selection and reset table
        function clearProgramSelection(type) {
            // Reset dropdown text
            if (type === 'fiduciary') {
                const fiduciaryText = document.getElementById('fiduciarySelectedText');
                if (fiduciaryText) {
                    fiduciaryText.textContent = 'Select program...';
                    fiduciaryText.classList.remove('text-gray-900', 'font-bold');
                    fiduciaryText.classList.add('text-gray-400');
                }
                // Hide clear button
                const clearBtn = document.getElementById('clearFiduciaryBtn');
                if (clearBtn) clearBtn.classList.add('hidden');
            } else {
                const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
                if (nonFiduciaryText) {
                    nonFiduciaryText.textContent = 'Select program...';
                    nonFiduciaryText.classList.remove('text-gray-900', 'font-bold');
                    nonFiduciaryText.classList.add('text-gray-400');
                }
                // Hide clear button
                const clearBtn = document.getElementById('clearNonFiduciaryBtn');
                if (clearBtn) clearBtn.classList.add('hidden');
            }
            
            // Clear selected type display
            const selectedTypeSpan = document.getElementById('selectedType');
            if (selectedTypeSpan) {
                selectedTypeSpan.textContent = '';
            }
            
            // Clear localStorage
            localStorage.removeItem('cabac_selected_program');
            localStorage.removeItem('cabac_selected_type');
            
            // Reset global variables
            selectedProgramName = '';
            currentFiduciaryType = '';
            
            // Clear the table
            const tbody = document.getElementById('budgetTableBody');
            if (tbody) {
                tbody.innerHTML = '';
            }
        }
        
        // Restore selected program from localStorage on page load
        function restoreSelectedProgram() {
            const savedProgram = localStorage.getItem('cabac_selected_program');
            const savedType = localStorage.getItem('cabac_selected_type');
            
            if (savedProgram && savedType) {
                // Update the dropdown display
                if (savedType === 'fiduciary') {
                    const fiduciaryText = document.getElementById('fiduciarySelectedText');
                    if (fiduciaryText) {
                        fiduciaryText.textContent = savedProgram;
                        fiduciaryText.classList.remove('text-gray-400');
                        fiduciaryText.classList.add('text-gray-900', 'font-bold');
                    }
                    // Show clear button
                    const clearBtn = document.getElementById('clearFiduciaryBtn');
                    if (clearBtn) clearBtn.classList.remove('hidden');
                    
                    // Clear non-fiduciary
                    const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
                    if (nonFiduciaryText) {
                        nonFiduciaryText.textContent = 'Select program...';
                        nonFiduciaryText.classList.remove('text-gray-900', 'font-bold');
                        nonFiduciaryText.classList.add('text-gray-400');
                    }
                } else {
                    const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
                    if (nonFiduciaryText) {
                        nonFiduciaryText.textContent = savedProgram;
                        nonFiduciaryText.classList.remove('text-gray-400');
                        nonFiduciaryText.classList.add('text-gray-900', 'font-bold');
                    }
                    // Show clear button
                    const clearBtn = document.getElementById('clearNonFiduciaryBtn');
                    if (clearBtn) clearBtn.classList.remove('hidden');
                    
                    // Clear fiduciary
                    const fiduciaryText = document.getElementById('fiduciarySelectedText');
                    if (fiduciaryText) {
                        fiduciaryText.textContent = 'Select program...';
                        fiduciaryText.classList.remove('text-gray-900', 'font-bold');
                        fiduciaryText.classList.add('text-gray-400');
                    }
                }
                
                // Update selected type display
                const selectedTypeSpan = document.getElementById('selectedType');
                if (selectedTypeSpan) {
                    selectedTypeSpan.textContent = savedProgram;
                }
                
                // Set global variables
                selectedProgramName = savedProgram;
                currentFiduciaryType = savedType;
                
                // Load budget entries for this program
                loadBudgetEntries(savedProgram, savedType);
            }
        }
        
        // Load programs from database
        async function loadProgramsFromDB() {
            try {
                const response = await fetch('../api/cabac_programs.php?action=get_programs');
                const data = await response.json();
                
                if (data.success && data.programs) {
                    // Clear existing options
                    const fiduciaryOptions = document.getElementById('fiduciaryOptions');
                    const nonFiduciaryOptions = document.getElementById('nonFiduciaryOptions');
                    
                    if (fiduciaryOptions) fiduciaryOptions.innerHTML = '';
                    if (nonFiduciaryOptions) nonFiduciaryOptions.innerHTML = '';
                    
                    // Populate dropdowns
                    data.programs.forEach(program => {
                        const type = program.type === 'fiduciary' ? 'fiduciary' : 'nonFiduciary';
                        renderDropdownItem(type, program.program_name, program.id);
                    });
                }
            } catch (error) {
                console.error('Error loading programs:', error);
            }
        }
        
        // Render a dropdown item
        function renderDropdownItem(type, value, id) {
            const optionsContainer = document.getElementById(type + 'Options');
            if (optionsContainer) {
                const newItem = document.createElement('div');
                newItem.className = 'dropdown-item flex items-center justify-between px-4 py-2 hover:bg-gray-100 cursor-pointer';
                newItem.setAttribute('data-value', value);
                newItem.setAttribute('data-id', id);
                newItem.innerHTML = `
                    <span class="flex-1 cursor-pointer" onclick="selectCustomDropdownItem('${type}', '${value}')">${value}</span>
                    <button type="button" onclick="deleteDropdownItem(event, '${type}', '${value}')" class="text-red-500 hover:text-red-700 p-1 ml-2"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button>
                `;
                optionsContainer.appendChild(newItem);
            }
        }
        
        // Filter dropdown items based on search
        function filterDropdownItems(type, searchValue) {
            const optionsContainer = document.getElementById(type + 'Options');
            if (!optionsContainer) return;
            
            const items = optionsContainer.querySelectorAll('.dropdown-item');
            const search = searchValue.toLowerCase();
            
            items.forEach(item => {
                const value = item.getAttribute('data-value').toLowerCase();
                if (value.includes(search)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        // Toggle custom dropdown (fiduciary/nonFiduciary)
        function toggleCustomDropdown(type) {
            const dropdownList = document.getElementById(type + 'DropdownList');
            const otherType = type === 'fiduciary' ? 'nonFiduciary' : 'fiduciary';
            const otherDropdownList = document.getElementById(otherType + 'DropdownList');
            const searchInput = document.getElementById(type + 'SearchInput');
            
            // Close the other dropdown
            if (otherDropdownList) {
                otherDropdownList.classList.add('hidden');
            }
            
            // Toggle current dropdown
            if (dropdownList) {
                const isHidden = dropdownList.classList.contains('hidden');
                dropdownList.classList.toggle('hidden');
                
                // Focus search input when opening
                if (isHidden && searchInput) {
                    setTimeout(() => searchInput.focus(), 100);
                }
            }
        }
        
        // Select item from custom dropdown
        function selectCustomDropdownItem(type, value) {
            const selectedText = document.getElementById(type + 'SelectedText');
            const dropdownList = document.getElementById(type + 'DropdownList');
            const searchInput = document.getElementById(type + 'SearchInput');
            
            if (selectedText) {
                selectedText.textContent = value;
                selectedText.classList.remove('text-gray-500');
                selectedText.classList.add('text-gray-900');
            }
            
            if (dropdownList) {
                dropdownList.classList.add('hidden');
            }
            
            // Clear search input
            if (searchInput) {
                searchInput.value = '';
                filterDropdownItems(type, '');
            }
            
            // Update selected type display
            const selectedTypeSpan = document.getElementById('selectedType');
            if (selectedTypeSpan) {
                selectedTypeSpan.textContent = value;
            }
            
            // Call the appropriate handler
            if (type === 'fiduciary') {
                handleFiduciarySelect(value);
            } else {
                handleNonFiduciarySelect(value);
            }
        }
        
        // Delete item from dropdown (with database)
        async function deleteDropdownItem(event, type, value) {
            event.stopPropagation();
            
            if (!confirm(`Are you sure you want to delete "${value}" from the list?`)) {
                return;
            }
            
            try {
                const dbType = type === 'fiduciary' ? 'fiduciary' : 'non-fiduciary';
                const response = await fetch('../api/cabac_programs.php?action=delete_program', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ program_name: value, type: dbType })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Remove from DOM
                    const optionsContainer = document.getElementById(type + 'Options');
                    if (optionsContainer) {
                        const items = optionsContainer.querySelectorAll('.dropdown-item');
                        items.forEach(item => {
                            if (item.getAttribute('data-value') === value) {
                                item.remove();
                            }
                        });
                    }
                    
                    // Reset selection if the deleted item was selected
                    const selectedText = document.getElementById(type + 'SelectedText');
                    if (selectedText && selectedText.textContent === value) {
                        selectedText.textContent = type === 'fiduciary' ? 'Select Fiduciary Program...' : 'Select Non-Fiduciary Program...';
                        selectedText.classList.add('text-gray-500');
                        selectedText.classList.remove('text-gray-900');
                    }
                } else {
                    alert('Error deleting program: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error deleting program');
            }
        }
        
        // Add new item to dropdown (with database)
        async function addDropdownItem(type, value) {
            try {
                const dbType = type === 'fiduciary' ? 'fiduciary' : 'non-fiduciary';
                const response = await fetch('../api/cabac_programs.php?action=add_program', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ program_name: value, type: dbType })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    renderDropdownItem(type, value, data.id);
                    return true;
                } else {
                    alert('Error adding program: ' + data.message);
                    return false;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error adding program');
                return false;
            }
        }
        
        // Save all budget entries to database
        async function saveBudgetEntries() {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) {
                alert('No budget table found');
                return;
            }
            
            // Get selected program from dropdown
            const fiduciaryText = document.getElementById('fiduciarySelectedText');
            const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
            
            let selectedProgram = null;
            let programType = null;
            
            
            // Check which dropdown has a selected value (not placeholder text)
            if (nonFiduciaryText && nonFiduciaryText.textContent.trim() !== 'Select program...' && !nonFiduciaryText.classList.contains('text-gray-500')) {
                selectedProgram = nonFiduciaryText.textContent.trim();
                programType = 'non-fiduciary';
            } else if (fiduciaryText && fiduciaryText.textContent.trim() !== 'Select program...' && !fiduciaryText.classList.contains('text-gray-500')) {
                selectedProgram = fiduciaryText.textContent.trim();
                programType = 'fiduciary';
            }
            
            if (!selectedProgram) {
                alert('Please select a Fiduciary or Non-Fiduciary program first');
                return;
            }
            
            // Get program ID from database
            try {
                const programsResponse = await fetch('../api/cabac_programs.php?action=get_programs&type=' + programType);
                const programsData = await programsResponse.json();
                
                if (!programsData.success) {
                    alert('Error getting programs');
                    return;
                }
                
                const program = programsData.programs.find(p => p.program_name === selectedProgram);
                if (!program) {
                    alert('Selected program not found in database');
                    return;
                }
                
                const programId = program.id;
                
                // Collect all entries from table rows
                const rows = tbody.querySelectorAll('tr.program-row');
                const entries = [];
                
                rows.forEach(row => {
                    const programNameInput = row.querySelector('td:first-child input[type="text"]');
                    const approvedBudgetInput = row.querySelector('.approved-budget');
                    const availableAllotmentInput = row.querySelector('.available-amount');
                    
                    const programName = programNameInput ? programNameInput.value.trim() : '';
                    const approvedBudget = approvedBudgetInput ? parseCurrency(approvedBudgetInput.value) : 0;
                    const availableAllotment = availableAllotmentInput ? parseCurrency(availableAllotmentInput.value) : 0;
                    
                    // Only add if there's some data
                    if (programName || approvedBudget > 0 || availableAllotment > 0) {
                        entries.push({
                            program_name: programName,
                            approved_budget: approvedBudget,
                            available_allotment: availableAllotment
                        });
                    }
                });
                
                if (entries.length === 0) {
                    alert('No entries to save. Please add at least one program entry.');
                    return;
                }
                
                // Save to database
                const saveResponse = await fetch('../api/cabac_programs.php?action=save_all_entries', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        program_id: programId,
                        entries: entries
                    })
                });
                
                const saveData = await saveResponse.json();
                
                if (saveData.success) {
                    alert('Budget entries saved successfully!');
                    // Reload entries to get proper IDs from database
                    loadBudgetEntries(selectedProgram, programType);
                } else {
                    alert('Error saving entries: ' + saveData.message);
                }
                
            } catch (error) {
                console.error('Error:', error);
                alert('Error saving budget entries');
            }
        }
        
        // Download CABAC PDF
        async function downloadCabacPdf() {
            // Get selected program from dropdown
            const fiduciaryText = document.getElementById('fiduciarySelectedText');
            const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
            
            let selectedProgram = null;
            let programType = null;
            
            // Check which dropdown has a selected value (not placeholder text)
            if (nonFiduciaryText && nonFiduciaryText.textContent.trim() !== 'Select program...' && !nonFiduciaryText.classList.contains('text-gray-500')) {
                selectedProgram = nonFiduciaryText.textContent.trim();
                programType = 'non-fiduciary';
            } else if (fiduciaryText && fiduciaryText.textContent.trim() !== 'Select program...' && !fiduciaryText.classList.contains('text-gray-500')) {
                selectedProgram = fiduciaryText.textContent.trim();
                programType = 'fiduciary';
            }
            
            if (!selectedProgram) {
                alert('Please select a Fiduciary or Non-Fiduciary program first');
                return;
            }
            
            try {
                // Get program ID from database
                const programsResponse = await fetch('../api/cabac_programs.php?action=get_programs&type=' + programType);
                const programsData = await programsResponse.json();
                
                if (!programsData.success) {
                    alert('Error getting programs');
                    return;
                }
                
                const program = programsData.programs.find(p => p.program_name === selectedProgram);
                if (!program) {
                    alert('Selected program not found in database');
                    return;
                }
                
                // Open PDF in new window for printing
                const pdfUrl = '../api/generate_cabac_pdf.php?program_id=' + encodeURIComponent(program.id);
                const printWindow = window.open(pdfUrl, '_blank');
                
                // Auto-trigger print dialog after page loads
                if (printWindow) {
                    printWindow.onload = function() {
                        printWindow.print();
                    };
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error generating PDF');
            }
        }
        
        // Load budget entries from database
        async function loadBudgetEntries(programName, programType) {
            try {
                // Get program ID
                const programsResponse = await fetch('../api/cabac_programs.php?action=get_programs&type=' + programType);
                const programsData = await programsResponse.json();
                
                if (!programsData.success) return;
                
                const program = programsData.programs.find(p => p.program_name === programName);
                if (!program) return;
                
                // Get entries for this program
                const entriesResponse = await fetch('../api/cabac_programs.php?action=get_entries&program_id=' + program.id);
                const entriesData = await entriesResponse.json();
                
                // Clear existing rows
                const tbody = document.getElementById('budgetTableBody');
                if (!tbody) return;
                tbody.innerHTML = '';
                
                if (entriesData.success && entriesData.entries.length > 0) {
                    // Add rows for each entry
                    entriesData.entries.forEach(entry => {
                        addSimpleProgramRowWithData(entry);
                    });
                }
                // If no entries, table stays empty - user can add rows with ADD PROGRAM button
                
            } catch (error) {
                console.error('Error loading entries:', error);
                // Table stays empty on error
            }
        }
        
        // Add a program row with pre-filled data
        function addSimpleProgramRowWithData(entry) {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            const newRow = document.createElement('tr');
            newRow.className = 'program-row hover:bg-gray-50/50 transition-all duration-200 border-b border-gray-100';
            newRow.setAttribute('data-entry-id', entry.id || '');
            
            newRow.innerHTML = `
                <td class="py-4 px-4" style="width: 20%;">
                    <input type="text" value="${entry.program_name || ''}" placeholder="Enter program name..." class="w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-gray-50 hover:bg-white text-gray-800 text-sm transition-all duration-200">
                </td>
                <td class="py-4 px-3" style="width: 20%;">
                    <input type="text" value="${formatCurrency(entry.approved_budget || 0)}" placeholder="₱ 0.00" class="approved-budget w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-gray-50 hover:bg-white text-gray-800 text-sm text-center font-medium transition-all duration-200" onblur="formatCurrencyInput(this); autoCalculateBalance(this);" oninput="autoCalculateBalance(this);" />
                </td>
                <td class="py-4 px-3" style="width: 25%;">
                    <div class="flex items-center gap-2">
                        <input type="text" value="${formatCurrency(entry.available_allotment || 0)}" placeholder="₱ 0.00" class="available-amount flex-1 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-gray-50 hover:bg-white text-gray-800 text-sm text-center font-medium transition-all duration-200" onblur="formatCurrencyInput(this); autoCalculateBalance(this);" oninput="autoCalculateBalance(this);" />
                        <div class="relative">
                            <button type="button" onclick="toggleAmountMenu(this)" class="p-2.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 active:scale-95 transition-all duration-200 shadow-sm hover:shadow flex-shrink-0" title="Options">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                            <div class="amount-menu hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <button type="button" onclick="addAmountToBalance(this)" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors flex items-center gap-2 rounded-t-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Amount
                                </button>
                                <button type="button" onclick="deductAmountFromBalance(this)" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors flex items-center gap-2 rounded-b-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                    Deduction
                                </button>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-3" style="width: 25%;">
                    <input type="text" value="${formatCurrency(entry.balance || 0)}" readonly placeholder="₱ 0.00" class="balance-amount w-full px-4 py-2.5 border border-gray-200 rounded-lg ${(entry.balance || 0) >= 0 ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700'} text-sm text-center font-semibold" />
                </td>
                <td class="py-4 px-3 text-center" style="width: 10%;">
                    <button type="button" onclick="removeSimpleProgramRow(this)" class="p-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 active:scale-95 transition-all duration-200 shadow-sm hover:shadow" title="Delete Row">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
        }
        
        // Close dropdowns when clicking outside
        document.addEventListener('click', function(e) {
            const fiduciaryWrapper = document.getElementById('fiduciaryDropdownWrapper');
            const nonFiduciaryWrapper = document.getElementById('nonFiduciaryDropdownWrapper');
            const fiduciaryList = document.getElementById('fiduciaryDropdownList');
            const nonFiduciaryList = document.getElementById('nonFiduciaryDropdownList');
            
            if (fiduciaryWrapper && !fiduciaryWrapper.contains(e.target) && fiduciaryList) {
                fiduciaryList.classList.add('hidden');
            }
            if (nonFiduciaryWrapper && !nonFiduciaryWrapper.contains(e.target) && nonFiduciaryList) {
                nonFiduciaryList.classList.add('hidden');
            }
        });

        // Toggle settings dropdown
        function toggleSettingsDropdown() {
            const dropdown = document.getElementById('settingsDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        
        // Close settings dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const settingsBtn = document.getElementById('settingsBtn');
            const dropdown = document.getElementById('settingsDropdown');
            if (dropdown && settingsBtn && !settingsBtn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
        
        // Select entry type from dropdown - opens modal to add new entry
        function selectEntryType(type) {
            const dropdown = document.getElementById('settingsDropdown');
            if (dropdown) {
                dropdown.classList.add('hidden');
            }
            
            // Open the add entry modal
            openAddEntryModal(type);
        }
        
        // Open the add entry modal
        function openAddEntryModal(type) {
            const modal = document.getElementById('addEntryModal');
            const title = document.getElementById('addEntryModalTitle');
            const desc = document.getElementById('addEntryModalDesc');
            const input = document.getElementById('newEntryInput');
            const hiddenType = document.getElementById('entryTypeHidden');
            
            if (modal && title && desc && hiddenType) {
                hiddenType.value = type;
                
                if (type === 'fiduciary') {
                    title.textContent = 'Add New Fiduciary Entry';
                    desc.textContent = 'Enter the name of the new fiduciary program to add to the selection list.';
                } else {
                    title.textContent = 'Add New Non-Fiduciary Entry';
                    desc.textContent = 'Enter the name of the new non-fiduciary program to add to the selection list.';
                }
                
                input.value = '';
                modal.classList.remove('hidden');
                input.focus();
            }
        }
        
        // Close the add entry modal
        function closeAddEntryModal() {
            const modal = document.getElementById('addEntryModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }
        
        // Save new entry to the respective dropdown
        async function saveNewEntry() {
            const input = document.getElementById('newEntryInput');
            const hiddenType = document.getElementById('entryTypeHidden');
            
            if (!input || !hiddenType) return;
            
            const entryName = input.value.trim();
            const entryType = hiddenType.value;
            
            if (!entryName) {
                alert('Please enter an entry name');
                return;
            }
            
            // Add to the appropriate custom dropdown (saves to database)
            const type = entryType === 'fiduciary' ? 'fiduciary' : 'nonFiduciary';
            const success = await addDropdownItem(type, entryName);
            
            if (success) {
                // Close modal
                closeAddEntryModal();
                
                // Show success message
                alert(`"${entryName}" has been added to the ${entryType === 'fiduciary' ? 'Fiduciary' : 'Non-Fiduciary'} Programs selection list!`);
            }
        }

        // Load and save fiduciary type selection
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize template HTML first
            initializeTemplateHTML();
            
            // Load programs from database
            loadProgramsFromDB();
            
            // Restore selected program from localStorage after programs are loaded
            setTimeout(() => {
                restoreSelectedProgram();
            }, 300);
            
            const fiduciaryTypeSelect = document.getElementById('fiduciaryType');
            
            if (fiduciaryTypeSelect) {
                // Load saved selection from localStorage
                try {
                    const savedType = localStorage.getItem('cabac_fiduciary_type');
                    if (savedType) {
                        fiduciaryTypeSelect.value = savedType;
                        // Load entries for the saved type after a short delay to ensure DOM is ready
            setTimeout(() => {
                            loadCabacEntries(savedType);
                        }, 100);
                }
                    // If no saved type, table stays empty - user must select a program first
                } catch (e) {
                    console.error('Error loading fiduciary type:', e);
                    // Table stays empty on error - user must select a program first
                }
                
                // Update programs header based on fiduciary type
                function updateProgramsHeader(fiduciaryType) {
                    const programsHeader = document.getElementById('programsHeader');
                    if (programsHeader) {
                        if (fiduciaryType === 'fiduciary') {
                            programsHeader.textContent = 'FIDUCIARY FEES';
                        } else {
                            programsHeader.textContent = 'PROGRAMS';
                        }
                    }
                }
                
                // Update header on initial load
                const savedType = localStorage.getItem('cabac_fiduciary_type');
                if (savedType) {
                    updateProgramsHeader(savedType);
                }
                
                // Save selection to localStorage and load entries when changed
                fiduciaryTypeSelect.addEventListener('change', function() {
                    try {
                        if (this.value) {
                            localStorage.setItem('cabac_fiduciary_type', this.value);
                            updateProgramsHeader(this.value);
                            // Load entries for the selected type
                            loadCabacEntries(this.value);
                } else {
                            localStorage.removeItem('cabac_fiduciary_type');
                            updateProgramsHeader('');
                            // Clear the table - stays empty until user selects a program
                            const tbody = document.getElementById('budgetTableBody');
                            if (tbody) {
                                tbody.innerHTML = '';
                                budgetRows = [];
                                groupIdCounter = 0;
                            }
                        }
                    } catch (e) {
                        console.error('Error saving fiduciary type to localStorage:', e);
                    }
                });
            }
            // Table stays empty on page load - user must select a program first
        });

        // Load CABAC entries from database
        function loadCabacEntries(fiduciaryType) {
            if (!fiduciaryType) {
                // If no type selected, table stays empty
                return;
            }
            
            const fiscalYear = new Date().getFullYear();
            
            fetch(`../api/load_cabac_entries.php?fiduciary_type=${encodeURIComponent(fiduciaryType)}&fiscal_year=${fiscalYear}`)
                .then(response => response.json())
                .then(data => {
                    const tbody = document.getElementById('budgetTableBody');
                    if (!tbody) return;
                    
                    // Clear all existing rows
                    tbody.innerHTML = '';
                    budgetRows = [];
                    groupIdCounter = 0;
                    
                    if (data.success && data.entries && data.entries.length > 0) {
                        // Group entries by particular
                        const groupedByParticular = {};
                        data.entries.forEach(entry => {
                            const particular = entry.particulars || '';
                            if (!groupedByParticular[particular]) {
                                groupedByParticular[particular] = [];
                            }
                            groupedByParticular[particular].push(entry);
                        });
                        
                        // Create groups and load entries
                        Object.keys(groupedByParticular).forEach(particular => {
                            const entries = groupedByParticular[particular];
                            if (entries.length > 0) {
                                // Create new particular group
                                addParticularGroup();
                                
                                // Get the last added group row
                                const groupRows = tbody.querySelectorAll('.particular-group');
                                const lastGroupRow = groupRows[groupRows.length - 1];
                                const groupId = lastGroupRow.getAttribute('data-group-id');
                                
                                // Set the particular value
                                const particularsSelect = lastGroupRow.querySelector('.particulars-select');
                                if (particularsSelect && particular) {
                                    particularsSelect.value = particular;
                                    // Show program columns since particular is selected
                                    showProgramColumns(particularsSelect);
                                }
                                
                                // Load first entry to get sub_particular (honoraria)
                                if (entries.length > 0) {
                                    const firstEntry = entries[0];
                                    
                                    // If PS is selected and sub_particular exists, set honoraria
                                    if (particular === 'ps' && firstEntry.sub_particular) {
                                        // Wait for honoraria row to be created
                                        setTimeout(() => {
                                            const honorariaRow = document.querySelector(`tr.honoraria-row[data-group-id="${groupId}"]`);
                                            if (honorariaRow) {
                                                const honorariaWrapper = honorariaRow.querySelector('.honoraria-select-wrapper');
                                                if (honorariaWrapper) {
                                                    const searchInput = honorariaWrapper.querySelector('.honoraria-search-input');
                                                    if (searchInput) {
                                                        // Find the option text that matches the value
                                                        const options = honorariaWrapper.honorariaOptions || [];
                                                        const option = options.find(opt => opt.value === firstEntry.sub_particular);
                                                        if (option) {
                                                            searchInput.value = option.text;
                                                            searchInput.setAttribute('data-value', option.value);
                                                            handleHonorariaChange(searchInput, groupId);
                                                        }
                                                    }
                                                } else {
                                                    // Fallback to old select element
                                                    const honorariaSelect = honorariaRow.querySelector('.honoraria-select-element, .honoraria-select');
                                                    if (honorariaSelect) {
                                                        honorariaSelect.value = firstEntry.sub_particular;
                                                        honorariaSelect.dispatchEvent(new Event('change'));
                                                    }
                                                }
                                            }
                                        }, 100);
                                    }
                                    
                                    // Load first entry into the group row or program row
                                    if (firstEntry.programs) {
                                        // Check if there's a program row or if we need to create one
                                        const programRow = document.querySelector(`tr.program-row[data-group-id="${groupId}"]`);
                                        if (programRow) {
                                            populateProgramRow(programRow, firstEntry);
                                        } else {
                                            populateProgramRow(lastGroupRow, firstEntry);
                                        }
                                    }
                                    
                                    // Add additional program rows for remaining entries
                                    for (let i = 1; i < entries.length; i++) {
                                        addProgramRowToGroup(groupId, entries[i]);
                                    }
                                }
                            }
                        });
                } else {
                        // If no entries, table stays empty - user can add rows manually
                    }
                })
                .catch(error => {
                    console.error('Error loading CABAC entries:', error);
                    // Table stays empty on error
                });
        }

        // Populate a program row with entry data
        function populateProgramRow(row, entry) {
            // Try to find programs search input (custom dropdown) or select (old structure)
            const programsWrapper = row.querySelector('.programs-select-wrapper');
            let programsInput = null;
            if (programsWrapper) {
                programsInput = programsWrapper.querySelector('.programs-search-input');
            }
            if (!programsInput) {
                const programsSelect = row.querySelector('.programs-select-element, .programs-select');
                if (programsSelect) {
                    programsSelect.value = entry.programs || '';
                }
            } else {
                // Set value in search input
                if (entry.programs) {
                    programsInput.value = entry.programs;
                    programsInput.setAttribute('data-value', entry.programs.toLowerCase().replace(/\s+/g, '-'));
                }
            }
            
            const approvedBudgetInput = row.querySelector('.approved-budget');
            const totalAllotmentInput = row.querySelector('.total-allotment');
            const balanceInput = row.querySelector('.balance-amount');
            if (approvedBudgetInput) {
                approvedBudgetInput.value = formatCurrency(entry.approved_budget || 0);
            }
            if (totalAllotmentInput) {
                totalAllotmentInput.value = formatCurrency(entry.total_allotment || 0);
            }
            if (balanceInput) {
                balanceInput.value = formatCurrency(entry.balance || 0);
            }
            
            // Store allotment details
            const rowId = row.rowIndex;
            if (entry.allotment_details && Array.isArray(entry.allotment_details)) {
                budgetRows[rowId] = entry.allotment_details;
            } else {
                budgetRows[rowId] = [];
            }
        }

        // Add program row to existing group with data
        function addProgramRowToGroup(groupId, entry) {
            const groupRows = document.querySelectorAll(`tr[data-group-id="${groupId}"]`);
            if (groupRows.length === 0) return;
            
            const firstGroupRow = groupRows[0];
            const addButton = firstGroupRow.querySelector('button[onclick*="addProgramRow"]');
            if (addButton) {
                // Add the row first
                addProgramRow(addButton);
                
                // Then populate it
                const allGroupRows = document.querySelectorAll(`tr[data-group-id="${groupId}"]`);
                const newRow = allGroupRows[allGroupRows.length - 1];
                populateProgramRow(newRow, entry);
            }
        }

        // Helper function to create particulars select (fallback)
        function createParticularsSelect() {
            const select = document.createElement('select');
            select.className = 'particulars-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            
            // Use stored template HTML if available
            if (templateParticularsHTML) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = templateParticularsHTML;
                const templateSelect = tempDiv.firstElementChild;
                if (templateSelect) {
                    select.innerHTML = templateSelect.innerHTML;
                    return select;
                }
            }
            
            // Copy from existing select if available
            const existing = document.querySelector('.particulars-select');
            if (existing) {
                select.innerHTML = existing.innerHTML;
            }
            return select;
        }

        // Helper function to create programs select (fallback)
        function createProgramsSelect() {
            const select = document.createElement('select');
            select.className = 'programs-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            
            // Use stored template HTML if available
            if (templateProgramsHTML) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = templateProgramsHTML;
                const templateSelect = tempDiv.firstElementChild;
                if (templateSelect) {
                    select.innerHTML = templateSelect.innerHTML;
                    return select;
                }
            }
            
            // Copy from existing select if available
            const existing = document.querySelector('.programs-select');
            if (existing) {
                select.innerHTML = existing.innerHTML;
            }
            return select;
        }

        // Profile dropdown functionality
        function toggleProfileDropdown(event) {
            if (event) {
                event.stopPropagation();
            }
            const dropdown = document.getElementById('profileDropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }

        // Close profile dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            if (!dropdown) return;
            
            const profileButton = event.target.closest('button[onclick*="toggleProfileDropdown"]');
            const isInsideDropdown = dropdown.contains(event.target);
            
            if (!profileButton && !isInsideDropdown) {
                dropdown.classList.add('hidden');
            }
        });

        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        // Generate unique group ID
        let groupIdCounter = 0;
        function getNextGroupId() {
            return groupIdCounter++;
        }

        // Add new PARTICULAR group (adds a new particular with one program row)
        function addParticularGroup() {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            const groupId = getNextGroupId();
            const groupRow = document.createElement('tr');
            groupRow.className = 'particular-group hover:bg-gray-50 transition-colors';
            groupRow.setAttribute('data-group-id', groupId);
            
            groupRow.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <div id="particular-select-container-${groupId}" class="flex-1 min-w-0"></div>
                        <button 
                            type="button"
                            onclick="addSubParticularRow(this)"
                            class="bg-red-600 text-white rounded hover:bg-red-700 transition-colors flex-shrink-0 w-8 h-8 flex items-center justify-center"
                            id="add-subparticular-btn-${groupId}"
                            style="display: none;"
                            title="Add Sub-Particular Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 border-b border-gray-200" id="subparticular-cell-${groupId}" style="display: none;">
                    <div class="flex items-center gap-2">
                        <div id="subparticular-select-container-${groupId}" class="flex-1 min-w-0">
                            <!-- Sub-particular (Honoraria) dropdown will appear here when PS is selected -->
                        </div>
                        <button 
                            type="button"
                            onclick="addProgramRow(this)"
                            class="bg-red-600 text-white rounded hover:bg-red-700 transition-colors flex-shrink-0 w-8 h-8 flex items-center justify-center"
                            title="Add Program Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 border-b border-gray-200" id="programs-cell-${groupId}" style="display: none;">
                    <div id="program-select-container-${groupId}" class="w-full">
                        <!-- Programs dropdown will appear here when Honoraria is selected -->
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200" id="approved-budget-cell-${groupId}" style="display: none;">
                    <input 
                        type="text" 
                        placeholder="₱ 0.00"
                        class="approved-budget w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-right"
                        onblur="formatCurrencyInput(this); calculateBalance(this);"
                    />
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200" id="allotment-cell-${groupId}" style="display: none;">
                    <div class="flex items-center gap-2 justify-end">
                        <input 
                            type="text" 
                            readonly
                            placeholder="₱ 0.00"
                            class="total-allotment px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 cursor-pointer text-right"
                            onclick="showAllotmentDetails(this)"
                            style="min-width: 120px;"
                        />
                        <button 
                            type="button"
                            onclick="showAllotmentDetails(this.previousElementSibling)"
                            class="px-2 py-2 bg-maroon text-white rounded hover:bg-maroon-dark transition-colors text-xs font-semibold whitespace-nowrap"
                        >
                            DETAILS
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200" id="balance-cell-${groupId}" style="display: none;">
                    <input 
                        type="text" 
                        readonly
                        placeholder="₱ 0.00"
                        class="balance-amount w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 text-right"
                    />
                </td>
                <td class="px-4 py-3 text-center border-b border-gray-200" id="action-cell-${groupId}" style="display: none;">
                    <div class="flex items-center justify-center gap-1">
                        <button 
                            type="button"
                            onclick="openAmountModal(this)"
                            class="p-1.5 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                            title="Add Amount"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                        <button 
                            type="button"
                            onclick="removeParticularGroup(this)"
                            class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                            title="Delete Particular Group"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            
            // Create selects AFTER innerHTML is set
            let particularsSelect;
            if (templateParticularsHTML) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = templateParticularsHTML;
                particularsSelect = tempDiv.firstElementChild;
            } else {
                particularsSelect = createParticularsSelect();
            }
            
            if (particularsSelect) {
                particularsSelect.setAttribute('onchange', 'onParticularsChange(this)');
                particularsSelect.addEventListener('change', function() {
                    showProgramColumns(this);
                    handleAddNewSelect(this, 'particular');
                });
            }
            
            // Programs select will be created when sub-particular is selected (using createProgramsSelectInContainer)
            
            // Insert particulars select into the row
            const particularSelectContainer = groupRow.querySelector(`#particular-select-container-${groupId}`);
            if (particularSelectContainer && particularsSelect) {
                particularsSelect.classList.add('w-full');
                particularSelectContainer.appendChild(particularsSelect);
            }
            
            tbody.appendChild(groupRow);
        }
        
        // Add honoraria row when PS is selected
        function addHonorariaRow(groupId) {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            const groupRow = document.querySelector(`tr[data-group-id="${groupId}"].particular-group`);
            if (!groupRow) return;
            
            // Check if honoraria row already exists
            const existingHonorariaRow = document.querySelector(`tr[data-group-id="${groupId}"].honoraria-row`);
            if (existingHonorariaRow) return;
            
            // Create honoraria row
            const honorariaRow = document.createElement('tr');
            honorariaRow.className = 'honoraria-row hover:bg-gray-50 transition-colors';
            honorariaRow.setAttribute('data-group-id', groupId);
            
            honorariaRow.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-200" id="honoraria-cell-${groupId}">
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-500 font-medium">sub-particular:</span>
                        <div id="honoraria-select-container-${groupId}" class="flex-1 min-w-0"></div>
                        <button 
                            type="button"
                            onclick="addHonorariaOption(this)"
                            class="bg-red-600 text-white rounded hover:bg-red-700 transition-colors flex-shrink-0 w-8 h-8 flex items-center justify-center"
                            title="Add Honoraria Option"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <div id="program-select-container-honoraria-${groupId}" class="w-full">
                        <!-- Programs dropdown will appear here when Honoraria is selected -->
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200"></td>
                <td class="px-4 py-3 text-right border-b border-gray-200"></td>
                <td class="px-4 py-3 text-right border-b border-gray-200"></td>
                <td class="px-4 py-3 text-center border-b border-gray-200"></td>
            `;
            
            // Insert after group row first (so container exists in DOM)
            groupRow.parentNode.insertBefore(honorariaRow, groupRow.nextSibling);
            
            // Now create and insert honoraria select using custom dropdown
            const honorariaContainer = honorariaRow.querySelector(`#honoraria-select-container-${groupId}`);
            if (honorariaContainer) {
                createHonorariaSelectInContainer(honorariaContainer, groupId);
            }
            
            // Update rowspan
            updateParticularRowspan(groupId);
        }
        
        // Add program row when Honoraria is selected
        function addProgramRowForHonoraria(groupId) {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            const honorariaRow = document.querySelector(`tr[data-group-id="${groupId}"].honoraria-row`);
            if (!honorariaRow) return;
            
            // Check if program row already exists
            const existingProgramRow = document.querySelector(`tr[data-group-id="${groupId}"].program-row`);
            if (existingProgramRow) return;
            
            // Create program row
            const programRow = document.createElement('tr');
            programRow.className = 'program-row hover:bg-gray-50 transition-colors';
            programRow.setAttribute('data-group-id', groupId);
            
            programRow.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-200"></td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <div id="program-select-container-${groupId}" class="w-full"></div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="number" 
                        step="0.01"
                        placeholder="0.00"
                        class="input-amount w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-right"
                    />
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="number" 
                        step="0.01"
                        placeholder="0.00"
                        readonly
                        class="auto-add-amount w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 text-right cursor-pointer"
                        onclick="autoAddToAllotment(this)"
                    />
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="number" 
                        step="0.01"
                        placeholder="0.00"
                        readonly
                        class="balance-amount w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 text-right"
                    />
                </td>
                <td class="px-4 py-3 text-center border-b border-gray-200">
                    <div class="flex items-center justify-center gap-1">
                        <button 
                            type="button"
                            onclick="openAmountModal(this)"
                            class="p-1.5 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                            title="Add Amount"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                        <button 
                            type="button"
                            onclick="removeProgramRow(this)"
                            class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                            title="Delete Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            
            // Create and insert programs select
            // Create programs select using custom dropdown
            const programContainer = programRow.querySelector(`#program-select-container-${groupId}`);
            if (programContainer) {
                createProgramsSelectInContainer(programContainer, groupId);
            }
            
            // Insert after honoraria row
            honorariaRow.parentNode.insertBefore(programRow, honorariaRow.nextSibling);
            
            // Update rowspan
            updateParticularRowspan(groupId);
        }
        
        // Add honoraria option or program row for honoraria-overload
        function addHonorariaOption(button) {
            const honorariaRow = button.closest('tr.honoraria-row');
            if (!honorariaRow) {
                alert('Add new honoraria option functionality');
                return;
            }
            
            const groupId = honorariaRow.getAttribute('data-group-id');
            if (!groupId) return;
            
            // Get the selected honoraria value
            const honorariaWrapper = honorariaRow.querySelector('.honoraria-select-wrapper');
            let honorariaValue = '';
            if (honorariaWrapper) {
                const searchInput = honorariaWrapper.querySelector('.honoraria-search-input');
                if (searchInput) {
                    honorariaValue = searchInput.getAttribute('data-value') || '';
                }
            } else {
                // Fallback to old select element
                const honorariaSelect = honorariaRow.querySelector('.honoraria-select-element, .honoraria-select');
                if (honorariaSelect) {
                    honorariaValue = honorariaSelect.value;
                }
            }
            
            // If honoraria-overload is selected, add program row
            if (honorariaValue === 'honoraria-overload') {
                addProgramRowForHonorariaOverload(button, groupId);
            } else {
                // For other cases, show add new honoraria option (placeholder)
                alert('Add new honoraria option functionality');
            }
        }
        
        // Add program row for honoraria-overload
        function addProgramRowForHonorariaOverload(button, groupId) {
            const honorariaRow = button.closest('tr.honoraria-row');
            if (!honorariaRow) return;
            
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            // Create new program row
            const newRow = document.createElement('tr');
            newRow.className = 'program-row hover:bg-gray-50 transition-colors';
            newRow.setAttribute('data-group-id', groupId);
            
            newRow.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-200">
                    <!-- Empty for program rows (honoraria cell spans) -->
                </td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <!-- Empty for program rows (sub-particular spans) -->
                </td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <div id="program-select-container-${groupId}-honoraria-overload" class="w-full"></div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="text" 
                        placeholder="₱ 0.00"
                        class="approved-budget w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-right"
                        onblur="formatCurrencyInput(this); calculateBalance(this);"
                    />
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <div class="flex items-center gap-2 justify-end">
                        <input 
                            type="text" 
                            readonly
                            placeholder="₱ 0.00"
                            class="total-allotment px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 cursor-pointer text-right"
                            onclick="showAllotmentDetails(this)"
                            style="min-width: 120px;"
                        />
                        <button 
                            type="button"
                            onclick="showAllotmentDetails(this.previousElementSibling)"
                            class="px-2 py-2 bg-maroon text-white rounded hover:bg-maroon-dark transition-colors text-xs font-semibold whitespace-nowrap"
                        >
                            DETAILS
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="text" 
                        readonly
                        placeholder="₱ 0.00"
                        class="balance-amount w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 text-right"
                    />
                </td>
                <td class="px-4 py-3 text-center border-b border-gray-200">
                    <div class="flex items-center justify-center gap-1">
                        <button 
                            type="button"
                            onclick="openAmountModal(this)"
                            class="p-1.5 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                            title="Add Amount"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                        </button>
                        <button 
                            type="button"
                            onclick="removeProgramRow(this)"
                            class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                            title="Delete Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            
            // Create programs select using custom dropdown
            const programContainer = newRow.querySelector(`#program-select-container-${groupId}-honoraria-overload`);
            if (programContainer) {
                createProgramsSelectInContainer(programContainer, `${groupId}-honoraria-overload`);
            }
            
            // Insert after honoraria row
            honorariaRow.parentNode.insertBefore(newRow, honorariaRow.nextSibling);
            
            // Update rowspan
            updateParticularRowspan(groupId);
        }
        
        // Auto add to allotment function
        function autoAddToAllotment(input) {
            const row = input.closest('tr');
            if (!row) return;
            
            const inputAmount = row.querySelector('.input-amount');
            const autoAddAmount = row.querySelector('.auto-add-amount');
            const balanceAmount = row.querySelector('.balance-amount');
            
            if (inputAmount && autoAddAmount && balanceAmount) {
                const inputValue = parseFloat(inputAmount.value) || 0;
                const currentAutoAdd = parseFloat(autoAddAmount.value) || 0;
                const newAutoAdd = currentAutoAdd + inputValue;
                
                autoAddAmount.value = newAutoAdd.toFixed(2);
                balanceAmount.value = newAutoAdd.toFixed(2);
                inputAmount.value = '';
            }
        }

        // Add program row within a particular group
        function addProgramRow(button) {
            const currentRow = button.closest('tr');
            if (!currentRow) return;
            
            const groupId = currentRow.getAttribute('data-group-id');
            if (!groupId) return;
            
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            // Create new program row (without particulars column)
            const newRow = document.createElement('tr');
            newRow.className = 'program-row hover:bg-gray-50 transition-colors';
            newRow.setAttribute('data-group-id', groupId);
            
            const uniqueId = Date.now();
            
            newRow.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-200">
                    <!-- Empty for program rows (particular spans) -->
                </td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <!-- Empty for program rows (sub-particular spans) -->
                </td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <div id="program-select-container-${groupId}-program-row-${uniqueId}" class="w-full">
                        <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg" placeholder="Enter name...">
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="text" 
                        placeholder="INPUTAMOUNT"
                        class="approved-budget w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-center"
                        onblur="formatCurrencyInput(this); calculateBalance(this);"
                        oninput="autoCalculateBalance(this);"
                    />
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            placeholder="INPUTAMOUNT"
                            class="available-amount w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-center"
                            onblur="formatCurrencyInput(this); calculateBalance(this);"
                            oninput="autoCalculateBalance(this);"
                        />
                        <button 
                            type="button"
                            onclick="addAmountToBalance(this)"
                            class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                            title="Add Amount"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200">
                    <input 
                        type="text" 
                        readonly
                        placeholder="AUTO CALCULATE"
                        class="balance-amount w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 text-center"
                    />
                </td>
                <td class="px-4 py-3 text-center border-b border-gray-200">
                    <div class="flex items-center justify-center gap-1">
                        <button 
                            type="button"
                            onclick="removeProgramRow(this)"
                            class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                            title="Delete Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            
            // Insert after current row
            currentRow.parentNode.insertBefore(newRow, currentRow.nextSibling);
            
            // Update rowspan of the particular cell
            updateParticularRowspan(groupId);
        }

        // Add sub-particular row within a particular group
        function addSubParticularRow(button) {
            const currentRow = button.closest('tr');
            if (!currentRow) return;
            
            const groupId = currentRow.getAttribute('data-group-id');
            if (!groupId) return;
            
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            // Get the particular value to determine what sub-particulars to show
            const particularSelect = currentRow.querySelector('.particulars-select');
            const particularValue = particularSelect ? particularSelect.value : '';
            
            // Create new sub-particular row
            const newRow = document.createElement('tr');
            newRow.className = 'subparticular-row hover:bg-gray-50 transition-colors';
            newRow.setAttribute('data-group-id', groupId);
            
            const uniqueId = Date.now();
            
            newRow.innerHTML = `
                <td class="px-4 py-3 border-b border-gray-200">
                    <!-- Empty for sub-particular rows (particular spans) -->
                </td>
                <td class="px-4 py-3 border-b border-gray-200">
                    <div class="flex items-center gap-2">
                        <div id="subparticular-select-container-${groupId}-${uniqueId}" class="flex-1 min-w-0"></div>
                        <button 
                            type="button"
                            onclick="addProgramRow(this)"
                            class="bg-red-600 text-white rounded hover:bg-red-700 transition-colors flex-shrink-0 w-8 h-8 flex items-center justify-center"
                            title="Add Program Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 border-b border-gray-200" id="programs-cell-${groupId}-${uniqueId}" style="display: none;">
                    <div id="program-select-container-${groupId}-${uniqueId}" class="w-full"></div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200" id="approved-budget-cell-${groupId}-${uniqueId}" style="display: none;">
                    <input 
                        type="text" 
                        placeholder="₱ 0.00"
                        class="approved-budget w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-right"
                        onblur="formatCurrencyInput(this); calculateBalance(this);"
                    />
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200" id="allotment-cell-${groupId}-${uniqueId}" style="display: none;">
                    <div class="flex items-center gap-2 justify-end">
                        <input 
                            type="text" 
                            readonly
                            placeholder="₱ 0.00"
                            class="total-allotment px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 cursor-pointer text-right"
                            onclick="showAllotmentDetails(this)"
                            style="min-width: 120px;"
                        />
                        <button 
                            type="button"
                            onclick="showAllotmentDetails(this.previousElementSibling)"
                            class="px-2 py-2 bg-maroon text-white rounded hover:bg-maroon-dark transition-colors text-xs font-semibold whitespace-nowrap"
                        >
                            DETAILS
                        </button>
                    </div>
                </td>
                <td class="px-4 py-3 text-right border-b border-gray-200" id="balance-cell-${groupId}-${uniqueId}" style="display: none;">
                    <input 
                        type="text" 
                        readonly
                        placeholder="₱ 0.00"
                        class="balance-amount w-full px-3 py-2 border border-gray-300 rounded bg-gray-50 text-gray-900 text-right"
                    />
                </td>
                <td class="px-4 py-3 text-center border-b border-gray-200" id="action-cell-${groupId}-${uniqueId}" style="display: none;">
                    <div class="flex items-center justify-center gap-1">
                        <button 
                            type="button"
                            onclick="openAmountModal(this)"
                            class="p-1.5 bg-green-600 text-white rounded hover:bg-green-700 transition-colors"
                            title="Add Amount"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                        <button 
                            type="button"
                            onclick="removeSubParticularRow(this)"
                            class="p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors"
                            title="Delete Sub-Particular Row"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </div>
                </td>
            `;
            
            // Create and insert sub-particular select based on particular type
            const subParticularContainer = newRow.querySelector(`#subparticular-select-container-${groupId}-${uniqueId}`);
            if (subParticularContainer) {
                if (particularValue === 'ps') {
                    // Create honoraria select for PS
                    createHonorariaSelectInContainer(subParticularContainer, `${groupId}-${uniqueId}`);
                } else if (particularValue === 'co') {
                    // Create Capital Outlay select
                    createMOOESubParticularSelectInContainer(subParticularContainer, `${groupId}-${uniqueId}`, 'co');
                } else if (particularValue === 'mooe') {
                    // Create MOOE select
                    createMOOESubParticularSelectInContainer(subParticularContainer, `${groupId}-${uniqueId}`, 'mooe');
                }
            }
            
            // Find the last row in this group to insert after all program rows
            const allGroupRows = Array.from(document.querySelectorAll(`tr[data-group-id="${groupId}"]`));
            let lastRowInGroup = null;
            
            // Find the last row that belongs to this group
            if (allGroupRows.length > 0) {
                lastRowInGroup = allGroupRows[allGroupRows.length - 1];
            }
            
            // Insert after the last row in the group (or after current row if no other rows found)
            const insertAfterRow = lastRowInGroup || currentRow;
            insertAfterRow.parentNode.insertBefore(newRow, insertAfterRow.nextSibling);
            
            // Update rowspan
            updateParticularRowspan(groupId);
        }
        
        // Remove sub-particular row
        function removeSubParticularRow(button) {
            const row = button.closest('tr');
            if (!row) return;
            
            const groupId = row.getAttribute('data-group-id');
            row.remove();
            
            // Update rowspan
            updateParticularRowspan(groupId);
        }

        // Update rowspan for particular cell when rows are added/removed
        function updateParticularRowspan(groupId) {
            const groupRows = document.querySelectorAll(`tr[data-group-id="${groupId}"]`);
            const particularCell = document.querySelector(`#particular-cell-${groupId}`);
            
            if (particularCell && groupRows.length > 0) {
                particularCell.setAttribute('rowspan', groupRows.length);
            }
        }

        // Initialize on page load - table stays empty until user selects a program
        document.addEventListener('DOMContentLoaded', function() {
            // Table stays empty on page load - user must select a program first or click ADD PROGRAM
            // Note: ADD PROGRAM + button uses onclick attribute, no need for event listener
        });

        // Handle particulars change
        function onParticularsChange(select) {
            // This function can be used to update UI when particular changes
            // For now, we'll just keep it for future enhancements
        }

        // Show sub-particular and programs when particular is selected
        function showProgramColumns(select) {
            const row = select.closest('tr');
            if (!row) return;
            
            const groupId = row.getAttribute('data-group-id');
            if (!groupId) return;
            
            const particularValue = select.value;
            const subParticularCell = row.querySelector(`#subparticular-cell-${groupId}`);
            const subParticularContainer = row.querySelector(`#subparticular-select-container-${groupId}`);
            const programContainer = row.querySelector(`#program-select-container-${groupId}`);
            
            // Remove all sub-particular rows when particular changes
            const allSubParticularRows = document.querySelectorAll(`tr.subparticular-row[data-group-id="${groupId}"]`);
            allSubParticularRows.forEach(subRow => {
                // Remove any MOOE dropdown overlays associated with this row
                const subParticularContainer = subRow.querySelector('[id*="subparticular-select-container"]');
                if (subParticularContainer) {
                    const mooeWrapper = subParticularContainer.querySelector('.mooe-select-wrapper');
                    if (mooeWrapper) {
                        const uniqueId = mooeWrapper.getAttribute('data-unique-id');
                        if (uniqueId) {
                            const overlay = document.querySelector(`.mooe-dropdown-overlay[data-unique-id="${uniqueId}"]`);
                            if (overlay) {
                                overlay.remove();
                            }
                        }
                    }
                }
                subRow.remove();
            });
            // Also remove any MOOE overlays from main group row
            const allMooeOverlays = document.querySelectorAll('.mooe-dropdown-overlay');
            allMooeOverlays.forEach(overlay => {
                const wrapper = document.querySelector(`.mooe-select-wrapper[data-unique-id="${overlay.getAttribute('data-unique-id')}"]`);
                if (wrapper && wrapper.closest(`tr[data-group-id="${groupId}"]`)) {
                    overlay.remove();
                }
            });
            // Update rowspan after removing rows
            updateParticularRowspan(groupId);
            
            if (particularValue && particularValue !== '') {
                // Show "+" button for adding sub-particular rows
                const addSubParticularBtn = row.querySelector(`#add-subparticular-btn-${groupId}`);
                if (addSubParticularBtn) {
                    addSubParticularBtn.style.display = 'flex';
                }
                
                // Show SUB-PARTICULAR column when particular is selected
                if (subParticularCell) {
                    subParticularCell.style.display = '';
                    
                    // If PS is selected, show honoraria dropdown
                    if (particularValue === 'ps') {
                        if (subParticularContainer) {
                            // Hide MOOE wrapper if exists
                            const mooeWrapper = subParticularContainer.querySelector('.mooe-select-wrapper');
                            if (mooeWrapper) {
                                mooeWrapper.style.display = 'none';
                                // Also remove the MOOE dropdown overlay if it exists
                                const mooeOverlay = document.querySelector(`.mooe-dropdown-overlay[data-group-id="${groupId}"]`);
                                if (mooeOverlay) {
                                    mooeOverlay.remove();
                                }
                            }
                            // Create honoraria select wrapper if it doesn't exist
                            if (!subParticularContainer.querySelector('.honoraria-select-wrapper')) {
                                createHonorariaSelectInContainer(subParticularContainer, groupId);
                            }
                            // Show the honoraria select wrapper
                            const honorariaWrapper = subParticularContainer.querySelector('.honoraria-select-wrapper');
                            if (honorariaWrapper) {
                                honorariaWrapper.style.display = '';
                                // Reset search input if exists
                                const searchInput = honorariaWrapper.querySelector('.honoraria-search-input');
                                if (searchInput) {
                                    searchInput.value = '';
                                    searchInput.setAttribute('data-value', '');
                                }
                            }
                        }
                    } else if (particularValue === 'co') {
                        // If Capital Outlay is selected, show Capital Outlay searchable input
                        if (subParticularContainer) {
                            // Hide honoraria wrapper if exists
                            const honorariaWrapper = subParticularContainer.querySelector('.honoraria-select-wrapper');
                            if (honorariaWrapper) {
                                honorariaWrapper.style.display = 'none';
                                // Also remove the honoraria dropdown overlay if it exists
                                const uniqueId = honorariaWrapper.getAttribute('data-unique-id');
                                if (uniqueId) {
                                    const honorariaOverlay = document.querySelector(`.honoraria-dropdown-overlay[data-unique-id="${uniqueId}"]`);
                                    if (honorariaOverlay) {
                                        honorariaOverlay.remove();
                                    }
                                }
                                // Reset search input if exists
                                const searchInput = honorariaWrapper.querySelector('.honoraria-search-input');
                                if (searchInput) {
                                    searchInput.value = '';
                                    searchInput.setAttribute('data-value', '');
                                }
                            }
                            // Hide MOOE wrapper if exists
                            const mooeWrapper = subParticularContainer.querySelector('.mooe-select-wrapper');
                            if (mooeWrapper) {
                                mooeWrapper.style.display = 'none';
                                const mooeOverlay = document.querySelector(`.mooe-dropdown-overlay[data-group-id="${groupId}"]`);
                                if (mooeOverlay) {
                                    mooeOverlay.remove();
                                }
                            }
                            // Create Capital Outlay select wrapper if it doesn't exist
                            if (!subParticularContainer.querySelector('.mooe-select-wrapper')) {
                                createMOOESubParticularSelectInContainer(subParticularContainer, groupId, 'co');
                            }
                            // Show the Capital Outlay select wrapper
                            const coWrapper = subParticularContainer.querySelector('.mooe-select-wrapper');
                            if (coWrapper) {
                                coWrapper.style.display = '';
                                // Reset select value
                                const selectElement = coWrapper.querySelector('.mooe-subparticular-select');
                                if (selectElement) {
                                    selectElement.value = '';
                                }
                                // Reset search input if exists
                                const searchInput = coWrapper.querySelector('.mooe-search-input');
                                if (searchInput) {
                                    searchInput.value = '';
                                    searchInput.setAttribute('data-value', '');
                                }
                            }
                        }
                    } else if (particularValue === 'mooe') {
                        // If MOOE is selected, show MOOE searchable input
                        if (subParticularContainer) {
                            // Hide honoraria wrapper if exists
                            const honorariaWrapper = subParticularContainer.querySelector('.honoraria-select-wrapper');
                            if (honorariaWrapper) {
                                honorariaWrapper.style.display = 'none';
                                // Also remove the honoraria dropdown overlay if it exists
                                const uniqueId = honorariaWrapper.getAttribute('data-unique-id');
                                if (uniqueId) {
                                    const honorariaOverlay = document.querySelector(`.honoraria-dropdown-overlay[data-unique-id="${uniqueId}"]`);
                                    if (honorariaOverlay) {
                                        honorariaOverlay.remove();
                                    }
                                }
                                // Reset search input if exists
                                const searchInput = honorariaWrapper.querySelector('.honoraria-search-input');
                                if (searchInput) {
                                    searchInput.value = '';
                                    searchInput.setAttribute('data-value', '');
                                }
                            }
                            // Create MOOE select wrapper if it doesn't exist
                            if (!subParticularContainer.querySelector('.mooe-select-wrapper')) {
                                createMOOESubParticularSelectInContainer(subParticularContainer, groupId, 'mooe');
                            }
                            // Show the MOOE select wrapper
                            const mooeWrapper = subParticularContainer.querySelector('.mooe-select-wrapper');
                            if (mooeWrapper) {
                                mooeWrapper.style.display = '';
                                // Reset select value
                                const selectElement = mooeWrapper.querySelector('.mooe-subparticular-select');
                                if (selectElement) {
                                    selectElement.value = '';
                                }
                                // Reset search input if exists
                                const searchInput = mooeWrapper.querySelector('.mooe-search-input');
                                if (searchInput) {
                                    searchInput.value = '';
                                    searchInput.setAttribute('data-value', '');
                                }
                            }
                        }
                    } else {
                        // Hide both selects for other particulars
                        if (subParticularContainer) {
                            const honorariaWrapper = subParticularContainer.querySelector('.honoraria-select-wrapper');
                            if (honorariaWrapper) {
                                honorariaWrapper.style.display = 'none';
                                // Reset search input if exists
                                const searchInput = honorariaWrapper.querySelector('.honoraria-search-input');
                                if (searchInput) {
                                    searchInput.value = '';
                                    searchInput.setAttribute('data-value', '');
                                }
                            }
                            const mooeWrapper = subParticularContainer.querySelector('.mooe-select-wrapper');
                            if (mooeWrapper) {
                                const selectElement = mooeWrapper.querySelector('.mooe-subparticular-select');
                                if (selectElement) {
                                    selectElement.value = '';
                                }
                                mooeWrapper.style.display = 'none';
                                // Remove MOOE dropdown overlay if exists
                                const mooeOverlay = document.querySelector(`.mooe-dropdown-overlay[data-group-id="${groupId}"]`);
                                if (mooeOverlay) {
                                    mooeOverlay.remove();
                                }
                            }
                        }
                    }
                }
                
                // Hide programs and amount/action columns until sub-particular is selected
                const programsCell = row.querySelector(`#programs-cell-${groupId}`);
                const approvedBudgetCell = row.querySelector(`#approved-budget-cell-${groupId}`);
                const allotmentCell = row.querySelector(`#allotment-cell-${groupId}`);
                const balanceCell = row.querySelector(`#balance-cell-${groupId}`);
                const actionCell = row.querySelector(`#action-cell-${groupId}`);
                
                if (programsCell) programsCell.style.display = 'none';
                if (approvedBudgetCell) approvedBudgetCell.style.display = 'none';
                if (allotmentCell) allotmentCell.style.display = 'none';
                if (balanceCell) balanceCell.style.display = 'none';
                if (actionCell) actionCell.style.display = 'none';
            } else {
                // Hide "+" button if no particular selected
                const addSubParticularBtn = row.querySelector(`#add-subparticular-btn-${groupId}`);
                if (addSubParticularBtn) {
                    addSubParticularBtn.style.display = 'none';
                }
                
                // Hide everything if no particular selected
                if (subParticularCell) {
                    subParticularCell.style.display = 'none';
                }
                if (programContainer) {
                    programContainer.style.display = 'none';
                }
            }
        }
        
        // Create sub-particular select that transforms to search bar on click
        function createMOOESubParticularSelectInContainer(container, groupId, type = 'co') {
            let optionsList;
            let prefix;
            
            if (type === 'mooe') {
                // MOOE options list
                optionsList = [
                    { value: 'mooe-travel-local', text: 'Travel Expenses - Local' },
                    { value: 'mooe-travel-foreign', text: 'Travel Expenses - Foreign' },
                    { value: 'mooe-training', text: 'Training Expenses' },
                    { value: 'mooe-scholarship', text: 'Scholarship Expenses' },
                    { value: 'mooe-office-supplies', text: 'Office Supplies Expenses' },
                    { value: 'mooe-water', text: 'Water Expenses' },
                    { value: 'mooe-electricity', text: 'Electricity Expenses' },
                    { value: 'mooe-insurance', text: 'Insurance Expenses' },
                    { value: 'mooe-subscription', text: 'Subscription Expenses' },
                    { value: 'mooe-labor', text: 'Labor and Wages' },
                    { value: 'mooe-fuel', text: 'Fuel, Oil and Lubricants Expenses' },
                    { value: 'mooe-printing', text: 'Printing and Publication Expenses' },
                    { value: 'mooe-rewards', text: 'Rewards and Incentives' },
                    { value: 'mooe-textbooks', text: 'Textbooks & Instructional Materials' },
                    { value: 'mooe-forms', text: 'Accountable Forms' },
                    { value: 'mooe-bond', text: 'Facility Bond Prem.' },
                    { value: 'mooe-membership', text: 'Membership Dues' },
                    { value: 'mooe-taxes', text: 'Taxes, Duties and Licenses' },
                    { value: 'mooe-supplies', text: 'Other Supplies and Materials' },
                    { value: 'mooe-professional', text: 'Other Professional Services' },
                    { value: 'mooe-consultancy', text: 'Consultancy Services' },
                    { value: 'mooe-janitor', text: 'Janitor Services' },
                    { value: 'mooe-security', text: 'Security Services' },
                    { value: 'mooe-repair-printing', text: 'Repairs and Maintenance - Printing Equipment' },
                    { value: 'mooe-repair-office', text: 'Repairs and Maintenance - Office Equipment' },
                    { value: 'mooe-repair-structures', text: 'Repairs and Maintenance - Other Structures' },
                    { value: 'mooe-repair-machinery', text: 'Repairs and Maintenance - Other Machinery' },
                    { value: 'mooe-repair-ict', text: 'Repairs and Maintenance - ICT' },
                    { value: 'mooe-repair-mv', text: 'Repairs and Maintenance - MV' },
                    { value: 'mooe-other', text: 'Other MOOE' }
                ];
                prefix = 'mooe';
            } else {
                // Capital Outlay options list
                optionsList = [
                    { value: 'co-power-supply', text: 'Power Supply' },
                    { value: 'co-building', text: 'Building' },
                    { value: 'co-school-building', text: 'School Building' },
                    { value: 'co-office-equipment', text: 'Office Equipment' },
                    { value: 'co-ict-equipment', text: 'ICT Equipment' },
                    { value: 'co-machinery-equipment', text: 'Other Machinery & Equipment' },
                    { value: 'co-ict-software', text: 'ICT Software' },
                    { value: 'co-motor-vehicle', text: 'Motor Vehicle' },
                    { value: 'co-furniture-fixture', text: 'Furniture & Fixture' },
                    { value: 'co-disaster-response', text: 'Disaster Response & Rescue Equipment' }
                ];
                prefix = 'co';
            }
            
            const uniqueId = `${prefix}-${groupId}-${Date.now()}`;
            
            // Create wrapper div
            const wrapper = document.createElement('div');
            wrapper.className = 'mooe-select-wrapper relative w-full';
            wrapper.setAttribute('data-group-id', groupId);
            wrapper.setAttribute('data-unique-id', uniqueId);
            wrapper.isSearchMode = false;
            
            // Create select element (initial state)
            const selectElement = document.createElement('select');
            selectElement.className = 'mooe-subparticular-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            selectElement.innerHTML = '<option value="">SELECT</option>';
            optionsList.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                selectElement.appendChild(option);
            });
            
            // Store options in wrapper
            wrapper.mooeOptions = optionsList;
            wrapper.selectElement = selectElement;
            
            // Function to transform select to search bar
            function transformToSearchBar() {
                if (wrapper.isSearchMode) return;
                wrapper.isSearchMode = true;
                
                const currentValue = selectElement.value;
                const selectedText = currentValue ? 
                    optionsList.find(opt => opt.value === currentValue)?.text || '' : '';
                
                // Create search container
                const searchContainer = document.createElement('div');
                searchContainer.className = 'flex items-center gap-2';
                
                // Create search input
                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.className = 'mooe-search-input w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
                searchInput.placeholder = 'Search...';
                searchInput.value = selectedText;
                searchInput.setAttribute('data-value', currentValue);
                
                // Create dropdown list - append to body for proper overlay
                const dropdownList = document.createElement('div');
                dropdownList.className = 'mooe-dropdown-overlay bg-white border-2 border-gray-300 rounded-lg shadow-xl';
                dropdownList.style.display = 'none';
                dropdownList.style.position = 'fixed';
                dropdownList.style.zIndex = '9999';
                dropdownList.style.maxHeight = '400px';
                dropdownList.style.overflowY = 'auto';
                dropdownList.style.minHeight = '100px';
                dropdownList.setAttribute('data-unique-id', uniqueId);
                
                // Function to position dropdown
                function positionDropdown() {
                    const rect = searchInput.getBoundingClientRect();
                    dropdownList.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                    dropdownList.style.left = rect.left + 'px';
                    dropdownList.style.width = rect.width + 'px';
                }
                
                // Populate dropdown function
                function populateDropdown(filterText = '') {
                    dropdownList.innerHTML = '';
                    const filtered = wrapper.mooeOptions.filter(opt => 
                        opt.text.toLowerCase().includes(filterText.toLowerCase())
                    );
                    
                    // Add "+ Add New" option at the top
                    const addNewItem = document.createElement('div');
                    addNewItem.className = 'px-4 py-2.5 hover:bg-blue-600 hover:text-white cursor-pointer whitespace-normal break-words border-b border-gray-200';
                    addNewItem.style.minHeight = '40px';
                    addNewItem.style.display = 'flex';
                    addNewItem.style.alignItems = 'center';
                    addNewItem.style.fontWeight = '500';
                    addNewItem.textContent = '+ Add New';
                    addNewItem.setAttribute('data-value', '__add_new__');
                    
                    // Container for add new input (hidden by default)
                    const addNewInputContainer = document.createElement('div');
                    addNewInputContainer.className = 'px-4 py-2 border-b border-gray-200 bg-gray-50';
                    addNewInputContainer.style.display = 'none';
                    
                    const addNewInput = document.createElement('input');
                    addNewInput.type = 'text';
                    addNewInput.className = 'w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-sm';
                    addNewInput.placeholder = 'Enter new sub-particular...';
                    
                    const addNewButtons = document.createElement('div');
                    addNewButtons.className = 'flex gap-2 mt-2';
                    
                    const saveButton = document.createElement('button');
                    saveButton.type = 'button';
                    saveButton.className = 'px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-semibold';
                    saveButton.textContent = 'Save';
                    saveButton.onclick = function(e) {
                        e.stopPropagation();
                        const newValue = addNewInput.value.trim();
                        if (!newValue) {
                            alert('Please enter a value');
                            return;
                        }
                        
                        const safeValue = newValue.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        const exists = wrapper.mooeOptions.some(opt => opt.value === safeValue || opt.text === newValue);
                        if (exists) {
                            alert('This item already exists');
                            return;
                        }
                        
                        // Add to options
                        wrapper.mooeOptions.push({ value: safeValue, text: newValue });
                        // Add to select element if exists
                        const selectEl = wrapper.querySelector('.mooe-subparticular-select');
                        if (selectEl) {
                            const newOption = document.createElement('option');
                            newOption.value = safeValue;
                            newOption.textContent = newValue;
                            selectEl.appendChild(newOption);
                        }
                        
                        // Set value in search input
                        searchInput.value = newValue;
                        searchInput.setAttribute('data-value', safeValue);
                        addNewInput.value = '';
                        addNewInputContainer.style.display = 'none';
                        populateDropdown('');
                    };
                    
                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'px-3 py-1.5 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-semibold';
                    cancelButton.textContent = 'Cancel';
                    cancelButton.onclick = function(e) {
                        e.stopPropagation();
                        addNewInput.value = '';
                        addNewInputContainer.style.display = 'none';
                    };
                    
                    addNewButtons.appendChild(saveButton);
                    addNewButtons.appendChild(cancelButton);
                    addNewInputContainer.appendChild(addNewInput);
                    addNewInputContainer.appendChild(addNewButtons);
                    
                    addNewItem.onclick = function(e) {
                        e.stopPropagation();
                        addNewInputContainer.style.display = addNewInputContainer.style.display === 'none' ? 'block' : 'none';
                        if (addNewInputContainer.style.display === 'block') {
                            addNewInput.focus();
                        }
                    };
                    
                    dropdownList.appendChild(addNewItem);
                    dropdownList.appendChild(addNewInputContainer);
                    
                    // Add filtered options
                    filtered.forEach(option => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2.5 hover:bg-maroon hover:text-white cursor-pointer whitespace-normal break-words';
                        item.style.minHeight = '40px';
                        item.style.display = 'flex';
                        item.style.alignItems = 'center';
                        item.textContent = option.text;
                        item.setAttribute('data-value', option.value);
                        item.onclick = function(e) {
                            e.stopPropagation();
                            searchInput.value = option.text;
                            searchInput.setAttribute('data-value', option.value);
                            dropdownList.style.display = 'none';
                            // Show columns when sub-particular is selected
                            handleMOOESubParticularChange(groupId, option.value);
                        };
                        dropdownList.appendChild(item);
                    });
                }
                
                populateDropdown();
                
                // Append dropdown to body for proper overlay
                document.body.appendChild(dropdownList);
                
                // Event listeners
                searchInput.addEventListener('focus', function() {
                    positionDropdown();
                    dropdownList.style.display = 'block';
                    populateDropdown(this.value);
                });
                
                searchInput.addEventListener('input', function() {
                    positionDropdown();
                    populateDropdown(this.value);
                    dropdownList.style.display = 'block';
                });
                
                searchInput.addEventListener('blur', function(e) {
                    // Delay to allow click events on dropdown items
                    setTimeout(() => {
                        if (!dropdownList.contains(document.activeElement) && document.activeElement !== searchInput) {
                            dropdownList.style.display = 'none';
                        }
                    }, 200);
                });
                
                // Keep dropdown open when clicking inside
                dropdownList.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!wrapper.contains(e.target) && !dropdownList.contains(e.target)) {
                        dropdownList.style.display = 'none';
                    }
                });
                
                // Reposition on scroll/resize
                window.addEventListener('scroll', function() {
                    if (dropdownList.style.display === 'block') {
                        positionDropdown();
                    }
                }, true);
                
                window.addEventListener('resize', function() {
                    if (dropdownList.style.display === 'block') {
                        positionDropdown();
                    }
                });
                
                searchContainer.appendChild(searchInput);
                
                // Replace select with search container
                selectElement.style.display = 'none';
                wrapper.appendChild(searchContainer);
                
                // Store references
                wrapper.searchContainer = searchContainer;
                wrapper.searchInput = searchInput;
                wrapper.dropdownList = dropdownList;
                wrapper.populateDropdown = populateDropdown;
            }
            
            // Transform on click
            selectElement.addEventListener('click', function(e) {
                e.stopPropagation();
                transformToSearchBar();
                if (wrapper.searchInput) {
                    wrapper.searchInput.focus();
                }
            });
            
            wrapper.appendChild(selectElement);
            container.appendChild(wrapper);
        }
        
        // Create Programs select that transforms to search bar on click
        function createProgramsSelectInContainer(container, groupId) {
            // Check fiduciary type to determine options
            const fiduciaryTypeSelect = document.getElementById('fiduciaryType');
            const fiduciaryType = fiduciaryTypeSelect ? fiduciaryTypeSelect.value : '';
            
            // Programs/Fees options list
            let programsOptions;
            if (fiduciaryType === 'fiduciary') {
                // Fiduciary Fees options list
                programsOptions = [
                    { value: 'athletics', text: 'Athletics' },
                    { value: 'library-fee', text: 'Library Fee' },
                    { value: 'laboratory-fee', text: 'Laboratory Fee' },
                    { value: 'nstp', text: 'NSTP' },
                    { value: 'scuaa-fee', text: 'SCUAA Fee' },
                    { value: 'computer-fee', text: 'Computer Fee' },
                    { value: 'internet-fee', text: 'Internet Fee' },
                    { value: 'ccna', text: 'CCNA' },
                    { value: 'cultural', text: 'Cultural' },
                    { value: 'development-fee', text: 'Development Fee' },
                    { value: 'student-activity-fee', text: 'Student Activity Fee' },
                    { value: 'student-council-fee', text: 'Student Council Fee' },
                    { value: 'school-organ-fee', text: 'School Organ Fee' },
                    { value: 'guidance-fee', text: 'Guidance Fee' },
                    { value: 'medical-dental-fee', text: 'Medical Dental Fee' },
                    { value: 'insurance-fee', text: 'Insurance Fee' },
                    { value: 'school-id-fee', text: 'School ID Fee' },
                    { value: 'graduation-fee', text: 'Graduation Fee' },
                    { value: 'handbook', text: 'Handbook' },
                    { value: 'ojt-fee', text: 'OJT Fee' },
                    { value: 'documentary-stamp', text: 'Documentary Stamp' },
                    { value: 'trust-fund', text: 'Trust Fund' },
                    { value: 'other-services-income', text: 'Other Services Income' },
                    { value: 'rent-income', text: 'Rent Income' }
                ];
            } else {
                // Regular Programs options list
                programsOptions = [
                    { value: 'faculty-staff', text: 'Faculty & Staff Development' },
                    { value: 'curriculum', text: 'Curriculum Development' },
                    { value: 'student', text: 'Student Development' },
                    { value: 'facilities', text: 'Facilities Development' },
                    { value: 'research', text: 'Research' },
                    { value: 'extension', text: 'Extension' },
                    { value: 'production', text: 'Production' },
                    { value: 'admin', text: 'Admin' },
                    { value: 'petition', text: 'Petition' }
                ];
            }
            
            const uniqueId = `programs-${groupId}-${Date.now()}`;
            
            // Create wrapper div
            const wrapper = document.createElement('div');
            wrapper.className = 'programs-select-wrapper relative w-full';
            wrapper.setAttribute('data-group-id', groupId);
            wrapper.setAttribute('data-unique-id', uniqueId);
            wrapper.isSearchMode = false;
            
            // Create select element (initial state)
            const selectElement = document.createElement('select');
            selectElement.className = 'programs-select-element w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            selectElement.innerHTML = '<option value="">SELECT</option>';
            programsOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                selectElement.appendChild(option);
            });
            
            // Store options in wrapper
            wrapper.programsOptions = programsOptions;
            wrapper.selectElement = selectElement;
            
            // Function to transform select to search bar
            function transformToSearchBar() {
                if (wrapper.isSearchMode) return;
                wrapper.isSearchMode = true;
                
                const currentValue = selectElement.value;
                const selectedText = currentValue ? 
                    programsOptions.find(opt => opt.value === currentValue)?.text || '' : '';
                
                // Create search container
                const searchContainer = document.createElement('div');
                searchContainer.className = 'flex items-center gap-2';
                
                // Create search input
                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.className = 'programs-search-input w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
                searchInput.placeholder = 'Search...';
                searchInput.value = selectedText;
                searchInput.setAttribute('data-value', currentValue);
                
                // Create dropdown list - append to body for proper overlay
                const dropdownList = document.createElement('div');
                dropdownList.className = 'programs-dropdown-overlay bg-white border-2 border-gray-300 rounded-lg shadow-xl';
                dropdownList.style.display = 'none';
                dropdownList.style.position = 'fixed';
                dropdownList.style.zIndex = '9999';
                dropdownList.style.maxHeight = '400px';
                dropdownList.style.overflowY = 'auto';
                dropdownList.style.minHeight = '100px';
                dropdownList.setAttribute('data-unique-id', uniqueId);
                
                // Function to position dropdown
                function positionDropdown() {
                    const rect = searchInput.getBoundingClientRect();
                    dropdownList.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                    dropdownList.style.left = rect.left + 'px';
                    dropdownList.style.width = rect.width + 'px';
                }
                
                // Populate dropdown function
                function populateDropdown(filterText = '') {
                    dropdownList.innerHTML = '';
                    const filtered = wrapper.programsOptions.filter(opt => 
                        opt.text.toLowerCase().includes(filterText.toLowerCase())
                    );
                    
                    // Add "+ Add New" option at the top
                    const addNewItem = document.createElement('div');
                    addNewItem.className = 'px-4 py-2.5 hover:bg-blue-600 hover:text-white cursor-pointer whitespace-normal break-words border-b border-gray-200';
                    addNewItem.style.minHeight = '40px';
                    addNewItem.style.display = 'flex';
                    addNewItem.style.alignItems = 'center';
                    addNewItem.style.fontWeight = '500';
                    addNewItem.textContent = '+ Add New';
                    addNewItem.setAttribute('data-value', '__add_new__');
                    
                    // Container for add new input (hidden by default)
                    const addNewInputContainer = document.createElement('div');
                    addNewInputContainer.className = 'px-4 py-2 border-b border-gray-200 bg-gray-50';
                    addNewInputContainer.style.display = 'none';
                    
                    const addNewInput = document.createElement('input');
                    addNewInput.type = 'text';
                    addNewInput.className = 'w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-sm';
                    addNewInput.placeholder = 'Enter new program...';
                    
                    const addNewButtons = document.createElement('div');
                    addNewButtons.className = 'flex gap-2 mt-2';
                    
                    const saveButton = document.createElement('button');
                    saveButton.type = 'button';
                    saveButton.className = 'px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-semibold';
                    saveButton.textContent = 'Save';
                    saveButton.onclick = function(e) {
                        e.stopPropagation();
                        const newValue = addNewInput.value.trim();
                        if (!newValue) {
                            alert('Please enter a value');
                            return;
                        }
                        
                        const safeValue = newValue.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        const exists = wrapper.programsOptions.some(opt => opt.value === safeValue || opt.text === newValue);
                        if (exists) {
                            alert('This item already exists');
                            return;
                        }
                        
                        // Add to options
                        wrapper.programsOptions.push({ value: safeValue, text: newValue });
                        // Add to select element if exists
                        const selectEl = wrapper.querySelector('.programs-select-element');
                        if (selectEl) {
                            const newOption = document.createElement('option');
                            newOption.value = safeValue;
                            newOption.textContent = newValue;
                            selectEl.appendChild(newOption);
                        }
                        
                        // Add to all other programs wrappers
                        const allProgramsWrappers = document.querySelectorAll('.programs-select-wrapper');
                        allProgramsWrappers.forEach(otherWrapper => {
                            if (otherWrapper !== wrapper && otherWrapper.programsOptions) {
                                const existsOther = otherWrapper.programsOptions.some(opt => opt.value === safeValue || opt.text === newValue);
                                if (!existsOther) {
                                    otherWrapper.programsOptions.push({ value: safeValue, text: newValue });
                                    const selectElOther = otherWrapper.querySelector('.programs-select-element');
                                    if (selectElOther) {
                                        const newOptionOther = document.createElement('option');
                                        newOptionOther.value = safeValue;
                                        newOptionOther.textContent = newValue;
                                        selectElOther.appendChild(newOptionOther);
                                    }
                                    if (otherWrapper.populateDropdown) {
                                        otherWrapper.populateDropdown(otherWrapper.searchInput ? otherWrapper.searchInput.value : '');
                                    }
                                }
                            }
                        });
                        
                        // Set value in search input
                        searchInput.value = newValue;
                        searchInput.setAttribute('data-value', safeValue);
                        addNewInput.value = '';
                        addNewInputContainer.style.display = 'none';
                        populateDropdown('');
                    };
                    
                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'px-3 py-1.5 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-semibold';
                    cancelButton.textContent = 'Cancel';
                    cancelButton.onclick = function(e) {
                        e.stopPropagation();
                        addNewInput.value = '';
                        addNewInputContainer.style.display = 'none';
                    };
                    
                    addNewButtons.appendChild(saveButton);
                    addNewButtons.appendChild(cancelButton);
                    addNewInputContainer.appendChild(addNewInput);
                    addNewInputContainer.appendChild(addNewButtons);
                    
                    addNewItem.onclick = function(e) {
                        e.stopPropagation();
                        addNewInputContainer.style.display = addNewInputContainer.style.display === 'none' ? 'block' : 'none';
                        if (addNewInputContainer.style.display === 'block') {
                            addNewInput.focus();
                        }
                    };
                    
                    dropdownList.appendChild(addNewItem);
                    dropdownList.appendChild(addNewInputContainer);
                    
                    // Add filtered options
                    filtered.forEach(option => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2.5 hover:bg-maroon hover:text-white cursor-pointer whitespace-normal break-words';
                        item.style.minHeight = '40px';
                        item.style.display = 'flex';
                        item.style.alignItems = 'center';
                        item.textContent = option.text;
                        item.setAttribute('data-value', option.value);
                        item.onclick = function(e) {
                            e.stopPropagation();
                            searchInput.value = option.text;
                            searchInput.setAttribute('data-value', option.value);
                            dropdownList.style.display = 'none';
                        };
                        dropdownList.appendChild(item);
                    });
                }
                
                populateDropdown();
                
                // Append dropdown to body for proper overlay
                document.body.appendChild(dropdownList);
                
                // Event listeners
                searchInput.addEventListener('focus', function() {
                    positionDropdown();
                    dropdownList.style.display = 'block';
                    populateDropdown(this.value);
                });
                
                searchInput.addEventListener('input', function() {
                    positionDropdown();
                    populateDropdown(this.value);
                    dropdownList.style.display = 'block';
                });
                
                searchInput.addEventListener('blur', function(e) {
                    // Delay to allow click events on dropdown items
                    setTimeout(() => {
                        if (!dropdownList.contains(document.activeElement) && document.activeElement !== searchInput) {
                            dropdownList.style.display = 'none';
                        }
                    }, 200);
                });
                
                // Keep dropdown open when clicking inside
                dropdownList.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!wrapper.contains(e.target) && !dropdownList.contains(e.target)) {
                        dropdownList.style.display = 'none';
                    }
                });
                
                // Reposition on scroll/resize
                window.addEventListener('scroll', function() {
                    if (dropdownList.style.display === 'block') {
                        positionDropdown();
                    }
                }, true);
                
                window.addEventListener('resize', function() {
                    if (dropdownList.style.display === 'block') {
                        positionDropdown();
                    }
                });
                
                searchContainer.appendChild(searchInput);
                
                // Replace select with search container
                selectElement.style.display = 'none';
                wrapper.appendChild(searchContainer);
                
                // Store references
                wrapper.searchContainer = searchContainer;
                wrapper.searchInput = searchInput;
                wrapper.dropdownList = dropdownList;
                wrapper.populateDropdown = populateDropdown;
            }
            
            // Transform on click
            selectElement.addEventListener('click', function(e) {
                e.stopPropagation();
                transformToSearchBar();
                if (wrapper.searchInput) {
                    wrapper.searchInput.focus();
                }
            });
            
            wrapper.appendChild(selectElement);
            container.appendChild(wrapper);
        }
        
        // Create Honoraria select that transforms to search bar on click
        function createHonorariaSelectInContainer(container, groupId) {
            // Honoraria options list
            const honorariaOptions = [
                { value: 'honoraria', text: 'Honoraria' },
                { value: 'honoraria-part-time', text: 'Honoraria - Part Time' },
                { value: 'honoraria-overload', text: 'Honoraria - Overload' }
            ];
            
            const uniqueId = `honoraria-${groupId}-${Date.now()}`;
            
            // Create wrapper div
            const wrapper = document.createElement('div');
            wrapper.className = 'honoraria-select-wrapper relative w-full';
            wrapper.setAttribute('data-group-id', groupId);
            wrapper.setAttribute('data-unique-id', uniqueId);
            wrapper.isSearchMode = false;
            
            // Create select element (initial state)
            const selectElement = document.createElement('select');
            selectElement.className = 'honoraria-select-element w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
            selectElement.innerHTML = '<option value="">SELECT</option>';
            honorariaOptions.forEach(opt => {
                const option = document.createElement('option');
                option.value = opt.value;
                option.textContent = opt.text;
                selectElement.appendChild(option);
            });
            
            // Store options in wrapper
            wrapper.honorariaOptions = honorariaOptions;
            wrapper.selectElement = selectElement;
            
            // Function to transform select to search bar
            function transformToSearchBar() {
                if (wrapper.isSearchMode) return;
                wrapper.isSearchMode = true;
                
                const currentValue = selectElement.value;
                const selectedText = currentValue ? 
                    honorariaOptions.find(opt => opt.value === currentValue)?.text || '' : '';
                
                // Create search container
                const searchContainer = document.createElement('div');
                searchContainer.className = 'flex items-center gap-2';
                
                // Create search input
                const searchInput = document.createElement('input');
                searchInput.type = 'text';
                searchInput.className = 'honoraria-search-input w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
                searchInput.placeholder = 'Search...';
                searchInput.value = selectedText;
                searchInput.setAttribute('data-value', currentValue);
                
                // Create dropdown list - append to body for proper overlay
                const dropdownList = document.createElement('div');
                dropdownList.className = 'honoraria-dropdown-overlay bg-white border-2 border-gray-300 rounded-lg shadow-xl';
                dropdownList.style.display = 'none';
                dropdownList.style.position = 'fixed';
                dropdownList.style.zIndex = '9999';
                dropdownList.style.maxHeight = '400px';
                dropdownList.style.overflowY = 'auto';
                dropdownList.style.minHeight = '100px';
                dropdownList.setAttribute('data-unique-id', uniqueId);
                
                // Function to position dropdown
                function positionDropdown() {
                    const rect = searchInput.getBoundingClientRect();
                    dropdownList.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                    dropdownList.style.left = rect.left + 'px';
                    dropdownList.style.width = rect.width + 'px';
                }
                
                // Populate dropdown function
                function populateDropdown(filterText = '') {
                    dropdownList.innerHTML = '';
                    const filtered = wrapper.honorariaOptions.filter(opt => 
                        opt.text.toLowerCase().includes(filterText.toLowerCase())
                    );
                    
                    // Add "+ Add New" option at the top
                    const addNewItem = document.createElement('div');
                    addNewItem.className = 'px-4 py-2.5 hover:bg-blue-600 hover:text-white cursor-pointer whitespace-normal break-words border-b border-gray-200';
                    addNewItem.style.minHeight = '40px';
                    addNewItem.style.display = 'flex';
                    addNewItem.style.alignItems = 'center';
                    addNewItem.style.fontWeight = '500';
                    addNewItem.textContent = '+ Add New';
                    addNewItem.setAttribute('data-value', '__add_new__');
                    
                    // Container for add new input (hidden by default)
                    const addNewInputContainer = document.createElement('div');
                    addNewInputContainer.className = 'px-4 py-2 border-b border-gray-200 bg-gray-50';
                    addNewInputContainer.style.display = 'none';
                    
                    const addNewInput = document.createElement('input');
                    addNewInput.type = 'text';
                    addNewInput.className = 'w-full px-3 py-2 border border-gray-300 rounded focus:ring-1 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900 text-sm';
                    addNewInput.placeholder = 'Enter new honoraria...';
                    
                    const addNewButtons = document.createElement('div');
                    addNewButtons.className = 'flex gap-2 mt-2';
                    
                    const saveButton = document.createElement('button');
                    saveButton.type = 'button';
                    saveButton.className = 'px-3 py-1.5 bg-green-600 text-white rounded hover:bg-green-700 text-sm font-semibold';
                    saveButton.textContent = 'Save';
                    saveButton.onclick = function(e) {
                        e.stopPropagation();
                        const newValue = addNewInput.value.trim();
                        if (!newValue) {
                            alert('Please enter a value');
                            return;
                        }
                        
                        const safeValue = newValue.toLowerCase().replace(/\s+/g, '-').replace(/[^a-z0-9-]/g, '');
                        const exists = wrapper.honorariaOptions.some(opt => opt.value === safeValue || opt.text === newValue);
                        if (exists) {
                            alert('This item already exists');
                            return;
                        }
                        
                        // Add to options
                        wrapper.honorariaOptions.push({ value: safeValue, text: newValue });
                        // Add to select element if exists
                        const selectEl = wrapper.querySelector('.honoraria-select-element');
                        if (selectEl) {
                            const newOption = document.createElement('option');
                            newOption.value = safeValue;
                            newOption.textContent = newValue;
                            selectEl.appendChild(newOption);
                        }
                        
                        // Add to all other honoraria wrappers
                        const allHonorariaWrappers = document.querySelectorAll('.honoraria-select-wrapper');
                        allHonorariaWrappers.forEach(otherWrapper => {
                            if (otherWrapper !== wrapper && otherWrapper.honorariaOptions) {
                                const existsOther = otherWrapper.honorariaOptions.some(opt => opt.value === safeValue || opt.text === newValue);
                                if (!existsOther) {
                                    otherWrapper.honorariaOptions.push({ value: safeValue, text: newValue });
                                    const selectElOther = otherWrapper.querySelector('.honoraria-select-element');
                                    if (selectElOther) {
                                        const newOptionOther = document.createElement('option');
                                        newOptionOther.value = safeValue;
                                        newOptionOther.textContent = newValue;
                                        selectElOther.appendChild(newOptionOther);
                                    }
                                    if (otherWrapper.populateDropdown) {
                                        otherWrapper.populateDropdown(otherWrapper.searchInput ? otherWrapper.searchInput.value : '');
                                    }
                                }
                            }
                        });
                        
                        // Set value in search input
                        searchInput.value = newValue;
                        searchInput.setAttribute('data-value', safeValue);
                        addNewInput.value = '';
                        addNewInputContainer.style.display = 'none';
                        populateDropdown('');
                    };
                    
                    const cancelButton = document.createElement('button');
                    cancelButton.type = 'button';
                    cancelButton.className = 'px-3 py-1.5 bg-gray-500 text-white rounded hover:bg-gray-600 text-sm font-semibold';
                    cancelButton.textContent = 'Cancel';
                    cancelButton.onclick = function(e) {
                        e.stopPropagation();
                        addNewInput.value = '';
                        addNewInputContainer.style.display = 'none';
                    };
                    
                    addNewButtons.appendChild(saveButton);
                    addNewButtons.appendChild(cancelButton);
                    addNewInputContainer.appendChild(addNewInput);
                    addNewInputContainer.appendChild(addNewButtons);
                    
                    addNewItem.onclick = function(e) {
                        e.stopPropagation();
                        addNewInputContainer.style.display = addNewInputContainer.style.display === 'none' ? 'block' : 'none';
                        if (addNewInputContainer.style.display === 'block') {
                            addNewInput.focus();
                        }
                    };
                    
                    dropdownList.appendChild(addNewItem);
                    dropdownList.appendChild(addNewInputContainer);
                    
                    // Add filtered options
                    filtered.forEach(option => {
                        const item = document.createElement('div');
                        item.className = 'px-4 py-2.5 hover:bg-maroon hover:text-white cursor-pointer whitespace-normal break-words';
                        item.style.minHeight = '40px';
                        item.style.display = 'flex';
                        item.style.alignItems = 'center';
                        item.textContent = option.text;
                        item.setAttribute('data-value', option.value);
                        item.onclick = function(e) {
                            e.stopPropagation();
                            searchInput.value = option.text;
                            searchInput.setAttribute('data-value', option.value);
                            dropdownList.style.display = 'none';
                            // Trigger honoraria change handler
                            handleHonorariaChange(searchInput, groupId);
                        };
                        dropdownList.appendChild(item);
                    });
                }
                
                populateDropdown();
                
                // Append dropdown to body for proper overlay
                document.body.appendChild(dropdownList);
                
                // Event listeners
                searchInput.addEventListener('focus', function() {
                    positionDropdown();
                    dropdownList.style.display = 'block';
                    populateDropdown(this.value);
                });
                
                searchInput.addEventListener('input', function() {
                    positionDropdown();
                    populateDropdown(this.value);
                    dropdownList.style.display = 'block';
                });
                
                searchInput.addEventListener('blur', function(e) {
                    // Delay to allow click events on dropdown items
                    setTimeout(() => {
                        if (!dropdownList.contains(document.activeElement) && document.activeElement !== searchInput) {
                            dropdownList.style.display = 'none';
                        }
                    }, 200);
                });
                
                // Keep dropdown open when clicking inside
                dropdownList.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!wrapper.contains(e.target) && !dropdownList.contains(e.target)) {
                        dropdownList.style.display = 'none';
                    }
                });
                
                // Reposition on scroll/resize
                window.addEventListener('scroll', function() {
                    if (dropdownList.style.display === 'block') {
                        positionDropdown();
                    }
                }, true);
                
                window.addEventListener('resize', function() {
                    if (dropdownList.style.display === 'block') {
                        positionDropdown();
                    }
                });
                
                searchContainer.appendChild(searchInput);
                
                // Replace select with search container
                selectElement.style.display = 'none';
                wrapper.appendChild(searchContainer);
                
                // Store references
                wrapper.searchContainer = searchContainer;
                wrapper.searchInput = searchInput;
                wrapper.dropdownList = dropdownList;
                wrapper.populateDropdown = populateDropdown;
            }
            
            // Transform on click
            selectElement.addEventListener('click', function(e) {
                e.stopPropagation();
                transformToSearchBar();
                if (wrapper.searchInput) {
                    wrapper.searchInput.focus();
                }
            });
            
            wrapper.appendChild(selectElement);
            container.appendChild(wrapper);
        }
        
        // Create honoraria select
        function createHonorariaSelect(groupId) {
            const honorariaContainer = document.querySelector(`#honoraria-select-container-${groupId}`);
            if (!honorariaContainer) return;
            
            // Check if honoraria select already exists
            let honorariaSelect = honorariaContainer.querySelector('.honoraria-select');
            if (honorariaSelect) {
                return honorariaSelect; // Return existing select
            }
            
            if (templateHonorariaHTML) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = templateHonorariaHTML;
                honorariaSelect = tempDiv.firstElementChild;
            } else {
                honorariaSelect = document.createElement('select');
                honorariaSelect.className = 'honoraria-select w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon outline-none bg-white text-gray-900';
                honorariaSelect.innerHTML = `
                    <option value="">SELECT</option>
                    <option value="honoraria">Honoraria</option>
                    <option value="honoraria-part-time">Honoraria - Part Time</option>
                    <option value="honoraria-overload">Honoraria- Overload</option>
                    <option value="__add_new__">+ Add New</option>
                `;
            }
            
            // Add event listener for honoraria selection
            honorariaSelect.addEventListener('change', function() {
                handleHonorariaChange(this, groupId);
                handleAddNewSelect(this, 'honoraria');
            });
            
            honorariaSelect.classList.add('w-full');
            honorariaContainer.appendChild(honorariaSelect);
            
            return honorariaSelect;
        }
        
        // Handle MOOE sub-particular selection change
        function handleMOOESubParticularChange(groupId, subParticularValue) {
            if (!subParticularValue || subParticularValue === '' || subParticularValue === '__add_new__') {
                return;
            }
            
            // Find the row that contains the MOOE select wrapper with this groupId
            const mooeWrapper = document.querySelector(`.mooe-select-wrapper[data-group-id="${groupId}"]`);
            if (!mooeWrapper) return;
            
            // Find the container to determine if it's main group row or sub-particular row
            const container = mooeWrapper.closest('[id*="subparticular-select-container"]');
            if (!container) return;
            
            const containerId = container.id;
            let rowToUse = container.closest('tr');
            let cellIdSuffix = '';
            let baseGroupId = groupId;
            
            // Check if it's a sub-particular row (container ID has format: subparticular-select-container-${groupId}-${uniqueId})
            if (rowToUse && rowToUse.classList.contains('subparticular-row')) {
                // Extract uniqueId from container ID (last part after last dash)
                // Container ID format: subparticular-select-container-${baseGroupId}-${uniqueId}
                // Example: subparticular-select-container-0-1234567890
                const prefix = 'subparticular-select-container-';
                if (containerId.startsWith(prefix)) {
                    const afterPrefix = containerId.substring(prefix.length);
                    const lastDashIndex = afterPrefix.lastIndexOf('-');
                    if (lastDashIndex > 0) {
                        baseGroupId = afterPrefix.substring(0, lastDashIndex);
                        const uniqueId = afterPrefix.substring(lastDashIndex + 1);
                        cellIdSuffix = `-${uniqueId}`;
                    }
                }
            } else {
                // This is the main group row - no suffix needed
                rowToUse = container.closest('tr.particular-group');
            }
            
            if (!rowToUse) return;
            
            // For sub-particular rows, use baseGroupId-${uniqueId} format for cell IDs
            // For main rows, use just baseGroupId (which equals groupId)
            const cellIdPrefix = rowToUse.classList.contains('subparticular-row') ? `${baseGroupId}${cellIdSuffix}` : baseGroupId;
            
            const programsCell = rowToUse.querySelector(`#programs-cell-${cellIdPrefix}`);
            const programContainer = rowToUse.querySelector(`#program-select-container-${cellIdPrefix}`);
            const approvedBudgetCell = rowToUse.querySelector(`#approved-budget-cell-${cellIdPrefix}`);
            const allotmentCell = rowToUse.querySelector(`#allotment-cell-${cellIdPrefix}`);
            const balanceCell = rowToUse.querySelector(`#balance-cell-${cellIdPrefix}`);
            const actionCell = rowToUse.querySelector(`#action-cell-${cellIdPrefix}`);
            
            // Show PROGRAMS column
            if (programsCell) {
                programsCell.style.display = 'table-cell';
                
                // Create and show programs dropdown
                if (programContainer && !programContainer.querySelector('.programs-select-wrapper')) {
                    createProgramsSelectInContainer(programContainer, baseGroupId);
                }
            }
            
            // Show all budget/balance/action columns
            if (approvedBudgetCell) approvedBudgetCell.style.display = 'table-cell';
            if (allotmentCell) allotmentCell.style.display = 'table-cell';
            if (balanceCell) balanceCell.style.display = 'table-cell';
            if (actionCell) actionCell.style.display = 'table-cell';
        }
        
        // Handle honoraria selection change
        function handleHonorariaChange(selectOrInput, groupId) {
            // Handle both select element and search input
            let honorariaValue = '';
            if (selectOrInput.tagName === 'SELECT') {
                honorariaValue = selectOrInput.value;
            } else if (selectOrInput.tagName === 'INPUT') {
                honorariaValue = selectOrInput.getAttribute('data-value') || '';
            }
            
            // Find the container to determine if it's main group row or sub-particular row
            let container;
            if (selectOrInput.tagName === 'SELECT') {
                container = selectOrInput.closest('[id*="subparticular-select-container"], [id*="honoraria-select-container"]');
            } else {
                const wrapper = selectOrInput.closest('.honoraria-select-wrapper');
                container = wrapper ? wrapper.closest('[id*="subparticular-select-container"], [id*="honoraria-select-container"]') : null;
            }
            if (!container) return;
            
            const containerId = container.id;
            let rowToUse = container.closest('tr');
            let cellIdSuffix = '';
            let baseGroupId = groupId;
            
            // Check if it's a sub-particular row (container ID has format: subparticular-select-container-${groupId}-${uniqueId})
            if (rowToUse && rowToUse.classList.contains('subparticular-row')) {
                // Extract uniqueId from container ID (last part after last dash)
                // Container ID format: subparticular-select-container-${baseGroupId}-${uniqueId}
                // Example: subparticular-select-container-0-1234567890
                const prefix = 'subparticular-select-container-';
                if (containerId.startsWith(prefix)) {
                    const afterPrefix = containerId.substring(prefix.length);
                    const lastDashIndex = afterPrefix.lastIndexOf('-');
                    if (lastDashIndex > 0) {
                        baseGroupId = afterPrefix.substring(0, lastDashIndex);
                        const uniqueId = afterPrefix.substring(lastDashIndex + 1);
                        cellIdSuffix = `-${uniqueId}`;
                    }
                }
            } else {
                // This is the main group row - find it by data-group-id
                rowToUse = container.closest('tr.particular-group');
                if (!rowToUse) {
                    // Try to find by data-group-id if it's an honoraria row
                    const honorariaRow = document.querySelector(`tr.honoraria-row[data-group-id="${groupId}"]`);
                    if (honorariaRow) {
                        rowToUse = honorariaRow;
                    }
                }
            }
            
            if (!rowToUse) return;
            
            // For sub-particular rows, use baseGroupId-${uniqueId} format for cell IDs
            // For main rows, use just baseGroupId (which equals groupId)
            const cellIdPrefix = rowToUse.classList.contains('subparticular-row') ? `${baseGroupId}${cellIdSuffix}` : baseGroupId;
            
            const programsCell = rowToUse.querySelector(`#programs-cell-${cellIdPrefix}`);
            const programContainer = rowToUse.querySelector(`#program-select-container-${cellIdPrefix}`);
            const approvedBudgetCell = rowToUse.querySelector(`#approved-budget-cell-${cellIdPrefix}`);
            const allotmentCell = rowToUse.querySelector(`#allotment-cell-${cellIdPrefix}`);
            const balanceCell = rowToUse.querySelector(`#balance-cell-${cellIdPrefix}`);
            const actionCell = rowToUse.querySelector(`#action-cell-${cellIdPrefix}`);
            
            if (honorariaValue && honorariaValue !== '' && honorariaValue !== '__add_new__') {
                // Show PROGRAMS column when honoraria is selected
                if (programsCell) {
                    programsCell.style.display = 'table-cell';
                    
                    // Create and show programs dropdown
                    if (programContainer) {
                        // Check if programs select wrapper already exists
                        if (!programContainer.querySelector('.programs-select-wrapper')) {
                            createProgramsSelectInContainer(programContainer, cellIdPrefix);
                        }
                    }
                }
                
                // Show all budget/balance/action columns when honoraria is selected
                if (approvedBudgetCell) approvedBudgetCell.style.display = 'table-cell';
                if (allotmentCell) allotmentCell.style.display = 'table-cell';
                if (balanceCell) balanceCell.style.display = 'table-cell';
                if (actionCell) actionCell.style.display = 'table-cell';
            } else {
                // Hide everything if honoraria is deselected
                if (programsCell) programsCell.style.display = 'none';
                if (approvedBudgetCell) approvedBudgetCell.style.display = 'none';
                if (allotmentCell) allotmentCell.style.display = 'none';
                if (balanceCell) balanceCell.style.display = 'none';
                if (actionCell) actionCell.style.display = 'none';
                
                if (programContainer) {
                    const programsWrapper = programContainer.querySelector('.programs-select-wrapper');
                    if (programsWrapper) {
                        const searchInput = programsWrapper.querySelector('.programs-search-input');
                        if (searchInput) {
                            searchInput.value = '';
                            searchInput.setAttribute('data-value', '');
                        }
                    }
                }
            }
        }

        // Remove program row
        function removeProgramRow(button) {
            if (confirm('Are you sure you want to remove this row?')) {
                const row = button.closest('tr');
                const groupId = row.getAttribute('data-group-id');
                const rowId = row.rowIndex;
                
                // Remove from budgetRows array
                delete budgetRows[rowId];
                row.remove();
                
                // Update rowspan if this was part of a group
                if (groupId) {
                    updateParticularRowspan(groupId);
                }
            }
        }

        // Remove particular group (removes all rows in the group)
        function removeParticularGroup(button) {
            if (confirm('Are you sure you want to remove this entire particular group and all its program rows?')) {
                const groupRow = button.closest('tr');
                const groupId = groupRow.getAttribute('data-group-id');
                
                if (groupId) {
                    // Remove all rows in this group
                    const allGroupRows = document.querySelectorAll(`tr[data-group-id="${groupId}"]`);
                    allGroupRows.forEach(row => {
                        const rowId = row.rowIndex;
                        delete budgetRows[rowId];
                        row.remove();
                    });
                } else {
                    // Fallback: just remove the current row
                    const rowId = groupRow.rowIndex;
                    delete budgetRows[rowId];
                    groupRow.remove();
                }
            }
        }

        // Legacy function for backward compatibility (now calls addSimpleProgramRow)
        function addBudgetRow() {
            addSimpleProgramRow();
        }

        // Format currency with peso sign and commas
        function formatCurrency(value) {
            if (!value && value !== 0) return '₱ 0.00';
            const numValue = typeof value === 'string' ? parseFloat(value.replace(/[₱, ]/g, '')) : value;
            if (isNaN(numValue)) return '₱ 0.00';
            return '₱ ' + numValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }

        // Parse currency value (remove peso sign and commas)
        function parseCurrency(value) {
            if (!value) return 0;
            const numValue = typeof value === 'string' ? parseFloat(value.replace(/[₱, ]/g, '')) : value;
            return isNaN(numValue) ? 0 : numValue;
        }

        // Format input on blur (when user leaves the field)
        function formatCurrencyInput(input) {
            const value = parseCurrency(input.value);
            input.value = formatCurrency(value);
        }

        // Calculate balance
        function calculateBalance(input) {
            const row = input.closest('tr');
            const approvedBudgetInput = row.querySelector('.approved-budget');
            const availableAmountInput = row.querySelector('.available-amount');
            const balanceInput = row.querySelector('.balance-amount');
            
            if (!approvedBudgetInput || !availableAmountInput || !balanceInput) return;
            
            const approvedBudget = parseCurrency(approvedBudgetInput.value);
            const availableAmount = parseCurrency(availableAmountInput.value);
            const balance = approvedBudget - availableAmount;
            
            // Format all values
            approvedBudgetInput.value = formatCurrency(approvedBudget);
            availableAmountInput.value = formatCurrency(availableAmount);
            balanceInput.value = formatCurrency(balance);
        }
        
        // Auto calculate balance as user types
        function autoCalculateBalance(input) {
            const row = input.closest('tr');
            const approvedBudgetInput = row.querySelector('.approved-budget');
            const availableAmountInput = row.querySelector('.available-amount');
            const balanceInput = row.querySelector('.balance-amount');
            
            if (!approvedBudgetInput || !availableAmountInput || !balanceInput) return;
            
            // Get raw values (without formatting)
            let approvedBudget = parseCurrency(approvedBudgetInput.value);
            let availableAmount = parseCurrency(availableAmountInput.value);
            
            // Calculate balance
            const balance = approvedBudget - availableAmount;
            
            // Update balance field
            balanceInput.value = formatCurrency(balance);
            
            // Update balance color based on value
            updateBalanceColor(balanceInput, balance);
            
            // Trigger auto-save with debounce
            debouncedAutoSave();
        }
        
        // Debounce timer for auto-save
        let autoSaveTimer = null;
        
        // Debounced auto-save function
        function debouncedAutoSave() {
            if (autoSaveTimer) clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                autoSaveCabacEntries();
            }, 1000); // Save 1 second after last change
        }
        
        // Auto-save CABAC entries to database (silent, no alerts)
        async function autoSaveCabacEntries() {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) return;
            
            // Get selected program from dropdown
            const fiduciaryText = document.getElementById('fiduciarySelectedText');
            const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
            
            let selectedProgram = null;
            let programType = null;
            
            // Check which dropdown has a selected value (not placeholder text)
            if (nonFiduciaryText && nonFiduciaryText.textContent.trim() !== 'Select program...' && !nonFiduciaryText.classList.contains('text-gray-500')) {
                selectedProgram = nonFiduciaryText.textContent.trim();
                programType = 'non-fiduciary';
            } else if (fiduciaryText && fiduciaryText.textContent.trim() !== 'Select program...' && !fiduciaryText.classList.contains('text-gray-500')) {
                selectedProgram = fiduciaryText.textContent.trim();
                programType = 'fiduciary';
            }
            
            if (!selectedProgram) return; // No program selected, skip auto-save
            
            try {
                const programsResponse = await fetch('../api/cabac_programs.php?action=get_programs&type=' + programType);
                const programsData = await programsResponse.json();
                
                if (!programsData.success) return;
                
                const program = programsData.programs.find(p => p.program_name === selectedProgram);
                if (!program) return;
                
                const programId = program.id;
                
                // Collect all entries from table rows
                const rows = tbody.querySelectorAll('tr.program-row');
                const entries = [];
                
                rows.forEach(row => {
                    const programNameInput = row.querySelector('td:first-child input[type="text"]');
                    const approvedBudgetInput = row.querySelector('.approved-budget');
                    const availableAllotmentInput = row.querySelector('.available-amount');
                    
                    const programName = programNameInput ? programNameInput.value.trim() : '';
                    const approvedBudget = approvedBudgetInput ? parseCurrency(approvedBudgetInput.value) : 0;
                    const availableAllotment = availableAllotmentInput ? parseCurrency(availableAllotmentInput.value) : 0;
                    
                    // Only add if there's some data
                    if (programName || approvedBudget > 0 || availableAllotment > 0) {
                        entries.push({
                            program_name: programName,
                            approved_budget: approvedBudget,
                            available_allotment: availableAllotment
                        });
                    }
                });
                
                if (entries.length === 0) return;
                
                // Save to database silently (no notifications - that's for the Save button)
                const saveResponse = await fetch('../api/cabac_programs.php?action=auto_save_entries', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        program_id: programId,
                        entries: entries
                    })
                });
                
                const saveData = await saveResponse.json();
                
                if (saveData.success) {
                    console.log('CABAC entries auto-saved successfully');
                    // Update row data-entry-id attributes with new IDs from database
                    if (saveData.entry_ids && saveData.entry_ids.length > 0) {
                        const rows = tbody.querySelectorAll('tr.program-row');
                        rows.forEach((row, index) => {
                            if (saveData.entry_ids[index]) {
                                row.setAttribute('data-entry-id', saveData.entry_ids[index]);
                            }
                        });
                    }
                }
            } catch (error) {
                console.error('Auto-save error:', error);
            }
        }
        
        // Update balance field color based on value
        function updateBalanceColor(balanceInput, balance) {
            // Remove existing color classes
            balanceInput.classList.remove('bg-green-50', 'text-green-700', 'bg-red-50', 'text-red-700');
            
            if (balance >= 0) {
                // Positive or zero balance - green
                balanceInput.classList.add('bg-green-50', 'text-green-700');
            } else {
                // Negative balance - red
                balanceInput.classList.add('bg-red-50', 'text-red-700');
            }
        }
        
        // Toggle amount menu dropdown
        function toggleAmountMenu(button) {
            const menu = button.nextElementSibling;
            if (!menu) return;
            
            // Close all other menus first
            document.querySelectorAll('.amount-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            
            // Toggle current menu
            menu.classList.toggle('hidden');
        }
        
        // Close menus when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.relative')) {
                document.querySelectorAll('.amount-menu').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });
        
        // Add amount to balance when the "Add Amount" option is clicked
        function addAmountToBalance(button) {
            // Close the menu
            const menu = button.closest('.amount-menu');
            if (menu) menu.classList.add('hidden');
            
            const row = button.closest('tr');
            if (!row) return;

            const availableAmountInput = row.querySelector('.available-amount');
            if (!availableAmountInput) return;

            currentAvailableAllotmentInput = availableAmountInput;
            currentOperationType = 'add'; // Set operation type
            
            const modal = document.getElementById('inputAmountModal');
            const input = document.getElementById('amountInput');
            const modalTitle = modal ? modal.querySelector('h3') : null;
            
            if (!modal || !input) return;

            if (modalTitle) modalTitle.textContent = 'Add Amount';
            input.value = '';
            modal.classList.remove('hidden');
        }
        
        // Deduct amount from balance when the "Deduction" option is clicked
        function deductAmountFromBalance(button) {
            // Close the menu
            const menu = button.closest('.amount-menu');
            if (menu) menu.classList.add('hidden');
            
            const row = button.closest('tr');
            if (!row) return;

            const availableAmountInput = row.querySelector('.available-amount');
            if (!availableAmountInput) return;

            currentAvailableAllotmentInput = availableAmountInput;
            currentOperationType = 'deduct'; // Set operation type
            
            const modal = document.getElementById('inputAmountModal');
            const input = document.getElementById('amountInput');
            const modalTitle = modal ? modal.querySelector('h3') : null;
            
            if (!modal || !input) return;

            if (modalTitle) modalTitle.textContent = 'Deduct Amount';
            input.value = '';
            modal.classList.remove('hidden');
        }

        function closeInputAmountModal() {
            const modal = document.getElementById('inputAmountModal');
            if (modal) modal.classList.add('hidden');
            const input = document.getElementById('amountInput');
            if (input) input.value = '';
            currentAvailableAllotmentInput = null;
            currentOperationType = null;
        }

        function confirmInputAmountModal() {
            const input = document.getElementById('amountInput');
            if (!input || !currentAvailableAllotmentInput) return;

            const amount = parseFloat(input.value);
            if (!amount || amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            const currentValue = parseCurrency(currentAvailableAllotmentInput.value);
            let newValue;
            
            if (currentOperationType === 'deduct') {
                newValue = currentValue - amount;
            } else {
                newValue = currentValue + amount;
            }
            
            currentAvailableAllotmentInput.value = formatCurrency(newValue);

            autoCalculateBalance(currentAvailableAllotmentInput);
            closeInputAmountModal();
        }
        
        // Simple function to add a program row exactly like in the screenshot
        function addSimpleProgramRow() {
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody) {
                console.error('budgetTableBody not found');
                return;
            }
            
            const newRow = document.createElement('tr');
            newRow.className = 'program-row hover:bg-gray-50/50 transition-all duration-200 border-b border-gray-100';
            
            newRow.innerHTML = `
                <td class="py-4 px-4" style="width: 20%;">
                    <input type="text" class="program-name-input w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-gray-50 hover:bg-white text-gray-800 text-sm transition-all duration-200" placeholder="Enter program name..." onblur="debouncedAutoSave();">
                </td>
                <td class="py-4 px-3" style="width: 20%;">
                    <input 
                        type="text" 
                        placeholder="₱ 0.00"
                        class="approved-budget w-full px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-gray-50 hover:bg-white text-gray-800 text-sm text-center font-medium transition-all duration-200"
                        onblur="formatCurrencyInput(this); autoCalculateBalance(this);"
                        oninput="autoCalculateBalance(this);"
                    />
                </td>
                <td class="py-4 px-3" style="width: 25%;">
                    <div class="flex items-center gap-2">
                        <input 
                            type="text" 
                            placeholder="₱ 0.00"
                            class="available-amount flex-1 px-4 py-2.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-red-500/20 focus:border-red-500 outline-none bg-gray-50 hover:bg-white text-gray-800 text-sm text-center font-medium transition-all duration-200"
                            onblur="formatCurrencyInput(this); autoCalculateBalance(this);"
                            oninput="autoCalculateBalance(this);"
                        />
                        <div class="relative">
                            <button 
                                type="button"
                                onclick="toggleAmountMenu(this)"
                                class="p-2.5 bg-emerald-500 text-white rounded-lg hover:bg-emerald-600 active:scale-95 transition-all duration-200 shadow-sm hover:shadow flex-shrink-0"
                                title="Options"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                                </svg>
                            </button>
                            <div class="amount-menu hidden absolute right-0 mt-2 w-40 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                                <button type="button" onclick="addAmountToBalance(this)" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 transition-colors flex items-center gap-2 rounded-t-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    Add Amount
                                </button>
                                <button type="button" onclick="deductAmountFromBalance(this)" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-red-50 hover:text-red-700 transition-colors flex items-center gap-2 rounded-b-lg">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                    </svg>
                                    Deduction
                                </button>
                            </div>
                        </div>
                    </div>
                </td>
                <td class="py-4 px-3" style="width: 25%;">
                    <input 
                        type="text" 
                        readonly
                        value="₱ 0.00"
                        class="balance-amount w-full px-4 py-2.5 border border-gray-200 rounded-lg bg-green-50 text-green-700 text-sm text-center font-semibold"
                    />
                </td>
                <td class="py-4 px-3 text-center" style="width: 10%;">
                    <button 
                        type="button"
                        onclick="removeSimpleProgramRow(this)"
                        class="p-2.5 bg-red-500 text-white rounded-lg hover:bg-red-600 active:scale-95 transition-all duration-200 shadow-sm hover:shadow"
                        title="Delete Row"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
        }
        
        // Function to remove a simple program row
        async function removeSimpleProgramRow(button) {
            const row = button.closest('tr');
            if (!row) return;
            
            // Get entry ID if it exists (for saved entries)
            const entryId = row.getAttribute('data-entry-id');
            
            if (entryId) {
                // Delete from database
                try {
                    const response = await fetch('../api/cabac_programs.php?action=delete_entry', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id: parseInt(entryId) })
                    });
                    
                    const data = await response.json();
                    
                    if (!data.success) {
                        alert('Error deleting entry: ' + data.message);
                        return;
                    }
                } catch (error) {
                    console.error('Error deleting entry:', error);
                    alert('Error deleting entry from database');
                    return;
                }
            }

            // Remove from display
            row.remove();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtn = document.getElementById('cancelAmountBtn');
            const confirmBtn = document.getElementById('confirmAmountBtn');
            const modal = document.getElementById('inputAmountModal');
            const input = document.getElementById('amountInput');

            cancelBtn?.addEventListener('click', closeInputAmountModal);
            confirmBtn?.addEventListener('click', confirmInputAmountModal);

            input?.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    confirmInputAmountModal();
                }
            });

            modal?.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeInputAmountModal();
                }
            });
        });

        // Show allotment details
        function showAllotmentDetails(input) {
            currentAllotmentInput = input;
            const row = input.closest('tr');
            const rowId = row.rowIndex;
            const entries = budgetRows[rowId] || [];

            const detailsModal = document.getElementById('allotmentDetailsModal');
            const detailsList = document.getElementById('allotmentDetailsList');

            if (entries.length === 0) {
                detailsList.innerHTML = '<p class="text-gray-500 text-sm text-center py-8">No entries yet</p>';
            } else {
                detailsList.innerHTML = entries.map((entry, index) => {
                    const dateStr = entry.date ? new Date(entry.date).toLocaleDateString() : '';
                    const monthStr = entry.month || '';
                    let label;
                    if (entry.fhe) {
                        // FHE checked: show FHE and date
                        label = `AMOUNT FHE ${dateStr}`.trim();
                    } else {
                        // FHE unchecked: show collection and month
                        label = `AMOUNT collection ${monthStr}`.trim();
                    }
                    return `
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                            <div class="w-3 h-3 bg-red-600 rounded-full"></div>
                            <span class="text-sm font-medium text-gray-900">${label}</span>
                            <span class="ml-auto text-sm font-semibold text-maroon">₱ ${entry.amount.toFixed(2)}</span>
                            <button 
                                type="button"
                                onclick="deleteAllotmentEntry(${rowId}, ${index})"
                                class="ml-2 p-1.5 bg-red-600 text-white rounded hover:bg-red-700 transition-colors flex-shrink-0"
                                title="Delete Entry"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    `;
                }).join('');
            }

            detailsModal.classList.remove('hidden');
        }

        // Delete allotment entry
        function deleteAllotmentEntry(rowId, index) {
            if (!budgetRows[rowId] || !budgetRows[rowId][index]) return;
            
            if (confirm('Are you sure you want to delete this entry?')) {
                // Get the amount to subtract
                const entry = budgetRows[rowId][index];
                const amountToSubtract = entry.amount || 0;
                
                // Remove entry from array
                budgetRows[rowId].splice(index, 1);
                
                // Find the row - get from currentAllotmentInput if available
                let row = null;
                if (currentAllotmentInput) {
                    row = currentAllotmentInput.closest('tr');
                } else {
                    // Fallback: find by rowId
                    const tbody = document.getElementById('budgetTableBody');
                    if (tbody) {
                        const rows = Array.from(tbody.querySelectorAll('tr'));
                        if (rows[rowId]) {
                            row = rows[rowId];
                        }
                    }
                }
                
                if (row) {
                    const totalAllotmentInput = row.querySelector('.total-allotment');
                    if (totalAllotmentInput) {
                        const currentTotal = parseCurrency(totalAllotmentInput.value);
                        const newTotal = Math.max(0, currentTotal - amountToSubtract);
                        totalAllotmentInput.value = formatCurrency(newTotal);
                        
                        // Recalculate balance
                        const approvedBudgetInput = row.querySelector('.approved-budget');
                        if (approvedBudgetInput) {
                            const approvedBudget = parseCurrency(approvedBudgetInput.value);
                            const balance = approvedBudget - newTotal;
                            const balanceInput = row.querySelector('.balance-amount');
                            if (balanceInput) {
                                balanceInput.value = formatCurrency(balance);
                            }
                        }
                    }
                }
                
                // Refresh the details list
                if (currentAllotmentInput) {
                    showAllotmentDetails(currentAllotmentInput);
                }
            }
        }

        // Close allotment details modal
        function closeAllotmentDetails() {
            document.getElementById('allotmentDetailsModal').classList.add('hidden');
            currentAllotmentInput = null;
        }

        // Generate summary and save to database
        function generateSummary() {
            const fiduciaryTypeSelect = document.getElementById('fiduciaryType');
            if (!fiduciaryTypeSelect || !fiduciaryTypeSelect.value) {
                alert('Please select a fiduciary type (Non-Fiduciary or Fiduciary)');
                return;
            }
            
            const tbody = document.getElementById('budgetTableBody');
            if (!tbody || tbody.children.length === 0) {
                alert('Please add at least one budget entry');
                return;
            }

            // Collect all entries grouped by particular
            const entries = [];
            const allRows = tbody.querySelectorAll('tr[data-group-id]');
            
            allRows.forEach((row) => {
                const groupId = row.getAttribute('data-group-id');
                const isGroupRow = row.classList.contains('particular-group');
                
                // Get particular from the group row
                let particular = '';
                const groupRow = isGroupRow ? row : tbody.querySelector(`tr.particular-group[data-group-id="${groupId}"]`);
                
                if (groupRow) {
                    const particularsSelect = groupRow.querySelector('.particulars-select');
                    particular = particularsSelect?.value || '';
                }
                
                // Get sub-particular (honoraria) from the same row
                let subParticular = null;
                const honorariaSelect = row.querySelector('.honoraria-select');
                if (honorariaSelect && honorariaSelect.value) {
                    subParticular = honorariaSelect.value;
                }
                
                // Get program and other data from this row
                // Try to find programs search input (custom dropdown) or select (old structure)
                const programsWrapper = row.querySelector('.programs-select-wrapper');
                let programsValue = '';
                if (programsWrapper) {
                    const programsInput = programsWrapper.querySelector('.programs-search-input');
                    if (programsInput) {
                        programsValue = programsInput.value || programsInput.getAttribute('data-value') || '';
                    }
                } else {
                    const programsSelect = row.querySelector('.programs-select-element, .programs-select');
                    if (programsSelect) {
                        programsValue = programsSelect.value || '';
                    }
                }
                
                const approvedBudgetInput = row.querySelector('.approved-budget');
                const totalAllotmentInput = row.querySelector('.total-allotment');
                const balanceInput = row.querySelector('.balance-amount');
                
                if (programsValue && approvedBudgetInput) {
                    const programs = programsValue;
                    const approvedBudget = parseCurrency(approvedBudgetInput.value);
                    const totalAllotment = parseFloat(totalAllotmentInput.value) || 0;
                    const balance = parseFloat(balanceInput.value) || 0;
                    
                    // Get allotment details for this row
                    const rowId = row.rowIndex;
                    const allotmentDetails = budgetRows[rowId] || [];
                    
                    entries.push({
                        particulars: particular,
                        sub_particular: subParticular, // Honoraria values go here
                        programs: programs, // Faculty & Staff Development, Research, etc. go here
                        approved_budget: approvedBudget,
                        total_allotment: totalAllotment,
                        balance: balance,
                        allotment_details: allotmentDetails
                    });
                }
            });

            // Save to database
            const fiscalYear = new Date().getFullYear();
            const saveData = {
                fiduciary_type: fiduciaryTypeSelect.value,
                fiscal_year: fiscalYear,
                entries: entries
            };

            fetch('../api/save_cabac_entries.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(saveData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(`CABAC entries saved successfully! ${data.count} entries saved.`);
                } else {
                    alert('Error saving entries: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error saving entries. Please try again.');
            });
        }

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeAmountModal();
                closeAllotmentDetails();
                closeInputAmountModal();
            }
        });

        // Program name mapping (e.g., Administrator -> Honoraria)
        const PROGRAM_NAME_MAP = {
            'Administrator': 'Honoraria'
        };

        let currentFiduciaryType = 'non-fiduciary';
        let selectedProgramName = '';

        // Handle Non-Fiduciary dropdown selection
        function handleNonFiduciarySelect(value) {
            if (value) {
                selectedProgramName = value;
                currentFiduciaryType = 'non-fiduciary';
                
                // Save selection to localStorage for persistence
                localStorage.setItem('cabac_selected_program', value);
                localStorage.setItem('cabac_selected_type', 'non-fiduciary');
                
                // Update selected type display
                document.getElementById('selectedType').textContent = value;
                
                // Update dropdown button text to show selected program (bold)
                const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
                if (nonFiduciaryText) {
                    nonFiduciaryText.textContent = value;
                    nonFiduciaryText.classList.remove('text-gray-400');
                    nonFiduciaryText.classList.add('text-gray-900', 'font-bold');
                }
                
                // Show clear button
                const clearBtn = document.getElementById('clearNonFiduciaryBtn');
                if (clearBtn) clearBtn.classList.remove('hidden');
                
                // Clear fiduciary selection
                const fiduciaryText = document.getElementById('fiduciarySelectedText');
                if (fiduciaryText) {
                    fiduciaryText.textContent = 'Select program...';
                    fiduciaryText.classList.remove('text-gray-900', 'font-bold');
                    fiduciaryText.classList.add('text-gray-400');
                }
                const clearFiduciaryBtn = document.getElementById('clearFiduciaryBtn');
                if (clearFiduciaryBtn) clearFiduciaryBtn.classList.add('hidden');
                
                // Load budget entries from database for this program
                loadBudgetEntries(value, 'non-fiduciary');
            }
        }

        // Handle Fiduciary dropdown selection
        function handleFiduciarySelect(value) {
            if (value) {
                selectedProgramName = value;
                currentFiduciaryType = 'fiduciary';
                
                // Save selection to localStorage for persistence
                localStorage.setItem('cabac_selected_program', value);
                localStorage.setItem('cabac_selected_type', 'fiduciary');
                
                // Update selected type display
                document.getElementById('selectedType').textContent = value;
                
                // Update dropdown button text to show selected program (bold)
                const fiduciaryText = document.getElementById('fiduciarySelectedText');
                if (fiduciaryText) {
                    fiduciaryText.textContent = value;
                    fiduciaryText.classList.remove('text-gray-400');
                    fiduciaryText.classList.add('text-gray-900', 'font-bold');
                }
                
                // Show clear button
                const clearBtn = document.getElementById('clearFiduciaryBtn');
                if (clearBtn) clearBtn.classList.remove('hidden');
                
                // Clear non-fiduciary selection
                const nonFiduciaryText = document.getElementById('nonFiduciarySelectedText');
                if (nonFiduciaryText) {
                    nonFiduciaryText.textContent = 'Select program...';
                    nonFiduciaryText.classList.remove('text-gray-900', 'font-bold');
                    nonFiduciaryText.classList.add('text-gray-400');
                }
                const clearNonFiduciaryBtn = document.getElementById('clearNonFiduciaryBtn');
                if (clearNonFiduciaryBtn) clearNonFiduciaryBtn.classList.add('hidden');
                
                // Load budget entries from database for this program
                loadBudgetEntries(value, 'fiduciary');
            }
        }

        // Clear Non-Fiduciary selection
        function clearNonFiduciarySearch(event) {
            if (event) event.stopPropagation();
            const select = document.getElementById('searchNonFiduciary');
            const clearBtn = document.getElementById('clearNonFiduciarySearch');
            if (select) select.value = '';
            if (clearBtn) clearBtn.classList.add('hidden');
            selectedProgramName = '';
            document.getElementById('selectedType').textContent = '';
        }

        // Clear Fiduciary selection
        function clearFiduciarySearch(event) {
            if (event) event.stopPropagation();
            const select = document.getElementById('searchFiduciary');
            const clearBtn = document.getElementById('clearFiduciarySearch');
            if (select) select.value = '';
            if (clearBtn) clearBtn.classList.add('hidden');
            selectedProgramName = '';
            document.getElementById('selectedType').textContent = '';
        }

        // Auto-populate program name field
        function autoPopulateProgramName(programValue) {
            // Map program to display name (e.g., Administrator -> Honoraria)
            const displayName = PROGRAM_NAME_MAP[programValue] || programValue;
            
            // If there's an existing row, populate it
            const firstRow = document.querySelector('.program-row');
            if (firstRow) {
                const nameInput = firstRow.querySelector('.program-name');
                if (nameInput && !nameInput.value) {
                    nameInput.value = displayName;
                }
            }
        }

        // Initialize clear buttons visibility on page load
        document.addEventListener('DOMContentLoaded', function() {
            const nonFiduciarySelect = document.getElementById('searchNonFiduciary');
            const fiduciarySelect = document.getElementById('searchFiduciary');
            
            if (nonFiduciarySelect) {
                nonFiduciarySelect.addEventListener('change', function() {
                    const clearBtn = document.getElementById('clearNonFiduciarySearch');
                    if (this.value && clearBtn) {
                        clearBtn.classList.remove('hidden');
                    } else if (clearBtn) {
                        clearBtn.classList.add('hidden');
                    }
                });
            }
            
            if (fiduciarySelect) {
                fiduciarySelect.addEventListener('change', function() {
                    const clearBtn = document.getElementById('clearFiduciarySearch');
                    if (this.value && clearBtn) {
                        clearBtn.classList.remove('hidden');
                    } else if (clearBtn) {
                        clearBtn.classList.add('hidden');
                    }
                });
            }
        });
    </script>

</body>
</html>
