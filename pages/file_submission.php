<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin', 'procurement'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';

function publicSubmissionPath(?string $filePath): string {
    if (!$filePath) {
        return '';
    }

    $normalized = str_replace('\\', '/', $filePath);
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));

    if ($projectRoot && strpos($normalized, $projectRoot) === 0) {
        $normalized = ltrim(substr($normalized, strlen($projectRoot)), '/');
    }

    $normalized = ltrim($normalized, '/');
    if (strpos($normalized, 'uploads/') !== 0) {
        return '';
    }

    return $normalized;
}

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Admin';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$activeSidebar = 'file_submission';
$userRole = $_SESSION['user_role'] ?? '';
$isProcurement = ($userRole === 'procurement');
$isBudget = in_array($userRole, ['budget', 'school_admin']);

// Get department name for procurement portal label
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;
if (!$departmentName && $isProcurement && isset($_SESSION['department_id'])) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($_SESSION['department_id']);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}
$portalLabel = $isProcurement 
    ? ($departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal")
    : "Administration Panel";

// Get filter parameters
$fiscal_year = isset($_GET['fiscal_year']) ? $_GET['fiscal_year'] : '';
$submission_type = isset($_GET['submission_type']) ? $_GET['submission_type'] : '';
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : '';

// Get data - exclude PR submissions
$fileSubmission = new FileSubmission();
$allSubmissions = $fileSubmission->getFilteredSubmissions(
    $fiscal_year ?: null,
    $submission_type ?: null,
    $department_id ?: null,
    200
);

// Filter out PR submissions
$submissions = array_filter($allSubmissions, function($submission) {
    return ($submission['submission_type'] ?? '') !== 'PR';
});
$departments = $fileSubmission->getDepartments();
$fiscal_years = $fileSubmission->getFiscalYears();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - File Submission</title>
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
        .file-name-text {
            display: inline-block;
            max-width: 180px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            vertical-align: middle;
        }

        @media (min-width: 1280px) {
            .file-name-text {
                max-width: 280px;
            }
        }
    </style>
</head>
<body class="bg-gray-50 font-inter">
<div class="flex min-h-screen">
        <?php if ($isProcurement): ?>
            <!-- Procurement Sidebar Structure -->
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
        <?php else: ?>
            <!-- Budget Office Sidebar (includes full structure) -->
            <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
        <?php endif; ?>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col" data-main-content>
            <!-- Header -->
            <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-6">
                    <div class="flex flex-col gap-6 md:flex-row md:items-start md:justify-between">
                        <div class="text-white">
                            <h1 class="text-3xl font-bold">File Submission Inbox</h1>
                            <p class="text-red-100 text-sm mt-1">Monitor PPMP, LIB, APP, and Supplemental uploads across the campus, <?php echo htmlspecialchars($username); ?></p>
                    </div>
                        <div class="flex flex-wrap items-center gap-4">
                        <!-- Notification Bell -->
                        <?php 
                        require_once __DIR__ . '/../classes/Notification.php';
                        $notification = new Notification();
                        $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
                        $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
                        include __DIR__ . '/../components/notification_bell.php'; 
                        ?>
                        
                        <div class="relative">
                                <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-white bg-opacity-20 px-4 py-2 rounded-lg hover:bg-opacity-30 transition-colors backdrop-blur">
                                    <?php render_profile_avatar(['classes' => 'bg-maroon text-white font-semibold']); ?>
                                <div>
                                        <div class="font-medium text-white"><?php echo htmlspecialchars($username); ?></div>
                                        <div class="text-xs text-red-100"><?php echo htmlspecialchars($userEmail); ?></div>
    </div>
                                    <svg class="w-4 h-4 text-red-100" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
        </svg>
                            </button>
                            
                            <!-- Profile Dropdown -->
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
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
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
            </header>
            
            <!-- Content Area -->
            <div class="flex-1 p-6">
                <!-- Filters Section -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                    <h2 class="text-xl font-bold text-maroon mb-4">Filter Submissions</h2>
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fiscal Year</label>
                            <select name="fiscal_year" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon">
                                <option value="">All Years</option>
                                <?php foreach ($fiscal_years as $year): ?>
                                    <option value="<?php echo $year; ?>" <?php echo $fiscal_year == $year ? 'selected' : ''; ?>>
                                        <?php echo $year; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Document Type</label>
                            <select name="submission_type" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon">
                                <option value="">All Types</option>
                                <option value="PPMP" <?php echo $submission_type == 'PPMP' ? 'selected' : ''; ?>>PPMP</option>
                                <option value="LIB" <?php echo $submission_type == 'LIB' ? 'selected' : ''; ?>>LIB</option>
                                <option value="APP" <?php echo $submission_type == 'APP' ? 'selected' : ''; ?>>APP</option>
                                <option value="SUPPLEMENTAL" <?php echo $submission_type == 'SUPPLEMENTAL' ? 'selected' : ''; ?>>Supplemental</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Department/Office</label>
                            <select name="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $department_id == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['dept_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex items-end">
                            <button type="submit" class="w-full px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                                Apply Filters
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Submissions Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-xl font-bold text-maroon">Recent Submissions</h2>
                        <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($submissions); ?> submissions ready to view
                        </span>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department/Office</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Document Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fiscal Year</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted By</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">File Size</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Submitted Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($submissions)): ?>
                                    <tr>
                                        <td colspan="8" class="px-6 py-6 text-center text-gray-500">
                                            No submissions found for the selected filters.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($submissions as $submission): ?>
                                        <?php
                                            $fileUrl = publicSubmissionPath($submission['file_path'] ?? '');
                                            $fileExt = strtolower(pathinfo($submission['file_name'] ?? '', PATHINFO_EXTENSION));
                                            $fileMime = $submission['file_type'] ?? '';
                                            $submitted_at = isset($submission['submitted_at']) ? strtotime($submission['submitted_at']) : (isset($submission['created_at']) ? strtotime($submission['created_at']) : false);
                                            $submittedDisplay = $submitted_at ? date('M j, Y g:i A', $submitted_at) : '—';
                                            $metaParts = array_filter([
                                                $submission['dept_name'] ?? null,
                                                !empty($submission['fiscal_year']) ? 'FY ' . $submission['fiscal_year'] : null,
                                                $submission['submission_type'] ?? null,
                                                $submittedDisplay !== '—' ? $submittedDisplay : null,
                                            ]);
                                            $metaText = implode(' • ', $metaParts);
                                        ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($submission['dept_name'] ?? 'Unknown Department'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $docType = $submission['submission_type'] ?? '';
                                                $docTypeClass = '';
                                                $docTypeDisplay = $docType;
                                                if ($docType === 'PPMP') {
                                                    $docTypeClass = 'bg-blue-100 text-blue-800';
                                                } elseif ($docType === 'APP') {
                                                    $docTypeClass = 'bg-red-100 text-red-700'; // Faded red for APP
                                                } elseif ($docType === 'SUPPLEMENTAL') {
                                                    $docTypeClass = 'bg-yellow-100 text-yellow-800';
                                                    $docTypeDisplay = 'Supplemental';
                                                } elseif ($docType === 'LIB') {
                                                    $docTypeClass = 'bg-green-100 text-green-800';
                                                } else {
                                                    $docTypeClass = 'bg-gray-100 text-gray-800';
                                                }
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $docTypeClass; ?>">
                                                    <?php echo htmlspecialchars($docTypeDisplay ?: 'N/A'); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars($submission['fiscal_year'] ?? '—'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo htmlspecialchars(trim(($submission['first_name'] ?? '') . ' ' . ($submission['last_name'] ?? '')) ?: '—'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php if ($fileUrl): ?>
                                                    <button type="button"
                                                            class="view-file-trigger text-maroon hover:underline font-semibold"
                                                            data-file="<?php echo htmlspecialchars($fileUrl); ?>"
                                                            data-name="<?php echo htmlspecialchars($submission['file_name']); ?>"
                                                            data-ext="<?php echo htmlspecialchars($fileExt); ?>"
                                                            data-mime="<?php echo htmlspecialchars($fileMime); ?>"
                                                            data-meta="<?php echo htmlspecialchars($metaText); ?>">
                                                        <span class="file-name-text"><?php echo htmlspecialchars($submission['file_name']); ?></span>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-500 italic">File unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $fileSubmission->formatFileSize($submission['file_size']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo $submitted_at ? date('M j, Y', $submitted_at) : '—'; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <?php if ($fileUrl): ?>
                                                    <button type="button"
                                                            class="download-file-trigger text-gray-700 hover:text-gray-900"
                                                            data-file="<?php echo htmlspecialchars($fileUrl); ?>"
                                                            data-name="<?php echo htmlspecialchars($submission['file_name']); ?>">
                                                        Download
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-xs text-gray-400">Unavailable</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
      </div>
</div>
    
    <!-- File Viewer Modal -->
    <div id="fileViewerModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 id="fileModalTitle" class="text-lg font-semibold text-gray-900">Document preview</h3>
                        <p id="fileModalMeta" class="text-sm text-gray-500 mt-1">Select a file to start previewing.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button id="fileModalDownload" class="px-4 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            Download
                        </button>
                        <button type="button" onclick="closeFileModal()" class="p-2 rounded-full hover:bg-gray-100 transition-colors text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="filePreviewContainer" class="flex-1 bg-gray-50 overflow-hidden">
                    <div class="h-full flex items-center justify-center text-gray-500 text-sm">
                        Choose a submission to preview the document instantly.
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
        
        // Close modal when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (modal && event.target === modal) {
                closeLogoutModal();
            }
        });
        
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

        // File preview + download handling
        const fileModal = document.getElementById('fileViewerModal');
        const fileModalTitle = document.getElementById('fileModalTitle');
        const fileModalMeta = document.getElementById('fileModalMeta');
        const fileModalDownload = document.getElementById('fileModalDownload');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        let activeFile = null;

        function buildAbsoluteUrl(path) {
            if (!path) return '';
            if (/^https?:\/\//i.test(path)) {
                return path;
            }
            return '../' + path.replace(/^\/+/, '');
        }

        function renderFilePreview(path, ext) {
            const absoluteUrl = buildAbsoluteUrl(path);
            if (!absoluteUrl) {
                return '<div class="h-full flex items-center justify-center text-gray-500 text-sm px-6 text-center">File preview is unavailable for this submission.</div>';
            }

            const loweredExt = (ext || '').toLowerCase();
            if (['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg'].includes(loweredExt)) {
                return `<div class="h-full w-full flex items-center justify-center bg-white">
                            <img src="${absoluteUrl}" alt="Document preview" class="max-h-full max-w-full object-contain">
                        </div>`;
            }

            if (loweredExt === 'pdf') {
                return `<iframe src="${absoluteUrl}#toolbar=0&navpanes=0" class="w-full h-full bg-white" frameborder="0"></iframe>`;
            }

            if (['xls', 'xlsx', 'csv'].includes(loweredExt)) {
                const viewerUrl = '../ajax/view_excel.php?file=' + encodeURIComponent(path);
                return `<iframe src="${viewerUrl}" class="w-full h-full bg-white" frameborder="0"></iframe>`;
            }

            if (['mp4', 'webm', 'ogg'].includes(loweredExt)) {
                return `<video src="${absoluteUrl}" controls class="w-full h-full bg-black"></video>`;
            }

            return `<div class="h-full flex flex-col items-center justify-center gap-4 text-gray-600 text-sm px-6 text-center">
                        <p>Preview not available for this file type.</p>
                        <a href="${absoluteUrl}" target="_blank" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">Open in new tab</a>
                    </div>`;
        }

        function openFileModal(fileData) {
            activeFile = fileData;
            fileModalTitle.textContent = fileData.name || 'Document preview';
            fileModalMeta.textContent = fileData.meta || '';
            filePreviewContainer.innerHTML = renderFilePreview(fileData.path, fileData.ext);
            fileModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeFileModal() {
            fileModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        window.closeFileModal = closeFileModal;

        function triggerDownload(path, name) {
            const absoluteUrl = buildAbsoluteUrl(path);
            if (!absoluteUrl) {
                alert('File path unavailable for this submission.');
                return;
            }
            const link = document.createElement('a');
            link.href = absoluteUrl;
            if (name) {
                link.download = name;
            }
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        document.querySelectorAll('.view-file-trigger').forEach(button => {
            button.addEventListener('click', () => {
                const filePath = button.dataset.file;
                if (!filePath) {
                    alert('File path unavailable for this submission.');
                    return;
                }
                openFileModal({
                    name: button.dataset.name,
                    path: filePath,
                    ext: button.dataset.ext,
                    meta: button.dataset.meta
                });
            });
        });

        document.querySelectorAll('.download-file-trigger').forEach(button => {
            button.addEventListener('click', () => {
                triggerDownload(button.dataset.file, button.dataset.name);
            });
        });

        fileModalDownload.addEventListener('click', () => {
            if (activeFile) {
                triggerDownload(activeFile.path, activeFile.name);
            }
        });

        fileModal.addEventListener('click', (event) => {
            if (event.target === fileModal) {
                closeFileModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !fileModal.classList.contains('hidden')) {
                closeFileModal();
            }
        });

        <?php if ($isProcurement): ?>
        // Sidebar toggle functionality for procurement
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
        <?php endif; ?>
</script>
<?php if ($isProcurement): ?>
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
<?php endif; ?>

</body>
</html>
