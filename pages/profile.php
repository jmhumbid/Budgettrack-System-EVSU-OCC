<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

require_once __DIR__ . '/../classes/User.php';
$user = new User();

// Get user information
$user_info = $user->getUserById($_SESSION['user_id']);
$user_role = $_SESSION['user_role'];
$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
include __DIR__ . '/../components/profile_avatar.php';
$departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - BudgetTrack</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { maroon: '#800000','maroon-dark':'#5a0000' } } } }
    </script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<div class="flex min-h-screen">
    <aside class="w-64 bg-white border-r">
        <div class="p-6 border-b">
            <h2 class="text-2xl font-bold text-maroon">BudgetTrack</h2>
            <p class="text-sm text-gray-600">Profile Management</p>
        </div>
        <nav class="mt-6">
            <?php if ($user_role === 'budget'): ?>
                <a href="admin_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
            <?php elseif ($user_role === 'school_admin'): ?>
                <a href="school_admin_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
            <?php elseif ($user_role === 'procurement'): ?>
                <a href="proc_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
            <?php else: ?>
                <a href="dept_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
            <?php endif; ?>
            <a href="profile.php" class="flex items-center px-6 py-3 text-maroon bg-red-50 border-r-4 border-maroon">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Profile
            </a>
            <a href="change_password.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
                Change Password
            </a>
        </nav>
    </aside>
    <main class="flex-1">
        <header class="bg-maroon border-b border-maroon-dark px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-white">Profile Information</h1>
                    <p class="text-red-100">View and manage your profile details</p>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- Notification Bell -->
                    <?php 
                    require_once __DIR__ . '/../classes/Notification.php';
                    $notification = new Notification();
                    $notifications = $notification->getUserNotifications($_SESSION['user_id'], 10);
                    $unreadCount = $notification->getUnreadCount($_SESSION['user_id']);
                    include __DIR__ . '/../components/notification_bell.php'; 
                    ?>
                    
                    <div class="relative">
                        <button onclick="toggleProfileDropdown()" class="flex items-center space-x-3 bg-gray-50 px-4 py-2 rounded-lg hover:bg-gray-100 transition-colors">
                            <?php render_profile_avatar(['classes' => 'bg-maroon text-white font-semibold', 'size' => 'w-10 h-10']); ?>
                            <div>
                                <div class="font-medium text-gray-900"><?php echo htmlspecialchars($username); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($userEmail); ?></div>
                            </div>
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
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
        </header>

        <section class="p-6">
            <!-- Profile Overview Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center space-x-6">
                    <div class="relative">
                        <div class="w-20 h-20 bg-maroon rounded-full flex items-center justify-center text-white text-2xl font-bold overflow-hidden" id="profilePhotoContainer">
                            <?php if (!empty($user_info['profile_photo']) && file_exists('../' . $user_info['profile_photo'])): ?>
                                <img src="../<?php echo htmlspecialchars($user_info['profile_photo']); ?>" 
                                     alt="Profile Photo" 
                                     class="w-full h-full object-cover rounded-full"
                                     id="profilePhotoImg">
                            <?php else: ?>
                                <span id="profilePhotoInitials"><?php echo strtoupper(substr($user_info['first_name'], 0, 1) . substr($user_info['last_name'], 0, 1)); ?></span>
                            <?php endif; ?>
                        </div>
                        <!-- Photo Upload Overlay -->
                        <div class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-200 cursor-pointer" 
                             onclick="document.getElementById('profilePhotoInput').click()"
                             id="photoUploadOverlay">
                            <i class="fas fa-pen text-white text-lg"></i>
                        </div>
                        <!-- Hidden File Input -->
                        <input type="file" 
                               id="profilePhotoInput" 
                               accept="image/*" 
                               class="hidden" 
                               onchange="uploadProfilePhoto(this)">
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($user_info['first_name'] . ' ' . $user_info['last_name']); ?></h2>
                        <p class="text-gray-600 mb-2"><?php echo htmlspecialchars($user_info['email']); ?></p>
                        <div class="flex items-center space-x-4">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium <?php echo $user_info['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                <i class="fas fa-circle text-xs mr-2"></i>
                                <?php echo $user_info['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <?php 
                                $role_names = [
                                    'budget' => 'Budget/Finance Office',
                                    'school_admin' => 'School Administrator',
                                    'offices' => 'Department Office',
                                    'supply_office' => 'Supply Office'
                                ];
                                echo $role_names[$user_info['role_name']] ?? ucfirst($user_info['role_name']);
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-xl font-bold text-maroon">Personal Information</h3>
                    <button onclick="toggleEditMode()" class="px-4 py-2 bg-maroon text-white rounded-lg hover:bg-maroon-dark transition-colors">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Profile
                    </button>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50" id="first_name_display">
                                <?php echo htmlspecialchars($user_info['first_name']); ?>
                            </div>
                            <button onclick="editField('first_name')" class="p-2 text-gray-400 hover:text-gray-600" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <input type="text" id="first_name_input" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" value="<?php echo htmlspecialchars($user_info['first_name']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50" id="last_name_display">
                                <?php echo htmlspecialchars($user_info['last_name']); ?>
                            </div>
                            <button onclick="editField('last_name')" class="p-2 text-gray-400 hover:text-gray-600" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <input type="text" id="last_name_input" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" value="<?php echo htmlspecialchars($user_info['last_name']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Middle Name</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50" id="middle_name_display">
                                <?php echo htmlspecialchars($user_info['middle_name'] ?: 'N/A'); ?>
                            </div>
                            <button onclick="editField('middle_name')" class="p-2 text-gray-400 hover:text-gray-600" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <input type="text" id="middle_name_input" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" value="<?php echo htmlspecialchars($user_info['middle_name']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Employee ID</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50" id="employee_id_display">
                                <?php echo htmlspecialchars($user_info['employee_id']); ?>
                            </div>
                            <button onclick="editField('employee_id')" class="p-2 text-gray-400 hover:text-gray-600" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <input type="text" id="employee_id_input" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" value="<?php echo htmlspecialchars($user_info['employee_id']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                        <div class="flex items-center space-x-2">
                            <div class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50" id="email_display">
                                <?php echo htmlspecialchars($user_info['email']); ?>
                            </div>
                            <button onclick="editField('email')" class="p-2 text-gray-400 hover:text-gray-600" title="Edit">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                        <input type="email" id="email_input" class="hidden w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon" value="<?php echo htmlspecialchars($user_info['email']); ?>">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Department</label>
                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <?php echo htmlspecialchars($user_info['dept_name'] ?? 'N/A'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Account Information Card -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h3 class="text-xl font-bold text-maroon mb-6">Account Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Account Created</label>
                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <i class="fas fa-calendar-alt text-gray-400 mr-2"></i>
                            <?php echo date('F j, Y g:i A', strtotime($user_info['created_at'])); ?>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Login</label>
                        <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50">
                            <i class="fas fa-sign-in-alt text-gray-400 mr-2"></i>
                            <?php echo $user_info['last_login'] ? date('F j, Y g:i A', strtotime($user_info['last_login'])) : 'Never'; ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Confirm Logout</h3>
                <button onclick="closeLogoutModal()" class="text-gray-400 hover:text-gray-600">✕</button>
            </div>
            <p class="text-gray-600 mb-6">Are you sure you want to logout?</p>
            <div class="flex justify-end gap-3">
                <button onclick="closeLogoutModal()" class="px-4 py-2 bg-gray-300 rounded">Cancel</button>
                <button onclick="performLogout()" class="px-4 py-2 bg-red-600 text-white rounded">Logout</button>
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
    const button = event.target.closest('button[onclick="toggleProfileDropdown()"]');
    
    if (!button && !dropdown.contains(event.target)) {
        dropdown.classList.add('hidden');
    }
});

// Edit field functionality
function editField(fieldName) {
    const display = document.getElementById(fieldName + '_display');
    const input = document.getElementById(fieldName + '_input');
    const editBtn = display.parentElement.querySelector('button');
    
    // Hide display, show input
    display.classList.add('hidden');
    input.classList.remove('hidden');
    input.focus();
    
    // Change edit button to save/cancel
    editBtn.innerHTML = '<i class="fas fa-check"></i>';
    editBtn.onclick = () => saveField(fieldName);
    editBtn.title = 'Save';
    
    // Add cancel button
    const cancelBtn = document.createElement('button');
    cancelBtn.innerHTML = '<i class="fas fa-times"></i>';
    cancelBtn.className = 'p-2 text-gray-400 hover:text-gray-600 ml-1';
    cancelBtn.title = 'Cancel';
    cancelBtn.onclick = () => cancelEdit(fieldName);
    editBtn.parentElement.appendChild(cancelBtn);
}

function saveField(fieldName) {
    const input = document.getElementById(fieldName + '_input');
    const display = document.getElementById(fieldName + '_display');
    const newValue = input.value.trim();
    
    if (newValue === '') {
        alert('This field cannot be empty');
        return;
    }
    
    // Show loading state
    const editBtn = display.parentElement.querySelector('button');
    const originalBtnContent = editBtn.innerHTML;
    editBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    editBtn.disabled = true;
    
    // Send AJAX request to update database
    fetch('../ajax/update_profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            field: fieldName,
            value: newValue
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update display
            display.textContent = newValue === '' ? 'N/A' : newValue;
            
            // Update profile header if name changed
            if (fieldName === 'first_name' || fieldName === 'last_name') {
                // Reload user info to get full name
                fetch('../ajax/get_user_info.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.user_name) {
                            // Update profile header name
                            const profileNameElements = document.querySelectorAll('.font-medium.text-gray-900, .text-white.font-medium');
                            profileNameElements.forEach(el => {
                                if (el.textContent.includes(data.user_name.split(' ')[0]) || el.textContent.includes(data.user_name.split(' ')[1])) {
                                    el.textContent = data.user_name;
                                }
                            });
                        }
                    });
            }
            
            // Hide input, show display
            input.classList.add('hidden');
            display.classList.remove('hidden');
            
            // Reset buttons
            editBtn.innerHTML = '<i class="fas fa-edit"></i>';
            editBtn.onclick = () => editField(fieldName);
            editBtn.title = 'Edit';
            editBtn.disabled = false;
            
            // Remove cancel button
            const cancelBtn = editBtn.parentElement.querySelector('button:last-child');
            if (cancelBtn) cancelBtn.remove();
            
            // Show success message
            showNotification('Profile updated successfully', 'success');
        } else {
            alert('Error: ' + data.message);
            editBtn.innerHTML = originalBtnContent;
            editBtn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating your profile');
        editBtn.innerHTML = originalBtnContent;
        editBtn.disabled = false;
    });
}

function cancelEdit(fieldName) {
    const input = document.getElementById(fieldName + '_input');
    const display = document.getElementById(fieldName + '_display');
    const originalValue = display.textContent;
    
    // Reset input value
    input.value = originalValue === 'N/A' ? '' : originalValue;
    
    // Hide input, show display
    input.classList.add('hidden');
    display.classList.remove('hidden');
    
    // Reset buttons
    const editBtn = display.parentElement.querySelector('button');
    editBtn.innerHTML = '<i class="fas fa-edit"></i>';
    editBtn.onclick = () => editField(fieldName);
    editBtn.title = 'Edit';
    
    // Remove cancel button
    const cancelBtn = editBtn.parentElement.querySelector('button:last-child');
    if (cancelBtn) cancelBtn.remove();
}

// Show notification
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg ${
        type === 'success' ? 'bg-green-500 text-white' : 
        type === 'error' ? 'bg-red-500 text-white' : 
        'bg-blue-500 text-white'
    }`;
    notification.textContent = message;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
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

// Profile photo upload functionality
function uploadProfilePhoto(input) {
    const file = input.files[0];
    if (!file) return;
    
    console.log('File selected:', file.name, file.type, file.size);
    
    // Validate file type
    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!allowedTypes.includes(file.type)) {
        alert('Please select a valid image file (JPEG, PNG, or GIF).');
        return;
    }
    
    // Validate file size (max 5MB)
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (file.size > maxSize) {
        alert('File size too large. Maximum size is 5MB.');
        return;
    }
    
    // Show loading state
    const container = document.getElementById('profilePhotoContainer');
    const originalContent = container.innerHTML;
    container.innerHTML = '<div class="w-full h-full flex items-center justify-center"><i class="fas fa-spinner fa-spin text-white text-xl"></i></div>';
    
    // Create FormData
    const formData = new FormData();
    formData.append('profile_photo', file);
    
    console.log('Uploading file...');
    
    // Upload photo
    fetch('../ajax/upload_profile_photo.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response received:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            // Update profile photo
            const container = document.getElementById('profilePhotoContainer');
            
            if (data.photo_path) {
                // Show uploaded photo - use relative path
                container.innerHTML = `<img src="../${data.photo_path}" alt="Profile Photo" class="w-full h-full object-cover rounded-full" id="profilePhotoImg">`;
                
                // Update profile avatar in header dropdown immediately
                const headerAvatar = document.querySelector('button[onclick="toggleProfileDropdown()"] img, button[onclick="toggleProfileDropdown()"] .bg-maroon');
                if (headerAvatar) {
                    if (headerAvatar.tagName === 'IMG') {
                        headerAvatar.src = `../${data.photo_path}`;
                    } else {
                        // Replace initials with image
                        const avatarContainer = headerAvatar.closest('button').querySelector('.bg-maroon, .bg-white');
                        if (avatarContainer) {
                            avatarContainer.innerHTML = `<img src="../${data.photo_path}" alt="Profile" class="w-full h-full object-cover rounded-full">`;
                        }
                    }
                }
            } else {
                // Show initials if no photo
                container.innerHTML = `<span id="profilePhotoInitials"><?php echo strtoupper(substr($user_info['first_name'], 0, 1) . substr($user_info['last_name'], 0, 1)); ?></span>`;
            }
            
            showNotification('Profile photo updated successfully', 'success');
        } else {
            // Restore original content on error
            container.innerHTML = originalContent;
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Upload error:', error);
        // Restore original content on error
        container.innerHTML = originalContent;
        alert('An error occurred while uploading the photo: ' + error.message);
    });
}
</script>

</body>
</html>