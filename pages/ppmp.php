<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_role'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/Department.php';

$notification = new Notification();
$department = new Department();

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userRole = $_SESSION['user_role'] ?? '';
$departmentId = $_SESSION['department_id'] ?? null;

// For budget role with no session department, look up their department from users table,
// then fall back to the Fiduciary (Budget Office) department
if (!$departmentId && $userRole === 'budget') {
    try {
        $db = getDB();
        // First try users table
        $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['department_id']) {
            $departmentId = $row['department_id'];
        } else {
            // Fall back to Budget Office department (find by budget role users)
            $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) $departmentId = $row['department_id'];
        }
    } catch (Exception $e) { /* silently fail */ }
}

include __DIR__ . '/../components/profile_avatar.php';
$activeSidebar = 'ppmp';

// Determine which sidebar to use based on role
$isBudget = ($userRole === 'budget');
$isProcurement = ($userRole === 'procurement');
$isDepartment = ($userRole === 'department' || $userRole === 'supply_office');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - PPMP</title>
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
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes pulseSlow {
            0%, 100% { transform: scale(1); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); }
            50% { transform: scale(1.05); box-shadow: 0 25px 50px -12px rgba(139, 92, 246, 0.5); }
        }
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        .animate-pulse-slow { animation: pulseSlow 3s ease-in-out infinite; }
        .ppmp-table th { background-color: #800000; color: white; padding: 12px; text-align: left; font-weight: 600; font-size: 11px; }
        .ppmp-table td { padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; word-wrap: break-word; overflow-wrap: break-word; }
        .ppmp-table td:first-child { max-width: 250px; word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        .ppmp-table td textarea { word-wrap: break-word; overflow-wrap: break-word; white-space: normal; }
        .ppmp-table tbody tr:hover { background-color: #f9fafb; }
        .ppmp-table tbody tr.total-row { background-color: #800000; color: white; font-weight: bold; }
        .ppmp-table tbody tr.total-row:hover { background-color: #800000 !important; }
        .ppmp-table tbody tr.total-row td { color: white; }
        
        /* Sidebar spacing */
        [data-main-content] {
            margin-left: 256px;
        }
        
        /* Screen only elements */
        .screen-only-header {
            display: block;
        }
        
        /* Print only elements - hidden by default */
        .print-only-header,
        .print-footer {
            display: none;
        }
        
        /* Print only rows - hidden on screen */
        .print-only-row {
            display: none;
        }
        
        /* Print styles */
        @media print {
            .no-print { display: none !important; }
            
            /* Hide screen elements */
            .screen-only-header { display: none !important; }
            .screen-only-row { display: none !important; }
            
            /* Show print elements */
            .print-only-header { display: block !important; }
            .print-only-row { display: table-row !important; }
            .print-footer { 
                display: block !important;
                page-break-inside: avoid;
            }
            
            /* Hide sidebar */
            aside { display: none !important; }
            
            /* Hide browser default headers and footers */
            @page {
                margin: 0.5in;
                size: landscape;
            }
            
            /* Force landscape orientation */
            @media print {
                @page {
                    size: A4 landscape;
                }
            }
            
            body { 
                margin: 0; 
                padding: 0;
                font-family: Arial, sans-serif;
            }
            [data-main-content] {
                margin-left: 0 !important;
                width: 100% !important;
            }
            
            /* Remove container styling in print */
            #currentPPMPContainer {
                padding: 0 !important;
                margin: 0 !important;
                background: none !important;
                border: none !important;
                box-shadow: none !important;
                border-radius: 0 !important;
            }
            
            .flex-1.p-6 {
                padding: 0 !important;
            }
            
            /* Header styling for print */
            .print-only-header {
                page-break-inside: avoid;
                page-break-after: avoid;
                margin-top: 0 !important;
                padding-top: 10px !important;
                margin-bottom: 20px !important;
            }
            .print-only-header h1 {
                color: #800000 !important;
                font-size: 18px !important;
                font-weight: bold !important;
                margin-bottom: 4px !important;
                letter-spacing: 0.5px;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-only-header h2 {
                color: #2d3748 !important;
                font-size: 16px !important;
                font-weight: 600 !important;
                margin-bottom: 2px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-only-header h3 {
                color: #2d3748 !important;
                font-size: 16px !important;
                font-weight: bold !important;
                margin-bottom: 4px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-only-header h4 {
                color: #2d3748 !important;
                font-size: 14px !important;
                font-weight: bold !important;
                margin-bottom: 6px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .print-only-header p {
                color: #2d3748 !important;
                font-size: 12px !important;
                margin: 2px 0 !important;
                line-height: 1.4;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Table styling for print - show full columns */
            .ppmp-table {
                page-break-inside: auto;
                margin-top: 10px;
            }
            .ppmp-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            .ppmp-table thead {
                display: table-header-group;
            }
            .ppmp-table thead th {
                background-color: #800000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 6px !important;
                font-weight: bold;
                font-size: 9px !important;
                border: 1px solid #666 !important;
            }
            .ppmp-table tbody td {
                padding: 4px !important;
                font-size: 8px !important;
                border: 1px solid #666 !important;
                word-wrap: break-word;
                overflow-wrap: break-word;
            }
            .ppmp-table td:first-child {
                max-width: 200px;
                word-wrap: break-word;
                overflow-wrap: break-word;
                white-space: normal;
            }
            .ppmp-table tbody tr:nth-child(even) {
                background-color: #f9f9f9 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .ppmp-table tbody tr.total-row {
                background-color: #800000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .ppmp-table tbody tr.total-row td {
                color: white !important;
                font-weight: bold !important;
                padding: 8px !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Prevent page break before total row */
            .ppmp-table tbody tr.total-row {
                page-break-before: avoid;
                page-break-inside: avoid;
            }
            
            /* Footer styling */
            .print-footer {
                margin-top: 20px;
                padding-top: 10px;
                border-top: 1px solid #e5e7eb;
            }
            .print-footer p {
                color: #718096 !important;
                font-size: 10px !important;
                margin: 2px 0 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
<div class="flex min-h-screen">
    <?php if ($isBudget): ?>
        <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
    <?php elseif ($isProcurement): ?>
        <aside class="w-64 bg-white shadow-lg fixed left-0 top-0 h-screen z-40 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-maroon">BudgetTrack</h2>
                <p class="text-sm text-gray-600">Procurement Portal</p>
            </div>
            <?php include __DIR__ . '/../components/proc_sidebar.php'; ?>
        </aside>
    <?php else: ?>
        <aside class="w-64 bg-white shadow-lg fixed left-0 top-0 h-screen z-40 overflow-y-auto">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-2xl font-bold text-maroon">BudgetTrack</h2>
                <p class="text-sm text-gray-600">Department Portal</p>
            </div>
            <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
        </aside>
    <?php endif; ?>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col" data-main-content>
        <!-- Header -->
        <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg no-print">
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
                                <h1 class="text-3xl font-bold mb-1">Project Procurement Management Plan (PPMP)</h1>
                                <p class="text-red-100 text-sm">Create and manage procurement plans</p>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center space-x-4">
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
                <!-- Tab Navigation -->
                <div class="mb-6 border-b border-gray-200 no-print">
                    <div class="flex gap-2">
                        <button id="ppmpTab-ppmp" onclick="switchPPMPTab('ppmp')"
                            class="ppmp-tab-btn px-6 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5 rounded-t-lg flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            PPMP
                        </button>
                        <button id="ppmpTab-supplemental" onclick="switchPPMPTab('supplemental')"
                            class="ppmp-tab-btn px-6 py-3 text-sm font-medium border-b-2 border-transparent text-gray-500 hover:text-gray-700 hover:bg-gray-50 rounded-t-lg flex items-center gap-2 hidden">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4">
                                </path>
                            </svg>
                            Supplemental
                        </button>
                    </div>
                </div>
                
                <!-- Year Filter -->
                <div class="mb-4 flex items-center gap-3 no-print">
                    <label class="text-sm font-semibold text-gray-700">Filter by Year:</label>
                    <select id="yearFilter" onchange="filterPPMPByYear()" class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon bg-white">
                        <option value="">All Years</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                        <option value="2029">2029</option>
                        <option value="2030">2030</option>
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-6 flex justify-between items-center no-print">
                    <div class="flex gap-3">
                        <div class="relative">
                            <button id="createPPMPButton" type="button" onclick="toggleCreatePPMPDropdown()" class="px-6 py-3 bg-gradient-to-r from-maroon to-red-700 text-white rounded-lg hover:from-maroon-dark hover:to-red-800 transition-all font-semibold flex items-center gap-2 shadow-lg">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                </svg>
                                <span id="createButtonText">Create New PPMP</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div id="createPPMPDropdown" class="hidden absolute top-full left-0 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 z-50">
                                <button type="button" onclick="showCreatePPMPModal('ppmp')" class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors flex items-center gap-3 border-b border-gray-100">
                                    <svg class="w-5 h-5 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <div>
                                        <div class="font-semibold text-gray-900">Regular PPMP</div>
                                        <div class="text-xs text-gray-500">Standard procurement plan</div>
                                    </div>
                                </button>
                                <button type="button" onclick="showCreatePPMPModal('supplemental')" class="w-full px-4 py-3 text-left hover:bg-gray-50 transition-colors flex items-center gap-3">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <div>
                                        <div class="font-semibold text-gray-900">Supplemental PPMP</div>
                                        <div class="text-xs text-gray-500">Additional procurement items</div>
                                    </div>
                                </button>
                            </div>
                        </div>
                        <button onclick="showDraftsModal()" class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all font-semibold flex items-center gap-2 shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Drafts
                        </button>
                    </div>
                    <div class="flex gap-3">
                        <button onclick="showHistoryModal()" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            History
                        </button>
                    </div>
                </div>

                <!-- PPMP Tab Content -->
                <div id="ppmpTabContent" class="ppmp-content-panel">
                    <div id="currentPPMPContainer" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <div class="text-center py-12 text-gray-500">
                            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-semibold mb-2">No PPMP Created</p>
                            <p class="text-sm">Click "Create New PPMP" to get started</p>
                        </div>
                    </div>
                </div>

                <!-- Supplemental Tab Content -->
                <div id="supplementalTabContent" class="ppmp-content-panel hidden">
                    <div id="currentSupplementalContainer" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                        <div class="text-center py-12 text-gray-500">
                            <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            <p class="text-lg font-semibold mb-2">No Supplemental Created</p>
                            <p class="text-sm">Click "Create New PPMP" and select "Supplemental PPMP" to get started</p>
                        </div>
                    </div>
                </div>

                <!-- Important Precondition Banner (Bottom) -->
                <div class="mt-6 bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded-r-lg no-print">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-bold text-yellow-800">Important Precondition</h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p class="font-semibold mb-1">Before creating a PPMP, ensure that:</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Your budget allocation has been <strong>fully approved</strong> and signed</li>
                                    <li>All procurement requirements have been <strong>identified and documented</strong></li>
                                    <li>Timeline and delivery schedules have been <strong>properly planned</strong></li>
                                </ul>
                                <p class="mt-2 text-xs italic">Only create a PPMP after proper planning and budget approval.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Precondition Confirmation Modal -->
<div id="preconditionModal" class="fixed inset-0 bg-black bg-opacity-70 hidden z-[70] flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-lg w-full mx-4 overflow-hidden">
        <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4 flex items-center gap-3">
            <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
            </svg>
            <h3 class="text-xl font-bold text-white">Important Reminder</h3>
        </div>
        <div class="p-6">
            <p class="text-gray-800 font-semibold mb-4" id="preconditionIntro">Before creating a PPMP, please ensure that:</p>
            <ul class="space-y-3 mb-6" id="preconditionList">
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-700">Your budget allocation has been <strong>fully approved</strong> and signed</span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-700">All procurement requirements have been <strong>identified and documented</strong></span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-700">Timeline and delivery schedules have been <strong>properly planned</strong></span>
                </li>
            </ul>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-6">
                <p class="text-sm text-yellow-800 italic" id="preconditionNote">Only proceed if proper planning and budget approval are in place.</p>
            </div>
            <div class="flex gap-3 justify-end">
                <button onclick="closePreconditionModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-semibold transition-colors">
                    Cancel
                </button>
                <button onclick="confirmProceedToCreate()" class="px-6 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark font-semibold transition-colors">
                    I Understand, Proceed
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit PPMP Modal - Modern Card-Based Layout -->
<div id="ppmpModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="min-h-screen px-4 py-8 flex items-center justify-center">
        <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-2xl shadow-2xl w-full max-w-7xl relative">
            <!-- Header -->
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-8 py-6 rounded-t-2xl">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-3xl font-bold text-white" id="modalTitle">Create PPMP</h3>
                        <p class="text-red-100 text-sm mt-1">Build your procurement plan step by step</p>
                    </div>
                    <button onclick="closePPMPModal()" class="text-white hover:bg-white hover:bg-opacity-20 rounded-full p-2 transition-all">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <!-- Sticky Floating Add Item Button -->
            <button type="button" onclick="addPPMPItem()" 
                class="fixed bottom-8 left-8 z-[60] px-6 py-4 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-full hover:from-purple-700 hover:to-purple-800 flex items-center gap-3 shadow-2xl transition-all transform hover:scale-110 animate-pulse-slow"
                title="Add New Item">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="font-bold">Add Item</span>
            </button>

            <form id="ppmpForm" class="p-8 space-y-6">
                <input type="hidden" id="ppmpId" name="ppmpId">
                <input type="hidden" id="ppmpType" name="ppmpType" value="ppmp">
                <input type="hidden" id="isIndicative" name="isIndicative" value="1">
                <input type="hidden" id="isFinal" name="isFinal" value="0">
                
                <!-- Step 1: Basic Information -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-blue-500">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">1</div>
                        <h4 class="text-xl font-bold text-gray-800">Basic Information</h4>
                    </div>
                    <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                        <div class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-blue-800 mb-1">Fiscal Year</p>
                                <p class="text-sm text-blue-700">
                                    This PPMP will be created for fiscal year <strong id="selectedFiscalYearDisplay">2026</strong>. 
                                    To change the year, close this form and select a different year from the filter dropdown above.
                                </p>
                            </div>
                        </div>
                    </div>
                    <!-- Hidden field to store fiscal year -->
                    <input type="hidden" id="fiscalYear" name="fiscalYear" value="2026">
                </div>

                <!-- Step 2: Finalization Option -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-green-500">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">2</div>
                        <h4 class="text-xl font-bold text-gray-800">Finalization</h4>
                    </div>
                    <div class="bg-gradient-to-r from-green-50 to-blue-50 rounded-lg p-4 border-2 border-green-200">
                        <label class="flex items-start cursor-pointer group">
                            <input type="checkbox" id="markAsFinal" name="markAsFinal" value="1" 
                                class="w-6 h-6 text-green-600 border-gray-300 rounded focus:ring-2 focus:ring-green-500 mt-1" 
                                onchange="handleMarkAsFinal()">
                            <div class="ml-4 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-base font-bold text-gray-800 group-hover:text-green-700 transition-colors">
                                        Mark as Final
                                    </span>
                                    <span class="px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-semibold rounded-full">Optional</span>
                                </div>
                                <p class="text-sm text-gray-600 mt-1">
                                    Check this box to finalize your PPMP. Final PPMPs will automatically sync to LIB and cannot be edited.
                                </p>
                                <div class="mt-2 flex items-start gap-2 text-xs text-gray-500">
                                    <svg class="w-4 h-4 text-blue-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <span>Leave unchecked to save as a draft that you can edit later</span>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Step 3: Procurement Items -->
                <div class="bg-white rounded-xl shadow-md p-6 border-l-4 border-purple-500">
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <div class="bg-purple-500 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold">3</div>
                            <h4 class="text-xl font-bold text-gray-800">Procurement Items</h4>
                            <span id="itemCountBadge" class="hidden px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">0 items</span>
                        </div>
                        
                        <!-- Search Bar (hidden by default, shown when items > 5) -->
                        <div id="itemSearchContainer" class="hidden flex-1 max-w-md ml-4">
                            <div class="relative">
                                <input type="text" id="itemSearchInput" 
                                    placeholder="Search items by description, type, or budget..." 
                                    class="w-full px-4 py-2 pl-10 border-2 border-purple-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 text-sm"
                                    oninput="searchPPMPItems()">
                                <svg class="w-5 h-5 text-purple-400 absolute left-3 top-1/2 transform -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                <button type="button" id="clearSearchBtn" onclick="clearItemSearch()" 
                                    class="hidden absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            <div id="searchResultsInfo" class="hidden mt-2 text-sm text-purple-600 font-medium"></div>
                        </div>
                    </div>

                    <!-- Items Container -->
                    <div id="ppmpItemsContainer" class="space-y-4">
                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-12 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                            </svg>
                            <p class="text-lg font-semibold text-gray-600 mb-2">No items added yet</p>
                            <p class="text-sm text-gray-500">Click the "Add Item" button below to start building your procurement plan</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex justify-end gap-4 pt-4">
                    <button type="button" onclick="closePPMPModal()" 
                        class="px-8 py-3 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-semibold transition-all">
                        Cancel
                    </button>
                    <button type="button" onclick="savePPMP()" id="savePPMPButton" 
                        class="px-8 py-3 bg-gradient-to-r from-maroon to-red-700 text-white rounded-lg hover:from-maroon-dark hover:to-red-800 font-semibold shadow-lg transition-all transform hover:scale-105">
                        Save Draft
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl z-10">
                <h3 class="text-2xl font-bold">PPMP History (Final Records)</h3>
                <button onclick="closeHistoryModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div id="historyListContainer">
                    <div class="text-center py-8 text-gray-500">
                        <p>Loading history...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Drafts Modal -->
<div id="draftsModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-gray-600 to-gray-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl z-10">
                <div class="flex items-center gap-4">
                    <h3 class="text-2xl font-bold">PPMP Drafts</h3>
                    <select id="draftTypeFilter" onchange="filterDrafts()" class="px-4 py-2 bg-white text-gray-900 rounded-lg border-2 border-gray-300 focus:ring-2 focus:ring-gray-500 focus:border-gray-500 font-semibold">
                        <option value="all">All Types</option>
                        <option value="ppmp">PPMP Only</option>
                        <option value="supplemental">Supplemental Only</option>
                    </select>
                </div>
                <button onclick="closeDraftsModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div id="draftsListContainer">
                    <div class="text-center py-8 text-gray-500">
                        <p>Loading drafts...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- LIB Expense Selector Modal -->
<div id="libExpenseSelectorModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60] overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center rounded-t-xl flex-shrink-0">
                <div>
                    <h3 class="text-xl font-bold text-white">Link to LIB Expense Category</h3>
                    <p class="text-sm text-blue-100 mt-1">Select where this budget will be allocated in the Line Item Budget</p>
                </div>
                <button onclick="closeLibExpenseSelector()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 border-b border-gray-200 flex-shrink-0">
                <div class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-6 h-6 text-blue-600 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-semibold text-blue-900 mb-1">Item: <span id="libSelectorItemName"></span></p>
                            <p class="text-sm text-blue-800">Budget: <span id="libSelectorItemBudget" class="font-bold"></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <input type="hidden" id="libSelectorItemIndex">
                    <input type="text" id="libExpenseSearch" onkeyup="searchLibExpenses()" placeholder="Search expense categories..." class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto p-6">
                <div id="libExpenseCategoriesContainer">
                    <div class="text-center py-8 text-gray-500">
                        <p>Loading expense categories...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Set global variables for JavaScript
window.DEPARTMENT_ID = <?php echo $departmentId ?? 'null'; ?>;
window.IS_BUDGET = <?php echo $isBudget ? 'true' : 'false'; ?>;

// Test if JavaScript is working
console.log('JavaScript is loaded');
console.log('Department ID:', window.DEPARTMENT_ID);
</script>
<script src="../assets/js/ppmp.js?v=<?php echo time(); ?>"></script>
<script>
// Verify ppmp.js loaded
console.log('After ppmp.js load');
console.log('toggleProfileDropdown exists:', typeof toggleProfileDropdown);
console.log('showCreatePPMPModal exists:', typeof showCreatePPMPModal);

// Real-time auto-refresh: when budget office saves a utilization summary,
// reload both PPMP and Supplemental tables so remarks update instantly
window.addEventListener('utilizationSummaryUpdated', function () {
    if (typeof loadCurrentPPMP === 'function') {
        loadCurrentPPMP('ppmp', true);
        loadCurrentPPMP('supplemental', true);
    }
});
</script>

</body>
</html>
