<?php
require_once __DIR__ . '/../classes/Notification.php';
require_once __DIR__ . '/../classes/UserActivity.php';

if (!function_exists('broadcastBudgetWorkflowChange')) {
    /**
     * Notify budget workflow recipients and log the activity.
     */
    function broadcastBudgetWorkflowChange(
        Notification $notification,
        UserActivity $activityLogger,
        int $initiatorId,
        string $moduleLabel,
        string $fileName,
        bool $isUpdate,
        array $roles = ['offices', 'supply_office', 'procurement'],
        ?string $contextLabel = null
    ): array {
        $actionVerb = $isUpdate ? 'updated' : 'attached';
        $titleAction = $isUpdate ? 'Updated' : 'Attached';
        $title = "{$moduleLabel} {$titleAction}";
        $contextText = $contextLabel ? " for {$contextLabel}" : '';
        $message = "Budget Office has {$actionVerb} the {$moduleLabel} file{$contextText} ({$fileName}). View the latest version in the system.";

        $notifiedUserIds = $notification->notifyUsersByRoles($roles, $title, $message, 'info');

        $payload = json_encode([
            'module' => $moduleLabel,
            'file_name' => $fileName,
            'action' => $actionVerb,
            'year' => date('Y'),
            'context' => $contextLabel,
        ], JSON_UNESCAPED_SLASHES);

        $activityType = $isUpdate ? 'submission_update' : 'submission_upload';

        if ($initiatorId) {
            $activityLogger->logActivity($initiatorId, $activityType, null, null, $payload);
        }

        foreach ($notifiedUserIds as $targetId) {
            $activityLogger->logActivity($targetId, $activityType, null, null, $payload);
        }

        return $notifiedUserIds;
    }
}

