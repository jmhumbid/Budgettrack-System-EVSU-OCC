<?php
session_start();
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../helpers/budget_workflow_notifications.php';

$notification = new Notification();
$activityLogger = new UserActivity();
$userRole = $_SESSION['user_role'] ?? '';

/**
 * Ensure history table exists for Utilization.
 */
function ensureUtilizationHistoryTable()
{
    try {
        $db = getDB();
        $db->exec("CREATE TABLE IF NOT EXISTS utilization_history (
            id INT PRIMARY KEY AUTO_INCREMENT,
            file_name VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size BIGINT NOT NULL,
            file_type VARCHAR(100) NOT NULL,
            uploaded_by INT NOT NULL,
            status VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } catch (PDOException $e) {
        // Table might already exist or fail; safe to ignore here
    }
}

/**
 * Insert a Utilization history record.
 */
function recordUtilizationHistory(PDO $db, array $data, string $status)
{
    $stmt = $db->prepare("INSERT INTO utilization_history (file_name, file_path, file_size, file_type, uploaded_by, status) VALUES (:name, :path, :size, :type, :user, :status)");
    $stmt->execute([
        ':name' => $data['name'],
        ':path' => $data['path'],
        ':size' => $data['size'],
        ':type' => $data['type'],
        ':user' => $data['user'],
        ':status' => $status
    ]);
}

ensureUtilizationHistoryTable();

function getDepartmentNameById($departmentId)
{
    if (!$departmentId) {
        return 'Unknown Department';
    }

    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT dept_name FROM departments WHERE id = :id");
        $stmt->execute([':id' => $departmentId]);
        $name = $stmt->fetchColumn();
        return $name ?: 'Unknown Department';
    } catch (PDOException $e) {
        return 'Unknown Department';
    }
}

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$profilePhoto = $_SESSION['profile_photo'] ?? '';
include __DIR__ . '/../components/profile_avatar.php';
$activeSidebar = 'utilization';

// For budget role, resolve their own department ID so the page can auto-select it
$budgetOwnDepartmentId = null;
if ($userRole === 'budget') {
    $sessionDeptId = $_SESSION['department_id'] ?? null;
    if ($sessionDeptId) {
        $budgetOwnDepartmentId = $sessionDeptId;
    } else {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT department_id FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && $row['department_id']) {
                $budgetOwnDepartmentId = $row['department_id'];
            } else {
                $stmt = $db->prepare("SELECT u.department_id FROM users u INNER JOIN roles r ON u.role_id = r.id WHERE r.role_name = 'budget' AND u.department_id IS NOT NULL LIMIT 1");
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row) $budgetOwnDepartmentId = $row['department_id'];
            }
        } catch (Exception $e) {}
    }
}

// Separate departments and offices based on fiduciary_type
// Non-Fiduciary = Department, Fiduciary = Office
$departments = [];
$offices = [];

try {
    $db = getDB();
    
    // Ensure fiduciary_type column exists
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM departments LIKE 'fiduciary_type'");
        if ($checkCol->rowCount() == 0) {
            $db->exec("ALTER TABLE departments ADD COLUMN fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code");
        }
    } catch (Exception $e) {}
    
    $stmt = $db->query("SELECT id, dept_name, dept_code, fiduciary_type FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $allDepartments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($allDepartments as $dept) {
        $fiduciaryType = $dept['fiduciary_type'] ?? 'Non-Fiduciary';
        if ($fiduciaryType === 'Fiduciary') {
            $offices[] = $dept;
        } else {
            $departments[] = $dept;
        }
    }
} catch (Exception $e) {
    // Handle error silently
    $departments = [];
    $offices = [];
}

// Handle file operations
$uploadMessage = '';
$uploadSuccess = false;
$uploadedFileName = '';
$autoOpenFile = null;

// Check for success message from URL parameters
if (isset($_GET['success']) && $_GET['success'] === '1' && isset($_GET['file'])) {
    $uploadSuccess = true;
    $uploadedFileName = urldecode($_GET['file']);
    $uploadMessage = 'File uploaded successfully!';
} elseif (isset($_GET['error']) && isset($_GET['file'])) {
    $uploadSuccess = false;
    $uploadedFileName = urldecode($_GET['file']);
    $uploadMessage = 'Failed to upload file.';
}

// Create allocations_files table if it doesn't exist
try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS allocations_files (
        id INT PRIMARY KEY AUTO_INCREMENT,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_size BIGINT NOT NULL,
        file_type VARCHAR(100) NOT NULL,
        uploaded_by INT NOT NULL,
        department_id INT NULL DEFAULT NULL,
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_uploaded_by (uploaded_by),
        INDEX idx_department_id (department_id)
    )");

    // Add department_id column if table exists but column doesn't
    try {
        $checkCol = $db->query("SHOW COLUMNS FROM allocations_files LIKE 'department_id'");
        if ($checkCol->rowCount() == 0) {
            $db->exec("ALTER TABLE allocations_files 
                       ADD COLUMN department_id INT NULL DEFAULT NULL AFTER uploaded_by,
                       ADD INDEX idx_department_id (department_id)");
        }
    } catch (Exception $e) {
        // Column might already exist
    }
} catch (PDOException $e) {
    // Table might already exist
}

// Create uploads/allocations directory if it doesn't exist
$uploadDir = __DIR__ . '/../uploads/allocations/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'upload' && isset($_FILES['allocation_file']) && $_FILES['allocation_file']['error'] === UPLOAD_ERR_OK) {
            $departmentId = isset($_POST['department_id']) && !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;

            if (!$departmentId) {
                $uploadMessage = 'Please select a department/office.';
            } else {
                $fileExt = pathinfo($_FILES['allocation_file']['name'], PATHINFO_EXTENSION);
                $fileName = 'ALLOCATION_' . time() . '.' . $fileExt;
                $filePath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['allocation_file']['tmp_name'], $filePath)) {
                    try {
                        $db = getDB();
                        // Check if department already has a file - replace active record but keep old file for archive
                        $checkStmt = $db->prepare("SELECT id, file_path FROM allocations_files WHERE department_id = :dept_id");
                        $checkStmt->execute([':dept_id' => $departmentId]);
                        $existingFile = $checkStmt->fetch(PDO::FETCH_ASSOC);

                        if ($existingFile) {
                            // Don't delete old file - keep it for archive viewing
                            // The old file path is already saved in history table
                            $deleteStmt = $db->prepare("DELETE FROM allocations_files WHERE id = :id");
                            $deleteStmt->execute([':id' => $existingFile['id']]);
                        }

                        $stmt = $db->prepare("INSERT INTO allocations_files (file_name, file_path, file_size, file_type, uploaded_by, department_id) VALUES (:name, :path, :size, :type, :user, :dept_id)");
                        $stmt->execute([
                            ':name' => $_FILES['allocation_file']['name'],
                            ':path' => 'uploads/allocations/' . $fileName,
                            ':size' => $_FILES['allocation_file']['size'],
                            ':type' => $_FILES['allocation_file']['type'],
                            ':user' => $_SESSION['user_id'],
                            ':dept_id' => $departmentId
                        ]);
                        $uploadSuccess = true;
                        $uploadMessage = 'File uploaded and assigned to department successfully!';
                        $fileName = htmlspecialchars($_FILES['allocation_file']['name'], ENT_QUOTES, 'UTF-8');
                        header('Location: utilization.php?success=1&file=' . urlencode($fileName));
                        exit;
                        $deptName = getDepartmentNameById($departmentId);
                        broadcastBudgetWorkflowChange(
                            $notification,
                            $activityLogger,
                            (int) $_SESSION['user_id'],
                            'Utilization',
                            $_FILES['allocation_file']['name'],
                            false,
                            ['offices', 'procurement', 'supply_office'],
                            $deptName
                        );
                    } catch (PDOException $e) {
                        $uploadMessage = 'Failed to save file information.';
                    }
                } else {
                    $uploadMessage = 'Failed to upload file.';
                }
            }
        } elseif ($_POST['action'] === 'update' && isset($_POST['file_id']) && isset($_FILES['allocation_file']) && $_FILES['allocation_file']['error'] === UPLOAD_ERR_OK) {
            $fileId = (int) $_POST['file_id'];
            $departmentId = isset($_POST['department_id']) ? (int) $_POST['department_id'] : null;

            try {
                $db = getDB();
                // Get existing file info
                $stmt = $db->prepare("SELECT file_path, department_id FROM allocations_files WHERE id = :id");
                $stmt->execute([':id' => $fileId]);
                $existingFile = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($existingFile) {
                    $fileExt = pathinfo($_FILES['allocation_file']['name'], PATHINFO_EXTENSION);
                    $fileName = 'ALLOCATION_' . time() . '.' . $fileExt;
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES['allocation_file']['tmp_name'], $filePath)) {
                        // Don't delete old file - keep it for archive viewing
                        // The old file path is already saved in history table

                        // Update record (preserve department_id)
                        $updateStmt = $db->prepare("UPDATE allocations_files SET file_name = :name, file_path = :path, file_size = :size, file_type = :type, uploaded_by = :user, uploaded_at = NOW() WHERE id = :id");
                        $updateStmt->execute([
                            ':name' => $_FILES['allocation_file']['name'],
                            ':path' => 'uploads/allocations/' . $fileName,
                            ':size' => $_FILES['allocation_file']['size'],
                            ':type' => $_FILES['allocation_file']['type'],
                            ':user' => $_SESSION['user_id'],
                            ':id' => $fileId
                        ]);
                        $uploadSuccess = true;
                        $uploadMessage = 'File updated successfully!';
                        $deptName = getDepartmentNameById($departmentId);
                        broadcastBudgetWorkflowChange(
                            $notification,
                            $activityLogger,
                            (int) $_SESSION['user_id'],
                            'Utilization',
                            $_FILES['allocation_file']['name'],
                            true,
                            ['offices', 'procurement', 'supply_office'],
                            $deptName
                        );
                    } else {
                        $uploadMessage = 'Failed to upload new file.';
                    }
                } else {
                    $uploadMessage = 'File not found.';
                }
            } catch (PDOException $e) {
                $uploadMessage = 'Failed to update file.';
            }
        } elseif ($_POST['action'] === 'archive_upload' && isset($_FILES['archive_file']) && $_FILES['archive_file']['error'] === UPLOAD_ERR_OK) {
            $fileExt = pathinfo($_FILES['archive_file']['name'], PATHINFO_EXTENSION);
            $fileName = 'ARCHIVE_' . time() . ($fileExt ? '.' . $fileExt : '');
            $filePath = $uploadDir . $fileName;

            if (move_uploaded_file($_FILES['archive_file']['tmp_name'], $filePath)) {
                try {
                    $db = getDB();

                    // Remove previous general utilization files from active list (keep physical files for archive)
                    // Don't delete physical files - keep them for archive viewing
                    $db->prepare("DELETE FROM allocations_files WHERE department_id IS NULL")->execute();

                    // Check if this is an update or new file (before deleting old files)
                    $checkStmt = $db->prepare("SELECT COUNT(*) as count FROM allocations_files WHERE department_id IS NULL");
                    $checkStmt->execute();
                    $countResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
                    $isUtilizationUpdate = ($countResult['count'] > 0);

                    $stmt = $db->prepare("INSERT INTO allocations_files (file_name, file_path, file_size, file_type, uploaded_by, department_id) VALUES (:name, :path, :size, :type, :user, NULL)");
                    $stmt->execute([
                        ':name' => $_FILES['archive_file']['name'],
                        ':path' => 'uploads/allocations/' . $fileName,
                        ':size' => $_FILES['archive_file']['size'],
                        ':type' => $_FILES['archive_file']['type'],
                        ':user' => $_SESSION['user_id']
                    ]);

                    recordUtilizationHistory($db, [
                        'name' => $_FILES['archive_file']['name'],
                        'path' => 'uploads/allocations/' . $fileName,
                        'size' => $_FILES['archive_file']['size'],
                        'type' => $_FILES['archive_file']['type'],
                        'user' => $_SESSION['user_id'],
                    ], $isUtilizationUpdate ? 'updated' : 'new');
                    $autoOpenFile = [
                        'id' => (int) $db->lastInsertId(),
                        'path' => 'uploads/allocations/' . $fileName,
                        'type' => strtolower($fileExt),
                        'name' => $_FILES['archive_file']['name']
                    ];
                    broadcastBudgetWorkflowChange(
                        $notification,
                        $activityLogger,
                        (int) $_SESSION['user_id'],
                        'Utilization',
                        $_FILES['archive_file']['name'],
                        false,
                        ['offices', 'procurement', 'supply_office'],
                        'all departments'
                    );
                    $uploadSuccess = true;
                    $uploadMessage = 'File archived successfully!';
                } catch (PDOException $e) {
                    $uploadMessage = 'Failed to save archived file information.';
                }
            } else {
                $uploadMessage = 'Failed to upload file.';
            }
        } elseif ($_POST['action'] === 'remove' && isset($_POST['file_id'])) {
            try {
                $db = getDB();
                // Don't delete the physical file - keep it for archive viewing
                // Only remove from the active files table
                $stmt = $db->prepare("DELETE FROM allocations_files WHERE id = :id");
                $stmt->execute([':id' => $_POST['file_id']]);
                $uploadSuccess = true;
                $uploadMessage = 'File removed from department successfully!';

                // Redirect to clear any cached state
                header('Location: utilization.php?removed=1');
                exit;
            } catch (PDOException $e) {
                $uploadMessage = 'Failed to remove file.';
            }
        } elseif ($_POST['action'] === 'delete' && isset($_POST['file_id'])) {
            try {
                $db = getDB();
                // Don't delete the physical file - keep it for archive viewing
                // Only remove from the active files table
                $stmt = $db->prepare("DELETE FROM allocations_files WHERE id = :id");
                $stmt->execute([':id' => $_POST['file_id']]);

                // Redirect to clear any cached state and localStorage
                header('Location: utilization.php?deleted=1');
                exit;
            } catch (PDOException $e) {
                $uploadMessage = 'Failed to delete file.';
            }
        }
    }
}


// Get the latest general utilization file (unassigned to a department)
$currentArchiveFile = null;
try {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM allocations_files WHERE department_id IS NULL ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->execute();
    $currentArchiveFile = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $currentArchiveFile = null;
}

if (!$autoOpenFile && $currentArchiveFile) {
    $currentExt = strtolower(pathinfo($currentArchiveFile['file_name'], PATHINFO_EXTENSION));
    $autoOpenFile = [
        'id' => (int) $currentArchiveFile['id'],
        'path' => $currentArchiveFile['file_path'],
        'type' => $currentExt,
        'name' => $currentArchiveFile['file_name']
    ];
}

$utilizationHistoryList = [];
try {
    $db = getDB();
    $historyStmt = $db->prepare("SELECT * FROM utilization_history ORDER BY created_at DESC LIMIT 8");
    $historyStmt->execute();
    $utilizationHistoryList = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $utilizationHistoryList = [];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin • Utilization</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { maroon: '#800000', 'maroon-dark': '#5a0000' } } } }
    </script>
    <style>
        #particularsModal,
        #purchaseRequestTextModal,
        #prNumberModal,
        #bulkEntryModal {
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
        }

        #particularsModal.opacity-100,
        #purchaseRequestTextModal.opacity-100,
        #prNumberModal.opacity-100,
        #bulkEntryModal.opacity-100 {
            opacity: 1;
        }

        #particularsModalContent,
        #purchaseRequestTextModalContent,
        #prNumberModalContent,
        #bulkEntryModalContent {
            transition: transform 0.3s ease-in-out, opacity 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <?php include __DIR__ . '/../components/admin_sidebar.php'; ?>
        <main class="flex-1 flex flex-col" data-main-content>
            <header class="bg-gradient-to-r from-maroon via-red-700 to-red-800 shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-2xl font-bold text-white">Budget Utilization</h1>
                            <p class="text-red-100 text-sm">Track and manage budget allocations and expenditures</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <!-- Notification Bell -->
                            <?php
                            $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
                            $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
                            include __DIR__ . '/../components/notification_bell.php';
                            ?>

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

                                <!-- Profile Dropdown -->
                                <div id="profileDropdown"
                                    class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 hidden">
                                    <div class="py-1">
                                        <a href="profile.php"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                                </path>
                                            </svg>
                                            Profile
                                        </a>
                                        <a href="change_password.php"
                                            class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z">
                                                </path>
                                                </path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            </svg>
                                            Change Password
                                        </a>
                                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'budget'): ?>
                                            <a href="super_admin_dashboard.php"
                                                class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                    </path>
                                                </svg>
                                                Admin Panel
                                            </a>
                                        <?php endif; ?>
                                        <div class="border-t border-gray-100"></div>
                                        <button onclick="confirmLogout()"
                                            class="flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1">
                                                </path>
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
            <div class="flex-1 p-6 space-y-6">
                <!-- Main Card -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-200 overflow-hidden">
                    <!-- Department Selection Section -->
                    <div class="px-8 py-6 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200">
                        <div class="flex items-end gap-4">
                            <div class="flex-1 grid grid-cols-[1fr_1fr_auto] gap-4 items-start">
                                <!-- Department Search -->
                                <div>
                                    <label for="departmentSearch"
                                        class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        Search & Select Department
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" id="departmentSearch" placeholder="Search department..."
                                            class="w-full pl-12 pr-20 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 placeholder-gray-400 font-medium shadow-sm"
                                            autocomplete="off" />
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-2">
                                            <button id="clearDepartmentSearch" onclick="clearDepartmentSearch()"
                                                class="hidden text-gray-400 hover:text-red-600 transition-colors p-1"
                                                title="Clear">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <svg id="departmentDropdownIcon"
                                                class="w-5 h-5 text-gray-400 cursor-pointer hover:text-gray-600 transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                        <input type="hidden" id="departmentSelect" name="departmentSelect" value="">
                                        <div id="departmentDropdown"
                                            class="absolute z-50 w-full mt-1 bg-white border-2 border-gray-300 rounded-xl shadow-xl max-h-60 overflow-auto hidden">
                                            <div class="py-2">
                                                <?php foreach ($departments as $dept): ?>
                                                    <div class="department-option px-4 py-3 hover:bg-maroon hover:text-white cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                                                        data-id="<?php echo htmlspecialchars($dept['id']); ?>"
                                                        data-name="<?php echo htmlspecialchars($dept['dept_name']); ?>">
                                                        <div class="font-medium">
                                                            <?php echo htmlspecialchars($dept['dept_name']); ?>
                                                        </div>
                                                        <?php if (!empty($dept['dept_code'])): ?>
                                                            <div class="text-xs opacity-75">
                                                                <?php echo htmlspecialchars($dept['dept_code']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="selectedDepartmentDisplay"
                                        class="mt-2 flex items-center gap-2 text-sm font-semibold text-maroon hidden">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-gray-600">Selected: </span><span id="selectedDepartmentName"
                                            class="text-maroon font-bold"></span>
                                    </div>
                                </div>

                                <!-- Office Search -->
                                <div>
                                    <label for="officeSearch"
                                        class="block text-sm font-bold text-gray-700 mb-2 uppercase tracking-wide">
                                        <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        Search & Select Office
                                    </label>
                                    <div class="relative">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                            </svg>
                                        </div>
                                        <input type="text" id="officeSearch" placeholder="Search office..."
                                            class="w-full pl-12 pr-20 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 placeholder-gray-400 font-medium shadow-sm"
                                            autocomplete="off" />
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center gap-2">
                                            <button id="clearOfficeSearch" onclick="clearOfficeSearch()"
                                                class="hidden text-gray-400 hover:text-red-600 transition-colors p-1"
                                                title="Clear">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            <svg id="officeDropdownIcon"
                                                class="w-5 h-5 text-gray-400 cursor-pointer hover:text-gray-600 transition-colors"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 9l-7 7-7-7"></path>
                                            </svg>
                                        </div>
                                        <input type="hidden" id="officeSelect" name="officeSelect" value="">
                                        <div id="officeDropdown"
                                            class="absolute z-50 w-full mt-1 bg-white border-2 border-gray-300 rounded-xl shadow-xl max-h-60 overflow-auto hidden">
                                            <div class="py-2">
                                                <?php foreach ($offices as $office): ?>
                                                    <div class="office-option px-4 py-3 hover:bg-maroon hover:text-white cursor-pointer transition-colors border-b border-gray-100 last:border-b-0"
                                                        data-id="<?php echo htmlspecialchars($office['id']); ?>"
                                                        data-name="<?php echo htmlspecialchars($office['dept_name']); ?>">
                                                        <div class="font-medium">
                                                            <?php echo htmlspecialchars($office['dept_name']); ?>
                                                        </div>
                                                        <?php if (!empty($office['dept_code'])): ?>
                                                            <div class="text-xs opacity-75">
                                                                <?php echo htmlspecialchars($office['dept_code']); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- History Button - Third Column -->
                                <div class="pt-7">
                                    <button onclick="showHistory()"
                                        class="px-6 py-3 bg-gradient-to-r from-gray-600 to-gray-700 text-white rounded-xl hover:from-gray-700 hover:to-gray-800 transition-all font-semibold shadow-lg hover:shadow-xl flex items-center gap-2 relative transform hover:scale-105">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        History
                                        <span id="historyBadge"
                                            class="ml-2 px-2.5 py-1 bg-maroon text-white text-xs font-semibold rounded-full shadow-md hidden">0</span>
                                    </button>
                                </div>
                                <!-- Fiscal Year Selector - Fourth Column -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2 flex items-center gap-2">
                                        <svg class="w-5 h-5 text-maroon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        Fiscal Year
                                    </label>
                                    <select id="fiscalYearSelect" onchange="changeFiscalYear()"
                                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none transition-all bg-white text-gray-900 font-medium shadow-sm">
                                        <?php
                                        $currentYear = date('Y');
                                        for ($year = $currentYear + 1; $year >= $currentYear - 10; $year--) {
                                            $selected = ($year == $currentYear) ? 'selected' : '';
                                            echo "<option value='$year' $selected>$year</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Budget Utilization Table -->
                    <div class="p-8">
                        <div class="overflow-x-auto rounded-xl border-2 border-gray-200 shadow-lg bg-white">
                            <table class="w-full border-collapse">
                                <thead>
                                    <tr class="bg-gradient-to-r from-maroon via-red-700 to-red-800">
                                        <th
                                            class="border-b-2 border-red-900 py-3 px-4 text-left font-bold text-white uppercase text-xs tracking-wider">
                                            Expense Categories</th>
                                        <th
                                            class="border-b-2 border-red-900 py-3 px-4 text-center font-bold text-white uppercase text-xs tracking-wider">
                                            Account Code</th>
                                        <th
                                            class="border-b-2 border-red-900 py-3 px-4 text-center font-bold text-white uppercase text-xs tracking-wider">
                                            Allocated Budget</th>
                                        <th
                                            class="border-b-2 border-red-900 py-3 px-4 text-center font-bold text-white uppercase text-xs tracking-wider">
                                            Deductions (Expenditures)</th>
                                        <th
                                            class="border-b-2 border-red-900 py-3 px-4 text-center font-bold text-white uppercase text-xs tracking-wider">
                                            Total Balance</th>
                                        <th
                                            class="border-b-2 border-red-900 py-3 px-4 text-center font-bold text-white uppercase text-xs tracking-wider w-16">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="utilizationTableBody" class="bg-white divide-y divide-gray-200">
                                    <!-- Entries will be added here -->
                                </tbody>
                                <tfoot>
                                    <tr
                                        class="bg-gradient-to-r from-gray-100 via-gray-50 to-gray-100 border-t-4 border-maroon">
                                        <td class="py-3 px-4 text-left font-bold text-gray-900 text-base">TOTAL:</td>
                                        <td class="py-3 px-4"></td>
                                        <td class="py-3 px-4 text-right font-bold text-maroon text-base"
                                            id="totalAllocatedBudget">₱0.00</td>
                                        <td class="py-3 px-4 text-right font-bold text-red-600 text-base"
                                            id="totalDeductions">₱0.00</td>
                                        <td class="py-3 px-4 text-right font-bold text-green-600 text-base"
                                            id="totalBalance">₱0.00</td>
                                        <td class="py-3 px-4"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <!-- Empty State -->
                        <div id="emptyState" class="text-center py-12 hidden">
                            <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">No entries yet</p>
                            <p class="text-gray-400 text-sm mt-1">Click "Add Entry" to start tracking budget utilization
                            </p>
                        </div>

                        <!-- Add Entry Button -->
                        <div class="mt-6">
                            <button onclick="openBulkEntryModal()"
                                class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-maroon to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Add Entry
                            </button>
                        </div>
                    </div>

                    <!-- Action Buttons Section -->
                    <div class="px-8 py-6 bg-gray-50 border-t border-gray-200">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex flex-wrap gap-4">
                                <button onclick="handlePurchaseRequest()"
                                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    Purchase Request
                                </button>
                                <button onclick="handleTravels()"
                                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                                        </path>
                                    </svg>
                                    Travels
                                </button>
                                <button onclick="handlePriorYears()"
                                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Prior Years
                                </button>
                            </div>
                            <div class="flex items-center gap-3" style="position: relative; z-index: 10;">
                                <button onclick="clearUtilizationData(); return false;"
                                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                    style="cursor: pointer; pointer-events: auto;"
                                    type="button">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Clear Data
                                </button>
                                <button onclick="generateSummary()"
                                    class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-maroon to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5"
                                    style="cursor: pointer; pointer-events: auto;"
                                    type="button">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                    Generate Summary
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </main>
    </div>

    <!-- Purchase Request Modal -->
    <div id="purchaseRequestModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-[95vw] w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Purchase Request</h2>
                    <p class="text-blue-100 text-sm mt-1">(the same with the expense add an add entry button here)</p>
                </div>
                <button onclick="closePurchaseRequestModal()"
                    class="text-white hover:text-blue-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- PPMP Selection Section -->
                <div class="mb-6 p-4 bg-blue-50 border-2 border-blue-200 rounded-xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-bold text-blue-900">Select from PPMP</h3>
                        </div>
                        <button onclick="openPPMPSelectionModal()" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all font-semibold text-sm flex items-center gap-2 shadow-md">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Select PPMP Items
                        </button>
                    </div>
                    <p class="text-sm text-blue-700">You can select items from approved PPMPs to automatically fill purchase request details.</p>
                </div>

                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 via-gray-100 to-gray-50">
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Purchase Request</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Particulars</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    PR No. / PO No.</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Date of Obligation
                                    <div class="text-xs font-normal text-gray-500 mt-1">(Use Timestamp Here)</div>
                                </th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-right font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Amount</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-center font-bold text-gray-800 uppercase text-sm tracking-wide w-24">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="purchaseRequestTableBody" class="bg-white">
                            <!-- Rows will be added here dynamically -->
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-to-r from-gray-100 to-gray-50 border-t-4 border-blue-600">
                                <td class="py-4 px-6 text-right font-bold text-gray-900 text-lg" colspan="4">TOTAL:</td>
                                <td class="py-4 px-6 text-right font-bold text-blue-600 text-lg"
                                    id="purchaseRequestTotal">₱0.00</td>
                                <td class="py-4 px-6"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Add Entry Button -->
                <div class="mt-6">
                    <button onclick="addPurchaseRequestEntry()"
                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Add Entry
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closePurchaseRequestModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
                <!-- Save button removed - entries auto-save now -->
            </div>
        </div>
    </div>

    <!-- PPMP Selection Modal -->
    <div id="ppmpSelectionModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Select PPMP Items</h2>
                    <p class="text-purple-100 text-sm mt-1" id="ppmpSelectionDepartmentName">Select items from approved PPMPs</p>
                </div>
                <button onclick="closePPMPSelectionModal()"
                    class="text-white hover:text-purple-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Tab Navigation -->
            <div class="flex border-b border-gray-200 px-8 pt-4">
                <button onclick="switchPPMPSelectionTab('ppmp')" id="ppmpSelectionTab-ppmp" 
                    class="ppmp-selection-tab px-6 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    PPMP
                </button>
                <button onclick="switchPPMPSelectionTab('supplemental')" id="ppmpSelectionTab-supplemental" 
                    class="ppmp-selection-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-yellow-600">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Supplemental
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- PPMP Tab Content -->
                <div id="ppmpSelectionContent-ppmp" class="ppmp-selection-content">
                    <div class="mb-4">
                        <input type="text" id="ppmpItemsSearch" oninput="filterPPMPItems('ppmp')" placeholder="Search PPMP items..." class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 outline-none text-sm">
                    </div>
                    <div id="ppmpItemsContainer" class="space-y-4">
                        <!-- PPMP items will be loaded here -->
                    </div>
                    <div id="ppmpItemsLoading" class="text-center py-8 hidden">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-600"></div>
                        <p class="mt-2 text-gray-600">Loading PPMP items...</p>
                    </div>
                    <div id="ppmpItemsEmpty" class="text-center py-8 hidden">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-gray-600 font-medium">No approved PPMP items found</p>
                        <p class="text-gray-500 text-sm mt-1">Please ensure the department has approved PPMPs</p>
                    </div>
                </div>

                <!-- Supplemental Tab Content -->
                <div id="ppmpSelectionContent-supplemental" class="ppmp-selection-content hidden">
                    <div class="mb-4">
                        <input type="text" id="supplementalItemsSearch" oninput="filterPPMPItems('supplemental')" placeholder="Search Supplemental items..." class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 outline-none text-sm">
                    </div>
                    <div id="supplementalItemsContainer" class="space-y-4">
                        <!-- Supplemental items will be loaded here -->
                    </div>
                    <div id="supplementalItemsLoading" class="text-center py-8 hidden">
                        <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-yellow-600"></div>
                        <p class="mt-2 text-gray-600">Loading Supplemental items...</p>
                    </div>
                    <div id="supplementalItemsEmpty" class="text-center py-8 hidden">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        <p class="text-gray-600 font-medium">No approved Supplemental items found</p>
                        <p class="text-gray-500 text-sm mt-1">Please ensure the department has approved Supplemental PPMPs</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <span id="ppmpSelectedCount">0</span> item(s) selected
                </div>
                <div class="flex gap-4">
                    <button onclick="closePPMPSelectionModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                        Cancel
                    </button>
                    <button onclick="addSelectedPPMPItems()"
                        class="px-6 py-3 bg-gradient-to-r from-purple-600 to-purple-700 text-white rounded-xl hover:from-purple-700 hover:to-purple-800 transition-all font-semibold shadow-lg">
                        Add Selected Items
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Prior Years Modal -->
    <div id="priorYearsModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-[95vw] w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Prior Years</h2>
                    <p class="text-orange-100 text-sm mt-1">Manage prior years expense data by category</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-white text-sm font-semibold whitespace-nowrap">Fiscal Year:</label>
                        <select id="priorYearsFiscalYear" onchange="onPriorYearsFiscalYearChange()"
                            class="px-4 py-2 bg-white rounded-xl border border-white border-opacity-30 font-semibold text-sm cursor-pointer transition-all outline-none text-gray-900">
                        </select>
                    </div>
                    <button onclick="addPriorYearsColumn()"
                        class="flex items-center gap-1.5 px-3 py-1.5 bg-white bg-opacity-20 text-white rounded-lg hover:bg-opacity-30 transition-all text-sm font-semibold border border-white border-opacity-30" title="Add Column">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                        Add Column
                    </button>
                    <button onclick="showPriorYearsHistory()"
                        class="text-white hover:text-orange-200 transition-colors p-2" title="View History">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </button>
                    <button onclick="closePriorYearsModal()"
                        class="text-white hover:text-orange-200 transition-colors p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="w-full border-collapse" style="min-width: 1200px;">
                        <thead>
                            <tr class="bg-gradient-to-r from-orange-50 via-orange-100 to-orange-50">
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-left font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 200px;">Expense Categories</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 100px;">Stud. Dev</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 100px;">Fac. Dev</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 110px;">Curr. Dev</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 110px;">Facil. Dev</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 100px;">Dev Fee</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 100px;">Lab Fee</th>
                                <th class="border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide"
                                    style="min-width: 100px;">Comp Fee</th>
                            </tr>
                        </thead>
                        <tbody id="priorYearsTableBody" class="bg-white divide-y divide-gray-200">
                            <!-- Entries will be added here -->
                        </tbody>
                        <tfoot>
                            <tr
                                class="bg-gradient-to-r from-orange-100 via-orange-50 to-orange-100 border-t-4 border-orange-500">
                                <td class="py-4 px-4 text-left font-bold text-gray-900 text-sm">TOTAL:</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalStudentDev">₱0.00</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalFacultyDev">₱0.00</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalCurriculumDev">₱0.00</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalFacilitiesDev">₱0.00</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalDevFee">₱0.00</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalLabFee">₱0.00</td>
                                <td class="py-4 px-4 text-right font-bold text-orange-700 text-sm"
                                    id="priorYearsTotalCompFee">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Sync Info -->
                <div class="mt-4 p-4 bg-orange-50 border border-orange-200 rounded-xl">
                    <div class="flex items-center gap-2 text-orange-700 text-sm">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span>Expense categories are synced from the utilization table. You can also add custom entries
                            below.</span>
                    </div>
                </div>

                <!-- Add Entry Button -->
                <div class="mt-6">
                    <button onclick="addPriorYearsEntry()"
                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Add Entry
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-between gap-4">
                <div>
                    <?php if ($userRole === 'budget' || $userRole === 'school_admin'): ?>
                    <button onclick="deleteAllPriorYears()"
                        class="px-6 py-3 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl hover:from-red-600 hover:to-red-700 transition-all font-semibold shadow-lg">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete All
                        </span>
                    </button>
                    <?php endif; ?>
                </div>
                <div class="flex gap-4">
                    <button onclick="closePriorYearsModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                        Close
                    </button>
                    <button onclick="savePriorYearsEntries()"
                        class="px-6 py-3 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-xl hover:from-orange-600 hover:to-orange-700 transition-all font-semibold shadow-lg">
                        <span class="flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                                </path>
                            </svg>
                            Save Prior Years
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Modal -->
    <div id="summaryModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="summaryModalContent">
            <!-- Modal Header -->
            <div
                class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Budget Utilization Summary</h2>
                    <p class="text-red-100 text-sm mt-1" id="summaryDepartmentName">Department/Office: -</p>
                </div>
                <button onclick="closeSummaryModal()" class="text-white hover:text-red-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body - Receipt Style -->
            <div class="flex-1 overflow-y-auto p-8">
                <!-- Receipt Header -->
                <div class="text-center mb-6 pb-6 border-b-2 border-gray-300">
                    <h2 class="text-2xl font-bold text-maroon mb-2">Budget Utilization Summary</h2>
                    <p class="text-sm text-gray-600" id="summaryDate"></p>
                    <p class="text-sm text-gray-600 font-semibold" id="summaryDepartmentNameReceipt">Department/Office:
                        -</p>
                </div>

                <!-- Purchase Requests Breakdown -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Purchase Requests</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Purchase Request</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Particulars</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">PR No. / PO No.</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Date</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="summaryPRBody" class="bg-white">
                                <!-- PR entries will be added here -->
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3" colspan="4">Total</td>
                                    <td class="text-right py-2 px-3" id="summaryPRTotal">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Travels Breakdown -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Travels</h3>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-300">
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Travelled</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Event/Activity</th>
                                    <th class="text-left py-2 px-3 font-semibold text-gray-700">Date</th>
                                    <th class="text-right py-2 px-3 font-semibold text-gray-700">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="summaryTravelsBody" class="bg-white">
                                <!-- Travels entries will be added here -->
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3" colspan="3">Total</td>
                                    <td class="text-right py-2 px-3" id="summaryTravelsTotal">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
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
                            <tbody id="summaryPRDeductionsBody" class="bg-white">
                                <!-- PR Deductions entries will be added here -->
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3" colspan="2">Total</td>
                                    <td class="text-right py-2 px-3" id="summaryPRDeductionsTotal">₱0.00</td>
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
                            <tbody id="summaryTravelsDeductionsBody" class="bg-white">
                                <!-- Travels Deductions entries will be added here -->
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3" colspan="4">Total</td>
                                    <td class="text-right py-2 px-3" id="summaryTravelsDeductionsTotal">₱0.00</td>
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
                            <tbody id="summaryUtilizationBody" class="bg-white">
                                <!-- Utilization entries will be added here -->
                            </tbody>
                            <tfoot class="border-t-2 border-gray-400">
                                <tr class="font-bold">
                                    <td class="py-2 px-3">Total</td>
                                    <td class="py-2 px-3"></td>
                                    <td class="text-right py-2 px-3" id="summaryTotalAllocated">₱0.00</td>
                                    <td class="text-right py-2 px-3" id="summaryTotalDeductions">₱0.00</td>
                                    <td class="text-right py-2 px-3" id="summaryTotalBalance">₱0.00</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Overall Total Summary -->
                <div class="mt-6 pt-6 border-t-2 border-maroon">
                    <div class="flex items-center justify-between">
                        <span class="text-xl font-bold text-maroon">Overall Total</span>
                        <span class="text-xl font-bold text-maroon" id="summaryOverallTotal">₱0.00</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-center gap-4">
                <button id="saveUtilizationSummaryBtn" onclick="confirmAndSaveUtilizationSummary()"
                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all font-semibold shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Save Summary
                </button>
                <button onclick="printSummary()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Download Summary
                </button>
                <button onclick="closeSummaryModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Travel Event Modal (Large Textarea) -->
    <div id="travelEventModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="travelEventModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Enter Event/Activity</h2>
                    <p class="text-green-100 text-sm mt-1">Provide a detailed description of the event or activity</p>
                </div>
                <button onclick="closeTravelEventModal()" class="text-white hover:text-green-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <textarea id="travelEventTextarea"
                    class="w-full h-64 px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all text-gray-900 font-medium text-lg resize-none"
                    placeholder="Enter the event or activity description..."></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeTravelEventModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Cancel
                </button>
                <button onclick="saveTravelEvent()"
                    class="px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all font-semibold shadow-lg">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Particulars Modal (Large Textarea) -->
    <div id="particularsModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="particularsModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Enter Particulars / Reason</h2>
                    <p class="text-blue-100 text-sm mt-1">Provide a detailed explanation for this purchase request</p>
                </div>
                <button onclick="closeParticularsModal()" class="text-white hover:text-blue-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <textarea id="particularsTextarea"
                    class="w-full h-64 px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-900 font-medium text-lg resize-none"
                    placeholder="Enter the reason or detailed explanation for this purchase request..."></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeParticularsModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Cancel
                </button>
                <button onclick="saveParticulars()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Purchase Request Text Modal (Large Textarea) -->
    <div id="purchaseRequestTextModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="purchaseRequestTextModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Enter Purchase Request</h2>
                    <p class="text-blue-100 text-sm mt-1">Provide detailed information about the purchase request</p>
                </div>
                <button onclick="closePurchaseRequestTextModal()" class="text-white hover:text-blue-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <textarea id="purchaseRequestTextarea"
                    class="w-full h-64 px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-900 font-medium text-lg resize-none"
                    placeholder="Enter the purchase request details..."></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closePurchaseRequestTextModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Cancel
                </button>
                <button onclick="savePurchaseRequestText()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- PR Number Modal (Large Textarea) -->
    <div id="prNumberModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="prNumberModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Enter PR No. / PO No.</h2>
                    <p class="text-blue-100 text-sm mt-1">Provide the PR number or PO number for this request</p>
                </div>
                <button onclick="closePRNumberModal()" class="text-white hover:text-blue-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <textarea id="prNumberTextarea"
                    class="w-full h-64 px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all text-gray-900 font-medium text-lg resize-none"
                    placeholder="Enter the PR number or PO number..."></textarea>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closePRNumberModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Cancel
                </button>
                <button onclick="savePRNumber()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-xl hover:from-blue-700 hover:to-blue-800 transition-all font-semibold shadow-lg">
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- Add Amount Modal -->
    <div id="addAmountModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="addAmountModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Add Amount</h2>
                    <p class="text-red-100 text-sm mt-1">Manually add to deduction amount</p>
                </div>
                <button onclick="closeAddAmountModal()" class="text-white hover:text-red-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="p-8">
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Current Deduction Amount</label>
                    <input 
                        type="text" 
                        id="currentDeductionAmount"
                        class="w-full px-4 py-3 border-2 border-gray-300 rounded-xl bg-gray-50 text-right font-bold text-gray-900 text-lg"
                        readonly
                        value="₱0.00"
                    >
                </div>
                <div class="mb-6">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Amount to Add</label>
                    <input 
                        type="text" 
                        id="addAmountInput"
                        class="w-full px-4 py-3 border-2 border-red-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon transition-all text-right font-bold text-gray-900 text-lg"
                        placeholder="₱0.00"
                    >
                </div>
                <div class="bg-red-50 border-2 border-red-200 rounded-xl p-4">
                    <div class="flex items-center justify-between text-sm font-semibold text-gray-700 mb-2">
                        <span>New Total:</span>
                        <span id="newTotalAmount" class="text-xl text-maroon font-bold">₱0.00</span>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeAddAmountModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Cancel
                </button>
                <button onclick="confirmAddAmount()"
                    class="px-6 py-3 bg-gradient-to-r from-maroon via-red-700 to-red-800 text-white rounded-xl hover:from-red-800 hover:to-red-900 transition-all font-semibold shadow-lg">
                    Add Amount
                </button>
            </div>
        </div>
    </div>

    <!-- View Details Modal (for Purchase Request, PR No./PO No., Date) -->
    <div id="viewDetailsModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[70] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[80vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="viewDetailsModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white" id="viewDetailsModalTitle">View Details</h2>
                    <p class="text-blue-100 text-sm mt-1" id="viewDetailsModalSubtitle">View full content</p>
                </div>
                <button onclick="closeViewDetailsModal()" class="text-white hover:text-blue-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="bg-gray-50 rounded-xl p-6 border-2 border-gray-200">
                    <div class="text-gray-900 font-medium text-lg whitespace-pre-wrap break-words"
                        id="viewDetailsContent">
                        <!-- Content will be displayed here -->
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeViewDetailsModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- History Modal -->
    <div id="historyModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-[80] hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[85vh] overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="historyModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-gray-700 to-gray-800 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Utilization History</h2>
                    <p class="text-gray-200 text-sm mt-1" id="historyDepartmentName">View all changes and activities</p>
                </div>
                <button onclick="closeHistoryModal()" class="text-white hover:text-gray-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="bg-white rounded-xl border border-gray-200">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Action</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Details</th>
                                <th class="py-3 px-4 text-left text-sm font-semibold text-gray-700">Date & Time</th>
                                <th class="py-3 px-4 text-center text-sm font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody">
                            <!-- History entries will be loaded here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeHistoryModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Deduction Entry Selection Modal -->
    <div id="deductionEntryModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-maroon to-red-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white" id="deductionEntryModalTitle">Select Entry</h2>
                    <p class="text-red-100 text-sm mt-1" id="deductionEntryModalSubtitle">Choose an entry to add to
                        deduction</p>
                </div>
                <button onclick="closeDeductionEntryModal()"
                    class="text-white hover:text-red-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Tab Navigation (only for purchase_request source type) -->
            <div id="deductionSourceTabs" class="hidden flex border-b border-gray-200 px-8 pt-4">
                <button onclick="switchDeductionSourceTab('ppmp')" id="deductionSourceTab-ppmp" 
                    class="deduction-source-tab px-6 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    PPMP
                </button>
                <button onclick="switchDeductionSourceTab('supplemental')" id="deductionSourceTab-supplemental" 
                    class="deduction-source-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-yellow-600">
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Supplemental
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-6">
                <div class="mb-3">
                    <input type="text" id="deductionEntrySearch" oninput="filterDeductionEntries()" placeholder="Search entries..." class="w-full px-4 py-2 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon outline-none text-sm">
                </div>
                <div class="mb-4 flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" id="selectAllEntries" onchange="toggleSelectAllEntries()"
                            class="w-4 h-4 text-maroon border-gray-300 rounded focus:ring-maroon">
                        <span class="text-sm font-semibold text-gray-700">Select All</span>
                    </label>
                    <div class="text-sm text-gray-600">
                        <span id="selectedCount">0</span> selected
                    </div>
                </div>
                <div id="deductionEntryModalBody" class="space-y-2">
                    <div class="text-center py-8">
                        <div class="text-gray-500">Loading entries...</div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Total Selected Amount: <span id="selectedTotalAmount" class="font-bold text-maroon">₱0.00</span>
                </div>
                <div class="flex gap-4">
                    <button onclick="closeDeductionEntryModal()"
                        class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                        Close
                    </button>
                    <button onclick="addSelectedDeductions()"
                        class="px-6 py-3 bg-gradient-to-r from-maroon to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-lg">
                        Add Selected
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Travels Modal -->
    <div id="travelsModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-[95vw] w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Travels</h2>
                    <p class="text-green-100 text-sm mt-1">(the same with the expense add an add entry button here)</p>
                </div>
                <button onclick="closeTravelsModal()" class="text-white hover:text-green-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 via-gray-100 to-gray-50">
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Travelled</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Event/Activity</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-left font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Date
                                    <div class="text-xs font-normal text-gray-500 mt-1">(Use Timestamp Here)</div>
                                </th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-right font-bold text-gray-800 uppercase text-sm tracking-wide">
                                    Amount</th>
                                <th
                                    class="border-b-2 border-gray-300 py-4 px-6 text-center font-bold text-gray-800 uppercase text-sm tracking-wide w-24">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="travelsTableBody" class="bg-white">
                            <!-- Rows will be added here dynamically -->
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-to-r from-gray-100 to-gray-50 border-t-4 border-green-600">
                                <td class="py-4 px-6 text-right font-bold text-gray-900 text-lg" colspan="3"></td>
                                <td class="py-4 px-6 text-right font-bold text-red-600 text-lg">Total:</td>
                                <td class="py-4 px-6 text-right font-bold text-green-600 text-lg" id="travelsTotal">
                                    ₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Add Entry Button -->
                <div class="mt-6">
                    <button onclick="addTravelsEntry()"
                        class="flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-xl hover:from-green-700 hover:to-green-800 transition-all font-semibold shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Add Entry
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeTravelsModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Amount Deduction Modal -->
    <div id="amountDeductionModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-maroon to-red-700 px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold text-white">Honoraria</h2>
                    <p class="text-red-100 text-xs mt-1">Manage amount for overtime and part time</p>
                </div>
                <button onclick="closeAmountDeductionModal()"
                    class="text-white hover:text-red-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-4">
                <div class="overflow-x-auto rounded-xl border border-gray-200 shadow-sm">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-gradient-to-r from-gray-50 via-gray-100 to-gray-50">
                                <th
                                    class="border-b-2 border-gray-300 py-3 px-4 text-left font-bold text-gray-800 uppercase text-xs tracking-wide">
                                    Date</th>
                                <th
                                    class="border-b-2 border-gray-300 py-3 px-4 text-right font-bold text-gray-800 uppercase text-xs tracking-wide">
                                    Amount</th>
                                <th
                                    class="border-b-2 border-gray-300 py-3 px-4 text-center font-bold text-gray-800 uppercase text-xs tracking-wide w-20">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody id="amountDeductionTableBody" class="bg-white">
                            <!-- Rows will be added here dynamically -->
                        </tbody>
                        <tfoot>
                            <tr class="bg-gradient-to-r from-gray-100 to-gray-50 border-t-4 border-maroon">
                                <td class="py-3 px-4 text-right font-bold text-gray-900 text-base"></td>
                                <td class="py-3 px-4 text-right font-bold text-red-600 text-base">Total:</td>
                                <td class="py-3 px-4 text-right font-bold text-maroon text-base"
                                    id="amountDeductionTotal">₱0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Add Entry Button -->
                <div class="mt-4">
                    <button onclick="addAmountDeductionEntry()"
                        class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-maroon to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-md hover:shadow-lg text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4">
                            </path>
                        </svg>
                        Add Entry
                    </button>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeAmountDeductionModal()"
                    class="px-5 py-2 bg-gradient-to-r from-maroon to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-md text-sm">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Bulk Entry Modal -->
    <div id="bulkEntryModal"
        class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4 transition-opacity duration-300">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full overflow-hidden flex flex-col transform transition-all duration-300 scale-95 opacity-0"
            id="bulkEntryModalContent">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-maroon to-red-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white">Add Expense Categories</h2>
                    <p class="text-red-100 text-sm mt-1">Paste your list of expense categories (one per line)</p>
                </div>
                <button onclick="closeBulkEntryModal()" class="text-white hover:text-red-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div class="mb-4">
                    <label for="bulkEntryTextarea" class="block text-sm font-semibold text-gray-700 mb-2">
                        Expense Categories (one per line):
                    </label>
                    <textarea id="bulkEntryTextarea"
                        class="w-full h-64 px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-maroon focus:border-maroon transition-all text-gray-900 font-medium text-base resize-none"
                        placeholder="Paste your expense categories here, one per line:&#10;&#10;Seminars and Training Expenses&#10;Honoraria-Part time&#10;Honoraria-Overload&#10;Travel Expenses&#10;Textbook & Instructional Materials&#10;..."></textarea>
                    <p class="text-xs text-gray-500 mt-2">
                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Each line will become a separate entry in the table
                    </p>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeBulkEntryModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Cancel
                </button>
                <button onclick="confirmBulkEntries()"
                    class="px-6 py-3 bg-gradient-to-r from-maroon to-red-700 text-white rounded-xl hover:from-red-700 hover:to-red-800 transition-all font-semibold shadow-lg">
                    Add Entries
                </button>
            </div>
        </div>
    </div>

    <!-- Clear Utilization Data Confirmation Modal -->
    <div id="clearUtilizationModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
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
                <p class="text-gray-700 mb-4">This will reset all budget utilization entries and summaries to 0.</p>
                <p class="text-green-600 text-sm font-semibold mb-4">✓ Purchase Requests, Travels, and Prior Years data will be preserved.</p>
                <p class="text-gray-600 text-sm mb-6">Do you want to continue?</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeClearUtilizationModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        Cancel
                    </button>
                    <button onclick="confirmClearUtilization()" class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold">
                        Yes, Clear
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Clear Utilization Database Modal -->
    <div id="clearUtilizationDatabaseModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
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
                <p class="text-gray-700 mb-4">Do you also want to clear the saved utilization entries and summaries from the database?</p>
                <p class="text-green-600 text-sm font-semibold mb-4">✓ Purchase Requests, Travels, and Prior Years will NOT be deleted.</p>
                <p class="text-red-600 text-sm font-semibold mb-6">⚠️ This will only delete budget utilization entries and summaries!</p>
                <div class="flex gap-3 justify-end">
                    <button onclick="closeClearUtilizationDatabaseModal()" class="px-6 py-2.5 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all font-semibold">
                        No, Keep Saved Data
                    </button>
                    <button onclick="confirmClearUtilizationDatabase()" class="px-6 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white rounded-lg hover:from-red-700 hover:to-red-800 transition-all font-semibold">
                        Yes, Delete from Database
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- PPMP Modal -->
    <div id="ppmpModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-maroon to-red-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white" id="ppmpModalTitle">PPMP - Department</h2>
                    <p class="text-red-100 text-sm mt-1">Project Procurement Management Plan</p>
                </div>
                <button onclick="closePPMPModal()" class="text-white hover:text-red-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div id="ppmpModalContent">
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <p class="text-lg font-semibold mb-2">Loading PPMP...</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closePPMPModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- LIB Modal -->
    <div id="libModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-7xl w-full max-h-[90vh] overflow-hidden flex flex-col">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-6 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-white" id="libModalTitle">LIB - Department</h2>
                    <p class="text-blue-100 text-sm mt-1">Line Item Budget</p>
                </div>
                <button onclick="closeLIBModal()" class="text-white hover:text-blue-200 transition-colors p-2">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Modal Body -->
            <div class="flex-1 overflow-y-auto p-8">
                <div id="libModalContent">
                    <div class="text-center py-8 text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                        <p class="text-lg font-semibold mb-2">Loading LIB...</p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-8 py-6 bg-gray-50 border-t border-gray-200 flex justify-end gap-4">
                <button onclick="closeLIBModal()"
                    class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">
                    Close
                </button>
            </div>
        </div>
    </div>

    <script>
        // Get user_id from PHP session to make localStorage account-specific
        const CURRENT_USER_ID = <?php echo isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0; ?>;
        
        // For budget role: their own department ID (Fiduciary/Budget Office)
        const BUDGET_OWN_DEPT_ID = <?php echo $budgetOwnDepartmentId ? (int)$budgetOwnDepartmentId : 'null'; ?>;
        
        // Global fiscal year variable - each year has its own localStorage
        let CURRENT_FISCAL_YEAR = new Date().getFullYear();
        
        // Helper function to generate deduction sources localStorage key with fiscal year
        function getDeductionSourcesKey(departmentId, entryId) {
            return `deduction_sources_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${entryId}_year_${CURRENT_FISCAL_YEAR}`;
        }
        
        // Helper function to generate deductions data localStorage key with fiscal year
        function getDeductionsDataKey(departmentId) {
            return `deductions_data_user_${CURRENT_USER_ID}_dept_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
        }
        
        // Helper function to generate amount deductions localStorage key with fiscal year
        function getAmountDeductionsKey(departmentId, entryId) {
            return `amount_deductions_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${entryId}_year_${CURRENT_FISCAL_YEAR}`;
        }
        
        // Helper function to generate travels data localStorage key with fiscal year
        function getTravelsDataKey(departmentId) {
            return `travels_data_dept_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
        }
        
        // Function to change fiscal year
        function changeFiscalYear() {
            const fiscalYearSelect = document.getElementById('fiscalYearSelect');
            if (!fiscalYearSelect) return;
            
            const newYear = parseInt(fiscalYearSelect.value);
            if (newYear === CURRENT_FISCAL_YEAR) return;
            
            CURRENT_FISCAL_YEAR = newYear;
            console.log('Fiscal year changed to:', CURRENT_FISCAL_YEAR);
            
            // Reset deduction sources flag when changing fiscal year
            window.deductionSourcesWereLoadedFromDatabase = false;
            
            // Save fiscal year selection to localStorage (user-specific)
            localStorage.setItem(`utilization_fiscal_year_user_${CURRENT_USER_ID}`, CURRENT_FISCAL_YEAR);
            
            // Reload data for the selected year
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (departmentId) {
                // Clear current table completely WITHOUT saving to database
                // We only want to clear the DOM, not delete data from the previous year
                clearAllEntriesWithoutSaving();
                
                // Load data for the new fiscal year
                loadUtilizationEntries(departmentId).then(() => {
                    // Load deductions and recalculate
                    setTimeout(() => {
                        recalculateAllDeductions().then(() => {
                            saveDeductionsToLocalStorage(departmentId);
                            loadDeductionsFromLocalStorage(departmentId);
                            cleanupEmptyDeductionSources(departmentId);
                            console.log(`Loaded utilization data for fiscal year ${CURRENT_FISCAL_YEAR}`);
                        });
                    }, 100);
                });
            }
        }

        // Profile dropdown functionality
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');

            if (!button && dropdown && !dropdown.contains(event.target)) {
                dropdown.classList.add('hidden');
            }
        });

        // Logout functionality
        function confirmLogout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = '../auth/logout.php';
            }
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Restore fiscal year from localStorage FIRST (before anything else)
            const savedFiscalYear = localStorage.getItem(`utilization_fiscal_year_user_${CURRENT_USER_ID}`);
            if (savedFiscalYear) {
                const year = parseInt(savedFiscalYear);
                if (!isNaN(year) && year >= 2020 && year <= 2100) {
                    CURRENT_FISCAL_YEAR = year;
                    const fiscalYearSelect = document.getElementById('fiscalYearSelect');
                    if (fiscalYearSelect) {
                        fiscalYearSelect.value = year;
                    }
                    console.log('Restored fiscal year from localStorage:', CURRENT_FISCAL_YEAR);
                }
            }
            
            updateEmptyState();

            // Setup department and office search functionality
            setupDepartmentSearch();
            setupOfficeSearch();

            // Auto-save to localStorage when inputs change
            setupAutoSaveToLocalStorage();

            // Restore selected department/office and data from localStorage on page load
            restoreUtilizationFromLocalStorage();

            // Close modal when clicking outside
            const purchaseRequestModal = document.getElementById('purchaseRequestModal');
            if (purchaseRequestModal) {
                purchaseRequestModal.addEventListener('click', function (e) {
                    if (e.target === purchaseRequestModal) {
                        closePurchaseRequestModal();
                    }
                });
            }

            // Close particulars modal when clicking outside
            const particularsModal = document.getElementById('particularsModal');
            if (particularsModal) {
                particularsModal.addEventListener('click', function (e) {
                    if (e.target === particularsModal) {
                        closeParticularsModal();
                    }
                });
            }

            // Close particulars modal on Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const particularsModal = document.getElementById('particularsModal');
                    const travelEventModal = document.getElementById('travelEventModal');
                    if (particularsModal && !particularsModal.classList.contains('hidden')) {
                        closeParticularsModal();
                    }
                    if (travelEventModal && !travelEventModal.classList.contains('hidden')) {
                        closeTravelEventModal();
                    }
                }
            });

            // Close travels modal when clicking outside
            const travelsModal = document.getElementById('travelsModal');
            if (travelsModal) {
                travelsModal.addEventListener('click', function (e) {
                    if (e.target === travelsModal) {
                        closeTravelsModal();
                    }
                });
            }

            // Close amount deduction modal when clicking outside
            const amountDeductionModal = document.getElementById('amountDeductionModal');
            if (amountDeductionModal) {
                amountDeductionModal.addEventListener('click', function (e) {
                    if (e.target === amountDeductionModal) {
                        closeAmountDeductionModal();
                    }
                });
            }

            // Close travel event modal when clicking outside
            const travelEventModal = document.getElementById('travelEventModal');
            if (travelEventModal) {
                travelEventModal.addEventListener('click', function (e) {
                    if (e.target === travelEventModal) {
                        closeTravelEventModal();
                    }
                });
            }

            // Close bulk entry modal when clicking outside
            const bulkEntryModal = document.getElementById('bulkEntryModal');
            if (bulkEntryModal) {
                bulkEntryModal.addEventListener('click', function (e) {
                    if (e.target === bulkEntryModal) {
                        closeBulkEntryModal();
                    }
                });
            }

            // Close bulk entry modal on Escape key
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    const bulkEntryModal = document.getElementById('bulkEntryModal');
                    if (bulkEntryModal && !bulkEntryModal.classList.contains('hidden')) {
                        closeBulkEntryModal();
                    }
                    const historyModal = document.getElementById('historyModal');
                    if (historyModal && !historyModal.classList.contains('hidden')) {
                        closeHistoryModal();
                    }
                }
            });

            // Close history modal when clicking outside
            const historyModal = document.getElementById('historyModal');
            if (historyModal) {
                historyModal.addEventListener('click', function (e) {
                    if (e.target === historyModal) {
                        closeHistoryModal();
                    }
                });
            }

            // Auto-open and close Purchase Request and Travel modals on page load (for initialization)
            // This happens so fast it's barely noticeable but ensures modals are initialized
            setTimeout(function () {
                const purchaseRequestModal = document.getElementById('purchaseRequestModal');
                const travelsModal = document.getElementById('travelsModal');

                // Open and immediately close Purchase Request modal (invisible)
                if (purchaseRequestModal) {
                    purchaseRequestModal.style.opacity = '0';
                    purchaseRequestModal.style.pointerEvents = 'none';
                    purchaseRequestModal.classList.remove('hidden');
                    setTimeout(function () {
                        purchaseRequestModal.classList.add('hidden');
                        purchaseRequestModal.style.opacity = '';
                        purchaseRequestModal.style.pointerEvents = '';

                        // Open and immediately close Travel modal (invisible)
                        if (travelsModal) {
                            travelsModal.style.opacity = '0';
                            travelsModal.style.pointerEvents = 'none';
                            travelsModal.classList.remove('hidden');
                            setTimeout(function () {
                                travelsModal.classList.add('hidden');
                                travelsModal.style.opacity = '';
                                travelsModal.style.pointerEvents = '';

                                // After modals are initialized, auto-load deductions
                                autoLoadDeductions();
                            }, 1); // Close after 1 millisecond
                        } else {
                            // If travel modal doesn't exist, still load deductions
                            autoLoadDeductions();
                        }
                    }, 1); // Close after 1 millisecond
                } else {
                    // If purchase request modal doesn't exist, still load deductions
                    autoLoadDeductions();
                }
            }, 100); // Start after 100ms to ensure page is fully loaded

            // Function to auto-load deductions on page refresh
            function autoLoadDeductions() {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

                if (departmentId) {
                    // Clean invalid deductions first (especially for Guidance Office - ID 18)
                    if (departmentId == 18) {
                        cleanInvalidDeductions(18);
                        // Also clear any amount deductions that might have the 10000 value
                        const keysToCheck = [];
                        for (let i = 0; i < localStorage.length; i++) {
                            const key = localStorage.key(i);
                            if (key && key.startsWith(`amount_deductions_user_${CURRENT_USER_ID}_dept_18_entry_`) && key.includes(`_year_${CURRENT_FISCAL_YEAR}`)) {
                                keysToCheck.push(key);
                            }
                        }
                        keysToCheck.forEach(key => {
                            try {
                                const data = JSON.parse(localStorage.getItem(key) || '[]');
                                const cleaned = data.filter(entry => parseFloat(entry.amount || 0) !== 10000);
                                if (cleaned.length !== data.length) {
                                    localStorage.setItem(key, JSON.stringify(cleaned));
                                    console.log(`Cleaned invalid amount deduction from ${key}`);
                                }
                            } catch (e) {
                                console.error('Error cleaning amount deductions:', e);
                            }
                        });
                    }

                    // Wait a bit more to ensure entries are loaded first
                    setTimeout(function () {
                        console.log('Auto-loading deductions for department/office:', departmentId);

                        // Recalculate deductions from database (PR and Travel entries)
                        // This ensures deductions are calculated from all saved PR entries
                        recalculateAllDeductions().then(function () {
                            // Update all row totals to reflect the deductions
                            const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                            mainTableRows.forEach(row => {
                                const entryId = row.id.split('_')[1];
                                calculateRowTotal(entryId);
                            });
                            // Recalculate overall totals
                            calculateTotals();

                            // Save deductions to localStorage
                            saveDeductionsToLocalStorage(departmentId);
                            // Also save utilization entries to database so deductions persist
                            saveUtilizationToLocalStorage();

                            // Only reconstruct deduction_sources if they weren't loaded from database
                            // This prevents overwriting correctly loaded sources with manual_add
                            if (!window.deductionSourcesWereLoadedFromDatabase) {
                                console.log('Reconstructing deduction sources from PR/Travel entries (no sources in database)');
                                reconstructDeductionSourcesFromDatabase(departmentId);
                            } else {
                                console.log('Skipping reconstruction - deduction sources already loaded from database');
                            }

                            console.log('✓ Deductions auto-loaded and applied after page refresh');
                        }).catch(function (error) {
                            console.error('Error auto-loading deductions:', error);
                            // Fallback: try to load from localStorage only
                            loadDeductionsFromLocalStorage(departmentId);
                            // Only reconstruct if sources weren't loaded from database
                            if (!window.deductionSourcesWereLoadedFromDatabase) {
                                console.log('Reconstructing deduction sources from PR/Travel entries (fallback)');
                                reconstructDeductionSourcesFromDatabase(departmentId);
                            } else {
                                console.log('Skipping reconstruction - deduction sources already loaded from database');
                            }
                        });
                    }, 1000); // Wait 1000ms to ensure entries are fully loaded
                } else {
                    // If no department selected, check if one will be restored from localStorage
                    setTimeout(function () {
                        const savedDeptId = localStorage.getItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}`);
                        const savedOfficeId = localStorage.getItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}`);
                        const deptId = savedDeptId || savedOfficeId;

                        if (deptId) {
                            // Wait for restoreUtilizationFromLocalStorage to complete
                            setTimeout(function () {
                                autoLoadDeductions();
                            }, 300);
                        }
                    }, 200);
                }
            }
        });

        // Utilization Table Management
        let entryCounter = 0;

        // Predefined expense categories
        const predefinedCategories = [
            'Seminars and Training Expenses',
            'Honoraria-Part time',
            'Honoraria-Overload',
            'Travel Expenses',
            'Textbook & Instructional Materials',
            'Scholarship Grants Expenses',
            'Office Supplies Expenses',
            'Award/Rewards Expenses',
            'Other MOOE',
            'Office Equipment',
            'Telephone Mobile',
            'ICT Equipment',
            'Repair and Maintenance-Other Structure',
            'Labor & Wages (COS)',
            'Laboratory Fee/Computer Fee',
            'Other Supplies and Materials'
        ];

        function formatNumber(value) {
            if (!value) return '₱0.00';
            const num = parseFloat(value.toString().replace(/[₱,]/g, '')) || 0;
            return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function parseAmount(value) {
            return parseFloat(value.toString().replace(/[₱,]/g, '')) || 0;
        }

        // Function to format currency input with auto-formatting and clear on focus
        function formatCurrencyInput(input) {
            if (!input) return;

            // Get the raw value (remove ₱ and commas)
            let rawValue = input.value.toString().replace(/[₱,]/g, '');

            // If empty or just ₱, set to empty
            if (rawValue === '' || rawValue === '0' || rawValue === '0.00') {
                input.value = '';
                return;
            }

            // Parse as number
            const num = parseFloat(rawValue) || 0;

            // Format with commas (no decimal places during typing for better UX)
            if (rawValue.includes('.')) {
                // Has decimal point, format with 2 decimal places
                input.value = '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            } else {
                // No decimal point, format with commas only
                input.value = '₱' + num.toLocaleString('en-US');
            }
        }

        // Function to setup amount input with focus clear and auto-formatting
        function setupAmountInputHandlers(input) {
            if (!input) return;

            // Clear ₱0.00 on focus and show raw number for easier editing
            input.addEventListener('focus', function () {
                const rawValue = this.value.toString().replace(/[₱,]/g, '');
                if (rawValue === '0' || rawValue === '0.00' || rawValue === '') {
                    this.value = '';
                    this.setSelectionRange(0, 0);
                } else {
                    // Remove formatting for easier editing - show only the number
                    const numValue = rawValue.replace(/[^0-9.]/g, '');
                    this.value = numValue;
                    // Place cursor at the end
                    this.setSelectionRange(numValue.length, numValue.length);
                }
            });

            // Allow natural typing without formatting interference
            input.addEventListener('input', function (e) {
                // Get the current value and cursor position
                let currentValue = this.value;
                const cursorPos = this.selectionStart;

                // Remove all non-numeric characters except decimal point
                let rawValue = currentValue.replace(/[^0-9.]/g, '');

                // Allow only one decimal point
                const parts = rawValue.split('.');
                if (parts.length > 2) {
                    rawValue = parts[0] + '.' + parts.slice(1).join('');
                }

                // Limit decimal places to 2
                if (parts.length === 2 && parts[1].length > 2) {
                    rawValue = parts[0] + '.' + parts[1].substring(0, 2);
                }

                // If empty or just 0, clear the field
                if (rawValue === '' || rawValue === '0') {
                    this.value = '';
                    return;
                }

                // Update with raw number only (no formatting while typing)
                // This allows natural typing without interference
                if (this.value !== rawValue) {
                    this.value = rawValue;
                    // Try to maintain cursor position
                    const newCursorPos = Math.min(cursorPos, rawValue.length);
                    this.setSelectionRange(newCursorPos, newCursorPos);
                }
            });

            // Format on blur only (not while typing)
            input.addEventListener('blur', function () {
                const rawValue = this.value.toString().replace(/[₱,]/g, '').replace(/[^0-9.]/g, '');
                if (rawValue === '' || rawValue === '0' || rawValue === '0.00') {
                    this.value = '₱0.00';
                } else {
                    // Format with currency symbol and commas
                    formatCurrencyInput(this);
                }
            });
        }

        // Department Search and Dropdown Functionality
        // Global flag to track if deduction sources were loaded from database
        // This prevents reconstruction from overwriting correctly loaded sources
        window.deductionSourcesWereLoadedFromDatabase = false;

        // Function to load utilization entries from database and localStorage
        // DATABASE IS THE SINGLE SOURCE OF TRUTH - All budget role accounts share the same database
        function loadUtilizationEntries(departmentId) {
            // Load entries from the selected fiscal year
            return fetch(`../api/load_utilization_entries.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                .then(response => response.json())
                .then(data => {
                    console.log('=== LOAD UTILIZATION ENTRIES FROM DATABASE ===');
                    console.log('Department ID:', departmentId);
                    console.log('Fiscal Year:', CURRENT_FISCAL_YEAR);
                    console.log('Response data:', data);
                    console.log('Deduction sources from DB:', data.deduction_sources);
                    
                    // Clear existing entries WITHOUT saving to database
                    // We're loading from database, so we don't want to overwrite it
                    clearAllEntriesWithoutSaving();

                    let entriesToLoad = [];

                    const storageKey = `utilization_data_user_${CURRENT_USER_ID}_dept_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;

                    console.log('Loading entries for department/office ID:', departmentId, 'Fiscal Year:', CURRENT_FISCAL_YEAR, 'Storage key:', storageKey);

                    // DATABASE IS THE PRIMARY SOURCE OF TRUTH - Always load from database first
                    if (data.success && data.entries && data.entries.length > 0) {
                        console.log('Loading', data.entries.length, 'entries from database (SINGLE SOURCE OF TRUTH) for fiscal year', CURRENT_FISCAL_YEAR);

                        // Use database entries directly - this ensures all budget role accounts see the same data
                        // IMPORTANT: Include deducted_from_entry_id for proper matching
                        entriesToLoad = data.entries.map(entry => ({
                            id: entry.id, // Store database ID for deletion
                            deducted_from_entry_id: entry.deducted_from_entry_id || entry.id, // Store for dropdown matching
                            expense_category: entry.expense_category || '',
                            account_code: entry.account_code || '',
                            allocated_budget: entry.allocated_budget || 0,
                            deductions: entry.deductions || 0, // IMPORTANT: Deductions from database are the source of truth
                            total_balance: entry.total_balance || 0,
                            is_auto_filled: entry.is_auto_filled || false,
                            lib_id: entry.lib_id || null
                        }));

                        // Store deduction sources temporarily to map them after DOM entries are created
                        window.pendingDeductionSources = data.deduction_sources || [];

                        // Sync localStorage with database after loading (for offline/cache purposes)
                        localStorage.setItem(storageKey, JSON.stringify({
                            entries: entriesToLoad.map(e => ({
                                expense_category: e.expense_category,
                                allocated_budget: e.allocated_budget,
                                deductions: e.deductions,
                                total_balance: e.total_balance
                            })),
                            department_id: departmentId,
                            fiscal_year: CURRENT_FISCAL_YEAR,
                            saved_at: new Date().toISOString(),
                            synced_from_db: true
                        }));
                        console.log('Synced localStorage with database entries for fiscal year', CURRENT_FISCAL_YEAR);
                    } else {
                        console.log('No database entries found for fiscal year', CURRENT_FISCAL_YEAR, '- checking localStorage for unsaved entries');

                        // Only use localStorage if database is empty (for unsaved entries)
                        const savedData = localStorage.getItem(storageKey);
                        if (savedData) {
                            try {
                                const parsed = JSON.parse(savedData);
                                if (parsed.entries && parsed.entries.length > 0) {
                                    entriesToLoad = parsed.entries;
                                    console.log('Loaded', entriesToLoad.length, 'unsaved entries from localStorage for fiscal year', CURRENT_FISCAL_YEAR);
                                }
                            } catch (e) {
                                console.error('Error parsing localStorage data:', e);
                            }
                        }
                        
                        // DO NOT load category names from other years
                        // Each fiscal year should be completely independent
                        // If there are no entries for this year, the year should remain empty
                        console.log('No entries found for fiscal year', CURRENT_FISCAL_YEAR, '- year will remain empty (fiscal years are independent)');
                    }

                    console.log('Total entries to load for fiscal year', CURRENT_FISCAL_YEAR, ':', entriesToLoad.length);

                    // Load entries
                    if (entriesToLoad.length > 0) {
                        entriesToLoad.forEach(entry => {
                            entryCounter++;
                            const row = document.createElement('tr');
                            row.id = `entryRow_${entryCounter}`;
                            row.className = 'hover:bg-gray-50 transition-colors';
                            // Store database entry ID for deletion and deducted_from_entry_id for dropdown
                            if (entry.id) {
                                row.setAttribute('data-db-entry-id', entry.id);
                            }
                            // Store deducted_from_entry_id (this is what will be used in "Deduct From" dropdowns)
                            if (entry.deducted_from_entry_id) {
                                row.setAttribute('data-deducted-from-entry-id', entry.deducted_from_entry_id);
                            } else if (entry.id) {
                                // Fallback to id if deducted_from_entry_id is not available
                                row.setAttribute('data-deducted-from-entry-id', entry.id);
                            }
                            if (entry.entry_id) {
                                row.setAttribute('data-entry-id', entry.entry_id);
                            }

                            // Create row HTML with conditional readonly attributes for auto-filled entries
                            const categoryName = (entry.expense_category || '').replace(/"/g, '&quot;');
                            const isAutoFilled = entry.is_auto_filled || false;
                            const readonlyAttr = isAutoFilled ? 'readonly' : '';
                            const readonlyClass = isAutoFilled ? 'bg-blue-50 cursor-not-allowed' : 'bg-white';
                            const autoFilledBadge = ''; // Badge removed to allow expense category to expand
                            
                            row.innerHTML = `
                        <td class="py-2 px-4">
                            <div class="flex items-center">
                                <input 
                                    type="text" 
                                    id="columnArea_${entryCounter}" 
                                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all ${readonlyClass} text-gray-900 font-medium text-sm" 
                                    value="${categoryName}"
                                    ${readonlyAttr}
                                    ${isAutoFilled ? 'title="This field is auto-filled from LIB and cannot be edited"' : ''}
                                >
                                ${autoFilledBadge}
                            </div>
                        </td>
                        <td class="py-2 px-4">
                            <input 
                                type="text" 
                                id="accountCode_${entryCounter}" 
                                class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-center focus:ring-2 focus:ring-maroon focus:border-maroon transition-all ${readonlyClass} text-gray-900 font-medium text-sm" 
                                placeholder="Account Code"
                                ${readonlyAttr}
                                ${isAutoFilled ? 'title="This field is auto-filled from LIB and cannot be edited"' : ''}
                            >
                        </td>
                        <td class="py-2 px-4">
                            <input 
                                type="text" 
                                id="budgetAllocated_${entryCounter}" 
                                class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all ${readonlyClass} text-gray-900 font-medium text-sm" 
                                placeholder="0.00"
                                ${readonlyAttr}
                                ${isAutoFilled ? 'title="This field is auto-filled from LIB and cannot be edited"' : ''}
                            >
                        </td>
                        <td class="py-2 px-4">
                            <div class="flex items-center gap-1.5 relative">
                                <input 
                                    type="text" 
                                    id="deduction_${entryCounter}" 
                                    class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                                    placeholder="₱0.00"
                                >
                                <button 
                                    onclick="showDeductionSourceMenu(${entryCounter})" 
                                    class="p-1.5 bg-maroon text-white rounded-lg hover:bg-red-700 transition-all shadow-sm flex items-center justify-center"
                                    title="Add deduction from Purchase Request or Travels"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                    </svg>
                                </button>
                                <div id="deductionSourceMenu_${entryCounter}" class="hidden fixed z-50 w-72 bg-white rounded-lg shadow-xl border border-gray-200">
                                    <div class="p-2">
                                        <div class="text-xs font-semibold text-gray-500 px-3 py-1.5">Select Source:</div>
                                        <button onclick="showDeductionEntries(${entryCounter}, 'purchase_request')" class="w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded transition-colors">
                                            📋 Purchase Request
                                        </button>
                                        <button onclick="showDeductionEntries(${entryCounter}, 'travels')" class="w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded transition-colors">
                                            ✈️ Travels
                                        </button>
                                        <div class="border-t border-gray-200 my-1"></div>
                                        <button onclick="showAddAmountModal(${entryCounter})" class="w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-700 rounded transition-colors font-semibold">
                                            ➕ Add Amount
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td class="py-2 px-4">
                            <input 
                                type="text" 
                                id="total_${entryCounter}" 
                                class="w-full px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-50 text-right font-bold text-gray-900 text-sm" 
                                readonly
                                value="₱0.00"
                            >
                        </td>
                        <td class="py-2 px-4 text-center">
                            <button 
                                onclick="removeEntry(${entryCounter})" 
                                class="p-1.5 ${isAutoFilled ? 'bg-gray-400 cursor-not-allowed' : 'bg-red-500 hover:bg-red-600'} text-white rounded-lg transition-all shadow-sm flex items-center justify-center mx-auto"
                                title="${isAutoFilled ? 'Cannot delete auto-filled entries from LIB' : 'Remove entry'}"
                                ${isAutoFilled ? 'disabled' : ''}
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    `;

                            const tbody = document.getElementById('utilizationTableBody');
                            if (tbody) {
                                tbody.appendChild(row);
                            }

                            // Set values - only set if they have values (not just 0)
                            const budgetAllocatedEl = document.getElementById(`budgetAllocated_${entryCounter}`);
                            const deductionEl = document.getElementById(`deduction_${entryCounter}`);
                            const totalEl = document.getElementById(`total_${entryCounter}`);
                            const columnAreaEl = document.getElementById(`columnArea_${entryCounter}`);
                            const accountCodeEl = document.getElementById(`accountCode_${entryCounter}`);

                            // Set category name (always set if it exists)
                            if (columnAreaEl) {
                                if (entry.expense_category && entry.expense_category.trim()) {
                                    columnAreaEl.value = entry.expense_category.trim();
                                }
                            }

                            // Set account code (always set if it exists)
                            if (accountCodeEl) {
                                if (entry.account_code && entry.account_code.trim()) {
                                    accountCodeEl.value = entry.account_code.trim();
                                }
                            }

                            // Set budget allocated (set even if 0, but format it)
                            if (budgetAllocatedEl) {
                                const allocated = parseFloat(entry.allocated_budget || 0);
                                if (allocated > 0) {
                                    budgetAllocatedEl.value = formatNumber(allocated);
                                } else {
                                    budgetAllocatedEl.value = '';
                                }
                            }

                            // Set deduction - will be recalculated from PR/Travel entries below
                            // If deduction is 0, leave empty to show placeholder
                            if (deductionEl) {
                                const deduction = parseFloat(entry.deductions || 0);
                                if (deduction > 0) {
                                    deductionEl.value = formatNumber(deduction);
                                } else {
                                    deductionEl.value = '';
                                }
                            }

                            // Set total (always set, format it)
                            if (totalEl) {
                                const total = parseFloat(entry.total_balance || 0);
                                totalEl.value = formatNumber(total);
                                if (total < 0) {
                                    totalEl.classList.add('text-red-600');
                                    totalEl.classList.remove('text-green-600', 'text-gray-900');
                                } else if (total > 0) {
                                    totalEl.classList.add('text-green-600');
                                    totalEl.classList.remove('text-red-600', 'text-gray-900');
                                } else {
                                    totalEl.classList.remove('text-red-600', 'text-green-600');
                                    totalEl.classList.add('text-gray-900');
                                }
                            }

                            // Setup amount input listeners
                            setupAmountInputListeners(`budgetAllocated_${entryCounter}`);
                            // Setup deduction input listeners
                            setupDeductionInputListeners(`deduction_${entryCounter}`, entryCounter);
                        });

                        calculateTotals();
                        updateEmptyState();

                        // IMPORTANT: Deductions are already loaded from database when entries are loaded
                        // The deductions field in the database is the source of truth - it's calculated by the API
                        // when PR entries are saved. We should preserve them and NOT recalculate to avoid clearing them.
                        // Just ensure row totals are calculated correctly
                        const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                        mainTableRows.forEach(row => {
                            const entryId = row.id.split('_')[1];
                            calculateRowTotal(entryId);
                        });
                        calculateTotals();

                        // Map deduction sources from database to new DOM entry IDs
                        let deductionSourcesWereLoaded = false;
                        if (window.pendingDeductionSources && window.pendingDeductionSources.length > 0) {
                            deductionSourcesWereLoaded = true;
                            window.deductionSourcesWereLoadedFromDatabase = true; // Set global flag
                            console.log('=== MAPPING DEDUCTION SOURCES FROM DATABASE ===');
                            console.log('Mapping', window.pendingDeductionSources.length, 'deduction sources to new DOM entry IDs');
                            console.log('Pending deduction sources:', JSON.stringify(window.pendingDeductionSources, null, 2));
                            
                            // Create a map of category name to DOM entry ID
                            const categoryToEntryIdMap = new Map();
                            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
                            utilizationRows.forEach(row => {
                                const domEntryId = row.id.split('_')[1];
                                const columnArea = document.getElementById(`columnArea_${domEntryId}`);
                                if (columnArea && columnArea.value) {
                                    const categoryName = columnArea.value.trim();
                                    categoryToEntryIdMap.set(categoryName, domEntryId);
                                    console.log(`Category "${categoryName}" -> DOM entry ID ${domEntryId}`);
                                }
                            });
                            
                            // Map each deduction source to the correct DOM entry ID
                            window.pendingDeductionSources.forEach(source => {
                                const categoryName = (source.categoryName || '').trim();
                                const newDomEntryId = categoryToEntryIdMap.get(categoryName);
                                
                                console.log(`Processing source for category "${categoryName}":`, source);
                                
                                if (newDomEntryId) {
                                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, newDomEntryId);
                                    
                                    // Load existing sources for this entry
                                    let existingSources = [];
                                    const saved = localStorage.getItem(deductionSourcesKey);
                                    if (saved) {
                                        try {
                                            existingSources = JSON.parse(saved);
                                        } catch (e) {
                                            existingSources = [];
                                        }
                                    }
                                    
                                    // Check if this source already exists
                                    const existingIndex = existingSources.findIndex(s => 
                                        s.sourceType === source.sourceType && 
                                        (s.categoryName || '').trim() === categoryName
                                    );
                                    
                                    const sourceData = {
                                        categoryEntryId: String(newDomEntryId), // Ensure it's a string for consistent comparison
                                        categoryName: categoryName,
                                        sourceType: source.sourceType,
                                        amount: source.amount,
                                        entries: source.entries || []
                                    };
                                    
                                    if (existingIndex >= 0) {
                                        // Update existing source
                                        existingSources[existingIndex] = sourceData;
                                        console.log(`Updated existing source at index ${existingIndex}`);
                                    } else {
                                        // Add new source
                                        existingSources.push(sourceData);
                                        console.log(`Added new source`);
                                    }
                                    
                                    // Save back to localStorage
                                    localStorage.setItem(deductionSourcesKey, JSON.stringify(existingSources));
                                    console.log(`✓ Mapped deduction source for "${categoryName}" to DOM entry ID ${newDomEntryId}`, sourceData);
                                    console.log(`Saved to localStorage key: ${deductionSourcesKey}`);
                                    console.log(`Source has ${sourceData.entries.length} entries:`, JSON.stringify(sourceData.entries, null, 2));
                                    
                                    // Verify it was saved correctly
                                    const verification = localStorage.getItem(deductionSourcesKey);
                                    if (verification) {
                                        const parsed = JSON.parse(verification);
                                        console.log(`Verification - localStorage now contains:`, parsed);
                                    }
                                } else {
                                    console.warn(`⚠ Could not find DOM entry for category "${categoryName}"`);
                                    console.log('Available categories:', Array.from(categoryToEntryIdMap.keys()));
                                }
                            });
                            
                            // Clear pending sources
                            window.pendingDeductionSources = [];
                            console.log('✓ All deduction sources mapped to new DOM entry IDs');
                        }

                        // Save deductions to localStorage for backup (but database is source of truth)
                        saveDeductionsToLocalStorage(departmentId);

                        // Only reconstruct deduction_sources if we didn't load any from the database
                        // If we loaded deduction sources from the database, they're already mapped correctly
                        if (!deductionSourcesWereLoaded) {
                            console.log('No deduction sources loaded from database, attempting reconstruction from PR/Travel entries');
                            reconstructDeductionSourcesFromDatabase(departmentId);
                        } else {
                            console.log('Deduction sources already loaded from database, skipping reconstruction to preserve them');
                        }




                        console.log('✓ Deductions loaded from database and preserved (database is source of truth)');
                        highlightCategoryFromUrl();
                        return Promise.resolve();
                    } else {
                        console.log('No entries to load');
                        updateEmptyState();

                        // Still recalculate deductions even if no entries loaded (in case entries exist in database)
                        return recalculateAllDeductions().then(() => {
                            // Save deductions to localStorage
                            saveDeductionsToLocalStorage(departmentId);
                            // Then load from localStorage
                            loadDeductionsFromLocalStorage(departmentId);
                            console.log('Deductions recalculated after loading (no entries to load)');
                            return Promise.resolve();
                        });
                    }

                })
                .catch(error => {
                    console.error('Error loading utilization entries:', error);
                    // Recalculate deductions even on error (try to load from database)
                    return recalculateAllDeductions().then(() => {
                        // Save and load deductions from localStorage
                        saveDeductionsToLocalStorage(departmentId);
                        loadDeductionsFromLocalStorage(departmentId);
                        console.log('Deductions recalculated after error loading entries');
                        return Promise.resolve();
                    });
                });
        }

        // Function to recalculate deductions from saved PR and Travels entries
        function recalculateDeductionsFromSavedEntries(departmentId) {
            if (!departmentId) return;

            // Calculate deductions directly from database without creating DOM elements
            Promise.all([
                fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()),
                fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json())
            ])
                .then(([prData, travelsData]) => {
                    // Create a map to store deductions per entry ID
                    const deductionsMap = new Map();

                    // Process PR entries
                    if (prData.success && prData.entries) {
                        prData.entries.forEach(entry => {
                            if (entry.entry_id && entry.amount) {
                                const entryId = entry.entry_id;
                                const amount = parseFloat(entry.amount || 0);
                                const current = deductionsMap.get(entryId) || 0;
                                deductionsMap.set(entryId, current + amount);
                            }
                        });
                    }

                    // Process Travels entries
                    if (travelsData.success && travelsData.entries) {
                        travelsData.entries.forEach(entry => {
                            // Use entry_id for deduction tracking
                            const entryId = entry.entry_id;
                            if (entryId && entry.amount) {
                                const amount = parseFloat(entry.amount || 0);
                                const current = deductionsMap.get(entryId) || 0;
                                deductionsMap.set(entryId, current + amount);
                            }
                        });
                    }

                    // Update deduction fields in the main table
                    deductionsMap.forEach((totalDeduction, entryId) => {
                        const deductionInput = document.getElementById(`deduction_${entryId}`);
                        if (deductionInput) {
                            if (totalDeduction > 0) {
                                deductionInput.value = formatNumber(totalDeduction);
                            } else {
                                deductionInput.value = '';
                            }
                            // Recalculate row total
                            calculateRowTotal(entryId);
                        }
                    });

                    // Recalculate overall totals
                    calculateTotals();

                    // IMPORTANT: Save to localStorage after recalculating deductions so they persist
                    saveUtilizationToLocalStorage();

                    // Also save deductions to localStorage
                    const departmentSelect = document.getElementById('departmentSelect');
                    const officeSelect = document.getElementById('officeSelect');
                    const deptId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                    if (deptId) {
                        saveDeductionsToLocalStorage(deptId);
                    }
                })
                .catch(error => {
                    console.error('Error recalculating deductions from saved entries:', error);
                });
        }


        // Function to reconstruct deduction_sources from database entries to restore checkbox states
        function reconstructDeductionSourcesFromDatabase(departmentId) {
            if (!departmentId) return;

            // Get all expense category entries from the DOM
            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
            if (utilizationRows.length === 0) return;

            // Create a map of database entry ID to DOM entry ID
            const dbIdToDomIdMap = new Map();
            const domIdToCategoryMap = new Map();
            const domIdToDbDeductionMap = new Map(); // Store the deduction amount from database

            utilizationRows.forEach(row => {
                const domEntryId = row.id.split('_')[1];
                const dbEntryId = row.getAttribute('data-db-entry-id');
                const columnArea = document.getElementById(`columnArea_${domEntryId}`);
                const categoryName = columnArea ? columnArea.value : `ENTRY ${domEntryId}`;
                const deductionInput = document.getElementById(`deduction_${domEntryId}`);
                const dbDeduction = deductionInput ? parseAmount(deductionInput.value) : 0;

                if (dbEntryId) {
                    dbIdToDomIdMap.set(parseInt(dbEntryId), domEntryId);
                }
                domIdToCategoryMap.set(domEntryId, categoryName);
                domIdToDbDeductionMap.set(domEntryId, dbDeduction);
            });

            // Load PR, Travel, and Honoraria entries from database
            Promise.all([
                fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] })),
                fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] })),
                fetch(`../api/load_honoraria.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] }))
            ]).then(([prData, travelsData, honorariaData]) => {
                // Initialize deduction sources for each expense category
                utilizationRows.forEach(row => {
                    const domEntryId = row.id.split('_')[1];
                    const dbEntryId = row.getAttribute('data-db-entry-id');
                    const columnArea = document.getElementById(`columnArea_${domEntryId}`);
                    const categoryName = columnArea ? columnArea.value : `ENTRY ${domEntryId}`;
                    const totalDbDeduction = domIdToDbDeductionMap.get(domEntryId) || 0;

                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, domEntryId);
                    let deductionSources = [];
                    let prTravelHonorariaTotal = 0; // Track total from PR/Travel/Honoraria

                    // Process PR entries
                    if (prData.success && prData.entries) {
                        prData.entries.forEach(entry => {
                            if (entry.entry_id && entry.amount > 0) {
                                // Find the DOM entry ID that matches this database entry ID
                                const targetDbEntryId = parseInt(entry.entry_id);
                                const targetDomEntryId = dbIdToDomIdMap.get(targetDbEntryId);

                                // Only process if this PR is deducted from the current expense category
                                // Check if the PR's entry_id matches this expense category's database ID
                                if (dbEntryId && parseInt(dbEntryId) === targetDbEntryId) {
                                    // Find or create the PR deduction source
                                    let prSource = deductionSources.find(ds => ds.sourceType === 'purchase_request' && ds.categoryEntryId === domEntryId);

                                    if (!prSource) {
                                        prSource = {
                                            categoryEntryId: domEntryId,
                                            categoryName: categoryName,
                                            sourceType: 'purchase_request',
                                            amount: 0,
                                            entries: []
                                        };
                                        deductionSources.push(prSource);
                                    }

                                    // Add this PR entry to the source with description
                                    const prAmount = parseFloat(entry.amount || 0);
                                    prSource.entries.push({
                                        sourceEntryId: entry.id || entry.pr_id,
                                        description: entry.purchase_request || entry.purchaseRequest || 'N/A',
                                        amount: prAmount
                                    });
                                    prSource.amount += prAmount;
                                    prTravelHonorariaTotal += prAmount;
                                }
                            }
                        });
                    }

                    // Process Travel entries
                    if (travelsData.success && travelsData.entries) {
                        travelsData.entries.forEach(entry => {
                            if (entry.entry_id && entry.amount > 0) {
                                const targetDbEntryId = parseInt(entry.entry_id);

                                // Check if the Travel's entry_id matches this expense category's database ID
                                if (dbEntryId && parseInt(dbEntryId) === targetDbEntryId) {
                                    let travelsSource = deductionSources.find(ds => ds.sourceType === 'travels' && ds.categoryEntryId === domEntryId);

                                    if (!travelsSource) {
                                        travelsSource = {
                                            categoryEntryId: domEntryId,
                                            categoryName: categoryName,
                                            sourceType: 'travels',
                                            amount: 0,
                                            entries: []
                                        };
                                        deductionSources.push(travelsSource);
                                    }

                                    const travelAmount = parseFloat(entry.amount || 0);
                                    travelsSource.entries.push({
                                        sourceEntryId: entry.id || entry.travel_id,
                                        amount: travelAmount
                                    });
                                    travelsSource.amount += travelAmount;
                                    prTravelHonorariaTotal += travelAmount;
                                }
                            }
                        });
                    }

                    // Process Honoraria entries
                    if (honorariaData.success && honorariaData.entries) {
                        honorariaData.entries.forEach(entry => {
                            if (entry.deducted_from_entry_id && entry.amount > 0) {
                                const targetDbEntryId = parseInt(entry.deducted_from_entry_id);

                                // Check if the Honoraria's deducted_from_entry_id matches this expense category's database ID
                                if (dbEntryId && parseInt(dbEntryId) === targetDbEntryId) {
                                    let honorariaSource = deductionSources.find(ds => ds.sourceType === 'honoraria' && ds.categoryEntryId === domEntryId);

                                    if (!honorariaSource) {
                                        honorariaSource = {
                                            categoryEntryId: domEntryId,
                                            categoryName: categoryName,
                                            sourceType: 'honoraria',
                                            amount: 0,
                                            entries: []
                                        };
                                        deductionSources.push(honorariaSource);
                                    }

                                    const honorariaAmount = parseFloat(entry.amount || 0);
                                    honorariaSource.entries.push({
                                        sourceEntryId: entry.id || entry.honoraria_id,
                                        amount: honorariaAmount
                                    });
                                    honorariaSource.amount += honorariaAmount;
                                    prTravelHonorariaTotal += honorariaAmount;
                                }
                            }
                        });
                    }

                    // Calculate manual_add amount as the difference between total deduction and PR/Travel/Honoraria total
                    const manualAddAmount = totalDbDeduction - prTravelHonorariaTotal;
                    
                    if (manualAddAmount > 0.01) { // Use small threshold to avoid floating point errors
                        // Add manual_add source
                        deductionSources.push({
                            categoryEntryId: domEntryId,
                            categoryName: categoryName,
                            sourceType: 'manual_add',
                            amount: manualAddAmount,
                            entries: []
                        });
                        console.log(`✓ Reconstructed manual_add amount for entry ${domEntryId}: ₱${manualAddAmount.toFixed(2)} (Total: ₱${totalDbDeduction.toFixed(2)} - PR/Travel/Honoraria: ₱${prTravelHonorariaTotal.toFixed(2)})`);
                    }

                    // Save deduction sources to localStorage
                    if (deductionSources.length > 0) {
                        localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                        console.log(`✓ Reconstructed deduction sources for entry ${domEntryId} (${categoryName}):`, deductionSources.length, 'sources');
                    }
                });

                console.log('✓ Deduction sources reconstructed from database entries');
            }).catch(error => {
                console.error('Error reconstructing deduction sources:', error);
            });
        }

        // Helper function to load a single deduction from localStorage by entry ID
        function loadDeductionFromLocalStorage(entryId, departmentId) {
            if (!departmentId) return null;

            const storageKey = getDeductionsDataKey(departmentId);
            const savedData = localStorage.getItem(storageKey);

            if (!savedData) return null;

            try {
                const parsed = JSON.parse(savedData);
                if (parsed.deductions && Array.isArray(parsed.deductions)) {
                    const deduction = parsed.deductions.find(d => d.entry_id == entryId);
                    if (deduction) {
                        return parseFloat(deduction.deduction_amount || 0);
                    }
                }
            } catch (e) {
                console.error('Error parsing deductions from localStorage:', e);
            }

            return null;
        }

        // Function to save deductions to localStorage based on PR/Travel entries
        function saveDeductionsToLocalStorage(departmentId) {
            if (!departmentId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            }
            if (!departmentId) return;

            // Calculate deductions from deduction_sources in localStorage (NEW SYSTEM)
            // The old system used prDeductFrom_ dropdowns which no longer exist
            const deductionsMap = new Map(); // Map of entryId -> total deduction amount

            // Get all utilization entries and check their deduction sources
            const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
            mainTableRows.forEach(row => {
                const domEntryId = row.id.split('_')[1];
                const deductionSourcesKey = getDeductionSourcesKey(departmentId, domEntryId);
                const savedSources = localStorage.getItem(deductionSourcesKey);

                if (savedSources) {
                    try {
                        const deductionSources = JSON.parse(savedSources);
                        
                        // Filter out sources with zero or empty amounts
                        const validSources = deductionSources.filter(source => 
                            source.amount && parseFloat(source.amount) > 0
                        );
                        
                        // If no valid sources remain, remove the localStorage entry
                        if (validSources.length === 0) {
                            localStorage.removeItem(deductionSourcesKey);
                            return; // Skip this entry
                        }
                        
                        // Update localStorage with filtered sources if any were removed
                        if (validSources.length !== deductionSources.length) {
                            localStorage.setItem(deductionSourcesKey, JSON.stringify(validSources));
                        }
                        
                        let totalDeduction = 0;

                        // Sum up all deductions from all valid sources for this entry
                        validSources.forEach(source => {
                            totalDeduction += parseFloat(source.amount);
                        });

                        if (totalDeduction > 0) {
                            deductionsMap.set(domEntryId, totalDeduction);
                        }
                    } catch (e) {
                        console.error('Error parsing deduction sources:', e);
                    }
                }
            });

            // Convert map to array for storage
            // Store by category name so it persists across page refreshes (entry IDs change)
            // Use Map to prevent duplicates by category name
            const deductionsByCategory = new Map();
            deductionsMap.forEach((amount, entryId) => {
                // Get category name for this entry ID
                const categoryInput = document.getElementById(`columnArea_${entryId}`);
                const categoryName = categoryInput ? (categoryInput.value || '').trim() : '';

                if (categoryName) {
                    const categoryKey = categoryName.toLowerCase();
                    // If category already exists, use the higher amount or latest entryId
                    if (deductionsByCategory.has(categoryKey)) {
                        const existing = deductionsByCategory.get(categoryKey);
                        // Keep the one with higher amount
                        if (amount > existing.deduction_amount) {
                            deductionsByCategory.set(categoryKey, {
                                category_name: categoryName,
                                entry_id: entryId,
                                deduction_amount: amount
                            });
                        }
                    } else {
                        deductionsByCategory.set(categoryKey, {
                            category_name: categoryName,
                            entry_id: entryId,
                            deduction_amount: amount
                        });
                    }
                }
            });

            // Convert Map to array
            const deductionsData = Array.from(deductionsByCategory.values());

            // Save to localStorage (account-specific with fiscal year)
            const storageKey = getDeductionsDataKey(departmentId);
            localStorage.setItem(storageKey, JSON.stringify({
                deductions: deductionsData,
                department_id: departmentId,
                user_id: CURRENT_USER_ID,
                fiscal_year: CURRENT_FISCAL_YEAR,
                saved_at: new Date().toISOString()
            }));

            console.log('Saved deductions to localStorage:', storageKey, 'fiscal year:', CURRENT_FISCAL_YEAR, 'with', deductionsData.length, 'entries');
        }

        // Function to remove a specific deduction from localStorage by entryId or categoryName
        function removeDeductionFromLocalStorage(entryId, departmentId) {
            if (!departmentId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            }
            if (!departmentId || !entryId) return false;

            const storageKey = getDeductionsDataKey(departmentId);
            const savedData = localStorage.getItem(storageKey);

            if (!savedData) return false;

            try {
                const parsed = JSON.parse(savedData);
                if (parsed.deductions && Array.isArray(parsed.deductions)) {
                    // Get category name for this entry ID
                    const categoryInput = document.getElementById(`columnArea_${entryId}`);
                    const categoryName = categoryInput ? (categoryInput.value || '').trim() : '';

                    // Filter out the deduction for this entry (by entryId or categoryName)
                    const filteredDeductions = parsed.deductions.filter(deduction => {
                        // Match by entry_id or category_name
                        const matchesEntryId = deduction.entry_id == entryId;
                        const matchesCategory = categoryName && deduction.category_name &&
                            deduction.category_name.trim().toLowerCase() === categoryName.toLowerCase();

                        return !(matchesEntryId || matchesCategory);
                    });

                    // If we removed any deductions, update localStorage
                    if (filteredDeductions.length !== parsed.deductions.length) {
                        if (filteredDeductions.length > 0) {
                            localStorage.setItem(storageKey, JSON.stringify({
                                deductions: filteredDeductions,
                                department_id: departmentId,
                                user_id: CURRENT_USER_ID,
                                saved_at: new Date().toISOString()
                            }));
                        } else {
                            // If no deductions left, remove the entire key
                            localStorage.removeItem(storageKey);
                        }

                        // Also clear amount deductions for this specific entry
                        const amountDeductionKey = getAmountDeductionsKey(departmentId, entryId);
                        localStorage.removeItem(amountDeductionKey);

                        console.log(`Removed deduction from localStorage for entry ${entryId}`);
                        return true;
                    }
                }
            } catch (e) {
                console.error('Error removing deduction from localStorage:', e);
            }

            return false;
        }

        // Function to clear localStorage deductions for a specific department
        function clearDeductionsLocalStorage(departmentId) {
            if (!departmentId) {
                console.error('Department ID is required to clear localStorage');
                return false;
            }

            // Clear main deductions data (account-specific)
            const storageKey = getDeductionsDataKey(departmentId);
            localStorage.removeItem(storageKey);

            // Clear all amount deductions for this department (account-specific) for current fiscal year
            const keysToRemove = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith(`amount_deductions_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_`) && key.includes(`_year_${CURRENT_FISCAL_YEAR}`)) {
                    keysToRemove.push(key);
                }
            }
            keysToRemove.forEach(key => localStorage.removeItem(key));

            console.log(`Cleared localStorage deductions for department ${departmentId}`);
            return true;
        }

        // Function to clean invalid deductions from localStorage (removes suspicious values like 10000)
        function cleanInvalidDeductions(departmentId) {
            if (!departmentId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            }
            if (!departmentId) return;

            const storageKey = getDeductionsDataKey(departmentId);
            const savedData = localStorage.getItem(storageKey);

            if (!savedData) return;

            try {
                const parsed = JSON.parse(savedData);
                if (parsed.deductions && Array.isArray(parsed.deductions)) {
                    // Filter out invalid deductions (e.g., exactly 10000 which seems suspicious)
                    const validDeductions = parsed.deductions.filter(deduction => {
                        const amount = parseFloat(deduction.deduction_amount || 0);
                        // Remove deductions that are exactly 10000 (likely invalid)
                        if (amount === 10000) {
                            console.log('Removing invalid deduction:', deduction);
                            return false;
                        }
                        return true;
                    });

                    // If we removed any deductions, save the cleaned data back
                    if (validDeductions.length !== parsed.deductions.length) {
                        localStorage.setItem(storageKey, JSON.stringify({
                            deductions: validDeductions,
                            department_id: departmentId,
                            saved_at: new Date().toISOString()
                        }));
                        console.log(`Cleaned ${parsed.deductions.length - validDeductions.length} invalid deductions from localStorage`);
                    }
                }
            } catch (e) {
                console.error('Error cleaning invalid deductions:', e);
            }
        }

        // Function to load deductions from localStorage and apply them
        // Matches by category name since entry IDs change on refresh
        function loadDeductionsFromLocalStorage(departmentId) {
            if (!departmentId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            }
            if (!departmentId) return;

            // Clean invalid deductions before loading
            cleanInvalidDeductions(departmentId);

            const storageKey = getDeductionsDataKey(departmentId);
            const savedData = localStorage.getItem(storageKey);

            if (!savedData) {
                console.log('No deductions found in localStorage for:', storageKey);
                return;
            }

            try {
                const parsed = JSON.parse(savedData);
                if (parsed.deductions && Array.isArray(parsed.deductions)) {
                    // Remove duplicates by category name (keep the one with highest amount)
                    const uniqueDeductions = new Map();
                    parsed.deductions.forEach(deduction => {
                        const categoryName = deduction.category_name || deduction.entry_id;
                        const amount = parseFloat(deduction.deduction_amount || 0);

                        // Filter out suspicious values like exactly 10000
                        if (categoryName && amount > 0 && amount !== 10000) {
                            const categoryKey = categoryName.trim().toLowerCase();

                            // If duplicate found, keep the one with higher amount
                            if (uniqueDeductions.has(categoryKey)) {
                                const existing = uniqueDeductions.get(categoryKey);
                                const existingAmount = parseFloat(existing.deduction_amount || 0);
                                if (amount > existingAmount) {
                                    uniqueDeductions.set(categoryKey, deduction);
                                }
                            } else {
                                uniqueDeductions.set(categoryKey, deduction);
                            }
                        }
                    });

                    // If duplicates were removed, update localStorage
                    if (uniqueDeductions.size !== parsed.deductions.length) {
                        const cleanedDeductions = Array.from(uniqueDeductions.values());

                        localStorage.setItem(storageKey, JSON.stringify({
                            deductions: cleanedDeductions,
                            department_id: parsed.department_id || departmentId,
                            user_id: parsed.user_id || CURRENT_USER_ID,
                            saved_at: new Date().toISOString()
                        }));

                        console.log(`Cleaned ${parsed.deductions.length - cleanedDeductions.length} duplicate deductions from localStorage`);
                    }

                    // Create a map of category name -> deduction amount from cleaned data
                    const deductionsByCategory = new Map();
                    uniqueDeductions.forEach((deduction, categoryKey) => {
                        const amount = parseFloat(deduction.deduction_amount || 0);
                        deductionsByCategory.set(categoryKey, amount);
                    });

                    // Apply deductions to matching categories in DOM
                    const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                    mainTableRows.forEach(row => {
                        const entryId = row.id.split('_')[1];
                        const categoryInput = document.getElementById(`columnArea_${entryId}`);
                        const deductionInput = document.getElementById(`deduction_${entryId}`);

                        if (categoryInput && categoryInput.value && deductionInput) {
                            const categoryKey = categoryInput.value.trim().toLowerCase();
                            const savedAmount = deductionsByCategory.get(categoryKey);

                            if (savedAmount !== undefined && savedAmount > 0) {
                                deductionInput.value = formatNumber(savedAmount);
                                calculateRowTotal(entryId);
                            } else if (savedAmount !== undefined && savedAmount === 0) {
                                deductionInput.value = '';
                                calculateRowTotal(entryId);
                            }
                        }
                    });

                    // Recalculate totals
                    calculateTotals();
                    console.log('Loaded', parsed.deductions.length, 'deductions from localStorage');
                }
            } catch (e) {
                console.error('Error parsing deductions from localStorage:', e);
            }
        }

        // Function to calculate and save deductions from PR/Travel entries in database (for page refresh)
        function calculateAndSaveDeductionsFromDatabase(departmentId) {
            if (!departmentId) return Promise.resolve();

            return Promise.all([
                fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] })),
                fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] }))
            ]).then(([prData, travelsData]) => {
                const deductionsMap = new Map(); // Map of entryId -> total deduction amount

                // Process PR entries
                if (prData.success && prData.entries) {
                    prData.entries.forEach(entry => {
                        if (entry.entry_id && entry.amount) {
                            const entryId = entry.entry_id;
                            const amount = parseFloat(entry.amount || 0);

                            if (amount > 0 && entryId) {
                                const current = deductionsMap.get(entryId) || 0;
                                deductionsMap.set(entryId, current + amount);
                            }
                        }
                    });
                }

                // Process Travel entries
                if (travelsData.success && travelsData.entries) {
                    travelsData.entries.forEach(entry => {
                        if (entry.entry_id && entry.amount) {
                            const entryId = entry.entry_id;
                            const amount = parseFloat(entry.amount || 0);

                            if (amount > 0 && entryId) {
                                const current = deductionsMap.get(entryId) || 0;
                                deductionsMap.set(entryId, current + amount);
                            }
                        }
                    });
                }

                // Convert map to array for storage
                const deductionsData = [];
                deductionsMap.forEach((amount, entryId) => {
                    deductionsData.push({
                        entry_id: entryId,
                        deduction_amount: amount
                    });
                });

                // Save to localStorage (account-specific)
                const storageKey = getDeductionsDataKey(departmentId);
                localStorage.setItem(storageKey, JSON.stringify({
                    deductions: deductionsData,
                    department_id: departmentId,
                    user_id: CURRENT_USER_ID,
                    saved_at: new Date().toISOString()
                }));

                console.log('Calculated and saved deductions from database to localStorage:', storageKey, 'with', deductionsData.length, 'entries');

                // Also apply to DOM
                deductionsMap.forEach((amount, entryId) => {
                    const deductionInput = document.getElementById(`deduction_${entryId}`);
                    if (deductionInput) {
                        if (amount > 0) {
                            deductionInput.value = formatNumber(amount);
                        } else {
                            deductionInput.value = '';
                        }
                        calculateRowTotal(entryId);
                    }
                });

                calculateTotals();
            }).catch(error => {
                console.error('Error calculating deductions from database:', error);
            });
        }

        // Function to save utilization data to localStorage
        function saveUtilizationToLocalStorage() {
            // Clear any pending save to implement debouncing
            if (saveDebounceTimer) {
                clearTimeout(saveDebounceTimer);
            }
            
            // Set a new timer to save after delay
            saveDebounceTimer = setTimeout(() => {
                saveUtilizationData();
            }, SAVE_DEBOUNCE_DELAY);
        }
        
        // Actual save function (called after debounce delay)
        function saveUtilizationData() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentSearch = document.getElementById('departmentSearch');
            const officeSearch = document.getElementById('officeSearch');
            const selectedId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!selectedId) return;

            // Save selected department/office info (account-specific with fiscal year)
            if (departmentSelect && departmentSelect.value) {
                const deptName = departmentSearch ? departmentSearch.value : '';
                localStorage.setItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`, departmentSelect.value);
                localStorage.setItem(`utilization_selected_department_name_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`, deptName);
                localStorage.removeItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);
                localStorage.removeItem(`utilization_selected_office_name_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);
            } else if (officeSelect && officeSelect.value) {
                const officeName = officeSearch ? officeSearch.value : '';
                localStorage.setItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`, officeSelect.value);
                localStorage.setItem(`utilization_selected_office_name_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`, officeName);
                localStorage.removeItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);
                localStorage.removeItem(`utilization_selected_department_name_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);
            }

            const utilizationEntries = [];
            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');

            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const columnArea = document.getElementById(`columnArea_${entryId}`);
                const accountCode = document.getElementById(`accountCode_${entryId}`);
                const budgetAllocated = document.getElementById(`budgetAllocated_${entryId}`);
                const deduction = document.getElementById(`deduction_${entryId}`);
                const total = document.getElementById(`total_${entryId}`);

                // Save entry if it has a category name (even if budget/deduction are empty)
                const categoryValue = columnArea ? (columnArea.value || '').trim() : '';
                if (categoryValue) {
                    const allocated = parseAmount(budgetAllocated?.value || '0');
                    const deduct = parseAmount(deduction?.value || '0');
                    const bal = parseAmount(total?.value || '0');
                    const accountCodeValue = accountCode ? (accountCode.value || '').trim() : '';

                    // IMPORTANT: Always preserve deductions from database, don't use DOM value
                    // Deductions are calculated by API when PR entries are saved, not from DOM
                    // We'll send the deduction value from DOM, but API will preserve it from database
                    utilizationEntries.push({
                        expense_category: categoryValue,
                        account_code: accountCodeValue,
                        allocated_budget: allocated,
                        deductions: deduct, // This will be preserved by API from database
                        total_balance: bal
                    });
                }
            });

            // Always save to localStorage (even if empty, to preserve state)
            // Use a unique key for each user, department/office, and fiscal year to ensure data isolation
            const storageKey = `utilization_data_user_${CURRENT_USER_ID}_dept_${selectedId}_year_${CURRENT_FISCAL_YEAR}`;
            localStorage.setItem(storageKey, JSON.stringify({
                entries: utilizationEntries,
                department_id: selectedId,
                user_id: CURRENT_USER_ID,
                fiscal_year: CURRENT_FISCAL_YEAR,
                saved_at: new Date().toISOString()
            }));

            console.log('Saved to localStorage:', storageKey, 'for department/office ID:', selectedId, 'fiscal year:', CURRENT_FISCAL_YEAR, 'with', utilizationEntries.length, 'entries');

            // IMMEDIATELY save to database so all budget role users can access it
            // DATABASE IS THE SINGLE SOURCE OF TRUTH - All budget role accounts share the same database
            // This ensures data is shared across all budget role accounts immediately
            // Always save to database, even if entries array is empty (to clear database)
            // Clear any pending save
            if (window.utilizationDatabaseSaveTimeout) {
                clearTimeout(window.utilizationDatabaseSaveTimeout);
            }

            // Prepare entries for database (convert field names to match API)
            // Filter out entries with empty category names
            const dbEntries = utilizationEntries
                .filter(entry => entry.expense_category && entry.expense_category.trim().length > 0)
                .map(entry => ({
                    category: entry.expense_category || '',
                    account_code: entry.account_code || '',
                    allocated: entry.allocated_budget || 0,
                    deductions: entry.deductions || 0,
                    balance: entry.total_balance || 0
                }));

            // Collect all deduction sources from localStorage to save to database
            const allDeductionSources = [];
            const seenSources = new Set(); // Track unique sources to prevent duplicates
            
            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const columnArea = document.getElementById(`columnArea_${entryId}`);
                const categoryValue = columnArea ? (columnArea.value || '').trim() : '';
                
                if (categoryValue) {
                    const deductionSourcesKey = getDeductionSourcesKey(selectedId, entryId);
                    const savedSources = localStorage.getItem(deductionSourcesKey);
                    
                    if (savedSources) {
                        try {
                            const sources = JSON.parse(savedSources);
                            console.log(`Collecting deduction sources for entry ${entryId} (${categoryValue}):`, sources);
                            sources.forEach(source => {
                                // Create unique key for this source to prevent duplicates
                                const uniqueKey = `${entryId}_${categoryValue}_${source.sourceType}_${source.amount}`;
                                
                                // Only add if we haven't seen this exact source before
                                if (!seenSources.has(uniqueKey)) {
                                    seenSources.add(uniqueKey);
                                    
                                    const sourceData = {
                                        entry_id: entryId, // This is the DOM entry ID, but we'll use category_name for mapping on load
                                        category_name: categoryValue, // Use actual category name from DOM for reliable mapping
                                        source_type: source.sourceType,
                                        amount: source.amount,
                                        entries: source.entries
                                    };
                                    console.log(`  Adding source to database payload:`, sourceData);
                                    allDeductionSources.push(sourceData);
                                } else {
                                    console.log(`  Skipping duplicate source: ${uniqueKey}`);
                                }
                            });
                        } catch (e) {
                            console.error('Error parsing deduction sources for entry', entryId, e);
                        }
                    } else {
                        console.log(`No deduction sources found for entry ${entryId} (${categoryValue})`);
                    }
                }
            });

            console.log('=== SAVING TO DATABASE ===');
            console.log('Department ID:', selectedId);
            console.log('Fiscal Year:', CURRENT_FISCAL_YEAR);
            console.log('Entries count:', dbEntries.length);
            console.log('Deduction sources count:', allDeductionSources.length);
            console.log('Deduction sources:', JSON.stringify(allDeductionSources, null, 2));
            console.log('Entries:', dbEntries);

            // Save to database immediately (database is the single source of truth)
            fetch('../api/save_utilization_entry.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: selectedId,
                    entries: dbEntries,
                    deduction_sources: allDeductionSources,
                    fiscal_year: CURRENT_FISCAL_YEAR
                })
            })
                .then(async response => {
                    // Try to get the error message from response body even if status is not ok
                    const responseText = await response.text();
                    let data;
                    try {
                        data = JSON.parse(responseText);
                    } catch (e) {
                        // If response is not JSON, use the text as error message
                        throw new Error(responseText || `HTTP error! status: ${response.status}`);
                    }

                    if (!response.ok) {
                        throw new Error(data.message || `HTTP error! status: ${response.status}`);
                    }

                    return data;
                })
                .then(data => {
                    if (data.success) {
                        console.log('✓ Saved utilization entries to database (SINGLE SOURCE OF TRUTH) for department:', selectedId, 'fiscal year:', CURRENT_FISCAL_YEAR, '-', dbEntries.length, 'entries');
                        // After successful database save, update localStorage to match database
                        // This keeps localStorage in sync with the database
                        localStorage.setItem(storageKey, JSON.stringify({
                            entries: utilizationEntries,
                            department_id: selectedId,
                            fiscal_year: CURRENT_FISCAL_YEAR,
                            saved_at: new Date().toISOString(),
                            synced_from_db: true
                        }));
                    } else {
                        console.error('✗ Error saving to database:', data.message);
                        alert('Error saving to database: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('✗ Error saving utilization entries to database:', error);
                    alert('Error saving to database: ' + error.message);
                });
        }

        // Clear functions for search inputs - only clear the search text, not the selection
        function clearDepartmentSearch() {
            const departmentSearch = document.getElementById('departmentSearch');
            const clearBtn = document.getElementById('clearDepartmentSearch');

            // Only clear the search input text, keep the selection
            if (departmentSearch) {
                departmentSearch.value = '';
                // Hide clear button if search is empty
                if (clearBtn && !departmentSearch.value.trim()) {
                    clearBtn.classList.add('hidden');
                }
            }
        }

        function clearOfficeSearch() {
            const officeSearch = document.getElementById('officeSearch');
            const clearBtn = document.getElementById('clearOfficeSearch');

            // Only clear the search input text, keep the selection
            if (officeSearch) {
                officeSearch.value = '';
                // Hide clear button if search is empty
                if (clearBtn && !officeSearch.value.trim()) {
                    clearBtn.classList.add('hidden');
                }
            }
        }

        function handleDepartmentChange() {
            // This function is called when department or office is selected via the searchable dropdown
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const selectedDepartmentDisplay = document.getElementById('selectedDepartmentDisplay');
            const selectedDepartmentName = document.getElementById('selectedDepartmentName');

            // Get the selected ID (either department or office)
            const selectedId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            // Reset deduction sources flag when changing department
            window.deductionSourcesWereLoadedFromDatabase = false;

            // Clear the other selection when one is selected
            if (departmentSelect && departmentSelect.value) {
                if (officeSelect) officeSelect.value = '';
                const officeSearch = document.getElementById('officeSearch');
                if (officeSearch) officeSearch.value = '';
                const clearOfficeBtn = document.getElementById('clearOfficeSearch');
                if (clearOfficeBtn) clearOfficeBtn.classList.add('hidden');
            }
            if (officeSelect && officeSelect.value) {
                if (departmentSelect) departmentSelect.value = '';
                const departmentSearch = document.getElementById('departmentSearch');
                if (departmentSearch) departmentSearch.value = '';
                const clearDeptBtn = document.getElementById('clearDepartmentSearch');
                if (clearDeptBtn) clearDeptBtn.classList.add('hidden');
            }

            if (selectedId) {
                const departmentSearch = document.getElementById('departmentSearch');
                const officeSearch = document.getElementById('officeSearch');
                const selectedText = (departmentSearch && departmentSearch.value) ? departmentSearch.value : (officeSearch && officeSearch.value ? officeSearch.value : '');

                if (selectedDepartmentName) {
                    selectedDepartmentName.textContent = selectedText;
                }
                if (selectedDepartmentDisplay) {
                    selectedDepartmentDisplay.classList.remove('hidden');
                }

                // Get the previous selection before switching
                const previousDeptId = localStorage.getItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}`);
                const previousOfficeId = localStorage.getItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}`);
                const previousId = previousDeptId || previousOfficeId;

                // If we're switching to a different department/office, save the current data first
                if (previousId && previousId !== selectedId) {
                    // Save current entries to the PREVIOUS department/office's storage before switching
                    const previousStorageKey = `utilization_data_user_${CURRENT_USER_ID}_dept_${previousId}_year_${CURRENT_FISCAL_YEAR}`;
                    const utilizationEntries = [];
                    const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');

                    utilizationRows.forEach(row => {
                        const entryId = row.id.split('_')[1];
                        const columnArea = document.getElementById(`columnArea_${entryId}`);
                        const budgetAllocated = document.getElementById(`budgetAllocated_${entryId}`);
                        const deduction = document.getElementById(`deduction_${entryId}`);
                        const total = document.getElementById(`total_${entryId}`);

                        const categoryValue = columnArea ? (columnArea.value || '').trim() : '';
                        if (categoryValue) {
                            const allocated = parseAmount(budgetAllocated?.value || '0');
                            const deduct = parseAmount(deduction?.value || '0');
                            const bal = parseAmount(total?.value || '0');

                            utilizationEntries.push({
                                expense_category: categoryValue,
                                allocated_budget: allocated,
                                deductions: deduct,
                                total_balance: bal
                            });
                        }
                    });

                    // Save to previous department/office's storage
                    localStorage.setItem(previousStorageKey, JSON.stringify({
                        entries: utilizationEntries,
                        department_id: previousId,
                        saved_at: new Date().toISOString()
                    }));

                    console.log('Saved', utilizationEntries.length, 'entries to previous department/office ID:', previousId);
                }

                // Clear PR and Travel modals when switching departments to prevent cross-contamination
                clearPurchaseRequestModal();
                clearTravelModal();

                // Load utilization entries from database first, then from localStorage for the NEW selection
                loadUtilizationEntries(selectedId);
            } else {
                if (selectedDepartmentDisplay) {
                    selectedDepartmentDisplay.classList.add('hidden');
                }
                clearAllEntries();
            }
        }

        function setupDepartmentSearch() {
            const departmentSearch = document.getElementById('departmentSearch');
            const departmentDropdown = document.getElementById('departmentDropdown');
            const departmentSelect = document.getElementById('departmentSelect');
            const selectedDepartmentDiv = document.getElementById('selectedDepartmentDisplay');
            const selectedDepartmentName = document.getElementById('selectedDepartmentName');
            const departmentOptions = document.querySelectorAll('.department-option');
            const departmentSearchContainer = departmentSearch ? departmentSearch.closest('.relative') : null;

            if (!departmentSearch || !departmentDropdown) return;

            // Show dropdown when clicking search input or dropdown icon
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

            function selectDepartment(id, name) {
                // Don't save when switching - each fiscal year should be independent
                // saveUtilizationToLocalStorage(); // REMOVED - causes entries to sync across years

                // Clear office selection first
                const officeSelect = document.getElementById('officeSelect');
                const officeSearch = document.getElementById('officeSearch');
                const clearOfficeBtn = document.getElementById('clearOfficeSearch');
                if (officeSelect) officeSelect.value = '';
                if (officeSearch) officeSearch.value = '';
                if (clearOfficeBtn) clearOfficeBtn.classList.add('hidden');

                // Set department selection
                if (departmentSelect) departmentSelect.value = id;
                if (departmentSearch) departmentSearch.value = name;
                if (selectedDepartmentName) selectedDepartmentName.textContent = name;
                if (selectedDepartmentDiv) selectedDepartmentDiv.classList.remove('hidden');

                // Show clear button
                const clearBtn = document.getElementById('clearDepartmentSearch');
                if (clearBtn) clearBtn.classList.remove('hidden');

                // Save selection to localStorage (account-specific)
                localStorage.setItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}`, id);
                localStorage.setItem(`utilization_selected_department_name_user_${CURRENT_USER_ID}`, name);
                localStorage.removeItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}`);
                localStorage.removeItem(`utilization_selected_office_name_user_${CURRENT_USER_ID}`);

                hideDepartmentDropdown();

                // Load entries for the selected department
                handleDepartmentChange();
            }

            // Event listeners
            if (departmentSearch) {
                departmentSearch.addEventListener('focus', function (e) {
                    e.stopPropagation();
                    showDepartmentDropdown();
                });

                departmentSearch.addEventListener('click', function (e) {
                    e.stopPropagation();
                    showDepartmentDropdown();
                });

                departmentSearch.addEventListener('input', function () {
                    filterDepartmentOptions();
                    if (departmentDropdown) {
                        departmentDropdown.classList.remove('hidden');
                    }

                    // Show/hide clear button based on input value
                    const clearBtn = document.getElementById('clearDepartmentSearch');
                    if (clearBtn) {
                        if (this.value.trim()) {
                            clearBtn.classList.remove('hidden');
                        } else {
                            clearBtn.classList.add('hidden');
                        }
                    }
                });
            }

            const dropdownIcon = document.getElementById('departmentDropdownIcon');
            if (dropdownIcon) {
                dropdownIcon.addEventListener('click', function (e) {
                    e.stopPropagation();
                    showDepartmentDropdown();
                });
            }

            // Select department when clicking on option
            if (departmentOptions.length > 0) {
                departmentOptions.forEach(option => {
                    option.addEventListener('click', function (e) {
                        e.stopPropagation();
                        selectDepartment(this.dataset.id, this.dataset.name);
                    });
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (departmentSearchContainer && !departmentSearchContainer.contains(e.target)) {
                    hideDepartmentDropdown();
                }
            });

        }

        function setupOfficeSearch() {
            const officeSearch = document.getElementById('officeSearch');
            const officeDropdown = document.getElementById('officeDropdown');
            const officeSelect = document.getElementById('officeSelect');
            const selectedDepartmentDiv = document.getElementById('selectedDepartmentDisplay');
            const selectedDepartmentName = document.getElementById('selectedDepartmentName');
            const officeOptions = document.querySelectorAll('.office-option');
            const officeSearchContainer = officeSearch ? officeSearch.closest('.relative') : null;

            if (!officeSearch || !officeDropdown) return;

            // Show dropdown when clicking search input or dropdown icon
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

            function selectOffice(id, name) {
                // Don't save when switching - each fiscal year should be independent
                // saveUtilizationToLocalStorage(); // REMOVED - causes entries to sync across years

                // Clear department selection first
                const departmentSelect = document.getElementById('departmentSelect');
                const departmentSearch = document.getElementById('departmentSearch');
                const clearDeptBtn = document.getElementById('clearDepartmentSearch');
                if (departmentSelect) departmentSelect.value = '';
                if (departmentSearch) departmentSearch.value = '';
                if (clearDeptBtn) clearDeptBtn.classList.add('hidden');

                // Set office selection
                if (officeSelect) officeSelect.value = id;
                if (officeSearch) officeSearch.value = name;
                if (selectedDepartmentName) selectedDepartmentName.textContent = name;
                if (selectedDepartmentDiv) selectedDepartmentDiv.classList.remove('hidden');

                // Show clear button
                const clearBtn = document.getElementById('clearOfficeSearch');
                if (clearBtn) clearBtn.classList.remove('hidden');

                // Save selection to localStorage (account-specific)
                localStorage.setItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}`, id);
                localStorage.setItem(`utilization_selected_office_name_user_${CURRENT_USER_ID}`, name);
                localStorage.removeItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}`);
                localStorage.removeItem(`utilization_selected_department_name_user_${CURRENT_USER_ID}`);

                hideOfficeDropdown();

                // Load entries for the selected office
                handleDepartmentChange();
            }

            // Event listeners
            if (officeSearch) {
                officeSearch.addEventListener('focus', function (e) {
                    e.stopPropagation();
                    showOfficeDropdown();
                });

                officeSearch.addEventListener('click', function (e) {
                    e.stopPropagation();
                    showOfficeDropdown();
                });

                officeSearch.addEventListener('input', function () {
                    filterOfficeOptions();
                    if (officeDropdown) {
                        officeDropdown.classList.remove('hidden');
                    }

                    // Show/hide clear button based on input value
                    const clearBtn = document.getElementById('clearOfficeSearch');
                    if (clearBtn) {
                        if (this.value.trim()) {
                            clearBtn.classList.remove('hidden');
                        } else {
                            clearBtn.classList.add('hidden');
                        }
                    }
                });
            }

            const dropdownIcon = document.getElementById('officeDropdownIcon');
            if (dropdownIcon) {
                dropdownIcon.addEventListener('click', function (e) {
                    e.stopPropagation();
                    showOfficeDropdown();
                });
            }

            // Select office when clicking on option
            if (officeOptions.length > 0) {
                officeOptions.forEach(option => {
                    option.addEventListener('click', function (e) {
                        e.stopPropagation();
                        selectOffice(this.dataset.id, this.dataset.name);
                    });
                });
            }

            // Close dropdown when clicking outside
            document.addEventListener('click', function (e) {
                if (officeSearchContainer && !officeSearchContainer.contains(e.target)) {
                    hideOfficeDropdown();
                }
            });
        }

        function loadPredefinedCategories() {
            const tbody = document.getElementById('utilizationTableBody');
            if (!tbody) return;

            // Clear existing entries
            tbody.innerHTML = '';
            entryCounter = 0;

            // Add predefined categories
            predefinedCategories.forEach((category, index) => {
                entryCounter++;
                const row = document.createElement('tr');
                row.id = `entryRow_${entryCounter}`;
                row.className = 'hover:bg-gray-50 transition-colors';

                row.innerHTML = `
            <td class="border-b border-gray-200 py-2 px-4">
                <input 
                    type="text" 
                    id="columnArea_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-gray-50 text-gray-700 font-medium text-sm" 
                    value="${category}"
                    readonly
                >
            </td>
            <td class="border-b border-gray-200 py-2 px-4">
                <input 
                    type="text" 
                    id="budgetAllocated_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                    placeholder="0.00"
                >
            </td>
            <td class="border-b border-gray-200 py-2 px-4">
                <input 
                    type="text" 
                    id="deduction_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                    placeholder="0.00"
                >
            </td>
            <td class="border-b border-gray-200 py-2 px-4">
                <input 
                    type="text" 
                    id="total_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-50 text-right font-bold text-gray-900 text-sm" 
                    readonly
                    value="₱0.00"
                >
            </td>
            <td class="border-b border-gray-200 py-2 px-4"></td>
        `;

                tbody.appendChild(row);

                // Setup input listeners for each row
                setupAmountInputListeners(`budgetAllocated_${entryCounter}`);
                // Setup deduction input listeners
                setupDeductionInputListeners(`deduction_${entryCounter}`, entryCounter);
            });

            // Update empty state
            updateEmptyState();

            // Calculate initial totals
            calculateTotals();

            // Save to localStorage after adding entries
            saveUtilizationToLocalStorage();
        }

        function clearAllEntries() {
            const tbody = document.getElementById('utilizationTableBody');
            if (tbody) {
                tbody.innerHTML = '';
            }
            entryCounter = 0;
            calculateTotals();
            updateEmptyState();
            
            // IMPORTANT: Save empty state to database to ensure fiscal year is cleared
            // This ensures that when all entries are deleted, the database is updated
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (departmentId) {
                console.log('Clearing all entries for department/office:', departmentId, 'fiscal year:', CURRENT_FISCAL_YEAR);
                // Save empty array to database to clear this fiscal year
                saveUtilizationToLocalStorage();
            }
        }
        
        function clearAllEntriesWithoutSaving() {
            const tbody = document.getElementById('utilizationTableBody');
            if (tbody) {
                tbody.innerHTML = '';
            }
            entryCounter = 0;
            calculateTotals();
            updateEmptyState();
        }

        function updateEmptyState() {
            const tbody = document.getElementById('utilizationTableBody');
            const emptyState = document.getElementById('emptyState');
            if (tbody && emptyState) {
                if (tbody.children.length === 0) {
                    emptyState.classList.remove('hidden');
                } else {
                    emptyState.classList.add('hidden');
                }
            }
        }

        // Function to update history badge count
        function updateHistoryBadge(departmentId) {
            if (!departmentId) return;

            fetch(`../api/get_utilization_history.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('historyBadge');
                    if (badge) {
                        // Count only summary type entries for the badge
                        const summaryCount = data.success && data.history ?
                            data.history.filter(item => item.type === 'summary').length : 0;

                        if (summaryCount > 0) {
                            badge.textContent = summaryCount;
                            badge.classList.remove('hidden');
                        } else {
                            badge.classList.add('hidden');
                        }
                    }
                })
                .catch(error => {
                    console.error('Error updating history badge:', error);
                });
        }

        let allUtilizationSummaries = [];

        function showHistory() {
            // Create modal if it doesn't exist
            const existingModal = document.getElementById('utilizationHistoryModal');
            if (!existingModal) {
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
            <div id="utilizationHistoryModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                    <div class="sticky top-0 bg-gradient-to-r from-maroon to-red-800 text-white p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h2 class="text-2xl font-bold">Utilization History</h2>
                            <div class="flex items-center gap-3">
                                <button onclick="closeUtilizationHistory()" class="text-white hover:text-red-200 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="bg-white bg-opacity-20 rounded-lg p-3">
                            <div class="grid grid-cols-3 gap-3">
                                <div>
                                    <label for="utilHistoryDepartmentFilter" class="block text-xs font-semibold mb-1">Department:</label>
                                    <select 
                                        id="utilHistoryDepartmentFilter" 
                                        onchange="filterUtilizationHistory('department')"
                                        class="w-full px-2 py-1.5 text-sm rounded-lg bg-white text-gray-900 border-2 border-white border-opacity-30 focus:ring-2 focus:ring-white focus:border-white outline-none transition-all"
                                    >
                                        ${departmentOptions}
                                    </select>
                                </div>
                                <div>
                                    <label for="utilHistoryOfficeFilter" class="block text-xs font-semibold mb-1">Office:</label>
                                    <select 
                                        id="utilHistoryOfficeFilter" 
                                        onchange="filterUtilizationHistory('office')"
                                        class="w-full px-2 py-1.5 text-sm rounded-lg bg-white text-gray-900 border-2 border-white border-opacity-30 focus:ring-2 focus:ring-white focus:border-white outline-none transition-all"
                                    >
                                        ${officeOptions}
                                    </select>
                                </div>
                                <div>
                                    <label for="utilHistoryYearFilter" class="block text-xs font-semibold mb-1">Year:</label>
                                    <select 
                                        id="utilHistoryYearFilter" 
                                        onchange="filterUtilizationHistory('year')"
                                        class="w-full px-2 py-1.5 text-sm rounded-lg bg-white text-gray-900 border-2 border-white border-opacity-30 focus:ring-2 focus:ring-white focus:border-white outline-none transition-all"
                                    >
                                        <option value="">All Years</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="utilizationHistoryContent" class="flex-1 overflow-y-auto p-6">
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
            const modal = document.getElementById('utilizationHistoryModal');
            if (modal) {
                modal.classList.remove('hidden');
            }

            loadUtilizationHistory();
        }

        function loadUtilizationHistory() {
            const departmentFilter = document.getElementById('utilHistoryDepartmentFilter')?.value || '';
            const officeFilter = document.getElementById('utilHistoryOfficeFilter')?.value || '';
            const yearFilter = document.getElementById('utilHistoryYearFilter')?.value || '';

            let url = '../api/get_all_utilization_summaries.php?';
            const params = [];
            const selectedFilter = officeFilter || departmentFilter;
            if (selectedFilter) params.push(`department_id=${selectedFilter}`);
            if (yearFilter) params.push(`year=${yearFilter}`);
            url += params.length > 0 ? params.join('&') : '';

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allUtilizationSummaries = data.summaries;
                        displayUtilizationHistory(data.summaries);
                        
                        // Populate year filter with available years from data
                        populateUtilYearFilter(data.summaries);
                    } else {
                        alert('Error loading history: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading utilization history');
                });
        }

        // Populate year filter dropdown with available years
        function populateUtilYearFilter(summaries) {
            const yearFilter = document.getElementById('utilHistoryYearFilter');
            if (!yearFilter) return;
            
            const currentValue = yearFilter.value;
            const years = new Set();
            
            summaries.forEach(summary => {
                if (summary.fiscal_year) {
                    years.add(parseInt(summary.fiscal_year));
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

        function filterUtilizationHistory(type) {
            if (type === 'department') {
                document.getElementById('utilHistoryOfficeFilter').value = '';
            } else if (type === 'office') {
                document.getElementById('utilHistoryDepartmentFilter').value = '';
            }
            loadUtilizationHistory();
        }

        function displayUtilizationHistory(summaries) {
            const content = document.getElementById('utilizationHistoryContent');
            if (!content) return;

            if (!summaries || summaries.length === 0) {
                content.innerHTML = `
            <div class="text-center py-12">
                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <p class="text-gray-500 text-lg">No utilization summaries found</p>
                <p class="text-gray-400 text-sm mt-2">Save a utilization summary to see it here</p>
            </div>
        `;
                return;
            }

            let html = '<div class="space-y-4">';
            summaries.forEach((summary, index) => {
                const createdDate = new Date(summary.created_at);
                const updatedDate = summary.updated_at ? new Date(summary.updated_at) : null;
                const displayDate = updatedDate || createdDate;
                const formattedDate = displayDate.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'short',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });

                const statusBadge = summary.status === 'updated'
                    ? '<span class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full text-xs font-semibold">Updated</span>'
                    : '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">Created</span>';

                let totals = {};
                try {
                    totals = typeof summary.totals === 'string' ? JSON.parse(summary.totals) : (summary.totals || {});
                } catch (e) {
                    totals = {};
                }

                html += `
            <div class="bg-white border-2 ${index === 0 ? 'border-maroon' : 'border-gray-200'} rounded-xl p-4 hover:shadow-lg transition-all">
                <div class="flex items-center justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-gray-800">${summary.department_name || summary.dept_name || 'Unknown'}</h3>
                            ${statusBadge}
                            ${index === 0 ? '<span class="px-2 py-1 bg-maroon text-white rounded-full text-xs font-semibold">Latest</span>' : ''}
                        </div>
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Fiscal Year:</span> ${summary.fiscal_year} | 
                            <span class="font-medium">Date:</span> ${formattedDate}
                        </div>
                        ${totals.grandTotalBalance ? `<div class="text-sm mt-1"><span class="font-medium">Total Balance:</span> <span class="text-green-600 font-bold">${formatNumber(totals.grandTotalBalance)}</span></div>` : ''}
                    </div>
                    <div class="flex items-center gap-2">
                        <button onclick="viewUtilizationSummary(${summary.id})" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View
                        </button>
                        <button onclick="downloadUtilizationPdfFromHistory(${summary.id})" class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Download
                        </button>
                        <button onclick="deleteUtilizationSummary(${summary.id})" class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors text-sm font-semibold flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
            });
            html += '</div>';

            content.innerHTML = html;
        }

        function closeUtilizationHistory() {
            const modal = document.getElementById('utilizationHistoryModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function viewUtilizationSummary(summaryId) {
            fetch(`../api/get_utilization_summary.php?id=${summaryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.summary) {
                        showSummaryViewModal(data.summary);
                    } else {
                        alert('Error loading summary: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading summary');
                });
        }

        function downloadUtilizationPdfFromHistory(summaryId) {
            if (!summaryId) {
                alert('Invalid summary ID');
                return;
            }

            // Open PDF download in new tab
            window.open(`../api/download_utilization_pdf.php?id=${summaryId}`, '_blank');
        }

        function deleteUtilizationSummary(summaryId) {
            if (!confirm('Are you sure you want to delete this utilization summary? This action cannot be undone.')) {
                return;
            }

            fetch(`../api/delete_utilization_summary.php?id=${summaryId}`, {
                method: 'DELETE'
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Summary deleted successfully!');
                        loadUtilizationHistory();
                    } else {
                        alert('Error deleting summary: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error deleting summary');
                });
        }

        function closeHistoryModal() {
            const modal = document.getElementById('historyModal');
            const modalContent = document.getElementById('historyModalContent');

            if (modal && modalContent) {
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }

        // View history summary in modal
        function viewHistorySummary(summaryId) {
            if (!summaryId) {
                alert('Invalid summary ID');
                return;
            }

            // Fetch summary data and display in a modal
            fetch(`../api/get_utilization_summary.php?id=${summaryId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.summary) {
                        // Create and show summary view modal
                        showSummaryViewModal(data.summary);
                    } else {
                        alert('Error loading summary: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading summary:', error);
                    alert('Error loading summary. Please try again.');
                });
        }

        // Show summary view modal with data
        function showSummaryViewModal(summary) {
            // Check if modal exists, if not create it
            let modal = document.getElementById('historySummaryViewModal');
            if (!modal) {
                modal = document.createElement('div');
                modal.id = 'historySummaryViewModal';
                modal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[90] hidden flex items-center justify-center p-4';
                modal.innerHTML = `
            <div class="bg-white rounded-2xl shadow-2xl max-w-6xl w-full max-h-[90vh] overflow-hidden flex flex-col">
                <div class="bg-gradient-to-r from-maroon via-red-700 to-red-800 px-6 py-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white">Budget Utilization Summary</h2>
                        <p class="text-red-100 text-sm mt-1" id="historySummaryDeptName">Department/Office: -</p>
                    </div>
                    <button onclick="closeHistorySummaryViewModal()" class="text-white hover:text-red-200 transition-colors p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto p-8" id="historySummaryContent">
                    <!-- Summary content will be loaded here -->
                </div>
            </div>
        `;
                document.body.appendChild(modal);
            }

            // Set department name
            const deptNameEl = document.getElementById('historySummaryDeptName');
            if (deptNameEl) {
                deptNameEl.textContent = `Department/Office: ${summary.department_name || 'Unknown'} - Fiscal Year ${summary.fiscal_year || new Date().getFullYear()}`;
            }

            // Build summary content
            const contentEl = document.getElementById('historySummaryContent');
            if (contentEl) {
                const utilizationEntries = JSON.parse(summary.utilization_entries || '[]');
                const prEntries = JSON.parse(summary.pr_entries || '[]');
                const travelsEntries = JSON.parse(summary.travels_entries || '[]');
                const prDeductions = JSON.parse(summary.pr_deductions || '[]');
                const travelsDeductions = JSON.parse(summary.travels_deductions || '[]');

                let html = `
            <div class="space-y-6">
                <div class="bg-gray-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Utilization Entries</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="py-2 px-4 text-left">Category</th>
                                <th class="py-2 px-4 text-right">Allocated</th>
                                <th class="py-2 px-4 text-right">Deductions</th>
                                <th class="py-2 px-4 text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${utilizationEntries.length > 0 ? utilizationEntries.map(e => `
                                <tr class="border-b">
                                    <td class="py-2 px-4">${e.category || '-'}</td>
                                    <td class="py-2 px-4 text-right">${formatNumber(e.allocated || 0)}</td>
                                    <td class="py-2 px-4 text-right">${formatNumber(e.deductions || 0)}</td>
                                    <td class="py-2 px-4 text-right">${formatNumber(e.total || 0)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="4" class="py-4 text-center text-gray-500">No entries</td></tr>'}
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-blue-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-blue-800 mb-4">Purchase Requests</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-blue-100">
                                <th class="py-2 px-4 text-left">Description</th>
                                <th class="py-2 px-4 text-left">PR Number</th>
                                <th class="py-2 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${prEntries.length > 0 ? prEntries.map(e => `
                                <tr class="border-b">
                                    <td class="py-2 px-4">${e.purchaseRequest || e.purchase_request || '-'}</td>
                                    <td class="py-2 px-4">${e.prNumber || e.pr_number || '-'}</td>
                                    <td class="py-2 px-4 text-right">${formatNumber(e.amount || 0)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="3" class="py-4 text-center text-gray-500">No entries</td></tr>'}
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-green-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-green-800 mb-4">Travels</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-green-100">
                                <th class="py-2 px-4 text-left">Travelled</th>
                                <th class="py-2 px-4 text-left">Event</th>
                                <th class="py-2 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${travelsEntries.length > 0 ? travelsEntries.map(e => `
                                <tr class="border-b">
                                    <td class="py-2 px-4">${e.travelled || '-'}</td>
                                    <td class="py-2 px-4">${e.event || '-'}</td>
                                    <td class="py-2 px-4 text-right">${formatNumber(e.amount || 0)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="3" class="py-4 text-center text-gray-500">No entries</td></tr>'}
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-indigo-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-indigo-800 mb-4">Purchase Request Deductions</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-indigo-100">
                                <th class="py-2 px-4 text-left">Expense Category</th>
                                <th class="py-2 px-4 text-left">Purchase Request</th>
                                <th class="py-2 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${prDeductions.length > 0 ? prDeductions.map(entry => {
                                let rows = '';
                                if (entry.items && entry.items.length > 0) {
                                    entry.items.forEach((item, index) => {
                                        if (index === 0) {
                                            rows += `<tr class="border-b">
                                                <td class="py-2 px-4 font-semibold align-top" rowspan="${entry.items.length}">${entry.category || '-'}</td>
                                                <td class="py-2 px-4">${item.purchaseRequest || '-'}</td>
                                                <td class="py-2 px-4 text-right align-top font-semibold" rowspan="${entry.items.length}">${formatNumber(entry.amount || 0)}</td>
                                            </tr>`;
                                        } else {
                                            rows += `<tr class="border-b">
                                                <td class="py-2 px-4">${item.purchaseRequest || '-'}</td>
                                            </tr>`;
                                        }
                                    });
                                } else {
                                    rows = `<tr class="border-b">
                                        <td class="py-2 px-4">${entry.category || '-'}</td>
                                        <td class="py-2 px-4">-</td>
                                        <td class="py-2 px-4 text-right">${formatNumber(entry.amount || 0)}</td>
                                    </tr>`;
                                }
                                return rows;
                            }).join('') : '<tr><td colspan="3" class="py-4 text-center text-gray-500">No purchase request deductions</td></tr>'}
                        </tbody>
                    </table>
                </div>
                
                <div class="bg-emerald-50 rounded-xl p-6">
                    <h3 class="text-lg font-bold text-emerald-800 mb-4">Travels Deductions</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-emerald-100">
                                <th class="py-2 px-4 text-left">Expense Category</th>
                                <th class="py-2 px-4 text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${travelsDeductions.length > 0 ? travelsDeductions.map(e => `
                                <tr class="border-b">
                                    <td class="py-2 px-4">${e.category || '-'}</td>
                                    <td class="py-2 px-4 text-right">${formatNumber(e.amount || 0)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="2" class="py-4 text-center text-gray-500">No travels deductions</td></tr>'}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
                contentEl.innerHTML = html;
            }

            // Show modal
            modal.classList.remove('hidden');
        }

        function closeHistorySummaryViewModal() {
            const modal = document.getElementById('historySummaryViewModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        // Download history summary as PDF (uses same function as utilization_view)
        function downloadHistoryPdf(summaryId) {
            if (!summaryId) {
                alert('Invalid summary ID');
                return;
            }

            // Use the same PDF generation approach as utilization_view
            downloadUtilizationPDF(summaryId);
        }

        // Function to download utilization summary PDF (same as utilization_view.php)
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
                    const prDeductions = JSON.parse(summary.pr_deductions || '[]');
                    const travelsDeductions = JSON.parse(summary.travels_deductions || '[]');
                    const totals = JSON.parse(summary.totals || '{}');

                    // Build utilization table rows
                    let utilizationRows = '';
                    if (utilizationEntries.length === 0) {
                        utilizationRows = '<tr><td colspan="4" style="text-align: center; padding: 16px; font-style: italic;">No budget utilization entries found</td></tr>';
                    } else {
                        utilizationEntries.forEach(entry => {
                            const balanceClass = entry.balance < 0 ? 'negative' : '';
                            utilizationRows += `
                        <tr>
                            <td><strong>${entry.category || '-'}</strong></td>
                            <td class="text-right">₱${formatNumber(entry.allocated || 0)}</td>
                            <td class="text-right">₱${formatNumber(entry.deduction || entry.deductions || 0)}</td>
                            <td class="text-right ${balanceClass}"><strong>₱${formatNumber(entry.balance || entry.total || 0)}</strong></td>
                        </tr>
                    `;
                        });
                    }

                    // Build PR table rows
                    let prRows = '';
                    if (prEntries.length === 0) {
                        prRows = '<tr><td colspan="5" style="text-align: center; padding: 16px; font-style: italic;">No purchase requests found</td></tr>';
                    } else {
                        prEntries.forEach(entry => {
                            const particulars = entry.particulars ? (entry.particulars.length > 50 ? entry.particulars.substring(0, 50) + '...' : entry.particulars) : '-';
                            prRows += `
                        <tr>
                            <td>${entry.purchaseRequest || entry.purchase_request || '-'}</td>
                            <td>${particulars}</td>
                            <td>${entry.prNumber || entry.pr_number || '-'}</td>
                            <td>${entry.date || '-'}</td>
                            <td class="text-right">₱${formatNumber(entry.amount || 0)}</td>
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
                            const event = (entry.event_activity || entry.event) ? ((entry.event_activity || entry.event).length > 50 ? (entry.event_activity || entry.event).substring(0, 50) + '...' : (entry.event_activity || entry.event)) : '-';
                            travelsRows += `
                        <tr>
                            <td>${entry.travelled || '-'}</td>
                            <td>${event}</td>
                            <td>${entry.date || '-'}</td>
                            <td class="text-right">₱${formatNumber(entry.amount || 0)}</td>
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

                    printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Budget Utilization Summary - ${summary.department_name || 'Department/Office'}</title>
                    <style>
                        @page { size: landscape; margin: 1cm; }
                        * { margin: 0; padding: 0; box-sizing: border-box; }
                        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; padding: 15px; font-size: 10px; color: #333; }
                        .header { border-bottom: 4px solid #800000; padding-bottom: 15px; margin-bottom: 20px; }
                        .header h1 { color: #800000; font-size: 24px; margin-bottom: 5px; }
                        .header-info { display: flex; justify-content: space-between; margin-top: 10px; font-size: 9px; color: #666; }
                        h2 { color: #800000; margin-top: 20px; margin-bottom: 10px; font-size: 14px; border-bottom: 2px solid #800000; padding-bottom: 5px; }
                        table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 9px; }
                        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
                        th { background: linear-gradient(to bottom, #800000, #a00000); color: white; font-weight: bold; font-size: 9px; text-transform: uppercase; }
                        tr:nth-child(even) { background-color: #f9f9f9; }
                        .text-right { text-align: right; }
                        .negative { color: #dc2626; }
                        .footer { margin-top: 30px; padding-top: 15px; border-top: 2px solid #800000; font-size: 9px; color: #666; text-align: center; }
                        @media print { body { print-color-adjust: exact; -webkit-print-color-adjust: exact; } }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>Budget Utilization Summary</h1>
                        <div class="header-info">
                            <div><strong>Department/Office:</strong> ${summary.department_name || 'N/A'}</div>
                            <div><strong>Fiscal Year:</strong> ${summary.fiscal_year || new Date().getFullYear()}</div>
                            <div><strong>Generated:</strong> ${generatedDate}</div>
                        </div>
                    </div>
                    
                    <h2>Budget Utilization Entries</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Expense Category</th>
                                <th class="text-right">Allocated Budget</th>
                                <th class="text-right">Deductions</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>${utilizationRows}</tbody>
                    </table>
                    
                    <h2>Purchase Requests</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Particulars</th>
                                <th>PR/PO Number</th>
                                <th>Date</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>${prRows}</tbody>
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
                        <tbody>${travelsRows}</tbody>
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
                            }).join('') : '<tr><td colspan="3" style="text-align: center; padding: 16px; font-style: italic;">No purchase request deductions</td></tr>'}
                        </tbody>
                    </table>
                    
                    <h2>Travels Deductions</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Expense Category</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${travelsDeductions.length > 0 ? travelsDeductions.map(entry => `
                                <tr>
                                    <td>${entry.category || '-'}</td>
                                    <td class="text-right">₱${formatNumber(entry.amount || 0)}</td>
                                </tr>
                            `).join('') : '<tr><td colspan="2" style="text-align: center; padding: 16px; font-style: italic;">No travels deductions</td></tr>'}
                        </tbody>
                    </table>
                    
                    <div class="footer">
                        <p>BudgetTrack System - Generated on ${generatedDate}</p>
                        <p style="margin-top: 5px;">This document is system-generated. Please verify all figures before use.</p>
                    </div>
                
</body>
                </html>
            `);

                    printWindow.document.close();

                    // Auto-trigger print dialog after a short delay
                    setTimeout(() => {
                        printWindow.print();
                    }, 500);
                })
                .catch(error => {
                    console.error('Error loading summary for PDF:', error);
                    alert('Error generating PDF. Please try again.');
                });
        }

        // Bulk Entry Modal Management
        function openBulkEntryModal() {
            const modal = document.getElementById('bulkEntryModal');
            const modalContent = document.getElementById('bulkEntryModalContent');
            const textarea = document.getElementById('bulkEntryTextarea');

            if (modal && modalContent && textarea) {
                // Clear textarea
                textarea.value = '';

                // Show modal with fade-in animation
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Focus on textarea
                setTimeout(() => {
                    textarea.focus();
                }, 100);
            }
        }

        function closeBulkEntryModal() {
            const modal = document.getElementById('bulkEntryModal');
            const modalContent = document.getElementById('bulkEntryModalContent');

            if (modal && modalContent) {
                // Fade out animation
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }

        function confirmBulkEntries() {
            const textarea = document.getElementById('bulkEntryTextarea');
            if (!textarea) return;

            const text = textarea.value.trim();
            if (!text) {
                alert('Please enter at least one expense category.');
                return;
            }

            // Split by newlines and filter out empty lines
            const categories = text.split('\n')
                .map(line => line.trim())
                .filter(line => line.length > 0);

            if (categories.length === 0) {
                alert('Please enter at least one expense category.');
                return;
            }

            // Add all entries to the table
            const tbody = document.getElementById('utilizationTableBody');
            if (!tbody) return;

            categories.forEach(category => {
                entryCounter++;
                const row = document.createElement('tr');
                row.id = `entryRow_${entryCounter}`;
                row.className = 'hover:bg-gray-50 transition-colors';

                row.innerHTML = `
            <td class="py-2 px-4">
                <input 
                    type="text" 
                    id="columnArea_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                    value="${category.replace(/"/g, '&quot;')}"
                >
            </td>
            <td class="py-2 px-4">
                <input 
                    type="text" 
                    id="accountCode_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-center focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                    placeholder="Account Code"
                >
            </td>
            <td class="py-2 px-4">
                <input 
                    type="text" 
                    id="budgetAllocated_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                    placeholder="0.00"
                >
            </td>
            <td class="py-2 px-4">
                <div class="flex items-center gap-1.5 relative">
                    <input 
                        type="text" 
                        id="deduction_${entryCounter}" 
                        class="flex-1 px-3 py-1.5 border border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                        placeholder="0.00"
                    >
                    <button 
                        onclick="showDeductionSourceMenu(${entryCounter})" 
                        class="p-1.5 bg-maroon text-white rounded-lg hover:bg-red-700 transition-all shadow-sm flex items-center justify-center"
                        title="Add deduction from Purchase Request, Travels, or Honoraria"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </button>
                    <div id="deductionSourceMenu_${entryCounter}" class="hidden fixed z-50 w-72 bg-white rounded-lg shadow-xl border border-gray-200">
                        <div class="p-2">
                            <div class="text-xs font-semibold text-gray-500 px-3 py-1.5">Select Source:</div>
                            <button onclick="showDeductionEntries(${entryCounter}, 'purchase_request')" class="w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 rounded transition-colors">
                                📋 Purchase Request
                            </button>
                            <button onclick="showDeductionEntries(${entryCounter}, 'travels')" class="w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 rounded transition-colors">
                                ✈️ Travels
                            </button>
                            <div class="border-t border-gray-200 my-1"></div>
                            <button onclick="showAddAmountModal(${entryCounter})" class="w-full text-left px-3 py-1.5 text-sm text-gray-700 hover:bg-orange-50 hover:text-orange-700 rounded transition-colors font-semibold">
                                ➕ Add Amount
                            </button>
                        </div>
                    </div>
                </div>
            </td>
            <td class="py-2 px-4">
                <input 
                    type="text" 
                    id="total_${entryCounter}" 
                    class="w-full px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-50 text-right font-bold text-gray-900 text-sm" 
                    readonly
                    value="₱0.00"
                >
            </td>
            <td class="py-2 px-4 text-center">
                <button 
                    onclick="removeEntry(${entryCounter})" 
                    class="p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-sm flex items-center justify-center mx-auto"
                    title="Remove entry"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
            </td>
        `;

                tbody.appendChild(row);

                // Setup input listeners for the new row
                setupAmountInputListeners(`budgetAllocated_${entryCounter}`);
                // Setup deduction input listeners
                setupDeductionInputListeners(`deduction_${entryCounter}`, entryCounter);
            });

            // Update empty state and calculate totals
            updateEmptyState();
            calculateTotals();



            // Save to localStorage immediately with the data we just added
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const selectedId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (selectedId) {
                // Get existing entries from localStorage for THIS specific department/office (account-specific)
                const storageKey = `utilization_data_user_${CURRENT_USER_ID}_dept_${selectedId}_year_${CURRENT_FISCAL_YEAR}`;
                const existingData = localStorage.getItem(storageKey);
                let existingEntries = [];

                if (existingData) {
                    try {
                        const parsed = JSON.parse(existingData);
                        if (parsed.entries && Array.isArray(parsed.entries)) {
                            existingEntries = parsed.entries;
                            console.log('Found', existingEntries.length, 'existing entries for department/office ID:', selectedId);
                        }
                    } catch (e) {
                        console.error('Error parsing existing localStorage data:', e);
                    }
                } else {
                    console.log('No existing entries found for department/office ID:', selectedId);
                }

                // Add the new entries we just created
                const newEntries = categories.map(category => ({
                    expense_category: category.trim(),
                    allocated_budget: 0,
                    deductions: 0,
                    total_balance: 0
                }));

                // Combine existing and new entries (avoid duplicates by category name)
                const allEntries = [...existingEntries];
                newEntries.forEach(newEntry => {
                    // Check if this category already exists
                    const exists = allEntries.some(existing =>
                        existing.expense_category && existing.expense_category.trim().toLowerCase() === newEntry.expense_category.toLowerCase()
                    );
                    if (!exists) {
                        allEntries.push(newEntry);
                    }
                });

                // Save to localStorage first (for immediate UI update)
                localStorage.setItem(storageKey, JSON.stringify({
                    entries: allEntries,
                    department_id: selectedId,
                    saved_at: new Date().toISOString()
                }));

                console.log('Saved', allEntries.length, 'entries to localStorage for department/office ID:', selectedId, 'Storage key:', storageKey);

                // IMMEDIATELY save to database (SINGLE SOURCE OF TRUTH) so all budget role accounts can see it
                const dbEntries = allEntries.map(entry => ({
                    category: entry.expense_category || '',
                    allocated: entry.allocated_budget || 0,
                    deductions: entry.deductions || 0,
                    balance: entry.total_balance || 0
                }));

                fetch('../api/save_utilization_entry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        department_id: selectedId,
                        entries: dbEntries,
                        fiscal_year: CURRENT_FISCAL_YEAR
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            console.log('Saved new entries to database (SINGLE SOURCE OF TRUTH) for department:', selectedId, '-', dbEntries.length, 'entries');
                            // Update localStorage after successful database save to keep in sync
                            localStorage.setItem(storageKey, JSON.stringify({
                                entries: allEntries,
                                department_id: selectedId,
                                saved_at: new Date().toISOString(),
                                synced_from_db: true
                            }));
                        } else {
                            console.error('Error saving new entries to database:', data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error saving new entries to database:', error);
                    });
            }

            // Also save using the standard function (as backup)
            setTimeout(() => {
                saveUtilizationToLocalStorage();
            }, 300);

            // Clear the "cleared" flag since we now have entries
            if (selectedId) {
                const clearedFlagKey = `utilization_cleared_user_${CURRENT_USER_ID}_dept_${selectedId}_year_${CURRENT_FISCAL_YEAR}`;
                localStorage.removeItem(clearedFlagKey);
                console.log('Cleared the "no entries" flag for fiscal year', CURRENT_FISCAL_YEAR);
            }

            // Close modal
            closeBulkEntryModal();

            // Show success message
            const count = categories.length;
            alert(`Successfully added ${count} ${count === 1 ? 'entry' : 'entries'} to the table!`);
        }

        function removeEntry(entryId) {
            const row = document.getElementById(`entryRow_${entryId}`);
            if (!row) {
                console.error('Row not found for entry ID:', entryId);
                alert('Error: Entry row not found. Please refresh the page.');
                return;
            }

            // Check if we're viewing a prior year - if so, prevent deletion
            // Prior years are years BEFORE the current fiscal year, not the current calendar year
            // For example, if current fiscal year is 2025, then 2024 and earlier are prior years
            const currentYear = new Date().getFullYear();
            
            // Allow deletion if:
            // 1. Viewing current calendar year OR
            // 2. Viewing a future year (for planning purposes)
            // Only block if viewing a year that's definitely in the past (more than 1 year old)
            if (CURRENT_FISCAL_YEAR < (currentYear - 1)) {
                alert(`⚠️ Cannot delete entries from prior year ${CURRENT_FISCAL_YEAR}.\n\nPrior years are archived and cannot be modified.\n\nPlease switch to the current year (${currentYear}) to make changes.`);
                return;
            }

            // Get category name for confirmation
            const categoryInput = document.getElementById(`columnArea_${entryId}`);
            const categoryName = categoryInput ? categoryInput.value : 'this entry';

            // Confirm deletion
            if (!confirm(`Are you sure you want to delete "${categoryName}"? This action cannot be undone.`)) {
                return;
            }

            // Get database entry ID if this entry was saved
            const dbEntryId = row.getAttribute('data-db-entry-id');
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            console.log('Removing entry:', {
                entryId: entryId,
                dbEntryId: dbEntryId,
                departmentId: departmentId,
                categoryName: categoryName
            });

            // Helper function to handle successful deletion
            const handleSuccessfulDeletion = () => {
                // Remove row from DOM
                row.remove();
                calculateTotals();
                updateEmptyState();

                // Remove entry from localStorage immediately to prevent it from reappearing (account-specific with fiscal year)
                const storageKey = `utilization_data_user_${CURRENT_USER_ID}_dept_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
                const savedData = localStorage.getItem(storageKey);
                if (savedData) {
                    try {
                        const parsed = JSON.parse(savedData);
                        if (parsed.entries && Array.isArray(parsed.entries)) {
                            // Remove the deleted entry from localStorage by category name
                            parsed.entries = parsed.entries.filter(entry => {
                                const entryCategory = (entry.expense_category || '').trim().toLowerCase();
                                const deletedCategory = (categoryName || '').trim().toLowerCase();
                                return entryCategory !== deletedCategory;
                            });
                            localStorage.setItem(storageKey, JSON.stringify(parsed));
                            console.log('Removed entry from localStorage for fiscal year', CURRENT_FISCAL_YEAR, ':', categoryName);
                        }
                    } catch (e) {
                        console.error('Error updating localStorage after deletion:', e);
                    }
                }

                // Remove deduction from localStorage for this entry using the new function
                if (departmentId && entryId) {
                    removeDeductionFromLocalStorage(entryId, departmentId);

                    // Also remove deduction_sources for this entry (used in summary table)
                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, entryId);
                    localStorage.removeItem(deductionSourcesKey);
                    console.log('Removed deduction sources for entry:', entryId);

                    // Also remove deduction_selections for this entry
                    ['purchase_request', 'travels', 'honoraria'].forEach(sourceType => {
                        const selectionsKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${entryId}_source_${sourceType}`;
                        localStorage.removeItem(selectionsKey);
                    });
                }

                // Reload utilization entries to reflect the changes
                loadUtilizationEntries(departmentId).then(() => {
                    // After reloading, save the current state to database
                    // This ensures the database reflects the deletion
                    saveUtilizationToLocalStorage();
                    
                    // Recalculate deductions from database
                    recalculateAllDeductions().then(() => {
                        // Save deductions to localStorage (will exclude deleted entry)
                        saveDeductionsToLocalStorage(departmentId);
                        console.log('Utilization entry deleted and deductions removed successfully');
                    });
                });
            };

            // Helper function to try deleting by category name
            const tryDeleteByCategoryName = () => {
                if (!departmentId || !categoryName) {
                    console.error('Cannot delete by category: missing department ID or category name');
                    removeEntryFromLocalStorage();
                    return;
                }

                // Use the new API method that accepts category_name directly
                console.log('Attempting to delete by category name:', categoryName, 'for department:', departmentId, 'fiscal year:', CURRENT_FISCAL_YEAR);
                fetch('../api/delete_utilization_entry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        category_name: categoryName,
                        department_id: departmentId,
                        fiscal_year: CURRENT_FISCAL_YEAR
                    })
                })
                    .then(async response => {
                        if (!response.ok) {
                            // Try to get error message from response
                            let errorMessage = 'Network response was not ok';
                            try {
                                const errorData = await response.json();
                                errorMessage = errorData.message || errorMessage;
                            } catch (e) {
                                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                            }
                            throw new Error(errorMessage);
                        }
                        return response.json();
                    })
                    .then(deleteData => {
                        if (deleteData.success) {
                            console.log('Successfully deleted entry by category name');
                            handleSuccessfulDeletion();
                        } else {
                            console.error('Delete by category name failed:', deleteData.message);
                            // Still remove from DOM and localStorage as fallback
                            alert('Warning: Could not delete from database: ' + deleteData.message + '. Entry removed from display only.');
                            removeEntryFromLocalStorage();
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting by category name:', error);
                        // Try force delete as last resort
                        console.log('Attempting force delete as last resort...');
                        fetch('../api/force_delete_utilization_entry.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                category_name: categoryName,
                                department_id: departmentId,
                                fiscal_year: CURRENT_FISCAL_YEAR
                            })
                        })
                            .then(async response => {
                                if (!response.ok) {
                                    let errorMessage = 'Network response was not ok';
                                    try {
                                        const errorData = await response.json();
                                        errorMessage = errorData.message || errorMessage;
                                    } catch (e) {
                                        errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                                    }
                                    throw new Error(errorMessage);
                                }
                                return response.json();
                            })
                            .then(deleteData => {
                                if (deleteData.success) {
                                    console.log('Successfully force deleted entry');
                                    alert('Entry force deleted successfully!');
                                    handleSuccessfulDeletion();
                                } else {
                                    alert('Error: Could not delete entry even with force delete: ' + deleteData.message + '. Entry removed from display only.');
                                    removeEntryFromLocalStorage();
                                }
                            })
                            .catch(forceError => {
                                console.error('Force delete also failed:', forceError);
                                // Try manual delete as absolute last resort
                                console.log('Attempting manual delete as absolute last resort...');
                                fetch('../api/manual_delete_entry.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        category_name: categoryName,
                                        department_id: departmentId,
                                        fiscal_year: CURRENT_FISCAL_YEAR
                                    })
                                })
                                    .then(async response => {
                                        if (!response.ok) {
                                            let errorMessage = 'Network response was not ok';
                                            try {
                                                const errorData = await response.json();
                                                errorMessage = errorData.message || errorMessage;
                                            } catch (e) {
                                                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                                            }
                                            throw new Error(errorMessage);
                                        }
                                        return response.json();
                                    })
                                    .then(deleteData => {
                                        if (deleteData.success) {
                                            console.log('Successfully manually deleted entry');
                                            alert('Entry manually deleted from database!');
                                            handleSuccessfulDeletion();
                                        } else {
                                            alert('All deletion methods failed. Entry removed from display only. Error: ' + deleteData.message);
                                            removeEntryFromLocalStorage();
                                        }
                                    })
                                    .catch(manualError => {
                                        console.error('Manual delete also failed:', manualError);
                                        alert('All deletion methods failed. Entry removed from display only. You may need to delete it directly from the database. Last error: ' + manualError.message);
                                        removeEntryFromLocalStorage();
                                    });
                            });
                    });
            };

            // Helper function to remove entry from DOM and localStorage
            const removeEntryFromLocalStorage = () => {
                // Remove entry from localStorage if department is selected (account-specific)
                if (departmentId) {
                    const storageKey = `utilization_data_user_${CURRENT_USER_ID}_dept_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
                    const savedData = localStorage.getItem(storageKey);
                    if (savedData) {
                        try {
                            const parsed = JSON.parse(savedData);
                            if (parsed.entries && Array.isArray(parsed.entries)) {
                                // Remove the deleted entry from localStorage by category name
                                parsed.entries = parsed.entries.filter(entry => {
                                    const entryCategory = (entry.expense_category || '').trim().toLowerCase();
                                    const deletedCategory = (categoryName || '').trim().toLowerCase();
                                    return entryCategory !== deletedCategory;
                                });
                                localStorage.setItem(storageKey, JSON.stringify(parsed));
                                console.log('Removed entry from localStorage:', categoryName);
                            }
                        } catch (e) {
                            console.error('Error updating localStorage after deletion:', e);
                        }
                    }

                    // Remove deduction from localStorage for this entry
                    const deductionsStorageKey = getDeductionsDataKey(departmentId);
                    const deductionsData = localStorage.getItem(deductionsStorageKey);
                    if (deductionsData) {
                        try {
                            const parsed = JSON.parse(deductionsData);
                            if (parsed.deductions && Array.isArray(parsed.deductions)) {
                                // Remove deductions for this category
                                parsed.deductions = parsed.deductions.filter(ded => {
                                    const dedCategory = (ded.category_name || ded.entry_id || '').trim().toLowerCase();
                                    const deletedCategory = (categoryName || '').trim().toLowerCase();
                                    return dedCategory !== deletedCategory;
                                });
                                localStorage.setItem(deductionsStorageKey, JSON.stringify(parsed));
                                console.log('Removed deduction from localStorage for deleted entry:', categoryName);
                            }
                        } catch (e) {
                            console.error('Error removing deduction from localStorage:', e);
                        }
                    }
                }

                // Remove from DOM
                row.remove();
                calculateTotals();
                updateEmptyState();

                // Recalculate deductions to ensure deleted entry's deduction is removed
                recalculateAllDeductions().then(() => {
                    if (departmentId) {
                        saveDeductionsToLocalStorage(departmentId);
                    }
                });

                // Save to localStorage after removing entry (this will save current state)
                saveUtilizationToLocalStorage();
            };

            // Delete from database if it exists
            if (dbEntryId && departmentId) {
                fetch('../api/delete_utilization_entry.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        entry_id: dbEntryId,
                        department_id: departmentId
                    })
                })
                    .then(async response => {
                        if (!response.ok) {
                            // Try to get error message from response
                            let errorMessage = 'Network response was not ok';
                            try {
                                const errorData = await response.json();
                                errorMessage = errorData.message || errorMessage;
                            } catch (e) {
                                errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                            }
                            throw new Error(errorMessage);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Successfully deleted from database
                            handleSuccessfulDeletion();
                        } else {
                            // Delete by ID failed, try to find and delete by category name
                            console.log('Delete by ID failed:', data.message, '- Trying to find entry by category name');
                            tryDeleteByCategoryName();
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting utilization entry:', error);
                        // Try to delete by category name as fallback
                        console.log('Attempting fallback delete by category name...');
                        tryDeleteByCategoryName();
                    });
            } else if (departmentId) {
                // Entry exists in database but no ID found in attribute, or entry not yet saved
                // Try to delete by category name directly using the new API method
                console.log('No database ID attribute found, attempting to delete by category name.');
                tryDeleteByCategoryName();
            } else {
                // No department selected, just remove from DOM
                console.log('No department selected, removing from DOM only');
                removeEntryFromLocalStorage();
            }
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
                    // No decimal point, format as integer with commas
                    const number = parseFloat(cleanValue) || 0;
                    return number.toLocaleString('en-US');
                }
            }
            return cleanValue;
        }

        function formatAmountInput(input) {
            if (!input) return;

            let value = input.value.replace(/[₱,]/g, '');

            // Allow empty or just decimal point
            if (value === '' || value === '.') {
                input.value = value;
                return;
            }

            // Format with commas but no peso sign while typing
            if (!isNaN(value)) {
                input.value = formatNumberInput(value);
            }
        }

        function setupAmountInputListeners(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            let originalValue = '';

            input.addEventListener('focus', function (e) {
                originalValue = e.target.value;
                // Remove peso sign and commas for easier editing
                e.target.value = e.target.value.replace(/[₱,]/g, '');
            });

            input.addEventListener('input', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value === '' || value === '.' || !isNaN(value)) {
                    // Format with commas but no peso sign while typing
                    e.target.value = formatNumberInput(value);
                    // Calculate totals as user types
                    const entryId = inputId.split('_')[1];
                    if (entryId) {
                        calculateRowTotal(entryId);
                    }
                    // Auto-save to localStorage
                    saveUtilizationToLocalStorage();
                } else {
                    // Invalid input, revert to previous value
                    e.target.value = originalValue.replace(/[₱,]/g, '');
                }
            });

            input.addEventListener('blur', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value !== '' && !isNaN(value)) {
                    // Format with peso sign and commas on blur
                    e.target.value = formatNumber(parseFloat(value));
                    originalValue = e.target.value;
                    // Calculate row total and overall totals
                    const entryId = inputId.split('_')[1];
                    if (entryId) {
                        calculateRowTotal(entryId);
                    }
                    // Auto-save to localStorage
                    saveUtilizationToLocalStorage();
                } else if (value === '') {
                    e.target.value = '';
                    const entryId = inputId.split('_')[1];
                    if (entryId) {
                        calculateRowTotal(entryId);
                    }
                    // Auto-save to localStorage
                    saveUtilizationToLocalStorage();
                }
            });
        }

        // Function to clear deduction sources for a specific entry
        function clearDeductionSourcesForEntry(entryId, departmentId) {
            if (!entryId || !departmentId) return;

            const deductionSourcesKey = getDeductionSourcesKey(departmentId, entryId);
            localStorage.removeItem(deductionSourcesKey);
            console.log(`✓ Cleared deduction sources for entry ${entryId}`);
        }

        // Function to cleanup empty/zero deduction sources on page load
        function cleanupEmptyDeductionSources(departmentId) {
            if (!departmentId) return;

            // Get all utilization entries
            const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
            let cleanedCount = 0;

            mainTableRows.forEach(row => {
                const domEntryId = row.id.split('_')[1];
                const deductionInput = document.getElementById(`deduction_${domEntryId}`);
                const deductionAmount = deductionInput ? parseAmount(deductionInput.value || '0') : 0;

                // If deduction amount is zero or empty, clear the deduction sources
                if (deductionAmount === 0) {
                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, domEntryId);
                    const savedSources = localStorage.getItem(deductionSourcesKey);
                    
                    if (savedSources) {
                        localStorage.removeItem(deductionSourcesKey);
                        cleanedCount++;
                        console.log(`✓ Cleaned up empty deduction sources for entry ${domEntryId}`);
                    }
                }
            });

            if (cleanedCount > 0) {
                console.log(`✓ Cleaned up ${cleanedCount} empty deduction source entries`);
            }
        }

        // Function to setup deduction input listeners
        function setupDeductionInputListeners(inputId, entryId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            let originalValue = '';

            input.addEventListener('focus', function (e) {
                originalValue = e.target.value;
                // Remove peso sign and commas for easier editing
                e.target.value = e.target.value.replace(/[₱,]/g, '');
            });

            input.addEventListener('input', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value === '' || value === '.' || !isNaN(value)) {
                    // Format with commas but no peso sign while typing
                    e.target.value = formatNumberInput(value);
                    // Calculate row total and overall totals
                    if (entryId) {
                        calculateRowTotal(entryId);
                        calculateTotals();
                    }
                    // If value is cleared (empty), remove from localStorage including deduction sources
                    if (value === '') {
                        const departmentSelect = document.getElementById('departmentSelect');
                        const officeSelect = document.getElementById('officeSelect');
                        const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                        if (departmentId && entryId) {
                            removeDeductionFromLocalStorage(entryId, departmentId);
                            // Also clear deduction sources for this entry
                            clearDeductionSourcesForEntry(entryId, departmentId);
                        }
                    }
                    // Auto-save to localStorage
                    saveUtilizationToLocalStorage();
                } else {
                    // Invalid input, revert to previous value
                    e.target.value = originalValue.replace(/[₱,]/g, '');
                }
            });

            input.addEventListener('blur', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value !== '' && !isNaN(value)) {
                    // Format with peso sign and commas on blur
                    e.target.value = formatNumber(parseFloat(value));
                    originalValue = e.target.value;
                    // Calculate row total and overall totals
                    if (entryId) {
                        calculateRowTotal(entryId);
                        calculateTotals();
                    }
                    // Auto-save to localStorage
                    saveUtilizationToLocalStorage();
                } else if (value === '') {
                    e.target.value = '';
                    // Calculate row total and overall totals
                    if (entryId) {
                        calculateRowTotal(entryId);
                        calculateTotals();
                    }
                    // Clear deduction from localStorage when value is cleared including deduction sources
                    const departmentSelect = document.getElementById('departmentSelect');
                    const officeSelect = document.getElementById('officeSelect');
                    const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                    if (departmentId && entryId) {
                        removeDeductionFromLocalStorage(entryId, departmentId);
                        // Also clear deduction sources for this entry
                        clearDeductionSourcesForEntry(entryId, departmentId);
                    }
                    // Auto-save to localStorage
                    saveUtilizationToLocalStorage();
                }
            });
        }

        // Setup auto-save to localStorage when inputs change
        function setupAutoSaveToLocalStorage() {
            // Use MutationObserver to watch for new rows added to the table
            const tableBody = document.getElementById('utilizationTableBody');
            if (!tableBody) return;

            // Listen for changes to category inputs
            document.addEventListener('input', function (e) {
                if (e.target && e.target.id && e.target.id.startsWith('columnArea_')) {
                    // Debounce the save to avoid too many saves, but ensure it saves
                    clearTimeout(window.utilizationSaveTimeout);
                    window.utilizationSaveTimeout = setTimeout(() => {
                        console.log('Auto-saving after category input change');
                        saveUtilizationToLocalStorage();
                    }, 500); // Increased delay slightly to reduce API calls but ensure save happens
                }
            });

            // Also listen for blur events on category inputs - save immediately on blur
            document.addEventListener('blur', function (e) {
                if (e.target && e.target.id && e.target.id.startsWith('columnArea_')) {
                    console.log('Auto-saving after category input blur');
                    // Clear any pending timeout and save immediately
                    if (window.utilizationSaveTimeout) {
                        clearTimeout(window.utilizationSaveTimeout);
                    }
                    saveUtilizationToLocalStorage();
                }
            }, true);

            // Also listen for budget allocated changes - these should also trigger save
            document.addEventListener('input', function (e) {
                if (e.target && e.target.id && e.target.id.startsWith('budgetAllocated_')) {
                    clearTimeout(window.utilizationSaveTimeout);
                    window.utilizationSaveTimeout = setTimeout(() => {
                        console.log('Auto-saving after budget allocated change');
                        saveUtilizationToLocalStorage();
                    }, 500);
                }
            });
        }

        // Restore selected department/office and data from localStorage on page load
        function restoreUtilizationFromLocalStorage() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentSearch = document.getElementById('departmentSearch');
            const officeSearch = document.getElementById('officeSearch');
            const selectedDepartmentDisplay = document.getElementById('selectedDepartmentDisplay');
            const selectedDepartmentName = document.getElementById('selectedDepartmentName');

            // Check for saved department selection (account-specific with fiscal year)
            const savedDeptId = localStorage.getItem(`utilization_selected_department_id_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);
            const savedDeptName = localStorage.getItem(`utilization_selected_department_name_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);

            // Check for saved office selection (account-specific with fiscal year)
            const savedOfficeId = localStorage.getItem(`utilization_selected_office_id_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);
            const savedOfficeName = localStorage.getItem(`utilization_selected_office_name_user_${CURRENT_USER_ID}_year_${CURRENT_FISCAL_YEAR}`);

            if (savedDeptId && savedDeptName) {
                // Restore department selection
                if (departmentSelect) departmentSelect.value = savedDeptId;
                if (departmentSearch) departmentSearch.value = savedDeptName;
                if (selectedDepartmentName) selectedDepartmentName.textContent = savedDeptName;
                if (selectedDepartmentDisplay) selectedDepartmentDisplay.classList.remove('hidden');

                // Show clear button
                const clearBtn = document.getElementById('clearDepartmentSearch');
                if (clearBtn) clearBtn.classList.remove('hidden');

                // Clear office selection
                if (officeSelect) officeSelect.value = '';
                if (officeSearch) officeSearch.value = '';
                const clearOfficeBtn = document.getElementById('clearOfficeSearch');
                if (clearOfficeBtn) clearOfficeBtn.classList.add('hidden');

                // Load utilization entries for the saved department
                setTimeout(() => {
                    loadUtilizationEntries(savedDeptId).then(() => {
                        loadPriorYearsCacheInBackground(savedDeptId);
                        setTimeout(() => {
                            recalculateAllDeductions().then(() => {
                                saveDeductionsToLocalStorage(savedDeptId);
                                loadDeductionsFromLocalStorage(savedDeptId);
                                // Cleanup empty deduction sources after loading
                                cleanupEmptyDeductionSources(savedDeptId);
                                console.log('Deductions auto-loaded after restoring department from localStorage');
                            });
                        }, 100);
                    });
                }, 200);
            } else if (savedOfficeId && savedOfficeName) {
                // Restore office selection
                if (officeSelect) officeSelect.value = savedOfficeId;
                if (officeSearch) officeSearch.value = savedOfficeName;
                if (selectedDepartmentName) selectedDepartmentName.textContent = savedOfficeName;
                if (selectedDepartmentDisplay) selectedDepartmentDisplay.classList.remove('hidden');

                // Show clear button
                const clearBtn = document.getElementById('clearOfficeSearch');
                if (clearBtn) clearBtn.classList.remove('hidden');

                // Clear department selection
                if (departmentSelect) departmentSelect.value = '';
                if (departmentSearch) departmentSearch.value = '';
                const clearDeptBtn = document.getElementById('clearDepartmentSearch');
                if (clearDeptBtn) clearDeptBtn.classList.add('hidden');

                // Load utilization entries for the saved office
                setTimeout(() => {
                    loadUtilizationEntries(savedOfficeId).then(() => {
                        loadPriorYearsCacheInBackground(savedOfficeId);
                        setTimeout(() => {
                            recalculateAllDeductions().then(() => {
                                saveDeductionsToLocalStorage(savedOfficeId);
                                loadDeductionsFromLocalStorage(savedOfficeId);
                                // Cleanup empty deduction sources after loading
                                cleanupEmptyDeductionSources(savedOfficeId);
                                console.log('Deductions auto-loaded after restoring office from localStorage');
                            });
                        }, 100);
                    });
                }, 200);
            } else {
                // No saved selection in localStorage
                // For budget role: auto-select their own Fiduciary/Budget Office department first
                if (BUDGET_OWN_DEPT_ID) {
                    setTimeout(() => {
                        const ownOfficeOption = document.querySelector(`.office-option[data-id="${BUDGET_OWN_DEPT_ID}"]`);
                        const ownDeptOption = document.querySelector(`.department-option[data-id="${BUDGET_OWN_DEPT_ID}"]`);
                        const targetOption = ownOfficeOption || ownDeptOption;

                        if (targetOption) {
                            const deptName = targetOption.dataset.name || targetOption.getAttribute('data-name') || targetOption.textContent.trim();
                            const isOffice = !!ownOfficeOption;

                            if (isOffice) {
                                if (officeSelect) officeSelect.value = BUDGET_OWN_DEPT_ID;
                                if (officeSearch) officeSearch.value = deptName;
                                const clearBtn = document.getElementById('clearOfficeSearch');
                                if (clearBtn) clearBtn.classList.remove('hidden');
                                if (departmentSelect) departmentSelect.value = '';
                                if (departmentSearch) departmentSearch.value = '';
                            } else {
                                if (departmentSelect) departmentSelect.value = BUDGET_OWN_DEPT_ID;
                                if (departmentSearch) departmentSearch.value = deptName;
                                const clearBtn = document.getElementById('clearDepartmentSearch');
                                if (clearBtn) clearBtn.classList.remove('hidden');
                                if (officeSelect) officeSelect.value = '';
                                if (officeSearch) officeSearch.value = '';
                            }

                            if (selectedDepartmentName) selectedDepartmentName.textContent = deptName;
                            if (selectedDepartmentDisplay) selectedDepartmentDisplay.classList.remove('hidden');

                            loadUtilizationEntries(BUDGET_OWN_DEPT_ID).then(() => {
                                loadPriorYearsCacheInBackground(BUDGET_OWN_DEPT_ID);
                                setTimeout(() => {
                                    recalculateAllDeductions().then(() => {
                                        saveDeductionsToLocalStorage(BUDGET_OWN_DEPT_ID);
                                        loadDeductionsFromLocalStorage(BUDGET_OWN_DEPT_ID);
                                        cleanupEmptyDeductionSources(BUDGET_OWN_DEPT_ID);
                                    });
                                }, 100);
                            });
                        }
                    }, 200);
                } else {
                // No saved selection in localStorage - scan all departments for data
                console.log('No saved selection in localStorage, checking database for available data...');

                // Wait a bit for DOM to be ready
                setTimeout(() => {
                    // Get all department/office options from the page
                    const departmentOptions = document.querySelectorAll('.department-option, .office-option');
                    const departmentsToCheck = [];

                    departmentOptions.forEach(option => {
                        const deptId = option.dataset.id || option.getAttribute('data-id');
                        const deptName = option.dataset.name || option.getAttribute('data-name') || option.textContent.trim();
                        if (deptId && deptName) {
                            departmentsToCheck.push({ id: deptId, name: deptName });
                        }
                    });

                    // Try to find the first department with utilization data
                    if (departmentsToCheck.length > 0) {
                        let checkedCount = 0;
                        const checkNextDepartment = () => {
                            if (checkedCount >= departmentsToCheck.length) {
                                console.log('No utilization data found in any department');
                                return;
                            }

                            const dept = departmentsToCheck[checkedCount];
                            checkedCount++;

                            // Check if this department has utilization data
                            fetch(`../api/load_utilization_entries.php?department_id=${dept.id}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success && data.entries && data.entries.length > 0) {
                                        // Found data! Auto-select this department
                                        console.log(`Found utilization data for ${dept.name}, auto-selecting...`);

                                        // Determine if it's a department or office
                                        const isOffice = Array.from(document.querySelectorAll('.office-option')).some(opt =>
                                            (opt.dataset.id || opt.getAttribute('data-id')) == dept.id
                                        );

                                        if (isOffice) {
                                            if (officeSelect) officeSelect.value = dept.id;
                                            if (officeSearch) officeSearch.value = dept.name;
                                        } else {
                                            if (departmentSelect) departmentSelect.value = dept.id;
                                            if (departmentSearch) departmentSearch.value = dept.name;
                                        }

                                        if (selectedDepartmentName) selectedDepartmentName.textContent = dept.name;
                                        if (selectedDepartmentDisplay) selectedDepartmentDisplay.classList.remove('hidden');

                                        const clearBtn = isOffice ?
                                            document.getElementById('clearOfficeSearch') :
                                            document.getElementById('clearDepartmentSearch');
                                        if (clearBtn) clearBtn.classList.remove('hidden');

                                        // Clear the other selector
                                        if (isOffice) {
                                            if (departmentSelect) departmentSelect.value = '';
                                            if (departmentSearch) departmentSearch.value = '';
                                        } else {
                                            if (officeSelect) officeSelect.value = '';
                                            if (officeSearch) officeSearch.value = '';
                                        }

                                        // Load utilization entries from database
                                        setTimeout(() => {
                                            loadUtilizationEntries(dept.id).then(() => {
                                                setTimeout(() => {
                                                    recalculateAllDeductions().then(() => {
                                                        saveDeductionsToLocalStorage(dept.id);
                                                        loadDeductionsFromLocalStorage(dept.id);
                                                        // Cleanup empty deduction sources after loading
                                                        cleanupEmptyDeductionSources(dept.id);
                                                        console.log(`Auto-loaded data for ${dept.name} from database`);
                                                    });
                                                }, 100);
                                            });
                                        }, 200);
                                    } else {
                                        // No data for this department, check next
                                        checkNextDepartment();
                                    }
                                })
                                .catch(error => {
                                    console.error(`Error checking department ${dept.name}:`, error);
                                    // Continue checking next department
                                    checkNextDepartment();
                                });
                        };

                        // Start checking from the first department
                        checkNextDepartment();
                    }
                }, 1000); // Wait for DOM to be ready
                } // end else (no BUDGET_OWN_DEPT_ID)
            }
        }

        function calculateRowTotal(entryId) {
            const budgetAllocatedEl = document.getElementById(`budgetAllocated_${entryId}`);
            const deductionEl = document.getElementById(`deduction_${entryId}`);
            const totalEl = document.getElementById(`total_${entryId}`);

            if (!budgetAllocatedEl || !deductionEl || !totalEl) return;

            const budgetAllocated = parseAmount(budgetAllocatedEl.value);
            const deduction = parseAmount(deductionEl.value);
            const total = budgetAllocated - deduction;
            totalEl.value = formatNumber(total);

            // Apply red color if negative
            if (total < 0) {
                totalEl.classList.remove('text-gray-900', 'text-green-600');
                totalEl.classList.add('text-red-600');
            } else {
                totalEl.classList.remove('text-red-600', 'text-green-600');
                totalEl.classList.add('text-gray-900');
            }

            calculateTotals();
        }

        function calculateTotals() {
            let totalAllocated = 0;
            let totalDeductions = 0;
            let totalBalance = 0;

            const rows = document.querySelectorAll('[id^="entryRow_"]');
            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const budgetAllocatedEl = document.getElementById(`budgetAllocated_${entryId}`);
                const deductionEl = document.getElementById(`deduction_${entryId}`);
                const totalEl = document.getElementById(`total_${entryId}`);

                if (budgetAllocatedEl) {
                    totalAllocated += parseAmount(budgetAllocatedEl.value);
                }
                if (deductionEl) {
                    totalDeductions += parseAmount(deductionEl.value);
                }
                if (totalEl) {
                    totalBalance += parseAmount(totalEl.value);
                }
            });

            const totalAllocatedEl = document.getElementById('totalAllocatedBudget');
            const totalDeductionsEl = document.getElementById('totalDeductions');
            const totalBalanceEl = document.getElementById('totalBalance');

            if (totalAllocatedEl) totalAllocatedEl.textContent = formatNumber(totalAllocated);
            if (totalDeductionsEl) totalDeductionsEl.textContent = formatNumber(totalDeductions);

            if (totalBalanceEl) {
                totalBalanceEl.textContent = formatNumber(totalBalance);

                // Apply red color if negative, green if positive
                if (totalBalance < 0) {
                    totalBalanceEl.classList.remove('text-green-600');
                    totalBalanceEl.classList.add('text-red-600');
                } else {
                    totalBalanceEl.classList.remove('text-red-600');
                    totalBalanceEl.classList.add('text-green-600');
                }
            }
        }

        // ==========================================
        // PPMP SELECTION MODAL MANAGEMENT
        // ==========================================
        let selectedPPMPItems = []; // For regular PPMP items
        let selectedSupplementalItems = []; // For supplemental items
        let ppmpItemsCache = [];
        let supplementalItemsCache = [];
        let currentPPMPSelectionTab = 'ppmp';

        function switchPPMPSelectionTab(tabName) {
            currentPPMPSelectionTab = tabName;
            
            // Update tab buttons
            document.querySelectorAll('.ppmp-selection-tab').forEach(btn => {
                btn.classList.remove('border-maroon', 'text-maroon', 'bg-maroon', 'bg-opacity-5', 'border-yellow-600', 'text-yellow-600', 'bg-yellow-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Highlight selected tab
            const selectedTab = document.getElementById('ppmpSelectionTab-' + tabName);
            if (selectedTab) {
                selectedTab.classList.remove('border-transparent', 'text-gray-500');
                if (tabName === 'ppmp') {
                    selectedTab.classList.add('border-maroon', 'text-maroon', 'bg-maroon', 'bg-opacity-5');
                } else {
                    selectedTab.classList.add('border-yellow-600', 'text-yellow-600', 'bg-yellow-600', 'bg-opacity-5');
                }
            }
            
            // Show/hide content
            document.querySelectorAll('.ppmp-selection-content').forEach(content => {
                content.classList.add('hidden');
            });
            
            const selectedContent = document.getElementById('ppmpSelectionContent-' + tabName);
            if (selectedContent) {
                selectedContent.classList.remove('hidden');
            }
        }

        function openPPMPSelectionModal() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (!departmentId) {
                alert('Please select a department/office first');
                return;
            }
            
            // Check if purchase request table is loaded
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (!tbody) {
                alert('Please wait for the purchase request table to load');
                return;
            }
            
            // Show modal
            const modal = document.getElementById('ppmpSelectionModal');
            if (modal) {
                modal.classList.remove('hidden');
                // Clear search inputs
                const ppmpSearch = document.getElementById('ppmpItemsSearch');
                const suppSearch = document.getElementById('supplementalItemsSearch');
                if (ppmpSearch) ppmpSearch.value = '';
                if (suppSearch) suppSearch.value = '';
                // Load both PPMP and Supplemental items
                loadPPMPItems(departmentId, 'ppmp');
                loadPPMPItems(departmentId, 'supplemental');
                // Switch to PPMP tab by default
                switchPPMPSelectionTab('ppmp');
            }
        }

        function closePPMPSelectionModal() {
            const modal = document.getElementById('ppmpSelectionModal');
            if (modal) {
                modal.classList.add('hidden');
            }
            // Don't reset selectedPPMPItems - keep the selection for next time
        }

        function loadPPMPItems(departmentId, ppmpType = 'ppmp') {
            const isSupplemental = ppmpType === 'supplemental';
            const containerPrefix = isSupplemental ? 'supplemental' : 'ppmp';
            const container = document.getElementById(`${containerPrefix}ItemsContainer`);
            const loading = document.getElementById(`${containerPrefix}ItemsLoading`);
            const empty = document.getElementById(`${containerPrefix}ItemsEmpty`);
            
            // Show loading
            container.classList.add('hidden');
            empty.classList.add('hidden');
            loading.classList.remove('hidden');
            
            // Fetch PPMP items
            fetch(`../api/get_ppmp_items_for_pr.php?department_id=${departmentId}&ppmp_type=${ppmpType}`)
                .then(response => response.json())
                .then(data => {
                    loading.classList.add('hidden');
                    
                    if (data.success && data.items && data.items.length > 0) {
                        if (isSupplemental) {
                            supplementalItemsCache = data.items;
                        } else {
                            ppmpItemsCache = data.items;
                        }
                        displayPPMPItems(data.items, ppmpType);
                        container.classList.remove('hidden');
                    } else {
                        empty.classList.remove('hidden');
                    }
                })
                .catch(error => {
                    console.error('Error loading PPMP items:', error);
                    loading.classList.add('hidden');
                    empty.classList.remove('hidden');
                });
        }

        function displayPPMPItems(items, ppmpType = 'ppmp') {
            const isSupplemental = ppmpType === 'supplemental';
            const containerPrefix = isSupplemental ? 'supplemental' : 'ppmp';
            const container = document.getElementById(`${containerPrefix}ItemsContainer`);
            container.innerHTML = '';
            
            // Use the correct selection array based on type
            const selectedItems = isSupplemental ? selectedSupplementalItems : selectedPPMPItems;
            
            // Get existing PPMP item IDs that are already added to purchase request
            const existingPPMPItemIds = [];
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (tbody) {
                const rows = tbody.querySelectorAll('tr[data-ppmp-item-id]');
                rows.forEach(row => {
                    const ppmpItemId = row.getAttribute('data-ppmp-item-id');
                    if (ppmpItemId) {
                        existingPPMPItemIds.push(parseInt(ppmpItemId));
                    }
                });
            }
            
            items.forEach(item => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'border-2 border-gray-200 rounded-xl p-4 hover:border-purple-400 transition-all cursor-pointer';
                itemDiv.onclick = () => togglePPMPItemSelection(item.id, ppmpType);
                itemDiv.id = `ppmpItem_${item.id}`;
                
                const remainingAmount = item.remaining_amount || (item.amount - (item.deducted_amount || 0));
                const isFullyDeducted = remainingAmount <= 0;
                const isAlreadyAdded = existingPPMPItemIds.includes(item.id);
                
                // Already-added items show as checked/disabled but are NOT added to the selection arrays
                // (they are excluded in addSelectedPPMPItems via the existingPPMPItemIds check)
                const isSelected = selectedItems.includes(item.id);
                
                // Apply selected styling if item is selected
                if (isSelected) {
                    itemDiv.classList.add('border-yellow-600', 'bg-yellow-50');
                    itemDiv.classList.remove('border-gray-200');
                }
                
                const ppmpLabel = isSupplemental ? 'Supplemental' : 'PPMP';
                const ppmpBadgeColor = isSupplemental ? 'bg-yellow-100 text-yellow-800' : 'bg-maroon bg-opacity-10 text-maroon';
                
                itemDiv.innerHTML = `
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 mt-1">
                            <input type="checkbox" 
                                id="ppmpCheckbox_${item.id}" 
                                class="w-5 h-5 text-yellow-600 rounded focus:ring-yellow-500"
                                ${isFullyDeducted || isAlreadyAdded ? 'disabled' : ''}
                                ${isSelected ? 'checked' : ''}
                                onclick="event.stopPropagation(); togglePPMPItemSelection(${item.id}, '${ppmpType}')">
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <div>
                                    <h4 class="font-bold text-gray-900 text-lg">${item.description}</h4>
                                    <p class="text-sm text-gray-600 mt-1">
                                        <span class="font-semibold">Type:</span> ${item.type} | 
                                        <span class="font-semibold">Qty:</span> ${item.quantity} | 
                                        <span class="font-semibold">Unit:</span> ${item.unit}
                                    </p>
                                </div>
                                <div class="text-right">
                                    <div class="text-lg font-bold text-yellow-600">₱${formatNumber(item.amount)}</div>
                                    ${item.deducted_amount > 0 ? `
                                        <div class="text-sm text-red-600">Deducted: ₱${formatNumber(item.deducted_amount)}</div>
                                        <div class="text-sm font-semibold ${isFullyDeducted ? 'text-red-600' : 'text-green-600'}">
                                            Remaining: ₱${formatNumber(remainingAmount)}
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <span class="px-2 py-1 ${ppmpBadgeColor} rounded-lg font-medium">
                                    ${ppmpLabel} #${item.ppmp_number}
                                </span>
                                <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-lg font-medium">
                                    FY ${item.fiscal_year}
                                </span>
                                ${isFullyDeducted ? `
                                    <span class="px-2 py-1 bg-red-100 text-red-700 rounded-lg font-medium">
                                        Fully Deducted
                                    </span>
                                ` : ''}
                                ${isAlreadyAdded ? `
                                    <span class="px-2 py-1 bg-green-100 text-green-700 rounded-lg font-medium">
                                        ✓ Already Added
                                    </span>
                                ` : ''}
                            </div>
                            ${item.expense_category ? `
                                <div class="mt-2 text-sm text-gray-600">
                                    <span class="font-semibold">Linked to:</span> ${item.expense_category}
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                container.appendChild(itemDiv);
            });
            
            // Update selected count
            updatePPMPSelectedCount();
        }

        function togglePPMPItemSelection(itemId, ppmpType = 'ppmp') {
            const checkbox = document.getElementById(`ppmpCheckbox_${itemId}`);
            const itemDiv = document.getElementById(`ppmpItem_${itemId}`);
            
            if (!checkbox || checkbox.disabled) return;
            
            checkbox.checked = !checkbox.checked;
            
            // Use the correct selection array based on type
            const isSupplemental = ppmpType === 'supplemental';
            let selectedItems = isSupplemental ? selectedSupplementalItems : selectedPPMPItems;
            
            if (checkbox.checked) {
                if (!selectedItems.includes(itemId)) {
                    selectedItems.push(itemId);
                }
                itemDiv.classList.add('border-yellow-600', 'bg-yellow-50');
                itemDiv.classList.remove('border-gray-200');
            } else {
                selectedItems = selectedItems.filter(id => id !== itemId);
                itemDiv.classList.remove('border-yellow-600', 'bg-yellow-50');
                itemDiv.classList.add('border-gray-200');
            }
            
            // Update the correct array
            if (isSupplemental) {
                selectedSupplementalItems = selectedItems;
            } else {
                selectedPPMPItems = selectedItems;
            }
            
            updatePPMPSelectedCount();
        }

        function updatePPMPSelectedCount() {
            const countElement = document.getElementById('ppmpSelectedCount');
            if (countElement) {
                // Count total selected items from both tabs
                const totalSelected = selectedPPMPItems.length + selectedSupplementalItems.length;
                countElement.textContent = totalSelected;
            }
        }

        function filterPPMPItems(ppmpType) {
            const isSupplemental = ppmpType === 'supplemental';
            const searchInput = document.getElementById(isSupplemental ? 'supplementalItemsSearch' : 'ppmpItemsSearch');
            const query = searchInput ? searchInput.value.toLowerCase().trim() : '';
            const cache = isSupplemental ? supplementalItemsCache : ppmpItemsCache;
            if (!cache || cache.length === 0) return;
            const filtered = query ? cache.filter(item =>
                (item.description || '').toLowerCase().includes(query) ||
                (item.type || '').toLowerCase().includes(query) ||
                String(item.ppmp_number || '').toLowerCase().includes(query)
            ) : cache;
            displayPPMPItems(filtered, ppmpType);
        }

        function filterDeductionEntries() {
            const query = (document.getElementById('deductionEntrySearch')?.value || '').toLowerCase().trim();
            const modalBody = document.getElementById('deductionEntryModalBody');
            if (!modalBody) return;
            const items = modalBody.querySelectorAll('.deduction-entry-item');
            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                item.style.display = (!query || text.includes(query)) ? '' : 'none';
            });
        }

        function addSelectedPPMPItems() {
            const totalSelected = selectedPPMPItems.length + selectedSupplementalItems.length;
            
            console.log('Selected PPMP items:', selectedPPMPItems.length);
            console.log('Selected supplemental items:', selectedSupplementalItems.length);
            console.log('Total selected:', totalSelected);
            
            if (totalSelected === 0) {
                alert('Please select at least one PPMP item');
                return;
            }
            
            // Get IDs of items already in the PR table to avoid duplicates
            const existingPPMPItemIds = [];
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (tbody) {
                tbody.querySelectorAll('tr[data-ppmp-item-id]').forEach(row => {
                    const id = row.getAttribute('data-ppmp-item-id');
                    if (id) existingPPMPItemIds.push(parseInt(id));
                });
            }

            // Get selected items from both caches, excluding already-added ones
            const ppmpItemsToAdd = ppmpItemsCache.filter(item => selectedPPMPItems.includes(item.id) && !existingPPMPItemIds.includes(item.id));
            const supplementalItemsToAdd = supplementalItemsCache.filter(item => selectedSupplementalItems.includes(item.id) && !existingPPMPItemIds.includes(item.id));
            const itemsToAdd = [...ppmpItemsToAdd, ...supplementalItemsToAdd];
            
            console.log('Items to add (excluding duplicates):', itemsToAdd.length);
            console.log('Items details:', itemsToAdd.map(item => ({ id: item.id, description: item.description })));
            
            if (itemsToAdd.length === 0) {
                closePPMPSelectionModal();
                return;
            }
            
            // CRITICAL: Disable auto-save during bulk addition to prevent partial saves
            window.isBulkAddingPPMPItems = true;
            
            // Create INDIVIDUAL entries for each selected item (not combined)
            itemsToAdd.forEach((item, index) => {
                console.log(`Adding individual item ${index + 1}:`, item.description);
                addPurchaseRequestEntryFromPPMP(item);
            });
            
            // Re-enable auto-save and trigger ONE save
            window.isBulkAddingPPMPItems = false;
            
            // Get department ID and save all entries
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (departmentId) {
                // Wait a bit for DOM to be ready, then save
                setTimeout(() => {
                    savePurchaseRequest();
                }, 500);
            }
            
            console.log(`Added ${itemsToAdd.length} item(s) as individual purchase request entries`);
            
            // Close modal
            closePPMPSelectionModal();
        }

        function addCombinedPurchaseRequestEntry(items) {
            purchaseRequestCounter++;
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (!tbody) return;

            const row = document.createElement('tr');
            row.id = `prRow_${purchaseRequestCounter}`;
            
            // Determine if items are mixed or all same type
            const hasRegular = items.some(item => item.ppmp_type !== 'supplemental');
            const hasSupplemental = items.some(item => item.ppmp_type === 'supplemental');
            
            let badgeText, badgeClass, rowBgColor, borderColor, ringColor, textColor;
            
            if (hasRegular && hasSupplemental) {
                // Mixed items
                badgeText = `Mixed PPMP Items (${items.length})`;
                badgeClass = 'bg-purple-100 text-purple-800';
                rowBgColor = 'bg-purple-50';
                borderColor = 'border-purple-300';
                ringColor = 'ring-purple-500';
                textColor = 'text-purple-600';
            } else if (hasSupplemental) {
                // All supplemental
                badgeText = `Supplemental Items (${items.length})`;
                badgeClass = 'bg-yellow-100 text-yellow-800';
                rowBgColor = 'bg-yellow-50';
                borderColor = 'border-yellow-300';
                ringColor = 'ring-yellow-500';
                textColor = 'text-yellow-600';
            } else {
                // All regular PPMP
                badgeText = `PPMP Items (${items.length})`;
                badgeClass = 'bg-red-100 text-red-800';
                rowBgColor = 'bg-red-50';
                borderColor = 'border-red-300';
                ringColor = 'ring-red-500';
                textColor = 'text-red-600';
            }
            
            row.className = `hover:bg-gray-50 transition-colors ${rowBgColor}`;
            
            // Store all PPMP item IDs as comma-separated string
            const ppmpItemIds = items.map(item => item.id).join(',');
            const ppmpIds = items.map(item => item.ppmp_id).join(',');
            row.setAttribute('data-ppmp-item-id', ppmpItemIds);
            row.setAttribute('data-ppmp-id', ppmpIds);
            row.setAttribute('data-ppmp-type', 'combined');
            row.setAttribute('data-item-count', items.length);

            // Create combined description with all items
            let combinedDescription = '';
            console.log('Creating combined description for', items.length, 'items'); // Debug log
            items.forEach((item, index) => {
                const itemLine = `${index + 1}. ${item.description} (${item.type}, Qty: ${item.quantity} ${item.unit}, ₱${formatNumber(item.amount)})`;
                console.log('Item', index + 1, ':', itemLine); // Debug log
                combinedDescription += itemLine;
                if (index < items.length - 1) {
                    combinedDescription += '\n';
                }
            });
            
            console.log('Final combined description:', combinedDescription); // Debug log
            
            // Calculate total amount
            const totalAmount = items.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
            
            // Get current date
            const now = new Date();
            const currentDate = now.toISOString().split('T')[0];

            row.innerHTML = `
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <textarea 
                    id="prPurchaseRequest_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 ${borderColor} rounded-lg focus:ring-2 focus:${ringColor} transition-all ${rowBgColor} text-gray-900 font-medium resize-none" 
                    rows="6"
                    readonly
                >${combinedDescription.trim()}</textarea>
                <div class="absolute right-3 top-3 ${textColor}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-1">
                <span class="inline-block px-2 py-1 ${badgeClass} rounded-lg text-xs font-semibold">
                    ${badgeText}
                </span>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prParticulars_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter particulars/reason..."
                    readonly
                    onclick="openParticularsModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prNumber_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter PR/PO number..."
                    readonly
                    onclick="openPRNumberModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="date" 
                id="prDate_${purchaseRequestCounter}" 
                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                value="${currentDate}"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="text" 
                id="prAmount_${purchaseRequestCounter}" 
                class="w-full px-4 py-2.5 border-2 ${borderColor} rounded-lg text-right focus:ring-2 focus:${ringColor} transition-all ${rowBgColor} text-gray-900 font-medium" 
                value="${formatNumber(totalAmount)}"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6 text-center">
            <button 
                onclick="removePurchaseRequestEntry(${purchaseRequestCounter})" 
                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                title="Remove entry"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    `;

            tbody.appendChild(row);

            // Setup amount input listener
            setupPurchaseRequestAmountListener(`prAmount_${purchaseRequestCounter}`);

            // Setup auto-save listeners
            setupPurchaseRequestAutoSave(purchaseRequestCounter);
        }

        function addPurchaseRequestEntryFromPPMP(ppmpItem) {
            purchaseRequestCounter++;
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (!tbody) return;

            const row = document.createElement('tr');
            row.id = `prRow_${purchaseRequestCounter}`;
            
            // Determine badge and colors based on ppmp_type
            const isSupplemental = ppmpItem.ppmp_type === 'supplemental';
            const badgeText = isSupplemental ? `Supplemental #${ppmpItem.ppmp_number}` : `PPMP #${ppmpItem.ppmp_number}`;
            const badgeClass = isSupplemental ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800';
            
            // Set row background color based on type
            const rowBgColor = isSupplemental ? 'bg-yellow-50' : 'bg-red-50';
            const borderColor = isSupplemental ? 'border-yellow-300' : 'border-red-300';
            const ringColor = isSupplemental ? 'ring-yellow-500' : 'ring-red-500';
            const textColor = isSupplemental ? 'text-yellow-600' : 'text-red-600';
            
            row.className = `hover:bg-gray-50 transition-colors ${rowBgColor}`;
            row.setAttribute('data-ppmp-item-id', ppmpItem.id);
            row.setAttribute('data-ppmp-id', ppmpItem.ppmp_id);
            row.setAttribute('data-ppmp-type', ppmpItem.ppmp_type || 'ppmp');

            // Format the purchase request description
            const prDescription = `${ppmpItem.description}, Type: ${ppmpItem.type}, Qty: ${ppmpItem.quantity}, Unit: ${ppmpItem.unit}, Amount: ${formatNumber(ppmpItem.amount)}`;
            
            // Get current date
            const now = new Date();
            const currentDate = now.toISOString().split('T')[0];

            row.innerHTML = `
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prPurchaseRequest_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 ${borderColor} rounded-lg focus:ring-2 focus:${ringColor} focus:border-${isSupplemental ? 'yellow' : 'red'}-500 transition-all ${rowBgColor} text-gray-900 font-medium" 
                    value="${prDescription}"
                    readonly
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 ${textColor}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-1">
                <span class="inline-block px-2 py-1 ${badgeClass} rounded-lg text-xs font-semibold">
                    From ${badgeText}
                </span>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prParticulars_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter particulars/reason..."
                    readonly
                    onclick="openParticularsModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prNumber_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter PR/PO number..."
                    readonly
                    onclick="openPRNumberModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="date" 
                id="prDate_${purchaseRequestCounter}" 
                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                value="${currentDate}"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="text" 
                id="prAmount_${purchaseRequestCounter}" 
                class="w-full px-4 py-2.5 border-2 ${borderColor} rounded-lg text-right focus:ring-2 focus:${ringColor} focus:border-${isSupplemental ? 'yellow' : 'red'}-500 transition-all ${rowBgColor} text-gray-900 font-medium" 
                value="${formatNumber(ppmpItem.amount)}"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6 text-center">
            <button 
                onclick="removePurchaseRequestEntry(${purchaseRequestCounter})" 
                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                title="Remove entry"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    `;

            tbody.appendChild(row);

            // Setup amount input listener
            setupPurchaseRequestAmountListener(`prAmount_${purchaseRequestCounter}`);

            // Setup auto-save listeners
            setupPurchaseRequestAutoSave(purchaseRequestCounter);

            // NOTE: Auto-save removed from here - will be triggered once after bulk addition completes
            // Individual auto-saves during bulk addition cause race conditions and partial saves
        }

        // Purchase Request Modal Management
        let purchaseRequestCounter = 0;
        
        // Debouncing variables for save operations
        let saveDebounceTimer = null;
        const SAVE_DEBOUNCE_DELAY = 500; // 500ms delay to prevent excessive saves

        function handlePurchaseRequest() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            const modal = document.getElementById('purchaseRequestModal');
            if (!modal) return;

            // Load saved PR entries from database FIRST, then show modal
            // This prevents race conditions where modal opens before data is loaded
            console.log('Loading purchase request entries before opening modal...');
            loadPurchaseRequestEntries(departmentId).then(() => {
                // After database entries are loaded, also load from localStorage as backup/unsaved changes
                // This will only add entries if there are no database entries
                loadPurchaseRequestFromLocalStorage(departmentId);

                // IMPORTANT: Don't recalculate deductions when opening modal - preserve existing deductions
                // Deductions are already loaded from database when entries are loaded
                // Only recalculate if we need to update them (e.g., after adding new PR entries)
                // This ensures deductions persist when opening/closing the modal
                // Just ensure row totals are calculated correctly
                const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                mainTableRows.forEach(row => {
                    const entryId = row.id.split('_')[1];
                    calculateRowTotal(entryId);
                });
                calculateTotals();
                
                // NOW show the modal after data is fully loaded
                modal.classList.remove('hidden');
                console.log('✓ Purchase request modal opened - data loaded, deductions preserved from database');
            }).catch(error => {
                console.error('Error loading purchase request entries:', error);
                alert('Error loading purchase request data. Please try again.');
            });
        }

        function closePurchaseRequestModal() {
            // Set flag to prevent auto-saves from triggering during close
            window.isClosingPurchaseRequestModal = true;

            // Simply close the modal - no saving, no reloading, no side effects
            // This preserves the checkbox state in the Select Source modal
            const modal = document.getElementById('purchaseRequestModal');
            if (modal) {
                modal.classList.add('hidden');
            }

            // Reset flag after a short delay
            setTimeout(() => {
                window.isClosingPurchaseRequestModal = false;
            }, 100);

            console.log('Purchase request modal closed (no changes made).');
        }

        // Function to clear purchase request modal content when switching departments
        function clearPurchaseRequestModal() {
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (tbody) {
                tbody.innerHTML = ''; // Clear all rows
            }
            // Reset counter if needed, but keep it global so IDs stay unique
            // purchaseRequestCounter is kept as is to avoid ID conflicts
        }

        function addPurchaseRequestEntry() {
            purchaseRequestCounter++;
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (!tbody) return;

            const row = document.createElement('tr');
            row.id = `prRow_${purchaseRequestCounter}`;
            row.className = 'hover:bg-gray-50 transition-colors';

            // Get current date for date input
            const now = new Date();
            const currentDate = now.toISOString().split('T')[0]; // Format: YYYY-MM-DD
            const timestamp = now.toLocaleString('en-US', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });

            row.innerHTML = `
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prPurchaseRequest_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter purchase request..."
                    readonly
                    onclick="openPurchaseRequestTextModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prParticulars_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter particulars/reason..."
                    readonly
                    onclick="openParticularsModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="prNumber_${purchaseRequestCounter}" 
                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter PR/PO number..."
                    readonly
                    onclick="openPRNumberModal(${purchaseRequestCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </div>
            </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="date" 
                id="prDate_${purchaseRequestCounter}" 
                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                value="${currentDate}"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="text" 
                id="prAmount_${purchaseRequestCounter}" 
                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                placeholder="0.00"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6 text-center">
            <button 
                onclick="removePurchaseRequestEntry(${purchaseRequestCounter})" 
                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                title="Remove entry"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    `;

            tbody.appendChild(row);

            // Setup amount input listener
            setupPurchaseRequestAmountListener(`prAmount_${purchaseRequestCounter}`);


            // Setup auto-save listeners
            setupPurchaseRequestAutoSave(purchaseRequestCounter);

            // Auto-save the new entry after a short delay to allow fields to be populated
            setTimeout(() => {
                autoSavePurchaseRequestEntry(purchaseRequestCounter);
            }, 500);
        }

        // Function to populate the "Deduct From" dropdown with expense categories from database
        // Returns a Promise that resolves when the dropdown is fully populated
        function populateDeductFromDropdown(selectId, retryCount = 0) {
            const MAX_RETRIES = 5; // Maximum 5 retries (500ms total wait time)
            
            return new Promise((resolve) => {
                const select = document.getElementById(selectId);
                if (!select) {
                    if (retryCount < MAX_RETRIES) {
                        console.warn(`populateDeductFromDropdown: Select element with ID ${selectId} not found, retrying (${retryCount + 1}/${MAX_RETRIES})...`);
                        // Retry after short delay if element not found (DOM may not be ready yet)
                        setTimeout(() => {
                            populateDeductFromDropdown(selectId, retryCount + 1).then(resolve);
                        }, 100);
                    } else {
                        console.error(`populateDeductFromDropdown: Select element with ID ${selectId} not found after ${MAX_RETRIES} retries. Giving up.`);
                        resolve(); // Resolve anyway to prevent hanging promises
                    }
                    return;
                }

                // Clear existing options except the first one
                while (select.children.length > 1) {
                    select.removeChild(select.lastChild);
                }

                // Get department ID
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

                if (!departmentId) {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Please select a department/office first';
                    option.disabled = true;
                    select.appendChild(option);
                    resolve();
                    return;
                }

                // First, try to get entries from DOM (main utilization table)
                const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                const domEntries = [];

                mainTableRows.forEach(row => {
                    const dbEntryId = row.getAttribute('data-db-entry-id');
                    const deductedFromEntryId = row.getAttribute('data-deducted-from-entry-id'); // Get deducted_from_entry_id
                    const domEntryId = row.id.split('_')[1];

                    if (dbEntryId && dbEntryId !== 'null' && dbEntryId !== 'undefined' && dbEntryId !== '') {
                        const categoryInput = document.getElementById(`columnArea_${domEntryId}`);
                        const budgetAllocatedInput = document.getElementById(`budgetAllocated_${domEntryId}`);

                        if (categoryInput && categoryInput.value && categoryInput.value.trim()) {
                            const categoryName = categoryInput.value.trim();
                            const budgetAllocated = budgetAllocatedInput ? parseAmount(budgetAllocatedInput.value || '0') : 0;

                            // Use deducted_from_entry_id if available, otherwise use id as fallback
                            const entryIdToUse = deductedFromEntryId && deductedFromEntryId !== 'null' && deductedFromEntryId !== 'undefined'
                                ? deductedFromEntryId
                                : dbEntryId;

                            domEntries.push({
                                id: dbEntryId,
                                deducted_from_entry_id: entryIdToUse,
                                expense_category: categoryName,
                                allocated_budget: budgetAllocated
                            });
                        }
                    }
                });

                // If we have DOM entries, use them (synchronous)
                if (domEntries.length > 0) {
                    domEntries.forEach(entry => {
                        const option = document.createElement('option');
                        // Use deducted_from_entry_id as the value (this is what will be saved)
                        // Convert to string to ensure consistent type matching
                        option.value = String(entry.deducted_from_entry_id);

                        if (entry.allocated_budget > 0) {
                            option.textContent = `${entry.expense_category} (Budget: ${formatNumber(entry.allocated_budget)})`;
                        } else {
                            option.textContent = `${entry.expense_category} (No budget allocated)`;
                            option.disabled = true;
                            option.style.color = '#999';
                        }

                        select.appendChild(option);
                    });
                    console.log(`populateDeductFromDropdown: Added ${domEntries.length} entries from DOM to dropdown ${selectId}`);
                    resolve(); // Resolve immediately since DOM entries are synchronous
                    return;
                }

                // If no DOM entries, load from database (asynchronous)
                console.log(`populateDeductFromDropdown: No DOM entries found, loading from database for department ${departmentId}`);
                fetch(`../api/load_utilization_entries.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.entries && data.entries.length > 0) {
                            let optionsAdded = 0;

                            data.entries.forEach(entry => {
                                const categoryName = (entry.expense_category || '').trim();
                                if (categoryName) {
                                    const option = document.createElement('option');
                                    // Use deducted_from_entry_id as the value (this is what should be used for deductions)
                                    // Convert to string to ensure consistent type matching
                                    const deductedFromEntryId = entry.deducted_from_entry_id || entry.id;
                                    option.value = String(deductedFromEntryId);

                                    const allocatedBudget = parseFloat(entry.allocated_budget || 0);
                                    if (allocatedBudget > 0) {
                                        option.textContent = `${categoryName} (Budget: ${formatNumber(allocatedBudget)})`;
                                    } else {
                                        option.textContent = `${categoryName} (No budget allocated)`;
                                        option.disabled = true;
                                        option.style.color = '#999';
                                    }

                                    select.appendChild(option);
                                    optionsAdded++;
                                }
                            });

                            console.log(`populateDeductFromDropdown: Added ${optionsAdded} entries from database to dropdown ${selectId}`);

                            if (optionsAdded === 0) {
                                const option = document.createElement('option');
                                option.value = '';
                                option.textContent = 'No expense categories available. Please add expense categories first.';
                                option.disabled = true;
                                select.appendChild(option);
                            }
                            resolve(); // Resolve after database entries are loaded
                        } else {
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'No expense categories available. Please add expense categories first.';
                            option.disabled = true;
                            select.appendChild(option);
                            console.log(`populateDeductFromDropdown: No entries found in database for department ${departmentId}`);
                            resolve();
                        }
                    })
                    .catch(error => {
                        console.error(`populateDeductFromDropdown: Error loading entries from database:`, error);
                        const option = document.createElement('option');
                        option.value = '';
                        option.textContent = 'Error loading expense categories';
                        option.disabled = true;
                        select.appendChild(option);
                        resolve(); // Resolve even on error
                    });
            });
        }

        function removePurchaseRequestEntry(entryId) {
            const row = document.getElementById(`prRow_${entryId}`);
            if (!row) return;

            // Get the database ID if this entry was saved
            const prId = row.getAttribute('data-pr-id');
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            // Get the category this PR was deducting from - check row attribute first, then select dropdown
            let selectedEntryId = row.getAttribute('data-deduct-from-entry-id');
            if (!selectedEntryId) {
                const deductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);
                selectedEntryId = deductFromSelect ? deductFromSelect.value : null;
            }

            // Delete from database if it exists
            if (prId && departmentId) {
                fetch('../api/delete_purchase_request.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        pr_id: prId,
                        department_id: departmentId
                    })
                })
                    .then(response => {
                        return response.json().then(data => {
                            if (!response.ok) {
                                throw new Error(data.message || 'Server error');
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            // Get the amount and PR ID before removing the row
                            const prAmountInput = document.getElementById(`prAmount_${entryId}`);
                            const prAmount = prAmountInput ? parseAmount(prAmountInput.value || '0') : 0;

                            // Remove row from DOM immediately
                            row.remove();
                            calculatePurchaseRequestTotal();

                            // NEW DEDUCTION SYSTEM: Check if this PR was used in any Expense Category deductions
                            if (prId && departmentId && prAmount > 0) {
                                // Find all Expense Categories that have this PR in their deduction sources
                                const allUtilizationRows = document.querySelectorAll('[id^="entryRow_"]');

                                allUtilizationRows.forEach(utilRow => {
                                    const categoryEntryId = utilRow.id.split('_')[1];
                                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, categoryEntryId);
                                    const savedSources = localStorage.getItem(deductionSourcesKey);

                                    if (savedSources) {
                                        try {
                                            let deductionSources = JSON.parse(savedSources);
                                            let updated = false;

                                            // Check each deduction source
                                            deductionSources.forEach((ds, index) => {
                                                if (ds.sourceType === 'purchase_request') {
                                                    // Find if this PR entry is in the entries array
                                                    const prEntryIndex = ds.entries.findIndex(e => {
                                                        const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                                        const prIdNum = parseInt(prId) || prId;
                                                        return eId === prIdNum || String(eId) === String(prIdNum) || e.sourceEntryId === prId;
                                                    });

                                                    if (prEntryIndex >= 0) {
                                                        // Found this PR in the deduction sources
                                                        const prEntryAmount = parseFloat(ds.entries[prEntryIndex].amount) || 0;

                                                        // Remove this PR entry from the array
                                                        ds.entries.splice(prEntryIndex, 1);

                                                        // Recalculate total amount
                                                        ds.amount = ds.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);

                                                        // Update deduction field
                                                        const deductionInput = document.getElementById(`deduction_${categoryEntryId}`);
                                                        if (deductionInput) {
                                                            const currentDeduction = parseAmount(deductionInput.value || '0');
                                                            const newDeduction = Math.max(0, currentDeduction - prEntryAmount);

                                                            if (newDeduction > 0) {
                                                                deductionInput.value = formatNumber(newDeduction);
                                                            } else {
                                                                deductionInput.value = '';
                                                            }

                                                            // Recalculate row total
                                                            calculateRowTotal(categoryEntryId);

                                                            console.log(`Removed PR ${prId} (${formatNumber(prEntryAmount)}) from deduction for category entry ${categoryEntryId}. New deduction: ${formatNumber(newDeduction)}`);
                                                        }

                                                        updated = true;
                                                    }
                                                }
                                            });

                                            // Remove deduction sources with 0 amount or no entries
                                            deductionSources = deductionSources.filter(ds => ds.amount > 0 && ds.entries.length > 0);

                                            if (updated) {
                                                // Save updated deduction sources
                                                if (deductionSources.length > 0) {
                                                    localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                                                } else {
                                                    localStorage.removeItem(deductionSourcesKey);
                                                }

                                                // Also remove from selections
                                                const selectionsKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${categoryEntryId}_source_purchase_request`;
                                                const savedSelections = localStorage.getItem(selectionsKey);
                                                if (savedSelections) {
                                                    try {
                                                        let selections = JSON.parse(savedSelections);
                                                        selections = selections.filter(sel => {
                                                            const selId = parseInt(sel) || sel;
                                                            const prIdNum = parseInt(prId) || prId;
                                                            return selId !== prIdNum && String(selId) !== String(prIdNum) && sel !== prId;
                                                        });

                                                        if (selections.length > 0) {
                                                            localStorage.setItem(selectionsKey, JSON.stringify(selections));
                                                        } else {
                                                            localStorage.removeItem(selectionsKey);
                                                        }
                                                    } catch (e) {
                                                        console.error('Error updating selections:', e);
                                                    }
                                                }

                                                // Save the updated deduction to database immediately
                                                saveUtilizationToLocalStorage();
                                            }
                                        } catch (e) {
                                            console.error('Error processing deduction sources:', e);
                                        }
                                    }
                                });
                            }

                            // OLD DEDUCTION SYSTEM: Handle legacy deducted_from_entry_id (if still exists)
                            const prDeductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);
                            const deductedFromEntryId = prDeductFromSelect ? prDeductFromSelect.value : selectedEntryId;

                            if (deductedFromEntryId && prAmount > 0) {
                                // Find the DOM entry ID by matching the database ID
                                let domEntryId = null;
                                const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');

                                for (let i = 0; i < mainTableRows.length; i++) {
                                    const row = mainTableRows[i];
                                    const rowDbEntryId = row.getAttribute('data-db-entry-id');
                                    const rowDeductedFromEntryId = row.getAttribute('data-deducted-from-entry-id');

                                    // Try to match by deducted_from_entry_id first
                                    if (rowDeductedFromEntryId && rowDeductedFromEntryId !== 'null' && rowDeductedFromEntryId !== 'undefined' && rowDeductedFromEntryId == deductedFromEntryId) {
                                        domEntryId = row.id.split('_')[1];
                                        break;
                                    }
                                    // Fallback: match by db-entry-id
                                    if (rowDbEntryId && rowDbEntryId !== 'null' && rowDbEntryId !== 'undefined' && rowDbEntryId == deductedFromEntryId) {
                                        domEntryId = row.id.split('_')[1];
                                        break;
                                    }
                                }

                                if (domEntryId) {
                                    const deductionInput = document.getElementById(`deduction_${domEntryId}`);
                                    if (deductionInput) {
                                        const currentDeduction = parseAmount(deductionInput.value || '0');
                                        const newDeduction = Math.max(0, currentDeduction - prAmount);
                                        if (newDeduction > 0) {
                                            deductionInput.value = formatNumber(newDeduction);
                                        } else {
                                            deductionInput.value = '';
                                        }

                                        // Recalculate row total for this entry
                                        calculateRowTotal(domEntryId);
                                        console.log(`Updated deduction for entry ${domEntryId} (db ID: ${deductedFromEntryId}): removed ${prAmount}, new total: ${newDeduction}`);
                                    }
                                } else {
                                    console.warn(`Could not find DOM entry ID for database entry ID: ${deductedFromEntryId} when deleting PR`);
                                }
                            }

                            // Save deductions to database immediately (don't recalculate - we already updated them correctly)
                            saveUtilizationToLocalStorage();
                            // Recalculate totals only
                            calculateTotals();
                            console.log('Purchase request deleted and deduction removed successfully');
                            // Show success message
                            if (data.message) {
                                console.log(data.message);
                            }
                            
                            // Check if PR modal is currently open
                            const modal = document.getElementById('purchaseRequestModal');
                            const isModalOpen = modal && !modal.classList.contains('hidden');
                            
                            // Reload PR list from database to ensure UI is in sync
                            // This prevents deleted entries from reappearing
                            if (departmentId) {
                                fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Clear localStorage to prevent deleted entries from reappearing
                                            const storageKey = `pr_entries_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
                                            localStorage.removeItem(storageKey);
                                            console.log('✓ Cleared PR localStorage after deletion');
                                            
                                            // Clear and reload PR table
                                            const tbody = document.getElementById('purchaseRequestTableBody');
                                            if (tbody) {
                                                tbody.innerHTML = '';
                                                purchaseRequestCounter = 0; // Reset counter
                                                
                                                if (data.entries && data.entries.length > 0) {
                                                    data.entries.forEach((entry, index) => {
                                                        purchaseRequestCounter++;
                                                        // Create row manually (same as loadPurchaseRequestEntries)
                                                        const row = document.createElement('tr');
                                                        row.id = `prRow_${purchaseRequestCounter}`;
                                                        row.className = 'hover:bg-gray-50 transition-colors';

                                                        if (entry.id) {
                                                            row.setAttribute('data-pr-id', entry.id);
                                                        }
                                                        if (entry.deducted_from_entry_id) {
                                                            row.setAttribute('data-deduct-from-entry-id', entry.deducted_from_entry_id);
                                                        }

                                                        const date = entry.date || '';
                                                        let particularsDisplay = entry.particulars || '';
                                                        let particularsTitle = '';
                                                        if (particularsDisplay.length > 50) {
                                                            particularsTitle = particularsDisplay;
                                                            particularsDisplay = particularsDisplay.substring(0, 50) + '...';
                                                        }

                                                        let prPoNumber = '';
                                                        if (entry.pr_number) prPoNumber = entry.pr_number;
                                                        if (entry.po_number) prPoNumber += (prPoNumber ? ' / ' : '') + entry.po_number;

                                                        row.innerHTML = `
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="prPurchaseRequest_${purchaseRequestCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${(entry.purchase_request || '').replace(/"/g, '&quot;')}">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="prParticulars_${purchaseRequestCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer"
                                                                    value="${particularsDisplay.replace(/"/g, '&quot;')}"
                                                                    title="${particularsTitle.replace(/"/g, '&quot;')}"
                                                                    onclick="openParticularsModal(${purchaseRequestCounter})" readonly>
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="prNumber_${purchaseRequestCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${prPoNumber.replace(/"/g, '&quot;')}">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="date" id="prDate_${purchaseRequestCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${date}">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="prAmount_${purchaseRequestCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${formatNumber(parseFloat(entry.amount || 0))}" placeholder="0.00">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6 text-center">
                                                                <button onclick="removePurchaseRequestEntry(${purchaseRequestCounter})"
                                                                    class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                                                                    title="Remove entry">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            </td>
                                                        `;
                                                        tbody.appendChild(row);
                                                    });
                                                } else if (isModalOpen) {
                                                    // If modal is open and no entries, show empty message
                                                    tbody.innerHTML = '<tr><td colspan="6" class="text-center py-4 text-gray-500">No purchase request entries</td></tr>';
                                                }
                                                calculatePurchaseRequestTotal();
                                            }
                                            console.log('✓ PR list reloaded from database');
                                            
                                            // If modal is open, ensure it stays visible and updated
                                            if (isModalOpen && modal) {
                                                modal.classList.remove('hidden');
                                            }
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error reloading PR list:', error);
                                    });
                            }
                        } else {
                            console.error('Error deleting purchase request:', data.message);
                            alert('Error deleting purchase request: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting purchase request:', error);
                        alert('Error deleting purchase request. Please try again.');
                    });
            } else {
                // If not saved to database, get amount before removing
                const prAmountInput = document.getElementById(`prAmount_${entryId}`);
                const prAmount = prAmountInput ? parseAmount(prAmountInput.value || '0') : 0;

                // Get a temporary ID for this unsaved PR (use the entryId as identifier)
                const tempPrId = entryId;

                // Remove row from DOM
                row.remove();
                calculatePurchaseRequestTotal();

                // NEW DEDUCTION SYSTEM: Check if this PR was used in any Expense Category deductions
                if (departmentId && prAmount > 0) {
                    // Find all Expense Categories that have this PR in their deduction sources
                    const allUtilizationRows = document.querySelectorAll('[id^="entryRow_"]');

                    allUtilizationRows.forEach(utilRow => {
                        const categoryEntryId = utilRow.id.split('_')[1];
                        const deductionSourcesKey = getDeductionSourcesKey(departmentId, categoryEntryId);
                        const savedSources = localStorage.getItem(deductionSourcesKey);

                        if (savedSources) {
                            try {
                                let deductionSources = JSON.parse(savedSources);
                                let updated = false;

                                // Check each deduction source
                                deductionSources.forEach((ds, index) => {
                                    if (ds.sourceType === 'purchase_request') {
                                        // Find if this PR entry is in the entries array (match by temp ID or amount)
                                        const prEntryIndex = ds.entries.findIndex(e => {
                                            const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                            const tempId = parseInt(tempPrId) || tempPrId;
                                            // Match by ID or by amount if ID matches
                                            return eId === tempId || String(eId) === String(tempId) || e.sourceEntryId === tempPrId;
                                        });

                                        if (prEntryIndex >= 0) {
                                            // Found this PR in the deduction sources
                                            const prEntryAmount = parseFloat(ds.entries[prEntryIndex].amount) || 0;

                                            // Remove this PR entry from the array
                                            ds.entries.splice(prEntryIndex, 1);

                                            // Recalculate total amount
                                            ds.amount = ds.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);

                                            // Update deduction field
                                            const deductionInput = document.getElementById(`deduction_${categoryEntryId}`);
                                            if (deductionInput) {
                                                const currentDeduction = parseAmount(deductionInput.value || '0');
                                                const newDeduction = Math.max(0, currentDeduction - prEntryAmount);

                                                if (newDeduction > 0) {
                                                    deductionInput.value = formatNumber(newDeduction);
                                                } else {
                                                    deductionInput.value = '';
                                                }

                                                // Recalculate row total
                                                calculateRowTotal(categoryEntryId);

                                                console.log(`Removed unsaved PR ${tempPrId} (${formatNumber(prEntryAmount)}) from deduction for category entry ${categoryEntryId}. New deduction: ${formatNumber(newDeduction)}`);
                                            }

                                            updated = true;
                                        }
                                    }
                                });

                                // Remove deduction sources with 0 amount or no entries
                                deductionSources = deductionSources.filter(ds => ds.amount > 0 && ds.entries.length > 0);

                                if (updated) {
                                    // Save updated deduction sources
                                    if (deductionSources.length > 0) {
                                        localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                                    } else {
                                        localStorage.removeItem(deductionSourcesKey);
                                    }

                                    // Also remove from selections
                                    const selectionsKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${categoryEntryId}_source_purchase_request`;
                                    const savedSelections = localStorage.getItem(selectionsKey);
                                    if (savedSelections) {
                                        try {
                                            let selections = JSON.parse(savedSelections);
                                            selections = selections.filter(sel => {
                                                const selId = parseInt(sel) || sel;
                                                const tempId = parseInt(tempPrId) || tempPrId;
                                                return selId !== tempId && String(selId) !== String(tempId) && sel !== tempPrId;
                                            });

                                            if (selections.length > 0) {
                                                localStorage.setItem(selectionsKey, JSON.stringify(selections));
                                            } else {
                                                localStorage.removeItem(selectionsKey);
                                            }
                                        } catch (e) {
                                            console.error('Error updating selections:', e);
                                        }
                                    }
                                }
                            } catch (e) {
                                console.error('Error processing deduction sources:', e);
                            }
                        }
                    });
                }

                // OLD DEDUCTION SYSTEM: Handle legacy deducted_from_entry_id (if still exists)
                const prDeductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);
                const deductedFromEntryId = prDeductFromSelect ? prDeductFromSelect.value : selectedEntryId;

                // Immediately update the deduction for the specific entry that had this PR
                if (deductedFromEntryId && prAmount > 0) {
                    const deductionInput = document.getElementById(`deduction_${deductedFromEntryId}`);
                    if (deductionInput) {
                        const currentDeduction = parseAmount(deductionInput.value || '0');
                        const newDeduction = Math.max(0, currentDeduction - prAmount);
                        if (newDeduction > 0) {
                            deductionInput.value = formatNumber(newDeduction);
                        } else {
                            deductionInput.value = '';
                        }

                        // Recalculate row total for this entry
                        calculateRowTotal(deductedFromEntryId);
                    }
                }

                // Immediately recalculate deductions
                recalculateAllDeductions().then(() => {
                    // Recalculate totals
                    calculateTotals();
                    // Save to localStorage after removal
                    if (departmentId) {
                        savePurchaseRequestToLocalStorage(departmentId);
                        saveDeductionsToLocalStorage(departmentId);
                        saveUtilizationToLocalStorage();
                    }
                });
            }
        }

        // Function to recalculate all deductions based on purchase requests and travels
        // Returns a Promise so callers can await it
        function recalculateAllDeductions() {
            return new Promise((resolve) => {
                // Get all expense categories
                const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');

                if (mainTableRows.length === 0) {
                    resolve();
                    return;
                }

                // Get department ID
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

                if (!departmentId) {
                    // If no department selected, just calculate from DOM
                    calculateDeductionsFromDOM();
                    resolve();
                    return;
                }

                // IMPORTANT: Include both database entries AND DOM entries (from open modals)
                // Since modals are cleared when switching departments, DOM entries should be for current department only
                // This ensures real-time updates work correctly
                // First, fetch utilization entries to create a mapping of database entry IDs to category names
                // This allows us to match deductions even when entry IDs change after page refresh
                Promise.all([
                    fetch(`../api/load_utilization_entries.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] })),
                    fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] })),
                    fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] })),
                    fetch(`../api/load_honoraria.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`).then(r => r.json()).catch(() => ({ success: false, entries: [] }))
                ]).then(([utilizationData, prData, travelsData, honorariaData]) => {
                    // Create a map to store deductions per entry ID (current DOM entry IDs)
                    const deductionsMap = new Map();

                    // Create a mapping of database entry IDs to category names (for matching deductions)
                    const dbEntryIdToCategoryMap = new Map();
                    // Also create reverse map: category name -> database entry ID (for fallback matching)
                    const categoryNameToDbEntryIdMap = new Map();
                    if (utilizationData.success && utilizationData.entries) {
                        utilizationData.entries.forEach(entry => {
                            if (entry.id && entry.expense_category) {
                                const categoryKey = entry.expense_category.trim().toLowerCase();
                                dbEntryIdToCategoryMap.set(entry.id, categoryKey);
                                categoryNameToDbEntryIdMap.set(categoryKey, entry.id);
                            }
                        });
                    }

                    // Create a reverse mapping: category name -> current DOM entry ID
                    const categoryToEntryIdMap = new Map();
                    mainTableRows.forEach(row => {
                        const entryId = row.id.split('_')[1];
                        const categoryInput = document.getElementById(`columnArea_${entryId}`);
                        if (categoryInput && categoryInput.value) {
                            const categoryName = categoryInput.value.trim().toLowerCase();
                            categoryToEntryIdMap.set(categoryName, entryId);
                        }
                    });

                    // IMPORTANT: Use database entries as the baseline, then overlay DOM entries
                    // Strategy: Build a map of database IDs that are currently in DOM, then use database for all others

                    // Step 1: Collect all PR, Travel, and Honoraria entries from DOM (to identify which database entries to exclude)
                    const domPRIds = new Set(); // Track which database PR IDs are in DOM
                    const domTravelIds = new Set(); // Track which database Travel IDs are in DOM
                    const domHonorariaIds = new Set(); // Track which database Honoraria IDs are in DOM
                    const domPRMap = new Map(); // Map of prId -> {entryId, amount} for DOM entries with database IDs
                    const domTravelMap = new Map(); // Map of travelId -> {entryId, amount} for DOM entries with database IDs
                    const domHonorariaMap = new Map(); // Map of honorariaId -> {entryId, amount} for DOM entries with database IDs

                    const allPRRows = document.querySelectorAll('[id^="prRow_"]');
                    allPRRows.forEach(prRow => {
                        const prEntryId = prRow.id.split('_')[1];
                        const prAmountInput = document.getElementById(`prAmount_${prEntryId}`);
                        const prDeductFromSelect = document.getElementById(`prDeductFrom_${prEntryId}`);
                        const prId = prRow.getAttribute('data-pr-id');

                        if (prAmountInput && prDeductFromSelect && prDeductFromSelect.value) {
                            const entryId = prDeductFromSelect.value;
                            const prAmount = parseAmount(prAmountInput.value || '0');

                            if (prAmount > 0) {
                                if (prId) {
                                    // This entry exists in database - track it to use DOM value instead of database
                                    domPRIds.add(String(prId));
                                    domPRMap.set(String(prId), { entryId: entryId, amount: prAmount });
                                }
                                // Note: New unsaved entries (no prId) will be handled separately after database entries
                            }
                        }
                    });

                    const allTravelRows = document.querySelectorAll('[id^="travelRow_"]');
                    allTravelRows.forEach(travelRow => {
                        const travelEntryId = travelRow.id.split('_')[1];
                        const travelAmountInput = document.getElementById(`travelAmount_${travelEntryId}`);
                        const travelDeductFromSelect = document.getElementById(`travelDeductFrom_${travelEntryId}`);
                        const travelId = travelRow.getAttribute('data-travel-id');

                        if (travelAmountInput && travelDeductFromSelect && travelDeductFromSelect.value) {
                            const entryId = travelDeductFromSelect.value;
                            const travelAmount = parseAmount(travelAmountInput.value || '0');

                            if (travelAmount > 0) {
                                if (travelId) {
                                    // This entry exists in database - track it to use DOM value instead of database
                                    domTravelIds.add(String(travelId));
                                    domTravelMap.set(String(travelId), { entryId: entryId, amount: travelAmount });
                                }
                                // Note: New unsaved entries (no travelId) will be handled separately after database entries
                            }
                        }
                    });

                    // Collect all Honoraria entries from DOM
                    const allHonorariaRows = document.querySelectorAll('[id^="amountDeductionRow_"]');
                    // Honoraria entries no longer deduct from categories

                    // Step 2: Add all PR entries from database EXCEPT those in DOM (which we'll add from DOM instead)
                    // Match by category name instead of entry ID (since entry IDs can change after refresh)
                    if (prData.success && prData.entries) {
                        console.log('Processing', prData.entries.length, 'PR entries from database for deduction calculation');
                        prData.entries.forEach(entry => {
                            if (!domPRIds.has(String(entry.id))) {
                                // This entry is not in DOM (modal closed or not loaded), use database value
                                if (entry.entry_id && entry.amount) {
                                    const dbEntryId = entry.entry_id;
                                    const amount = parseFloat(entry.amount || 0);

                                    if (amount > 0) {
                                        // Try multiple matching strategies to find the correct DOM entry
                                        let matchedEntryId = null;

                                        // Strategy 1: Find the category name for this database entry ID
                                        const categoryName = dbEntryIdToCategoryMap.get(dbEntryId);
                                        if (categoryName) {
                                            // Find the current DOM entry ID for this category
                                            matchedEntryId = categoryToEntryIdMap.get(categoryName);

                                            if (!matchedEntryId) {
                                                // If category not found in map, try to match by checking all entries
                                                // This handles cases where entry IDs changed but categories match
                                                mainTableRows.forEach(row => {
                                                    const entryId = row.id.split('_')[1];
                                                    const categoryInput = document.getElementById(`columnArea_${entryId}`);
                                                    if (categoryInput && categoryInput.value) {
                                                        const currentCategoryName = categoryInput.value.trim().toLowerCase();
                                                        if (currentCategoryName === categoryName) {
                                                            matchedEntryId = entryId;
                                                        }
                                                    }
                                                });
                                            }
                                        }

                                        // Strategy 2: If still not found, try matching by data-db-entry-id or data-deducted-from-entry-id
                                        if (!matchedEntryId) {
                                            mainTableRows.forEach(row => {
                                                const rowDbEntryId = row.getAttribute('data-db-entry-id');
                                                const rowDeductedFromEntryId = row.getAttribute('data-deducted-from-entry-id');

                                                // Try to match by deducted_from_entry_id first
                                                if (rowDeductedFromEntryId && rowDeductedFromEntryId !== 'null' && rowDeductedFromEntryId !== 'undefined' && rowDeductedFromEntryId == dbEntryId) {
                                                    matchedEntryId = row.id.split('_')[1];
                                                }
                                                // Fallback: match by db-entry-id
                                                else if (rowDbEntryId && rowDbEntryId !== 'null' && rowDbEntryId !== 'undefined' && rowDbEntryId == dbEntryId) {
                                                    matchedEntryId = row.id.split('_')[1];
                                                }
                                            });
                                        }

                                        if (matchedEntryId) {
                                            const current = deductionsMap.get(matchedEntryId) || 0;
                                            deductionsMap.set(matchedEntryId, current + amount);
                                            console.log(`✓ Added PR amount ${amount} to entry ${matchedEntryId} (dbEntryId: ${dbEntryId}, category: ${categoryName || 'unknown'})`);
                                        } else {
                                            console.warn(`⚠ Could not match PR entry (dbEntryId: ${dbEntryId}, amount: ${amount}) to any DOM entry. Category: ${categoryName || 'unknown'}`);
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Step 3: Add all Travel entries from database EXCEPT those in DOM
                    // Match by category name instead of entry ID
                    if (travelsData.success && travelsData.entries) {
                        travelsData.entries.forEach(entry => {
                            if (!domTravelIds.has(String(entry.id))) {
                                // This entry is not in DOM (modal closed or not loaded), use database value
                                if (entry.entry_id && entry.amount) {
                                    const dbEntryId = entry.entry_id;
                                    const amount = parseFloat(entry.amount || 0);

                                    if (amount > 0) {
                                        // Find the category name for this database entry ID
                                        const categoryName = dbEntryIdToCategoryMap.get(dbEntryId);
                                        if (categoryName) {
                                            // Find the current DOM entry ID for this category
                                            const currentEntryId = categoryToEntryIdMap.get(categoryName);
                                            if (currentEntryId) {
                                                const current = deductionsMap.get(currentEntryId) || 0;
                                                deductionsMap.set(currentEntryId, current + amount);
                                            } else {
                                                // If category not found in current DOM, try to match by checking all entries
                                                // This handles cases where entry IDs changed but categories match
                                                mainTableRows.forEach(row => {
                                                    const entryId = row.id.split('_')[1];
                                                    const categoryInput = document.getElementById(`columnArea_${entryId}`);
                                                    if (categoryInput && categoryInput.value) {
                                                        const currentCategoryName = categoryInput.value.trim().toLowerCase();
                                                        if (currentCategoryName === categoryName) {
                                                            const current = deductionsMap.get(entryId) || 0;
                                                            deductionsMap.set(entryId, current + amount);
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Step 3.5: Add all Honoraria entries from database EXCEPT those in DOM
                    // Match by category name instead of entry ID
                    if (honorariaData.success && honorariaData.entries) {
                        honorariaData.entries.forEach(entry => {
                            if (!domHonorariaIds.has(String(entry.id))) {
                                // This entry is not in DOM (modal closed or not loaded), use database value
                                if (entry.deductedFromEntryId && entry.amount) {
                                    const dbEntryId = entry.deductedFromEntryId;
                                    const amount = parseFloat(entry.amount || 0);

                                    if (amount > 0) {
                                        // Find the category name for this database entry ID
                                        const categoryName = dbEntryIdToCategoryMap.get(dbEntryId);
                                        if (categoryName) {
                                            // Find the current DOM entry ID for this category
                                            const currentEntryId = categoryToEntryIdMap.get(categoryName);
                                            if (currentEntryId) {
                                                const current = deductionsMap.get(currentEntryId) || 0;
                                                deductionsMap.set(currentEntryId, current + amount);
                                            } else {
                                                // If category not found in current DOM, try to match by checking all entries
                                                // This handles cases where entry IDs changed but categories match
                                                mainTableRows.forEach(row => {
                                                    const entryId = row.id.split('_')[1];
                                                    const categoryInput = document.getElementById(`columnArea_${entryId}`);
                                                    if (categoryInput && categoryInput.value) {
                                                        const currentCategoryName = categoryInput.value.trim().toLowerCase();
                                                        if (currentCategoryName === categoryName) {
                                                            const current = deductionsMap.get(entryId) || 0;
                                                            deductionsMap.set(entryId, current + amount);
                                                        }
                                                    }
                                                });
                                            }
                                        }
                                    }
                                }
                            }
                        });
                    }

                    // Step 4: Add DOM entries that have database IDs (these override database values for entries being edited)
                    domPRMap.forEach((value) => {
                        const entryId = value.entryId;
                        const amount = value.amount;
                        const current = deductionsMap.get(entryId) || 0;
                        deductionsMap.set(entryId, current + amount);
                    });

                    domTravelMap.forEach((value) => {
                        const entryId = value.entryId;
                        const amount = value.amount;
                        const current = deductionsMap.get(entryId) || 0;
                        deductionsMap.set(entryId, current + amount);
                    });

                    domHonorariaMap.forEach((value) => {
                        const entryId = value.entryId;
                        const amount = value.amount;
                        const current = deductionsMap.get(entryId) || 0;
                        deductionsMap.set(entryId, current + amount);
                    });

                    // Step 5: Add new unsaved entries from DOM (entries without database IDs)
                    allPRRows.forEach(prRow => {
                        const prEntryId = prRow.id.split('_')[1];
                        const prAmountInput = document.getElementById(`prAmount_${prEntryId}`);
                        const prDeductFromSelect = document.getElementById(`prDeductFrom_${prEntryId}`);
                        const prId = prRow.getAttribute('data-pr-id');

                        if (!prId && prAmountInput && prDeductFromSelect && prDeductFromSelect.value) {
                            // New unsaved entry - add to deductions
                            const entryId = prDeductFromSelect.value;
                            const prAmount = parseAmount(prAmountInput.value || '0');
                            if (prAmount > 0) {
                                const current = deductionsMap.get(entryId) || 0;
                                deductionsMap.set(entryId, current + prAmount);
                            }
                        }
                    });

                    allTravelRows.forEach(travelRow => {
                        const travelEntryId = travelRow.id.split('_')[1];
                        const travelAmountInput = document.getElementById(`travelAmount_${travelEntryId}`);
                        const travelDeductFromSelect = document.getElementById(`travelDeductFrom_${travelEntryId}`);
                        const travelId = travelRow.getAttribute('data-travel-id');

                        if (!travelId && travelAmountInput && travelDeductFromSelect && travelDeductFromSelect.value) {
                            // New unsaved entry - add to deductions
                            const entryId = travelDeductFromSelect.value;
                            const travelAmount = parseAmount(travelAmountInput.value || '0');
                            if (travelAmount > 0) {
                                const current = deductionsMap.get(entryId) || 0;
                                deductionsMap.set(entryId, current + travelAmount);
                            }
                        }
                    });

                    // Honoraria entries no longer deduct from categories

                    // Step 6: Add amount deductions from localStorage (legacy - for backward compatibility)
                    // Load all amount deductions for all entries from localStorage
                    mainTableRows.forEach(row => {
                        const sourceEntryId = row.id.split('_')[1];
                        const storageKey = getAmountDeductionsKey(departmentId, sourceEntryId);
                        const savedEntries = localStorage.getItem(storageKey);

                        if (savedEntries) {
                            try {
                                const entries = JSON.parse(savedEntries);
                                entries.forEach(entry => {
                                    if (entry.deductedFromEntryId && entry.amount > 0) {
                                        const deductedFromEntryId = entry.deductedFromEntryId;
                                        const amount = parseFloat(entry.amount || 0);
                                        if (amount > 0) {
                                            const current = deductionsMap.get(deductedFromEntryId) || 0;
                                            deductionsMap.set(deductedFromEntryId, current + amount);
                                        }
                                    }
                                });
                            } catch (e) {
                                console.error('Error loading amount deductions from localStorage:', e);
                            }
                        }
                    });

                    // Update deduction fields in the main table
                    // IMPORTANT: Only update deductions for entries that we found PR/Travel/Honoraria entries for
                    // Preserve existing deductions (from database) for entries that don't have PR/Travel/Honoraria entries
                    // This ensures deductions persist on refresh unless an entry was actually deleted
                    mainTableRows.forEach(row => {
                        const entryId = row.id.split('_')[1];
                        const deductionInput = document.getElementById(`deduction_${entryId}`);
                        const categoryInput = document.getElementById(`columnArea_${entryId}`);

                        if (deductionInput && categoryInput) {
                            const categoryName = categoryInput.value.trim().toLowerCase();

                            // Check if we have a calculated deduction for this entry
                            let calculatedDeduction = deductionsMap.get(entryId);

                            // If not found by entryId, try to find by category name (entry IDs change on refresh)
                            if (calculatedDeduction === undefined && categoryName) {
                                // Look for matching category in the database entries
                                const dbEntryId = categoryNameToDbEntryIdMap.get(categoryName);
                                if (dbEntryId) {
                                    // Check PR/Travel entries directly for this database entry ID
                                    let totalDeduction = 0;

                                    // Check PR entries
                                    if (prData.success && prData.entries) {
                                        prData.entries.forEach(prEntry => {
                                            if (prEntry.entry_id == dbEntryId && prEntry.amount) {
                                                totalDeduction += parseFloat(prEntry.amount || 0);
                                            }
                                        });
                                    }

                                    // Check Travel entries
                                    if (travelsData.success && travelsData.entries) {
                                        travelsData.entries.forEach(travelEntry => {
                                            if (travelEntry.entry_id == dbEntryId && travelEntry.amount) {
                                                totalDeduction += parseFloat(travelEntry.amount || 0);
                                            }
                                        });
                                    }

                                    // Check Honoraria entries
                                    if (honorariaData.success && honorariaData.entries) {
                                        honorariaData.entries.forEach(honorariaEntry => {
                                            if (honorariaEntry.deductedFromEntryId == dbEntryId && honorariaEntry.amount) {
                                                totalDeduction += parseFloat(honorariaEntry.amount || 0);
                                            }
                                        });
                                    }

                                    if (totalDeduction > 0) {
                                        calculatedDeduction = totalDeduction;
                                        // Store it in the map for this entry
                                        deductionsMap.set(entryId, totalDeduction);
                                    } else {
                                        // No PR/Travel entries found for this database entry ID
                                        // IMPORTANT: Always preserve deduction from database - it was calculated when PR was saved
                                        // The deduction in the database is the source of truth
                                        const currentDeductionValue = parseAmount(deductionInput.value || '0');

                                        // Only clear deduction if we're recalculating after a deletion AND confirmed no PR entries exist
                                        const isAfterDeletion = window.recalculatingAfterDeletion === true;

                                        if (isAfterDeletion && prData.success && travelsData.success && honorariaData.success && currentDeductionValue > 0) {
                                            // We're recalculating after a deletion, successfully fetched data, and entry had deduction
                                            // This means PR/Travel/Honoraria entries were deleted - set to 0
                                            calculatedDeduction = 0;
                                        } else if (currentDeductionValue > 0) {
                                            // Entry has deduction from database - ALWAYS preserve it
                                            // This ensures deductions persist even if PR entries can't be matched
                                            calculatedDeduction = currentDeductionValue;
                                            deductionsMap.set(entryId, currentDeductionValue);
                                            console.log(`✓ Preserving deduction ${currentDeductionValue} for entry ${entryId} (category: ${categoryName}) from database`);
                                        }
                                        // If current deduction is 0, keep undefined to preserve existing value (might be 0 from database)
                                    }
                                }
                            }

                            // IMPORTANT: Always use calculated deduction if available (from PR/Travel/Honoraria)
                            // But if no calculation was done, preserve the existing deduction from database
                            if (calculatedDeduction !== undefined) {
                                // We have definitive data from PR/Travel/Honoraria entries - update the deduction
                                if (calculatedDeduction > 0) {
                                    deductionInput.value = formatNumber(calculatedDeduction);
                                    console.log(`✓ Updated deduction for entry ${entryId} (category: ${categoryName}) to ${calculatedDeduction} from PR/Travel entries`);
                                } else {
                                    // If calculated deduction is 0, check if deduction exists in database
                                    // Only clear if we successfully fetched data AND confirmed no PR/Travel entries exist
                                    // AND the deduction wasn't in the database originally
                                    const currentDeductionValue = parseAmount(deductionInput.value || '0');

                                    // IMPORTANT: Always preserve deduction from database - it's the source of truth
                                    // Only clear if we're recalculating after a deletion
                                    const isAfterDeletion = window.recalculatingAfterDeletion === true;

                                    if (isAfterDeletion && prData.success && travelsData.success && honorariaData.success && currentDeductionValue > 0) {
                                        // We're recalculating after a deletion - PR entries were deleted, so clear deduction
                                        deductionInput.value = '';
                                        console.log(`✓ Cleared deduction for entry ${entryId} (category: ${categoryName}) - PR entries deleted`);
                                    } else if (currentDeductionValue > 0) {
                                        // Preserve deduction from database - it was calculated by API when PR was saved
                                        // Don't clear it even if PR entries can't be matched during recalculation
                                        console.log(`✓ Preserving deduction ${currentDeductionValue} for entry ${entryId} (category: ${categoryName}) from database`);
                                        // Keep the existing deduction value - don't change it
                                    } else {
                                        // No deduction in database and no PR entries - keep it empty
                                        deductionInput.value = '';
                                    }
                                }

                                // CRITICAL: Immediately recalculate row total to update balance
                                calculateRowTotal(entryId);
                            } else {
                                // If deduction wasn't recalculated, preserve existing value from database
                                // This ensures deductions persist even if PR entries can't be matched
                                const currentDeductionValue = parseAmount(deductionInput.value || '0');
                                if (currentDeductionValue > 0) {
                                    // Keep the existing deduction value from database
                                    console.log(`✓ Preserving deduction ${currentDeductionValue} for entry ${entryId} (category: ${categoryName}) from database`);
                                }
                                // Still update row total to ensure totals stay in sync
                                calculateRowTotal(entryId);
                            }
                        }
                    });

                    // Recalculate overall totals
                    calculateTotals();

                    // Save to localStorage
                    saveUtilizationToLocalStorage();

                    resolve();
                }).catch(error => {
                    console.error('Error loading deductions from database:', error);
                    // Fallback to DOM-only calculation
                    calculateDeductionsFromDOM();
                    // Save to localStorage even in fallback case
                    saveUtilizationToLocalStorage();
                    resolve();
                });
            });
        }

        // Helper function to calculate deductions from DOM only
        function calculateDeductionsFromDOM() {
            const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');

            mainTableRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const deductionInput = document.getElementById(`deduction_${entryId}`);

                if (deductionInput) {
                    // Calculate total PR amount for this category
                    let totalPRAmount = 0;
                    const allPRRows = document.querySelectorAll('[id^="prRow_"]');

                    allPRRows.forEach(prRow => {
                        const prEntryId = prRow.id.split('_')[1];
                        const prAmountInput = document.getElementById(`prAmount_${prEntryId}`);
                        const prDeductFromSelect = document.getElementById(`prDeductFrom_${prEntryId}`);

                        if (prAmountInput && prDeductFromSelect && prDeductFromSelect.value === entryId) {
                            const prAmount = parseAmount(prAmountInput.value || '0');
                            totalPRAmount += prAmount;
                        }
                    });

                    // Calculate total Travels amount for this category
                    let totalTravelsAmount = 0;
                    const allTravelRows = document.querySelectorAll('[id^="travelRow_"]');

                    allTravelRows.forEach(travelRow => {
                        const travelEntryId = travelRow.id.split('_')[1];
                        const travelAmountInput = document.getElementById(`travelAmount_${travelEntryId}`);
                        const travelDeductFromSelect = document.getElementById(`travelDeductFrom_${travelEntryId}`);

                        if (travelAmountInput && travelDeductFromSelect && travelDeductFromSelect.value === entryId) {
                            const travelAmount = parseAmount(travelAmountInput.value || '0');
                            totalTravelsAmount += travelAmount;
                        }
                    });

                    // Calculate total amount deductions for this category from localStorage (legacy - for backward compatibility)
                    let totalAmountDeductions = 0;
                    const departmentSelect = document.getElementById('departmentSelect');
                    const officeSelect = document.getElementById('officeSelect');
                    const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

                    if (departmentId) {
                        // Check all entries for amount deductions that deduct from this entry (entry-specific mode)
                        mainTableRows.forEach(row => {
                            const sourceEntryId = row.id.split('_')[1];
                            const storageKey = getAmountDeductionsKey(departmentId, sourceEntryId);
                            const savedEntries = localStorage.getItem(storageKey);

                            if (savedEntries) {
                                try {
                                    const entries = JSON.parse(savedEntries);
                                    entries.forEach(entry => {
                                        if (entry.deductedFromEntryId === entryId && entry.amount > 0) {
                                            totalAmountDeductions += parseFloat(entry.amount || 0);
                                        }
                                    });
                                } catch (e) {
                                    console.error('Error loading amount deductions from localStorage:', e);
                                }
                            }
                        });

                        // Also check Honoraria entries from DOM (honoraria mode, not entry-specific)
                        if (!currentAmountDeductionEntryId) {
                            const honorariaRows = document.querySelectorAll('[id^="amountDeductionRow_"]');
                            // Honoraria entries no longer deduct from categories
                        }
                    }

                    // Total deductions = PR + Travels + Amount Deductions
                    const totalDeductions = totalPRAmount + totalTravelsAmount + totalAmountDeductions;

                    // Update deduction input
                    if (totalDeductions > 0) {
                        deductionInput.value = formatNumber(totalDeductions);
                    } else {
                        deductionInput.value = '';
                    }

                    // Recalculate row total
                    calculateRowTotal(entryId);
                }
            });

            // Recalculate overall totals
            calculateTotals();

            // Save to localStorage
            saveUtilizationToLocalStorage();
        }

        // Function to auto-save a single purchase request entry to database
        function autoSavePurchaseRequestEntry(entryId) {
            // Don't auto-save if modal is being closed
            if (window.isClosingPurchaseRequestModal) {
                console.log('Skipping auto-save - modal is closing');
                return;
            }
            
            // Don't auto-save while a full save is in progress (prevents race condition duplicates)
            if (window.isSavingPurchaseRequest) {
                console.log('Skipping auto-save - full save in progress');
                return;
            }
            
            // Don't auto-save during bulk PPMP item addition
            if (window.isBulkAddingPPMPItems) {
                console.log('Skipping auto-save - bulk adding PPMP items');
                return;
            }

            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) return;

            const row = document.getElementById(`prRow_${entryId}`);
            if (!row) return;

            // Get the database ID if this entry was already saved
            const prId = row.getAttribute('data-pr-id');
            
            // Get PPMP references if this entry is from PPMP
            const ppmpItemId = row.getAttribute('data-ppmp-item-id');
            const ppmpId = row.getAttribute('data-ppmp-id');

            // Get all field values
            const purchaseRequestInput = document.getElementById(`prPurchaseRequest_${entryId}`);
            const particularsInput = document.getElementById(`prParticulars_${entryId}`);
            const prNumberInput = document.getElementById(`prNumber_${entryId}`);
            const dateInput = document.getElementById(`prDate_${entryId}`);
            const amountInput = document.getElementById(`prAmount_${entryId}`);
            const deductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);

            const purchaseRequest = purchaseRequestInput ? purchaseRequestInput.value : '';
            let particulars = '';
            if (particularsInput) {
                particulars = particularsInput.title || particularsInput.value || '';
            }
            let prNumber = '';
            if (prNumberInput) {
                // Get full text from title if truncated, otherwise from value
                prNumber = prNumberInput.title || prNumberInput.value || '';
                // Also check data-full-text attribute
                if (!prNumber || prNumber.endsWith('...')) {
                    const fullText = prNumberInput.getAttribute('data-full-text');
                    if (fullText) prNumber = fullText;
                }
            }
            const date = dateInput ? dateInput.value : '';
            const amount = amountInput ? amountInput.value : '';
            const deductFromEntryId = deductFromSelect ? deductFromSelect.value : '';

            // Only save if there's at least some data OR if a category is selected (to persist the selection)
            if (!purchaseRequest && !particulars && !prNumber && !amount && !deductFromEntryId) {
                return;
            }

            // Prepare entry data
            const entryData = {
                purchaseRequest: purchaseRequest,
                particulars: particulars,
                prNumber: prNumber,
                date: date,
                amount: amount,
                deducted_from_entry_id: deductFromEntryId ? parseInt(deductFromEntryId) : null,
                ppmp_item_id: ppmpItemId ? parseInt(ppmpItemId) : null,
                ppmp_id: ppmpId ? parseInt(ppmpId) : null,
                ppmp_description: ppmpItemId ? purchaseRequest : null
            };

            // Save to database
            fetch('../api/save_single_purchase_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    entry: entryData,
                    fiscal_year: CURRENT_FISCAL_YEAR,
                    pr_id: prId || null
                })
            })
                .then(response => {
                    // Check if response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        return response.text().then(text => {
                            console.error('Non-JSON response:', text);
                            throw new Error('Server returned non-JSON response');
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        // Store the pr_id in the row for future updates
                        if (data.pr_id && !prId) {
                            row.setAttribute('data-pr-id', data.pr_id);
                        }

                        // Store deducted_from_entry_id if returned
                        if (data.deducted_from_entry_id) {
                            row.setAttribute('data-deduct-from-entry-id', data.deducted_from_entry_id);
                            // Update dropdown value to match
                            if (deductFromSelect) {
                                deductFromSelect.value = data.deducted_from_entry_id;
                            }
                        }

                        // Update deductions in main table
                        recalculateAllDeductions().then(() => {
                            // Save deductions to localStorage after recalculation
                            saveDeductionsToLocalStorage(departmentId);
                        });

                        // Also save to localStorage as backup
                        savePurchaseRequestToLocalStorage(departmentId);
                    } else {
                        console.error('Error auto-saving purchase request:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error auto-saving purchase request:', error);
                });
        }

        // Function to setup auto-save listeners for PR entry fields
        function setupPurchaseRequestAutoSave(entryId) {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) return;

            // Get all input fields for this entry
            const purchaseRequestInput = document.getElementById(`prPurchaseRequest_${entryId}`);
            const particularsInput = document.getElementById(`prParticulars_${entryId}`);
            const prNumberInput = document.getElementById(`prNumber_${entryId}`);
            const dateInput = document.getElementById(`prDate_${entryId}`);
            const amountInput = document.getElementById(`prAmount_${entryId}`);
            const deductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);

            // Debounce function to avoid too many saves
            let saveTimeout;
            const debouncedAutoSave = () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    autoSavePurchaseRequestEntry(entryId);
                }, 1000); // Wait 1 second after last change
            };

            // Save to localStorage immediately, database after debounce
            const saveToLocalStorage = () => {
                savePurchaseRequestToLocalStorage(departmentId);
            };

            if (purchaseRequestInput) {
                purchaseRequestInput.addEventListener('input', () => {
                    saveToLocalStorage();
                    debouncedAutoSave();
                });
                purchaseRequestInput.addEventListener('blur', () => {
                    autoSavePurchaseRequestEntry(entryId);
                });
            }
            if (particularsInput) {
                particularsInput.addEventListener('input', () => {
                    saveToLocalStorage();
                    debouncedAutoSave();
                });
                particularsInput.addEventListener('blur', () => {
                    autoSavePurchaseRequestEntry(entryId);
                });
            }
            if (prNumberInput) {
                prNumberInput.addEventListener('input', () => {
                    saveToLocalStorage();
                    debouncedAutoSave();
                });
                prNumberInput.addEventListener('blur', () => {
                    autoSavePurchaseRequestEntry(entryId);
                });
            }
            if (dateInput) {
                dateInput.addEventListener('change', () => {
                    saveToLocalStorage();
                    autoSavePurchaseRequestEntry(entryId);
                });
                dateInput.addEventListener('blur', () => {
                    autoSavePurchaseRequestEntry(entryId);
                });
            }
            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    saveToLocalStorage();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Trigger real-time deduction recalculation
                    recalculateAllDeductions().then(() => {
                        window.forceRecalculateDeductions = false;
                    });
                    debouncedAutoSave();
                });
                amountInput.addEventListener('blur', () => {
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Trigger real-time deduction recalculation
                    recalculateAllDeductions().then(() => {
                        window.forceRecalculateDeductions = false;
                    });
                    autoSavePurchaseRequestEntry(entryId);
                });
            }
            if (deductFromSelect) {
                deductFromSelect.addEventListener('change', function () {
                    const selectedEntryId = this.value;
                    const row = document.getElementById(`prRow_${entryId}`);
                    if (row && selectedEntryId) {
                        row.setAttribute('data-deduct-from-entry-id', selectedEntryId);
                    }
                    saveToLocalStorage();
                    // Auto-save immediately when category is selected to persist it
                    autoSavePurchaseRequestEntry(entryId);
                    // Don't recalculate deductions in real-time - they will be calculated when PR is saved
                });
            }
        }

        function setupPurchaseRequestAmountListener(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            let originalValue = '';

            input.addEventListener('focus', function (e) {
                originalValue = e.target.value;
                e.target.value = e.target.value.replace(/[₱,]/g, '');
            });

            input.addEventListener('input', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value === '' || value === '.' || !isNaN(value)) {
                    e.target.value = formatNumberInput(value);
                    calculatePurchaseRequestTotal();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Auto-deduct from selected category
                    autoDeductFromCategory(inputId);
                } else {
                    e.target.value = originalValue.replace(/[₱,]/g, '');
                }
            });

            input.addEventListener('blur', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value !== '' && !isNaN(value)) {
                    e.target.value = formatNumber(parseFloat(value));
                    originalValue = e.target.value;
                    calculatePurchaseRequestTotal();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Auto-deduct from selected category
                    autoDeductFromCategory(inputId);
                } else if (value === '') {
                    e.target.value = '';
                    calculatePurchaseRequestTotal();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Remove deduction if amount is cleared
                    autoDeductFromCategory(inputId);
                }
            });
        }

        // Function to automatically deduct purchase request amount from selected category
        function autoDeductFromCategory(amountInputId) {
            // Get the entry ID from the amount input ID (e.g., prAmount_1 -> 1)
            const entryId = amountInputId.split('_')[1];
            if (!entryId) return;

            // Get the amount and deduct from select
            const amountInput = document.getElementById(amountInputId);
            const deductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);

            if (!amountInput || !deductFromSelect) return;

            const amount = parseAmount(amountInput.value || '0');
            const selectedEntryId = deductFromSelect.value;

            if (!selectedEntryId) {
                // If no category selected, don't deduct
                return;
            }

            // Get the deduction input for the selected category
            const deductionInput = document.getElementById(`deduction_${selectedEntryId}`);
            const budgetAllocatedInput = document.getElementById(`budgetAllocated_${selectedEntryId}`);

            if (!deductionInput || !budgetAllocatedInput) return;

            // Check if the category has budget allocated
            const budgetAllocated = parseAmount(budgetAllocatedInput.value || '0');

            if (budgetAllocated === 0) {
                // Show warning if no budget allocated and amount is being entered
                if (amount > 0) {
                    alert('Warning: This expense category has no budget allocated. Please allocate a budget first before deducting.');
                    // Clear the amount or category selection
                    if (amountInput) amountInput.value = '';
                    if (deductFromSelect) deductFromSelect.value = '';
                }
                return;
            }

            // Set flag to force recalculation from DOM (for real-time updates during editing)
            // Don't recalculate deductions in real-time - they will be calculated when PR is saved to database
            // Deductions are the source of truth from the database, not calculated on the fly
        }

        function calculatePurchaseRequestTotal() {
            let total = 0;
            const rows = document.querySelectorAll('[id^="prRow_"]');

            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const amountEl = document.getElementById(`prAmount_${entryId}`);
                if (amountEl) {
                    total += parseAmount(amountEl.value);
                }
            });

            const totalEl = document.getElementById('purchaseRequestTotal');
            if (totalEl) {
                totalEl.textContent = formatNumber(total);
                // Apply red color if negative
                if (total < 0) {
                    totalEl.classList.remove('text-blue-600');
                    totalEl.classList.add('text-red-600');
                } else {
                    totalEl.classList.remove('text-red-600');
                    totalEl.classList.add('text-blue-600');
                }
            }
        }

        // Particulars Modal Management
        let currentParticularsEntryId = null;

        function openParticularsModal(entryId) {
            currentParticularsEntryId = entryId;
            const particularsInput = document.getElementById(`prParticulars_${entryId}`);
            const modal = document.getElementById('particularsModal');
            const modalContent = document.getElementById('particularsModalContent');
            const textarea = document.getElementById('particularsTextarea');

            if (modal && modalContent && textarea && particularsInput) {
                // Get full text from title attribute if truncated, otherwise from value
                // The title attribute stores the full text when truncated
                let fullText = '';
                if (particularsInput.title && particularsInput.title.trim()) {
                    // If title exists, it contains the full text
                    fullText = particularsInput.title;
                } else {
                    // Otherwise, use the value (which might be truncated)
                    fullText = particularsInput.value;
                    // If value ends with '...', it's truncated, so we need to check data attribute
                    if (fullText.endsWith('...')) {
                        const dataFullText = particularsInput.getAttribute('data-full-text');
                        if (dataFullText) {
                            fullText = dataFullText;
                        }
                    }
                }

                textarea.value = fullText;

                // Show modal with fade-in animation
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Focus on textarea
                setTimeout(() => {
                    textarea.focus();
                }, 100);
            }
        }

        function closeParticularsModal() {
            const modal = document.getElementById('particularsModal');
            const modalContent = document.getElementById('particularsModalContent');

            if (modal && modalContent) {
                // Fade out animation
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);

                currentParticularsEntryId = null;
            }
        }

        // Add Amount Modal Functions
        let currentAddAmountEntryId = null;

        function showAddAmountModal(entryId) {
            currentAddAmountEntryId = entryId;
            
            // Close the deduction source menu
            const menu = document.getElementById(`deductionSourceMenu_${entryId}`);
            if (menu) menu.classList.add('hidden');

            // Get current deduction amount
            const deductionInput = document.getElementById(`deduction_${entryId}`);
            const currentAmount = deductionInput ? parseAmount(deductionInput.value) : 0;

            const modal = document.getElementById('addAmountModal');
            const modalContent = document.getElementById('addAmountModalContent');
            const currentDeductionAmountInput = document.getElementById('currentDeductionAmount');
            const addAmountInput = document.getElementById('addAmountInput');
            const newTotalAmount = document.getElementById('newTotalAmount');

            if (modal && modalContent && currentDeductionAmountInput && addAmountInput && newTotalAmount) {
                // Set current amount
                currentDeductionAmountInput.value = formatNumber(currentAmount);
                
                // RESET Amount to Add to 0
                addAmountInput.value = '0';
                
                // Reset new total to current amount (since we're adding 0)
                newTotalAmount.textContent = formatNumber(currentAmount);

                // Show modal with fade-in animation
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Setup input listener for live calculation with currency formatting
                addAmountInput.oninput = function() {
                    // Remove all non-numeric characters except decimal point
                    let value = this.value.replace(/[₱,\s]/g, '').trim();
                    
                    // Parse the amount - handle empty string as 0
                    const addAmount = value === '' ? 0 : (parseFloat(value) || 0);
                    const newTotal = currentAmount + addAmount;
                    
                    // Update the new total display in real-time
                    newTotalAmount.textContent = formatNumber(newTotal);
                    
                    console.log('Add Amount Input:', value, '-> Parsed:', addAmount, '-> New Total:', newTotal);
                };

                // Format input on blur
                addAmountInput.onblur = function() {
                    const value = parseAmount(this.value);
                    if (value > 0) {
                        this.value = formatNumber(value);
                    } else {
                        this.value = '0';
                    }
                };

                // Clear formatting on focus for easier editing
                addAmountInput.onfocus = function() {
                    const value = parseAmount(this.value);
                    if (value > 0) {
                        this.value = value.toString();
                    } else {
                        this.value = '';
                    }
                };

                // Focus on input and select all for easy editing
                setTimeout(() => {
                    addAmountInput.focus();
                    addAmountInput.select();
                }, 100);
            }
        }

        function closeAddAmountModal() {
            const modal = document.getElementById('addAmountModal');
            const modalContent = document.getElementById('addAmountModalContent');
            const addAmountInput = document.getElementById('addAmountInput');

            if (modal && modalContent) {
                // Fade out animation
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                    // Reset the input field
                    if (addAmountInput) {
                        addAmountInput.value = '0';
                    }
                }, 300);

                currentAddAmountEntryId = null;
            }
        }

        function confirmAddAmount() {
            if (!currentAddAmountEntryId) return;

            const addAmountInput = document.getElementById('addAmountInput');
            const addAmount = parseAmount(addAmountInput.value);

            if (addAmount <= 0) {
                alert('Please enter a valid amount greater than 0.');
                return;
            }

            // Get current deduction amount
            const deductionInput = document.getElementById(`deduction_${currentAddAmountEntryId}`);
            if (!deductionInput) return;

            const currentAmount = parseAmount(deductionInput.value);
            const newAmount = currentAmount + addAmount;

            // Update the deduction input
            deductionInput.value = formatNumber(newAmount);

            // Recalculate row total
            calculateRowTotal(currentAddAmountEntryId);

            // Save the added amount to deduction_sources so it persists
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (departmentId) {
                const deductionSourcesKey = getDeductionSourcesKey(departmentId, currentAddAmountEntryId);
                let deductionSources = [];
                
                // Load existing deduction sources
                const savedSources = localStorage.getItem(deductionSourcesKey);
                if (savedSources) {
                    try {
                        deductionSources = JSON.parse(savedSources);
                    } catch (e) {
                        console.error('Error parsing deduction sources:', e);
                    }
                }
                
                // Find or create manual_add source
                let manualAddSource = deductionSources.find(ds => ds.sourceType === 'manual_add');
                
                if (manualAddSource) {
                    // Update existing manual add amount
                    manualAddSource.amount = (parseFloat(manualAddSource.amount) || 0) + addAmount;
                } else {
                    // Create new manual add source
                    manualAddSource = {
                        sourceType: 'manual_add',
                        categoryEntryId: currentAddAmountEntryId,
                        amount: addAmount,
                        entries: []
                    };
                    deductionSources.push(manualAddSource);
                }
                
                // Save updated deduction sources
                localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                console.log('✓ Saved manual add amount to deduction sources:', addAmount, 'Total manual_add:', manualAddSource.amount);
                
                // Save deductions to localStorage
                saveDeductionsToLocalStorage(departmentId);
            }

            // Refresh the modal with updated values
            const currentDeductionAmountInput = document.getElementById('currentDeductionAmount');
            const newTotalAmount = document.getElementById('newTotalAmount');
            
            if (currentDeductionAmountInput && newTotalAmount) {
                // Update current deduction to show the new amount
                currentDeductionAmountInput.value = formatNumber(newAmount);
                
                // Reset amount to add field to 0
                addAmountInput.value = '0';
                
                // Update new total to match current (since we reset add amount to 0)
                newTotalAmount.textContent = formatNumber(newAmount);
            }

            // Save utilization entries to database
            saveUtilizationToLocalStorage();
            
            // Recalculate all totals
            calculateTotals();
            
            // Show success message briefly
            const button = document.querySelector('[onclick="confirmAddAmount()"]');
            if (button) {
                const originalButtonText = button.textContent;
                button.textContent = '✓ Added!';
                button.classList.add('bg-green-600');
                setTimeout(() => {
                    button.textContent = originalButtonText;
                    button.classList.remove('bg-green-600');
                }, 1500);
            }
        }

        // View Details Modal Functions
        function truncatePurchaseRequestField(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const fullText = input.value.trim();
            if (fullText.length > 30) {
                // Store full text in data attribute for viewing
                input.setAttribute('data-full-text', fullText);
                input.value = fullText.substring(0, 30) + '...';
            } else {
                input.removeAttribute('data-full-text');
            }
        }

        function openViewDetailsModal(fieldName, inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const modal = document.getElementById('viewDetailsModal');
            const modalContent = document.getElementById('viewDetailsModalContent');
            const modalTitle = document.getElementById('viewDetailsModalTitle');
            const modalSubtitle = document.getElementById('viewDetailsModalSubtitle');
            const modalContentDiv = document.getElementById('viewDetailsContent');

            if (!modal || !modalContent || !modalTitle || !modalSubtitle || !modalContentDiv) return;

            // Get the full content - prioritize data-full-text, then title, then value
            let fullContent = '';

            // First check data-full-text attribute (for truncated fields)
            const dataFullText = input.getAttribute('data-full-text');
            if (dataFullText && dataFullText.trim()) {
                fullContent = dataFullText;
            }
            // Then check title attribute (also used for truncated fields)
            else if (input.title && input.title.trim()) {
                fullContent = input.title;
            }
            // Then check value attribute (for readonly fields like Date)
            else if (input.getAttribute('value')) {
                fullContent = input.getAttribute('value');
            }
            // Finally check the value property
            else {
                fullContent = input.value || '';
            }

            // Set modal title and subtitle
            modalTitle.textContent = `View ${fieldName}`;
            modalSubtitle.textContent = `Full content of ${fieldName.toLowerCase()}`;

            // Set the content
            if (fullContent.trim()) {
                modalContentDiv.textContent = fullContent;
                modalContentDiv.classList.remove('text-gray-400', 'italic');
            } else {
                modalContentDiv.textContent = '(No content entered)';
                modalContentDiv.classList.add('text-gray-400', 'italic');
            }

            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Close on background click
            modal.onclick = function (e) {
                if (e.target === modal) {
                    closeViewDetailsModal();
                }
            };
        }

        function closeViewDetailsModal() {
            const modal = document.getElementById('viewDetailsModal');
            const modalContent = document.getElementById('viewDetailsModalContent');
            const modalContentDiv = document.getElementById('viewDetailsContent');

            if (modal && modalContent && modalContentDiv) {
                // Fade out animation
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                    modalContentDiv.classList.remove('text-gray-400', 'italic');
                }, 300);
            }
        }


        function saveParticulars() {
            if (!currentParticularsEntryId) return;

            const textarea = document.getElementById('particularsTextarea');
            const particularsInput = document.getElementById(`prParticulars_${currentParticularsEntryId}`);

            if (textarea && particularsInput) {
                // Update the input field with the textarea value
                // Show first 50 characters in the input, or full text if shorter
                const fullText = textarea.value.trim();
                if (fullText.length > 50) {
                    particularsInput.value = fullText.substring(0, 50) + '...';
                    particularsInput.title = fullText; // Show full text on hover
                    particularsInput.setAttribute('data-full-text', fullText); // Store full text in data attribute
                } else {
                    particularsInput.value = fullText;
                    particularsInput.title = '';
                    particularsInput.removeAttribute('data-full-text');
                }

                // Add visual indicator that it has content
                if (fullText) {
                    particularsInput.classList.add('bg-blue-50', 'border-blue-300');
                    particularsInput.classList.remove('bg-white');
                } else {
                    particularsInput.classList.remove('bg-blue-50', 'border-blue-300');
                    particularsInput.classList.add('bg-white');
                }

                // No need to auto-save - will be saved when user clicks Save button
            }

            closeParticularsModal();
        }

        // Purchase Request Text Modal Management
        let currentPurchaseRequestTextEntryId = null;

        function openPurchaseRequestTextModal(entryId) {
            currentPurchaseRequestTextEntryId = entryId;
            const prInput = document.getElementById(`prPurchaseRequest_${entryId}`);
            const modal = document.getElementById('purchaseRequestTextModal');
            const modalContent = document.getElementById('purchaseRequestTextModalContent');
            const textarea = document.getElementById('purchaseRequestTextarea');

            if (modal && modalContent && textarea && prInput) {
                let fullText = prInput.title || prInput.value || '';
                if (fullText.endsWith('...')) {
                    const dataFullText = prInput.getAttribute('data-full-text');
                    if (dataFullText) fullText = dataFullText;
                }
                textarea.value = fullText;

                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                setTimeout(() => textarea.focus(), 100);
            }
        }

        function closePurchaseRequestTextModal() {
            const modal = document.getElementById('purchaseRequestTextModal');
            const modalContent = document.getElementById('purchaseRequestTextModalContent');

            if (modal && modalContent) {
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        }

        function savePurchaseRequestText() {
            if (!currentPurchaseRequestTextEntryId) return;

            const textarea = document.getElementById('purchaseRequestTextarea');
            const prInput = document.getElementById(`prPurchaseRequest_${currentPurchaseRequestTextEntryId}`);

            if (textarea && prInput) {
                const fullText = textarea.value.trim();
                if (fullText.length > 50) {
                    prInput.value = fullText.substring(0, 50) + '...';
                    prInput.title = fullText;
                    prInput.setAttribute('data-full-text', fullText);
                } else {
                    prInput.value = fullText;
                    prInput.title = '';
                    prInput.removeAttribute('data-full-text');
                }

                if (fullText) {
                    prInput.classList.add('bg-blue-50', 'border-blue-300');
                    prInput.classList.remove('bg-white');
                } else {
                    prInput.classList.remove('bg-blue-50', 'border-blue-300');
                    prInput.classList.add('bg-white');
                }
            }

            closePurchaseRequestTextModal();
        }

        // PR Number Modal Management
        let currentPRNumberEntryId = null;

        function openPRNumberModal(entryId) {
            currentPRNumberEntryId = entryId;
            const prNumberInput = document.getElementById(`prNumber_${entryId}`);
            const modal = document.getElementById('prNumberModal');
            const modalContent = document.getElementById('prNumberModalContent');
            const textarea = document.getElementById('prNumberTextarea');

            if (modal && modalContent && textarea && prNumberInput) {
                let fullText = prNumberInput.title || prNumberInput.value || '';
                if (fullText.endsWith('...')) {
                    const dataFullText = prNumberInput.getAttribute('data-full-text');
                    if (dataFullText) fullText = dataFullText;
                }
                textarea.value = fullText;

                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                setTimeout(() => textarea.focus(), 100);
            }
        }

        function closePRNumberModal() {
            const modal = document.getElementById('prNumberModal');
            const modalContent = document.getElementById('prNumberModalContent');

            if (modal && modalContent) {
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');
                setTimeout(() => modal.classList.add('hidden'), 300);
            }
        }

        function savePRNumber() {
            if (!currentPRNumberEntryId) return;

            const textarea = document.getElementById('prNumberTextarea');
            const prNumberInput = document.getElementById(`prNumber_${currentPRNumberEntryId}`);

            if (textarea && prNumberInput) {
                const fullText = textarea.value.trim();
                if (fullText.length > 50) {
                    prNumberInput.value = fullText.substring(0, 50) + '...';
                    prNumberInput.title = fullText;
                    prNumberInput.setAttribute('data-full-text', fullText);
                } else {
                    prNumberInput.value = fullText;
                    prNumberInput.title = '';
                    prNumberInput.removeAttribute('data-full-text');
                }

                if (fullText) {
                    prNumberInput.classList.add('bg-blue-50', 'border-blue-300');
                    prNumberInput.classList.remove('bg-white');
                } else {
                    prNumberInput.classList.remove('bg-blue-50', 'border-blue-300');
                    prNumberInput.classList.add('bg-white');
                }
                
                // Trigger auto-save after updating the field
                autoSavePurchaseRequestEntry(currentPRNumberEntryId);
            }

            closePRNumberModal();
        }

        function savePurchaseRequest() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            const entries = [];
            const rows = document.querySelectorAll('[id^="prRow_"]');

            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const purchaseRequest = document.getElementById(`prPurchaseRequest_${entryId}`).value;
                const particularsInput = document.getElementById(`prParticulars_${entryId}`);
                // Get full text from title attribute if truncated, otherwise from value
                let particulars = '';
                if (particularsInput) {
                    particulars = particularsInput.title || particularsInput.value;
                }
                const prNumberInput = document.getElementById(`prNumber_${entryId}`);
                let prNumber = '';
                if (prNumberInput) {
                    // Get full text from title or data attribute if truncated
                    prNumber = prNumberInput.title || prNumberInput.getAttribute('data-full-text') || prNumberInput.value;
                }
                const date = document.getElementById(`prDate_${entryId}`).value;
                const amountInput = document.getElementById(`prAmount_${entryId}`);
                const amount = amountInput ? amountInput.value : '';
                const deductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);
                const deductFromEntryId = deductFromSelect ? deductFromSelect.value : '';
                
                // Get PPMP references from row attributes
                const ppmpItemId = row.getAttribute('data-ppmp-item-id');
                const ppmpId = row.getAttribute('data-ppmp-id');

                // Include entry if it has purchase_request (required) - particulars and prNumber can be NULL
                if (purchaseRequest) {
                    entries.push({
                        purchaseRequest: purchaseRequest,
                        particulars: particulars || null,  // Allow NULL
                        prNumber: prNumber || null,  // Allow NULL
                        date: date,
                        amount: amount,
                        entry_id: deductFromEntryId,  // Use entry_id to match database column
                        ppmpItemId: ppmpItemId ? parseInt(ppmpItemId) : null,
                        ppmpId: ppmpId ? parseInt(ppmpId) : null
                    });
                }
            });

            if (entries.length === 0) {
                alert('Please add at least one entry before saving.');
                return;
            }

            // Prepare entries for database
            const dbEntries = entries.map(entry => ({
                purchaseRequest: entry.purchaseRequest,
                particulars: entry.particulars,
                prNumber: entry.prNumber,
                date: entry.date,
                amount: entry.amount,
                entry_id: entry.deductFromEntryId ? parseInt(entry.deductFromEntryId) : null,
                ppmp_item_id: entry.ppmpItemId,
                ppmp_id: entry.ppmpId,
                ppmp_description: entry.purchaseRequest  // Use purchase_request as description for PPMP items
            }));

            // Save to database - server will handle deductions
            window.isSavingPurchaseRequest = true;
            fetch('../api/save_purchase_request.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    entries: dbEntries,
                    fiscal_year: CURRENT_FISCAL_YEAR
                })
            })
                .then(response => response.json())
                .then(data => {
                    window.isSavingPurchaseRequest = false;
                    if (data.success) {
                        // Clear localStorage after successful save
                        clearPurchaseRequestLocalStorage(departmentId);

                        // Reload PR entries from DB to get correct data-pr-id values on all rows.
                        // This prevents pending autoSavePurchaseRequestEntry calls (from savePRNumber/
                        // saveParticulars) from creating duplicate INSERT records because the rows
                        // now have stale/missing data-pr-id after the DELETE+INSERT in save_purchase_request.php.
                        loadPurchaseRequestEntries(departmentId).then(() => {
                            recalculateAllDeductions().then(() => {
                                syncDeductionSelectionsFromSources(departmentId);
                                calculateTotals();
                                console.log('✓ Purchase Request saved successfully');
                            });
                        });
                    } else {
                        alert('Error saving purchase request: ' + data.message);
                    }
                })
                .catch(error => {
                    window.isSavingPurchaseRequest = false;
                    console.error('Error:', error);
                    alert('Error saving purchase request. Please try again.');
                });
        }

        // Save purchase request entries to localStorage (separate for each department)
        function savePurchaseRequestToLocalStorage(departmentId) {
            // Don't save if modal is being closed
            if (window.isClosingPurchaseRequestModal) return;

            if (!departmentId) return;

            const entries = [];
            const rows = document.querySelectorAll('[id^="prRow_"]');

            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const purchaseRequestInput = document.getElementById(`prPurchaseRequest_${entryId}`);
                const particularsInput = document.getElementById(`prParticulars_${entryId}`);
                const prNumberInput = document.getElementById(`prNumber_${entryId}`);
                const dateInput = document.getElementById(`prDate_${entryId}`);
                const amountInput = document.getElementById(`prAmount_${entryId}`);
                const deductFromSelect = document.getElementById(`prDeductFrom_${entryId}`);

                const purchaseRequest = purchaseRequestInput ? purchaseRequestInput.value : '';
                let particulars = '';
                if (particularsInput) {
                    // Get full text from title attribute if truncated, otherwise from value
                    particulars = particularsInput.title || particularsInput.value || '';
                }
                const prNumber = prNumberInput ? prNumberInput.value : '';
                const date = dateInput ? dateInput.value : '';
                const amount = amountInput ? amountInput.value : '';
                const deductFromEntryId = deductFromSelect ? deductFromSelect.value : '';

                if (purchaseRequest || particulars || prNumber || amount) {
                    entries.push({
                        purchaseRequest: purchaseRequest,
                        particulars: particulars,
                        prNumber: prNumber,
                        date: date,
                        amount: amount,
                        deductFromEntryId: deductFromEntryId
                    });
                }
            });

            // Save to localStorage with department-specific key AND fiscal year
            // This ensures each fiscal year has separate Purchase Request entries
            const storageKey = `pr_entries_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
            localStorage.setItem(storageKey, JSON.stringify(entries));

            // Also save deductions to localStorage after PR entries are saved
            setTimeout(() => {
                saveDeductionsToLocalStorage(departmentId);
            }, 100);
        }

        // Load purchase request entries from localStorage (separate for each department AND fiscal year)
        function loadPurchaseRequestFromLocalStorage(departmentId) {
            if (!departmentId) return;

            // Use fiscal year in storage key to ensure each year has separate entries
            const storageKey = `pr_entries_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
            const savedEntries = localStorage.getItem(storageKey);

            if (!savedEntries) return;

            try {
                const entries = JSON.parse(savedEntries);

                // Only load if there are no existing rows (to avoid duplicates with database entries)
                const existingRows = document.querySelectorAll('[id^="prRow_"]');
                if (existingRows.length > 0) {
                    // Merge localStorage entries with existing ones (only add if not already present)
                    return;
                }

                // Restore each entry
                entries.forEach(entry => {
                    purchaseRequestCounter++;
                    const tbody = document.getElementById('purchaseRequestTableBody');
                    if (!tbody) return;

                    const row = document.createElement('tr');
                    row.id = `prRow_${purchaseRequestCounter}`;
                    row.className = 'hover:bg-gray-50 transition-colors';

                    // Get timestamp (use saved date or current time)
                    const timestamp = entry.date || new Date().toLocaleString('en-US', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit',
                        hour12: true
                    });

                    // Format particulars display (truncate if needed)
                    let particularsDisplay = entry.particulars || '';
                    let particularsTitle = '';
                    if (particularsDisplay.length > 50) {
                        particularsTitle = particularsDisplay;
                        particularsDisplay = particularsDisplay.substring(0, 50) + '...';
                    }

                    row.innerHTML = `
                <td class="border-b border-gray-200 py-4 px-6">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="prPurchaseRequest_${purchaseRequestCounter}" 
                            class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                            placeholder="Enter purchase request"
                            value="${(entry.purchaseRequest || '').replace(/"/g, '&quot;')}"
                            onblur="truncatePurchaseRequestField('prPurchaseRequest_${purchaseRequestCounter}')"
                        >
                        <button 
                            onclick="openViewDetailsModal('Purchase Request', 'prPurchaseRequest_${purchaseRequestCounter}')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500 hover:text-blue-700 transition-colors p-1"
                            title="View full content"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="border-b border-gray-200 py-4 px-6">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="prParticulars_${purchaseRequestCounter}" 
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                            placeholder="Click to enter particulars/reason..."
                            value="${particularsDisplay.replace(/"/g, '&quot;')}"
                            title="${particularsTitle.replace(/"/g, '&quot;')}"
                            readonly
                            onclick="openParticularsModal(${purchaseRequestCounter})"
                        >
                        <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                            </svg>
                        </div>
                    </div>
                </td>
                <td class="border-b border-gray-200 py-4 px-6">
                    <div class="relative">
                        <input 
                            type="text" 
                            id="prNumber_${purchaseRequestCounter}" 
                            class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                            placeholder="Enter PR/PO number"
                            value="${(entry.prNumber || '').replace(/"/g, '&quot;')}"
                            onblur="truncatePurchaseRequestField('prNumber_${purchaseRequestCounter}')"
                        >
                        <button 
                            onclick="openViewDetailsModal('PR No. / PO No.', 'prNumber_${purchaseRequestCounter}')"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500 hover:text-blue-700 transition-colors p-1"
                            title="View full content"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                        </button>
                    </div>
                </td>
                <td class="border-b border-gray-200 py-4 px-6">
                    <input 
                        type="date" 
                        id="prDate_${purchaseRequestCounter}" 
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                        value="${entry.date || new Date().toISOString().split('T')[0]}"
                    >
                </td>
                <td class="border-b border-gray-200 py-4 px-6">
                    <input 
                        type="text" 
                        id="prAmount_${purchaseRequestCounter}" 
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                        placeholder="0.00"
                        value="${(entry.amount || '').replace(/"/g, '&quot;')}"
                    >
                </td>
                <td class="border-b border-gray-200 py-4 px-6">
                    <select 
                        id="prDeductFrom_${purchaseRequestCounter}" 
                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                    >
                        <option value="">-- Select Expense Category --</option>
                    </select>
                </td>
                <td class="border-b border-gray-200 py-4 px-6 text-center">
                    <button 
                        onclick="removePurchaseRequestEntry(${purchaseRequestCounter})" 
                        class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                        title="Remove entry"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                </td>
            `;

                    tbody.appendChild(row);

                    // Setup amount input listener
                    setupPurchaseRequestAmountListener(`prAmount_${purchaseRequestCounter}`);

                    // NOTE: populateDeductFromDropdown removed - using new Select Source system instead
                    // Old deduction dropdown is kept for legacy compatibility but not actively populated

                    // Set the selected value if it exists (legacy support)
                    if (entry.deductFromEntryId) {
                        const deductFromSelect = document.getElementById(`prDeductFrom_${purchaseRequestCounter}`);
                        if (deductFromSelect) {
                            deductFromSelect.value = entry.deductFromEntryId;
                        }
                    }

                    // Setup listener for "Deduct From" dropdown change
                    const deductFromSelect = document.getElementById(`prDeductFrom_${purchaseRequestCounter}`);
                    if (deductFromSelect) {
                        deductFromSelect.addEventListener('change', function () {
                            const selectedEntryId = this.value;
                            const amountInput = document.getElementById(`prAmount_${purchaseRequestCounter}`);
                            const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;

                            if (selectedEntryId && amount > 0) {
                                const budgetAllocatedInput = document.getElementById(`budgetAllocated_${selectedEntryId}`);
                                if (budgetAllocatedInput) {
                                    const budgetAllocated = parseAmount(budgetAllocatedInput.value || '0');
                                    if (budgetAllocated === 0) {
                                        alert('Warning: This expense category has no budget allocated. Please allocate a budget first before deducting.');
                                        this.value = '';
                                        return;
                                    }
                                }
                            }

                            recalculateAllDeductions();
                        });
                    }

                    // Setup auto-save listeners
                    setupPurchaseRequestAutoSave(purchaseRequestCounter);
                });

                // Recalculate total
                calculatePurchaseRequestTotal();
            } catch (error) {
                console.error('Error loading purchase requests from localStorage:', error);
            }
        }

        // Clear purchase request entries from localStorage for a specific department AND fiscal year
        function clearPurchaseRequestLocalStorage(departmentId) {
            if (!departmentId) return;
            // Use fiscal year in storage key to ensure we only clear the current year's entries
            const storageKey = `pr_entries_${departmentId}_year_${CURRENT_FISCAL_YEAR}`;
            localStorage.removeItem(storageKey);
        }

        // Function to load PR entries from database
        function loadPurchaseRequestEntries(departmentId) {
            const tbody = document.getElementById('purchaseRequestTableBody');
            if (!tbody) return Promise.resolve();

            // Clear existing entries
            tbody.innerHTML = '';
            purchaseRequestCounter = 0;

            return fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.entries && data.entries.length > 0) {
                        data.entries.forEach(entry => {
                            purchaseRequestCounter++;
                            const row = document.createElement('tr');
                            row.id = `prRow_${purchaseRequestCounter}`;
                            
                            // Check if this is a PPMP-based entry
                            const isFromPPMP = entry.ppmp_item_id && entry.ppmp_id;
                            row.className = isFromPPMP ? 'hover:bg-gray-50 transition-colors bg-yellow-50' : 'hover:bg-gray-50 transition-colors';

                            // Store the database ID for updates/deletes
                            if (entry.id) {
                                row.setAttribute('data-pr-id', entry.id);
                            }
                            
                            // Store PPMP references if this is from PPMP
                            if (entry.ppmp_item_id) {
                                row.setAttribute('data-ppmp-item-id', entry.ppmp_item_id);
                            }
                            if (entry.ppmp_id) {
                                row.setAttribute('data-ppmp-id', entry.ppmp_id);
                            }

                            // Store deducted_from_entry_id if it exists (for deletion to remove deduction)
                            if (entry.deducted_from_entry_id) {
                                row.setAttribute('data-deduct-from-entry-id', entry.deducted_from_entry_id);
                            }

                            // Format date
                            const date = entry.date || '';

                            // Format particulars display (truncate if needed)
                            let particularsDisplay = entry.particulars || '';
                            let particularsTitle = '';
                            if (particularsDisplay.length > 50) {
                                particularsTitle = particularsDisplay;
                                particularsDisplay = particularsDisplay.substring(0, 50) + '...';
                            }

                            // Combine PR and PO numbers
                            let prPoNumber = '';
                            if (entry.pr_number) prPoNumber = entry.pr_number;
                            if (entry.po_number) prPoNumber += (prPoNumber ? ' / ' : '') + entry.po_number;
                            
                            // Determine input styling based on PPMP origin
                            const prInputClass = isFromPPMP 
                                ? 'w-full px-4 py-2.5 border-2 border-yellow-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-purple-500 transition-all bg-yellow-50 text-gray-900 font-medium'
                                : 'w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium';
                            
                            const amountInputClass = isFromPPMP
                                ? 'w-full px-4 py-2.5 border-2 border-yellow-300 rounded-lg text-right focus:ring-2 focus:ring-yellow-500 focus:border-purple-500 transition-all bg-yellow-50 text-gray-900 font-medium'
                                : 'w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium';

                            row.innerHTML = `
                        <td class="border-b border-gray-200 py-4 px-6">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="prPurchaseRequest_${purchaseRequestCounter}"
                                    class="${prInputClass}"
                                    value="${(entry.purchase_request || '').replace(/"/g, '&quot;')}"
                                    ${isFromPPMP ? 'readonly' : ''}
                                >
                                ${isFromPPMP ? `
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-yellow-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                ` : ''}
                            </div>
                            ${isFromPPMP ? '<div class="mt-1 text-xs text-yellow-600 font-medium">From PPMP</div>' : ''}
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="prParticulars_${purchaseRequestCounter}"
                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer"
                                    value="${particularsDisplay.replace(/"/g, '&quot;')}"
                                    title="${particularsTitle.replace(/"/g, '&quot;')}"
                                    onclick="openParticularsModal(${purchaseRequestCounter})"
                                    readonly
                                >
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </div>
                            </div>
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <div class="relative">
                                <input 
                                    type="text" 
                                    id="prNumber_${purchaseRequestCounter}"
                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer"
                                    value="${prPoNumber.replace(/"/g, '&quot;')}"
                                    placeholder="Click to enter PR/PO number..."
                                    readonly
                                    onclick="openPRNumberModal(${purchaseRequestCounter})"
                                >
                                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                    </svg>
                                </div>
                            </div>
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="date" 
                                id="prDate_${purchaseRequestCounter}"
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                                value="${date}"
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="text" 
                                id="prAmount_${purchaseRequestCounter}"
                                class="${amountInputClass}"
                                value="${formatNumber(parseFloat(entry.amount || 0))}"
                                placeholder="0.00"
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6 text-center">
                            <button
                                onclick="removePurchaseRequestEntry(${purchaseRequestCounter})"
                                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                                title="Remove entry"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    `;
                            tbody.appendChild(row);

                            // NOTE: populateDeductFromDropdown removed - using new Select Source system instead
                            // Old deduction dropdown is kept for legacy compatibility but not actively populated
                            
                            // Legacy support: Store deducted_from_entry_id if it exists
                            if (entry.deducted_from_entry_id) {
                                row.setAttribute('data-deduct-from-entry-id', entry.deducted_from_entry_id);
                            }

                            // Setup amount input listener
                            setupPurchaseRequestAmountListener(`prAmount_${purchaseRequestCounter}`);

                            // Setup auto-save listeners
                            setupPurchaseRequestAutoSave(purchaseRequestCounter);
                        });

                        // Recalculate total
                        calculatePurchaseRequestTotal();

                        // IMPORTANT: After loading all PR entries, recalculate deductions to ensure they persist
                        // This ensures deductions are displayed in the main table even after refresh
                        setTimeout(() => {
                            recalculateAllDeductions().then(() => {
                                // Update all row totals to reflect the deductions
                                const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                                mainTableRows.forEach(row => {
                                    const entryId = row.id.split('_')[1];
                                    calculateRowTotal(entryId);
                                });
                                // Recalculate overall totals
                                calculateTotals();
                                console.log('✓ Deductions recalculated after loading purchase requests');
                            });
                        }, 500); // Wait a bit for all dropdowns to be populated
                    } else {
                        // Even if no PR entries, recalculate deductions (in case entries were deleted)
                        setTimeout(() => {
                            recalculateAllDeductions().then(() => {
                                const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
                                mainTableRows.forEach(row => {
                                    const entryId = row.id.split('_')[1];
                                    calculateRowTotal(entryId);
                                });
                                calculateTotals();
                            });
                        }, 500);
                    }
                })
                .catch(error => {
                    console.error('Error loading purchase requests:', error);
                });
        }

        // OLD FUNCTION - REMOVED (keeping for reference)
        function loadPurchaseRequestEntries_OLD(departmentId) {
            const storageKey = `pr_entries_${departmentId}`;
            const savedEntries = localStorage.getItem(storageKey);

            const tbody = document.getElementById('purchaseRequestTableBody');
            if (!tbody) return;

            // Clear existing entries
            tbody.innerHTML = '';
            purchaseRequestCounter = 0;

            if (savedEntries) {
                try {
                    const entries = JSON.parse(savedEntries);

                    // Restore each entry
                    entries.forEach(entry => {
                        purchaseRequestCounter++;
                        const row = document.createElement('tr');
                        row.id = `prRow_${purchaseRequestCounter}`;
                        row.className = 'hover:bg-gray-50 transition-colors';

                        // Get timestamp (use saved date or current time)
                        const timestamp = entry.date || new Date().toLocaleString('en-US', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit',
                            hour12: true
                        });

                        // Format particulars display (truncate if needed)
                        let particularsDisplay = entry.particulars || '';
                        let particularsTitle = '';
                        if (particularsDisplay.length > 50) {
                            particularsTitle = particularsDisplay;
                            particularsDisplay = particularsDisplay.substring(0, 50) + '...';
                        }

                        row.innerHTML = `
                    <td class="border-b border-gray-200 py-4 px-6">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="prPurchaseRequest_${purchaseRequestCounter}" 
                                class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                                placeholder="Enter purchase request"
                                value="${(entry.purchaseRequest || '').replace(/"/g, '&quot;')}"
                                onblur="truncatePurchaseRequestField('prPurchaseRequest_${purchaseRequestCounter}')"
                            >
                            <button 
                                onclick="openViewDetailsModal('Purchase Request', 'prPurchaseRequest_${purchaseRequestCounter}')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500 hover:text-blue-700 transition-colors p-1"
                                title="View full content"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </button>
            </div>
                    </td>
                    <td class="border-b border-gray-200 py-4 px-6">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="prParticulars_${purchaseRequestCounter}" 
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                                placeholder="Click to enter particulars/reason..."
                                value="${particularsDisplay.replace(/"/g, '&quot;')}"
                                title="${particularsTitle.replace(/"/g, '&quot;')}"
                                readonly
                                onclick="openParticularsModal(${purchaseRequestCounter})"
                            >
                            <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                                </svg>
                </div>
            </div>
                    </td>
                    <td class="border-b border-gray-200 py-4 px-6">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="prNumber_${purchaseRequestCounter}" 
                                class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                                placeholder="Enter PR/PO number"
                                value="${(entry.prNumber || '').replace(/"/g, '&quot;')}"
                                onblur="truncatePurchaseRequestField('prNumber_${purchaseRequestCounter}')"
                            >
                            <button 
                                onclick="openViewDetailsModal('PR No. / PO No.', 'prNumber_${purchaseRequestCounter}')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500 hover:text-blue-700 transition-colors p-1"
                                title="View full content"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                </button>
                        </div>
                    </td>
                    <td class="border-b border-gray-200 py-4 px-6">
                        <div class="relative">
                            <input 
                                type="text" 
                                id="prDate_${purchaseRequestCounter}" 
                                class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-gray-50 text-gray-700 font-medium cursor-pointer" 
                                value="${timestamp.replace(/"/g, '&quot;')}"
                                readonly
                                onclick="openViewDetailsModal('Date', 'prDate_${purchaseRequestCounter}')"
                            >
                            <button 
                                onclick="openViewDetailsModal('Date', 'prDate_${purchaseRequestCounter}')"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-blue-500 hover:text-blue-700 transition-colors p-1"
                                title="View full content"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                </button>
            </div>
                    </td>
                    <td class="border-b border-gray-200 py-4 px-6">
                        <input 
                            type="text" 
                            id="prAmount_${purchaseRequestCounter}" 
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium" 
                            placeholder="0.00"
                            value="${entry.amount || ''}"
                        >
                    </td>
                    <td class="border-b border-gray-200 py-4 px-6">
                        <select 
                            id="prDeductFrom_${purchaseRequestCounter}" 
                            class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all bg-white text-gray-900 font-medium"
                        >
                            <option value="">-- Select Expense Category --</option>
                        </select>
                    </td>
                    <td class="border-b border-gray-200 py-4 px-6 text-center">
                        <button 
                            onclick="removePurchaseRequestEntry(${purchaseRequestCounter})" 
                            class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                            title="Remove entry"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </td>
                `;

                        tbody.appendChild(row);

                        // Setup amount input listener
                        setupPurchaseRequestAmountListener(`prAmount_${purchaseRequestCounter}`);

                        // NOTE: populateDeductFromDropdown removed - using new Select Source system instead
                        // Old deduction dropdown is kept for legacy compatibility but not actively populated

                        // Legacy support: Store deducted_from_entry_id if it exists
                        if (entry.deductFromEntryId) {
                            row.setAttribute('data-deduct-from-entry-id', entry.deductFromEntryId);
                        }

                        // Setup auto-save listeners
                        setupPurchaseRequestAutoSave(purchaseRequestCounter);
                    });

                    calculatePurchaseRequestTotal();
                } catch (e) {
                    console.error('Error loading PR entries:', e);
                }
            } else {
                // No saved entries, just calculate total
                calculatePurchaseRequestTotal();
            }
        }

        // Travels Modal Management
        let travelsCounter = 0;
        let amountDeductionCounter = 0;
        let currentAmountDeductionEntryId = null;

        function handleHonoraria() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            // Show modal - use the existing amount deduction modal
            // Clear current entry ID so modal works for general honoraria management
            currentAmountDeductionEntryId = null;

            const modal = document.getElementById('amountDeductionModal');
            if (modal) {
                modal.classList.remove('hidden');

                // Clear the table first
                const tbody = document.getElementById('amountDeductionTableBody');
                if (tbody) {
                    tbody.innerHTML = '';
                }
                amountDeductionCounter = 0;

                // Load honoraria entries from database
                loadHonorariaEntries(departmentId);

                calculateAmountDeductionTotal();

                // Note: User can add entries using the "Add Entry" button
                // Each entry will need to specify which expense category to deduct from
            }
        }

        function handleTravels() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            // Show modal
            const modal = document.getElementById('travelsModal');
            if (modal) {
                modal.classList.remove('hidden');

                // Load saved travels from database and localStorage
                loadTravelsEntries(departmentId);

                // Also load from localStorage and merge (only after database load completes)
                setTimeout(() => {
                    const localTravels = loadTravelsFromLocalStorage(departmentId);
                    if (localTravels.length > 0) {
                        // Get all currently loaded database entry IDs
                        const existingRows = document.querySelectorAll('[id^="travelRow_"]');
                        const loadedDatabaseIds = new Set();
                        existingRows.forEach(row => {
                            const travelId = row.getAttribute('data-travel-id');
                            if (travelId) {
                                loadedDatabaseIds.add(parseInt(travelId));
                            }
                        });

                        // Merge with database entries
                        localTravels.forEach(localEntry => {
                            // Check if entry already exists in database (by ID)
                            let exists = false;
                            if (localEntry.travelId) {
                                exists = loadedDatabaseIds.has(parseInt(localEntry.travelId));
                            }

                            // Also check if entry exists in table by comparing key fields
                            if (!exists) {
                                existingRows.forEach(row => {
                                    const rowId = row.id.split('_')[1];
                                    const travelled = document.getElementById(`travelTravelled_${rowId}`)?.value || '';
                                    const eventInput = document.getElementById(`travelEvent_${rowId}`);
                                    const event = eventInput ? (eventInput.getAttribute('data-full-text') || eventInput.title || eventInput.value || '') : '';
                                    const dateInput = document.getElementById(`travelDate_${rowId}`);
                                    const date = dateInput ? dateInput.value : '';

                                    // More comprehensive duplicate check
                                    if (travelled === (localEntry.travelled || '') &&
                                        event === (localEntry.event || '') &&
                                        date === (localEntry.date || '')) {
                                        exists = true;
                                    }
                                });
                            }

                            if (!exists) {
                                // Add entry from localStorage
                                travelsCounter++;
                                const tbody = document.getElementById('travelsTableBody');
                                if (tbody) {
                                    const row = document.createElement('tr');
                                    row.id = `travelRow_${travelsCounter}`;
                                    row.className = 'hover:bg-gray-50 transition-colors';
                                    if (localEntry.travelId) {
                                        row.setAttribute('data-travel-id', localEntry.travelId);
                                    }

                                    const now = new Date();
                                    const timestamp = localEntry.date || now.toLocaleString('en-US', {
                                        year: 'numeric',
                                        month: '2-digit',
                                        day: '2-digit',
                                        hour: '2-digit',
                                        minute: '2-digit',
                                        second: '2-digit',
                                        hour12: true
                                    });

                                    let eventDisplay = localEntry.event || '';
                                    let eventTitle = '';
                                    if (eventDisplay.length > 50) {
                                        eventTitle = eventDisplay;
                                        eventDisplay = eventDisplay.substring(0, 50) + '...';
                                    }

                                    row.innerHTML = `
                                <td class="border-b border-gray-200 py-4 px-6">
                                    <input 
                                        type="text" 
                                        id="travelTravelled_${travelsCounter}"
                                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                        value="${(localEntry.travelled || '').replace(/"/g, '&quot;')}"
                                    >
                                </td>
                                <td class="border-b border-gray-200 py-4 px-6">
                                    <input 
                                        type="text" 
                                        id="travelEvent_${travelsCounter}"
                                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium cursor-pointer"
                                        value="${eventDisplay.replace(/"/g, '&quot;')}"
                                        title="${eventTitle.replace(/"/g, '&quot;')}"
                                        onclick="openTravelEventModal(${travelsCounter})"
                                        readonly
                                    >
                                </td>
                                <td class="border-b border-gray-200 py-4 px-6">
                                    <input 
                                        type="date" 
                                        id="travelDate_${travelsCounter}"
                                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                        value="${localEntry.date || ''}"
                                    >
                                </td>
                                <td class="border-b border-gray-200 py-4 px-6">
                                    <input 
                                        type="text" 
                                        id="travelAmount_${travelsCounter}"
                                        class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                        value="${localEntry.amount ? formatNumber(parseAmount(localEntry.amount)) : '₱0.00'}"
                                        placeholder="₱0.00"
                                    >
                                </td>
                                <td class="border-b border-gray-200 py-4 px-6 text-center">
                                    <button
                                        onclick="removeTravelsEntry(${travelsCounter})"
                                        class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                                        title="Remove entry"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </td>
                            `;
                                    tbody.appendChild(row);

                                    // Setup amount input listener
                                    setupTravelsAmountListener(`travelAmount_${travelsCounter}`);

                                    // Setup auto-save for this entry
                                    setupTravelsAutoSave(travelsCounter);
                                }
                            }
                        });
                        calculateTravelsTotal();
                    }
                }, 500);
            }
        }

        // Function to load Travels entries from database
        function loadTravelsEntries(departmentId) {
            const tbody = document.getElementById('travelsTableBody');
            if (!tbody) return;

            // Clear existing entries
            tbody.innerHTML = '';
            travelsCounter = 0;

            // Store loaded database entry IDs to prevent duplicates
            const loadedDatabaseIds = new Set();

            // Load from database first
            fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.entries && data.entries.length > 0) {
                        data.entries.forEach(entry => {
                            // Store database ID to prevent duplicates
                            if (entry.id) {
                                loadedDatabaseIds.add(entry.id);
                            }
                            travelsCounter++;
                            const row = document.createElement('tr');
                            row.id = `travelRow_${travelsCounter}`;
                            row.className = 'hover:bg-gray-50 transition-colors';

                            // Store the database ID for updates/deletes
                            if (entry.id) {
                                row.setAttribute('data-travel-id', entry.id);
                            }

                            // Store deducted_from_entry_id if it exists (for deletion to remove deduction)
                            if (entry.deducted_from_entry_id) {
                                row.setAttribute('data-deduct-from-entry-id', entry.deducted_from_entry_id);
                            }

                            // Format date
                            const date = entry.date || '';

                            // Format event display (truncate if needed)
                            let eventDisplay = entry.event_activity || '';
                            let eventTitle = '';
                            if (eventDisplay.length > 50) {
                                eventTitle = eventDisplay;
                                eventDisplay = eventDisplay.substring(0, 50) + '...';
                            }

                            row.innerHTML = `
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="text" 
                                id="travelTravelled_${travelsCounter}"
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                value="${(entry.travelled || '').replace(/"/g, '&quot;')}"
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="text" 
                                id="travelEvent_${travelsCounter}"
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium cursor-pointer"
                                value="${eventDisplay.replace(/"/g, '&quot;')}"
                                title="${eventTitle.replace(/"/g, '&quot;')}"
                                onclick="openTravelEventModal(${travelsCounter})"
                                readonly
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="date" 
                                id="travelDate_${travelsCounter}"
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                value="${date}"
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="text" 
                                id="travelAmount_${travelsCounter}"
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                value="${formatNumber(parseAmount(entry.amount || 0))}"
                                placeholder="₱0.00"
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6 text-center">
                            <button
                                onclick="removeTravelsEntry(${travelsCounter})"
                                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                                title="Remove entry"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
                        </td>
                    `;
                            tbody.appendChild(row);

                            // Setup amount input listener
                            setupTravelsAmountListener(`travelAmount_${travelsCounter}`);

                            // Setup auto-save for this entry
                            setupTravelsAutoSave(travelsCounter);
                        });

                        // Recalculate total
                        calculateTravelsTotal();

                        // After loading from database, clear localStorage entries that are already in database
                        // This prevents duplicates on refresh
                        const storageKey = `travels_entries_${departmentId}`;
                        const localTravels = loadTravelsFromLocalStorage(departmentId);
                        if (localTravels.length > 0) {
                            // Filter out entries that are already in the database
                            const unsavedEntries = localTravels.filter(localEntry => {
                                // If entry has travelId and it's in the loaded database IDs, remove it
                                if (localEntry.travelId && loadedDatabaseIds.has(parseInt(localEntry.travelId))) {
                                    return false; // Remove from localStorage
                                }
                                return true; // Keep in localStorage (not yet saved)
                            });

                            // Update localStorage with only unsaved entries
                            if (unsavedEntries.length > 0) {
                                const storageData = {
                                    entries: unsavedEntries,
                                    lastUpdated: new Date().toISOString()
                                };
                                localStorage.setItem(storageKey, JSON.stringify(storageData));
                            } else {
                                // Clear localStorage if all entries are saved
                                localStorage.removeItem(storageKey);
                            }
                        }
                    } else {
                        // No database entries, but still check localStorage
                        const storageKey = `travels_entries_${departmentId}`;
                        const localTravels = loadTravelsFromLocalStorage(departmentId);
                        // Keep localStorage entries if no database entries exist
                    }
                })
                .catch(error => {
                    console.error('Error loading travels:', error);
                });
        }

        function closeTravelsModal() {
            // Set flag to prevent auto-saves from triggering during close
            window.isClosingTravelsModal = true;

            // Simply close the modal - no saving, no reloading, no side effects
            // This preserves the checkbox state in the Select Source modal
            const modal = document.getElementById('travelsModal');
            if (modal) {
                modal.classList.add('hidden');
            }

            // Reset flag after a short delay
            setTimeout(() => {
                window.isClosingTravelsModal = false;
            }, 100);

            console.log('Travels modal closed (no changes made).');
        }

        // Function to clear travel modal content when switching departments
        function clearTravelModal() {
            const tbody = document.getElementById('travelsTableBody');
            if (tbody) {
                tbody.innerHTML = ''; // Clear all rows
            }
            // Reset counter if needed, but keep it global so IDs stay unique
            // travelsCounter is kept as is to avoid ID conflicts
        }

        function addTravelsEntry() {
            travelsCounter++;
            const tbody = document.getElementById('travelsTableBody');
            if (!tbody) return;

            const row = document.createElement('tr');
            row.id = `travelRow_${travelsCounter}`;
            row.className = 'hover:bg-gray-50 transition-colors';

            // Get current date for date input
            const now = new Date();
            const currentDate = now.toISOString().split('T')[0]; // Format: YYYY-MM-DD

            row.innerHTML = `
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="travelTravelled_${travelsCounter}" 
                    class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium" 
                    placeholder="Enter destination"
                    onblur="truncateTravelField('travelTravelled_${travelsCounter}')"
                >
                <button 
                    onclick="openViewDetailsModal('Travelled', 'travelTravelled_${travelsCounter}')"
                    class="absolute right-3 top-1/2 transform -translate-y-1/2 text-green-500 hover:text-green-700 transition-colors p-1"
                    title="View full content"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
            </button>
        </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <div class="relative">
                <input 
                    type="text" 
                    id="travelEvent_${travelsCounter}" 
                    class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                    placeholder="Click to enter event/activity..."
                    readonly
                    onclick="openTravelEventModal(${travelsCounter})"
                >
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                    <button 
                        onclick="openViewDetailsModal('Event/Activity', 'travelEvent_${travelsCounter}')"
                        class="text-green-500 hover:text-green-700 transition-colors p-1"
                        title="View full content"
                    >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                </button>
                    <button 
                        onclick="openTravelEventModal(${travelsCounter})"
                        class="text-green-500 hover:text-green-700 transition-colors p-1"
                        title="Edit event/activity"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>
                    </svg>
                </button>
            </div>
                </div>
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="date" 
                id="travelDate_${travelsCounter}" 
                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                value="${currentDate}"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6">
            <input 
                type="text" 
                id="travelAmount_${travelsCounter}" 
                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium" 
                value="₱0.00"
                placeholder="₱0.00"
            >
        </td>
        <td class="border-b border-gray-200 py-4 px-6 text-center">
            <button 
                onclick="removeTravelsEntry(${travelsCounter})" 
                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                title="Remove entry"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                </button>
        </td>
    `;

            tbody.appendChild(row);

            // Setup amount input listener
            setupTravelsAmountListener(`travelAmount_${travelsCounter}`);

            // Setup auto-save for this entry
            setupTravelsAutoSave(travelsCounter);

            // Auto-save the new entry after a short delay to allow fields to be populated
            setTimeout(() => {
                autoSaveTravelEntry(travelsCounter);
            }, 500);
        }

        function removeTravelsEntry(entryId) {
            const row = document.getElementById(`travelRow_${entryId}`);
            if (!row) {
                console.error('Travel row not found for entryId:', entryId);
                return;
            }

            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            let departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            const travelId = row.getAttribute('data-travel-id');

            // Validate travelId if it exists
            const travelIdNum = travelId ? parseInt(travelId) : null;
            if (travelId && (isNaN(travelIdNum) || travelIdNum <= 0)) {
                console.error('Invalid travelId:', travelId);
                alert('Error: Invalid travel entry ID. Please refresh the page and try again.');
                return;
            }

            // Travels no longer deduct from categories

            // Confirm deletion
            if (!confirm('Are you sure you want to delete this travel entry?')) {
                return;
            }

            // Delete from database if it exists
            if (travelIdNum) {
                // departmentId might be null for budget users, but API will handle it
                if (!departmentId) {
                    // Try to get departmentId from the row or other sources
                    const deptSelect = document.getElementById('departmentSelect');
                    const offSelect = document.getElementById('officeSelect');
                    departmentId = (deptSelect && deptSelect.value) ? deptSelect.value : (offSelect && offSelect.value ? offSelect.value : null);
                }

                fetch('../api/delete_travel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        travel_id: travelIdNum,
                        department_id: departmentId ? parseInt(departmentId) : 0  // Send 0 if null, API will handle it for budget users
                    })
                })
                    .then(response => {
                        // Check if response is ok, but also parse JSON even if not ok to get error message
                        return response.json().then(data => {
                            if (!response.ok) {
                                throw new Error(data.message || 'Network response was not ok');
                            }
                            return data;
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            // Get the amount before removing the row
                            const travelAmountInput = document.getElementById(`travelAmount_${entryId}`);
                            const travelAmount = travelAmountInput ? parseAmount(travelAmountInput.value || '0') : 0;

                            // Remove row from DOM immediately
                            if (row && row.parentNode) {
                                row.remove();
                            }
                            calculateTravelsTotal();

                            // NEW DEDUCTION SYSTEM: Check if this Travel was used in any Expense Category deductions
                            if (travelIdNum && departmentId && travelAmount > 0) {
                                // Find all Expense Categories that have this Travel in their deduction sources
                                const allUtilizationRows = document.querySelectorAll('[id^="entryRow_"]');

                                allUtilizationRows.forEach(utilRow => {
                                    const categoryEntryId = utilRow.id.split('_')[1];
                                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, categoryEntryId);
                                    const savedSources = localStorage.getItem(deductionSourcesKey);

                                    if (savedSources) {
                                        try {
                                            let deductionSources = JSON.parse(savedSources);
                                            let updated = false;

                                            // Check each deduction source
                                            deductionSources.forEach((ds, index) => {
                                                if (ds.sourceType === 'travels') {
                                                    // Find if this Travel entry is in the entries array
                                                    const travelEntryIndex = ds.entries.findIndex(e => {
                                                        const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                                        const tId = parseInt(travelIdNum) || travelIdNum;
                                                        return eId === tId || String(eId) === String(tId) || e.sourceEntryId === travelIdNum;
                                                    });

                                                    if (travelEntryIndex >= 0) {
                                                        // Found this Travel in the deduction sources
                                                        const travelEntryAmount = parseFloat(ds.entries[travelEntryIndex].amount) || 0;

                                                        // Remove this Travel entry from the array
                                                        ds.entries.splice(travelEntryIndex, 1);

                                                        // Recalculate total amount
                                                        ds.amount = ds.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);

                                                        // Update deduction field
                                                        const deductionInput = document.getElementById(`deduction_${categoryEntryId}`);
                                                        if (deductionInput) {
                                                            const currentDeduction = parseAmount(deductionInput.value || '0');
                                                            const newDeduction = Math.max(0, currentDeduction - travelEntryAmount);

                                                            if (newDeduction > 0) {
                                                                deductionInput.value = formatNumber(newDeduction);
                                                            } else {
                                                                deductionInput.value = '';
                                                            }

                                                            // Recalculate row total
                                                            calculateRowTotal(categoryEntryId);

                                                            console.log(`Removed Travel ${travelIdNum} (${formatNumber(travelEntryAmount)}) from deduction for category entry ${categoryEntryId}. New deduction: ${formatNumber(newDeduction)}`);
                                                        }

                                                        updated = true;
                                                    }
                                                }
                                            });

                                            // Remove deduction sources with 0 amount or no entries
                                            deductionSources = deductionSources.filter(ds => ds.amount > 0 && ds.entries.length > 0);

                                            if (updated) {
                                                // Save updated deduction sources
                                                if (deductionSources.length > 0) {
                                                    localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                                                } else {
                                                    localStorage.removeItem(deductionSourcesKey);
                                                }

                                                // Also remove from selections
                                                const selectionsKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${categoryEntryId}_source_travels`;
                                                const savedSelections = localStorage.getItem(selectionsKey);
                                                if (savedSelections) {
                                                    try {
                                                        let selections = JSON.parse(savedSelections);
                                                        selections = selections.filter(sel => {
                                                            const selId = parseInt(sel) || sel;
                                                            const tId = parseInt(travelIdNum) || travelIdNum;
                                                            return selId !== tId && String(selId) !== String(tId) && sel !== travelIdNum;
                                                        });

                                                        if (selections.length > 0) {
                                                            localStorage.setItem(selectionsKey, JSON.stringify(selections));
                                                        } else {
                                                            localStorage.removeItem(selectionsKey);
                                                        }
                                                    } catch (e) {
                                                        console.error('Error updating selections:', e);
                                                    }
                                                }

                                                // Save the updated deduction to database immediately
                                                saveUtilizationToLocalStorage();
                                            }
                                        } catch (e) {
                                            console.error('Error processing deduction sources:', e);
                                        }
                                    }
                                });
                            }

                            // Save deductions to database immediately (don't recalculate - we already updated them correctly)
                            saveUtilizationToLocalStorage();
                            // Recalculate totals only
                            calculateTotals();
                            console.log('Travel entry deleted and deduction removed successfully');
                            // Show success message
                            if (data.message) {
                                console.log(data.message);
                            }
                            
                            // Check if Travel modal is currently open
                            const modal = document.getElementById('travelsModal');
                            const isModalOpen = modal && !modal.classList.contains('hidden');
                            
                            // Reload Travel list from database to ensure UI is in sync
                            // This prevents deleted entries from reappearing
                            if (departmentId) {
                                fetch(`../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Clear and reload Travel table
                                            const tbody = document.getElementById('travelsTableBody');
                                            if (tbody) {
                                                tbody.innerHTML = '';
                                                travelsCounter = 0; // Reset counter
                                                
                                                if (data.entries && data.entries.length > 0) {
                                                    data.entries.forEach((entry, index) => {
                                                        travelsCounter++;
                                                        const row = document.createElement('tr');
                                                        row.id = `travelRow_${travelsCounter}`;
                                                        row.className = 'hover:bg-gray-50 transition-colors';

                                                        if (entry.id) {
                                                            row.setAttribute('data-travel-id', entry.id);
                                                        }
                                                        if (entry.deducted_from_entry_id) {
                                                            row.setAttribute('data-deduct-from-entry-id', entry.deducted_from_entry_id);
                                                        }

                                                        const date = entry.date || '';
                                                        let eventDisplay = entry.event_activity || '';
                                                        let eventTitle = '';
                                                        if (eventDisplay.length > 50) {
                                                            eventTitle = eventDisplay;
                                                            eventDisplay = eventDisplay.substring(0, 50) + '...';
                                                        }

                                                        row.innerHTML = `
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="travelTravelled_${travelsCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${(entry.travelled || '').replace(/"/g, '&quot;')}">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="travelEvent_${travelsCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium cursor-pointer"
                                                                    value="${eventDisplay.replace(/"/g, '&quot;')}"
                                                                    title="${eventTitle.replace(/"/g, '&quot;')}"
                                                                    onclick="openTravelEventModal(${travelsCounter})" readonly>
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="date" id="travelDate_${travelsCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${date}">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6">
                                                                <input type="text" id="travelAmount_${travelsCounter}"
                                                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                                                    value="${formatNumber(parseAmount(entry.amount || 0))}" placeholder="₱0.00">
                                                            </td>
                                                            <td class="border-b border-gray-200 py-4 px-6 text-center">
                                                                <button onclick="removeTravelsEntry(${travelsCounter})"
                                                                    class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                                                                    title="Remove entry">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            </td>
                                                        `;
                                                        tbody.appendChild(row);
                                                    });
                                                } else if (isModalOpen) {
                                                    // If modal is open and no entries, show empty message
                                                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-gray-500">No travel entries</td></tr>';
                                                }
                                                calculateTravelsTotal();
                                            }
                                            console.log('✓ Travel list reloaded from database');
                                            
                                            // If modal is open, ensure it stays visible and updated
                                            if (isModalOpen && modal) {
                                                modal.classList.remove('hidden');
                                            }
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error reloading Travel list:', error);
                                    });
                            }
                        } else {
                            // If entry not found, it might have been deleted already or ID is stale
                            // Check if the error is "not found"
                            if (data.message && (data.message.includes('not found') || data.message.includes('not found'))) {
                                console.warn('Travel entry not found in database (ID may be stale), reloading entries:', travelIdNum);
                                // Remove from DOM anyway since it doesn't exist in database
                                if (row && row.parentNode) {
                                    row.remove();
                                }
                                calculateTravelsTotal();

                                // Reload entries to sync with database and get fresh IDs
                                if (departmentId) {
                                    loadTravelsEntries(departmentId);
                                    saveTravelsToLocalStorage(departmentId);
                                    // Show a brief message that entries were refreshed
                                    console.log('Travel entries reloaded to sync with database');
                                }
                                // Don't show alert - just silently reload to get fresh IDs
                                // The entry will disappear from the UI if it was already deleted
                            } else {
                                console.error('Error deleting travel:', data.message);
                                alert('Error deleting travel entry: ' + (data.message || 'Unknown error'));
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error deleting travel:', error);
                        // If it's a "not found" error, try reloading entries to get fresh IDs
                        if (error.message && (error.message.includes('not found') || error.message.includes('404'))) {
                            console.warn('Travel entry not found, reloading entries to sync with database');
                            if (departmentId) {
                                loadTravelsEntries(departmentId);
                                saveTravelsToLocalStorage(departmentId);
                            }
                            // Remove the row from UI if it still exists
                            if (row && row.parentNode) {
                                row.remove();
                                calculateTravelsTotal();
                            }
                        } else {
                            // Show more specific error message
                            const errorMsg = error.message || 'Unknown error occurred';
                            alert('Error deleting travel entry: ' + errorMsg);
                        }
                    });
            } else if (!travelId) {
                // Entry not saved to database yet (localStorage only), just remove from DOM
                const travelAmountInput = document.getElementById(`travelAmount_${entryId}`);
                const travelDeductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);
                const travelAmount = travelAmountInput ? parseAmount(travelAmountInput.value || '0') : 0;
                const deductedFromEntryId = travelDeductFromSelect ? travelDeductFromSelect.value : selectedEntryId;

                // Remove row from DOM
                if (row && row.parentNode) {
                    row.remove();
                }
                calculateTravelsTotal();

                // Update deduction if needed
                if (deductedFromEntryId && travelAmount > 0) {
                    const deductionInput = document.getElementById(`deduction_${deductedFromEntryId}`);
                    if (deductionInput) {
                        const currentDeduction = parseAmount(deductionInput.value || '0');
                        const newDeduction = Math.max(0, currentDeduction - travelAmount);
                        if (newDeduction > 0) {
                            deductionInput.value = formatNumber(newDeduction);
                        } else {
                            deductionInput.value = '';
                        }
                        calculateRowTotal(deductedFromEntryId);
                    }
                }

                // Recalculate and update localStorage
                recalculateAllDeductions().then(() => {
                    calculateTotals();
                    if (departmentId) {
                        saveTravelsToLocalStorage(departmentId);
                        saveDeductionsToLocalStorage(departmentId);
                        saveUtilizationToLocalStorage();
                    }
                });
            }
        }

        function setupTravelsAmountListener(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            let originalValue = '';

            input.addEventListener('focus', function (e) {
                originalValue = e.target.value;
                e.target.value = e.target.value.replace(/[₱,]/g, '');
            });

            input.addEventListener('input', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value === '' || value === '.' || !isNaN(value)) {
                    e.target.value = formatNumberInput(value);
                    calculateTravelsTotal();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Auto-deduct from selected category immediately
                    autoDeductFromTravelCategory(inputId);
                } else {
                    e.target.value = originalValue.replace(/[₱,]/g, '');
                }
            });

            input.addEventListener('blur', function (e) {
                const value = e.target.value.replace(/[₱,]/g, '');
                if (value !== '' && !isNaN(value)) {
                    e.target.value = formatNumber(parseFloat(value));
                    originalValue = e.target.value;
                    calculateTravelsTotal();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Auto-deduct from selected category
                    autoDeductFromTravelCategory(inputId);
                } else if (value === '') {
                    e.target.value = '';
                    calculateTravelsTotal();
                    // Set flag for real-time updates during editing
                    window.forceRecalculateDeductions = true;
                    // Remove deduction if amount is cleared
                    autoDeductFromTravelCategory(inputId);
                }
            });
        }

        // Function to automatically deduct travel amount from selected category
        function autoDeductFromTravelCategory(amountInputId) {
            // Get the entry ID from the amount input ID (e.g., travelAmount_1 -> 1)
            const entryId = amountInputId.split('_')[1];
            if (!entryId) return;

            // Get the amount and deduct from select
            const amountInput = document.getElementById(amountInputId);
            const deductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);

            if (!amountInput || !deductFromSelect) return;

            const amount = parseAmount(amountInput.value || '0');
            const selectedEntryId = deductFromSelect.value;

            if (!selectedEntryId) {
                // If no category selected, don't deduct
                return;
            }

            // Get the deduction input for the selected category
            const deductionInput = document.getElementById(`deduction_${selectedEntryId}`);
            const budgetAllocatedInput = document.getElementById(`budgetAllocated_${selectedEntryId}`);

            if (!deductionInput || !budgetAllocatedInput) return;

            // Check if the category has budget allocated
            const budgetAllocated = parseAmount(budgetAllocatedInput.value || '0');

            if (budgetAllocated === 0) {
                // Show warning if no budget allocated and amount is being entered
                if (amount > 0) {
                    alert('Warning: This expense category has no budget allocated. Please allocate a budget first before deducting.');
                    // Clear the amount or category selection
                    if (amountInput) amountInput.value = '';
                    if (deductFromSelect) deductFromSelect.value = '';
                }
                return;
            }

            // Set flag to force recalculation from DOM (for real-time updates during editing)
            window.forceRecalculateDeductions = true;

            // Get department ID
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const deptId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            // Recalculate all deductions (this will sum all PRs and Travels for each category)
            recalculateAllDeductions().then(() => {
                // Save deductions to localStorage after recalculation
                if (deptId) {
                    saveDeductionsToLocalStorage(deptId);
                }
                // Reset flag after recalculation
                window.forceRecalculateDeductions = false;
            });
        }

        function calculateTravelsTotal() {
            let total = 0;
            const rows = document.querySelectorAll('[id^="travelRow_"]');

            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const amountEl = document.getElementById(`travelAmount_${entryId}`);
                if (amountEl) {
                    total += parseAmount(amountEl.value);
                }
            });

            const totalEl = document.getElementById('travelsTotal');
            if (totalEl) {
                totalEl.textContent = formatNumber(total);
                // Apply red color if negative
                if (total < 0) {
                    totalEl.classList.remove('text-green-600');
                    totalEl.classList.add('text-red-600');
                } else {
                    totalEl.classList.remove('text-red-600');
                    totalEl.classList.add('text-green-600');
                }
            }
        }

        // Travel Event Modal Management
        let currentTravelEventEntryId = null;

        function openTravelEventModal(entryId) {
            currentTravelEventEntryId = entryId;
            const eventInput = document.getElementById(`travelEvent_${entryId}`);
            const modal = document.getElementById('travelEventModal');
            const modalContent = document.getElementById('travelEventModalContent');
            const textarea = document.getElementById('travelEventTextarea');

            if (modal && modalContent && textarea && eventInput) {
                // Get full text from title attribute if truncated, otherwise from value
                // The title attribute stores the full text when truncated
                let fullText = '';
                if (eventInput.title && eventInput.title.trim()) {
                    // If title exists, it contains the full text
                    fullText = eventInput.title;
                } else {
                    // Otherwise, use the value (which might be truncated)
                    fullText = eventInput.value;
                    // If value ends with '...', it's truncated, so we need to check data attribute
                    if (fullText.endsWith('...')) {
                        const dataFullText = eventInput.getAttribute('data-full-text');
                        if (dataFullText) {
                            fullText = dataFullText;
                        }
                    }
                }

                textarea.value = fullText;

                // Show modal with fade-in animation
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('opacity-100');
                    modalContent.classList.remove('scale-95', 'opacity-0');
                    modalContent.classList.add('scale-100', 'opacity-100');
                }, 10);

                // Focus on textarea
                setTimeout(() => {
                    textarea.focus();
                }, 100);
            }
        }

        function closeTravelEventModal() {
            const modal = document.getElementById('travelEventModal');
            const modalContent = document.getElementById('travelEventModalContent');

            if (modal && modalContent) {
                // Fade out animation
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);

                currentTravelEventEntryId = null;
            }
        }

        function saveTravelEvent() {
            if (!currentTravelEventEntryId) return;

            const textarea = document.getElementById('travelEventTextarea');
            const eventInput = document.getElementById(`travelEvent_${currentTravelEventEntryId}`);

            if (textarea && eventInput) {
                // Update the input field with the textarea value
                const fullText = textarea.value.trim();
                if (fullText.length > 50) {
                    eventInput.value = fullText.substring(0, 50) + '...';
                    eventInput.title = fullText; // Show full text on hover
                    eventInput.setAttribute('data-full-text', fullText); // Store full text in data attribute
                } else {
                    eventInput.value = fullText;
                    eventInput.title = '';
                    eventInput.removeAttribute('data-full-text');
                }

                // Add visual indicator that it has content
                if (fullText) {
                    eventInput.classList.add('bg-green-50', 'border-green-300');
                    eventInput.classList.remove('bg-white');
                } else {
                    eventInput.classList.remove('bg-green-50', 'border-green-300');
                    eventInput.classList.add('bg-white');
                }

                // Auto-save the travel entry
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                if (departmentId) {
                    saveTravelsToLocalStorage(departmentId);
                    autoSaveTravelEntry(currentTravelEventEntryId);
                }
            }

            closeTravelEventModal();
        }

        function truncateTravelField(inputId) {
            const input = document.getElementById(inputId);
            if (!input) return;

            const fullText = input.value.trim();
            if (fullText.length > 30) {
                // Store full text in data attribute for viewing
                input.setAttribute('data-full-text', fullText);
                input.value = fullText.substring(0, 30) + '...';
            } else {
                input.removeAttribute('data-full-text');
            }
        }

        // Function to save travels to localStorage
        function saveTravelsToLocalStorage(departmentId) {
            // Don't save if modal is being closed
            if (window.isClosingTravelsModal) return;

            if (!departmentId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            }
            if (!departmentId) return;

            const entries = [];
            const rows = document.querySelectorAll('[id^="travelRow_"]');

            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const travelled = document.getElementById(`travelTravelled_${entryId}`)?.value || '';
                const eventInput = document.getElementById(`travelEvent_${entryId}`);
                let event = '';
                if (eventInput) {
                    event = eventInput.getAttribute('data-full-text') || eventInput.title || eventInput.value || '';
                }
                const date = document.getElementById(`travelDate_${entryId}`)?.value || '';
                const amountInput = document.getElementById(`travelAmount_${entryId}`);
                // Parse amount to get raw numeric value (remove ₱ and commas)
                const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;
                const deductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);
                const deductedFromEntryId = deductFromSelect ? (deductFromSelect.value || null) : null;
                const travelId = row.getAttribute('data-travel-id') || null;

                if (travelled || event || amount > 0) {
                    entries.push({
                        travelled: travelled,
                        event: event,
                        date: date,
                        amount: amount, // Save as numeric value, not formatted string
                        deductedFromEntryId: deductedFromEntryId,
                        travelId: travelId
                    });
                }
            });

            const storageKey = getTravelsDataKey(departmentId);
            localStorage.setItem(storageKey, JSON.stringify({
                entries: entries,
                department_id: departmentId,
                saved_at: new Date().toISOString()
            }));

            // Also save deductions to localStorage after Travel entries are saved
            setTimeout(() => {
                saveDeductionsToLocalStorage(departmentId);
            }, 100);
        }

        // Function to load travels from localStorage
        function loadTravelsFromLocalStorage(departmentId) {
            if (!departmentId) return [];

            const storageKey = getTravelsDataKey(departmentId);
            const stored = localStorage.getItem(storageKey);

            if (stored) {
                try {
                    const data = JSON.parse(stored);
                    return data.entries || [];
                } catch (e) {
                    console.error('Error parsing localStorage data:', e);
                    return [];
                }
            }
            return [];
        }

        // Function to auto-save single travel entry
        function autoSaveTravelEntry(entryId) {
            // Don't auto-save if modal is being closed
            if (window.isClosingTravelsModal) {
                console.log('Skipping travel auto-save - modal is closing');
                return;
            }

            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) return;

            const row = document.getElementById(`travelRow_${entryId}`);
            if (!row) return;

            const travelled = document.getElementById(`travelTravelled_${entryId}`)?.value || '';
            const eventInput = document.getElementById(`travelEvent_${entryId}`);
            let event = '';
            if (eventInput) {
                event = eventInput.getAttribute('data-full-text') || eventInput.title || eventInput.value || '';
            }
            const date = document.getElementById(`travelDate_${entryId}`)?.value || '';
            const amountInput = document.getElementById(`travelAmount_${entryId}`);
            // Parse amount to get raw numeric value (remove ₱ and commas)
            const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;
            const deductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);
            let deductedFromEntryId = deductFromSelect ? (deductFromSelect.value || null) : null;

            console.log(`autoSaveTravelEntry ${entryId}: deductedFromSelect value = ${deductedFromEntryId}`);

            // Validate deductedFromEntryId - must be a valid positive integer
            if (deductedFromEntryId) {
                const parsedId = parseInt(deductedFromEntryId);
                if (isNaN(parsedId) || parsedId <= 0) {
                    console.warn(`Invalid deducted_from_entry_id: ${deductedFromEntryId}. Setting to null.`);
                    deductedFromEntryId = null;
                } else {
                    deductedFromEntryId = parsedId;
                }
            }

            console.log(`autoSaveTravelEntry ${entryId}: Final deducted_from_entry_id = ${deductedFromEntryId}`);

            const travelId = row.getAttribute('data-travel-id') || null;

            // Skip if entry is completely empty (no data at all) - but allow saving if category is selected
            if (!travelled && !event && amount === 0 && !date && !deductedFromEntryId) {
                return;
            }

            // IMPORTANT: If amount > 0, we MUST have a category selected for deduction to work
            // Save immediately when both amount and category are present
            if (amount > 0 && !deductedFromEntryId) {
                // Don't block saving, but log a warning
                console.warn(`Travel entry ${entryId}: Amount is ${amount} but no category selected. Deduction will not happen until category is selected.`);
            }

            const entryData = {
                travelled: travelled,
                event_activity: event,
                date: date,
                amount: amount, // Save as numeric value, not formatted string
                entry_id: deductedFromEntryId ? parseInt(deductedFromEntryId) : null
            };

            console.log(`Saving travel entry ${entryId} to database:`, entryData);
            console.log(`entry_id being sent: ${entryData.entry_id}`);

            // Save to database
            fetch('../api/save_single_travel.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    entry: entryData,
                    fiscal_year: CURRENT_FISCAL_YEAR,
                    travel_id: travelId || null
                })
            })
                .then(response => {
                    console.log(`Response status: ${response.status}`);
                    return response.json();
                })
                .then(data => {
                    console.log(`Response data:`, data);
                    if (data.success) {
                        // Store the travel_id in the row for future updates
                        if (data.travel_id && !travelId) {
                            row.setAttribute('data-travel-id', data.travel_id);
                        }

                        // Store deducted_from_entry_id if returned
                        if (data.deducted_from_entry_id) {
                            row.setAttribute('data-deduct-from-entry-id', data.deducted_from_entry_id);
                            // Update dropdown value to match
                            if (deductFromSelect) {
                                deductFromSelect.value = data.deducted_from_entry_id;
                            }
                        }

                        // IMPORTANT: Always reload utilization entries from database after saving
                        // This ensures deductions are automatically updated in the database and displayed in UI
                        // The API already recalculates deductions, so we just need to reload to see the changes
                        setTimeout(() => {
                            loadUtilizationEntries(departmentId).then(() => {
                                console.log('✓ Travel entry saved and deductions updated in database');
                                // Recalculate deductions to update UI
                                recalculateAllDeductions();
                            });
                        }, 200);

                        // Also save to localStorage as backup
                        saveTravelsToLocalStorage(departmentId);
                    } else {
                        console.error('Error auto-saving travel:', data.message);
                        if (data.message && data.message.includes('no budget allocated')) {
                            alert(data.message);
                        }
                    }
                })
                .catch(error => {
                    console.error('Error auto-saving travel:', error);
                });
        }

        // Function to setup auto-save listeners for travel entry fields
        function setupTravelsAutoSave(entryId) {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) return;

            // Get all input fields for this entry
            const travelledInput = document.getElementById(`travelTravelled_${entryId}`);
            const eventInput = document.getElementById(`travelEvent_${entryId}`);
            const dateInput = document.getElementById(`travelDate_${entryId}`);
            const amountInput = document.getElementById(`travelAmount_${entryId}`);
            const deductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);

            // Debounce function to avoid too many saves
            let saveTimeout;
            const debouncedAutoSave = () => {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    autoSaveTravelEntry(entryId);
                }, 1000); // Wait 1 second after last change
            };

            // Save to localStorage immediately, database after debounce
            const saveToLocalStorage = () => {
                saveTravelsToLocalStorage(departmentId);
            };

            if (travelledInput) {
                travelledInput.addEventListener('input', () => {
                    saveToLocalStorage();
                    debouncedAutoSave();
                });
                travelledInput.addEventListener('blur', () => {
                    autoSaveTravelEntry(entryId);
                });
            }
            if (eventInput) {
                eventInput.addEventListener('change', () => {
                    saveToLocalStorage();
                    debouncedAutoSave();
                });
            }
            if (dateInput) {
                dateInput.addEventListener('change', () => {
                    saveToLocalStorage();
                    debouncedAutoSave();
                });
            }
            if (amountInput) {
                amountInput.addEventListener('input', () => {
                    saveToLocalStorage();
                    // Trigger real-time deduction recalculation
                    recalculateAllDeductions();
                    // If category is already selected, save immediately
                    const deductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);
                    if (deductFromSelect && deductFromSelect.value) {
                        debouncedAutoSave();
                    }
                });
                amountInput.addEventListener('blur', () => {
                    // Trigger real-time deduction recalculation
                    recalculateAllDeductions();
                    // Always save when amount changes (even if no category yet)
                    autoSaveTravelEntry(entryId);
                });
            }
            if (deductFromSelect) {
                deductFromSelect.addEventListener('change', () => {
                    const selectedEntryId = deductFromSelect.value;
                    const amountInput = document.getElementById(`travelAmount_${entryId}`);
                    const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;

                    // Store the deducted_from_entry_id in the row attribute
                    const row = document.getElementById(`travelRow_${entryId}`);
                    if (row) {
                        if (selectedEntryId) {
                            row.setAttribute('data-deduct-from-entry-id', selectedEntryId);
                        } else {
                            row.removeAttribute('data-deduct-from-entry-id');
                        }
                    }

                    if (selectedEntryId && amount > 0) {
                        // Check if category has budget allocated
                        const budgetAllocatedInput = document.getElementById(`budgetAllocated_${selectedEntryId}`);
                        if (budgetAllocatedInput) {
                            const budgetAllocated = parseAmount(budgetAllocatedInput.value || '0');
                            if (budgetAllocated === 0) {
                                alert('Warning: This expense category has no budget allocated. Please allocate a budget first before deducting.');
                                deductFromSelect.value = '';
                                if (row) {
                                    row.removeAttribute('data-deduct-from-entry-id');
                                }
                                return;
                            }
                        }
                    }

                    // Save immediately when category is selected to persist it
                    autoSaveTravelEntry(entryId);

                    // Recalculate deductions immediately
                    recalculateAllDeductions();

                    saveToLocalStorage();
                });
            }
        }

        // Clear Utilization Data Function
        window.clearUtilizationData = function() {
            console.log('clearUtilizationData called');
            // Show the confirmation modal
            document.getElementById('clearUtilizationModal').classList.remove('hidden');
        };
        
        // Modal functions for clear data
        window.closeClearUtilizationModal = function() {
            document.getElementById('clearUtilizationModal').classList.add('hidden');
        };
        
        window.confirmClearUtilization = function() {
            console.log('confirmClearUtilization called');
            // Close first modal
            closeClearUtilizationModal();
            
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            console.log('Clearing utilization entries...');
            
            // Clear all utilization entries from the table
            const tbody = document.getElementById('utilizationTableBody');
            if (tbody) {
                tbody.innerHTML = '';
            }

            // Reset totals
            const totalBudget = document.getElementById('totalBudgetUtilization');
            const totalDeductions = document.getElementById('totalDeductions');
            const totalBalance = document.getElementById('totalBalance');
            
            if (totalBudget) totalBudget.textContent = '₱0.00';
            if (totalDeductions) totalDeductions.textContent = '₱0.00';
            if (totalBalance) totalBalance.textContent = '₱0.00';

            // Clear localStorage for this department
            if (departmentId) {
                localStorage.removeItem('utilization_' + departmentId);
            }

            // Hide summary section if visible
            const summarySection = document.getElementById('summarySection');
            if (summarySection) {
                summarySection.classList.add('hidden');
            }

            // Reset entry counter
            window.entryCounter = 1;

            console.log('Showing second modal...');
            // Show second modal asking about database
            const secondModal = document.getElementById('clearUtilizationDatabaseModal');
            if (secondModal) {
                secondModal.classList.remove('hidden');
                console.log('Second modal shown');
            } else {
                console.error('Second modal not found!');
            }
        }
        
        window.closeClearUtilizationDatabaseModal = function() {
            document.getElementById('clearUtilizationDatabaseModal').classList.add('hidden');
        };
        
        window.confirmClearUtilizationDatabase = function() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            
            if (!departmentId) {
                alert('⚠️ Please select a department or office first.');
                closeClearUtilizationDatabaseModal();
                return;
            }
            
            // Check if we're viewing a prior year (more than 1 year old)
            // Allow clearing current year and recent years, only block old archived years
            const currentYear = new Date().getFullYear();
            if (CURRENT_FISCAL_YEAR < (currentYear - 1)) {
                alert(`⚠️ Cannot clear data from prior year ${CURRENT_FISCAL_YEAR}.\n\nPrior years are archived and cannot be modified.\n\nPlease switch to the current year (${currentYear}) to clear data.`);
                closeClearUtilizationDatabaseModal();
                return;
            }
            
            fetch('../api/clear_utilization_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    fiscal_year: CURRENT_FISCAL_YEAR // Only clear current fiscal year
                })
            })
            .then(function(response) {
                return response.json();
            })
            .then(function(data) {
                closeClearUtilizationDatabaseModal();
                if (data.success) {
                    // Clear ONLY localStorage related to utilization for this department AND current fiscal year
                    const keysToRemove = [];
                    for (let i = 0; i < localStorage.length; i++) {
                        const key = localStorage.key(i);
                        // Only remove keys that include the current fiscal year
                        if (key && key.includes('year_' + CURRENT_FISCAL_YEAR) && (
                            key.includes('utilization') || 
                            key.includes('department_' + departmentId) ||
                            key.includes('dept_' + departmentId) ||
                            key.includes('deduction_sources') ||
                            key.includes('deductions_data') ||
                            key.includes('purchase_request') ||
                            key.includes('travels') ||
                            key.includes('honoraria')
                        )) {
                            keysToRemove.push(key);
                        }
                    }
                    
                    // Remove all identified keys
                    keysToRemove.forEach(key => {
                        localStorage.removeItem(key);
                        console.log('Removed localStorage key:', key);
                    });
                    
                    console.log(`Cleared ${keysToRemove.length} localStorage keys for fiscal year ${CURRENT_FISCAL_YEAR}`);
                    
                    alert('✓ Success! All utilization data has been cleared from the database and localStorage.');
                    
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
                closeClearUtilizationDatabaseModal();
                alert('❌ Error clearing utilization data. Please try again.');
            });
        };

        async function generateSummary() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            const departmentName = document.getElementById('selectedDepartmentName') ? document.getElementById('selectedDepartmentName').textContent : 'Unknown';

            // Recalculate all deductions first to ensure they're up to date (includes both PR and Travels)
            // This includes both database entries AND DOM entries (real-time) for the current department
            await recalculateAllDeductions();

            // Collect Budget Utilization Data
            const utilizationEntries = [];
            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');

            let totalAllocated = 0;
            let totalDeductions = 0;
            let totalBalance = 0;

            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const columnArea = document.getElementById(`columnArea_${entryId}`);
                const accountCode = document.getElementById(`accountCode_${entryId}`);
                const budgetAllocated = document.getElementById(`budgetAllocated_${entryId}`);
                const deduction = document.getElementById(`deduction_${entryId}`);
                const total = document.getElementById(`total_${entryId}`);

                if (columnArea && (columnArea.value || budgetAllocated?.value || deduction?.value)) {
                    const allocated = parseAmount(budgetAllocated?.value || '0');
                    const deduct = parseAmount(deduction?.value || '0');
                    const bal = parseAmount(total?.value || '0');

                    totalAllocated += allocated;
                    totalDeductions += deduct;
                    totalBalance += bal;

                    utilizationEntries.push({
                        category: columnArea.value || '',
                        accountCode: accountCode?.value || '',
                        allocated: allocated,
                        deduction: deduct,
                        balance: bal
                    });
                }
            });

            // Collect Purchase Request Data from database
            const prEntries = [];
            let prTotal = 0;

            // First, create a map of deducted_from_entry_id to category name
            const categoryMap = new Map();

            // Map from DOM entries
            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const rowDbEntryId = row.getAttribute('data-db-entry-id');
                const rowDeductedFromEntryId = row.getAttribute('data-deducted-from-entry-id');
                const columnArea = document.getElementById(`columnArea_${entryId}`);

                if (columnArea && columnArea.value) {
                    if (rowDbEntryId) {
                        categoryMap.set(parseInt(rowDbEntryId), columnArea.value);
                    }
                    if (rowDeductedFromEntryId) {
                        categoryMap.set(parseInt(rowDeductedFromEntryId), columnArea.value);
                    }
                }
            });

            // Also load from database to get category names
            try {
                const utilResponse = await fetch(`../api/load_utilization_entries.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`);
                const utilData = await utilResponse.json();
                if (utilData.success && utilData.entries) {
                    utilData.entries.forEach(utilEntry => {
                        if (utilEntry.id && utilEntry.expense_category) {
                            categoryMap.set(parseInt(utilEntry.id), utilEntry.expense_category);
                        }
                        if (utilEntry.deducted_from_entry_id && utilEntry.expense_category) {
                            categoryMap.set(parseInt(utilEntry.deducted_from_entry_id), utilEntry.expense_category);
                        }
                    });
                }
            } catch (e) {
                console.error('Error loading utilization entries for category mapping:', e);
            }

            // Load from database
            try {
                const prResponse = await fetch(`../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`);
                const prData = await prResponse.json();

                if (prData.success && prData.entries) {
                    prData.entries.forEach(entry => {
                        const amountValue = parseFloat(entry.amount || 0);
                        prTotal += amountValue;

                        // Get category name from map using entry_id
                        let deductedFrom = '';
                        if (entry.entry_id) {
                            // Try multiple matching strategies
                            const entryId = parseInt(entry.entry_id);
                            deductedFrom = categoryMap.get(entryId) || '';

                            // If not found, try as string
                            if (!deductedFrom) {
                                deductedFrom = categoryMap.get(String(entryId)) || '';
                            }

                            // If still not found, try to find by searching all utilization entries
                            if (!deductedFrom) {
                                utilizationRows.forEach(row => {
                                    const rowEntryId = row.id.split('_')[1];
                                    const rowDbEntryId = row.getAttribute('data-db-entry-id');
                                    const rowDeductedFromEntryId = row.getAttribute('data-deducted-from-entry-id');
                                    const columnArea = document.getElementById(`columnArea_${rowEntryId}`);

                                    if (columnArea && columnArea.value) {
                                        // Check if this row matches the entry_id
                                        if ((rowDbEntryId && parseInt(rowDbEntryId) === entryId) ||
                                            (rowDeductedFromEntryId && parseInt(rowDeductedFromEntryId) === entryId)) {
                                            deductedFrom = columnArea.value;
                                            console.log(`✓ Found category "${deductedFrom}" for PR entry by searching DOM (entry_id: ${entryId})`);
                                        }
                                    }
                                });
                            }

                            // Debug logging
                            if (!deductedFrom) {
                                console.warn(`⚠ Could not find category for PR entry with entry_id: ${entryId}`);
                                console.warn(`Category map keys:`, Array.from(categoryMap.keys()));
                                console.warn(`Category map entries:`, Array.from(categoryMap.entries()));
                            } else {
                                console.log(`✓ Found category "${deductedFrom}" for PR entry (entry_id: ${entryId})`);
                            }
                        }

                        prEntries.push({
                            id: entry.id, // Include ID for validation
                            purchaseRequest: entry.purchase_request || '',
                            particulars: entry.particulars || '',
                            prNumber: (entry.pr_number || '') + (entry.po_number ? ' / ' + entry.po_number : ''),
                            date: entry.date || '',
                            amount: amountValue,
                            deductedFrom: deductedFrom
                        });
                    });
                }
            } catch (e) {
                console.error('Error loading PR from database:', e);
            }

            // Collect Travels Data from database (same approach as Purchase Requests)
            // Note: categoryMap is already created above and will be used for travels too
            // Load directly from database FIRST - this ensures all saved travels appear even if modal was never opened
            let travelsEntries = [];
            let travelsTotal = 0;

            // Load from database
            try {
                const travelsUrl = `../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`;
                console.log(`🔄 [GENERATE SUMMARY] Loading travels from database...`);
                console.log(`   Department ID: ${departmentId}`);
                console.log(`   Fiscal Year: ${CURRENT_FISCAL_YEAR}`);
                console.log(`   API URL: ${travelsUrl}`);

                const travelsResponse = await fetch(travelsUrl);

                console.log(`   Response Status: ${travelsResponse.status} ${travelsResponse.statusText}`);
                console.log(`   Response OK: ${travelsResponse.ok}`);

                // Check response status and handle errors properly
                let travelsData = null;

                if (!travelsResponse.ok) {
                    console.error(`❌ [GENERATE SUMMARY] Travels API HTTP error: ${travelsResponse.status} ${travelsResponse.statusText}`);
                    // Try to parse error response, but don't fail completely
                    try {
                        const errorText = await travelsResponse.text();
                        console.error('   Error response text:', errorText);
                        // Try to parse as JSON if possible
                        try {
                            travelsData = JSON.parse(errorText);
                            console.error('   Parsed error JSON:', JSON.stringify(travelsData, null, 2));
                        } catch (parseError) {
                            // If not JSON, create default error structure
                            travelsData = { success: false, entries: [], message: errorText };
                            console.error('   Could not parse as JSON, using default error structure');
                        }
                    } catch (e) {
                        console.error('   Failed to parse travels error response:', e);
                        travelsData = { success: false, entries: [] };
                    }
                } else {
                    try {
                        const responseText = await travelsResponse.text();
                        console.log('   Raw response text length:', responseText.length);
                        console.log('   Raw response preview:', responseText.substring(0, 200));

                        travelsData = JSON.parse(responseText);
                        console.log('📥 [GENERATE SUMMARY] Travels API Response:');
                        console.log('   Success:', travelsData.success);
                        console.log('   Entries count:', travelsData.entries ? travelsData.entries.length : 'null/undefined');
                        if (travelsData.entries && travelsData.entries.length > 0) {
                            console.log('   First entry sample:', JSON.stringify(travelsData.entries[0], null, 2));
                        }
                        console.log('   Full response:', JSON.stringify(travelsData, null, 2));
                    } catch (e) {
                        console.error('❌ [GENERATE SUMMARY] Failed to parse travels JSON response:', e);
                        console.error('   Error details:', e.message);
                        console.error('   Error stack:', e.stack);
                        travelsData = { success: false, entries: [] };
                    }
                }

                // Process travels data if available
                if (travelsData && travelsData.success && travelsData.entries && Array.isArray(travelsData.entries)) {
                    const travelCount = travelsData.entries.length;
                    console.log(`✅✅✅ Found ${travelCount} travel entries in database - WILL ADD TO SUMMARY`);

                    if (travelCount > 0) {
                        travelsData.entries.forEach((entry, index) => {
                            // Parse amount - ensure it's a valid number
                            let amountValue = 0;
                            if (entry.amount !== null && entry.amount !== undefined && entry.amount !== '') {
                                const amountStr = String(entry.amount).replace(/[₱,\s]/g, '');
                                amountValue = parseFloat(amountStr) || 0;
                            }

                            // Get category name for deductedFrom field using the category map
                            let deductedFrom = '';
                            if (entry.entry_id) {
                                // Try multiple matching strategies
                                const entryId = parseInt(entry.entry_id);
                                deductedFrom = categoryMap.get(entryId) || '';

                                // If not found, try as string
                                if (!deductedFrom) {
                                    deductedFrom = categoryMap.get(String(entryId)) || '';
                                }

                                // If still not found, try to find by searching all utilization entries
                                if (!deductedFrom) {
                                    utilizationRows.forEach(row => {
                                        const rowEntryId = row.id.split('_')[1];
                                        const rowDbEntryId = row.getAttribute('data-db-entry-id');
                                        const rowDeductedFromEntryId = row.getAttribute('data-deducted-from-entry-id');
                                        const columnArea = document.getElementById(`columnArea_${rowEntryId}`);

                                        if (columnArea && columnArea.value) {
                                            // Check if this row matches the entry_id
                                            if ((rowDbEntryId && parseInt(rowDbEntryId) === entryId) ||
                                                (rowDeductedFromEntryId && parseInt(rowDeductedFromEntryId) === entryId)) {
                                                deductedFrom = columnArea.value;
                                            }
                                        }
                                    });
                                }
                            }

                            // Add to travels array - ADD ALL ENTRIES from database
                            const travelEntry = {
                                id: entry.id, // Include ID for validation
                                travelled: entry.travelled || '',
                                event_activity: entry.event_activity || '',
                                event: entry.event_activity || '', // Map event_activity to event for compatibility
                                date: entry.date || '',
                                amount: amountValue,
                                deductedFrom: deductedFrom || ''
                            };

                            // ALWAYS add - database is source of truth
                            travelsEntries.push(travelEntry);
                            travelsTotal += amountValue;

                            console.log(`  ✓✓✓ [${index + 1}/${travelCount}] ADDED Travel: "${travelEntry.travelled}" | Amount: ₱${amountValue.toFixed(2)} | Category: "${travelEntry.deductedFrom || 'Not assigned'}"`);
                        });

                        console.log(`✅ Successfully loaded ${travelsEntries.length} travels, Total: ₱${travelsTotal.toFixed(2)}`);
                    } else {
                        console.log('⚠ No travel entries in database for this department/year');
                    }
                } else if (travelsData && !travelsData.success) {
                    console.warn('⚠ Travels API returned error:', travelsData.message || 'Unknown error');
                    console.warn('Full response:', JSON.stringify(travelsData, null, 2));
                } else {
                    console.warn('⚠ No travel entries found in database or invalid response structure');
                    console.warn('travelsData:', travelsData);
                }
            } catch (e) {
                console.error('❌ Exception loading Travels from database:', e);
                console.error('Error stack:', e.stack);
            }

            // STEP 2: Use a Map to track entries and avoid duplicates
            // Key format: travelled_event_date_amount to identify unique entries
            const travelsMap = new Map();

            // Add database entries to map first (they take priority)
            travelsEntries.forEach(entry => {
                const key = `${entry.travelled || ''}_${entry.event || ''}_${entry.date || ''}_${entry.amount || 0}`;
                travelsMap.set(key, entry);
            });

            // STEP 3: If no travels found in database, check localStorage for saved travels
            // This handles the case where travels were saved to localStorage but not yet synced to database
            if (travelsMap.size === 0) {
                console.log('🔍 No travels in database, checking localStorage...');
                try {
                    const localTravels = loadTravelsFromLocalStorage(departmentId);
                    if (localTravels && localTravels.length > 0) {
                        console.log(`📦 Found ${localTravels.length} travel entries in localStorage`);
                        localTravels.forEach((entry, index) => {
                            if (entry && (entry.travelled || entry.event || entry.amount)) {
                                const amountValue = parseAmount(entry.amount || '0');

                                // Try to get deductedFrom from multiple sources
                                // Check all possible field names: deductedFromEntryId (camelCase), deducted_from_entry_id (snake_case), deductFromEntryId
                                let deductedFrom = '';
                                let deductedFromEntryId = entry.deductedFromEntryId ||
                                    entry.deducted_from_entry_id ||
                                    entry.deductFromEntryId ||
                                    null;

                                console.log(`  🔍 Entry ${index + 1} deductedFromEntryId:`, deductedFromEntryId);

                                // First, try from entry data using the entry ID
                                if (deductedFromEntryId) {
                                    // Convert to string for comparison
                                    const entryIdStr = String(deductedFromEntryId);

                                    // Try direct DOM lookup
                                    const deductedFromInput = document.getElementById(`columnArea_${entryIdStr}`);
                                    if (deductedFromInput && deductedFromInput.value) {
                                        deductedFrom = deductedFromInput.value.trim();
                                        console.log(`    ✓ Found category from DOM: "${deductedFrom}"`);
                                    } else {
                                        // If not found, try to find by iterating through all utilization entries
                                        const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
                                        utilizationRows.forEach(row => {
                                            const utilEntryId = row.id.split('_')[1];
                                            if (String(utilEntryId) === entryIdStr) {
                                                const categoryInput = document.getElementById(`columnArea_${utilEntryId}`);
                                                if (categoryInput && categoryInput.value) {
                                                    deductedFrom = categoryInput.value.trim();
                                                    console.log(`    ✓ Found category from utilization entry: "${deductedFrom}"`);
                                                }
                                            }
                                        });
                                    }
                                }

                                if (!deductedFrom && deductedFromEntryId) {
                                    console.log(`    ⚠ Could not find category for entry ID: ${deductedFromEntryId}`);
                                    console.log(`    🔍 Attempting alternative lookup methods...`);

                                    // Try to find by checking all select options in deductFrom dropdowns
                                    const allDeductFromSelects = document.querySelectorAll('[id^="travelDeductFrom_"]');
                                    allDeductFromSelects.forEach(select => {
                                        if (select.value === deductedFromEntryId) {
                                            const entryId = select.id.split('_')[1];
                                            const categoryInput = document.getElementById(`columnArea_${deductedFromEntryId}`);
                                            if (categoryInput && categoryInput.value) {
                                                deductedFrom = categoryInput.value.trim();
                                                console.log(`    ✓ Found category via alternative method: "${deductedFrom}"`);
                                            }
                                        }
                                    });

                                    // Last resort: try to get from any utilization entry that matches
                                    if (!deductedFrom) {
                                        const allUtilRows = document.querySelectorAll('[id^="entryRow_"]');
                                        allUtilRows.forEach(row => {
                                            const rowEntryId = row.id.split('_')[1];
                                            if (String(rowEntryId) === String(deductedFromEntryId)) {
                                                const catInput = document.getElementById(`columnArea_${rowEntryId}`);
                                                if (catInput && catInput.value) {
                                                    deductedFrom = catInput.value.trim();
                                                    console.log(`    ✓ Found category via utilization row match: "${deductedFrom}"`);
                                                }
                                            }
                                        });
                                    }
                                }

                                const key = `${entry.travelled || ''}_${entry.event || entry.event_activity || ''}_${entry.date || ''}_${amountValue}`;

                                // Only add if not already in map (avoid duplicates)
                                if (!travelsMap.has(key)) {
                                    travelsMap.set(key, {
                                        travelled: entry.travelled || '',
                                        event: entry.event || entry.event_activity || '',
                                        date: entry.date || '',
                                        amount: amountValue,
                                        deductedFrom: deductedFrom
                                    });
                                    console.log(`  ✓✓✓ [${index + 1}/${localTravels.length}] Added from localStorage: "${entry.travelled || 'N/A'}" | Amount: ₱${amountValue.toFixed(2)} | Category: "${deductedFrom || 'Not assigned'}"`);
                                } else {
                                    console.log(`  ⊗ Skipped duplicate from localStorage: "${entry.travelled || 'N/A'}"`);
                                }
                            }
                        });
                        console.log(`✅ Loaded ${travelsMap.size} unique travels from localStorage`);
                    }
                } catch (e) {
                    console.error('Error loading travels from localStorage:', e);
                }
            }

            // STEP 4: Check DOM for any UNSAVED entries (only if modal was opened)
            // These are entries that were added but not yet saved to database or localStorage
            const travelRows = document.querySelectorAll('[id^="travelRow_"]');
            if (travelRows.length > 0) {
                console.log(`🔍 Checking ${travelRows.length} DOM entries for unsaved travels...`);
                travelRows.forEach(row => {
                    const entryId = row.id.split('_')[1];
                    const travelId = row.getAttribute('data-travel-id');

                    // Only process if this is an UNSAVED entry (no database ID)
                    if (!travelId) {
                        const travelled = document.getElementById(`travelTravelled_${entryId}`)?.value || '';
                        const eventInput = document.getElementById(`travelEvent_${entryId}`);
                        let event = '';
                        if (eventInput) {
                            event = eventInput.getAttribute('data-full-text') || eventInput.title || eventInput.value || '';
                        }
                        const date = document.getElementById(`travelDate_${entryId}`)?.value || '';
                        const amountInput = document.getElementById(`travelAmount_${entryId}`);
                        // Parse amount to get raw numeric value (remove ₱ and commas)
                        const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;
                        const deductFromSelect = document.getElementById(`travelDeductFrom_${entryId}`);
                        const deductedFromEntryId = deductFromSelect ? (deductFromSelect.value || null) : null;

                        // Only add if entry has actual data
                        if (travelled || event || amount > 0) {
                            const amountValue = amount; // Already parsed

                            let deductedFrom = '';
                            if (deductedFromEntryId) {
                                const deductedFromInput = document.getElementById(`columnArea_${deductedFromEntryId}`);
                                if (deductedFromInput) {
                                    deductedFrom = deductedFromInput.value || '';
                                }
                            }

                            const key = `${travelled}_${event}_${date}_${amountValue}`;

                            // Only add if not already in map (avoid duplicates with localStorage)
                            if (!travelsMap.has(key)) {
                                travelsMap.set(key, {
                                    travelled: travelled,
                                    event: event,
                                    date: date,
                                    amount: amountValue,
                                    deductedFrom: deductedFrom
                                });
                                console.log(`  ✓ Added unsaved DOM entry: "${travelled}" - ₱${amountValue.toFixed(2)} | Category: "${deductedFrom || 'Not assigned'}"`);
                            } else {
                                console.log(`  ⊗ Skipped duplicate from DOM: "${travelled}"`);
                            }
                        }
                    }
                });
            }

            // Convert map back to array and recalculate total
            travelsEntries = [];
            travelsTotal = 0;
            travelsMap.forEach(entry => {
                travelsEntries.push(entry);
                travelsTotal += entry.amount;
            });

            // Final summary
            console.log(`📊 FINAL: ${travelsEntries.length} travel entries, Total: ₱${travelsTotal.toFixed(2)}`);
            if (travelsEntries.length > 0) {
                console.log('Travel entries to display in summary:');
                travelsEntries.forEach((entry, idx) => {
                    console.log(`  ${idx + 1}. "${entry.travelled}" | "${entry.event}" | ${entry.date} | ₱${entry.amount.toFixed(2)} | "${entry.deductedFrom}"`);
                });
            } else {
                console.warn('⚠ WARNING: No travel entries found! Check database for department:', departmentId);
            }

            // Collect Honoraria Data from database (same approach as Purchase Requests and Travels)
            let honorariaEntries = [];
            let honorariaTotal = 0;

            // Load from database
            try {
                const honorariaResponse = await fetch(`../api/load_honoraria.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`);
                const honorariaData = await honorariaResponse.json();

                if (honorariaData.success && honorariaData.entries && honorariaData.entries.length > 0) {
                    honorariaData.entries.forEach(entry => {
                        const amountValue = parseFloat(entry.amount || 0);
                        if (amountValue > 0) {
                            honorariaTotal += amountValue;

                            // Format date - convert YYYY-MM to readable format if needed
                            let entryDate = entry.date || '';
                            if (entryDate) {
                                entryDate = entryDate.trim();
                                // If it's YYYY-MM format, keep it as is
                                if (entryDate.match(/^\d{4}-\d{2}$/)) {
                                    // Format as "Month Year" (e.g., "January 2026")
                                    const [year, month] = entryDate.split('-');
                                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                        'July', 'August', 'September', 'October', 'November', 'December'];
                                    const monthName = monthNames[parseInt(month) - 1] || month;
                                    entryDate = `${monthName} ${year}`;
                                } else if (entryDate.length > 7) {
                                    // If it's a full date, convert to month format
                                    entryDate = entryDate.slice(0, 7);
                                    const [year, month] = entryDate.split('-');
                                    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                                        'July', 'August', 'September', 'October', 'November', 'December'];
                                    const monthName = monthNames[parseInt(month) - 1] || month;
                                    entryDate = `${monthName} ${year}`;
                                }
                            }

                            honorariaEntries.push({
                                id: entry.id, // Include ID for validation
                                date: entryDate,
                                amount: amountValue
                            });
                        }
                    });
                }
            } catch (e) {
                console.error('Error loading Honoraria from database:', e);
            }

            console.log(`📊 FINAL: ${honorariaEntries.length} Honoraria entries, Total: ₱${honorariaTotal.toFixed(2)}`);

            // Determine if it's a department or office
            const deptSelect = document.getElementById('departmentSelect');
            const offSelect = document.getElementById('officeSelect');
            const isDepartment = deptSelect && deptSelect.value === departmentId;
            const isOffice = offSelect && offSelect.value === departmentId;

            // Display Summary Modal
            displaySummaryModal(departmentName, utilizationEntries, prEntries, travelsEntries, honorariaEntries, {
                totalAllocated: totalAllocated,
                totalDeductions: totalDeductions,
                totalBalance: totalBalance,
                prTotal: prTotal,
                travelsTotal: travelsTotal,
                honorariaTotal: honorariaTotal
            }, isDepartment, isOffice);
        }

        function displaySummaryModal(departmentName, utilizationEntries, prEntries, travelsEntries, honorariaEntries, totals, isDepartment = false, isOffice = false) {
            const modal = document.getElementById('summaryModal');
            const modalContent = document.getElementById('summaryModalContent');
            const summaryDeptName = document.getElementById('summaryDepartmentName');
            const summaryDeptNameReceipt = document.getElementById('summaryDepartmentNameReceipt');
            const summaryDate = document.getElementById('summaryDate');

            if (!modal || !modalContent) return;

            // Determine label based on whether it's a department or office
            let label = 'Department/Office';
            if (isDepartment) {
                label = 'Department';
            } else if (isOffice) {
                label = 'Office';
            }

            // Set department name with proper label
            if (summaryDeptName) {
                summaryDeptName.textContent = `${label}: ${departmentName}`;
            }
            if (summaryDeptNameReceipt) {
                summaryDeptNameReceipt.textContent = `${label}: ${departmentName}`;
            }

            // Set date
            if (summaryDate) {
                const now = new Date();
                summaryDate.textContent = now.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            }

            // Collect deduction breakdowns for saving (aggregate by category)
            const prDeductionsMap = new Map(); // category -> {items: [], totalAmount: 0}
            const travelsDeductionsMap = new Map();
            const honorariaDeductionsMap = new Map();

            // Get department ID for deduction sources
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            // Create a map of PR entries by ID for quick lookup
            const prEntriesMap = new Map();
            prEntries.forEach(pr => {
                if (pr.id) {
                    prEntriesMap.set(String(pr.id), pr);
                }
            });

            if (departmentId) {
                const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
                utilizationRows.forEach(row => {
                    const entryId = row.id.split('_')[1];
                    const columnArea = document.getElementById(`columnArea_${entryId}`);
                    const categoryName = columnArea ? columnArea.value : `ENTRY ${entryId}`;

                    // Load deduction sources for this entry
                    const deductionSourcesKey = getDeductionSourcesKey(departmentId, entryId);
                    const saved = localStorage.getItem(deductionSourcesKey);

                    if (saved) {
                        try {
                            const deductionSources = JSON.parse(saved);
                            deductionSources.forEach(ds => {
                                if (ds.amount > 0) {
                                    if (ds.sourceType === 'purchase_request') {
                                        // Get or create category entry
                                        if (!prDeductionsMap.has(categoryName)) {
                                            prDeductionsMap.set(categoryName, {items: [], totalAmount: 0});
                                        }
                                        const categoryData = prDeductionsMap.get(categoryName);
                                        
                                        // Add PR item details from entries array
                                        if (ds.entries && ds.entries.length > 0) {
                                            ds.entries.forEach(entry => {
                                                // Look up the actual PR data from prEntriesMap
                                                const prData = prEntriesMap.get(String(entry.sourceEntryId));
                                                const purchaseRequestText = prData ? (prData.purchaseRequest || prData.purchase_request || 'N/A') : 'N/A';
                                                
                                                // Check if this is a combined PPMP item (contains numbered list)
                                                if (purchaseRequestText.includes('\n') && /^\d+\./.test(purchaseRequestText.trim())) {
                                                    // Split combined items into individual lines
                                                    const individualItems = purchaseRequestText.split('\n').filter(line => line.trim());
                                                    const itemAmount = entry.amount / individualItems.length; // Distribute amount evenly
                                                    
                                                    individualItems.forEach(item => {
                                                        categoryData.items.push({
                                                            purchaseRequest: item.trim(),
                                                            amount: itemAmount
                                                        });
                                                    });
                                                } else {
                                                    // Single item
                                                    categoryData.items.push({
                                                        purchaseRequest: purchaseRequestText,
                                                        amount: entry.amount
                                                    });
                                                }
                                                categoryData.totalAmount += entry.amount;
                                            });
                                        } else {
                                            // Fallback if no entries array
                                            categoryData.items.push({
                                                purchaseRequest: 'N/A',
                                                amount: ds.amount
                                            });
                                            categoryData.totalAmount += ds.amount;
                                        }
                                    } else if (ds.sourceType === 'travels') {
                                        if (!travelsDeductionsMap.has(categoryName)) {
                                            travelsDeductionsMap.set(categoryName, { items: [], totalAmount: 0 });
                                        }
                                        const catData = travelsDeductionsMap.get(categoryName);
                                        if (ds.entries && ds.entries.length > 0) {
                                            ds.entries.forEach(tEntry => {
                                                // Look up travel details from travelsEntries
                                                const travelDetail = travelsEntries.find(t =>
                                                    String(t.id || t.travel_id || '') === String(tEntry.sourceEntryId) ||
                                                    (t.amount === tEntry.amount)
                                                );
                                                catData.items.push({
                                                    travelled: travelDetail ? (travelDetail.travelled || '-') : '-',
                                                    event: travelDetail ? (travelDetail.event || travelDetail.event_activity || '-') : '-',
                                                    date: travelDetail ? (travelDetail.date || '-') : '-',
                                                    amount: tEntry.amount
                                                });
                                                catData.totalAmount += tEntry.amount;
                                            });
                                        } else {
                                            catData.items.push({ travelled: '-', event: '-', date: '-', amount: ds.amount });
                                            catData.totalAmount += ds.amount;
                                        }
                                    } else if (ds.sourceType === 'honoraria') {
                                        const current = honorariaDeductionsMap.get(categoryName) || 0;
                                        honorariaDeductionsMap.set(categoryName, current + ds.amount);
                                    }
                                }
                            });
                        } catch (e) {
                            console.error('Error parsing deduction sources:', e);
                        }
                    }
                });
            }

            // Convert maps to arrays
            const prDeductions = Array.from(prDeductionsMap.entries()).map(([category, data]) => ({
                category: category,
                items: data.items,
                amount: data.totalAmount
            }));
            const travelsDeductions = Array.from(travelsDeductionsMap.entries()).map(([category, data]) => ({
                category: category,
                items: data.items,
                amount: data.totalAmount
            }));
            const honorariaDeductions = Array.from(honorariaDeductionsMap.entries()).map(([category, amount]) => ({
                category: category,
                amount: amount
            }));

            // Debug logging
            console.log('Deduction data to save:', {
                prDeductions,
                travelsDeductions,
                honorariaDeductions
            });

            // Store summary data for saving
            window.currentSummaryData = {
                departmentName: departmentName,
                utilizationEntries: utilizationEntries,
                prEntries: prEntries,
                travelsEntries: travelsEntries,
                honorariaEntries: honorariaEntries,
                prDeductions: prDeductions,
                travelsDeductions: travelsDeductions,
                honorariaDeductions: honorariaDeductions,
                totals: totals
            };

            // Populate Budget Utilization Table
            const utilizationBody = document.getElementById('summaryUtilizationBody');
            if (utilizationBody) {
                utilizationBody.innerHTML = '';

                if (utilizationEntries.length === 0) {
                    utilizationBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-4 px-3 text-center text-gray-500 italic">
                        No budget utilization entries found
                    </td>
                </tr>
            `;
                } else {
                    utilizationEntries.forEach(entry => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                    <td class="py-2 px-3 text-gray-900">${entry.category || '-'}</td>
                    <td class="py-2 px-3 text-gray-700">${entry.accountCode || '-'}</td>
                    <td class="py-2 px-3 text-right text-gray-900">${formatNumber(entry.allocated)}</td>
                    <td class="py-2 px-3 text-right text-red-600">${formatNumber(entry.deduction)}</td>
                    <td class="py-2 px-3 text-right font-bold ${entry.balance < 0 ? 'text-red-600' : 'text-green-600'}">${formatNumber(entry.balance)}</td>
                `;
                        utilizationBody.appendChild(row);
                    });
                }
            }

            // Update Utilization Totals
            const summaryTotalAllocated = document.getElementById('summaryTotalAllocated');
            const summaryTotalDeductions = document.getElementById('summaryTotalDeductions');
            const summaryTotalBalance = document.getElementById('summaryTotalBalance');

            if (summaryTotalAllocated) summaryTotalAllocated.textContent = formatNumber(totals.totalAllocated);
            if (summaryTotalDeductions) summaryTotalDeductions.textContent = formatNumber(totals.totalDeductions);
            if (summaryTotalBalance) {
                summaryTotalBalance.textContent = formatNumber(totals.totalBalance);
                summaryTotalBalance.classList.remove('text-green-600', 'text-red-600');
                summaryTotalBalance.classList.add(totals.totalBalance < 0 ? 'text-red-600' : 'text-green-600');
            }

            // Populate Purchase Requests Table
            const prBody = document.getElementById('summaryPRBody');
            if (prBody) {
                prBody.innerHTML = '';

                if (prEntries.length === 0) {
                    prBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-4 px-3 text-center text-gray-500 italic">
                        No purchase requests found
                    </td>
                </tr>
            `;
                } else {
                    prEntries.forEach(entry => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                            <td class="py-2 px-3 text-gray-900 whitespace-normal break-words">${entry.purchaseRequest || '-'}</td>
                            <td class="py-2 px-3 text-gray-700 whitespace-normal break-words">${entry.particulars || '-'}</td>
                            <td class="py-2 px-3 text-gray-700">${entry.prNumber || '-'}</td>
                            <td class="py-2 px-3 text-gray-700">${entry.date || '-'}</td>
                            <td class="py-2 px-3 text-right text-blue-600">${formatNumber(entry.amount)}</td>
                        `;
                        prBody.appendChild(row);
                    });
                }
            }

            // Update PR Total
            const summaryPRTotal = document.getElementById('summaryPRTotal');
            if (summaryPRTotal) summaryPRTotal.textContent = formatNumber(totals.prTotal);

            // Populate Travels Table
            const travelsBody = document.getElementById('summaryTravelsBody');
            if (travelsBody) {
                travelsBody.innerHTML = '';

                if (travelsEntries.length === 0) {
                    travelsBody.innerHTML = `
                <tr>
                    <td colspan="4" class="py-4 px-3 text-center text-gray-500 italic">
                        No travels found
                    </td>
                </tr>
            `;
                } else {
                    travelsEntries.forEach(entry => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                    <td class="py-2 px-3 text-gray-900">${entry.travelled || '-'}</td>
                    <td class="py-2 px-3 text-gray-700">${(entry.event_activity || entry.event) ? ((entry.event_activity || entry.event).length > 50 ? (entry.event_activity || entry.event).substring(0, 50) + '...' : (entry.event_activity || entry.event)) : '-'}</td>
                    <td class="py-2 px-3 text-gray-700">${entry.date || '-'}</td>
                    <td class="py-2 px-3 text-right text-green-600">${formatNumber(entry.amount)}</td>
                `;
                        travelsBody.appendChild(row);
                    });
                }
            }

            // Update Travels Total
            const summaryTravelsTotal = document.getElementById('summaryTravelsTotal');
            if (summaryTravelsTotal) summaryTravelsTotal.textContent = formatNumber(totals.travelsTotal);

            // Populate Honoraria Table
            const honorariaBody = document.getElementById('summaryHonorariaBody');
            if (honorariaBody) {
                honorariaBody.innerHTML = '';

                if (honorariaEntries.length === 0) {
                    honorariaBody.innerHTML = `
                <tr>
                    <td colspan="2" class="py-4 px-3 text-center text-gray-500 italic">
                        No Honoraria entries found
                    </td>
                </tr>
            `;
                } else {
                    honorariaEntries.forEach(entry => {
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                    <td class="py-2 px-3 text-gray-700">${entry.date || '-'}</td>
                    <td class="py-2 px-3 text-right text-yellow-600">${formatNumber(entry.amount)}</td>
                `;
                        honorariaBody.appendChild(row);
                    });
                }
            }

            // Update Honoraria Total
            const summaryHonorariaTotal = document.getElementById('summaryHonorariaTotal');
            if (summaryHonorariaTotal) summaryHonorariaTotal.textContent = formatNumber(totals.honorariaTotal || 0);

            // Display Deduction Sources (using data already collected and stored in window.currentSummaryData)
            // The deduction data was already collected above and stored in window.currentSummaryData
            const prDeductionsDisplay = window.currentSummaryData ? window.currentSummaryData.prDeductions || [] : [];
            const travelsDeductionsDisplay = window.currentSummaryData ? window.currentSummaryData.travelsDeductions || [] : [];
            const honorariaDeductionsDisplay = window.currentSummaryData ? window.currentSummaryData.honorariaDeductions || [] : [];

            // Populate Purchase Request Deductions Table
            const prDeductionsBody = document.getElementById('summaryPRDeductionsBody');
            if (prDeductionsBody) {
                prDeductionsBody.innerHTML = '';

                if (prDeductionsDisplay.length === 0) {
                    prDeductionsBody.innerHTML = `
                <tr>
                    <td colspan="3" class="py-4 px-3 text-center text-gray-500 italic">
                        No purchase request deductions found
                    </td>
                </tr>
            `;
                } else {
                    let prDeductionsTotal = 0;
                    prDeductionsDisplay.forEach(entry => {
                        prDeductionsTotal += entry.amount;
                        
                        // If there are multiple items, create rows for each
                        if (entry.items && entry.items.length > 0) {
                            entry.items.forEach((item, index) => {
                                const row = document.createElement('tr');
                                row.className = 'border-b border-gray-200';
                                
                                // Only show category name on first row
                                if (index === 0) {
                                    row.innerHTML = `
                                        <td class="py-2 px-3 text-gray-900 font-semibold align-top" rowspan="${entry.items.length}">${entry.category || '-'}</td>
                                        <td class="py-2 px-3 text-gray-700 whitespace-normal break-words">${item.purchaseRequest || '-'}</td>
                                        <td class="py-2 px-3 text-right text-blue-600 align-top font-semibold" rowspan="${entry.items.length}">${formatNumber(entry.amount)}</td>
                                    `;
                                } else {
                                    row.innerHTML = `
                                        <td class="py-2 px-3 text-gray-700 whitespace-normal break-words">${item.purchaseRequest || '-'}</td>
                                    `;
                                }
                                prDeductionsBody.appendChild(row);
                            });
                        } else {
                            // Fallback for entries without items array
                            const row = document.createElement('tr');
                            row.className = 'border-b border-gray-200';
                            row.innerHTML = `
                                <td class="py-2 px-3 text-gray-900">${entry.category || '-'}</td>
                                <td class="py-2 px-3 text-gray-700">-</td>
                                <td class="py-2 px-3 text-right text-blue-600">${formatNumber(entry.amount)}</td>
                            `;
                            prDeductionsBody.appendChild(row);
                        }
                    });

                    const prDeductionsTotalEl = document.getElementById('summaryPRDeductionsTotal');
                    if (prDeductionsTotalEl) {
                        prDeductionsTotalEl.textContent = formatNumber(prDeductionsTotal);
                    }
                }
            }

            // Populate Travels Deductions Table
            const travelsDeductionsBody = document.getElementById('summaryTravelsDeductionsBody');
            if (travelsDeductionsBody) {
                travelsDeductionsBody.innerHTML = '';

                if (travelsDeductionsDisplay.length === 0) {
                    travelsDeductionsBody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-4 px-3 text-center text-gray-500 italic">
                        No travels deductions found
                    </td>
                </tr>
            `;
                } else {
                    let travelsDeductionsTotal = 0;
                    travelsDeductionsDisplay.forEach(entry => {
                        travelsDeductionsTotal += entry.amount;
                        const items = entry.items && entry.items.length > 0 ? entry.items : [{ travelled: '-', event: '-', date: '-', amount: entry.amount }];
                        items.forEach((item, idx) => {
                            const row = document.createElement('tr');
                            row.className = 'border-b border-gray-200';
                            if (idx === 0) {
                                row.innerHTML = `
                                    <td class="py-2 px-3 text-gray-900 font-semibold align-top" rowspan="${items.length}">${entry.category || '-'}</td>
                                    <td class="py-2 px-3 text-gray-700">${item.travelled || '-'}</td>
                                    <td class="py-2 px-3 text-gray-700">${item.event || '-'}</td>
                                    <td class="py-2 px-3 text-gray-700">${item.date || '-'}</td>
                                    <td class="py-2 px-3 text-right text-green-600">${formatNumber(item.amount)}</td>
                                `;
                            } else {
                                row.innerHTML = `
                                    <td class="py-2 px-3 text-gray-700">${item.travelled || '-'}</td>
                                    <td class="py-2 px-3 text-gray-700">${item.event || '-'}</td>
                                    <td class="py-2 px-3 text-gray-700">${item.date || '-'}</td>
                                    <td class="py-2 px-3 text-right text-green-600">${formatNumber(item.amount)}</td>
                                `;
                            }
                            travelsDeductionsBody.appendChild(row);
                        });
                    });

                    const travelsDeductionsTotalEl = document.getElementById('summaryTravelsDeductionsTotal');
                    if (travelsDeductionsTotalEl) {
                        travelsDeductionsTotalEl.textContent = formatNumber(travelsDeductionsTotal);
                    }
                }
            }

            // Populate Honoraria Deductions Table
            const honorariaDeductionsBody = document.getElementById('summaryHonorariaDeductionsBody');
            if (honorariaDeductionsBody) {
                honorariaDeductionsBody.innerHTML = '';

                if (honorariaDeductionsDisplay.length === 0) {
                    honorariaDeductionsBody.innerHTML = `
                <tr>
                    <td colspan="2" class="py-4 px-3 text-center text-gray-500 italic">
                        No honoraria deductions found
                    </td>
                </tr>
            `;
                } else {
                    let honorariaDeductionsTotal = 0;
                    honorariaDeductionsDisplay.forEach(entry => {
                        honorariaDeductionsTotal += entry.amount;
                        const row = document.createElement('tr');
                        row.className = 'border-b border-gray-200';
                        row.innerHTML = `
                    <td class="py-2 px-3 text-gray-900">${entry.category || '-'}</td>
                    <td class="py-2 px-3 text-right text-yellow-600">${formatNumber(entry.amount)}</td>
                `;
                        honorariaDeductionsBody.appendChild(row);
                    });

                    const honorariaDeductionsTotalEl = document.getElementById('summaryHonorariaDeductionsTotal');
                    if (honorariaDeductionsTotalEl) {
                        honorariaDeductionsTotalEl.textContent = formatNumber(honorariaDeductionsTotal);
                    }
                }
            }

            // Update Overall Summary - Use Total Balance instead of calculated remaining balance
            const overallTotal = document.getElementById('summaryOverallTotal');

            if (overallTotal) {
                // Use the Total Balance from Budget Utilization Breakdown
                overallTotal.textContent = formatNumber(totals.totalBalance);
                overallTotal.classList.remove('text-red-600', 'text-maroon');
                overallTotal.classList.add(totals.totalBalance < 0 ? 'text-red-600' : 'text-maroon');
            }

            // Show modal with animation
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.add('opacity-100');
                modalContent.classList.remove('scale-95', 'opacity-0');
                modalContent.classList.add('scale-100', 'opacity-100');
            }, 10);

            // Close on background click
            modal.onclick = function (e) {
                if (e.target === modal) {
                    closeSummaryModal();
                }
            };
        }

        function closeSummaryModal() {
            const modal = document.getElementById('summaryModal');
            const modalContent = document.getElementById('summaryModalContent');

            if (modal && modalContent) {
                // Fade out animation
                modal.classList.remove('opacity-100');
                modalContent.classList.remove('scale-100', 'opacity-100');
                modalContent.classList.add('scale-95', 'opacity-0');

                setTimeout(() => {
                    modal.classList.add('hidden');
                }, 300);
            }
        }

        function printSummary() {
            window.print();
        }

        function confirmAndSaveUtilizationSummary() {
            if (!window.currentSummaryData) {
                alert('No summary data available to save.');
                return;
            }

            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            if (confirm('Are you sure you want to save this budget utilization summary? The selected department/office will be notified.')) {
                saveUtilizationSummaryToDatabase();
            }
        }

        function saveUtilizationSummaryToDatabase() {
            if (!window.currentSummaryData) {
                alert('No summary data available to save.');
                console.error('No currentSummaryData found');
                return;
            }

            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                console.error('No department selected');
                return;
            }

            const summaryData = {
                department_id: departmentId,
                fiscal_year: CURRENT_FISCAL_YEAR,
                department_name: window.currentSummaryData.departmentName || '',
                utilization_entries: window.currentSummaryData.utilizationEntries || [],
                pr_entries: window.currentSummaryData.prEntries || [],
                travels_entries: window.currentSummaryData.travelsEntries || [],
                honoraria_entries: window.currentSummaryData.honorariaEntries || [],
                pr_deductions: window.currentSummaryData.prDeductions || [],
                travels_deductions: window.currentSummaryData.travelsDeductions || [],
                honoraria_deductions: window.currentSummaryData.honorariaDeductions || [],
                totals: window.currentSummaryData.totals || {}
            };

            console.log('Saving utilization summary:', summaryData);
            console.log('Fiscal Year:', CURRENT_FISCAL_YEAR);
            console.log('Department ID:', departmentId);

            fetch('../api/save_utilization_summary.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(summaryData)
            })
                .then(response => {
                    console.log('Response status:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        alert('Budget utilization summary saved successfully! The department/office has been notified.');
                        closeSummaryModal();
                        
                        // Reload the page to show the saved summary
                        console.log('Summary saved successfully, reloading page...');
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert('Error saving summary: ' + data.message);
                        console.error('Save failed:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error saving summary:', error);
                    alert('Error saving to database: ' + error.message);
                });
        }
        // Function to show/hide deduction menu
        function showDeductionMenu(entryId) {
            const menu = document.getElementById(`deductionMenu_${entryId}`);
            if (!menu) return;

            // Close all other menus first
            document.querySelectorAll('[id^="deductionMenu_"]').forEach(otherMenu => {
                if (otherMenu.id !== `deductionMenu_${entryId}`) {
                    otherMenu.classList.add('hidden');
                }
            });

            // Close all amount deduction menus
            document.querySelectorAll('[id^="amountDeductionMenu_"]').forEach(amountMenu => {
                amountMenu.classList.add('hidden');
            });

            // Toggle current menu
            menu.classList.toggle('hidden');
        }

        // Function to open Purchase Request modal (can be called from anywhere)
        function openPurchaseRequestModal() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            // Show modal
            const modal = document.getElementById('purchaseRequestModal');
            if (modal) {
                modal.classList.remove('hidden');

                // Load saved PR entries from database ONLY
                // Database is the single source of truth - don't load from localStorage
                loadPurchaseRequestEntries(departmentId);
            }
        }

        // Function to open Travels modal (can be called from anywhere)
        function openTravelsModal() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            // Show modal
            const modal = document.getElementById('travelsModal');
            if (modal) {
                modal.classList.remove('hidden');

                // Always reload from database first to get fresh IDs
                // This ensures we have the latest IDs after any saves
                loadTravelsEntries(departmentId);

                // Also load from localStorage and merge (only after database load completes)
                setTimeout(() => {
                    const localTravels = loadTravelsFromLocalStorage(departmentId);
                    if (localTravels.length > 0) {
                        // Get all currently loaded database entry IDs
                        const existingRows = document.querySelectorAll('[id^="travelRow_"]');
                        const loadedDatabaseIds = new Set();
                        existingRows.forEach(row => {
                            const travelId = row.getAttribute('data-travel-id');
                            if (travelId) {
                                loadedDatabaseIds.add(parseInt(travelId));
                            }
                        });

                        // Merge with database entries
                        localTravels.forEach(localEntry => {
                            // Check if entry already exists in database (by ID)
                            let exists = false;
                            if (localEntry.travelId) {
                                exists = loadedDatabaseIds.has(parseInt(localEntry.travelId));
                            }

                            // Also check if entry exists in table by comparing key fields
                            if (!exists) {
                                existingRows.forEach(row => {
                                    const rowId = row.id.split('_')[1];
                                    const travelled = document.getElementById(`travelTravelled_${rowId}`)?.value || '';
                                    const eventInput = document.getElementById(`travelEvent_${rowId}`);
                                    const event = eventInput ? (eventInput.getAttribute('data-full-text') || eventInput.title || eventInput.value || '') : '';
                                    const dateInput = document.getElementById(`travelDate_${rowId}`);
                                    const date = dateInput ? dateInput.value : '';

                                    // More comprehensive duplicate check
                                    if (travelled === (localEntry.travelled || '') &&
                                        event === (localEntry.event || '') &&
                                        date === (localEntry.date || '')) {
                                        exists = true;
                                    }
                                });
                            }

                            if (!exists) {
                                travelsCounter++;
                                const row = document.createElement('tr');
                                row.id = `travelRow_${travelsCounter}`;
                                row.className = 'hover:bg-gray-50 transition-colors';

                                // Format event display (truncate if needed)
                                let eventDisplay = localEntry.event || '';
                                let eventTitle = '';
                                if (eventDisplay.length > 50) {
                                    eventTitle = eventDisplay;
                                    eventDisplay = eventDisplay.substring(0, 50) + '...';
                                }

                                row.innerHTML = `
                            <td class="border-b border-gray-200 py-4 px-6">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="travelTravelled_${travelsCounter}" 
                                        class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium" 
                                        placeholder="Enter destination"
                                        onblur="truncateTravelField('travelTravelled_${travelsCounter}')"
                                        value="${(localEntry.travelled || '').replace(/"/g, '&quot;')}"
                                    >
                                    <button 
                                        onclick="openViewDetailsModal('Travelled', 'travelTravelled_${travelsCounter}')"
                                        class="absolute right-3 top-1/2 transform -translate-y-1/2 text-green-500 hover:text-green-700 transition-colors p-1"
                                        title="View full content"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                            <td class="border-b border-gray-200 py-4 px-6">
                                <div class="relative">
                                    <input 
                                        type="text" 
                                        id="travelEvent_${travelsCounter}" 
                                        class="w-full px-4 py-2.5 pr-10 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium cursor-pointer" 
                                        placeholder="Click to enter event/activity..."
                                        readonly
                                        onclick="openTravelEventModal(${travelsCounter})"
                                        value="${eventDisplay.replace(/"/g, '&quot;')}"
                                        title="${eventTitle.replace(/"/g, '&quot;')}"
                                        ${eventTitle ? `data-full-text="${eventTitle.replace(/"/g, '&quot;')}"` : ''}
                                    >
                                    <div class="absolute right-3 top-1/2 transform -translate-y-1/2 flex items-center gap-2">
                                        <button 
                                            onclick="openViewDetailsModal('Event/Activity', 'travelEvent_${travelsCounter}')"
                                            class="text-green-500 hover:text-green-700 transition-colors p-1"
                                            title="View full content"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                            </svg>
                                        </button>
                                        <button 
                                            onclick="openTravelEventModal(${travelsCounter})"
                                            class="text-green-500 hover:text-green-700 transition-colors p-1"
                                            title="Edit event/activity"
                                        >
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="border-b border-gray-200 py-4 px-6">
                                <input 
                                    type="date" 
                                    id="travelDate_${travelsCounter}" 
                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium" 
                                    value="${localEntry.date || ''}"
                                >
                            </td>
                            <td class="border-b border-gray-200 py-4 px-6">
                                <input 
                                    type="text" 
                                id="travelAmount_${travelsCounter}" 
                                class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium" 
                                value="${localEntry.amount ? formatNumber(parseAmount(localEntry.amount)) : '₱0.00'}"
                                placeholder="₱0.00"
                            >
                            </td>
                            <td class="border-b border-gray-200 py-4 px-6">
                                <select 
                                    id="travelDeductFrom_${travelsCounter}" 
                                    class="w-full px-4 py-2.5 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all bg-white text-gray-900 font-medium"
                                >
                                    <option value="">-- Select Expense Category --</option>
                                </select>
                            </td>
                            <td class="border-b border-gray-200 py-4 px-6 text-center">
                                <button 
                                    onclick="removeTravelsEntry(${travelsCounter})" 
                                    class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg flex items-center justify-center mx-auto"
                                    title="Remove entry"
                                >
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </td>
                        `;
                                const tbody = document.getElementById('travelsTableBody');
                                if (tbody) {
                                    tbody.appendChild(row);
                                }

                                // Setup amount input listener
                                setupTravelsAmountListener(`travelAmount_${travelsCounter}`);

                                // Setup auto-save for this entry
                                setupTravelsAutoSave(travelsCounter);
                            }
                        });
                        calculateTravelsTotal();
                    }
                }, 500);
            }
        }

        // Function to open Purchase Request modal for a specific deduction entry
        function openPurchaseRequestForDeduction(entryId) {
            // Close the deduction menu
            const menu = document.getElementById(`deductionMenu_${entryId}`);
            if (menu) menu.classList.add('hidden');

            // Store the entry ID so we can auto-select it in the PR modal
            window.currentDeductionEntryId = entryId;

            // Open Purchase Request modal
            openPurchaseRequestModal();
        }

        // Function to open Travels modal for a specific deduction entry
        function openTravelsForDeduction(entryId) {
            // Close the deduction menu
            const menu = document.getElementById(`deductionMenu_${entryId}`);
            if (menu) menu.classList.add('hidden');

            // Store the entry ID so we can auto-select it in the Travels modal
            window.currentDeductionEntryId = entryId;

            // Open Travels modal
            openTravelsModal();
        }

        // Function to show amount deduction menu (opens modal)
        function showAmountDeductionMenu(entryId) {
            // Close the deduction menu
            const menu = document.getElementById(`deductionMenu_${entryId}`);
            if (menu) menu.classList.add('hidden');

            // Store the entry ID for the modal
            currentAmountDeductionEntryId = entryId;

            // Load and display amount deduction entries
            loadAmountDeductionEntries(entryId);

            // Show the modal
            const modal = document.getElementById('amountDeductionModal');
            if (modal) {
                modal.classList.remove('hidden');
            }
        }

        // Function to close amount deduction modal
        // Function to load honoraria entries from database
        function loadHonorariaEntries(departmentId) {
            const tbody = document.getElementById('amountDeductionTableBody');
            if (!tbody) return;

            // Clear existing entries
            tbody.innerHTML = '';
            amountDeductionCounter = 0;

            // Load from database first
            fetch(`../api/load_honoraria.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.entries && data.entries.length > 0) {
                        // Filter out entries with 0 amount
                        const validEntries = data.entries.filter(entry => {
                            const amount = parseFloat(entry.amount || 0);
                            return amount > 0;
                        });

                        validEntries.forEach(entry => {
                            amountDeductionCounter++;
                            const row = document.createElement('tr');
                            row.id = `amountDeductionRow_${amountDeductionCounter}`;
                            row.className = 'hover:bg-gray-50 transition-colors';

                            // Store the database ID for updates/deletes
                            if (entry.id) {
                                row.setAttribute('data-honoraria-id', entry.id);
                            }

                            // Parse date (format: YYYY-MM)
                            let monthName = '';
                            let year = '';
                            if (entry.date && entry.date.match(/^\d{4}-\d{2}$/)) {
                                const [yearPart, monthPart] = entry.date.split('-');
                                year = yearPart;
                                const monthNum = parseInt(monthPart);
                                monthName = getMonthName(monthNum);
                            } else {
                                // Default to current month/year if no date
                                const now = new Date();
                                year = now.getFullYear().toString();
                                monthName = getMonthName(now.getMonth() + 1);
                            }

                            row.innerHTML = `
                        <td class="border-b border-gray-200 py-4 px-6">
                            <div class="flex gap-2">
                                <select 
                                    id="amountDeductionMonth_${amountDeductionCounter}" 
                                    class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm"
                                >
                                    ${['January', 'February', 'March', 'April', 'May', 'June',
                                    'July', 'August', 'September', 'October', 'November', 'December']
                                    .map(m => `<option value="${m}" ${m === monthName ? 'selected' : ''}>${m}</option>`)
                                    .join('')}
                                </select>
                                <input 
                                    type="number" 
                                    id="amountDeductionYear_${amountDeductionCounter}" 
                                    class="w-20 px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                                    value="${year}"
                                    min="2000"
                                    max="2100"
                                    placeholder="Year"
                                >
                            </div>
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6">
                            <input 
                                type="text" 
                                id="amountDeductionAmount_${amountDeductionCounter}" 
                                class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                                value="${formatNumber(entry.amount || 0)}"
                                placeholder="₱0.00"
                            >
                        </td>
                        <td class="border-b border-gray-200 py-4 px-6 text-center">
                            <button
                                onclick="deleteAmountDeductionEntry(${amountDeductionCounter})"
                                class="p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-md hover:shadow-lg"
                                title="Delete entry"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </td>
                    `;
                            tbody.appendChild(row);

                            // Setup amount input listener
                            // Setup amount input listeners
                            const amountInput = document.getElementById(`amountDeductionAmount_${amountDeductionCounter}`);
                            if (amountInput) {
                                setupAmountInputHandlers(amountInput);
                                amountInput.addEventListener('input', function () {
                                    formatCurrencyInput(this);
                                    calculateAmountDeductionTotal();
                                    // Save to database if in honoraria mode
                                    if (!currentAmountDeductionEntryId) {
                                        const departmentSelect = document.getElementById('departmentSelect');
                                        const officeSelect = document.getElementById('officeSelect');
                                        const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                                        if (departmentId) {
                                            setTimeout(() => {
                                                saveHonorariaToDatabase(departmentId);
                                            }, 500);
                                        }
                                    } else {
                                        saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
                                    }
                                });
                            }

                            // Setup year input listener
                            const yearInput = document.getElementById(`amountDeductionYear_${amountDeductionCounter}`);
                            if (yearInput) {
                                yearInput.addEventListener('focus', function (e) {
                                    e.stopPropagation();
                                });
                                yearInput.addEventListener('input', function (e) {
                                    e.stopPropagation();
                                    const year = this.value;
                                    if (year && year.match(/^\d{4}$/)) {
                                        // Auto-save when valid year is entered
                                        setTimeout(() => {
                                            if (!currentAmountDeductionEntryId) {
                                                saveHonorariaToDatabase(departmentId);
                                            } else {
                                                saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
                                            }
                                        }, 500);
                                    }
                                });
                                yearInput.addEventListener('blur', function (e) {
                                    e.stopPropagation();
                                });
                            }

                            // Setup month select listener
                            const monthSelect = document.getElementById(`amountDeductionMonth_${amountDeductionCounter}`);
                            if (monthSelect) {
                                monthSelect.addEventListener('change', function () {
                                    setTimeout(() => {
                                        if (!currentAmountDeductionEntryId) {
                                            saveHonorariaToDatabase(departmentId);
                                        } else {
                                            saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
                                        }
                                    }, 500);
                                });
                            }
                        });

                        calculateAmountDeductionTotal();
                        console.log('Loaded', data.entries.length, 'honoraria entries from database');
                    } else {
                        console.log('No honoraria entries found in database');
                    }
                })
                .catch(error => {
                    console.error('Error loading honoraria entries:', error);
                });
        }

        // Function to save honoraria entries to database
        function saveHonorariaToDatabase(departmentId) {
            if (!departmentId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            }
            if (!departmentId) return;

            const entries = [];
            const tbody = document.getElementById('amountDeductionTableBody');

            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const counter = row.id.replace('amountDeductionRow_', '');
                    const monthSelect = document.getElementById(`amountDeductionMonth_${counter}`);
                    const yearInput = document.getElementById(`amountDeductionYear_${counter}`);
                    const amountInput = document.getElementById(`amountDeductionAmount_${counter}`);

                    // Get date from month dropdown and year input
                    let date = '';
                    if (monthSelect && yearInput) {
                        const monthName = monthSelect.value || '';
                        const year = yearInput.value || '';

                        if (monthName && year && year.match(/^\d{4}$/)) {
                            const month = getMonthNumber(monthName);
                            if (month) {
                                date = formatDateString(parseInt(year), month);
                            }
                        }
                    }

                    const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;
                    const honorariaId = row.getAttribute('data-honoraria-id') || null;

                    // Only include entries with valid amount (> 0)
                    if (amount > 0) {
                        entries.push({
                            date: date || null,
                            amount: amount,
                            id: honorariaId
                        });
                    }
                });
            }

            // Don't save empty entries - this will clear the database if all entries are invalid

            // Save to database and return promise
            return fetch('../api/save_honoraria.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    department_id: departmentId,
                    entries: entries,
                    fiscal_year: CURRENT_FISCAL_YEAR
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        console.log('Honoraria entries saved to database successfully');
                        // Recalculate all deductions from database
                        return recalculateAllDeductions().then(() => {
                            // Update localStorage
                            saveDeductionsToLocalStorage(departmentId);
                            return data;
                        });
                    } else {
                        console.error('Error saving honoraria entries:', data.message);
                        throw new Error(data.message || 'Failed to save honoraria entries');
                    }
                })
                .catch(error => {
                    console.error('Error saving honoraria entries:', error);
                    throw error;
                });
        }

        function closeAmountDeductionModal() {
            // Set flag to prevent auto-saves from triggering during close
            window.isClosingHonorariaModal = true;

            // Simply close the modal - no saving, no reloading, no side effects
            // This preserves the checkbox state in the Select Source modal
            const modal = document.getElementById('amountDeductionModal');
            if (modal) {
                modal.classList.add('hidden');
            }

            currentAmountDeductionEntryId = null;

            // Reset flag after a short delay
            setTimeout(() => {
                window.isClosingHonorariaModal = false;
            }, 100);

            console.log('Honoraria modal closed (no changes made).');
        }

        // Helper function to get month name from number (1-12)
        function getMonthName(monthNumber) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            return months[monthNumber - 1] || months[0];
        }

        // Helper function to get month number from name
        function getMonthNumber(monthName) {
            const months = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'];
            return months.indexOf(monthName) + 1;
        }

        // Helper function to convert YYYY-MM to {year, month}
        function parseDateString(dateString) {
            if (!dateString || !dateString.match(/^\d{4}-\d{2}$/)) {
                const now = new Date();
                return { year: now.getFullYear(), month: now.getMonth() + 1 };
            }
            const [year, month] = dateString.split('-');
            return { year: parseInt(year), month: parseInt(month) };
        }

        // Helper function to convert {year, month} to YYYY-MM
        function formatDateString(year, month) {
            const monthStr = month.toString().padStart(2, '0');
            return `${year}-${monthStr}`;
        }

        // Function to load amount deduction entries
        function loadAmountDeductionEntries(entryId) {
            const tbody = document.getElementById('amountDeductionTableBody');
            if (!tbody) return;

            // Clear existing entries
            tbody.innerHTML = '';
            amountDeductionCounter = 0;

            // Get department ID for localStorage key
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) {
                calculateAmountDeductionTotal();
                return;
            }

            // Load from localStorage
            const storageKey = getAmountDeductionsKey(departmentId, entryId);
            const savedEntries = localStorage.getItem(storageKey);

            console.log('📂 Loading amount deductions for entryId:', entryId, 'storageKey:', storageKey);

            if (savedEntries) {
                try {
                    const entries = JSON.parse(savedEntries);
                    console.log('📂 Loaded entries from localStorage:', entries);

                    // Restore each entry
                    entries.forEach(entry => {
                        amountDeductionCounter++;
                        const row = document.createElement('tr');
                        row.id = `amountDeductionRow_${amountDeductionCounter}`;
                        row.className = 'hover:bg-gray-50 transition-colors';

                        // Convert date to month format (YYYY-MM) if it's a full date
                        let monthValue = entry.date || '';
                        if (!monthValue || monthValue.trim() === '') {
                            // If no date, use current month
                            monthValue = new Date().toISOString().slice(0, 7);
                        } else {
                            monthValue = monthValue.trim();
                            // If it's a full date (YYYY-MM-DD), convert to month (YYYY-MM)
                            if (monthValue.length > 7) {
                                monthValue = monthValue.slice(0, 7);
                            }
                            // Ensure it's in YYYY-MM format
                            if (monthValue.length !== 7 || !monthValue.match(/^\d{4}-\d{2}$/)) {
                                // Invalid format, use current month
                                console.warn('Invalid date format in localStorage:', entry.date, 'using current month');
                                monthValue = new Date().toISOString().slice(0, 7);
                            } else {
                                // Valid date format - log it for debugging
                                console.log('Loading amount deduction - Date:', monthValue, 'from localStorage entry:', entry);
                            }
                        }

                        // Parse the date to get year and month
                        const { year, month } = parseDateString(monthValue);
                        const monthName = getMonthName(month);

                        // Generate month options
                        const monthOptions = ['January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December']
                            .map(m => `<option value="${m}" ${m === monthName ? 'selected' : ''}>${m}</option>`)
                            .join('');

                        row.innerHTML = `
                    <td class="border-b border-gray-200 py-3 px-4">
                        <div class="flex gap-2">
                            <select 
                                id="amountDeductionMonth_${amountDeductionCounter}" 
                                class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm"
                            >
                                ${monthOptions}
                            </select>
                            <input 
                                type="number" 
                                id="amountDeductionYear_${amountDeductionCounter}" 
                                class="w-20 px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm"
                                value="${year}"
                                min="2000"
                                max="2100"
                                placeholder="Year"
                            >
                        </div>
                    </td>
                    <td class="border-b border-gray-200 py-3 px-4">
                        <input 
                            type="text" 
                            id="amountDeductionAmount_${amountDeductionCounter}" 
                            class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                            value="${entry.amount ? formatNumber(entry.amount) : '₱0.00'}"
                            placeholder="₱0.00"
                        >
                    </td>
                    <td class="border-b border-gray-200 py-3 px-4 text-center">
                        <button 
                            onclick="deleteAmountDeductionEntry(${amountDeductionCounter})" 
                            class="p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-sm hover:shadow-md"
                            title="Delete entry"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                    </td>
                `;

                        tbody.appendChild(row);

                        // Setup event listeners for amount input
                        const amountInput = document.getElementById(`amountDeductionAmount_${amountDeductionCounter}`);

                        if (amountInput) {
                            // Setup focus clear and auto-formatting
                            setupAmountInputHandlers(amountInput);

                            // Add input event for calculations
                            amountInput.addEventListener('input', function () {
                                formatCurrencyInput(this);
                                calculateAmountDeductionTotal();
                                saveAmountDeductionsToLocalStorage(entryId);
                            });
                        }

                        // Setup date input event listeners - use setTimeout to ensure DOM is ready
                        setTimeout(() => {
                            const monthSelect = document.getElementById(`amountDeductionMonth_${amountDeductionCounter}`);
                            const yearInput = document.getElementById(`amountDeductionYear_${amountDeductionCounter}`);

                            if (monthSelect && yearInput) {
                                // Function to get current date value and save
                                const updateAndSave = () => {
                                    const monthName = monthSelect.value;
                                    const year = yearInput.value;

                                    if (monthName && year && year.match(/^\d{4}$/)) {
                                        const month = getMonthNumber(monthName);
                                        const formattedDate = formatDateString(parseInt(year), month);
                                        console.log('✅✅✅ USER CHANGED DATE to:', formattedDate, 'Month:', monthName, 'Year:', year, 'for counter:', amountDeductionCounter, 'entryId:', entryId);

                                        // Save immediately with the new date
                                        saveAmountDeductionsToLocalStorage(entryId);

                                        // Verify it was saved
                                        setTimeout(() => {
                                            const departmentId = document.getElementById('departmentSelect')?.value || document.getElementById('officeSelect')?.value || '';
                                            const storageKey = getAmountDeductionsKey(departmentId, entryId);
                                            const saved = localStorage.getItem(storageKey);
                                            if (saved) {
                                                const parsed = JSON.parse(saved);
                                                const entry = parsed.find(e => e.date === formattedDate);
                                                if (entry) {
                                                    console.log('✅✅✅ CONFIRMED: Date', formattedDate, 'was saved successfully!');
                                                } else {
                                                    console.error('❌ ERROR: Date', formattedDate, 'was NOT found in saved data!');
                                                }
                                            }
                                        }, 100);
                                    }
                                };

                                // Add change event listeners
                                monthSelect.addEventListener('change', updateAndSave);
                                yearInput.addEventListener('change', updateAndSave);

                                // Also save on input event for better responsiveness
                                yearInput.addEventListener('input', function () {
                                    const year = this.value;
                                    if (year && year.match(/^\d{4}$/)) {
                                        updateAndSave();
                                    }
                                });
                            }
                        }, 100);
                    });
                } catch (e) {
                    console.error('Error loading amount deductions from localStorage:', e);
                }
            }

            // Calculate and display total
            calculateAmountDeductionTotal();
        }

        // Function to save amount deduction entries to localStorage
        function saveAmountDeductionsToLocalStorage(entryId) {
            // Don't save if modal is being closed
            if (window.isClosingHonorariaModal) return;

            if (!entryId) entryId = currentAmountDeductionEntryId;
            if (!entryId) return;

            // Get department ID for localStorage key
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) return;

            const entries = [];
            const tbody = document.getElementById('amountDeductionTableBody');

            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const counter = row.id.replace('amountDeductionRow_', '');
                    const monthSelect = document.getElementById(`amountDeductionMonth_${counter}`);
                    const yearInput = document.getElementById(`amountDeductionYear_${counter}`);
                    const amountInput = document.getElementById(`amountDeductionAmount_${counter}`);

                    // Get date from month dropdown and year input
                    let date = '';
                    if (monthSelect && yearInput) {
                        const monthName = monthSelect.value || '';
                        const year = yearInput.value || '';

                        if (monthName && year && year.match(/^\d{4}$/)) {
                            const month = getMonthNumber(monthName);
                            if (month) {
                                date = formatDateString(parseInt(year), month);
                            }
                        }
                    }

                    const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;

                    // Validate date format - must be YYYY-MM
                    if (date && !date.match(/^\d{4}-\d{2}$/)) {
                        console.warn('Invalid date format:', date, 'for counter:', counter);
                        date = '';
                    }

                    if (date || amount > 0) {
                        entries.push({
                            date: date,
                            amount: amount
                        });
                        // Debug log to verify date is being saved correctly
                        if (date) {
                            console.log('Saving amount deduction - Date:', date, 'Month:', monthSelect ? monthSelect.value : 'N/A', 'Year:', yearInput ? yearInput.value : 'N/A', 'Amount:', amount, 'Counter:', counter, 'EntryId:', entryId);
                        }
                    }
                });
            }

            // Save to localStorage with entry-specific key
            const storageKey = getAmountDeductionsKey(departmentId, entryId);
            localStorage.setItem(storageKey, JSON.stringify(entries));
            console.log('✅ Saved amount deductions to localStorage:', storageKey, 'Entries:', entries.length, 'Dates:', entries.map(e => e.date).join(', '));

            // Verify what was saved
            const saved = localStorage.getItem(storageKey);
            if (saved) {
                const parsed = JSON.parse(saved);
                console.log('✅ Verified saved data:', parsed);
            }
        }

        // Function to add amount deduction entry
        function addAmountDeductionEntry() {
            // If no entry ID is set, we're in general honoraria mode
            // User will need to select which expense category to deduct from
            if (!currentAmountDeductionEntryId) {
                // Allow adding entry, but user must select expense category
            }

            amountDeductionCounter++;
            const tbody = document.getElementById('amountDeductionTableBody');
            if (!tbody) return;

            const currentDate = new Date().toISOString().split('T')[0];
            const row = document.createElement('tr');
            row.id = `amountDeductionRow_${amountDeductionCounter}`;
            row.className = 'hover:bg-gray-50 transition-colors';

            // Get current month and year
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth() + 1;
            const currentMonthName = getMonthName(currentMonth);

            // Generate month options
            const monthOptions = ['January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December']
                .map(m => `<option value="${m}" ${m === currentMonthName ? 'selected' : ''}>${m}</option>`)
                .join('');

            row.innerHTML = `
        <td class="border-b border-gray-200 py-3 px-4">
            <div class="flex gap-2">
                <select 
                    id="amountDeductionMonth_${amountDeductionCounter}" 
                    class="flex-1 px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm"
                >
                    ${monthOptions}
                </select>
                <input 
                    type="number" 
                    id="amountDeductionYear_${amountDeductionCounter}" 
                    class="w-20 px-3 py-2 border-2 border-gray-200 rounded-lg focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm"
                    value="${currentYear}"
                    min="2000"
                    max="2100"
                    placeholder="Year"
                >
            </div>
        </td>
        <td class="border-b border-gray-200 py-3 px-4">
            <input 
                type="text" 
                id="amountDeductionAmount_${amountDeductionCounter}" 
                class="w-full px-3 py-2 border-2 border-gray-200 rounded-lg text-right focus:ring-2 focus:ring-maroon focus:border-maroon transition-all bg-white text-gray-900 font-medium text-sm" 
                value="₱0.00"
                placeholder="₱0.00"
            >
        </td>
        <td class="border-b border-gray-200 py-3 px-4 text-center">
            <button 
                onclick="deleteAmountDeductionEntry(${amountDeductionCounter})" 
                class="p-1.5 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-all shadow-sm hover:shadow-md"
                title="Delete entry"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </button>
        </td>
    `;

            tbody.appendChild(row);

            // Setup event listeners for amount input
            const amountInput = document.getElementById(`amountDeductionAmount_${amountDeductionCounter}`);

            if (amountInput) {
                // Setup focus clear and auto-formatting
                setupAmountInputHandlers(amountInput);

                // Add input event for calculations
                amountInput.addEventListener('input', function () {
                    formatCurrencyInput(this);
                    calculateAmountDeductionTotal();

                    // Save to database if in honoraria mode, otherwise save to localStorage
                    if (!currentAmountDeductionEntryId) {
                        const departmentSelect = document.getElementById('departmentSelect');
                        const officeSelect = document.getElementById('officeSelect');
                        const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                        if (departmentId) {
                            setTimeout(() => {
                                saveHonorariaToDatabase(departmentId);
                            }, 500);
                        }
                    } else {
                        if (currentAmountDeductionEntryId) {
                            saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
                        }
                    }
                });
            }

            // Also save when date changes
            const monthSelect = document.getElementById(`amountDeductionMonth_${amountDeductionCounter}`);
            const yearInput = document.getElementById(`amountDeductionYear_${amountDeductionCounter}`);

            if (monthSelect && yearInput) {
                // Function to get current date value and save
                const updateAndSave = () => {
                    const monthName = monthSelect.value;
                    const year = yearInput.value;

                    if (monthName && year && year.match(/^\d{4}$/)) {
                        const month = getMonthNumber(monthName);
                        const formattedDate = formatDateString(parseInt(year), month);
                        console.log('✅ Date changed to:', formattedDate, 'Month:', monthName, 'Year:', year, 'for counter:', amountDeductionCounter);

                        // Save to database if in honoraria mode, otherwise save to localStorage
                        if (!currentAmountDeductionEntryId) {
                            const departmentSelect = document.getElementById('departmentSelect');
                            const officeSelect = document.getElementById('officeSelect');
                            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                            if (departmentId) {
                                setTimeout(() => {
                                    saveHonorariaToDatabase(departmentId);
                                }, 500);
                            }
                        } else {
                            if (currentAmountDeductionEntryId) {
                                saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
                            }
                        }
                    }
                };

                // Add change event listeners
                monthSelect.addEventListener('change', updateAndSave);
                yearInput.addEventListener('change', updateAndSave);

                // Also save on input event for better responsiveness (catches changes as user types/selects)
                yearInput.addEventListener('input', function () {
                    const year = this.value;
                    if (year && year.match(/^\d{4}$/)) {
                        updateAndSave();
                    }
                });
            }

            calculateAmountDeductionTotal();

            // Save to database if in honoraria mode, otherwise save to localStorage
            if (!currentAmountDeductionEntryId) {
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
                if (departmentId) {
                    // Save after a short delay to ensure DOM is ready
                    // Note: This will save even if amount is 0 or no category selected
                    // But the save function filters out invalid entries, so it's safe
                    setTimeout(() => {
                        saveHonorariaToDatabase(departmentId);
                    }, 300);
                }
            } else {
                saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
            }
        }

        // Function to delete amount deduction entry
        function deleteAmountDeductionEntry(counter) {
            if (confirm('Are you sure you want to delete this entry?')) {
                const row = document.getElementById(`amountDeductionRow_${counter}`);
                if (!row) return;

                // Get the database ID if this entry was saved
                const honorariaId = row.getAttribute('data-honoraria-id');
                const departmentSelect = document.getElementById('departmentSelect');
                const officeSelect = document.getElementById('officeSelect');
                const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

                const amountInput = document.getElementById(`amountDeductionAmount_${counter}`);
                const deletedAmount = amountInput ? parseAmount(amountInput.value || '0') : 0;

                // Delete from database if it exists
                if (honorariaId && departmentId && !currentAmountDeductionEntryId) {
                    // In honoraria mode (not entry-specific mode) - delete from database
                    fetch('../api/delete_honoraria.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            honoraria_id: honorariaId,
                            department_id: departmentId
                        })
                    })
                        .then(response => {
                            // Check if response is ok, but also parse JSON even if not ok to get error message
                            return response.json().then(data => {
                                if (!response.ok) {
                                    throw new Error(data.message || 'Network response was not ok');
                                }
                                return data;
                            });
                        })
                        .then(data => {
                            if (data.success) {
                                // Remove row from DOM immediately
                                row.remove();
                                calculateAmountDeductionTotal();

                                // NEW DEDUCTION SYSTEM: Check if this Honoraria was used in any Expense Category deductions
                                if (honorariaId && departmentId && deletedAmount > 0) {
                                    // Find all Expense Categories that have this Honoraria in their deduction sources
                                    const allUtilizationRows = document.querySelectorAll('[id^="entryRow_"]');

                                    allUtilizationRows.forEach(utilRow => {
                                        const categoryEntryId = utilRow.id.split('_')[1];
                                        const deductionSourcesKey = getDeductionSourcesKey(departmentId, categoryEntryId);
                                        const savedSources = localStorage.getItem(deductionSourcesKey);

                                        if (savedSources) {
                                            try {
                                                let deductionSources = JSON.parse(savedSources);
                                                let updated = false;

                                                // Check each deduction source
                                                deductionSources.forEach((ds, index) => {
                                                    if (ds.sourceType === 'honoraria') {
                                                        // Find if this Honoraria entry is in the entries array
                                                        const honorariaEntryIndex = ds.entries.findIndex(e => {
                                                            const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                                            const hId = parseInt(honorariaId) || honorariaId;
                                                            return eId === hId || String(eId) === String(hId) || e.sourceEntryId === honorariaId;
                                                        });

                                                        if (honorariaEntryIndex >= 0) {
                                                            // Found this Honoraria in the deduction sources
                                                            const honorariaEntryAmount = parseFloat(ds.entries[honorariaEntryIndex].amount) || 0;

                                                            // Remove this Honoraria entry from the array
                                                            ds.entries.splice(honorariaEntryIndex, 1);

                                                            // Recalculate total amount
                                                            ds.amount = ds.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);

                                                            // Update deduction field
                                                            const deductionInput = document.getElementById(`deduction_${categoryEntryId}`);
                                                            if (deductionInput) {
                                                                const currentDeduction = parseAmount(deductionInput.value || '0');
                                                                const newDeduction = Math.max(0, currentDeduction - honorariaEntryAmount);

                                                                if (newDeduction > 0) {
                                                                    deductionInput.value = formatNumber(newDeduction);
                                                                } else {
                                                                    deductionInput.value = '';
                                                                }

                                                                // Recalculate row total
                                                                calculateRowTotal(categoryEntryId);

                                                                console.log(`Removed Honoraria ${honorariaId} (${formatNumber(honorariaEntryAmount)}) from deduction for category entry ${categoryEntryId}. New deduction: ${formatNumber(newDeduction)}`);
                                                            }

                                                            updated = true;
                                                        }
                                                    }
                                                });

                                                // Remove deduction sources with 0 amount or no entries
                                                deductionSources = deductionSources.filter(ds => ds.amount > 0 && ds.entries.length > 0);

                                                if (updated) {
                                                    // Save updated deduction sources
                                                    if (deductionSources.length > 0) {
                                                        localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                                                    } else {
                                                        localStorage.removeItem(deductionSourcesKey);
                                                    }

                                                    // Also remove from selections
                                                    const selectionsKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${categoryEntryId}_source_honoraria`;
                                                    const savedSelections = localStorage.getItem(selectionsKey);
                                                    if (savedSelections) {
                                                        try {
                                                            let selections = JSON.parse(savedSelections);
                                                            selections = selections.filter(sel => {
                                                                const selId = parseInt(sel) || sel;
                                                                const hId = parseInt(honorariaId) || honorariaId;
                                                                return selId !== hId && String(selId) !== String(hId) && sel !== honorariaId;
                                                            });

                                                            if (selections.length > 0) {
                                                                localStorage.setItem(selectionsKey, JSON.stringify(selections));
                                                            } else {
                                                                localStorage.removeItem(selectionsKey);
                                                            }
                                                        } catch (e) {
                                                            console.error('Error updating selections:', e);
                                                        }
                                                    }

                                                    // Save the updated deduction to database immediately
                                                    saveUtilizationToLocalStorage();
                                                }
                                            } catch (e) {
                                                console.error('Error processing deduction sources:', e);
                                            }
                                        }
                                    });
                                }

                                // Save deductions to database immediately (don't recalculate - we already updated them correctly)
                                saveUtilizationToLocalStorage();
                                // Recalculate totals only
                                calculateTotals();
                                console.log('Honoraria entry deleted and deduction removed successfully');
                            } else {
                                console.error('Error deleting honoraria:', data.message);
                                alert('Error deleting honoraria entry: ' + (data.message || 'Unknown error'));
                            }
                        })
                        .catch(error => {
                            console.error('Error deleting honoraria:', error);
                            alert('Error deleting honoraria entry: ' + (error.message || 'Please try again.'));
                        });
                } else {
                    // If not saved to database, or in entry-specific mode, just remove from DOM
                    // Update deduction FIRST before removing row (so we can still access the amount)
                    if (deductedFromEntryId && deletedAmount > 0) {
                        // Get current deduction value
                        const deductionInput = document.getElementById(`deduction_${deductedFromEntryId}`);
                        if (deductionInput) {
                            // Get current deduction amount
                            const currentDeduction = parseAmount(deductionInput.value || '0');
                            // Subtract the deleted amount
                            const newDeduction = Math.max(0, currentDeduction - deletedAmount);

                            // Update deduction field
                            if (newDeduction > 0) {
                                deductionInput.value = formatNumber(newDeduction);
                            } else {
                                deductionInput.value = '';
                            }

                            // Recalculate row total for this entry
                            calculateRowTotal(deductedFromEntryId);
                        }
                    }

                    // Now remove the row
                    row.remove();
                    calculateAmountDeductionTotal();

                    // Save to database/localStorage immediately after deletion
                    if (currentAmountDeductionEntryId) {
                        saveAmountDeductionsToLocalStorage(currentAmountDeductionEntryId);
                    } else if (departmentId) {
                        // In honoraria mode - save to database to remove deleted entry
                        saveHonorariaToDatabase(departmentId).then(() => {
                            // After saving, recalculate all deductions to ensure everything is in sync
                            recalculateAllDeductions();
                        });
                    }

                    // Recalculate all deductions to ensure everything is accurate
                    recalculateAllDeductions();

                    // Recalculate totals
                    calculateTotals();
                }
            }
        }

        // Function to calculate amount deduction total
        function calculateAmountDeductionTotal() {
            const totalEl = document.getElementById('amountDeductionTotal');
            if (!totalEl) return;

            let total = 0;
            const tbody = document.getElementById('amountDeductionTableBody');
            if (tbody) {
                const rows = tbody.querySelectorAll('tr');
                rows.forEach(row => {
                    const counter = row.id.replace('amountDeductionRow_', '');
                    const amountInput = document.getElementById(`amountDeductionAmount_${counter}`);
                    if (amountInput) {
                        const amount = parseAmount(amountInput.value || '0');
                        total += amount;
                    }
                });
            }

            totalEl.textContent = formatNumber(total);
        }

        // Function to update deduction field from amount deductions
        function updateDeductionFromAmountDeductions() {
            // Get all main table entries to update deductions for all of them
            const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');

            // Create a map to store deductions per entry ID
            const amountDeductionsMap = new Map();

            // Honoraria entries no longer deduct from categories
            // Skip processing honoraria entries

            // Get amount deductions from localStorage for all entries (to include other modals' entries)
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (departmentId) {
                mainTableRows.forEach(row => {
                    const sourceEntryId = row.id.split('_')[1];
                    // Skip the current modal's entry since we already processed it above
                    if (sourceEntryId === currentAmountDeductionEntryId) return;

                    const storageKey = getAmountDeductionsKey(departmentId, sourceEntryId);
                    const savedEntries = localStorage.getItem(storageKey);

                    if (savedEntries) {
                        // Honoraria entries no longer deduct from categories
                        // Skip processing these entries
                    }
                });
            }

            // Calculate PR/Travel deductions for all entries
            const prTravelDeductionsMap = new Map();

            // Sum PR deductions
            const prRows = document.querySelectorAll('[id^="prRow_"]');
            prRows.forEach(prRow => {
                const prDeductFromSelect = document.getElementById(`prDeductFrom_${prRow.id.replace('prRow_', '')}`);
                const prAmountInput = document.getElementById(`prAmount_${prRow.id.replace('prRow_', '')}`);
                if (prDeductFromSelect && prAmountInput && prDeductFromSelect.value) {
                    const entryId = prDeductFromSelect.value;
                    const amount = parseAmount(prAmountInput.value || '0');
                    if (amount > 0) {
                        const current = prTravelDeductionsMap.get(entryId) || 0;
                        prTravelDeductionsMap.set(entryId, current + amount);
                    }
                }
            });

            // Sum Travel deductions
            const travelRows = document.querySelectorAll('[id^="travelRow_"]');
            travelRows.forEach(travelRow => {
                const travelDeductFromSelect = document.getElementById(`travelDeductFrom_${travelRow.id.replace('travelRow_', '')}`);
                const travelAmountInput = document.getElementById(`travelAmount_${travelRow.id.replace('travelRow_', '')}`);
                if (travelDeductFromSelect && travelAmountInput && travelDeductFromSelect.value) {
                    const entryId = travelDeductFromSelect.value;
                    const amount = parseAmount(travelAmountInput.value || '0');
                    if (amount > 0) {
                        const current = prTravelDeductionsMap.get(entryId) || 0;
                        prTravelDeductionsMap.set(entryId, current + amount);
                    }
                }
            });

            // Update deduction fields for ALL entries (not just affected ones)
            // This ensures that if all deductions are deleted, the field goes to 0
            mainTableRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const deductionInput = document.getElementById(`deduction_${entryId}`);
                if (deductionInput) {
                    const amountDeduction = amountDeductionsMap.get(entryId) || 0;
                    const prTravelDeduction = prTravelDeductionsMap.get(entryId) || 0;
                    const totalDeduction = amountDeduction + prTravelDeduction;

                    // Update deduction (empty string if 0 to show placeholder)
                    if (totalDeduction > 0) {
                        deductionInput.value = formatNumber(totalDeduction);
                    } else {
                        deductionInput.value = '';
                    }

                    // Recalculate row total
                    calculateRowTotal(entryId);
                }
            });

            calculateTotals();
            saveUtilizationToLocalStorage();
        }

        // Function to update deduction for a specific entry only (used when adding/editing Honoraria)
        function updateDeductionForSpecificEntry(entryId) {
            if (!entryId) return;

            const deductionInput = document.getElementById(`deduction_${entryId}`);
            if (!deductionInput) return;

            // Calculate PR/Travel deductions for this entry (preserve them)
            let prTravelDeduction = 0;

            // Sum PR deductions for this entry
            const prRows = document.querySelectorAll('[id^="prRow_"]');
            prRows.forEach(prRow => {
                const prDeductFromSelect = document.getElementById(`prDeductFrom_${prRow.id.replace('prRow_', '')}`);
                const prAmountInput = document.getElementById(`prAmount_${prRow.id.replace('prRow_', '')}`);
                if (prDeductFromSelect && prAmountInput && prDeductFromSelect.value === entryId) {
                    prTravelDeduction += parseAmount(prAmountInput.value || '0');
                }
            });

            // Sum Travel deductions for this entry
            const travelRows = document.querySelectorAll('[id^="travelRow_"]');
            travelRows.forEach(travelRow => {
                const travelDeductFromSelect = document.getElementById(`travelDeductFrom_${travelRow.id.replace('travelRow_', '')}`);
                const travelAmountInput = document.getElementById(`travelAmount_${travelRow.id.replace('travelRow_', '')}`);
                if (travelDeductFromSelect && travelAmountInput && travelDeductFromSelect.value === entryId) {
                    prTravelDeduction += parseAmount(travelAmountInput.value || '0');
                }
            });

            // Honoraria entries no longer deduct from categories
            let amountDeduction = 0;

            // Total deduction = PR/Travel deductions only (honoraria no longer deducts)
            const totalDeduction = prTravelDeduction + amountDeduction;

            // Update only this entry's deduction
            if (totalDeduction > 0) {
                deductionInput.value = formatNumber(totalDeduction);
            } else {
                deductionInput.value = '';
            }

            // Recalculate row total for this entry only
            calculateRowTotal(entryId);

            // Recalculate overall totals
            calculateTotals();
        }

        // Function to close amount deduction menu
        function closeAmountDeductionMenu(entryId) {
            const amountMenu = document.getElementById(`amountDeductionMenu_${entryId}`);
            if (amountMenu) {
                amountMenu.classList.add('hidden');
            }

            // Don't clear the amount - keep it so user can edit it when they reopen the menu
            // Only reset the date to today when closing (user can change it next time)
            const dateInput = document.getElementById(`amountDeductionDate_${entryId}`);
            const amountInput = document.getElementById(`amountDeductionValue_${entryId}`);

            if (dateInput) {
                // Only reset if amount is empty (new entry), otherwise keep the date
                const amount = amountInput ? parseAmount(amountInput.value || '0') : 0;
                if (amount === 0) {
                    dateInput.value = new Date().toISOString().slice(0, 7); // Use month format (YYYY-MM)
                }
            }
        }

        // Setup event delegation for amount deduction sub-menu inputs
        document.addEventListener('DOMContentLoaded', function () {
            // Use MutationObserver to detect when amount deduction sub-menus are shown
            const observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type === 'attributes' && mutation.attributeName === 'class') {
                        const target = mutation.target;
                        if (target.id && target.id.startsWith('amountDeductionMenu_')) {
                            // Check if menu is now visible (not hidden)
                            if (!target.classList.contains('hidden')) {
                                const entryId = target.id.replace('amountDeductionMenu_', '');
                                // Setup handlers for the amount input
                                setTimeout(() => {
                                    const amountInput = document.getElementById(`amountDeductionValue_${entryId}`);
                                    if (amountInput && !amountInput.hasAttribute('data-handlers-setup')) {
                                        setupAmountInputHandlers(amountInput);
                                        amountInput.setAttribute('data-handlers-setup', 'true');
                                    }
                                }, 50);
                            }
                        }
                    }
                });
            });

            // Observe all amount deduction menus
            document.querySelectorAll('[id^="amountDeductionMenu_"]').forEach(menu => {
                observer.observe(menu, { attributes: true, attributeFilter: ['class'] });
            });

            // Also observe dynamically added menus
            const bodyObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    mutation.addedNodes.forEach(function (node) {
                        if (node.nodeType === 1 && node.id && node.id.startsWith('amountDeductionMenu_')) {
                            observer.observe(node, { attributes: true, attributeFilter: ['class'] });
                        }
                    });
                });
            });
            bodyObserver.observe(document.body, { childList: true, subtree: true });
        });

        // Function to save amount deduction
        function saveAmountDeduction(entryId) {
            const dateInput = document.getElementById(`amountDeductionDate_${entryId}`);
            const amountInput = document.getElementById(`amountDeductionValue_${entryId}`);

            if (!dateInput || !amountInput) return;

            const date = dateInput.value;
            const amount = parseAmount(amountInput.value || '0');

            if (!date) {
                alert('Please select a date');
                return;
            }

            if (amount <= 0) {
                alert('Please enter a valid amount');
                return;
            }

            // Get current deduction value
            const deductionInput = document.getElementById(`deduction_${entryId}`);
            if (!deductionInput) return;

            // Replace the deduction with the new amount (since Amount field is pre-filled with current deduction)
            // This allows user to edit the deduction value directly
            if (amount > 0) {
                deductionInput.value = formatNumber(amount);
            } else {
                deductionInput.value = '';
            }

            // Recalculate row total for this entry
            calculateRowTotal(entryId);

            // Recalculate totals
            calculateTotals();

            // Format the amount in the input field so it persists for editing
            amountInput.value = formatNumber(amount);

            // Close menu after saving (amount will remain in field for next time)
            closeAmountDeductionMenu(entryId);

            // Save to database/localStorage
            saveUtilizationToLocalStorage();
        }

        // Close menus when clicking outside
        document.addEventListener('click', function (event) {
            // Close deduction menus if clicking outside
            if (!event.target.closest('[id^="deductionMenu_"]') &&
                !event.target.closest('button[onclick*="showDeductionMenu"]')) {
                document.querySelectorAll('[id^="deductionMenu_"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }

            // Close amount deduction menus if clicking outside
            if (!event.target.closest('[id^="amountDeductionMenu_"]') &&
                !event.target.closest('button[onclick*="showAmountDeductionMenu"]')) {
                document.querySelectorAll('[id^="amountDeductionMenu_"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Make utility functions available globally for debugging
        window.clearDeductionsLocalStorage = clearDeductionsLocalStorage;
        window.cleanInvalidDeductions = cleanInvalidDeductions;

        // Auto-clean Guidance Office (ID 18) deductions on page load
        document.addEventListener('DOMContentLoaded', function () {
            // Clean Guidance Office deductions immediately
            cleanInvalidDeductions(18);

            // Also check and clean amount deductions for Guidance Office
            const keysToCheck = [];
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key && key.startsWith(`amount_deductions_user_${CURRENT_USER_ID}_dept_18_entry_`) && key.includes(`_year_${CURRENT_FISCAL_YEAR}`)) {
                    keysToCheck.push(key);
                }
            }
            keysToCheck.forEach(key => {
                try {
                    const data = JSON.parse(localStorage.getItem(key) || '[]');
                    const cleaned = data.filter(entry => {
                        const amount = parseFloat(entry.amount || 0);
                        return amount !== 10000; // Remove exactly 10000 values
                    });
                    if (cleaned.length !== data.length) {
                        localStorage.setItem(key, JSON.stringify(cleaned));
                        console.log(`Cleaned ${data.length - cleaned.length} invalid amount deduction(s) from ${key}`);
                    }
                } catch (e) {
                    console.error('Error cleaning amount deductions:', e);
                }
            });

            // Clean main deductions data for Guidance Office (account-specific)
            const guidanceStorageKey = `deductions_data_user_${CURRENT_USER_ID}_dept_18`;
            const guidanceData = localStorage.getItem(guidanceStorageKey);
            if (guidanceData) {
                try {
                    const parsed = JSON.parse(guidanceData);
                    if (parsed.deductions && Array.isArray(parsed.deductions)) {
                        const cleaned = parsed.deductions.filter(deduction => {
                            const amount = parseFloat(deduction.deduction_amount || 0);
                            return amount !== 10000; // Remove exactly 10000 values
                        });
                        if (cleaned.length !== parsed.deductions.length) {
                            localStorage.setItem(guidanceStorageKey, JSON.stringify({
                                deductions: cleaned,
                                department_id: 18,
                                user_id: CURRENT_USER_ID,
                                saved_at: new Date().toISOString()
                            }));
                            console.log(`Cleaned ${parsed.deductions.length - cleaned.length} invalid deduction(s) from Guidance Office`);
                        }
                    }
                } catch (e) {
                    console.error('Error cleaning Guidance Office deductions:', e);
                }
            }
        });

        // Function to show deduction source menu
        function showDeductionSourceMenu(entryId) {
            // Close all other menus first
            document.querySelectorAll('[id^="deductionSourceMenu_"]').forEach(menu => {
                menu.classList.add('hidden');
                menu.style.position = '';
                menu.style.top = '';
                menu.style.left = '';
                menu.style.right = '';
            });
            document.querySelectorAll('[id^="deductionEntriesMenu_"]').forEach(menu => {
                menu.classList.add('hidden');
            });

            const menu = document.getElementById(`deductionSourceMenu_${entryId}`);
            const button = event.currentTarget;

            if (menu) {
                const isHidden = menu.classList.contains('hidden');
                if (isHidden) {
                    // Get button position
                    const rect = button.getBoundingClientRect();

                    // Use fixed positioning to escape overflow clipping
                    menu.style.position = 'fixed';
                    menu.style.top = (rect.bottom + 8) + 'px';
                    menu.style.left = Math.max(10, rect.right - 320) + 'px';
                    menu.style.right = 'auto';
                    menu.style.zIndex = '9999';
                }
                menu.classList.toggle('hidden');
            }
        }

        // Function to show entries from selected source
        // Store current entry ID and source type for modal
        let currentDeductionEntryId = null;
        let currentDeductionSourceType = null;
        let currentDeductionSourceTab = 'ppmp'; // Track current tab (ppmp or supplemental)
        let allDeductionEntries = []; // Store all entries before filtering by tab

        // Function to switch deduction source tabs
        function switchDeductionSourceTab(tabName) {
            currentDeductionSourceTab = tabName;
            // Clear search when switching tabs
            const searchEl = document.getElementById('deductionEntrySearch');
            if (searchEl) searchEl.value = '';
            
            // Update tab styling
            const tabs = document.querySelectorAll('.deduction-source-tab');
            tabs.forEach(tab => {
                const tabId = tab.id;
                if (tabId === `deductionSourceTab-${tabName}`) {
                    // Active tab styling
                    if (tabName === 'ppmp') {
                        tab.className = 'deduction-source-tab px-6 py-3 text-sm font-semibold border-b-2 border-maroon text-maroon bg-maroon bg-opacity-5';
                    } else {
                        tab.className = 'deduction-source-tab px-6 py-3 text-sm font-semibold border-b-2 border-yellow-600 text-yellow-600 bg-yellow-50';
                    }
                } else {
                    // Inactive tab styling
                    tab.className = 'deduction-source-tab px-6 py-3 text-sm font-semibold border-b-2 border-transparent text-gray-500 hover:text-yellow-600';
                }
            });
            
            // Filter and display entries for current tab
            displayDeductionEntriesByTab();
        }

        // Function to display entries filtered by current tab
        function displayDeductionEntriesByTab() {
            const modalBody = document.getElementById('deductionEntryModalBody');
            if (!modalBody || !allDeductionEntries || allDeductionEntries.length === 0) {
                return;
            }

            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            // Filter entries by ppmp_type matching current tab
            const filteredEntries = allDeductionEntries.filter(entry => {
                const entryType = entry.ppmp_type || 'ppmp';
                return entryType === currentDeductionSourceTab;
            });

            if (filteredEntries.length === 0) {
                const tabLabel = currentDeductionSourceTab === 'ppmp' ? 'PPMP' : 'Supplemental';
                modalBody.innerHTML = `<div class="text-center py-8"><div class="text-gray-500">No ${tabLabel} entries found</div></div>`;
                updateSelectedCount();
                return;
            }

            let entriesHtml = '<div class="space-y-2">';

            filteredEntries.forEach((entry, index) => {
                const entryIdValue = entry.id || index;
                const displayText = entry.purchase_request || 'N/A';
                let details = entry.particulars || 'No particulars';
                if (entry.pr_number) {
                    details += ` | PR#: ${entry.pr_number}`;
                }
                if (entry.date) {
                    details += ` | Date: ${entry.date}`;
                }
                const amount = parseFloat(entry.amount || 0);

                if (amount > 0) {
                    // Check if this entry was previously selected FOR THIS SPECIFIC EXPENSE CATEGORY ONLY
                    const storageKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${currentDeductionEntryId}_source_${currentDeductionSourceType}`;
                    const savedSelections = localStorage.getItem(storageKey);
                    let isSelected = false;
                    if (savedSelections) {
                        try {
                            const selections = JSON.parse(savedSelections);
                            isSelected = selections.some(sel =>
                                sel == entryIdValue ||
                                String(sel) === String(entryIdValue) ||
                                parseInt(sel) === parseInt(entryIdValue)
                            );
                        } catch (e) {
                            console.error('Error parsing saved selections:', e);
                        }
                    }

                    // ALSO check if this entry is in deduction_sources (actually applied)
                    if (!isSelected) {
                        const deductionSourcesKey = getDeductionSourcesKey(departmentId, currentDeductionEntryId);
                        const savedSources = localStorage.getItem(deductionSourcesKey);
                        if (savedSources) {
                            try {
                                const deductionSources = JSON.parse(savedSources);
                                deductionSources.forEach(ds => {
                                    const dsEntryId = String(ds.categoryEntryId);
                                    const currentEntryId = String(currentDeductionEntryId);
                                    
                                    if (ds.sourceType === currentDeductionSourceType && dsEntryId === currentEntryId) {
                                        const foundEntry = ds.entries.find(e => {
                                            const eId = String(e.sourceEntryId);
                                            const sId = String(entryIdValue);
                                            return eId === sId;
                                        });
                                        
                                        if (foundEntry) {
                                            isSelected = true;
                                        }
                                    }
                                });
                            } catch (e) {
                                console.error('Error parsing deduction sources:', e);
                            }
                        }
                    }

                    // Check if this entry is already used by OTHER expense categories
                    let isUsedByOtherCategory = false;
                    let usedByCategory = null;
                    const allUtilizationRows = document.querySelectorAll('[id^="entryRow_"]');
                    allUtilizationRows.forEach(row => {
                        const otherEntryId = row.id.split('_')[1];
                        if (otherEntryId == currentDeductionEntryId || String(otherEntryId) === String(currentDeductionEntryId)) {
                            return;
                        }

                        const otherStorageKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${otherEntryId}_source_${currentDeductionSourceType}`;
                        const otherSavedSelections = localStorage.getItem(otherStorageKey);
                        if (otherSavedSelections) {
                            try {
                                const otherSelections = JSON.parse(otherSavedSelections);
                                const isInOther = otherSelections.some(sel =>
                                    sel == entryIdValue ||
                                    String(sel) === String(entryIdValue) ||
                                    parseInt(sel) === parseInt(entryIdValue)
                                );

                                if (isInOther) {
                                    isUsedByOtherCategory = true;
                                    const otherColumnArea = document.getElementById(`columnArea_${otherEntryId}`);
                                    usedByCategory = otherColumnArea ? otherColumnArea.value : `ENTRY ${otherEntryId}`;
                                }
                            } catch (e) {
                                // Ignore parsing errors
                            }
                        }

                        const otherDeductionSourcesKey = getDeductionSourcesKey(departmentId, otherEntryId);
                        const otherSavedSources = localStorage.getItem(otherDeductionSourcesKey);
                        if (otherSavedSources) {
                            try {
                                const otherDeductionSources = JSON.parse(otherSavedSources);
                                otherDeductionSources.forEach(ds => {
                                    if (ds.sourceType === currentDeductionSourceType) {
                                        const foundEntry = ds.entries.find(e => {
                                            const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                            const sId = parseInt(entryIdValue) || entryIdValue;
                                            return eId === sId || String(eId) === String(sId) || e.sourceEntryId === entryIdValue;
                                        });
                                        if (foundEntry && !isUsedByOtherCategory) {
                                            isUsedByOtherCategory = true;
                                            const otherColumnArea = document.getElementById(`columnArea_${otherEntryId}`);
                                            usedByCategory = otherColumnArea ? otherColumnArea.value : `ENTRY ${otherEntryId}`;
                                        }
                                    }
                                });
                            } catch (e) {
                                // Ignore parsing errors
                            }
                        }
                    });

                    const shouldShowAsSelected = isSelected && !isUsedByOtherCategory;

                    // Add badge based on ppmp_type
                    const ppmpType = entry.ppmp_type || 'ppmp';
                    const badgeHtml = ppmpType === 'supplemental' 
                        ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2">From Supplemental</span>'
                        : '<span class="text-xs bg-maroon bg-opacity-10 text-maroon px-2 py-1 rounded ml-2">From PPMP</span>';

                    entriesHtml += `
                        <label class="deduction-entry-item flex items-start gap-3 w-full px-6 py-4 bg-white border-2 ${isUsedByOtherCategory ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200 hover:border-maroon hover:bg-red-50'} rounded-lg transition-all shadow-sm hover:shadow-md cursor-pointer">
                            <input 
                                type="checkbox" 
                                class="mt-1 w-5 h-5 text-maroon border-gray-300 rounded focus:ring-maroon entry-checkbox" 
                                data-entry-id="${entryIdValue}"
                                data-amount="${amount}"
                                data-source-type="${currentDeductionSourceType}"
                                data-ppmp-type="${ppmpType}"
                                ${shouldShowAsSelected ? 'checked' : ''}
                                ${isUsedByOtherCategory ? 'disabled' : ''}
                                onchange="updateSelectedCount(); saveDeductionSelection(${currentDeductionEntryId}, '${currentDeductionSourceType}', ${entryIdValue}, this.checked)"
                            >
                            <div class="flex-1">
                                <div class="font-semibold text-gray-900 mb-1">${displayText} ${badgeHtml} ${isUsedByOtherCategory ? '<span class="text-xs text-yellow-700 bg-yellow-200 px-2 py-1 rounded ml-2">Used by ' + (usedByCategory || 'another category') + '</span>' : ''}</div>
                                <div class="text-sm text-gray-600 mb-2">${details.length > 80 ? details.substring(0, 80) + '...' : details}</div>
                                <div class="text-lg font-bold text-maroon">Amount: ${formatNumber(amount)}</div>
                            </div>
                        </label>
                    `;
                }
            });

            entriesHtml += '</div>';
            modalBody.innerHTML = entriesHtml;

            // Reset select all checkbox
            const selectAllCheckbox = document.getElementById('selectAllEntries');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }

            // Update selected count
            updateSelectedCount();
        }

        function showDeductionEntries(entryId, sourceType) {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) {
                alert('Please select a department/office first.');
                return;
            }

            // Clear search on open
            const searchEl = document.getElementById('deductionEntrySearch');
            if (searchEl) searchEl.value = '';

            // Close source menu
            const sourceMenu = document.getElementById(`deductionSourceMenu_${entryId}`);
            if (sourceMenu) {
                sourceMenu.classList.add('hidden');
            }

            // Store current entry ID and source type
            currentDeductionEntryId = entryId;
            currentDeductionSourceType = sourceType;

            // Set modal title based on source type
            const modalTitle = document.getElementById('deductionEntryModalTitle');
            const modalSubtitle = document.getElementById('deductionEntryModalSubtitle');
            const modalBody = document.getElementById('deductionEntryModalBody');
            const tabsContainer = document.getElementById('deductionSourceTabs');

            // Show/hide tabs based on source type
            if (sourceType === 'purchase_request') {
                modalTitle.textContent = 'Select Purchase Request Entry';
                modalSubtitle.textContent = 'Choose a purchase request entry to add to deduction';
                // Show tabs for purchase request
                if (tabsContainer) {
                    tabsContainer.classList.remove('hidden');
                }
                // Reset to PPMP tab
                currentDeductionSourceTab = 'ppmp';
                switchDeductionSourceTab('ppmp');
            } else if (sourceType === 'travels') {
                modalTitle.textContent = 'Select Travel Entry';
                modalSubtitle.textContent = 'Choose a travel entry to add to deduction';
                // Hide tabs for other source types
                if (tabsContainer) {
                    tabsContainer.classList.add('hidden');
                }
            } else if (sourceType === 'honoraria') {
                modalTitle.textContent = 'Select Honoraria Entry';
                modalSubtitle.textContent = 'Choose an honoraria entry to add to deduction';
                // Hide tabs for other source types
                if (tabsContainer) {
                    tabsContainer.classList.add('hidden');
                }
            }

            // Show loading state
            modalBody.innerHTML = '<div class="text-center py-8"><div class="text-gray-500">Loading entries...</div></div>';

            // Show modal
            const modal = document.getElementById('deductionEntryModal');
            if (modal) {
                modal.classList.remove('hidden');
            }

            // Load entries based on source type
            let apiUrl = '';
            if (sourceType === 'purchase_request') {
                apiUrl = `../api/load_purchase_requests.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`;
            } else if (sourceType === 'travels') {
                apiUrl = `../api/load_travels.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`;
            } else if (sourceType === 'honoraria') {
                apiUrl = `../api/load_honoraria.php?department_id=${departmentId}&fiscal_year=${CURRENT_FISCAL_YEAR}`;
            }

            fetch(apiUrl)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.entries && data.entries.length > 0) {
                        // For purchase_request, store all entries and use tab filtering
                        if (sourceType === 'purchase_request') {
                            allDeductionEntries = data.entries;
                            displayDeductionEntriesByTab();
                        } else {
                            // For other source types (travels, honoraria), display normally without tabs
                            let entriesHtml = '<div class="space-y-2">';

                            data.entries.forEach((entry, index) => {
                                let displayText = '';
                                let details = '';
                                let amount = 0;

                                if (sourceType === 'travels') {
                                    displayText = entry.travelled || 'N/A';
                                    details = entry.event || 'No event specified';
                                    if (entry.date) {
                                        details += ` | Date: ${entry.date}`;
                                    }
                                    amount = parseFloat(entry.amount || 0);
                                } else if (sourceType === 'honoraria') {
                                    displayText = `Honoraria Entry`;
                                    details = entry.date ? `Date: ${entry.date}` : 'No date specified';
                                    amount = parseFloat(entry.amount || 0);
                                }

                                if (amount > 0) {
                                    const entryIdValue = entry.id || index;
                                    const storageKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${entryId}_source_${sourceType}`;
                                    const savedSelections = localStorage.getItem(storageKey);
                                    let isSelected = false;
                                    if (savedSelections) {
                                        try {
                                            const selections = JSON.parse(savedSelections);
                                            isSelected = selections.some(sel =>
                                                sel == entryIdValue ||
                                                String(sel) === String(entryIdValue) ||
                                                parseInt(sel) === parseInt(entryIdValue)
                                            );
                                        } catch (e) {
                                            console.error('Error parsing saved selections:', e);
                                        }
                                    }

                                    if (!isSelected) {
                                        const deductionSourcesKey = getDeductionSourcesKey(departmentId, entryId);
                                        const savedSources = localStorage.getItem(deductionSourcesKey);
                                        if (savedSources) {
                                            try {
                                                const deductionSources = JSON.parse(savedSources);
                                                deductionSources.forEach(ds => {
                                                    const dsEntryId = String(ds.categoryEntryId);
                                                    const currentEntryId = String(entryId);
                                                    
                                                    if (ds.sourceType === sourceType && dsEntryId === currentEntryId) {
                                                        const foundEntry = ds.entries.find(e => {
                                                            const eId = String(e.sourceEntryId);
                                                            const sId = String(entryIdValue);
                                                            return eId === sId;
                                                        });
                                                        
                                                        if (foundEntry) {
                                                            isSelected = true;
                                                        }
                                                    }
                                                });
                                            } catch (e) {
                                                console.error('Error parsing deduction sources:', e);
                                            }
                                        }
                                    }

                                    let isUsedByOtherCategory = false;
                                    let usedByCategory = null;
                                    const allUtilizationRows = document.querySelectorAll('[id^="entryRow_"]');
                                    allUtilizationRows.forEach(row => {
                                        const otherEntryId = row.id.split('_')[1];
                                        if (otherEntryId == entryId || String(otherEntryId) === String(entryId)) {
                                            return;
                                        }

                                        const otherStorageKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${otherEntryId}_source_${sourceType}`;
                                        const otherSavedSelections = localStorage.getItem(otherStorageKey);
                                        if (otherSavedSelections) {
                                            try {
                                                const otherSelections = JSON.parse(otherSavedSelections);
                                                const isInOther = otherSelections.some(sel =>
                                                    sel == entryIdValue ||
                                                    String(sel) === String(entryIdValue) ||
                                                    parseInt(sel) === parseInt(entryIdValue)
                                                );

                                                if (isInOther) {
                                                    isUsedByOtherCategory = true;
                                                    const otherColumnArea = document.getElementById(`columnArea_${otherEntryId}`);
                                                    usedByCategory = otherColumnArea ? otherColumnArea.value : `ENTRY ${otherEntryId}`;
                                                }
                                            } catch (e) {
                                                // Ignore parsing errors
                                            }
                                        }

                                        const otherDeductionSourcesKey = getDeductionSourcesKey(departmentId, otherEntryId);
                                        const otherSavedSources = localStorage.getItem(otherDeductionSourcesKey);
                                        if (otherSavedSources) {
                                            try {
                                                const otherDeductionSources = JSON.parse(otherSavedSources);
                                                otherDeductionSources.forEach(ds => {
                                                    if (ds.sourceType === sourceType) {
                                                        const foundEntry = ds.entries.find(e => {
                                                            const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                                            const sId = parseInt(entryIdValue) || entryIdValue;
                                                            return eId === sId || String(eId) === String(sId) || e.sourceEntryId === entryIdValue;
                                                        });
                                                        if (foundEntry && !isUsedByOtherCategory) {
                                                            isUsedByOtherCategory = true;
                                                            const otherColumnArea = document.getElementById(`columnArea_${otherEntryId}`);
                                                            usedByCategory = otherColumnArea ? otherColumnArea.value : `ENTRY ${otherEntryId}`;
                                                        }
                                                    }
                                                });
                                            } catch (e) {
                                                // Ignore parsing errors
                                            }
                                        }
                                    });

                                    const shouldShowAsSelected = isSelected && !isUsedByOtherCategory;

                                    entriesHtml += `
                                <label class="flex items-start gap-3 w-full px-6 py-4 bg-white border-2 ${isUsedByOtherCategory ? 'border-yellow-300 bg-yellow-50' : 'border-gray-200 hover:border-maroon hover:bg-red-50'} rounded-lg transition-all shadow-sm hover:shadow-md cursor-pointer">
                                    <input 
                                        type="checkbox" 
                                        class="mt-1 w-5 h-5 text-maroon border-gray-300 rounded focus:ring-maroon entry-checkbox" 
                                        data-entry-id="${entryIdValue}"
                                        data-amount="${amount}"
                                        data-source-type="${sourceType}"
                                        ${shouldShowAsSelected ? 'checked' : ''}
                                        ${isUsedByOtherCategory ? 'disabled' : ''}
                                        onchange="updateSelectedCount(); saveDeductionSelection(${entryId}, '${sourceType}', ${entryIdValue}, this.checked)"
                                    >
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900 mb-1">${displayText} ${isUsedByOtherCategory ? '<span class="text-xs text-yellow-700 bg-yellow-200 px-2 py-1 rounded ml-2">Used by ' + (usedByCategory || 'another category') + '</span>' : ''}</div>
                                        <div class="text-sm text-gray-600 mb-2">${details.length > 80 ? details.substring(0, 80) + '...' : details}</div>
                                        <div class="text-lg font-bold text-maroon">Amount: ${formatNumber(amount)}</div>
                                    </div>
                                </label>
                            `;
                                }
                            });

                            entriesHtml += '</div>';
                            modalBody.innerHTML = entriesHtml;

                            // Reset select all checkbox
                            const selectAllCheckbox = document.getElementById('selectAllEntries');
                            if (selectAllCheckbox) {
                                selectAllCheckbox.checked = false;
                            }

                            // Update selected count
                            updateSelectedCount();
                        }
                    } else {
                        modalBody.innerHTML = '<div class="text-center py-8"><div class="text-gray-500">No entries found</div></div>';
                    }
                })
                .catch(error => {
                    console.error('Error loading entries:', error);
                    modalBody.innerHTML = '<div class="text-center py-8"><div class="text-red-500">Error loading entries. Please try again.</div></div>';
                });
        }

        // Sync deduction_selections localStorage keys from deduction_sources after PR save
        // (DELETE+INSERT in save_purchase_request.php assigns new IDs; this keeps selections in sync)
        function syncDeductionSelectionsFromSources(departmentId) {
            if (!departmentId) return;
            const mainTableRows = document.querySelectorAll('[id^="entryRow_"]');
            mainTableRows.forEach(row => {
                const domEntryId = row.id.split('_')[1];
                const deductionSourcesKey = getDeductionSourcesKey(departmentId, domEntryId);
                const savedSources = localStorage.getItem(deductionSourcesKey);
                if (!savedSources) return;
                try {
                    const sources = JSON.parse(savedSources);
                    sources.forEach(ds => {
                        if (!ds.entries || ds.entries.length === 0) return;
                        const selKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${domEntryId}_source_${ds.sourceType}`;
                        const newIds = ds.entries.map(e => e.sourceEntryId);
                        localStorage.setItem(selKey, JSON.stringify(newIds));
                        console.log(`✓ Synced deduction_selections for entry ${domEntryId} source ${ds.sourceType}: [${newIds.join(',')}]`);
                    });
                } catch (e) { /* ignore */ }
            });
        }

        // Function to close deduction entry modal
        function closeDeductionEntryModal() {
            const modal = document.getElementById('deductionEntryModal');
            if (modal) {
                modal.classList.add('hidden');
            }
            currentDeductionEntryId = null;
            currentDeductionSourceType = null;

            // DO NOT clear checkboxes here - they should persist based on actual deduction state
            // Checkboxes are only cleared/unchecked when the deduction is actually removed
            // (e.g., when entry is deleted or deduction amount is cleared)

            // Just reset the select all checkbox and update count display
            const selectAllCheckbox = document.getElementById('selectAllEntries');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = false;
            }
            updateSelectedCount();
        }

        // Function to toggle select all entries
        function toggleSelectAllEntries() {
            const selectAllCheckbox = document.getElementById('selectAllEntries');
            const checkboxes = document.querySelectorAll('.entry-checkbox');

            if (selectAllCheckbox) {
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateSelectedCount();
            }
        }

        // Function to update selected count and total
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.entry-checkbox:checked');
            const count = checkboxes.length;
            const countElement = document.getElementById('selectedCount');
            const totalElement = document.getElementById('selectedTotalAmount');

            if (countElement) {
                countElement.textContent = count;
            }

            // Calculate total amount
            let total = 0;
            checkboxes.forEach(checkbox => {
                const amount = parseFloat(checkbox.getAttribute('data-amount') || 0);
                total += amount;
            });

            if (totalElement) {
                totalElement.textContent = formatNumber(total);
            }

            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.entry-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllEntries');
            if (selectAllCheckbox && allCheckboxes.length > 0) {
                selectAllCheckbox.checked = count === allCheckboxes.length;
            }
        }

        // Function to save deduction selection state and update deduction amount
        function saveDeductionSelection(entryId, sourceType, sourceEntryId, isChecked) {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) return;

            const storageKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${entryId}_source_${sourceType}`;
            let savedSelections = [];

            const saved = localStorage.getItem(storageKey);
            if (saved) {
                try {
                    savedSelections = JSON.parse(saved);
                } catch (e) {
                    savedSelections = [];
                }
            }

            // Get the checkbox to find the amount
            const checkbox = document.querySelector(`.entry-checkbox[data-entry-id="${sourceEntryId}"][data-source-type="${sourceType}"]`);
            const amount = checkbox ? parseFloat(checkbox.getAttribute('data-amount') || 0) : 0;

            // Get current deduction value
            const deductionInput = document.getElementById(`deduction_${entryId}`);
            if (!deductionInput) return;

            const currentDeduction = parseAmount(deductionInput.value || '0');
            let newDeduction = currentDeduction;

            // Check if this entry was previously applied (added via "Add Selected")
            const deductionSourcesKey = getDeductionSourcesKey(departmentId, entryId);
            const savedSources = localStorage.getItem(deductionSourcesKey);
            let wasApplied = false;
            let appliedAmount = 0;

            if (savedSources) {
                try {
                    const deductionSources = JSON.parse(savedSources);
                    deductionSources.forEach(ds => {
                        if (ds.sourceType === sourceType && ds.categoryEntryId === entryId) {
                            const foundEntry = ds.entries.find(e => {
                                const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                const sId = parseInt(sourceEntryId) || sourceEntryId;
                                return eId === sId || String(eId) === String(sId) || e.sourceEntryId === sourceEntryId;
                            });
                            if (foundEntry) {
                                wasApplied = true;
                                appliedAmount = parseFloat(foundEntry.amount) || 0;
                            }
                        }
                    });
                } catch (e) {
                    console.error('Error checking applied entries:', e);
                }
            }

            if (isChecked) {
                // Add to selections if not already present
                const exists = savedSelections.some(id =>
                    parseInt(id) === parseInt(sourceEntryId) ||
                    String(id) === String(sourceEntryId) ||
                    id === sourceEntryId
                );
                if (!exists) {
                    savedSelections.push(sourceEntryId);
                }
                // Don't add amount immediately when checking - wait for "Add Selected" button
                // Exception: If it was previously applied and then removed (unchecked), restore it when re-checking
                if (wasApplied && appliedAmount > 0) {
                    // Check if this amount is already reflected in the deduction
                    // We'll add it back since it was previously applied but removed when unchecked
                    newDeduction = currentDeduction + appliedAmount;
                }
            } else {
                // Remove from selections
                savedSelections = savedSelections.filter(id => {
                    const normalizedId = parseInt(id) || id;
                    const normalizedSourceId = parseInt(sourceEntryId) || sourceEntryId;
                    return normalizedId !== normalizedSourceId &&
                        String(normalizedId) !== String(normalizedSourceId) &&
                        id !== sourceEntryId;
                });

                // Subtract amount from deduction if it was previously applied
                if (wasApplied && appliedAmount > 0) {
                    newDeduction = Math.max(0, currentDeduction - appliedAmount);
                }

                // Also remove from deduction sources tracking
                if (savedSources) {
                    try {
                        let deductionSources = JSON.parse(savedSources);
                        deductionSources = deductionSources.map(ds => {
                            if (ds.sourceType === sourceType && ds.categoryEntryId === entryId) {
                                // Remove this entry from the sources
                                const beforeCount = ds.entries.length;
                                ds.entries = ds.entries.filter(e => {
                                    const eId = parseInt(e.sourceEntryId) || e.sourceEntryId;
                                    const sId = parseInt(sourceEntryId) || sourceEntryId;
                                    return eId !== sId && String(eId) !== String(sId) && e.sourceEntryId !== sourceEntryId;
                                });
                                // Recalculate total amount
                                ds.amount = ds.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);
                            }
                            return ds;
                        }).filter(ds => ds.amount > 0 && ds.entries.length > 0); // Remove sources with 0 amount or no entries

                        localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
                    } catch (e) {
                        console.error('Error updating deduction sources:', e);
                    }
                }
            }

            localStorage.setItem(storageKey, JSON.stringify(savedSelections));

            // Update deduction field
            if (newDeduction !== currentDeduction) {
                deductionInput.value = newDeduction > 0 ? formatNumber(newDeduction) : '';

                // Trigger input event to recalculate totals
                deductionInput.dispatchEvent(new Event('input', { bubbles: true }));

                // Recalculate row total
                calculateRowTotal(entryId);

                // Recalculate all totals
                calculateTotals();

                // Update selected count
                updateSelectedCount();

                // Save to database
                saveUtilizationToLocalStorage();

                const logAmount = isChecked && wasApplied ? appliedAmount : (!isChecked && wasApplied ? appliedAmount : amount);
                console.log(`${isChecked ? (wasApplied ? 'Restored' : 'Selected') : 'Removed'} ${formatNumber(logAmount)} ${isChecked ? (wasApplied ? 'to' : '') : 'from'} deduction for entry ${entryId}. New total: ${formatNumber(newDeduction)}`);
            }
        }

        // Function to add all selected deductions
        function addSelectedDeductions() {
            if (!currentDeductionEntryId) return;

            const deductionInput = document.getElementById(`deduction_${currentDeductionEntryId}`);
            if (!deductionInput) return;

            // Get category name for tracking
            const columnArea = document.getElementById(`columnArea_${currentDeductionEntryId}`);
            const categoryName = columnArea ? columnArea.value : `ENTRY ${currentDeductionEntryId}`;

            const checkboxes = document.querySelectorAll('.entry-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Please select at least one entry.');
                return;
            }

            // Get department ID
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) return;

            console.log('=== ADD SELECTED DEDUCTIONS START ===');
            console.log('Entry ID:', currentDeductionEntryId);
            console.log('Category Name:', categoryName);
            console.log('Source Type:', currentDeductionSourceType);
            console.log('Checked boxes:', checkboxes.length);

            // Load existing deduction sources to check what's already been added
            const deductionSourcesKey = getDeductionSourcesKey(departmentId, currentDeductionEntryId);
            let deductionSources = [];
            const saved = localStorage.getItem(deductionSourcesKey);
            if (saved) {
                try {
                    deductionSources = JSON.parse(saved);
                    console.log('Existing deduction sources loaded:', deductionSources);
                } catch (e) {
                    deductionSources = [];
                    console.error('Error parsing existing deduction sources:', e);
                }
            } else {
                console.log('No existing deduction sources found');
            }

            // Get current deduction value
            const currentDeduction = parseAmount(deductionInput.value || '0');

            // Calculate total amount from ONLY NEW selected entries
            let totalAmountToAdd = 0;
            const newEntriesToAdd = [];

            checkboxes.forEach(checkbox => {
                const amount = parseFloat(checkbox.getAttribute('data-amount') || 0);
                const sourceEntryId = checkbox.getAttribute('data-entry-id');
                const sourceType = checkbox.getAttribute('data-source-type');

                console.log(`Checkbox: entryId=${sourceEntryId}, amount=${amount}, sourceType=${sourceType}`);

                // Check if this entry already exists in deduction_sources
                let alreadyExists = false;
                deductionSources.forEach(ds => {
                    // Compare both as strings to handle type mismatches
                    const dsEntryId = String(ds.categoryEntryId);
                    const currentEntryId = String(currentDeductionEntryId);
                    
                    if (ds.sourceType === sourceType && dsEntryId === currentEntryId) {
                        const found = ds.entries.some(e => {
                            const eId = String(e.sourceEntryId);
                            const sId = String(sourceEntryId);
                            return eId === sId || parseInt(eId) === parseInt(sId);
                        });
                        if (found) {
                            alreadyExists = true;
                        }
                    }
                });

                // Only add if it doesn't already exist
                if (!alreadyExists) {
                    totalAmountToAdd += amount;
                    newEntriesToAdd.push({
                        sourceEntryId: sourceEntryId,
                        amount: amount,
                        sourceType: sourceType
                    });
                } else {
                    console.log(`⚠ Skipping entry ${sourceEntryId} - already added to this category`);
                }
            });

            // If no new entries to add, show message and return
            if (newEntriesToAdd.length === 0) {
                alert('All selected entries have already been added to this category.');
                return;
            }

            console.log(`Total amount to add from NEW entries: ${totalAmountToAdd}`);
            console.log(`Current deduction value: ${currentDeduction}`);

            // Calculate what the deduction SHOULD be based on deduction sources
            // This ensures we're working with the correct base value
            let calculatedDeduction = 0;
            deductionSources.forEach(ds => {
                const dsEntryId = String(ds.categoryEntryId);
                const currentEntryId = String(currentDeductionEntryId);
                if (dsEntryId === currentEntryId) {
                    calculatedDeduction += parseFloat(ds.amount) || 0;
                }
            });
            
            console.log(`Calculated deduction from existing sources: ${calculatedDeduction}`);

            // Update deduction field with calculated amount + new amounts
            const newDeduction = calculatedDeduction + totalAmountToAdd;
            console.log(`New deduction will be: ${newDeduction} (${calculatedDeduction} + ${totalAmountToAdd})`);
            deductionInput.value = formatNumber(newDeduction);

            // Group new entries by source type
            const sourceGroups = {};
            newEntriesToAdd.forEach(entry => {
                if (!sourceGroups[entry.sourceType]) {
                    sourceGroups[entry.sourceType] = [];
                }
                sourceGroups[entry.sourceType].push({
                    sourceEntryId: entry.sourceEntryId,
                    amount: entry.amount
                });
            });

            console.log('Source groups to add:', sourceGroups);

            // Add or update deduction sources with ONLY new entries
            Object.keys(sourceGroups).forEach(sourceType => {
                const group = sourceGroups[sourceType];
                const groupTotal = group.reduce((sum, e) => sum + e.amount, 0);

                console.log(`Processing source type: ${sourceType}, total: ${groupTotal}, entries:`, group);

                // Check if source type already exists for this category
                // IMPORTANT: Compare both as strings to handle type mismatches
                const existingIndex = deductionSources.findIndex(ds => {
                    const dsEntryId = String(ds.categoryEntryId);
                    const currentEntryId = String(currentDeductionEntryId);
                    return dsEntryId === currentEntryId && ds.sourceType === sourceType;
                });

                if (existingIndex >= 0) {
                    // Update existing source - REPLACE entries instead of adding to them
                    // This prevents duplicates when re-selecting the same entries
                    const existing = deductionSources[existingIndex];
                    console.log(`Updating existing source at index ${existingIndex}:`, existing);
                    
                    // Merge new entries with existing ones, avoiding duplicates
                    group.forEach(newEntry => {
                        const alreadyExists = existing.entries.some(e => {
                            const eId = String(e.sourceEntryId);
                            const nId = String(newEntry.sourceEntryId);
                            return eId === nId;
                        });
                        
                        if (!alreadyExists) {
                            existing.entries.push(newEntry);
                        }
                    });
                    
                    // Recalculate total amount from all entries
                    existing.amount = existing.entries.reduce((sum, e) => sum + (parseFloat(e.amount) || 0), 0);
                    
                    console.log(`Updated source:`, existing);
                } else {
                    // Add new source type
                    const newSource = {
                        categoryEntryId: currentDeductionEntryId,
                        categoryName: categoryName,
                        sourceType: sourceType,
                        amount: groupTotal,
                        entries: group
                    };
                    console.log(`Adding new source:`, newSource);
                    deductionSources.push(newSource);
                }
            });

            console.log('Final deduction sources before saving:', deductionSources);
            localStorage.setItem(deductionSourcesKey, JSON.stringify(deductionSources));
            console.log('Saved to localStorage key:', deductionSourcesKey);
            console.log('=== ADD SELECTED DEDUCTIONS END ===');

            // Trigger input event to recalculate totals
            deductionInput.dispatchEvent(new Event('input', { bubbles: true }));

            // Persist applied selections so checkboxes remain checked when modal is reopened
            const storageKey = `deduction_selections_user_${CURRENT_USER_ID}_dept_${departmentId}_entry_${currentDeductionEntryId}_source_${currentDeductionSourceType}`;
            const appliedIds = [];
            deductionSources.forEach(ds => {
                if (ds.sourceType === currentDeductionSourceType && String(ds.categoryEntryId) === String(currentDeductionEntryId)) {
                    ds.entries.forEach(e => appliedIds.push(e.sourceEntryId));
                }
            });
            localStorage.setItem(storageKey, JSON.stringify(appliedIds));
            console.log(`✓ Updated deduction selections for entry ${currentDeductionEntryId} with ${appliedIds.length} applied entries`);

            // Close modal
            closeDeductionEntryModal();

            // Close source menu
            document.querySelectorAll('[id^="deductionSourceMenu_"]').forEach(menu => {
                menu.classList.add('hidden');
            });

            // Recalculate row total
            calculateRowTotal(currentDeductionEntryId);

            // Recalculate all totals
            calculateTotals();

            // Save to database immediately to persist deductions
            saveUtilizationToLocalStorage();

            console.log(`✓ Added ${formatNumber(totalAmountToAdd)} from ${newEntriesToAdd.length} new entries to deduction for entry ${currentDeductionEntryId}. New total: ${formatNumber(newDeduction)}`);
        }

        // Function to add deduction from selected entry
        function addDeductionFromEntry(entryId, sourceType, entrySourceId, amount) {
            const deductionInput = document.getElementById(`deduction_${entryId}`);
            if (!deductionInput) return;

            // Get current deduction value
            const currentDeduction = parseAmount(deductionInput.value || '0');
            const newDeduction = currentDeduction + amount;

            // Update deduction field
            deductionInput.value = formatNumber(newDeduction);

            // Trigger input event to recalculate totals
            deductionInput.dispatchEvent(new Event('input', { bubbles: true }));

            // Close modal
            closeDeductionEntryModal();

            // Close source menu
            document.querySelectorAll('[id^="deductionSourceMenu_"]').forEach(menu => {
                menu.classList.add('hidden');
            });

            // Recalculate row total
            calculateRowTotal(entryId);

            // Recalculate all totals
            calculateTotals();

            // Save to localStorage
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);
            if (departmentId) {
                saveUtilizationToLocalStorage();
            }
        }

        // Close menus when clicking outside
        document.addEventListener('click', function (event) {
            if (!event.target.closest('[id^="deductionSourceMenu_"]') &&
                !event.target.closest('button[onclick*="showDeductionSourceMenu"]')) {
                document.querySelectorAll('[id^="deductionSourceMenu_"]').forEach(menu => {
                    menu.classList.add('hidden');
                });
            }
        });

        // Close deduction source menus when scrolling
        let scrollTimeout;
        window.addEventListener('scroll', function() {
            // Clear existing timeout
            clearTimeout(scrollTimeout);
            
            // Set a small delay to avoid closing immediately on minor scrolls
            scrollTimeout = setTimeout(function() {
                document.querySelectorAll('[id^="deductionSourceMenu_"]').forEach(menu => {
                    if (!menu.classList.contains('hidden')) {
                        menu.classList.add('hidden');
                    }
                });
            }, 50);
        }, true); // Use capture phase to catch all scroll events

        // Close deduction entry modal when clicking outside
        document.addEventListener('click', function (event) {
            const modal = document.getElementById('deductionEntryModal');
            if (modal && !modal.classList.contains('hidden')) {
                if (event.target === modal) {
                    closeDeductionEntryModal();
                }
            }
        });

        // Close modal on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('deductionEntryModal');
                if (modal && !modal.classList.contains('hidden')) {
                    closeDeductionEntryModal();
                }
            }
        });

        // ==========================================
        // PRIOR YEARS FUNCTIONS
        // ==========================================
        let priorYearsCounter = 0;
        let priorYearsLiveEnabled = false;
         let _priorYearsTotalsCache = {}; // persists totals even when modal is closed

        let currentPriorYearsFiscalYear = new Date().getFullYear();

        function initPriorYearsFiscalYearSelect() {
            const select = document.getElementById('priorYearsFiscalYear');
            if (!select) return;
            select.innerHTML = '';
            const currentYear = new Date().getFullYear();
            for (let y = currentYear + 1; y >= currentYear - 5; y--) {
                const opt = document.createElement('option');
                opt.value = y;
                opt.textContent = y;
                if (y === currentPriorYearsFiscalYear) opt.selected = true;
                select.appendChild(opt);
            }
        }

        function onPriorYearsFiscalYearChange() {
            const select = document.getElementById('priorYearsFiscalYear');
            if (!select) return;
            currentPriorYearsFiscalYear = parseInt(select.value);
            const departmentId = _priorYearsDeptId || _getDeptId();
            if (departmentId) {
                const tbody = document.getElementById('priorYearsTableBody');
                if (tbody) tbody.innerHTML = '';
                priorYearsCounter = 0;
                loadPriorYearsEntries(departmentId);
            }
        }

        let _priorYearsDeptId = null;

        function handlePriorYears() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) {
                alert('Please select a department or office first.');
                return;
            }

            _priorYearsDeptId = departmentId;

            const modal = document.getElementById('priorYearsModal');
            if (modal) {
                modal.classList.remove('hidden');
                const tbody = document.getElementById('priorYearsTableBody');
                if (tbody) {
                    tbody.innerHTML = '';
                }
                priorYearsCounter = 0;

                initPriorYearsFiscalYearSelect();
                // Load existing entries from database first
                loadPriorYearsEntries(departmentId);
            }
        }

        function closePriorYearsModal() {
            const modal = document.getElementById('priorYearsModal');
            if (modal) {
                modal.classList.add('hidden');
            }
        }

        function syncPriorYearsCategories() {
            // Get all expense categories from the utilization table
            const categories = [];
            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const categoryInput = document.getElementById(`columnArea_${entryId}`);
                if (categoryInput && categoryInput.value && categoryInput.value.trim()) {
                    categories.push(categoryInput.value.trim());
                }
            });

            // Get existing prior years categories (never remove them)
            const existingCategories = new Set();
            document.querySelectorAll('[id^="priorYearRow_"]').forEach(row => {
                const entryId = row.id.split('_').pop();
                const catInput = document.getElementById(`priorYearCategory_${entryId}`);
                if (catInput) {
                    existingCategories.add(catInput.value.trim().toLowerCase());
                }
            });

            // Only ADD utilization categories not already present in prior years
            categories.forEach(category => {
                if (!existingCategories.has(category.toLowerCase())) {
                    addPriorYearsEntry(category);
                }
            });

            calculatePriorYearsTotals();
        }

        function addPriorYearsEntry(categoryName = '', data = {}) {
            priorYearsCounter++;
            const entryId = priorYearsCounter;
            const tbody = document.getElementById('priorYearsTableBody');
            if (!tbody) return;

            const row = document.createElement('tr');
            row.id = `priorYearRow_${entryId}`;
            row.className = 'hover:bg-orange-50 transition-colors';

            const isSynced = categoryName !== '';

            row.innerHTML = `
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearCategory_${entryId}" value="${categoryName}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-[13px] font-medium ${isSynced ? 'bg-orange-50' : ''}" 
                        placeholder="Expense category..." ${isSynced ? 'readonly' : ''}>
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearStudentDev_${entryId}" value="${formatPriorYearDisplay(data.student_development || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearFacultyDev_${entryId}" value="${formatPriorYearDisplay(data.faculty_development || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearCurriculumDev_${entryId}" value="${formatPriorYearDisplay(data.curriculum_development || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearFacilitiesDev_${entryId}" value="${formatPriorYearDisplay(data.facilities_development || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearDevFee_${entryId}" value="${formatPriorYearDisplay(data.development_fee || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearLabFee_${entryId}" value="${formatPriorYearDisplay(data.laboratory_fee || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
                <td class="py-1.5 px-4">
                    <input type="text" id="priorYearCompFee_${entryId}" value="${formatPriorYearDisplay(data.computer_fee || 0)}" 
                        class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                        placeholder="0" oninput="autoFormatNumber(this); calculatePriorYearsTotals()" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">
                </td>
            `;

            tbody.appendChild(row);

            // Append cells for any custom columns already added
            priorYearsCustomColumns.forEach(col => {
                const td = document.createElement('td');
                td.className = 'py-1.5 px-4';
                td.innerHTML = `<input type="text" id="priorYear_${col.key}_${entryId}" value=""
                    class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]"
                    placeholder="0" oninput="autoFormatNumber(this); recalcCustomColumn('${col.key}')" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">`;
                row.appendChild(td);
            });

            calculatePriorYearsTotals();
        }

        function removePriorYearsEntry(entryId) {
            const row = document.getElementById(`priorYearRow_${entryId}`);
            if (row) {
                row.remove();
                calculatePriorYearsTotals();
            }
        }

        let priorYearsCustomColumns = [];

        function _getDeptId() {
            const ds = document.getElementById('departmentSelect');
            const os = document.getElementById('officeSelect');
            return (ds && ds.value) ? ds.value : (os && os.value ? os.value : null);
        }

        function addPriorYearsColumn() {
            const colName = prompt('Enter column name:');
            if (!colName || !colName.trim()) return;

            const name = colName.trim();
            const colKey = 'custom_' + Date.now();
            priorYearsCustomColumns.push({ key: colKey, name: name });

            _renderCustomColumn(colKey, name);

            // Persist to DB
            const deptId = _getDeptId();
            if (deptId) {
                fetch('../api/save_prior_years.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_column',
                        department_id: deptId,
                        fiscal_year: currentPriorYearsFiscalYear,
                        col_key: colKey,
                        col_name: name,
                        sort_order: priorYearsCustomColumns.length
                    })
                }).catch(e => console.error('Error saving column:', e));
            }
        }

        function deletePriorYearsColumn(colKey) {
            if (!confirm('Delete this column?')) return;

            priorYearsCustomColumns = priorYearsCustomColumns.filter(c => c.key !== colKey);

            const th = document.getElementById(`priorYearsTh_${colKey}`);
            if (th) th.remove();
            const totalTd = document.getElementById(`priorYearsTotal_${colKey}`);
            if (totalTd) totalTd.remove();
            document.querySelectorAll(`[id^="priorYearCell_${colKey}_"]`).forEach(td => td.remove());

            // Persist deletion to DB
            const deptId = _getDeptId();
            if (deptId) {
                fetch('../api/save_prior_years.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_column',
                        department_id: deptId,
                        fiscal_year: currentPriorYearsFiscalYear,
                        col_key: colKey
                    })
                }).catch(e => console.error('Error deleting column:', e));
            }
        }

        function recalcCustomColumn(colKey) {
            let total = 0;
            document.querySelectorAll(`[id^="priorYear_${colKey}_"]`).forEach(input => {
                total += parsePriorYearAmount(input.value);
            });
            const totalEl = document.getElementById(`priorYearsTotal_${colKey}`);
            if (totalEl) {
                totalEl.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
        }

        let _saveColValTimer = {};
        function saveCustomColumnValues(colKey) {
            clearTimeout(_saveColValTimer[colKey]);
            _saveColValTimer[colKey] = setTimeout(() => {
                const deptId = _getDeptId();
                if (!deptId) return;
                const values = {};
                document.querySelectorAll('[id^="priorYearRow_"]').forEach(row => {
                    const entryId = row.id.split('_').pop();
                    const catInput = document.getElementById(`priorYearCategory_${entryId}`);
                    const input = document.getElementById(`priorYear_${colKey}_${entryId}`);
                    if (catInput && input) {
                        values[catInput.value.trim()] = parsePriorYearAmount(input.value);
                    }
                });
                fetch('../api/save_prior_years.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save_column_values',
                        department_id: deptId,
                        fiscal_year: currentPriorYearsFiscalYear,
                        col_key: colKey,
                        values: values
                    })
                }).catch(e => console.error('Error saving column values:', e));
            }, 800);
        }

        function autoFormatNumber(input) {
            const cursorPos = input.selectionStart;
            const oldLen = input.value.length;
            let raw = input.value.replace(/[^0-9.]/g, '');
            const parts = raw.split('.');
            if (parts.length > 2) raw = parts[0] + '.' + parts.slice(1).join('');
            if (raw === '' || raw === '.') { input.value = raw; return; }
            const intPart = parts[0];
            const decPart = parts.length > 1 ? parts[1] : null;
            const formatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            input.value = decPart !== null ? formatted + '.' + decPart : formatted;
            const diff = input.value.length - oldLen;
            input.setSelectionRange(Math.max(0, cursorPos + diff), Math.max(0, cursorPos + diff));
        }

        function onPriorYearFocus(input) {
            let val = input.value.replace(/[₱\s]/g, '');
            if (val === '0' || val === '0.00') val = '';
            input.value = val;
        }

        function onPriorYearBlur(input) {
            let val = input.value.replace(/[^0-9.,]/g, '');
            if (val === '' || val === '.' || val === '0') { input.value = ''; return; }
            const num = parseFloat(val.replace(/,/g, '')) || 0;
            if (num === 0) { input.value = ''; return; }
            input.value = num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function formatPriorYearDisplay(value) {
            const num = parseFloat(String(value).replace(/[₱,\s]/g, '')) || 0;
            if (num === 0) return '';
            return num.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        }

        function parsePriorYearAmount(value) {
            return parseFloat(String(value).replace(/[₱,\s]/g, '')) || 0;
        }

        function calculatePriorYearsTotals() {
            const columns = ['StudentDev', 'FacultyDev', 'CurriculumDev', 'FacilitiesDev', 'DevFee', 'LabFee', 'CompFee'];
            const fieldNames = ['StudentDev', 'FacultyDev', 'CurriculumDev', 'FacilitiesDev', 'DevFee', 'LabFee', 'CompFee'];

            columns.forEach((col, idx) => {
                let total = 0;
                const inputs = document.querySelectorAll(`[id^="priorYear${fieldNames[idx]}_"]`);
                inputs.forEach(input => {
                    total += parsePriorYearAmount(input.value);
                });
                const totalEl = document.getElementById(`priorYearsTotal${col}`);
                if (totalEl) {
                    totalEl.textContent = '₱' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                }
            });

            // Auto-update allocated budgets in real-time as user types
            if (priorYearsLiveEnabled) {
                updateBudgetFromPriorYearsLive();
            }
        }

        async function savePriorYearsEntries() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) {
                alert('Please select a department or office first.');
                return;
            }

            const entries = [];
            const rows = document.querySelectorAll('[id^="priorYearRow_"]');
            rows.forEach(row => {
                const entryId = row.id.split('_').pop();
                const category = document.getElementById(`priorYearCategory_${entryId}`)?.value?.trim() || '';
                if (!category) return;

                entries.push({
                    expense_category: category,
                    student_development: parsePriorYearAmount(document.getElementById(`priorYearStudentDev_${entryId}`)?.value || '0'),
                    faculty_development: parsePriorYearAmount(document.getElementById(`priorYearFacultyDev_${entryId}`)?.value || '0'),
                    curriculum_development: parsePriorYearAmount(document.getElementById(`priorYearCurriculumDev_${entryId}`)?.value || '0'),
                    facilities_development: parsePriorYearAmount(document.getElementById(`priorYearFacilitiesDev_${entryId}`)?.value || '0'),
                    development_fee: parsePriorYearAmount(document.getElementById(`priorYearDevFee_${entryId}`)?.value || '0'),
                    laboratory_fee: parsePriorYearAmount(document.getElementById(`priorYearLabFee_${entryId}`)?.value || '0'),
                    computer_fee: parsePriorYearAmount(document.getElementById(`priorYearCompFee_${entryId}`)?.value || '0')
                });
            });

            try {
                const response = await fetch('../api/save_prior_years.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'save',
                        department_id: departmentId,
                        fiscal_year: currentPriorYearsFiscalYear,
                        entries: entries
                    })
                });

                const result = await response.json();
                if (result.success) {
                    // Build cache BEFORE closing modal (DOM rows still exist here)
                    _priorYearsTotalsCache = getPriorYearsCategoryTotals();

                    // Close modal
                    closePriorYearsModal();

                    // Apply prior years to all allocated budgets
                    // data-prior-addition tracks what was previously added, so math is always correct
                    applyPriorYearsToAllocated(null, true);

                    // Show success notification (non-blocking)
                    showPriorYearsNotification('Prior years entries saved successfully!', 'success');
                } else {
                    alert('Error saving: ' + (result.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Error saving prior years:', e);
                alert('Error saving prior years entries. Please try again.');
            }
        }

        async function deleteAllPriorYears() {
            const departmentSelect = document.getElementById('departmentSelect');
            const officeSelect = document.getElementById('officeSelect');
            const departmentId = (departmentSelect && departmentSelect.value) ? departmentSelect.value : (officeSelect && officeSelect.value ? officeSelect.value : null);

            if (!departmentId) {
                alert('Please select a department or office first.');
                return;
            }

            if (!confirm(`⚠️ Are you sure you want to delete ALL prior years entries for fiscal year ${currentPriorYearsFiscalYear}?\n\nThis action cannot be undone!`)) {
                return;
            }

            try {
                const response = await fetch('../api/save_prior_years.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_year',
                        department_id: departmentId,
                        fiscal_year: currentPriorYearsFiscalYear
                    })
                });

                const result = await response.json();
                if (result.success) {
                    // Clear the table
                    document.getElementById('priorYearsTableBody').innerHTML = '';
                    priorYearsCounter = 0;
                    
                    // Clear cache
                    _priorYearsTotalsCache = {};
                    
                    // Reset totals
                    updatePriorYearsTotals();
                    
                    // Remove prior years from allocated budgets
                    applyPriorYearsToAllocated(null, true);
                    
                    // Show success notification
                    showPriorYearsNotification('All prior years entries deleted successfully!', 'success');
                } else {
                    alert('Error deleting: ' + (result.message || 'Unknown error'));
                }
            } catch (e) {
                console.error('Error deleting prior years:', e);
                alert('Error deleting prior years entries. Please try again.');
            }
        }

        function showPriorYearsNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-6 right-6 z-[200] px-6 py-4 rounded-xl shadow-2xl text-white font-semibold text-sm flex items-center gap-3 transform translate-x-full transition-transform duration-500 ${type === 'success' ? 'bg-gradient-to-r from-green-500 to-green-600' : 'bg-gradient-to-r from-red-500 to-red-600'}`;
            notification.innerHTML = `
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    ${type === 'success' ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>' : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>'}
                </svg>
                ${message}
            `;
            document.body.appendChild(notification);

            // Slide in
            requestAnimationFrame(() => {
                notification.style.transform = 'translateX(0)';
            });

            // Auto-remove after 3 seconds
            setTimeout(() => {
                notification.style.transform = 'translateX(120%)';
                setTimeout(() => notification.remove(), 500);
            }, 3000);
        }

        // Build a map of category -> total prior years amount from all prior year rows
        function getPriorYearsCategoryTotals() {
            const categoryTotals = {};
            const priorRows = document.querySelectorAll('[id^="priorYearRow_"]');
            priorRows.forEach(row => {
                const entryId = row.id.split('_').pop();
                const catInput = document.getElementById(`priorYearCategory_${entryId}`);
                if (!catInput) return;
                const category = catInput.value.trim().toLowerCase();
                if (!category) return;

                const total =
                    parsePriorYearAmount(document.getElementById(`priorYearStudentDev_${entryId}`)?.value || '0') +
                    parsePriorYearAmount(document.getElementById(`priorYearFacultyDev_${entryId}`)?.value || '0') +
                    parsePriorYearAmount(document.getElementById(`priorYearCurriculumDev_${entryId}`)?.value || '0') +
                    parsePriorYearAmount(document.getElementById(`priorYearFacilitiesDev_${entryId}`)?.value || '0') +
                    parsePriorYearAmount(document.getElementById(`priorYearDevFee_${entryId}`)?.value || '0') +
                    parsePriorYearAmount(document.getElementById(`priorYearLabFee_${entryId}`)?.value || '0') +
                    parsePriorYearAmount(document.getElementById(`priorYearCompFee_${entryId}`)?.value || '0');

                categoryTotals[category] = (categoryTotals[category] || 0) + total;
            });
            return categoryTotals;


        }

        // Initialize data-prior-addition on budget inputs based on loaded DB data
        // This registers what additions are already baked into the budget values
        // WITHOUT changing the budget values themselves
        function initPriorYearsDeductions() {
            const categoryTotals = getPriorYearsCategoryTotals();

            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const categoryInput = document.getElementById(`columnArea_${entryId}`);
                if (!categoryInput) return;
                const category = categoryInput.value.trim().toLowerCase();
                if (!category) return;

                const allocatedInput = document.getElementById(`budgetAllocated_${entryId}`);
                if (!allocatedInput) return;

                const totalPriorAmount = categoryTotals[category] || 0;
                // Register existing addition — don't change the budget value
                allocatedInput.setAttribute('data-prior-addition', totalPriorAmount.toString());
            });
        }

        // Live-update allocated budgets as user types in prior year fields
        // Prior years amounts ADD to the allocated budget
        function updateBudgetFromPriorYearsLive() {
            const categoryTotals = getPriorYearsCategoryTotals();
            _priorYearsTotalsCache = categoryTotals; // keep cache in sync
            const utilizationRows = document.querySelectorAll('[id^="entryRow_"]');
            utilizationRows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const categoryInput = document.getElementById(`columnArea_${entryId}`);
                if (!categoryInput) return;
                const category = categoryInput.value.trim().toLowerCase();
                if (!category) return;

                const allocatedInput = document.getElementById(`budgetAllocated_${entryId}`);
                if (!allocatedInput) return;

                const totalPriorAmount = categoryTotals[category] || 0;
                const oldAddition = parseFloat(allocatedInput.getAttribute('data-prior-addition') || '0') || 0;

                // Only update if the addition actually changed
                if (totalPriorAmount === oldAddition) return;

                const currentAllocated = parseFloat(allocatedInput.value.replace(/[₱,\s]/g, '')) || 0;

                // Remove old addition to get original base, then add the new amount
                // baseBudget = currentAllocated - oldAddition (undo previous addition)
                // newAllocated = baseBudget + totalPriorAmount (apply new addition)
                const baseBudget = currentAllocated - oldAddition;
                const newAllocated = baseBudget + totalPriorAmount;

                allocatedInput.value = newAllocated >= 0
                    ? newAllocated.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '0.00';

                // Store the new addition for next time
                allocatedInput.setAttribute('data-prior-addition', totalPriorAmount.toString());

                // Trigger events to recalculate balances
            });
        }


        // Silently fetch prior years data on page load to populate the cache.
        // Does NOT change displayed allocated budget values — the DB already has prior years baked in.
        // Only sets data-prior-addition so applyPriorYearsToAllocated won't double-add on next save.
        async function loadPriorYearsCacheInBackground(departmentId) {
            try {
                const response = await fetch(`../api/load_prior_years.php?department_id=${departmentId}&fiscal_year=${currentPriorYearsFiscalYear}`);
                const data = await response.json();
                if (!data.success || !data.entries || data.entries.length === 0) return;
                // Build category totals
                const totals = {};
                data.entries.forEach(entry => {
                    const cat = (entry.expense_category || '').trim().toLowerCase();
                    if (!cat) return;
                    const sum = (parseFloat(entry.student_development) || 0)
                        + (parseFloat(entry.faculty_development) || 0)
                        + (parseFloat(entry.curriculum_development) || 0)
                        + (parseFloat(entry.facilities_development) || 0)
                        + (parseFloat(entry.development_fee) || 0)
                        + (parseFloat(entry.laboratory_fee) || 0)
                        + (parseFloat(entry.computer_fee) || 0);
                    totals[cat] = (totals[cat] || 0) + sum;
                });
                _priorYearsTotalsCache = totals;
                // Mark each row's data-prior-addition to match what's already in DB
                // so applyPriorYearsToAllocated won't add on top of the already-saved value
                document.querySelectorAll('[id^="entryRow_"]').forEach(row => {
                    const entryId = row.id.split('_')[1];
                    const categoryInput = document.getElementById(`columnArea_${entryId}`);
                    if (!categoryInput) return;
                    const category = categoryInput.value.trim().toLowerCase();
                    if (!category) return;
                    const allocatedInput = document.getElementById(`budgetAllocated_${entryId}`);
                    if (!allocatedInput) return;
                    const priorAmount = totals[category] || 0;
                    allocatedInput.setAttribute('data-prior-addition', priorAmount.toString());
                });
            } catch (e) {
                console.error('Error loading prior years cache:', e);
            }
        }

        // Apply cached prior years totals to allocated budgets.
        // Called after save, after new rows added, after page load.
        function applyPriorYearsToAllocated(specificRows, forceApply) {
            const totals = _priorYearsTotalsCache;
            if (!totals || Object.keys(totals).length === 0) return;
            const rows = specificRows || document.querySelectorAll('[id^="entryRow_"]');
            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const categoryInput = document.getElementById(`columnArea_${entryId}`);
                if (!categoryInput) return;
                const category = categoryInput.value.trim().toLowerCase();
                if (!category) return;
                const allocatedInput = document.getElementById(`budgetAllocated_${entryId}`);
                if (!allocatedInput) return;
                const totalPriorAmount = totals[category] || 0;
                const oldAddition = parseFloat(allocatedInput.getAttribute('data-prior-addition') || '0') || 0;
                if (!forceApply && totalPriorAmount === oldAddition) return;
                const currentAllocated = parseFloat(allocatedInput.value.replace(/[₱,\s]/g, '')) || 0;


                const baseBudget = currentAllocated - oldAddition;
                const newAllocated = baseBudget + totalPriorAmount;
                allocatedInput.value = newAllocated >= 0
                    ? newAllocated.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
                    : '0.00';
                allocatedInput.setAttribute('data-prior-addition', totalPriorAmount.toString());
                if (typeof calculateRowTotal === 'function') calculateRowTotal(entryId);
            });
            if (typeof calculateTotals === 'function') calculateTotals();
            // Only persist to DB when explicitly saving (not on background page-load apply)
            if (forceApply && typeof saveUtilizationToLocalStorage === 'function') saveUtilizationToLocalStorage();
        }
        async function loadPriorYearsEntries(departmentId) {
            priorYearsLiveEnabled = false;
            try {
                const response = await fetch(`../api/load_prior_years.php?department_id=${departmentId}&fiscal_year=${currentPriorYearsFiscalYear}`);
                const data = await response.json();

                if (data.success && data.entries && data.entries.length > 0) {
                    data.entries.forEach(entry => {
                        addPriorYearsEntry(entry.expense_category, entry);
                    });
                }

                syncPriorYearsCategories();

                // Restore custom columns from DB
                priorYearsCustomColumns = [];
                if (data.custom_columns && data.custom_columns.length > 0) {
                    data.custom_columns.forEach(col => {
                        priorYearsCustomColumns.push({ key: col.col_key, name: col.col_name });
                        _renderCustomColumn(col.col_key, col.col_name);
                    });
                    const customValues = data.custom_values || {};
                    document.querySelectorAll('[id^="priorYearRow_"]').forEach(row => {
                        const entryId = row.id.split('_').pop();
                        const catInput = document.getElementById(`priorYearCategory_${entryId}`);
                        const cat = catInput ? catInput.value.trim() : '';
                        priorYearsCustomColumns.forEach(col => {
                            const input = document.getElementById(`priorYear_${col.key}_${entryId}`);
                            if (input && customValues[col.key] && customValues[col.key][cat] !== undefined) {
                                const v = parseFloat(customValues[col.key][cat]) || 0;
                                input.value = v > 0 ? v.toLocaleString('en-US', { minimumFractionDigits: 0, maximumFractionDigits: 2 }) : '';
                            }
                        });
                    });
                    priorYearsCustomColumns.forEach(col => recalcCustomColumn(col.key));
                }

                calculatePriorYearsTotals();
                _priorYearsTotalsCache = getPriorYearsCategoryTotals();
                priorYearsLiveEnabled = true;
            } catch (e) {
                console.error('Error loading prior years:', e);
                syncPriorYearsCategories();
                priorYearsLiveEnabled = true;
            }
        }

        function _renderCustomColumn(colKey, name) {
            const thead = document.querySelector('#priorYearsModal table thead tr');
            if (thead && !document.getElementById(`priorYearsTh_${colKey}`)) {
                const th = document.createElement('th');
                th.id = `priorYearsTh_${colKey}`;
                th.className = 'border-b-2 border-orange-300 py-2.5 px-4 text-right font-bold text-gray-800 uppercase text-[10px] tracking-wide';
                th.style.minWidth = '120px';
                th.innerHTML = `<div class="flex items-center justify-end gap-1"><span>${name}</span><button onclick="deletePriorYearsColumn('${colKey}')" title="Delete column" class="ml-1 text-red-400 hover:text-red-600 transition-colors flex-shrink-0"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>`;
                thead.appendChild(th);
            }
            const tfoot = document.querySelector('#priorYearsModal table tfoot tr');
            if (tfoot && !document.getElementById(`priorYearsTotal_${colKey}`)) {
                const td = document.createElement('td');
                td.id = `priorYearsTotal_${colKey}`;
                td.className = 'py-4 px-4 text-right font-bold text-orange-700 text-sm';
                td.textContent = '₱0.00';
                tfoot.appendChild(td);
            }
            document.querySelectorAll('[id^="priorYearRow_"]').forEach(row => {
                const entryId = row.id.split('_').pop();
                if (!document.getElementById(`priorYearCell_${colKey}_${entryId}`)) {
                    const td = document.createElement('td');
                    td.id = `priorYearCell_${colKey}_${entryId}`;
                    td.className = 'py-1.5 px-4';
                    td.innerHTML = `<input type="text" id="priorYear_${colKey}_${entryId}" value="" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none text-right text-[13px]" placeholder="0" oninput="autoFormatNumber(this); recalcCustomColumn('${colKey}'); saveCustomColumnValues('${colKey}')" onfocus="onPriorYearFocus(this)" onblur="onPriorYearBlur(this)">`;
                    row.appendChild(td);
                }
            });
        }

        function getPriorYearsDataForSummary() {
            const entries = [];
            const rows = document.querySelectorAll('[id^="priorYearRow_"]');
            rows.forEach(row => {
                const entryId = row.id.split('_').pop();
                const category = document.getElementById(`priorYearCategory_${entryId}`)?.value?.trim() || '';
                if (!category) return;
                entries.push({
                    expense_category: category,
                    student_development: parsePriorYearAmount(document.getElementById(`priorYearStudentDev_${entryId}`)?.value || '0'),
                    faculty_development: parsePriorYearAmount(document.getElementById(`priorYearFacultyDev_${entryId}`)?.value || '0'),
                    curriculum_development: parsePriorYearAmount(document.getElementById(`priorYearCurriculumDev_${entryId}`)?.value || '0'),
                    facilities_development: parsePriorYearAmount(document.getElementById(`priorYearFacilitiesDev_${entryId}`)?.value || '0'),
                    development_fee: parsePriorYearAmount(document.getElementById(`priorYearDevFee_${entryId}`)?.value || '0'),
                    laboratory_fee: parsePriorYearAmount(document.getElementById(`priorYearLabFee_${entryId}`)?.value || '0'),
                    computer_fee: parsePriorYearAmount(document.getElementById(`priorYearCompFee_${entryId}`)?.value || '0')
                });
            });
            return entries;
        }

        // ==========================================
        // PRIOR YEARS HISTORY
        // ==========================================
        async function showPriorYearsHistory() {
            const departmentId = _priorYearsDeptId || _getDeptId();

            if (!departmentId) {
                alert('Please select a department or office first.');
                return;
            }

            // Create history modal
            let historyModal = document.getElementById('priorYearsHistoryModal');
            if (!historyModal) {
                historyModal = document.createElement('div');
                historyModal.id = 'priorYearsHistoryModal';
                historyModal.className = 'fixed inset-0 bg-black bg-opacity-50 z-[60] hidden flex items-center justify-center p-4';
                historyModal.onclick = function (e) { if (e.target === historyModal) closePriorYearsHistory(); };
                document.body.appendChild(historyModal);
            }

            historyModal.innerHTML = `
                <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden flex flex-col">
                    <div class="bg-gradient-to-r from-orange-500 to-orange-600 px-8 py-6 flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-white">Prior Years History</h2>
                            <p class="text-orange-100 text-sm mt-1">All prior years entries across fiscal years</p>
                        </div>
                        <button onclick="closePriorYearsHistory()" class="text-white hover:text-orange-200 transition-colors p-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-8" id="priorYearsHistoryContent">
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-orange-600 mx-auto"></div>
                            <p class="text-gray-500 mt-4">Loading history...</p>
                        </div>
                    </div>
                    <div class="px-8 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button onclick="closePriorYearsHistory()" class="px-6 py-3 bg-gray-300 text-gray-700 rounded-xl hover:bg-gray-400 transition-all font-semibold">Close</button>
                    </div>
                </div>
            `;
            historyModal.classList.remove('hidden');

            try {
                const response = await fetch(`../api/load_prior_years.php?department_id=${departmentId}&all_years=1`);
                const data = await response.json();
                const container = document.getElementById('priorYearsHistoryContent');

                if (data.success && data.entries && data.entries.length > 0) {
                    // Group by fiscal year
                    const grouped = {};
                    data.entries.forEach(entry => {
                        const fy = entry.fiscal_year || 'Unknown';
                        if (!grouped[fy]) grouped[fy] = [];
                        grouped[fy].push(entry);
                    });

                    const years = Object.keys(grouped).sort((a, b) => b - a);
                    let html = '<div class="grid gap-6">';

                    years.forEach(year => {
                        const yearEntries = grouped[year];
                        const totalAll = yearEntries.reduce((sum, entry) => {
                            return sum + parseFloat(entry.student_development || 0) + parseFloat(entry.faculty_development || 0) +
                                parseFloat(entry.curriculum_development || 0) + parseFloat(entry.facilities_development || 0) +
                                parseFloat(entry.development_fee || 0) + parseFloat(entry.laboratory_fee || 0) + parseFloat(entry.computer_fee || 0);
                        }, 0);

                        html += `
                            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm hover:shadow-md transition-all p-6 flex items-center justify-between gap-6 border-l-8 border-l-orange-500">
                                <div>
                                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                                        Fiscal Year ${year}
                                        <span class="px-3 py-1 bg-orange-50 text-orange-700 text-xs rounded-full border border-orange-100 uppercase tracking-wider font-semibold">
                                            ${yearEntries.length} Items
                                        </span>
                                    </h3>
                                    <div class="mt-2 flex items-center gap-4 text-sm text-gray-500 font-medium">
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                            </svg>
                                            <span>Total Amount: <span class="text-orange-600 font-bold">₱${totalAll.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</span></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <button 
                                        onclick="viewPriorYearEntries('${year}')" 
                                        class="px-4 py-2.5 bg-orange-600 text-white rounded-xl hover:bg-orange-700 transition-all font-bold text-sm flex items-center gap-2 shadow-sm"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View
                                    </button>
                                    <button 
                                        onclick="downloadPriorYearPDF(${departmentId}, '${year}')" 
                                        class="px-4 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-all font-bold text-sm flex items-center gap-2 shadow-sm"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Download
                                    </button>
                                    <button 
                                        onclick="deletePriorYearHistory(${departmentId}, '${year}')" 
                                        class="px-4 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-all font-bold text-sm flex items-center gap-2 shadow-sm"
                                        title="Delete this fiscal year's data"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Delete
                                    </button>
                                </div>
                            </div>
                        `;
                    });

                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = `
                        <div class="text-center py-12">
                            <svg class="w-20 h-20 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <p class="text-gray-500 text-lg font-medium">No prior years history found.</p>
                        </div>
                    `;
                }
            } catch (e) {
                console.error('Error loading history:', e);
                document.getElementById('priorYearsHistoryContent').innerHTML = '<p class="text-red-500 font-bold text-center">Failed to load history.</p>';
            }
        }

        function viewPriorYearEntries(year) {
            const yearSelect = document.getElementById('priorYearsFiscalYear');
            if (yearSelect) {
                yearSelect.value = year;
                onPriorYearsFiscalYearChange();
                closePriorYearsHistory();
            }
        }

        function downloadPriorYearPDF(departmentId, year) {
            window.open(`../api/generate_prior_years_pdf.php?department_id=${departmentId}&fiscal_year=${year}`, '_blank');
        }

        function formatHistoryNum(val) {
            const num = parseFloat(val) || 0;
            if (num === 0) return '<span class="text-gray-300">-</span>';
            return '₱' + num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function closePriorYearsHistory() {
            const modal = document.getElementById('priorYearsHistoryModal');
            if (modal) modal.classList.add('hidden');
        }

        async function deletePriorYearHistory(departmentId, year) {
            if (!confirm(`Are you sure you want to delete all Prior Years data for Fiscal Year ${year}? This action cannot be undone.`)) {
                return;
            }

            try {
                const response = await fetch('../api/save_prior_years.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete_year',
                        department_id: departmentId,
                        fiscal_year: year
                    })
                });

                const data = await response.json();
                if (data.success) {
                    alert(`Prior Years data for Fiscal Year ${year} has been deleted successfully.`);
                    // Refresh the history modal
                    showPriorYearsHistory();
                } else {
                    alert('Failed to delete: ' + (data.message || 'Unknown error'));
                }
            } catch (error) {
                console.error('Error deleting prior year:', error);
                alert('Failed to delete prior year data.');
            }
        }

        function highlightCategoryFromUrl() {
            const params = new URLSearchParams(window.location.search);
            const category = params.get('highlight');
            if (!category) return;
            const rows = document.querySelectorAll('[id^="entryRow_"]');
            rows.forEach(row => {
                const entryId = row.id.split('_')[1];
                const input = document.getElementById(`columnArea_${entryId}`);
                if (input && input.value.trim().toLowerCase() === category.toLowerCase()) {
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
    </script>

</body>

</html>

