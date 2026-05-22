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
$activeSidebar = 'lib';

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
    <title>BudgetTrack - Line Item Budget (LIB)</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="../assets/js/uacs_codes.js"></script>
    <script src="../assets/js/lib_subcategories.js"></script>
    <script src="../assets/js/lib_subcategories_inline.js"></script>
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
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        .lib-table th { background-color: #800000; color: white; padding: 12px; text-align: left; font-weight: 600; }
        .lib-table td { padding: 10px; border-bottom: 1px solid #e5e7eb; }
        .lib-table tbody tr:hover { background-color: #f9fafb; }
        .lib-table tbody tr.category-header:hover { background-color: #800000 !important; }
        .lib-table tbody tr.subtotal-row:hover { background-color: #f3f4f6 !important; }
        .lib-table tbody tr.grandtotal-row:hover { background-color: #800000 !important; }
        
        /* Autocomplete dropdown */
        .autocomplete-dropdown {
            position: fixed;
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-height: 200px;
            overflow-y: auto;
            z-index: 9999;
            display: none;
            min-width: 300px;
        }
        .autocomplete-item {
            padding: 8px 12px;
            cursor: pointer;
            border-bottom: 1px solid #f3f4f6;
            background: white;
        }
        .autocomplete-item:hover {
            background-color: #f3f4f6;
        }
        .autocomplete-item:last-child {
            border-bottom: none;
        }
        .autocomplete-code {
            font-weight: 600;
            color: #800000;
            font-size: 0.875rem;
        }
        .autocomplete-name {
            color: #6b7280;
            font-size: 0.75rem;
        }
        
        /* Sidebar spacing */
        [data-main-content] {
            margin-left: 256px;
        }
        
        /* Category collapse/expand */
        .category-content {
            max-height: 2000px;
            overflow: visible;
            transition: max-height 0.3s ease-out;
        }
        .category-content.collapsed {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease-in;
        }
        .category-toggle {
            transition: transform 0.3s ease;
        }
        .category-toggle.collapsed {
            transform: rotate(-90deg);
        }
        
        /* Ensure modal content is scrollable */
        #libModal .flex-1 {
            overflow-y: auto !important;
            overflow-x: hidden !important;
        }
        
        /* Smooth scrolling */
        .overflow-y-auto {
            -webkit-overflow-scrolling: touch;
            scroll-behavior: smooth;
        }
        
        /* Ensure table container doesn't clip dropdowns */
        .category-content .overflow-x-auto {
            overflow-y: visible !important;
        }
        
        /* Make sure autocomplete dropdowns appear above everything */
        .autocomplete-dropdown {
            z-index: 99999 !important;
        }
        
        /* Screen only elements */
        .screen-only-header {
            display: block;
        }
        .print-only-header {
            display: none;
        }
        .print-footer {
            display: none;
        }
        
        /* Sub-category table styling - remove backgrounds */
        tr[id^="subCategoriesRow_"] thead,
        tr[id^="subCategoriesRow_"] thead tr,
        tr[id^="subCategoriesRow_"] thead th {
            background: transparent !important;
            background-color: transparent !important;
            color: #000 !important;
        }
        
        /* Force right alignment on Amount header */
        tr[id^="subCategoriesRow_"] thead th:last-child {
            text-align: right !important;
        }
        
        tr[id^="subCategoriesRow_"] tfoot,
        tr[id^="subCategoriesRow_"] tfoot tr,
        tr[id^="subCategoriesRow_"] tfoot td {
            background: transparent !important;
            background-color: transparent !important;
            color: #000 !important;
        }
        
        @media print {
            .no-print { display: none !important; }
            
            /* Hide screen elements */
            .screen-only-header { display: none !important; }
            
            /* Show print elements */
            .print-only-header { display: block !important; }
            .print-footer { 
                display: block !important;
                page-break-inside: avoid;
            }
            
            /* Hide sidebar */
            aside { display: none !important; }
            
            /* Hide browser default headers and footers */
            @page {
                margin: 0.5in;
                size: auto;
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
            #currentLIBContainer {
                padding: 0 !important;
                margin: 0 !important;
                background: none !important;
                border: none !important;
                box-shadow: none !important;
            
            /* Show sub-categories when printing */
            tr[id^="subCategoriesRow_"] {
                display: table-row !important;
                background: white !important;
            }
            
            /* Hide decorative container elements in print */
            tr[id^="subCategoriesRow_"] td > div {
                border: none !important;
                padding: 0 !important;
                background: none !important;
            }
            
            /* Hide "Sub-Categories:" heading in print */
            tr[id^="subCategoriesRow_"] h5 {
                display: none !important;
            }
            
            /* Clean table styling for print */
            tr[id^="subCategoriesRow_"] table {
                margin: 0 !important;
            }
            
            /* Remove background colors and set black text */
            tr[id^="subCategoriesRow_"] thead,
            tr[id^="subCategoriesRow_"] thead tr,
            tr[id^="subCategoriesRow_"] thead th {
                background: white !important;
                color: #000 !important;
            }
            
            tr[id^="subCategoriesRow_"] tfoot,
            tr[id^="subCategoriesRow_"] tfoot tr,
            tr[id^="subCategoriesRow_"] tfoot td {
                background: white !important;
                color: #000 !important;
            }
            
            tr[id^="subCategoriesRow_"] tbody tr,
            tr[id^="subCategoriesRow_"] tbody td {
                color: #000 !important;
            }
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
            
            /* Table styling for print */
            .lib-table {
                page-break-inside: auto;
                margin-top: 10px;
            }
            .lib-table tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            .lib-table thead {
                display: table-header-group;
            }
            .lib-table th {
                background-color: #800000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                padding: 10px !important;
                font-weight: bold;
                font-size: 12px;
            }
            .category-header td {
                background-color: #800000 !important;
                color: white !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-weight: bold;
                padding: 8px !important;
                font-size: 11px;
            }
            .subtotal-row td {
                background-color: #f3f4f6 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                font-weight: bold;
                font-size: 11px;
            }
            .lib-table tbody tr td {
                padding: 6px !important;
                font-size: 11px;
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
                                <h1 class="text-3xl font-bold mb-1">Line Item Budget (LIB)</h1>
                                <p class="text-red-100 text-sm">Create and manage line item budgets</p>
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
                <!-- Year Filter -->
                <div class="mb-4 flex items-center gap-3 no-print">
                    <label class="text-sm font-semibold text-gray-700">Filter by Year:</label>
                    <select id="yearFilter" onchange="filterLIBByYear()" class="px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon bg-white">
                        <option value="">All Years</option>
                        <option value="2024">2024</option>
                        <option value="2025">2025</option>
                        <option value="2026" selected>2026</option>
                        <option value="2027">2027</option>
                        <option value="2028">2028</option>
                    </select>
                </div>
                
                <!-- Action Buttons -->
                <div class="mb-6 flex justify-between items-center no-print">
                    <div class="flex gap-3">
                        <button onclick="showAutoGenerateLIBModal()" class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all font-semibold flex items-center gap-2 shadow-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Generate
                        </button>
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

                <!-- Current LIB Display -->
                <div id="currentLIBContainer" class="bg-white rounded-xl shadow-sm border border-gray-200 p-8">
                    <div class="text-center py-12 text-gray-500">
                        <svg class="w-20 h-20 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg font-semibold mb-2">No Line Item Budget Created</p>
                        <p class="text-sm">Use "Generate" to create a LIB or manually add items to an existing one</p>
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
                                <p class="font-semibold mb-1">Before creating a Line Item Budget (LIB), ensure that:</p>
                                <ul class="list-disc list-inside space-y-1 ml-2">
                                    <li>Your Line Item Budget has been <strong>fully signed</strong> by all required signatories</li>
                                    <li>Your PPMP (Project Procurement Management Plan) has been <strong>completed and approved</strong></li>
                                </ul>
                                <p class="mt-2 text-xs italic">Only create a LIB after proper documentation and approvals are in place.</p>
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
            <p class="text-gray-800 font-semibold mb-4">Before creating a Line Item Budget (LIB), please ensure that:</p>
            <ul class="space-y-3 mb-6">
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-700">Your Line Item Budget has been <strong>fully signed</strong> by all required signatories</span>
                </li>
                <li class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-gray-700">Your PPMP (Project Procurement Management Plan) has been <strong>completed and approved</strong></span>
                </li>
            </ul>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-6">
                <p class="text-sm text-yellow-800 italic">Only proceed if proper documentation and approvals are in place.</p>
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

<!-- Create/Edit LIB Modal -->
<div id="libModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-7xl w-full h-[95vh] flex flex-col">
        <div class="bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl flex-shrink-0">
            <h3 class="text-2xl font-bold" id="modalTitle">Create Line Item Budget</h3>
            <button onclick="closeLIBModal()" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto overflow-x-hidden p-8 min-h-0" style="max-height: calc(95vh - 200px);">
            <form id="libForm">
                <input type="hidden" id="libId" name="libId">
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Fiscal Year</label>
                        <input type="text" id="fiscalYear" name="fiscalYear" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon" placeholder="FY 2026">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Fund Type</label>
                        <select id="fundType" name="fundType" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon">
                            <option value="Internally Generated Fund">Internally Generated Fund</option>
                            <option value="Other Fund">Other Fund</option>
                        </select>
                    </div>
                </div>

                <!-- Mark as Final Checkbox -->
                <div class="mb-6 bg-blue-50 border-2 border-blue-200 rounded-lg p-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" id="markAsFinal" name="markAsFinal" value="1" onchange="updateSaveButtonText()" class="w-5 h-5 text-maroon border-gray-300 rounded focus:ring-2 focus:ring-maroon">
                        <span class="ml-3 text-sm font-semibold text-gray-800">
                            <svg class="w-5 h-5 inline-block mr-1 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Mark as Final
                        </span>
                        <span class="ml-2 text-xs text-gray-600">(Uncheck to save as Draft)</span>
                    </label>
                    <p class="text-xs text-gray-600 mt-2 ml-8">
                        <svg class="w-4 h-4 inline-block mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Final LIBs cannot be edited. Leave unchecked if you want to make changes later.
                    </p>
                </div>

                <div class="mb-4 flex justify-between items-center">
                    <h4 class="text-lg font-bold text-maroon">Budget Categories</h4>
                    <button type="button" onclick="showAddCategoryModal()" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Category
                    </button>
                </div>

                <div id="categoriesContainer" class="space-y-4 mb-6">
                    <!-- Categories will be added here dynamically -->
                </div>
                
                <!-- Extra padding at bottom to ensure last item is visible -->
                <div style="height: 20px;"></div>
            </form>
        </div>
        <div class="border-t-2 border-gray-300 bg-gray-50 px-8 py-3 flex justify-between items-center flex-shrink-0">
            <span class="text-lg font-bold text-gray-700">Grand Total:</span>
            <span class="text-2xl font-bold text-maroon" id="grandTotal">₱0.00</span>
        </div>
        <div class="border-t border-gray-200 px-8 py-4 flex justify-end gap-3 flex-shrink-0 bg-white">
            <button type="button" onclick="closeLIBModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
            <button type="button" onclick="document.getElementById('libForm').requestSubmit()" id="saveLIBButton" class="px-6 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark">Save Draft</button>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div id="addCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60]">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-md w-full">
            <div class="bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl">
                <h3 class="text-xl font-bold">Select Category</h3>
                <button onclick="closeAddCategoryModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    <button onclick="addCategory('A. PERSONAL SERVICES')" class="w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 rounded-lg text-left font-semibold text-blue-900 transition-colors">
                        A. PERSONAL SERVICES
                    </button>
                    <button onclick="addCategory('B. Maintenance & Other Operating Expenses')" class="w-full px-4 py-3 bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-lg text-left font-semibold text-green-900 transition-colors">
                        B. Maintenance & Other Operating Expenses
                    </button>
                    <button onclick="addCategory('C. Capital Outlay')" class="w-full px-4 py-3 bg-purple-50 hover:bg-purple-100 border-2 border-purple-200 rounded-lg text-left font-semibold text-purple-900 transition-colors">
                        C. Capital Outlay
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bulk Entry Modal -->
<div id="bulkEntryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60]">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] flex flex-col">
            <div class="bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl flex-shrink-0">
                <h3 class="text-xl font-bold">Add Items to <span id="bulkCategoryName"></span></h3>
                <button onclick="closeBulkEntryModal()" class="text-white hover:text-gray-200">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="flex-1 overflow-y-auto p-8" style="max-height: calc(90vh - 140px);">
                <div class="mb-4">
                    <label for="bulkEntryTextarea" class="block text-sm font-semibold text-gray-700 mb-2">Expense Categories (one per line):</label>
                    <textarea id="bulkEntryTextarea" class="w-full h-64 px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon transition-all text-gray-900 font-medium text-base resize-none" placeholder="Paste your expense categories here, one per line:

Seminars and Training Expenses
Honoraria-Part time
Honoraria-Overload
Travel Expenses
Textbook & Instructional Materials
..."></textarea>
                    <p class="text-xs text-gray-500 mt-2">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Each line will become a separate entry in the table
                    </p>
                </div>
                
                <div id="bulkPreviewContainer" class="hidden mb-4">
                    <h4 class="text-sm font-semibold text-gray-700 mb-2">Preview:</h4>
                    <div class="border-2 border-gray-300 rounded-lg overflow-auto" style="max-height: 400px;">
                        <table class="w-full">
                            <thead class="bg-maroon text-white sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-2 text-left text-sm font-semibold">Particulars</th>
                                    <th class="px-4 py-2 text-left text-sm font-semibold">Account Code</th>
                                    <th class="px-4 py-2 text-right text-sm font-semibold">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="bulkPreviewTableBody" class="bg-white divide-y divide-gray-200">
                                <!-- Preview rows will be inserted here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-200 px-8 py-4 flex justify-end gap-3 flex-shrink-0">
                <button onclick="closeBulkEntryModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">Cancel</button>
                <button onclick="processBulkEntry()" class="px-6 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark">Add All Items</button>
            </div>
        </div>
    </div>
</div>

<!-- History Modal -->
<div id="historyModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl z-10">
                <h3 class="text-2xl font-bold">LIB History (Final Records)</h3>
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
                <h3 class="text-2xl font-bold">LIB Drafts</h3>
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

<!-- View LIB Modal -->
<div id="viewLIBModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-y-auto">
            <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-700 text-white px-6 py-4 flex justify-between items-center rounded-t-xl no-print">
                <h3 class="text-2xl font-bold">View Line Item Budget</h3>
                <div class="flex gap-2">
                    <button onclick="printLIB()" class="px-4 py-2 bg-white text-maroon rounded-lg hover:bg-gray-100 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                    <button onclick="closeViewLIBModal()" class="text-white hover:text-gray-200">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            <div class="p-8" id="libPrintContent">
                <!-- LIB content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<!-- Auto-Generate LIB Modal -->
<div id="autoGenerateLIBModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-5xl w-full mx-4 max-h-[90vh] flex flex-col">
        <div class="bg-gradient-to-r from-green-600 to-green-700 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-2xl font-bold text-white">Auto-Generate LIB from Allocations</h3>
            <button onclick="closeAutoGenerateLIBModal()" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="flex-1 overflow-y-auto p-6">
            <div class="mb-6">
                <label class="block text-sm font-semibold text-gray-700 mb-2">Select Year</label>
                <select id="autoGenYear" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-green-600">
                    <option value="2024">2024</option>
                    <option value="2025">2025</option>
                    <option value="2026" selected>2026</option>
                    <option value="2027">2027</option>
                    <option value="2028">2028</option>
                </select>
            </div>
            
            <div class="mb-4">
                <button onclick="generateAutoLIB()" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold">
                    Generate LIB
                </button>
            </div>
            
            <div id="autoGenPreview" class="hidden">
                <h4 class="text-lg font-bold text-gray-800 mb-3">Generated LIB Items</h4>
                <div class="border-2 border-gray-300 rounded-lg overflow-auto max-h-96">
                    <table class="w-full">
                        <thead class="bg-green-600 text-white sticky top-0">
                            <tr>
                                <th class="px-4 py-2 text-left">Source</th>
                                <th class="px-4 py-2 text-left">UACS Code</th>
                                <th class="px-4 py-2 text-left">Description</th>
                                <th class="px-4 py-2 text-right">Total Amount</th>
                                <th class="px-4 py-2 text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="autoGenTableBody">
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 flex justify-between items-center">
                    <button onclick="showAddCustomItemModal()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <svg class="w-5 h-5 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Custom Item
                    </button>
                    <div class="text-right">
                        <span class="text-lg font-bold text-gray-700">Grand Total: </span>
                        <span class="text-2xl font-bold text-green-600" id="autoGenGrandTotal">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="border-t border-gray-200 px-6 py-4 flex justify-end gap-3">
            <button onclick="closeAutoGenerateLIBModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                Cancel
            </button>
            <button onclick="saveAutoGeneratedLIB()" id="saveAutoGenBtn" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 hidden">
                Save LIB
            </button>
        </div>
    </div>
</div>

<!-- Add Custom Item Modal -->
<div id="addCustomItemModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-[60] flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full mx-4">
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4 flex justify-between items-center rounded-t-xl">
            <h3 class="text-xl font-bold text-white">Add Custom LIB Item</h3>
            <button onclick="closeAddCustomItemModal()" class="text-white hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        
        <div class="p-6">
            <form id="customItemForm">
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">UACS Code</label>
                        <input type="text" id="customUACSCode" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Total Amount</label>
                        <input type="number" step="0.01" id="customTotalAmount" required class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Description</label>
                    <textarea id="customDescription" required rows="3" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600"></textarea>
                </div>
                
                <div class="grid grid-cols-4 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q1</label>
                        <input type="number" step="0.01" id="customQ1" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q2</label>
                        <input type="number" step="0.01" id="customQ2" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q3</label>
                        <input type="number" step="0.01" id="customQ3" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Q4</label>
                        <input type="number" step="0.01" id="customQ4" class="w-full px-4 py-2 border-2 border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-600">
                    </div>
                </div>
                
                <div class="flex justify-end gap-3">
                    <button type="button" onclick="closeAddCustomItemModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let budgetItemCounter = 0;
let categoryCounter = 0;
let categories = {}; // Store categories and their items
let autoGeneratedItems = [];
let currentAutoGenYear = new Date().getFullYear();

// Department ID for API calls
window.DEPARTMENT_ID = <?php echo $departmentId ? intval($departmentId) : 'null'; ?>;
window.IS_BUDGET = <?php echo $isBudget ? 'true' : 'false'; ?>;

// Profile dropdown toggle
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
    if (confirm('Are you sure you want to logout?')) {
        window.location.href = '../auth/logout.php';
    }
}

// Toggle sub-categories display
function toggleSubCategories(itemId) {
    const subCategoriesRow = document.getElementById(`subCategoriesRow_${itemId}`);
    const toggleIcon = document.getElementById(`toggleIcon_${itemId}`);
    
    if (subCategoriesRow && toggleIcon) {
        if (subCategoriesRow.classList.contains('hidden')) {
            subCategoriesRow.classList.remove('hidden');
            toggleIcon.style.transform = 'rotate(0deg)';
        } else {
            subCategoriesRow.classList.add('hidden');
            toggleIcon.style.transform = 'rotate(-90deg)';
        }
    }
}

// Update Save button text based on Mark as Final checkbox
function updateSaveButtonText() {
    const markAsFinalCheckbox = document.getElementById('markAsFinal');
    const saveButton = document.getElementById('saveLIBButton');
    
    if (markAsFinalCheckbox && saveButton) {
        if (markAsFinalCheckbox.checked) {
            saveButton.textContent = 'Save LIB';
        } else {
            saveButton.textContent = 'Save Draft';
        }
    }
}

// Load LIB list
function loadLIBList(filterYear = null) {
    const departmentId = window.DEPARTMENT_ID || '';
    let url = `../api/get_lib_list.php${departmentId ? '?department_id=' + departmentId : ''}`;
    
    // Add year filter if provided
    if (filterYear) {
        url += (departmentId ? '&' : '?') + 'year=' + filterYear;
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Loaded LIBs:', data.libs); // Debug log
                
                // Display the most recent LIB regardless of status (draft or approved)
                if (data.libs.length > 0) {
                    console.log('Displaying most recent LIB (draft or approved)'); // Debug log
                    displayCurrentLIB(data.libs[0].id);
                } else {
                    console.log('No LIBs found at all'); // Debug log
                    // Show empty state
                    const container = document.getElementById('currentLIBContainer');
                    const yearText = filterYear ? ` for ${filterYear}` : '';
                    container.innerHTML = `
                        <div class="text-center py-12 text-gray-500">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-lg font-medium mb-2">No Line Item Budget Found${yearText}</p>
                            <p class="text-sm">Create your first LIB to get started.</p>
                        </div>
                    `;
                }
            } else {
                console.error('Error loading LIB records:', data.message);
                const container = document.getElementById('currentLIBContainer');
                container.innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p>Error loading LIB records: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            const container = document.getElementById('currentLIBContainer');
            container.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p>Error loading LIB records</p>
                </div>
            `;
        });
}

// Filter LIB by year
function filterLIBByYear() {
    const yearFilter = document.getElementById('yearFilter').value;
    loadLIBList(yearFilter || null);
}

function displayCurrentLIB(libId) {
    fetch(`../api/get_lib_details.php?id=${libId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('currentLIBContainer');
                container.innerHTML = generateLIBView(data.lib, data.items, data.department, true);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

function generateLIBView(lib, items, department, showActions = false) {
    const statusColors = {
        'draft': 'bg-gray-100 text-gray-800',
        'pending_approval': 'bg-yellow-100 text-yellow-800',
        'approved': 'bg-green-100 text-green-800',
        'rejected': 'bg-red-100 text-red-800'
    };
    const statusLabels = {
        'draft': 'DRAFT',
        'pending_approval': 'PENDING APPROVAL',
        'approved': 'FINAL',
        'rejected': 'REJECTED'
    };
    const statusClass = statusColors[lib.status] || 'bg-gray-100 text-gray-800';
    const statusText = statusLabels[lib.status] || lib.status.replace('_', ' ').toUpperCase();
    
    // Get current date and time for footer
    const now = new Date();
    const dateOptions = { year: 'numeric', month: 'long', day: 'numeric' };
    const timeOptions = { hour: '2-digit', minute: '2-digit', hour12: true };
    const generatedDate = now.toLocaleDateString('en-US', dateOptions);
    const generatedTime = now.toLocaleTimeString('en-US', timeOptions);
    
    let html = `
        <!-- Screen View Header (Hidden in Print) -->
        <div class="screen-only-header mb-6">
            <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                <!-- Header Bar -->
                <div class="bg-gradient-to-r from-maroon to-red-700 px-6 py-4">
                    <h2 class="text-2xl font-bold text-white flex items-center gap-3">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Line Item Budget
                    </h2>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Department -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Department</p>
                                <p class="text-base font-bold text-gray-900">${department.dept_name}</p>
                            </div>
                        </div>
                        
                        <!-- Fiscal Year -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fiscal Year</p>
                                <p class="text-base font-bold text-gray-900">${lib.fiscal_year}</p>
                            </div>
                        </div>
                        
                        <!-- Fund Type -->
                        <div class="flex items-start gap-3">
                            <div class="flex-shrink-0 w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fund Type</p>
                                <p class="text-base font-bold text-gray-900">${lib.fund_type}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Status Badge -->
                    <div class="mt-4 flex justify-end">
                        <span class="px-4 py-2 rounded-full text-sm font-bold ${statusClass}">${statusText}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Print Only Header (Hidden on Screen) -->
        <div class="print-only-header text-center mb-6" style="page-break-inside: avoid;">
            <h1 class="text-xl font-bold mb-1" style="color: #800000; letter-spacing: 0.5px;">EASTERN VISAYAS STATE UNIVERSITY</h1>
            <h2 class="text-lg font-semibold mb-1" style="color: #2d3748;">ORMOC CAMPUS</h2>
            <p class="text-sm mb-4" style="color: #718096;">Ormoc City</p>
            
            <h3 class="text-lg font-bold mb-1" style="color: #2d3748;">DEPARTMENT OF ${department.dept_name.toUpperCase()}</h3>
            <h4 class="text-base font-bold mb-2" style="color: #2d3748;">LINE ITEM BUDGET</h4>
            
            <p class="text-sm font-semibold mb-1" style="color: #2d3748;">${lib.fiscal_year}</p>
            <p class="text-sm font-semibold mb-2" style="color: #2d3748;">${lib.fund_type}</p>
        </div>
    `;
    
    if (showActions && lib.status === 'draft') {
        html += `
            <div class="flex justify-end gap-2 mb-4 no-print">
                <button onclick="editLIB(${lib.id})" class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                    </svg>
                    Edit
                </button>
                <button onclick="deleteLIB(${lib.id})" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Delete
                </button>
                <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print
                </button>
            </div>
        `;
    } else if (showActions && lib.status === 'approved') {
        html += `
            <div class="flex justify-end gap-2 mb-4 no-print">
                <button onclick="window.print()" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                    </svg>
                    Print
                </button>
            </div>
        `;
    }
    
    html += `
        <table class="w-full lib-table border-collapse border border-gray-300 mb-6">
            <thead>
                <tr>
                    <th class="border border-gray-300 text-center">PARTICULARS</th>
                    <th class="border border-gray-300 text-center">ACCOUNT CODE</th>
                    <th class="border border-gray-300 text-center">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
    `;
    
    // Define all categories to ensure they all appear
    const allCategories = [
        'A. PERSONAL SERVICES',
        'B. Maintenance & Other Operating Expenses',
        'C. Capital Outlay'
    ];
    
    // Group items by category
    const itemsByCategory = {};
    allCategories.forEach(cat => {
        itemsByCategory[cat] = items.filter(item => item.category === cat);
    });
    
    let grandTotal = 0;
    const isDraft = lib.status === 'draft';
    
    allCategories.forEach(category => {
        const categoryItems = itemsByCategory[category] || [];
        let categoryTotal = 0;
        
        // Category Header
        html += `
            <tr class="bg-maroon text-white font-bold category-header">
                <td class="border border-gray-300 pl-4" colspan="3">
                    <div class="flex justify-between items-center">
                        <span>${category}</span>
                    </div>
                </td>
            </tr>
        `;
        
        // Add Item Button Row (only show for draft LIBs)
        if (isDraft && showActions) {
            html += `
                <tr class="bg-gray-50 no-print">
                    <td class="border border-gray-300 px-4 py-3" colspan="3">
                        <button type="button" onclick="showInlineAddItem('${category}', ${lib.id})" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Item
                        </button>
                    </td>
                </tr>
            `;
        }
        
        // Add Item Row (hidden by default, only for draft LIBs)
        if (isDraft && showActions) {
            html += `
                <tr id="addItemRow_${category.replace(/[^a-zA-Z]/g, '')}" class="hidden bg-blue-50 no-print">
                    <td class="border border-gray-300 p-3" colspan="3">
                        <div class="flex gap-3 items-start">
                            <div class="flex-1">
                                <label class="block text-xs font-semibold text-gray-700 mb-1">Particulars</label>
                                <input type="text" 
                                       id="newParticulars_${category.replace(/[^a-zA-Z]/g, '')}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-maroon focus:border-maroon"
                                   placeholder="Type to search UACS..."
                                   onkeyup="searchUACSInline('${category.replace(/[^a-zA-Z]/g, '')}')"
                                   autocomplete="off">
                            <div id="uacsDropdown_${category.replace(/[^a-zA-Z]/g, '')}" class="hidden absolute bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50" style="width: 400px;"></div>
                        </div>
                        <div class="w-48">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Account Code</label>
                            <input type="text" 
                                   id="newAccountCode_${category.replace(/[^a-zA-Z]/g, '')}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100"
                                   placeholder="Auto-filled"
                                   readonly>
                        </div>
                        <div class="w-40">
                            <label class="block text-xs font-semibold text-gray-700 mb-1">Amount</label>
                            <input type="number" 
                                   id="newAmount_${category.replace(/[^a-zA-Z]/g, '')}" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-maroon focus:border-maroon"
                                   placeholder="0.00"
                                   step="0.01"
                                   min="0">
                        </div>
                        <div class="flex gap-2 pt-6">
                            <button onclick="saveInlineItem('${category}', ${lib.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold">
                                Save
                            </button>
                            <button onclick="cancelInlineAddItem('${category.replace(/[^a-zA-Z]/g, '')}')" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                Cancel
                            </button>
                        </div>
                    </div>
                </td>
            </tr>
            `;
        }
        
        // Category Items
        if (categoryItems.length === 0) {
            html += `
                <tr>
                    <td class="border border-gray-300 pl-8 py-4 text-gray-400 italic" colspan="3">No items in this category</td>
                </tr>
            `;
        } else {
            categoryItems.forEach(item => {
                const amount = parseFloat(item.amount);
                categoryTotal += amount;
                grandTotal += amount;
                
                // Check if item is manual and can be edited
                const isManual = item.source === 'manual';
                const canEdit = isDraft && isManual && showActions;
                
                // Check if item has sub-categories
                const hasSubCategories = item.sub_categories && item.sub_categories.length > 0;
                const isOtherMaintenance = item.particulars && item.particulars.toLowerCase().includes('other maintenance') && item.particulars.toLowerCase().includes('operating expenses');
                
                console.log('Item:', item.particulars, 'Source:', item.source, 'isManual:', isManual, 'canEdit:', canEdit, 'hasSubCategories:', hasSubCategories);
                
                html += `
                    <tr id="itemRow_${item.id}">
                        <td class="border border-gray-300 pl-8">
                            ${hasSubCategories ? `
                                <div class="flex items-center gap-2">
                                    <button onclick="toggleSubCategories(${item.id})" class="text-maroon hover:text-maroon-dark no-print">
                                        <svg id="toggleIcon_${item.id}" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    </button>
                                    <span class="font-semibold">${item.particulars}</span>
                                    ${item.is_ppmp_linked ? `
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full no-print" title="This item is linked to a PPMP and can only be edited through the PPMP">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            PPMP
                                        </span>
                                    ` : ''}
                                </div>
                            ` : `
                                <div class="flex items-center gap-2">
                                    <span>${item.particulars}</span>
                                    ${item.is_ppmp_linked ? `
                                        <span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full no-print" title="This item is linked to a PPMP and can only be edited through the PPMP">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path>
                                            </svg>
                                            PPMP
                                        </span>
                                    ` : ''}
                                </div>
                            `}
                        </td>
                        <td class="border border-gray-300 text-center">${item.account_code}</td>
                        <td class="border border-gray-300 text-right pr-4">
                            <div class="flex items-center justify-between">
                                <span>₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                                ${canEdit && !item.is_ppmp_linked ? `
                                    <div class="flex gap-2 no-print ml-4">
                                        <button onclick="showEditItemRow(${item.id}, '${item.particulars.replace(/'/g, "\\'")}', '${item.account_code}', ${amount}, ${lib.id})" 
                                                class="px-2 py-1 bg-blue-500 text-white rounded text-xs hover:bg-blue-600" 
                                                title="Edit">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                        <button onclick="deleteLibItem(${item.id}, ${lib.id})" 
                                                class="px-2 py-1 bg-red-500 text-white rounded text-xs hover:bg-red-600" 
                                                title="Delete">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                ` : item.is_ppmp_linked ? `
                                    <div class="flex items-center gap-2 no-print ml-4">
                                        <span class="px-3 py-1 bg-gray-100 text-gray-500 rounded text-xs font-medium" title="PPMP-linked items can only be edited through the PPMP">
                                            <svg class="w-3 h-3 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                            </svg>
                                            Locked
                                        </span>
                                    </div>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `;
                
                // Add sub-categories row (hidden by default)
                if (hasSubCategories) {
                    html += `
                        <tr id="subCategoriesRow_${item.id}" class="hidden">
                            <td colspan="3" class="border border-gray-300 px-8 py-3">
                                <div class="border border-gray-300 rounded p-3">
                                    <h5 class="font-bold text-sm text-gray-900 mb-2">Sub-Categories:</h5>
                                    <table class="w-full">
                                        <thead>
                                            <tr>
                                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-900 border-b border-gray-300">Sub-Category Name</th>
                                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-900 border-b border-gray-300">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                    `;
                    
                    item.sub_categories.forEach(sub => {
                        const subAmount = parseFloat(sub.amount);
                        html += `
                            <tr class="border-b border-gray-200">
                                <td class="px-3 py-2 text-sm text-gray-900">${sub.sub_category_name}</td>
                                <td class="px-3 py-2 text-sm text-right font-semibold text-gray-900">₱${subAmount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                            </tr>
                        `;
                    });
                    
                    html += `
                                        </tbody>
                                        <tfoot class="border-t-2 border-gray-300">
                                            <tr>
                                                <td class="px-3 py-2 text-sm font-bold text-right text-gray-900">Total:</td>
                                                <td class="px-3 py-2 text-sm font-bold text-right text-gray-900">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </td>
                        </tr>
                    `;
                }
                
                // Edit row (existing code)
                html += `
                    <tr id="editItemRow_${item.id}" class="hidden bg-blue-50 no-print">
                        <td class="border border-gray-300 p-3" colspan="3">
                            <div class="flex gap-3 items-start">
                                <div class="flex-1">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Particulars</label>
                                    <input type="text" 
                                           id="editParticulars_${item.id}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-maroon focus:border-maroon"
                                           value="${item.particulars.replace(/"/g, '&quot;')}"
                                           onkeyup="searchUACSForEdit(${item.id})"
                                           autocomplete="off">
                                    <div id="editUacsDropdown_${item.id}" class="hidden absolute bg-white border border-gray-300 rounded-lg shadow-lg mt-1 max-h-48 overflow-y-auto z-50" style="width: 400px;"></div>
                                </div>
                                <div class="w-48">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Account Code</label>
                                    <input type="text" 
                                           id="editAccountCode_${item.id}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded bg-gray-100"
                                           value="${item.account_code}"
                                           readonly>
                                </div>
                                <div class="w-40">
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Amount</label>
                                    <input type="number" 
                                           id="editAmount_${item.id}" 
                                           class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-maroon focus:border-maroon"
                                           value="${amount}"
                                           step="0.01"
                                           min="0">
                                </div>
                                <div class="flex gap-2 pt-6">
                                    <button onclick="saveEditedItem(${item.id}, ${lib.id})" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 font-semibold">
                                        Save
                                    </button>
                                    <button onclick="cancelEditItem(${item.id})" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">
                                        Cancel
                                    </button>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
            });
        }
        
        // Sub-Total
        html += `
            <tr class="font-bold bg-gray-100 subtotal-row">
                <td class="border border-gray-300 text-right pr-4" colspan="2">Sub-Total</td>
                <td class="border border-gray-300 text-right pr-4">₱${categoryTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
    });
    
    html += `
            <tr class="font-bold bg-maroon text-white text-lg grandtotal-row">
                <td class="border border-gray-300 text-right pr-4" colspan="2">Grand Total</td>
                <td class="border border-gray-300 text-right pr-4">₱${grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        </tbody>
    </table>
    `;
    
    // Add Finalize button for draft LIBs
    if (isDraft && showActions) {
        html += `
            <div class="mt-6 flex justify-end no-print">
                <button onclick="finalizeLIB(${lib.id})" class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-bold text-lg flex items-center gap-2 shadow-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Finalize LIB
                </button>
            </div>
        `;
    }
    
    html += `
    <!-- Print Footer (Hidden on Screen) -->
    <div class="print-footer">
        <div style="border-top: 2px solid #800000; padding-top: 8px; margin-top: 15px;">
            <table style="width: 100%; font-size: 9px; color: #666;">
                <tr>
                    <td style="text-align: left;">
                        <strong style="color: #800000;">Eastern Visayas State University - Ormoc Campus</strong><br>
                        <span>BudgetTrack System</span>
                    </td>
                    <td style="text-align: right;">
                        <span>Generated: ${generatedDate} at ${generatedTime}</span>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    `;
    
    return html;
}

function showHistoryModal() {
    document.getElementById('historyModal').classList.remove('hidden');
    loadHistoryList();
}

function closeHistoryModal() {
    document.getElementById('historyModal').classList.add('hidden');
}

function showDraftsModal() {
    document.getElementById('draftsModal').classList.remove('hidden');
    loadDraftsList();
}

function closeDraftsModal() {
    document.getElementById('draftsModal').classList.add('hidden');
}

function loadHistoryList() {
    const departmentId = window.DEPARTMENT_ID || '';
    fetch(`../api/get_lib_list.php${departmentId ? '?department_id=' + departmentId : ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Filter only approved (final) records for history
                const finalLIBs = data.libs.filter(lib => lib.status === 'approved');
                displayHistoryList(finalLIBs);
            } else {
                document.getElementById('historyListContainer').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p>Error loading history: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('historyListContainer').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p>Error loading history</p>
                </div>
            `;
        });
}

function loadDraftsList() {
    const departmentId = window.DEPARTMENT_ID || '';
    fetch(`../api/get_lib_list.php${departmentId ? '?department_id=' + departmentId : ''}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Filter only draft records
                const draftLIBs = data.libs.filter(lib => lib.status === 'draft');
                displayDraftsList(draftLIBs);
            } else {
                document.getElementById('draftsListContainer').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <p>Error loading drafts: ${data.message}</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            document.getElementById('draftsListContainer').innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <p>Error loading drafts</p>
                </div>
            `;
        });
}

function displayHistoryList(libs) {
    const container = document.getElementById('historyListContainer');
    if (libs.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p>No LIB records found.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="overflow-x-auto"><table class="w-full lib-table"><thead><tr>';
    html += '<th>Fiscal Year</th><th>Fund Type</th><th>Status</th><th>Created Date</th><th>Total Amount</th><th>Actions</th>';
    html += '</tr></thead><tbody>';

    libs.forEach(lib => {
        const statusColors = {
            'draft': 'bg-gray-100 text-gray-800',
            'pending_approval': 'bg-yellow-100 text-yellow-800',
            'approved': 'bg-green-100 text-green-800',
            'rejected': 'bg-red-100 text-red-800'
        };
        const statusLabels = {
            'draft': 'DRAFT',
            'pending_approval': 'PENDING APPROVAL',
            'approved': 'FINAL',
            'rejected': 'REJECTED'
        };
        const statusClass = statusColors[lib.status] || 'bg-gray-100 text-gray-800';
        const statusText = statusLabels[lib.status] || lib.status.replace('_', ' ').toUpperCase();
        
        html += `<tr>
            <td class="font-semibold">${lib.fiscal_year}</td>
            <td>${lib.fund_type}</td>
            <td><span class="px-3 py-1 rounded-full text-xs font-semibold ${statusClass}">${statusText}</span></td>
            <td>${new Date(lib.created_at).toLocaleDateString()}</td>
            <td class="font-bold text-maroon">₱${parseFloat(lib.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td>
                <div class="flex gap-2">
                    <button onclick="viewLIBFromHistory(${lib.id})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">View</button>
                    <button onclick="editLIB(${lib.id})" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">Edit</button>
                    <button onclick="downloadLIB(${lib.id})" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm">Download</button>
                    <button onclick="deleteLIBFromHistory(${lib.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm">Delete</button>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function displayDraftsList(libs) {
    const container = document.getElementById('draftsListContainer');
    if (libs.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p>No draft LIB records found.</p>
            </div>
        `;
        return;
    }

    let html = '<div class="overflow-x-auto"><table class="w-full lib-table"><thead><tr>';
    html += '<th>Fiscal Year</th><th>Fund Type</th><th>Created Date</th><th>Total Amount</th><th>Actions</th>';
    html += '</tr></thead><tbody>';

    libs.forEach(lib => {
        html += `<tr>
            <td class="font-semibold">${lib.fiscal_year}</td>
            <td>${lib.fund_type}</td>
            <td>${new Date(lib.created_at).toLocaleDateString()}</td>
            <td class="font-bold text-maroon">₱${parseFloat(lib.total_amount || 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            <td>
                <div class="flex gap-2">
                    <button onclick="viewLIBFromDrafts(${lib.id})" class="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">View</button>
                    <button onclick="editLIBFromDrafts(${lib.id})" class="px-3 py-1 bg-yellow-600 text-white rounded hover:bg-yellow-700 text-sm">Edit</button>
                    <button onclick="deleteLIBFromDrafts(${lib.id})" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm">Delete</button>
                </div>
            </td>
        </tr>`;
    });

    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function viewLIBFromDrafts(id) {
    closeDraftsModal();
    displayCurrentLIB(id);
}

function editLIBFromDrafts(id) {
    closeDraftsModal();
    editLIB(id);
}

function deleteLIBFromDrafts(id) {
    if (!confirm('Are you sure you want to delete this draft LIB? This action cannot be undone.')) {
        return;
    }
    
    fetch('../api/delete_lib.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear utilization localStorage if requested
            if (data.clear_utilization && data.department_id && data.fiscal_year) {
                clearUtilizationLocalStorage(data.department_id, data.fiscal_year);
            }
            alert(data.message);
            // Reload drafts list AND refresh main container
            loadDraftsList();
            loadLIBList();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the LIB');
    });
}

function viewLIBFromHistory(id) {
    closeHistoryModal();
    displayCurrentLIB(id);
}

function editLIBFromHistory(id) {
    closeHistoryModal();
    editLIB(id);
}

function deleteLIBFromHistory(id) {
    if (!confirm('Are you sure you want to delete this LIB? This action cannot be undone.')) {
        return;
    }
    
    fetch('../api/delete_lib.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear utilization localStorage if requested
            if (data.clear_utilization && data.department_id && data.fiscal_year) {
                clearUtilizationLocalStorage(data.department_id, data.fiscal_year);
            }
            alert(data.message);
            // Reload history list AND refresh main container
            loadHistoryList();
            loadLIBList();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the LIB');
    });
}

function downloadLIB(id) {
    // Load the LIB and display it in the main container, then print
    fetch(`../api/get_lib_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const container = document.getElementById('currentLIBContainer');
                container.innerHTML = generateLIBView(data.lib, data.items, data.department, false);
                
                // Close history modal
                closeHistoryModal();
                
                // Trigger print after a short delay to ensure content is rendered
                setTimeout(() => {
                    window.print();
                }, 300);
            } else {
                alert('Error loading LIB: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the LIB');
        });
}

function showCreateLIBModal() {
    // Show custom precondition modal first
    document.getElementById('preconditionModal').classList.remove('hidden');
}

function closePreconditionModal() {
    document.getElementById('preconditionModal').classList.add('hidden');
}

function confirmProceedToCreate() {
    closePreconditionModal();
    
    document.getElementById('modalTitle').textContent = 'Create Line Item Budget';
    document.getElementById('libForm').reset();
    document.getElementById('libId').value = '';
    
    // Reset checkbox to unchecked (draft by default)
    const markAsFinalCheckbox = document.getElementById('markAsFinal');
    if (markAsFinalCheckbox) {
        markAsFinalCheckbox.checked = false;
    }
    
    document.getElementById('categoriesContainer').innerHTML = '';
    categories = {};
    categoryCounter = 0;
    budgetItemCounter = 0;
    updateGrandTotal();
    document.getElementById('libModal').classList.remove('hidden');
}

function closeLIBModal() {
    document.getElementById('libModal').classList.add('hidden');
}

function showAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.remove('hidden');
}

function closeAddCategoryModal() {
    document.getElementById('addCategoryModal').classList.add('hidden');
}

function addCategory(categoryName) {
    // Check if category already exists
    if (categories[categoryName]) {
        alert('This category has already been added!');
        closeAddCategoryModal();
        return;
    }
    
    categoryCounter++;
    const categoryId = `category${categoryCounter}`;
    categories[categoryName] = { id: categoryId, items: [] };
    
    const container = document.getElementById('categoriesContainer');
    const categoryDiv = document.createElement('div');
    categoryDiv.className = 'border-2 border-gray-300 rounded-lg overflow-hidden';
    categoryDiv.id = categoryId;
    
    const categoryColors = {
        'A. PERSONAL SERVICES': 'bg-blue-100 border-blue-300 text-blue-900',
        'B. Maintenance & Other Operating Expenses': 'bg-green-100 border-green-300 text-green-900',
        'C. Capital Outlay': 'bg-purple-100 border-purple-300 text-purple-900'
    };
    
    const colorClass = categoryColors[categoryName] || 'bg-gray-100 border-gray-300 text-gray-900';
    
    categoryDiv.innerHTML = `
        <div class="flex justify-between items-center p-4 ${colorClass} cursor-pointer" onclick="toggleCategory('${categoryId}')">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 category-toggle" id="${categoryId}_toggle" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
                <h5 class="font-bold text-lg">${categoryName}</h5>
            </div>
            <div class="flex gap-2" onclick="event.stopPropagation()">
                <button type="button" onclick="showBulkEntryModal('${categoryName}')" class="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Add Multiple Items
                </button>
                <button type="button" onclick="removeCategory('${categoryName}')" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </div>
        </div>
        <div class="category-content bg-white" id="${categoryId}_content">
            <div class="p-4">
                <div id="${categoryId}_items">
                    <table class="w-full border-collapse">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 border-b-2 border-gray-300">Particulars</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold text-gray-700 border-b-2 border-gray-300">Account Code</th>
                                <th class="px-3 py-2 text-right text-xs font-semibold text-gray-700 border-b-2 border-gray-300">Amount</th>
                                <th class="px-3 py-2 text-center text-xs font-semibold text-gray-700 border-b-2 border-gray-300 w-20">Action</th>
                            </tr>
                        </thead>
                        <tbody id="${categoryId}_tbody">
                            <tr>
                                <td colspan="4" class="px-3 py-4 text-center text-gray-500 text-sm italic">No items added yet. Click "Add Item" or "Add Multiple Items" to add budget items.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 pt-3 border-t border-gray-300">
                    <div class="flex justify-between items-center mb-3">
                        <button type="button" onclick="addSingleItemToCategory('${categoryName}')" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add Item
                        </button>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="font-semibold text-gray-700">Category Sub-Total:</span>
                        <span class="font-bold text-maroon" id="${categoryId}_subtotal">₱0.00</span>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    container.appendChild(categoryDiv);
    
    // Collapse all other categories
    collapseAllCategoriesExcept(categoryId);
    
    closeAddCategoryModal();
}

function toggleCategory(categoryId) {
    const content = document.getElementById(`${categoryId}_content`);
    const toggle = document.getElementById(`${categoryId}_toggle`);
    
    if (content.classList.contains('collapsed')) {
        // Expand this category
        content.classList.remove('collapsed');
        toggle.classList.remove('collapsed');
    } else {
        // Collapse this category
        content.classList.add('collapsed');
        toggle.classList.add('collapsed');
    }
}

function collapseAllCategoriesExcept(exceptId) {
    for (const [categoryName, categoryData] of Object.entries(categories)) {
        if (categoryData.id !== exceptId) {
            const content = document.getElementById(`${categoryData.id}_content`);
            const toggle = document.getElementById(`${categoryData.id}_toggle`);
            if (content && toggle) {
                content.classList.add('collapsed');
                toggle.classList.add('collapsed');
            }
        }
    }
}

let currentBulkCategory = null;

function addSingleItemToCategory(categoryName) {
    const categoryData = categories[categoryName];
    if (!categoryData) {
        alert('Category not found');
        return;
    }
    
    const tbody = document.getElementById(`${categoryData.id}_tbody`);
    
    // Remove "no items" message if it exists
    const noItemsRow = tbody.querySelector('td[colspan="4"]');
    if (noItemsRow) {
        noItemsRow.parentElement.remove();
    }
    
    budgetItemCounter++;
    categoryData.items.push(budgetItemCounter);
    
    const row = document.createElement('tr');
    row.id = `item${budgetItemCounter}`;
    row.className = 'hover:bg-gray-50 border-b border-gray-200';
    row.innerHTML = `
        <input type="hidden" name="category[]" value="${categoryName}">
        <td class="px-3 py-2">
            <input type="text" name="particulars[]" value="" class="particulars-table-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon" data-item-id="${budgetItemCounter}" data-category="${categoryName}" placeholder="Enter description" onchange="handleParticularsChange(this, ${budgetItemCounter})">
        </td>
        <td class="px-3 py-2 relative">
            <input type="text" name="account_code[]" value="" class="account-code-table-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon bg-gray-50" data-item-id="${budgetItemCounter}" readonly title="Auto-filled based on Particulars">
            <div id="table-autocomplete-${budgetItemCounter}" class="autocomplete-dropdown"></div>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="amount[]" value="" class="amount-display-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon text-right" data-category="${categoryName}" placeholder="₱0.00" data-item-id="${budgetItemCounter}">
            <input type="hidden" name="amount_raw[]" value="0" class="amount-raw-input" data-item-id="${budgetItemCounter}">
        </td>
        <td class="px-3 py-2 text-center">
            <button type="button" onclick="removeTableItem(${budgetItemCounter}, '${categoryName}')" class="text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    `;
    tbody.appendChild(row);
    
    // Setup amount formatting for this row
    setupAmountFormatting(budgetItemCounter, categoryName);
    
    // Focus on the particulars input
    setTimeout(() => {
        const particularsInput = row.querySelector('.particulars-table-input');
        if (particularsInput) {
            particularsInput.focus();
        }
    }, 100);
}

function showBulkEntryModal(categoryName) {
    currentBulkCategory = categoryName;
    document.getElementById('bulkCategoryName').textContent = categoryName;
    document.getElementById('bulkEntryTextarea').value = '';
    document.getElementById('bulkPreviewContainer').classList.add('hidden');
    document.getElementById('bulkEntryModal').classList.remove('hidden');
    
    // Setup real-time preview
    const textarea = document.getElementById('bulkEntryTextarea');
    textarea.addEventListener('input', updateBulkPreview);
}

function closeBulkEntryModal() {
    document.getElementById('bulkEntryModal').classList.add('hidden');
    currentBulkCategory = null;
}

function updateBulkPreview() {
    const textarea = document.getElementById('bulkEntryTextarea');
    const lines = textarea.value.split('\n').filter(line => line.trim() !== '');
    const previewContainer = document.getElementById('bulkPreviewContainer');
    const tbody = document.getElementById('bulkPreviewTableBody');
    
    if (lines.length === 0) {
        previewContainer.classList.add('hidden');
        return;
    }
    
    previewContainer.classList.remove('hidden');
    tbody.innerHTML = '';
    
    lines.forEach((line, index) => {
        const trimmedLine = line.trim();
        const results = searchUACSCode(currentBulkCategory, trimmedLine);
        const suggestedCode = results.length > 0 ? results[0].code : '';
        
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';
        row.innerHTML = `
            <td class="px-4 py-2 text-sm text-gray-900">${trimmedLine}</td>
            <td class="px-4 py-2 text-sm ${suggestedCode ? 'text-maroon font-semibold' : 'text-gray-400'}">${suggestedCode || 'No match found'}</td>
            <td class="px-4 py-2 text-sm text-right">
                <input type="number" step="0.01" min="0" class="bulk-amount-input w-24 px-2 py-1 border border-gray-300 rounded text-sm text-right" placeholder="0.00" data-index="${index}">
            </td>
        `;
        tbody.appendChild(row);
    });
}

function processBulkEntry() {
    const textarea = document.getElementById('bulkEntryTextarea');
    const lines = textarea.value.split('\n').filter(line => line.trim() !== '');
    
    if (lines.length === 0) {
        alert('Please enter at least one item');
        return;
    }
    
    const categoryData = categories[currentBulkCategory];
    if (!categoryData) {
        alert('Category not found');
        return;
    }
    
    const tbody = document.getElementById(`${categoryData.id}_tbody`);
    
    // Remove "no items" message if it exists
    const noItemsRow = tbody.querySelector('td[colspan="4"]');
    if (noItemsRow) {
        noItemsRow.parentElement.remove();
    }
    
    lines.forEach((line, index) => {
        const trimmedLine = line.trim();
        const results = searchUACSCode(currentBulkCategory, trimmedLine);
        const suggestedCode = results.length > 0 ? results[0].code : '';
        const amountInput = document.querySelector(`.bulk-amount-input[data-index="${index}"]`);
        const amount = amountInput ? amountInput.value : '0.00';
        
        budgetItemCounter++;
        categoryData.items.push(budgetItemCounter);
        
        const row = document.createElement('tr');
        row.id = `item${budgetItemCounter}`;
        row.className = 'hover:bg-gray-50 border-b border-gray-200';
        row.innerHTML = `
            <input type="hidden" name="category[]" value="${currentBulkCategory}">
            <td class="px-3 py-2">
                <input type="text" name="particulars[]" value="${trimmedLine}" class="particulars-table-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon" data-item-id="${budgetItemCounter}" data-category="${currentBulkCategory}" onchange="handleParticularsChange(this, ${budgetItemCounter})">
            </td>
            <td class="px-3 py-2 relative">
                <input type="text" name="account_code[]" value="${suggestedCode}" class="account-code-table-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon bg-gray-50" data-item-id="${budgetItemCounter}" readonly title="Auto-filled based on Particulars">
                <div id="table-autocomplete-${budgetItemCounter}" class="autocomplete-dropdown"></div>
            </td>
            <td class="px-3 py-2">
                <input type="text" name="amount[]" value="${amount && parseFloat(amount) > 0 ? '₱' + parseFloat(amount).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : ''}" class="amount-display-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon text-right" data-category="${currentBulkCategory}" data-item-id="${budgetItemCounter}" placeholder="₱0.00">
                <input type="hidden" name="amount_raw[]" value="${amount || '0'}" class="amount-raw-input" data-item-id="${budgetItemCounter}">
            </td>
            <td class="px-3 py-2 text-center">
                <button type="button" onclick="removeTableItem(${budgetItemCounter}, '${currentBulkCategory}')" class="text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </td>
        `;
        tbody.appendChild(row);
        
        // Setup real-time autocomplete for this row
        setupTableRowAutocomplete(budgetItemCounter, currentBulkCategory);
        // Setup amount formatting for this row
        setupAmountFormatting(budgetItemCounter, currentBulkCategory);
    });
    
    updateTotals();
    closeBulkEntryModal();
}

function setupTableRowAutocomplete(itemId, categoryName) {
    // Use setTimeout to ensure DOM is ready
    setTimeout(() => {
        const particularsInput = document.querySelector(`input.particulars-table-input[data-item-id="${itemId}"]`);
        const accountCodeInput = document.querySelector(`input.account-code-table-input[data-item-id="${itemId}"]`);
        
        if (!particularsInput || !accountCodeInput) {
            console.error('Could not find inputs for item', itemId);
            return;
        }
        
        let debounceTimer;
        
        particularsInput.addEventListener('input', function(e) {
            clearTimeout(debounceTimer);
            const searchText = this.value.trim();
            
            console.log('Input event triggered:', searchText); // Debug log
            
            // If user has already selected a code and is just modifying the text
            if (particularsInput.dataset.selectedCode && searchText.length > 0) {
                const baseKeyword = particularsInput.dataset.baseKeyword || '';
                // Check if the text still contains the base keyword
                if (baseKeyword && searchText.toLowerCase().includes(baseKeyword.toLowerCase())) {
                    // Keep the same code, user is just modifying
                    accountCodeInput.value = particularsInput.dataset.selectedCode;
                    return;
                } else {
                    // User changed to something completely different, reset
                    particularsInput.dataset.selectedCode = '';
                    particularsInput.dataset.baseKeyword = '';
                    accountCodeInput.value = '';
                }
            }
            
            if (searchText.length < 2) {
                if (!particularsInput.dataset.selectedCode) {
                    accountCodeInput.value = '';
                }
                return;
            }
            
            debounceTimer = setTimeout(() => {
                const results = searchUACSCode(categoryName, searchText);
                console.log('Search results:', results); // Debug log
                
                if (results.length > 0) {
                    accountCodeInput.value = results[0].code;
                    // Store the base keyword and code for this selection
                    const baseKeyword = results[0].keywords[0]; // Use first keyword as base
                    particularsInput.dataset.selectedCode = results[0].code;
                    particularsInput.dataset.baseKeyword = baseKeyword;
                    console.log('Set code:', results[0].code); // Debug log
                } else {
                    accountCodeInput.value = '';
                    console.log('No results found'); // Debug log
                }
            }, 300);
        });
        
        console.log('Autocomplete setup complete for item', itemId); // Debug log
    }, 100);
}

function removeTableItem(itemId, categoryName) {
    const item = document.getElementById(`item${itemId}`);
    if (item) {
        item.remove();
        
        // Update category items array
        const categoryData = categories[categoryName];
        if (categoryData) {
            categoryData.items = categoryData.items.filter(id => id !== itemId);
            
            // Show "no items" message if category is empty
            const tbody = document.getElementById(`${categoryData.id}_tbody`);
            if (categoryData.items.length === 0) {
                tbody.innerHTML = '<tr><td colspan="4" class="px-3 py-4 text-center text-gray-500 text-sm italic">No items added yet. Click "Add Items" to add budget items.</td></tr>';
            }
        }
        
        updateTotals();
    }
}

function removeCategory(categoryName) {
    if (!confirm(`Remove entire category "${categoryName}" and all its items?`)) {
        return;
    }
    
    const categoryData = categories[categoryName];
    if (categoryData) {
        document.getElementById(categoryData.id).remove();
        delete categories[categoryName];
        updateGrandTotal();
    }
}

function addItemToCategory(categoryName) {
    budgetItemCounter++;
    const categoryData = categories[categoryName];
    const itemsContainer = document.getElementById(`${categoryData.id}_items`);
    
    // Remove "no items" message if it exists
    const noItemsMsg = itemsContainer.querySelector('p.text-gray-500');
    if (noItemsMsg) {
        noItemsMsg.remove();
    }
    
    const itemDiv = document.createElement('div');
    itemDiv.className = 'bg-white p-3 rounded-lg border border-gray-200';
    itemDiv.id = `item${budgetItemCounter}`;
    
    itemDiv.innerHTML = `
        <input type="hidden" name="category[]" value="${categoryName}">
        <div class="flex justify-between items-start mb-2">
            <span class="text-xs font-semibold text-gray-600">Item #${budgetItemCounter}</span>
            <button type="button" onclick="removeItem(${budgetItemCounter}, '${categoryName}')" class="text-red-600 hover:text-red-800">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>
        <div class="space-y-2">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Particulars</label>
                <input type="text" name="particulars[]" required class="particulars-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon" placeholder="Description" data-item-id="${budgetItemCounter}" data-category="${categoryName}">
            </div>
            <div class="grid grid-cols-2 gap-2">
                <div class="relative">
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Account Code</label>
                    <input type="text" name="account_code[]" required class="account-code-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon bg-gray-50" placeholder="5 01 02 100 01" data-item-id="${budgetItemCounter}" readonly title="Auto-filled based on Particulars">
                    <div id="autocomplete-${budgetItemCounter}" class="autocomplete-dropdown"></div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Amount</label>
                    <input type="number" name="amount[]" required step="0.01" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon amount-input" placeholder="0.00" data-category="${categoryName}" oninput="updateTotals()">
                </div>
            </div>
        </div>
    `;
    
    itemsContainer.appendChild(itemDiv);
    categoryData.items.push(budgetItemCounter);
    
    // Setup autocomplete for this item
    setupAutocomplete(budgetItemCounter, categoryName);
}

function removeItem(itemId, categoryName) {
    const item = document.getElementById(`item${itemId}`);
    if (item) {
        item.remove();
        
        // Update category items array
        const categoryData = categories[categoryName];
        if (categoryData) {
            categoryData.items = categoryData.items.filter(id => id !== itemId);
            
            // Show "no items" message if category is empty
            const itemsContainer = document.getElementById(`${categoryData.id}_items`);
            if (categoryData.items.length === 0) {
                itemsContainer.innerHTML = '<p class="text-gray-500 text-sm italic">No items added yet. Click "Add Item" to add budget items.</p>';
            }
        }
        
        updateTotals();
    }
}

function updateTotals() {
    let grandTotal = 0;
    
    // Update each category subtotal
    for (const [categoryName, categoryData] of Object.entries(categories)) {
        let categoryTotal = 0;
        const rawInputs = document.querySelectorAll(`input.amount-raw-input`);
        
        rawInputs.forEach(input => {
            const row = input.closest('tr');
            if (row) {
                const categoryInput = row.querySelector('input[name="category[]"]');
                if (categoryInput && categoryInput.value === categoryName) {
                    const value = parseFloat(input.value) || 0;
                    categoryTotal += value;
                }
            }
        });
        
        grandTotal += categoryTotal;
        
        const subtotalElement = document.getElementById(`${categoryData.id}_subtotal`);
        if (subtotalElement) {
            subtotalElement.textContent = '₱' + categoryTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        }
    }
    
    document.getElementById('grandTotal').textContent = '₱' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

// Format amount input with peso sign and thousand separators
function setupAmountFormatting(itemId, categoryName) {
    setTimeout(() => {
        const displayInput = document.querySelector(`input.amount-display-input[data-item-id="${itemId}"]`);
        const rawInput = document.querySelector(`input.amount-raw-input[data-item-id="${itemId}"]`);
        
        if (!displayInput || !rawInput) return;
        
        displayInput.addEventListener('focus', function() {
            // On focus, show raw number for editing
            const rawValue = rawInput.value;
            if (rawValue && rawValue !== '0') {
                this.value = rawValue;
            } else {
                this.value = '';
            }
        });
        
        displayInput.addEventListener('blur', function() {
            // On blur, format with peso sign and commas
            let value = this.value.replace(/[₱,]/g, '').trim();
            const numValue = parseFloat(value) || 0;
            rawInput.value = numValue;
            
            if (numValue > 0) {
                this.value = '₱' + numValue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            } else {
                this.value = '';
            }
            
            updateTotals();
        });
        
        displayInput.addEventListener('input', function() {
            // Allow only numbers, decimal point, and comma while typing
            let value = this.value.replace(/[^0-9.]/g, '');
            // Prevent multiple decimal points
            const parts = value.split('.');
            if (parts.length > 2) {
                value = parts[0] + '.' + parts.slice(1).join('');
            }
            this.value = value;
        });
        
        displayInput.addEventListener('keypress', function(e) {
            // Allow: backspace, delete, tab, escape, enter, decimal point
            if ([46, 8, 9, 27, 13, 110, 190].indexOf(e.keyCode) !== -1 ||
                // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X
                (e.keyCode === 65 && e.ctrlKey === true) ||
                (e.keyCode === 67 && e.ctrlKey === true) ||
                (e.keyCode === 86 && e.ctrlKey === true) ||
                (e.keyCode === 88 && e.ctrlKey === true)) {
                return;
            }
            // Ensure that it is a number and stop the keypress
            if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
                e.preventDefault();
            }
        });
    }, 100);
}

function updateGrandTotal() {
    updateTotals();
}

// Submit LIB form
document.getElementById('libForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Form submit event triggered'); // Debug log
    
    // Check if marking as final
    const markAsFinalCheckbox = document.getElementById('markAsFinal');
    const isMarkingAsFinal = markAsFinalCheckbox && markAsFinalCheckbox.checked;
    
    console.log('Mark as final checkbox:', !!markAsFinalCheckbox, 'checked:', isMarkingAsFinal); // Debug log
    
    if (isMarkingAsFinal) {
        // Only check for existing FINAL LIBs when marking as FINAL
        const fiscalYear = document.getElementById('fiscalYear').value || new Date().getFullYear();
        const libId = document.getElementById('libId').value; // Current LIB ID (if editing)
        
        console.log('Checking for existing FINAL LIBs for fiscal year:', fiscalYear, 'excluding LIB ID:', libId); // Debug log
        
        // Build URL with exclude parameter if editing existing LIB
        let checkUrl = `../api/check_existing_final_lib.php?fiscal_year=${fiscalYear}`;
        if (libId) {
            checkUrl += `&exclude_lib_id=${libId}`;
        }
        
        fetch(checkUrl)
            .then(response => response.json())
            .then(data => {
                console.log('Check existing FINAL LIB response:', data); // Debug log
                if (data.success) {
                    if (data.has_existing_final_lib) {
                        console.log('Existing FINAL LIB found, showing modal'); // Debug log
                        // Show warning modal - there are existing FINAL LIBs
                        showLIBReplaceModal();
                    } else {
                        console.log('No existing FINAL LIB, saving directly'); // Debug log
                        // No existing FINAL LIBs, save directly
                        submitLIBForm();
                    }
                } else {
                    console.error('Error checking existing LIBs:', data.message);
                    // If check fails, save anyway (user is marking as final)
                    submitLIBForm();
                }
            })
            .catch(error => {
                console.error('Error checking existing LIBs:', error);
                // If check fails, save anyway (user is marking as final)
                submitLIBForm();
            });
    } else {
        console.log('Saving as DRAFT - no replacement check needed'); // Debug log
        // Saving as DRAFT - always save directly (drafts don't replace FINAL LIBs)
        submitLIBForm();
    }
});

function submitLIBForm() {
    console.log('submitLIBForm called - starting form submission'); // Debug log
    
    // Check if form exists
    const form = document.getElementById('libForm');
    if (!form) {
        console.error('Form not found!');
        alert('Error: Form not found');
        return;
    }
    
    console.log('Form found, processing amounts...'); // Debug log
    
    // Update all raw values before submitting
    document.querySelectorAll('.amount-display-input').forEach(displayInput => {
        const itemId = displayInput.dataset.itemId;
        const rawInput = document.querySelector(`input.amount-raw-input[data-item-id="${itemId}"]`);
        if (rawInput) {
            let value = displayInput.value.replace(/[₱,]/g, '').trim();
            const numValue = parseFloat(value) || 0;
            rawInput.value = numValue;
        }
    });
    
    const formData = new FormData(form);
    
    // Replace amount[] with amount_raw[] values
    formData.delete('amount[]');
    const rawAmounts = document.querySelectorAll('input[name="amount_raw[]"]');
    rawAmounts.forEach(input => {
        formData.append('amount[]', input.value);
    });
    
    const libId = document.getElementById('libId').value;
    const url = libId ? '../api/update_lib.php' : '../api/create_lib.php';
    
    console.log('Submitting to:', url, 'with libId:', libId); // Debug log
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status); // Debug log
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data); // Debug log
        if (data.success) {
            alert(data.message);
            closeLIBModal();
            loadLIBList(); // Refresh the current LIB display
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the LIB');
    });
}

function showLIBReplaceModal() {
    console.log('showLIBReplaceModal called - creating modal'); // Debug log
    
    // Create modal HTML
    const modalHTML = `
        <div id="libReplaceModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4">
                <div class="bg-orange-500 px-6 py-4 rounded-t-lg">
                    <h3 class="text-lg font-bold text-white flex items-center">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Replace Existing LIB
                    </h3>
                </div>
                <div class="p-6">
                    <p class="text-gray-700 mb-4">
                        You already have a FINAL LIB for this fiscal year. Saving this LIB as FINAL will replace the existing one.
                    </p>
                    <p class="text-gray-700 font-medium">
                        Are you sure you want to continue?
                    </p>
                </div>
                <div class="bg-gray-50 px-6 py-4 rounded-b-lg flex justify-end gap-3">
                    <button type="button" id="libReplaceCancelBtn" class="px-4 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="button" id="libReplaceConfirmBtn" class="px-4 py-2 bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        Yes, Replace
                    </button>
                </div>
            </div>
        </div>
    `;
    
    // Add modal to page
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    console.log('Modal HTML added to DOM'); // Debug log
    
    // Add event listeners after modal is added to DOM
    const cancelBtn = document.getElementById('libReplaceCancelBtn');
    const confirmBtn = document.getElementById('libReplaceConfirmBtn');
    
    console.log('Cancel button found:', !!cancelBtn); // Debug log
    console.log('Confirm button found:', !!confirmBtn); // Debug log
    
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            console.log('Cancel clicked'); // Debug log
            closeLIBReplaceModal();
        });
    }
    
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function() {
            console.log('Confirm clicked - starting submission'); // Debug log
            closeLIBReplaceModal();
            
            // Add a small delay to ensure modal is closed before submitting
            setTimeout(() => {
                console.log('About to call submitLIBForm'); // Debug log
                submitLIBForm(); // Call submitLIBForm directly
            }, 100);
        });
    } else {
        console.error('Confirm button not found!'); // Debug log
    }
}

function closeLIBReplaceModal() {
    console.log('closeLIBReplaceModal called'); // Debug log
    const modal = document.getElementById('libReplaceModal');
    if (modal) {
        modal.remove();
        console.log('Modal removed'); // Debug log
    }
}

function confirmLIBReplace() {
    console.log('confirmLIBReplace called'); // Debug log
    closeLIBReplaceModal();
    if (window.libReplaceConfirmCallback) {
        console.log('Executing callback'); // Debug log
        window.libReplaceConfirmCallback();
    } else {
        console.log('No callback found'); // Debug log
    }
}

function editLIB(id) {
    fetch(`../api/get_lib_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('modalTitle').textContent = 'Edit Line Item Budget';
                document.getElementById('libId').value = data.lib.id;
                document.getElementById('fiscalYear').value = data.lib.fiscal_year;
                document.getElementById('fundType').value = data.lib.fund_type;
                
                // Set checkbox state based on current status
                const markAsFinalCheckbox = document.getElementById('markAsFinal');
                if (markAsFinalCheckbox) {
                    markAsFinalCheckbox.checked = (data.lib.status === 'approved');
                }
                
                document.getElementById('categoriesContainer').innerHTML = '';
                categories = {};
                categoryCounter = 0;
                budgetItemCounter = 0;
                
                // Group items by category
                const itemsByCategory = {};
                data.items.forEach(item => {
                    if (!itemsByCategory[item.category]) {
                        itemsByCategory[item.category] = [];
                    }
                    itemsByCategory[item.category].push(item);
                });
                
                // Add each category and its items
                for (const [categoryName, items] of Object.entries(itemsByCategory)) {
                    addCategory(categoryName);
                    
                    // Add items to the category using the new table layout
                    items.forEach(item => {
                        const categoryData = categories[categoryName];
                        if (!categoryData) return;
                        
                        const tbody = document.getElementById(`${categoryData.id}_tbody`);
                        
                        // Remove "no items" message if it exists
                        const noItemsRow = tbody.querySelector('td[colspan="4"]');
                        if (noItemsRow) {
                            noItemsRow.parentElement.remove();
                        }
                        
                        budgetItemCounter++;
                        categoryData.items.push(budgetItemCounter);
                        
                        const row = document.createElement('tr');
                        row.id = `item${budgetItemCounter}`;
                        row.className = 'hover:bg-gray-50 border-b border-gray-200';
                        
                        const amount = parseFloat(item.amount);
                        const formattedAmount = amount > 0 ? '₱' + amount.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2}) : '';
                        
                        row.innerHTML = `
                            <input type="hidden" name="category[]" value="${categoryName}">
                            <td class="px-3 py-2">
                                <input type="text" name="particulars[]" value="${item.particulars}" class="particulars-table-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon" data-item-id="${budgetItemCounter}" data-category="${categoryName}" placeholder="Enter description" onchange="handleParticularsChange(this, ${budgetItemCounter})">
                            </td>
                            <td class="px-3 py-2 relative">
                                <input type="text" name="account_code[]" value="${item.account_code}" class="account-code-table-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon bg-gray-50" data-item-id="${budgetItemCounter}" readonly title="Auto-filled based on Particulars">
                                <div id="table-autocomplete-${budgetItemCounter}" class="autocomplete-dropdown"></div>
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="amount[]" value="${formattedAmount}" class="amount-display-input w-full px-2 py-1 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-maroon text-right" data-category="${categoryName}" placeholder="₱0.00" data-item-id="${budgetItemCounter}">
                                <input type="hidden" name="amount_raw[]" value="${amount}" class="amount-raw-input" data-item-id="${budgetItemCounter}">
                            </td>
                            <td class="px-3 py-2 text-center">
                                <button type="button" onclick="removeTableItem(${budgetItemCounter}, '${categoryName}')" class="text-red-600 hover:text-red-800">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </td>
                        `;
                        tbody.appendChild(row);
                        
                        // Setup amount formatting for this row
                        setupAmountFormatting(budgetItemCounter, categoryName);
                        
                        // Store the selected code for autocomplete
                        const particularsInput = row.querySelector('.particulars-table-input');
                        if (particularsInput && item.account_code) {
                            particularsInput.dataset.selectedCode = item.account_code;
                        }
                    });
                }
                
                updateTotals();
                document.getElementById('libModal').classList.remove('hidden');
            } else {
                alert('Error loading LIB: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the LIB');
        });
}

function deleteLIB(id) {
    if (!confirm('Are you sure you want to delete this LIB? This action cannot be undone.')) {
        return;
    }
    
    fetch('../api/delete_lib.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ id: id })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Clear utilization localStorage if requested
            if (data.clear_utilization && data.department_id && data.fiscal_year) {
                clearUtilizationLocalStorage(data.department_id, data.fiscal_year);
            }
            alert(data.message);
            loadLIBList();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the LIB');
    });
}

function clearUtilizationLocalStorage(departmentId, fiscalYear) {
    // Get current user ID from session (you may need to pass this from PHP)
    const userId = <?php echo $_SESSION['user_id'] ?? 0; ?>;
    
    // Clear all utilization-related localStorage keys for this department and fiscal year
    const keysToRemove = [
        `utilization_data_user_${userId}_dept_${departmentId}_year_${fiscalYear}`,
        `deductions_data_user_${userId}_dept_${departmentId}_year_${fiscalYear}`,
        `pr_data_user_${userId}_dept_${departmentId}_year_${fiscalYear}`,
        `travels_data_user_${userId}_dept_${departmentId}_year_${fiscalYear}`,
        `honoraria_data_user_${userId}_dept_${departmentId}_year_${fiscalYear}`
    ];
    
    keysToRemove.forEach(key => {
        localStorage.removeItem(key);
        console.log(`✓ Cleared localStorage: ${key}`);
    });
    
    console.log(`✓ All utilization data cleared for department ${departmentId}, fiscal year ${fiscalYear}`);
}

function viewLIB(id) {
    fetch(`../api/get_lib_details.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const content = generateLIBView(data.lib, data.items, data.department, false);
                document.getElementById('libPrintContent').innerHTML = content;
                document.getElementById('viewLIBModal').classList.remove('hidden');
            } else {
                alert('Error loading LIB: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the LIB');
        });
}

