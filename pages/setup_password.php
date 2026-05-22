<?php
session_start();

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/UserActivity.php';
require_once __DIR__ . '/../config/database.php';

$user = new User();
$message = '';
$error = '';
$user_email = '';
$current_password = '';

// Handle password setup form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'setup_password') {
        $user_email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Validate inputs
        if (empty($user_email) || empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $error = 'All fields are required.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'New password and confirm password do not match.';
        } elseif (strlen($new_password) < 6) {
            $error = 'New password must be at least 6 characters long.';
        } else {
            // Verify user by email + current (temporary) password
            $auth = $user->authenticate($user_email, $current_password);
            if (!$auth) {
                $error = 'Invalid email or current password.';
            } else {
                // Update password and clear first-login requirement if set
                $user_id = $auth['id'];
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $clearFlagSql = "UPDATE users SET password_hash = :ph, password_change_required = 0 WHERE id = :id";
                $stmt = getDB()->prepare($clearFlagSql);
                $stmt->bindParam(':ph', $hashed_password);
                $stmt->bindParam(':id', $user_id);
                if ($stmt->execute()) {
                    // Authenticate with new password and set up session
                    $user_data = $user->authenticate($user_email, $new_password);
                    if ($user_data) {
                        // Set up session
                        $_SESSION['user_id'] = $user_data['id'];
                        $_SESSION['user_role'] = $user_data['role_name'];
                        $_SESSION['user_name'] = $user_data['first_name'] . ' ' . $user_data['last_name'];
                        $_SESSION['user_email'] = $user_data['email'];
                        $_SESSION['department_id'] = $user_data['department_id'];
                        $_SESSION['department_name'] = $user_data['dept_name'];
                        $_SESSION['profile_photo'] = $user_data['profile_photo'] ?? '';
                        $_SESSION['login_success'] = true;

                        // Log login activity
                        $userActivity = new UserActivity();
                        $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
                        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
                        $userActivity->logLogin($user_data['id'], $ip_address, $user_agent);

                        // Redirect to appropriate dashboard based on role
                        switch ($user_data['role_name']) {
                            case 'budget':
                                header('Location: admin_dashboard.php');
                                exit;
                            case 'school_admin':
                                header('Location: school_admin_dashboard.php');
                                exit;
                            case 'procurement':
                                header('Location: proc_dashboard.php');
                                exit;
                            case 'offices':
                            case 'supply_office':
                                header('Location: dept_dashboard.php');
                                exit;
                            default:
                                header('Location: dept_dashboard.php');
                                exit;
                        }
                    } else {
                        $error = 'Password updated but failed to log in. Please try logging in manually.';
                    }
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
            }
        }
    }
}

// Prefill from link if available (email & code)
if (isset($_GET['email'])) {
    $user_email = trim($_GET['email']);
}
if (isset($_GET['code'])) {
    $current_password = $_GET['code'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set Up Your Password - BudgetTrack</title>
    <link rel="icon" type="image/png" href="../img/evsu_logo.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'maroon': '#800000',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <img src="../img/evsu_logo.png" alt="EVSU Logo" class="h-8 w-8 mr-3">
                    <h1 class="text-xl font-bold text-maroon">BudgetTrack</h1>
                </div>
                <div class="text-sm text-gray-600">
                    EVSU-Ormoc Campus Budget Management System
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-md mx-auto py-12 px-4">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-maroon bg-opacity-10 mb-4">
                    <i class="fas fa-key text-maroon text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Set Up Your Password</h2>
                <p class="text-gray-600">Welcome to BudgetTrack! Please set up your password to complete your account setup.</p>
            </div>

            <!-- Messages -->
            <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-800"><?php echo htmlspecialchars($message); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-md">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="fas fa-exclamation-circle text-red-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-red-800"><?php echo htmlspecialchars($error); ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Password Setup Form -->
            <form method="POST" action="" class="space-y-6">
                <input type="hidden" name="action" value="setup_password">

                <!-- Email -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <input type="email" 
                           id="email" 
                           name="email" 
                           value="<?php echo htmlspecialchars($user_email); ?>"
                           required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon focus:border-maroon"
                           placeholder="Enter your email address">
                </div>

                <!-- Current (Temporary) Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-2">
                        Current Password (from email) <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" 
                               id="current_password" 
                               name="current_password" 
                               value="<?php echo htmlspecialchars($current_password); ?>"
                               required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-maroon focus:border-maroon">
                        <button type="button" 
                                onclick="togglePassword('current_password')"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center">
                            <i class="fas fa-eye text-gray-400 hover:text-gray-600"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">Use the temporary password sent to your email. It does not expire.</p>
                </div>

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
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-maroon hover:bg-maroon focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-maroon transition duration-150 ease-in-out">
                        <i class="fas fa-check mr-2"></i>
                        Set Up Password
                    </button>
                </div>
            </form>

            <!-- Help Section -->
            <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-md">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Need Help?</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Use the temporary password sent to your email (it does not expire).</li>
                                <li>If you can't find the email, check your spam folder.</li>
                                <li>Contact your administrator if you need assistance.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Link -->
            <div class="mt-6 text-center">
                <p class="text-sm text-gray-600">
                    Already have an account? 
                    <a href="../login.php" class="font-medium text-maroon hover:text-maroon">Sign in here</a>
                </p>
            </div>
        </div>
    </main>

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
            
            if (confirmPassword && newPassword !== confirmPassword) {
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
                alert('Passwords do not match. Please check your input.');
                return false;
            }
        });
    </script>

</body>
</html>
