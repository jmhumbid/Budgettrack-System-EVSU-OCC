<?php
session_start();

// Check if user is logged in and is budget office
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/DepartmentBudget.php';

$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Budget Office';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';
$activeSidebar = 'department_management';

$user = new User();
$departmentBudget = new DepartmentBudget();

// Handle form submissions
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_department':
                $dept_name = $_POST['dept_name'] ?? '';
                $dept_code = $_POST['dept_code'] ?? '';
                $fiduciary_type = $_POST['fiduciary_type'] ?? 'Non-Fiduciary';
                $description = '';
                
                if ($dept_name && $dept_code) {
                    try {
                        $conn = getDB();
                        // Ensure fiduciary_type column exists
                        try {
                            $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'fiduciary_type'");
                            if ($checkCol->rowCount() == 0) {
                                $conn->exec("ALTER TABLE departments ADD COLUMN fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code");
                            }
                        } catch (Exception $e) {}
                        
                        // Ensure parent_department_id column exists
                        try {
                            $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'parent_department_id'");
                            if ($checkCol->rowCount() == 0) {
                                $conn->exec("ALTER TABLE departments ADD COLUMN parent_department_id INT(11) DEFAULT NULL AFTER fiduciary_type");
                            }
                        } catch (Exception $e) {}
                        
                        $parent_department_id = !empty($_POST['parent_department_id']) ? (int)$_POST['parent_department_id'] : null;
                        
                        $query = "INSERT INTO departments (dept_name, dept_code, fiduciary_type, parent_department_id, dept_description) VALUES (:dept_name, :dept_code, :fiduciary_type, :parent_department_id, :description)";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':dept_name', $dept_name);
                        $stmt->bindParam(':dept_code', $dept_code);
                        $stmt->bindParam(':fiduciary_type', $fiduciary_type);
                        $stmt->bindParam(':parent_department_id', $parent_department_id, PDO::PARAM_INT);
                        $stmt->bindParam(':description', $description);
                        
                        if ($stmt->execute()) {
                            $success_message = 'Department created successfully!';
                        } else {
                            $error_message = 'Failed to create department.';
                        }
                    } catch (Exception $e) {
                        $error_message = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Please fill in all required fields.';
                }
                break;
                
            case 'update_department':
                $dept_id = isset($_POST['dept_id']) ? (int)$_POST['dept_id'] : 0;
                $dept_name = trim($_POST['dept_name'] ?? '');
                $dept_code = trim($_POST['dept_code'] ?? '');
                $fiduciary_type = isset($_POST['fiduciary_type']) ? trim($_POST['fiduciary_type']) : 'Non-Fiduciary';
                
                // Validate fiduciary_type value
                if (!in_array($fiduciary_type, ['Fiduciary', 'Non-Fiduciary'])) {
                    $fiduciary_type = 'Non-Fiduciary';
                }
                
                if ($dept_id > 0 && $dept_name && $dept_code) {
                    try {
                        $conn = getDB();
                        // Ensure fiduciary_type column exists
                        try {
                            $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'fiduciary_type'");
                            if ($checkCol->rowCount() == 0) {
                                $conn->exec("ALTER TABLE departments ADD COLUMN fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code");
                            }
                        } catch (Exception $e) {}
                        
                        // Ensure parent_department_id column exists
                        try {
                            $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'parent_department_id'");
                            if ($checkCol->rowCount() == 0) {
                                $conn->exec("ALTER TABLE departments ADD COLUMN parent_department_id INT(11) DEFAULT NULL AFTER fiduciary_type");
                            }
                        } catch (Exception $e) {}
                        
                        $parent_department_id = isset($_POST['parent_department_id']) && $_POST['parent_department_id'] !== '' ? (int)$_POST['parent_department_id'] : null;
                        
                        // First, verify the department exists and get current values
                        $checkStmt = $conn->prepare("SELECT dept_name, dept_code, fiduciary_type, parent_department_id FROM departments WHERE id = :id");
                        $checkStmt->bindParam(':id', $dept_id, PDO::PARAM_INT);
                        $checkStmt->execute();
                        $currentDept = $checkStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if (!$currentDept) {
                            $error_message = 'Department not found.';
                        } else {
                            // Prevent self-referencing
                            if ($parent_department_id == $dept_id) {
                                $parent_department_id = null;
                            }
                            
                            // Update the department
                            $query = "UPDATE departments SET dept_name = :dept_name, dept_code = :dept_code, fiduciary_type = :fiduciary_type, parent_department_id = :parent_department_id WHERE id = :id";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':dept_name', $dept_name, PDO::PARAM_STR);
                            $stmt->bindParam(':dept_code', $dept_code, PDO::PARAM_STR);
                            $stmt->bindParam(':fiduciary_type', $fiduciary_type, PDO::PARAM_STR);
                            $stmt->bindParam(':parent_department_id', $parent_department_id, PDO::PARAM_INT);
                            $stmt->bindParam(':id', $dept_id, PDO::PARAM_INT);
                            
                            if ($stmt->execute()) {
                                $rowsAffected = $stmt->rowCount();
                                if ($rowsAffected > 0) {
                                    $success_message = 'Department updated successfully!';
                                    // Redirect to refresh the page and show updated data
                                    header('Location: department_management.php?updated=1');
                                    exit;
                                } else {
                                    // Values might be the same, but still show success
                                    $success_message = 'Department information is up to date.';
                                    // Redirect anyway to refresh
                                    header('Location: department_management.php?updated=1');
                                    exit;
                                }
                            } else {
                                $errorInfo = $stmt->errorInfo();
                                $error_message = 'Failed to update department: ' . ($errorInfo[2] ?? 'Unknown error');
                            }
                        }
                    } catch (Exception $e) {
                        $error_message = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Please fill in all required fields.';
                }
                break;
                
            case 'delete_department':
                $dept_id = $_POST['dept_id'] ?? 0;
                
                if ($dept_id) {
                    try {
                        $conn = getDB();
                        // Check if department has users
                        $checkUsers = $conn->prepare("SELECT COUNT(*) as user_count FROM users WHERE department_id = :id");
                        $checkUsers->bindParam(':id', $dept_id);
                        $checkUsers->execute();
                        $result = $checkUsers->fetch(PDO::FETCH_ASSOC);
                        
                        if ($result['user_count'] > 0) {
                            $error_message = 'Cannot delete department. There are ' . $result['user_count'] . ' user(s) assigned to this department. Please reassign or remove these users first.';
                        } else {
                            $query = "DELETE FROM departments WHERE id = :id";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':id', $dept_id);
                            
                            if ($stmt->execute()) {
                                $success_message = 'Department deleted successfully!';
                                // Redirect to refresh the page
                                header('Location: department_management.php');
                                exit;
                            } else {
                                $error_message = 'Failed to delete department.';
                            }
                        }
                    } catch (Exception $e) {
                        $error_message = 'Error: ' . $e->getMessage();
                    }
                } else {
                    $error_message = 'Invalid department ID.';
                }
                break;
        }
    }
}