function closeViewLIBModal() {
    document.getElementById('viewLIBModal').classList.add('hidden');
}

function downloadLIBPDF(libId) {
    // Open the PDF download in a new window/tab
    window.open(`../api/download_lib_pdf.php?id=${libId}`, '_blank');
}

function printLIB() {
    window.print();
}

function togglePrintMenu() {
    const menu = document.getElementById('printMenu');
    if (menu) {
        menu.classList.toggle('hidden');
    }
}

function downloadAsPDF() {
    // Trigger the browser's print dialog with PDF as default
    // Most modern browsers will show "Save as PDF" option in print dialog
    window.print();
}

// Close print menu when clicking outside
document.addEventListener('click', function(event) {
    const menu = document.getElementById('printMenu');
    const button = event.target.closest('button[onclick="togglePrintMenu()"]');
    if (menu && !button && !menu.contains(event.target)) {
        menu.classList.add('hidden');
    }
});

// Load LIB list on page load
document.addEventListener('DOMContentLoaded', function() {
    // Load with default year filter (2026)
    const defaultYear = document.getElementById('yearFilter').value;
    loadLIBList(defaultYear);
    
    // Setup event delegation for table row autocomplete
    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('particulars-table-input')) {
            handleTableParticularsInput(e.target);
        }
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.particulars-table-input') && !e.target.closest('.autocomplete-dropdown')) {
            document.querySelectorAll('.autocomplete-dropdown').forEach(dropdown => {
                dropdown.style.display = 'none';
            });
        }
    });
    
    // Reposition dropdowns on scroll
    document.addEventListener('scroll', function() {
        document.querySelectorAll('.autocomplete-dropdown').forEach(dropdown => {
            if (dropdown.style.display === 'block') {
                const itemId = dropdown.id.replace('table-autocomplete-', '');
                const input = document.querySelector(`input.particulars-table-input[data-item-id="${itemId}"]`);
                if (input) {
                    positionDropdown(dropdown, input);
                }
            }
        });
    }, true);
});

