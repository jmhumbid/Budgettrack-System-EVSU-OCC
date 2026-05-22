<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/EmailService.php';

$message = '';
$error = '';

// Check if this is an AJAX request
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'request_reset':
                $email = trim($_POST['email']);
                
                if (empty($email)) {
                    $error = 'Please enter your email address.';
                } else {
                    try {
                        $conn = getDB();
                        
                        // Check if user exists
                        $query = "SELECT id, first_name, last_name FROM users WHERE email = :email AND is_active = 1";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':email', $email);
                        $stmt->execute();
                        $user = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($user) {
                            // Invalidate any existing unused tokens for this user (optional but good practice)
                            $query = "UPDATE password_reset_tokens SET used = TRUE WHERE user_id = :user_id AND used = FALSE";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':user_id', $user['id']);
                            $stmt->execute();
                            
                            // Generate new reset token
                            $token = bin2hex(random_bytes(32));
                            $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
                            
                            // Store token in database
                            $query = "INSERT INTO password_reset_tokens (user_id, token, expires_at) VALUES (:user_id, :token, :expires_at)";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':user_id', $user['id']);
                            $stmt->bindParam(':token', $token);
                            $stmt->bindParam(':expires_at', $expires_at);
                            
                            if ($stmt->execute()) {
                                // Send email
                                $emailService = new EmailService();
                                $userName = $user['first_name'] . ' ' . $user['last_name'];
                                
                                if ($emailService->sendPasswordResetLink($email, $userName, $token)) {
                                    $message = 'Password reset link has been sent to your email address.';
                                } else {
                                    $error = 'Failed to send password reset email. Please try again later.';
                                }
                            } else {
                                $error = 'Failed to generate reset token. Please try again.';
                            }
                        } else {
                            $error = 'No account found with that email address.';
                        }
                    } catch (Exception $e) {
                        $error = 'An error occurred. Please try again later.';
                        error_log("Password reset error: " . $e->getMessage());
                    }
                }
                
                // If AJAX request, return JSON response
                if ($isAjax) {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => !empty($message),
                        'message' => $message ?: $error
                    ]);
                    exit;
                }
                break;
                
            case 'reset_password':
                $token = $_POST['token'];
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];
                
                if (empty($password) || empty($confirm_password)) {
                    $error = 'Please fill in all fields.';
                } elseif ($password !== $confirm_password) {
                    $error = 'Passwords do not match.';
                } elseif (strlen($password) < 6) {
                    $error = 'Password must be at least 6 characters long.';
                } elseif (!preg_match('/[A-Z]/', $password)) {
                    $error = 'Password must contain at least one uppercase letter.';
                } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                    $error = 'Password must contain at least one special character (!@#$%^&*(),.?":{}|<>)';
                } else {
                    try {
                        $conn = getDB();
                        
                        // Verify token
                        $query = "SELECT prt.user_id, u.email, u.first_name, u.last_name 
                                  FROM password_reset_tokens prt
                                  JOIN users u ON prt.user_id = u.id
                                  WHERE prt.token = :token 
                                  AND prt.expires_at > NOW() 
                                  AND prt.used = FALSE";
                        $stmt = $conn->prepare($query);
                        $stmt->bindParam(':token', $token);
                        $stmt->execute();
                        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($token_data) {
                            // Update password
                            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                            $query = "UPDATE users SET password_hash = :password, password_change_required = FALSE WHERE id = :user_id";
                            $stmt = $conn->prepare($query);
                            $stmt->bindParam(':password', $hashed_password);
                            $stmt->bindParam(':user_id', $token_data['user_id']);
                            
                            if ($stmt->execute()) {
                                // Mark token as used
                                $query = "UPDATE password_reset_tokens SET used = TRUE WHERE token = :token";
                                $stmt = $conn->prepare($query);
                                $stmt->bindParam(':token', $token);
                                $stmt->execute();
                                
                                $message = 'Password has been reset successfully. You can now login with your new password.';
                            } else {
                                $error = 'Failed to update password. Please try again.';
                            }
                        } else {
                            $error = 'Invalid or expired reset token.';
                        }
                    } catch (Exception $e) {
                        $error = 'An error occurred. Please try again later.';
                        error_log("Password reset error: " . $e->getMessage());
                    }
                }
                break;
        }
    }
}

// Check if token is provided in URL
$token = isset($_GET['token']) ? $_GET['token'] : '';
$show_reset_form = false;
$token_error = '';

