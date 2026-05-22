<?php
session_start();

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Role.php';
require_once __DIR__ . '/../classes/Department.php';
require_once __DIR__ . '/../classes/EmailService.php';

$user = new User();
$role = new Role();
$department = new Department();
$activeSidebar = 'user_management';
$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Administrator';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
include __DIR__ . '/../components/profile_avatar.php';

// Check if user has permission to manage users
if (!$user->hasPermission($_SESSION['user_id'], 'create_users')) {
    header('Location: ../pages/dashboard.php');
    exit;
}

$message = '';
$error = '';

// Function to generate secure password with better randomness
function generateSecurePassword($length = 12) {
    // Define character sets
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    // Ensure at least one character from each set
    $password = '';
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];
    
    // Fill the rest with random characters from all sets
    $allChars = $lowercase . $uppercase . $numbers . $symbols;
    for ($i = 4; $i < $length; $i++) {
        $password .= $allChars[random_int(0, strlen($allChars) - 1)];
    }
    
    // Shuffle the password to randomize positions
    return str_shuffle($password);
}

// Function to check password strength
function checkPasswordStrength($password) {
    $score = 0;
    $feedback = [];
    
    // Length check
    if (strlen($password) >= 8) $score += 1;
    else $feedback[] = 'At least 8 characters';
    
    if (strlen($password) >= 12) $score += 1;
    
    // Character variety checks
    if (preg_match('/[a-z]/', $password)) $score += 1;
    else $feedback[] = 'Lowercase letters';
    
    if (preg_match('/[A-Z]/', $password)) $score += 1;
    else $feedback[] = 'Uppercase letters';
    
    if (preg_match('/[0-9]/', $password)) $score += 1;
    else $feedback[] = 'Numbers';
    
    if (preg_match('/[^a-zA-Z0-9]/', $password)) $score += 1;
    else $feedback[] = 'Special characters';
    
    // Determine strength
    if ($score >= 6) {
        return ['strength' => 'strong', 'color' => 'green', 'score' => $score, 'feedback' => $feedback];
    } elseif ($score >= 4) {
        return ['strength' => 'medium', 'color' => 'yellow', 'score' => $score, 'feedback' => $feedback];
    } else {
        return ['strength' => 'weak', 'color' => 'red', 'score' => $score, 'feedback' => $feedback];
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_user':
                $new_user = new User();
                $new_user->email = trim($_POST['email']);
                
                // Generate auto password (hashing is done in User::create)
                $auto_password = generateSecurePassword();
                $new_user->password_hash = $auto_password;
                
                $new_user->first_name = trim($_POST['first_name']);
                $new_user->last_name = trim($_POST['last_name']);
                $new_user->middle_name = trim($_POST['middle_name']);
                $new_user->department_id = $_POST['department_id']; // Department assigned during creation
                $new_user->role_id = $_POST['role_id'];
                $new_user->created_by = $_SESSION['user_id'];
                $new_user->password_change_required = true; // Require password change on first login

                // Validate required fields
                if (empty($new_user->email) || empty($new_user->first_name) || empty($new_user->last_name) || 
                    empty($new_user->role_id) || empty($new_user->department_id)) {
                    $error = 'All required fields must be filled.';
                } elseif ($new_user->emailExists($new_user->email)) {
                    $error = 'Email already exists.';
                } else {
                    if ($new_user->create()) {
                        // Send welcome email with auto-generated password
                        $emailService = new EmailService();
                        $userName = $new_user->first_name . ' ' . $new_user->last_name;
                        
                        if ($emailService->sendWelcomeEmail($new_user->email, $userName, $auto_password)) {
                            $message = 'User created successfully! Welcome email sent with password setup instructions.';
                        } else {
                            $message = 'User created successfully, but failed to send welcome email. <strong>Please provide these credentials to the user:</strong><br>Email: ' . htmlspecialchars($new_user->email) . '<br>Verification Code: ' . htmlspecialchars($auto_password) . '<br>Password Setup Link: <a href="setup_password.php" target="_blank">Set Up Password</a>';
                        }
                    } else {
                        $error = 'Failed to create user.';
                    }
                }
                break;

            case 'update_user':
                $update_user = new User();
                $update_user->id = $_POST['user_id'];
                $update_user->email = trim($_POST['email']);
                $update_user->first_name = trim($_POST['first_name']);
                $update_user->last_name = trim($_POST['last_name']);
                $update_user->middle_name = trim($_POST['middle_name']);
                $update_user->department_id = $_POST['department_id'];
                $update_user->role_id = $_POST['role_id'];
                $update_user->is_active = isset($_POST['is_active']) ? 1 : 0;

                if ($update_user->emailExists($update_user->email, $update_user->id)) {
                    $error = 'Email already exists.';
                } else {
                    if ($update_user->update()) {
                        $message = 'User updated successfully.';
                    } else {
                        $error = 'Failed to update user.';
                    }
                }
                break;

            case 'delete_user':
                // Verify admin password before permanent deletion
                $admin_password = $_POST['admin_password'] ?? '';
                $current_admin = new User();
                $admin_data = $current_admin->getUserById($_SESSION['user_id']);
                
                if (empty($admin_password)) {
                    $error = 'Admin password is required to permanently delete user.';
                } elseif (!password_verify($admin_password, $admin_data['password_hash'])) {
                    $error = 'Invalid admin password. Deletion cancelled.';
                } else {
                    $delete_user = new User();
                    $delete_user->id = $_POST['user_id'];
                    if ($delete_user->hardDelete()) {
                        $message = 'User permanently deleted successfully.';
                    } else {
                        $error = 'Failed to delete user.';
                    }
                }
                break;

            case 'reset_password':
                $reset_user = new User();
                $reset_user->id = $_POST['user_id'];
                $new_password = $_POST['new_password'];
                
                if (empty($new_password) || strlen($new_password) < 6) {
                    $error = 'New password must be at least 6 characters long.';
                } else {
                    if ($reset_user->resetPassword($reset_user->id, $new_password)) {
                        $message = 'Password reset successfully.';
                    } else {
                        $error = 'Failed to reset password.';
                    }
                }
                break;
        }
    }
}