function handleTableParticularsInput(input) {
    const itemId = input.dataset.itemId;
    const categoryName = input.dataset.category;
    const accountCodeInput = document.querySelector(`input.account-code-table-input[data-item-id="${itemId}"]`);
    const dropdown = document.getElementById(`table-autocomplete-${itemId}`);
    
    if (!accountCodeInput || !dropdown) return;
    
    const searchText = input.value.trim();
    
    // If user has already selected a code and is just modifying the text
    if (input.dataset.selectedCode && searchText.length > 0) {
        const baseKeyword = input.dataset.baseKeyword || '';
        if (baseKeyword && searchText.toLowerCase().includes(baseKeyword.toLowerCase())) {
            accountCodeInput.value = input.dataset.selectedCode;
            dropdown.style.display = 'none';
            return;
        } else {
            input.dataset.selectedCode = '';
            input.dataset.baseKeyword = '';
            accountCodeInput.value = '';
        }
    }
    
    if (searchText.length < 2) {
        dropdown.style.display = 'none';
        if (!input.dataset.selectedCode) {
            accountCodeInput.value = '';
        }
        return;
    }
    
    // Clear any existing timeout
    if (input.debounceTimer) {
        clearTimeout(input.debounceTimer);
    }
    
    input.debounceTimer = setTimeout(() => {
        const results = searchUACSCode(categoryName, searchText);
        displayTableAutocompleteResults(results, dropdown, accountCodeInput, input);
    }, 300);
}

