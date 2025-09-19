<?php

declare(strict_types=1);

namespace Modules\Shared\Domain\Repository;

use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\Domain\Model\SocialMedia;

interface SocialMediaRepositoryInterface
{
    public function findById(int $id): ?SocialMedia;

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getAll(): Collection;

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getActive(): Collection;

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getActiveOrdered(): Collection;

    public function getByPlatform(string $platform): ?SocialMedia;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SocialMedia;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function updateOrder(int $id, int $order): bool;

    /**
     * @param  array<string, mixed>  $items
     */
    public function reorder(array $items): bool;

    public function getMaxOrder(): int;

    public function activate(int $id): bool;

    public function deactivate(int $id): bool;

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getActiveSocialMedia(): Collection;
}