// Get all users, roles, and departments
$users = $user->getAllUsers();
$roles = $role->getAllRoles();
$departments = $department->getAllDepartments();

// Ensure fiduciary_type is included in departments data
try {
    $conn = getDB();
    $checkCol = $conn->query("SHOW COLUMNS FROM departments LIKE 'fiduciary_type'");
    if ($checkCol->rowCount() == 0) {
        $conn->exec("ALTER TABLE departments ADD COLUMN fiduciary_type ENUM('Fiduciary', 'Non-Fiduciary') DEFAULT 'Non-Fiduciary' AFTER dept_code");
    }
    // Refresh departments with fiduciary_type
    $stmt = $conn->prepare("SELECT * FROM departments WHERE is_active = 1 ORDER BY dept_name");
    $stmt->execute();
    $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // If error, use existing departments array
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - BudgetTrack</title>
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
                        <h1 class="text-2xl font-bold text-gray-800">User Management</h1>
                        <p class="text-gray-600 text-sm mt-1">Create and manage user accounts</p>
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
                            
                            <!-- Profile Dropdown -->
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
                    <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
                    <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">All Users</h2>
                    <button onclick="openCreateModal()" class="bg-maroon hover:bg-maroon-dark text-white px-4 py-2 rounded-lg flex items-center">
                <i class="fas fa-plus mr-2"></i> Create New User
            </button>
        </div>

        <!-- Users Table -->
                <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $u): ?>
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <?php echo htmlspecialchars($u['email']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center gap-2">
                                <span><?php echo htmlspecialchars($u['dept_name'] ?? 'N/A'); ?></span>
                                <?php 
                                // Get fiduciary type for this department
                                $userDeptId = $u['department_id'] ?? null;
                                if ($userDeptId) {
                                    foreach ($departments as $dept) {
                                        if ($dept['id'] == $userDeptId) {
                                            $fidType = $dept['fiduciary_type'] ?? 'Non-Fiduciary';
                                            $isFid = ($fidType === 'Fiduciary');
                                            echo '<span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ' . ($isFid ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800') . '">' . htmlspecialchars($fidType) . '</span>';
                                            break;
                                        }
                                    }
                                }
                                ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span class="inline-flex items-center">
                                <?php if ($u['role_name'] === 'admin'): ?>
                                    <i class="fas fa-crown text-yellow-500 mr-1"></i>
                                <?php elseif ($u['role_name'] === 'budget'): ?>
                                    <i class="fas fa-shield-alt text-red-500 mr-1"></i>
                                <?php elseif ($u['role_name'] === 'offices'): ?>
                                    <i class="fas fa-building text-blue-500 mr-1"></i>
                                <?php elseif ($u['role_name'] === 'supply_office'): ?>
                                    <i class="fas fa-boxes text-teal-500 mr-1"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars($u['role_name']); ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $u['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($u)); ?>)" class="text-indigo-600 hover:text-indigo-900 mr-3" title="Edit User">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button onclick="openResetPasswordModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?>')" class="text-yellow-600 hover:text-yellow-900 mr-3" title="Reset Password">
                                <i class="fas fa-key"></i>
                            </button>
                            <button onclick="deleteUser(<?php echo $u['id']; ?>)" class="text-red-600 hover:text-red-900" title="Deactivate User">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create_user">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Create New User</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="first_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="last_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name (Optional)</label>
                            <input type="text" name="middle_name" placeholder="Optional" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department/Office *</label>
                            <select name="department_id" id="create_department_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" onchange="updateFiduciaryTag('create')">
                                <option value="">Select Department/Office</option>
                                <?php foreach ($departments as $dept): 
                                    $fidType = $dept['fiduciary_type'] ?? 'Non-Fiduciary';
                                ?>
                                    <option value="<?php echo $dept['id']; ?>" data-fiduciary="<?php echo htmlspecialchars($fidType); ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="create_fiduciary_tag" class="mt-2 hidden">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" id="create_fiduciary_badge"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role *</label>
                            <select name="role_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeCreateModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            Create User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" id="edit_user_id">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Edit User</h3>
                    </div>
                    <div class="px-6 py-4 space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email *</label>
                            <input type="email" name="email" id="edit_email" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">First Name *</label>
                                <input type="text" name="first_name" id="edit_first_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Last Name *</label>
                                <input type="text" name="last_name" id="edit_last_name" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Middle Name (Optional)</label>
                            <input type="text" name="middle_name" id="edit_middle_name" placeholder="Optional" class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Department *</label>
                            <select name="department_id" id="edit_department_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2" onchange="updateFiduciaryTag('edit')">
                                <option value="">Select Department</option>
                                <?php foreach ($departments as $dept): 
                                    $fidType = $dept['fiduciary_type'] ?? 'Non-Fiduciary';
                                ?>
                                    <option value="<?php echo $dept['id']; ?>" data-fiduciary="<?php echo htmlspecialchars($fidType); ?>"><?php echo htmlspecialchars($dept['dept_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div id="edit_fiduciary_tag" class="mt-2 hidden">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" id="edit_fiduciary_badge"></span>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Role *</label>
                            <select name="role_id" id="edit_role_id" required class="mt-1 block w-full border border-gray-300 rounded-md px-3 py-2">
                                <option value="">Select Role</option>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['role_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                            Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div id="resetPasswordModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg shadow-xl max-w-md w-full">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="user_id" id="reset_user_id">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Reset Password</h3>
                        <p class="mt-1 text-sm text-gray-500">Reset password for: <span id="reset_user_name" class="font-medium"></span></p>
                    </div>
                    <div class="px-6 py-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">New Password *</label>
                            <input type="password" name="new_password" id="reset_new_password" required minlength="6" class="w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" onkeyup="checkPasswordStrength('reset_new_password')">
                            
                            <!-- Password Strength Indicator -->
                            <div class="mt-2">
                                <div class="flex items-center space-x-2">
                                    <div class="flex-1 bg-gray-200 rounded-full h-2">
                                        <div id="reset_strength_bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%; background-color: #ef4444;"></div>
                                    </div>
                                    <span id="reset_strength_text" class="text-sm font-medium text-gray-500">Weak</span>
                                </div>
                                <div id="reset_strength_feedback" class="mt-1 text-xs text-gray-500"></div>
                            </div>
                            
                            <p class="mt-1 text-sm text-gray-500">Password must be at least 6 characters long</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeResetPasswordModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-yellow-600 border border-transparent rounded-md hover:bg-yellow-700">
                            Reset Password
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
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    <div class="px-6 py-4">
                        <h3 class="text-lg font-medium text-red-600">⚠️ Confirm Permanent Deletion</h3>
                        <p class="mt-2 text-sm text-gray-500">Are you sure you want to <strong>permanently delete</strong> this user? This action <strong>cannot be undone</strong> and will remove all user data from the system.</p>
                        
                        <div class="mt-4">
                            <label for="admin_password" class="block text-sm font-medium text-gray-700">
                                Admin Password <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   id="admin_password" 
                                   name="admin_password" 
                                   required
                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                   placeholder="Enter your admin password">
                            <p class="mt-1 text-xs text-red-500">⚠️ Enter your password to confirm permanent deletion</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex justify-end space-x-3">
                        <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                            Permanently Delete User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Password strength checking function
        function checkPasswordStrength(inputId) {
            const password = document.getElementById(inputId).value;
            const strengthBar = document.getElementById(inputId.replace('_password', '_strength_bar'));
            const strengthText = document.getElementById(inputId.replace('_password', '_strength_text'));
            const strengthFeedback = document.getElementById(inputId.replace('_password', '_strength_feedback'));
            
            if (!password) {
                strengthBar.style.width = '0%';
                strengthText.textContent = 'Weak';
                strengthText.className = 'text-sm font-medium text-gray-500';
                strengthFeedback.textContent = '';
                return;
            }
            
            let score = 0;
            let feedback = [];
            
            // Length check
            if (password.length >= 8) score += 1;
            else feedback.push('At least 8 characters');
            
            if (password.length >= 12) score += 1;
            
            // Character variety checks
            if (/[a-z]/.test(password)) score += 1;
            else feedback.push('Lowercase letters');
            
            if (/[A-Z]/.test(password)) score += 1;
            else feedback.push('Uppercase letters');
            
            if (/[0-9]/.test(password)) score += 1;
            else feedback.push('Numbers');
            
            if (/[^a-zA-Z0-9]/.test(password)) score += 1;
            else feedback.push('Special characters');
            
            // Update UI based on score
            let strength, color, width;
            if (score >= 6) {
                strength = 'Strong';
                color = '#10b981'; // green
                width = '100%';
                strengthText.className = 'text-sm font-medium text-green-600';
            } else if (score >= 4) {
                strength = 'Medium';
                color = '#f59e0b'; // yellow
                width = '66%';
                strengthText.className = 'text-sm font-medium text-yellow-600';
            } else {
                strength = 'Weak';
                color = '#ef4444'; // red
                width = '33%';
                strengthText.className = 'text-sm font-medium text-red-600';
            }
            
            strengthBar.style.width = width;
            strengthBar.style.backgroundColor = color;
            strengthText.textContent = strength;
            strengthFeedback.textContent = feedback.length > 0 ? 'Missing: ' + feedback.join(', ') : 'All requirements met!';
        }

        function openCreateModal() {
            document.getElementById('createModal').classList.remove('hidden');
        }

        function closeCreateModal() {
            document.getElementById('createModal').classList.add('hidden');
        }

        function updateFiduciaryTag(formType) {
            const selectId = formType === 'create' ? 'create_department_id' : 'edit_department_id';
            const tagDivId = formType === 'create' ? 'create_fiduciary_tag' : 'edit_fiduciary_tag';
            const badgeId = formType === 'create' ? 'create_fiduciary_badge' : 'edit_fiduciary_badge';
            
            const select = document.getElementById(selectId);
            const tagDiv = document.getElementById(tagDivId);
            const badge = document.getElementById(badgeId);
            
            if (select && select.value) {
                const selectedOption = select.options[select.selectedIndex];
                const fiduciaryType = selectedOption.getAttribute('data-fiduciary') || 'Non-Fiduciary';
                const isFiduciary = (fiduciaryType === 'Fiduciary');
                
                badge.textContent = fiduciaryType;
                badge.className = 'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' + 
                    (isFiduciary ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800');
                tagDiv.classList.remove('hidden');
            } else {
                tagDiv.classList.add('hidden');
            }
        }

        function openEditModal(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_first_name').value = user.first_name;
            document.getElementById('edit_last_name').value = user.last_name;
            document.getElementById('edit_middle_name').value = user.middle_name || '';
            document.getElementById('edit_department_id').value = user.department_id || '';
            document.getElementById('edit_role_id').value = user.role_id;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            document.getElementById('editModal').classList.remove('hidden');
            // Update fiduciary tag when opening edit modal
            setTimeout(() => updateFiduciaryTag('edit'), 100);
        }

        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
        }

        function openResetPasswordModal(userId, userName) {
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_name').textContent = userName;
            document.getElementById('reset_new_password').value = '';
            // Reset strength indicator
            document.getElementById('reset_strength_bar').style.width = '0%';
            document.getElementById('reset_strength_text').textContent = 'Weak';
            document.getElementById('reset_strength_text').className = 'text-sm font-medium text-gray-500';
            document.getElementById('reset_strength_feedback').textContent = '';
            document.getElementById('resetPasswordModal').classList.remove('hidden');
        }

        function closeResetPasswordModal() {
            document.getElementById('resetPasswordModal').classList.add('hidden');
        }

        function deleteUser(userId) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('admin_password').value = ''; // Clear admin password field
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('admin_password').value = ''; // Clear admin password field
        }

        // Close modals when clicking outside - use addEventListener to prevent conflicts
        document.addEventListener('click', function(event) {
            const createModal = document.getElementById('createModal');
            const editModal = document.getElementById('editModal');
            const resetPasswordModal = document.getElementById('resetPasswordModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === createModal) {
                closeCreateModal();
            }
            if (event.target === editModal) {
                closeEditModal();
            }
            if (event.target === resetPasswordModal) {
                closeResetPasswordModal();
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