function displayTableAutocompleteResults(results, dropdown, accountCodeInput, particularsInput) {
    if (results.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item" style="color: #9ca3af;">No matching UACS codes found</div>';
        dropdown.style.display = 'block';
        positionDropdown(dropdown, particularsInput);
        return;
    }
    
    dropdown.innerHTML = '';
    results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'autocomplete-item';
        div.innerHTML = `
            <div class="autocomplete-code">${item.code}</div>
            <div class="autocomplete-name">${item.name}</div>
        `;
        div.addEventListener('click', function() {
            accountCodeInput.value = item.code;
            particularsInput.value = item.name;
            
            // Store the base keyword and code for this selection
            const baseKeyword = item.keywords[0];
            particularsInput.dataset.selectedCode = item.code;
            particularsInput.dataset.baseKeyword = baseKeyword;
            
            dropdown.style.display = 'none';
            
            // Allow user to edit the particulars while keeping the code
            setTimeout(() => {
                particularsInput.focus();
                const len = particularsInput.value.length;
                particularsInput.setSelectionRange(len, len);
            }, 100);
        });
        dropdown.appendChild(div);
    });
    
    dropdown.style.display = 'block';
    positionDropdown(dropdown, particularsInput);
}

function positionDropdown(dropdown, inputElement) {
    // Get the position of the input element
    const rect = inputElement.getBoundingClientRect();
    
    // Position the dropdown below the input
    dropdown.style.position = 'fixed';
    dropdown.style.top = (rect.bottom + 2) + 'px';
    dropdown.style.left = rect.left + 'px';
    dropdown.style.width = Math.max(rect.width, 300) + 'px';
}

