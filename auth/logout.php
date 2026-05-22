<?php
session_start();

require_once __DIR__ . '/../classes/UserActivity.php';

// Log logout activity before destroying session
if (isset($_SESSION['user_id'])) {
    $userActivity = new UserActivity();
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $userActivity->logLogout($_SESSION['user_id'], $ip_address, $user_agent);
}

session_destroy();

header('Location: ../login.php');
exit;
?>