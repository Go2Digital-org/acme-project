<?php

declare(strict_types=1);

use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationEloquentRepository;
use Tests\Traits\OptimizedTesting;

uses(OptimizedTesting::class);

beforeEach(function (): void {
    $this->repository = new OrganizationEloquentRepository(new Organization);
    // Disable query logging for performance
    $this->disableQueryLogging();
});

describe('OrganizationEloquentRepository - Basic CRUD Operations', function (): void {
    it('creates an organization successfully', function (): void {
        $uniqueId = uniqid();
        $data = [
            'name' => ['en' => 'Test Organization', 'nl' => 'Test Organisatie', 'fr' => 'Organisation Test'],
            'registration_number' => 'REG' . $uniqueId,
            'tax_id' => 'TAX' . $uniqueId,
            'category' => 'non_profit',
            'email' => 'contact' . $uniqueId . '@test.com',
            'is_active' => true,
            'is_verified' => false,
        ];

        $organization = $this->repository->create($data);

        expect($organization)->toBeInstanceOf(Organization::class)
            ->and($organization->getName())->toBe('Test Organization')
            ->and($organization->registration_number)->toBe('REG' . $uniqueId)
            ->and($organization->is_active)->toBeTrue()
            ->and($organization->exists)->toBeTrue();

        $this->assertDatabaseHas('organizations', [
            'registration_number' => 'REG' . $uniqueId,
        ]);
    });

    it('finds organization by id', function (): void {
        $organization = Organization::factory()->create();

        $found = $this->repository->findById($organization->id);

        expect($found)->not->toBeNull()
            ->and($found->id)->toBe($organization->id);
    });

    it('returns null for non-existent organization', function (): void {
        $found = $this->repository->findById(999999);

        expect($found)->toBeNull();
    });

    it('updates organization by id', function (): void {
        $organization = Organization::factory()->create(['is_verified' => false]);

        $updateData = ['is_verified' => true];
        $result = $this->repository->updateById($organization->id, $updateData);

        expect($result)->toBeTrue();
        $this->assertDatabaseHas('organizations', [
            'id' => $organization->id,
            'is_verified' => true,
        ]);
    });

    it('deletes organization by id', function (): void {
        $organization = Organization::factory()->create();

        $result = $this->repository->delete($organization->id);

        expect($result)->toBeTrue();
        $this->assertSoftDeleted($organization);
    });
});

describe('OrganizationEloquentRepository - Status Queries', function (): void {
    it('finds active organizations', function (): void {
        // Use optimized batch creation
        $this->createOrganizationsBatch(2, ['is_active' => true, 'is_verified' => true]);
        $this->createOrganizationsBatch(1, ['is_active' => false]);

        $result = $this->repository->findActiveOrganizations();

        expect($result)->toBeArray()
            ->and(count($result))->toBeGreaterThanOrEqual(2);

        foreach ($result as $organization) {
            expect($organization->is_active)->toBeTrue()
                ->and($organization->is_verified)->toBeTrue();
        }
    });

    it('finds verified organizations', function (): void {
        // Use optimized batch creation
        $this->createOrganizationsBatch(2, ['is_verified' => true]);
        $this->createOrganizationsBatch(1, ['is_verified' => false]);

        $result = $this->repository->findVerifiedOrganizations();

        expect($result)->toBeArray()
            ->and(count($result))->toBeGreaterThanOrEqual(2);

        foreach ($result as $organization) {
            expect($organization->is_verified)->toBeTrue();
        }
    });
});

describe('OrganizationEloquentRepository - Unique Field Queries', function (): void {
    it('finds organization by name', function (): void {
        $organization = Organization::factory()->create([
            'name' => ['en' => 'Unique Organization', 'nl' => 'Unieke Organisatie', 'fr' => 'Organisation Unique'],
        ]);

        $found = $this->repository->findByName('Unique Organization');

        expect($found)->not->toBeNull()
            ->and($found->getName())->toBe('Unique Organization');
    });

    it('finds organization by registration number', function (): void {
        $uniqueId = uniqid();
        $regNumber = 'REG' . $uniqueId;
        $organization = Organization::factory()->create(['registration_number' => $regNumber]);

        $found = $this->repository->findByRegistrationNumber($regNumber);

        expect($found)->not->toBeNull()
            ->and($found->registration_number)->toBe($regNumber);
    });

    it('returns null for non-existent registration number', function (): void {
        $found = $this->repository->findByRegistrationNumber('NONEXISTENT');

        expect($found)->toBeNull();
    });
});
