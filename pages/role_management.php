<?php
session_start();

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Role.php';
require_once __DIR__ . '/../config/database.php';

$user = new User();
$role = new Role();
$activeSidebar = 'role_management';
$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';

// Check if user has permission to manage roles
if (!$user->hasPermission($_SESSION['user_id'], 'create_roles')) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_role':
                $new_role = new Role();
                $new_role->role_name = trim($_POST['role_name']);
                $new_role->role_description = trim($_POST['role_description']);

                if (empty($new_role->role_name)) {
                    $error = 'Role name is required.';
                } elseif ($role->roleNameExists($new_role->role_name)) {
                    $error = 'Role name already exists.';
                } else {
                    if ($new_role->create()) {
                        $message = 'Role created successfully.';
                    } else {
                        $error = 'Failed to create role.';
                    }
                }
                break;

            case 'update_role':
                $update_role = new Role();
                $update_role->id = $_POST['role_id'];
                $update_role->role_name = trim($_POST['role_name']);
                $update_role->role_description = trim($_POST['role_description']);

                if (empty($update_role->role_name)) {
                    $error = 'Role name is required.';
                } elseif ($role->roleNameExists($update_role->role_name, $update_role->id)) {
                    $error = 'Role name already exists.';
                } else {
                    if ($update_role->update()) {
                        $message = 'Role updated successfully.';
                    } else {
                        $error = 'Failed to update role.';
                    }
                }
                break;

            case 'delete_role':
                $delete_role = new Role();
                $delete_role->id = $_POST['role_id'];
                $result = $delete_role->delete();
                if (is_array($result)) {
                    if ($result['success']) {
                        $message = $result['message'];
                    } else {
                        $error = $result['message'];
                    }
                } else {
                    // Backward compatibility - if method returns boolean
                    if ($result) {
                    $message = 'Role deleted successfully.';
                } else {
                    $error = 'Failed to delete role.';
                    }
                }
                break;

            case 'update_permissions':
                $role_id = $_POST['role_id'];
                $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
                
                // Get database connection
                $db = getDB();
                
                // Delete existing permissions for this role
                $delete_query = "DELETE FROM role_permissions WHERE role_id = :role_id";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(':role_id', $role_id);
                $delete_stmt->execute();
                
                // Insert new permissions
                if (!empty($permissions)) {
                    $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
                    $insert_stmt = $db->prepare($insert_query);
                    
                    foreach ($permissions as $permission_id) {
                        $insert_stmt->bindParam(':role_id', $role_id);
                        $insert_stmt->bindParam(':permission_id', $permission_id);
                        $insert_stmt->execute();
                    }
                }
                
                $message = 'Role permissions updated successfully.';
                break;
        }
    }
}

// Get all roles and permissions
$roles = $role->getAllRoles();

// Get user count for each role
$roleUserCounts = [];
foreach ($roles as $r) {
    $tempRole = new Role();
    $tempRole->id = $r['id'];
    $roleUserCounts[$r['id']] = $tempRole->getUserCount();
}

// Get all permissions grouped by module
$db = getDB();
$permissions_query = "SELECT * FROM permissions ORDER BY module, permission_name";
$permissions_stmt = $db->prepare($permissions_query);
$permissions_stmt->execute();
$all_permissions = $permissions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group permissions by module
$permissions_by_module = [];
foreach ($all_permissions as $permission) {
    $permissions_by_module[$permission['module']][] = $permission;
}