// Setup autocomplete for particulars input
function setupAutocomplete(itemId, categoryName) {
    const particularsInput = document.querySelector(`input.particulars-input[data-item-id="${itemId}"]`);
    const accountCodeInput = document.querySelector(`input.account-code-input[data-item-id="${itemId}"]`);
    const dropdown = document.getElementById(`autocomplete-${itemId}`);
    
    if (!particularsInput || !accountCodeInput || !dropdown) return;
    
    let debounceTimer;
    let selectedBaseCode = null; // Store the base UACS code when user selects from dropdown
    
    particularsInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const searchText = this.value.trim();
        
        // If user is modifying after selection, keep the code
        if (selectedBaseCode && searchText.length > 0) {
            // Check if the text still contains the base keyword
            const baseKeyword = selectedBaseCode.baseKeyword.toLowerCase();
            if (searchText.toLowerCase().includes(baseKeyword)) {
                // Keep the same code, user is just modifying the description
                return;
            } else {
                // User changed to something completely different, reset
                selectedBaseCode = null;
                accountCodeInput.value = '';
            }
        }
        
        if (searchText.length < 2) {
            dropdown.style.display = 'none';
            if (!selectedBaseCode) {
                accountCodeInput.value = '';
            }
            return;
        }
        
        debounceTimer = setTimeout(() => {
            const results = searchUACSCode(categoryName, searchText);
            displayAutocompleteResults(results, dropdown, accountCodeInput, particularsInput, itemId);
        }, 300);
    });
    
    // Store reference to selectedBaseCode for this item
    particularsInput.dataset.selectedBaseCode = '';
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!particularsInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

