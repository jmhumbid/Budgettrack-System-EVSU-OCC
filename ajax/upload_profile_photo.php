<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/User.php';

$user = new User();
$existingUser = $user->getUserById($_SESSION['user_id']);
$oldPhotoPath = $existingUser['profile_photo'] ?? null;
$response = ['success' => false, 'message' => ''];

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Debug: Log what we received
    error_log('POST data: ' . print_r($_POST, true));
    error_log('FILES data: ' . print_r($_FILES, true));

    if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
        $errorCode = $_FILES['profile_photo']['error'] ?? 'No file uploaded';
        throw new Exception('No file uploaded or upload error. Error code: ' . $errorCode);
    }

    $file = $_FILES['profile_photo'];
    
    // Validate file type
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $fileType = mime_content_type($file['tmp_name']);
    
    if (!in_array($fileType, $allowedTypes)) {
        throw new Exception('Invalid file type: ' . $fileType . '. Only JPEG, PNG, and GIF images are allowed.');
    }
    
    // Validate file size (max 5MB)
    $maxSize = 5 * 1024 * 1024; // 5MB in bytes
    if ($file['size'] > $maxSize) {
        throw new Exception('File size too large: ' . $file['size'] . ' bytes. Maximum size is 5MB.');
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/profile_photos/';
    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
            throw new Exception('Failed to create upload directory');
        }
    }
    
    // Check if directory is writable
    if (!is_writable($uploadDir)) {
        throw new Exception('Upload directory is not writable');
    }
    
    // Generate unique filename
    $userId = $_SESSION['user_id'];
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = 'profile_' . $userId . '_' . time() . '.' . $fileExtension;
    $filePath = $uploadDir . $fileName;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        throw new Exception('Failed to save uploaded file to: ' . $filePath);
    }
    
    // Update user profile photo in database
    $relativePath = 'uploads/profile_photos/' . $fileName;
    if ($user->updateProfilePhoto($userId, $relativePath)) {
        // Delete old profile photo if it exists
        if (!empty($oldPhotoPath) && file_exists(__DIR__ . '/../' . $oldPhotoPath)) {
            @unlink(__DIR__ . '/../' . $oldPhotoPath);
        }
        
        // Update session immediately
        $_SESSION['profile_photo'] = $relativePath;
        
        // Get updated user info for name
        $updatedUser = $user->getUserById($userId);
        if ($updatedUser) {
            $_SESSION['user_name'] = $updatedUser['first_name'] . ' ' . $updatedUser['last_name'];
        }
        
        $response = [
            'success' => true, 
            'message' => 'Profile photo updated successfully',
            'photo_path' => $relativePath
        ];
    } else {
        throw new Exception('Failed to update profile photo in database');
    }
    
} catch (Exception $e) {
    error_log('Upload error: ' . $e->getMessage());
    $response = ['success' => false, 'message' => $e->getMessage()];
}

header('Content-Type: application/json');
echo json_encode($response);
?>
