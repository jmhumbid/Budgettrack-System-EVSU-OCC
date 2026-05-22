<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    
    $fiduciaryType = $_GET['fiduciary_type'] ?? '';
    $fiscalYear = $_GET['fiscal_year'] ?? date('Y');
    
    if (empty($fiduciaryType) || !in_array($fiduciaryType, ['non-fiduciary', 'fiduciary'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid fiduciary type']);
        exit;
    }
    
    // Determine which table to use
    $tableName = $fiduciaryType === 'non-fiduciary' ? 'cabac_non_fiduciary_entries' : 'cabac_fiduciary_entries';
    
    $stmt = $db->prepare("
        SELECT 
            id,
            particulars,
            sub_particular,
            programs,
            approved_budget,
            total_allotment,
            balance,
            allotment_details,
            created_at,
            updated_at
        FROM `{$tableName}`
        WHERE fiscal_year = ?
        ORDER BY id ASC
    ");
    
    $stmt->execute([$fiscalYear]);
    $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Decode allotment_details JSON
    foreach ($entries as &$entry) {
        if (!empty($entry['allotment_details'])) {
            $entry['allotment_details'] = json_decode($entry['allotment_details'], true);
        } else {
            $entry['allotment_details'] = [];
        }
        // Convert decimal to float for JSON
        $entry['approved_budget'] = (float)$entry['approved_budget'];
        $entry['total_allotment'] = (float)$entry['total_allotment'];
        $entry['balance'] = (float)$entry['balance'];
    }
    
    echo json_encode([
        'success' => true,
        'entries' => $entries,
        'count' => count($entries)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
