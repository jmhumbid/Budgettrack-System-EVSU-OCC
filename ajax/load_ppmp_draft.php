<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$fiscal_year = isset($_GET['fiscal_year']) ? (int)$_GET['fiscal_year'] : (int)date('Y');
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) { http_response_code(400); echo json_encode(['success'=>false,'message'=>'No user']); exit; }

$draftDir = __DIR__ . '/../uploads/ppmp_drafts/';
$filePath = $draftDir . 'draft_' . $userId . '_' . $fiscal_year . '.json';
if (!file_exists($filePath)) { http_response_code(404); echo json_encode(['success'=>false,'message'=>'Not found']); exit; }

readfile($filePath);
?>


