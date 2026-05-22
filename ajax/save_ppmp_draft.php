<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_role'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!$data || !isset($data['fiscal_year']) || !isset($data['rows'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
if ($userId <= 0) { echo json_encode(['success'=>false,'message'=>'No user']); exit; }

$draftDir = __DIR__ . '/../uploads/ppmp_drafts/';
if (!is_dir($draftDir)) { mkdir($draftDir, 0777, true); }
$filePath = $draftDir . 'draft_' . $userId . '_' . (int)$data['fiscal_year'] . '.json';

$ok = file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT));
echo json_encode(['success' => (bool)$ok]);
?>


