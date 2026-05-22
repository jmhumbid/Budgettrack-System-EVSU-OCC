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

// Check if user is Budget/Accounting Office (only Budget Office can control admin)
if ($_SESSION['user_role'] !== 'budget') {
    header('Location: ../pages/dashboard.php');
    exit;
}

$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_admin_permissions':
                $admin_role_id = $_POST['admin_role_id'];
                $permissions = isset($_POST['permissions']) ? $_POST['permissions'] : [];
                
                // Get database connection
                $db = getDB();
                
                // Delete existing permissions for admin role
                $delete_query = "DELETE FROM role_permissions WHERE role_id = :role_id";
                $delete_stmt = $db->prepare($delete_query);
                $delete_stmt->bindParam(':role_id', $admin_role_id);
                $delete_stmt->execute();
                
                // Insert new permissions
                if (!empty($permissions)) {
                    $insert_query = "INSERT INTO role_permissions (role_id, permission_id) VALUES (:role_id, :permission_id)";
                    $insert_stmt = $db->prepare($insert_query);
                    
                    foreach ($permissions as $permission_id) {
                        $insert_stmt->bindParam(':role_id', $admin_role_id);
                        $insert_stmt->bindParam(':permission_id', $permission_id);
                        $insert_stmt->execute();
                    }
                }
                
                $message = 'Admin permissions updated successfully.';
                break;

            case 'toggle_admin_access':
                $admin_user_id = $_POST['admin_user_id'];
                $is_active = $_POST['is_active'] == '1' ? 0 : 1;
                
                $update_user = new User();
                $update_user->id = $admin_user_id;
                $update_user->is_active = $is_active;
                
                if ($update_user->update()) {
                    $status = $is_active ? 'activated' : 'deactivated';
                    $message = "Admin user $status successfully.";
                } else {
                    $error = 'Failed to update admin user status.';
                }
                break;
        }
    }
}

// Get admin role and its permissions
$db = getDB();
$admin_role_query = "SELECT * FROM roles WHERE role_name = 'admin'";
$admin_role_stmt = $db->prepare($admin_role_query);
$admin_role_stmt->execute();
$admin_role = $admin_role_stmt->fetch(PDO::FETCH_ASSOC);

// Get admin role permissions
$admin_permissions_query = "SELECT permission_id FROM role_permissions WHERE role_id = :role_id";
$admin_permissions_stmt = $db->prepare($admin_permissions_query);
$admin_permissions_stmt->bindParam(':role_id', $admin_role['id']);
$admin_permissions_stmt->execute();
$admin_permissions = array_column($admin_permissions_stmt->fetchAll(PDO::FETCH_ASSOC), 'permission_id');

// Get all permissions grouped by module
$permissions_query = "SELECT * FROM permissions ORDER BY module, permission_name";
$permissions_stmt = $db->prepare($permissions_query);
$permissions_stmt->execute();
$all_permissions = $permissions_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group permissions by module
$permissions_by_module = [];
foreach ($all_permissions as $permission) {
    $permissions_by_module[$permission['module']][] = $permission;
}

// Get all admin users
$admin_users_query = "SELECT u.*, d.dept_name 
                      FROM users u 
                      LEFT JOIN departments d ON u.department_id = d.id 
                      WHERE u.role_id = :admin_role_id 
                      ORDER BY u.created_at DESC";
