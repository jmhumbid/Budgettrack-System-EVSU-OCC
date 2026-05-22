<?php
// One-time script to add Procurement Office role with basic permissions
require_once __DIR__ . '/../config/database.php';
$db = getDB();

$db->beginTransaction();
try {
    // Add role if not exists
    $roleId = null;
    $stmt = $db->prepare("SELECT id FROM roles WHERE role_name = 'procurement'");
    $stmt->execute();
    $roleId = $stmt->fetchColumn();
    if (!$roleId) {
        $db->prepare("INSERT INTO roles (role_name, role_description) VALUES ('procurement','Procurement Office - limited sidebar and access')")->execute();
        $roleId = $db->lastInsertId();
    }

    // Grant view-only style permissions similar to departments but without create_ppmp
    $permStmt = $db->prepare("SELECT id, permission_name FROM permissions");
    $permStmt->execute();
    $perms = $permStmt->fetchAll(PDO::FETCH_KEY_PAIR); // id=>name

    $wanted = [
        'view_budget','view_ppmp','view_reports','view_dashboard','view_notifications','view_departments','view_users','view_allocations','view_announcements'
    ];
    $insert = $db->prepare("INSERT IGNORE INTO role_permissions (role_id, permission_id) VALUES (:rid, :pid)");
    foreach ($perms as $id => $name) {
        if (in_array($name, $wanted, true)) {
            $insert->execute([':rid'=>$roleId, ':pid'=>$id]);
        }
    }

    $db->commit();
    echo "Procurement role ensured. ID: {$roleId}\n";
} catch (Throwable $e) {
    $db->rollBack();
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}