function displayAutocompleteResults(results, dropdown, accountCodeInput, particularsInput, itemId) {
    if (results.length === 0) {
        dropdown.innerHTML = '<div class="autocomplete-item" style="color: #9ca3af;">No matching UACS codes found</div>';
        dropdown.style.display = 'block';
        return;
    }
    
    dropdown.innerHTML = '';
    results.forEach(item => {
        const div = document.createElement('div');
        div.className = 'autocomplete-item';
        div.innerHTML = `
            <div class="autocomplete-code">${item.code}</div>
            <div class="autocomplete-name">${item.name}</div>
        `;
        div.addEventListener('click', function() {
            accountCodeInput.value = item.code;
            particularsInput.value = item.name;
            
            // Store the base keyword and code for this selection
            const baseKeyword = item.keywords[0]; // Use first keyword as base
            particularsInput.dataset.selectedCode = item.code;
            particularsInput.dataset.baseKeyword = baseKeyword;
            
            dropdown.style.display = 'none';
            
            // Allow user to edit the particulars while keeping the code
            setTimeout(() => {
                particularsInput.focus();
                // Move cursor to end
                const len = particularsInput.value.length;
                particularsInput.setSelectionRange(len, len);
            }, 100);
        });
        dropdown.appendChild(div);
    });
    
    dropdown.style.display = 'block';
}

