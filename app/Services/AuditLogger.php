<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Auth;

final class AuditLogger
{
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        if (!app()->isInstalled()) {
            return;
        }

        app()->db()->execute(
            'INSERT INTO audit_logs (user_id, action, entity_type, entity_id, ip_address, user_agent, metadata)
             VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                Auth::id(),
                $action,
                $entityType,
                $entityId,
                $_SERVER['REMOTE_ADDR'] ?? null,
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 255),
                $metadata ? json_encode($metadata, JSON_THROW_ON_ERROR) : null,
            ]
        );
    }
}