if (!empty($token)) {
    // Validate token before showing form
    try {
        $conn = getDB();
        $query = "SELECT prt.user_id, prt.used, prt.expires_at, u.email, u.first_name, u.last_name 
                  FROM password_reset_tokens prt
                  JOIN users u ON prt.user_id = u.id
                  WHERE prt.token = :token";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':token', $token);
        $stmt->execute();
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($token_data) {
            // Check if token is used
            if ($token_data['used']) {
                $token_error = 'This password reset link has already been used. Please request a new password reset link.';
            }
            // Check if token is expired
            elseif (strtotime($token_data['expires_at']) < time()) {
                $token_error = 'This password reset link has expired. Please request a new password reset link.';
            } else {
                // Token is valid
                $show_reset_form = true;
            }
        } else {
            $token_error = 'Invalid password reset link. Please request a new password reset link.';
        }
    } catch (Exception $e) {
        $token_error = 'An error occurred while validating the reset link. Please try again.';
        error_log("Token validation error: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset - BudgetTrack</title>
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
                    }
                }
            }
        }
        
        function togglePasswordVisibility(inputId, toggleId) {
            const input = document.getElementById(inputId);
            const eye = document.getElementById(inputId === 'password' ? 'passwordEye' : 'confirmPasswordEye');
            const eyeSlash = document.getElementById(inputId === 'password' ? 'passwordEyeSlash' : 'confirmPasswordEyeSlash');
            
            if (input.type === 'password') {
                input.type = 'text';
                eye.classList.add('hidden');
                eyeSlash.classList.remove('hidden');
            } else {
                input.type = 'password';
                eye.classList.remove('hidden');
                eyeSlash.classList.add('hidden');
            }
        }
    </script>
</head>
<body class="bg-gray-50 font-inter min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full space-y-8 p-8">
        <div class="text-center">
            <div class="mx-auto h-12 w-12 bg-maroon rounded-full flex items-center justify-center">
                <svg class="h-6 w-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                </svg>
            </div>
            <h2 class="mt-6 text-3xl font-bold text-gray-900">
                <?php echo $show_reset_form ? 'Reset Your Password' : 'Forgot Password'; ?>
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                <?php echo $show_reset_form ? 'Enter your new password below' : 'Enter your email to receive a password reset link'; ?>
            </p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($token_error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($token_error); ?>
                <div class="mt-3">
                    <a href="reset_password.php" class="text-red-800 underline font-semibold">Request a new password reset link</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($show_reset_form): ?>
            <!-- Reset Password Form -->
            <form method="POST" class="mt-8 space-y-6">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700">New Password</label>
                        <div class="mt-1 relative">
                            <input id="password" name="password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-maroon focus:border-maroon focus:z-10 sm:text-sm">
                            <button type="button" onclick="togglePasswordVisibility('password', 'passwordToggle')" 
                                    id="passwordToggle"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                <svg id="passwordEye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="passwordEyeSlash" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Must be at least 6 characters, include uppercase and special character</p>
                    </div>
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                        <div class="mt-1 relative">
                            <input id="confirm_password" name="confirm_password" type="password" required 
                                   class="appearance-none relative block w-full px-3 py-2 pr-10 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-maroon focus:border-maroon focus:z-10 sm:text-sm">
                            <button type="button" onclick="togglePasswordVisibility('confirm_password', 'confirmPasswordToggle')" 
                                    id="confirmPasswordToggle"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-500 hover:text-gray-700">
                                <svg id="confirmPasswordEye" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                <svg id="confirmPasswordEyeSlash" class="h-5 w-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-maroon hover:bg-maroon-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-maroon">
                        Reset Password
                    </button>
                </div>
            </form>
        <?php else: ?>
            <!-- Request Reset Form -->
            <form method="POST" class="mt-8 space-y-6">
                <input type="hidden" name="action" value="request_reset">
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                    <input id="email" name="email" type="email" required 
                           class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-maroon focus:border-maroon focus:z-10 sm:text-sm" 
                           placeholder="Enter your email address">
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-maroon hover:bg-maroon-dark focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-maroon">
                        Send Reset Link
                    </button>
                </div>
            </form>
        <?php endif; ?>

        <div class="text-center">
            <a href="../login.php" class="text-maroon hover:text-maroon-dark text-sm">
                Back to Login
            </a>
        </div>
    </div>
</body>
</html>
