<?php
/**
 * Session Helper Functions
 * Provides robust session management to prevent unexpected logouts
 */

if (!function_exists('checkSession')) {
    /**
     * Check if user session is valid
     * @return bool True if session is valid, false otherwise
     */
    function checkSession() {
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Check if session has required keys
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            return false;
        }
        
        // Check if session is not expired (optional: add session timeout check here)
        // For now, just check if keys exist
        
        return true;
    }
}

if (!function_exists('requireLogin')) {
    /**
     * Require user to be logged in, redirect to login if not
     * @param string $redirectUrl Optional redirect URL (default: ../login.php)
     */
    function requireLogin($redirectUrl = '../login.php') {
        if (!checkSession()) {
            // Clear any partial session data
            $_SESSION = array();
            
            // Destroy session cookie if it exists
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            header('Location: ' . $redirectUrl);
            exit;
        }
    }
}

if (!function_exists('requireRole')) {
    /**
     * Require user to have specific role(s)
     * @param array|string $allowedRoles Role(s) allowed to access
     * @param string $redirectUrl Optional redirect URL
     */
    function requireRole($allowedRoles, $redirectUrl = '../login.php') {
        requireLogin($redirectUrl);
        
        $userRole = $_SESSION['user_role'] ?? null;
        $allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];
        
        if (!in_array($userRole, $allowedRoles, true)) {
            // Redirect to appropriate dashboard instead of login
            switch ($userRole) {
                case 'budget':
                    header('Location: admin_dashboard.php');
                    break;
                case 'school_admin':
                    header('Location: school_admin_dashboard.php');
                    break;
                case 'procurement':
                    header('Location: proc_dashboard.php');
                    break;
                default:
                    header('Location: dept_dashboard.php');
                    break;
            }
            exit;
        }
    }
}
?>

