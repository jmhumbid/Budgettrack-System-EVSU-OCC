<?php
session_start();

require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/UserActivity.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = 'Please enter both email and password.';
        header('Location: ../login.php');
        exit;
    }

    $user = new User();
    $user_data = $user->authenticate($email, $password);

    if ($user_data) {
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

        // Check if password change is required
        if (isset($user_data['password_change_required']) && $user_data['password_change_required']) {
            $_SESSION['password_change_required'] = true;
            header('Location: ../pages/change_password.php');
            exit;
        }

        switch ($user_data['role_name']) {
            case 'budget':
                header('Location: ../pages/admin_dashboard.php');
                break;
            case 'school_admin':
                header('Location: ../pages/school_admin_dashboard.php');
                break;
            case 'procurement':
                header('Location: ../pages/proc_dashboard.php');
                break;
            case 'offices':
            case 'supply_office':
                header('Location: ../pages/dept_dashboard.php');
                break;
            default:
                header('Location: ../pages/dept_dashboard.php');
                break;
        }
        exit;
    } else {
        $_SESSION['login_error'] = 'Invalid email or password. Please try again.';
        header('Location: ../login.php');
        exit;
    }
}

header('Location: ../login.php');
exit;
?>


