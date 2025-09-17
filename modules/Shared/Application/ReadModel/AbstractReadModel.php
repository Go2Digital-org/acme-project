<?php

declare(strict_types=1);

namespace Modules\Shared\Application\ReadModel;

/**
 * Abstract base class for read models providing common functionality.
 */
abstract class AbstractReadModel implements ReadModelInterface
{
    protected string $version;

    protected int $cacheTtl = 3600; // 1 hour default

    protected bool $cacheable = true;

    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(protected string|int $id, protected array $data, ?string $version = null)
    {
        $this->version = $version ?? (string) time();
    }

    public function getId(): string|int
    {
        return $this->id;
    }

    public function getCacheKey(): string
    {
        $className = str_replace('\\', '_', static::class);

        return sprintf('%s:%s:%s', $className, $this->id, $this->version);
    }

    /**
     * @return array<int, string>
     */
    public function getCacheTags(): array
    {
        $className = class_basename(static::class);

        return [
            strtolower($className),
            sprintf('%s:%s', strtolower($className), $this->id),
        ];
    }

    public function getCacheTtl(): int
    {
        return $this->cacheTtl;
    }

    public function isCacheable(): bool
    {
        return $this->cacheable;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_merge($this->data, [
            'id' => $this->id,
            'version' => $this->version,
        ]);
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get a data field value.
     */
    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get all data.
     *
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the entity ID (alias for getId for compatibility).
     */
    public function getEntityId(): string|int
    {
        return $this->getId();
    }

    /**
     * Get the generated timestamp.
     */
    public function getGeneratedAt(): string
    {
        return date('Y-m-d H:i:s', (int) $this->version);
    }

    /**
     * Set cache TTL for this read model.
     */
    protected function setCacheTtl(int $ttl): void
    {
        $this->cacheTtl = $ttl;
    }

    /**
     * Set whether this read model is cacheable.
     */
    protected function setCacheable(bool $cacheable): void
    {
        $this->cacheable = $cacheable;
    }
}