// Modified setupAutocomplete to handle code persistence
function setupAutocomplete(itemId, categoryName) {
    const particularsInput = document.querySelector(`input.particulars-input[data-item-id="${itemId}"]`);
    const accountCodeInput = document.querySelector(`input.account-code-input[data-item-id="${itemId}"]`);
    const dropdown = document.getElementById(`autocomplete-${itemId}`);
    
    if (!particularsInput || !accountCodeInput || !dropdown) return;
    
    let debounceTimer;
    
    particularsInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const searchText = this.value.trim();
        
        // If user has already selected a code and is just modifying the text
        if (particularsInput.dataset.selectedCode && searchText.length > 0) {
            const baseKeyword = particularsInput.dataset.baseKeyword || '';
            // Check if the text still contains the base keyword
            if (baseKeyword && searchText.toLowerCase().includes(baseKeyword.toLowerCase())) {
                // Keep the same code, user is just modifying (e.g., "Honoraria - Civilian" to "Honoraria - Overload")
                accountCodeInput.value = particularsInput.dataset.selectedCode;
                return;
            } else {
                // User changed to something completely different, reset
                particularsInput.dataset.selectedCode = '';
                particularsInput.dataset.baseKeyword = '';
                accountCodeInput.value = '';
            }
        }
        
        if (searchText.length < 2) {
            dropdown.style.display = 'none';
            if (!particularsInput.dataset.selectedCode) {
                accountCodeInput.value = '';
            }
            return;
        }
        
        debounceTimer = setTimeout(() => {
            const results = searchUACSCode(categoryName, searchText);
            displayAutocompleteResults(results, dropdown, accountCodeInput, particularsInput, itemId);
        }, 300);
    });
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!particularsInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });
}

// ============================================
// AUTO-GENERATE LIB FUNCTIONS
// ============================================

function showAutoGenerateLIBModal() {
    document.getElementById('autoGenerateLIBModal').classList.remove('hidden');
    document.getElementById('autoGenYear').value = currentAutoGenYear;
}

function closeAutoGenerateLIBModal() {
    document.getElementById('autoGenerateLIBModal').classList.add('hidden');
    autoGeneratedItems = [];
    document.getElementById('autoGenPreview').classList.add('hidden');
    document.getElementById('saveAutoGenBtn').classList.add('hidden');
}

function generateAutoLIB() {
    const year = document.getElementById('autoGenYear').value;
    currentAutoGenYear = year;
    
    const formData = new FormData();
    formData.append('department_id', window.DEPARTMENT_ID);
    formData.append('year', year);
    
    fetch('../api/generate_auto_lib.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            autoGeneratedItems = data.items;
            displayAutoGeneratedItems();
            document.getElementById('autoGenPreview').classList.remove('hidden');
            document.getElementById('saveAutoGenBtn').classList.remove('hidden');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while generating LIB');
    });
}

