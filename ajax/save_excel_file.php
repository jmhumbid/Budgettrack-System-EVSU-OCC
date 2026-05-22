<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File parameter required']);
    exit;
}

$relativePath = urldecode($_GET['file']);
// Normalize path - remove any directory traversal attempts
$relativePath = str_replace('..', '', $relativePath);
$relativePath = ltrim($relativePath, '/\\');
$relativePath = str_replace('\\', '/', $relativePath);

// Security check - ensure file path starts with uploads/
if (strpos($relativePath, 'uploads/') !== 0 && strpos($relativePath, 'uploads\\') !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid file path']);
    exit;
}

$filePath = __DIR__ . '/../' . $relativePath;
$filePath = realpath($filePath);

if (!$filePath || !file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// Additional security check
$uploadsDir = realpath(__DIR__ . '/../uploads/');
if (!$uploadsDir || strpos($filePath, $uploadsDir) !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid file path']);
    exit;
}

// Check if user has permission to edit (budget/admin roles or file owner)
$userRole = $_SESSION['user_role'] ?? '';
$canEdit = in_array($userRole, ['budget', 'school_admin']);

if (!$canEdit) {
    // Check if user is the owner (for department files)
    // This would require checking file ownership in database
    // For now, only allow budget/admin to edit
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You do not have permission to edit this file']);
    exit;
}

// Get the uploaded file from FormData
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$uploadedFile = $_FILES['file'];
$fileExt = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

// Validate file type
if (!in_array($fileExt, ['xlsx', 'xls'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Unsupported file type. Only Excel files (.xlsx, .xls) can be saved.']);
    exit;
}

// Validate uploaded file type
$uploadedExt = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));
if (!in_array($uploadedExt, ['xlsx', 'xls'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file type uploaded']);
    exit;
}

try {
    // Move uploaded file to target location (overwrite existing)
    if (move_uploaded_file($uploadedFile['tmp_name'], $filePath)) {
        // Set proper permissions
        chmod($filePath, 0644);
        
        echo json_encode([
            'success' => true,
            'message' => 'File saved successfully',
            'file' => $relativePath
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to save file. Please check file permissions.'
        ]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error saving file: ' . $e->getMessage()
    ]);
}
?>

