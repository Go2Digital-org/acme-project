<?php

declare(strict_types=1);

namespace Modules\Audit\Application\ReadModel;

use Modules\Shared\Application\ReadModel\AbstractReadModel;

/**
 * Audit log read model optimized for displaying audit information.
 */
class AuditLogReadModel extends AbstractReadModel
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        int $auditId,
        array $data,
        ?string $version = null
    ) {
        parent::__construct($auditId, $data, $version);
        $this->setCacheTtl(3600); // 1 hour for audit logs
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        return array_merge(parent::getCacheTags(), [
            'audit_log',
            'audit:' . $this->id,
            'auditable:' . $this->getAuditableType() . ':' . $this->getAuditableId(),
            'user:' . $this->getUserId(),
        ]);
    }

    // Basic Audit Information
    public function getAuditId(): int
    {
        return (int) $this->id;
    }

    public function getEvent(): string
    {
        return $this->get('event', '');
    }

    public function getFormattedEvent(): string
    {
        return ucfirst($this->getEvent());
    }

    public function getAuditableType(): string
    {
        return $this->get('auditable_type', '');
    }

    public function getAuditableId(): int
    {
        return (int) $this->get('auditable_id', 0);
    }

    public function getAuditableTypeName(): string
    {
        $type = $this->getAuditableType();

        return class_basename($type);
    }

    // User Information
    public function getUserId(): ?int
    {
        $userId = $this->get('user_id');

        return $userId ? (int) $userId : null;
    }

    public function getUserType(): ?string
    {
        return $this->get('user_type');
    }

    public function getUserName(): ?string
    {
        return $this->get('user_name');
    }

    public function getUserEmail(): ?string
    {
        return $this->get('user_email');
    }

    // Change Information
    /**
     * @return array<string, mixed>|null
     */
    public function getOldValues(): ?array
    {
        return $this->get('old_values');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getNewValues(): ?array
    {
        return $this->get('new_values');
    }

    /**
     * @return array<string, array{old: mixed, new: mixed, changed: bool}>
     */
    public function getDiff(): array
    {
        $diff = [];
        $oldValues = $this->getOldValues() ?? [];
        $newValues = $this->getNewValues() ?? [];
        $allKeys = array_unique(array_merge(array_keys($oldValues), array_keys($newValues)));

        foreach ($allKeys as $key) {
            $diff[$key] = [
                'old' => $oldValues[$key] ?? null,
                'new' => $newValues[$key] ?? null,
                'changed' => ($oldValues[$key] ?? null) !== ($newValues[$key] ?? null),
            ];
        }

        return $diff;
    }

    public function getChangeCount(): int
    {
        return count($this->getDiff());
    }

    public function hasChanges(): bool
    {
        return $this->getChangeCount() > 0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getChangedFields(): array
    {
        $diff = $this->getDiff();

        return array_filter($diff, fn ($item) => $item['changed']);
    }

    // Context Information
    public function getUrl(): ?string
    {
        return $this->get('url');
    }

    public function getIpAddress(): ?string
    {
        return $this->get('ip_address');
    }

    public function getUserAgent(): ?string
    {
        return $this->get('user_agent');
    }

    public function getTags(): ?string
    {
        return $this->get('tags');
    }

    /**
     * @return array<string>
     */
    public function getTagsArray(): array
    {
        $tags = $this->getTags();

        if ($tags === null || $tags === '' || $tags === '0') {
            return [];
        }

        return array_map('trim', explode(',', $tags));
    }

    public function hasTag(string $tag): bool
    {
        return in_array($tag, $this->getTagsArray());
    }

    // Timestamps
    public function getCreatedAt(): ?string
    {
        return $this->get('created_at');
    }

    public function getFormattedCreatedAt(): string
    {
        $createdAt = $this->getCreatedAt();

        if ($createdAt === null || $createdAt === '' || $createdAt === '0') {
            return '';
        }

        $timestamp = strtotime($createdAt);

        return $timestamp !== false ? date('Y-m-d H:i:s', $timestamp) : '';
    }

    public function getRelativeTime(): string
    {
        $createdAt = $this->getCreatedAt();

        if ($createdAt === null || $createdAt === '' || $createdAt === '0') {
            return '';
        }

        $timestamp = strtotime($createdAt);
        $now = time();
        $diff = $now - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }

        if ($diff < 3600) {
            $minutes = floor($diff / 60);

            return $minutes . ' minute' . ($minutes != 1 ? 's' : '') . ' ago';
        }

        if ($diff < 86400) {
            $hours = floor($diff / 3600);

            return $hours . ' hour' . ($hours != 1 ? 's' : '') . ' ago';
        }

        $days = floor($diff / 86400);

        return $days . ' day' . ($days != 1 ? 's' : '') . ' ago';
    }

    // Auditable Entity Information
    public function getAuditableName(): ?string
    {
        return $this->get('auditable_name');
    }

    public function getAuditableTitle(): ?string
    {
        return $this->get('auditable_title');
    }

    public function getAuditableDescription(): ?string
    {
        return $this->get('auditable_description');
    }

    // Browser/Device Information
    public function getBrowserName(): ?string
    {
        $userAgent = $this->getUserAgent();

        if ($userAgent === null || $userAgent === '' || $userAgent === '0') {
            return null;
        }

        // Simple browser detection
        if (str_contains($userAgent, 'Chrome')) {
            return 'Chrome';
        }

        if (str_contains($userAgent, 'Firefox')) {
            return 'Firefox';
        }

        if (str_contains($userAgent, 'Safari')) {
            return 'Safari';
        }

        if (str_contains($userAgent, 'Edge')) {
            return 'Edge';
        }

        return 'Unknown';
    }

    public function getOperatingSystem(): ?string
    {
        $userAgent = $this->getUserAgent();

        if ($userAgent === null || $userAgent === '' || $userAgent === '0') {
            return null;
        }

        // Simple OS detection
        if (str_contains($userAgent, 'Windows')) {
            return 'Windows';
        }

        if (str_contains($userAgent, 'Mac OS')) {
            return 'macOS';
        }

        if (str_contains($userAgent, 'Linux')) {
            return 'Linux';
        }

        if (str_contains($userAgent, 'Android')) {
            return 'Android';
        }

        if (str_contains($userAgent, 'iOS')) {
            return 'iOS';
        }

        return 'Unknown';
    }

    // Security Analysis
    public function isSuspiciousActivity(): bool
    {
        // Multiple failed login attempts
        if ($this->getEvent() === 'failed_login') {
            return true;
        }

        // Unusual IP address patterns
        $ipAddress = $this->getIpAddress();
        if ($ipAddress && (! str_starts_with($ipAddress, '127.') && ! str_starts_with($ipAddress, '192.168.'))) {
            // Could add more sophisticated IP analysis here
        }

        // Changes to sensitive fields
        $sensitiveFields = ['password', 'email', 'permissions', 'roles'];
        $changedFields = array_keys($this->getChangedFields());

        return (bool) array_intersect($sensitiveFields, $changedFields);
    }

    public function getRiskLevel(): string
    {
        if ($this->isSuspiciousActivity()) {
            return 'high';
        }

        if ($this->getEvent() === 'updated' && $this->getChangeCount() > 5) {
            return 'medium';
        }

        return 'low';
    }

    // Formatted Output
    /**
     * @return array<string, mixed>
     */
    public function toSummaryArray(): array
    {
        return [
            'id' => $this->getAuditId(),
            'event' => $this->getEvent(),
            'formatted_event' => $this->getFormattedEvent(),
            'auditable_type' => $this->getAuditableType(),
            'auditable_type_name' => $this->getAuditableTypeName(),
            'auditable_id' => $this->getAuditableId(),
            'auditable_name' => $this->getAuditableName(),
            'user_id' => $this->getUserId(),
            'user_name' => $this->getUserName(),
            'user_email' => $this->getUserEmail(),
            'change_count' => $this->getChangeCount(),
            'has_changes' => $this->hasChanges(),
            'created_at' => $this->getCreatedAt(),
            'formatted_created_at' => $this->getFormattedCreatedAt(),
            'relative_time' => $this->getRelativeTime(),
            'ip_address' => $this->getIpAddress(),
            'browser_name' => $this->getBrowserName(),
            'operating_system' => $this->getOperatingSystem(),
            'risk_level' => $this->getRiskLevel(),
            'is_suspicious' => $this->isSuspiciousActivity(),
            'tags' => $this->getTagsArray(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toDetailedArray(): array
    {
        return array_merge($this->toSummaryArray(), [
            'old_values' => $this->getOldValues(),
            'new_values' => $this->getNewValues(),
            'diff' => $this->getDiff(),
            'changed_fields' => $this->getChangedFields(),
            'url' => $this->getUrl(),
            'user_agent' => $this->getUserAgent(),
            'auditable_title' => $this->getAuditableTitle(),
            'auditable_description' => $this->getAuditableDescription(),
        ]);
    }
}
