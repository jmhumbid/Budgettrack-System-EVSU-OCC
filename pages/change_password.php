<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Check if password change is required
$password_change_required = isset($_SESSION['password_change_required']) && $_SESSION['password_change_required'];

require_once __DIR__ . '/../classes/User.php';

$user = new User();
$message = '';
$error = '';

// Handle password change form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate inputs
        if (empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirm password do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            if ($password_change_required) {
                // For required password changes, skip current password verification
                if ($user->resetPassword($_SESSION['user_id'], $new_password)) {
                    // Clear the password change requirement
                    unset($_SESSION['password_change_required']);
                    $message = 'Password changed successfully. You can now access the system.';
                } else {
                    $error = 'Failed to change password. Please try again.';
                }
            } else {
                // Regular password change requires current password
                if (empty($current_password)) {
                    $error = 'Current password is required.';
                } else {
                    if ($user->changePassword($_SESSION['user_id'], $current_password, $new_password)) {
                        $message = 'Password changed successfully.';
                    } else {
                        $error = 'Current password is incorrect.';
                    }
                }
            }
        }
    }
}

// Get user information
$user_info = $user->getUserById($_SESSION['user_id']);
$user_role = $_SESSION['user_role'];
$username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
$userEmail = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
$departmentId = isset($_SESSION['department_id']) ? (int)$_SESSION['department_id'] : null;
include __DIR__ . '/../components/profile_avatar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - BudgetTrack</title>
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
            <p class="text-sm text-gray-600">Password Management</p>
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
            <?php else: ?>
                <a href="dept_dashboard.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v2H8V5z"></path>
                    </svg>
                    Dashboard
                </a>
            <?php endif; ?>
            <a href="profile.php" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-50 hover:text-maroon">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                Profile
            </a>
            <a href="change_password.php" class="flex items-center px-6 py-3 text-maroon bg-red-50 border-r-4 border-maroon">
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
                    <h1 class="text-2xl font-bold text-white">Change Password</h1>
                    <p class="text-red-100">Update your account password for security</p>
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
                            <?php render_profile_avatar(['classes' => 'bg-maroon text-white font-semibold']); ?>
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
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Change Password Form -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
                <h3 class="text-xl font-bold text-maroon mb-6">
                    <?php echo $password_change_required ? 'Required Password Change' : 'Password Change Form'; ?>
                </h3>
                
                <?php if ($password_change_required): ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-yellow-800">
                                Password Change Required
                            </h3>
                            <div class="mt-2 text-sm text-yellow-700">
                                <p>For security reasons, you must change your password before accessing the system.</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <form method="POST" action="" class="space-y-6">
                    <input type="hidden" name="action" value="change_password">
                    
                    <?php if (!$password_change_required): ?>
                    <!-- Current Password -->
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Current Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="current_password" 
                                   name="current_password" 
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon focus:border-maroon">
                            <button type="button" 
                                    onclick="togglePassword('current_password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 mb-2">
                            New Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="new_password" 
                                   name="new_password" 
                                   required
                                   minlength="6"
                                   onkeyup="checkPasswordStrength('new_password')"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon focus:border-maroon">
                            <button type="button" 
                                    onclick="togglePassword('new_password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                        
                        <!-- Password Strength Indicator -->
                        <div class="mt-2">
                            <div class="flex items-center space-x-2">
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div id="new_strength_bar" class="h-2 rounded-full transition-all duration-300" style="width: 0%; background-color: #ef4444;"></div>
                                </div>
                                <span id="new_strength_text" class="text-sm font-medium text-gray-500">Weak</span>
                            </div>
                            <div id="new_strength_feedback" class="mt-1 text-xs text-gray-500"></div>
                        </div>
                        
                        <p class="mt-1 text-sm text-gray-500">Password must be at least 6 characters long</p>
                    </div>

                    <!-- Confirm New Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">
                            Confirm New Password <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" 
                                   id="confirm_password" 
                                   name="confirm_password" 
                                   required
                                   minlength="6"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon focus:border-maroon">
                            <button type="button" 
                                    onclick="togglePassword('confirm_password')"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <button type="button" 
                                onclick="history.back()" 
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="submit" 
                                class="px-4 py-2 text-sm font-medium text-white bg-maroon border border-transparent rounded-md hover:bg-maroon-dark focus:outline-none focus:ring-2 focus:ring-maroon">
                            <i class="fas fa-key mr-2"></i>
                            Change Password
                        </button>
                    </div>
                </form>
            </div>

            <!-- Password Security Guide -->
            <div class="bg-blue-50 border border-blue-200 rounded-xl p-6 mb-6">
                <h3 class="text-lg font-bold text-blue-900 mb-4">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Password Security Guide
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-blue-800 mb-3">Password Requirements</h4>
                        <ul class="text-sm text-blue-800 space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-check text-blue-600 mr-2"></i>
                                Minimum 6 characters long
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-blue-600 mr-2"></i>
                                Use a combination of letters and numbers
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-blue-600 mr-2"></i>
                                Include uppercase and lowercase letters
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-check text-blue-600 mr-2"></i>
                                Add special characters for extra security
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="font-semibold text-blue-800 mb-3">Best Practices</h4>
                        <ul class="text-sm text-blue-800 space-y-2">
                            <li class="flex items-center">
                                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                                Don't use personal information
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                                Avoid common words or patterns
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                                Don't reuse passwords from other accounts
                            </li>
                            <li class="flex items-center">
                                <i class="fas fa-lightbulb text-blue-600 mr-2"></i>
                                Change your password regularly
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Security Tips -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6">
                <h3 class="text-lg font-bold text-yellow-900 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Important Security Tips
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-lg p-4 border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-lock mr-2"></i>
                            Keep It Private
                        </h4>
                        <p class="text-sm text-yellow-700">Never share your password with anyone, including colleagues or IT support.</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-desktop mr-2"></i>
                            Secure Devices
                        </h4>
                        <p class="text-sm text-yellow-700">Always log out when using shared computers and keep your personal devices secure.</p>
                    </div>
                    <div class="bg-white rounded-lg p-4 border border-yellow-200">
                        <h4 class="font-semibold text-yellow-800 mb-2">
                            <i class="fas fa-sync-alt mr-2"></i>
                            Regular Updates
                        </h4>
                        <p class="text-sm text-yellow-700">Change your password every 3-6 months or immediately if you suspect it's compromised.</p>
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

function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Password confirmation validation
document.getElementById('confirm_password').addEventListener('input', function() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = this.value;
    
    if (newPassword !== confirmPassword) {
        this.setCustomValidity('Passwords do not match');
    } else {
        this.setCustomValidity('');
    }
});

// Form validation
document.querySelector('form').addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('New password and confirm password do not match.');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('New password must be at least 6 characters long.');
        return false;
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
</script>

</body>
</html>
</html>