<?php
/**
 * Authentication helper functions
 */

if (!function_exists('checkAuth')) {
    function checkAuth() {
        session_start();
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            exit;
        }
        
        // Check if user has required role (budget or school_admin)
        if (!in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Forbidden']);
            exit;
        }
    }
}

