<?php
session_start();

// Require login; allow Procurement and Department/Offices roles to access
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$allowedRoles = ['procurement', 'offices', 'department', 'supply_office', 'budget'];
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], $allowedRoles, true)) {
    // If role not allowed, send to their dashboard instead of login
    switch ($_SESSION['user_role'] ?? '') {
        case 'school_admin':
            header('Location: ./school_admin_dashboard.php');
            break;
        default:
            header('Location: ./dept_dashboard.php');
            break;
    }
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../config/database.php';

function submissionPublicPath(?string $filePath): string {
    if (!$filePath) {
        return '';
    }
    $normalized = str_replace('\\', '/', trim($filePath));
    
    // If it already starts with 'uploads/', return as-is (relative path)
    if (strpos($normalized, 'uploads/') === 0) {
        return $normalized;
    }
    
    // If it's an absolute path, try to extract relative path
    $projectRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
    if ($projectRoot && strpos($normalized, $projectRoot) === 0) {
        $normalized = ltrim(substr($normalized, strlen($projectRoot)), '/');
    }
    
    $normalized = ltrim($normalized, '/');
    
    // Final check - must start with 'uploads/'
    if (strpos($normalized, 'uploads/') !== 0) {
        return '';
    }
    
    return $normalized;
}

function submissionAbsolutePath(?string $filePath): string {
    $relative = submissionPublicPath($filePath);
    return $relative ? (__DIR__ . '/../' . $relative) : '';
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
include __DIR__ . '/../components/profile_avatar.php';
$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

// Get department name
$departmentName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : null;
if (!$departmentName && $departmentId) {
    require_once __DIR__ . '/../classes/Department.php';
    $dept = new Department();
    $deptInfo = $dept->getDepartmentById($departmentId);
    $departmentName = $deptInfo ? $deptInfo['dept_name'] : null;
}

$isProcurement = ($_SESSION['user_role'] === 'procurement');
$isBudget = ($_SESSION['user_role'] === 'budget');
$officeLabel = $isProcurement ? 'Procurement Office' : ($isBudget ? 'Budget Office' : ($departmentName ?? 'Department/Office'));
$portalLabel = $isProcurement 
    ? ($departmentName ? "Procurement Portal | " . htmlspecialchars($departmentName) : "Procurement Portal")
    : ($isBudget ? "Budget Office Portal" : ($departmentName ? "Department Portal | " . htmlspecialchars($departmentName) : "Department Portal"));

$fileSubmission = new FileSubmission();
$notification = new Notification();
$activityLogger = new UserActivity();
$notificationsForBell = $notification->getUserNotifications($userId, 10);
$unreadCount = $notification->getUnreadCount($userId);
$currentYear = (int)date('Y');
$feedback = [
    'PPMP' => ['success' => null, 'error' => null],
    'LIB' => ['success' => null, 'error' => null],
    'APP' => ['success' => null, 'error' => null],
    'SUPPLEMENTAL' => ['success' => null, 'error' => null],
];

// Get submission history for the user (including removed files for complete history)
$submissionHistoryQuery = "SELECT fs.*, d.dept_name, u.first_name, u.last_name
                  FROM file_submissions fs
                  LEFT JOIN departments d ON fs.department_id = d.id
                  LEFT JOIN users u ON fs.user_id = u.id
                  WHERE fs.user_id = :user_id 
                  ORDER BY fs.submitted_at DESC LIMIT 100";
$conn = getDB();
$historyStmt = $conn->prepare($submissionHistoryQuery);
$historyStmt->bindParam(':user_id', $userId);
$historyStmt->execute();
$submissionHistory = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch current records - will be refreshed after POST processing
$currentPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
$currentLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
$currentApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');
$currentSupplemental = $fileSubmission->getLatestSubmissionByType($userId, 'SUPPLEMENTAL');

// Check if PPMP and LIB are both submitted (required for APP submission)
$ppmpSubmitted = (bool)$currentPpmp;
$libSubmitted = (bool)$currentLib;
$canSubmitApp = $ppmpSubmitted && $libSubmitted;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submission_type'], $_POST['action'])) {
    $allowedTypes = ['PPMP', 'LIB', 'APP', 'SUPPLEMENTAL'];
    $submissionType = in_array($_POST['submission_type'], $allowedTypes, true) ? $_POST['submission_type'] : 'PPMP';
    $action = $_POST['action'];
    $currentRecord = $submissionType === 'PPMP' ? $currentPpmp : ($submissionType === 'LIB' ? $currentLib : ($submissionType === 'APP' ? $currentApp : $currentSupplemental));
    $targetDirMap = [
        'PPMP' => __DIR__ . '/../uploads/ppmp/',
        'LIB' => __DIR__ . '/../uploads/lib/',
        'APP' => __DIR__ . '/../uploads/app/',
        'SUPPLEMENTAL' => __DIR__ . '/../uploads/supplemental/',
    ];
    $relativeDirMap = [
        'PPMP' => 'uploads/ppmp/',
        'LIB' => 'uploads/lib/',
        'APP' => 'uploads/app/',
        'SUPPLEMENTAL' => 'uploads/supplemental/',
    ];
    $targetDir = $targetDirMap[$submissionType];
    $relativeDir = $relativeDirMap[$submissionType];

    if ($action === 'remove') {
        // Soft delete - mark as removed but keep in database for budget office
        if ($currentRecord) {
            $fileSubmission->markAsRemovedByUser((int)$currentRecord['id'], $userId);
            $feedback[$submissionType]['success'] = $submissionType . ' file removed successfully. It will no longer appear in your view, but the Budget Office will still have access to the record.';
            $activityLogger->logActivity(
                $userId,
                'submission_removal',
                null,
                null,
                json_encode([
                    'submission_id' => (int)$currentRecord['id'],
                    'submission_type' => $submissionType,
                    'file_name' => $currentRecord['file_name'] ?? '',
                    'action' => 'removed',
                    'year' => $currentYear,
                ])
            );
            
            // Refresh the record after removal
            if ($submissionType === 'PPMP') {
                $currentPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
            } elseif ($submissionType === 'LIB') {
                $currentLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
            } elseif ($submissionType === 'APP') {
                $currentApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');
            } elseif ($submissionType === 'SUPPLEMENTAL') {
                $currentSupplemental = $fileSubmission->getLatestSubmissionByType($userId, 'SUPPLEMENTAL');
            }
        } else {
            $feedback[$submissionType]['error'] = 'No existing ' . $submissionType . ' file to remove.';
        }
    } elseif ($action === 'upload') {
        // Regular upload - creates new submission (doesn't update existing)
        // For APP, check if PPMP and LIB are both submitted
        if ($submissionType === 'APP') {
            $ppmpCheck = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
            $libCheck = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
            if (!$ppmpCheck || !$libCheck) {
                $feedback[$submissionType]['error'] = 'You must submit both PPMP and LIB before you can submit an APP.';
            }
        }
        
        if (!isset($feedback[$submissionType]['error'])) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
                $feedback[$submissionType]['error'] = 'Please choose a file to upload.';
            } else {
            $originalName = $_FILES['submission_file']['name'];
            $ext = pathinfo($originalName, PATHINFO_EXTENSION);
            
            $uniqueName = $submissionType . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(3));
            if ($ext) {
                $uniqueName .= '.' . $ext;
            }

            $relativePath = $relativeDir . $uniqueName;
            $absolutePath = __DIR__ . '/../' . $relativePath;

            if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $absolutePath)) {
                // Always create new submission (don't update existing)
                $submissionId = $fileSubmission->submitFile(
                    $userId,
                    $departmentId,
                    $submissionType,
                    $currentYear,
                    $originalName,
                    $relativePath,
                    (int)$_FILES['submission_file']['size'],
                    $_FILES['submission_file']['type'] ?? ''
                );
                
                if ($submissionId) {
                    // Send notifications for PPMP, LIB, and APP
                    require_once __DIR__ . '/../classes/Notification.php';
                    $notif = new Notification();
                    
                    if ($submissionType === 'PPMP' || $submissionType === 'LIB' || $submissionType === 'SUPPLEMENTAL') {
                        // Get department name for notification
                        $deptName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : 'Unknown Department';
                        if (!$deptName || $deptName === 'Unknown Department') {
                            // Try to get from database
                            require_once __DIR__ . '/../classes/Department.php';
                            if ($departmentId) {
                                $dept = new Department();
                                $deptInfo = $dept->getDepartmentById($departmentId);
                                $deptName = $deptInfo ? $deptInfo['dept_name'] : 'Unknown Department';
                            }
                        }
                        // Notify budget/admin users about new PPMP/LIB/SUPPLEMENTAL submission
                        $notif->notifyBudgetAdmins($submissionType, $userId, $deptName, false);
                    } elseif ($submissionType === 'APP') {
                        // Send notification to the submitting user
                        $notif->createNotification(
                            $userId,
                            'APP Uploaded Successfully',
                            'Your Annual Procurement Plan has been uploaded and approved. You can now submit a Purchase Request.',
                            'success'
                        );
                    }
                    
                    // Refresh the record for the submitted type
                    if ($submissionType === 'PPMP') {
                        $currentPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
                    } elseif ($submissionType === 'LIB') {
                        $currentLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
                    } elseif ($submissionType === 'APP') {
                        $currentApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');
                    } elseif ($submissionType === 'SUPPLEMENTAL') {
                        $currentSupplemental = $fileSubmission->getLatestSubmissionByType($userId, 'SUPPLEMENTAL');
                    }
                    $activityLogger->logActivity(
                        $userId,
                        'submission_upload',
                        null,
                        null,
                        json_encode([
                            'submission_id' => (int)$submissionId,
                            'submission_type' => $submissionType,
                            'file_name' => $originalName,
                            'action' => 'uploaded',
                            'year' => $currentYear,
                        ])
                    );
                }

                header('Location: submit_documents.php?success=' . urlencode($submissionType));
                exit;
            } else {
                $feedback[$submissionType]['error'] = 'Upload failed. Please try again.';
            }
            }
        }
    } elseif ($action === 'update') {
        // Update existing submission (replaces the file)
        // Fo r APP, check if PPMP and LIB are both submitted (even for updates, to maintain consistency)
        if ($submissionType === 'APP') {
            $ppmpCheck = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
            $libCheck = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
            if (!$ppmpCheck || !$libCheck) {
                $feedback[$submissionType]['error'] = 'You must submit both PPMP and LIB before you can update an APP.';
            }
        }
        
        if (!isset($feedback[$submissionType]['error'])) {
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0777, true);
            }

            if (!isset($_FILES['submission_file']) || $_FILES['submission_file']['error'] !== UPLOAD_ERR_OK) {
                $feedback[$submissionType]['error'] = 'Please choose a file to upload.';
            } else {
            // Check for existing record
            $existingRecord = $fileSubmission->getLatestSubmissionByType($userId, $submissionType);
            
            if (!$existingRecord) {
                $feedback[$submissionType]['error'] = 'No existing ' . $submissionType . ' file to update.';
            } else {
                $originalName = $_FILES['submission_file']['name'];
                $ext = pathinfo($originalName, PATHINFO_EXTENSION);
                
                $uniqueName = $submissionType . '_' . $userId . '_' . time() . '_' . bin2hex(random_bytes(3));
                if ($ext) {
                    $uniqueName .= '.' . $ext;
                }

                $relativePath = $relativeDir . $uniqueName;
                $absolutePath = __DIR__ . '/../' . $relativePath;

                if (move_uploaded_file($_FILES['submission_file']['tmp_name'], $absolutePath)) {
                    // Delete old file
                    $absoluteOld = submissionAbsolutePath($existingRecord['file_path'] ?? '');
                    if ($absoluteOld && file_exists($absoluteOld)) {
                        @unlink($absoluteOld);
                    }
                    
                    // Update existing record
                    $preserveStatus = ($submissionType === 'APP') ? 'approved' : null;
                    $fileSubmission->updateSubmissionFile(
                        (int)$existingRecord['id'],
                        $originalName,
                        $relativePath,
                        (int)$_FILES['submission_file']['size'],
                        $_FILES['submission_file']['type'] ?? '',
                        $currentYear,
                        $preserveStatus
                    );
                    
                    $feedback[$submissionType]['success'] = $submissionType . ' file updated successfully.';
                    
                    // Send notifications for PPMP, LIB, and SUPPLEMENTAL updates
                    if ($submissionType === 'PPMP' || $submissionType === 'LIB' || $submissionType === 'SUPPLEMENTAL') {
                        require_once __DIR__ . '/../classes/Notification.php';
                        $notif = new Notification();
                        
                        // Get department name for notification
                        $deptName = isset($_SESSION['department_name']) ? $_SESSION['department_name'] : 'Unknown Department';
                        if (!$deptName || $deptName === 'Unknown Department') {
                            // Try to get from database
                            require_once __DIR__ . '/../classes/Department.php';
                            if ($departmentId) {
                                $dept = new Department();
                                $deptInfo = $dept->getDepartmentById($departmentId);
                                $deptName = $deptInfo ? $deptInfo['dept_name'] : 'Unknown Department';
                            }
                        }
                        // Notify budget/admin users about PPMP/LIB/SUPPLEMENTAL update
                        $notif->notifyBudgetAdmins($submissionType, $userId, $deptName, true);
                    }
                    
                    // Refresh the record
                    if ($submissionType === 'PPMP') {
                        $currentPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
                    } elseif ($submissionType === 'LIB') {
                        $currentLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
                    } elseif ($submissionType === 'APP') {
                        $currentApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');
                    } elseif ($submissionType === 'SUPPLEMENTAL') {
                        $currentSupplemental = $fileSubmission->getLatestSubmissionByType($userId, 'SUPPLEMENTAL');
                    }
                    
                    header('Location: submit_documents.php?success=' . urlencode($submissionType));
                    $activityLogger->logActivity(
                        $userId,
                        'submission_update',
                        null,
                        null,
                        json_encode([
                            'submission_id' => (int)$existingRecord['id'],
                            'submission_type' => $submissionType,
                            'file_name' => $originalName,
                            'action' => 'updated',
                            'year' => $currentYear,
                        ])
                    );
                    exit;
                } else {
                    $feedback[$submissionType]['error'] = 'Upload failed. Please try again.';
                }
            }
            }
        }
    }
}

