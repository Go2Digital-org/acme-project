<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Organization\Application\Query\FindOrganizationByIdQuery;
use Modules\Organization\Application\Query\FindOrganizationByIdQueryHandler;
use Modules\Organization\Domain\Exception\OrganizationException;
use Modules\Organization\Domain\Model\Organization;
use Modules\Organization\Infrastructure\Laravel\Repository\OrganizationEloquentRepository;

uses(RefreshDatabase::class);

describe('FindOrganizationByIdQueryHandler - Integration Tests', function (): void {
    beforeEach(function (): void {
        $this->repository = new OrganizationEloquentRepository(new Organization);
        $this->handler = new FindOrganizationByIdQueryHandler($this->repository);
    });

    describe('Organization retrieval', function (): void {
        it('finds existing organization by ID', function (): void {
            $organization = Organization::factory()->create([
                'name' => ['en' => 'Test Foundation'],
                'email' => 'test@example.com',
                'category' => 'technology',
                'is_active' => true,
                'is_verified' => true,
            ]);

            $query = new FindOrganizationByIdQuery($organization->id);
            $result = $this->handler->handle($query);

            expect($result)->toBeInstanceOf(Organization::class)
                ->and($result->id)->toBe($organization->id)
                ->and($result->getName())->toBe('Test Foundation')
                ->and($result->email)->toBe('test@example.com')
                ->and($result->category)->toBe('technology')
                ->and($result->is_active)->toBeTrue()
                ->and($result->is_verified)->toBeTrue();
        });

        it('throws exception when organization not found', function (): void {
            $nonExistentId = 999999;

            $query = new FindOrganizationByIdQuery($nonExistentId);

            expect(fn () => $this->handler->handle($query))
                ->toThrow(OrganizationException::class);
        });

        it('finds organization with different statuses', function (): void {
            $activeOrg = Organization::factory()->active()->verified()->create();
            $inactiveOrg = Organization::factory()->unverified()->create(['is_active' => false]);

            // Test finding active organization
            $activeQuery = new FindOrganizationByIdQuery($activeOrg->id);
            $activeResult = $this->handler->handle($activeQuery);

            expect($activeResult->id)->toBe($activeOrg->id)
                ->and($activeResult->is_active)->toBeTrue()
                ->and($activeResult->is_verified)->toBeTrue();

            // Test finding inactive organization
            $inactiveQuery = new FindOrganizationByIdQuery($inactiveOrg->id);
            $inactiveResult = $this->handler->handle($inactiveQuery);

            expect($inactiveResult->id)->toBe($inactiveOrg->id)
                ->and($inactiveResult->is_active)->toBeFalse()
                ->and($inactiveResult->is_verified)->toBeFalse();
        });

        it('finds organization with complete data', function (): void {
            $organization = Organization::factory()->healthcare()->create([
                'registration_number' => 'REG123456',
                'tax_id' => 'TAX789012',
                'website' => 'https://healthcare.org',
                'phone' => '+1-555-0123',
                'address' => '123 Healthcare St',
                'city' => 'Medical City',
                'country' => 'USA',
            ]);

            $query = new FindOrganizationByIdQuery($organization->id);
            $result = $this->handler->handle($query);

            expect($result->registration_number)->toBe('REG123456')
                ->and($result->tax_id)->toBe('TAX789012')
                ->and($result->website)->toBe('https://healthcare.org')
                ->and($result->phone)->toBe('+1-555-0123')
                ->and($result->address)->toBe('123 Healthcare St')
                ->and($result->city)->toBe('Medical City')
                ->and($result->country)->toBe('USA');
        });
    });

    describe('Query validation', function (): void {
        it('handles various organization IDs correctly', function (): void {
            $organizations = Organization::factory()->count(3)->create();

            foreach ($organizations as $organization) {
                $query = new FindOrganizationByIdQuery($organization->id);
                $result = $this->handler->handle($query);

                expect($result->id)->toBe($organization->id);
            }
        });

        it('preserves organization object relationships', function (): void {
            $organization = Organization::factory()->create();

            $query = new FindOrganizationByIdQuery($organization->id);
            $result = $this->handler->handle($query);

            // Verify the returned object has the same ID and can access relationships
            expect($result->id)->toBe($organization->id)
                ->and($result)->toBeInstanceOf(Organization::class)
                ->and($result->campaigns())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });
    });

    describe('Business logic integration', function (): void {
        it('finds organization and verifies business methods work', function (): void {
            $organization = Organization::factory()->verified()->create();

            $query = new FindOrganizationByIdQuery($organization->id);
            $result = $this->handler->handle($query);

            // Test business logic methods work on retrieved object
            expect($result->canCreateCampaigns())->toBeTrue()
                ->and($result->isActive())->toBeTrue()
                ->and($result->getStatus())->toBe('active');
        });

        it('finds unverified organization and verifies status methods', function (): void {
            $organization = Organization::factory()->unverified()->create();

            $query = new FindOrganizationByIdQuery($organization->id);
            $result = $this->handler->handle($query);

            expect($result->canCreateCampaigns())->toBeFalse()
                ->and($result->isActive())->toBeTrue()
                ->and($result->getStatus())->toBe('unverified');
        });
    });

    describe('Edge cases', function (): void {
        it('handles finding organization with minimal data', function (): void {
            $organization = Organization::factory()->create([
                'name' => ['en' => 'Minimal Org'],
                'email' => 'minimal@example.com',
                'phone' => null,
                'address' => null,
                'registration_number' => null,
                'tax_id' => null,
            ]);

            $query = new FindOrganizationByIdQuery($organization->id);
            $result = $this->handler->handle($query);

            expect($result->id)->toBe($organization->id)
                ->and($result->email)->toBe('minimal@example.com')
                ->and($result->phone)->toBeNull()
                ->and($result->address)->toBeNull()
                ->and($result->registration_number)->toBeNull()
                ->and($result->tax_id)->toBeNull();
        });

        it('handles zero and negative IDs appropriately', function (): void {
            $query = new FindOrganizationByIdQuery(0);
            expect(fn () => $this->handler->handle($query))
                ->toThrow(OrganizationException::class);

            $negativeQuery = new FindOrganizationByIdQuery(-1);
            expect(fn () => $this->handler->handle($negativeQuery))
                ->toThrow(OrganizationException::class);
        });
    });
});