// Get all departments
try {
    $conn = getDB();
    // Ensure fiduciary_type column exists
    try {
        $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'fiduciary_type'");
        if ($checkCol->rowCount() == 0) {
            $conn->exec("ALTER TABLE departments ADD COLUMN fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code");
        }
    } catch (Exception $e) {}
    
    // Ensure parent_department_id column exists
    try {
        $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'parent_department_id'");
        if ($checkCol->rowCount() == 0) {
            $conn->exec("ALTER TABLE departments ADD COLUMN parent_department_id INT(11) DEFAULT NULL AFTER fiduciary_type");
        }
    } catch (Exception $e) {}
    
    // Explicitly select all columns including fiduciary_type and parent_department_id
    $query = "SELECT d.id, d.dept_name, d.dept_code, d.fiduciary_type, d.parent_department_id, d.dept_description, d.is_active, d.created_at, d.updated_at,
              (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count,
              p.dept_name as parent_dept_name
              FROM departments d
              LEFT JOIN departments p ON d.parent_department_id = p.id
              ORDER BY d.dept_name";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $departments = [];
    // Log error for debugging
    error_log("Error fetching departments: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BudgetTrack - Department/Offices Management</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        maroon: '#800000',
                        'maroon-dark': '#5a0000',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../components/super_admin_sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col ml-64" data-main-content>
            <!-- Header -->
            <header class="bg-white border-b border-gray-200 shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Department/Offices Management</h1>
                        <p class="text-gray-600 text-sm mt-1">Create and manage departments/offices, assign users to departments/offices</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <!-- Notification Bell -->
                        <?php 
                        require_once __DIR__ . '/../classes/Notification.php';
                        $notification = new Notification();
                        $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
                        $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
                        include __DIR__ . '/../components/notification_bell.php'; 
                        ?>
                        <button onclick="window.location.href='admin_dashboard.php'" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Return
                        </button>
                        <div class="relative">
                            <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-gray-100 px-4 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                                <?php render_profile_avatar(['classes' => 'bg-maroon text-white font-semibold']); ?>
                                <div class="text-sm">
                                    <div class="font-medium text-gray-800"><?php echo htmlspecialchars($username); ?></div>
                                    <div class="text-xs text-gray-600"><?php echo htmlspecialchars($userEmail); ?></div>
                                </div>
                                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
            </header>
            
            <!-- Content Area -->
            <div class="flex-1 p-6">
                <?php if ($success_message): ?>
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <i class="fas fa-check-circle mr-2"></i><?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="space-y-6 mb-6">
                    <!-- Create Department/Office Form -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                        <h2 class="text-xl font-bold text-maroon mb-6">Create New Department/Office</h2>
                        
                        <form method="POST" class="space-y-4">
                            <input type="hidden" name="action" value="create_department">
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department/Office Name *</label>
                                <input type="text" name="dept_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" placeholder="e.g., Computer Science Department">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Department/Office Code *</label>
                                <input type="text" name="dept_code" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" placeholder="e.g., CSD">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                                <select name="fiduciary_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon">
                                    <option value="">-- Select Type --</option>
                                    <option value="Non-Fiduciary">Department (Non-Fiduciary)</option>
                                    <option value="Fiduciary">Office (Fiduciary)</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">
                                    <strong>Department:</strong> Will appear in "Departments" dropdown in Budget Workflow<br>
                                    <strong>Office:</strong> Will appear in "Offices" dropdown in Budget Workflow
                                </p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Assign to Parent Department</label>
                                <select name="parent_department_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon">
                                    <option value="">None (Independent Department)</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="text-xs text-gray-500 mt-1">If assigned, users from the parent department can view this department's allocations.</p>
                            </div>
                            
                            <button type="submit" class="w-full bg-maroon text-white py-2 px-4 rounded-lg hover:bg-maroon-dark">
                                <i class="fas fa-plus mr-2"></i>Create Department/Office
                            </button>
                        </form>
                </div>
                
                <!-- Departments/Offices List -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
                    <div class="p-6 border-b border-gray-200">
                        <h2 class="text-xl font-bold text-maroon">All Departments/Offices</h2>
                        <p class="text-gray-600">Manage existing departments/offices and view user assignments</p>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department/Office</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fiduciary Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Users</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($departments)): ?>
                                    <tr>
                                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                            <i class="fas fa-building text-4xl mb-4"></i>
                                            <p>No departments/offices found</p>
                                            <p class="text-sm mt-2">Create your first department/office using the form above.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($departments as $dept): 
                                        $fiduciaryType = $dept['fiduciary_type'] ?? 'Non-Fiduciary';
                                        $isFiduciary = ($fiduciaryType === 'Fiduciary');
                                    ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0 h-10 w-10">
                                                        <div class="h-10 w-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                            <i class="fas fa-building text-blue-600"></i>
                                                        </div>
                                                    </div>
                                                    <div class="ml-4">
                                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($dept['dept_name']); ?></div>
                                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($dept['dept_description'] ?: 'No description'); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($dept['dept_code']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $isFiduciary ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo htmlspecialchars($fiduciaryType); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo $dept['user_count']; ?> user(s)
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo date('M j, Y', strtotime($dept['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($dept)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteDepartment(<?php echo $dept['id']; ?>, <?php echo $dept['user_count']; ?>)" class="text-red-600 hover:text-red-900" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
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

    <!-- Edit Department Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_department">
                    <input type="hidden" name="dept_id" id="edit_dept_id">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Edit Department/Office</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department/Office Name *</label>
                            <input type="text" name="dept_name" id="edit_dept_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department/Office Code *</label>
                            <input type="text" name="dept_code" id="edit_dept_code" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fiduciary Type *</label>
                            <select name="fiduciary_type" id="edit_fiduciary_type" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="Non-Fiduciary">Non-Fiduciary</option>
                                <option value="Fiduciary">Fiduciary</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Assign to Parent Department</label>
                            <select name="parent_department_id" id="edit_parent_department_id" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">None (Independent Department)</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-xs text-gray-500 mt-1">If assigned, users from the parent department can view this department's allocations.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            Update Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_department">
                    <input type="hidden" name="dept_id" id="delete_dept_id">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
                        <p class="mt-2 text-sm text-gray-500" id="delete_confirm_message">Are you sure you want to delete this department? This action cannot be undone.</p>
                        <div id="delete_warning" class="mt-3 hidden bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                                </div>
                                <div class="ml-3">
                                    <h4 class="text-sm font-medium text-yellow-800">Warning</h4>
                                    <p class="mt-1 text-sm text-yellow-700" id="delete_warning_text"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" id="delete_submit_btn" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                            Delete Department
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full mx-4">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Logout</h3>
                <p class="text-sm text-gray-500 mb-6">Are you sure you want to logout?</p>
                <div class="flex justify-end space-x-3">
                    <button onclick="closeLogoutModal()" class="px-4 py-2 text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300">
                        Cancel
                    </button>
                    <button onclick="logout()" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Logout
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Profile dropdown functionality
        function toggleProfileDropdown() {
            const dropdown = document.getElementById('profileDropdown');
            dropdown.classList.toggle('hidden');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('profileDropdown');
            const button = event.target.closest('button');
            
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleProfileDropdown') === -1) {
                dropdown.classList.add('hidden');
            }
        });

        function goBack() {
            window.location.href = 'admin_dashboard.php';
        }

        function confirmLogout() {
            document.getElementById('logoutModal').classList.remove('hidden');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.add('hidden');
        }

        function logout() {
            window.location.href = '../auth/logout.php';
        }

        function openEditModal(dept) {
            document.getElementById('edit_dept_id').value = dept.id;
            document.getElementById('edit_dept_name').value = dept.dept_name;
            document.getElementById('edit_dept_code').value = dept.dept_code;
            document.getElementById('edit_fiduciary_type').value = dept.fiduciary_type || 'Non-Fiduciary';
            document.getElementById('edit_parent_department_id').value = dept.parent_department_id || '';
            
            // Disable selecting self as parent
            const parentSelect = document.getElementById('edit_parent_department_id');
            for (let option of parentSelect.options) {
                option.disabled = (option.value == dept.id);
            }
            
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deleteDepartment(deptId, userCount) {
            document.getElementById('delete_dept_id').value = deptId;
            const warningDiv = document.getElementById('delete_warning');
            const warningText = document.getElementById('delete_warning_text');
            const submitBtn = document.getElementById('delete_submit_btn');
            const confirmMessage = document.getElementById('delete_confirm_message');
            
            if (userCount > 0) {
                warningDiv.classList.remove('hidden');
                warningText.textContent = `This department is currently assigned to ${userCount} user(s). You cannot delete a department that is in use. Please reassign or remove these users first.`;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                confirmMessage.textContent = 'Cannot delete this department because it is currently in use.';
            } else {
                warningDiv.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                confirmMessage.textContent = 'Are you sure you want to delete this department? This action cannot be undone.';
            }
            
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });
    </script>

</body>
</html>
