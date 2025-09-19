<?php

declare(strict_types=1);

namespace Modules\Auth\Infrastructure\Filament\Resources\RoleResource\Pages;

use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Modules\Auth\Infrastructure\Filament\Resources\RoleResource;
use Spatie\Permission\Contracts\Role as RoleContract;

class CreateRole extends CreateRecord
{
    /** @var Collection<int, mixed> */
    public Collection $permissions;

    protected static string $resource = RoleResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->permissions = collect($data)
            ->reject(fn (mixed $permission, string $key): bool => in_array($key, ['name', 'guard_name', 'select_all', Utils::getTenantModelForeignKey()], true))
            ->values()
            ->flatten()
            ->unique();

        if (Arr::has($data, Utils::getTenantModelForeignKey())) {
            return Arr::only($data, ['name', 'guard_name', Utils::getTenantModelForeignKey()]);
        }

        return Arr::only($data, ['name', 'guard_name']);
    }

    protected function afterCreate(): void
    {
        if (! $this->record instanceof Model) {
            return;
        }

        // Cast to Role model for type safety
        /** @var RoleContract&Model $role */
        $role = $this->record;

        $permissionModels = collect();
        $this->permissions->each(function (string $permission) use ($permissionModels): void {
            $permissionModels->push(Utils::getPermissionModel()::firstOrCreate([
                'name' => $permission,
                'guard_name' => $this->data['guard_name'] ?? 'web',
            ]));
        });

        $role->syncPermissions($permissionModels);
    }
}
