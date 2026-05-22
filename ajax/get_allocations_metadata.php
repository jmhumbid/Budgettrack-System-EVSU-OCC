<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['budget', 'school_admin'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : date('Y');
    $db = getDB();
    
    // Check if table exists
    $tableCheck = $db->query("SHOW TABLES LIKE 'allocation_grid_metadata'");
    if ($tableCheck->rowCount() === 0) {
        echo json_encode(['headers' => null, 'columns' => null]);
        exit;
    }
    
    $stmt = $db->prepare("SELECT headers, columns FROM allocation_grid_metadata WHERE fiscal_year = :year");
    $stmt->execute([':year' => $year]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo json_encode([
            'headers' => json_decode($row['headers'], true),
            'columns' => json_decode($row['columns'], true)
        ]);
    } else {
        echo json_encode(['headers' => null, 'columns' => null]);
    }
} catch (Exception $e) {
    echo json_encode(['headers' => null, 'columns' => null]);
}
?>