// Handle success message from redirect
    if (isset($_GET['success']) && in_array($_GET['success'], ['PPMP', 'LIB', 'APP', 'SUPPLEMENTAL'], true)) {
        $successType = $_GET['success'];
        $feedback[$successType]['success'] = $successType . ' file submitted successfully.';
        // Force refresh records after redirect to ensure latest data is displayed
        $currentPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
        $currentLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
        $currentApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');
        $currentSupplemental = $fileSubmission->getLatestSubmissionByType($userId, 'SUPPLEMENTAL');
    } else {
        // Always refresh records to ensure latest data (for non-redirect loads)
        $currentPpmp = $fileSubmission->getLatestSubmissionByType($userId, 'PPMP');
        $currentLib = $fileSubmission->getLatestSubmissionByType($userId, 'LIB');
        $currentApp = $fileSubmission->getLatestSubmissionByType($userId, 'APP');
        $currentSupplemental = $fileSubmission->getLatestSubmissionByType($userId, 'SUPPLEMENTAL');
    }
    
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Submit Documents</title>
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
            
            <?php 
            $userRole = $_SESSION['user_role'];
            $isProcurement = ($userRole === 'procurement');
            $isBudget = ($userRole === 'budget');
            $activeSidebar = 'upload';
            ?>
            <?php if ($isBudget): ?>
                <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
            <?php elseif ($isProcurement): ?>
                <?php include __DIR__ . '/../components/proc_sidebar.php'; ?>
            <?php else: ?>
                <?php include __DIR__ . '/../components/dept_sidebar.php'; ?>
            <?php endif; ?>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 flex flex-col" data-main-content>
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div class="text-white max-w-2xl">
                            <p class="text-sm text-red-100 mb-2">PPMP | LIB | APP | Supplemental Submissions</p>
                            <h1 class="text-3xl font-bold">Manage Your Submissions</h1>
                            <p class="text-red-100 mt-1 text-sm">Upload and refresh PPMP, LIB, APP, and Supplemental documents with live feedback.</p>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php 
                                $notifications = $notificationsForBell;
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
                                    <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'budget'): ?>
                                        <a href="super_admin_dashboard.php" class="flex items-center px-4 py-3 text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
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
            <div class="flex-1 p-6 space-y-6">
                <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm text-blue-900">
                    Submit one PPMP file and one LIB file. You can replace them anytime—only the latest version stays on record, and the Budget Office can preview them instantly. You can also submit supplemental documents as needed.
                </div>

                <?php
                $cards = [
                    'PPMP' => [
                        'title' => 'Project Procurement Management Plan (PPMP)',
                        'description' => 'Upload your latest PPMP so the Budget Office can review it without waiting for approvals.',
                        'record' => $currentPpmp,
                        'feedback' => $feedback['PPMP'],
                        'accept' => '*/*',
                    ],
                    'LIB' => [
                        'title' => 'Line-Item Budget (LIB)',
                        'description' => 'Keep your LIB current so the financial team always references the newest numbers.',
                        'record' => $currentLib,
                        'feedback' => $feedback['LIB'],
                        'accept' => '*/*',
                    ],
                    'APP' => [
                        'title' => 'Annual Procurement Plan (APP)',
                        'description' => 'Upload your fully signed Annual Procurement Plan file. Once uploaded, it will be automatically approved and you can proceed to submit Purchase Requests.',
                        'record' => $currentApp,
                        'feedback' => $feedback['APP'],
                        'accept' => '*/*',
                        'note' => 'Any file type is accepted. The APP will be automatically approved upon upload.',
                        'requires_ppmp_lib' => true, // Flag to indicate APP requires PPMP and LIB
                    ],
                    'SUPPLEMENTAL' => [
                        'title' => 'Supplemental Submission',
                        'description' => 'Upload additional supporting documents or supplemental files as needed for your budget submissions.',
                        'record' => $currentSupplemental,
                        'feedback' => $feedback['SUPPLEMENTAL'],
                        'accept' => '*/*',
                    ],
                ];
                ?>

                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-4">
                            <p class="text-sm text-gray-600">Use the selector to focus on a specific document type.</p>
                    <div class="flex gap-3">
                        <select id="submissionFilter" class="w-full md:w-60 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent">
                            <option value="ALL">Show All</option>
                            <?php foreach (array_keys($cards) as $type): ?>
                                <option value="<?php echo $type; ?>"><?php echo $type === 'SUPPLEMENTAL' ? 'Supplemental' : $type; ?> only</option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="openSubmissionHistory()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Submission History
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" id="submissionCards">
                    <?php foreach ($cards as $type => $card): 
                        $record = $card['record'];
                        $fileUrl = $record ? submissionPublicPath($record['file_path'] ?? '') : '';
                        $updatedAt = $record && !empty($record['submitted_at']) ? date('M j, Y g:i A', strtotime($record['submitted_at'])) : null;
                        $size = $record ? $fileSubmission->formatFileSize($record['file_size']) : null;
                        $inputId = strtolower($type) . '_input';
                        $statusId = strtolower($type) . '_status';
                        $acceptAttr = $card['accept'] ?? '*/*';
                        
                        // For PPMP, LIB, and SUPPLEMENTAL, show only Submitted or Not Submitted (no approval needed)
                        // For APP, show Submitted or Not Submitted (auto-approved when submitted)
                        if ($type === 'PPMP' || $type === 'LIB' || $type === 'SUPPLEMENTAL') {
                            $isComplete = (bool)$record;
                            $statusLabel = $isComplete ? 'Submitted' : 'Not Submitted';
                            $statusClass = $isComplete ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800';
                        } elseif ($type === 'APP') {
                            // APP should show Submitted or Not Submitted (same as PPMP and LIB)
                            $isComplete = (bool)$record;
                            $statusLabel = $isComplete ? 'Submitted' : 'Not Submitted';
                            $statusClass = $isComplete ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800'; // Green when submitted, same as PPMP and LIB
                        } else {
                            $isComplete = (bool)$record;
                            $statusLabel = $isComplete ? 'Submitted' : 'Not Submitted';
                            $statusClass = $isComplete ? 'bg-green-100 text-green-700' : 'bg-yellow-100 text-yellow-800';
                        }
                    ?>
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6 flex flex-col h-full submission-card" data-card-type="<?php echo $type; ?>">
                        <div class="flex items-start justify-between mb-4">
                            <div>
                                <p class="text-xs uppercase tracking-wide text-gray-500"><?php echo $type === 'SUPPLEMENTAL' ? 'Supplemental' : $type; ?> Submission</p>
                                <h2 class="text-lg font-semibold text-gray-900"><?php echo htmlspecialchars($card['title']); ?></h2>
                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($card['description']); ?></p>
                                <?php if (!empty($card['note'])): ?>
                                    <p class="text-xs text-gray-400 mt-1"><?php echo htmlspecialchars($card['note']); ?></p>
                                <?php endif; ?>
                            </div>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $statusClass; ?>">
                                <?php echo $statusLabel; ?>
                            </span>
                        </div>

                        <?php if ($card['feedback']['success']): ?>
                            <div class="mb-4 px-3 py-2 rounded-lg bg-green-50 border border-green-100 text-sm text-green-700">
                                <?php echo htmlspecialchars($card['feedback']['success']); ?>
                            </div>
                        <?php elseif ($card['feedback']['error']): ?>
                            <div class="mb-4 px-3 py-2 rounded-lg bg-red-50 border border-red-100 text-sm text-red-700">
                                <?php echo htmlspecialchars($card['feedback']['error']); ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($record): ?>
                            <?php 
                            // Ensure fileUrl is generated correctly
                            if (!$fileUrl && !empty($record['file_path'])) {
                                $fileUrl = submissionPublicPath($record['file_path']);
                            }
                            ?>
                            <?php if ($fileUrl): ?>
                                <div class="mb-4 rounded-xl bg-gray-50 border border-gray-200 p-4">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($record['file_name']); ?></p>
                                    <p class="text-xs text-gray-500 mt-1">Size: <?php echo htmlspecialchars($size); ?> • Updated: <?php echo htmlspecialchars($updatedAt ?? '—'); ?></p>
                                    <div class="mt-3 flex flex-wrap gap-3">
                                        <button type="button"
                                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white bg-maroon rounded-lg hover:bg-maroon-dark transition-colors view-file-trigger"
                                                data-file="<?php echo htmlspecialchars($fileUrl); ?>"
                                                data-name="<?php echo htmlspecialchars($record['file_name']); ?>"
                                                data-ext="<?php echo htmlspecialchars(strtolower(pathinfo($record['file_name'], PATHINFO_EXTENSION))); ?>"
                                                data-mime="<?php echo htmlspecialchars($record['file_type'] ?? ''); ?>"
                                                data-meta="<?php echo htmlspecialchars(($updatedAt ? 'Updated ' . $updatedAt : '') . ($size ? ' • ' . $size : '')); ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553 2.276A2 2 0 0121 14.09V17a2 2 0 01-2 2h-5"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A2 2 0 0021 5.91V3a2 2 0 00-2-2H9a2 2 0 00-2 2v4"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l6 6m0 0l6-6m-6 6V10"></path></svg>
                                            View file
                                        </button>
                                        <button type="button"
                                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-white <?php echo ($type === 'APP' && !$canSubmitApp) ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700'; ?> transition-colors re-upload-trigger"
                                                data-input-id="<?php echo $inputId; ?>-update"
                                                data-form-id="update-form-<?php echo strtolower($type); ?>"
                                                <?php echo ($type === 'APP' && !$canSubmitApp) ? 'disabled title="You must submit both PPMP and LIB first"' : ''; ?>>
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                            Re-upload
                                        </button>
                                        <form method="POST" onsubmit="return confirm('Remove the existing <?php echo $type; ?> file from your view? The Budget Office will still have access to this record.');">
                                            <input type="hidden" name="submission_type" value="<?php echo $type; ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <button type="submit" class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                Remove
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="mb-4 rounded-xl bg-blue-50 border border-blue-100 p-4 text-sm text-blue-800">
                                    <p class="font-medium">File uploaded: <?php echo htmlspecialchars($record['file_name'] ?? 'Unknown'); ?></p>
                                    <p class="text-xs mt-1">Status: <?php echo htmlspecialchars($record['status'] ?? 'N/A'); ?></p>
                                    <p class="text-xs mt-1">Path: <?php echo htmlspecialchars($record['file_path'] ?? 'N/A'); ?></p>
                                    <p class="text-xs mt-1 text-red-600">Note: File path may need processing. If this persists, contact support.</p>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($type === 'APP' && !$canSubmitApp): ?>
                                <div class="mb-4 rounded-xl bg-yellow-50 border border-yellow-100 p-4 text-sm text-yellow-800">
                                    <p class="font-medium mb-1">APP submission is not available yet.</p>
                                    <p class="text-xs">You must submit both PPMP and LIB before you can submit an APP.</p>
                                    <ul class="text-xs mt-2 list-disc list-inside space-y-1">
                                        <li>PPMP: <?php echo $ppmpSubmitted ? '<span class="text-green-700 font-semibold">✓ Submitted</span>' : '<span class="text-red-700 font-semibold">✗ Not Submitted</span>'; ?></li>
                                        <li>LIB: <?php echo $libSubmitted ? '<span class="text-green-700 font-semibold">✓ Submitted</span>' : '<span class="text-red-700 font-semibold">✗ Not Submitted</span>'; ?></li>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="mb-4 rounded-xl bg-yellow-50 border border-yellow-100 p-4 text-sm text-yellow-800">
                                    No <?php echo $type; ?> uploaded yet. Please submit the latest file to keep your records in sync with the Budget Office.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($isComplete): ?>
                            <!-- Update form (Re-upload) - only shown when file exists -->
                            <form method="POST" enctype="multipart/form-data" class="mt-auto space-y-3" id="update-form-<?php echo strtolower($type); ?>" style="display: none;">
                                <input type="hidden" name="submission_type" value="<?php echo $type; ?>">
                                <input type="hidden" name="action" value="update">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Update file</label>
                                    <input type="file"
                                           name="submission_file"
                                           id="<?php echo $inputId; ?>-update"
                                           accept="<?php echo htmlspecialchars($acceptAttr); ?>"
                                           class="file-input block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent"
                                           required>
                                    <p id="<?php echo $statusId; ?>-update" class="text-xs text-gray-500 mt-2">No file selected</p>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Update File
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Submit new form - only shown when file exists -->
                            <form method="POST" enctype="multipart/form-data" class="mt-3 space-y-3" id="upload-form-<?php echo strtolower($type); ?>">
                                <input type="hidden" name="submission_type" value="<?php echo $type; ?>">
                                <input type="hidden" name="action" value="upload">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Submit new file</label>
                                    <input type="file"
                                           name="submission_file"
                                           id="<?php echo $inputId; ?>"
                                           accept="<?php echo htmlspecialchars($acceptAttr); ?>"
                                           class="file-input block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent <?php echo ($type === 'APP' && !$canSubmitApp) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                           <?php echo ($type === 'APP' && !$canSubmitApp) ? 'disabled' : 'required'; ?>>
                                    <p id="<?php echo $statusId; ?>" class="text-xs text-gray-500 mt-2">No file selected</p>
                                    <p class="text-xs text-gray-400 mt-1">This will create a new submission. The old file will remain in the Budget UI.</p>
                                    <?php if ($type === 'APP' && !$canSubmitApp): ?>
                                        <p class="text-xs text-red-600 mt-1 font-medium">You must submit both PPMP and LIB first.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white <?php echo ($type === 'APP' && !$canSubmitApp) ? 'bg-gray-400 cursor-not-allowed' : 'bg-maroon hover:bg-maroon-dark'; ?> transition-colors" <?php echo ($type === 'APP' && !$canSubmitApp) ? 'disabled' : ''; ?>>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16h16V4H4zm4 4h8v4H8V8zm0 6h5v2H8v-2z"></path></svg>
                                        Submit New File
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <!-- Regular upload form - only shown when no file exists -->
                            <form method="POST" enctype="multipart/form-data" class="mt-auto space-y-3" id="upload-form-<?php echo strtolower($type); ?>">
                                <input type="hidden" name="submission_type" value="<?php echo $type; ?>">
                                <input type="hidden" name="action" value="upload">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Upload file</label>
                                    <input type="file"
                                           name="submission_file"
                                           id="<?php echo $inputId; ?>"
                                           accept="<?php echo htmlspecialchars($acceptAttr); ?>"
                                           class="file-input block w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-maroon focus:border-transparent <?php echo ($type === 'APP' && !$canSubmitApp) ? 'opacity-50 cursor-not-allowed' : ''; ?>"
                                           <?php echo ($type === 'APP' && !$canSubmitApp) ? 'disabled' : 'required'; ?>>
                                    <p id="<?php echo $statusId; ?>" class="text-xs text-gray-500 mt-2">No file selected</p>
                                    <?php if ($type === 'APP' && !$canSubmitApp): ?>
                                        <p class="text-xs text-red-600 mt-1 font-medium">You must submit both PPMP and LIB first.</p>
                                    <?php endif; ?>
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-semibold text-white <?php echo ($type === 'APP' && !$canSubmitApp) ? 'bg-gray-400 cursor-not-allowed' : 'bg-maroon hover:bg-maroon-dark'; ?> transition-colors" <?php echo ($type === 'APP' && !$canSubmitApp) ? 'disabled' : ''; ?>>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v16h16V4H4zm4 4h8v4H8V8zm0 6h5v2H8v-2z"></path></svg>
                                        Submit File
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>

                <p class="text-sm text-gray-500">Tip: You can preview what you uploaded anytime using the "View file" button. This is the exact view the Budget Office sees.</p>
            </div>
        </div>
    </div>

    <!-- File Viewer Modal -->
    <div id="fileViewerModal" class="fixed inset-0 bg-gray-900 bg-opacity-70 hidden z-[60]">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div id="fileModalContainer" class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl h-[80vh] flex flex-col">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <div>
                        <h3 id="fileModalTitle" class="text-lg font-semibold text-gray-900">Document preview</h3>
                        <p id="fileModalMeta" class="text-sm text-gray-500 mt-1">Choose a file to preview.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button type="button" id="fileModalFullscreen" class="px-3 py-2 text-sm font-medium bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                            Fullscreen
                        </button>
                        <button type="button" onclick="closeFileModal()" class="p-2 rounded-full hover:bg-gray-100 transition-colors text-gray-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
                <div id="filePreviewContainer" class="flex-1 bg-gray-50 overflow-hidden flex items-center justify-center text-gray-500 text-sm px-4 text-center">
                    Select a file to preview.
                </div>
            </div>
        </div>
    </div>
    
    <!-- Submission History Modal -->
    <div id="submissionHistoryModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="flex justify-between items-center p-6 border-b border-gray-200">
                    <h3 class="text-xl font-bold text-gray-900">Submission History</h3>
                    <button onclick="closeSubmissionHistory()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <?php if (empty($submissionHistory)): ?>
                        <div class="text-center py-8 text-gray-500">
                            <p>No submission history found.</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($submissionHistory as $submission): 
                                $fileUrl = submissionPublicPath($submission['file_path'] ?? '');
                                $submittedAt = $submission['submitted_at'] ? date('M j, Y g:i A', strtotime($submission['submitted_at'])) : 'N/A';
                                $fileSize = $fileSubmission->formatFileSize($submission['file_size'] ?? 0);
                            ?>
                                <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                    <div class="mb-3">
                                        <div class="flex-1">
                                            <h4 class="font-semibold text-gray-900"><?php echo htmlspecialchars($submission['file_name']); ?></h4>
                                            <p class="text-sm text-gray-600 mt-1">
                                                <span class="font-medium"><?php echo htmlspecialchars($submission['submission_type']); ?></span> • 
                                                Submitted: <?php echo htmlspecialchars($submittedAt); ?> • 
                                                Size: <?php echo htmlspecialchars($fileSize); ?>
                                            </p>
                                            <?php if ($submission['dept_name']): ?>
                                                <p class="text-xs text-gray-500 mt-1">Department: <?php echo htmlspecialchars($submission['dept_name']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex gap-2 flex-wrap">
                                        <?php if ($fileUrl): ?>
                                            <button onclick="viewHistoryFile('<?php echo htmlspecialchars($fileUrl); ?>', '<?php echo htmlspecialchars($submission['file_name']); ?>', '<?php echo htmlspecialchars(strtolower(pathinfo($submission['file_name'], PATHINFO_EXTENSION))); ?>')" 
                                                    class="px-3 py-1 bg-blue-600 text-white rounded text-sm hover:bg-blue-700">
                                                View
                                            </button>
                                            <a href="../<?php echo htmlspecialchars($fileUrl); ?>" download class="px-3 py-1 bg-green-600 text-white rounded text-sm hover:bg-green-700">
                                                Download
                                            </a>
                                        <?php else: ?>
                                            <span class="px-3 py-1 bg-gray-300 text-gray-600 rounded text-sm cursor-not-allowed">File unavailable</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- File Submission Confirmation Modal -->
    <div id="submitConfirmationModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm File Submission</h3>
                    <button onclick="closeSubmitConfirmation()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <p class="text-gray-600 mb-2">Are you sure you want to submit the following file?</p>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3">
                        <p class="font-semibold text-gray-900" id="confirmFileName">No file selected</p>
                        <p class="text-sm text-gray-500 mt-1" id="confirmFileType"></p>
                    </div>
                </div>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeSubmitConfirmation()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                    <button onclick="confirmSubmitFile()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        Confirm
                    </button>
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
        
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('logoutModal');
            if (event.target === modal) {
                closeLogoutModal();
            }
        });
        
        // Profile dropdown functionality
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
        }

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const trigger = event.target.closest('button[onclick="toggleProfileDropdown()"]');
            if (!trigger && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // File input helpers for both upload and update forms
        document.querySelectorAll('.file-input').forEach(input => {
            // Handle both regular inputs and update inputs
            let statusId = input.id.replace('_input', '_status');
            if (input.id.includes('-update')) {
                statusId = input.id.replace('-update', '_status-update');
            }
            const statusEl = document.getElementById(statusId);
            if (!statusEl) return;
            
            input.addEventListener('change', () => {
                if (input.files.length > 0) {
                    const file = input.files[0];
                    const size = (file.size / 1024 / 1024).toFixed(2);
                    statusEl.textContent = `Selected: ${file.name} (${size} MB)`;
                    statusEl.classList.remove('text-gray-500');
                    statusEl.classList.add('text-green-600');
                } else {
                    statusEl.textContent = 'No file selected';
                    statusEl.classList.remove('text-green-600');
                    statusEl.classList.add('text-gray-500');
                }
            });
        });
        
        // Submission History Modal
        function openSubmissionHistory() {
            document.getElementById('submissionHistoryModal').classList.remove('hidden');
        }
        
        function closeSubmissionHistory() {
            document.getElementById('submissionHistoryModal').classList.add('hidden');
        }
        
        function viewHistoryFile(filePath, fileName, ext) {
            const url = '../' + filePath.replace(/^\/+/, '');
            const loweredExt = (ext || '').toLowerCase();
            
            if (['png','jpg','jpeg','gif','bmp','webp','svg','jfif'].includes(loweredExt)) {
                openFileModal({
                    name: fileName,
                    path: filePath,
                    ext: ext,
                    meta: 'Submission History'
                });
            } else if (loweredExt === 'pdf') {
                openFileModal({
                    name: fileName,
                    path: filePath,
                    ext: ext,
                    meta: 'Submission History'
                });
            } else if (['xls','xlsx','csv'].includes(loweredExt)) {
                openFileModal({
                    name: fileName,
                    path: filePath,
                    ext: ext,
                    meta: 'Submission History'
                });
            } else {
                openFileModal({
                    name: fileName,
                    path: filePath,
                    ext: ext,
                    meta: 'Submission History'
                });
            }
        }
        
        // File Submission Confirmation Modal
        let pendingForm = null;
        
        function openSubmitConfirmation(form, fileName, fileType) {
            document.getElementById('confirmFileName').textContent = fileName || 'No file selected';
            document.getElementById('confirmFileType').textContent = fileType ? `Type: ${fileType}` : '';
            pendingForm = form;
            document.getElementById('submitConfirmationModal').classList.remove('hidden');
        }
        
        function closeSubmitConfirmation() {
            document.getElementById('submitConfirmationModal').classList.add('hidden');
            pendingForm = null;
        }
        
        function confirmSubmitFile() {
            if (pendingForm) {
                pendingForm.submit();
            }
            closeSubmitConfirmation();
        }
        
        // Intercept form submissions to show confirmation modal
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            const action = form.querySelector('input[name="action"]');
            if (action && (action.value === 'upload' || action.value === 'update')) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const fileInput = form.querySelector('input[type="file"]');
                    if (fileInput && fileInput.files.length > 0) {
                        const file = fileInput.files[0];
                        const fileName = file.name;
                        const fileType = file.type || 'Unknown';
                        openSubmitConfirmation(form, fileName, fileType);
                    } else {
                        // No file selected, submit normally
                        form.submit();
                    }
                });
            }
        });
        
        // Close modals when clicking outside
        document.getElementById('submissionHistoryModal')?.addEventListener('click', function(event) {
            if (event.target === this) {
                closeSubmissionHistory();
            }
        });
        
        document.getElementById('submitConfirmationModal')?.addEventListener('click', function(event) {
            if (event.target === this) {
                closeSubmitConfirmation();
            }
        });

        // Re-upload button functionality - when clicked, show update form and trigger file input
        document.querySelectorAll('.re-upload-trigger').forEach(button => {
            button.addEventListener('click', function() {
                const inputId = this.getAttribute('data-input-id');
                const formId = this.getAttribute('data-form-id');
                const updateForm = document.getElementById(formId);
                const fileInput = document.getElementById(inputId);
                
                if (updateForm && fileInput) {
                    // Show the update form
                    updateForm.style.display = 'block';
                    // Trigger file input
                    fileInput.click();
                    // Scroll to the form after a short delay to show the selected file
                    setTimeout(() => {
                        updateForm.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                    }, 100);
                }
            });
        });

        // File viewer modal
        const fileModal = document.getElementById('fileViewerModal');
        const fileModalContainer = document.getElementById('fileModalContainer');
        const fileModalTitle = document.getElementById('fileModalTitle');
        const fileModalMeta = document.getElementById('fileModalMeta');
        const filePreviewContainer = document.getElementById('filePreviewContainer');
        const fileModalFullscreenBtn = document.getElementById('fileModalFullscreen');

        function buildAbsoluteUrl(path) {
            if (!path) return '';
            if (/^https?:\/\//i.test(path)) return path;
            return '../' + path.replace(/^\/+/, '');
        }

        function renderFilePreview(path, ext) {
            const url = buildAbsoluteUrl(path);
            if (!url) {
                return '<div class="h-full flex items-center justify-center text-gray-500 text-sm px-4">Preview unavailable for this file.</div>';
            }
            const loweredExt = (ext || '').toLowerCase();
            if (['png','jpg','jpeg','gif','bmp','webp','svg'].includes(loweredExt)) {
                return `<div class="h-full w-full flex items-center justify-center bg-white"><img src="${url}" alt="Preview" class="max-h-full max-w-full object-contain"></div>`;
            }
            if (loweredExt === 'pdf') {
                return `<iframe src="${url}#toolbar=0&navpanes=0" class="w-full h-full bg-white" frameborder="0"></iframe>`;
            }
            if (['xls','xlsx','csv'].includes(loweredExt)) {
                return `<iframe src="../ajax/view_excel.php?file=${encodeURIComponent(path)}" class="w-full h-full bg-white" frameborder="0"></iframe>`;
            }
            if (['mp4','webm','ogg'].includes(loweredExt)) {
                return `<video src="${url}" controls class="w-full h-full bg-black"></video>`;
            }
            return `<div class="h-full flex flex-col items-center justify-center gap-3 text-gray-600 text-sm px-6 text-center">
                        <p>Preview not available for this file type.</p>
                        <a href="${url}" target="_blank" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">Open in new tab</a>
                    </div>`;
        }

        function openFileModal(fileData) {
            fileModalTitle.textContent = fileData.name || 'Document preview';
            fileModalMeta.textContent = fileData.meta || '';
            filePreviewContainer.innerHTML = renderFilePreview(fileData.path, fileData.ext);
            fileModal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        }

        function closeFileModal() {
            if (document.fullscreenElement === fileModalContainer) {
                document.exitFullscreen();
            }
            fileModal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
        window.closeFileModal = closeFileModal;

        function updateModalFullscreenLabel(isFullscreen) {
            if (!fileModalFullscreenBtn) return;
            fileModalFullscreenBtn.textContent = isFullscreen ? 'Exit Fullscreen' : 'Fullscreen';
        }

        function toggleFileModalFullscreen() {
            if (!fileModalContainer) return;
            if (document.fullscreenElement === fileModalContainer) {
                document.exitFullscreen();
            } else if (fileModalContainer.requestFullscreen) {
                fileModalContainer.requestFullscreen();
            }
        }

        if (fileModalFullscreenBtn) {
            fileModalFullscreenBtn.addEventListener('click', toggleFileModalFullscreen);
        }

        document.addEventListener('fullscreenchange', () => {
            updateModalFullscreenLabel(document.fullscreenElement === fileModalContainer);
        });
        updateModalFullscreenLabel(false);

        document.querySelectorAll('.view-file-trigger').forEach(button => {
            button.addEventListener('click', () => {
                const dataset = button.dataset;
                if (!dataset.file) {
                    alert('File path unavailable for this submission.');
                    return;
                }
                openFileModal({
                    name: dataset.name,
                    path: dataset.file,
                    ext: dataset.ext,
                    meta: dataset.meta
                });
            });
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

            const submissionFilter = document.getElementById('submissionFilter');
            if (submissionFilter) {
                const cards = document.querySelectorAll('.submission-card');
                submissionFilter.addEventListener('change', () => {
                    const value = submissionFilter.value;
                    cards.forEach(card => {
                        const type = card.getAttribute('data-card-type');
                        card.style.display = (value === 'ALL' || value === type) ? '' : 'none';
                    });
                });
            }
        });
        
        function toggleSidebar() {
            const body = document.body;
            const collapsed = !body.classList.contains('sidebar-collapsed');
            body.classList.toggle('sidebar-collapsed');
            localStorage.setItem('sidebarCollapsed', collapsed ? 'true' : 'false');
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