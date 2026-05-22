<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../classes/FileSubmission.php';
require_once __DIR__ . '/../config/database.php';

$fileSubmission = new FileSubmission();

// Get submission ID from POST data
$submission_id = $_POST['submission_id'] ?? $_POST['file_id'] ?? null;

if (!$submission_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No file ID provided']);
    exit;
}

try {
    // Get file information from database
    $conn = getDB();
    $query = "SELECT file_name, file_path, file_type, submission_type FROM file_submissions WHERE id = :submission_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':submission_id', $submission_id);
    $stmt->execute();
    
    $file = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$file) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found']);
        exit;
    }
    
    $file_path = $file['file_path'];
    $file_name = $file['file_name'];
    $file_type = $file['file_type'];
    
    // Check if file exists
    if (!file_exists($file_path)) {
        http_response_code(404);
        echo json_encode(['error' => 'File not found on server']);
        exit;
    }
    
    // Set headers for file download
    header('Content-Type: ' . $file_type);
    header('Content-Disposition: attachment; filename="' . $file_name . '"');
    header('Content-Length: ' . filesize($file_path));
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Clear any output buffer
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    // Output the file
    readfile($file_path);
    exit;
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
    exit;
}
?>
