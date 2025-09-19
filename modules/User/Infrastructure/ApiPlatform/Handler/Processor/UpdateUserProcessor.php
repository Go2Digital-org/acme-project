<?php

declare(strict_types=1);

namespace Modules\User\Infrastructure\ApiPlatform\Handler\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use Modules\User\Infrastructure\ApiPlatform\Resource\UserResource;
use Modules\User\Infrastructure\Laravel\Models\User;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * @implements ProcessorInterface<UserResource, UserResource>
 */
final readonly class UpdateUserProcessor implements ProcessorInterface
{
    /**
     * @param  array<string, mixed>  $uriVariables
     * @param  array<string, mixed>  $context
     */
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): UserResource
    {
        if (! isset($uriVariables['id']) || ! is_numeric($uriVariables['id'])) {
            throw new NotFoundHttpException('Invalid user ID');
        }

        $id = (int) $uriVariables['id'];

        $user = User::find($id);

        if (! $user) {
            throw new NotFoundHttpException('User not found');
        }

        // Update only non-null fields
        $updateData = [];

        if (($data->name ?? null) !== null) {
            $updateData['name'] = ($data->name ?? null);
        }

        if (($data->email ?? null) !== null) {
            $updateData['email'] = ($data->email ?? null);
        }

        if (isset($data->department)) {
            $updateData['department'] = $data->department;
        }

        if (isset($data->job_title)) {
            $updateData['job_title'] = $data->job_title;
        }

        if (($data->phone ?? null) !== null) {
            $updateData['phone'] = ($data->phone ?? null);
        }

        if (($data->address ?? null) !== null) {
            $updateData['address'] = ($data->address ?? null);
        }

        if (isset($data->preferred_language)) {
            $updateData['preferred_language'] = $data->preferred_language;
        }

        if (isset($data->timezone)) {
            $updateData['timezone'] = $data->timezone;
        }

        if (isset($data->manager_email)) {
            $updateData['manager_email'] = $data->manager_email;
        }

        if (isset($data->hire_date)) {
            $updateData['hire_date'] = $data->hire_date;
        }

        if ($updateData !== []) {
            $user->update($updateData);
        }

        $refreshedUser = $user->fresh();

        if ($refreshedUser === null) {
            throw new NotFoundHttpException('User was deleted during update');
        }

        return UserResource::fromModel($refreshedUser);
    }
}
