<?php

declare(strict_types=1);

namespace App\Services;

use App\Infrastructure\Database\Connection;

/**
 * Structured audit logging for security and compliance.
 * Writes to audit_logs table.
 */
final class AuditLogService
{
    public function __construct(private readonly Connection $db) {}

    /**
     * Write an audit log entry.
     *
     * @param int|null    $actorId   User who performed the action (null = anonymous)
     * @param string      $action    Dot-notation event name (e.g. auth.login)
     * @param string|null $entity    Table name of the affected record
     * @param int|null    $entityId  Primary key of affected record
     * @param array       $metadata  Additional context (IP, reason, etc.)
     */
    public function log(
        ?int $actorId,
        string $action,
        ?string $entity = null,
        ?int $entityId = null,
        array $metadata = []
    ): void {
        try {
            $ip = $this->getClientIp();
            $this->db->execute(
                'INSERT INTO audit_logs
                 (actor_id, action, entity_type, entity_id, ip_address, metadata, created_at)
                 VALUES (:actor, :action, :entity, :eid, :ip, :meta, NOW())',
                [
                    ':actor'  => $actorId,
                    ':action' => $action,
                    ':entity' => $entity,
                    ':eid'    => $entityId,
                    ':ip'     => $ip,
                    ':meta'   => !empty($metadata) ? json_encode($metadata) : null,
                ]
            );
        } catch (\Throwable) {
            // Audit log must never crash the application
        }
    }

    private function getClientIp(): string
    {
        $candidates = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($candidates as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = trim(explode(',', $_SERVER[$key])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }
}
