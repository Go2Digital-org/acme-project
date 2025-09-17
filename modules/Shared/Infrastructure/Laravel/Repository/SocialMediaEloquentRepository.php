<?php

declare(strict_types=1);

namespace Modules\Shared\Infrastructure\Laravel\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Domain\Model\SocialMedia;
use Modules\Shared\Domain\Repository\SocialMediaRepositoryInterface;

class SocialMediaEloquentRepository implements SocialMediaRepositoryInterface
{
    public function __construct(
        private readonly SocialMedia $model,
    ) {}

    public function findById(int $id): ?SocialMedia
    {
        return $this->model->find($id);
    }

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getAll(): Collection
    {
        return $this->model->ordered()->get();
    }

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getActive(): Collection
    {
        return $this->model->active()->get();
    }

    /**
     * @return Collection<int, SocialMedia>
     */
    public function getActiveOrdered(): Collection
    {
        return $this->model->active()->ordered()->get();
    }

    public function getByPlatform(string $platform): ?SocialMedia
    {
        return $this->model->byPlatform($platform)->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): SocialMedia
    {
        return $this->model->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data) > 0;
    }

    public function delete(int $id): bool
    {
        return $this->model->where('id', $id)->delete() > 0;
    }

    public function updateOrder(int $id, int $order): bool
    {
        return $this->model->where('id', $id)->update(['order' => $order]) > 0;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    public function reorder(array $items): bool
    {
        return DB::transaction(function () use ($items): true {
            foreach ($items as $item) {
                if (isset($item['id'], $item['order'])) {
                    $this->updateOrder((int) $item['id'], (int) $item['order']);
                }
            }

            return true;
        });
    }

    public function getMaxOrder(): int
    {
        return \DB::table('social_media')->max('order') ?? 0;
    }

    public function activate(int $id): bool
    {
        return $this->model->where('id', $id)->update(['is_active' => true]) > 0;
    }

    public function deactivate(int $id): bool
    {
        return $this->model->where('id', $id)->update(['is_active' => false]) > 0;
    }

    /**
     * Get active social media links (alias for getActiveOrdered).
     *
     * @return Collection<int, SocialMedia>
     */
    public function getActiveSocialMedia(): Collection
    {
        return $this->getActiveOrdered();
    }
}
