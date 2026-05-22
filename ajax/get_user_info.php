<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

require_once __DIR__ . '/../classes/User.php';

try {
    $user = new User();
    $userInfo = $user->getUserById($_SESSION['user_id']);
    
    if ($userInfo) {
        echo json_encode([
            'success' => true,
            'user_name' => $userInfo['first_name'] . ' ' . $userInfo['last_name'],
            'user_email' => $userInfo['email'],
            'profile_photo' => $userInfo['profile_photo'] ?? ''
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>