function displayAutoGeneratedItems() {
    const tbody = document.getElementById('autoGenTableBody');
    tbody.innerHTML = '';
    
    let grandTotal = 0;
    
    autoGeneratedItems.forEach((item, index) => {
        const amount = parseFloat(item.total_amount);
        grandTotal += amount;
        
        // Search for UACS code if not already set (for allocation items)
        let uacsCode = item.uacs_code || '';
        let description = item.general_desc;
        let category = item.category || 'B. Maintenance & Other Operating Expenses';
        
        if (!item.is_custom && !uacsCode) {
            // Search for UACS code based on description
            const searchResults = searchUACSForDescription(description);
            uacsCode = searchResults.code || '';
            description = searchResults.name || description;
            
            // Update the item with found UACS code and proper description
            item.uacs_code = uacsCode;
            item.general_desc = description;
            
            // Determine category from UACS code
            if (uacsCode) {
                category = determineCategoryFromUACS(uacsCode);
                item.category = category;
            }
        }
        
        const sourceLabel = item.is_custom ? 
            '<span class="px-2 py-1 bg-blue-100 text-blue-800 text-xs font-semibold rounded">Custom</span>' :
            (item.source === 'ppmp' ? 
                '<span class="px-2 py-1 bg-purple-100 text-purple-800 text-xs font-semibold rounded">PPMP</span>' :
                '<span class="px-2 py-1 bg-green-100 text-green-800 text-xs font-semibold rounded">Allocation</span>');
        
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50 border-b border-gray-200';
        row.innerHTML = `
            <td class="px-4 py-2">${sourceLabel}</td>
            <td class="px-4 py-2 font-mono text-sm">${uacsCode || '<span class="text-gray-400">N/A</span>'}</td>
            <td class="px-4 py-2">${description}</td>
            <td class="px-4 py-2 text-right font-semibold">₱${amount.toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
            <td class="px-4 py-2 text-center">
                ${item.is_custom ? `
                    <button onclick="editCustomItem(${index})" class="text-blue-600 hover:text-blue-800 mr-2" title="Edit">
                        <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                    </button>
                    <button onclick="deleteCustomItem(${index})" class="text-red-600 hover:text-red-800" title="Delete">
                        <svg class="w-4 h-4 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                ` : (item.source === 'ppmp' ? 
                    '<span class="text-gray-400 text-xs">From PPMP</span>' : 
                    '<span class="text-gray-400 text-xs">From Allocation</span>')}
            </td>
        `;
        tbody.appendChild(row);
    });
    
    document.getElementById('autoGenGrandTotal').textContent = 
        '₱' + grandTotal.toLocaleString('en-US', {minimumFractionDigits: 2});
}

function showAddCustomItemModal() {
    document.getElementById('addCustomItemModal').classList.remove('hidden');
    document.getElementById('customItemForm').reset();
    delete document.getElementById('customItemForm').dataset.editIndex;
}

function closeAddCustomItemModal() {
    document.getElementById('addCustomItemModal').classList.add('hidden');
}

// Handle custom item form submission
document.getElementById('customItemForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const editIndex = this.dataset.editIndex;
    const customItem = {
        uacs_code: document.getElementById('customUACSCode').value,
        general_desc: document.getElementById('customDescription').value,
        total_amount: parseFloat(document.getElementById('customTotalAmount').value),
        quarter_1: parseFloat(document.getElementById('customQ1').value) || 0,
        quarter_2: parseFloat(document.getElementById('customQ2').value) || 0,
        quarter_3: parseFloat(document.getElementById('customQ3').value) || 0,
        quarter_4: parseFloat(document.getElementById('customQ4').value) || 0,
        source: 'custom',
        is_custom: true
    };
    
    if (editIndex !== undefined) {
        // Update existing item
        const existingItem = autoGeneratedItems[editIndex];
        if (existingItem.custom_item_id) {
            fetch('../api/update_lib_custom_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    custom_item_id: existingItem.custom_item_id,
                    ...customItem
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    customItem.custom_item_id = existingItem.custom_item_id;
                    autoGeneratedItems[editIndex] = customItem;
                    displayAutoGeneratedItems();
                    closeAddCustomItemModal();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating custom item');
            });
        } else {
            autoGeneratedItems[editIndex] = customItem;
            displayAutoGeneratedItems();
            closeAddCustomItemModal();
        }
    } else {
        // Add new item
        fetch('../api/add_lib_custom_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                department_id: window.DEPARTMENT_ID,
                year: currentAutoGenYear,
                ...customItem
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                customItem.custom_item_id = data.custom_item_id;
                autoGeneratedItems.push(customItem);
                displayAutoGeneratedItems();
                closeAddCustomItemModal();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while adding custom item');
        });
    }
});

function editCustomItem(index) {
    const item = autoGeneratedItems[index];
    // Populate form with item data
    document.getElementById('customUACSCode').value = item.uacs_code;
    document.getElementById('customDescription').value = item.general_desc;
    document.getElementById('customTotalAmount').value = item.total_amount;
    document.getElementById('customQ1').value = item.quarter_1 || 0;
    document.getElementById('customQ2').value = item.quarter_2 || 0;
    document.getElementById('customQ3').value = item.quarter_3 || 0;
    document.getElementById('customQ4').value = item.quarter_4 || 0;
    
    // Store index for update
    document.getElementById('customItemForm').dataset.editIndex = index;
    showAddCustomItemModal();
}

function deleteCustomItem(index) {
    const item = autoGeneratedItems[index];
    
    if (!confirm('Are you sure you want to delete this custom item?')) {
        return;
    }
    
    if (item.custom_item_id) {
        fetch('../api/delete_lib_custom_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                custom_item_id: item.custom_item_id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                autoGeneratedItems.splice(index, 1);
                displayAutoGeneratedItems();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting custom item');
        });
    } else {
        autoGeneratedItems.splice(index, 1);
        displayAutoGeneratedItems();
    }
}

function saveAutoGeneratedLIB() {
    if (autoGeneratedItems.length === 0) {
        alert('No items to save. Please generate LIB first.');
        return;
    }
    
    // Show confirmation
    if (!confirm('Save this auto-generated LIB? This will create a new LIB record.')) {
        return;
    }
    
    // Prepare FormData for LIB creation (create_lib.php expects POST arrays)
    const formData = new FormData();
    formData.append('fiscalYear', 'FY ' + currentAutoGenYear);
    formData.append('fundType', 'Internally Generated Fund');
    formData.append('markAsFinal', '0'); // Save as DRAFT, not final
    
    // Add items as arrays - use already searched UACS codes from items
    autoGeneratedItems.forEach(item => {
        // Use the UACS code and description that were already searched in displayAutoGeneratedItems
        const uacsCode = item.uacs_code || '';
        const uacsName = item.general_desc;
        const category = item.category || 'B. Maintenance & Other Operating Expenses';
        
        formData.append('category[]', category);
        formData.append('particulars[]', uacsName);
        formData.append('account_code[]', uacsCode);
        formData.append('amount[]', item.total_amount);
    });
    
    // Save to database
    fetch('../api/create_lib.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('LIB saved successfully!');
            closeAutoGenerateLIBModal();
            loadLIBList(); // Refresh the display
        } else {
            alert('Error saving LIB: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving LIB');
    });
}

// Search for UACS code based on description/keyword
function searchUACSForDescription(description) {
    if (!description || typeof UACS_CODES === 'undefined') {
        return { code: '', name: description };
    }
    
    const searchTerm = description.toLowerCase().trim();
    
    // Keyword mapping for common allocation terms
    const keywordMap = {
        'part-time': 'honoraria part',
        'overload': 'honoraria overload',
        'cos': 'labor wages',
        'water': 'water expenses',
        'electricity': 'electricity expenses',
        'electric': 'electricity expenses',
        'security': 'security',
        'labor': 'labor wages',
        'wages': 'labor wages',
        'internet': 'internet',
        'telephone': 'telephone',
        'supplies': 'office supplies',
        'materials': 'office supplies',
        'repair': 'repairs maintenance',
        'maintenance': 'repairs maintenance'
    };
    
    // Check if we have a keyword match
    let searchKey = searchTerm;
    for (const [keyword, replacement] of Object.entries(keywordMap)) {
        if (searchTerm.includes(keyword)) {
            searchKey = replacement;
            break;
        }
    }
    
    // Flatten UACS_CODES into a single array
    const allUACSCodes = [];
    for (const category in UACS_CODES) {
        if (UACS_CODES.hasOwnProperty(category)) {
            allUACSCodes.push(...UACS_CODES[category]);
        }
    }
    
    // Search through UACS codes
    let bestMatch = null;
    let bestScore = 0;
    
    for (const uacs of allUACSCodes) {
        const uacsName = uacs.name.toLowerCase();
        const uacsCode = uacs.code.toLowerCase();
        
        // Exact match
        if (uacsName === searchKey || uacsName === searchTerm) {
            return { code: uacs.code, name: uacs.name };
        }
        
        // Partial match
        if (uacsName.includes(searchKey) || uacsName.includes(searchTerm)) {
            const score = searchKey.length / uacsName.length;
            if (score > bestScore) {
                bestScore = score;
                bestMatch = uacs;
            }
        }
        
        // Check keywords in UACS name
        const keywords = searchKey.split(' ');
        let matchCount = 0;
        for (const keyword of keywords) {
            if (keyword.length > 2 && uacsName.includes(keyword)) {
                matchCount++;
            }
        }
        if (matchCount > 0) {
            const score = matchCount / keywords.length;
            if (score > bestScore) {
                bestScore = score;
                bestMatch = uacs;
            }
        }
    }
    
    if (bestMatch && bestScore > 0.3) {
        return { code: bestMatch.code, name: bestMatch.name };
    }
    
    return { code: '', name: description };
}

function determineCategoryFromUACS(uacsCode) {
    if (!uacsCode) return 'B. Maintenance & Other Operating Expenses';
    
    // Remove spaces and dashes for comparison
    const cleanCode = uacsCode.replace(/[\s-]/g, '');
    
    // Simple categorization based on UACS code patterns
    if (cleanCode.startsWith('501')) {
        return 'A. PERSONAL SERVICES';
    } else if (cleanCode.startsWith('502')) {
        return 'B. Maintenance & Other Operating Expenses';
    } else if (cleanCode.startsWith('506')) {
        return 'C. Capital Outlay';
    }
    return 'B. Maintenance & Other Operating Expenses'; // Default
}

// ============================================
// INLINE ADD ITEM FUNCTIONS
// ============================================

function showInlineAddItem(category, libId) {
    const categoryKey = category.replace(/[^a-zA-Z]/g, '');
    const row = document.getElementById(`addItemRow_${categoryKey}`);
    row.classList.remove('hidden');
    
    // Focus on particulars input
    document.getElementById(`newParticulars_${categoryKey}`).focus();
}

function cancelInlineAddItem(categoryKey) {
    const row = document.getElementById(`addItemRow_${categoryKey}`);
    row.classList.add('hidden');
    
    // Clear inputs
    document.getElementById(`newParticulars_${categoryKey}`).value = '';
    document.getElementById(`newAccountCode_${categoryKey}`).value = '';
    document.getElementById(`newAmount_${categoryKey}`).value = '';
    
    // Hide dropdown
    const dropdown = document.getElementById(`uacsDropdown_${categoryKey}`);
    if (dropdown) dropdown.classList.add('hidden');
}

function searchUACSInline(categoryKey) {
    const input = document.getElementById(`newParticulars_${categoryKey}`);
    const dropdown = document.getElementById(`uacsDropdown_${categoryKey}`);
    const searchText = input.value.trim();
    
    if (searchText.length < 2) {
        dropdown.classList.add('hidden');
        return;
    }
    
    // Get category from categoryKey
    let category = '';
    if (categoryKey === 'APERSONALSERVICES') {
        category = 'A. PERSONAL SERVICES';
    } else if (categoryKey === 'BMaintenanceOtherOperatingExpenses') {
        category = 'B. Maintenance & Other Operating Expenses';
    } else if (categoryKey === 'CCapitalOutlay') {
        category = 'C. Capital Outlay';
    }
    
    // Search UACS codes
    const results = searchUACSCode(category, searchText);
    
    if (results.length === 0) {
        dropdown.classList.add('hidden');
        return;
    }
    
    // Display results with red/maroon styled design
    dropdown.innerHTML = '';
    results.forEach(result => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-200';
        div.innerHTML = `
            <div class="font-semibold text-maroon">${escapeHtml(result.name)}</div>
            <div class="text-xs text-gray-600">${escapeHtml(result.code)}</div>
        `;
        div.onclick = function() {
            selectUACSInline(categoryKey, result.code, result.name);
        };
        dropdown.appendChild(div);
    });
    
    dropdown.classList.remove('hidden');
}

// Helper function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function selectUACSInline(categoryKey, code, name) {
    document.getElementById(`newParticulars_${categoryKey}`).value = name;
    document.getElementById(`newAccountCode_${categoryKey}`).value = code;
    document.getElementById(`uacsDropdown_${categoryKey}`).classList.add('hidden');
    
    // Check if this is "Other Maintenance and Operating Expenses"
    if (isOtherMaintenanceExpense(name)) {
        // Show sub-category section
        showInlineSubCategorySection(categoryKey);
    } else {
        // Hide sub-category section if it exists
        hideInlineSubCategorySection(categoryKey);
        // Focus on amount input
        document.getElementById(`newAmount_${categoryKey}`).focus();
    }
}

function saveInlineItem(category, libId) {
    const categoryKey = category.replace(/[^a-zA-Z]/g, '');
    
    const particulars = document.getElementById(`newParticulars_${categoryKey}`).value.trim();
    const accountCode = document.getElementById(`newAccountCode_${categoryKey}`).value.trim();
    const amount = parseFloat(document.getElementById(`newAmount_${categoryKey}`).value);
    
    if (!particulars) {
        alert('Please enter particulars');
        return;
    }
    
    if (!accountCode) {
        alert('Please select a UACS code');
        return;
    }
    
    // Get sub-categories if they exist
    const subCategories = getInlineSubCategories(categoryKey);
    
    // If there are sub-categories, validate them
    if (subCategories.length > 0) {
        const hasInvalidSub = subCategories.some(sub => !sub.name || sub.amount <= 0);
        if (hasInvalidSub) {
            alert('Please ensure all sub-categories have names and valid amounts');
            return;
        }
        
        // Calculate total from sub-categories
        const subTotal = subCategories.reduce((sum, sub) => sum + sub.amount, 0);
        
        // Validate that amount matches sub-category total
        if (Math.abs(amount - subTotal) > 0.01) {
            alert(`Amount mismatch: Parent amount (${amount}) should equal sub-category total (${subTotal})`);
            return;
        }
    } else {
        // No sub-categories, validate amount
        if (!amount || amount <= 0) {
            alert('Please enter a valid amount');
            return;
        }
    }
    
    // Save to database
    const formData = new FormData();
    formData.append('lib_id', libId);
    formData.append('category', category);
    formData.append('particulars', particulars);
    formData.append('account_code', accountCode);
    formData.append('amount', amount);
    
    // Add sub-categories if they exist
    if (subCategories.length > 0) {
        formData.append('sub_categories', JSON.stringify(subCategories));
    }
    
    console.log('Saving item:', {
        lib_id: libId,
        category: category,
        particulars: particulars,
        account_code: accountCode,
        amount: amount,
        sub_categories: subCategories
    });
    
    fetch('../api/add_lib_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                // Clear inline sub-categories data
                if (inlineSubCategoriesData[categoryKey]) {
                    delete inlineSubCategoriesData[categoryKey];
                }
                
                // Reload the LIB display
                displayCurrentLIB(libId);
                alert('Item added successfully!' + (data.sub_categories_count > 0 ? ` (${data.sub_categories_count} sub-categories)` : ''));
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            alert('Server error: Invalid response format. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while adding the item: ' + error.message);
    });
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    const dropdowns = document.querySelectorAll('[id^="uacsDropdown_"]');
    dropdowns.forEach(dropdown => {
        const categoryKey = dropdown.id.replace('uacsDropdown_', '');
        const input = document.getElementById(`newParticulars_${categoryKey}`);
        if (input && !input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.add('hidden');
        }
    });
});

// ============================================
// FINALIZE LIB FUNCTION
// ============================================

function finalizeLIB(libId) {
    // First, check if all linked PPMPs are finalized
    const checkFormData = new FormData();
    checkFormData.append('lib_id', libId);
    checkFormData.append('check_only', '1'); // Flag to only check, not finalize
    
    fetch('../api/finalize_lib.php', {
        method: 'POST',
        body: checkFormData
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            // Show error message if PPMPs are not finalized
            alert(data.message);
            return;
        }
        
        // If validation passed, show confirmation dialog
        if (!confirm('Are you sure you want to finalize this LIB?\n\nAt least one PPMP for this fiscal year has been verified as finalized.\n\nOnce finalized:\n- The LIB cannot be edited\n- It will be visible to Budget Office for utilization\n- This action cannot be undone')) {
            return;
        }
        
        // Proceed with finalization
        const formData = new FormData();
        formData.append('lib_id', libId);
        
        fetch('../api/finalize_lib.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('LIB has been finalized successfully!');
                loadLIBList(); // Refresh the display
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while finalizing the LIB');
        });
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while checking PPMP status');
    });
}

// ============================================
// EDIT AND DELETE LIB ITEM FUNCTIONS
// ============================================

function showEditItemRow(itemId, particulars, accountCode, amount, libId) {
    // Hide the display row and show the edit row
    document.getElementById(`itemRow_${itemId}`).classList.add('hidden');
    document.getElementById(`editItemRow_${itemId}`).classList.remove('hidden');
    
    // Focus on particulars input
    document.getElementById(`editParticulars_${itemId}`).focus();
}

function cancelEditItem(itemId) {
    // Show the display row and hide the edit row
    document.getElementById(`itemRow_${itemId}`).classList.remove('hidden');
    document.getElementById(`editItemRow_${itemId}`).classList.add('hidden');
    
    // Hide dropdown if visible
    const dropdown = document.getElementById(`editUacsDropdown_${itemId}`);
    if (dropdown) dropdown.classList.add('hidden');
}

function searchUACSForEdit(itemId) {
    const input = document.getElementById(`editParticulars_${itemId}`);
    const dropdown = document.getElementById(`editUacsDropdown_${itemId}`);
    const searchText = input.value.trim();
    
    if (searchText.length < 2) {
        dropdown.classList.add('hidden');
        return;
    }
    
    // Search UACS codes (reuse existing search function)
    const results = searchUACSCode('', searchText); // Search all categories
    
    if (results.length === 0) {
        dropdown.classList.add('hidden');
        return;
    }
    
    // Display results with red/maroon styled design (matching Add Item)
    dropdown.innerHTML = '';
    results.forEach(result => {
        const div = document.createElement('div');
        div.className = 'px-4 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-200';
        div.innerHTML = `
            <div class="font-semibold text-maroon">${escapeHtml(result.name)}</div>
            <div class="text-xs text-gray-600">${escapeHtml(result.code)}</div>
        `;
        div.onclick = function() {
            selectUACSForEdit(itemId, result.code, result.name);
        };
        dropdown.appendChild(div);
    });
    
    dropdown.classList.remove('hidden');
}

function selectUACSForEdit(itemId, code, name) {
    document.getElementById(`editParticulars_${itemId}`).value = name;
    document.getElementById(`editAccountCode_${itemId}`).value = code;
    document.getElementById(`editUacsDropdown_${itemId}`).classList.add('hidden');
    
    // Focus on amount input
    document.getElementById(`editAmount_${itemId}`).focus();
}

function saveEditedItem(itemId, libId) {
    const particulars = document.getElementById(`editParticulars_${itemId}`).value.trim();
    const accountCode = document.getElementById(`editAccountCode_${itemId}`).value.trim();
    const amount = parseFloat(document.getElementById(`editAmount_${itemId}`).value);
    
    if (!particulars) {
        alert('Please enter particulars');
        return;
    }
    
    if (!accountCode) {
        alert('Please select a UACS code');
        return;
    }
    
    if (!amount || amount <= 0) {
        alert('Please enter a valid amount');
        return;
    }
    
    // Update via API
    const formData = new FormData();
    formData.append('item_id', itemId);
    formData.append('particulars', particulars);
    formData.append('account_code', accountCode);
    formData.append('amount', amount);
    
    fetch('../api/update_lib_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Item updated successfully!');
            displayCurrentLIB(libId); // Refresh display
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating the item');
    });
}

function deleteLibItem(itemId, libId) {
    if (!confirm('Are you sure you want to delete this item?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('item_id', itemId);
    
    console.log('Deleting item:', itemId);
    
    fetch('../api/delete_lib_item.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Delete response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Delete response text:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                alert('Item deleted successfully!');
                displayCurrentLIB(libId); // Refresh display
            } else {
                alert('Error: ' + data.message);
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            alert('Server error: Invalid response format. Check console for details.');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        alert('An error occurred while deleting the item: ' + error.message);
    });
}

</script>

</body>
</html>