// Get role permissions for editing
$role_permissions = [];
if (isset($_GET['edit_permissions'])) {
    $role_id = $_GET['edit_permissions'];
    $permissions_query = "SELECT permission_id FROM role_permissions WHERE role_id = :role_id";
    $permissions_stmt = $db->prepare($permissions_query);
    $permissions_stmt->bindParam(':role_id', $role_id);
    $permissions_stmt->execute();
    $role_permissions = array_column($permissions_stmt->fetchAll(PDO::FETCH_ASSOC), 'permission_id');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Role Management - BudgetTrack</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">Role Management</h1>
                        <p class="text-gray-600 text-sm mt-1">Create and manage user roles and permissions</p>
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
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div class="flex items-center space-x-4">
                <button onclick="goBack()" class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back
                </button>
                <h2 class="text-2xl font-bold text-gray-800">Role Management</h2>
            </div>
            <button onclick="openCreateModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded flex items-center">
                <i class="fas fa-plus mr-2"></i> Create New Role
            </button>
        </div>

        <!-- Roles Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($roles as $r): 
                        $userCount = $roleUserCounts[$r['id']] ?? 0;
                        $isInUse = $userCount > 0;
                    ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($r['role_name']); ?>
                            </div>
                            <?php if ($isInUse): ?>
                                <div class="text-xs text-gray-500 mt-1">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        <i class="fas fa-users mr-1"></i>
                                        <?php echo $userCount; ?> user<?php echo $userCount !== 1 ? 's' : ''; ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-500">
                                <?php echo htmlspecialchars($r['role_description']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($r)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit Role">
                                <i class="fas fa-edit"></i>
                            </button>
                            <a href="?edit_permissions=<?php echo $r['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3" title="Manage Permissions">
                                <i class="fas fa-key"></i>
                            </a>
                            <button onclick="deleteRole(<?php echo $r['id']; ?>, <?php echo $userCount; ?>)" 
                                    class="<?php echo $isInUse ? 'text-gray-400 cursor-not-allowed' : 'text-red-600 hover:text-red-900'; ?>" 
                                    <?php echo $isInUse ? 'disabled title="Cannot delete: ' . $userCount . ' user(s) assigned to this role"' : 'title="Delete Role"'; ?>>
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Permissions Management -->
        <?php if (isset($_GET['edit_permissions'])): ?>
        <?php 
        $editing_role = $roles[array_search($_GET['edit_permissions'], array_column($roles, 'id'))];
        $is_editing_admin = ($editing_role['role_name'] === 'admin');
        ?>
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900">Manage Permissions for Role: <?php echo htmlspecialchars($editing_role['role_name']); ?></h3>
                <?php if ($is_editing_admin): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded flex items-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    <span class="text-sm font-medium">Admin Role - Use with caution!</span>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if ($is_editing_admin): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-yellow-800">Warning: Modifying Admin Permissions</h3>
                        <div class="mt-2 text-sm text-yellow-700">
                            <p>You are modifying the admin role permissions. Changes here will affect all admin users immediately. Be very careful about removing permissions as this could lock out admin users from important system functions.</p>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_permissions">
                <input type="hidden" name="role_id" value="<?php echo $_GET['edit_permissions']; ?>">
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($permissions_by_module as $module => $module_permissions): ?>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3 capitalize"><?php echo str_replace('_', ' ', $module); ?></h4>
                        <div class="space-y-2">
                            <?php foreach ($module_permissions as $permission): ?>
                            <label class="flex items-center">
                                <input type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" 
                                       <?php echo in_array($permission['id'], $role_permissions) ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($permission['permission_name']); ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-6 flex justify-end space-x-3">
                    <a href="role_management.php" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Update Permissions
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Create Role Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_role">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Create New Role</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role Name *</label>
                            <input type="text" name="role_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="role_description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            Create Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_role">
                    <input type="hidden" name="role_id" id="edit_role_id">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Edit Role</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role Name *</label>
                            <input type="text" name="role_name" id="edit_role_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea name="role_description" id="edit_role_description" rows="3" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2"></textarea>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            Update Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="delete_role">
                    <input type="hidden" name="role_id" id="delete_role_id">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3>
                        <p class="mt-2 text-sm text-gray-500" id="delete_confirm_message">Are you sure you want to delete this role? This action cannot be undone.</p>
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
                            Delete Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function openEditModal(role) {
            document.getElementById('edit_role_id').value = role.id;
            document.getElementById('edit_role_name').value = role.role_name;
            document.getElementById('edit_role_description').value = role.role_description || '';
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function deleteRole(roleId, userCount) {
            document.getElementById('delete_role_id').value = roleId;
            const warningDiv = document.getElementById('delete_warning');
            const warningText = document.getElementById('delete_warning_text');
            const submitBtn = document.getElementById('delete_submit_btn');
            const confirmMessage = document.getElementById('delete_confirm_message');
            
            if (userCount > 0) {
                // Role is in use - show warning and disable delete
                warningDiv.classList.remove('hidden');
                warningText.textContent = `This role is currently assigned to ${userCount} user(s). You cannot delete a role that is in use. Please reassign or remove these users first.`;
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
                confirmMessage.textContent = 'Cannot delete this role because it is currently in use.';
            } else {
                // Role is not in use - allow deletion
                warningDiv.classList.add('hidden');
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                confirmMessage.textContent = 'Are you sure you want to delete this role? This action cannot be undone.';
            }
            
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modals when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === createModal) {
                closeCreateModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
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
            const button = event.target.closest('button');
            
            if (!button || !button.onclick || button.onclick.toString().indexOf('toggleProfileDropdown') === -1) {
                dropdown.classList.add('hidden');
            }
        });

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

        // Back button functionality
        function goBack() {
            // Check user role and redirect to appropriate dashboard
            <?php if ($_SESSION['user_role'] === 'budget'): ?>
                window.location.href = 'admin_dashboard.php';
            <?php elseif ($_SESSION['user_role'] === 'school_admin'): ?>
                window.location.href = 'school_admin_dashboard.php';
            <?php else: ?>
                window.location.href = 'dept_dashboard.php';
            <?php endif; ?>
        }
    </script>

    <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
                    <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="px-6 py-4">
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
    </div>

</body>
</html>