$admin_users_stmt = $db->prepare($admin_users_query);
$admin_users_stmt->bindParam(':admin_role_id', $admin_role['id']);
$admin_users_stmt->execute();
$admin_users = $admin_users_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control - BudgetTrack</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <link rel="stylesheet" href="../public/css/dashboard.css">
    <script src="https://cdn.tailwindcss.com">
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
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="bg-red-800 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center space-x-4">
                    <h1 class="text-xl font-bold">BudgetTrack</h1>
                    <span class="text-red-200">|</span>
                    <span>Admin Control</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                    <a href="admin_dashboard.php" class="bg-red-700 hover:bg-red-600 px-3 py-2 rounded">Back to Dashboard</a>
                    <button onclick="confirmLogout()" class="bg-red-700 hover:bg-red-600 px-3 py-2 rounded">Logout</button>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
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
        <div class="mb-6">
            <div class="flex items-center space-x-4">
                <button onclick="goBack()" class="flex items-center text-gray-600 hover:text-gray-800 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back
                </button><h2 class="text-2xl font-bold text-gray-800">Admin Control Panel</h2></div>
            <p class="text-gray-600">As Budget Officer, you have complete control over admin permissions and access.</p>
        </div>

        <!-- Admin Users Management -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Admin Users</h3>
                <p class="text-sm text-gray-500">Manage admin user accounts and their access status</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($admin_users as $admin_user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($admin_user['first_name'] . ' ' . $admin_user['last_name']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($admin_user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($admin_user['dept_name'] ?? 'N/A'); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $admin_user['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $admin_user['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $admin_user['last_login'] ? date('M d, Y H:i', strtotime($admin_user['last_login'])) : 'Never'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <form method="POST" action="" class="inline">
                                    <input type="hidden" name="action" value="toggle_admin_access">
                                    <input type="hidden" name="admin_user_id" value="<?php echo $admin_user['id']; ?>">
                                    <input type="hidden" name="is_active" value="<?php echo $admin_user['is_active']; ?>">
                                    <button type="submit" class="text-<?php echo $admin_user['is_active'] ? 'red' : 'green'; ?>-600 hover:text-<?php echo $admin_user['is_active'] ? 'red' : 'green'; ?>-900">
                                        <i class="fas fa-<?php echo $admin_user['is_active'] ? 'ban' : 'check'; ?>"></i>
                                        <?php echo $admin_user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Admin Permissions Management -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Admin Role Permissions</h3>
                <p class="text-sm text-gray-500">Control what admin users can and cannot do in the system</p>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_admin_permissions">
                <input type="hidden" name="admin_role_id" value="<?php echo $admin_role['id']; ?>">
                
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php foreach ($permissions_by_module as $module => $module_permissions): ?>
                        <div class="border border-gray-200 rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3 capitalize flex items-center">
                                <i class="fas fa-<?php 
                                    switch($module) {
                                        case 'user_management': echo 'users'; break;
                                        case 'role_management': echo 'key'; break;
                                        case 'department_management': echo 'building'; break;
                                        case 'budget_management': echo 'dollar-sign'; break;
                                        case 'ppmp_management': echo 'file-alt'; break;
                                        case 'reports': echo 'chart-bar'; break;
                                        case 'dashboard': echo 'tachometer-alt'; break;
                                        case 'notifications': echo 'bell'; break;
                                        case 'system_control': echo 'cog'; break;
                                        default: echo 'folder';
                                    }
                                ?> mr-2"></i>
                                <?php echo str_replace('_', ' ', $module); ?>
                            </h4>
                            <div class="space-y-2">
                                <?php foreach ($module_permissions as $permission): ?>
                                <label class="flex items-center">
                                    <input type="checkbox" name="permissions[]" value="<?php echo $permission['id']; ?>" 
                                           <?php echo in_array($permission['id'], $admin_permissions) ? 'checked' : ''; ?>
                                           class="rounded border-gray-300 text-red-600 shadow-sm focus:border-red-300 focus:ring focus:ring-red-200 focus:ring-opacity-50">
                                    <span class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($permission['permission_name']); ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="selectAll()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                            Select All
                        </button>
                        <button type="button" onclick="selectNone()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                            Select None
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                            Update Admin Permissions
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function selectAll() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = true);
        }

        function selectNone() {
            const checkboxes = document.querySelectorAll('input[type="checkbox"][name="permissions[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = false);
        }
    
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
