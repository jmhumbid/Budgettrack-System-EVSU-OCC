<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

// Check if user is logged in and has budget access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'budget') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    $db = getDB();
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit;
    }
    
    $fiduciaryType = $input['fiduciary_type'] ?? '';
    $entries = $input['entries'] ?? [];
    $fiscalYear = $input['fiscal_year'] ?? date('Y');
    $userId = $_SESSION['user_id'];
    
    if (empty($fiduciaryType) || !in_array($fiduciaryType, ['non-fiduciary', 'fiduciary'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid fiduciary type']);
        exit;
    }
    
    // Determine which table to use
    $tableName = $fiduciaryType === 'non-fiduciary' ? 'cabac_non_fiduciary_entries' : 'cabac_fiduciary_entries';
    
    $db->beginTransaction();
    
    // Delete all existing entries for this fiscal year (replace strategy)
    $deleteStmt = $db->prepare("DELETE FROM `{$tableName}` WHERE fiscal_year = ?");
    $deleteStmt->execute([$fiscalYear]);
    
    // Insert new entries
    if (!empty($entries)) {
        $insertStmt = $db->prepare("
            INSERT INTO `{$tableName}` 
            (particulars, sub_particular, programs, approved_budget, total_allotment, balance, allotment_details, fiscal_year, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($entries as $entry) {
            $particulars = $entry['particulars'] ?? '';
            $subParticular = $entry['sub_particular'] ?? null; // Honoraria values go here
            $programs = $entry['programs'] ?? ''; // Faculty & Staff Development, Research, etc. go here
            $approvedBudget = (float)($entry['approved_budget'] ?? 0);
            $totalAllotment = (float)($entry['total_allotment'] ?? 0);
            $balance = (float)($entry['balance'] ?? 0);
            $allotmentDetails = isset($entry['allotment_details']) ? json_encode($entry['allotment_details']) : null;
            
            $insertStmt->execute([
                $particulars,
                $subParticular,
                $programs,
                $approvedBudget,
                $totalAllotment,
                $balance,
                $allotmentDetails,
                $fiscalYear,
                $userId
            ]);
        }
    }
    
    $db->commit();
    echo json_encode([
        'success' => true, 
        'message' => 'CABAC entries saved successfully',
        'count' => count($entries)
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
